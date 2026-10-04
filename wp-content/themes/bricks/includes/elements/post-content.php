<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Post_Content extends Element {
	public $category = 'single';
	public $name     = 'post-content';
	public $icon     = 'ti-wordpress';

	public function enqueue_scripts() {
		wp_enqueue_style( 'wp-block-library' );
	}

	public function get_label() {
		return esc_html__( 'Post Content', 'bricks' );
	}

	public function set_controls() {
		$post_id = get_the_ID();

		$template_preview_post_id = Helpers::get_template_setting( 'templatePreviewPostId', $post_id );

		if ( $template_preview_post_id ) {
			$post_id = $template_preview_post_id;
		}

		$edit_link = get_edit_post_link( $post_id );

		$this->controls['info'] = [
			'tab'      => 'content',
			'type'     => 'info',
			'content'  => sprintf( '<a href="' . $edit_link . '" target="_blank">%s</a>', esc_html__( 'Edit WordPress content (WP admin).', 'bricks' ) ),
			'required' => [ 'dataSource', '!=', 'bricks' ],
		];

		if ( BRICKS_DB_TEMPLATE_SLUG === get_post_type() ) {
			$this->controls['dataSource'] = [
				'tab'         => 'content',
				'label'       => esc_html__( 'Data source', 'bricks' ),
				'type'        => 'select',
				'options'     => [
					'editor' => 'WordPress',
					'bricks' => 'Bricks',
				],
				'inline'      => true,
				'placeholder' => 'WordPress',
			];
		}
	}

	public function render() {
		$settings    = $this->settings;
		$data_source = ! empty( $settings['dataSource'] ) ? $settings['dataSource'] : '';

		// To apply CSS flex when "Data Source" is set to "bricks"
		if ( $data_source ) {
			$this->set_attribute( '_root', 'data-source', $data_source );
		}

		$output = '';

		// STEP: Render Bricks data
		if ( $data_source === 'bricks' ) {
			// Previewing a template
			if ( BRICKS_DB_TEMPLATE_SLUG === get_post_type( $this->post_id ) ) {
				return $this->render_element_placeholder(
					[
						'title'       => esc_html__( 'For better preview select content to show.', 'bricks' ),
						'description' => esc_html__( 'Go to: Settings > Template Settings > Populate Content', 'bricks' ),
					]
				);
			}

			// Get Bricks data
			$bricks_data = get_post_meta( $this->post_id, BRICKS_DB_PAGE_CONTENT, true );

			if ( empty( $bricks_data ) || ! is_array( $bricks_data ) ) {
				return $this->render_element_placeholder(
					[
						'title' => esc_html__( 'No Bricks data found.', 'bricks' ),
					]
				);
			}

			// Avoid infinite loop
			static $post_content_loop = 0;

			if ( $post_content_loop < 2 ) {
				$post_content_loop++;

				// Store the current main render_data self::$elements
				$store_elements = Frontend::$elements;

				// STEP: Temporary disable lazy load (required in builder when generating frontend data)
				$disable_lazy_load = isset( Database::$global_settings['disableLazyLoad'] );

				if ( bricks_is_builder_call() ) {
					Database::$global_settings['disableLazyLoad'] = true;
				}

				$output = Frontend::render_data( $bricks_data );

				// STEP: Restore original lazy load setting
				if ( bricks_is_builder_call() ) {
					if ( $disable_lazy_load ) {
						Database::$global_settings['disableLazyLoad'] = true;
					} else {
						unset( Database::$global_settings['disableLazyLoad'] );
					}
				}

				// Reset the main render_data self::$elements
				Frontend::$elements = $store_elements;

				// Add 'style' inline (elements & global classes) in the Builder or Frontend (with Query Loop + External Files)
				if ( bricks_is_builder_call() || ( Query::is_looping() && Database::get_setting( 'cssLoading' ) === 'file' ) ) {
					Assets::$inline_css['content'] = '';

					// Clear the list of elements already styled (@since 1.5)
					Assets::$css_looping_elements = [];

					Assets::generate_css_from_elements( $bricks_data, 'content' );
					$inline_css = Assets::$inline_css['content'];

					// Add global classes CSS (@since 1.5)
					$inline_css_global_classes = Assets::generate_inline_css_global_classes();
					$inline_css               .= Assets::$inline_css['global_classes'];

					$output .= "\n <style>{$inline_css}</style>";
				}

				$post_content_loop--;
			}
		}

		// STEP: Render WordPress content
		else {
			global $wp_query;
			global $post;

			// Store current global post object
			$current_global_post = $post;
			$current_in_the_loop = $wp_query->in_the_loop;

			// Load current $post_id context
			$post = get_post( $this->post_id );
			setup_postdata( $post );

			// Set global in_the_loop()
			// Some plugins might rely on the `in_the_loop` check (e.g. BuddyBoss)
			$wp_query->in_the_loop = true;

			// Render the content like in the loop (@since 1.5)
			ob_start();
			the_content();
			$output = ob_get_clean();

			if ( bricks_is_builder_call() && ! $output ) {
				return $this->render_element_placeholder(
					[
						'title' => esc_html__( 'No WordPress added content found.', 'bricks' ),
					]
				);
			}

			$output .= wp_link_pages(
				[
					'before'      => '<div class="bricks-pagination"><ul><span class="title">' . esc_html__( 'Pages:', 'bricks' ) . '</span>',
					'after'       => '</ul></div>',
					'link_before' => '<span>',
					'link_after'  => '</span>',
					'echo'        => false
				]
			);

			// Restores the global $post / in_the_loop
			setup_postdata( $current_global_post );
			$wp_query->in_the_loop = $current_in_the_loop;
		}

		echo "<div {$this->render_attributes( '_root' )}>$output</div>";
	}
}
