<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://webdeclic.com/
 * @since      1.0.0
 *
 * @package    Mentions_Legales_Par_Webdeclic
 * @subpackage Mentions_Legales_Par_Webdeclic/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Mentions_Legales_Par_Webdeclic
 * @subpackage Mentions_Legales_Par_Webdeclic/includes
 * @author     Webdeclic SAS <contact@webdeclic.com>
 */
class Mentions_Legales_Par_Webdeclic_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'mentions-legales-par-webdeclic',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
