<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Post_Excerpt extends Element {
	public $category = 'single';
	public $name     = 'post-excerpt';
	public $icon     = 'ti-paragraph';

	public function get_label() {
		return esc_html__( 'Excerpt', 'bricks' );
	}

	public function set_controls() {
		$this->controls['info'] = [
			'tab'     => 'content',
			'type'    => 'info',
			'content' => sprintf( '<a href="https://codex.wordpress.org/Excerpt" target="_blank">%s</a>', esc_html__( 'Learn more on wordpress.org', 'bricks' ) ),
		];

		$this->controls['length'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Excerpt length', 'bricks' ),
			'type'        => 'number',
			'max'         => 999,
			'placeholder' => 15,
		];

		$this->controls['more'] = [
			'tab'            => 'content',
			'label'          => esc_html__( 'More text', 'bricks' ),
			'type'           => 'text',
			'inline'         => true,
			'small'          => true,
			'hasDynamicData' => false,
			'placeholder'    => '...',
		];
	}

	public function render() {
		$settings = $this->settings;

		// Render category/term description
		if ( category_description() ) {
			echo category_description();
		} else {
			$length = isset( $settings['length'] ) ? $settings['length'] : 15;
			$more   = isset( $settings['more'] ) ? $settings['more'] : '&hellip;';

			$excerpt = Helpers::get_the_excerpt( $this->post_id, $length, $more );
			$excerpt = apply_filters( 'the_excerpt', $excerpt );

			if ( ! $excerpt ) {
				return $this->render_element_placeholder( [ 'title' => esc_html__( 'No excerpt found.', 'bricks' ) ] );
			}

			echo "<div {$this->render_attributes( '_root' )}>$excerpt</div>";
		}
	}
}
