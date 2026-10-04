<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Frontend {
	/**
	 * List of elements requested for rendering (format: ID => element)
	 */
	public static $elements = [];
	public static $area     = 'content';

	/**
	 * PhotoSwipe script loaded: Add PhotoSwipe HTML to DOM (frontend only)
	 *
	 * @since 1.3.4
	 */
	public function add_photoswipe_html() {
		if ( wp_script_is( 'bricks-photoswipe', 'enqueued' ) ) {
			echo self::photoswipe_html();
		}
	}

	public function __construct() {
		add_action( 'wp_head', [ $this, 'add_seo_meta_tags' ], 1 );
		add_filter( 'wp_get_attachment_image_attributes', [ $this, 'set_image_attributes' ], 10, 3 );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_inline_css' ], 11 );

		add_action( 'bricks_after_site_wrapper', [ $this, 'one_page_navigation_wrapper' ] );
		add_action( 'bricks_after_site_wrapper', [ $this, 'add_photoswipe_html' ] );

		// Load custom header body script (for analytics) only on the frontend
		add_action( 'wp_head', [ $this, 'add_header_scripts' ] );
		add_action( 'wp_head', [ $this, 'add_open_graph_meta_tags' ], 99999 );
		add_action( 'bricks_body', [ $this, 'add_body_header_scripts' ] );

		// Change the priority to 21 to load the custom scripts after the default Bricks scripts in the footer (@since 1.5)
		// @see core: add_action( 'wp_footer', 'wp_print_footer_scripts', 20 );
		add_action( 'wp_footer', [ $this, 'add_body_footer_scripts' ], 21 );

		add_action( 'template_redirect', [ $this, 'template_redirect' ] );

		add_action( 'bricks_body', [ $this, 'add_skip_link' ] );

		add_action( 'render_header', [ $this, 'render_header' ] );
		add_action( 'render_footer', [ $this, 'render_footer' ] );
	}

	/**
	 * Add header scripts
	 *
	 * Do not add template JS (we only want to provide content)
	 *
	 * @since 1.0
	 */
	public function add_header_scripts() {
		$header_scripts = '';

		// Global settings scripts
		if ( ! empty( Database::$global_settings['customScriptsHeader'] ) ) {
			$header_scripts .= stripslashes_deep( Database::$global_settings['customScriptsHeader'] ) . PHP_EOL;
		}

		// Page settings header scripts (@since 1.4)
		$header_scripts .= Assets::get_page_settings_scripts( 'customScriptsHeader' );

		echo $header_scripts;
	}

	/**
	 * Add meta description, keywords and robots
	 */
	public function add_seo_meta_tags() {
		// NOTE: Undocumented
		$disable_seo = apply_filters( 'bricks/frontend/disable_seo', ! empty( Database::$global_settings['disableSeo'] ) );

		if ( $disable_seo ) {
			return;
		}

		$template_id = Database::$active_templates['content'];

		$template_settings = get_post_meta( $template_id, BRICKS_DB_PAGE_SETTINGS, true );

		$post_id = is_home() ? get_option( 'page_for_posts' ) : get_the_ID();

		if ( $template_id !== $post_id ) {
			$page_settings = get_post_meta( $post_id, BRICKS_DB_PAGE_SETTINGS, true );
		}

		$seo_tags = [
			'metaDescription' => 'description',
			'metaKeywords'    => 'keywords',
			'metaRobots'      => 'robots',
		];

		foreach ( $seo_tags as $meta_key => $name ) {
			// Page settings preceeds Template settings
			$meta_value = ! empty( $page_settings[ $meta_key ] ) ? $page_settings[ $meta_key ] : ( ! empty( $template_settings[ $meta_key ] ) ? $template_settings[ $meta_key ] : false );

			if ( empty( $meta_value ) ) {
				continue;
			}

			if ( $meta_key == 'metaRobots' ) {
				$meta_value = join( ', ', $meta_value );
			} else {
				$meta_value = Integrations\Dynamic_Data\Providers::render_content( $meta_value, $post_id );
			}

			echo '<meta name="' . $name . '" content="' . esc_attr( $meta_value ) . '">';
		}
	}

	/**
	 * Add Facebook Open Graph Meta Data
	 *
	 * https://ogp.me
	 *
	 * @since 1.0
	 */
	public function add_open_graph_meta_tags() {
		// NOTE: Undocumented
		$disable_og = apply_filters( 'bricks/frontend/disable_opengraph', ! empty( Database::$global_settings['disableOpenGraph'] ) );

		if ( $disable_og ) {
			return;
		}

		// STEP: Calculate OG settings
		$template_id = Database::$active_templates['content'];

		$template_settings = get_post_meta( $template_id, BRICKS_DB_PAGE_SETTINGS, true );

		$post_id = is_home() ? get_option( 'page_for_posts' ) : get_the_ID();
		$post_id = empty( $post_id ) ? null : $post_id; // Fix PHP notice on Error page

		if ( $template_id !== $post_id && ! empty( $post_id ) ) {
			$page_settings = get_post_meta( $post_id, BRICKS_DB_PAGE_SETTINGS, true );
		}

		$settings = [];

		$og_tags = [
			'sharingTitle',
			'sharingDescription',
			'sharingImage',
		];

		foreach ( $og_tags as $meta_key ) {
			// Page settings preceeds Template settings
			$settings[ $meta_key ] = ! empty( $page_settings[ $meta_key ] ) ? $page_settings[ $meta_key ] : ( ! empty( $template_settings[ $meta_key ] ) ? $template_settings[ $meta_key ] : false );
		}

		// STEP: Render tags
		$open_graph_meta_tags = [ '<!-- Facebook Open Graph (by Bricks) -->' ];

		$facebook_app_id = isset( Database::$global_settings['facebookAppId'] ) && ! empty( Database::$global_settings['facebookAppId'] ) ? Database::$global_settings['facebookAppId'] : false;

		if ( $facebook_app_id ) {
			$open_graph_meta_tags[] = '<meta property="fb:app_id" content="' . $facebook_app_id . '" />';
		}

		$open_graph_meta_tags[] = '<meta property="og:url" content="' . get_permalink() . '" />';

		// Site Name
		$open_graph_meta_tags[] = '<meta property="og:site_name" content="' . get_bloginfo( 'name' ) . '" />';

		// Title
		if ( ! empty( $settings['sharingTitle'] ) ) {
			$sharing_title = Integrations\Dynamic_Data\Providers::render_content( $settings['sharingTitle'], $post_id );
		} else {
			$sharing_title = get_the_title( $post_id );
		}

		$open_graph_meta_tags[] = '<meta property="og:title" content="' . esc_attr( trim( $sharing_title ) ) . '" />';

		// Description
		if ( ! empty( $settings['sharingDescription'] ) ) {
			$sharing_description = Integrations\Dynamic_Data\Providers::render_content( $settings['sharingDescription'], $post_id );
		} else {
			$sharing_description = $post_id ? get_the_excerpt( $post_id ) : '';
		}

		if ( $sharing_description ) {
			$open_graph_meta_tags[] = '<meta property="og:description" content="' . esc_attr( trim( $sharing_description ) ) . '" />';
		}

		// Image
		$sharing_image     = ! empty( $settings['sharingImage'] ) ? $settings['sharingImage'] : false;
		$sharing_image_url = ! empty( $sharing_image['url'] ) ? $sharing_image['url'] : false;

		if ( $sharing_image ) {
			if ( ! empty( $sharing_image['useDynamicData'] ) ) {
				$images = Integrations\Dynamic_Data\Providers::render_tag( $sharing_image['useDynamicData'], $post_id, 'image' );

				if ( ! empty( $images[0] ) ) {
					$size              = ! empty( $sharing_image['size'] ) ? $sharing_image['size'] : BRICKS_DEFAULT_IMAGE_SIZE;
					$sharing_image_url = is_numeric( $images[0] ) ? wp_get_attachment_image_url( $images[0], $size ) : $images[0];
				}
			} else {
				$sharing_image_url = $sharing_image['url'];
			}
		} elseif ( has_post_thumbnail() ) {
			$sharing_image_url = get_the_post_thumbnail_url( get_the_ID(), 'large' );
		}

		if ( $sharing_image_url ) {
			$open_graph_meta_tags[] = '<meta property="og:image" content="' . esc_url( $sharing_image_url ) . '" />';
		}

		// Type
		if ( is_home() ) {
			$sharing_type = 'blog';
		} elseif ( get_post_type() === 'post' ) {
			$sharing_type = 'article';
		} else {
			$sharing_type = 'website';
		}

		$open_graph_meta_tags[] = '<meta property="og:type" content="' . $sharing_type . '" />';

		echo "\n" . join( "\n", $open_graph_meta_tags ) . "\n";
	}

	/**
	 * Add body header scripts
	 *
	 * NOTE: Do not add template JS (we only want to provide content)
	 *
	 * @since 1.0
	 */
	public function add_body_header_scripts() {
		$body_header_scripts = '';

		// Global settings scripts
		if ( isset( Database::$global_settings['customScriptsBodyHeader'] ) && ! empty( Database::$global_settings['customScriptsBodyHeader'] ) ) {
			$body_header_scripts .= stripslashes_deep( Database::$global_settings['customScriptsBodyHeader'] ) . PHP_EOL;
		}

		// Page settings scripts (@since 1.4)
		$body_header_scripts .= Assets::get_page_settings_scripts( 'customScriptsBodyHeader' );

		// if ( isset( Database::$page_settings['customScriptsBodyHeader'] ) && ! empty( Database::$page_settings['customScriptsBodyHeader'] ) ) {
		// $body_header_scripts .= stripslashes_deep( Database::$page_settings['customScriptsBodyHeader'] ) . PHP_EOL;
		// }

		echo $body_header_scripts;
	}

	/**
	 * Add body footer scripts
	 *
	 * NOTE: Do not add template JS (only provide content)
	 *
	 * @since 1.0
	 */
	public function add_body_footer_scripts() {
		$body_footer_scripts = '';

		// Global settings scripts
		if ( isset( Database::$global_settings['customScriptsBodyFooter'] ) && ! empty( Database::$global_settings['customScriptsBodyFooter'] ) ) {
			$body_footer_scripts .= stripslashes_deep( Database::$global_settings['customScriptsBodyFooter'] ) . PHP_EOL;
		}

		// Page settings scripts (@since 1.4)
		$body_footer_scripts .= Assets::get_page_settings_scripts( 'customScriptsBodyFooter' );

		// if ( isset( Database::$page_settings['customScriptsBodyFooter'] ) && ! empty( Database::$page_settings['customScriptsBodyFooter'] ) ) {
		// $body_footer_scripts .= stripslashes_deep( Database::$page_settings['customScriptsBodyFooter'] ) . PHP_EOL;
		// }

		echo $body_footer_scripts;
	}

	/**
	 * Enqueue styles and scripts
	 */
	public function enqueue_scripts() {
		if ( is_admin_bar_showing() && Capabilities::current_user_has_full_access() ) {
			// Load admin.min.css to add styles to the quick edit links
			wp_enqueue_style( 'bricks-admin', BRICKS_URL_ASSETS . 'css/admin.min.css', [], filemtime( BRICKS_PATH_ASSETS . 'css/admin.min.css' ) );
		}

		// No Bricks content, but WP content: Load default content styles (post header & content)
		if ( ! Helpers::get_bricks_data( get_the_ID(), 'content' ) ) {
			if ( is_search() || get_the_content() ) {
				wp_enqueue_style( 'bricks-default-content', BRICKS_URL_ASSETS . 'css/frontend/content-default.min.css', [], filemtime( BRICKS_PATH_ASSETS . 'css/frontend/content-default.min.css' ) );
			}
		}

		wp_localize_script(
			'bricks-scripts',
			'bricksData',
			[
				'debug'                   => isset( $_GET['debug'] ),
				'locale'                  => get_locale(),
				'ajaxUrl'                 => admin_url( 'admin-ajax.php' ),
				'restApiUrl'              => Api::get_rest_api_url(),
				'nonce'                   => wp_create_nonce( 'bricks-nonce' ),
				'wpRestNonce'             => wp_create_nonce( 'wp_rest' ),
				'postId'                  => get_the_ID(),
				'recaptchaIds'            => [],
				'animatedTypingInstances' => [], // To destroy and then re-init TypedJS instances
				'videoInstances'          => [], // To destroy and then re-init Plyr instances
				'splideInstances'         => [], // Necessary to destroy and then reinit SplideJS instances
				'swiperInstances'         => [], // To destroy and then re-init SwiperJS instances
				'infiniteScrollQueries'   => [], // To hold the query data for infinite scroll
				'mapStyles'               => Setup::get_map_styles(),
				'facebookAppId'           => isset( Database::$global_settings['facebookAppId'] ) ? Database::$global_settings['facebookAppId'] : false,
				'headerPosition'          => Database::$header_position,
				'offsetLazyLoad'          => ! empty( Database::$global_settings['offsetLazyLoad'] ) ? Database::$global_settings['offsetLazyLoad'] : 300,
			]
		);
	}

	public function enqueue_inline_css() {
		// Dummy style to load after woocommerce.min.css
		wp_register_style( 'bricks-frontend-inline', false );
		wp_enqueue_style( 'bricks-frontend-inline' );

		// CSS loading method: Inline (default)
		if ( Database::get_setting( 'cssLoading' ) !== 'file' ) {
			wp_add_inline_style( 'bricks-frontend-inline', Assets::generate_inline_css() );
		}

		// // CSS loading method: External files
		else {
			// Global classes need to be loaded inline
			wp_add_inline_style( 'bricks-frontend-inline', Assets::$inline_css['global_classes'] );
		}
	}

	/**
	 * Get element content wrapper
	 *
	 * @param array $content_fields Element content wrapper for carousel, posts.
	 */
	public static function get_content_wrapper( $settings, $fields, $post ) {
		$output = '';

		foreach ( $fields as $index => $field ) {
			if ( ! empty( $field['dynamicData'] ) ) {
				$content = Integrations\Dynamic_Data\Providers::render_content( $field['dynamicData'], $post->ID );

				$content = do_shortcode( $content );

				if ( $content == '' ) {
					continue;
				}

				$tag      = ! empty( $field['tag'] ) ? esc_attr( $field['tag'] ) : 'div';
				$field_id = isset( $field['id'] ) ? $field['id'] : $index;

				$output .= "<{$tag} class=\"dynamic\" data-field-id=\"{$field_id}\">{$content}</{$tag}>";
			}
		}

		return $output;
	}

	/**
	 * Render element recursively
	 *
	 * @param array $element
	 */
	public static function render_element( $element ) {
		$output = '';

		// Check: Get global element settings
		$global_settings = Helpers::get_global_element_settings( $element );

		if ( is_array( $global_settings ) ) {
			$element['settings'] = $global_settings;
		}

		// Init element class (e.g.: new Bricks\Element_Alert( $element ))
		$element_class_name = isset( Elements::$elements[ $element['name'] ]['class'] ) ? Elements::$elements[ $element['name'] ]['class'] : $element['name'];

		if ( class_exists( $element_class_name ) ) {
			$element['themeStyleSettings'] = Theme_Styles::$active_settings;

			$element_instance = new $element_class_name( $element );
			$element_instance->load();

			// Enqueue element styles/scripts & render element
			ob_start();
			$element_instance->init();
			return ob_get_clean();
		}

			// Element doesn't exist
		if ( Capabilities::current_user_can_use_builder() ) {
			return sprintf( '<div class="bricks-element-placeholder no-php-class">%s: ' . $element_class_name . '</div>', esc_html__( 'PHP class does not exist', 'bricks' ) );
		}
	}

	/**
	 * Render element 'children' (= nestable element)
	 *
	 * @param array $element
	 *
	 * @since 1.5
	 */
	public static function render_children( $element_instance ) {
		$element  = $element_instance->element;
		$children = ! empty( $element['children'] ) && is_array( $element['children'] ) ? $element['children'] : [];
		$output   = '';

		foreach ( $children as $child_id ) {
			$child = ! empty( self::$elements[ $child_id ] ) ? self::$elements[ $child_id ] : false;

			if ( $child ) {
				$output .= self::render_element( $child ); // Recursive
			}
		}

		// FRONTEND: Return children HTML
		if ( $element_instance->is_frontend ) {
			return $output;
		}

		// BUILDER: Replace children placeholder node with Vue components (in BricksElementPHP.vue)
		return '<div class="brx-nestable-children-placeholder"></div>';
	}

	/**
	 * Return rendered elements (header/content/footer)
	 *
	 * @param array   $elements. Array of Bricks elements.
	 * @param integer $post_id.  Current post ID. @deprecated 1.5
	 * @param string  $area      header/content/footer.
	 *
	 * @since 1.2
	 */
	public static function render_data( $elements = [], $area = 'content' ) {
		if ( ! is_array( $elements ) ) {
			return;
		}

		if ( ! count( $elements ) ) {
			return;
		}

		self::$elements = [];
		self::$area     = $area;

		// Prepare flat list of elements for recursive calls
		foreach ( $elements as $element ) {
			self::$elements[ $element['id'] ] = $element;
		}

		$content = '';

		$element_index = 0;

		foreach ( $elements as $element ) {
			if ( ! empty( $element['parent'] ) ) {
				continue;
			}

			$content .= self::render_element( $element );

			$element_index++;
		}

		// We need to check here for the looping in case we are looping a template element
		$post_id = Query::is_looping() && Query::get_query_object_type() == 'post' ? get_the_ID() : Database::$page_data['preview_or_post_id'];

		$post = get_post( $post_id );

		// NOTE: Undocumented. Filter Bricks content.
		$content = apply_filters( 'bricks/frontend/render_data', $content, $post );

		self::$elements = [];

		return $content;
	}

	/**
	 * One Page Navigation Wrapper
	 */
	public function one_page_navigation_wrapper() {
		if ( isset( Database::$page_settings['onePageNavigation'] ) ) {
			echo '<ul id="bricks-one-page-navigation"></ul>';
		}
	}

	/**
	 * Lazy load via img data attribute
	 *
	 * https://developer.wordpress.org/reference/hooks/wp_get_attachment_image_attributes/
	 *
	 * @param array        $attr Image attributes.
	 * @param object       $attachment WP_POST object of image.
	 * @param string|array $size Requested image size.
	 *
	 * @return array
	 */
	function set_image_attributes( $attr, $attachment, $size ) {
		// Disable lazy load for in-builder AJAX or REST API calls (ensures assets are always rendered properly)
		// REST_REQUEST constant discussion: https://github.com/WP-API/WP-API/issues/926
		if ( bricks_is_builder_call() ) {
			return $attr;
		}

		// Disable images lazy loading in the Product Gallery
		if ( isset( $attr['_brx_disable_lazy_loading'] ) ) {
			unset( $attr['_brx_disable_lazy_loading'] );

			return $attr;
		}

		// Check: Lazy load disabled
		if ( isset( Database::$global_settings['disableLazyLoad'] ) ) {
			return $attr;
		}

		$attr['class']    = $attr['class'] . ' bricks-lazy-hidden';
		$attr['data-src'] = $attr['src'];

		// Lazy load placeholder: URL-encoded SVG with image dimensions
		$attr['data-type'] = gettype( $size );

		if ( gettype( $size ) === 'string' ) {
			$image_src    = wp_get_attachment_image_src( $attachment->ID, $size );
			$image_width  = $image_src[1];
			$image_height = $image_src[2];
		} else {
			$image_width  = $size[0];
			$image_height = $size[1];
		}

		// Set SVG placeholder to preserve image aspect ratio to prevent browser content reflow when lazy loading the image
		// Encode spaces and use singlequotes instead of double quotes to avoid W3 "space" validator error (@since 1.5.1)
		$attr['src'] = "data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%20$image_width%20$image_height'%3E%3C/svg%3E";

		// Add data-sizes attribute for lazy load to avoid "sizes" W3 validator error (@since 1.5.1)
		if ( isset( $attr['sizes'] ) ) {
			$attr['data-sizes'] = $attr['sizes'];
			$attr['sizes']      = '';
			unset( $attr['sizes'] );
		}

		if ( isset( $attr['srcset'] ) ) {
			$attr['data-srcset'] = $attr['srcset'];
			$attr['srcset']      = '';
			unset( $attr['srcset'] );
		}

		return $attr;
	}

	/**
	 * Template frontend view: Permanently redirect users without Bricks editing permission to homepage
	 *
	 * Exclude template pages in search engine results.
	 *
	 * Overwrite via 'publicTemplates' setting
	 *
	 * @since 1.0
	 */
	public function template_redirect() {
		if ( is_singular( BRICKS_DB_TEMPLATE_SLUG ) && ! Capabilities::current_user_can_use_builder() && ! isset( Database::$global_settings['publicTemplates'] ) ) {
			wp_safe_redirect( site_url(), 301 );
			die;
		}
	}

	public function add_skip_link() {
		if ( Database::get_setting( 'disableSkipLinks', false ) ) {
			return;
		}

		$template_footer_id = Database::$active_templates['footer'];
		?>
		<a class="skip-link" href="#brx-content" aria-label="<?php esc_html_e( 'Skip to main content', 'bricks' ); ?>"><?php esc_html_e( 'Skip to main content', 'bricks' ); ?></a>

		<?php if ( ! empty( $template_footer_id ) ) { ?>
			<a class="skip-link" href="#brx-footer" aria-label="<?php esc_html_e( 'Skip to footer', 'bricks' ); ?>"><?php esc_html_e( 'Skip to footer', 'bricks' ); ?></a>
			<?php
		}
	}

	/**
	 * Add Photoswipe lightbox HTML before closing body tag
	 */
	public static function photoswipe_html() {
		ob_start();
		?>
		<div class="pswp" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="pswp__bg"></div>
		<div class="pswp__scroll-wrap">
			<div class="pswp__container">
				<div class="pswp__item"></div>
				<div class="pswp__item"></div>
				<div class="pswp__item"></div>
			</div>
			<div class="pswp__ui pswp__ui--hidden">
				<div class="pswp__top-bar">
					<div class="pswp__counter"></div>
					<button class="pswp__button pswp__button--close" title="Close (Esc)"></button>
					<button class="pswp__button pswp__button--share" title="Share"></button>
					<button class="pswp__button pswp__button--fs" title="Toggle fullscreen"></button>
					<button class="pswp__button pswp__button--zoom" title="Zoom in/out"></button>
					<div class="pswp__preloader">
						<div class="pswp__preloader__icn">
							<div class="pswp__preloader__cut">
								<div class="pswp__preloader__donut"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap">
					<div class="pswp__share-tooltip"></div>
				</div>
				<button class="pswp__button pswp__button--arrow--left" title="Previous (arrow left)"></button>
				<button class="pswp__button pswp__button--arrow--right" title="Next (arrow right)"></button>
				<div class="pswp__caption">
					<div class="pswp__caption__center"></div>
				</div>
			</div>
		</div>
		</div>
		<?php
		$html = ob_get_clean();

		return trim( apply_filters( 'bricks/photoswipe_html', $html ) );
	}

	/**
	 * Render header
	 *
	 * Bricks data exists & header is not disabled on this page.
	 *
	 * @since 1.3.2
	 */
	public function render_header() {
		// Check: Header disabled
		if ( isset( Database::$page_settings['headerDisabled'] ) ) {
			return;
		}

		$header_data = Database::get_template_data( 'header' );

		// Return: No header data exists
		if ( ! is_array( $header_data ) ) {
			return;
		}

		$settings = Helpers::get_template_settings( Database::$active_templates['header'] );
		$classes  = [];

		// Sticky header (top, not left or right)
		if ( ! isset( $settings['headerPosition'] ) && isset( $settings['headerSticky'] ) ) {
			$classes[] = 'sticky';

			if ( isset( $settings['headerStickyOnScroll'] ) ) {
				$classes[] = 'on-scroll';
			}
		}

		$attributes = [
			'id' => 'brx-header',
		];

		if ( count( $classes ) ) {
			$attributes['class'] = $classes;
		}

		if ( ! empty( $settings['headerStickySlideUpAfter'] ) ) {
			$attributes['data-slide-up-after'] = $settings['headerStickySlideUpAfter'];
		}

		// https://academy.bricksbuilder.io/article/filter-bricks-header-attributes/ (@since 1.5)
		$attributes = apply_filters( 'bricks/header/attributes', $attributes );

		$attributes = Helpers::stringify_html_attributes( $attributes );

		$header_html = "<header {$attributes}>" . self::render_data( $header_data, 'header' ) . '</header>';

		// NOTE: Undocumented
		echo apply_filters( 'bricks/render_header', $header_html );
	}

	/**
	 * Render Bricks content + surrounding 'main' tag
	 *
	 * For pages rendered with Bricks
	 *
	 * To allow customizing the 'main' tag attributes
	 *
	 * @since 1.5
	 */
	public static function render_content( $bricks_data = [], $attributes = [], $html_after_begin = '', $html_before_end = '', $tag = 'main' ) {
		// Merge custom attributes with default attributes ('id')
		$attributes = array_merge(
			[
				'id' => 'brx-content',
			],
			$attributes
		);

		// https://academy.bricksbuilder.io/article/filter-bricks-content-attributes/ (@since 1.5)
		$attributes = apply_filters( 'bricks/content/attributes', $attributes );

		$attributes = Helpers::stringify_html_attributes( $attributes );

		echo "<{$tag} {$attributes}>";

		if ( $html_after_begin ) {
			echo $html_after_begin;
		}

		if ( is_array( $bricks_data ) && count( $bricks_data ) ) {
			echo self::render_data( $bricks_data );
		}

		if ( $html_before_end ) {
			echo $html_before_end;
		}

		echo "</{$tag}>";
	}

	/**
	 * Render footer
	 *
	 * To follow already available 'render_header' function syntax
	 *
	 * @since 1.5
	 */
	public function render_footer() {
		$footer_data = Database::get_template_data( 'footer' );

		if ( ! is_array( $footer_data ) ) {
			return;
		}

		// https://academy.bricksbuilder.io/article/filter-bricks-footer-attributes/ (@since 1.5)
		$attributes = apply_filters(
			'bricks/footer/attributes',
			[
				'id' => 'brx-footer',
			]
		);

		$attributes = Helpers::stringify_html_attributes( $attributes );

		$footer_html = "<footer {$attributes}>" . self::render_data( $footer_data, 'footer' ) . '</footer>';

		// NOTE: Undocumented
		echo apply_filters( 'bricks/render_footer', $footer_html );
	}
}
