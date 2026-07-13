# Pruebas

Registro de pruebas ejecutadas fase a fase. El detalle completo se consolidará en la Fase 10; mientras tanto, cada fase con cambios de código deja aquí su resultado real.

## Fase 4 — Redis local (2026-07-10)

### Pruebas manuales de verificación de infraestructura

| Prueba | Comando | Resultado |
|---|---|---|
| Redis responde | `Redis::connection()->ping()` (Predis, vía tinker) | `Predis\Response\Status {payload: "PONG"}` — OK |
| Job se encola en Redis | Dispatch de un closure desde un script PHP real + `redis-cli llen plataforma-ama-database-queues:default` | `1` — OK, el job quedó en la cola |
| Worker consume el job | `php artisan queue:work redis --once --timeout=10` | `DONE` en 27.31ms — OK |
| Efecto real del job | Lectura del archivo que el job debía escribir | Contenido presente (`queue-ok-2026-07-10 16:25:21`) — OK |
| Cola queda vacía tras procesar | `redis-cli keys '*'` | Sin resultados — OK |

Nota metodológica: el primer intento de esta prueba usó `php artisan tinker --execute="dispatch(function(){...})"` y el job **falló** al ejecutarse (`FAIL` en el log del worker), por un problema de serialización de closures definidas dentro del código evaluado por PsySH (limitación conocida de `tinker --execute`, no del pipeline Redis/cola). Se repitió con un script PHP real en disco y funcionó correctamente — se documenta para que quede claro que no fue un fallo de Redis, sino de la herramienta de prueba usada inicialmente.

### Suite de tests automatizados

Comando: `php artisan test`

```
Tests:    1 skipped, 142 passed (454 assertions)
Duration: 150.40s
```

- El único test no ejecutado (`Tests\Integration\WordPress\WordPressPublishingIntegrationTest`) requiere `RUN_WORDPRESS_INTEGRATION=true` explícito — comportamiento esperado y preexistente, no relacionado con el cambio de esta fase.
- Ningún test falló. Esto era esperable: `phpunit.xml` fija `QUEUE_CONNECTION=sync` para el entorno de testing sin importar el `.env` local, por lo que el cambio de `QUEUE_CONNECTION=database` → `redis` en `.env` no afecta la suite.
- No se añadieron tests nuevos en esta fase (no correspondía: la Fase 4 es infraestructura/configuración, no código de aplicación con lógica propia que probar).

### Pendiente de prueba (fuera de esta fase)

- El comportamiento de `ImportArtistsJob` corriendo realmente sobre Redis (en vez de `sync` de test o `database` de antes) no se ha probado manualmente end-to-end con datos reales de la aplicación — solo se probó con un closure de prueba genérico. Se puede validar en cualquier momento corriendo `php artisan queue:work redis` y disparando una importación real desde la UI, pero no es necesario para cerrar la Fase 4.

## Fase 5 — Tablas y estados (2026-07-10)

### Verificación previa a ejecutar la migración

`php artisan migrate --pretend` — SQL generado revisado y coincide con lo diseñado (ver `03-cambios-realizados.md`), antes de aplicarlo de verdad.

### Aplicación de la migración

`php artisan migrate` → `2026_07_10_170000_add_status_columns_to_media_table` — `DONE` en 204.99ms, sin errores.

### Verificación del esquema

`Schema::getColumns('media')` vía tinker confirmó las 3 columnas nuevas y el cambio de nullabilidad de `file_path`, sin alterar ninguna otra columna existente.

### Suite de tests automatizados

Comando: `php artisan test`

```
Tests:    1 skipped, 142 passed (454 assertions)
Duration: 117.17s
```

Mismo resultado que en la Fase 4 (142 passed, 1 skipped, 0 failed) — la migración y los cambios en `Media.php` no rompieron nada. Relevante: la suite corre con `DB_CONNECTION=sqlite` (fijado en `phpunit.xml`, `RefreshDatabase` corre todas las migraciones desde cero en cada test), por lo que este resultado confirma que la migración nueva también es válida bajo SQLite, no solo bajo el MySQL usado en el entorno local real.

No se agregaron tests nuevos en esta fase (no correspondía: todavía no hay comportamiento nuevo que probar — ningún código usa los estados nuevos aún, eso llega en las Fases 6-7).

## Fase 6 — Servicios y Jobs (2026-07-10)

### Verificación manual end-to-end (scripts reales, no PHPUnit — eso es Fase 10)

Contra la base de datos MySQL local real, con datos de prueba creados y eliminados en la misma sesión:

| Prueba | Resultado |
|---|---|
| `MediaUploadService::queue()` crea `Media` en `queued` con `pending_path` en disco | OK |
| Job llega a Redis (`LLEN` = 1) | OK |
| `php artisan queue:work redis --once` procesa el job (camino feliz) | `DONE` en ~1s |
| Tras procesar: `status=completed`, `file_path`/`thumbnail_path` reales en disco, `pending_path=null`, `error_message=null`, archivo crudo borrado | OK |
| Job con archivo crudo corrupto (texto plano en vez de imagen) | `DONE` (no `FAIL`, por diseño — el Job captura internamente) |
| Tras procesar el corrupto: `status=failed`, `error_message='Unable to decode input'`, `pending_path=null`, archivo corrupto borrado del disco | OK |
| Limpieza de datos de prueba (`Media`/`Artist`/`User`) sin errores, incluyendo el `deleteFiles()` ampliado | OK |
| Cola de Redis vacía al finalizar | OK (confirmado con `redis-cli keys '*'`) |

### Suite de tests automatizados

Comando: `php artisan test`

```
Tests:    1 skipped, 142 passed (454 assertions)
Duration: 118.19s
```

Mismo resultado que las Fases 4 y 5 — sin regresiones. No se agregaron tests PHPUnit nuevos en esta fase a propósito (la creación de tests formales para `ProcessMediaUploadJob` está asignada a la Fase 10 en el plan); la verificación de esta fase se apoyó en los scripts reales descritos arriba, igual que se hizo para validar Redis en la Fase 4.

## Fase 7 — Adaptación del controlador (2026-07-10)

### Suite de tests automatizados

Comando: `php artisan test`

```
Tests:    1 skipped, 142 passed (454 assertions)
Duration: 104.52s
```

Mismo resultado que las fases anteriores. Confirmado específicamente que `ArtistMediaTest.php` (el único con cobertura de subida de medios hoy) sigue pasando sin modificaciones, gracias a que `phpunit.xml` fuerza `QUEUE_CONNECTION=sync`.

### Verificación manual con Redis real (no `sync`) — la que realmente prueba el comportamiento asíncrono

| Paso | Resultado |
|---|---|
| Llamada directa a `ArtistMediaController::avatar()` con usuario/artista reales, `.env` real (`QUEUE_CONNECTION=redis`) | `RedirectResponse` devuelto de inmediato |
| Estado del `Media` justo después de la respuesta HTTP | `queued` — confirma que el request no esperó el procesamiento |
| `redis-cli llen` sobre la cola | `1` — el job efectivamente esperaba en Redis |
| `php artisan queue:work redis --once` | `DONE` en 300.80ms |
| Estado del `Media` después de procesar | `completed`, `file_path`/`thumbnail_path` reales, archivos en disco confirmados |
| `$media->fullUrl()` | URL válida no nula (`http://localhost:8000/storage/media/avatar/...`) |
| Limpieza de datos de prueba | Sin errores |
| Cola de Redis vacía al final | Confirmado |

Esta prueba es la que realmente valida la Fase 7 — la suite de PHPUnit, por usar `sync`, no puede distinguir el flujo nuevo del viejo.

## Fase 8 — Endpoint de estado (2026-07-10)

### Suite de tests automatizados

Comando: `php artisan test`

```
Tests:    1 skipped, 142 passed (454 assertions)
Duration: 94.58s
```

Mismo resultado que las fases anteriores.

### Verificación manual vía Kernel HTTP real (enrutamiento + middleware + policy reales)

| Caso | HTTP esperado | HTTP obtenido | Body |
|---|---|---|---|
| Dueño consulta `Media` `queued` | 200 | 200 | `{"status":"queued","url":null,"thumbnail_url":null}` |
| Dueño consulta `Media` `processing` | 200 | 200 | `{"status":"processing","url":null,"thumbnail_url":null}` |
| Dueño consulta `Media` `completed` | 200 | 200 | `{"status":"completed","url":"...","thumbnail_url":"..."}` |
| Dueño consulta `Media` `failed` | 200 | 200 | `{"status":"failed","error_message":"Unable to decode input"}` |
| Otro artista (no dueño) consulta el `Media` `completed` del primero | 403 | 403 | `This action is unauthorized.` |

Los 5 casos coincidieron exactamente con lo esperado. Se corrigió sobre la marcha que los usuarios de prueba necesitaban `must_change_password => false` para no ser redirigidos por un middleware existente antes de llegar a la ruta (mismo ajuste que ya usa `WordPressPublicationFlowTest`).

No se agregaron tests PHPUnit nuevos en esta fase (formales para `MediaStatusController` quedan para la Fase 10); la verificación se apoyó en despachar `Request` reales a través de `Illuminate\Contracts\Http\Kernel`, que ejercita el mismo camino que una petición HTTP real (a diferencia de llamar el método del controlador directamente).

## Fase 9 — Frontend local (2026-07-10)

### Verificaciones estáticas

| Comando | Resultado |
|---|---|
| `php artisan view:cache` | Compiló sin errores (incluye las 3 vistas modificadas) |
| `npm run build` | Build exitoso, `app-Cdis1613.js` (47.22 kB) incluye el nuevo módulo |
| `php artisan test` | **142 passed, 1 skipped, 0 failed** (454 assertions, 109.99s) — sin regresiones |

### Verificación real en navegador (Playwright headless, no simulada)

No había `chromium-cli` disponible en este entorno; se usó `npx playwright` (instalado localmente en el scratchpad, sin agregarlo como dependencia del proyecto) para manejar un Chromium headless real.

| Paso | Resultado |
|---|---|
| Login real como artista (Livewire/Volt) | OK — llega a `/dashboard` |
| Subir avatar real (JPEG generado con GD) | Formulario con auto-submit funciona |
| Estado justo después de la subida | Spinner visible en la captura de pantalla, mensaje flash correcto |
| Worker Redis procesa el job | < 200ms (más rápido de lo esperado en este entorno local) |
| Polling JS detecta `completed` | Confirmado vía `waitForFunction` sobre `data-media-status` |
| Imagen real se pinta en pantalla (`img.complete && naturalWidth > 0`) | Confirmado — captura final muestra la imagen de prueba real, no el spinner |
| Sin recarga de página durante el swap | Confirmado (mismo `page.url()`, sin navegación) |
| Caso `failed` (Media pre-sembrado en ese estado) | Círculo rosa/rojo con texto "Error", sin romper el layout |
| Errores de JavaScript en consola | 0 en ambas corridas |

Nota metodológica: el primer intento de captura del estado `queued` tuvo una lectura ambigua (`isVisible()` reportó `false` pese a que la captura de pantalla mostraba el spinner claramente visible) — se determinó que fue una condición de carrera del propio script de prueba, no un bug de la aplicación: el worker local procesa tan rápido (sub-200ms) que el estado puede pasar a `completed` entre dos aserciones consecutivas del script. Se repitió la prueba con esperas explícitas (`waitForFunction` sobre el evento `load` de la imagen) para eliminar la ambigüedad, confirmando el resultado correcto.

No se agregaron tests PHPUnit nuevos en esta fase (no aplica: es JS/Blade, no PHP testeable con PHPUnit; la verificación real en navegador cumple ese rol para esta fase).

## Fase 10 — Pruebas (2026-07-13)

### Resultado final de los 20 tests nuevos (aislados)

Comando: `php artisan test --filter="ArtistMediaTest|ProcessMediaUploadJobTest|ActivityMediaTest|ArtworkMediaTest|MediaStatusTest"`

```
Tests:    25 passed (83 assertions)
Duration: 7.18s
```

(25 = 7 de `ArtistMediaTest`, de los cuales 5 ya existían y 2 son nuevos, más 4+4+6+4 de los 4 archivos nuevos = 20 tests genuinamente nuevos añadidos en esta fase).

### Primera corrida — 3 fallos, ambos en los tests nuevos, no en código de producción

| Test | Causa | Corrección |
|---|---|---|
| `ProcessMediaUploadJobTest::test_job_processes_valid_pending_image_and_marks_completed` | `file_get_contents()` sobre un archivo temporal de `UploadedFile::fake()` ya eliminado | Helper propio `fakeJpegContents()` con GD directo |
| `ProcessMediaUploadJobTest::test_job_clears_previous_cover_when_new_cover_completes` | Mismo motivo | Misma corrección |
| `MediaStatusTest::test_owner_can_view_status_of_completed_media` | Helper compartido forzaba `file_path`/`thumbnail_path` a `null` sin que el test de `completed` los sobrescribiera | Se pasaron `file_path`/`thumbnail_path` explícitos en ese test |

### Segunda corrida — todo verde

```
Tests:    25 passed (83 assertions)
```

### Suite completa del proyecto

Comando: `php artisan test`

```
Tests:    1 skipped, 162 passed (524 assertions)
Duration: 141.58s
```

162 = 142 (acumulado hasta la Fase 9) + 20 tests nuevos de esta fase. 0 failed, mismo skip esperado de siempre (integración real de WordPress).

### Estilo de código

Comando: `php vendor/bin/pint --test` sobre los 5 archivos de test y los 4 archivos de producción (`ProcessMediaUploadJob.php`, `MediaStatusController.php`, `MediaUploadService.php`, `Media.php`) tocados a lo largo de todo el plan de colas/Redis.

```
{"tool":"pint","result":"passed"}
```

Sin violaciones de estilo en ningún archivo de esta iniciativa.
