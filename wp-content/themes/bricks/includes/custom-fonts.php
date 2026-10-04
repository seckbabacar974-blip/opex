<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Custom Fonts Upload
 *
 * Font naming convention: custom_font_{font_id}
 *
 * @since 1.0
 */

class Custom_Fonts {
	public static $fonts = [];

	public function __construct() {
		self::get_custom_fonts();

		add_filter( 'init', [ $this, 'register_post_type' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

		add_filter( 'post_row_actions', [ $this, 'post_row_actions' ], 10, 2 );

		add_filter( 'manage_' . BRICKS_DB_CUSTOM_FONTS . '_posts_columns', [ $this, 'manage_columns' ] );
		add_action( 'manage_' . BRICKS_DB_CUSTOM_FONTS . '_posts_custom_column', [ $this, 'render_columns' ], 10, 2 );

		add_action( 'add_meta_boxes_' . BRICKS_DB_CUSTOM_FONTS, [ $this, 'add_meta_boxes' ] );
		add_filter( 'upload_mimes', [ $this, 'upload_mimes' ] );

		add_action( 'wp_ajax_bricks_save_font_faces', [ $this, 'save_font_faces' ], 10, 2 );
	}

	/**
	 * Get all custom fonts (in-builder & assets generation)
	 */
	public static function get_custom_fonts() {
		$custom_font_ids = get_posts(
			[
				'post_type'      => BRICKS_DB_CUSTOM_FONTS,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		);

		$custom_fonts = [];

		foreach ( $custom_font_ids as $custom_font_id ) {
			$font_faces = get_post_meta( $custom_font_id, BRICKS_DB_CUSTOM_FONT_FACES, true );

			if ( ! $font_faces ) {
				continue;
			}

			$css = [];

			foreach ( $font_faces as $font_variant => $font_face ) {
				$font_weight          = substr( $font_variant, 0, 3 );
				$font_style           = substr( $font_variant, 3, strlen( $font_variant ) );
				$css[ $font_variant ] = self::generate_font_face_inline_css( $custom_font_id, $font_weight, $font_style );
			}

			// Add 'custom_font_' prefix for correct font order in ControlTypography.vue & to build @font-face from font ID
			$custom_fonts[ "custom_font_{$custom_font_id}" ] = [
				'id'     => "custom_font_{$custom_font_id}",
				'family' => get_the_title( $custom_font_id ),
				'css'    => $css,
			];
		}

		self::$fonts = $custom_fonts;

		return $custom_fonts;
	}

	public function admin_enqueue_scripts() {
		$current_screen = get_current_screen();

		if ( is_object( $current_screen ) && $current_screen->post_type === BRICKS_DB_CUSTOM_FONTS ) {
			wp_enqueue_media();

			wp_enqueue_script( 'bricks-custom-fonts', BRICKS_URL_ASSETS . 'js/custom-fonts.min.js', [], filemtime( BRICKS_PATH_ASSETS . 'js/custom-fonts.min.js' ), true );
		}
	}

	public function add_meta_boxes() {
		add_meta_box(
			'bricks-font-metabox',
			esc_html__( 'Manage your custom font files', 'bricks' ),
			[ $this, 'render_meta_boxes' ],
			BRICKS_DB_CUSTOM_FONTS,
			'normal',
			'default'
		);
	}

	/**
	 * Enable font file uploads for the following mime types: .TTF, .woff, .woff2 (specified in 'get_custom_fonts_mime_types' function below)
	 *
	 * .EOT only supported in IE (https://caniuse.com/?search=eot)
	 *
	 * https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types
	 */
	public function upload_mimes( $mime_types ) {
		if ( Capabilities::current_user_can_use_builder() && isset( $_POST['bricksCustomFontsUpload'] ) ) {
			foreach ( $this->get_custom_fonts_mime_types() as $type => $mime ) {
				if ( ! isset( $mime_types[ $type ] ) ) {
					$mime_types[ $type ] = $mime;
				}
			}
		}

		return $mime_types;
	}

	private static function get_custom_fonts_mime_types() {
		$font_mime_types = [
			// 'eot'   => 'font/eot', // <IE9 only (if specified, it must be listed first)
			'woff2' => 'font/woff2',
			'woff'  => 'font/woff',
			'ttf'   => 'font/ttf',
		];

		// NOTE: Undocumented
		return apply_filters( 'bricks/custom_fonts/mime_types', $font_mime_types );
	}

	/**
	 * Generate custom font-face declarations
	 *
	 * Load all font-faces to avoid having to select font-family too (@since 1.5)
	 *
	 * @param integer $font_id The custom font ID.
	 * @param integer $font_weight 100 - 900
	 * @param string  $font_style italic, etc.
	 */
	public static function generate_font_face_inline_css( $font_id = 0, $font_weight = '', $font_style = '' ) {
		$font_id    = intval( trim( $font_id ) );
		$font_faces = get_post_meta( $font_id, BRICKS_DB_CUSTOM_FONT_FACES, true );

		if ( ! $font_faces ) {
			return;
		}

		$custom_fonts         = Assets::$inline_css['custom_fonts'];
		$font_face_inline_css = '';

		// $key is weight + variant string (e.g.: '700italic')
		foreach ( $font_faces as $key => $font_face ) {
			$font_weight = filter_var( $key, FILTER_SANITIZE_NUMBER_INT );
			$font_style  = str_replace( $font_weight, '', $key );
			$src         = [];

			foreach ( $font_face as $format => $value ) {
				$font_variant_url = wp_get_attachment_url( $font_face[ $format ] );

				// Skip if font variant URL has already been added to custom fonts inline CSS
				if ( $custom_fonts && strpos( $custom_fonts, $font_variant_url ) ) {
					continue;
				}

				if ( $font_variant_url ) {
					if ( $format === 'ttf' ) {
						$format = 'truetype';
					} elseif ( $format === 'otf' ) {
						$format = 'opentype';
					} elseif ( $format === 'eot' ) {
						$format = 'embedded-opentype';
					}

					// Load woff2 first @since 1.4 (smaller file size, almost same support as 'woff')
					if ( $format === 'woff2' ) {
						array_unshift( $src, "url($font_variant_url) format(\"$format\")" );
					} else {
						array_push( $src, "url($font_variant_url) format(\"$format\")" );
					}
				}
			}

			if ( ! count( $src ) ) {
				return;
			}

			$font_family = get_the_title( $font_id );
			$src         = implode( ",\n", $src );

			if ( $font_family && $src ) {
				$font_face_inline_css .= "@font-face {\n";
				$font_face_inline_css .= "	font-family: \"$font_family\";\n";

				if ( $font_weight ) {
					$font_face_inline_css .= "	font-weight: $font_weight;\n";
				}

				if ( $font_style ) {
					$font_face_inline_css .= "	font-style: $font_style;\n";
				}

				$font_face_inline_css .= "	font-display: swap;\n";
				$font_face_inline_css .= "	src: $src;\n";
				$font_face_inline_css .= "}\n";
			}
		}

		return $font_face_inline_css;
	}

	public function render_meta_boxes( $post ) {
		echo '<h2 class="title">';
		esc_html_e( 'Manage your custom font files', 'bricks' );
		echo Helpers::article_link( 'custom-fonts', '<i class="dashicons dashicons-editor-help"></i>' );
		echo '</h2>';

		$font_faces = get_post_meta( $post->ID, BRICKS_DB_CUSTOM_FONT_FACES, true );

		if ( is_array( $font_faces ) && count( $font_faces ) ) {
			foreach ( $font_faces as $font_variant => $font_face ) {
				echo self::render_font_faces_meta_box( $font_face, $font_variant );
			}
		} else {
			echo self::render_font_faces_meta_box( [], 400 );
		}

		echo '<button id="bricks-custom-fonts-add-font-variant" class="button button-primary">' . esc_html__( 'Add a font variant', 'bricks' ) . '</button>';
	}

	public static function render_font_faces_meta_box( $font_face = [], $font_variant = 400 ) {
		$mime_types  = self::get_custom_fonts_mime_types();
		$font_weight = substr( $font_variant, 0, 3 );
		$font_style  = substr( $font_variant, 3, strlen( $font_variant ) );
		?>
		<div class="bricks-font-variant">
			<div class="font-header">
				<div
					class="bricks-font-weight-wrapper"
					data-balloon="<?php esc_html_e( 'Font weight', 'bricks' ); ?>"
					data-balloon-pos="top">
					<select name="font_weight">
						<option value="100" <?php selected( $font_weight, 100, true ); ?>><?php echo '100 (' . esc_html__( 'Thin', 'bricks' ); ?>)</option>
						<option value="200" <?php selected( $font_weight, 200, true ); ?>><?php echo '200 (' . esc_html__( 'Extra Light', 'bricks' ); ?>)</option>
						<option value="300" <?php selected( $font_weight, 300, true ); ?>><?php echo '300 (' . esc_html__( 'Light', 'bricks' ); ?>)</option>
						<option value="400" <?php selected( $font_weight, 400, true ); ?>><?php echo '400 (' . esc_html__( 'Normal', 'bricks' ); ?>)</option>
						<option value="500" <?php selected( $font_weight, 500, true ); ?>><?php echo '500 (' . esc_html__( 'Medium', 'bricks' ); ?>)</option>
						<option value="600" <?php selected( $font_weight, 600, true ); ?>><?php echo '600 (' . esc_html__( 'Semi Bold', 'bricks' ); ?>)</option>
						<option value="700" <?php selected( $font_weight, 700, true ); ?>><?php echo '700 (' . esc_html__( 'Bold', 'bricks' ); ?>)</option>
						<option value="800" <?php selected( $font_weight, 800, true ); ?>><?php echo '800 (' . esc_html__( 'Extra Bold', 'bricks' ); ?>)</option>
						<option value="900" <?php selected( $font_weight, 900, true ); ?>><?php echo '900 (' . esc_html__( 'Black', 'bricks' ); ?>)</option>
					</select>
				</div>

				<div
					class="bricks-font-style-wrapper"
					data-balloon="<?php esc_html_e( 'Font style', 'bricks' ); ?>"
					data-balloon-pos="top">
					<select name="font_style">
						<option value="" <?php selected( $font_style, '', true ); ?>><?php esc_html_e( 'Normal', 'bricks' ); ?></option>
						<option value="italic" <?php selected( $font_style, 'italic', true ); ?>><?php esc_html_e( 'Italic', 'bricks' ); ?></option>
						<option value="oblique" <?php selected( $font_style, 'oblique', true ); ?>><?php esc_html_e( 'Oblique', 'bricks' ); ?></option>
					</select>
				</div>

				<div
					class="bricks-font-preview"
					data-balloon="<?php esc_html_e( 'Font preview', 'bricks' ); ?>"
					data-balloon-pos="top">
					<?php
					// @font-face
					$custom_font_id          = get_the_ID();
					$font_family             = get_the_title();
					$font_variant_inline_css = self::generate_font_face_inline_css( $custom_font_id, $font_weight, $font_style );
					$style                   = [];

					if ( $font_variant_inline_css ) {
						echo '<style>' . $font_variant_inline_css . '</style>';

						$style[] = 'font-family: "' . $font_family . '"';
						$style[] = 'font-weight: ' . $font_weight;

						if ( ! empty( $font_style ) ) {
							$style[] = "font-style: $font_style";
						}
					}
					?>
					<div class="pangram" style='<?php echo implode( ';', $style ); ?>'><?php esc_html_e( 'The quick brown fox jumps over the lazy dog.', 'bricks ' ); ?></div>
				</div>

				<div class="actions">
					<button class="button edit" data-label="<?php esc_html_e( 'Close', 'bricks' ); ?>"><?php esc_html_e( 'Edit', 'bricks' ); ?></button>
					<button class="button delete"><?php esc_html_e( 'Delete', 'bricks' ); ?></button>
				</div>
			</div>

			<ul class="font-faces hide">
				<?php
				foreach ( $mime_types as $extension => $mime_type ) {
					$font_id     = isset( $font_face[ $extension ] ) ? $font_face[ $extension ] : '';
					$font_url    = wp_get_attachment_url( $font_id );
					$file_size   = $font_id ? ceil( filesize( get_attached_file( $font_id ) ) / 1024 ) . ' KB' : false;
					$placeholder = '';

					switch ( $extension ) {
						case 'ttf':
							$placeholder = esc_html__( 'TrueType Font: Uncompressed font data, but partial IE9+ support.', 'bricks' );
							break;

						case 'woff':
							$placeholder = esc_html__( 'Web Open Font Format: Compressed TrueType/OpenType font with information about font source and full IE9+ support (recommended).', 'bricks' );
							break;

						case 'woff2':
							$placeholder = esc_html__( 'Web Open Font Format 2.0: TrueType/OpenType font with even better compression than WOFF 1.0, but no IE browser support.', 'bricks' );
							break;
					}
					?>
				<li class="font-face">
					<label>
						<div
							class="font-name"
							data-balloon="<?php echo $file_size; ?>"
							data-balloon-pos="top">
							<?php printf( esc_html__( '%s file', 'bricks' ), strtoupper( $extension ) ); ?>
						</div>
					</label>

					<input type="url" name="font_url" value="<?php echo $font_url; ?>" placeholder="<?php echo $placeholder; ?>">
					<input type="number" name="font_id" value="<?php echo $font_id; ?>">

					<button
						id="<?php echo Helpers::generate_random_id(); ?>"
						class="button upload<?php echo $font_id ? ' hide' : ''; ?>"
						data-mime-type="<?php echo esc_attr( $mime_type ); ?>"
						data-extension="<?php echo esc_attr( $extension ); ?>"
						data-title="<?php echo esc_attr( sprintf( esc_html__( 'Upload .%s file', 'bricks' ), $extension ) ); ?>"><?php esc_html_e( 'Upload', 'bricks' ); ?></button>
					<button class="button remove<?php echo $font_id ? '' : ' hide'; ?>"><?php esc_html_e( 'Remove', 'bricks' ); ?></button>
				</li>
				<?php } ?>
			</ul>
		</div>

		<?php
		return ob_get_clean();
	}

	public function save_font_faces() {
		$post_id    = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$font_faces = isset( $_POST['font_faces'] ) ? json_decode( stripslashes( $_POST['font_faces'] ), true ) : false;

		if ( ! Capabilities::current_user_can_use_builder( $post_id ) ) {
			wp_send_json_error();
		}

		if ( count( $font_faces ) ) {
			$updated = update_post_meta( $post_id, BRICKS_DB_CUSTOM_FONT_FACES, $font_faces );
		} else {
			$updated = delete_post_meta( $post_id, BRICKS_DB_CUSTOM_FONT_FACES );
		}

		wp_send_json_success(
			[
				'post_id'    => $post_id,
				'font_faces' => $font_faces,
				'updated'    => $updated,
			]
		);
	}

	public function manage_columns( $columns ) {
		$columns = [
			'cb'           => '<input type="checkbox" />',
			'title'        => __( 'Font Family', 'bricks' ),
			'font_preview' => __( 'Font Preview', 'bricks' ),
		];

		$mime_types = self::get_custom_fonts_mime_types();

		foreach ( $mime_types as $extension => $label ) {
			$columns[ $extension ] = sprintf( esc_html__( '%s file', 'bricks' ), strtoupper( $extension ) );
		}

		return $columns;
	}

	public function render_columns( $column, $post_id ) {
		if ( $column === 'font_preview' ) {
			$font_variant_inline_css = self::generate_font_face_inline_css( $post_id );
			$font_family             = get_the_title( $post_id );
			$style                   = '';

			if ( $font_variant_inline_css ) {
				echo '<style>' . $font_variant_inline_css . '</style>';

				$style .= 'font-family: "' . $font_family . '";';
				$style .= 'font-size: 18px';
			}

			echo '<div class="pangram" style=\'' . $style . '\'>';

			if ( $style ) {
				esc_html_e( 'The quick brown fox jumps over the lazy dog.', 'bricks ' );
			} else {
				echo '-';
			}

			echo '</div>';
		}

		$extensions = array_keys( self::get_custom_fonts_mime_types() );
		$font_faces = get_post_meta( $post_id, BRICKS_DB_CUSTOM_FONT_FACES, true );

		if ( in_array( $column, $extensions ) && $font_faces ) {
			$has_font_file = false;

			foreach ( $font_faces as $font_variant => $font_face ) {
				if ( isset( $font_face[ $column ] ) ) {
					$has_font_file = true;
				}
			}

			echo $has_font_file ? '<i class="dashicons dashicons-yes-alt"></i>' : '<i class="dashicons dashicons-minus"></i>';
		}
	}

	public function post_row_actions( $actions, $post ) {
		// Remove 'Quick Edit'
		if ( $post->post_type === BRICKS_DB_CUSTOM_FONTS ) {
			// unset( $actions['inline hide-if-no-js'] );
			unset( $actions['view'] );
		}

		return $actions;
	}

	public function register_post_type() {
		$args = [
			'labels'              => [
				'name'               => esc_html__( 'Custom Fonts', 'bricks' ),
				'singular_name'      => esc_html__( 'Custom Font', 'bricks' ),
				'add_new'            => esc_html__( 'Add New', 'bricks' ),
				'add_new_item'       => esc_html__( 'Add New Custom Font', 'bricks' ),
				'edit_item'          => esc_html__( 'Edit Custom Font', 'bricks' ),
				'new_item'           => esc_html__( 'New Custom Font', 'bricks' ),
				'view_item'          => esc_html__( 'View Custom Font', 'bricks' ),
				'view_items'         => esc_html__( 'View Custom Fonts', 'bricks' ),
				'search_items'       => esc_html__( 'Search Custom Fonts', 'bricks' ),
				'not_found'          => esc_html__( 'No Custom Fonts found', 'bricks' ),
				'not_found_in_trash' => esc_html__( 'No Custom Font found in Trash', 'bricks' ),
				'all_items'          => esc_html__( 'All Custom Fonts', 'bricks' ),
				'menu_name'          => esc_html__( 'Custom Fonts', 'bricks' ),
			],
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'hierarchical'        => false,
			'rewrite'             => false,
			'supports'            => [ 'title' ],
		];

		// Custom Fonts are only accessible for user role with full Bricks access
		if ( ! Capabilities::current_user_has_full_access() ) {
			$args['capability_type'] = 'post';

			$args['capabilities'] = [
				'read_post'    => Capabilities::FULL_ACCESS,
				'edit_post'    => Capabilities::FULL_ACCESS,
				'delete_post'  => Capabilities::FULL_ACCESS,
				'create_posts' => Capabilities::FULL_ACCESS,
			];
		}

		register_post_type( BRICKS_DB_CUSTOM_FONTS, $args );
	}
}
