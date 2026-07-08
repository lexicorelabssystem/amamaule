# Plataforma AMA - Arquitectura de Software

**Fecha:** 2026-07-08  
**Estado:** Arquitectura base para MVP y escalabilidad futura  
**Stack:** Laravel 11/12 + MySQL/MariaDB + Blade + Livewire + Tailwind CSS

---

## 1. Visión general del sistema

Plataforma AMA es el sistema privado de gestión artística de AMA Maule. WordPress sigue siendo el sitio público, la vitrina editorial y el catálogo visible. La plataforma Laravel es el núcleo operacional privado.

```text
┌─────────────────────────────────────────────────────────────────────┐
│                         USUARIOS                                    │
│  Artistas        Revisores        Equipo AMA        Administradores │
└──────────────────┬─────────────────┬─────────────────┬──────────────┘
                   │                 │                 │
┌──────────────────▼─────────────────▼─────────────────▼──────────────┐
│              Plataforma AMA (Laravel)                               │
│  Autenticación · Perfiles · Actividades · Propuestas · Revisiones   │
│  Notificaciones · Auditoría · Publicación controlada a WordPress    │
└──────────────────────────────────┬──────────────────────────────────┘
                                   │
                    ┌──────────────┴──────────────┐
                    │                             │
┌───────────────────▼──────────┐   ┌──────────────▼──────────────┐
│  Base de datos propia        │   │  Storage local / futuro S3  │
│  ama_plataforma_*            │   │  Imágenes · documentos      │
└──────────────────────────────┘   └─────────────────────────────┘
                                   │
                    ┌──────────────▼──────────────┐
                    │  WordPress (solo lectura/   │
                    │  publicación controlada)    │
                    │  http://localhost/wordpress │
                    └─────────────────────────────┘
```

### Principios rectores

1. **Separación estricta:** WordPress nunca es fuente de verdad para datos operacionales.
2. **Lista blanca de publicación:** Solo contenido aprobado llega a WordPress.
3. **Seguridad por defecto:** políticas, roles, validación, auditoría y rate limiting.
4. **Escalabilidad progresiva:** arquitectura modular que permita agregar chat, API pública, reportes.
5. **Experiencia simple:** Blade + Livewire para el MVP; SPA solo si se justifica.

---

## 2. Arquitectura por capas

```text
┌─────────────────────────────────────────────┐
│  Presentación                               │
│  Blade · Livewire · Tailwind · Alpine.js    │
│  Dashboards · formularios · galerías        │
├─────────────────────────────────────────────┤
│  Aplicación                                 │
│  Controllers · Form Requests · Jobs · Mail  │
│  Orquesta casos de uso; delega al dominio   │
├─────────────────────────────────────────────┤
│  Dominio                                    │
│  Models · Policies · Actions/Services       │
│  Reglas de negocio puro; sin I/O directo    │
├─────────────────────────────────────────────┤
│  Infraestructura                            │
│  Auth · Storage · Queue · Mail · WordPress  │
│  Integraciones externas encapsuladas        │
├─────────────────────────────────────────────┤
│  Persistencia                               │
│  MySQL/MariaDB · migraciones · índices      │
│  Eloquent como ORM; queries optimizadas     │
└─────────────────────────────────────────────┘
```

### Responsabilidades

| Capa | Responsabilidad | Ejemplo |
|------|-----------------|---------|
| Presentación | Renderizar UI, capturar eventos | Componente Livewire `ArtistProfileForm` |
| Aplicación | Validar entrada, disparar jobs, responder HTTP | `ArtistController@store`, `ImportArtistsJob` |
| Dominio | Modelar reglas, estados, permisos | Model `Artist`, Policy `ArtistPolicy`, Action `SubmitProfile` |
| Infraestructura | Comunicarse con sistemas externos | `WordPressPublisher`, `ImageProcessor` |
| Persistencia | Almacenar y recuperar datos | Migraciones, Eloquent, scopes |

### Reglas para mantener capas limpias

- Los controladores solo reciben, validan y responden; no contienen lógica de negocio.
- Las reglas de negocio viven en modelos, policies, actions o service classes.
- Las consultas complejas usan scopes o query objects.
- Las integraciones con WordPress pasan por un adapter (`WordPressPublisher`).
- Los jobs procesan imágenes, correos, exportaciones y publicaciones.

---

## 3. Módulos principales del sistema

### 3.1 Autenticación

- **Objetivo:** Acceso seguro con clave temporal, cambio obligatorio, recuperación y trazabilidad.
- **Entidades:** `User`, `PasswordReset`, `LoginAttempt`.
- **Pantallas:** login, cambio de clave obligatorio, recuperar contraseña, verificar email.
- **Permisos:** acceso público a login; cambio de clave propio para todos.

### 3.2 Usuarios

- **Objetivo:** Gestión de cuentas internas y artistas.
- **Entidades:** `User` (rol asignado vía Spatie).
- **Pantallas:** listado de usuarios, crear/editar usuario, detalle.
- **Permisos:** `users.view`, `users.create`, `users.edit`, `users.delete`, `users.impersonate`.

### 3.3 Roles y permisos

- **Objetivo:** Control de acceso basado en roles.
- **Entidades:** `roles`, `permissions`, `model_has_roles`, `model_has_permissions`.
- **Pantallas:** asignar roles, gestionar permisos.
- **Permisos:** `roles.manage`.

### 3.4 Artistas

- **Objetivo:** Núcleo del sistema; gestión de perfiles artísticos.
- **Entidades:** `Artist`, `ArtistProfile`, `ArtistDiscipline`, `ArtistTerritory`.
- **Pantallas:** mi perfil, listado de artistas, ficha, revisión, importación.
- **Permisos:** `artists.view_own`, `artists.edit_own`, `artists.review`, `artists.publish`.

### 3.5 Disciplinas

- **Objetivo:** Catálogo normalizado de disciplinas artísticas.
- **Entidades:** `Discipline`.
- **Pantallas:** CRUD de disciplinas.
- **Permisos:** `disciplines.manage`.

### 3.6 Territorios / Comunas

- **Objetivo:** Normalización geográfica.
- **Entidades:** `Territory`.
- **Pantallas:** CRUD de comunas/regiones.
- **Permisos:** `territories.manage`.

### 3.7 Perfil artístico

- **Objetivo:** Datos públicos y privados del artista.
- **Entidades:** `ArtistProfile`.
- **Pantallas:** edición de perfil, preview, comparación de versiones.
- **Permisos:** `profile.edit_own`, `profile.review`.

### 3.8 Actividades

- **Objetivo:** Registrar actividades, eventos o participaciones del artista.
- **Entidades:** `Activity`, `ActivityMedia`.
- **Pantallas:** crear/editar actividad, galería, listado, revisión.
- **Permisos:** `activities.create_own`, `activities.review`, `activities.publish`.

### 3.9 Galerías

- **Objetivo:** Gestión de imágenes asociadas a actividades o perfiles.
- **Entidades:** `Media` (polimórfica a `Artist`, `Activity`, `LifebookEntry`).
- **Pantallas:** uploader, ordenar, eliminar, seleccionar portada.
- **Permisos:** `media.manage_own`, `media.review`.

### 3.10 Propuestas / Talleres

- **Objetivo:** Flujo de postulación y revisión de propuestas.
- **Entidades:** `Proposal`, `ProposalReview`.
- **Pantallas:** crear propuesta, bandeja de revisión, detalle, historial.
- **Permisos:** `proposals.create_own`, `proposals.review`, `proposals.approve`.

### 3.11 Revisiones

- **Objetivo:** Cambios de estado y decisiones del equipo AMA.
- **Entidades:** `Review` (polimórfica).
- **Pantallas:** bandeja de entrada, filtros, acciones masivas.
- **Permisos:** `reviews.manage`.

### 3.12 Comentarios internos

- **Objetivo:** Comunicación del equipo sobre perfiles, actividades y propuestas.
- **Entidades:** `InternalComment`.
- **Pantallas:** hilo de comentarios, visibilidad.
- **Permisos:** `comments.view_internal`, `comments.create_internal`.

### 3.13 Libro de vida

- **Objetivo:** Cronología artística del artista.
- **Entidades:** `LifebookEntry`.
- **Pantallas:** línea de tiempo, crear entrada, asociar actividad.
- **Permisos:** `lifebook.manage_own`, `lifebook.review`.

### 3.14 Notificaciones

- **Objetivo:** Informar cambios de estado y acciones relevantes.
- **Entidades:** `Notification` (database), emails, future WhatsApp/SMS.
- **Pantallas:** centro de notificaciones, preferencias.
- **Permisos:** acceso propio.

### 3.15 Publicación hacia WordPress

- **Objetivo:** Publicar contenido aprobado en WordPress de forma controlada.
- **Entidades:** `WordPressPublication`.
- **Pantallas:** publicar ficha/actividad, log de publicaciones, reintentar.
- **Permisos:** `wordpress.publish`.

### 3.16 Auditoría

- **Objetivo:** Trazabilidad de acciones relevantes.
- **Entidades:** `AuditLog`.
- **Pantallas:** log de auditoría, filtros.
- **Permisos:** `audit.view`.

### 3.17 Exportaciones

- **Objetivo:** Exportar datos filtrados a Excel/CSV.
- **Entidades:** `Export`.
- **Pantallas:** configurar exportación, descargar archivo.
- **Permisos:** `exports.create`.

### 3.18 Configuración del sistema

- **Objetivo:** Parámetros globales: plazos, límites de imágenes, SMTP, WordPress API.
- **Entidades:** `Setting`.
- **Pantallas:** panel de configuración.
- **Permisos:** `settings.manage`.

---

## 4. Roles y permisos

| Rol | Descripción |
|-----|-------------|
| `super_admin` | Control total del sistema. |
| `admin` | Gestiona usuarios, configuración y publicaciones. |
| `operativo` | Revisa perfiles, actividades y propuestas; comenta internamente. |
| `revisor` | Enfocado en curaduría y aprobación de contenido. |
| `comunicaciones` | Publica contenido aprobado en WordPress; gestiona editorial. |
| `artista` | Gestiona su perfil, actividades, propuestas y libro de vida. |
| `soporte` | Ve información de usuarios para ayuda; no modifica contenido. |

### Matriz de permisos (resumen)

| Acción | Super Admin | Admin | Operativo | Revisor | Comunicaciones | Artista | Soporte |
|--------|:-----------:|:-----:|:---------:|:-------:|:--------------:|:-------:|:-------:|
| Ver dashboard propio | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Editar perfil propio | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Ver perfiles artistas | ✅ | ✅ | ✅ | ✅ | ✅ | Solo el suyo | Limitado |
| Revisar perfiles | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Aprobar/rechazar | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Publicar en WordPress | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Crear actividades | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Revisar actividades | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Crear propuestas | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Revisar propuestas | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Comentarios internos | ✅ | ✅ | ✅ | ✅ | ✅ | Solo visibles cuando se indique | ❌ |
| Gestionar usuarios | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Gestionar roles | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Importar artistas | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Exportar datos | ✅ | ✅ | ✅ | ❌ | ✅ | Solo propios | ❌ |
| Ver auditoría | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | Limitado |
| Configuración | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

*Los permisos se implementan con Spatie Laravel Permission y Laravel Policies.*

---

## 5. Modelo de base de datos inicial

### Convenciones

- Clave primaria: `id` bigint unsigned auto-increment.
- Timestamps: `created_at`, `updated_at`.
- Soft deletes: `deleted_at` donde aplique.
- UUIDs: opcional para datos públicos/API; por ahora usamos IDs internos con slugs.
- Estados: enum o small string; documentados en cada tabla.
- Auditoría: campos `created_by`, `updated_by` donde sea relevante.

### Tablas

#### users

| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint unsigned PK | |
| name | varchar(255) | Nombre completo o artístico |
| email | varchar(255) unique | Identificador principal |
| email_verified_at | timestamp nullable | |
| password | varchar(255) | Hash Bcrypt |
| must_change_password | boolean default true | Obligar cambio en primer login |
| status | enum('active','inactive','suspended') default 'active' | |
| last_login_at | timestamp nullable | |
| last_login_ip | varchar(45) nullable | |
| failed_login_attempts | tinyint unsigned default 0 | |
| locked_until | timestamp nullable | |
| wordpress_user_id | bigint unsigned nullable | Referencia externa a wp_users |
| remember_token | varchar(100) nullable | |
| timestamps | | |
| soft deletes | | |

**Índices:** `email` (unique), `wordpress_user_id` (unique nullable), `status`, `must_change_password`.

#### roles, permissions, model_has_roles, model_has_permissions, role_has_permissions

Generadas por `spatie/laravel-permission`.

#### disciplines

| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint unsigned PK | |
| name | varchar(255) | Danza, Música, Teatro, etc. |
| slug | varchar(255) unique | |
| description | text nullable | |
| is_active | boolean default true | |
| timestamps | | |

**Índices:** `slug` (unique), `is_active`.

#### territories

| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint unsigned PK | |
| name | varchar(255) | Nombre comuna |
| region | varchar(255) | Región |
| slug | varchar(255) unique | |
| is_active | boolean default true | |
| timestamps | | |

**Índices:** `slug` (unique), `region`, `is_active`.

#### artists

| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint unsigned PK | |
| user_id | bigint unsigned FK -> users.id | Uno a uno |
| legal_name | varchar(255) nullable | Nombre legal |
| artistic_name | varchar(255) nullable | Nombre artístico |
| slug | varchar(255) unique | Para URL pública futura |
| territory_id | bigint unsigned FK -> territories.id nullable | |
| phone | varchar(50) nullable | |
| website | varchar(255) nullable | |
| social_networks | json nullable | Instagram, Facebook, etc. |
| status | enum('draft','submitted','in_review','needs_changes','approved','rejected','archived') default 'draft' | |
| submitted_at | timestamp nullable | |
| approved_at | timestamp nullable | |
| approved_by | bigint unsigned FK -> users.id nullable | |
| profile_views | bigint unsigned default 0 | |
| timestamps | | |
| soft deletes | | |

**Índices:** `user_id` (unique), `slug` (unique), `territory_id`, `status`, `approved_by`, (`status`, `territory_id`).

#### artist_discipline

| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint unsigned PK | |
| artist_id | bigint unsigned FK -> artists.id | |
| discipline_id | bigint unsigned FK -> disciplines.id | |
| is_primary | boolean default false | |
| timestamps | | |

**Índices:** (`artist_id`, `discipline_id`) unique, `discipline_id`.

#### artist_profiles

| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint unsigned PK | |
| artist_id | bigint unsigned FK -> artists.id unique | |
| short_bio | text nullable | Máx. 500 caracteres |
| long_bio | longtext nullable | |
| profile_image_id | bigint unsigned nullable | Referencia a media |
| header_image_id | bigint unsigned nullable | |
| extra_data | json nullable | Campos flexibles futuros |
| timestamps | | |

**Índices:** `artist_id` (unique).

#### activities

| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint unsigned PK | |
| artist_id | bigint unsigned FK -> artists.id | |
| title | varchar(255) | |
| slug | varchar(255) unique | |
| description | longtext nullable | |
| activity_type | varchar(100) nullable | Taller, exposición, etc. |
| start_date | date nullable | |
| end_date | date nullable | |
| location | varchar(255) nullable | |
| territory_id | bigint unsigned FK -> territories.id nullable | |
| status | enum('draft','submitted','review','approved','rejected','published','archived') default 'draft' | |
| submitted_at | timestamp nullable | |
| approved_at | timestamp nullable | |
| approved_by | bigint unsigned FK -> users.id nullable | |
| published_to_wordpress_at | timestamp nullable | |
| wordpress_post_id | bigint unsigned nullable | |
| timestamps | | |
| soft deletes | | |

**Índices:** `artist_id`, `slug` (unique), `territory_id`, `status`, `approved_by`, (`status`, `artist_id`), (`status`, `territory_id`).

#### proposals

| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint unsigned PK | |
| artist_id | bigint unsigned FK -> artists.id | |
| title | varchar(255) | |
| slug | varchar(255) unique | |
| summary | text nullable | |
| description | longtext nullable | |
| objectives | longtext nullable | |
| requirements | longtext nullable | |
| status | enum('draft','submitted','in_review','changes_requested','approved','rejected','archived') default 'draft' | |
| submitted_at | timestamp nullable | |
| reviewed_at | timestamp nullable | |
| reviewed_by | bigint unsigned FK -> users.id nullable | |
| score | tinyint unsigned nullable | 1-5 opcional |
| timestamps | | |
| soft deletes | | |

**Índices:** `artist_id`, `slug` (unique), `status`, `reviewed_by`, (`status`, `artist_id`).

#### media

| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint unsigned PK | |
| model_type | varchar(255) | Polimórfico |
| model_id | bigint unsigned | |
| collection_name | varchar(100) default 'default' | profile, gallery, cover, etc. |
| file_name | varchar(255) | Nombre original |
| disk | varchar(100) default 'local' | local / s3 |
| path | varchar(500) | Ruta relativa |
| mime_type | varchar(255) | |
| size_bytes | bigint unsigned | |
| width | int unsigned nullable | |
| height | int unsigned nullable | |
| caption | text nullable | |
| sort_order | smallint unsigned default 0 | |
| is_featured | boolean default false | |
| generated_conversions | json nullable | Thumbnails generados |
| created_by | bigint unsigned FK -> users.id | |
| timestamps | | |
| soft deletes | | |

**Índices:** (`model_type`, `model_id`), `collection_name`, `created_by`, (`model_type`, `model_id`, `collection_name`).

#### reviews

| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint unsigned PK | |
| reviewable_type | varchar(255) | Polimórfico |
| reviewable_id | bigint unsigned | |
| reviewer_id | bigint unsigned FK -> users.id | |
| from_status | varchar(50) nullable | Estado anterior |
| to_status | varchar(50) | Nuevo estado |
| comment | text nullable | |
| is_internal | boolean default true | false = visible al artista |
| timestamps | | |

**Índices:** (`reviewable_type`, `reviewable_id`), `reviewer_id`, `to_status`.

#### internal_comments

| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint unsigned PK | |
| commentable_type | varchar(255) | Polimórfico |
| commentable_id | bigint unsigned | |
| user_id | bigint unsigned FK -> users.id | |
| content | text | |
| is_internal | boolean default true | |
| parent_id | bigint unsigned FK -> internal_comments.id nullable | Respuestas |
| timestamps | | |
| soft deletes | | |

**Índices:** (`commentable_type`, `commentable_id`), `user_id`, `parent_id`.

#### lifebook_entries

| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint unsigned PK | |
| artist_id | bigint unsigned FK -> artists.id | |
| title | varchar(255) | |
| entry_date | date nullable | |
| content | longtext | |
| visibility | enum('private','team','public_candidate') default 'private' | |
| activity_id | bigint unsigned FK -> activities.id nullable | |
| status | enum('draft','submitted','approved','rejected','archived') default 'draft' | |
| timestamps | | |
| soft deletes | | |

**Índices:** `artist_id`, `activity_id`, `visibility`, `status`.

#### notifications

| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint unsigned PK | |
| user_id | bigint unsigned FK -> users.id | |
| type | varchar(100) | Tipo de notificación |
| title | varchar(255) | |
| body | text | |
| data | json nullable | Payload extra |
| read_at | timestamp nullable | |
| sent_via_email | boolean default false | |
| sent_at | timestamp nullable | |
| timestamps | | |

**Índices:** `user_id`, `read_at`, (`user_id`, `read_at`), `type`.

#### wordpress_publications

| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint unsigned PK | |
| publishable_type | varchar(255) | Polimórfico |
| publishable_id | bigint unsigned | |
| wordpress_post_id | bigint unsigned | |
| wordpress_url | text nullable | |
| status | enum('pending','published','failed','updated','unpublished') default 'pending' | |
| payload_sent | json | |
| response_received | json nullable | |
| published_by | bigint unsigned FK -> users.id | |
| published_at | timestamp nullable | |
| failed_at | timestamp nullable | |
| retry_count | tinyint unsigned default 0 | |
| timestamps | | |

**Índices:** (`publishable_type`, `publishable_id`), `wordpress_post_id`, `status`, `published_by`.

#### audit_logs

| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint unsigned PK | |
| user_id | bigint unsigned FK -> users.id nullable | Sistema si es null |
| action | varchar(100) | created, updated, deleted, login, published, etc. |
| auditable_type | varchar(255) nullable | |
| auditable_id | bigint unsigned nullable | |
| description | text | |
| ip_address | varchar(45) nullable | |
| user_agent | text nullable | |
| old_values | json nullable | |
| new_values | json nullable | |
| timestamps | | |

**Índices:** `user_id`, (`auditable_type`, `auditable_id`), `action`, `created_at`.

#### imports

| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint unsigned PK | |
| type | varchar(100) | artists, users |
| file_path | varchar(500) | |
| status | enum('pending','processing','completed','failed','partial') default 'pending' | |
| total_rows | int unsigned default 0 | |
| processed_rows | int unsigned default 0 | |
| failed_rows | int unsigned default 0 | |
| created_by | bigint unsigned FK -> users.id | |
| completed_at | timestamp nullable | |
| timestamps | | |

**Índices:** `type`, `status`, `created_by`.

#### import_rows

| Campo | Tipo | Notas |
|-------|------|-------|
| id | bigint unsigned PK | |
| import_id | bigint unsigned FK -> imports.id | |
| row_number | int unsigned | |
| raw_data | json | |
| status | enum('pending','processed','failed','skipped') default 'pending' | |
| error_message | text nullable | |
| created_model_type | varchar(255) nullable | |
| created_model_id | bigint unsigned nullable | |
| timestamps | | |

**Índices:** `import_id`, `status`, (`import_id`, `row_number`).

---

## 6. Estados y workflows

### 6.1 Perfil de artista

```text
draft ──► submitted ──► in_review ──► approved
                        │
                        ▼
                  needs_changes ──► rejected
                        │
                        └────────► archived (desde cualquier estado final)
```

| Transición | Quién | Acciones |
|------------|-------|----------|
| draft → submitted | Artista | Validar campos mínimos; notificar equipo |
| submitted → in_review | Sistema/Equipo | Asignar revisor; notificar revisor |
| in_review → approved | Revisor/Operativo | Perfil público listo; notificar artista |
| in_review → needs_changes | Revisor | Enviar comentarios; notificar artista |
| needs_changes → submitted | Artista | Notificar revisor |
| any → rejected | Admin/Revisor | Motivo obligatorio; notificar artista |
| any → archived | Admin | No visible; conserva historial |

### 6.2 Actividad

```text
draft ──► submitted ──► review ──► approved ──► published
                                      │
                                      ▼
                                  rejected / archived
```

| Transición | Quién | Acciones |
|------------|-------|----------|
| approved → published | Comunicaciones/Admin | Publica en WordPress; registra `wordpress_publications` |
| published → updated | Comunicaciones/Admin | Sincroniza cambios a WordPress |
| published → unpublished | Comunicaciones/Admin | Retira de WordPress |

### 6.3 Propuesta / Taller

```text
draft ──► submitted ──► in_review ──► changes_requested ──► approved
                                      │
                                      ▼
                                  rejected / archived
```

| Transición | Quién | Acciones |
|------------|-------|----------|
| approved → activity | Admin/Operativo | Opcional: convertir propuesta a actividad pública |
| approved → wordpress_publication | Comunicaciones | Publicar ficha resumen en WordPress |

### 6.4 Publicación WordPress

```text
pending ──► published ──► updated
   │
   ▼
failed (con reintentos)
   │
   ▼
unpublished
```

| Transición | Quién | Acciones |
|------------|-------|----------|
| pending → published | Sistema vía REST API | Guardar `wordpress_post_id` y URL |
| pending → failed | Sistema | Log de error; notificar admin; reintentar manualmente |
| published → updated | Sistema/Admin | PUT a WordPress con cambios |
| published → unpublished | Admin | Cambiar status a draft en WordPress |

---

## 7. Flujo de autenticación

### 7.1 Creación de usuario

1. Admin crea usuario manualmente o importación masiva.
2. Sistema genera contraseña temporal aleatoria segura (16+ caracteres).
3. Sistema marca `must_change_password = true`.
4. Se envía email con credenciales temporales (job en cola).
5. Registro en `audit_logs`.

### 7.2 Primer login

1. Usuario ingresa email y clave temporal.
2. Laravel valida credenciales.
3. Middleware `MustChangePassword` detecta flag.
4. Redirige a formulario de cambio obligatorio.
5. Usuario ingresa nueva clave y confirmación.
6. Sistema valida política de contraseña.
7. Actualiza hash, limpia flag, regenera sesión.
8. Redirige al dashboard.

### 7.3 Recuperación de contraseña

1. Usuario solicita reset vía email.
2. Laravel genera token de un solo uso con expiración (60 min).
3. Se envía link seguro.
4. Usuario accede, establece nueva clave.
5. Sistema invalida token y sesiones previas.

### 7.4 Bloqueo y suspensión

- `status = suspended`: no puede iniciar sesión; mensaje genérico.
- `locked_until`: bloqueo temporal tras N intentos fallidos.
- `failed_login_attempts` se reinicia tras login exitoso.

### 7.5 Seguridad

- Hash de contraseñas: `bcrypt` (default Laravel).
- Rate limiting: 5 intentos por minuto en login, 3 solicitudes de reset por hora.
- Sesiones: invalidación en cambio de clave; tiempo de expiración configurable.
- Política de claves: mínimo 10 caracteres, mayúscula, minúscula, número, símbolo.
- 2FA: preparar campo `two_factor_secret` desde el modelo; implementar en fase posterior.

---

## 8. Importación inicial de artistas

### Estrategia

1. **Exportar desde WordPress** usando SQL o plugin de exportación de usuarios a CSV.
2. **Normalizar** emails (trim, lowercase), eliminar duplicados.
3. **Validar** emails únicos; detectar comunas y disciplinas contra catálogos normalizados.
4. **Preview** del resultado antes de importar.
5. **Importación por lotes** (chunks de 100 filas) usando Laravel Queue.
6. **Registro** de errores por fila en `import_rows`.
7. **Generación** de usuarios con rol `artista` y clave temporal.
8. **Envío opcional** de credenciales (recomendado desactivar en pruebas masivas).

### Flujo

```text
Subir CSV ──► Validar encabezados ──► Preview ──► Confirmar
                                    │
                                    ▼
                           Job ImportArtists
                                    │
                    ┌───────────────┼───────────────┐
                    ▼               ▼               ▼
                Crear User      Crear Artist    Asignar rol
                    │               │               │
                    ▼               ▼               ▼
                Generar clave   Normalizar      Enviar email
                temporal        disciplinas     opcional
```

### Campos CSV esperados

`email`, `legal_name`, `artistic_name`, `phone`, `territory`, `disciplines`, `short_bio`, `website`.

---

## 9. Perfil del artista

### Campos editables por el artista

- Nombre artístico
- Nombre legal
- Comuna / región
- Teléfono
- Email (solo lectura; cambio mediante solicitud)
- Disciplinas (múltiples, una primaria)
- Biografía corta (máx. 500 caracteres)
- Biografía larga
- Redes sociales (JSON)
- Sitio web
- Imagen de perfil
- Imagen de portada

### Flujo

1. Artista edita borrador.
2. Sistema guarda automáticamente (autosave opcional).
3. Artista presiona "Enviar a revisión".
4. Estado cambia a `submitted`.
5. Equipo AMA revisa y aprueba o solicita cambios.
6. Versión aprobada es la que puede publicarse.

---

## 10. Actividades y galerías

### Actividad

- Título, descripción, tipo, fechas, ubicación, comuna.
- Asociación a artista.
- Estado de revisión.
- Galería de imágenes.
- Portada destacada.

### Galería de imágenes

| Aspecto | Especificación |
|---------|----------------|
| Formatos | JPEG, PNG, WebP |
| Peso máximo | 5 MB por archivo |
| Dimensiones recomendadas | 1920x1080 máx. |
| Compresión | Automática a calidad 85% |
| Miniaturas | 400x400, 800x600, 1200x800 |
| Almacenamiento | `storage/app/media/{model}/{id}/{uuid}-{size}.jpg` |
| Nombres | UUID + timestamp |
| Límite por actividad | 20 imágenes (configurable) |
| Procesamiento | Síncrono si son pocas; job si son muchas |
| Limpieza | Command diario para eliminar huérfanos |

### Entidad polimórfica `media`

Permite asociar imágenes a `Artist`, `Activity`, `LifebookEntry` y futuros modelos sin duplicar tablas.

---

## 11. Propuestas y talleres

### Campos

- Título y slug
- Resumen ejecutivo
- Descripción completa
- Objetivos
- Requerimientos técnicos
- Disciplinas asociadas
- Territorio
- Presupuesto estimado (opcional)
- Adjuntos

### Flujo

1. Artista crea borrador.
2. Envía propuesta.
3. Revisor recibe notificación.
4. Revisor comenta internamente o pide cambios.
5. Artista corrige y reenvía.
6. Revisor aprueba/rechaza con motivo.
7. Propuesta aprobada puede convertirse en actividad o publicación WordPress.

---

## 12. Comentarios internos

### Diseño

- Modelo polimórfico `InternalComment`.
- Campo `is_internal` (true = solo equipo; false = visible al artista).
- Soporte de respuestas anidadas (`parent_id`).
- Soft delete para conservar contexto.
- Notificación al artista solo si `is_internal = false`.

### Uso

| Contexto | Visibilidad artista |
|----------|---------------------|
| Perfil en revisión | Solo comentarios marcados como "visible" |
| Actividad | Solo comentarios marcados como "visible" |
| Propuesta | Solo comentarios marcados como "visible" |
| Publicación WordPress | Nunca visible |

---

## 13. Libro de vida del artista

### Entrada

- Título
- Fecha del evento
- Contenido enriquecido
- Imágenes opcionales
- Visibilidad: `private`, `team`, `public_candidate`
- Estado: `draft`, `submitted`, `approved`, `rejected`, `archived`
- Relación opcional con actividad

### Flujo

1. Artista escribe entrada privada.
2. Puede marcar como "candidata a pública".
3. Equipo revisa y aprueba.
4. Entrada aprobada puede publicarse en WordPress como hito o biografía extendida.

---

## 14. Notificaciones

### Tipos iniciales

- `profile_approved`
- `profile_needs_changes`
- `proposal_submitted`
- `proposal_approved`
- `proposal_rejected`
- `changes_requested`
- `activity_published`
- `wordpress_publication_failed`
- `new_internal_comment`

### Canales

1. **En aplicación:** tabla `notifications`, badge en navbar.
2. **Email:** jobs en cola con plantillas Markdown.
3. **Futuro:** WhatsApp/SMS vía servicios externos.

### Reglas

- No enviar email a usuarios inactivos.
- Agrupar notificaciones similares en resumen diario (futuro).
- Permitir configurar preferencias de notificación.

---

## 15. Integración segura con WordPress

### Estrategia recomendada

**MVP:** Opción A - Publicación manual controlada desde Plataforma AMA hacia WordPress vía REST API.

**Fase avanzada:** Combinar A + B.

- **A:** La plataforma empuja contenido aprobado a WordPress (artistas, actividades, noticias).
- **B:** WordPress consume endpoints cacheados de Plataforma AMA para catálogos dinámicos.

### Qué publicar

- Ficha pública de artista aprobada.
- Actividades aprobadas con galería seleccionada.
- Noticias/editoriales creadas por comunicaciones.
- Catálogo público filtrado.

### Qué NO publicar

- Comentarios internos, notas privadas, estados internos, auditoría, datos sensibles.

### Autenticación

- WordPress Application Password para usuario dedicado (`ama_platform`).
- Token almacenado en `.env`, nunca en repositorio.
- Encabezado `Authorization: Basic base64(user:pass)`.

### Manejo de errores

- Guardar respuesta completa en `wordpress_publications.response_received`.
- Reintentos automáticos con backoff (3 intentos).
- Notificación a admin si falla.
- Botón manual de reintentar.

### Prevención de duplicados

- Guardar `wordpress_post_id` en el modelo publicable.
- Antes de crear, verificar si ya existe.
- Actualizar en lugar de crear si ya fue publicado.

---

## 16. Seguridad

| Área | Medida |
|------|--------|
| Autorización | Spatie roles + Laravel Policies |
| Validación | Form Requests con reglas estrictas |
| CSRF | Protección nativa de Laravel en todos los formularios |
| Rate limiting | Middleware por ruta en login, reset, uploads |
| Subida de archivos | Validación MIME real, tamaño máximo, escaneo básico |
| HTML | `htmlPurifier` o `striptags` para campos de texto enriquecido |
| Datos privados | Scopes en modelos para separar visibilidad |
| Auditoría | `AuditLog` en acciones críticas |
| Endpoints | Protegidos por `auth` y `can` |
| Backups | Base de datos y storage periódicos |
| Variables | `.env` fuera de Git; `php artisan key:generate` |
| Errores | Vista genérica en producción; log detallado |
| Storage | Permisos 755 carpetas, 644 archivos |
| HTTPS | Obligatorio en producción |
| Headers | HSTS, X-Frame-Options, CSP básico |

---

## 17. Rendimiento y optimización

- **Índices:** definidos en cada tabla para búsquedas por `status`, `artist_id`, `territory_id`, etc.
- **Paginación:** siempre paginar listados (20, 50, 100).
- **Eager loading:** `with()` en listados para evitar N+1.
- **Cache:** cachear catálogos de disciplinas, comunas y configuraciones.
- **Colas:** jobs para procesar imágenes, enviar correos, publicar en WordPress, exportar.
- **Imágenes:** procesamiento asíncrono y miniaturas.
- **Dashboard:** métricas agregadas con consultas eficientes; evitar conteos en tiempo real si son masivos.
- **Búsqueda:** índices full-text en nombres y biografías; futuro Elasticsearch/Meilisearch.
- **Lazy loading:** galerías cargan miniaturas primero.

---

## 18. Dashboard

### Artista

- Estado del perfil.
- Actividades recientes.
- Propuestas y sus estados.
- Notificaciones no leídas.
- Pendientes de corrección.
- Acceso rápido al libro de vida.

### Equipo AMA

- Perfiles pendientes de revisión.
- Propuestas pendientes.
- Actividades en revisión.
- Últimas actualizaciones.
- Filtros rápidos por disciplina, comuna, estado.
- Estadísticas por comuna y disciplina.

### Administrador

- Gestión de usuarios y roles.
- Importaciones.
- Auditoría.
- Configuración.
- Publicaciones WordPress.
- Logs de errores.

---

## 19. Plan de fases

### Fase 0 - Preparación
- Crear proyecto Laravel en `platform/`.
- Configurar `.env`, base de datos, queue, mail.
- Instalar Breeze/Livewire, Spatie Permission.
- Estructura de carpetas base.
- Commit inicial.

### Fase 1 - Autenticación base
- Login, logout, recuperar contraseña.
- Middleware `MustChangePassword`.
- Seeder de roles y usuario admin.
- Layout dashboard básico.

### Fase 2 - Importación y gestión de artistas
- Migraciones de artistas, disciplinas, comunas.
- Importador CSV/Excel por lotes.
- Generación de claves temporales.
- Envío de credenciales.

### Fase 3 - Perfil artístico
- CRUD de perfil.
- Estados y flujo de revisión.
- Comentarios internos básicos.

### Fase 4 - Actividades y galerías
- CRUD de actividades.
- Uploader de imágenes.
- Optimización y miniaturas.
- Estados de revisión.

### Fase 5 - Propuestas y revisión
- CRUD de propuestas.
- Bandeja de revisión.
- Comentarios internos avanzados.
- Historial de revisiones.

### Fase 6 - Bandeja administrativa
- Filtros, búsqueda, paginación.
- Auditoría.
- Notificaciones en aplicación.

### Fase 7 - WordPress
- Adapter de publicación.
- Publicación de fichas y actividades.
- Logs y reintentos.

### Fase 8 - Exportaciones y reportes
- Excel/CSV.
- Métricas.
- Optimizaciones.

### Fase 9 - Comunidad futura
- Chat/canales.
- Moderación.
- API pública cacheada.

---

## 20. Backlog técnico inicial

Ver archivo `BACKLOG-PLATAFORMA-AMA.md`.

---

## 21. Entregables de implementación

Ver documentos técnicos individuales por fase a medida que se implementen.

---

## 22. Decisiones clave

1. **Blade + Livewire** para el MVP; SPA solo si se justifica.
2. **MySQL propio** separado de WordPress.
3. **WordPress como consumidor**, no como fuente de verdad.
4. **Roles y permisos con Spatie** desde el día uno.
5. **Auditoría explícita** en cada acción de estado.
6. **Jobs en cola** para tareas pesadas.
7. **Polimorfismo** para comentarios, revisiones y media.
8. **Escalabilidad progresiva:** API pública, S3, chat y 2FA se dejan preparados pero se implementan en fases posteriores.
