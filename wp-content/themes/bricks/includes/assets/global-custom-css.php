<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Assets_Global_Custom_Css {
	public function __construct() {
		add_action( 'add_action_bricks_global_settings', [ $this, 'added' ], 10, 2 );
		add_action( 'update_option_bricks_global_settings', [ $this, 'updated' ], 10, 3 );
	}

	public function added( $option_name, $value ) {
		self::generate_css_file( $value );
	}

	public function updated( $old_value, $value, $option_name ) {
		$old_css_loading_method = ! empty( $old_value['cssLoading'] ) ? $old_value['cssLoading'] : '';
		$new_css_loading_method = ! empty( $value['cssLoading'] ) ? $value['cssLoading'] : '';

		$global_custom_css_old = ! empty( $old_value['customCss'] ) ? $old_value['customCss'] : '';
		$global_custom_css_new = ! empty( $value['customCss'] ) ? $value['customCss'] : '';

		if ( $global_custom_css_old !== $global_custom_css_new ) {
			self::generate_css_file( $value );
		}
	}

	public static function generate_css_file( $global_settings ) {
		$global_css = ! empty( $global_settings['customCss'] ) ? trim( $global_settings['customCss'] ) : false;
		$global_css = Assets::minify_css( $global_css );

		$file_name     = 'global-custom-css.min.css';
		$css_file_path = Assets::$css_dir . "/$file_name";

		if ( $global_css ) {
			$file = fopen( $css_file_path, 'w' );
			fwrite( $file, $global_css );
			fclose( $file );

			return $file_name;
		} else {
			if ( file_exists( $css_file_path ) ) {
				unlink( $css_file_path );
			}
		}
	}
}
