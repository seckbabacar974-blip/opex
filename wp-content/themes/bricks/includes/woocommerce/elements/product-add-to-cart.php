<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Product_Add_To_Cart extends Element {
	public $category = 'woocommerce_product';
	public $name     = 'product-add-to-cart';
	public $icon     = 'ti-shopping-cart';

	public function get_label() {
		return esc_html__( 'Add to cart', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['variations'] = [
			'title' => esc_html__( 'Variations', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['stock'] = [
			'title' => esc_html__( 'Stock', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['quantity'] = [
			'title' => esc_html__( 'Quantity', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['button'] = [
			'title' => esc_html__( 'Button', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		// VARIATIONS

		$this->controls['variationsTypography'] = [
			'tab'   => 'content',
			'group' => 'variations',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => 'table.variations label',
				],
			],
		];

		$this->controls['variationsBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'variations',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => 'table.variations tr',
				],
			],
		];

		$this->controls['variationsBorder'] = [
			'tab'   => 'content',
			'group' => 'variations',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.cart .variations tr',
				]
			],
		];

		$this->controls['variationsMargin'] = [
			'tab'         => 'content',
			'group'       => 'variations',
			'label'       => esc_html__( 'Margin', 'bricks' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'selector' => '.cart table.variations',
					'property' => 'margin',
				],
			],
			'placeholder' => [
				'bottom' => 30,
			],
		];

		$this->controls['variationsPadding'] = [
			'tab'         => 'content',
			'group'       => 'variations',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'selector' => '.cart table.variations td',
					'property' => 'padding',
				],
			],
			'placeholder' => [
				'top'    => 15,
				'bottom' => 15,
			],
		];

		$this->controls['variationsDescriptionTypography'] = [
			'tab'   => 'content',
			'group' => 'variations',
			'label' => esc_html__( 'Description typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.woocommerce-variation-description',
				],
			],
		];

		$this->controls['variationsPriceTypography'] = [
			'tab'   => 'content',
			'group' => 'variations',
			'label' => esc_html__( 'Price typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.woocommerce-variation-price',
				],
			],
		];

		// STOCK

		$this->controls['hideStock'] = [
			'tab'   => 'content',
			'group' => 'stock',
			'label' => esc_html__( 'Hide stock', 'bricks' ),
			'type'  => 'checkbox',
			'css'   => [
				[
					'selector' => '.stock',
					'property' => 'display',
					'value'    => 'none',
				],
			],
		];

		$this->controls['stockTypography'] = [
			'tab'      => 'content',
			'group'    => 'stock',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.stock',
				],
			],
			'required' => [ 'hideStock', '=', '' ]
		];

		// QUANTITY
		$this->controls['quantityWidth'] = [
			'tab'   => 'content',
			'group' => 'quantity',
			'type'  => 'number',
			'units' => true,
			'label' => esc_html__( 'Width', 'bricks' ),
			'css'   => [
				[
					'selector' => '.quantity',
					'property' => 'width',
				],
			],
		];

		$this->controls['quantityBackground'] = [
			'tab'   => 'content',
			'group' => 'quantity',
			'type'  => 'color',
			'label' => esc_html__( 'Background', 'bricks' ),
			'css'   => [
				[
					'selector' => '.quantity',
					'property' => 'background-color',
				],
			],
		];

		$this->controls['quantityBorder'] = [
			'tab'   => 'content',
			'group' => 'quantity',
			'type'  => 'border',
			'label' => esc_html__( 'Border', 'bricks' ),
			'css'   => [
				[
					'selector' => '.quantity',
					'property' => 'border',
				],
				[
					'selector' => '.minus',
					'property' => 'border',
				],
				[
					'selector' => '.plus',
					'property' => 'border',
				],
			],
		];

		// BUTTON

		$this->controls['buttonText'] = [
			'tab'            => 'content',
			'group'          => 'button',
			'tooltip'        => [
				'content'  => esc_html__( 'Text', 'bricks' ),
				'position' => 'top-left',
			],
			'type'           => 'text',
			'hasDynamicData' => 'text',
			'placeholder'    => esc_html__( 'Add to cart', 'bricks' ),
		];

		$this->controls['buttonPadding'] = [
			'tab'   => 'content',
			'group' => 'button',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'selector' => '.single_add_to_cart_button',
					'property' => 'padding',
				],
			],
		];

		$this->controls['buttonWidth'] = [
			'tab'   => 'content',
			'group' => 'button',
			'label' => esc_html__( 'Width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'selector' => '.single_add_to_cart_button',
					'property' => 'min-width',
				],
			],
		];

		$this->controls['buttonBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'button',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'selector' => '.cart .single_add_to_cart_button',
					'property' => 'background-color',
				],
			],
		];

		$this->controls['buttonBorder'] = [
			'tab'   => 'content',
			'group' => 'button',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.cart .single_add_to_cart_button',
				],
			],
		];

		$this->controls['buttonTypography'] = [
			'tab'   => 'content',
			'group' => 'button',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'selector' => '.cart .single_add_to_cart_button',
					'property' => 'font',
				],
			],
		];

		// Button icon

		$this->controls['icon'] = [
			'tab'      => 'content',
			'group'    => 'button',
			'label'    => esc_html__( 'Icon', 'bricks' ),
			'type'     => 'icon',
			'rerender' => true,
		];

		$this->controls['iconTypography'] = [
			'tab'      => 'content',
			'group'    => 'button',
			'label'    => esc_html__( 'Icon typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.icon',
				],
			],
			'exclude'  => [
				'font-family',
				'font-weight',
				'font-style',
				'text-align',
				'text-decoration',
				'text-transform',
				'line-height',
				'letter-spacing',
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];

		$this->controls['iconPosition'] = [
			'tab'         => 'content',
			'group'       => 'button',
			'label'       => esc_html__( 'Icon position', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['iconPosition'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Left', 'bricks' ),
			'required'    => [ 'icon', '!=', '' ],
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

		add_filter( 'woocommerce_product_single_add_to_cart_text', [ $this, 'add_to_cart_text' ], 10, 2 );
		add_filter( 'esc_html', [ $this, 'avoid_esc_html' ], 10, 2 );

		echo "<div {$this->render_attributes( '_root' )}>";

		woocommerce_template_single_add_to_cart();

		echo '</div>';

		remove_filter( 'woocommerce_product_single_add_to_cart_text', [ $this, 'add_to_cart_text' ], 10, 2 );
		remove_filter( 'esc_html', [ $this, 'avoid_esc_html' ], 10, 2 );
	}

	/**
	 * Add custom text and/or icon to the button
	 *
	 * @param string     $text
	 * @param WC_Product $product
	 * @return void
	 */
	public function add_to_cart_text( $text, $product ) {
		$settings = $this->settings;

		$text = ! empty( $settings['buttonText'] ) ? $settings['buttonText'] : $text;

		$icon          = ! empty( $settings['icon'] ) ? self::render_icon( $settings['icon'], [ 'icon' ] ) : false;
		$icon_position = isset( $settings['iconPosition'] ) ? $settings['iconPosition'] : 'left';

		$output = '';

		if ( $icon && $icon_position === 'left' ) {
			$output .= $icon;
		}

		$output .= "<span>$text</span>";

		if ( $icon && $icon_position === 'right' ) {
			$output .= $icon;
		}

		return $output;
	}

	public function avoid_esc_html( $safe_text, $text ) {
		return $text;
	}
}
