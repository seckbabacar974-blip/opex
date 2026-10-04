<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Product_Gallery extends Element {
	public $category = 'woocommerce_product';
	public $name     = 'product-gallery';
	public $icon     = 'ti-gallery';
	public $scripts  = [ 'bricksWooProductGallery' ];

	public function enqueue_scripts() {
		wp_enqueue_script( 'wc-single-product' );
		wp_enqueue_script( 'flexslider' );

		if ( bricks_is_builder_iframe() ) {
			wp_enqueue_script( 'zoom' );
		} elseif ( ! Database::get_setting( 'woocommerceDisableProductGalleryZoom', false ) ) {
			wp_enqueue_script( 'zoom' );
		}

		if ( ! Database::get_setting( 'woocommerceDisableProductGalleryLightbox', false ) ) {
			// woocommerce_photoswipe(); // Prevents display in builder
			wp_enqueue_script( 'bricks-photoswipe' );
			wp_enqueue_style( 'bricks-photoswipe' );
		}
	}

	public function get_label() {
		return esc_html__( 'Product gallery', 'bricks' );
	}

	public function set_controls() {
		$this->controls['_width']['rerender']    = true;
		$this->controls['_widthMax']['rerender'] = true;

		$this->controls['columns'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Columns', 'bricks' ),
			'type'        => 'number',
			'css'         => [
				[
					'selector' => '.flex-control-thumbs',
					'property' => 'grid-template-columns',
					'value'    => 'repeat(%s, 1fr)', // NOTE: Undocumented (@since 1.3)
				],
			],
			'placeholder' => 4,
			'rerender'    => true,
		];

		$this->controls['gap'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Gap', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[

					'selector' => '.flex-control-thumbs',
					'property' => 'gap',
				],
				[
					'selector' => '.woocommerce-product-gallery',
					'property' => 'gap',
				],
			],
			'placeholder' => '30px',
		];
	}

	public function render() {
		$settings = $this->settings;

		global $product;

		$product = wc_get_product( $this->post_id );

		if ( empty( $product ) ) {
			return $this->render_element_placeholder(
				[
					'title'       => esc_html__( 'For better preview select content to show.', 'bricks' ),
					'description' => esc_html__( 'Go to: Settings > Template Settings > Populate Content', 'bricks' ),
				]
			);
		}

		if ( $this->lazy_load() ) {
			add_filter( 'woocommerce_gallery_image_html_attachment_image_params', [ $this, 'add_image_class_prevent_lazy_loading' ], 10, 4 );
		}

		echo "<div {$this->render_attributes( '_root' )}>";

		wc_get_template( 'single-product/product-image.php' );

		echo '</div>';

		if ( $this->lazy_load() ) {
			remove_filter( 'woocommerce_gallery_image_html_attachment_image_params', [ $this, 'add_image_class_prevent_lazy_loading' ], 10, 4 );
		}
	}

	public function add_image_class_prevent_lazy_loading( $attr, $attachment_id, $image_size, $main_image ) {
		// NOTE: Undocumented. Used internally in the Frontend::set_image_attributes()
		$attr['_brx_disable_lazy_loading'] = 1;

		return $attr;
	}
}
