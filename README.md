# AMA Maule - WordPress

Repositorio profesional del sitio WordPress de AMA Maule.

## URL local

`http://localhost/wordpress/`

## Propósito de este repositorio

Este repositorio utiliza una estrategia de **lista blanca** (whitelist):

- Se versiona únicamente el código propio del proyecto.
- WordPress core, plugins de terceros, archivos subidos, secretos y cachés están ignorados.
- El objetivo es mantener el repositorio pequeño, seguro y preparado para futuras expansiones.

## Estructura

```text
.
├── README.md                 # Este archivo
├── .gitignore                # Reglas de exclusión de lista blanca
├── wp-config.example.php     # Plantilla de configuración sin secretos
├── docs/                     # Documentación del proyecto
└── wp-content/
    └── themes/
        └── AMA-MAULE/        # Tema hijo personalizado de AMA Maule
```

## Qué NO se versiona

- WordPress core (`wp-admin/`, `wp-includes/`, archivos `wp-*.php` raíz)
- `wp-config.php` real (contiene credenciales)
- `.htaccess` real
- Plugins de terceros (`wp-content/plugins/`)
- Archivos subidos (`wp-content/uploads/`)
- Cachés, logs y backups (`_local_backups/`, `wp-content.zip`, `wp-content - copia/`)
- Temas de terceros (`astra/`, `chromenews/`, `twentytwentyfive/`, `lpnsolabs/`)

## Convenciones de trabajo

1. **Nunca ejecutar `git add .` a ciegas**. El `.gitignore` ignora casi todo, pero siempre revisar `git status` antes de confirmar.
2. Modificar solo archivos dentro de `wp-content/themes/AMA-MAULE/` y `docs/`.
3. Para agregar una nueva carpeta propia en el futuro (por ejemplo `platform/` para Laravel), actualizar `.gitignore` con las reglas necesarias.
4. Las credenciales y configuraciones sensibles permanecen fuera del repositorio.

## Preparación para Laravel (futuro)

Este repositorio está preparado para recibir una aplicación Laravel como carpeta hermana, por ejemplo:

```text
.
├── README.md
├── .gitignore
├── wp-config.example.php
├── docs/
├── wp-content/themes/AMA-MAULE/
└── platform/               # Aplicación Laravel futura
```

La carpeta `platform/` ya está permitida en `.gitignore`, aunque actualmente esté vacía.
