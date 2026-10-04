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
define( 'AUTH_KEY',          'b[{0d-!E~#n*2C*6Qqui(M95>RnZ&N^-:(EIxhe+&)E }m>t|?XLB{CC/3k7&_T_' );
define( 'SECURE_AUTH_KEY',   'qGIhn!&z0,#o*4>b`4v%.$f4C<mqF?0.nbr+l;aZr~gJ4fB*6~Wvmk:&)(0j;F>r' );
define( 'LOGGED_IN_KEY',     '*n]rGR>DRoYu!.9oWie3-CFrAFb%SGgl<sMUXn8r!PnGVL49fP<A;v@$?>E(lL,P' );
define( 'NONCE_KEY',         '>uWt{{[3,wYNjy6_;((W3NV%F,@iL%La>ofXa~?QA|d%c)./0o9U}_-!c0gc7T&w' );
define( 'AUTH_SALT',         'xVjHI);zWSE=[:u]e1xN([<WlukM~yRQd~B[Ns>@D7{$#ghNvw?6Ki}G5h}X:_|_' );
define( 'SECURE_AUTH_SALT',  'Pdh,w4:?`H/O78pAS$-h3,1N=Rquxx Ky)*Fr$XN!-7b}0Wx?Gpg/e0erH^PO`{f' );
define( 'LOGGED_IN_SALT',    '4n-;n.S@G18R&X`Y~:@]:x^leprq>NV&<g4O5&pT8Afk-xIW*/SE].A@AHG:Uf,y' );
define( 'NONCE_SALT',        'Is >j+>MZ ^ef]#oc,uZk[6H(OrX=T|L^[(0azQj!M1,wHXo@q/~y3-@6$e162wB' );
define( 'WP_CACHE_KEY_SALT', ';8rNB,Y*8zKGIWp~pvm~lWt[ya`el(-tQ`o76?Zrj7tunfms}>blllxuI?HKnlTd' );


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
