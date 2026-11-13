<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'xAB}+!{L;<@5}BtGk3[,go!2rC_@e;[GxB2_2L8,,o]kIkweF:Bz|epz_7HAL&!N' );
define( 'SECURE_AUTH_KEY',   'C54|p&QU.-+|b^7w5Y;=>$&dFFEN2E,<O_SeH!5t7 lI(>zNz5QMX5zHAAl,Q`QH' );
define( 'LOGGED_IN_KEY',     'sg,1 gs><+|NGe:q2DFu`/h2l[xvF.K)5F~8WN&XWQLV=>)A4w1L?d3vsR-C2T![' );
define( 'NONCE_KEY',         '+a@j;d|B#v?/8Vrmh@zr~{~n&_%U%c{JvLe1Y5Qtc<()*3hFnUYE.Q:=9Ey~&/:J' );
define( 'AUTH_SALT',         'g>um2.n+IG,LR|Ph/v0KpTC{&C?C*yoX%^*<a/N[2m@s5+oFPJhy#817HF,%knX0' );
define( 'SECURE_AUTH_SALT',  'Hv^LUX.Cyk08%==6ycs`lvr`Bv5Fh<oy>)JN(*WF*V]^)T:f,6NCN8TK-qY7D2h_' );
define( 'LOGGED_IN_SALT',    'R>%UG,ZQI7u8*Q%|Kuz4}pLTXF}f&<L$PK*#APv7 [gB*dlob<:uE8:O@)8E79:v' );
define( 'NONCE_SALT',        'ZzE9K0tQ~jxXZU=0F|fKv.A(c@n(d36PfxKnJ/hdEE6rXUUF6J!z;QBQYF)*`VJj' );
define( 'WP_CACHE_KEY_SALT', 'Y@aKd@ZKV6m]lIJ}R3]heYZeAU2R1NUU,$JI|7rg&r&7q(iV4P3~ T;q>X?7@:MO' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
