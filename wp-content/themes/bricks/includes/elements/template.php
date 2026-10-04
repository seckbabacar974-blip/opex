<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Template extends Element {
	public $block    = 'core/template';
	public $category = 'general';
	public $name     = 'template';
	public $icon     = 'ti-layers';

	public function get_label() {
		return esc_html__( 'Template', 'bricks' );
	}

	public function set_controls() {
		$this->controls['template'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Template', 'bricks' ),
			'type'        => 'select',
			'options'     => bricks_is_builder() ? Templates::get_templates_list( get_the_ID() ) : [],
			'searchable'  => true,
			'placeholder' => esc_html__( 'Select template', 'bricks' ),
		];
	}

	public function render() {
		$settings    = $this->settings;
		$template_id = ! empty( $settings['template'] ) ? intval( $settings['template'] ) : false;

		if ( ! $template_id ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No template selected.', 'bricks' ),
				]
			);
		}

		if ( $template_id == $this->post_id || $template_id == get_the_ID() ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'Not allowed: Infinite template loop.', 'bricks' ),
				]
			);
		}

		$template_elements = get_post_meta( $template_id, BRICKS_DB_PAGE_CONTENT, true );

		if ( empty( $template_elements ) || ! is_array( $template_elements ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'Your selected template is empty.', 'bricks' ),
				]
			);
		}

		// Avoid infinite loop
		static $rendered_templates = [];

		if ( ! array_key_exists( $template_id, $rendered_templates ) ) {
			// Store template to avoid loops
			$rendered_templates[ $template_id ] = true;

			// Store current main render_data self::$elements
			$store_elements = Frontend::$elements;

			echo "<div {$this->render_attributes( '_root' )}>";
			echo Frontend::render_data( $template_elements );
			echo '</div>';

			// Reset the main render_data self::$elements
			Frontend::$elements = $store_elements;

			// Remove template from loop control
			unset( $rendered_templates[ $template_id ] );
		}
	}

	/**
	 * Helper function to add information to the builder render call (AJAX or REST API)
	 *
	 * @since 1.5
	 *
	 * @param boolean $template_id
	 * @return array
	 */
	public static function get_builder_call_additional_data( $template_id ) {
		$template_elements = get_post_meta( $template_id, BRICKS_DB_PAGE_CONTENT, true );

		// STEP: Add the template elements to the response
		$response['elements'] = $template_elements;

		// Set post_id before generating styles for proper dynamic data translation
		Assets::$post_id = Database::$page_data['preview_or_post_id'];

		$css  = Templates::generate_inline_css( $template_id, $template_elements );
		$css .= Assets::$inline_css_dynamic_data;

		// STEP: Add inline CSS
		$response['css'] = $css;

		return $response;
	}
}
