<?php

/**
 * Add shortcodes for Plugin Name.
 *
 * @link       https://webdeclic.com/
 * @since      1.0.0
 *
 * @package    Mentions_Legales_Par_Webdeclic
 * @subpackage Mentions_Legales_Par_Webdeclic/public
 */
class Mentions_Legales_Par_Webdeclic_Shortcodes {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}
	
	/**
	 * add_shrortcodes
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function add_shortcodes() {
		add_shortcode( 'wbd_mentions_legales', array( $this, 'shortcode_mentions_legales' ) );
	}
	
	/**
	 * shortcode_mentions_legales
	 * 
	 * @since    1.0.0
	 * @param  mixed $atts
	 * @return void
	 */
	public function shortcode_mentions_legales() {
		ob_start();
		include MENTIONS_LEGALES_PAR_WEBDECLIC_PLUGIN_PATH . "public/templates/shortcode-mentions-legales.php";
		return ob_get_clean();
	}
}
