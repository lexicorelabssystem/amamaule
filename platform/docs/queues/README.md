# Colas, Redis y procesamiento asíncrono de imágenes — Resumen final

Fecha de cierre: 2026-07-13
Estado: **Fases 1-10 completas.** Este documento es el punto de entrada; el detalle fase por fase vive en los otros 4 archivos de esta carpeta.

## Qué se construyó

El síntoma original era "la carga y procesamiento de archivos demora". La auditoría (Fase 1-2) identificó que el cuello de botella real era la **subida de imágenes** (avatar/portada de artista, galería de actividades, galería de obras): el servidor decodificaba, redimensionaba y guardaba cada imagen de forma síncrona dentro del propio request HTTP, bloqueando al usuario hasta 10 imágenes × 2 variantes cada una antes de responder.

Se resolvió moviendo ese procesamiento a un **Job en cola sobre Redis**, con 4 estados explícitos (`queued`, `processing`, `completed`, `failed`) visibles tanto por API como en la interfaz, con actualización automática sin recargar la página.

El **import de artistas por planilla** (la otra fuente de lentitud identificada en la auditoría) y la **publicación en WordPress** quedaron **explícitamente fuera de esta iteración** — documentados como hallazgos, no resueltos. Ver sección "Fuera de alcance" más abajo.

## Cómo correrlo localmente

1. **Redis**: contenedor Docker dedicado a este proyecto, en el puerto **6380** del host (no 6379, para no chocar con el Redis de otro proyecto que ya corría en esta máquina):
   ```
   docker start ama-redis
   # o, si no existe el contenedor:
   docker run -d --name ama-redis -p 6380:6379 redis:7-alpine
   ```
2. **`.env`** (ya configurado en este entorno local):
   ```
   QUEUE_CONNECTION=redis
   REDIS_CLIENT=predis
   REDIS_HOST=127.0.0.1
   REDIS_PORT=6380
   ```
3. **Worker**: sin este proceso corriendo, los uploads se quedan en `queued` para siempre.
   ```
   php artisan queue:work redis
   ```
4. **Servidor**: `php artisan serve` (o `composer dev`, que ya levanta servidor + worker + logs + Vite juntos — aunque ese script usa `queue:listen` sobre la conexión por defecto, no específicamente `redis`; si se usa `composer dev` conviene correr el `queue:work redis` aparte hasta que se decida unificarlo).

## Arquitectura del flujo nuevo

```
Usuario sube imagen
   → Controlador guarda el archivo crudo tal cual (sin procesar) en disco, en media-pending/{collection}/
   → Crea el registro Media en estado QUEUED (file_path aún null)
   → Despacha ProcessMediaUploadJob(Media) a Redis
   → Responde de inmediato (el usuario no espera nada)

Worker (proceso aparte, "php artisan queue:work redis")
   → Marca el Media como PROCESSING
   → Decodifica, redimensiona (variante principal + miniatura), guarda en disco
   → Marca el Media como COMPLETED (o FAILED si algo falla, con error_message)
   → Borra el archivo crudo en cualquiera de los dos casos

Frontend (JS, sin dependencias nuevas)
   → Hace polling cada 3s a GET /media/{id}/status mientras el estado sea queued/processing
   → Al recibir completed: reemplaza el spinner por la imagen real, sin recargar la página
   → Al recibir failed: muestra el mensaje de error inline
```

## Componentes nuevos

| Componente | Archivo | Qué hace |
|---|---|---|
| Job | `app/Jobs/ProcessMediaUploadJob.php` | Recibe el modelo `Media` (nunca el archivo, regla 10 de la tarea). `tries=1`, captura sus propios fallos sin relanzar. |
| Servicio | `app/Services/MediaUploadService.php` | `queue()` (encola) y `process()` (procesa) nuevos; `upload()` original se conserva sin usar. |
| Modelo | `app/Models/Media.php` | Constantes de estado, `isQueued()/isProcessing()/isCompleted()/isFailed()`, `fullUrl()` ahora nullable-safe, `deletePendingFile()`. |
| Migración | `database/migrations/2026_07_10_170000_add_status_columns_to_media_table.php` | Agrega `status`, `pending_path`, `error_message`; hace `file_path` nullable. No toca la migración original. |
| Controladores | `ArtistMediaController`, `ActivityMediaController`, `ArtworkMediaController` | Ahora llaman a `queue()` en vez de `upload()`. |
| Endpoint de estado | `app/Http/Controllers/MediaStatusController.php` | `GET media/{media}/status`, autorización reutilizada de la policy del `mediable`. |
| Frontend | `resources/js/media-status-poll.js` | Polling vanilla JS, integrado en `app.js`, sin dependencias nuevas. |
| Vistas tocadas | `artists/show.blade.php`, `activities/edit.blade.php`, `artworks/edit.blade.php` | Placeholder con spinner + swap automático. |

Dependencia nueva: **`predis/predis`** (única). Justificación: no había extensión `phpredis` compilada para esta build exacta de PHP en Windows; Predis es 100% PHP vía Composer, sin ese requisito.

## Estados de `Media`

| Estado | Significado | `file_path` | `pending_path` | `error_message` |
|---|---|---|---|---|
| `queued` | Archivo crudo guardado, esperando worker | `null` | ruta del crudo | `null` |
| `processing` | Worker lo está procesando ahora mismo | `null` | ruta del crudo | `null` |
| `completed` | Listo, archivos finales en disco | ruta real | `null` | `null` |
| `failed` | El procesamiento falló | `null` | `null` (ya limpiado) | mensaje corto |

Filas creadas por el flujo síncrono anterior (si las hubiera) quedan `completed` por default de la migración — no requieren migración de datos.

## Pruebas

- **162 tests pasan** en la suite completa del proyecto (1 skip esperado: integración real de WordPress, requiere opt-in explícito). 20 de esos tests son nuevos de esta iniciativa.
- Cobertura nueva: `ProcessMediaUploadJobTest` (Job en aislamiento, éxito y fallo), `MediaStatusTest` (4 estados + autorización), `ActivityMediaTest`/`ArtworkMediaTest` (antes sin ningún test), 2 tests nuevos en `ArtistMediaTest` que verifican explícitamente el despacho asíncrono con `Queue::fake()`.
- Además de los tests automatizados, cada fase con cambios de código real se verificó manualmente contra infraestructura real (Redis de verdad, no mocks): scripts PHP reales para el Job y el endpoint, y un navegador real (Playwright headless) para el frontend — login real, subida real, captura de pantalla del spinner y del swap automático.
- `php vendor/bin/pint --test` sin violaciones de estilo en ningún archivo tocado.

Detalle completo: `04-pruebas.md`.

## Fuera de alcance (decisiones explícitas, no descuidos)

| Qué queda fuera | Por qué |
|---|---|
| **Import de artistas por planilla** (`ImportController::store()`, parseo síncrono + N inserts individuales) | Prioridad 2 del diagnóstico (Fase 2). El usuario decidió acotar esta iteración solo a imágenes (Fase 3). El mismo patrón (Job + estado) aplica directamente cuando se retome — diseño anotado en `02-plan-implementacion.md` sección 11. |
| **Publicación en WordPress** (`WordPressPublicationService`, con 3 timeouts reales de 15s confirmados en logs) | No estaba en el enunciado original de la tarea (que menciona OCR/PDF/Excel/reportes, no integraciones externas). |
| **Vistas de solo lectura** (`artists/index`, `activities/show`, `artworks/show`, `artworks/index`, `dashboard`) | No es ahí donde aterrizan subidas nuevas; ver `Media` no completado en esas páginas es un caso de borde raro. Decisión de alcance de la Fase 9. |
| **Supervisor / Horizon / configuración de producción** | Prohibido explícitamente por las reglas de la tarea (regla 13); esto es 100% entorno local. |

## Pendientes documentados (ninguno bloqueante)

- **Extensión `zip` de PHP no habilitada** en este entorno — descubierto en la Fase 4 al instalar `predis/predis`. Sospecha sin confirmar: la exportación a Excel (`SpreadsheetExportService`) podría estar fallando ya hoy por este motivo, independientemente de este trabajo.
- **Contenedor `ama-redis` sin volumen persistente ni arranque automático** — aceptable para desarrollo local; si se reinicia la máquina, hay que correr `docker start ama-redis` de nuevo.
- **Mensaje flash ("...en proceso...") no se actualiza vía JS** una vez que el swap ya ocurrió — cosmético, no funcional.
- **`WordPressPublicationService::ensureFeaturedMedia()`** no está protegido si algún día se intenta publicar una actividad cuya portada quedó `queued` — relacionado pero fuera de alcance (ver tabla de arriba).
- **Servidor de desarrollo y worker de Redis** pueden haber quedado corriendo en segundo plano de sesiones de verificación anteriores — revisar con `tasklist`/`docker ps` si se retoma el trabajo.

## Índice de documentos

- [`01-auditoria.md`](01-auditoria.md) — Fases 1-2: auditoría del proyecto y diagnóstico del flujo de archivos (con evidencia real de logs).
- [`02-plan-implementacion.md`](02-plan-implementacion.md) — Fase 3: plan completo con las decisiones de arquitectura (Predis, alcance, polling) y su justificación.
- [`03-cambios-realizados.md`](03-cambios-realizados.md) — Fases 4-10: registro exhaustivo de cada cambio real aplicado, fase por fase.
- [`04-pruebas.md`](04-pruebas.md) — Resultados reales de cada verificación (automatizada y manual) en cada fase.
- [`progreso.md`](progreso.md) — Bitácora de seguimiento fase a fase (archivos inspeccionados/modificados, comandos, pendientes).
