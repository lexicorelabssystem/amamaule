> # Plataforma AMA - Backlog Técnico Inicial
> 
> **Estado:** MVP - Fases 0 a 7  
> **Metodología:** Kanban con hitos por fase. Cada tarea debe terminar en commit y push.

---

## Épica 0: Preparación del proyecto

| ID | Tarea | Prioridad | Criterio de aceptación | Fase |
|----|-------|-----------|------------------------|------|
| E0-T1 | Descargar `composer.phar` localmente | Alta | Existe `platform/composer.phar` funcional | 0 |
| E0-T2 | Crear proyecto Laravel en `platform/` | Alta | `php artisan --version` responde correctamente | 0 |
| E0-T3 | Configurar `.env` con base de datos `ama_plataforma_local` | Alta | Conexión DB exitosa | 0 |
| E0-T4 | Crear base de datos MySQL `ama_plataforma_local` | Alta | Base de datos existe y es accesible | 0 |
| E0-T5 | Configurar `.gitignore` interno de Laravel | Alta | `vendor/`, `.env`, `storage/logs/` ignorados | 0 |
| E0-T6 | Agregar `.htaccess` de protección en `platform/` | Media | Acceso vía Apache a `platform/` bloqueado | 0 |
| E0-T7 | Actualizar README raíz con instrucciones de plataforma | Media | Documentación clara para ejecutar localmente | 0 |

---

## Épica 1: Autenticación y seguridad base

| ID | Tarea | Prioridad | Criterio de aceptación | Fase |
|----|-------|-----------|------------------------|------|
| E1-T1 | Instalar Laravel Breeze con Livewire | Alta | Login/logout funcionan | 1 |
| E1-T2 | Instalar y configurar Spatie Laravel Permission | Alta | Roles y permisos seedeados | 1 |
| E1-T3 | Extender modelo `User` con campos adicionales | Alta | Migración y modelo actualizados | 1 |
| E1-T4 | Crear seeder de roles iniciales | Alta | Roles existen en base de datos | 1 |
| E1-T5 | Crear seeder de usuario administrador | Alta | Admin puede iniciar sesión | 1 |
| E1-T6 | Crear middleware `MustChangePassword` | Alta | Usuarios con flag son redirigidos | 1 |
| E1-T7 | Implementar flujo de cambio obligatorio de clave | Alta | Usuario cambia clave y puede continuar | 1 |
| E1-T8 | Configurar rate limiting en login y reset | Media | Límites funcionan correctamente | 1 |
| E1-T9 | Crear layout base del dashboard | Alta | Dashboard renderiza para usuarios autenticados | 1 |
| E1-T10 | Crear primera pantalla de dashboard | Alta | Dashboard muestra mensaje de bienvenida y menú | 1 |

---

## Épica 2: Importación de artistas

| ID | Tarea | Prioridad | Criterio de aceptación | Fase |
|----|-------|-----------|------------------------|------|
| E2-T1 | Crear migraciones de `disciplines` y `territories` | Alta | Tablas creadas con seeders básicos | 2 |
| E2-T2 | Crear migración de `artists` y relaciones | Alta | Tabla `artists` lista | 2 |
| E2-T3 | Crear modelo `Artist` y relación con `User` | Alta | Relación uno a uno funciona | 2 |
| E2-T4 | Crear tabla y modelo `Import` / `ImportRow` | Media | Estructura de importación lista | 2 |
| E2-T5 | Implementar parser de CSV/Excel | Alta | Archivo se lee y valida encabezados | 2 |
| E2-T6 | Implementar vista de preview de importación | Alta | Usuario ve filas antes de confirmar | 2 |
| E2-T7 | Implementar job `ImportArtistsJob` con chunks | Alta | Importa lotes sin timeout | 2 |
| E2-T8 | Normalizar emails y detectar duplicados | Alta | Emails duplicados marcados como error | 2 |
| E2-T9 | Generar claves temporales para artistas importados | Alta | Claves seguras generadas y hasheadas | 2 |
| E2-T10 | Enviar credenciales por email vía cola | Media | Email enviado correctamente | 2 |
| E2-T11 | Registrar errores por fila en `import_rows` | Media | Errores visibles en detalle de importación | 2 |

---

## Épica 3: Perfil artístico

| ID | Tarea | Prioridad | Criterio de aceptación | Fase |
|----|-------|-----------|------------------------|------|
| E3-T1 | Crear migración `artist_profiles` | Alta | Tabla lista | 3 |
| E3-T2 | Crear formulario de edición de perfil | Alta | Artista puede editar datos | 3 |
| E3-T3 | Implementar validaciones de perfil | Alta | Campos requeridos validados | 3 |
| E3-T4 | Implementar estados de perfil | Alta | Estados draft, submitted, in_review, etc. | 3 |
| E3-T5 | Crear bandeja de revisión de perfiles | Alta | Equipo AMA ve perfiles pendientes | 3 |
| E3-T6 | Implementar acciones de aprobar/rechazar/solicitar cambios | Alta | Estados cambian con permisos | 3 |
| E3-T7 | Crear políticas `ArtistPolicy` | Alta | Autorización funciona | 3 |
| E3-T8 | Implementar comentarios internos en perfiles | Media | Comentarios visibles según permisos | 3 |

---

## Épica 4: Actividades y galerías

| ID | Tarea | Prioridad | Criterio de aceptación | Fase |
|----|-------|-----------|------------------------|------|
| E4-T1 | Crear migración `activities` | Alta | Tabla lista | 4 |
| E4-T2 | Crear CRUD de actividades | Alta | Artista crea/edita actividades | 4 |
| E4-T3 | Crear migración polimórfica `media` | Alta | Tabla lista | 4 |
| E4-T4 | Implementar uploader de imágenes | Alta | Imágenes se suben y asocian | 4 |
| E4-T5 | Implementar compresión automática | Alta | Imágenes reducen tamaño | 4 |
| E4-T6 | Generar miniaturas | Alta | Thumbnails disponibles | 4 |
| E4-T7 | Implementar orden y portada de galería | Media | Usuario ordena y elige portada | 4 |
| E4-T8 | Implementar estados de actividad | Alta | Flujo draft → published funciona | 4 |
| E4-T9 | Limpieza de archivos huérfanos | Baja | Command elimina archivos sin modelo | 4 |

---

## Épica 5: Propuestas y revisión

| ID | Tarea | Prioridad | Criterio de aceptación | Fase |
|----|-------|-----------|------------------------|------|
| E5-T1 | Crear migración `proposals` | Alta | Tabla lista | 5 |
| E5-T2 | Crear CRUD de propuestas | Alta | Artista crea propuestas | 5 |
| E5-T3 | Implementar flujo de revisión | Alta | Estados cambian correctamente | 5 |
| E5-T4 | Crear bandeja de revisión de propuestas | Alta | Revisores ven propuestas pendientes | 5 |
| E5-T5 | Implementar comentarios internos en propuestas | Alta | Comentarios con visibilidad controlada | 5 |
| E5-T6 | Implementar historial de revisiones | Media | Tabla `reviews` registra cambios | 5 |
| E5-T7 | Opcional: scoring de propuestas | Baja | Revisor puede asignar puntaje | 5 |

---

## Épica 6: Bandeja administrativa, auditoría y notificaciones

| ID | Tarea | Prioridad | Criterio de aceptación | Fase |
|----|-------|-----------|------------------------|------|
| E6-T1 | Crear migración `audit_logs` | Alta | Tabla lista | 6 |
| E6-T2 | Implementar servicio `AuditService` | Alta | Acciones críticas se registran | 6 |
| E6-T3 | Crear migración `notifications` | Alta | Tabla lista | 6 |
| E6-T4 | Implementar notificaciones en aplicación | Alta | Usuarios reciben notificaciones | 6 |
| E6-T5 | Implementar envío de emails vía cola | Alta | Emails encolados y enviados | 6 |
| E6-T6 | Crear filtros y búsqueda en listados | Alta | Filtros por estado, comuna, disciplina | 6 |
| E6-T7 | Implementar paginación eficiente | Alta | Listados paginados | 6 |
| E6-T8 | Crear dashboard administrativo | Media | Métricas y filtros visibles | 6 |

---

## Épica 7: Integración WordPress

| ID | Tarea | Prioridad | Criterio de aceptación | Fase |
|----|-------|-----------|------------------------|------|
| E7-T1 | Crear migración `wordpress_publications` | Alta | Tabla lista | 7 |
| E7-T2 | Crear servicio `WordPressPublisher` | Alta | Publica posts vía REST API | 7 |
| E7-T3 | Configurar Application Password en WordPress | Alta | Autenticación funciona | 7 |
| E7-T4 | Publicar ficha de artista en WordPress | Alta | Post creado con datos aprobados | 7 |
| E7-T5 | Publicar actividad en WordPress | Alta | Post creado con datos aprobados | 7 |
| E7-T6 | Implementar sincronización de actualizaciones | Media | Cambios se reflejan en WordPress | 7 |
| E7-T7 | Implementar reintentos y manejo de errores | Alta | Fallos registrados y reintentables | 7 |
| E7-T8 | Implementar despublicación | Media | Post pasa a draft en WordPress | 7 |

---

## Épica 8: Exportaciones y reportes

| ID | Tarea | Prioridad | Criterio de aceptación | Fase |
|----|-------|-----------|------------------------|------|
| E8-T1 | Instalar Laravel Excel | Media | Paquete disponible | 8 |
| E8-T2 | Exportar listado de artistas | Media | Excel/CSV generado | 8 |
| E8-T3 | Exportar actividades filtradas | Media | Excel/CSV generado | 8 |
| E8-T4 | Crear reportes básicos por comuna y disciplina | Baja | Dashboard con gráficos simples | 8 |

---

## Épica 9: Comunidad futura

| ID | Tarea | Prioridad | Criterio de aceptación | Fase |
|----|-------|-----------|------------------------|------|
| E9-T1 | Diseñar modelo de canales por disciplina | Baja | Documento técnico | 9 |
| E9-T2 | Implementar mensajería interna | Baja | Usuarios se envían mensajes | 9 |
| E9-T3 | Implementar moderación básica | Baja | Reportes de contenido | 9 |
| E9-T4 | Crear API pública cacheada | Baja | Endpoints de catálogo aprobado | 9 |

---

## Historias técnicas transversales

| ID | Tarea | Prioridad | Notas |
|----|-------|-----------|-------|
| HT-1 | Configurar Laravel Pint / CS Fixer | Media | Código consistente |
| HT-2 | Configurar PHPUnit y tests base | Alta | Cobertura mínima en autenticación |
| HT-3 | Configurar Laravel Telescope en local | Media | Debug de queries y mails |
| HT-4 | Configurar logging estructurado | Media | Canales diarios y de errores |
| HT-5 | Documentar comandos artisan útiles | Baja | En README de platform/ |
| HT-6 | Preparar estructura para tests de integración | Media | Tests de publicación WordPress |

---

## Definición de terminado (DoD)

- Código funcional en local.
- Migraciones ejecutadas sin errores.
- Seeders si aplica.
- Tests base si aplica.
- Commit con mensaje claro.
- Push a `origin/master`.
- WordPress sigue funcionando sin cambios.
