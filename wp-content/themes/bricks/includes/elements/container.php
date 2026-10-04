<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Container extends Element {
	public $category      = 'layout';
	public $name          = 'container';
	public $icon          = 'ti-layout-width-default';
	public $vue_component = 'bricks-nestable';
	public $nestable      = true;

	public function get_label() {
		return esc_html__( 'Container', 'bricks' );
	}

	public function get_keywords() {
		return [ 'query', 'loop', 'repeater' ];
	}

	public function set_controls() {
		/**
		 * Loop Builder
		 *
		 * Enable for elements: Container, Block, Div (not Section)
		 */
		if ( Capabilities::current_user_has_full_access() && in_array( $this->name, [ 'container', 'block', 'div' ] ) ) {
			$this->controls = array_replace_recursive( $this->controls, $this->get_loop_builder_controls() );

			$this->controls['loopSeparator'] = [
				'tab'  => 'content',
				'type' => 'separator',
			];
		}

		$this->controls['link'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Link', 'bricks' ),
			'type'        => 'link',
			'placeholder' => esc_html__( 'Select link type', 'bricks' ),
			'required'    => [ 'tag', '=', 'a' ],
		];

		$this->controls['linkInfo'] = [
			'tab'      => 'content',
			'type'     => 'info',
			'content'  => esc_html__( 'Make sure there are no elements with links inside your linked container (nested links).', 'bricks' ),
			'required' => [ 'link', '!=', '' ],
		];

		$this->controls['tag'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'HTML tag', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'div'     => 'div',
				'section' => 'section',
				'a'       => 'a (' . esc_html__( 'link', 'bricks' ) . ')',
				'article' => 'article',
				'nav'     => 'nav',
				'aside'   => 'aside',
				'address' => 'address',
				'figure'  => 'figure',
				'custom'  => esc_html__( 'Custom', 'bricks' ),
			],
			'lowercase'   => true,
			'inline'      => true,
			'placeholder' => $this->tag ? $this->tag : 'div',
			'fullAccess'  => true,
		];

		$this->controls['customTag'] = [
			'tab'            => 'content',
			'label'          => esc_html__( 'Custom tag', 'bricks' ),
			'type'           => 'text',
			'inline'         => true,
			'hasDynamicData' => false,
			'placeholder'    => 'div',
			'required'       => [ 'tag', '=', 'custom' ],
		];

		// Display
		$this->controls['_display'] = [
			'tab'       => 'content',
			'label'     => esc_html__( 'Display', 'bricks' ),
			'type'      => 'select',
			'options'   => [
				'flex'         => 'flex',
				// 'grid' => 'grid',
				'block'        => 'block',
				'inline-block' => 'inline-block',
				'inline'       => 'inline',
				'none'         => 'none',
			],
			'inline'    => true,
			'lowercase' => true,
			'css'       => [
				[
					'property' => 'display',
					'selector' => '',
				],
			],
		];

		$this->controls['_flexWrap'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Flex wrap', 'bricks' ),
			'tooltip'  => [
				'content'  => 'flex-wrap',
				'position' => 'top-left',
			],
			'type'     => 'select',
			'options'  => $this->control_options['flexWrap'],
			'inline'   => true,
			'css'      => [
				[
					'property' => 'flex-wrap',
				],
			],
			'required' => [ '_display', '=', 'flex' ],
		];

		$this->controls['_direction'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Direction', 'bricks' ),
			'tooltip'  => [
				'content'  => 'flex-direction',
				'position' => 'top-left',
			],
			'type'     => 'direction',
			'css'      => [
				[
					'property' => 'flex-direction',
				],
			],
			'inline'   => true,
			'rerender' => true,
			'required' => [ '_display', '=', 'flex' ],
		];

		$this->controls['_alignSelf'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Align', 'bricks' ) . " ({$this->name})",
			'tooltip'  => [
				'content'  => 'align-self',
				'position' => 'top-left',
			],
			'type'     => 'align-items',
			'css'      => [
				[
					'property'  => 'align-self',
					'important' => true,
				],
				[
					'selector' => '',
					'property' => 'width',
					'value'    => '100%',
					'required' => 'stretch', // NOTE: Undocumented (@since 1.4)
				],
			],
			'required' => [ '_display', '=', 'flex' ],
		];

		$this->controls['_justifyContent'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Align main axis', 'bricks' ),
			'tooltip'  => [
				'content'  => 'justify-content',
				'position' => 'top-left',
			],
			'type'     => 'justify-content',
			'css'      => [
				[
					'property' => 'justify-content',
				],
			],
			'required' => [ '_display', '=', 'flex' ],
		];

		$this->controls['_alignItems'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Align cross axis', 'bricks' ),
			'tooltip'  => [
				'content'  => 'align-items',
				'position' => 'top-left',
			],
			'type'     => 'align-items',
			'css'      => [
				[
					'property' => 'align-items',
				],
			],
			'required' => [ '_display', '=', 'flex' ],
		];

		$this->controls['_columnGap'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Column gap', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'column-gap',
				],
			],
			'info'     => sprintf( __( 'Current browser support: %s (no IE). Use margins for max. browser support.', 'bricks' ), '89%' ),
			'required' => [ '_display', '=', 'flex' ],
		];

		$this->controls['_rowGap'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Row gap', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'row-gap',
				],
			],
			'info'     => sprintf( __( 'Current browser support: %s (no IE). Use margins for max. browser support.', 'bricks' ), '89%' ),
			'required' => [ '_display', '=', 'flex' ],
		];

		$this->controls['_order'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Order', 'bricks' ),
			'type'        => 'number',
			'min'         => -999,
			'css'         => [
				[
					'property' => 'order',
				],
			],
			'placeholder' => 0,
			'required'    => [ '_display', '!=',  'none' ],
		];

		// @since 1.3.5
		$this->controls['_flexGrow'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Flex grow', 'bricks' ),
			'type'        => 'number',
			'min'         => 0,
			'tooltip'     => [
				'content'  => 'flex-grow',
				'position' => 'top-left',
			],
			'css'         => [
				[
					'property' => 'flex-grow',
				],
			],
			'placeholder' => 0,
			'required'    => [ '_display', '!=',  'none' ],
		];

		$this->controls['_flexShrink'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Flex shrink', 'bricks' ),
			'type'        => 'number',
			'min'         => 0,
			'tooltip'     => [
				'content'  => 'flex-shrink',
				'position' => 'top-left',
			],
			'css'         => [
				[
					'property' => 'flex-shrink',
				],
			],
			'placeholder' => 1,
			'required'    => [ '_display', '!=',  'none' ],
		];

		$this->controls['_flexBasis'] = [
			'tab'            => 'content',
			'label'          => esc_html__( 'Flex basis', 'bricks' ),
			'type'           => 'text',
			'tooltip'        => [
				'content'  => 'flex-basis',
				'position' => 'top-left',
			],
			'css'            => [
				[
					'property' => 'flex-basis',
				],
			],
			'inline'         => true,
			'small'          => true,
			'placeholder'    => 'auto',
			'hasDynamicData' => false,
			'required'       => [ '_display', '!=',  'none' ],
		];

		// TAB: STYLE

		// Inner container (direct children)
		$this->controls['_innerContainerSeparator'] = [
			'type'  => 'separator',
			'label' => esc_html__( 'Inner container', 'bricks' ) . ' / div',
			'tab'   => 'style',
			'group' => '_layout',
		];

		$this->controls['_innerContainerMargin'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Margin', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '> .brxe-container',
				],
				[
					'property' => 'margin',
					'selector' => '> .brxe-block',
				],
				[
					'property' => 'margin',
					'selector' => '> .brxe-div',
				],
			],
		];

		$this->controls['_innerContainerPadding'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '> .brxe-container',
				],
				[
					'property' => 'padding',
					'selector' => '> .brxe-block',
				],
				[
					'property' => 'padding',
					'selector' => '> .brxe-div',
				],
			],
		];
	}

	/**
	 * Return shape divider HTML
	 */
	public static function get_shape_divider_html( $settings = [] ) {
		$shape_dividers = ! empty( $settings['_shapeDividers'] ) && is_array( $settings['_shapeDividers'] ) ? $settings['_shapeDividers'] : [];

		$output = '';

		foreach ( $shape_dividers as $shape ) {
			// Skip no shape
			if ( empty( $shape['shape'] ) ) {
				return;
			}

			$svg = Helpers::get_file_contents( BRICKS_URL_ASSETS . "svg/shapes/{$shape['shape']}.svg" );

			// Return: SVG doesn't exist
			if ( ! $svg ) {
				return;
			}

			$shape_classes = [ 'bricks-shape-divider' ];
			$shape_styles  = [];

			// Shape classes
			if ( isset( $shape['front'] ) ) {
				$shape_classes[] = 'front';
			}

			if ( isset( $shape['flipHorizontal'] ) ) {
				$shape_classes[] = 'flip-horizontal';
			}

			if ( isset( $shape['flipVertical'] ) ) {
				$shape_classes[] = 'flip-vertical';
			}

			if ( isset( $shape['overflow'] ) ) {
				$shape_classes[] = 'overflow';
			}

			// Shape styles
			if ( isset( $shape['horizontalAlign'] ) ) {
				$shape_styles[] = "justify-content: {$shape['horizontalAlign']}";
			}

			if ( isset( $shape['verticalAlign'] ) ) {
				$shape_styles[] = "align-items: {$shape['verticalAlign']}";
			}

			// Shape inner styles
			$shape_inner_styles   = [];
			$shape_css_properties = [
				'height',
				'width',
				'top',
				'right',
				'bottom',
				'left',
			];

			foreach ( $shape_css_properties as $property ) {
				$value = isset( $shape[ $property ] ) ? $shape[ $property ] : null;

				if ( $value !== null ) {
					// Append default unit
					if ( is_numeric( $value ) ) {
						$value .= 'px';
					}

					$shape_inner_styles[] = "{$property}: {$value}";
				}
			}

			if ( isset( $shape['rotate'] ) ) {
				$rotate               = intval( $shape['rotate'] );
				$shape_inner_styles[] = "transform: rotate({$rotate}deg)";
			}

			$output .= '<div class="' . join( ' ', $shape_classes ) . '" style="' . join( '; ', $shape_styles ) . '">';
			$output .= '<div class="bricks-shape-divider-inner" style="' . join( '; ', $shape_inner_styles ) . '">';

			$dom = new \DOMDocument();
			libxml_use_internal_errors( true );
			$dom->loadXML( $svg );

			// SVG styles
			$svg_styles = [];

			if ( isset( $shape['fill']['raw'] ) ) {
				$svg_styles[] = "fill: {$shape['fill']['raw']}";
			} elseif ( isset( $shape['fill']['rgb'] ) ) {
				$svg_styles[] = "fill: {$shape['fill']['rgb']}";
			} elseif ( isset( $shape['fill']['hex'] ) ) {
				$svg_styles[] = "fill: {$shape['fill']['hex']}";
			}

			foreach ( $dom->getElementsByTagName( 'svg' ) as $element ) {
				$element->setAttribute( 'style', join( '; ', $svg_styles ) );
			}

			$svg = $dom->saveXML();

			$output .= str_replace( '<?xml version="1.0"?>', '', $svg );

			$output .= '</div>';
			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Return background video HTML
	 */
	public function get_background_video_html( $settings ) {
		$background = ! empty( $settings['_background'] ) ? $settings['_background'] : false;
		$video_url  = ! empty( $background['videoUrl'] ) ? $background['videoUrl'] : false;

		if ( ! $video_url ) {
			return '';
		}

		// Dynamic data video URL
		if ( strpos( $video_url, '{' ) !== false ) {
			$video_url = Integrations\Dynamic_Data\Providers::render_content( $video_url, $this->post_id, 'link' );
		}

		if ( ! $video_url ) {
			return '';
		}

		$attributes[] = 'class="bricks-background-video-wrapper bricks-lazy-video"';
		$attributes[] = 'data-background-video-url="' . esc_url( $video_url ) . '"';

		if ( ! empty( $background['videoScale'] ) ) {
			$attributes[] = 'data-background-video-scale="' . $background['videoScale'] . '"';
		}

		if ( ! empty( $background['videoAspectRatio'] ) ) {
			$attributes[] = 'data-background-video-ratio="' . $background['videoAspectRatio'] . '"';
		}

		$attributes = join( ' ', $attributes );

		/**
		 * @since 1.4: Chrome doesn't play the .mp4 background video if the <video> tag is injected programmatically using JavaScript
		 */
		return "<div $attributes><video autoplay loop playsinline muted></video></div>";
	}

	public function render() {
		$element  = $this->element;
		$settings = ! empty( $element['settings'] ) ? $element['settings'] : [];
		$output   = '';

		// Bricks Query Loop
		if ( isset( $settings['hasLoop'] ) ) {
			// STEP: Query
			add_filter( 'bricks/posts/query_vars', [ $this, 'maybe_set_preview_query' ], 10, 3 );

			$query = new \Bricks\Query( $element );

			remove_filter( 'bricks/posts/query_vars', [ $this, 'maybe_set_preview_query' ], 10, 3 );

			// Prevent endless loop
			unset( $element['settings']['hasLoop'] );

			// STEP: Render loop
			$output = $query->render( 'Bricks\Frontend::render_element', compact( 'element' ) );

			echo $output;

			// STEP: Infinite scroll
			$this->render_query_loop_trail( $query );

			// Destroy Query to explicitly remove it from global store
			$query->destroy();
			unset( $query );

			return;
		}

		// Render the video wrapper first so we know it before adding the has-bg-video class (@since 1.5.1)
		$video_wrapper_html = $this->get_background_video_html( $settings );

		// Add .has-bg-video to set z-index: 1 (#2g9ge90)
		if ( ! empty( $video_wrapper_html ) ) {
			$this->set_attribute( '_root', 'class', 'has-bg-video' );
		}

		// Add .has-shape to set position: relative (#2t7w2bq)
		if ( ! empty( $settings['_shapeDividers'] ) ) {
			$this->set_attribute( '_root', 'class', 'has-shape' );
		}

		// Default: Non Query Loop
		$output .= "<{$this->tag} {$this->render_attributes( '_root' )}>";

		$output .= self::get_shape_divider_html( $settings );
		$output .= $video_wrapper_html;

		if ( ! empty( $element['children'] ) && is_array( $element['children'] ) ) {
			foreach ( $element['children'] as $child_id ) {
				if ( ! array_key_exists( $child_id, Frontend::$elements ) ) {
					continue;
				}

				$child = Frontend::$elements[ $child_id ];

				$output .= Frontend::render_element( $child ); // Recursive
			}
		}

		$output .= "</{$this->tag}>";

		echo $output;
	}
}
