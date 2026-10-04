<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://webdeclic.com/
 * @since      1.0.0
 *
 * @package    Mentions_Legales_Par_Webdeclic
 * @subpackage Mentions_Legales_Par_Webdeclic/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Mentions_Legales_Par_Webdeclic
 * @subpackage Mentions_Legales_Par_Webdeclic/includes
 * @author     Webdeclic SAS <contact@webdeclic.com>
 */
class Mentions_Legales_Par_Webdeclic {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Mentions_Legales_Par_Webdeclic_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'MENTIONS_LEGALES_PAR_WEBDECLIC_VERSION' ) ) {
			$this->version = MENTIONS_LEGALES_PAR_WEBDECLIC_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'mentions-legales-par-webdeclic';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Mentions_Legales_Par_Webdeclic_Loader. Orchestrates the hooks of the plugin.
	 * - Mentions_Legales_Par_Webdeclic_i18n. Defines internationalization functionality.
	 * - Mentions_Legales_Par_Webdeclic_Admin. Defines all hooks for the admin area.
	 * - Mentions_Legales_Par_Webdeclic_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for loading composer dependencies.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/vendor/autoload.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-mentions-legales-par-webdeclic-loader.php';
		
		/**
		 * The global functions for this plugin
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/global-functions.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-mentions-legales-par-webdeclic-i18n.php';

		/**
		 * The class responsible of settings.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-settings.php';
		
		/**
		 * The class responsible of shortcodes.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-shortcodes.php';

		$this->loader = new Mentions_Legales_Par_Webdeclic_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Mentions_Legales_Par_Webdeclic_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Mentions_Legales_Par_Webdeclic_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$mentions_legales_par_webdeclic_settings = new Mentions_Legales_Par_Webdeclic_Settings( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_menu', $mentions_legales_par_webdeclic_settings, 'add_settings_menu' );
		$this->loader->add_filter( 'plugin_action_links_' . plugin_basename( plugin_dir_path( dirname( __FILE__ ) ) . 'mentions-legales-par-webdeclic.php' ), $mentions_legales_par_webdeclic_settings, 'add_settings_link' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$mentions_legales_par_webdeclic_shortcodes = new Mentions_Legales_Par_Webdeclic_Shortcodes( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'init', $mentions_legales_par_webdeclic_shortcodes, 'add_shortcodes' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Mentions_Legales_Par_Webdeclic_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
