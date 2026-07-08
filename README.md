> # AMA Maule - Plataforma cultural
>
> Repositorio profesional del ecosistema AMA Maule: WordPress como vitrina pública y Plataforma AMA como sistema privado de gestión artística.
>
> ## URLs locales
>
> | Aplicación | URL |
> |------------|-----|
> | WordPress público | `http://localhost/wordpress/` |
> | Plataforma AMA | `http://localhost:8000` (vía `php artisan serve`) |
>
> ## Propósito de este repositorio
>
> Este repositorio utiliza una estrategia de **lista blanca** (whitelist):
>
> - Se versiona únicamente el código propio del proyecto.
> - WordPress core, plugins de terceros, archivos subidos, secretos y cachés están ignorados.
> - Laravel core, vendor, node_modules, `.env` y logs de Plataforma AMA están ignorados por el `.gitignore` interno de `platform/`.
> - El objetivo es mantener el repositorio pequeño, seguro y escalable.
>
> ## Estructura
>
> ```text
> .
> ├── README.md                 # Este archivo
> ├── .gitignore                # Reglas de exclusión de lista blanca
> ├── wp-config.example.php     # Plantilla de configuración WordPress sin secretos
> ├── docs/                     # Documentación del proyecto
> ├── wp-content/
> │   └── themes/
> │       └── AMA-MAULE/        # Tema hijo personalizado de AMA Maule
> └── platform/                 # Aplicación Laravel: Plataforma AMA
>     ├── README.md             # Instrucciones específicas de Laravel
>     ├── .env.example          # Variables de entorno de ejemplo
>     ├── .htaccess             # Protección contra acceso directo vía Apache/XAMPP
>     └── ...
> ```
>
> ## Qué NO se versiona
>
> - WordPress core (`wp-admin/`, `wp-includes/`, archivos `wp-*.php` raíz)
> - `wp-config.php` real (contiene credenciales)
> - `.htaccess` real de WordPress
> - Plugins de terceros (`wp-content/plugins/`)
> - Archivos subidos (`wp-content/uploads/`)
> - Cachés, logs y backups (`_local_backups/`, `wp-content.zip`, `wp-content - copia/`)
> - Temas de terceros (`astra/`, `chromenews/`, `twentytwentyfive/`, `lpnsolabs/`)
> - Archivos internos de Laravel (`platform/vendor/`, `platform/node_modules/`, `platform/.env`, `platform/storage/logs/`)
>
> ## Convenciones de trabajo
>
> 1. **Nunca ejecutar `git add .` a ciegas**. El `.gitignore` ignora casi todo; siempre revisar `git status` antes de confirmar.
> 2. WordPress: modificar solo archivos dentro de `wp-content/themes/AMA-MAULE/`.
> 3. Plataforma AMA: trabajar dentro de `platform/app/`, `platform/resources/`, `platform/routes/`, `platform/database/`.
> 4. Documentación: agregar o actualizar archivos en `docs/`.
> 5. Las credenciales y configuraciones sensibles permanecen fuera del repositorio.
>
> ## Documentación clave
>
> - `docs/ARQUITECTURA-PLATAFORMA-AMA.md` - Arquitectura completa de Plataforma AMA.
> - `docs/BACKLOG-PLATAFORMA-AMA.md` - Backlog técnico por fases.
>
> ## Cómo ejecutar Plataforma AMA en local
>
> ```bash
> cd platform
> php artisan serve --port=8000
> ```
>
> La plataforma quedará disponible en `http://localhost:8000`.
