# AMA Maule - Documento arquitectonico base de Plataforma AMA

Fecha: 2026-07-07  
Estado: borrador tecnico base  
Sitio publico actual: `http://localhost/wordpress`  
Produccion prevista: `amamaule.cl` + subdominio de Plataforma AMA

## 1. Decision Arquitectonica

Para el volumen esperado, la opcion mas sana es:

```text
WordPress = sitio publico, contenido editorial y vitrina.
plataforma de gestion artistica = gestion privada de artistas, propuestas, actividades, galerias, revisiones y comunidad.
```

La plataforma debe vivir en subdominio:

```text
plataforma.amamaule.cl
```

En local puede vivir como:

```text
http://plataforma-amamaule.test
```

No se recomienda construir La plataforma pesada sobre `wp_posts`, `wp_postmeta` y ACF como nucleo principal, porque el proyecto considera cerca de 2000 artistas y crecimiento continuo de datos, imagenes, estados, comentarios, propuestas y eventos.

## 2. Objetivos

La plataforma debe permitir:

```text
- acceso privado para artistas
- cambio obligatorio de clave temporal en primer ingreso
- recuperacion de clave por correo
- perfil artistico individual
- libro de vida artistico
- registro de actividades
- propuestas de talleres
- galerias por actividad/evento
- revision administrativa por etapas
- anotaciones internas del equipo AMA
- notificaciones
- futura comunidad/chat/foro
- publicacion hacia WordPress solo de contenido aprobado
```

## 3. Stack Recomendado

Recomendacion principal:

```text
Backend: Laravel
Frontend: Laravel Blade + Livewire
Base de datos: MySQL/MariaDB
Storage: filesystem local al inicio, migrable a S3/R2
WordPress: sitio publico y API de publicacion
```

Motivo:

```text
- Laravel entrega login, recuperacion de clave, validaciones, roles, colas, jobs, mails y migraciones.
- Livewire permite dashboard moderno sin construir una SPA compleja desde el dia uno.
- MySQL propio permite tablas limpias, indices claros y consultas escalables.
```

Alternativa futura:

```text
Laravel API + React/Vue
```

Usarla solo si el dashboard requiere interacciones muy avanzadas.

## 4. Separacion De Responsabilidades

### WordPress

Responsable de:

```text
- home publica
- paginas institucionales
- noticias
- contenido editorial
- landing de talleres/eventos publicados
- galerias publicas aprobadas
- SEO publico
```

No debe cargar:

```text
- dashboard privado pesado
- chat en tiempo real
- historial completo de revisiones
- grandes flujos de subida/edicion de artistas
- consultas administrativas complejas
```

### Plataforma AMA

Responsable de:

```text
- autenticacion de artistas
- dashboard privado
- perfiles
- libro de vida
- propuestas
- galerias
- revision
- comentarios internos
- notificaciones
- reportes
- integracion con WordPress
```

## 5. Modelo De Usuarios

Los artistas ya existen en WordPress y deben importarse a La plataforma.

Regla:

```text
El email sera el identificador principal.
Cada artista tendra cuenta propia.
Cada artista representa a una persona responsable de su actividad/libro de vida.
```

Flujo inicial:

```text
1. Importar usuarios actuales desde wp_users.
2. Crear usuarios en intranet.
3. Guardar wordpress_user_id como referencia.
4. Asignar clave temporal.
5. Marcar must_change_password = true.
6. En primer ingreso, obligar cambio de clave.
7. Habilitar recuperar clave por correo.
```

Tabla base:

```text
users
- id
- wordpress_user_id nullable indexed
- name
- email unique indexed
- password
- must_change_password boolean
- email_verified_at nullable
- status enum: active, inactive, blocked
- last_login_at nullable
- created_at
- updated_at
```

## 6. Roles Y Permisos

Roles iniciales:

```text
super_admin
coordinador_ama
evaluador_ama
artista
moderador_comunidad
```

Permisos por modulo:

```text
artista:
- ver su dashboard
- editar su perfil
- crear/editar su libro de vida
- crear propuestas en borrador
- enviar propuestas
- responder observaciones
- subir imagenes dentro de limites
- participar en comunidad si esta habilitada

evaluador_ama:
- ver propuestas asignadas
- comentar internamente
- cambiar estados permitidos
- solicitar correcciones

coordinador_ama:
- gestionar artistas
- asignar evaluadores
- aprobar/rechazar/publicar
- ver reportes
- administrar eventos

super_admin:
- control total
- configuraciones
- integraciones
- auditoria
```

## 7. Entidades Principales

### Artista

```text
artists
- id
- user_id foreign key
- public_name
- legal_name nullable
- rut nullable
- phone nullable
- commune nullable
- region nullable
- discipline_main nullable
- biography text nullable
- profile_photo_path nullable
- public_visibility enum: private, ama_team, public
- profile_status enum: draft, pending_review, approved, observed
- created_at
- updated_at
```

### Disciplinas

```text
disciplines
- id
- name
- slug unique

artist_disciplines
- artist_id
- discipline_id
```

### Libro De Vida Artistico

```text
life_entries
- id
- artist_id indexed
- title
- description text
- entry_type enum: activity, milestone, work, workshop, recognition, collaboration, other
- happened_at date nullable
- location nullable
- visibility enum: private, ama_team, public
- status enum: draft, submitted, observed, approved, archived
- created_at
- updated_at
```

Imagenes:

```text
life_entry_images
- id
- life_entry_id indexed
- file_id indexed
- sort_order integer
- caption nullable
- created_at
```

### Actividades / Eventos

```text
events
- id
- title
- description text nullable
- starts_at datetime nullable
- ends_at datetime nullable
- location nullable
- status enum: draft, active, closed, archived
- created_by
- created_at
- updated_at
```

Relacion artista-evento:

```text
artist_events
- id
- artist_id indexed
- event_id indexed
- role nullable
- notes text nullable
- status enum: pending, confirmed, cancelled
- created_at
- updated_at
```

### Propuestas De Talleres

```text
workshop_proposals
- id
- artist_id indexed
- title
- summary text
- description longtext
- discipline_id nullable indexed
- duration_minutes integer nullable
- capacity integer nullable
- technical_requirements text nullable
- materials text nullable
- preferred_dates text nullable
- target_audience nullable
- status enum: draft, submitted, in_review, observed, corrected, approved, rejected, published, archived
- submitted_at nullable
- approved_at nullable
- published_at nullable
- created_at
- updated_at
```

Historial de estados:

```text
proposal_status_history
- id
- proposal_id indexed
- old_status nullable
- new_status
- changed_by indexed
- comment text nullable
- created_at
```

Revision:

```text
proposal_reviews
- id
- proposal_id indexed
- reviewer_id indexed
- score integer nullable
- recommendation enum: approve, observe, reject, none
- comment text nullable
- created_at
- updated_at
```

### Galerias

```text
galleries
- id
- owner_type enum: artist, event, proposal, life_entry
- owner_id indexed
- title
- description text nullable
- visibility enum: private, ama_team, public
- status enum: draft, submitted, approved, archived
- created_at
- updated_at
```

```text
gallery_images
- id
- gallery_id indexed
- file_id indexed
- sort_order integer
- caption nullable
- created_at
```

### Archivos

```text
files
- id
- owner_user_id indexed
- disk
- original_name
- stored_path
- mime_type
- size_bytes
- width nullable
- height nullable
- checksum indexed
- status enum: active, quarantined, deleted
- created_at
- updated_at
```

Derivados:

```text
file_variants
- id
- file_id indexed
- variant enum: thumb, medium, large, webp
- stored_path
- width
- height
- size_bytes
- created_at
```

## 8. Imagenes Y Storage

Regla inicial:

```text
Maximo recomendado: 10 imagenes por actividad/propuesta.
Tamano maximo inicial: 5 MB por imagen original.
Formatos aceptados: jpg, jpeg, png, webp.
Generar WebP y thumbnails.
No servir originales pesados al frontend publico.
```

Estructura local:

```text
storage/app/artists/{artist_id}/events/{event_id}/
storage/app/artists/{artist_id}/life/{entry_id}/
storage/app/proposals/{proposal_id}/
```

Futuro:

```text
Cloudflare R2 / Amazon S3 / Wasabi
```

## 9. Revision Por Etapas

Flujo recomendado para propuestas:

```text
draft
submitted
in_review
observed
corrected
approved
rejected
published
archived
```

Reglas:

```text
- El artista puede editar en draft, observed y corrected.
- El artista no edita mientras esta in_review, approved o published.
- Todo cambio de estado se guarda en proposal_status_history.
- Las observaciones deben quedar ligadas al usuario que las hizo.
```

## 10. Anotaciones Internas

```text
internal_notes
- id
- subject_type enum: artist, proposal, event, life_entry
- subject_id indexed
- author_id indexed
- message text
- visibility enum: ama_team, reviewer_group, private_admin
- expires_at nullable
- resolved_at nullable
- created_at
- updated_at
```

Politica recomendada:

```text
- notas normales: archivar 30 dias despues de resueltas
- notas criticas: conservar
- no borrar sin rastro; usar resolved_at/archived
```

## 11. Comunidad / Chat

Recomendacion:

```text
Fase 1: foro/muro asincronico.
Fase 2: comentarios por evento/propuesta.
Fase 3: chat real-time solo si se justifica.
```

Tablas fase 1:

```text
community_topics
- id
- author_id
- title
- body
- category
- status enum: open, closed, hidden
- created_at
- updated_at

community_replies
- id
- topic_id
- author_id
- body
- status enum: visible, hidden
- created_at
- updated_at
```

No implementar WebSockets al inicio salvo necesidad real.

## 12. Notificaciones

```text
notifications
- id
- user_id indexed
- type
- title
- body
- data json nullable
- read_at nullable
- created_at
```

Canales:

```text
- dentro de La plataforma
- email
- futuro: WhatsApp/API externa solo si hay presupuesto y politica clara
```

## 13. Integracion Con WordPress

Objetivo:

```text
La plataforma publica hacia WordPress solo contenido aprobado.
```

Opciones:

```text
1. WordPress REST API con Application Passwords.
2. Plugin puente propio en WordPress.
```

Recomendacion:

```text
Crear plugin pequeno: ama-platform-bridge
```

Responsabilidades del plugin:

```text
- recibir contenido aprobado desde intranet
- crear/actualizar posts publicos
- asociar imagenes optimizadas
- exponer endpoints seguros
- no manejar logica interna de La plataforma
```

Contenido publicable:

```text
- perfil publico de artista
- taller aprobado
- evento aprobado
- galeria aprobada
- actividad destacada
```

## 14. Importacion Desde WordPress

Origen:

```text
wp_users
wp_usermeta
```

Proceso:

```text
1. Exportar usuarios WordPress.
2. Normalizar emails.
3. Crear users en intranet.
4. Asociar wordpress_user_id.
5. Crear artists.
6. Asignar clave temporal.
7. Marcar must_change_password = true.
8. Enviar correo de activacion.
```

Validaciones:

```text
- email unico
- usuario bloqueado si no tiene email valido
- no migrar passwords WordPress como fuente principal
- registrar import batch id
```

Tablas de importacion:

```text
import_batches
- id
- source
- filename nullable
- status
- created_by
- created_at

import_rows
- id
- import_batch_id
- source_id nullable
- email
- status
- error_message nullable
- created_at
```

## 15. Seguridad

Requisitos:

```text
- HTTPS obligatorio en produccion
- password hashing nativo de Laravel
- cambio obligatorio de clave temporal
- recuperar clave por email
- rate limiting en login
- bloqueo temporal por intentos fallidos
- CSRF en formularios
- validacion de archivos
- escaneo basico de mime type
- permisos por rol y ownership
- logs de auditoria
```

Auditoria:

```text
audit_logs
- id
- actor_id nullable
- action
- subject_type nullable
- subject_id nullable
- ip_address nullable
- user_agent nullable
- data json nullable
- created_at
```

## 16. Rendimiento

Principios:

```text
- indices en foreign keys y estados
- paginacion obligatoria
- busquedas con indices
- no cargar imagenes originales
- lazy loading
- colas para procesamiento de imagenes
- jobs para emails
- cache de consultas frecuentes
- separar storage de base de datos
```

Indices clave:

```text
users.email
artists.user_id
workshop_proposals.artist_id
workshop_proposals.status
proposal_status_history.proposal_id
events.starts_at
files.checksum
notifications.user_id/read_at
```

## 17. Backups

Minimo:

```text
- dump diario de base de la plataforma
- backup diario de storage
- retencion local 7 dias
- retencion externa 30 dias
- prueba de restauracion mensual
```

Separar:

```text
- backup WordPress
- backup de la plataforma
- backup storage de archivos
```

## 18. Ambientes

```text
local:
  WordPress: http://localhost/wordpress
  Plataforma: http://plataforma-amamaule.test

staging:
  staging.amamaule.cl
  plataforma-staging.amamaule.cl

produccion:
  amamaule.cl
  plataforma.amamaule.cl
```

## 19. Fases De Implementacion

### Fase 0 - Preparacion

```text
- congelar WordPress estable
- documentar plugins activos
- definir repositorio de la plataforma
- definir dominio/subdominio
- definir base de datos
```

### Fase 1 - Autenticacion E Importacion

```text
- crear Laravel
- crear users/artists
- importar usuarios WordPress
- clave temporal
- cambio obligatorio de clave
- recuperar clave
```

### Fase 2 - Dashboard Artista

```text
- inicio privado
- perfil
- datos de contacto
- disciplinas
- estado de perfil
```

### Fase 3 - Libro De Vida

```text
- crear entradas
- editar borradores
- subir imagenes
- visibilidad
- revision basica
```

### Fase 4 - Propuestas Y Talleres

```text
- formulario propuesta
- estados
- historial
- observaciones
- revision equipo AMA
```

### Fase 5 - Galerias Y Eventos

```text
- eventos
- galerias por evento/actividad
- optimizacion de imagenes
- limites de carga
```

### Fase 6 - Notificaciones Y Comentarios Internos

```text
- notificaciones in-app
- emails
- internal_notes
- expiracion/archivo
```

### Fase 7 - Puente WordPress

```text
- plugin ama-platform-bridge
- publicar contenido aprobado
- sincronizar actualizaciones
```

### Fase 8 - Comunidad

```text
- foro/muro asincronico
- moderacion
- categorias
- reportes
```

### Fase 9 - Produccion

```text
- hardening
- backups
- monitoreo
- logs
- pruebas de carga
- documentacion operativa
```

## 20. Criterios De Exito

```text
- 2000 artistas pueden existir sin degradar WordPress.
- El sitio publico no se congela por actividad interna.
- Los artistas no entran al wp-admin para su flujo principal.
- Las imagenes se limitan y optimizan.
- Cada propuesta tiene historial y responsable.
- El equipo AMA puede revisar sin perder trazabilidad.
- Solo contenido aprobado llega al sitio publico.
- La arquitectura puede crecer sin rehacer todo.
```

## 21. Decisiones Pendientes

```text
- hosting final para la plataforma
- proveedor de email transaccional
- storage local vs S3/R2
- si el directorio publico de artistas vive en WordPress o plataforma embebida
- plazo de retencion exacto de comentarios internos
- cantidad final de imagenes permitidas por actividad
- si habra chat real-time o solo foro en primera version
```

## 22. Recomendacion Final

Construir:

```text
plataforma.amamaule.cl
Laravel + MySQL + Livewire
```

Mantener:

```text
amamaule.cl
WordPress optimizado como sitio publico
```

Evitar:

```text
meter todo el flujo de 2000 artistas en ACF/wp_postmeta
activar plugins pesados de comunidad sin diseno previo
usar WordPress admin como dashboard principal para artistas
```

Esta arquitectura permite partir ordenado, crecer con control y proteger el sitio publico de la carga operativa de La plataforma.

