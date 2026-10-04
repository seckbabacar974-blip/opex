<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed Woocommerce_directly

class Woocommerce_Theme_Styles {
	public function __construct() {
		add_filter( 'bricks/theme_styles/control_groups', [ $this, 'set_groups' ] );
		add_filter( 'bricks/theme_styles/controls', [ $this, 'set_controls' ] );
	}

	/**
	 * Add Woo Theme style control groups
	 */
	public function set_groups( $control_groups ) {
		$control_groups['woocommerce-button'] = [
			'title' => 'WooCommerce - ' . esc_html__( 'Button', 'bricks' ),
		];

		return $control_groups;
	}

	/**
	 * Add Woo Theme style controls
	 */
	public function set_controls( $controls ) {
		$woo_controls = [];

		// WooCommerce - Button

		$woo_controls['woocommerce-button'] = [
			'padding'    => [
				'group' => 'woocommerce-button',
				'label' => esc_html__( 'Padding', 'bricks' ),
				'type'  => 'spacing',
				'css'   => [
					[
						'selector' => '.woocommerce .button',
						'property' => 'padding',
					],
				],
			],

			'background' => [
				'group' => 'woocommerce-button',
				'label' => esc_html__( 'Background color', 'bricks' ),
				'type'  => 'color',
				'css'   => [
					[
						'selector' => '.woocommerce .button',
						'property' => 'background-color',
					],
				],
			],

			'border'     => [
				'group' => 'woocommerce-button',
				'label' => esc_html__( 'Border', 'bricks' ),
				'type'  => 'border',
				'css'   => [
					[
						'selector' => '.woocommerce .button',
						'property' => 'border',
					],
				],
			],

			'typography' => [
				'group' => 'woocommerce-button',
				'label' => esc_html__( 'Typography', 'bricks' ),
				'type'  => 'typography',
				'css'   => [
					[
						'selector' => '.woocommerce .button',
						'property' => 'font',
					],
				],
			],
		];

		return array_merge( $controls, $woo_controls );
	}
}
