# Cambios realizados

Registro fase a fase de cambios reales aplicados (no de lo planeado; eso vive en `02-plan-implementacion.md`). Fases 1-3 fueron de solo lectura/documentación, sin cambios de código — ver ahí para contexto.

## Fase 4 — Redis local (2026-07-10)

### Contexto y hallazgo previo relevante

Al intentar levantar Redis con Docker se detectó que el **puerto 6379 ya estaba ocupado por un contenedor de otro proyecto** (`cordillera-redis`, del proyecto `cordillera_saas_version_final`, corriendo desde hace 12 días, no relacionado con AMA Plataforma). **No se tocó ese contenedor.** Se optó por mapear el Redis de este proyecto a un puerto distinto (`6380` en el host) para evitar cualquier mezcla de datos/colas entre proyectos.

### Cambios de infraestructura local (no versionados en git)

- Se inició Docker Desktop (estaba instalado pero detenido).
- Se creó un contenedor Redis dedicado a este proyecto:
  ```
  docker run -d --name ama-redis -p 6380:6379 redis:7-alpine
  ```
  - Nombre: `ama-redis`.
  - Puerto host: `6380` → puerto interno del contenedor `6379` (el contenedor en sí no cambia, solo el mapeo hacia el host).
  - Sin volumen persistente (los datos se pierden si se borra el contenedor; es aceptable para colas de trabajo en desarrollo local, donde los jobs fallidos quedan igualmente registrados en `failed_jobs` de la base de datos).
  - Este contenedor **no arranca automáticamente** al reiniciar Windows/Docker Desktop; si se reinicia el equipo, hay que volver a correr `docker start ama-redis` (o el `docker run` de nuevo si se borró).

### Cambios de código/dependencias (sí versionados)

- `composer.json` / `composer.lock`: se agregó `predis/predis` (`^3.5`, instalado `v3.5.1`) como dependencia de producción.
  - Comando usado: `composer require predis/predis --ignore-platform-req=ext-zip`.
  - **Nota sobre el flag `--ignore-platform-req=ext-zip`**: no está relacionado con Redis. Se descubrió que la extensión `zip` de PHP no está habilitada en este entorno (`php -m` no la lista), y Composer bloqueaba cualquier `require` nuevo porque `phpoffice/phpspreadsheet` (ya instalado, sin relación con esta tarea) la necesita. Se usó el flag únicamente para no dejar bloqueada la instalación de `predis/predis`; **no se modificó `php.ini` ni se tocó nada de la funcionalidad de Excel existente.** Este hallazgo se deja registrado como pendiente separado (ver `progreso.md`), porque sugiere que la exportación a `.xlsx` (`SpreadsheetExportService`) podría estar fallando ya hoy en este entorno, independientemente de esta tarea.

### Cambios de configuración

`.env` (no versionado en git, solo entorno local):

| Variable | Antes | Después |
|---|---|---|
| `QUEUE_CONNECTION` | `database` | `redis` |
| `REDIS_CLIENT` | `phpredis` | `predis` |
| `REDIS_PORT` | `6379` | `6380` |
| `REDIS_HOST` | `127.0.0.1` | sin cambio |
| `REDIS_PASSWORD` | `null` | sin cambio |

No se modificó `.env.example` en esta fase — se deja para cuando el equipo decida el estándar del proyecto (Memurai/Docker/WSL2 pueden variar por desarrollador, así que forzar `REDIS_PORT=6380` en el ejemplo compartido podría no ser correcto para todos).

No se modificó `config/queue.php` ni `config/database.php`: los bloques de conexión `redis` ya existían de antes (confirmado en la Fase 1) y no requirieron cambios de código, solo activarse vía `.env`.

### Verificación end-to-end realizada

1. `Redis::connection()->ping()` vía Predis → respondió `PONG`.
2. Se descartó una prueba inicial con `php artisan tinker --execute="dispatch(function(){...})"` porque falló al serializar el closure definido en código evaluado por PsySH (limitación conocida de tinker con `--execute`, no un problema de Redis/cola) — el job quedó marcado `FAIL` en el log del worker.
3. Se repitió la prueba con un script PHP real (archivo, no eval'd): el job se encoló correctamente en Redis (`LLEN` sobre la clave `plataforma-ama-database-queues:default` mostró `1`), se procesó con `php artisan queue:work redis --once` (resultado `DONE`), y el efecto esperado (escritura de un archivo) ocurrió correctamente. Se verificó también que, tras procesar, la cola en Redis quedó vacía.
4. Se limpiaron todos los artefactos de la prueba (archivo de test, script temporal).
5. Se corrió la suite completa de tests (`php artisan test`): **142 passed, 1 skipped** (el test de integración real con WordPress, que requiere `RUN_WORDPRESS_INTEGRATION=true` explícito — comportamiento esperado, no relacionado con este cambio). Ningún test se rompió por el cambio de `.env`, porque `phpunit.xml` fuerza `QUEUE_CONNECTION=sync` para el entorno de testing independientemente del `.env` local (confirmado en la Fase 2).

### Estado al cierre de la Fase 4

Redis está operativo localmente y Laravel ya despacha/consume jobs reales a través de él. Ningún Job de la aplicación (`ImportArtistsJob` u otro) fue modificado todavía — seguirán funcionando igual que antes, solo que ahora corren sobre el driver `redis` en vez de `database`. El trabajo de la Fase 5 en adelante (migración de `media`, nuevo Job, controladores, endpoint) no se ha tocado.

## Fase 5 — Tablas y estados (2026-07-10)

Contenido mostrado al usuario y aprobado antes de crear o ejecutar nada (petición explícita: "muéstrame el contenido exacto que vas a crear").

### Migración nueva

Archivo: `database/migrations/2026_07_10_170000_add_status_columns_to_media_table.php` — no modifica la migración original `2026_07_08_155953_create_media_table.php` (regla 11), solo la altera con `Schema::table`:

```php
public function up(): void
{
    Schema::table('media', function (Blueprint $table) {
        $table->string('file_path')->nullable()->change();
        $table->string('status')->default('completed');
        $table->string('pending_path')->nullable();
        $table->string('error_message')->nullable();
    });
}

public function down(): void
{
    Schema::table('media', function (Blueprint $table) {
        $table->dropColumn(['status', 'pending_path', 'error_message']);
        $table->string('file_path')->nullable(false)->change();
    });
}
```

`status` queda con default `'completed'` para que las filas ya existentes (creadas por el flujo síncrono actual) sigan siendo válidas sin migrar datos. Advertencia documentada: el `down()` fallaría si se hace rollback mientras existen filas con `file_path` nulo (ej. un `Media` que quedó `queued` sin completarse) — aceptable para desarrollo local, no se resolvió porque no hay ese escenario hoy (nada crea `file_path` nulo todavía; eso llega en la Fase 7).

Se corrió `php artisan migrate --pretend` antes de aplicarla, para confirmar el SQL exacto:
```sql
alter table `media` modify `file_path` varchar(255) null
alter table `media` add `status` varchar(255) not null default 'completed'
alter table `media` add `pending_path` varchar(255) null
alter table `media` add `error_message` varchar(255) null
```
Coincidió exactamente con lo planeado. Se aplicó con `php artisan migrate` (204.99ms).

### Cambios en `app/Models/Media.php`

- Se agregaron las constantes `STATUS_QUEUED`, `STATUS_PROCESSING`, `STATUS_COMPLETED`, `STATUS_FAILED` y el array estático `$statuses`, mismo estilo que `Import`/`ImportRow` (convención ya existente en el proyecto, ver Fase 1 punto 7).
- Se agregaron `status`, `pending_path`, `error_message` a `$fillable`.
- Se agregaron los métodos `isQueued()`, `isProcessing()`, `isCompleted()`, `isFailed()`, mismo patrón que los métodos equivalentes de `Import` (`isPending()`, `isProcessing()`, `isCompleted()`).
- **No se tocaron** `fullUrl()`, `thumbnailUrl()` ni `deleteFiles()` — hoy asumen `file_path`/`thumbnail_path` presentes. Como en esta fase nada crea todavía un `Media` con `file_path` nulo (eso empieza en la Fase 7), estos métodos siguen siendo seguros de usar por ahora. Queda anotado como pendiente explícito para la Fase 6/7: antes de que los controladores empiecen a crear filas `queued`, hay que decidir si estos métodos deben guardarse contra `file_path`/`pending_path` nulos, o si la responsabilidad de no llamarlos en ese estado queda en las vistas (`$media->isCompleted()` antes de renderizar).

### Verificación del esquema resultante

`Schema::getColumns('media')` (MySQL local) confirmó exactamente lo esperado: `file_path` con `nullable => 1`; `status` `varchar(255)` no nulo con default `'completed'`; `pending_path` y `error_message` nuevos, nullable. Sin cambios inesperados en el resto de columnas.

### Estado al cierre de la Fase 5

El esquema y el modelo ya soportan los 4 estados pedidos por la tarea (`QUEUED`, `PROCESSING`, `COMPLETED`, `FAILED`), pero **todavía nada los usa**: ningún controlador ni servicio crea o actualiza `Media` con estos nuevos campos. Eso es exactamente el trabajo de las Fases 6 y 7 (Job y adaptación de controladores), que no se tocó en esta fase.

## Fase 6 — Servicios y Jobs (2026-07-10)

Diseño mostrado al usuario y aprobado antes de crear archivos (petición explícita, igual que en la Fase 5).

### Archivo nuevo: `app/Jobs/ProcessMediaUploadJob.php`

`ShouldQueue`, recibe el modelo `Media` completo (no el archivo, regla 10), `timeout = 60`, `tries = 1`. En `handle()` marca `PROCESSING`, llama a `MediaUploadService::process()`, y si algo falla lo captura internamente (**sin relanzar**, mismo patrón que `ImportArtistsJob::processRow()` ya usa por fila) para marcar `FAILED` sin disparar reintentos automáticos de Laravel sobre un fallo determinístico (imagen corrupta). El hook `failed()` queda como red de seguridad para casos que no pasan por ese `try/catch` (ej. timeout matando el proceso).

### `app/Services/MediaUploadService.php` — 2 métodos nuevos, nada eliminado

- `queue(UploadedFile $file, Model $model, string $collection, bool $isCover): Media`: guarda el archivo crudo en `media-pending/{collection}/{uuid}.{ext}` (disco `public`), crea el `Media` en `status = queued` con `pending_path` apuntando ahí, despacha `ProcessMediaUploadJob`, y retorna el `Media` (todavía `queued`).
- `process(Media $media): void`: lee `pending_path`, decodifica con Intervention, genera variante principal (1400×1050, calidad 85) y miniatura (400×300, calidad 80) reutilizando `storeImage()`/`extensionFromPath()`/`directory()` ya existentes (sin duplicar lógica), borra el archivo crudo, actualiza el `Media` a `completed` con `file_path`/`thumbnail_path` reales, y si `is_cover` es true llama a `clearOtherCovers()` (mismo comportamiento que `upload()` ya tenía, solo que ahora ocurre al completar el procesamiento en vez de al crear el registro).
- `upload()` (el método síncrono original) **se dejó intacto** — los controladores todavía lo llaman; se reemplaza recién en la Fase 7.

**Decisión de diseño — `media-pending/` como prefijo separado de `media/`**: el comando ya existente `media:cleanup` (`CleanupOrphanMedia`) escanea `disk->allFiles('media')` y borra cualquier archivo no registrado en `Media::file_path`/`thumbnail_path`. Si los archivos crudos vivieran dentro de `media/`, ese comando los borraría mientras un Job todavía los está procesando. Guardándolos en `media-pending/` (fuera del árbol que escanea `media:cleanup`), ese comando no los toca — no fue necesario modificarlo.

### `app/Models/Media.php` — 1 método nuevo, 1 ampliado

- `deletePendingFile()`: borra `pending_path` si existe (usado por el Job al marcar `FAILED`, sin tocar el resto de la fila).
- `deleteFiles()`: ahora también incluye `pending_path` en el borrado, envuelto en `array_filter()` para no pasarle `null` a `Storage::delete()` (que antes de este cambio hubiera lanzado un error si alguna vez se borraba un `Media` con `file_path` nulo — la Fase 5 ya había dejado esto anotado como pendiente, y se resolvió aquí porque es exactamente en esta fase donde `pending_path` empieza a existir de verdad).

### Corrección respecto al plan original

El plan (`02-plan-implementacion.md`, sección 5) proponía truncar el mensaje de error a 500 caracteres (`Str::limit($e->getMessage(), 500)`). La columna real `error_message` creada en la Fase 5 es `varchar(255)`, así que se ajustó a `Str::limit($exception->getMessage(), 250)` (dejando margen para los 3 puntos suspensivos que agrega `Str::limit`) para no exceder el límite de la columna.

### Verificación manual end-to-end (antes de la suite de tests)

Con un script PHP real (no tinker eval, mismo enfoque que la Fase 4), contra la base de datos MySQL local real:

1. **Camino feliz**: se creó un `Artist`/`User` de prueba, se generó una imagen fake (`UploadedFile::fake()->image(...)`), se llamó a `MediaUploadService::queue()` directamente → `Media` quedó `queued` con archivo en `media-pending/gallery/...`. Se confirmó `LLEN` en Redis = 1. Se corrió `php artisan queue:work redis --once` → `DONE`. Se releyó el `Media`: `status=completed`, `file_path`/`thumbnail_path` reales, ambos archivos existen en disco, `pending_path` es `null`, `error_message` es `null`, y el archivo crudo ya no existe (fue borrado tras procesar).
2. **Camino de fallo**: se creó otro `Media` de prueba apuntando a un `pending_path` con contenido que no es una imagen válida (texto plano), se despachó el Job manualmente, se corrió el worker → `DONE` (no `FAIL`, porque el Job captura la excepción internamente y no relanza). Se releyó el `Media`: `status=failed`, `error_message='Unable to decode input'`, `pending_path` es `null`, y el archivo corrupto ya no existe en disco (limpiado por `deletePendingFile()`).
3. Se limpiaron ambos `Media`/`Artist`/`User` de prueba (`->delete()`, que dispara `deleteFiles()` ya ampliado) y los scripts temporales. Se confirmó que la cola de Redis quedó vacía al final.

### Estado al cierre de la Fase 6

El Job y el servicio de procesamiento están completos y verificados end-to-end (éxito y fallo), pero **todavía no los usa ningún controlador real** — `queue()` existe pero nada lo llama desde HTTP todavía. Eso es exactamente el trabajo de la Fase 7.

## Fase 7 — Adaptación del controlador (2026-07-10)

Diseño mostrado al usuario y aprobado antes de aplicar los cambios, incluyendo la pregunta explícita del usuario sobre cómo proteger `fullUrl()`/`thumbnailUrl()`.

### Investigación previa: uso real de `fullUrl()`/`thumbnailUrl()`

Antes de tocar `Media::fullUrl()` se hizo `grep` de todos los usos en el proyecto (13 lugares): 3 en `WordPressPublicationService` (con tipo de retorno `?string`, ya nullable) y el resto interpolados directo en `<img src="...">` dentro de vistas Blade (`dashboard.blade.php`, `artists/show.blade.php`, `activities/show.blade.php`, `activities/edit.blade.php`, `artworks/show.blade.php`, `artworks/index.blade.php`, `artworks/edit.blade.php`, `artists/index.blade.php`). Ningún caller asumía estrictamente un `string` no nulo de forma que rompiera con `null` — confirmó que el cambio era seguro.

### `app/Models/Media.php`

`fullUrl(): string` → `fullUrl(): ?string`, con guard: `return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;`. `thumbnailUrl()` ya era seguro (no se tocó).

**Riesgo relacionado, documentado pero NO resuelto** (fuera de alcance, WordPress excluido desde la Fase 2): `WordPressPublicationService::ensureFeaturedMedia()` usa `Storage::disk('public')->path($cover->file_path)` directamente sin guard — si algún día se intenta publicar una actividad cuya portada apunta a un `Media` todavía `queued`, esa llamada fallaría (`path()` espera `string`, no `null`). No se tocó porque WordPress está fuera del alcance de este plan.

### Controladores — 3 archivos, cambio mínimo en cada uno

- `ArtistMediaController::avatar()` y `::cover()`: `$this->mediaUpload->upload(...)` → `$this->mediaUpload->queue(...)`. Mensajes de éxito actualizados ("... en proceso. Se actualizará en unos segundos.").
- `ActivityMediaController::store()`: mismo cambio, mensaje "Imágenes en proceso. Se mostrarán en unos segundos."
- `ArtworkMediaController::store()`: mismo cambio, mismo mensaje.
- `setCover()`, `destroy()`, `reorder()` de los 3 controladores: **sin cambios** (no procesan archivos).

### Verificación

1. `php -l` sobre los 4 archivos modificados — sin errores.
2. Suite completa (`php artisan test`): **142 passed, 1 skipped, 0 failed** (454 assertions, 104.52s) — idéntico a las fases anteriores. Confirmado que `ArtistMediaTest.php` **no necesitó ningún ajuste**: en el entorno de testing `QUEUE_CONNECTION=sync` (fijo en `phpunit.xml`), así que `ProcessMediaUploadJob::dispatch()` se ejecuta de inmediato dentro del mismo test, haciendo que el resultado final sea indistinguible del flujo síncrono anterior desde el punto de vista de las aserciones existentes.
3. **Verificación real contra Redis (no `sync`)**, para confirmar el comportamiento asíncrono de verdad: se llamó al método `ArtistMediaController::avatar()` directamente (autenticado con un usuario/artista de prueba reales, rol `artista` asignado) usando el `.env` real (`QUEUE_CONNECTION=redis`). Justo después de que el controlador devuelve el `RedirectResponse`, el `Media` seguía en `status=queued` — confirmando que el request HTTP **no espera** el procesamiento. Se corrió `php artisan queue:work redis --once`, y recién ahí el `Media` pasó a `completed`, con `file_path`/`thumbnail_path` reales, archivos en disco, y `fullUrl()` devolviendo una URL válida. Se limpiaron los datos de prueba al final.

### Estado al cierre de la Fase 7

Los 3 controladores ya usan el flujo asíncrono real. `fullUrl()` ya no revienta con `Media` no completados, aunque las vistas todavía no muestran ningún indicador visual de "procesando" — eso es exactamente el trabajo de la Fase 9. Falta también el endpoint de estado (Fase 8) para que el frontend pueda consultar cuándo terminó.

## Fase 8 — Endpoint de estado (2026-07-10)

Diseño mostrado al usuario y aprobado antes de crear archivos. Investigación previa confirmó que `Artist`, `Activity` y `Artwork` (los 3 tipos posibles de `mediable`) tienen un método `update(User $user, $model): bool` en su policy respectiva (`ArtistPolicy`, `ActivityPolicy`, `ArtworkPolicy`), lo que permite una autorización genérica sin lógica condicional por tipo.

### Archivo nuevo: `app/Http/Controllers/MediaStatusController.php`

Un solo método `show(Media $media): JsonResponse`, con route-model-binding implícito (mismo patrón que `setCover()`/`destroy()` ya usan). Autoriza con `$this->authorize('update', $media->mediable)` — Laravel resuelve automáticamente la policy correcta según la clase real de `$media->mediable` en tiempo de ejecución. Responde siempre `200 OK` con el estado del recurso (`queued`/`processing`/`completed`/`failed`); un `Media` inexistente da `404` automático por el binding, sin permiso da `403` vía `authorize()`.

### Ruta nueva en `routes/web.php`

```php
Route::get('media/{media}/status', [MediaStatusController::class, 'show'])->name('media.status');
```

Colocada como línea suelta dentro del grupo `auth`+`verified`, justo después de `dashboard`/`profile` y antes del bloque de `artists` — no depende de ningún recurso padre porque sirve a los 3 tipos de `mediable` por igual (evita triplicar la ruta bajo `artists/`, `activities/` y `artworks/` cuando la lógica no necesita el ID del padre en absoluto, ya que `$media->mediable` ya lo resuelve).

### Verificación

1. `php -l` sobre ambos archivos + `php artisan route:list --name=media.status` — ruta registrada correctamente.
2. Suite completa: `php artisan test` → **142 passed, 1 skipped, 0 failed** (454 assertions, 94.58s) — sin regresiones.
3. **Verificación HTTP real** (no solo llamar al método del controlador): se descubrió que el login del proyecto usa Livewire/Volt (no un form clásico con `_token`), así que autenticar por `curl` puro no era viable sin implementar el protocolo AJAX de Livewire. Se optó por despachar objetos `Request` reales a través de `Illuminate\Contracts\Http\Kernel` (mismo mecanismo que procesa las peticiones HTTP reales: enrutamiento, middleware, controlador, policy), autenticando con `Auth::setUser()`. Se creó un artista dueño con 4 `Media` de prueba (uno por cada estado) y un segundo artista sin relación:
   - `queued` → `200 {"status":"queued","url":null,"thumbnail_url":null,...}`
   - `processing` → `200 {"status":"processing","url":null,"thumbnail_url":null,...}`
   - `completed` → `200 {"status":"completed","url":"http://localhost:8000/storage/...","thumbnail_url":"http://localhost:8000/storage/..._thumb.jpg",...}`
   - `failed` → `200 {"status":"failed","error_message":"Unable to decode input","url":null,...}`
   - Artista no dueño consultando el `Media` del primero → `403 This action is unauthorized.`
   - Se detectó y corrigió sobre la marcha un detalle no anticipado: los usuarios de prueba creados vía factory quedaban con `must_change_password` en su valor por defecto, lo que activaba un middleware existente que redirige a `/password/change` antes de llegar a la ruta — se corrigió creando los usuarios de prueba con `must_change_password => false` (mismo ajuste que ya usa `WordPressPublicationFlowTest` para su usuario de prueba).
4. Se limpiaron todos los datos y archivos de prueba al finalizar.

### Estado al cierre de la Fase 8

El endpoint de estado está completo, autorizado correctamente, y verificado en los 4 estados posibles más el caso de autorización denegada, usando el mecanismo real de enrutamiento HTTP (no solo llamadas directas a métodos de PHP). Todavía no lo consume ningún frontend — eso es la Fase 9.

## Fase 9 — Frontend local (2026-07-10)

Diseño mostrado al usuario y aprobado antes de aplicar los cambios.

### Vistas tocadas (y por qué solo estas 3)

Se investigaron primero las vistas relevantes (`artists/edit.blade.php`, `artists/_form.blade.php`) y se descubrió que el formulario de subida de avatar/portada **no vive** en `artists/edit.blade.php` (que solo tiene los datos de texto del artista) sino en `artists/show.blade.php`, con auto-submit al elegir archivo (`onchange="this.form.submit()"`). Se tocaron exactamente los 3 lugares donde aterriza una subida nueva:

- `resources/views/artists/show.blade.php` — bloques de portada y avatar.
- `resources/views/activities/edit.blade.php` — grid de galería.
- `resources/views/artworks/edit.blade.php` — grid de imágenes.

**No se tocaron** `artists/index.blade.php`, `activities/show.blade.php`, `artworks/show.blade.php`, `artworks/index.blade.php`, `dashboard.blade.php` — son vistas de solo lectura para otros visitantes; extender el polling ahí sería un cambio de alcance mayor sin necesidad clara hoy (decisión de alcance documentada y comunicada al usuario antes de aplicar).

### Archivo nuevo: `resources/js/media-status-poll.js`

Módulo vanilla JS sin dependencias nuevas. Busca todos los elementos `[data-media-status-poll]` al cargar la página; si su `data-media-status` inicial es `queued`/`processing`, hace `fetch()` cada 3 segundos contra `data-media-status-url` (la ruta `media.status`), hasta un máximo de 40 intentos (~2 minutos, tope de seguridad). Al recibir `completed`, reemplaza el `src` de la imagen y oculta el spinner; al recibir `failed`, oculta el spinner y muestra el mensaje de error.

Integrado en `resources/js/app.js` (`import './media-status-poll';`) — se carga automáticamente en todas las páginas autenticadas vía `layouts/app.blade.php`, sin tocar la configuración de Vite ni agregar un entry point nuevo.

### Patrón de marcado aplicado en las 3 vistas

Cada contenedor de imagen lleva `data-media-status-poll` + `data-media-status="{{ $media->status }}"` + `data-media-status-url="{{ route('media.status', $media) }}"` (+ `data-media-status-thumbnail="1"` en las galerías, que usan `thumbnailUrl()` en vez de `fullUrl()`). El estado inicial (mostrar imagen / spinner / error) se resuelve **del lado del servidor con Blade** para evitar parpadeos — el JS solo entra en juego si el estado inicial es `queued`/`processing`.

Detalle importante: se usa `@if($media->isCompleted()) src="{{ $media->thumbnailUrl() }}" @endif` en vez de `src="{{ $media->thumbnailUrl() }}"` a secas, porque esta última emite `src=""` cuando la URL es `null` (ya nullable desde la Fase 7) — y un `<img src="">` hace que algunos navegadores repitan una petición a la URL de la página actual como si fuera una imagen. Omitir el atributo por completo evita ese problema.

En `artists/show.blade.php`, el bloque de avatar necesitó cuidado adicional porque alterna entre mostrar la imagen o las iniciales del artista (`@if($artist->avatar) ... @else {{ $initials(...) }} @endif`) — el polling solo se activa cuando existe un `Media` real que rastrear (`$artist->avatar` no nulo), sin importar su estado; si no hay ningún avatar todavía, se siguen mostrando las iniciales exactamente igual que antes.

### Verificación

1. `php artisan view:cache` sin errores (compiló las 3 vistas modificadas sin fallos de sintaxis Blade) — se corrigió un falso positivo del linter del IDE en una línea no relacionada (`@can('wordpress.unpublish')`, preexistente).
2. `npm run build` (Vite) — build exitoso, `app-Cdis1613.js` incluye el módulo nuevo.
3. Suite completa: `php artisan test` → **142 passed, 1 skipped, 0 failed** (454 assertions, 109.99s) — sin regresiones.
4. **Verificación real en navegador** (Playwright vía `npx`, headless Chromium — no había `chromium-cli` disponible en este entorno, se instaló Playwright localmente en el scratchpad): se creó un artista de prueba con contraseña conocida, se inició sesión de verdad (el login usa Livewire/Volt, un navegador real lo maneja sin problema, a diferencia de `curl` en la Fase 8), y se subió una imagen JPEG real generada con GD.
   - Justo después de la subida: la captura de pantalla confirma el spinner girando dentro del círculo del avatar, con el mensaje flash "Foto de perfil en proceso. Se actualizará en unos segundos."
   - El worker de Redis local procesó el job en menos de 200ms (imagen pequeña, worker ya activo) — más rápido de lo anticipado, lo que en un primer intento causó una lectura ambigua en el script de prueba (se resolvió repitiendo la prueba con una espera explícita al evento `load` de la imagen).
   - Captura final: la imagen real (azul, con el texto "FASE 9") reemplazó al spinner **sin recargar la página**, confirmando el swap vía `fetch()` funciona de extremo a extremo.
   - Caso de fallo: se creó un segundo artista con un `Media` de avatar ya en estado `failed` (sin necesidad de fabricar un JPEG corrupto que pasara la validación `image` de Laravel, algo no trivial), y se confirmó visualmente que el círculo muestra un fondo rosa/rojo con el texto "Error" en vez de romperse.
   - `console --errors` equivalente (listener de `pageerror`/`console.error` de Playwright): **0 errores de JavaScript** en ambas corridas.
5. Se limpiaron los datos de prueba (usuarios, artistas, media) y los scripts temporales; las capturas de pantalla se conservaron en el scratchpad como evidencia.

### Detalle cosmético menor, no resuelto a propósito

El mensaje flash superior ("Foto de perfil en proceso...") no se actualiza vía JS una vez que la imagen ya cargó — queda visible hasta la siguiente carga completa de página. No afecta la funcionalidad (el swap de la imagen sí ocurre correctamente), solo es un texto residual. Se documenta como posible pulido futuro, fuera del alcance mínimo de esta fase.

### Estado al cierre de la Fase 9

El flujo asíncrono ya es visible e interactivo de extremo a extremo para el usuario real: sube una imagen, ve un spinner, y la imagen aparece sola sin recargar la página, con manejo de error inline si el procesamiento falla. Verificado con un navegador real, no solo con scripts o tests.

## Fase 10 — Pruebas (2026-07-13)

Lista de archivos mostrada y aprobada por el usuario antes de crearlos.

### Archivos modificados

- `tests/Feature/ArtistMediaTest.php` — se agregaron 2 tests nuevos (`test_avatar_upload_dispatches_processing_job_and_starts_queued`, `test_cover_upload_dispatches_processing_job_and_starts_queued`), con `Queue::fake()`, que verifican explícitamente que el `Media` queda `queued` (no `completed`) justo tras el POST y que `ProcessMediaUploadJob` se despachó con el `Media` correcto. Los 5 tests originales **no se tocaron**.

### Archivos nuevos

- `tests/Feature/ProcessMediaUploadJobTest.php`: 4 tests que prueban el Job directamente (sin pasar por HTTP), mismo estilo que `ImportProcessingTest.php`:
  - Camino feliz: imagen válida → `completed`, archivos reales, `pending_path` limpio.
  - Camino de fallo: contenido no-imagen → `failed`, `error_message`, sin huérfanos.
  - `clearOtherCovers()` se dispara al completar una nueva portada, no al encolarla.
  - El hook `failed()` del Job (camino de timeout/fallo catastrófico) marca `Media` como `failed` correctamente.
- `tests/Feature/ActivityMediaTest.php` (4 tests) y `tests/Feature/ArtworkMediaTest.php` (4 tests): cobertura nueva para `ActivityMediaController::store()` y `ArtworkMediaController::store()` (hallazgo de la Fase 2: no tenían ningún test). Mismo patrón que `ArtistMediaTest`: subida exitosa, `Queue::fake()` + estado `queued` + Job despachado, autorización denegada a otro artista, rechazo de archivos no-imagen.
- `tests/Feature/MediaStatusTest.php` (6 tests): los 4 estados posibles del endpoint (`queued`/`processing`/`completed`/`failed`), autorización denegada a otro artista, e invitado sin sesión redirigido a `/login`.

**Alcance explícito**: solo se cubrieron las acciones que esta iniciativa tocó (`store()`/`avatar()`/`cover()`, el Job nuevo, el endpoint nuevo). No se agregó cobertura para `setCover()`/`destroy()`/`reorder()` de `ActivityMediaController`/`ArtworkMediaController` — esas acciones no fueron modificadas por este plan.

### Errores encontrados y corregidos durante la escritura de los tests (no en código de producción)

1. `ProcessMediaUploadJobTest`: dos tests intentaban generar la imagen de prueba con `file_get_contents(UploadedFile::fake()->image(...)->getRealPath())`, pero ese archivo temporal ya no existía al momento de leerlo (ciclo de vida transitorio del fake de Laravel). Se resolvió con un helper propio (`fakeJpegContents()`) que genera los bytes JPEG directamente con GD (`imagecreatetruecolor` + `imagejpeg` a buffer de salida), sin depender de archivos temporales.
2. `MediaStatusTest::test_owner_can_view_status_of_completed_media`: el helper compartido `mediaWithStatus()` fuerza `file_path`/`thumbnail_path` a `null` por defecto (pensado para los estados `queued`/`processing`/`failed`); el test de `completed` no los sobrescribía, así que `fullUrl()`/`thumbnailUrl()` devolvían `null` y la aserción fallaba. Se corrigió pasando `file_path`/`thumbnail_path` explícitos en ese test.

Ambos eran errores en los tests nuevos, no en `app/`, `routes/` ni `resources/` — ningún archivo de producción se tocó en esta fase.

### Verificación

1. `php -l` sobre los archivos con errores antes de corregir (confirmó que eran errores de runtime, no de sintaxis).
2. Corrida aislada de los 5 archivos de test (`--filter`): 3 fallos iniciales, ambos descritos arriba, corregidos y vueltos a correr → **25 passed, 0 failed** (83 assertions).
3. Suite completa del proyecto: `php artisan test` → **162 passed, 1 skipped, 0 failed** (524 assertions, 141.58s). 162 = 142 (Fase 9) + 20 tests nuevos (2 + 4 + 4 + 4 + 6).
4. `php vendor/bin/pint --test` sobre los 5 archivos de test nuevos/modificados **y** los 4 archivos de producción tocados a lo largo de todo el plan (`ProcessMediaUploadJob.php`, `MediaStatusController.php`, `MediaUploadService.php`, `Media.php`) → `"result":"passed"`, sin violaciones de estilo.

### Estado al cierre de la Fase 10

Todo el código nuevo de esta iniciativa (Job, servicio, controladores, endpoint, frontend) tiene cobertura de test automatizada real, no solo verificación manual. La suite completa del proyecto pasa sin regresiones y respeta el estilo de código existente.
