<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Product_Stock extends Element {
	public $category = 'woocommerce_product';
	public $name     = 'product-stock';
	public $icon     = 'ti-package';

	public function get_label() {
		return esc_html__( 'Product stock', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['inStock'] = [
			'title' => esc_html__( 'In Stock', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['lowStock'] = [
			'title' => esc_html__( 'Low Stock / On backorder', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['outOfStock'] = [
			'title' => esc_html__( 'Out of Stock', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		// In Stock

		$this->controls['inStockText'] = [
			'tab'            => 'content',
			'group'          => 'inStock',
			'type'           => 'text',
			'hasDynamicData' => 'text',
			'placeholder'    => esc_html__( 'Custom text', 'bricks' ),
		];

		$this->controls['inStockTypography'] = [
			'tab'   => 'content',
			'group' => 'inStock',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.in-stock',
				],
			],
		];

		$this->controls['inStockBackgroundColor'] = [
			'tab'   => 'style',
			'group' => 'inStock',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.in-stock',
				]
			],
		];

		// Low Stock

		$this->controls['lowStockText'] = [
			'tab'            => 'content',
			'group'          => 'lowStock',
			'type'           => 'text',
			'hasDynamicData' => 'text',
			'placeholder'    => esc_html__( 'Custom text', 'bricks' ),
		];

		$this->controls['lowStockTypography'] = [
			'tab'   => 'content',
			'group' => 'lowStock',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.low-stock',
				],
			],
		];

		$this->controls['lowStockBackgroundColor'] = [
			'tab'   => 'style',
			'group' => 'lowStock',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.low-stock',
				]
			],
		];

		// Out of Stock

		$this->controls['outOfStockText'] = [
			'tab'            => 'content',
			'group'          => 'outOfStock',
			'type'           => 'text',
			'hasDynamicData' => 'text',
			'placeholder'    => esc_html__( 'Custom text', 'bricks' ),
		];

		$this->controls['outOfStockTypography'] = [
			'tab'   => 'content',
			'group' => 'outOfStock',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.out-of-stock',
				],
			],
		];

		$this->controls['outOfStockBackgroundColor'] = [
			'tab'   => 'style',
			'group' => 'outOfStock',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.out-of-stock',
				]
			],
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

		add_filter( 'woocommerce_get_availability', [ $this, 'woocommerce_get_availability' ], 10, 2 );

		$stock_html = wc_get_stock_html( $product );

		if ( ! $stock_html ) {
			remove_filter( 'woocommerce_get_availability', [ $this, 'woocommerce_get_availability' ], 10, 2 );

			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'Stock management not enabled for this product.', 'bricks' ),
				]
			);
		}

		echo "<div {$this->render_attributes( '_root' )}>" . $stock_html . '</div>';

		remove_filter( 'woocommerce_get_availability', [ $this, 'woocommerce_get_availability' ], 10, 2 );
	}

	public function woocommerce_get_availability( $availability, $product ) {
		$settings = $this->settings;

		$stock_quantity   = $product->get_stock_quantity();
		$low_stock_amount = $product->get_low_stock_amount();

		if ( $product->is_in_stock() && $stock_quantity <= $low_stock_amount ) {
			$availability['class'] = 'low-stock';
		}

		if ( ! empty( $settings['inStockText'] ) && $availability['class'] === 'in-stock' ) {
			$availability['availability'] = $settings['inStockText'];
		} elseif ( ! empty( $settings['lowStockText'] ) && $availability['class'] === 'low-stock' ) {
			$availability['availability'] = $settings['lowStockText'];
		} elseif ( ! empty( $settings['outOfStockText'] ) && $availability['class'] === 'out-of-stock' ) {
			$availability['availability'] = $settings['outOfStockText'];
		}

		return $availability;
	}
}
