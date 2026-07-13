# Fase 3 — Plan de implementación

Fecha: 2026-07-10
Estado: **plan aprobado en sus decisiones clave, código aún NO modificado.** Este documento describe qué se va a construir en las Fases 4-11; la ejecución real ocurre fase por fase, con pruebas y check-in después de cada una.

## 0. Decisiones ya tomadas con el usuario

| Decisión | Elegido | Motivo |
|---|---|---|
| Cliente Redis | **Predis** (`predis/predis`, paquete Composer 100% PHP) | La extensión nativa `redis` (phpredis) no está cargada en este PHP 8.4.19 NTS y no hay garantía de un build precompilado para esta combinación exacta en Windows. Predis no requiere extensión ni recompilar PHP. |
| Alcance de esta iteración (Fases 4-10) | **Solo el flujo de subida de imágenes** (`MediaUploadService` + `ArtistMediaController`, `ActivityMediaController`, `ArtworkMediaController`) | Es la Prioridad 1 del diagnóstico (Fase 2): el cuello de botella más directo, sin ningún mecanismo de cola hoy. El parseo de planillas de import (Prioridad 2) queda para una iteración posterior, reutilizando el mismo patrón ya construido aquí. |
| Consulta de estado en frontend | **JS simple con `fetch()` + polling**, sin Livewire | Livewire está en `composer.json` pero no se usa en ningún componente real hoy (solo scaffold de Breeze). Introducirlo sería una dependencia nueva de facto sin justificación (regla 12). |

## 1. Explícitamente fuera de alcance de este plan

- **Import de artistas (planillas)**: el parseo síncrono en `ImportController::store()` no se toca en esta iteración. Se documenta como "siguiente flujo a migrar" al final de este plan, para cuando el usuario lo pida.
- **Publicación en WordPress** (`WordPressPublicationService`/`WordPressPublisher`): fuera del enunciado original de la tarea (que menciona OCR/PDF/Excel/reportes, no integraciones externas). Los 3 timeouts reales de 15s detectados en logs (Fase 2) quedan documentados pero no se resuelven aquí.
- **Exportación de planillas** (`SpreadsheetExportService`/`ExportController`): es generación de salida (`StreamedResponse`), no una carga de archivo; no aplica al enunciado "carga y procesamiento de archivos".
- Cualquier configuración de Supervisor, Horizon o infraestructura de producción (regla 13).

## 2. Vista general del nuevo flujo (imágenes)

Flujo actual (síncrono, ver Fase 2 del diagnóstico):

```
Usuario → Controller → MediaUploadService::upload() [decodifica+resize+encode+guarda x2] → Media::create() (ya completo) → redirect
```

Flujo propuesto (asíncrono):

```
Usuario → Controller → guarda el archivo crudo tal cual llegó (sin procesar) → Media::create(status=QUEUED) → dispatch(ProcessMediaUploadJob) sobre Redis → redirect inmediato
                                                                                        │
                                                                                        ▼ (worker, fuera del request HTTP)
                                                                          Job: lee el archivo crudo por su ruta guardada en el Media →
                                                                          decodifica+resize+encode+guarda x2 → Media::update(status=COMPLETED, file_path, thumbnail_path)
                                                                          (o status=FAILED + error_message si algo falla)

Frontend → GET /media/{media}/status (polling con fetch()) → JSON {status, urls si completed, error si failed}
```

Punto clave sobre la regla 10 ("los Jobs deben recibir IDs, no el contenido del archivo"): el Job recibirá el **modelo `Media`** (que Laravel serializa por su ID, mismo patrón ya usado por `ImportArtistsJob` con `Import`), y ese `Media` tendrá una columna con la **ruta** del archivo crudo ya guardado en disco por el controlador — nunca los bytes del archivo viajan dentro del payload de la cola.

## 3. Cambios de esquema propuestos (Fase 5)

**Nueva migración** (no se toca `2026_07_08_155953_create_media_table.php`, regla 11):

Modificaciones sobre la tabla `media`:

| Columna | Tipo | Cambio | Motivo |
|---|---|---|---|
| `file_path` | string | pasa a **nullable** | Hoy es obligatorio porque el registro solo se creaba una vez procesado. Con el nuevo flujo, el registro se crea en estado `QUEUED` antes de que exista el archivo final. |
| `status` | string, default `completed` | **nueva** | `queued`, `processing`, `completed`, `failed`. Default `completed` para que cualquier fila ya existente (creada por el flujo síncrono anterior) siga siendo válida sin migración de datos. Las filas nuevas la fijarán explícitamente en `queued`. |
| `pending_path` | string, nullable | **nueva** | Ruta del archivo crudo (sin procesar) tal como llegó del usuario, guardado por el controlador antes de encolar. El Job la lee, procesa, y la limpia (borra el archivo crudo) al terminar, sea éxito o fallo. |
| `error_message` | string, nullable | **nueva** | Mensaje corto de error cuando `status = failed`, para mostrarlo en el endpoint de estado y en el log de auditoría. |

No se agrega una tabla nueva porque el estado pertenece 1:1 a cada `Media`, igual que `Import`/`ImportRow`/`WordPressPublication` ya hacen con sus propios estados (mismo patrón de convención ya usado en el proyecto, ver Fase 1 punto 7).

`Media::casts()` se ampliará (en la Fase 6, no ahora) para tratar `status` como valor de un enum de constantes `Media::STATUS_QUEUED`, `STATUS_PROCESSING`, `STATUS_COMPLETED`, `STATUS_FAILED`, replicando el estilo ya usado en `Import` e `ImportRow` (constantes públicas + array `$statuses`).

## 4. Redis local (Fase 4)

Cambios previstos, todos en `.env` local (nunca en `.env.example` de forma que afecte producción sin que el usuario lo apruebe explícitamente, y nunca en configuración de producción):

- `composer require predis/predis` (única dependencia nueva; justificación: es el cliente Redis recomendado por Laravel para entornos sin extensión `phpredis`, cero configuración adicional de sistema).
- `.env`: `REDIS_CLIENT=predis`, `QUEUE_CONNECTION=redis` (los bloques de conexión en `config/queue.php` y `config/database.php` **ya existen**, no requieren cambios de código, solo activarse vía `.env`).
- Falta decidir en la Fase 4, con el usuario, **cómo correr un servidor Redis local en Windows** (no hay `redis-server` en el PATH hoy). Opciones a evaluar en ese momento, sin instalar nada todavía:
  - Memurai Developer (servicio nativo de Windows, compatible con protocolo Redis, gratis para desarrollo).
  - Redis dentro de WSL2, si el usuario ya tiene WSL habilitado.
  - Contenedor Docker (`redis:7-alpine`), si el usuario tiene Docker Desktop.
- Verificación de la Fase 4: `php artisan tinker` → `Redis::ping()` (o equivalente con `predis`) debe responder `PONG` antes de dar la fase por completa.

## 5. Job nuevo (Fase 6)

`App\Jobs\ProcessMediaUploadJob`:

- `implements ShouldQueue`, `use Queueable`.
- Constructor: `public function __construct(public Media $media) {}` (mismo patrón que `ImportArtistsJob`).
- `timeout` propuesto: 60 segundos (una imagen individual, no un lote de 2000 filas como el import; se ajustará si en pruebas reales resulta insuficiente).
- `handle()`:
  1. `$this->media->update(['status' => Media::STATUS_PROCESSING])`.
  2. Reconstruir la ruta absoluta del archivo crudo desde `$this->media->pending_path`.
  3. Reutilizar la lógica de procesamiento que hoy vive en `MediaUploadService` (decodificar con Intervention, generar variante principal + miniatura, guardar en disco `public`). Este método se extraerá de `MediaUploadService::upload()` a un método reutilizable (ej. `MediaUploadService::process(Media $media, string $rawPath)`) para no duplicar la lógica de resize/encode — se decide el diseño exacto de ese refactor en la Fase 6, no ahora.
  4. Si todo va bien: `$this->media->update(['status' => Media::STATUS_COMPLETED, 'file_path' => ..., 'thumbnail_path' => ..., 'pending_path' => null])` y borrar el archivo crudo de `pending_path`.
  5. Si falla (excepción decodificando imagen corrupta, disco lleno, etc.): capturar, `$this->media->update(['status' => Media::STATUS_FAILED, 'error_message' => Str::limit($e->getMessage(), 500)])`, borrar igualmente el archivo crudo para no dejar huérfanos (consistente con el espíritu de `CleanupOrphanMedia`, que ya limpia archivos sin registro — aquí evitamos generar archivos crudos sin registro de vuelta).
- Se implementará también `failed(Throwable $exception)` (hook estándar de Laravel para cuando se agotan los reintentos) para dejar el `Media` en `FAILED` incluso si el job se cae fuera del bloque `try/catch` de `handle()`.

## 6. Adaptación de controladores (Fase 7)

Para `ArtistMediaController::avatar()`, `::cover()`, `ArtworkMediaController::store()`, `ActivityMediaController::store()`:

1. La validación (`$request->validate([...])`) **se mantiene igual** — sigue siendo síncrona porque es barata (tipo MIME, tamaño) y le da feedback inmediato al usuario si sube un archivo inválido, sin necesidad de cola.
2. En vez de `$this->mediaUpload->upload($file, $model, $collection)`, el controlador:
   - Guarda el archivo crudo tal cual (`$file->store('media-pending/{collection}')`, sin pasar por Intervention).
   - Crea el `Media` con `status = QUEUED`, `pending_path` = la ruta recién guardada, `file_path = null`, `file_name`/`mime_type`/`size` ya conocidos (no requieren procesamiento), `order` calculado igual que hoy.
   - Despacha `ProcessMediaUploadJob::dispatch($media)`.
3. La respuesta (`redirect()->back()->with(...)`) se mantiene — el usuario sigue viendo la misma pantalla de inmediato, solo que el mensaje cambiará a algo como "Imagen en proceso" en vez de "Imagen subida correctamente", y la vista deberá reflejar el estado `queued`/`processing` (ver Fase 9).
4. Casos especiales a preservar (regla 6, no romper contratos):
   - `ArtistMediaController::avatar()`/`cover()` borran el `Media` anterior (`$old?->delete()`) — se mantiene igual, se sigue borrando el anterior de inmediato; el nuevo queda `QUEUED` hasta que el Job lo complete.
   - `is_cover` / `clearOtherCovers()` en `MediaUploadService` — se mantiene, pero solo aplica una vez que el Job marca el `Media` como `COMPLETED` (no tiene sentido marcarlo portada antes de que exista el archivo final).

## 7. Endpoint de estado (Fase 8)

Propuesta (nombres tentativos, a confirmar/ajustar al implementar, no se crea nada todavía):

- Ruta: `GET media/{media}/status` → nombre `media.status`.
- Controlador: método nuevo, ya sea en un `MediaController` ligero o como método adicional reutilizando uno de los controladores de media existentes — se decide en la Fase 8 para no duplicar autorización.
- Autorización: reutilizar la misma política que ya protege la edición del recurso dueño del `Media` (`$media->mediable`), es decir, la misma regla `authorize('update', $media->mediable)` que ya usan `destroy()`/`setCover()` hoy en `ActivityMediaController`/`ArtworkMediaController`. Esto evita crear una `MediaPolicy` nueva sin necesidad (regla 12).
- Respuesta JSON:
  ```json
  {
    "id": 123,
    "status": "processing",
    "error_message": null,
    "url": null,
    "thumbnail_url": null
  }
  ```
  Cuando `status = completed`, `url`/`thumbnail_url` se completan con `Media::fullUrl()`/`thumbnailUrl()` (métodos que ya existen). Cuando `status = failed`, `error_message` trae el mensaje corto.

## 8. Frontend local (Fase 9)

- Las vistas que listan galerías (`activities/edit`, vistas equivalentes de artista/obra — a identificar exactamente en la Fase 9) deben renderizar un placeholder distinto cuando `media.status` no es `completed` (ej. tarjeta gris con spinner y `data-media-id="{{ $media->id }}"`).
- Un script JS pequeño y autocontenido (sin dependencias nuevas, sin build adicional más allá de lo que ya compila Vite) hace `setInterval`/`fetch()` sobre `/media/{id}/status` cada pocos segundos **solo para los `data-media-id` en estado no terminal** (`queued`/`processing`), y al recibir `completed` reemplaza el placeholder por la imagen real; al recibir `failed`, muestra el `error_message` y detiene el polling para ese elemento.
- Se evaluará en ese momento si conviene un intervalo fijo (ej. 2s) o backoff progresivo, y un tope máximo de reintentos para no dejar polling infinito si un Job se pierde.

## 9. Pruebas (Fase 10)

Basado en la cobertura ya inventariada en la Fase 2:

- **Adaptar** `tests/Feature/ArtistMediaTest.php`: los tests que hoy asumen `Media` completo justo después del `post()` deberán usar `Queue::fake()` para verificar que se despachó `ProcessMediaUploadJob` con el `Media` correcto (estado `queued`), y tests nuevos que ejecuten el Job (sin fake, o con `Bus::dispatchSync`) para verificar que termina en `completed` con los archivos reales creados vía `Storage::fake('public')`.
- **Crear** tests nuevos para `ActivityMediaController` y `ArtworkMediaController` (hoy sin cobertura, hallazgo de la Fase 2) siguiendo el mismo patrón.
- **Crear** `tests/Feature/ProcessMediaUploadJobTest.php` (paralelo a `ImportProcessingTest.php`): caso éxito (imagen válida → `completed`, archivos en disco, `pending_path` limpio) y caso fallo (archivo corrupto/no decodificable → `failed`, `error_message` presente, sin archivos huérfanos).
- **Crear** test del endpoint de estado: autorización (dueño puede, otro artista no puede — mismo patrón que `test_artist_cannot_upload_avatar_to_another_artists_profile`), y las 4 formas de respuesta JSON (`queued`, `processing`, `completed`, `failed`).
- Todos estos tests corren con `QUEUE_CONNECTION=sync` (ya configurado así en `phpunit.xml`, sin cambios) — no depende de que Redis esté corriendo para que la suite de tests pase; esto es intencional y no contradice la regla 8 (esa regla aplica al entorno real vía `.env`, no al entorno de testing).

## 10. Documentación (Fase 11)

Al cerrar la Fase 10 se actualizará `docs/queues/03-cambios-realizados.md` (registro fase a fase de lo ya implementado, no de lo planeado) y se redactará un resumen final en `docs/queues/04-pruebas.md` con los resultados reales de `php artisan test`.

## 11. Siguiente flujo a migrar (fuera de esta iteración, para referencia futura)

Cuando el usuario decida continuar con la Prioridad 2 (parseo de planillas de import), el mismo patrón aplica:

- Nuevo Job `ParseImportFileJob` (o similar) que reciba el `Import` (ya existe el modelo), lea el archivo ya guardado (`stored_filename`), haga el parseo con `ArtistImportParser` y el `insert()` masivo de `ImportRow` (en vez de N `create()` individuales) dentro del Job, no en `ImportController::store()`.
- `Import` ya tiene estados (`pending/processing/completed/failed/cancelled`); habría que decidir si `pending` pasa a jugar el rol de `QUEUED` o si se agrega un estado explícito nuevo — esa decisión se toma cuando se aborde esa fase, no ahora.

## 12. Checklist de la Fase 3

- [x] Plan de Redis local definido (Predis, motivo documentado, decisión pendiente solo en "cómo correr el servidor" para la Fase 4).
- [x] Alcance de la iteración acotado a subida de imágenes (Prioridad 1), con el import de planillas explícitamente diferido.
- [x] Cambios de esquema propuestos sin tocar migraciones antiguas (regla 11).
- [x] Diseño del Job sin pasar contenido de archivo, solo el modelo `Media` (regla 10).
- [x] Ninguna dependencia nueva sin justificar salvo `predis/predis` (regla 12).
- [x] Sin Supervisor/Horizon/config de producción (regla 13).
- [x] No se modificó código en esta fase.
