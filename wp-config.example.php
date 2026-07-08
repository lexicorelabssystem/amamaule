<?php
/**
 * AMA Maule - WordPress configuration example
 *
 * This is a template ONLY. It does NOT contain real credentials.
 *
 * Copy this file to `wp-config.php`, fill in your real values and keep
 * `wp-config.php` outside version control.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 */

// ** Database settings ** //
define( 'DB_NAME', 'database_name_here' );
define( 'DB_USER', 'username_here' );
define( 'DB_PASSWORD', 'password_here' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Generate these at: https://api.wordpress.org/secret-key/1.1/salt/
 */
define( 'AUTH_KEY',         'put your unique phrase here' );
define( 'SECURE_AUTH_KEY',  'put your unique phrase here' );
define( 'LOGGED_IN_KEY',    'put your unique phrase here' );
define( 'NONCE_KEY',        'put your unique phrase here' );
define( 'AUTH_SALT',        'put your unique phrase here' );
define( 'SECURE_AUTH_SALT', 'put your unique phrase here' );
define( 'LOGGED_IN_SALT',   'put your unique phrase here' );
define( 'NONCE_SALT',       'put your unique phrase here' );
/**#@-*/

/**
 * WordPress database table prefix.
 *
 * Only numbers, letters, and underscores.
 */
$table_prefix = 'wp_';

/**
 * Developer debug mode.
 *
 * Set to true only in local development.
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
