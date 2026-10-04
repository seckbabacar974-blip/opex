<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Accordion extends Element {
	public $category     = 'general';
	public $name         = 'accordion';
	public $icon         = 'ti-layout-accordion-merged';
	public $scripts      = [ 'bricksAccordion' ];
	public $css_selector = '.accordion-item';
	public $loop_index   = 0;

	public function get_label() {
		return esc_html__( 'Accordion', 'bricks' );
	}

	public function set_control_groups() {
		$this->control_groups['title'] = [
			'title' => esc_html__( 'Title', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['content'] = [
			'title' => esc_html__( 'Content', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		$this->controls['accordions'] = [
			'tab'         => 'content',
			'placeholder' => esc_html__( 'Accordion', 'bricks' ),
			'type'        => 'repeater',
			'checkLoop'   => true,
			'fields'      => [
				'title'    => [
					'label' => esc_html__( 'Title', 'bricks' ),
					'type'  => 'text',
				],
				'subtitle' => [
					'label' => esc_html__( 'Subtitle', 'bricks' ),
					'type'  => 'text',
				],
				'content'  => [
					'label' => esc_html__( 'Content', 'bricks' ),
					'type'  => 'editor',
				],
			],
			'default'     => [
				[
					'title'    => esc_html__( 'Title', 'bricks' ),
					'subtitle' => esc_html__( 'I am a so called subtitle.', 'bricks' ),
					'content'  => esc_html__( 'Content goes here ..', 'bricks' ),
				],
				[
					'title'    => esc_html__( 'Title', 'bricks' ) . ' 2',
					'subtitle' => esc_html__( 'I am a so called subtitle.', 'bricks' ),
					'content'  => esc_html__( 'Content goes here ..', 'bricks' ),
				],
			],
		];

		$this->controls = array_replace_recursive( $this->controls, $this->get_loop_builder_controls() );

		$this->controls['independentToggle'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Behave Like Tabs', 'bricks' ),
			'type'        => 'checkbox',
			'description' => esc_html__( 'Enable to open & close an item without toggling other items.', 'bricks' ),
		];

		$this->controls['transition'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Transition', 'bricks' ) . ' (ms)',
			'type'        => 'number',
			'placeholder' => 200,
		];

		// TITLE

		$this->controls['titleTag'] = [
			'tab'         => 'content',
			'group'       => 'title',
			'label'       => esc_html__( 'HTML tag', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'div' => 'div',
				'h1'  => 'h1',
				'h2'  => 'h2',
				'h3'  => 'h3',
				'h4'  => 'h4',
				'h5'  => 'h5',
				'h6'  => 'h6',
			],
			'inline'      => true,
			'placeholder' => 'h5',
		];

		$this->controls['icon'] = [
			'tab'     => 'content',
			'group'   => 'title',
			'label'   => esc_html__( 'Icon', 'bricks' ),
			'type'    => 'icon',
			'default' => [
				'icon'    => 'ion-ios-arrow-forward',
				'library' => 'ionicons',
			],
		];

		$this->controls['iconTypography'] = [
			'tab'      => 'content',
			'group'    => 'title',
			'label'    => esc_html__( 'Icon typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.accordion-title{pseudo} .icon', // NOTE: Undocumented (@since 1.3.5)
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

		$this->controls['iconExpanded'] = [
			'tab'     => 'content',
			'group'   => 'title',
			'label'   => esc_html__( 'Icon expanded', 'bricks' ),
			'type'    => 'icon',
			'default' => [
				'icon'    => 'ion-ios-arrow-down',
				'library' => 'ionicons',
			],
		];

		$this->controls['iconExpandedTypography'] = [
			'tab'      => 'content',
			'group'    => 'title',
			'label'    => esc_html__( 'Icon expanded typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.accordion-title{pseudo} .icon.expanded',
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
			'required' => [ 'iconExpanded.icon', '!=', '' ],
		];

		$this->controls['iconPosition'] = [
			'tab'         => 'content',
			'group'       => 'title',
			'label'       => esc_html__( 'Icon position', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['iconPosition'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Right', 'bricks' ),
			'required'    => [ 'icon', '!=', '' ],
		];

		$this->controls['iconRotate'] = [
			'tab'         => 'content',
			'group'       => 'title',
			'label'       => esc_html__( 'Icon rotate in °', 'bricks' ),
			'type'        => 'number',
			'unit'        => 'deg',
			'css'         => [
				[
					'property' => 'transform:rotate',
					'selector' => '.brx-open .title + .icon',
				],
			],
			'small'       => false,
			'description' => esc_html__( 'Icon rotation for expanded accordion.', 'bricks' ),
			'required'    => [ 'icon', '!=', '' ],
		];

		$this->controls['titleMargin'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Margin', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.accordion-title-wrapper',
				],
			],
		];

		$this->controls['titlePadding'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.accordion-title-wrapper',
				],
			],
		];

		$this->controls['titleTypography'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Title typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.accordion-title{pseudo} .title',
				],
			],
		];

		$this->controls['subtitleTypography'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Subtitle typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.accordion-subtitle',
				],
			],
		];

		$this->controls['titleBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.accordion-title-wrapper',
				],
			],
		];

		$this->controls['titleBorder'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.accordion-title-wrapper',
				],
			],
		];

		$this->controls['titleActiveBoxShadow'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => '.accordion-title-wrapper',
				],
			],
		];

		$this->controls['titleActiveTypography'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Active typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.brx-open .title',
				],
			],
		];

		$this->controls['titleActiveBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Active background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.brx-open .accordion-title-wrapper',
				],
			],
		];

		$this->controls['titleActiveBorder'] = [
			'tab'   => 'content',
			'group' => 'title',
			'label' => esc_html__( 'Active border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.brx-open .accordion-title-wrapper',
				],
			],
		];

		// CONTENT

		$this->controls['expandFirstItem'] = [
			'tab'   => 'content',
			'group' => 'content',
			'label' => esc_html__( 'Expand first item', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['contentMargin'] = [
			'tab'   => 'content',
			'group' => 'content',
			'label' => esc_html__( 'Margin', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.accordion-content-wrapper',
				],
			],
		];

		$this->controls['contentPadding'] = [
			'tab'   => 'content',
			'group' => 'content',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.accordion-content-wrapper',
				],
			],
		];

		$this->controls['contentTypography'] = [
			'tab'   => 'content',
			'group' => 'content',
			'label' => esc_html__( 'Content typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.accordion-content-wrapper',
				],
			],
		];

		$this->controls['contentBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'content',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.accordion-content-wrapper',
				],
			],
		];

		$this->controls['contentBorder'] = [
			'tab'   => 'content',
			'group' => 'content',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.accordion-content-wrapper',
				],
			],
		];
	}

	public function render() {
		$settings     = $this->settings;
		$theme_styles = $this->theme_styles;

		// Icon
		$icon = false;

		if ( ! empty( $settings['icon'] ) ) {
			$icon = self::render_icon( $settings['icon'], [ 'icon' ] );
		} elseif ( ! empty( $theme_styles['accordionIcon'] ) ) {
			$icon = self::render_icon( $theme_styles['accordionIcon'], [ 'icon' ] );
		}

		// Icon expanded
		$icon_expanded = false;

		if ( ! empty( $settings['iconExpanded'] ) ) {
			$icon_expanded = self::render_icon( $settings['iconExpanded'], [ 'icon', 'expanded' ] );
		} elseif ( ! empty( $theme_styles['accordionIconExpanded'] ) ) {
			$icon_expanded = self::render_icon( $theme_styles['accordionIconExpanded'], [ 'icon', 'expanded' ] );
		}

		$item_classes[] = 'accordion-item';

		// Initially expand first item
		if ( isset( $settings['expandFirstItem'] ) ) {
			$item_classes[] = 'brx-open';
		}

		$this->set_attribute( 'accordion-item', 'class', $item_classes );

		$title_wrapper_classes = [ 'accordion-title-wrapper' ];

		// Toggle accordion items indpendent from each other (to open multiple accordions at the same time)
		if ( isset( $settings['independentToggle'] ) ) {
			$title_wrapper_classes[] = 'independent-toggle';
		}

		$this->set_attribute( 'accordion-title-wrapper', 'class', $title_wrapper_classes );

		$title_classes = [ 'accordion-title' ];

		if ( $icon && ! empty( $settings['iconPosition'] ) ) {
			$title_classes[] = "icon-{$settings['iconPosition']}";
		}

		$this->set_attribute( 'accordion-title', 'class', $title_classes );

		// STEP: Render Accordionss
		$accordions = ! empty( $settings['accordions'] ) ? $settings['accordions'] : false;

		if ( ! $accordions ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No accordion item added.', 'bricks' ),
				]
			);
		}

		$title_tag = ! empty( $settings['titleTag'] ) ? $settings['titleTag'] : 'h5';

		// data-script-args: Independent toggle
		if ( isset( $settings['independentToggle'] ) ) {
			$this->set_attribute( '_root', 'data-script-args', wp_json_encode( [ 'independentToggle' => true ] ) );
		}

		// data-transition: Transition duration in ms
		if ( isset( $settings['transition'] ) ) {
			$this->set_attribute( '_root', 'data-transition', $settings['transition'] );
		}

		$output = "<ul {$this->render_attributes( '_root' )}>";

		// Query Loop
		if ( isset( $settings['hasLoop'] ) ) {
			$query = new Query(
				[
					'id'       => $this->id,
					'settings' => $settings,
				]
			);

			$accordion = $accordions[0];

			$output .= $query->render( [ $this, 'render_repeater_item' ], compact( 'accordion', 'title_tag', 'icon', 'icon_expanded' ) );

			// We need to destroy the Query to explicitly remove it from the global store
			$query->destroy();
			unset( $query );
		} else {
			foreach ( $accordions as $index => $accordion ) {
				$output .= self::render_repeater_item( $accordion, $title_tag, $icon, $icon_expanded );
			}
		}

		$output .= '</ul>';

		echo $output;
	}

	public function render_repeater_item( $accordion, $title_tag, $icon, $icon_expanded ) {
		$settings = $this->settings;
		$index    = $this->loop_index;
		$output   = '';

		// Remove class 'brx-open' after first iteration
		if ( isset( $settings['expandFirstItem'] ) && $index === 1 ) {
			$this->remove_attribute( 'accordion-item', 'class', 'brx-open' );
		}

		$output .= "<li {$this->render_attributes( 'accordion-item' )}>";

		if ( ! empty( $accordion['title'] ) || ! empty( $accordion['subtitle'] ) ) {
			$output .= "<div {$this->render_attributes( 'accordion-title-wrapper' )}>";

			if ( ! empty( $accordion['title'] ) ) {
				$output .= "<div {$this->render_attributes( 'accordion-title' )}>";

				$this->set_attribute( "accordion-title-$index", 'class', [ 'title' ] );

				$output .= "<$title_tag {$this->render_attributes( "accordion-title-$index" )}>" . esc_html( $accordion['title'] ) . "</$title_tag>";

				if ( $icon_expanded ) {
					$output .= $icon_expanded;
				}

				if ( $icon ) {
					$output .= $icon;
				}

				$output .= '</div>';
			}

			if ( ! empty( $accordion['subtitle'] ) ) {
				$this->set_attribute( "accordion-subtitle-$index", 'class', [ 'accordion-subtitle' ] );

				$output .= "<div {$this->render_attributes( "accordion-subtitle-$index" )}>" . esc_html( $accordion['subtitle'] ) . '</div>';
			}

			$output .= '</div>';
		}

		if ( isset( $accordion['content'] ) ) {
			$this->set_attribute( "accordion-content-$index", 'class', [ 'accordion-content-wrapper' ] );

			$content = $this->render_dynamic_data( $accordion['content'] );

			$output .= "<div {$this->render_attributes( "accordion-content-$index" )}>" . apply_filters( 'the_content', $content ) . '</div>';
		}

		$output .= '</li>';

		$this->loop_index++;

		return $output;
	}
}
