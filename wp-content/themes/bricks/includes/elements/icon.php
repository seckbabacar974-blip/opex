<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Icon extends Element {
	public $category = 'basic';
	public $name     = 'icon';
	public $icon     = 'ti-star';

	public function get_label() {
		return esc_html__( 'Icon', 'bricks' );
	}

	public function set_controls() {
		$this->controls['icon'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Icon', 'bricks' ),
			'type'    => 'icon',
			'default' => [
				'library' => 'themify',
				'icon'    => 'ti-star',
			],
			'root'    => true, // To target 'svg' root
		];

		$this->controls['iconColor'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'color',
				],
				[
					'property' => 'fill',
				],
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];

		$this->controls['iconSize'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Size', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'font-size',
				],
			],
			'required' => [ 'icon.icon', '!=', '' ],
		];

		$this->controls['link'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Link', 'bricks' ),
			'type'  => 'link',
		];
	}

	public function render() {
		$settings = $this->settings;
		$is_svg   = ! empty( $settings['icon']['svg'] );

		if ( empty( $settings['icon'] ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No icon selected.', 'bricks' ),
				]
			);
		}

		$root_attributes = $this->attributes['_root'];

		if ( ! empty( $settings['link'] ) ) {
			$root_classes = ! empty( $root_attributes['class'] ) ? $root_attributes['class'] : '';

			// Move root classes to outer 'a' tag
			if ( $root_classes ) {
				$this->set_attribute( 'link', 'class', $root_classes );

				unset( $root_attributes['class'] );
			}

			$this->set_attribute( 'link', 'class', $is_svg ? 'svg' : 'i' );

			$this->set_link_attributes( 'link', $settings['link'] );

			$icon = self::render_icon( $settings['icon'], $root_attributes );

			echo "<a {$this->render_attributes( 'link' )}>{$icon}</a>";
		} else {
			echo self::render_icon( $settings['icon'], $root_attributes );
		}
	}
}
