# AMA Maule - Traspaso tecnico para Plataforma AMA

Fecha: 2026-07-08  
Estado WordPress local: estabilizado para continuar  
Objetivo del documento: entregar contexto claro para iniciar desarrollo de plataforma de gestion artistica con Laravel + MySQL propia, dejando WordPress como sitio publico/vitrina.

---

## 1. Resumen ejecutivo

El sitio WordPress de AMA Maule fue migrado y estabilizado en local bajo:

```text
http://localhost/wordpress
```

La base WordPress local es:

```text
amamaule_local
```

WordPress queda como:

```text
- sitio publico
- vitrina editorial
- noticias
- paginas informativas
- catalogo visible
- contenido aprobado para publico general
```

La plataforma NO debe crecer dentro de WordPress como nucleo pesado. La decision sana es construir una aplicacion separada:

```text
Subdominio produccion: plataforma.amamaule.cl
Backend: Laravel
Frontend inicial recomendado: Blade moderno + Livewire o Inertia
Frontend alternativo: React/Vue si se decide SPA
Base de datos: MySQL propia para Plataforma AMA
WordPress: integracion/publicacion, no sistema principal de gestion
```

Motivo principal: el proyecto considera cerca de 2000 artistas ya existentes, crecimiento continuo, carga de imagenes, actividades, talleres, bitacoras, comentarios, revision administrativa y posibles funcionalidades sociales. WordPress con `wp_posts/wp_postmeta` no es la base mas sana para esa carga operacional.
### Naming recomendado

El concepto no debe comunicarse como "intranet", porque suena antiguo y demasiado interno. El nombre funcional recomendado es:

```text
Plataforma AMA
```

Subdominio recomendado:

```text
plataforma.amamaule.cl
```

Alternativas validas si se quiere un nombre mas corto:

```text
gestion.amamaule.cl
panel.amamaule.cl
portal.amamaule.cl
```

Decision actual del documento:

```text
Usar plataforma.amamaule.cl como opcion principal.
Usar "Plataforma AMA" como nombre del sistema.
Usar "dashboard" para la zona interna luego del login.
```

---

## 2. Estado actual del WordPress local

### URL y configuracion

```text
siteurl = http://localhost/wordpress
home    = http://localhost/wordpress
```

Se corrigieron enlaces internos que apuntaban erroneamente a:

```text
http://localhost/ejemplo/
/ejemplo/
https://amamaule.cl/rondas-de-vinculacion/
```

para que apunten localmente a:

```text
http://localhost/wordpress/ejemplo/
/wordpress/ejemplo/
http://localhost/wordpress/rondas-de-vinculacion/
```

### Tema activo

```text
Tema padre: astra
Tema hijo: AMA-MAULE
```

Opciones WordPress:

```text
template = astra
stylesheet = AMA-MAULE
current_theme = AMA MAULE
```

### Plugins activos finales

Lista activa despues de estabilizacion:

```text
elementor/elementor.php
elementor-pro/elementor-pro.php
advanced-custom-fields/acf.php
custom-css-js/custom-css-js.php
font-awesome/font-awesome.php
loginpress/loginpress.php
show-hidecollapse-expand/bg_show_hide.php
creame-whatsapp-me/joinchat.php
document-embedder-addons-for-elementor/document-embedder-addons-for-elementor.php
wp-mail-smtp/wp_mail_smtp.php
forminator/forminator.php
ameliabooking/ameliabooking.php
wpforms-lite/wpforms.php
```

No volver a usar:

```sql
UPDATE wp_options SET option_value = 'a:0:{}' WHERE option_name = 'active_plugins';
```

salvo emergencia real, porque deja el sitio sin Elementor, estilos, formularios y funciones.

### Plugins que deben quedar apagados salvo decision especifica

```text
login-recaptcha
wordfence
litespeed-cache
wp-file-manager
spotlight-social-photo-feeds
```

Motivos:

```text
- login-recaptcha bloqueo el login local porque localhost no estaba permitido.
- wordfence puede bloquear flujos locales.
- litespeed-cache conviene dejarlo para produccion, no para depuracion local.
- wp-file-manager es sensible en seguridad.
- spotlight-social-photo-feeds causo fatal error durante pruebas.
```

### Acceso admin local

Usuario creado/recuperado:

```text
usuario: alexis
rol: administrator
```

Debe mantenerse solo como acceso local/controlado. En produccion se debe auditar usuarios reales, roles, claves y doble factor.

---

## 3. Correcciones realizadas en WordPress

### Charset y mojibake

Se detectaron textos con mojibake:

```text
Â¿QuÃ© -> Ã‚Â¿QuÃƒÂ©
MÃ¡s -> MÃƒÂ¡s
VinculaciÃ³n -> VinculaciÃƒÂ³n
```

Se corrigieron por tablas/columnas, no globalmente:

```text
wp_posts.post_title
wp_posts.post_content
wp_posts.post_excerpt
wp_users.display_name
wp_usermeta.meta_value, solo meta_key = first_name
wp_frmt_form_entry_meta.meta_value
opciones/metas serializadas puntuales
```

Regla para futuro:

```text
No hacer reemplazos masivos globales sin respaldo.
No tocar datos serializados sin parsear correctamente.
Corregir por tabla y columna.
Validar antes/despues con SELECT.
```

### Enlaces permanentes y rutas

`.htaccess` esta correcto para WordPress bajo `/wordpress`:

```text
RewriteBase /wordpress/
RewriteRule . /wordpress/index.php [L]
```

Problema resuelto:

```text
Algunos enlaces internos apuntaban a /programa-completo/, /preguntas-frecuentes/, /que-es-ama/, etc.
Eso sacaba el navegador de /wordpress y generaba 404.
```

Solucion:

```text
Enlaces internos normalizados a /wordpress/...
Enlaces Elementor escapados corregidos dentro de _elementor_data.
Cache renderizado de Elementor eliminado cuando correspondia.
```

### Rondas de vinculacion

Problema:

```text
El boton/enlace Rondas enviaba a https://amamaule.cl/rondas-de-vinculacion/
Luego se corrigio a local, pero seguia abriendo nueva pestana por target="_blank".
```

Solucion:

```text
URL final: http://localhost/wordpress/rondas-de-vinculacion/
is_external eliminado en Elementor.
target="_blank" eliminado para ese enlace local.
```

Regla:

```text
Si el enlace es interno local, no debe abrir nueva pestana.
Si el enlace es externo real, como YouTube, Google Forms o redes sociales, puede abrir nueva pestana.
```

### Contacto y WPForms

Problema:

```text
[wpforms id="1740"] aparecia como texto plano.
```

Causa:

```text
El plugin wpforms-lite estaba instalado, pero no activo.
El formulario ID 1740 existe como post_type wpforms.
```

Solucion:

```text
Se activo wpforms-lite/wpforms.php.
El shortcode ya renderiza como formulario real.
```

Validacion:

```text
shortcode_visible = false
wpforms_found = true
```

### Menu y contraste visual

Problema:

```text
El menu/navegador principal perdia legibilidad sobre fondos blancos o imagenes.
```

Solucion final:

```text
Se ajusto CSS en el tema hijo AMA-MAULE/style.css.
Menu mantiene comportamiento normal.
Fondo con degradado solido:
morada oscuro -> violeta -> fucsia -> coral.
Texto blanco con sombra moderada.
Subrayado activo verde.
Submenus con fondo blanco y texto oscuro.
```

Backups CSS creados:

```text
_local_backups/AMA-MAULE-style-before-contrast-20260708.css
_local_backups/AMA-MAULE-style-before-sticky-nav-20260708.css
_local_backups/AMA-MAULE-style-before-nav-final-20260708.css
```

---

## 4. Backups importantes

Backup completo previo:

```text
_local_backups/stable-local-20260707-164543/amamaule_local.sql
_local_backups/stable-local-20260707-164543/site-files.tar
```

Backups puntuales relevantes:

```text
_local_backups/pre-url-fix-posts-postmeta-options-20260707.sql
_local_backups/pre-localhost-url-fix-posts-postmeta-options-20260707.sql
_local_backups/pre-rondas-link-fix-20260707.sql
_local_backups/pre-elementor-url-fix-wp_postmeta-20260707.sql
```

Documento de plugins activos:

```text
_local_backups/stable-local-20260707-164543/PLUGINS-ACTIVOS-LOCAL.md
```

---

## 5. Vision funcional de La plataforma

La plataforma debe resolver la gestion privada de artistas y actividades.

### Actores principales

```text
Super administrador
Equipo AMA / administrador operativo
Revisor / curador / programador
Artista
Comunicaciones/editorial
Soporte
```

### Entidades principales

```text
usuarios
roles
artistas
perfiles_artisticos
disciplinas
territorios/comunas
actividades
talleres/propuestas
eventos
galerias
archivos_multimedia
bitacora/libro_de_vida
comentarios_internos
revisiones
estados/workflows
notificaciones
mensajes/chat futuro
publicaciones_sincronizadas_wordpress
auditoria/logs
```

### Objetivos funcionales

```text
- que cada artista ingrese con email y clave propia
- clave temporal inicial con cambio obligatorio en primer acceso
- recuperacion de clave por correo
- perfil artistico editable
- libro de vida del artista
- actividades por artista
- propuestas de talleres/actividades
- carga de galerias por evento/actividad
- limite sano de imagenes por actividad
- revision por etapas
- comentarios internos del equipo AMA
- notificaciones por estado
- exportacion de datos
- publicacion hacia WordPress solo cuando algo este aprobado
- futura comunidad/chat entre artistas
```

---

## 6. Arquitectura recomendada

### Separacion

```text
amamaule.cl          = WordPress publico
plataforma.amamaule.cl = Laravel Plataforma AMA
```

Local sugerido:

```text
WordPress: http://localhost/wordpress
Plataforma:  http://localhost:8000 o http://plataforma-amamaule.test
```

### Stack recomendado

Primera etapa:

```text
Laravel 11/12
Blade moderno + Livewire
Tailwind CSS
MySQL/MariaDB
Laravel Breeze/Fortify para autenticacion
Spatie Laravel Permission para roles/permisos
Laravel Queues para correos y procesamiento
Storage local al inicio, migrable a S3/R2
Intervention Image o Spatie Media Library para imagenes
```

Opcion si se decide SPA:

```text
Laravel API
React o Vue
Inertia si se quiere punto intermedio
Sanctum para auth
```

Recomendacion practica:

```text
Comenzar con Laravel + Blade/Livewire.
Pasar a React/Vue solo si el nivel de interaccion lo exige.
```

---

## 7. Modelo de datos base sugerido

### users

```text
id
name
email
password
must_change_password boolean
email_verified_at
last_login_at
status: active/inactive/suspended
created_at
updated_at
```

### artists

```text
id
user_id
public_name
legal_name
rut/documento opcional
phone
region
comuna
address opcional
bio_short
bio_long
main_discipline_id
website
instagram
facebook
youtube
profile_status: draft/submitted/approved/needs_changes
created_at
updated_at
```

### disciplines

```text
id
name
slug
parent_id nullable
active
```

### artist_disciplines

```text
artist_id
discipline_id
is_primary
```

### activities

```text
id
artist_id
title
description
activity_type: taller/evento/obra/exposicion/mediacion/otro
start_date
end_date
location
comuna
status: draft/submitted/review/approved/rejected/published/archived
created_at
updated_at
```

### activity_media

```text
id
activity_id
file_path
thumb_path
mime_type
file_size
width
height
caption
sort_order
created_at
```

Reglas:

```text
Maximo inicial recomendado: 10 imagenes por actividad.
Peso maximo recomendado: 2-4 MB por imagen ya optimizada.
Generar miniaturas.
Nunca cargar imagenes originales gigantes sin optimizar.
```

### proposals

```text
id
artist_id
title
summary
description
target_audience
duration_minutes
capacity
technical_requirements
budget_estimate
status: draft/submitted/in_review/changes_requested/approved/rejected/archived
submitted_at
created_at
updated_at
```

### proposal_reviews

```text
id
proposal_id
reviewer_id
status
score optional
comment
internal_only boolean
created_at
updated_at
```

### internal_comments

```text
id
commentable_type
commentable_id
user_id
body
visibility: internal/team/admin
expires_at nullable
created_at
updated_at
```

Regla:

```text
Los comentarios internos pueden archivarse o expirar para no saturar.
No borrar historial critico; archivar cuando sea necesario.
```

### lifebook_entries

```text
id
artist_id
title
body
entry_date
visibility: private/team/public_candidate
status: draft/submitted/approved
created_at
updated_at
```

### notifications

```text
id
user_id
type
title
body
read_at
created_at
```

### audit_logs

```text
id
user_id nullable
action
entity_type
entity_id
old_values json nullable
new_values json nullable
ip
user_agent
created_at
```

---

## 8. Fases de construccion

### Fase 0 - Preparacion

```text
- crear repositorio/proyecto Laravel
- configurar base de datos propia
- definir .env local
- crear autenticacion
- crear roles/permisos
- crear layout base del dashboard
```

### Fase 1 - Identidad y acceso

```text
- login por email
- recuperacion de clave
- cambio obligatorio de clave temporal
- roles: admin, equipo, revisor, artista
- panel inicial segun rol
```

### Fase 2 - Importacion/relacion con artistas existentes

```text
- extraer usuarios/artistas desde WordPress o CSV
- mapear email unico
- crear usuarios en intranet
- asignar rol artista
- enviar clave temporal
- registrar origen de importacion
```

### Fase 3 - Perfil artista

```text
- formulario de perfil
- disciplinas multiples
- comuna/territorio
- redes sociales
- biografia corta/larga
- estado de revision del perfil
```

### Fase 4 - Actividades y galerias

```text
- CRUD de actividades
- carga de imagenes optimizadas
- limite por actividad
- orden de imagenes
- captions
- validaciones de peso/formato
```

### Fase 5 - Propuestas/talleres

```text
- artista crea propuesta
- guarda borrador
- envia a revision
- equipo comenta internamente
- estados claros
- notificacion al artista
```

### Fase 6 - Revision administrativa

```text
- bandeja de revision
- filtros por estado, disciplina, comuna, fecha
- comentarios internos
- pedir cambios
- aprobar/rechazar
- historial de decisiones
```

### Fase 7 - Publicacion hacia WordPress

```text
- seleccionar contenido aprobado
- preparar version publica
- publicar como post/pagina/CPT en WordPress via REST API
- guardar wordpress_post_id en intranet
- no publicar borradores automaticamente
```

### Fase 8 - Comunidad/chat futuro

```text
- foro o muro interno
- canales por disciplina
- moderacion
- reportes
- retencion de mensajes
```

---

## 9. Reglas tecnicas importantes

```text
No mezclar la base de WordPress con la base de Plataforma AMA como si fueran una sola.
No usar wp_posts/wp_postmeta para operaciones pesadas de la plataforma.
No guardar imagenes gigantes sin optimizar.
No borrar datos historicos: archivar.
No exponer datos privados de artistas en WordPress sin aprobacion.
No publicar automaticamente contenido no revisado.
No depender de plugins WordPress para el flujo critico de la plataforma.
```

---

## 10. Integracion WordPress - Plataforma AMA

### WordPress debe consumir/publicar solo contenido aprobado

Opciones:

```text
1. Intranet publica hacia WordPress via REST API.
2. WordPress consulta endpoints publicos/cacheados de intranet.
3. Exportacion manual desde intranet a WordPress en fase inicial.
```

Recomendacion:

```text
Fase inicial: exportacion/publicacion controlada.
Fase avanzada: API con tokens y logs.
```

### Datos que pueden llegar a WordPress

```text
- ficha publica del artista aprobada
- actividades aprobadas
- galerias seleccionadas
- noticias/editoriales generadas por equipo
- catalogo publico filtrado
```

### Datos que NO deben llegar a WordPress

```text
- comentarios internos
- notas privadas
- documentos sensibles
- estados de revision privados
- datos personales no autorizados
- logs/auditoria
```

---

## 11. Prompt listo para Kimi

Usar este prompt para iniciar la siguiente etapa:

```text
Actua como arquitecto senior de software, Laravel, MySQL, seguridad, diseno de sistemas e intranets culturales.

Contexto:
Tengo un sitio WordPress de AMA Maule ya estabilizado en local en:
http://localhost/wordpress

WordPress queda como sitio publico, vitrina editorial, noticias, paginas informativas y contenido aprobado para publico general.

La plataforma se construira separada de WordPress, idealmente en:
Produccion: plataforma.amamaule.cl
Local: http://localhost:8000 o dominio local equivalente

Stack objetivo:
Backend: Laravel
Frontend inicial recomendado: Blade moderno + Livewire o Inertia
Frontend alternativo: React/Vue si se justifica
Base de datos: MySQL propia para Plataforma AMA
Storage: local al inicio, migrable a S3/R2
WordPress: integracion/publicacion de contenido aprobado, no nucleo operacional

Contexto de negocio:
Existen aproximadamente 2000 artistas ya registrados en WordPress y seguiran incorporandose mas.
Cada artista representa una persona responsable de su actividad artistica o libro de vida.
Cada artista debe ingresar con email y clave propia.
Se entregara clave temporal y al primer ingreso debe cambiarla obligatoriamente.
Debe existir recuperacion de clave por correo.
Los artistas podran gestionar perfil, actividades, propuestas/talleres, galerias de imagenes y libro de vida.
Una actividad puede tener normalmente 10 imagenes o mas, pero se debe definir limite tecnico sano.
El equipo AMA debe revisar propuestas por etapas, comentar internamente y aprobar/rechazar/pedir cambios.
En el futuro se quiere comunidad/chat entre artistas, con moderacion.

Estado WordPress:
- siteurl/home local: http://localhost/wordpress
- tema padre: astra
- tema hijo: AMA-MAULE
- Elementor activo
- WPForms activo para contacto
- enlaces internos normalizados a /wordpress/...
- Rondas ya apunta a http://localhost/wordpress/rondas-de-vinculacion/ y abre en la misma pestana
- WordPress no debe ser sobrecargado como intranet

Necesito que me ayudes a disenar e implementar La plataforma con una arquitectura excepcional, escalable y ordenada.

Entregame primero:
1. Arquitectura general por capas.
2. Modulos del sistema.
3. Roles y permisos.
4. Modelo de base de datos inicial con tablas, campos e indices.
5. Flujo de autenticacion con clave temporal y cambio obligatorio.
6. Flujo de artista: perfil, actividades, galerias y libro de vida.
7. Flujo de propuestas/talleres con revision administrativa.
8. Estrategia de imagenes y almacenamiento.
9. Integracion segura con WordPress para publicar contenido aprobado.
10. Plan de fases para construir MVP robusto y luego escalar.

Restricciones:
- No mezclar la base de datos de WordPress con La plataforma como sistema unico.
- No usar wp_posts/wp_postmeta como nucleo de datos operacionales.
- No publicar contenido privado sin aprobacion.
- No hacer una SPA compleja si Blade/Livewire resuelve el primer producto mejor.
- Priorizar seguridad, mantenibilidad, indices de BD, auditoria y crecimiento futuro.

Despues de la arquitectura, ayudame a crear el proyecto Laravel paso a paso.
```

---

## 12. Primer backlog tecnico sugerido

```text
1. Crear proyecto Laravel.
2. Crear base de datos `ama_plataforma_local`.
3. Configurar auth.
4. Instalar roles/permisos.
5. Crear layout dashboard.
6. Crear migraciones base: users, artists, disciplines.
7. Crear flujo clave temporal.
8. Crear importador inicial de artistas desde CSV/WordPress.
9. Crear CRUD perfil artista.
10. Crear CRUD actividades.
11. Crear carga de imagenes con limites.
12. Crear propuestas/talleres.
13. Crear bandeja de revision.
14. Crear auditoria basica.
15. Crear API/publicacion hacia WordPress.
```

---

## 13. Definicion de exito

La plataforma estara bien encaminada cuando:

```text
- los artistas puedan entrar de forma segura
- el equipo pueda gestionar y revisar sin Excel caotico
- cada dato tenga tabla clara e indice
- las imagenes no congelen el sistema
- WordPress no se rompa ni cargue trabajo privado
- el flujo permita crecer por fases
- la arquitectura permita agregar chat, comunidad, reportes y publicaciones futuras sin rehacer todo
```




