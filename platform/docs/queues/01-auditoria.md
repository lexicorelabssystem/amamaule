# Fase 1 — Auditoría del proyecto

Fecha: 2026-07-10
Entorno: local (XAMPP, Windows)
Alcance: solo lectura. No se modificó ningún archivo de código, configuración ni base de datos.

## 1. Versiones reales instaladas

| Componente | Versión detectada | Comando usado |
|---|---|---|
| PHP (CLI) | 8.4.19 (NTS, Visual C++ 2022 x64) | `php -v` |
| Laravel Framework | 12.63.0 | `php artisan --version` |
| Restricción en composer.json | `"php": "^8.2"`, `"laravel/framework": "^12.0"` | `composer.json` |

Nota: la versión de PHP mostrada es la del binario `php` disponible en el PATH (CLI). XAMPP suele tener un PHP separado para el módulo de Apache (`xampp/php/php.ini`); no se verificó si ambos son la misma instalación. Esto se debe confirmar antes de tocar `php.ini` en fases posteriores.

## 2. Configuración de colas (queue) actual

Archivo: `config/queue.php`

- Conexión por defecto: `QUEUE_CONNECTION=database` (confirmado en `.env` y `.env.example`).
- Conexiones disponibles ya definidas por Laravel: `sync`, `database`, `beanstalkd`, `sqs`, `redis`, `deferred`, `background`, `failover`. El bloque `redis` **ya existe** en `config/queue.php` y en `config/database.php` (líneas 146-181), apuntando a `REDIS_HOST=127.0.0.1`, `REDIS_PORT=6379`, `REDIS_CLIENT=phpredis`.
- Tabla de jobs: migración base `0001_01_01_000002_create_jobs_table.php` (estándar de Laravel, no debe modificarse).
- `failed_jobs`: driver `database-uuids` (estándar).

## 3. Estado real de Redis (hallazgo importante)

- **La extensión `redis` de PHP NO está cargada** en el CLI (`php -m` no la lista; módulos activos: bcmath, curl, gd, mbstring, mysqlnd, pdo_mysql, pdo_sqlite, sqlite3, etc., pero no `redis`).
- **El paquete `predis/predis` NO está en `composer.json` ni en `composer.lock`.**
- No se encontró binario `redis-server` en el PATH del sistema.
- Es decir: aunque `.env`/`.env.example` y `config/database.php` ya tienen placeholders para Redis (`REDIS_CLIENT=phpredis`, host/puerto), **Redis no está instalado ni funcional todavía en este entorno**. Esto es trabajo pendiente explícito para la Fase 4 (Redis local).

## 4. Cache y sesión

- `CACHE_STORE=database` (no Redis todavía).
- No se inspeccionó `config/session.php` en detalle (fuera de alcance de esta fase; no afecta colas).

## 5. Flujos de carga y procesamiento de archivos identificados

Se identificaron **tres flujos distintos** que procesan archivos, todos ejecutados hoy de forma síncrona dentro del ciclo de petición HTTP (controlador → respuesta):

### 5.1 Importación de artistas por planilla (CSV/XLSX/XLS)

- Ruta: `POST /imports` → `ImportController::store()` (`app/Http/Controllers/ImportController.php:42`)
- Dependencia: `phpoffice/phpspreadsheet` (`^5.8`, ya en `composer.json`)
- Flujo actual:
  1. `ArtistImportParser::parse($file)` (`app/Services/ArtistImportParser.php`) carga el **archivo completo** en memoria con `PhpSpreadsheet` (`IOFactory::createReaderForFile` o `CsvReader`) y recorre **todas las filas** de forma síncrona, dentro de la request HTTP, antes de responder.
  2. El archivo original se guarda en `Storage` (`imports/...`).
  3. Se crea el registro `Import` (tabla `imports`).
  4. Se crea **un registro `ImportRow` por cada fila** con un `foreach` + `->create()` individual (N inserts separados, no bulk insert) — también síncrono, dentro de la misma request.
  5. Solo el **procesamiento** (crear `User`/`Artist` por fila, enviar notificación con credenciales) está en una cola: `ImportArtistsJob::dispatch($import)` (`app/Http/Controllers/ImportController.php:105`), disparado en una acción **separada** (`POST /imports/{import}/process`), y el Job implementa `ShouldQueue` correctamente (`app/Jobs/ImportArtistsJob.php`).
- **Conclusión clave**: ya existe un patrón de Job para el procesamiento pesado (crear artistas), pero el **parseo del archivo y la creación masiva de `ImportRow`** (paso más costoso para archivos grandes) sigue ocurriendo de forma síncrona en el controlador durante la subida (`store()`), no en un Job. Con `QUEUE_CONNECTION=database`, además, el `ImportArtistsJob` depende de que exista un worker corriendo (`php artisan queue:work` o `queue:listen`); en `composer.json` el script `dev` sí levanta `queue:listen` en paralelo (línea 51), pero no hay evidencia de Supervisor/proceso persistente para producción — fuera de alcance de esta fase (regla: no tocar producción).

### 5.2 Subida de imágenes (Media: avatar, cover, galería de actividades/obras)

- Rutas: `POST artists/{artist}/avatar`, `POST artists/{artist}/cover`, `POST activities/{activity}/media` (hasta 10 imágenes), `POST artworks/{artwork}/media`
- Controladores: `ArtistMediaController`, `ActivityMediaController`, `ArtworkMediaController` — todos inyectan `MediaUploadService` y llaman `->upload()` **de forma síncrona** dentro de la acción del controlador.
- `MediaUploadService::upload()` (`app/Services/MediaUploadService.php`):
  - Usa `Intervention\Image` (driver GD) para leer la imagen (`$manager->read($file->getRealPath())`).
  - Genera **dos** variantes por imagen: principal (1400×1050, calidad 85) y miniatura (400×300, calidad 80), cada una con `scaleDown()` + `encodeByExtension()` — operaciones de CPU/memoria.
  - En `ActivityMediaController::store()` esto se repite hasta **10 veces por request** (`images.*`, máx. 10), es decir hasta 20 operaciones de codificación de imagen antes de devolver respuesta al usuario.
- **Conclusión clave**: este es actualmente el cuello de botella más directo para "la carga tarda": el usuario espera a que el servidor procese y guarde (disco) todas las imágenes antes de recibir respuesta. No hay Job, no hay estado `QUEUED/PROCESSING`, no hay forma de saber el progreso.

### 5.3 Publicación en WordPress (incluye subida de imagen destacada)

- Ruta: `POST activities/{activity}/publish` (vía `ActivityController::publish()`, `app/Http/Controllers/ActivityController.php:169`) y `WordPressPublicationController` (`publishArtist`, `publishActivity`, `unpublishArtist`, `unpublishActivity`).
- `WordPressPublicationService::publishActivity()` → `ensureFeaturedMedia()` → `WordPressPublisher::uploadMedia()` (`app/Services/WordPressPublisher.php:51`): hace `file_get_contents()` de la imagen local y un `POST` HTTP síncrono al sitio WordPress remoto (`Http::...->post(...)`), con timeout configurado (`config('wordpress.timeout', 15)` segundos) — es decir, la request del usuario puede quedar bloqueada hasta 15s (o más, sumando el `createPost`/`updatePost`) esperando una llamada de red externa.
- No implementa `ShouldQueue`; es una llamada directa dentro del controlador.
- **Conclusión clave**: dependencia de red externa ejecutada de forma síncrona en el hilo de la petición HTTP; candidato a asincronía, pero **fuera del alcance textual de la solicitud actual** (la tarea menciona explícitamente "OCR, PDF, Excel, reportes" como lo que no debe correr en controladores; no menciona WordPress). Se documenta como hallazgo, decisión de incluirlo o no se deja para la Fase 3 (plan).

### 5.4 Exportación de planillas (Excel/CSV)

- `ExportController` (`artists`, `activities`) usa `SpreadsheetExportService` con `phpoffice/phpspreadsheet` para generar `Xlsx`/`Csv` — es una `StreamedResponse`, no un upload, pero también es generación de archivo pesada síncrona en el hilo de la petición. Se documenta como hallazgo secundario (fuera del enunciado "carga y procesamiento de archivos" que se enfoca en *carga*, pero relevante si se pregunta por rendimiento general).

## 6. Límites de PHP relevantes (CLI actual)

| Directiva | Valor |
|---|---|
| `upload_max_filesize` | 2M |
| `post_max_size` | 8M |
| `memory_limit` | 128M |
| `max_execution_time` | 0 (sin límite en CLI) |

Estos valores son los del PHP usado por CLI (el mismo binario usado para `php artisan`). No se verificó si el `php.ini` que usa el módulo de Apache de XAMPP (el que realmente atiende las requests HTTP de subida) tiene los mismos valores — puede ser un `php.ini` distinto. Se deja como punto a confirmar en la Fase 2/4, sin modificar nada todavía.

## 7. Jobs, tablas y estados ya existentes (no crear nada duplicado)

- Job existente: `App\Jobs\ImportArtistsJob` (`ShouldQueue`, `Queueable`, `timeout = 300`).
- Tablas existentes relacionadas: `imports`, `import_rows` (migraciones `2026_07_08_150814_*` y `2026_07_08_150815_*`), `media` (migración `2026_07_08_155953_create_media_table.php`), `jobs` y `failed_jobs` (migraciones base de Laravel).
- Estados ya modelados:
  - `Import::STATUS_*` = `pending`, `processing`, `completed`, `failed`, `cancelled` (`app/Models/Import.php`).
  - `ImportRow::STATUS_*` = `pending`, `success`, `error`, `skipped` (`app/Models/ImportRow.php`).
  - `WordPressPublication::STATUS_*` (visto en `WordPressPublicationService`) = `pending`, `draft`, `published`, `failed`.
  - **La tabla `media` no tiene ningún campo de estado** (`status`, `queued`, etc.) — hoy un registro `Media` solo existe una vez que el archivo ya fue procesado y guardado por completo, de forma síncrona. Esto confirma que el flujo de imágenes (5.2) es el que necesita los nuevos estados `QUEUED/PROCESSING/COMPLETED/FAILED` que pide la tarea, ya que `imports`/`import_rows` ya tienen su propio esquema de estados (no debe tocarse, según regla 11 de no modificar migraciones antiguas).

## 8. Comandos ejecutados durante esta auditoría (todos de solo lectura)

```
php -v
php artisan --version
php -m
php -i | grep upload_max_filesize|post_max_size|max_execution_time|memory_limit
grep -E "QUEUE_|REDIS_|DB_CONNECTION|DB_QUEUE|CACHE_" .env
grep -E "QUEUE_|REDIS_|DB_CONNECTION|DB_QUEUE|CACHE_" .env.example
grep -n "'redis'" -A 30 config/database.php
```

No se ejecutó ningún comando de escritura (`migrate`, `queue:work`, `composer require`, etc.) en esta fase.

## 9. Resumen ejecutivo

1. **No hay Redis funcional todavía** en el entorno local (ni extensión PHP ni predis ni servidor) pese a que la configuración base ya lo contempla.
2. **La cola sí existe y ya se usa correctamente** para un caso (creación de artistas vía `ImportArtistsJob`), pero corre sobre driver `database`, no Redis.
3. **El cuello de botella principal de "carga que demora"** son las subidas de imágenes (`MediaUploadService`, procesamiento con Intervention Image de hasta 10 archivos x 2 variantes cada uno, todo síncrono en el controlador) y, en menor medida, el parseo síncrono de planillas grandes en `ImportController::store()` antes de encolar el procesamiento.
4. No existe hoy ningún estado `QUEUED/PROCESSING/COMPLETED/FAILED` asociado a `Media`; sí existen patrones equivalentes ya en `Import`/`ImportRow`/`WordPressPublication` que pueden servir de referencia de convención para la Fase 5.
5. No se tocó código, configuración ni base de datos en esta fase.

---

# Fase 2 — Diagnóstico del flujo de archivos

Fecha: 2026-07-10
Alcance: solo lectura, continuación directa de la Fase 1. No se modificó ningún archivo de código, configuración ni base de datos.

## 1. Traza completa del flujo de subida de imágenes (caso más crítico)

Ejemplo concreto: `POST activities/{activity}/media` (hasta 10 imágenes), pero el mismo patrón aplica a `artists/{artist}/avatar`, `artists/{artist}/cover` y `artworks/{artwork}/media`.

Secuencia real, todo dentro del **mismo request-response HTTP**, todo en el **mismo hilo/proceso PHP-FPM o Apache mod_php** que atiende al usuario:

1. Middleware de auth + `$this->authorize('update', $activity)` (rápido).
2. `$request->validate([...])` — valida tipo MIME, extensión y tamaño máx. 5120 KB por archivo, máx. 10 archivos (`ActivityMediaController.php:21-24`). La validación de Laravel ya requiere que **todo el archivo** haya sido subido al servidor (PHP ya lo dejó en `tmp` antes de que el controlador se ejecute) — esto es inherente a PHP/HTTP, no es el problema.
3. `foreach ($request->file('images') as $file) { $this->mediaUpload->upload($file, $activity, 'gallery'); }` — bucle síncrono, **una imagen a la vez**, sin paralelismo.
4. Dentro de `MediaUploadService::upload()` (`app/Services/MediaUploadService.php:23-58`), por cada imagen:
   - `$manager->read($file->getRealPath())` — decodifica la imagen completa en memoria (driver **GD**, confirmado con `php -m`; no hay Imagick instalado).
   - `storeImage(...)` para la variante principal: `scaleDown(1400x1050)` + `encodeByExtension(quality: 85)` + escritura a disco (`Storage::disk('public')->put(...)`).
   - `storeImage(...)` otra vez para la miniatura: `scaleDown(400x300)` + `encodeByExtension(quality: 80)` + otra escritura a disco.
   - Un `INSERT` a la tabla `media` (`$model->media()->create([...])`, con un `SELECT MAX(order)` previo — 2 queries por imagen).
5. Si `$isCover`, un `UPDATE` adicional (`clearOtherCovers`).
6. Recién cuando el `foreach` completo termina (hasta 10 imágenes × 2 encodes × 1-2 queries), el controlador hace `redirect()` y el navegador del usuario recibe respuesta.

**Punto de bloqueo confirmado**: el usuario que sube 10 fotos espera, en un solo request HTTP, la decodificación + 2 redimensionamientos/reencodes + 2 escrituras a disco **por cada una de las 10 imágenes**, de forma estrictamente secuencial. No hay forma de que el usuario vea progreso ni de que la request se libere antes de terminar todo el lote. Con `post_max_size=8M` y `max:5120` (5MB) por imagen, un lote de 10 imágenes de ~4-5MB cada una ya se acerca al límite de subida, y el procesamiento de imágenes de ese tamaño con GD (más lento que Imagick para reencode a calidad 85) es la explicación más directa y verificable en código de la lentitud reportada.

## 2. Traza completa del flujo de importación de artistas

`POST /imports` → `ImportController::store()`:

1. `ArtistImportParser::parse($file)` — con `PhpSpreadsheet`, para `.xlsx`/`.xls` usa `IOFactory::createReaderForFile()` (autodetecta formato, más lento que especificar el reader) y `setReadDataOnly(true)`. Carga el **libro completo** en memoria y itera **todas las filas** (`extractRows`), fila por fila, celda por celda, de forma síncrona.
2. `$file->store('imports')` — guarda el archivo original (I/O de disco adicional, después de ya haberlo leído completo en el paso 1; el archivo se lee dos veces: una desde el `tmp` de PHP para parsear, otra implícita al moverlo a `storage/app/imports`).
3. `Import::create([...])` — 1 insert.
4. `foreach ($result['rows'] as $row) { $import->rows()->create([...]); }` — **un INSERT individual por fila**, sin `insert()` masivo ni `chunk`. Para una planilla de, por ejemplo, 2.000 filas, son 2.000 queries individuales antes de poder responder al usuario.
5. Solo después de todo esto se redirige a `imports.show`.

El **procesamiento real** (crear `User`/`Artist`, notificar credenciales) sí está correctamente en `ImportArtistsJob` (`ShouldQueue`, `chunkById(100, ...)`, actualiza progreso cada 50 filas) — este job es un buen ejemplo a replicar para media, **pero se dispara en una acción separada** (`POST imports/{import}/process`), no automáticamente tras la subida, y depende de que exista un worker corriendo (`php artisan queue:work`), cosa que **hoy no está corriendo** en este entorno local (se verificó con `tasklist`/`ps aux`: ningún proceso `queue:work` ni `queue:listen` activo en este momento).

**Punto de bloqueo confirmado**: paso 1 (parseo completo) y paso 4 (N inserts individuales) ocurren en el request de subida, no en un Job. Para archivos grandes (miles de filas), esto es el cuello de botella de "la carga demora", separado del cuello de botella de "el procesamiento demora" (que ya está resuelto parcialmente con `ImportArtistsJob`).

## 3. Evidencia real en logs (no solo análisis de código)

Se revisó `storage/logs/laravel.log` (518 KB) y `storage/logs/serve-output.log`. Hallazgo relevante:

```
GuzzleHttp\Exception\ConnectException: cURL error 28: Operation timed out
after 15012 milliseconds with 0 bytes received for
http://localhost/wordpress/wp-json/wp/v2/posts/3264
```

Esta traza aparece **3 veces** en el log. Confirma en la práctica (no solo por lectura de código) el hallazgo 5.3 de la Fase 1: la publicación en WordPress (`WordPressPublicationService::publish()` → `WordPressPublisher::updatePost()`) se ejecuta de forma síncrona dentro de la request HTTP del usuario (ej. `ActivityController::publish()`), y cuando el sitio WordPress no responde, **el usuario queda esperando ~15 segundos completos** antes de recibir un error. Esto no es hipotético: ya ocurrió en este entorno.

No se encontraron en el log errores de `Maximum execution time` ni `Allowed memory size` asociados a `MediaUploadService` o `ArtistImportParser` — es decir, hasta ahora no se ha llegado a un timeout duro en esos flujos, pero el análisis de código (secciones 1 y 2) muestra que el diseño actual sí acumula trabajo síncrono proporcional al tamaño del lote/archivo, lo que es un riesgo de timeout a medida que crezcan los archivos reales (no solo una cuestión de percepción de lentitud).

## 4. Infraestructura local verificada

- `public/storage` → symlink correcto hacia `storage/app/public` (`php artisan storage:link` ya ejecutado). No es un problema pendiente.
- Driver de imagen: **GD** (confirmado, `php -m` no lista `imagick`). Intervention Image ya está configurado para usarlo (`app/Services/MediaUploadService.php:20`, `new Driver` de `Intervention\Image\Drivers\Gd`).
- Ningún worker de cola (`queue:work`/`queue:listen`) está corriendo actualmente en este entorno. El script `composer dev` sí lo levanta junto al servidor (`composer.json:51`), pero eso depende de que el desarrollador use ese script; no hay garantía de que esté activo siempre.
- No se detectó Redis corriendo (coherente con el hallazgo de la Fase 1).

## 5. Cobertura de pruebas existente sobre estos flujos (referencia para no romper contratos)

Ya existen tests que **debemos seguir pasando** una vez se introduzcan cambios (regla 6: conservar funcionalidades y contratos existentes):

- `tests/Feature/ArtistMediaTest.php`: sube avatar/cover, valida reemplazo del anterior, rechazo de no-imágenes, autorización. Usa `Storage::fake('public')` y hace las aserciones **inmediatamente después del `post()`**, es decir, asume que `Media` queda creado y accesible de forma síncrona al terminar la request.
- `tests/Feature/ImportUploadTest.php`: sube CSV, valida creación de `Import`/`import_rows` con estado `pending` inmediatamente tras el `post()` (síncrono).
- `tests/Feature/ImportProcessingTest.php`: prueba `ImportArtistsJob` directamente (`ImportArtistsJob::dispatch($import)` sin `Queue::fake()`, por lo que corre en el mismo proceso gracias a `QUEUE_CONNECTION=sync` definido **solo en `phpunit.xml`** para el entorno de testing, `phpunit.xml:33`) — esto es válido y estándar en Laravel para tests; **no** es el mismo `sync` que la regla 8 prohíbe como "solución final" (esa regla aplica al entorno real/`.env`, que ya usa `database`, no `sync`).
- `tests/Feature/WordPressPublicationFlowTest.php` y `WordPressPublisherTest.php`: usan `Http::fake(...)` para no depender de un WordPress real.
- No existen tests para `ArtworkMediaController` ni para `ActivityMediaController` (`ArtistMediaTest` solo cubre artista, no actividad ni obra) — vacío de cobertura a tener en cuenta si se refactoriza ese flujo en fases posteriores.

Importante para la Fase 3 en adelante: si el upload de imágenes pasa a ser asíncrono (Job + estado `QUEUED`), los tests de `ArtistMediaTest.php` que hoy asumen `Media` disponible inmediatamente tras el `post()` **dejarán de ser válidos tal cual** y deberán adaptarse (ej. usar `Queue::fake()` y verificar que se despachó el Job, o ejecutar el Job sincrónicamente en el test) — esto se documentará como parte del plan, no se toca código todavía.

## 6. Diagnóstico — priorización para la Fase 3

Con base en evidencia de código + evidencia real de logs:

1. **Prioridad 1 — Subida de imágenes (`MediaUploadService` + los 3 controladores que la usan)**: es el flujo más directamente descrito por "carga y procesamiento de archivos" en el enunciado, no tiene ningún mecanismo de cola hoy, y el procesamiento (Intervention Image) es exactamente el tipo de trabajo pesado que la regla 7 pide sacar de los controladores.
2. **Prioridad 2 — Parseo de planillas en `ImportController::store()`**: el procesamiento pesado (crear artistas) ya está en un Job; falta mover el **parseo** y la creación masiva de `ImportRow` fuera del request de subida.
3. **Fuera de alcance explícito por ahora — Publicación en WordPress**: aunque hay evidencia real de timeouts de 15s, el enunciado de la tarea no la menciona (menciona "OCR, PDF, Excel, reportes"). Se deja registrada como hallazgo para que el usuario decida si se incluye en el plan de la Fase 3 o se trata aparte.

## 7. Comandos ejecutados en esta fase (todos de solo lectura)

```
test -L public/storage && echo "SYMLINK EXISTS"
tasklist | grep -i php
ps aux | grep -i "queue:work|queue:listen"
php -m | grep -i "gd|imagick"
grep -c "ConnectException|cURL error 28|Operation timed out" storage/logs/laravel.log
grep -n "QUEUE_CONNECTION|CACHE_STORE|DB_CONNECTION" phpunit.xml
```

No se ejecutó ningún comando de escritura.
