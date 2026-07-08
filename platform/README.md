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
> # Alternativa si no tienes Composer global
> php composer.phar install
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

> ## Calidad, pruebas y mantenimiento
>
> ```bash
> # Ejecutar toda la suite automatizada
> composer test
>
> # Ejecutar solo una clase o grupo puntual
> php artisan test --filter=EpicNineTest
>
> # Revisar estilo con Laravel Pint sin modificar archivos
> composer lint
>
> # Formatear c?digo PHP con Laravel Pint
> composer format
>
> # Compilar vistas Blade para detectar errores de sintaxis
> php artisan view:cache
>
> # Limpiar vistas compiladas despu?s de la verificaci?n
> php artisan view:clear
>
> # Ver rutas web o API
> php artisan route:list
> php artisan route:list --path=api/catalog
>
> # Ejecutar migraciones pendientes
> php artisan migrate
>
> # Actualizar roles/permisos despu?s de agregar permisos nuevos
> php artisan db:seed --class=RolesAndPermissionsSeeder --force
>
> # Crear canales iniciales de comunidad
> php artisan db:seed --class=CommunityChannelSeeder --force
> ```
>
> ## Pruebas de integraci?n
>
> La suite `Integration` queda preparada para pruebas que golpean servicios reales, pero se mantiene apagada por defecto para no depender de red ni credenciales locales.
>
> ```bash
> # Ejecutar estructura de integraci?n; las pruebas reales se saltan si no est?n habilitadas
> composer test:integration
>
> # Para probar WordPress real, configurar WORDPRESS_* y habilitar expl?citamente:
> RUN_WORDPRESS_INTEGRATION=true php artisan test --testsuite=Integration
> ```
>
> ## Logging
>
> Canales estructurados disponibles:
>
> - `ama`: eventos de aplicaci?n en `storage/logs/ama-*.log`.
> - `audit`: auditor?a operativa en `storage/logs/audit-*.log`.
> - `integrations`: integraciones externas en `storage/logs/integrations-*.log`.
>
> Se pueden usar con `Log::channel('ama')->info(...)`, `Log::channel('audit')->info(...)` o `Log::channel('integrations')->warning(...)`.
>
> ## Telescope local opcional
>
> Telescope no queda instalado por defecto para evitar agregar dependencias durante el MVP. Si se necesita depurar queries, jobs o mails en local:
>
> ```bash
> composer require laravel/telescope --dev
> php artisan telescope:install
> php artisan migrate
> ```
>
> Mantener `TELESCOPE_ENABLED=false` en testing/producci?n salvo que exista una decisi?n expl?cita de operaci?n.
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
