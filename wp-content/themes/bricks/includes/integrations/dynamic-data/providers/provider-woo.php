<?php
namespace Bricks\Integrations\Dynamic_Data\Providers;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Provider_Woo extends Base {
	public static function load_me() {
		return class_exists( 'woocommerce' );
	}

	public function register_tags() {
		$tags = $this->get_tags_config();

		foreach ( $tags as $key => $tag ) {
			$name = 'woo_' . $key;

			$this->tags[ $name ] = [
				'name'     => '{' . $name . '}',
				'label'    => $tag['label'],
				'group'    => $tag['group'],
				'provider' => $this->name
			];

			if ( ! empty( $tag['render'] ) ) {
				$this->tags[ $name ]['render'] = $tag['render'];
			}
		}
	}

	public function get_tags_config() {
		$tags = [
			// Product
			'product_price'       => [
				'label' => esc_html__( 'Product price', 'bricks' ),
				'group' => esc_html__( 'Product', 'bricks' ),
			],
			'product_excerpt'     => [
				'label' => esc_html__( 'Product short description', 'bricks' ),
				'group' => esc_html__( 'Product', 'bricks' ),
			],
			'product_stock'       => [
				'label' => esc_html__( 'Product stock', 'bricks' ),
				'group' => esc_html__( 'Product', 'bricks' ),
			],
			'product_sku'         => [
				'label' => esc_html__( 'Product SKU', 'bricks' ),
				'group' => esc_html__( 'Product', 'bricks' ),
			],
			'product_rating'      => [
				'label' => esc_html__( 'Product rating', 'bricks' ),
				'group' => esc_html__( 'Product', 'bricks' ),
			],
			'product_on_sale'     => [
				'label' => esc_html__( 'Product on sale', 'bricks' ),
				'group' => esc_html__( 'Product', 'bricks' ),
			],
			'add_to_cart'         => [
				'label' => esc_html__( 'Add to cart', 'bricks' ),
				'group' => esc_html__( 'Product', 'bricks' ),
			],
			'product_cat_image'   => [
				'label' => esc_html__( 'Product category image', 'bricks' ),
				'group' => 'WooCommerce',
			],

			// Checkout Order
			'order_id'            => [
				'label' => esc_html__( 'Order id', 'bricks' ),
				'group' => 'WooCommerce',
			],
			'order_number'        => [
				'label' => esc_html__( 'Order number', 'bricks' ),
				'group' => 'WooCommerce',
			],
			'order_date'          => [
				'label' => esc_html__( 'Order date', 'bricks' ),
				'group' => 'WooCommerce',
			],
			'order_total'         => [
				'label' => esc_html__( 'Order total', 'bricks' ),
				'group' => 'WooCommerce',
			],
			'order_payment_title' => [
				'label' => esc_html__( 'Order payment method', 'bricks' ),
				'group' => 'WooCommerce',
			],
			'order_email'         => [
				'label' => esc_html__( 'Order email', 'bricks' ),
				'group' => 'WooCommerce',
			],
		];

		return $tags;
	}

	/**
	 * Main function to render the tag value for WordPress provider
	 *
	 * @param [type] $tag
	 * @param [type] $post
	 * @param [type] $args
	 * @param [type] $context
	 * @return void
	 */
	public function get_tag_value( $tag, $post, $args, $context ) {
		$post_id = isset( $post->ID ) ? $post->ID : '';

		$product = $post_id ? wc_get_product( $post_id ) : false;

		// STEP: Check for filter args
		$filters = $this->get_filters_from_args( $args );

		// STEP: Get the value
		$value = '';

		$render = isset( $this->tags[ $tag ]['render'] ) ? $this->tags[ $tag ]['render'] : str_replace( 'woo_', '', $tag );

		switch ( $render ) {

			case 'product_price':
				$value = $product ? $product->get_price_html() : '';
				break;

			case 'product_excerpt':
				$value = \Bricks\Helpers::get_the_excerpt( $post, ! empty( $filters['num_words'] ) ? $filters['num_words'] : 55 );

				$value = apply_filters( 'woocommerce_short_description', $value );
				break;

			case 'product_stock':
				$value = $product ? wc_get_stock_html( $product ) : '';
				break;

			case 'product_sku':
				$value = $product && wc_product_sku_enabled() && $product->get_sku() ? $product->get_sku() : '';

				// Wrap with class "sku" so that the Woo fragments mechanism updates the SKU for the variable products
				$value = "<span class=\"sku\">{$value}</span>";
				break;

			case 'product_rating':
				if ( $product && wc_review_ratings_enabled() ) {
					// $rating_count = $product->get_rating_count();
					// $review_count = $product->get_review_count();
					$average = $product->get_average_rating();

					$value = wc_get_rating_html( $average );
				}
				break;

			case 'product_on_sale':
				$value = $product && $product->is_on_sale() ? apply_filters( 'woocommerce_sale_flash', '<span class="badge onsale">' . esc_html__( 'Sale!', 'bricks' ) . '</span>', $post, $product ) : '';
				break;

			case 'add_to_cart':
				$value = $this->get_add_to_cart_value( $product, $filters, $context );
				break;

			case 'product_cat_image':
				$filters['object_type'] = 'media';
				$filters['image']       = 'true';

				// Loop
				if ( \Bricks\Query::is_looping() && \Bricks\Query::get_loop_object_type() == 'term' ) {
					$term_id = \Bricks\Query::get_loop_object_id();
				}

				// Template preview
				elseif ( BRICKS_DB_TEMPLATE_SLUG === get_post_type( $post_id ) ) {
					$template_preview_type = \Bricks\Helpers::get_template_setting( 'templatePreviewType', $post_id );

					if ( 'archive-term' === $template_preview_type ) {
						$template_preview_term          = \Bricks\Helpers::get_template_setting( 'templatePreviewTerm', $post_id );
						$template_preview_term_id_parts = ! empty( $template_preview_term ) ? explode( '::', $template_preview_term ) : '';

						$term_id = isset( $template_preview_term_id_parts[1] ) ? $template_preview_term_id_parts[1] : '';
					}
				}

				// Product Cat archive
				elseif ( is_tax( 'product_cat' ) ) {
					$queried_object = get_queried_object();
					$term_id        = isset( $queried_object->term_id ) ? $queried_object->term_id : '';
				}

				// Single product
				elseif ( is_singular( 'product' ) ) {
					$terms   = wp_get_post_terms( $post_id, 'product_cat' );
					$term_id = isset( $terms[0]->term_id ) ? $terms[0]->term_id : 0;
				}

				$value = ! empty( $term_id ) ? get_term_meta( $term_id, 'thumbnail_id', true ) : '';
				break;

			// Checkout order
			case 'order_id':
				$order = $this->get_order();
				$value = $order ? $order->get_id() : '';
				break;

			case 'order_number':
				$order = $this->get_order();
				$value = $order ? $order->get_order_number() : '';
				break;

			case 'order_date':
				$filters['object_type'] = 'date';

				$order = $this->get_order();
				$value = $order ? wc_format_datetime( $order->get_date_created(), 'U' ) : '';
				break;

			case 'order_total':
				$order = $this->get_order();
				$value = $order ? $order->get_formatted_order_total() : '';
				break;

			case 'order_payment_title':
				$order = $this->get_order();
				$value = $order ? $order->get_payment_method_title() : '';
				break;

			case 'order_email':
				$order = $this->get_order();
				$value = $order ? $order->get_billing_email() : '';
				break;
		}

		// STEP: Apply context (text, link, image, media)
		$value = $this->format_value_for_context( $value, $tag, $post_id, $filters, $context );

		return $value;
	}

	public function get_order() {
		$order_id  = 0;
		$order     = false;
		$order_key = false;

		// Order pay
		if ( ! empty( get_query_var( 'order-pay' ) ) ) {

			$order_id  = absint( get_query_var( 'order-pay' ) );
			$order_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';

		}

		// Order received
		elseif ( ! empty( get_query_var( 'order-received' ) ) ) {

			$order_id = absint( get_query_var( 'order-received' ) );

			$order_id  = apply_filters( 'woocommerce_thankyou_order_id', $order_id );
			$order_key = apply_filters( 'woocommerce_thankyou_order_key', empty( $_GET['key'] ) ? '' : wc_clean( wp_unslash( $_GET['key'] ) ) );

		}

		if ( $order_id > 0 ) {
			$order = wc_get_order( $order_id );
			if ( ! $order || ! hash_equals( $order->get_order_key(), $order_key ) ) {
				$order = false;
			}
		}

		return $order;
	}

	/**
	 * Get the "Add to cart" button html
	 *
	 * @param WP_Product $product
	 * @param array      $filters
	 * @return void
	 */
	public function get_add_to_cart_value( $product, $filters, $context ) {

		if ( ! $product ) {
			return '';
		}

		if ( $context == 'link' ) {
			return $product->add_to_cart_url();
		}

		$button_args = [];

		// @see woocommerce_template_loop_add_to_cart()
		$defaults = [
			'quantity'   => 1,
			'class'      => implode(
				' ',
				array_filter(
					[
						'button',
						'product_type_' . $product->get_type(),
						$product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
						$product->supports( 'ajax_add_to_cart' ) && $product->is_purchasable() && $product->is_in_stock() ? 'ajax_add_to_cart' : '',
					]
				)
			),
			'attributes' => [
				'data-product_id'  => $product->get_id(),
				'data-product_sku' => $product->get_sku(),
				'aria-label'       => $product->add_to_cart_description(),
				'rel'              => 'nofollow',
			],
		];

		$button_args = apply_filters( 'woocommerce_loop_add_to_cart_args', wp_parse_args( $button_args, $defaults ), $product );

		if ( isset( $button_args['attributes']['aria-label'] ) ) {
			$button_args['attributes']['aria-label'] = wp_strip_all_tags( $button_args['attributes']['aria-label'] );
		}

		return apply_filters(
			'woocommerce_loop_add_to_cart_link',
			sprintf(
				'<a href="%s" data-quantity="%s" class="%s" %s>%s</a>',
				esc_url( $product->add_to_cart_url() ),
				esc_attr( isset( $button_args['quantity'] ) ? $button_args['quantity'] : 1 ),
				esc_attr( isset( $button_args['class'] ) ? $button_args['class'] : 'button' ),
				isset( $button_args['attributes'] ) ? wc_implode_html_attributes( $button_args['attributes'] ) : '',
				esc_html( $product->add_to_cart_text() )
			),
			$product,
			$button_args
		);
	}
}
