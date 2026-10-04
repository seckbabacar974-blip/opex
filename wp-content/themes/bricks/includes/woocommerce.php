<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce {
	public static $product_categories = [];
	public static $product_tags       = [];

	public function __construct() {
		if ( ! self::is_woocommerce_active() ) {

			// Make sure the WooCommerce templates are not loaded, in case CPT "product" is used
			add_filter( 'template_include', [ $this, 'no_woo_template_include' ], 1001 );

			return;
		}

		add_action( 'after_setup_theme', [ $this, 'add_theme_support' ] );

		add_action( 'init', [ $this, 'set_products_terms' ] );

		add_action( 'init', [ $this, 'init_elements' ] );

		add_action( 'init', [ $this, 'init_theme_styles' ], 9 );

		add_action( 'wp', [ $this, 'maybe_set_template_preview_content' ], 9 );

		add_filter( 'bricks/builder/supported_post_types', [ $this, 'bypass_builder_post_type_check' ], 10, 2 );

		// On the builder hook to set the panel elements first element category
		add_filter( 'bricks/builder/first_element_category', [ $this, 'set_first_element_category' ], 10, 3 );

		// Builder/Database: set the post id used to localize the builder data -> is_shop() page
		add_filter( 'bricks/builder/data_post_id', [ $this, 'maybe_set_post_id' ], 10, 1 );

		// Add Template Types to control options
		add_filter( 'bricks/setup/control_options', [ $this, 'add_template_types' ] );

		// Remove the template conditions for the Cart & Checkout template parts
		add_filter( 'builder/settings/template/controls_data', [ $this, 'remove_template_conditions' ], 9 );

		// During the active_templates search set proper content_type
		add_filter( 'bricks/database/content_type', [ $this, 'set_content_type' ], 10, 2 );

		// Remove default WooCommerce styles
		add_filter( 'woocommerce_enqueue_styles', '__return_false' );

		// Enqueue Bricks WooCommerce custom styles
		add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 10 );

		// Product archive hooks
		add_action( 'bricks/archive_product/before', [ $this, 'setup_query' ], 10, 2 );
		add_action( 'bricks/archive_product/after', [ $this, 'reset_query' ], 10, 2 );

		// Mini cart fragments
		add_filter( 'woocommerce_add_to_cart_fragments', [ $this, 'update_mini_cart' ], 10, 1 );

		// Breadcrumb separator
		add_filter( 'woocommerce_breadcrumb_defaults', [ $this, 'breadcrumb_separator' ] );
		add_filter( 'woocommerce_get_breadcrumb', [ $this, 'add_breadcrumbs_from_filters' ], 10, 2 );

		// Quantity input field: Add plus/minus buttons
		add_action( 'woocommerce_before_quantity_input_field', [ $this, 'before_quantity_input_field' ] );
		add_action( 'woocommerce_after_quantity_input_field', [ $this, 'after_quantity_input_field' ] );

		// Product tabs: Remove panel titles
		add_filter( 'woocommerce_product_description_heading', '__return_false' );
		add_filter( 'woocommerce_product_additional_information_heading', '__return_false' );
		add_filter( 'woocommerce_reviews_title', '__return_false' );

		// On Sale HTML
		add_filter( 'woocommerce_sale_flash', [ $this, 'badge_sale' ], 10, 3 );

		// Single product
		add_action( 'woocommerce_before_shop_loop_item_title', [ $this, 'badge_new' ], 9 );
		add_filter( 'woocommerce_product_review_comment_form_args', [ $this, 'product_review_comment_form_args' ] );

		// Query loop: using the query loop builder for products
		add_filter( 'bricks/posts/merge_query', [ $this, 'maybe_merge_query' ], 10, 2 );
		add_filter( 'bricks/posts/query_vars', [ $this, 'set_products_query_vars' ], 10, 3 );

		// Query: Add Woo Cart contents
		add_filter( 'bricks/setup/control_options', [ $this, 'add_control_options' ], 10, 1 );
		add_filter( 'bricks/query/run', [ $this, 'run_cart_query' ], 10, 2 );
		add_filter( 'bricks/query/loop_object', [ $this, 'set_loop_object' ], 10, 3 );
	}

	/**
	 * If WooCommerce is not used, make sure the single and archive Woo templates are not used
	 *
	 * @since 1.5.1
	 *
	 * @param string $template
	 * @return string
	 */
	public function no_woo_template_include( $template ) {
		if ( empty( $template ) ) {
			return $template;
		}

		if ( strpos( $template, '/bricks/archive-product.php' ) ) {
			return get_query_template( 'archive', [ 'archive.php' ] );
		}

		if ( strpos( $template, '/bricks/single-product.php' ) ) {
			return get_query_template( 'single', [ 'single.php' ] );
		}

		return $template;
	}

	/**
	 * Sale badge HTML
	 *
	 * Show text or percentage.
	 */
	public function badge_sale( $html, $post, $product ) {
		$badge_type = Database::get_setting( 'woocommerceBadgeSale', false );

		// Type: ''
		if ( ! $badge_type ) {
			return;
		}

		// Type: text
		elseif ( $badge_type === 'text' ) {
			return '<span class="badge onsale">' . esc_html__( 'Sale', 'bricks' ) . '</span>';
		}

		// Type: percentage
		if ( $product->is_type( 'variable' ) ) {
			$percentages = [];

			// Get all variation prices
			$prices = $product->get_variation_prices();

			foreach ( $prices['price'] as $key => $price ) {
				if ( $prices['regular_price'][ $key ] !== $price ) {
					$percentages[] = round( 100 - ( floatval( $prices['sale_price'][ $key ] ) / floatval( $prices['regular_price'][ $key ] ) * 100 ) );
				}
			}

			// Use highest discountvalue
			$percentage = max( $percentages ) . '%';
		} elseif ( $product->is_type( 'grouped' ) ) {
			$percentages = [];

			$children = $product->get_children();

			foreach ( $children as $child ) {
				$child_product = wc_get_product( $child );

				$regular_price = (float) $child_product->get_regular_price();
				$sale_price    = (float) $child_product->get_sale_price();

				if ( $sale_price != 0 || ! empty( $sale_price ) ) {
					$percentages[] = round( 100 - ( $sale_price / $regular_price * 100 ) );
				}
			}

			// Use highest value
			$percentage = max( $percentages ) . '%';
		} else {
			$regular_price = (float) $product->get_regular_price();
			$sale_price    = (float) $product->get_sale_price();

			if ( $sale_price != 0 || ! empty( $sale_price ) ) {
				$percentage = round( 100 - ( $sale_price / $regular_price * 100 ) ) . '%';
			} else {
				return $html;
			}
		}

		return '<span class="badge onsale">-' . $percentage . '</span>';
	}

	public static function badge_new() {
		global $product;

		$newness_in_days = Database::get_setting( 'woocommerceBadgeNew', false );

		if ( ! $newness_in_days ) {
			return;
		}

		$newness_timestamp = time() - ( 60 * 60 * 24 * $newness_in_days );
		$created           = strtotime( $product->get_date_created() );
		$is_new            = $newness_timestamp < $created; // Created less than {$newness_in_days} days ago

		if ( $is_new ) {
			echo '<span class="badge new">' . esc_html__( 'New', 'bricks' ) . '</span>';
		}
	}

	/**
	 * Product review submit button: Add 'button' class to apply Woo button styles
	 */
	public function product_review_comment_form_args( $comment_form ) {
		$comment_form['class_submit'] = 'button';

		return $comment_form;
	}

	/**
	 * WooCommerce support sets WC_Template_Loader::$theme_support = true
	 */
	public function add_theme_support() {
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-slider' );

		// Disable/enable product gallery zoom
		if ( Database::get_setting( 'woocommerceDisableProductGalleryZoom', false ) ) {
			remove_theme_support( 'wc-product-gallery-zoom' );
		} else {
			add_theme_support( 'wc-product-gallery-zoom' );
		}

		// Disable/enable product gallery lightbox
		if ( Database::get_setting( 'woocommerceDisableProductGalleryLightbox', false ) ) {
			remove_theme_support( 'wc-product-gallery-lightbox' );
		} else {
			add_theme_support( 'wc-product-gallery-lightbox' );
		}
	}

	/**
	 * Get products terms (categories, tags) for in-builder product query controls
	 */
	public function set_products_terms() {
		if ( bricks_is_builder() ) {
			self::$product_categories = self::get_products_terms( 'product_cat' );
			self::$product_tags       = self::get_products_terms( 'product_tag' );
		}
	}

	/**
	 * Get terms for a given product taxonomy
	 */
	public static function get_products_terms( $taxonomy = null ) {
		if ( empty( $taxonomy ) ) {
			return [];
		}

		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			]
		);

		$tags = [];

		foreach ( $terms as $term ) {
			$tags[ $term->term_id ] = $term->name;
		}

		return $tags;
	}

	/**
	 * Check if WooCommerce plugin is active
	 *
	 * @return boolean
	 */
	public static function is_woocommerce_active() {
		return class_exists( 'woocommerce' ) && ! Database::get_setting( 'woocommerceDisableBuilder', false );
	}

	/**
	 * Init WooCommerce theme styles
	 */
	public function init_theme_styles() {
		$file = BRICKS_PATH . 'includes/woocommerce/theme-styles.php';

		if ( is_readable( $file ) ) {
			require_once $file;

			new Woocommerce_Theme_Styles();
		}
	}

	/**
	 * Init WooCommerce elements
	 */
	public function init_elements() {
		// Load WooCommerce helpers
		$helpers_file = BRICKS_PATH . 'includes/woocommerce/helpers.php';

		if ( is_readable( $helpers_file ) ) {
			require_once $helpers_file;
		}

		$woo_elements = [
			'woocommerce-breadcrumbs',
			'woocommerce-mini-cart',

			'product-title',
			'product-gallery',
			'product-short-description',
			'product-price',
			'product-stock',
			'product-meta',
			'product-rating',
			'product-content',
			'product-add-to-cart',
			'product-related',
			'product-additional-information',
			'product-tabs',
			'product-upsells',

			'woocommerce-cart-collaterals',
			'woocommerce-cart-coupon',
			'woocommerce-cart-items',

			'woocommerce-checkout-customer-details',
			'woocommerce-checkout-order-review',
			'woocommerce-checkout-thankyou',
			'woocommerce-checkout-order-table',
			'woocommerce-checkout-order-payment',

			'woocommerce-products',
			'woocommerce-products-pagination',
			'woocommerce-products-orderby',
			'woocommerce-products-total-results',
			'woocommerce-products-filter',
			'woocommerce-products-archive-description',
		];

		foreach ( $woo_elements as $element_name ) {
			$woo_element_file = BRICKS_PATH . "includes/woocommerce/elements/$element_name.php";

			// Get the class name from the element name
			$class_name = str_replace( '-', '_', $element_name );
			$class_name = ucwords( $class_name, '_' );
			$class_name = "Bricks\\$class_name";

			if ( is_readable( $woo_element_file ) ) {
				Elements::register_element( $woo_element_file, $element_name, $class_name );
			}
		}
	}

	public function before_quantity_input_field() {
		echo '<span class="action minus">';
		echo '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="6" y1="12" x2="18" y2="12"></line></svg>';
		echo '</span>';
	}

	public function after_quantity_input_field() {
		echo '<span class="action plus">';
		echo '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="6" x2="12" y2="18"></line><line x1="6" y1="12" x2="18" y2="12"></line></svg>';
		echo '</span>';
	}

	public function breadcrumb_separator( $defaults ) {
		$defaults['delimiter'] = '<span>/</span>';

		return $defaults;
	}

	/**
	 * Add search breadbrumb in the product archive if using Bricks search filter
	 *
	 * @param array         $crumbs
	 * @param WC_Breadcrumb $crumbs_obj
	 * @return array
	 */
	public function add_breadcrumbs_from_filters( $crumbs, $crumbs_obj ) {

		if ( ! empty( $_GET['b_search'] ) && Woocommerce_Helpers::is_archive_product() ) {
			$crumbs[] = [
				sprintf( __( 'Search results for &ldquo;%s&rdquo;', 'woocommerce' ), wp_strip_all_tags( $_GET['b_search'] ) ),
				remove_query_arg( 'paged' )
			];
		}

		return $crumbs;
	}

	/**
	 * Bypass Builder post type check because page set to WooCommerce Shop fails
	 *
	 * @return boolean
	 */
	public function bypass_builder_post_type_check( $supported_post_types, $current_post_type ) {
		if ( in_array( 'page', $supported_post_types ) && ( is_post_type_archive( 'product' ) || is_page( wc_get_page_id( 'shop' ) ) ) ) {
			$supported_post_types[] = 'product';
		}

		return $supported_post_types;
	}

	/**
	 * Builder: Set single product template & populate content (if needed)
	 *
	 * @return void
	 */
	public function maybe_set_template_preview_content() {
		$post_id = get_the_ID();

		$template_type       = get_post_meta( $post_id, BRICKS_DB_TEMPLATE_TYPE, true );
		$template_preview_id = Helpers::get_template_setting( 'templatePreviewPostId', $post_id );

		if (
			strpos( $template_type, 'wc_' ) !== false ||
			wc_get_page_id( 'shop' ) == $template_preview_id ) {
			// Necessary to add 'woocommerce' to body_class for styling
			add_filter(
				'is_woocommerce',
				function() {
					return true;
				}
			);
		}

		// Remove 'woocommerce body class in builder panel
		if ( bricks_is_builder_main() ) {
			add_filter(
				'is_woocommerce',
				function() {
					return false;
				}
			);
		}

		// Form checkout template
		if (
			$template_type === 'wc_form_checkout' ||
			$template_type === 'wc_form_pay' ||
			$template_type === 'wc_cart'
		) {
			add_filter( 'body_class', [ $this, 'add_body_class' ], 9, 1 );
		}

		// Return: Not in builder nor template
		if ( ! bricks_is_builder() || get_post_type( $post_id ) !== BRICKS_DB_TEMPLATE_SLUG ) {
			return;
		}

		// Get the last product and save it as preview ID
		if ( $template_type === 'wc_product' ) {
			// Template has already a preview post ID: Leave
			$template_preview_post_id = Helpers::get_template_setting( 'templatePreviewPostId', $post_id );

			if ( $template_preview_post_id ) {
				return;
			}

			$products = wc_get_products(
				[
					'limit'   => 1,
					'orderby' => 'date',
					'order'   => 'DESC',
					'return'  => 'ids',
				]
			);

			if ( isset( $products[0] ) ) {
				Helpers::set_template_setting( $post_id, 'templatePreviewPostId', $products[0] );
				Helpers::set_template_setting( $post_id, 'templatePreviewType', 'single' );
				Helpers::set_template_setting( $post_id, 'templatePreviewAutoContent', 1 ); // This setting will be used to trigger a notification
			}
		}

		// TODO: Replace this logic by a generic template preview CPT Archive > CPT = products
		// elseif ( $template_type === 'wc_archive' ) {
		// $template_preview_type = Helpers::get_template_setting( 'templatePreviewType', $post_id );

		// if ( $template_preview_type ) {
		// return;
		// }

		// Helpers::set_template_setting( $post_id, 'templatePreviewType', 'archive-product' );
		// }
	}

	/**
	 * Builder: Add body classes to Woo templates
	 *
	 * @param array $classes
	 * @return void
	 */
	public function add_body_class( $classes ) {
		if ( get_post_type() !== BRICKS_DB_TEMPLATE_SLUG ) {
			return $classes;
		}

		if ( Templates::get_template_type() === 'wc_form_checkout' ) {
			$classes[] = 'woocommerce-checkout';
			$classes[] = 'woocommerce-page';
		} elseif ( Templates::get_template_type() === 'wc_form_pay' ) {
			$classes[] = 'woocommerce-checkout';
		} elseif ( Templates::get_template_type() === 'wc_cart' ) {
			$classes[] = 'woocommerce-cart';
			$classes[] = 'woocommerce-page';
		}

		return $classes;
	}

	/**
	 * On the builder, move up WooCommerce specific elements
	 *
	 * @since 1.2.1
	 *
	 * @param string  $category
	 * @param integer $post_id
	 * @param string  $post_type
	 * @return string
	 */
	public function set_first_element_category( $category, $post_id, $post_type ) {
		if ( BRICKS_DB_TEMPLATE_SLUG === $post_type ) {
			$template_type = get_post_meta( $post_id, BRICKS_DB_TEMPLATE_TYPE, true );

			if ( $template_type == 'wc_product' ) {
				return 'woocommerce_product';
			} elseif ( strpos( $template_type, 'wc_' ) !== false ) {
				return 'woocommerce';
			}
		} elseif ( is_post_type_archive( 'product' ) || $post_id == wc_get_page_id( 'shop' ) ) {
			return 'woocommerce';
		} elseif ( 'product' == $post_type ) {
			return 'woocommerce_product';
		}

		return $category;
	}

	/**
	 * Page marked as shop - is_shop() - has a global $post_id set to the first product (like is_home)
	 * On the builder or when setting the active templates we need to replace the active post id by the page id
	 *
	 * @param integer $post_id
	 * @return void
	 */
	public function maybe_set_post_id( $post_id ) {
		// If launching bricks builder on page defined as shop
		if ( is_shop() && BRICKS_DB_TEMPLATE_SLUG != get_post_type( $post_id ) ) {
			$page_id = wc_get_page_id( 'shop' );

			$post_id = ! empty( $page_id ) ? $page_id : $post_id;
		}

		return $post_id;
	}

	/**
	 * Remove WooCommerce scripts on non-WooCommerce pages
	 *
	 * @since 1.2.1
	 */
	public function wp_enqueue_scripts() {
		if ( bricks_is_builder_iframe() ) {
			// Required for product gallery & tabs
			wp_enqueue_script( 'wc-single-product' );
		}

		if ( ! bricks_is_builder_main() ) {
			wp_enqueue_script( 'bricks-woocommerce', BRICKS_URL_ASSETS . 'js/integrations/woocommerce.min.js', [ 'bricks-scripts' ], filemtime( BRICKS_PATH_ASSETS . 'js/integrations/woocommerce.min.js' ), true );
			wp_enqueue_style( 'bricks-woocommerce', BRICKS_URL_ASSETS . 'css/integrations/woocommerce.min.css', [ 'bricks-frontend' ], filemtime( BRICKS_PATH_ASSETS . 'css/integrations/woocommerce.min.css' ) );
		}

		if ( is_rtl() ) {
			wp_enqueue_style( 'bricks-woocommerce-rtl', BRICKS_URL_ASSETS . 'css/integrations/woocommerce-rtl.min.css', [ 'bricks-frontend' ], filemtime( BRICKS_PATH_ASSETS . 'css/integrations/woocommerce-rtl.min.css' ) );
		}
	}

	/**
	 * Before Bricks searchs for the right template, set the content_type if needed
	 *
	 * @param string  $content_type
	 * @param integer $post_id
	 * @return void
	 */
	public static function set_content_type( $content_type, $post_id ) {
		// These will only kick in if user has defaultTemplatesDisabled = false
		if ( is_product() ) {
			$content_type = 'wc_product';
		} elseif ( is_shop() ) {
			$content_type = 'content';
		} elseif ( Woocommerce_Helpers::is_archive_product() ) {
			$content_type = 'wc_archive';
		}

		return $content_type;
	}

	/**
	 * Add template types to control options
	 *
	 * @param array $control_options
	 * @return array
	 *
	 * @since 1.4
	 */
	public function add_template_types( $control_options ) {
		$template_types = $control_options['templateTypes'];

		// @since 1.3: Product archive & single product templates
		$template_types['wc_archive'] = 'WooCommerce - ' . esc_html__( 'Product archive', 'bricks' );
		$template_types['wc_product'] = 'WooCommerce - ' . esc_html__( 'Single product', 'bricks' );

		// @since 1.4: Cart & checkout templates
		$template_types['wc_cart']          = 'WooCommerce - ' . esc_html__( 'Cart', 'bricks' );
		$template_types['wc_cart_empty']    = 'WooCommerce - ' . esc_html__( 'Empty cart', 'bricks' );
		$template_types['wc_form_checkout'] = 'WooCommerce - ' . esc_html__( 'Checkout', 'bricks' );
		$template_types['wc_form_pay']      = 'WooCommerce - ' . esc_html__( 'Pay', 'bricks' );
		$template_types['wc_thankyou']      = 'WooCommerce - ' . esc_html__( 'Thank you', 'bricks' );
		$template_types['wc_order_receipt'] = 'WooCommerce - ' . esc_html__( 'Order receipt', 'bricks' );

		$control_options['templateTypes'] = $template_types;

		return $control_options;
	}

	/**
	 * Remove "Template Conditions" & "Populate Content" panel controls for WooCommerce Cart & Checkout template parts
	 *
	 * @param array $settings
	 * @return array
	 *
	 * @since 1.4
	 */
	public function remove_template_conditions( $settings ) {
		$excluded_templates = [
			'wc_cart',
			'wc_cart_empty',
			'wc_form_checkout',
			'wc_form_pay',
			'wc_thankyou',
			'wc_order_receipt',
		];

		if ( isset( $settings['controlGroups']['template-preview'] ) ) {
			$settings['controlGroups']['template-preview']['required'] = [ 'templateType', '!=', $excluded_templates, 'templateType' ];
		}

		if ( isset( $settings['controls']['templateConditionsInfo'] ) ) {
			$settings['controls']['templateConditionsInfo']['required'] = [ 'templateType', '!=', $excluded_templates, 'templateType' ];
		}

		if ( isset( $settings['controls']['templateConditions'] ) ) {
			$settings['controls']['templateConditions']['required'] = [ 'templateType', '!=', $excluded_templates, 'templateType' ];
		}

		$settings['controls'][] = [
			'group'    => 'template-conditions',
			'type'     => 'info',
			'content'  => esc_html__( 'This template type is automatically rendered on the correct page.', 'bricks' ),
			'required' => [ 'templateType', '=', $excluded_templates, 'templateType' ],
		];

		return $settings;
	}

	/**
	 * Get template data by template type
	 *
	 * For woocommerce templates inside Bricks theme.
	 *
	 * Return template data rendered via Bricks template shortcode.
	 *
	 * @see /includes/woocommerce/cart/cart.php (wc_cart), etc.
	 *
	 * @since 1.4
	 */
	public static function get_template_data_by_type( $type ) {
		// Do not check for Database::get_setting( 'defaultTemplatesDisabled' )
		$template_ids = Templates::get_templates_by_type( $type );

		// No template found
		if ( empty( $template_ids[0] ) ) {
			return;
		}

		return do_shortcode( '[bricks_template id="' . $template_ids[0] . '"]' );
	}

	/**
	 * Add Archive Product content type
	 *
	 * Note: Not in use
	 *
	 * @param array $types
	 * @return void
	 */
	public function add_content_types( $types ) {
		$types['archive-product'] = esc_html__( 'Archive (products)', 'bricks' );

		return $types;
	}

	/**
	 * Setup the products query loop in the products archive, including is_shop page (frontend only)
	 *
	 * @param array  $data Elements list
	 * @param string $post_id
	 * @return void
	 */
	public function setup_query( $data, $post_id ) {

		$query_element = Woocommerce_Helpers::get_products_element( $post_id, $data );

		// No query element to merge, proceed with regular WooCommerce loop
		if ( ! $query_element ) {
			wc_setup_loop();

			return;
		}

		// Force the post type to feed the Bricks Query class
		if ( empty( $query_element['settings']['query'] ) ) {
			$query_element['settings']['query'] = [
				'post_type'           => [ 'product' ],
				'ignore_sticky_posts' => 1
			];
		}

		// Query
		$query_object = new Query( $query_element );

		$query = $query_object->query_result;

		// Remove ordering query arguments which may have been added by 'get_catalog_ordering_args'
		WC()->query->remove_ordering_args();

		$columns = isset( $query_element['settings']['columns'] ) ? $query_element['settings']['columns'] : 4;

		wc_setup_loop(
			[
				'columns'      => $columns,
				'name'         => 'bricks-products',
				'is_shortcode' => true,
				'is_search'    => false,
				'is_paginated' => true,
				'total'        => (int) $query->found_posts,
				'total_pages'  => (int) $query->max_num_pages,
				'per_page'     => (int) $query->get( 'posts_per_page' ),
				'current_page' => (int) max( 1, $query->get( 'paged', 1 ) ),
			]
		);
	}

	public function reset_query( $sections, $post_id ) {
		wc_reset_loop();
	}

	/**
	 * Update the mini-cart fragments
	 *
	 * @param array $fragments
	 * @return void
	 */
	public function update_mini_cart( $fragments ) {
		if ( ! is_object( WC()->cart ) ) {
			return;
		}

		// Cart Count
		$count = WC()->cart->get_cart_contents_count();

		$fragments['span.cart-count'] = '<span class="cart-count ' . ( $count == 0 ? 'hide' : 'show' ) . '">' . $count . '</span>';

		// Cart Subtotal
		$subtotal = WC()->cart->get_cart_subtotal();

		if ( $subtotal ) {
			$fragments['span.cart-subtotal'] = '<span class="cart-subtotal">' . $subtotal . '</span>';
		}

		return $fragments;
	}

	/**
	 * Check if the query loop is on Woo products, and if yes, check if we should merge the main query
	 *
	 * @since 1.5
	 *
	 * @param boolean $merge
	 * @param string  $element_id
	 * @return boolean
	 */
	public function maybe_merge_query( $merge, $element_id ) {
		$query = Query::get_query_for_element_id( $element_id );

		if ( ! isset( $query->query_vars['post_type'] ) || is_array( $query->query_vars['post_type'] ) && ! in_array( 'product', $query->query_vars['post_type'] ) ) {
			return $merge;
		}

		return Woocommerce_Helpers::is_archive_product();
	}

	/**
	 * Add products query vars to the query loop
	 *
	 * @since 1.5
	 *
	 * @param array  $query_vars
	 * @param array  $settings
	 * @param string $element_id
	 * @return boolean
	 */
	public function set_products_query_vars( $query_vars, $settings, $element_id ) {
		$query = Query::get_query_for_element_id( $element_id );

		if ( ! $query || ! isset( $query->query_vars['post_type'] ) || is_array( $query->query_vars['post_type'] ) && ! in_array( 'product', $query->query_vars['post_type'] ) ) {
			return $query_vars;
		}

		$new_query_vars = $query_vars;

		$filter_args = Woocommerce_Helpers::filters_query_args( $settings );

		// Override the query settings by the filters (orderby, filters)
		foreach ( $filter_args as $key => $filter_value ) {
			if ( in_array( $key, [ 'meta_query', 'tax_query' ] ) && ! empty( $query_vars[ $key ] ) ) {
				continue;
			}

			$new_query_vars[ $key ] = $filter_value;
		}

		// STEP: Merge meta or/and tax query (if has conflicts)
		foreach ( [ 'meta_query', 'tax_query' ] as $type ) {
			if ( empty( $query_vars[ $type ] ) || empty( $filter_args[ $type ] ) ) {
				continue;
			}

			$relation_query_vars  = isset( $query_vars[ $type ]['relation'] ) ? $query_vars[ $type ]['relation'] : false;
			$relation_filter_args = isset( $filter_args[ $type ]['relation'] ) ? $filter_args[ $type ]['relation'] : false;

			// Both meta query sources have the relation key set and they are different
			if ( $relation_query_vars && $relation_filter_args && $relation_query_vars != $relation_filter_args ) {
				$new_query_vars[ $type ] = [
					'relation' => 'AND',
					0          => $query_vars[ $type ],
					1          => $filter_args[ $type ]
				];
			}

			// Relations are equal or not set
			else {
				$relation = $relation_query_vars ? $relation_query_vars : ( $relation_filter_args ? $relation_filter_args : false );

				unset( $query_vars[ $type ]['relation'] );
				unset( $filter_args[ $type ]['relation'] );

				$new_query_vars[ $type ] = array_merge( $query_vars[ $type ], $filter_args[ $type ] );

				if ( $relation ) {
					$new_query_vars[ $type ]['relation'] = $relation;
				}
			}
		}

		return $new_query_vars;
	}

	/**
	 * Adds the cart contents query to the Query Loop builder
	 *
	 * @param array $control_options
	 * @return array
	 */
	public function add_control_options( $control_options ) {
		$control_options['queryTypes']['wooCart'] = esc_html__( 'Cart contents', 'bricks' );

		return $control_options;
	}

	/**
	 * Returns the cart contents query
	 *
	 * @param array $results
	 * @param Query $query
	 * @return array
	 */
	public function run_cart_query( $results, $query ) {
		if ( $query->object_type !== 'wooCart' ) {
			return $results;
		}

		return WC()->cart->get_cart();
	}

	/**
	 * Sets the loop object (to WP_Post) in each query loop iteration
	 *
	 * @param array  $loop_object
	 * @param string $loop_key
	 * @param Query  $query
	 * @return array
	 */
	public function set_loop_object( $loop_object, $loop_key, $query ) {
		if ( $query->object_type !== 'wooCart' ) {
			return $loop_object;
		}

		// @see woocommerce/templates/cart/cart.php
		$_product   = apply_filters( 'woocommerce_cart_item_product', $loop_object['data'], $loop_object, $loop_key );
		$product_id = apply_filters( 'woocommerce_cart_item_product_id', $loop_object['product_id'], $loop_object, $loop_key );

		global $post;

		$post = get_post( $product_id );

		setup_postdata( $post );

		return $_product;
	}

}
