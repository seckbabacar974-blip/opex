<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Post_Taxonomy extends Element {
	public $category     = 'single';
	public $name         = 'post-taxonomy';
	public $icon         = 'ti-clip';
	public $css_selector = '.bricks-button';

	public function get_label() {
		return esc_html__( 'Taxonomy', 'bricks' );
	}

	public function set_controls() {
		$this->controls['_margin']['css'][0]['selector'] = '';

		$this->controls['taxonomy'] = [
			'tab'       => 'content',
			'label'     => esc_html__( 'Taxonomy', 'bricks' ),
			'type'      => 'select',
			'options'   => Setup::$control_options['taxonomies'],
			'clearable' => false,
			'default'   => 'post_tag',
		];

		$this->controls['style'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Style', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['styles'],
			'inline'      => true,
			'placeholder' => esc_html__( 'None', 'bricks' ),
			'default'     => 'dark',
		];

		$this->controls['gap'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Spacing', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'selector' => '',
					'property' => 'gap',
				],
			],
			'placeholder' => 10,
		];

		$this->controls['icon'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Icon', 'bricks' ),
			'type'  => 'icon',
		];
	}

	public function render() {
		$settings = $this->settings;

		global $post;
		$post = get_post( $this->post_id );

		$taxonomy = isset( $settings['taxonomy'] ) ? $settings['taxonomy'] : 'post_tag';

		$terms = wp_get_post_terms( get_the_ID(), $taxonomy, [ 'fields' => 'all' ] );
		$terms = wp_list_filter( $terms, [ 'slug' => 'uncategorized' ], 'NOT' );

		if ( ! count( $terms ) ) {
			return $this->render_element_placeholder(
				[
					'title' => sprintf( esc_html__( 'This post has no %s terms.', 'bricks' ), ucfirst( get_taxonomy( $taxonomy )->name ) ),
				]
			);
		}

		$this->set_attribute( '_root', 'class', sanitize_html_class( $taxonomy ) );

		echo "<ul {$this->render_attributes( '_root' )}>";

		$icon = ! empty( $settings['icon'] ) ? self::render_icon( $settings['icon'] ) : false;

		foreach ( $terms as $index => $term_id ) {
			$term_object = get_term( $term_id );

			$button_classes = [ 'bricks-button' ];

			if ( ! empty( $settings['style'] ) ) {
				$button_classes[] = "bricks-background-{$settings['style']}";
			}

			$this->set_attribute( "a-$index", 'class', $button_classes );
			$this->set_attribute( "a-$index", 'href', get_term_link( $term_id ) );

			$output  = '<li>';
			$output .= "<a {$this->render_attributes( "a-$index" )}>";

			if ( $icon ) {
				$output .= $icon . '<span>';
			}

			$output .= $term_object->name;

			if ( $icon ) {
				$output .= '</span>';
			}

			$output .= '</a>';
			$output .= '</li>';

			echo $output;
		}

		echo '</ul>';
	}
}
