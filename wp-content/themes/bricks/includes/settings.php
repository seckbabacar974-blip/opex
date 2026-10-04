<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Page settings
 * Template settings
 */
class Settings {
	public static $controls = [];

	public function __construct() {
		require_once BRICKS_PATH . 'includes/settings/base.php';

		add_action( 'wp', [ $this, 'set_controls' ] );
	}

	public static function set_controls() {
		$setting_types = [ 'page', 'template' ];

		foreach ( $setting_types as $setting_type ) {
			require_once BRICKS_PATH . "includes/settings/settings-$setting_type.php";

			// Instantiate setting class (e.g. 'Bricks\Settings_Page', 'Bricks\Settings_Template')
			$class_name = __NAMESPACE__ . ucwords( "\Settings_{$setting_type}", '_' );

			$instance = new $class_name( $setting_type );

			self::$controls[ $setting_type ] = $instance->get_controls_data();
		}
	}

	/**
	 * Get page/template controls data (controls and control groups)
	 *
	 * @param string $type page/template
	 */
	public static function get_controls_data( $type = '' ) {
		if ( isset( self::$controls['template'] ) ) {
			foreach ( self::$controls['template']['controls'] as $key => $control ) {
				if ( isset( $control['css'] ) ) {
					self::$controls['template']['controls'][ $key ] = $control;
				}
			}
		}

		if ( ! empty( $type ) && ! empty( self::$controls[ $type ] ) ) {
			return self::$controls[ $type ];
		}

		return self::$controls;
	}
}
