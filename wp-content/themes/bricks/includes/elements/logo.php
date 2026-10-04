<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Logo extends Element {
	public $category = 'general';
	public $name     = 'logo';
	public $icon     = 'ti-home';

	public function get_label() {
		return esc_html__( 'Logo', 'bricks' );
	}

	public function get_keywords() {
		return [ 'image' ];
	}

	public function set_controls() {
		$this->controls['logo'] = [
			'tab'            => 'content',
			'label'          => esc_html__( 'Logo', 'bricks' ),
			'type'           => 'image',
			'hasDynamicData' => false,
			'unsplash'       => false,
			'description'    => esc_html__( 'Min. dimension: Twice the value under logo height / logo width for proper display on retina devices.', 'bricks' ),
		];

		$this->controls['logoInverse'] = [
			'tab'            => 'content',
			'label'          => esc_html__( 'Logo inverse', 'bricks' ),
			'type'           => 'image',
			'hasDynamicData' => false,
			'unsplash'       => false,
			'description'    => esc_html__( 'Use for sticky scrolling header etc.', 'bricks' ),
			'required'       => [ 'logo', '!=', '' ],
		];

		$this->controls['logoHeight'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Height', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'height',
					'selector' => '.bricks-site-logo',
				]
			],
			'max'         => 400,
			'placeholder' => 'auto',
			'required'    => [ 'logo', '!=', '' ],
		];

		$this->controls['logoWidth'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Width', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'width',
					'selector' => '.bricks-site-logo',
				]
			],
			'max'         => 999,
			'placeholder' => 'auto',
			'required'    => [ 'logo', '!=', '' ],
		];

		$this->controls['logoUrl'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Link to', 'bricks' ),
			'type'        => 'link',
			'placeholder' => esc_html__( 'Site Address', 'bricks' ),
		];

		$this->controls['logoText'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Text', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'description' => esc_html__( 'Used if logo image isn\'t set or available.', 'bricks' ),
			'default'     => get_bloginfo( 'name' ),
		];
	}

	public function render() {
		$settings = $this->settings;

		// Builder: Set active templates to get template header ID for bricks logo inverse CSS classes
		if ( bricks_is_builder_call() ) {
			Database::set_active_templates( $this->post_id );
		}

		$template_header_id       = Database::$active_templates['header'];
		$template_header_settings = $template_header_id ? Helpers::get_template_settings( $template_header_id ) : [];

		$logo_id   = ! empty( $settings['logo']['id'] ) ? $settings['logo']['id'] : false;
		$logo_size = ! empty( $settings['logo']['size'] ) ? $settings['logo']['size'] : 'full';
		$logo_url  = ! empty( $settings['logo']['url'] ) ? $settings['logo']['url'] : '';

		// NOTE: Use WP function 'wp_get_attachment_image' to render image (easier responsive image implementation)
		if ( $logo_id ) {
			$image_atts['alt']   = ! empty( $settings['logoText'] ) ? esc_attr( $settings['logoText'] ) : get_bloginfo( 'name' );
			$image_atts['class'] = 'bricks-site-logo css-filter';

			// Sticky header
			if ( isset( $template_header_settings['headerSticky'] ) ) {
				$image_atts['data-bricks-logo'] = wp_get_attachment_image_src( $logo_id, $logo_size )[0];

				// Logo inverse
				$logo_inverse_id   = ! empty( $settings['logoInverse']['id'] ) ? $settings['logoInverse']['id'] : false;
				$logo_inverse_size = ! empty( $settings['logoInverse']['size'] ) ? $settings['logoInverse']['size'] : 'full';

				if ( $logo_inverse_id ) {
					$image_atts['data-bricks-logo-inverse'] = wp_get_attachment_image_src( $logo_inverse_id, $logo_inverse_size )[0];
				}
			}

			// Render logo: SVG
			$file_info = pathinfo( $logo_url );

			if ( isset( $file_info['extension'] ) && $file_info['extension'] === 'svg' ) {
				unset( $image_atts['alt'] );

				foreach ( $image_atts as $key => $value ) {
					$this->set_attribute( 'logo', $key, $value );
				}

				$logo = "<div {$this->render_attributes( 'logo' )}>" . Helpers::get_file_contents( $logo_url ) . '</div>';
			}

			// Render logo: Image
			else {
				$logo = wp_get_attachment_image( $logo_id, $logo_size, false, $image_atts );
			}
		}

		// External URL
		elseif ( isset( $settings['logo']['external'] ) && ! empty( $logo_url ) ) {
			$logo_url = $this->render_dynamic_data( $logo_url );

			$logo = "<img class=\"bricks-site-logo\" src=\"{$logo_url}\">";
		}

		// Logo text
		elseif ( ! empty( $settings['logoText'] ) ) {
			$logo = esc_html( $settings['logoText'] );
		}

		// Default: Site name
		else {
			$logo = get_bloginfo( 'name' );
		}

		// Link: Custom URL if provided (fallback: home_url)
		if ( ! empty( $settings['logoUrl'] ) ) {
			$this->set_link_attributes( '_root', $settings['logoUrl'] );
		} else {
			$this->set_attribute( '_root', 'href', home_url() );
		}

		echo "<a {$this->render_attributes( '_root' )}>{$logo}</a>";
	}
}
