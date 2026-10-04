<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woocommerce_Helpers {
	/**
	 * Product query controls (products, related products, upsells)
	 *
	 * @param array $args Arguments to merge (e.g. control 'group', etc.)
	 */
	public static function get_product_query_controls( $args = false ) {
		$query_controls = [
			'posts_per_page' => [
				'tab'   => 'content',
				'label' => esc_html__( 'Products per page', 'bricks' ),
				'type'  => 'number',
				'min'   => -1,
				'step'  => 1,
			],

			'orderby'        => [
				'tab'         => 'content',
				'label'       => esc_html__( 'Order by', 'bricks' ),
				'type'        => 'select',
				'options'     => [
					'price'      => esc_html__( 'Price', 'bricks' ),
					'popularity' => esc_html__( 'Popularity', 'bricks' ),
					'rating'     => esc_html__( 'Rating', 'bricks' ),
					'name'       => esc_html__( 'Name', 'bricks' ),
					'rand'       => esc_html__( 'Random', 'bricks' ),
					'date'       => esc_html__( 'Published date', 'bricks' ),
					'modified'   => esc_html__( 'Modified date', 'bricks' ),
					'menu_order' => esc_html__( 'Menu order', 'bricks' ),
					'id'         => esc_html__( 'Product ID', 'bricks' ),
				],
				'inline'      => true,
				'placeholder' => esc_html__( 'Date', 'bricks' ),
			],

			'order'          => [
				'tab'         => 'content',
				'label'       => esc_html__( 'Order', 'bricks' ),
				'type'        => 'select',
				'options'     => [
					'ASC'  => esc_html__( 'Ascending', 'bricks' ),
					'DESC' => esc_html__( 'Descending', 'bricks' ),
				],
				'inline'      => true,
				'placeholder' => esc_html__( 'Descending', 'bricks' ),
			],

			'productType'    => [
				'tab'         => 'content',
				'label'       => esc_html__( 'Product type', 'bricks' ),
				'type'        => 'select',
				'options'     => wc_get_product_types(),
				'multiple'    => true,
				'placeholder' => esc_html__( 'Select product type', 'bricks' ),
			],

			'include'        => [
				'tab'         => 'content',
				'label'       => esc_html__( 'Include', 'bricks' ),
				'type'        => 'select',
				'optionsAjax' => [
					'action'   => 'bricks_get_posts',
					'postType' => 'product',
				],
				'multiple'    => true,
				'searchable'  => true,
				'placeholder' => esc_html__( 'Select products', 'bricks' ),
			],

			'exclude'        => [
				'tab'         => 'content',
				'label'       => esc_html__( 'Exclude', 'bricks' ),
				'type'        => 'select',
				'optionsAjax' => [
					'action'   => 'bricks_get_posts',
					'postType' => 'product',
				],
				'multiple'    => true,
				'searchable'  => true,
				'placeholder' => esc_html__( 'Select products', 'bricks' ),
			],

			'categories'     => [
				'tab'      => 'content',
				'label'    => esc_html__( 'Product categories', 'bricks' ),
				'type'     => 'select',
				'options'  => Woocommerce::$product_categories,
				'multiple' => true,
			],

			'tags'           => [
				'tab'      => 'content',
				'label'    => esc_html__( 'Product tags', 'bricks' ),
				'type'     => 'select',
				'options'  => Woocommerce::$product_tags,
				'multiple' => true,
			],

			'onSale'         => [
				'tab'   => 'content',
				'label' => esc_html__( 'On sale', 'bricks' ),
				'type'  => 'checkbox',
			],

			'featured'       => [
				'tab'   => 'content',
				'label' => esc_html__( 'Featured', 'bricks' ),
				'type'  => 'checkbox',
			],

			'hideOutOfStock' => [
				'tab'   => 'content',
				'label' => esc_html__( 'Hide out of stock', 'bricks' ),
				'type'  => 'checkbox',
			],
		];

		if ( is_array( $args ) ) {
			foreach ( $query_controls as $key => $control ) {
				$query_controls[ $key ] = array_merge( $query_controls[ $key ], $args );
			}
		}

		return $query_controls;
	}

	/**
	 * Default order by control options
	 */
	public static function get_default_orderby_control_options() {
		// NOTE: Undocumented
		$options = apply_filters(
			'bricks/woocommerce/products_orderby_options',
			[
				'menu_order' => esc_html__( 'Default sorting', 'bricks' ),
				'popularity' => esc_html__( 'Sort by popularity', 'bricks' ),
				'rating'     => esc_html__( 'Sort by average rating', 'bricks' ),
				'date'       => esc_html__( 'Sort by latest', 'bricks' ),
				'price'      => esc_html__( 'Sort by price: low to high', 'bricks' ),
				'price-desc' => esc_html__( 'Sort by price: high to low', 'bricks' ),
			]
		);

		return $options;
	}

	public static function get_filters_list( $flat = true ) {
		$options['other'] = [
			'reset'  => [
				'name'  => 'reset',
				'group' => 'other',
				'label' => esc_html__( 'Reset filters', 'bricks' ),
			],
			'price'  => [
				'name'  => 'price',
				'group' => 'other',
				'label' => esc_html__( 'Product price', 'bricks' ),
			],
			'rating' => [
				'name'  => 'rating',
				'group' => 'other',
				'label' => esc_html__( 'Product rating', 'bricks' ),
			],
			'stock'  => [
				'name'  => 'stock',
				'group' => 'other',
				'label' => esc_html__( 'Product stock', 'bricks' ),
			],
			'search' => [
				'name'  => 'search',
				'group' => 'other',
				'label' => esc_html__( 'Product search', 'bricks' ),
			],
		];

		// Taxonomies
		$taxonomies = get_object_taxonomies( 'product', 'objects' );

		foreach ( $taxonomies as $name => $taxonomy ) {
			$group = strpos( $name, 'pa_' ) === 0 ? 'attribute' : 'taxonomy';

			$options[ $group ][ $name ] = [
				'name'  => $name,
				'group' => $group,
				'label' => $taxonomy->label,
				'query' => 'taxonomy',
			];
		}

		if ( $flat ) {
			$options_flat = [];

			foreach ( $options as $group => $list ) {
				$options_flat = array_merge( $options_flat, $list );
			}

			return $options_flat;
		}

		return $options;
	}

	/**
	 * Is product archive page
	 *
	 * @return boolean
	 */
	public static function is_archive_product() {
		$is_default_product_archive = is_tax( 'product_cat' ) || is_tax( 'product_tag' ) || is_post_type_archive( 'product' );

		if ( $is_default_product_archive ) {
			return $is_default_product_archive;
		}

		// Check for product archive of a custom taxonomy (since 1.5)
		$queried_object = get_queried_object();

		if ( is_a( $queried_object, 'WP_Term' ) ) {
			$taxonomy = get_taxonomy( $queried_object->taxonomy );

			return isset( $taxonomy->object_type ) && in_array( 'product', $taxonomy->object_type );
		}

		return false;
	}

	/**
	 * Calculate the filters query args based on the URL parameters and element settings
	 *
	 * WooCommerce query
	 *
	 * https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query
	 * https://docs.woocommerce.com/wc-apidocs/class-WC_Product.html
	 *
	 * @since 1.5
	 *
	 * @param array $settings
	 * @return array
	 */
	public static function filters_query_args( $settings ) {
		// STEP: Convert Query Loop settings into Products settings (e.g. posts_per_page, orderby, order ...)
		if ( isset( $settings['query'] ) ) {
			$settings = wp_parse_args( $settings, $settings['query'] );
		}

		// STEP: Calculate the product query args
		$product_args = [];
		$tax_query    = [];

		// Check if loading a Bricks template, set preview
		if ( get_post_type() === BRICKS_DB_TEMPLATE_SLUG ) {
			$post_id      = get_the_ID();
			$preview_type = Helpers::get_template_setting( 'templatePreviewType', $post_id );

			if ( $preview_type == 'archive-term' ) {
				$preview_term = Helpers::get_template_setting( 'templatePreviewTerm', $post_id );

				if ( ! empty( $preview_term ) ) {
					$preview_term     = explode( '::', $preview_term );
					$preview_taxonomy = isset( $preview_term[0] ) ? $preview_term[0] : '';
					$preview_term_id  = isset( $preview_term[1] ) ? intval( $preview_term[1] ) : '';

					if ( $preview_taxonomy && $preview_term_id ) {
						$tax_query[ $preview_taxonomy ] = [
							'taxonomy' => $preview_taxonomy,
							'field'    => 'term_id',
							'terms'    => $preview_term_id,
						];
					}
				}
			}
			// Note: We don't need to run the check against the CPT archive preview, the products query will always return products
			// elseif ( $preview_type == 'archive-cpt' ) {
			// $template_preview_post_type = Helpers::get_template_setting( 'templatePreviewPostType', $post_id );
			// }
		}

		// Order & Orderby
		if ( isset( $_GET['orderby'] ) ) {
			$product_args['orderby'] = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );

			if ( $product_args['orderby'] == 'price' ) {
				$product_args['orderby'] = 'price-asc';
			}
		} else {
			$product_args['orderby'] = ! empty( $settings['orderby'] ) ? $settings['orderby'] : 'date';
		}

		$orderby_value           = explode( '-', $product_args['orderby'] ); // e.g. orderby=price-desc
		$product_args['orderby'] = esc_attr( $orderby_value[0] );
		$product_args['order']   = ! empty( $orderby_value[1] ) ? $orderby_value[1] : strtoupper( isset( $settings['order'] ) ? $settings['order'] : 'DESC' );

		// @see: WC_Shortcode_Products::parse_query_args() [woocommerce/includes/shortcodes/class-wc-shortcode-products.php]
		$ordering_args           = WC()->query->get_catalog_ordering_args( $product_args['orderby'], $product_args['order'] );
		$product_args['orderby'] = $ordering_args['orderby'];
		$product_args['order']   = $ordering_args['order'];

		// Meta query
		if ( $ordering_args['meta_key'] ) {
			$product_args['meta_key'] = $ordering_args['meta_key'];
		}

		// Appends meta queries from filter 'woocommerce_product_query_meta_query'
		$product_args['meta_query'] = WC()->query->get_meta_query();

		$product_visibility_terms = wc_get_product_visibility_term_ids();

		// Exclude Out of Stock
		if ( isset( $settings['hideOutOfStock'] ) ) {
			$product_visibility_not_in[] = $product_visibility_terms['outofstock'];
		}

		if ( ! empty( $product_visibility_not_in ) ) {
			$tax_query['product_visibility_not_in'] = [
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $product_visibility_not_in,
				'operator' => 'NOT IN',
			];
		}

		// Filter: Rating
		if ( isset( $_GET['b_rating'] ) ) {
			$rating_filter = array_filter( array_map( 'absint', (array) wp_unslash( $_GET['b_rating'] ) ) );

			// Include products rated with equal or higher ratings
			if ( count( $rating_filter ) == 1 ) {
				$rating_filter = range( $rating_filter[0], 5 );
			}

			$rating_terms = [];

			foreach ( range( 1, 5 ) as $key ) {
				if ( in_array( $key, $rating_filter, true ) && isset( $product_visibility_terms[ 'rated-' . $key ] ) ) {
					$rating_terms[] = $product_visibility_terms[ 'rated-' . $key ];
				}
			}

			if ( ! empty( $rating_terms ) ) {
				$tax_query['product_visibility_rating'] = [
					'taxonomy'      => 'product_visibility',
					'field'         => 'term_taxonomy_id',
					'terms'         => $rating_terms,
					'operator'      => 'IN',
					'rating_filter' => true,
				];
			}
		}

		// Filter: Stock
		if ( isset( $_GET['b_stock'] ) ) {
			$filter = (array) wp_unslash( $_GET['b_stock'] );

			// Default stock query instock, outofstock or onbackorder
			$stock_defaults = wc_get_product_stock_status_options();
			$default_filter = array_intersect( $filter, array_keys( $stock_defaults ) );

			if ( ! empty( $default_filter ) ) {
				$product_args['meta_query'][] = [
					'key'     => '_stock_status',
					'value'   => $default_filter,
					'compare' => 'IN',
				];

			} elseif ( in_array( 'lowstock', $filter ) ) {
				$low_amount                             = absint( max( get_option( 'woocommerce_notify_low_stock_amount' ), 2 ) );
				$product_args['meta_query']['relation'] = 'AND';
				$product_args['meta_query'][]           = [
					'key'     => '_stock',
					'type'    => 'numeric',
					'value'   => $low_amount,
					'compare' => '<=',
				];
				$product_args['meta_query'][]           = [
					'key'     => '_stock_status',
					'value'   => 'instock',
					'compare' => '=',
				];
			}
		}

		// Filter: Search
		if ( ! empty( $_GET['b_search'] ) || ! empty( $_GET['s'] ) ) {
			$product_args['s'] = ! empty( $_GET['b_search'] ) ? sanitize_text_field( $_GET['b_search'] ) : sanitize_text_field( $_GET['s'] );
		}

		// Filter: Product type
		if ( ! empty( $settings['productType'] ) ) {
			$tax_query['product_type'] = [
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => $settings['productType'],
			];
		}

		// Filter: Product category
		if ( ! empty( $settings['categories'] ) ) {
			$tax_query['product_cat'] = [
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => $settings['categories'],
			];
		}

		// Filter: Product tag
		if ( ! empty( $settings['tags'] ) ) {
			$tax_query['product_tag'] = [
				'taxonomy' => 'product_tag',
				'field'    => 'term_id',
				'terms'    => $settings['tags'],
			];
		}

		// Include products
		if ( ! empty( $settings['include'] ) ) {
			$product_args['post__in'] = $settings['include'];
		}
		// Query Loop (since 1.5)
		elseif ( ! empty( $settings['post__in'] ) ) {
			$product_args['post__in'] = $settings['post__in'];
		}

		// Exclude products
		if ( ! empty( $settings['exclude'] ) ) {
			$product_args['post__not_in'] = $settings['exclude'];
		}
		// Query Loop (since 1.5)
		elseif ( ! empty( $settings['post__not_in'] ) ) {
			$product_args['post__not_in'] = $settings['post__not_in'];
		}

		// Show only products on sale
		if ( isset( $settings['onSale'] ) ) {
			$post_in                  = isset( $product_args['post__in'] ) ? $product_args['post__in'] : [];
			$product_args['post__in'] = array_merge( $post_in, wc_get_product_ids_on_sale() );
		}

		// Show only products featured
		if ( isset( $settings['featured'] ) ) {
			$visibility_term_ids = wc_get_product_visibility_term_ids();

			$tax_query['product_visibility'] = [
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => [ $visibility_term_ids['featured'] ],
			];
		}

		// Posts per page
		$product_args['posts_per_page'] = isset( $settings['posts_per_page'] ) ? intval( $settings['posts_per_page'] ) : get_option( 'posts_per_page' );

		// Products Pagination
		if ( ! empty( $_GET['product-page'] ) ) {
			$product_args['paged'] = $_GET['product-page'];
		}

		// Merge tax_query with the filters
		$filters = self::get_filters_list();

		foreach ( $filters as $name => $filter ) {
			if ( ! isset( $_GET[ 'b_' . $name ] ) || ! isset( $filter['query'] ) || 'taxonomy' != $filter['query'] ) {
				continue;
			}

			$value = wp_unslash( $_GET[ 'b_' . $name ] );

			if ( ! empty( $value ) ) {

				$terms = (array) $value;

				if ( array_key_exists( $name, $tax_query ) ) {
					// $terms = array_merge( $terms, (array) $tax_query[ $name ]['terms'] );
					// $terms = array_unique( $terms );
					// @since 1.3.7 Fix CU #1t1tga4
					$terms = array_intersect( $terms, (array) $tax_query[ $name ]['terms'] );
				}

				$tax_query[ $name ] = [
					'taxonomy' => $name,
					'field'    => 'term_id',
					'terms'    => $terms,
				];
			}
		}

		// Add tax_query to the main query args
		if ( ! empty( $tax_query ) ) {
			$tax_query = array_values( $tax_query );

			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'AND';
			}
		}

		$product_args['tax_query'] = WC()->query->get_tax_query( $tax_query, false );

		unset( $tax_query );

		// Filter by price
		$product_args = self::set_price_query_args( $product_args );

		return $product_args;
	}

	/**
	 * Set query args for price filter
	 *
	 * @param array $args
	 * @return array
	 */
	public static function set_price_query_args( $args ) {
		if ( ! isset( $_GET['max_price'] ) && ! isset( $_GET['min_price'] ) ) {
			return $args;
		}

		$value_min = isset( $_GET['min_price'] ) ? floatval( wp_unslash( $_GET['min_price'] ) ) : 0;
		$value_max = isset( $_GET['max_price'] ) ? floatval( wp_unslash( $_GET['max_price'] ) ) : PHP_INT_MAX;

		if ( wc_tax_enabled() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) && ! wc_prices_include_tax() ) {
			$tax_class = apply_filters( 'woocommerce_price_filter_widget_tax_class', '' );
			$tax_rates = WC_Tax::get_rates( $tax_class );

			if ( $tax_rates ) {
				$value_min -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $value_min, $tax_rates ) );
				$value_max -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $value_max, $tax_rates ) );
			}
		}

		$args['meta_query'][] = [
			'key'     => '_price',
			'value'   => [ $value_min, $value_max ],
			'compare' => 'BETWEEN',
			'type'    => 'NUMERIC',
		];

		return $args;
	}

	/**
	 * Gets the first element from a flat list that contains a products query (Products element or Query Loop builder set to products)
	 *
	 * @since 1.5
	 *
	 * @param string $post_id
	 * @return array|boolean
	 */
	public static function get_products_element( $post_id = 0, $data = [] ) {
		$data = ! empty( $data ) ? $data : get_post_meta( $post_id, BRICKS_DB_PAGE_CONTENT, true );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return false;
		}

		foreach ( $data as $element ) {
			if (
				$element['name'] === 'woocommerce-products' ||
				isset( $element['settings']['hasLoop'] ) && ! empty( $element['settings']['query']['post_type'] ) && in_array( 'product', $element['settings']['query']['post_type'] )
			 ) {
				return $element;
			}
		}

		return false;
	}

	/**
	 * Get the products query based on a Products element present in the content of a page
	 *
	 * @param string $post_id
	 * @return WP_Query|boolean false if products element not found
	 */
	public static function get_products_element_query( $post_id ) {
		$cache_key = "get_products_element_query_$post_id";

		$query = wp_cache_get( $cache_key, 'bricks' );

		if ( $query !== false ) {
			return $query;
		}

		$query_element = self::get_products_element( $post_id );

		if ( ! $query_element ) {
			return false;
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

		wp_cache_set( $cache_key, $query, 'bricks', MINUTE_IN_SECONDS );

		return $query;
	}

	/**
	 * Helper function to set the cart variables for better builder preview
	 *
	 * @return void
	 */
	public static function maybe_init_cart_context() {
		if ( is_cart() ) {
			return;
		}

		wc_maybe_define_constant( 'WOOCOMMERCE_CART', true );

		// Check cart items are valid
		do_action( 'woocommerce_check_cart_items' );

		// Calculate totals
		if ( WC()->cart ) {
			WC()->cart->calculate_totals();
		}
	}

	/**
	 * Maybe add products to the cart if cart is empty for better builder preview
	 *
	 * @since 1.5
	 *
	 * @return void
	 */
	public static function maybe_populate_cart_contents() {
		if ( WC()->cart->is_empty() && ( bricks_is_builder() || bricks_is_builder_call() ) ) {
			$products = wc_get_products( [ 'limit' => 5 ] );

			if ( $products ) {
				foreach ( $products as $product ) {
					if ( $product->is_purchasable() ) {
						WC()->cart->add_to_cart( $product->get_id() );
					}
				}
			}
		}
	}

	/**
	 * Maybe load the cart - render using WP REST API
	 *
	 * @since 1.5
	 */
	public static function maybe_load_cart() {
		if ( bricks_is_builder_call() && is_null( WC()->cart ) ) {
			wc_load_cart();
		}
	}

}
