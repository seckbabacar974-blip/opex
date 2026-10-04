<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Assets {
	public static $wp_uploads_dir = '';
	public static $css_dir        = '';
	public static $css_url        = '';

	public static $global_colors = [];

	public static $inline_css = [
		'color_vars'     => '',
		'theme_style'    => '',
		'global'         => '',
		'global_classes' => '',
		'page'           => '',
		'template'       => '',
		'header'         => '',
		'content'        => '',
		'footer'         => '',
		'custom_fonts'   => '',
	];

	public static $elements = [];

	/**
	 * @since 1.3.6
	 * Set by Assets_Files::generate_post_css_file() method (during AJAX)
	 */
	public static $post_id = 0;

	/**
	 * Store inline CSS per css_type (content, theme_style, etc.) & breakpoint
	 *
	 * key: css_type
	 * subkeys: breakpoints
	 */
	public static $inline_css_breakpoints = [];

	public static $global_classes_elements = [];

	// Dynamic data CSS string (e.g. dynamic data 'featured_image' set in single post template, etc.)
	public static $inline_css_dynamic_data = '';

	// Stores the post_id values for all the templates and pages where we need to fetch the page settings values
	public static $page_settings_post_ids = [];

	// Keep track of the elements inside of a loop that were already styled - avoid duplicates (@since 1.5)
	public static $css_looping_elements = [];

	public static $webfonts_loaded = false;

	public function __construct() {
		$wp_uploads_dir = wp_upload_dir( null, false );

		self::$wp_uploads_dir = $wp_uploads_dir['basedir'];
		self::$css_dir        = $wp_uploads_dir['basedir'] . '/bricks/css';
		self::$css_url        = $wp_uploads_dir['baseurl'] . '/bricks/css';

		// "CSS loading method" set to 'file'
		if ( Database::get_setting( 'cssLoading' ) === 'file' ) {
			self::autoload_files();
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_setting_specific_scripts' ] );
	}

	/**
	 * CSS loading method "External Files": Autoload PHP files
	 *
	 * @since 1.3.5
	 */
	public static function autoload_files() {
		foreach ( glob( BRICKS_PATH . 'includes/assets/*.php' ) as $filename ) {
			require_once $filename;

			// Get last declared class to construct it
			$get_declared_classes = get_declared_classes();
			$last_class_name      = end( $get_declared_classes );

			// Init class
			new $last_class_name();
		}
	}

	/**
	 * Load element setting specific scripts (icon fonts, animations)
	 *
	 * Run for all CSS loading methods.
	 *
	 * @since 1.3.4
	 */
	public static function enqueue_setting_specific_scripts( $settings = [] ) {
		if ( ! empty( $settings ) ) {
			$bricks_settings_string = json_encode( $settings );
		} else {
			$bricks_settings_string  = json_encode( Database::get_template_data( 'header' ) );
			$bricks_settings_string .= json_encode( Database::get_template_data( 'content' ) );
			$bricks_settings_string .= json_encode( Database::get_template_data( 'footer' ) );
		}

		$theme_style_settings_string = json_encode( Theme_Styles::$active_settings );

		// Add settings of used global element to Bricks settings string
		if ( strpos( $bricks_settings_string, '"global"' ) ) {
			$global_elements = Database::$global_data['elements'] ? Database::$global_data['elements'] : [];

			foreach ( $global_elements as $global_element ) {
				$global_element_id = ! empty( $global_element['global'] ) ? $global_element['global'] : false;

				if ( ! $global_element_id ) {
					$global_element_id = ! empty( $global_element['id'] ) ? $global_element['id'] : false;
				}

				if ( $global_element_id ) {
					if ( strpos( $bricks_settings_string, $global_element_id ) ) {
						$bricks_settings_string .= json_encode( $global_element );
					}
				}
			}
		}

		/**
		 * STEP: Load icon font files
		 *
		 * 1. Check for icon font 'library' settings in Bricks data & theme styles ('prevArrow', 'nextArrow', etc.)
		 * 2. Check for icon font family in settings in Bricks data & theme styles ('Custom CSS', etc.)
		 */
		if (
			bricks_is_builder() ||
			strpos( $bricks_settings_string, '"library":"fontawesome' ) !== false ||
			strpos( $theme_style_settings_string, '"library":"fontawesome' ) !== false ||
			strpos( $bricks_settings_string, 'Font Awesome 6' ) !== false ||
			strpos( $theme_style_settings_string, 'Font Awesome 6' ) !== false
		) {
			wp_enqueue_style( 'bricks-font-awesome', BRICKS_URL_ASSETS . 'css/libs/font-awesome.min.css', [ 'bricks-frontend' ], filemtime( BRICKS_PATH_ASSETS . 'css/libs/font-awesome.min.css' ) );
		}

		if (
			bricks_is_builder() ||
			strpos( $bricks_settings_string, '"library":"ionicons' ) !== false ||
			strpos( $theme_style_settings_string, '"library":"ionicons' ) !== false ||
			strpos( $bricks_settings_string, 'Ionicons' ) !== false ||
			strpos( $theme_style_settings_string, 'Ionicons' ) !== false
		) {
			wp_enqueue_style( 'bricks-ionicons', BRICKS_URL_ASSETS . 'css/libs/ionicons.min.css', [ 'bricks-frontend' ], filemtime( BRICKS_PATH_ASSETS . 'css/libs/ionicons.min.css' ) );
		}

		if (
			bricks_is_builder() ||
			strpos( $bricks_settings_string, '"library":"themify' ) !== false ||
			strpos( $theme_style_settings_string, '"library":"themify' ) !== false ||
			strpos( $bricks_settings_string, 'themify' ) !== false ||
			strpos( $theme_style_settings_string, 'themify' ) !== false
		) {
			wp_enqueue_style( 'bricks-themify-icons', BRICKS_URL_ASSETS . 'css/libs/themify-icons.min.css', [ 'bricks-frontend' ], filemtime( BRICKS_PATH_ASSETS . 'css/libs/themify-icons.min.css' ) );
		}

		// STEP: Load animation CSS file (check for _animation settings in Bricks data)
		if ( bricks_is_builder() || strpos( $bricks_settings_string, '"_animation"' ) !== false ) {
			wp_enqueue_style( 'bricks-animate' );
		}

		// STEP: Load balloon (tooltip) CSS file (check for data-balloon-pos settings in Bricks data)
		if ( bricks_is_builder() || strpos( $bricks_settings_string, 'data-balloon' ) !== false ) {
			wp_enqueue_style( 'bricks-tooltips' );
		}

		// STEP: Load global elements style file (CSS class selector: .brxe-{global_element_id} and not CSS 'id')
		$global_elements_css_file_url = self::$css_url . '/global-elements.min.css';
		$global_elements_css_file_dir = self::$css_dir . '/global-elements.min.css';

		if ( strpos( $bricks_settings_string, '"global"' ) && Database::get_setting( 'cssLoading' ) === 'file' && file_exists( $global_elements_css_file_dir ) ) {
			wp_enqueue_style( 'bricks-global-elements', $global_elements_css_file_url, [], filemtime( $global_elements_css_file_dir ) );
		}

		// STEP: Get inline CSS to load webfonts (set in element settings)
		if ( Database::get_setting( 'cssLoading' ) === 'file' && empty( $settings ) ) {
			$inline_css = self::generate_inline_css();
			self::load_webfonts( $inline_css );
		}

		// STEP: Generate dynamic data CSS for requested page inline
		if ( ! bricks_is_builder() && self::$inline_css_dynamic_data && empty( $settings ) ) {
			wp_add_inline_style( 'bricks-frontend', self::$inline_css_dynamic_data );
		}
	}

	/**
	 * Minify CSS string (remove line breaks & tabs)
	 *
	 * @param string $inline_css CSS string.
	 *
	 * @since 1.3.4
	 */
	public static function minify_css( $inline_css ) {
		// Minify: Remove line breaks
		$inline_css = str_replace( "\n", '', $inline_css );

		// Minify: Remove tabs
		$inline_css = preg_replace( '/\t+/', '', $inline_css );

		return $inline_css;
	}

	/**
	 * Generate inline CSS
	 *
	 * Bricks Settings: "CSS loading Method" set to "Inline Styles" (= default)
	 *
	 * - Color Vars
	 * - Theme Styles
	 * - Global CSS Classes
	 * - Global Custom CSS
	 * - Page Custom CSS
	 * - Header
	 * - Content
	 * - Footer
	 * - Custom Fonts
	 * - Template
	 *
	 * @param $post_id Post ID.
	 *
	 * @return string $inline_css
	 */
	public static function generate_inline_css( $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$inline_css = '';

		// STEP #1: Color palette CSS color vars
		self::$inline_css['color_vars'] = self::generate_inline_css_color_vars( Database::$global_data['colorPalette'] );

		// STEP #2: Theme Styles
		self::$inline_css['theme_style'] = self::generate_inline_css_theme_style( Theme_Styles::$active_settings );

		// STEP #3: Bricks Settings - Custom CSS
		self::$inline_css['global'] = ! empty( Database::$global_settings['customCss'] ) ? trim( Database::$global_settings['customCss'] ) : '';

		// Check: Use active template ID to retrieve page data
		$content_template_id = Database::$active_templates['content'];

		if ( $content_template_id ) {
			Database::set_page_data( $content_template_id );
		}

		// STEP #4: Page settings (main page or template)
		if ( Database::$page_settings ) {
			self::$page_settings_post_ids[] = $content_template_id;
		}

		// STEP #5: Page header + content + footer

		// STEP #5.1: Header
		$header_template = Database::get_template_data( 'header' );

		if ( ! empty( $header_template ) && is_array( $header_template ) ) {
			// Add header template ID
			self::$page_settings_post_ids[] = Database::$active_templates['header'];

			self::generate_css_from_elements( $header_template, 'header' );
		}

		// STEP #5.2: Content
		$content_type     = ! empty( Database::$active_templates['content_type'] ) ? Database::$active_templates['content_type'] : 'content';
		$content_template = Database::get_template_data( $content_type );

		if ( ! empty( $content_template ) && is_array( $content_template ) ) {
			// Add content page or template ID
			if ( isset( Database::$active_templates[ $content_type ] ) ) {
				self::$page_settings_post_ids[] = Database::$active_templates[ $content_type ];
			}

			self::generate_css_from_elements( $content_template, 'content' );
		}

		// STEP #5.3: Footer
		$footer_template = Database::get_template_data( 'footer' );

		if ( ! empty( $footer_template ) && is_array( $footer_template ) ) {
			// Add footer template ID
			self::$page_settings_post_ids[] = Database::$active_templates['footer'];

			self::generate_css_from_elements( $footer_template, 'footer' );
		}

		// STEP #5.4: Global Classes
		self::generate_inline_css_global_classes();

		// STEP #4.1 Generates the Page Settings CSS (After the content because of the Templates and Post Content elements)
		self::generate_inline_css_page_settings();

		// STEP #7: Template header settings
		$template_header_id         = Database::$active_templates['header'];
		$template_header_settings   = Helpers::get_template_settings( $template_header_id );
		$template_settings_controls = Settings::get_controls_data( 'template' );

		self::generate_inline_css_from_element(
			[ 'settings' => $template_header_settings ],
			$template_settings_controls['controls'],
			'template'
		);

		$template_css = self::$inline_css['template'];

		// STEP: Concatinate styles (respecting precedences)

		// #1 Color palettes
		if ( ! empty( self::$inline_css['color_vars'] ) ) {
			$inline_css .= "/* COLOR VARS */\n" . self::$inline_css['color_vars'];
		}

		// #2 Theme Styles
		if ( self::$inline_css['theme_style'] ) {
			$inline_css .= "\n/* THEME STYLE CSS */\n" . self::$inline_css['theme_style'];
		}

		// #5.4 Global Classes
		if ( self::$inline_css['global_classes'] ) {
			// NOTE Not in use as closing "}" in @media query is stripped off.
			// Remove duplicate CSS rules (caused by global class custom CSS applied to multiple elements)
			// $global_classes_css_rules = explode( "\n", self::$inline_css['global_classes'] );
			// $global_classes_css_rules = array_unique( $global_classes_css_rules );
			// $global_classes_css_rules = implode( "\n", $global_classes_css_rules );
			// $inline_css .= "\n/* GLOBAL CLASSES CSS */\n" . $global_classes_css_rules;

			$inline_css .= "\n/* GLOBAL CLASSES CSS */\n" . self::$inline_css['global_classes'];
		}

		// #3 Bricks settings - Custom CSS
		if ( self::$inline_css['global'] ) {
			$inline_css .= "\n/* GLOBAL CSS */\n" . self::$inline_css['global'];
		}

		// #4 Page settings
		if ( isset( self::$inline_css['page'] ) ) {
			$inline_css .= "\n/* PAGE CSS */\n" . self::$inline_css['page'];
		}

		// #5.1 Header
		if ( self::$inline_css['header'] ) {
			$inline_css .= "\n/* HEADER CSS */\n" . self::$inline_css['header'];
		}

		// #5.2 Content
		if ( self::$inline_css['content'] ) {
			$inline_css .= "\n/* CONTENT CSS */\n" . self::$inline_css['content'];
		}

		// #5.3 Footer
		if ( self::$inline_css['footer'] ) {
			$inline_css .= "\n/* FOOTER CSS */\n" . self::$inline_css['footer'];
		}

		// #6 Custom Fonts @font-face (generated in: generate_css_from_elements)
		if ( self::$inline_css['custom_fonts'] ) {
			$inline_css .= "\n/* CUSTOM FONTS CSS */\n" . self::$inline_css['custom_fonts'];
		}

		// #7 Template header settings
		$template_css = trim( $template_css );

		if ( $template_css ) {
			$inline_css .= "\n/* TEMPLATE CSS */\n" . $template_css;
		}

		/**
		 * Build Google fonts array by scanning inline CSS for Google fonts
		 */
		self::load_webfonts( $inline_css );

		return $inline_css;
	}

	/**
	 * Generates list of global palette colors as CSS vars
	 *
	 * @param array $color_palettes
	 *
	 * @return string
	 */
	public static function generate_inline_css_color_vars( $color_palettes ) {
		self::$global_colors = [];
		$css_vars            = [];

		foreach ( $color_palettes as $palette ) {
			if ( empty( $palette['id'] ) || empty( $palette['colors'] ) ) {
				continue;
			}

			foreach ( $palette['colors'] as $color ) {
				$color_value = '';

				// Plain 'raw' color value (e.g. 'blue', 'red')
				if ( ! empty( $color['raw'] ) && strpos( $color['raw'], 'var(' ) === false ) {
					$color_value = Integrations\Dynamic_Data\Providers::render_content( $color['raw'], self::$post_id );
				} elseif ( ! empty( $color['rgb'] ) ) {
					$color_value = $color['rgb'];
				} elseif ( ! empty( $color['hex'] ) ) {
					$color_value = $color['hex'];
				}

				if ( ! $color_value ) {
					continue;
				}

				$css_var = "--bricks-color-{$color['id']}";

				$raw_value = ! empty( $color['raw'] ) ? $color['raw'] : '';

				// 'raw' value is CSS var
				if ( strpos( $raw_value, 'var(' ) !== false ) {
					$css_var = str_replace( 'var(', '', $raw_value );
					$css_var = str_replace( ')', '', $css_var );

					self::$global_colors[ $color['id'] ] = $raw_value;
				} else {
					self::$global_colors[ $color['id'] ] = $color_value;
				}

				$css_vars[] = "{$css_var}: {$color_value};";
			}
		}

		return ! empty( $css_vars ) ? ':root {' . PHP_EOL . implode( PHP_EOL, $css_vars ) . PHP_EOL . '}' . PHP_EOL : '';
	}

	/**
	 * Helper function to generate color code based on color array
	 *
	 * @param array $color
	 *
	 * @return string
	 */
	public static function generate_css_color( $color ) {
		// Re-run 'generate_inline_css_color_vars' to set self::$global_colors on file save to add color var to 'post-{ID}.min.css'
		if ( ! count( self::$global_colors ) ) {
			$color_vars_inline_css = self::generate_inline_css_color_vars( get_option( BRICKS_DB_COLOR_PALETTE, [] ) );
		}

		// Return color var if it exists as defined in the color palette
		if ( ! empty( $color['id'] ) ) {
			if ( array_key_exists( $color['id'], self::$global_colors ) ) {
				// Return 'raw' CSS var value from color
				$color_value = self::$global_colors[ $color['id'] ];

				if ( $color_value && strpos( $color_value, 'var(' ) !== false ) {
					return $color_value;
				}

				// Return Bricks color CSS var
				return "var(--bricks-color-{$color['id']})";
			}
		}

		// Plain color value (@since 1.5 for CSS vars, dynamic data color)
		if ( ! empty( $color['raw'] ) ) {
			return Integrations\Dynamic_Data\Providers::render_content( $color['raw'], self::$post_id );
		}

		if ( ! empty( $color['rgb'] ) ) {
			return $color['rgb'];
		}

		if ( ! empty( $color['hex'] ) ) {
			return $color['hex'];
		}
	}

	/**
	 * Generate theme style CSS string
	 *
	 * @return string Inline CSS for theme styles.
	 */
	public static function generate_inline_css_theme_style( $settings = [] ) {
		if ( ! is_array( $settings ) ) {
			return;
		}

		$controls = Theme_Styles::$controls;

		if ( ! count( $controls ) ) {
			Theme_Styles::set_controls();
			$controls = Theme_Styles::$controls;
		}

		// Order typography settings as controls to force precedence (H1 setting after "Headings" setting, etc.)
		if ( isset( $settings['typography'] ) && isset( $controls['typography'] ) ) {
			$typography_control_keys = array_keys( $controls['typography'] );

			$typography_settings      = $settings['typography'];
			$typography_settings_keys = array_keys( $typography_settings );

			$ordered = array_intersect( $typography_control_keys, $typography_settings_keys );

			foreach ( $ordered as $typography_key ) {
				unset( $settings['typography'][ $typography_key ] );
				$settings['typography'][ $typography_key ] = $typography_settings[ $typography_key ];
			}
		}

		$inline_css = '';

		foreach ( $settings as $group_key => $group_settings ) {
			$group_controls = ! empty( $controls[ $group_key ] ) ? $controls[ $group_key ] : false;

			if ( ! $group_controls ) {
				continue;
			}

			$element = [ 'settings' => $group_settings ];

			$inline_css .= self::generate_inline_css_from_element( $element, $group_controls, 'theme_style' );
		}

		// Breakpoint CSS
		$inline_css = self::generate_inline_css_for_breakpoints( 'theme_style', $inline_css );

		// Custom fonts
		$custom_fonts = self::$inline_css['custom_fonts'];

		if ( $custom_fonts ) {
			$inline_css .= "\n\n/* THEME STYLE CSS: Custom Fonts */\n";
			$inline_css .= $custom_fonts;
		}

		return $inline_css;
	}

	/**
	 * Generate global classes CSS string
	 *
	 * @return string Inline CSS for classes.
	 */
	public static function generate_inline_css_global_classes() {
		if ( empty( self::$global_classes_elements ) ) {
			return;
		}

		$global_classes = Database::$global_data['globalClasses'];

		if ( empty( $global_classes ) ) {
			return;
		}

		$inline_css = '';

		foreach ( self::$global_classes_elements as $global_class_id => $element_names ) {
			// Get element name from class
			$global_class_index = array_search( $global_class_id, array_column( $global_classes, 'id' ) );
			$global_class       = ! empty( $global_classes[ $global_class_index ] ) ? $global_classes[ $global_class_index ] : false;

			if ( ! $global_class ) {
				continue;
			}

			foreach ( $element_names as $element_name ) {
				$element_controls = Elements::get_element( [ 'name' => $element_name ], 'controls' );

				$inline_css .= self::generate_inline_css_from_element(
					[
						'name'            => $element_name,
						'settings'        => ! empty( $global_class['settings'] ) ? $global_class['settings'] : [],
						'_cssGlobalClass' => $global_class['name'], // Special property to add global CSS class the CSS selector
					],
					$element_controls,
					'global_classes'
				);
			}
		}

		return $inline_css;
	}

	public static function generate_inline_css_page_settings() {
		if ( empty( self::$page_settings_post_ids ) ) {
			return;
		}

		// Remove duplicated pages
		$post_ids = array_unique( self::$page_settings_post_ids );

		if ( ! isset( Settings::$controls['page'] ) ) {
			Settings::set_controls();
		}

		$page_settings_controls = Settings::get_controls_data( 'page' );

		$page_settings_css = '';

		foreach ( $post_ids as $post_id ) {
			$page_settings = get_post_meta( $post_id, BRICKS_DB_PAGE_SETTINGS, true );

			if ( empty( $page_settings ) ) {
				continue;
			}

			$page_settings_css .= self::generate_inline_css_from_element(
				[ 'settings' => $page_settings ],
				$page_settings_controls['controls'],
				'page'
			);
		}

		return $page_settings_css;
	}

	/**
	 * @since 1.4
	 *
	 * @param string $script_key customScriptsHeader, customScriptsBodyHeader, customScriptsBodyFooter
	 * @return string
	 */
	public static function get_page_settings_scripts( $script_key = '' ) {
		if ( empty( self::$page_settings_post_ids ) ) {
			return;
		}

		// Remove duplicated pages
		$post_ids = array_unique( self::$page_settings_post_ids );

		$page_settings_scripts = '';

		foreach ( $post_ids as $post_id ) {
			$page_settings = get_post_meta( $post_id, BRICKS_DB_PAGE_SETTINGS, true );

			if ( empty( $page_settings[ $script_key ] ) ) {
				continue;
			}

			$page_settings_scripts .= stripslashes_deep( $page_settings[ $script_key ] ) . PHP_EOL;
		}

		return $page_settings_scripts;
	}

	/**
	 * Load Google fonts according to inline CSS (source of truth) and remove loading wrapper
	 */
	public static function load_webfonts( $inline_css ) {
		// Return: Google fonts disabled
		if ( Helpers::google_fonts_disabled() ) {
			return;
		}

		$google_fonts_families_string = Helpers::get_file_contents( BRICKS_URL_ASSETS . 'fonts/google-fonts.min.json' );
		$google_fonts_families        = json_decode( $google_fonts_families_string, true );
		$google_fonts_families        = is_array( $google_fonts_families ) ? $google_fonts_families['items'] : [];
		$active_google_font_families  = [];
		$active_google_font_urls      = [];

		// Scan inline CSS for each Google font
		foreach ( $google_fonts_families as $google_font ) {
			$index           = strpos( $inline_css, $google_font['family'] );
			$add_google_font = false;

			// Skip iteration if this Google Font isn't found in inline CSS
			if ( ! $index ) {
				continue;
			}

			$font_weights = [];

			// Search all Google Font occurrences to build up font weights
			while ( $index = strpos( $inline_css, $google_font['family'], $index ) ) {
				$css_rule_index_start = strrpos( substr( $inline_css, 0, $index ), '{' ) + 1;
				$css_rule_index_end   = strpos( $inline_css, '}', $index );

				$css_rules_string = substr( $inline_css, $css_rule_index_start, $css_rule_index_end - $css_rule_index_start );
				$css_rules        = explode( '; ', $css_rules_string );

				foreach ( $css_rules as $css_rule_string ) {
					$css_rule     = explode( ': ', $css_rule_string );
					$css_property = $css_rule[0];
					$css_value    = str_replace( '"', '', $css_rule[1] ); // Remove added doulbe quotes (") from font-family value to find match

					// Remove fallback font (@since 1.5.1)
					$fallback_font_index = strpos( $css_value, ',' );

					if ( $fallback_font_index ) {
						$css_value = substr_replace( $css_value, '', $fallback_font_index, strlen( $css_value ) );
					}

					// Check for Google Font family
					if ( $css_property === 'font-family' && $css_value === $google_font['family'] ) {
						$add_google_font = $google_font['family'];
					}

					// Check for Google Font weight
					if ( $css_property === 'font-weight' && $add_google_font ) {
						// Check for italic
						if ( strpos( $css_rules_string, 'font-style: italic' ) !== false ) {
							$css_value .= 'italic';
						}

						if ( ! in_array( $css_value, $font_weights ) ) {
							$font_weights[] = $css_value;
						}
					}
				}

				// Increase index to start next iteration right after last inline CSS pointer
				$index++;
			}

			// Check next Google Font
			if ( ! $add_google_font ) {
				continue;
			}

			$google_font_family = '';

			// Default: Load all Google font variants (@since 1.5.1)
			$font_weights = ! empty( $google_font['variants'] ) && is_array( $google_font['variants'] ) ? $google_font['variants'] : [];

			// Optional: Theme Style typography: Load only selected font-variants
			$theme_style_typography = ! empty( Theme_Styles::$active_settings['typography'] ) ? Theme_Styles::$active_settings['typography'] : '';

			if ( $theme_style_typography ) {
				foreach ( $theme_style_typography as $typography_setting ) {
					$font_family   = ! empty( $typography_setting['font-family'] ) ? $typography_setting['font-family'] : false;
					$font_variants = ! empty( $typography_setting['font-variants'] ) ? $typography_setting['font-variants'] : false;

					if ( $font_family === $add_google_font && $font_variants ) {
						$font_weights = is_array( $font_variants ) ? $font_variants : [ $font_variants ];
					}
				}
			}

			if ( count( $font_weights ) ) {
				sort( $font_weights );

				$font_weights = join( ',', $font_weights );

				// Append font weights to Google font family name (e.g.: Roboto:100,300italic,700)
				$add_google_font .= ":$font_weights";
			}

			$google_font_family = $add_google_font;

			// Hack: https://github.com/typekit/webfontloader/issues/409#issuecomment-492831957
			$active_google_font_families[] = $google_font_family;
			$active_google_font_urls[]     = "https://fonts.googleapis.com/css?family=$google_font_family&display=swap";
		}

		// Frontend: Load Google font files (via Webfont loader OR stylesheets (= default))
		if (
			! bricks_is_builder() &&
			! self::$webfonts_loaded &&
			count( $active_google_font_families ) &&
			count( $active_google_font_urls )
		) {
			self::$webfonts_loaded = true;

			// Use wefont.min.js (and hide HTML until all webfonts are loaded)
			if ( ! Helpers::google_fonts_disabled() && Database::get_setting( 'webfontLoading' ) === 'webfontloader' ) {
				wp_enqueue_script( 'bricks-webfont' );

				wp_add_inline_script(
					'bricks-webfont',
					'WebFont.load({
					classes: false,
					loading: function() {
						document.documentElement.style.opacity = 0
					},
					active: function() {
						document.documentElement.removeAttribute("style")
					},
					custom: {
						families: ' . json_encode( $active_google_font_families ) . ',
						urls: ' . json_encode( $active_google_font_urls, JSON_UNESCAPED_SLASHES ) . '
					}
				})'
				);
			}

			// Use font stylesheet URLs
			else {
				foreach ( $active_google_font_urls as $index => $active_google_font_url ) {
					wp_enqueue_style( "bricks-google-font-$index", $active_google_font_url, [], '' );
				}
			}
		}
	}

	/**
	 * Loop over repeater items to generate CSS for each item (e.g. Slider 'items')
	 *
	 * @since 1.3.5
	 */
	public static function generate_inline_css_from_repeater( $settings, $repeater_items, $css_selector, $repeater_control, $css_type ) {
		$controls  = $repeater_control['fields'];
		$selector  = ! empty( $repeater_control['selector'] ) ? $repeater_control['selector'] : '.repeater-item';
		$nth_child = 1;
		$css_rules = [];

		foreach ( $repeater_items as $index => $item ) {
			foreach ( $item as $key => $value ) {
				if ( ! $value ) {
					continue;
				}

				$repeater_css_selector = $css_selector;

				// Modify CSS selector for repeater item control
				switch ( $selector ) {
					// SwiperJS: target slide index by data attribute
					case 'swiperJs':
						$repeater_css_selector .= isset( $settings['hasLoop'] ) ? ' .swiper-slide' : ' .swiper-slide[data-swiper-slide-index="' . $index . '"]';
						break;

					// Apply CSS to every repeater item via field ID (e.g. posts element: dynamicMargin, etc.)
					case 'fieldId':
						$item_id                = ! empty( $item['id'] ) ? $item['id'] : $index;
						$repeater_css_selector .= ' .repeater-item [data-field-id="' . $item_id . '"]';
						break;

					// Default: Target correct repeater item via :nth-child pseudo class
					default:
						$repeater_css_selector .= " $selector:nth-child($nth_child)";
						break;
				}

				$css_rules_repeater = self::generate_css_rules_from_setting( $settings, $key, $value, $controls, $repeater_css_selector, $css_type );

				if ( $css_rules_repeater ) {
					foreach ( $css_rules_repeater as $css_rule_selector => $css_rules_array ) {
						if ( ! isset( $css_rules[ $css_rule_selector ] ) ) {
							$css_rules[ $css_rule_selector ] = [];
						}

						$css_rules[ $css_rule_selector ] = array_merge( $css_rules[ $css_rule_selector ], $css_rules_array );
					}
				}
			}

			$nth_child++;
		}

		return $css_rules;
	}

	/**
	 * Generate CSS string from individual setting
	 *
	 * @return array key: CSS selector. value: array of CSS rules for this CSS selector.
	 *
	 * @since 1.3.5
	 */
	public static function generate_css_rules_from_setting( $settings, $setting_key, $setting_value, $controls, $selector, $css_type ) {
		$post_id = wp_doing_ajax() && ! empty( self::$post_id ) ? self::$post_id : get_the_ID();

		if ( is_home() ) {
			$post_id = get_option( 'page_for_posts' );
		}

		if ( get_post_type( $post_id ) === BRICKS_DB_TEMPLATE_SLUG ) {
			$preview_id = Helpers::get_template_setting( 'templatePreviewPostId', $post_id );
			$post_id    = $preview_id ? $preview_id : $post_id;
		}

		/**
		 * STEP: Get plain control key (extract breakpoint & pseudo-class)
		 *
		 * From '_margin:tablet_portait:hover' to '_margin'
		 */
		$control_key = $setting_key;

		// BREAKPOINT (':tablet_portrait' @pre 1.3.5: '_tablet_portrait')
		$breakpoint = '';

		foreach ( Breakpoints::$breakpoints as $bp ) {
			$breakpoint_key = $bp['key'];

			// @pre 1.3.5: Fallback to original '_tablet_portrait' syntax
			if ( strpos( $control_key, "_$breakpoint_key" ) ) {
				$control_key = str_replace( "_$breakpoint_key", '', $control_key );
				$breakpoint  = $breakpoint_key;
			}

			// @since 1.3.5
			else {
				if ( strpos( $control_key, ":$breakpoint_key" ) ) {
					$control_key = str_replace( ":$breakpoint_key", '', $control_key );
					$breakpoint  = $breakpoint_key;
				}
			}
		}

		// PSEUDO-CLASS (':hover' @pre 1.3.5: '_hover')
		$pseudo_class = '';

		// @pre 1.3.5: Fallback to original '_hover' syntax (e.g.: _margin_hover)
		if ( strpos( $control_key, '_hover' ) ) {
			$control_key  = str_replace( '_hover', '', $control_key );
			$pseudo_class = ':hover';
		}

		// @since 1.3.5
		else {
			foreach ( Database::$global_data['pseudoClasses'] as $pseudo_class_selector ) {
				if ( $pseudo_class ) {
					continue;
				}

				$pseudo_class_starts_at = strpos( $control_key, ':' );

				if ( $pseudo_class_starts_at === false ) {
					continue;
				}

				$pseudo_class_part = substr( $control_key, $pseudo_class_starts_at );

				if ( $pseudo_class_part === $pseudo_class_selector ) {
					$pseudo_class = $pseudo_class_part;
					$control_key  = str_replace( $pseudo_class, '', $control_key );
				}
			}
		}

		$control      = ! empty( $controls[ $control_key ] ) ? $controls[ $control_key ] : false;
		$control_type = ! empty( $control['type'] ) ? $control['type'] : '';
		$css_rules    = [];

		// STEP: Loop over repeater items to generate CSS string
		if ( $control_type === 'repeater' ) {
			$css_rules_repeater = self::generate_inline_css_from_repeater( $settings, $setting_value, $selector, $control, $css_type );

			if ( is_array( $css_rules_repeater ) && count( $css_rules_repeater ) ) {
				$css_rules = array_merge( $css_rules, $css_rules_repeater );
			}
		}

		// SVG icon: Set 'css' selector to 'svg' (and properties besides 'library' and 'svg' set (e.g. height, width, etc.))
		elseif ( $control_type === 'icon' && ! isset( $control['css'] ) && ! empty( $setting_value['svg'] ) && count( $setting_value ) > 2 ) {
			$control['css'] = [
				[ 'selector' => isset( $control['root'] ) ? '' : 'svg' ],
			];
		}

		$css_definitions = isset( $control['css'] ) && is_array( $control['css'] ) ? $control['css'] : false;

		// STEP: Is a CSS control: Loop through all control 'css' arrays to generate CSS rules from setting
		if ( $css_definitions ) {
			foreach ( $control['css'] as $css_definition ) {
				$css_selector = $selector;
				$css_property = isset( $css_definition['property'] ) ? $css_definition['property'] : '';

				// Has custom selector & is not a ::before OR ::after pseudo element (those are always applied to the element root @since 1.4)
				$custom_selector = ! empty( $css_definition['selector'] ) ? $css_definition['selector'] : '';

				// @since 1.4: Multiple CSS selector (see Social Icons element)
				if ( strpos( $custom_selector, ', ' ) ) {
					$custom_selector = str_replace( ', ', ", $css_selector ", $custom_selector );
				}

				if (
					$custom_selector &&
					! strpos( $pseudo_class, ':before' ) &&
					! strpos( $pseudo_class, ':after' )
				) {
					// Starts with '&' meaning no space
					if ( substr( $custom_selector, 0, 1 ) === '&' ) {
						$custom_selector = substr( $custom_selector, 1 );
					}

					// Add space between element ID & setting CSS selector
					elseif ( ! empty( $css_selector ) ) {
						$css_selector .= ' ';
					}

					// Append custom selector
					$css_selector .= $custom_selector;
				}

				// STEP Replace {pseudo} placeholder (see accordion.php) to apply pseudoclass in between selector
				if ( strpos( $css_selector, '{pseudo}' ) ) {
					$css_selector = str_replace( '{pseudo}', $pseudo_class, $css_selector );
				}

				// Append pseudo-class
				elseif ( $pseudo_class ) {
					// Check: Add pseudo-class to every selector in case multiple CSS selectors are passed in one CSS rule (see: theme styles $link_css_selectors)
					if ( strpos( $css_selector, ', ' ) ) {
						$css_selector = str_replace( ', ', "$pseudo_class, ", $css_selector );
					}

					$css_selector .= $pseudo_class;
				}

				if ( is_array( $setting_value ) ) {
					// Invert gutter/spacing (.form-group etc.)
					if ( isset( $css_definition['invert'] ) ) {
						$setting_value_invert = [];

						foreach ( $setting_value as $key => $value ) {
							$setting_value_invert[ $key ] = $value != 0 ? '-' . $value : $value;
						}

						$setting_value = $setting_value_invert;
					}

					$background_size = ! empty( $setting_value['size'] ) ? $setting_value['size'] : false;
					$background_url  = false;

					// Generate CSS declarations according to control type
					switch ( $control_type ) {
						case 'background':
							foreach ( $setting_value as $background_property => $background_value ) {
								switch ( $background_property ) {
									case 'color':
										$color_code = self::generate_css_color( $background_value );

										if ( $color_code ) {
											$css_rules[ $css_selector ][] = "background-color: {$color_code}";
										}
										break;

									case 'image':
										$dynamic_tag = ! empty( $background_value['useDynamicData'] ) ? $background_value['useDynamicData'] : false;

										if ( $dynamic_tag ) {
											// Generating template CSS file with dynamic image doesn't have a post ID (generate as inline CSS below instead)
											if ( $post_id ) {
												$image_size = ! empty( $background_value['size'] ) ? $background_value['size'] : BRICKS_DEFAULT_IMAGE_SIZE;
												$images     = Integrations\Dynamic_Data\Providers::render_tag( $dynamic_tag, $post_id, 'image', [ 'size' => $image_size ] );
												$image_id   = isset( $images[0] ) ? $images[0] : 0;

												if ( $image_id ) {
													$background_url = is_numeric( $image_id ) ? wp_get_attachment_image_url( $image_id, $background_size ) : $image_id;
												}
											}
										} else {
											$background_url = ! empty( $background_value['url'] ) ? $background_value['url'] : false;
										}

										// Add dynamic data background image: Is pseudo class OR NOT inside query loop (where its already added via inline style, @see base.php L1300)
										if ( $background_url && ( $pseudo_class || ! Query::is_looping() ) ) {
											// Add dynamic data via inline CSS (as we need the post ID of the requested post)
											if ( $dynamic_tag && Database::get_setting( 'cssLoading' ) === 'file' ) {
												self::$inline_css_dynamic_data .= $css_selector . ' {background-image: url(' . esc_url( $background_url ) . ')} ';
											} else {
												$css_rules[ $css_selector ][] = 'background-image: url(' . esc_url( $background_url ) . ')';
											}
										}
										break;

									case 'attachment':
										$css_rules[ $css_selector ][] = "background-attachment: $background_value";
										break;

									case 'repeat':
										$css_rules[ $css_selector ][] = "background-repeat: $background_value";
										break;

									case 'position':
										// Custom background-position x/y values
										if ( $background_value === 'custom' ) {
											$background_position = [];

											if ( isset( $setting_value['positionX'] ) ) {
												$background_position[] = $setting_value['positionX'];
											} else {
												$background_position[] = 'center';
											}

											if ( isset( $setting_value['positionY'] ) ) {
												$background_position[] = $setting_value['positionY'];
											} else {
												$background_position[] = 'center';
											}

											$css_rules[ $css_selector ][] = 'background-position: ' . implode( ' ', $background_position );
										} else {
											$css_rules[ $css_selector ][] = "background-position: $background_value";
										}
										break;

									case 'size':
										if ( $background_size !== 'custom' ) {
											$css_rules[ $css_selector ][] = "background-size: $background_value";
										}
										break;

									case 'custom':
										if ( $background_size === 'custom' ) {
											$css_rules[ $css_selector ][] = "background-size: $background_value";
										}
										break;
								}

								// Set background-size to cover (Bricks default)
								if ( ( $background_url || Database::get_setting( 'cssLoading' ) === 'file' ) && ! $background_size ) {
									$css_rules[ $css_selector ][] = 'background-size: cover';
								}
							}
							break;

						case 'border':
							foreach ( $setting_value as $border_property => $border ) {
								switch ( $border_property ) {
									case 'width':
										$directions = ! empty( $control['directions'] ) ? $control['directions'] : [ 'top', 'right', 'bottom', 'left' ];

										foreach ( $directions as $direction ) {
											$number = isset( $border[ $direction ] ) ? $border[ $direction ] : '';
											$unit   = ! empty( $border['unit'][ $direction ] ) ? trim( $border['unit'][ $direction ] ) : '';

											// Skip: No number, nor unit
											if ( $number === '' && $unit === '' ) {
												continue;
											}

											// Number only: Add defaultUnit
											if ( is_numeric( $number ) && $number != 0 ) {
												$unit = 'px';
											}

											// Unitless (default: 'px')
											if ( $unit === '-' || $unit === 'none' ) {
												$unit = '';
											}

											// Append unit
											$value = $unit && strpos( $number, $unit ) === false ? $number . $unit : $number;

											if ( $unit === 'auto' ) {
												$value = 'auto';
											}

											$css_rules[ $css_selector ][] = "border-$direction-width: $value";

											// Add style per direction
											if ( ! empty( $setting_value['style'] ) ) {
												$css_rules[ $css_selector ][] = "border-$direction-style: {$setting_value['style']}";
											}
										}
										break;

									case 'style':
										if ( ! isset( $setting_value['width'] ) ) {
											$css_rules[ $css_selector ][] = "border-style: $border";
										}
										break;

									case 'color':
										$color_code = self::generate_css_color( $border );

										if ( $color_code ) {
											$css_rules[ $css_selector ][] = "border-color: $color_code";
										}
										break;

									case 'radius':
										$directions    = ! empty( $control['directions'] ) ? $control['directions'] : [ 'top', 'right', 'bottom', 'left' ];
										$radius_rules  = [];
										$border_radius = null;

										foreach ( $directions as $direction ) {
											$number = isset( $border[ $direction ] ) ? $border[ $direction ] : '';
											$unit   = ! empty( $border['unit'][ $direction ] ) ? $border['unit'][ $direction ] : '';

											// Skip: No number, nor unit
											if ( $number === '' && $unit === '' ) {
												continue;
											}

											// Number only: Add defaultUnit
											if ( is_numeric( $number ) && $number != 0 ) {
												$unit = 'px';
											}

											// Unitless (default: 'px')
											if ( $unit === '-' || $unit === 'none' ) {
												$unit = '';
											}

											// Append unit
											$value = $unit && strpos( $number, $unit ) === false ? $number . $unit : $number;

											switch ( $direction ) {
												case 'top':
													$direction = 'top-left';
													break;

												case 'right':
													$direction = 'top-right';
													break;

												case 'bottom':
													$direction = 'bottom-right';
													break;

												case 'left':
													$direction = 'bottom-left';
													break;
											}

											$radius_rules[] = "border-$direction-radius: $value";

											if ( $border_radius === null || $border_radius == $value ) {
												$border_radius = $value;
											} else {
												$border_radius = false;
											}
										}

										// All four border-radius values are identical: Use 'border-radius' shorthand syntax
										if ( count( $radius_rules ) === 4 && count( array_unique( $radius_rules ) ) === 1 ) {
											$css_rules[ $css_selector ][] = "border-radius: $border_radius";
										}

										// Add individual border-radius rule (e.g. border-top-right-radius)
										else {
											foreach ( $radius_rules as $radius_rule ) {
												$css_rules[ $css_selector ][] = $radius_rule;
											}
										}
										break;
								}

							}
							break;

						case 'box-shadow':
							$box_shadow = [];

							if ( isset( $setting_value['inset'] ) ) {
								$box_shadow[] = 'inset';
							}

							$box_shadow_values = ! empty( $setting_value['values'] ) ? $setting_value['values'] : '';

							if ( $box_shadow_values ) {
								$box_shadow_properties = [ 'offsetX', 'offsetY', 'blur', 'spread' ];

								foreach ( $box_shadow_properties as $key ) {
									$box_shadow_value = isset( $box_shadow_values[ $key ] ) ? $box_shadow_values[ $key ] : 0;

									// Number only: Add defaultUnit
									if ( is_numeric( $box_shadow_value ) && $box_shadow_value != 0 ) {
										$box_shadow_value .= 'px';
									}

									$box_shadow[] = $box_shadow_value;
								}
							}

							$box_shadow_color = isset( $setting_value['color'] ) ? $setting_value['color'] : '';

							if ( $box_shadow_color ) {
								$color_code = self::generate_css_color( $box_shadow_color );

								if ( $color_code ) {
									$box_shadow[] = $color_code;
								}
							} else {
								$box_shadow[] = 'transparent';
							}

							$css_rules[ $css_selector ][] = 'box-shadow: ' . join( ' ', $box_shadow );
							break;

						case 'color':
							$color_code = self::generate_css_color( $setting_value );

							if ( $color_code ) {
								$css_rules[ $css_selector ][] = "{$css_property}: {$color_code}";
							}
							break;

						case 'dimensions':
						case 'spacing': // @since 1.5.1
							$directions = ! empty( $control['directions'] ) ? $control['directions'] : [ 'top', 'right', 'bottom', 'left' ];

							// Populate values for all set directions
							foreach ( $directions as $direction ) {
								$number = isset( $setting_value[ $direction ] ) ? $setting_value[ $direction ] : '';
								$unit   = ! empty( $setting_value['unit'][ $direction ] ) ? trim( $setting_value['unit'][ $direction ] ) : '';

								// Skip: No number, nor unit
								if ( $number === '' && $unit === '' ) {
									continue;
								}

								// Number only: Add defaultUnit
								if ( is_numeric( $number ) && $number != 0 && ! $unit ) {
									$unit = 'px';
								}

								// Unitless (default: 'px')
								if ( $unit === '-' || $unit === 'none' ) {
									$unit = '';
								}

								// Append unit
								$value = $unit && strpos( $number, $unit ) === false ? $number . $unit : $number;

								if ( $unit === 'auto' ) {
									$value = 'auto';
								}

								$property = $css_property ? "$css_property-$direction" : $direction;

								$css_rules[ $css_selector ][] = "$property: $value";
							}
							break;

						case 'filters':
							// CSS filters
							$filters = [];

							foreach ( $setting_value as $filter_key => $filter_value ) {
								if ( $filter_value === '' ) {
									continue;
								}

								switch ( $filter_key ) {
									case 'blur':
										$filter_value .= 'px';
										break;

									case 'brightness':
									case 'contrast':
									case 'invert':
									case 'opacity':
									case 'saturate':
									case 'sepia':
										$filter_value .= '%';
										break;

									case 'hue-rotate':
										$filter_value .= 'deg';
										break;
								}

								$filters[] = $filter_key . '(' . $filter_value . ')';
							}

							$css_rules[ $css_selector ][] = 'filter: ' . join( ' ', $filters );
							break;

						case 'gradient':
							if ( ! isset( $setting_value['colors'] ) ) {
								return;
							}

							$gradient_declaration = '';

							$setting_value['applyTo'] = ! empty( $setting_value['applyTo'] ) ? $setting_value['applyTo'] : 'background';

							if ( ! empty( $setting_value['cssSelector'] ) ) {
								$css_selector .= " {$setting_value['cssSelector']}";
							}

							if ( $setting_value['applyTo'] === 'text' ) {
								$css_rules[ $css_selector ][] = '-webkit-background-clip: text';
								$css_rules[ $css_selector ][] = '-webkit-text-fill-color: transparent';
							}

							$gradient_count = count( $setting_value['colors'] );

							$gradient_declaration .= 'background-image: linear-gradient(';

							// One color (use as second color too)
							if ( $gradient_count === 1 ) {
								$setting_value['colors'][] = $setting_value['colors'][0];
							}

							// Multiple colors: Angle
							elseif ( isset( $setting_value['angle'] ) ) {
								$gradient_declaration .= "{$setting_value['angle']}deg, ";
							}

							$colors = [];

							foreach ( $setting_value['colors'] as $color ) {
								if ( ! empty( $color['color']['raw'] ) ) {
									$color_value = $color['color']['raw'];
								} elseif ( ! empty( $color['color']['rgb'] ) ) {
									$color_value = $color['color']['rgb'];
								} elseif ( ! empty( $color['color']['hex'] ) ) {
									$color_value = $color['color']['hex'];
								} else {
									$color_value = false;
								}

								if ( $color_value ) {
									$colors[] = ! empty( $color['stop'] ) ? "{$color_value} {$color['stop']}%" : $color_value;
								}
							}

							if ( count( $colors ) ) {
								$gradient_declaration .= join( ', ', $colors );
								$gradient_declaration .= ')';

								/**
								 * Apply overlay to ::before pseudo class
								 * Can conflict with custom pseudo rules, but then user can move those CSS rules into ::after.
								 * Proper overlay requires ::before selector.
								 */
								if ( $setting_value['applyTo'] === 'overlay' ) {
									$css_rules[ $css_selector ][] = 'position: relative';

									$css_selector .= '::before';
								}

								$css_rules[ $css_selector ][] = $gradient_declaration;

								if ( $setting_value['applyTo'] === 'overlay' ) {
									$css_rules[ $css_selector ][] = 'position: absolute; content: ""; top: 0; right: 0; bottom: 0; left: 0; pointer-events: none';
								}
							}
							break;

						case 'icon':
							$svg_inline_css = [];

							foreach ( $setting_value as $key => $val ) {
								switch ( $key ) {
									case 'height':
									case 'width':
										// Add default unit 'px'
										if ( is_numeric( $val ) ) {
											$val .= 'px';
										}

										$svg_inline_css[] = "$key: $val";
										break;

									case 'strokeWidth':
										// Add default unit 'px'
										if ( is_numeric( $val ) ) {
											$val .= 'px';
										}

										$css_rules[ $css_selector ][] = "stroke-width: $val";
										break;

									case 'stroke':
									case 'fill':
										$color_code = self::generate_css_color( $val );

										if ( $color_code ) {
											$css_rules[ $css_selector ][] = "$key: $color_code";
										}
										break;
								}
							}

							if ( count( $svg_inline_css ) ) {
								$css_rules[ $css_selector ][] = implode( '; ', $svg_inline_css );
							}
							break;

						case 'image':
							if ( isset( $setting_value['url'] ) ) {
								$css_rules[ $css_selector ][] = $css_property . ': url(' . $setting_value['url'] . ')';
							}
							break;

						case 'radio':
							if ( count( $setting_value ) === 1 ) {
								$css_rules[ $css_selector ][] = $css_property . ': ' . $setting_value[0];
							}
							break;

						case 'transform':
							$transform = '';

							foreach ( $setting_value as $attribute => $value ) {
								switch ( $attribute ) {
									case 'translateX':
									case 'translateY':
										// Add default unit 'px' is number-only
										if ( is_numeric( $value ) && ! strpos( $value, 'var' ) && ! strpos( $value, 'calc' ) ) {
											$value .= 'px';
										}
										break;

									case 'rotateX':
									case 'rotateY':
									case 'rotateZ':
									case 'skewX':
									case 'skewY':
										// Remove unit, then add 'deg'
										$value  = intval( $value );
										$value .= 'deg';
										break;
								}

								$transform .= ' ' . $attribute . "($value)";
							}

							$css_rules[ $css_selector ][] = $css_property . ': ' . $transform;
							break;

						case 'typography':
							foreach ( $setting_value as $font_property => $font_value ) {
								switch ( $font_property ) {
									case 'color':
										$color_code = self::generate_css_color( $font_value );

										if ( $color_code ) {
											$css_rules[ $css_selector ][] = "color: $color_code";
										}
										break;

									case 'font-family':
										// Check: Custom font (value syntax: 'custom_font_{id})
										$custom_font_id = strpos( $font_value, 'custom_font_' ) !== false ? filter_var( $font_value, FILTER_SANITIZE_NUMBER_INT ) : false;

										if ( $custom_font_id ) {
											$font_value = get_the_title( $custom_font_id );

											// Add @font-face for custom fonts to inline CSS (add all @font-face rules)
											if ( get_post_type( $custom_font_id ) === BRICKS_DB_CUSTOM_FONTS && get_post_status( $custom_font_id ) === 'publish' ) {
												self::$inline_css['custom_fonts'] .= Custom_Fonts::generate_font_face_inline_css( $custom_font_id );
											}
										}

										// Check: Append fallback font (@since 1.5.1)
										$fallback_font = ! empty( $setting_value['fallback'] ) ? ", {$setting_value['fallback']}" : '';

										// Always add quotes to font-family (https://www.w3.org/TR/2011/REC-CSS2-20110607/fonts.html#font-family-prop)
										$css_rules[ $css_selector ][] = "$font_property: \"$font_value\"$fallback_font";
										break;

									case 'text-shadow':
										$text_shadow = [];

										$text_shadow_values = isset( $font_value['values'] ) ? $font_value['values'] : '';

										if ( $text_shadow_values ) {
											$text_shadow[] = isset( $text_shadow_values['offsetX'] ) && ! empty( $text_shadow_values['offsetX'] ) ? $text_shadow_values['offsetX'] . 'px' : 0;
											$text_shadow[] = isset( $text_shadow_values['offsetY'] ) && ! empty( $text_shadow_values['offsetY'] ) ? $text_shadow_values['offsetY'] . 'px' : 0;
											$text_shadow[] = isset( $text_shadow_values['blur'] ) && ! empty( $text_shadow_values['blur'] ) ? $text_shadow_values['blur'] . 'px' : 0;
										}

										$text_shadow_color = isset( $font_value['color'] ) ? $font_value['color'] : '';

										if ( $text_shadow_color ) {
											$color_code = self::generate_css_color( $text_shadow_color );

											if ( $color_code ) {
												$text_shadow[] = $color_code;
											}
										} else {
											$text_shadow[] = 'transparent';
										}

										$css_rules[ $css_selector ][] = $font_property . ': ' . join( ' ', $text_shadow );
										break;

									default:
										if (
											! is_array( $font_value ) &&
											$font_property !== 'font-variants' &&
											$font_property !== 'fallback'
										) {
											if ( $font_property === 'font-size' ) {
												// Numeric value: Append defaultUnit (px)
												if ( is_numeric( $font_value ) ) {
													$font_value .= 'px';
												}
											}

											$css_rules[ $css_selector ][] = "{$font_property}: {$font_value}";
										}
								}
							}
							break;

						default:
							if ( Capabilities::current_user_has_full_access() ) {
								error_log( 'Error: Control type ' . $control_type . ' is not defined!' );
							}
							break;
					}
				}

				// String value (number, etc.)
				else {
					// ControlNumber
					if ( $control_type === 'number' ) {
						// Append unit (only once for each css_selector to avoid 'pxpx', etc.)
						if ( ! empty( $control['unit'] ) && ! strpos( $setting_value, $control['unit'] ) ) {
							$setting_value .= $control['unit'];
						}

						// Number + unit
						elseif ( ! empty( $control['units'] ) ) {
							// Unit missing: Append default unit (px)
							if ( is_numeric( $setting_value ) ) {
								$setting_value = $setting_value . 'px';
							}
						}
					}

					// Build CSS property for 'transform'
					if ( strlen( $css_property ) && strpos( $css_property, 'transform:' ) !== false ) {
						$transform_parts = explode( ':', $css_property );
						$css_property    = $transform_parts[0];
						$setting_value   = "$transform_parts[1]($setting_value)";
					}

					/**
					 * Check: CSS property 'value'
					 *
					 * Replace '%s' placeholder with 'value'
					 *
					 * @see '_content' for pseudo classes runs through as well
					 * @example repeat(%s, 1fr) to set mobile breakpoint CSS grid columns without having to use classes.
					 *
					 * @since 1.3
					 */
					if ( isset( $css_definition['value'] ) ) {
						if ( $css_property === 'content' ) {
							// Strip slashes except if it's the pseudo class "_content", then we'll keep the slashes e.g. "\f410" (@since 1.5.1)
							if ( $control_key !== '_content' ) {
								$setting_value = stripslashes_deep( $setting_value );
							}

							$setting_value = str_replace( "'", '', $setting_value );
							$setting_value = str_replace( '"', '', $setting_value );

							$setting_value = Integrations\Dynamic_Data\Providers::render_content( $setting_value, $post_id );
						}

						// Check: 'required' value set, but doesn't match $setting_value (@since 1.4)
						if ( ! empty( $css_definition['required'] ) && $setting_value !== $css_definition['required'] ) {
							$setting_value = '';
						} else {
							if ( strpos( $css_definition['value'], '%s' ) === false ) {
								$setting_value = $css_definition['value'];
							} else {
								$setting_value = str_replace( '%s', $setting_value, $css_definition['value'] );
							}
						}
					}

					// Simple string CSS value

					// Invert gutter/spacing (image gallery, slider etc.)
					if ( $setting_value !== '' ) {
						if ( isset( $css_definition['invert'] ) ) {
							$css_rules[ $css_selector ][] = "$css_property: -$setting_value";
						} else {
							$css_rules[ $css_selector ][] = "$css_property: $setting_value";
						}
					}
				}

				// Append ' !important' to CSS rule
				if ( ! empty( $css_rules[ $css_selector ] ) ) {
					foreach ( $css_rules[ $css_selector ] as $index => $rule ) {
						if ( isset( $css_definition['important'] ) && ! strpos( $rule, '!important' ) ) {
							$css_rules[ $css_selector ][ $index ] .= ' !important';
						}
					}
				}
			}
		}

		// Add breakpoint-specific CSS string to css_type (content, theme_style, etc.)
		if ( $breakpoint ) {
			// Add CSS string to css_type and breakpoint
			if ( ! isset( self::$inline_css_breakpoints[ $css_type ][ $breakpoint ] ) ) {
				self::$inline_css_breakpoints[ $css_type ][ $breakpoint ] = '';
			}

			if ( count( $css_rules ) ) {
				foreach ( $css_rules as $css_selector => $css_declarations ) {
					// Remove duplicate CSS declarations
					$css_declarations = array_unique( $css_declarations, SORT_STRING );

					self::$inline_css_breakpoints[ $css_type ][ $breakpoint ] .= $css_selector . ' {' . join( '; ', $css_declarations ) . '}' . PHP_EOL;
				}
			}

			// Add plain CSS: _cssCustom (@since 1.5.1)
			elseif ( ! count( $css_rules ) && is_string( $setting_value ) ) {
				self::$inline_css_breakpoints[ $css_type ][ $breakpoint ] .= $setting_value . PHP_EOL;
			}

			return [];
		}

		return $css_rules;
	}

	/**
	 * Generate CSS string
	 *
	 * @param array  $element Array containing all element data (to retrieve element settings and name).
	 * @param array  $controls Array containing all element controls (to retrieve CSS selectors and properties).
	 * @param string $css_type String global/page/header/content/footer/mobile
	 *
	 * @return string (use & process asset-optimization)
	 */
	public static function generate_inline_css_from_element( $element, $controls, $css_type ) {
		$settings   = ! empty( $element['settings'] ) ? $element['settings'] : [];
		$element_id = ! empty( $element['id'] ) ? $element['id'] : '';

		// STEP: Generate CSS selector
		$css_selector = '';

		if ( $element_id ) {
			// Check if user has set a custom CSS ID
			$element_css_id = ! empty( $settings['_cssId'] ) ? $settings['_cssId'] : "brxe-{$element_id}";

			// Global element: Use CSS class (to apply styles to every occurence of this global element)
			if ( ! empty( $element['global'] ) ) {
				$css_selector = ".brxe-{$element['global']}";
			}

			// Element in loop selector
			elseif ( Query::is_looping() ) {
				$loop_element_id = Query::get_query_element_id();

				// Combine the loop element ID with the element id - enable multiple query loops containing the same template element (@since 1.5)
				$loop_style_key = $loop_element_id . $element_id;

				// CSS is identical for every loop item (except DD featured image)
				if ( ! in_array( $loop_style_key, self::$css_looping_elements ) ) {
					self::$css_looping_elements[] = $loop_style_key;

					// Using custom CSS id or default id
					$css_selector = ".{$element_css_id}";

					// Prefix selector with loop element ID to precede default element styles
					// (as we uses CSS classes instead of element ID inside a query loop)
					if ( $loop_element_id && $loop_element_id !== $element_id ) {
						$css_selector = ".brxe-{$loop_element_id} $css_selector";
					}

					// Append element name CSS class (to ensure query loop CSS styles precede default CSS like .brxe-container "width: 1100px", etc.)
					$css_selector .= ".brxe-{$element['name']}";
				}

				// Return: No need to generate CSS for this element in the loop (@since 1.5)
				else {
					return;
				}
			}

			/**
			 * Slides of 'slider-nested' element: Use 'data-id' as CSS selector to target cloned slides too (splide padding, etc.)
			 *
			 * .splide__slide selector needed to on frontend for specificity.
			 *
			 * @since 1.5.1
			 */
			elseif ( ! empty( $element['parent'] ) && isset( self::$elements[ $element['parent'] ]['name'] ) && self::$elements[ $element['parent'] ]['name'] === 'slider-nested' ) {
				$css_selector = "[data-id=\"{$element_css_id}\"].splide__slide";
			}

			// Default (custom CSS ID or the default brxe-)
			else {
				$css_selector = "#$element_css_id";
			}
		}

		// STEP: Prepend global class name
		if ( ! empty( $element['_cssGlobalClass'] ) ) {
			$css_selector = ".{$element['_cssGlobalClass']}";

			// Append element name CSS class
			$css_selector .= ".brxe-{$element['name']}";
		}

		$css_rules  = [];
		$inline_css = '';

		/**
		 * STEP: Get settings of all global elements
		 *
		 * For inline style loading only (use global-elements.min.css for external file CSS loading method to reflect global element changes everywhere)
		 */
		if ( Database::get_setting( 'cssLoading' ) !== 'file' ) {
			foreach ( Database::$global_data['elements'] as $global_element ) {
				// @since 1.2.1
				if ( ! empty( $global_element['global'] ) && ! empty( $element['global'] ) && $global_element['global'] === $element['global'] ) {
					$settings   = $global_element['settings'];
					$element_id = $global_element['global'];
				}

				// @pre 1.2.1
				elseif ( ! empty( $global_element['id'] ) && $global_element['id'] === $element_id ) {
					$settings   = $global_element['settings'];
					$element_id = $global_element['global'];
				}
			}
		}

		// STEP: Generate CSS rules array of every element setting
		foreach ( $settings as $setting_key => $setting_value ) {
			$setting_css_rules = self::generate_css_rules_from_setting( $settings, $setting_key, $setting_value, $controls, $css_selector, $css_type );

			// Add new CSS rules to existing ones
			if ( is_array( $setting_css_rules ) ) {
				foreach ( $setting_css_rules as $selector => $new_rules ) {
					if ( isset( $css_rules[ $selector ] ) ) {
						$css_rules[ $selector ] = array_merge( $css_rules[ $selector ], $new_rules );
					} else {
						$css_rules[ $selector ] = $new_rules;
					}
				}
			}
		}

		// STEP: Generate inline CSS (string)
		foreach ( $css_rules as $css_selector => $css_declarations ) {
			// Remove duplicate CSS declarations
			$css_declarations = array_unique( $css_declarations, SORT_STRING );

			// Order CSS declarations
			$css_declarations = array_values( $css_declarations );

			$inline_css .= $css_selector . ' {' . join( '; ', $css_declarations ) . '}' . PHP_EOL;
		}

		// STEP: Append custom CSS (string)
		$custom_css = '';

		// Global & page settings: Custom CSS
		if ( ! empty( $settings['customCss'] ) ) {
			$custom_css = $settings['customCss'];
		}

		// Element: Custom CSS (if looping, render custom_css for loop index = 0 only)
		if ( ! empty( $settings['_cssCustom'] ) ) {
			$custom_css = $settings['_cssCustom'];

			if ( Query::is_looping() ) {
				static $element_custom_css = [];

				$loop_element_id = Query::get_query_element_id();

				// Combine the loop element ID with the element id - enable multiple query loops containing the same template element (@since 1.5)
				$loop_style_key = $loop_element_id . $element_id;

				// CSS is identical for every loop item: skip the custom CSS for this element (@since 1.5.1)
				if ( in_array( $loop_style_key, $element_custom_css ) ) {
					$custom_css = '';
				} else {
					$element_custom_css[] = $loop_style_key;

					$custom_css = str_replace( "#brxe-$element_id", ".brxe-$element_id", $custom_css );
				}
			}

			if ( $custom_css ) {
				$custom_css = str_replace( [ "\r","\n" ], '', $custom_css );
				$custom_css = str_replace( '  ', ' ', $custom_css );
				$custom_css = str_replace( '}.', '} .', $custom_css );
			}
		}

		if ( $custom_css ) {
			// This is removing the slash of content: "\2713"; - outcommented (@since 1.5)
			// $custom_css = stripslashes( $custom_css );

			// Get custom font face CSS inside custom CSS to load the font-face
			$custom_fonts_css = self::generate_custom_font_face_from_custom_css( $custom_css );

			if ( $custom_fonts_css ) {
				$inline_css .= $custom_fonts_css;
			}

			$inline_css .= $custom_css . PHP_EOL;
		}

		if ( ! isset( self::$inline_css[ $css_type ] ) ) {
			self::$inline_css[ $css_type ] = '';
		}

		// STEP: Add breakpoint CSS
		if ( $css_type !== 'theme_style' ) {
			$inline_css = self::generate_inline_css_for_breakpoints( $css_type, $inline_css ) . PHP_EOL;
		}

		if ( $inline_css && ! strpos( self::$inline_css[ $css_type ], $inline_css ) ) {
			self::$inline_css[ $css_type ] .= $inline_css;
		}

		// @since 1.3.4 (asset-optimization)
		return $inline_css;
	}

	/**
	 * Generate inline CSS for breakpoints of specific type (content, theme_style, etc.)
	 *
	 * @since 1.3.5
	 *
	 * @return string
	 */
	public static function generate_inline_css_for_breakpoints( $css_type, $desktop_css ) {
		$breakpoints = Breakpoints::get_breakpoints();
		$base_width  = Breakpoints::$base_width;
		$inline_css  = '';

		foreach ( $breakpoints as $index => $breakpoint ) {
			// Skip: Paused breakpoint
			if ( isset( $breakpoint['paused'] ) ) {
				continue;
			}

			$key   = ! empty( $breakpoint['key'] ) ? $breakpoint['key'] : false;
			$label = ! empty( $breakpoint['label'] ) ? $breakpoint['label'] : $key;
			$width = ! empty( $breakpoint['width'] ) ? $breakpoint['width'] : false;
			$value = isset( self::$inline_css_breakpoints[ $css_type ][ $key ] ) ? self::$inline_css_breakpoints[ $css_type ][ $key ] : false;

			if ( $key === 'desktop' ) {
				$value = $desktop_css;
			}

			if ( ! $value ) {
				continue;
			}

			// Skip adding @media rule for custom base breakpoint
			$is_base_breakpoint = isset( $breakpoint['base'] );

			if ( $is_base_breakpoint ) {
				$label .= ' (BASE)';
			}

			/**
			 * Larger than base breakpoint:  use 'min-width'
			 * Smaller than base breakpoint: use 'max-width'
			 */
			$breakpoint_css = "\n/* BREAKPOINT: $label */\n";

			if ( ! $is_base_breakpoint ) {
				$breakpoint_css .= $width > $base_width ? "@media (min-width: {$width}px) {\n" : "@media (max-width: {$width}px) {\n";
			}

			$breakpoint_css .= $value;

			if ( ! $is_base_breakpoint ) {
				$breakpoint_css .= '}';
			}

			// Is base breakpoint, but not mobile-frist: Add first
			if ( ! Breakpoints::$is_mobile_first && ( $is_base_breakpoint || $width > $base_width ) ) {
				$inline_css = $breakpoint_css . $inline_css;
			}

			// Not the base breakpoint: Append (as @media rules need to come last)
			else {
				$inline_css .= $breakpoint_css;
			}

			// Clear breakpoint value to avoid generating duplicates (see: theme styles)
			unset( self::$inline_css_breakpoints[ $css_type ][ $key ] );
		}

		return $inline_css;
	}

	/**
	 * Generate custom font face from "Custom CSS" setting value
	 *
	 * @since 1.4.0.1
	 */
	public static function generate_custom_font_face_from_custom_css( $custom_css ) {
		// Return: No 'font-family' rule
		if ( strpos( $custom_css, 'font-family' ) === false ) {
			return;
		}

		$all_custom_fonts        = Custom_Fonts::$fonts;
		$css_declarations        = explode( '{', $custom_css );
		$font_variant_inline_css = '';

		// Loop over every CSS declaration "{}" (could contain custom font rules)
		foreach ( $css_declarations as $css_declaration ) {
			$custom_css_rules = explode( ';', $css_declaration );

			$custom_font_id = '';
			$font_weight    = 400;
			$font_style     = '';

			// Loop over rules to get font-family (font-weight, font-style must be set after font-family to group them)
			foreach ( $custom_css_rules as $index => $rule ) {
				$parts        = explode( ':', $rule );
				$css_property = isset( $parts[0] ) ? $parts[0] : false;
				$css_value    = isset( $parts[1] ) ? $parts[1] : false;

				if ( strpos( $css_property, 'font-family' ) !== false ) {
					$font_family = trim( $css_value );
					$font_family = str_replace( '"', '', $font_family );
					$font_family = str_replace( "'", '', $font_family );

					// Get custom font ID
					if ( strpos( $css_value, 'custom_font_' ) !== false ) {
						$custom_font_id = filter_var( $font_family, FILTER_SANITIZE_NUMBER_INT );
					}

					// Get custom font ID by name
					else {
						foreach ( $all_custom_fonts as $custom_font ) {
							if ( $custom_font['family'] === $font_family ) {
								$custom_font_id = $custom_font_id = filter_var( $custom_font['id'], FILTER_SANITIZE_NUMBER_INT );
							}
						}
					}
				} elseif ( strpos( $css_property, 'font-weight' ) !== false ) {
					$font_weight = filter_var( $css_value, FILTER_SANITIZE_NUMBER_INT );
				} elseif ( strpos( $css_property, 'font-style' ) !== false ) {
					$font_style = $css_value;
				}
			}

			if ( $custom_font_id ) {
				$font_variant_inline_css = Custom_Fonts::generate_font_face_inline_css( $custom_font_id, $font_weight, $font_style );
			}
		}

		return $font_variant_inline_css;
	}

	/**
	 * Generate CSS from elements
	 *
	 * @param array  $elements Array to loop through all the elements to generate CSS string of entire data.
	 * @param string $css_type header, footer, content, etc. (see: $inline_css)
	 *
	 * @return void
	 */
	public static function generate_css_from_elements( $elements, $css_type ) {
		if ( empty( $elements ) || ! is_array( $elements ) ) {
			return;
		}

		// Flat element list (@since1.2)
		self::$elements = [];

		// Prepare flat list of elements for recursive calls
		foreach ( $elements as $element ) {
			self::$elements[ $element['id'] ] = $element;
		}

		foreach ( $elements as $element ) {
			if ( ! empty( $element['parent'] ) ) {
				continue;
			}

			self::generate_css_from_element( $element, $css_type );
		}
	}

	public static function generate_css_from_element( $element, $css_type ) {
		$settings = ! empty( $element['settings'] ) ? $element['settings'] : false;

		// Store elements global classes (for each class ID, store the elements that use it)
		$element_css_global_classes = ! empty( $settings['_cssGlobalClasses'] ) ? $settings['_cssGlobalClasses'] : [];

		foreach ( $element_css_global_classes as $css_class_id ) {
			if ( ! isset( self::$global_classes_elements[ $css_class_id ] ) || ! in_array( $element['name'], self::$global_classes_elements[ $css_class_id ] ) ) {
				self::$global_classes_elements[ $css_class_id ][] = $element['name'];
			}
		}

		if ( in_array( $element['name'], [ 'container', 'block', 'div' ] ) && isset( $settings['hasLoop'] ) && ! Query::is_looping( $element['id'] ) ) {
			$query = new Query( $element );

			// Prevent endless loop
			unset( $element['settings']['hasLoop'] );

			$query->render( 'Bricks\Assets::generate_css_from_element', compact( 'element', 'css_type' ) ); // Recursive

			// We need to destroy the Query to explicitly remove it from the global store
			$query->destroy();
			unset( $query );

			// After generating the CSS of the loop, do not continue here
			return;
		}

		// Nestable elements (container, div, slider-nested, etc.)
		if ( ! empty( $element['children'] ) ) {
			foreach ( $element['children'] as $child_index => $child_id ) {
				if ( ! array_key_exists( $child_id, self::$elements ) ) {
					continue;
				}

				$child_element = self::$elements[ $child_id ];

				self::generate_css_from_element( $child_element, $css_type ); // Recursive
			}
		}

		// Template element: Generate CSS for all elements of template element
		elseif ( $element['name'] == 'template' ) {
			$template_id = ! empty( $settings['template'] ) ? intval( $settings['template'] ) : 0;

			if ( $template_id && $template_id != get_the_ID() ) {
				$template_data = get_post_meta( $template_id, BRICKS_DB_PAGE_CONTENT, true );

				// Template used inside a loop
				$looping_query_id   = Query::is_any_looping();
				$looping_element_id = Query::get_query_element_id( $looping_query_id );

				// Used to make sure we only render styles once for each template, or a combination of a template inside of a loop element (multiple loops using the same template)
				$template_key = $looping_element_id ? $template_id . '-' . $looping_element_id : $template_id;

				// Avoid infinite loops
				static $templates_css = [];

				if ( ! empty( $template_data ) && is_array( $template_data ) && ! in_array( $template_key, $templates_css ) ) {
					$templates_css[] = $template_key;

					// Add the template ID to the Page Settings list to be rendered
					self::$page_settings_post_ids[] = $template_id;

					// Check for icon fonts and global elements
					self::enqueue_setting_specific_scripts( $template_data );

					// Store the current main render_data self::$elements
					$store_elements = self::$elements;

					self::generate_css_from_elements( $template_data, $css_type ); // Recursive call

					// Reset the main render_data self::$elements
					self::$elements = $store_elements;
				}
			}
		}

		// Post Content element: Rendering Bricks data
		elseif ( $element['name'] == 'post-content' && isset( $settings['dataSource'] ) && $settings['dataSource'] === 'bricks' ) {
			// Post Content used inside a loop
			$looping_query_id       = Query::is_any_looping();
			$loop_query_object_type = Query::get_query_object_type( $looping_query_id );

			$post_id = $loop_query_object_type === 'post' ? get_the_ID() : Database::$page_data['preview_or_post_id'];

			// Do not remove this line to avoid infinite loops
			if ( get_post_type( $post_id ) === BRICKS_DB_TEMPLATE_SLUG ) {
				$post_id = Helpers::get_template_setting( 'templatePreviewPostId', $post_id );
			}

			if ( ! empty( $post_id ) ) {
				$bricks_data = get_post_meta( $post_id, BRICKS_DB_PAGE_CONTENT, true );

				if ( ! empty( $bricks_data ) && is_array( $bricks_data ) ) {

					// Add the Post ID to the Page Settings list to be rendered
					self::$page_settings_post_ids[] = $post_id;

					// Check for icon fonts and global elements
					self::enqueue_setting_specific_scripts( $bricks_data );

					// Store the current main render_data self::$elements
					$store_elements = self::$elements;

					self::generate_css_from_elements( $bricks_data, $css_type ); // Recursive call

					// Reset the main render_data self::$elements
					self::$elements = $store_elements;
				}
			}
		}

		// Nav menu: To add @media rules for mobile menu & toggle visibility (@since 1.5.1)
		elseif ( $element['name'] == 'nav-menu' ) {
			$mobile_menu_on_breakpoint = isset( $settings['mobileMenu'] ) ? $settings['mobileMenu'] : 'mobile_landscape';

			if ( $mobile_menu_on_breakpoint !== 'always' && $mobile_menu_on_breakpoint !== 'never' ) {
				$nav_menu_class_name = ! empty( Elements::$elements['nav-menu']['class'] ) ? Elements::$elements['nav-menu']['class'] : false;

				if ( $nav_menu_class_name ) {
					$nav_menu_instance = new $nav_menu_class_name( $element );
					$breakpoint        = Breakpoints::get_breakpoint_by( 'key', $mobile_menu_on_breakpoint );

					self::$inline_css[ $css_type ] .= $nav_menu_instance->generate_mobile_menu_inline_css( $settings, $breakpoint );
				}
			}
		}

		$controls = Elements::get_element( $element, 'controls' );

		self::generate_inline_css_from_element( $element, $controls, $css_type );
	}
}
