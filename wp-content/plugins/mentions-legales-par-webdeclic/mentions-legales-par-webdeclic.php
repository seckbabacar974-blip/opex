<?php

/**
 *
 * @link              https://webdeclic.com/
 * @since             1.0.0
 * @package           Mentions_Legales_Par_Webdeclic
 *
 * @wordpress-plugin
 * Plugin Name:       Mentions Legales Par Webdeclic
 * Plugin URI:        https://webdeclic.com/generateur-de-mentions-legales/
 * Description:       Ce plugin permet de générer des mentions légales et de les afficher facilement via un shortcode en front ([wbd_mentions_legales]).
 * Version:           1.0.5
 * Author:            Webdeclic
 * Author URI:        https://webdeclic.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       mentions-legales-par-webdeclic
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$is_local = isset($_SERVER['REMOTE_ADDR']) && ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' || $_SERVER['REMOTE_ADDR'] == '::1');

$version  = get_file_data( __FILE__, array( 'Version' => 'Version' ), false )['Version'];

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'MENTIONS_LEGALES_PAR_WEBDECLIC_VERSION', $version );

/**
 * You can use this const for check if you are in local environment
 */
define( 'MENTIONS_LEGALES_PAR_WEBDECLIC_DEV_MOD', $is_local );

/**
 * Plugin Name text domain for internationalization.
 */
define( 'MENTIONS_LEGALES_PAR_WEBDECLIC_TEXT_DOMAIN', 'mentions-legales-par-webdeclic' );

/**
 * Plugin Name Path for plugin includes.
 */
define( 'MENTIONS_LEGALES_PAR_WEBDECLIC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin Name URL for plugin sources (css, js, images etc...).
 */
define( 'MENTIONS_LEGALES_PAR_WEBDECLIC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-mentions-legales-par-webdeclic-activator.php
 */
function activate_mentions_legales_par_webdeclic() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mentions-legales-par-webdeclic-activator.php';
	Mentions_Legales_Par_Webdeclic_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-mentions-legales-par-webdeclic-deactivator.php
 */
function deactivate_mentions_legales_par_webdeclic() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mentions-legales-par-webdeclic-deactivator.php';
	Mentions_Legales_Par_Webdeclic_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_mentions_legales_par_webdeclic' );
register_deactivation_hook( __FILE__, 'deactivate_mentions_legales_par_webdeclic' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-mentions-legales-par-webdeclic.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_mentions_legales_par_webdeclic() {

	$plugin = new Mentions_Legales_Par_Webdeclic();
	$plugin->run();

}
run_mentions_legales_par_webdeclic();
