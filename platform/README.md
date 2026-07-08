> # Plataforma AMA
>
> Aplicación Laravel para la gestión artística privada de AMA Maule.
>
> ## Requisitos locales
>
> - PHP 8.2+ con extensiones: openssl, pdo_mysql, mbstring, xml, ctype, json, bcmath, fileinfo
> - MySQL/MariaDB
> - Composer
> - Node.js + NPM
>
> ## Entorno
>
> Copiar `.env.example` a `.env` y configurar:
>
> ```dotenv
> DB_CONNECTION=mysql
> DB_DATABASE=ama_plataforma_local
> DB_USERNAME=root
> DB_PASSWORD=
> ```
>
> ## Comandos comunes
>
> ```bash
> # Instalar dependencias PHP
> composer install
>
> # Instalar dependencias JS
> npm install
>
> # Compilar assets para desarrollo
> npm run dev
>
> # Compilar assets para producción
> npm run build
>
> # Ejecutar migraciones y seeders
> php artisan migrate:fresh --seed
>
> # Servidor de desarrollo
> php artisan serve --port=8000
> ```
>
> ## Usuario administrador inicial
>
> - Email: `admin@amamaule.cl`
> - Contraseña temporal: `CambiarClave2026!`
>
> Al iniciar sesión se exigirá cambiar la contraseña.
>
> ## Estructura del código
>
> - `app/Http/Controllers/Auth/PasswordChangeController.php` - Cambio obligatorio de contraseña.
> - `app/Http/Middleware/MustChangePassword.php` - Redirige a usuarios con contraseña temporal.
> - `database/seeders/RolesAndPermissionsSeeder.php` - Roles y permisos iniciales.
> - `database/seeders/AdminUserSeeder.php` - Usuario super_admin inicial.
>
> ## Seguridad
>
> - Las credenciales reales nunca deben guardarse en el repositorio.
> - El archivo `.env` está ignorado por Git.
> - La carpeta `storage/` debe tener permisos adecuados.
