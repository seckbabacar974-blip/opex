<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

abstract class Element {
	/**
	 * Gutenberg block name: 'core/heading', etc.
	 *
	 * Mapping of Gutenberg block to Bricks element to load block post_content in Bricks and save Bricks data as WordPress post_content.
	 */
	public $block = null;

	// Builder
	public $element;
	public $category;
	public $name;
	public $label;
	public $keywords;
	public $icon;
	public $controls;
	public $control_groups;
	public $control_options;
	public $css_selector;
	public $scripts         = [];
	public $post_id         = 0;
	public $draggable       = true;  // false to prevent dragging over entire element in builder
	public $deprecated      = false; // true to hide element in panel (editing of existing deprecated element still works)
	public $panel_condition = [];    // array conditions to show the element in the panel

	// Frontend
	public $id;
	public $tag        = 'div';
	public $attributes = [];
	public $settings;
	public $theme_styles = [];

	public $is_frontend = false;

	/**
	 * Custom attributes
	 *
	 * true: renders custom attributes on element '_root' (= default)
	 * false: handle custom attributes in element render_attributes( 'xxx', true ) function (e.g. Nav Menu)
	 *
	 * @since 1.3
	 */
	public $custom_attributes = true;

	/**
	 * Nestable elements
	 *
	 * @since 1.5
	 */
	public $nestable = false;      // true to allow to insert child elements (e.g. Container, Div)
	public $nestable_item;         // First child of nestable element (Use as blueprint for nestable children & when adding repeater item)
	public $nestable_children;     // Array of children elements that are added inside nestable element when it's added to the canvas.
	public $nestable_hide = false; // Boolean to hide nestable in Structure & prevent dragging (true if no full access)
	public $nestable_html = '';    // Nestable HTML with placeholder for element 'children'

	public $vue_component;         // Set specific Vue component to render element in builder (e.g. 'bricks-nestable' for Section, Container, Div)

	public $original_query = '';

	public function __construct( $element = null ) {
		$this->element           = $element;
		$this->label             = $this->get_label();
		$this->keywords          = $this->get_keywords();
		$this->is_frontend       = isset( $element['is_frontend'] ) ? $element['is_frontend'] : bricks_is_frontend();
		$this->id                = ! empty( $element['id'] ) ? $element['id'] : Helpers::generate_random_id( false );
		$this->settings          = ! empty( $element['settings'] ) ? $element['settings'] : [];
		$this->tag               = $this->get_tag();
		$this->nestable_item     = $this->get_nestable_item();
		$this->nestable_children = $this->get_nestable_children();
		$this->nestable_hide     = Capabilities::current_user_has_full_access() === false;

		// To distinguish non-layout nestables (slider-nested, etc.) in Vue render
		if ( $this->nestable && ! $this->is_layout_element() ) {
			$this->nestable_html = true;
		}

		// Element-specific theme style settings
		if ( ! empty( $element['themeStyles'] ) ) {
			$this->theme_styles = $element['themeStyles'];
		} elseif ( ! empty( Theme_Styles::$active_settings[ $this->name ] ) ) {
			$this->theme_styles = Theme_Styles::$active_settings[ $this->name ];
		}
	}

	/**
	 * Populate element data (when element is requested)
	 *
	 * Builder: Load all elements
	 * Frontend: Load only requested elements
	 *
	 * @since 1.0
	 */
	public function load() {
		$this->control_options = Setup::$control_options;

		// Control groups
		$this->control_groups = [];
		$this->set_common_control_groups();
		$this->set_control_groups();

		// @see: https://academy.bricksbuilder.io/article/filter-bricks-elements-element_name-control_groups
		$this->control_groups = apply_filters( "bricks/elements/$this->name/control_groups", $this->control_groups );

		// Controls
		$this->controls = [];
		$this->set_controls_before();
		$this->set_controls();
		$this->set_controls_after();

		// @see: https://academy.bricksbuilder.io/article/filter-bricks-elements-element_name-controls
		$this->controls = apply_filters( "bricks/elements/$this->name/controls", $this->controls );

		// Set CSS selector
		if ( ! empty( $this->css_selector ) ) {
			$this->set_css_selector( $this->css_selector );
		}

		// Frontend
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Add element-specific WordPress actions to run in constructor
	 *
	 * @since 1.0
	 */
	public function add_actions() {}

	/**
	 * Add element-specific WordPress filters to run in constructor
	 *
	 * E.g. 'nav_menu_item_title' filter in Element_Nav_Menu
	 *
	 * @since 1.0
	 */
	public function add_filters() {}

	/**
	 * Set default CSS selector of each control with 'css' property
	 *
	 * To target specific element child tag (such as 'a' in 'button' etc.)
	 * Avoids having to set CSS selector manually for each element control.
	 *
	 * @since 1.0
	 */
	public function set_css_selector( $custom_css_selector ) {
		foreach ( $this->controls as $key => $value ) {
			if ( isset( $this->controls[ $key ]['css'] ) && is_array( $this->controls[ $key ]['css'] ) ) {
				foreach ( $this->controls[ $key ]['css'] as $index => $value ) {
					if ( ! isset( $this->controls[ $key ]['css'][ $index ]['selector'] ) ) {
						$this->controls[ $key ]['css'][ $index ]['selector'] = $custom_css_selector;
					}
				}
			}
		}
	}

	public function get_label() {
		// Fallback: Use element name if element class has no get_label() defined
		return str_replace( '-', ' ', $this->name );
	}

	public function get_keywords() {
		return [];
	}

	/**
	 * Return element tag
	 *
	 * Default: $tag set in element class
	 * Fallback: 'div'
	 *
	 * Custom tag: Check element 'tag' and 'customTag' settings.
	 *
	 * @since 1.4
	 */
	public function get_tag() {
		$settings = $this->settings;
		$tag      = $this->tag ? $this->tag : 'div';

		// Return element default tag (e.g. heading is 'h3'. Otherwise fallback to 'div')
		if ( empty( $settings['tag'] ) ) {
			return $tag;
		}

		$tag = $settings['tag'];

		return $tag === 'custom' && ! empty( $settings['customTag'] ) ? $settings['customTag'] : $tag;
	}

	/**
	 * Element-specific control groups
	 *
	 * @since 1.0
	 */
	public function set_control_groups() {}

	/**
	 * Element-specific controls
	 *
	 * @since 1.0
	 */
	public function set_controls() {}

	/**
	 * Control groups used by all elements under 'style' tab
	 *
	 * @since 1.0
	 */
	public function set_common_control_groups() {
		$this->control_groups['_layout'] = [
			'title' => esc_html__( 'Layout', 'bricks' ),
			'tab'   => 'style',
		];

		$this->control_groups['_typography'] = [
			'title' => esc_html__( 'Typography', 'bricks' ),
			'tab'   => 'style',
		];

		$this->control_groups['_background'] = [
			'title' => esc_html__( 'Background', 'bricks' ),
			'tab'   => 'style',
		];

		$this->control_groups['_border'] = [
			'title' => esc_html__( 'Border / Box Shadow', 'bricks' ),
			'tab'   => 'style',
		];

		$this->control_groups['_gradient'] = [
			'title' => esc_html__( 'Gradient / Overlay', 'bricks' ),
			'tab'   => 'style',
		];

		if ( $this->is_layout_element() ) {
			$this->control_groups['_shapes'] = [
				'title' => esc_html__( 'Shape Dividers', 'bricks' ),
				'tab'   => 'style',
			];
		}

		$this->control_groups['_transform'] = [
			'title' => esc_html__( 'Transform', 'bricks' ),
			'tab'   => 'style',
		];

		$this->control_groups['_css'] = [
			'title' => esc_html__( 'CSS', 'bricks' ),
			'tab'   => 'style',
		];

		$this->control_groups['_attributes'] = [
			'title' => esc_html__( 'Attributes', 'bricks' ),
			'tab'   => 'style',
		];
	}

	/**
	 * Controls used by all elements under 'style' tab
	 *
	 * @since 1.0
	 */
	public function set_controls_before() {
		// For pseudo-elements like :before & :after (@since 1.3.5)
		$this->controls['_content'] = [
			'tab'    => 'style',
			'label'  => esc_html__( 'Content', 'bricks' ),
			'type'   => 'text',
			'hidden' => true,
			'css'    => [
				[
					'property' => 'content',
					'value'    => '"%s"', // Surround content value with double quotes
				]
			],
		];

		// LAYOUT

		// Spacing

		$this->controls['_spacingSeparator'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Spacing', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['_margin'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Margin', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '',
				]
			],
		];

		$this->controls['_padding'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
				]
			],
		];

		// Sizing: (width, height)

		$this->controls['_sizingSeparator'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Sizing', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['_width'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'width',
					'selector' => '',
				],
			],
		];

		$this->controls['_widthMin'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Min. width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'min-width',
					'selector' => '',
				],
			],
		];

		/**
		 * max-width: 100% by default for all elements & containers to avoid horizontal scrollbar when setting width
		 */
		$this->controls['_widthMax'] = [
			'tab'         => 'style',
			'group'       => '_layout',
			'label'       => esc_html__( 'Max. width', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'max-width',
					'selector' => '',
				],
			],
			'placeholder' => '100%',
		];

		$this->controls['_height'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Height', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'height',
				],
			],
			'info'  => __( 'Set to "100vh" for full height.', 'bricks' ),
		];

		$this->controls['_heightMin'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Min. height', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'min-height',
				],
			],
		];

		$this->controls['_heightMax'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Max. height', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'max-height',
				],
			],
		];

		// POSITIONING

		$this->controls['_positionSeparator'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Positioning', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['_position'] = [
			'tab'         => 'style',
			'group'       => '_layout',
			'label'       => esc_html__( 'Position', 'bricks' ),
			'type'        => 'select',
			'options'     => Setup::$control_options['position'],
			'css'         => [
				[
					'property' => 'position',
					'selector' => '',
				],
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Static', 'bricks' ),
		];

		$this->controls['_positionInfo'] = [
			'type'     => 'info',
			'content'  => esc_html__( 'Set "Top" value make this element "sticky".', 'bricks' ),
			'tab'      => 'style',
			'group'    => '_layout',
			'required' => [ '_position', '=', 'sticky' ],
		];

		$this->controls['_top'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Top', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'top',
					'selector' => '',
				],
			],
		];

		$this->controls['_right'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Right', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'right',
					'selector' => '',
				],
			],
		];

		$this->controls['_bottom'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Bottom', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'bottom',
					'selector' => '',
				],
			],
		];

		$this->controls['_left'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Left', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'left',
					'selector' => '',
				],
			],
		];

		$this->controls['_zIndex'] = [
			'tab'         => 'style',
			'group'       => '_layout',
			'label'       => esc_html__( 'Z-index', 'bricks' ),
			'type'        => 'number',
			'css'         => [
				[
					'property' => 'z-index',
					'selector' => '',
				],
			],
			'min'         => -999,
			'placeholder' => 0,
		];

		if ( ! $this->is_layout_element() ) {
			$this->controls['_order'] = [
				'tab'         => 'style',
				'group'       => '_layout',
				'label'       => esc_html__( 'Order', 'bricks' ),
				'type'        => 'number',
				'css'         => [
					[
						'selector' => '',
						'property' => 'order',
					],
				],
				'min'         => -999,
				'placeholder' => 0,
			];
		}

		// Misc

		$this->controls['_miscSeparator'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Misc', 'bricks' ),
			'type'  => 'separator',
		];

		if ( ! $this->is_layout_element() ) {
			$this->controls['_display'] = [
				'tab'       => 'style',
				'group'     => '_layout',
				'label'     => esc_html__( 'Display', 'bricks' ),
				'type'      => 'select',
				'options'   => [
					'flex'         => 'flex',
					'block'        => 'block',
					'inline-block' => 'inline-block',
					'inline'       => 'inline',
					'none'         => 'none',
				],
				'inline'    => true,
				'lowercase' => true,
				'css'       => [
					[
						'selector' => '',
						'property' => 'display',
					],
				],
			];
		}

		$this->controls['_overflow'] = [
			'tab'            => 'style',
			'group'          => '_layout',
			'label'          => esc_html__( 'Overflow', 'bricks' ),
			'type'           => 'text',
			'css'            => [
				[
					'property' => 'overflow',
				]
			],
			'inline'         => true,
			'hasDynamicData' => false,
			'placeholder'    => 'visible',
		];

		$this->controls['_opacity'] = [
			'tab'         => 'style',
			'group'       => '_layout',
			'label'       => esc_html__( 'Opacity', 'bricks' ),
			'type'        => 'number',
			'step'        => '.01',
			'min'         => '0',
			'max'         => '1',
			'css'         => [
				[
					'property' => 'opacity',
				]
			],
			'small'       => false,
			'placeholder' => 1,
		];

		// Flex controls (for non-layout elements: no section, container, div)
		if ( ! $this->is_layout_element() ) {
			$this->controls['_flexSeparator'] = [
				'tab'   => 'style',
				'group' => '_layout',
				'label' => esc_html__( 'Flex', 'bricks' ),
				'type'  => 'separator',
			];

			$this->controls['_alignSelf'] = [
				'tab'     => 'style',
				'group'   => '_layout',
				'label'   => esc_html__( 'Align self', 'bricks' ),
				'type'    => 'align-items',
				'tooltip' => [
					'content'  => 'align-self',
					'position' => 'top-left',
				],
				'css'     => [
					[
						'selector' => '',
						'property' => 'align-self',
					],
				],
			];

			$this->controls['_justifyContent'] = [
				'tab'      => 'style',
				'group'    => '_layout',
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
				'required' => [ '_display', '=', [ 'flex', 'inline-flex' ] ],
			];

			$this->controls['_alignItems'] = [
				'tab'      => 'style',
				'group'    => '_layout',
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
				'required' => [ '_display', '=', [ 'flex', 'inline-flex' ] ],
			];

			$this->controls['_flexGrow'] = [
				'tab'         => 'style',
				'group'       => '_layout',
				'label'       => esc_html__( 'Flex grow', 'bricks' ),
				'type'        => 'number',
				'tooltip'     => [
					'content'  => 'flex-grow',
					'position' => 'top-left',
				],
				'css'         => [
					[
						'selector' => '',
						'property' => 'flex-grow',
					],
				],
				'min'         => 0,
				'placeholder' => 0,
			];

			$this->controls['_flexShrink'] = [
				'tab'         => 'style',
				'group'       => '_layout',
				'label'       => esc_html__( 'Flex shrink', 'bricks' ),
				'type'        => 'number',
				'tooltip'     => [
					'content'  => 'flex-shrink',
					'position' => 'top-left',
				],
				'css'         => [
					[
						'selector' => '',
						'property' => 'flex-shrink',
					],
				],
				'min'         => 0,
				'placeholder' => 1,
			];

			$this->controls['_flexBasis'] = [
				'tab'            => 'style',
				'group'          => '_layout',
				'label'          => esc_html__( 'Flex basis', 'bricks' ),
				'type'           => 'text',
				'tooltip'        => [
					'content'  => 'flex-basis',
					'position' => 'top-left',
				],
				'css'            => [
					[
						'selector' => '',
						'property' => 'flex-basis',
					],
				],
				'inline'         => true,
				'small'          => true,
				'hasDynamicData' => false,
				'placeholder'    => 'auto',
			];
		}

		// TYPOGRAPHY

		$this->controls['_typography'] = [
			'tab'   => 'style',
			'group' => '_typography',
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
				],
			],
			'popup' => false,
		];

		// BACKGROUND

		$this->controls['_background'] = [
			'tab'   => 'style',
			'group' => '_background',
			'type'  => 'background',
			'css'   => [
				[
					'property' => 'background',
				]
			],
			'popup' => false,
		];

		// SHAPES
		if ( $this->is_layout_element() ) {
			$this->controls['_shapeDividers'] = [
				'tab'           => 'style',
				'group'         => '_shapes',
				'placeholder'   => esc_html__( 'Shape', 'bricks' ),
				'type'          => 'repeater',
				'pasteStyles'   => true,
				'titleProperty' => 'shape',
				'fields'        => [
					'shape'           => [
						'type'        => 'select',
						'options'     => [
							'cloud'                    => esc_html__( 'Cloud', 'bricks' ),
							'drops'                    => esc_html__( 'Drops', 'bricks' ),
							'grid-round'               => esc_html__( 'Grid (Round)', 'bricks' ),
							'grid-square'              => esc_html__( 'Grid (Square)', 'bricks' ),
							'round'                    => esc_html__( 'Round', 'bricks' ),
							'square'                   => esc_html__( 'Square', 'bricks' ),
							'stroke'                   => esc_html__( 'Stroke', 'bricks' ),
							'stroke-2'                 => esc_html__( 'Stroke #2', 'bricks' ),
							'tilt'                     => esc_html__( 'Tilt', 'bricks' ),
							'triangle'                 => esc_html__( 'Triangle', 'bricks' ),
							'triangle-concave'         => esc_html__( 'Triangle concave', 'bricks' ),
							'triangle-convex'          => esc_html__( 'Triangle convex', 'bricks' ),
							'triangle-double'          => esc_html__( 'Triangle double', 'bricks' ),
							'wave'                     => esc_html__( 'Wave', 'bricks' ),
							'waves'                    => esc_html__( 'Waves', 'bricks' ),
							'wave-brush'               => esc_html__( 'Wave brush', 'bricks' ),
							'zigzag'                   => esc_html__( 'Zigzag', 'bricks' ),

							'vertical-cloud'           => esc_html__( 'Vertical - Cloud', 'bricks' ),
							'vertical-drops'           => esc_html__( 'Vertical - Drops', 'bricks' ),
							'vertical-pixels'          => esc_html__( 'Vertical - Pixels', 'bricks' ),
							'vertical-stroke'          => esc_html__( 'Vertical - Stroke', 'bricks' ),
							'vertical-stroke-2'        => esc_html__( 'Vertical - Stroke #2', 'bricks' ),
							'vertical-tilt'            => esc_html__( 'Vertical - Tilt', 'bricks' ),
							'vertical-triangle'        => esc_html__( 'Vertical - Triangle', 'bricks' ),
							'vertical-triangle-double' => esc_html__( 'Vertical - Triangle double', 'bricks' ),
							'vertical-wave'            => esc_html__( 'Vertical - Wave', 'bricks' ),
							'vertical-waves'           => esc_html__( 'Vertical - Waves', 'bricks' ),
							'vertical-wave-brush'      => esc_html__( 'Vertical - Wave brush', 'bricks' ),
							'vertical-zigzag'          => esc_html__( 'Vertical - Zigzag', 'bricks' ),

							// 'custom' => esc_html__( 'Custom', 'bricks' ), // MAYBE: add custom SVG control
						],
						'placeholder' => esc_html__( 'Select shape', 'bricks' ),
					],

					'fill'            => [
						'label'    => esc_html__( 'Fill color', 'bricks' ),
						'type'     => 'color',
						'required' => [ 'shape', '!=', '' ],
					],

					'front'           => [
						'label'    => esc_html__( 'Front', 'bricks' ),
						'type'     => 'checkbox',
						'inline'   => true,
						'required' => [ 'shape', '!=', '' ],
					],

					'flipHorizontal'  => [
						'label'    => esc_html__( 'Flip horizontal', 'bricks' ),
						'type'     => 'checkbox',
						'inline'   => true,
						'small'    => true,
						'required' => [ 'shape', '!=', '' ],
					],

					'flipVertical'    => [
						'label'    => esc_html__( 'Flip vertical', 'bricks' ),
						'type'     => 'checkbox',
						'inline'   => true,
						'small'    => true,
						'required' => [ 'shape', '!=', '' ],
					],

					'overflow'        => [
						'label'    => esc_html__( 'Overflow', 'bricks' ),
						'type'     => 'checkbox',
						'inline'   => true,
						'small'    => true,
						'required' => [ 'shape', '!=', '' ],
					],

					'height'          => [
						'label'    => esc_html__( 'Height', 'bricks' ),
						'type'     => 'number',
						'units'    => true,
						'required' => [ 'shape', '!=', '' ],
					],

					'width'           => [
						'label'    => esc_html__( 'Width', 'bricks' ),
						'type'     => 'number',
						'units'    => true,
						'required' => [ 'shape', '!=', '' ],
					],

					'rotate'          => [
						'label'    => esc_html__( 'Rotate', 'bricks' ) . ' °',
						'type'     => 'number',
						'unit'     => 'deg',
						'required' => [ 'shape', '!=', '' ],
					],

					'horizontalAlign' => [
						'label'       => esc_html__( 'Horizontal align', 'bricks' ),
						'type'        => 'align-items',
						'exclude'     => 'stretch',
						'inline'      => true,
						'placeholder' => esc_html__( 'Select', 'bricks' ),
						'required'    => [ 'shape', '!=', '' ],
					],

					'verticalAlign'   => [
						'label'       => esc_html__( 'Vertical align', 'bricks' ),
						'type'        => 'justify-content',
						'exclude'     => 'space',
						'inline'      => true,
						'placeholder' => esc_html__( 'Select', 'bricks' ),
						'required'    => [ 'shape', '!=', '' ],
					],

					'top'             => [
						'label'    => esc_html__( 'Top', 'bricks' ),
						'type'     => 'number',
						'units'    => true,
						'required' => [ 'shape', '!=', '' ],
					],

					'right'           => [
						'label'    => esc_html__( 'Right', 'bricks' ),
						'type'     => 'number',
						'units'    => true,
						'required' => [ 'shape', '!=', '' ],
					],

					'bottom'          => [
						'label'    => esc_html__( 'Bottom', 'bricks' ),
						'type'     => 'number',
						'units'    => true,
						'required' => [ 'shape', '!=', '' ],
					],

					'left'            => [
						'label'    => esc_html__( 'Left', 'bricks' ),
						'type'     => 'number',
						'units'    => true,
						'required' => [ 'shape', '!=', '' ],
					],

				],
			];
		}

		// Exclude background video control from non-layout elements
		if ( ! $this->is_layout_element() ) {
			$this->controls['_background']['exclude'] = [
				'videoUrl',
				'videoScale',
				'videoAspectRatio',
			];
		}

		// GRADIENT

		$this->controls['_gradient'] = [
			'tab'   => 'style',
			'group' => '_gradient',
			'type'  => 'gradient',
			'css'   => [
				// @since 1.5.1
				[
					'selector' => '',
					'property' => 'position',
					'value'    => 'relative',
				],
				[
					'property' => 'background-image',
				],
			],
		];

		// BORDER

		$this->controls['_border'] = [
			'tab'   => 'style',
			'group' => '_border',
			'type'  => 'border',
			'label' => esc_html__( 'Border', 'bricks' ),
			'css'   => [
				[
					'property' => 'border',
				],
			],
		];

		$this->controls['_boxShadow'] = [
			'tab'   => 'style',
			'group' => '_border',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
				],
			],
		];

		// TRANSFORM

		$this->controls['_transform'] = [
			'tab'         => 'style',
			'group'       => '_transform',
			'type'        => 'transform',
			'label'       => esc_html__( 'Transform', 'bricks' ),
			'css'         => [
				[
					'property' => 'transform',
				],
			],
			'inline'      => true,
			'small'       => true,
			'description' => sprintf( '<a href="https://developer.mozilla.org/en-US/docs/Web/CSS/transform" target="_blank" rel="noopener">%s</a>', esc_html__( 'Learn more about CSS transform', 'bricks' ) ),
		];

		$this->controls['_transformOrigin'] = [
			'tab'            => 'style',
			'group'          => '_transform',
			'type'           => 'text',
			'label'          => esc_html__( 'Transform origin', 'bricks' ),
			'css'            => [
				[
					'property' => 'transform-origin',
				],
			],
			'inline'         => true,
			'hasDynamicData' => false,
			'description'    => sprintf( '<a href="https://developer.mozilla.org/en-US/docs/Web/CSS/transform-origin" target="_blank" rel="noopener">%s</a>', esc_html__( 'Learn more about CSS transform-origin', 'bricks' ) ),
			'placeholder'    => 'center',
		];

		// CSS

		$this->controls['_cssFilters'] = [
			'tab'           => 'style',
			'group'         => '_css',
			'label'         => esc_html__( 'CSS Filters', 'bricks' ),
			'titleProperty' => 'type',
			'type'          => 'filters',
			'inline'        => true,
			'small'         => true,
			'css'           => [
				[
					'property' => 'filter',
					// 'selector' => '.css-filter', // @since 1.5 (apply to element root to work on every element)
				],
			],
			'description'   => sprintf( '<a target="_blank" href="https://developer.mozilla.org/en-US/docs/Web/CSS/filter#Syntax">%s</a>', esc_html__( 'Learn more about CSS filters', 'bricks' ) ),
		];

		$this->controls['_cssTransition'] = [
			'tab'            => 'style',
			'group'          => '_css',
			'label'          => esc_html__( 'CSS transition', 'bricks' ),
			'class'          => 'ltr',
			'css'            => [
				[
					'property' => 'transition',
					'selector' => isset( $this->css_selector ) ? $this->css_selector : '',
				],
			],
			'type'           => 'text',
			'hasDynamicData' => false,
			'placeholder'    => 'all 0.2s ease-in',
			'description'    => sprintf( '<a href="https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Transitions/Using_CSS_transitions" target="_blank">%s</a>', esc_html__( 'Learn more about CSS transitions', 'bricks' ) ),
		];

		$this->controls['_cssCustom'] = [
			'tab'         => 'style',
			'group'       => '_css',
			'label'       => esc_html__( 'Custom CSS', 'bricks' ),
			'type'        => 'code',
			'pasteStyles' => true,
			'css'         => [], // NOTE: Undocumented (@since 1.5.1) return true instead of array with 'property' and 'selector' data to output as plain CSS
			'description' => esc_html__( 'Use "root" to target the element wrapper: root { background: blue }', 'bricks' ),
		];

		$this->controls['_cssClasses'] = [
			'tab'            => 'style',
			'group'          => '_css',
			'label'          => esc_html__( 'CSS classes', 'bricks' ),
			'class'          => 'ltr',
			'type'           => 'text',
			'hasDynamicData' => false,
			'description'    => esc_html__( 'Separated by space. Without class dot.', 'bricks' ),
		];

		$this->controls['_cssId'] = [
			'tab'            => 'style',
			'group'          => '_css',
			'label'          => esc_html__( 'CSS ID', 'bricks' ),
			'class'          => 'ltr',
			'type'           => 'text',
			'hasDynamicData' => false,
			'description'    => esc_html__( 'No spaces. No pound (#) sign.', 'bricks' ),
		];

		// ATTRIBUTES

		 $this->controls['_attributes'] = [
			 'tab'           => 'style',
			 'group'         => '_attributes',
			 'placeholder'   => esc_html__( 'Attributes', 'bricks' ),
			 'type'          => 'repeater',
			 'titleProperty' => 'name',
			 'fields'        => [
				 'name'  => [
					 'label'    => esc_html__( 'Name', 'bricks' ),
					 'type'     => 'text',
					 'rerender' => false,
				 ],
				 'value' => [
					 'label'    => esc_html__( 'Value', 'bricks' ),
					 'type'     => 'text',
					 'rerender' => false,
				 ],
			 ],
		 ];

		 $this->controls['infoAttributes'] = [
			 'tab'     => 'style',
			 'group'   => '_attributes',
			 'content' => sprintf( esc_html__( '%s will be added to the most relevant HTML node.', 'bricks' ), Helpers::article_link( 'custom-attributes', esc_html__( 'Custom attributes', 'bricks' ) ) ),
			 'type'    => 'info',
		 ];
	}

	/**
	 * Controls used by all elements under 'style' tab
	 *
	 * @since 1.0
	 */
	public function set_controls_after() {
		// ANIMATION

		$this->controls['_animationSeparator'] = [
			'tab'   => 'style',
			'group' => '_layout',
			'label' => esc_html__( 'Animation', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['_animation'] = [
			'tab'         => 'style',
			'group'       => '_layout',
			'label'       => esc_html__( 'Entry animation', 'bricks' ),
			'type'        => 'select',
			'searchable'  => true,
			'options'     => [
				'bounce'             => 'bounce',
				'flash'              => 'flash',
				'pulse'              => 'pulse',
				'rubberBand'         => 'rubberBand',
				// 'shake'              => 'shake', // deprecated on Bricks 1.5
				'shakeX'             => 'shakeX',
				'shakeY'             => 'shakeY',
				'headShake'          => 'headShake',
				'swing'              => 'swing',
				'tada'               => 'tada',
				'wobble'             => 'wobble',
				'jello'              => 'jello',
				'heartBeat'          => 'heartBeat',

				'backInDown'         => 'backInDown',
				'backInLeft'         => 'backInLeft',
				'backInRight'        => 'backInRight',
				'backInUp'           => 'backInUp',

				'backOutDown'        => 'backOutDown',
				'backOutLeft'        => 'backOutLeft',
				'backOutRight'       => 'backOutRight',
				'backOutUp'          => 'backOutUp',

				'bounceIn'           => 'bounceIn',
				'bounceInDown'       => 'bounceInDown',
				'bounceInLeft'       => 'bounceInLeft',
				'bounceInRight'      => 'bounceInRight',
				'bounceInUp'         => 'bounceInUp',

				'bounceOut'          => 'bounceOut',
				'bounceOutDown'      => 'bounceOutDown',
				'bounceOutLeft'      => 'bounceOutLeft',
				'bounceOutRight'     => 'bounceOutRight',
				'bounceOutUp'        => 'bounceOutUp',

				'fadeIn'             => 'fadeIn',
				'fadeInDown'         => 'fadeInDown',
				'fadeInDownBig'      => 'fadeInDownBig',
				'fadeInLeft'         => 'fadeInLeft',
				'fadeInLeftBig'      => 'fadeInLeftBig',
				'fadeInRight'        => 'fadeInRight',
				'fadeInRightBig'     => 'fadeInRightBig',
				'fadeInUp'           => 'fadeInUp',
				'fadeInUpBig'        => 'fadeInUpBig',
				'fadeInTopLeft'      => 'fadeInTopLeft',
				'fadeInTopRight'     => 'fadeInTopRight',
				'fadeInBottomLeft'   => 'fadeInBottomLeft',
				'fadeInBottomRight'  => 'fadeInBottomRight',

				'fadeOut'            => 'fadeOut',
				'fadeOutDown'        => 'fadeOutDown',
				'fadeOutDownBig'     => 'fadeOutDownBig',
				'fadeOutLeft'        => 'fadeOutLeft',
				'fadeOutLeftBig'     => 'fadeOutLeftBig',
				'fadeOutRight'       => 'fadeOutRight',
				'fadeOutRightBig'    => 'fadeOutRightBig',
				'fadeOutUp'          => 'fadeOutUp',
				'fadeOutUpBig'       => 'fadeOutUpBig',
				'fadeOutTopLeft'     => 'fadeOutTopLeft',
				'fadeOutTopRight'    => 'fadeOutTopRight',
				'fadeOutBottomRight' => 'fadeOutBottomRight',
				'fadeOutBottomLeft'  => 'fadeOutBottomLeft',

				'flip'               => 'flip',
				'flipInX'            => 'flipInX',
				'flipInY'            => 'flipInY',
				'flipOutX'           => 'flipOutX',
				'flipOutY'           => 'flipOutY',

				// 'lightSpeedIn'       => 'lightSpeedIn', // deprecated on Bricks 1.5
				// 'lightSpeedOut'      => 'lightSpeedOut', // deprecated on Bricks 1.5
				'lightSpeedInRight'  => 'lightSpeedInRight',
				'lightSpeedInLeft'   => 'lightSpeedInLeft',
				'lightSpeedOutRight' => 'lightSpeedOutRight',
				'lightSpeedOutLeft'  => 'lightSpeedOutLeft',

				'rotateIn'           => 'rotateIn',
				'rotateInDownLeft'   => 'rotateInDownLeft',
				'rotateInDownRight'  => 'rotateInDownRight',
				'rotateInUpLeft'     => 'rotateInUpLeft',
				'rotateInUpRight'    => 'rotateInUpRight',

				'rotateOut'          => 'rotateOut',
				'rotateOutDownLeft'  => 'rotateOutDownLeft',
				'rotateOutDownRight' => 'rotateOutDownRight',
				'rotateOutUpLeft'    => 'rotateOutUpLeft',
				'rotateOutUpRight'   => 'rotateOutUpRight',

				'hinge'              => 'hinge',
				'jackInTheBox'       => 'jackInTheBox',
				'rollIn'             => 'rollIn',
				'rollOut'            => 'rollOut',

				'zoomIn'             => 'zoomIn',
				'zoomInDown'         => 'zoomInDown',
				'zoomInLeft'         => 'zoomInLeft',
				'zoomInRight'        => 'zoomInRight',
				'zoomInUp'           => 'zoomInUp',

				'zoomOut'            => 'zoomOut',
				'zoomOutDown'        => 'zoomOutDown',
				'zoomOutLeft'        => 'zoomOutLeft',
				'zoomOutRight'       => 'zoomOutRight',
				'zoomOutUp'          => 'zoomOutUp',

				'slideInUp'          => 'slideInUp',
				'slideInDown'        => 'slideInDown',
				'slideInLeft'        => 'slideInLeft',
				'slideInRight'       => 'slideInRight',

				'slideOutUp'         => 'slideOutUp',
				'slideOutDown'       => 'slideOutDown',
				'slideOutLeft'       => 'slideOutLeft',
				'slideOutRight'      => 'slideOutRight',
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'None', 'bricks' ),
		];

		$this->controls['_animationDuration'] = [
			'tab'            => 'style',
			'group'          => '_layout',
			'label'          => esc_html__( 'Animation duration', 'bricks' ),
			'type'           => 'select',
			'searchable'     => true,
			'options'        => [
				'very-slow' => esc_html__( 'Very slow', 'bricks' ),
				'slow'      => esc_html__( 'Slow', 'bricks' ),
				'normal'    => esc_html__( 'Normal', 'bricks' ),
				'fast'      => esc_html__( 'Fast', 'bricks' ),
				'very-fast' => esc_html__( 'Very fast', 'bricks' ),
				'custom'    => esc_html__( 'Custom', 'bricks' ),
			],
			'inline'         => true,
			'hasDynamicData' => false,
			'placeholder'    => esc_html__( 'Normal', 'bricks' ) . ' (1s)',
			'required'       => [ '_animation', '!=', '' ],
		];

		$this->controls['_animationDurationCustom'] = [
			'tab'            => 'style',
			'group'          => '_layout',
			'label'          => esc_html__( 'Animation duration', 'bricks' ) . ' (' . esc_html__( 'Custom', 'bricks' ) . ')',
			'type'           => 'text',
			'css'            => [
				[
					'property' => 'animation-duration',
					'selector' => '',
				],
			],
			'inline'         => true,
			'hasDynamicData' => false,
			'info'           => '500ms | 1s',
			'required'       => [ '_animationDuration', '=', 'custom' ],
		];

		$this->controls['_animationDelay'] = [
			'tab'         => 'style',
			'group'       => '_layout',
			'label'       => esc_html__( 'Animation delay', 'bricks' ),
			'type'        => 'text',
			'css'         => [
				[
					'property' => 'animation-delay',
					'selector' => '',
				],
			],
			'inline'      => true,
			'placeholder' => 0,
			'info'        => '500ms | -2.5s',
			'required'    => [ '_animation', '!=', '' ],
		];

	}

	/**
	 * Get default data
	 *
	 * @since 1.0
	 */
	public function get_default_data() {
		return [
			'label'         => $this->label,
			'name'          => $this->name,
			'controls'      => $this->controls,
			'controlGroups' => $this->control_groups,
		];
	}

	/**
	 * Builder: Element placeholder HTML
	 *
	 * @since 1.0
	 */
	final public function render_element_placeholder( $data ) {
		if ( $this->is_frontend ) {
			return;
		}

		if ( ! isset( $data['icon-class'] ) ) {
			$data['icon-class'] = $this->icon;
		}

		// For custom context menu
		$data['id'] = $this->id;

		echo Helpers::get_element_placeholder( $data );
	}

	/**
	 * Return element ID
	 *
	 * @since 1.5
	 */
	public function get_element_id( $settings ) {
		return empty( $settings['_cssId'] ) ? "brxe-{$this->id}" : sanitize_html_class( $settings['_cssId'] );
	}

	/**
	 * Set element root attributes (element ID, classes, etc.)
	 *
	 * @since 1.4
	 *
	 * @return array/string
	 */
	public function set_root_attributes() {
		$element    = $this->element;
		$nestable   = $this->nestable;
		$element_id = $this->id;
		$settings   = ! empty( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : [];
		$attributes = [];

		$has_css_settings = self::has_css_settings( $settings );

		// Parent element is 'slider-nested' & 'pagination' is enabled: Ensure slide 'id' is added (needed for 'aria-controls' a11y)
		if ( $nestable ) {
			$parent_id = ! empty( $element['parent'] ) ? $element['parent'] : false;

			if ( $parent_id ) {
				$parent_element = ! empty( Frontend::$elements[ $parent_id ] ) ? Frontend::$elements[ $parent_id ] : false;

				if ( $parent_element ) {
					if (
						isset( $parent_element['name'] ) &&
						$parent_element['name'] === 'slider-nested' &&
						isset( $parent_element['settings']['pagination'] )
					) {
						$has_css_settings = true;
					}
				}
			}
		}

		/**
		 * STEP: Add element ID
		 *
		 * Not inside query loop; Not a global element; has CSS setting; has no custom ID set (could be indicator of anchor link to this element)
		 */
		if (
			( ! Query::is_looping() && empty( $element['global'] ) && $has_css_settings ) ||
			! empty( $settings['_cssId'] )
		) {
			$attributes['id'] = $this->get_element_id( $settings );
		}

		// STEP: Add element classes
		$classes = [];

		// Query loop item: Use class name instead of ID as main selector (every item uses same styling rules)
		// Always add element class as loop item element can contain CSS settings.
		if ( Query::is_looping() ) {
			$classes[] = "brxe-{$element_id}";
		}

		// Global element: Use class name instead of ID as main selector (global element can occur multiple times on a page)
		elseif ( ! empty( $element['global'] ) ) {
			if ( $has_css_settings ) {
				$classes[] = "brxe-{$element['global']}";
			}
		}

		$classes[] = sanitize_html_class( "brxe-{$this->name}" );

		// Element global classes
		if ( ! empty( $settings['_cssGlobalClasses'] ) ) {
			$classes = array_merge( $classes, self::get_element_global_classes( $settings['_cssGlobalClasses'] ) );
		}

		// STEP: data-loop
		if ( Query::is_looping() ) {
			// Custom element ID, transform it into a class
			if ( ! empty( $settings['_cssId'] ) ) {
				$classes[] = sanitize_html_class( $settings['_cssId'] );
			}

			$loop_index = Query::get_loop_index();

			/**
			 * First query loop result: Provide element ID via 'data-loop-element-id' attribute so that render knows which elements should re-render
			 *
			 * To render correct HTML for elements with PHP render() functions (no x-template).
			 *
			 * Example: "Post title" element.
			 *
			 * TODO NEXT Loop object type 'wooCart' not working @Luis
			 *
			 * @since 1.4
			 */
			if ( bricks_is_builder_call() && $loop_index === 0 && ! $nestable && ! $this->render_builder() ) {
				$attributes['data-loop-element-id'] = $element_id;
			}

			// Add background-image via HTML attribute
			if ( ! empty( $settings['_background']['image']['useDynamicData'] ) ) {
				$images     = Integrations\Dynamic_Data\Providers::render_tag( $settings['_background']['image']['useDynamicData'], get_the_ID(), 'image' );
				$image_id   = ! empty( $images[0] ) ? $images[0] : false;
				$image_size = ! empty( $settings['_background']['image']['size'] ) ? $settings['_background']['image']['size'] : 'full';

				if ( $image_id ) {
					$url = is_numeric( $image_id ) ? wp_get_attachment_image_url( $image_id, $image_size ) : $image_id;
				} else {
					$url = false;
				}
			} else {
				$url = ! empty( $settings['_background']['image']['url'] ) ? $settings['_background']['image']['url'] : false;
			}

			// Add the style if the $url is empty in a builder call to override the default image (@since 1.5)
			if ( $url || bricks_is_builder_call() ) {
				$background_style = "background-image: url(\"$url\")";

				// Add background-size: cover default
				if ( empty( $settings['_background']['size'] ) ) {
					$background_style .= '; background-size: cover';
				}

				// Lazy load background-image?
				$attributes[ $this->lazy_load() ? 'data-style' : 'style' ] = $background_style;
			}
		}

		/**
		 * Container link (class, href, rel, target)
		 *
		 * @since 1.2.1
		 */
		if ( $nestable && ! empty( $settings['link']['type'] ) ) {
			$this->set_link_attributes( 'link', $settings['link'] );

			$container_link = isset( $this->attributes['link'] ) ? $this->attributes['link'] : [];

			foreach ( $container_link as $key => $value ) {
				if ( $key === 'class' ) {
					$classes = array_merge( $classes, $value );

					continue;
				}

				if ( is_array( $value ) && count( $value ) ) {
					$value = $value[0];
				}

				$attributes[ $key ] = $value;
			}
		}

		// Custom classes (Control group: CSS)
		if ( ! empty( $settings['_cssClasses'] ) ) {
			$classes[] = $settings['_cssClasses'];
		}

		// Custom '_hidden' classes (@since 1.5)
		if ( ! empty( $settings['_hidden']['_cssClasses'] ) ) {
			$classes[] = $settings['_hidden']['_cssClasses'];
		}

		/**
		 * Set & use 'data-script-id' attribute to init scripts with (bricksSplide, bricksSwiper, etc.)
		 *
		 * Generate & use random script ID inside query loop.
		 *
		 * @since 1.4
		 */
		if ( ! empty( $this->scripts ) ) {
			$attributes['data-script-id'] = Query::is_any_looping() ? Helpers::generate_random_id( false ) : $this->id;
		}

		// Entry animation
		if ( ! empty( $settings['_animation'] ) ) {
			$classes[] = 'brx-animated';

			if ( ! empty( $settings['_animationDuration'] ) ) {
				$classes[] = sanitize_html_class( "animation-duration-{$settings['_animationDuration']}" );
			}

			$attributes['data-animation'] = $settings['_animation'];
		}

		// Frontend: Lazy load nestable background images (section, container, block, div, etc.)
		if ( $this->lazy_load() && $nestable ) {
			$classes[] = 'bricks-lazy-hidden';
		}

		$attributes['class'] = $classes;

		/**
		 * Add custom attributes (unless element has $custom_attributes = false like "Nav Menu")
		 */
		if ( ! empty( $settings['_attributes'] ) && $this->custom_attributes === true ) {
			// Add custom attributes (overwrites existing $attributes if needed)
			$custom_attributes = $this->get_custom_attributes( $settings );

			if ( is_array( $custom_attributes ) ) {
				foreach ( $custom_attributes as $att_key => $att_val ) {
					$attributes[ $att_key ] = $att_val;
				}
			}
		}

		// NOTE: Undocumented
		$attributes = apply_filters( 'bricks/element/set_root_attributes', $attributes, $this );

		$this->attributes['_root'] = $attributes;
	}

	/**
	 * Return true if element has 'css' settings
	 *
	 * @return boolean
	 *
	 * @since 1.5
	 */
	public function has_css_settings( $settings ) {
		// Builder: Always add element 'id' & class (needed in query loop to render 2..last items)
		if ( bricks_is_builder_call() ) {
			return true;
		}

		/**
		 * Always add element 'id' for the following elements:
		 *
		 * Nav menu: to add 'mobileMenu' <style> tag to <head> which contain element 'id'
		 *
		 * @since 1.5.1
		 */
		if ( $this->name === 'nav-menu' ) {
			return true;
		}

		// Experimental element ID & class setting not enabled: Always add element ID & class
		if ( ! isset( Database::$global_settings['elementAttsAsNeeded'] ) ) {
			return true;
		}

		$has_css_settings = false;

		$element_id = $this->get_element_id( $settings );

		// STEP: Check for 'css' setting
		foreach ( $settings as $key => $value ) {
			// Remove pseudo class & breapkoint keys to get plain control
			if ( $key && strpos( $key, ':' ) ) {
				$control_key_parts = explode( ':', $key );

				// First part is plain control key
				if ( count( $control_key_parts ) > 1 ) {
					$key = $control_key_parts[0];
				}
			}

			$control = ! empty( $this->controls[ $key ] ) ? $this->controls[ $key ] : false;

			// Check for breakpoint settings
			if ( ! $control ) {
				foreach ( Breakpoints::$breakpoints as $bp ) {
					$breakpoint_key = $bp['key'];

					// Setting contains breakpoint key (e.g.: "_background:tablet_portrait")
					if ( $breakpoint_key !== 'desktop' && strpos( $key, ":$breakpoint_key" ) ) {
						$has_css_settings = true;
						break;
					}
				}
			}

			if ( ! $control ) {
				continue;
			}

			// Loop over repeater items to see if it contains any CSS settings
			if ( ! empty( $control['type'] ) && $control['type'] === 'repeater' ) {
				if ( is_array( $value ) ) {
					foreach ( $value as $repeater_item ) {
						if ( is_array( $repeater_item ) ) {
							foreach ( $repeater_item as $repeater_key => $repeater_value ) {
								$repeater_control = ! empty( $this->controls[ $key ]['fields'][ $repeater_key ] ) ? $this->controls[ $key ]['fields'][ $repeater_key ] : false;

								if ( isset( $repeater_control['css'] ) ) {
									$has_css_settings = true;
								}
							}
						}
					}
				}
			}

			// Icon has 'css' property, but only used if 'svg' option is selected
			if ( ! empty( $control['type'] ) && $control['type'] === 'icon' ) {
				// Skip icon font
				if ( ! empty( $value['icon'] ) ) {
					continue;
				}

				// Return true: Property besides 'library' and 'svg' set (e.g. height, width, etc.)
				if ( ! empty( $value['svg'] ) && count( $value['svg'] ) > 2 ) {
					$has_css_settings = true;
					break;
				}
			}

			// Is CSS control
			if ( isset( $control['css'] ) ) {
				$has_css_settings = true;
				break;
			}

			// Check for element ID use in custom CSS
			if ( $key === '_cssCustom' ) {
				if ( strpos( $value, $element_id ) !== false ) {
					$has_css_settings = true;
					break;
				}
			}
		}

		if ( $has_css_settings ) {
			return true;
		}

		// STEP: Global settings 'customCss' contain element ID
		if ( ! empty( Database::$global_settings['customCss'] ) && strpos( Database::$global_settings['customCss'], $element_id ) !== false ) {
			return true;
		}

		// STEP: Page settings 'customCss' contain element ID
		if ( ! empty( Database::$page_settings['customCss'] ) && strpos( Database::$page_settings['customCss'], $element_id ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Convert the global classes ids into the classes names
	 *
	 * @param array $class_ids The global classes ids
	 * @return array
	 */
	public static function get_element_global_classes( $class_ids ) {
		$global_classes = Database::$global_data['globalClasses'];

		if ( empty( $global_classes ) || empty( $class_ids ) ) {
			return [];
		}

		$element_classes = [];

		$class_ids_names = wp_list_pluck( $global_classes, 'name', 'id' );

		foreach ( $class_ids as $class_id ) {
			if ( ! isset( $class_ids_names[ $class_id ] ) ) {
				continue;
			}

			$element_classes[] = $class_ids_names[ $class_id ];
		}

		return $element_classes;
	}

	/**
	 * Set HTML element attribute + value(s)
	 *
	 * @param string       $key         Element identifier.
	 * @param string       $attribute   Attribute to set value(s) for.
	 * @param string|array $value       Set single value (string) or values (array).
	 *
	 * @since 1.0
	 */
	public function set_attribute( $key, $attribute, $value = null ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $val ) {
				$this->attributes[ $key ][ $attribute ][] = $val;
			}
		} else {
			if ( empty( $value ) ) {
				$this->attributes[ $key ][ $attribute ] = '';
			} else {
				$this->attributes[ $key ][ $attribute ][] = $value;
			}
		}
	}

	/**
	 * Set link attributes
	 *
	 * Helper to set attributes for control type 'link'
	 *
	 * @since 1.0
	 *
	 * @param string $attribute_key Desired key for set_attribute.
	 * @param string $link_settings Element control type 'link' settings.
	 */
	public function set_link_attributes( $attribute_key, $link_settings ) {
		if ( isset( $link_settings['type'] ) ) {
			// Trigger popup via link type
			$link_type = strpos( $link_settings['type'], 'lightbox' ) !== false ? 'lightbox' : $link_settings['type'];

			if ( $link_type === 'lightbox' ) {
				$this->set_attribute( $attribute_key, 'data-link', $link_type );
			}

			if ( $link_settings['type'] === 'internal' && isset( $link_settings['postId'] ) ) {
				$permalink = get_the_permalink( $link_settings['postId'] );

				$this->set_attribute( $attribute_key, 'href', $permalink );
			}

			if ( $link_settings['type'] === 'external' && isset( $link_settings['url'] ) ) {
				$this->set_attribute(
					$attribute_key,
					'href',
					Integrations\Dynamic_Data\Providers::render_content( $link_settings['url'], get_the_ID(), 'link' )
				);
			}

			if ( $link_settings['type'] === 'lightboxImage' && isset( $link_settings['lightboxImage'] ) ) {
				if ( ! empty( $link_settings['lightboxImage']['useDynamicData'] ) ) {
					$image_size = isset( $link_settings['lightboxImage']['size'] ) ? $link_settings['lightboxImage']['size'] : BRICKS_DEFAULT_IMAGE_SIZE;

					$images = Integrations\Dynamic_Data\Providers::render_tag( $link_settings['lightboxImage']['useDynamicData'], get_the_ID(), 'image', [ 'size' => $image_size ] );

					if ( ! empty( $images[0] ) ) {
						$lightbox_image_url = is_numeric( $images[0] ) ? wp_get_attachment_image_url( $images[0], $image_size ) : $images[0];
					} else {
						$lightbox_image_url = '';
					}
				} else {
					$lightbox_image_url = isset( $link_settings['lightboxImage']['url'] ) ? $link_settings['lightboxImage']['url'] : '';
				}

				$this->set_attribute( $attribute_key, 'data-bricks-lightbox-image-url', $lightbox_image_url );
			}

			if ( $link_settings['type'] === 'lightboxVideo' && isset( $link_settings['lightboxVideo'] ) ) {
				$this->set_attribute( $attribute_key, 'data-bricks-lightbox-video-url', $link_settings['lightboxVideo'] );
			}

			if ( $link_settings['type'] === 'meta' && ! empty( $link_settings['useDynamicData'] ) ) {
				$this->set_attribute(
					$attribute_key,
					'href',
					Integrations\Dynamic_Data\Providers::render_content( $link_settings['useDynamicData'], get_the_ID(), 'link' )
				);
			}

			if ( $link_settings['type'] === 'media' && isset( $link_settings['mediaData']['id'] ) ) {
				$this->set_attribute( $attribute_key, 'href', wp_get_attachment_url( $link_settings['mediaData']['id'] ) );
			}

			if ( isset( $link_settings['rel'] ) ) {
				$this->set_attribute( $attribute_key, 'rel', $link_settings['rel'] );
			}

			if ( isset( $link_settings['newTab'] ) ) {
				$this->set_attribute( $attribute_key, 'target', '_blank' );
			}

			if ( isset( $link_settings['ariaLabel'] ) ) {
				$this->set_attribute( $attribute_key, 'aria-label', esc_attr( $link_settings['ariaLabel'] ) );
			}

			if ( isset( $link_settings['title'] ) ) {
				$this->set_attribute( $attribute_key, 'title', esc_attr( $link_settings['title'] ) );
			}
		}
	}

	/**
	 * Remove attribute
	 *
	 * @since 1.0
	 *
	 * @param string      $key        Element identifier.
	 * @param string      $attribute  Attribute to remove.
	 * @param string|null $value Set to remove single value instead of entire attribute.
	 */
	public function remove_attribute( $key, $attribute, $value = null ) {
		if ( ! isset( $this->attributes[ $key ] ) || ! is_array( $this->attributes[ $key ] ) ) {
			return;
		}

		if ( isset( $value ) ) {
			// Remove single attribute value
			$key_to_remove = array_search( $value, $this->attributes[ $key ][ $attribute ] );
			array_splice( $this->attributes[ $key ][ $attribute ], $key_to_remove, 1 );
		} else {
			// Remove entire attribute
			$key_to_remove = array_search( $attribute, $this->attributes[ $key ] );
			array_splice( $this->attributes[ $key ], $key_to_remove, 1 );
		}
	}

	/**
	 * Render HTML attributes for specific element
	 *
	 * @param string  $key                   Attribute identifier
	 * @param boolean $add_custom_attributes true to get custom atts for elements where we don't add them to the wrapper (Nav Menu)
	 *
	 * @since 1.0
	 *
	 * @param string  $key HTML element identifier to render attributes for.
	 */
	public function render_attributes( $key, $add_custom_attributes = false ) {
		// @see: https://academy.bricksbuilder.io/article/filter-bricks-element-render_attributes/ (@since 1.3.7)
		$attributes = apply_filters( 'bricks/element/render_attributes', $this->attributes, $key, $this );

		// Return: No attributes set for this element
		if ( ! isset( $attributes[ $key ] ) ) {
			return;
		}

		$attribute_strings = [];

		// Add custom attributes and overwrite existing $attributes
		if ( $add_custom_attributes ) {
			$custom_attributes = $this->get_custom_attributes( $this->settings );

			if ( is_array( $custom_attributes ) ) {
				foreach ( $custom_attributes as $att_key => $att_val ) {
					$attributes[ $key ][ $att_key ] = $att_val;
				}
			}
		}

		foreach ( $attributes[ $key ] as $key => $value ) {
			// Empty, non-numeric value
			if ( empty( $value ) && ! is_numeric( $value ) ) {
				$attribute_strings[] = $key;
			}

			// Array value
			else {
				if ( is_array( $value ) ) {
					// Filter out empty values
					$value = array_filter(
						$value,
						function( $val ) {
							return ! empty( $val );
						}
					);

					$value = join( ' ', $value );
				}

				$value = substr( $value, 0, 4 ) === 'http' || substr( $value, 0, 2 ) === '//' ? esc_url( $value ) : esc_attr( $value );

				$attribute_strings[] = $key . '="' . $value . '"';
			}
		}

		return join( ' ', $attribute_strings );
	}

	/**
	 * Calculate element custom attributes based on settings (dynamic data too)
	 *
	 * @since 1.3
	 */
	public function get_custom_attributes( $settings = [] ) {
		if ( empty( $settings['_attributes'] ) || ! is_array( $settings['_attributes'] ) ) {
			return [];
		}

		$attributes = [];

		foreach ( $settings['_attributes'] as $index => $field ) {
			if ( ! empty( $field['name'] ) ) {
				$key = sanitize_title( $field['name'] );

				$attributes[ $key ] = isset( $field['value'] ) ? Integrations\Dynamic_Data\Providers::render_content( $field['value'], $this->post_id ) : '';
			}
		}

		return $attributes;
	}

	public static function stringify_attributes( $attributes = [] ) {
		$string = [];

		foreach ( $attributes as $key => $values ) {
			$string[] = $key . '="' . ( is_array( $values ) ? join( ' ', $values ) : $values ) . '"';
		}

		return join( ' ', $string );
	}

	/**
	 * Enqueue element-specific styles and scripts
	 *
	 * @since 1.0
	 */
	public function enqueue_scripts() {}

	/**
	 * Element HTML render
	 *
	 * @since 1.0
	 */
	public function render() {}

	/**
	 * Element HTML render in builder via x-template
	 *
	 * @since 1.0
	 */
	public static function render_builder() {}

	/**
	 * Builder: Get nestable item
	 *
	 * Use as blueprint for nestable children & when adding repeater item.
	 *
	 * @return array First child element.
	 *
	 * @since 1.5
	 */
	public function get_nestable_item() {}

	/**
	 * Builder: Array of child elements added when inserting new nestable element
	 *
	 * @return array Array of child elements.
	 *
	 * @since 1.5
	 */
	public function get_nestable_children() {}

	/**
	 * Frontend: Lazy load (images, videos)
	 *
	 * Global settings 'disableLazyLoad': Disable lazy load altogether
	 * Element settings 'disableLazyLoad': Carousel, slider, testimonials (= bricksSwiper) (@since 1.4)
	 *
	 * @since 1.0
	 */
	public function lazy_load() {
		return $this->is_frontend &&
			! bricks_is_builder_call() &&
			! isset( Database::$global_settings['disableLazyLoad'] ) &&
			! isset( $this->settings['disableLazyLoad'] );
	}

	/**
	 * Enqueue element scripts & styles, set attributes, render
	 *
	 * @since 1.0
	 */
	public function init() {
		// Enqueue scripts & styles
		$this->enqueue_scripts();

		// Set global $post with builder AJAX/REST API submitted postId to retrieve correct post object (unless it is looping)
		if ( Query::is_looping() && Query::get_loop_object_type() == 'post' ) {
			$post_id = Query::get_loop_object_id();
		} else {
			// NOTE: Undocumented
			$post_id = apply_filters( 'bricks/builder/data_post_id', Database::$page_data['preview_or_post_id'] );
		}

		$this->set_post_id( $post_id );

		// Set root attributes
		$this->set_root_attributes();

		if ( get_post_type( $post_id ) === BRICKS_DB_TEMPLATE_SLUG ) {
			$this->setup_query( $post_id );
		}

		// NOTE: Undocumented (@since 1.5 to interject element render)
		$render_element = apply_filters( 'bricks/element/render', true, $this );

		if ( $render_element ) {
			// NOTE: Undocumented (@since 1.5 to interject element settings for translation plugins, etc.)
			$this->settings = apply_filters( 'bricks/element/settings', $this->settings, $this );

			$this->render();
		}

		if ( get_post_type( $post_id ) === BRICKS_DB_TEMPLATE_SLUG ) {
			$this->restore_query();
		}
	}

	/**
	 * Calculate column width
	 */
	public function calc_column_width( $columns_count = 1, $max = false ) {
		$column_width = floor( 100 / intval( $columns_count ) );

		if ( is_int( $max ) && $columns_count > $max ) {
			return floor( 100 / intval( $max ) );
		}

		return $column_width;
	}

	/**
	 * Column width calculator
	 *
	 * @param int $columns Number of columns.
	 * @param int $count   Total amount of items.
	 */
	public function column_width( $columns, $count ) {
		// If more columns are requested than available use $count instead of $columns
		if ( $columns > $count ) {
			$width = 100 / $count;
		} else {
			$width = 100 / $columns;
		}

		return $width;
	}

	/**
	 * Post fields
	 *
	 * Shared between elements: Carousel, Posts, Products, etc.
	 *
	 * @since 1.0
	 */
	public function get_post_fields() {
		$post_controls = [];

		$post_controls['fields'] = [
			'tab'           => 'content',
			'group'         => 'fields',
			'placeholder'   => esc_html__( 'Field', 'bricks' ),
			'type'          => 'repeater',
			'selector'      => 'fieldId',
			'titleProperty' => 'dynamicData',
			'fields'        => [

				'dynamicData'       => [
					'label' => esc_html__( 'Dynamic data', 'bricks' ),
					'type'  => 'text',
				],

				'tag'               => [
					'label'       => esc_html__( 'HTML tag', 'bricks' ),
					'type'        => 'select',
					'options'     => [
						'div' => 'div',
						'p'   => 'p',
						'h1'  => 'h1',
						'h2'  => 'h2',
						'h3'  => 'h3',
						'h4'  => 'h4',
						'h5'  => 'h5',
						'h6'  => 'h6',
					],
					'inline'      => true,
					'placeholder' => 'div',
				],

				'dynamicMargin'     => [
					'label' => esc_html__( 'Margin', 'bricks' ),
					'type'  => 'spacing',
					'css'   => [
						[
							'property' => 'margin',
						],
					],
				],

				'dynamicPadding'    => [
					'label' => esc_html__( 'Padding', 'bricks' ),
					'type'  => 'spacing',
					'css'   => [
						[
							'property' => 'padding',
						],
					],
				],

				'dynamicBackground' => [
					'label' => esc_html__( 'Background color', 'bricks' ),
					'type'  => 'color',
					'css'   => [
						[
							'property' => 'background-color',
						],
					],
				],

				'dynamicBorder'     => [
					'label' => esc_html__( 'Border', 'bricks' ),
					'type'  => 'border',
					'css'   => [
						[
							'property' => 'border',
						],
					],
				],

				'dynamicTypography' => [
					'label' => esc_html__( 'Typography', 'bricks' ),
					'type'  => 'typography',
					'css'   => [
						[
							'property' => 'font',
						],
					],
				],

				// Overlay

				'overlay'           => [
					'label'       => esc_html__( 'Overlay', 'bricks' ),
					'type'        => 'checkbox',
					'inline'      => true,
					'description' => esc_html__( 'Precedes "Link Image" setting.', 'bricks' ),
				],

			],

			'default'       => [
				[
					'dynamicData'   => '{post_title:link}',
					'tag'           => 'h3',
					'dynamicMargin' => [
						'top'    => 20,
						'right'  => 0,
						'bottom' => 20,
						'left'   => 0,
					],
					'id'            => Helpers::generate_random_id( false ),
				],
				[
					'dynamicData' => '{post_excerpt:20}',
					'id'          => Helpers::generate_random_id( false ),
				],
			],
		];

		return $post_controls;
	}

	/**
	 * Post content
	 *
	 * Shared between elements: Carousel, Posts
	 *
	 * @since 1.0
	 */

	public function get_post_content() {
		$post_content = [];

		$post_content['contentAlign'] = [
			'tab'     => 'content',
			'group'   => 'content',
			'type'    => 'select',
			'label'   => esc_html__( 'Alignment', 'bricks' ),
			'options' => [
				'top left'      => esc_html__( 'Top left', 'bricks' ),
				'top center'    => esc_html__( 'Top center', 'bricks' ),
				'top right'     => esc_html__( 'Top right', 'bricks' ),

				'middle left'   => esc_html__( 'Middle left', 'bricks' ),
				'middle center' => esc_html__( 'Middle center', 'bricks' ),
				'middle right'  => esc_html__( 'Middle right', 'bricks' ),

				'bottom left'   => esc_html__( 'Bottom left', 'bricks' ),
				'bottom center' => esc_html__( 'Bottom center', 'bricks' ),
				'bottom right'  => esc_html__( 'Bottom right', 'bricks' ),
			],
			'inline'  => true,
		];

		// NOTE: Necessary as Isotope doesn't play nice with flexbox, but float
		// https://github.com/metafizzy/isotope/issues/1234
		$post_content['contentHeight'] = [
			'tab'   => 'content',
			'group' => 'content',
			'label' => esc_html__( 'Min. height', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'css'   => [
				[
					'property' => 'min-height',
					'selector' => '.content-wrapper',
				],
			],
		];

		$post_content['contentMargin'] = [
			'tab'   => 'content',
			'group' => 'content',
			'type'  => 'spacing',
			'label' => esc_html__( 'Margin', 'bricks' ),
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.content-wrapper',
				],
			],
		];

		$post_content['contentPadding'] = [
			'tab'   => 'content',
			'group' => 'content',
			'type'  => 'spacing',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.content-wrapper',
				],
			],
		];

		$post_content['contentBackground'] = [
			'tab'   => 'content',
			'group' => 'content',
			'type'  => 'color',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.content-wrapper',
				],
			],
		];

		return $post_content;
	}

	/**
	 * Post overlay
	 *
	 * Shared between elements: Carousel, Posts
	 *
	 * @since 1.0
	 */

	public function get_post_overlay() {
		$post_overlay = [];

		$post_overlay['overlayOnHover'] = [
			'tab'         => 'content',
			'group'       => 'overlay',
			'label'       => esc_html__( 'Show on hover', 'bricks' ),
			'type'        => 'checkbox',
			'inline'      => true,
			'small'       => true,
			'description' => esc_html__( 'Always shows in builder for editing.', 'bricks' ),
		];

		$post_overlay['overlayAnimation'] = [
			'tab'      => 'content',
			'group'    => 'overlay',
			'label'    => esc_html__( 'Fade in animation', 'bricks' ),
			'type'     => 'select',
			'options'  => [
				'fade-in-up'    => esc_html__( 'Fade in up', 'bricks' ),
				'fade-in-right' => esc_html__( 'Fade in right', 'bricks' ),
				'fade-in-down'  => esc_html__( 'Fade in down', 'bricks' ),
				'fade-in-left'  => esc_html__( 'Fade in left', 'bricks' ),
				'zoom-in'       => esc_html__( 'Zoom in', 'bricks' ),
				'zoom-out'      => esc_html__( 'Zoom out', 'bricks' ),
			],
			'inline'   => true,
			'required' => [ 'overlayOnHover', '!=', '' ],
		];

		$post_overlay['overlayAlign'] = [
			'tab'     => 'content',
			'group'   => 'overlay',
			'type'    => 'select',
			'label'   => esc_html__( 'Alignment', 'bricks' ),
			'options' => [
				'top left'      => esc_html__( 'Top left', 'bricks' ),
				'top center'    => esc_html__( 'Top center', 'bricks' ),
				'top right'     => esc_html__( 'Top right', 'bricks' ),

				'middle left'   => esc_html__( 'Middle left', 'bricks' ),
				'middle center' => esc_html__( 'Middle center', 'bricks' ),
				'middle right'  => esc_html__( 'Middle right', 'bricks' ),

				'bottom left'   => esc_html__( 'Bottom left', 'bricks' ),
				'bottom center' => esc_html__( 'Bottom center', 'bricks' ),
				'bottom right'  => esc_html__( 'Bottom right', 'bricks' ),
			],
			'inline'  => true,
		];

		$post_overlay['overlayMargin'] = [
			'tab'   => 'content',
			'group' => 'overlay',
			'type'  => 'spacing',
			'label' => esc_html__( 'Margin', 'bricks' ),
			'css'   => [
				[
					'property' => 'margin',
					'selector' => '.overlay-wrapper',
				],
			],
		];

		$post_overlay['overlayPadding'] = [
			'tab'   => 'content',
			'group' => 'overlay',
			'type'  => 'spacing',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.overlay-wrapper',
				],
			],
		];

		$post_overlay['overlayBackground'] = [
			'tab'   => 'content',
			'group' => 'overlay',
			'type'  => 'color',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.overlay-wrapper',
				],
			],
		];

		$post_overlay['overlayInnerBackground'] = [
			'tab'   => 'content',
			'group' => 'overlay',
			'type'  => 'color',
			'label' => esc_html__( 'Inner background color', 'bricks' ),
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.overlay-inner',
				],
			],
		];

		return $post_overlay;
	}

	/**
	 * Get swiper controls
	 *
	 * Elements: Carousel, Slider, Team Members.
	 *
	 * @since 1.0
	 */
	public static function get_swiper_controls() {
		$controls['height'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Height', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'height',
					'selector' => '.swiper-slide',
				],
			],
			'placeholder' => 300,
		];

		$controls['gutter'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Spacing', 'bricks' ) . ' (px)',
			'type'        => 'number',
			'placeholder' => 0,
		];

		$controls['imageRatio'] = [
			'tab'       => 'content',
			'group'     => 'settings',
			'label'     => esc_html__( 'Image ratio', 'bricks' ),
			'type'      => 'select',
			'options'   => Setup::$control_options['imageRatio'],
			'default'   => 'ratio-square',
			'clearable' => false,
			'inline'    => true,
		];

		$controls['initialSlide'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Initial slide', 'bricks' ),
			'type'        => 'number',
			'min'         => 0,
			'max'         => 10,
			'placeholder' => 0,
		];

		$controls['slidesToShow'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Items to show', 'bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'max'         => 10,
			'placeholder' => 1,
			'breakpoints' => true, // NOTE: Undocumented (allows setting non-CSS settings per breakpoint: Carousel, Slider, etc.)
		];

		$controls['slidesToScroll'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Items to scroll', 'bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'max'         => 10,
			'placeholder' => 1,
			'breakpoints' => true,
		];

		$controls['effect'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Effect', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'slide'     => esc_html__( 'Slide', 'bricks' ),
				'fade'      => esc_html__( 'Fade', 'bricks' ),
				'cube'      => esc_html__( 'Cube', 'bricks' ),
				'coverflow' => esc_html__( 'Coverflow', 'bricks' ),
				'flip'      => esc_html__( 'Flip', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Slide', 'bricks' ),
			'info'        => __( '"Fade", "Cube", and "Flip" require "Items To Show" set to 1.', 'bricks' ),
		];

		$controls['infinite'] = [
			'tab'     => 'content',
			'group'   => 'settings',
			'label'   => esc_html__( 'Loop', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
			'inline'  => true,
		];

		$controls['centerMode'] = [
			'tab'   => 'style',
			'group' => 'settings',
			'label' => esc_html__( 'Center mode', 'bricks' ),
			'type'  => 'checkbox',
		];

		$controls['disableLazyLoad'] = [
			'tab'   => 'style',
			'group' => 'settings',
			'label' => esc_html__( 'Disable lazy load', 'bricks' ),
			'type'  => 'checkbox',
		];

		$controls['adaptiveHeight'] = [
			'tab'    => 'content',
			'group'  => 'settings',
			'label'  => esc_html__( 'Adaptive height', 'bricks' ),
			'type'   => 'checkbox',
			'inline' => true,
		];

		$controls['autoplay'] = [
			'tab'   => 'content',
			'group' => 'settings',
			'label' => esc_html__( 'Autoplay', 'bricks' ),
			'type'  => 'checkbox',
		];

		$controls['pauseOnHover'] = [
			'tab'      => 'content',
			'group'    => 'settings',
			'label'    => esc_html__( 'Pause on hover', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'autoplay', '!=', '' ],
			'inline'   => true,
		];

		$controls['stopOnLastSlide'] = [
			'tab'      => 'content',
			'group'    => 'settings',
			'label'    => esc_html__( 'Stop on last slide', 'bricks' ),
			'type'     => 'checkbox',
			'info'     => esc_html__( 'No effect with loop enabled', 'bricks' ),
			'required' => [ 'autoplay', '!=', '' ],
			'inline'   => true,
		];

		$controls['autoplaySpeed'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Autoplay delay in ms', 'bricks' ),
			'type'        => 'number',
			'required'    => [ 'autoplay', '!=', '' ],
			'placeholder' => 3000,
		];

		$controls['speed'] = [
			'tab'         => 'content',
			'group'       => 'settings',
			'label'       => esc_html__( 'Animation speed in ms', 'bricks' ),
			'type'        => 'number',
			'min'         => 1,
			'placeholder' => 300,
		];

		// Arrows

		$controls['arrows'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Show arrows', 'bricks' ),
			'type'     => 'checkbox',
			'inline'   => true,
			'rerender' => true,
			'default'  => true,
		];

		$controls['arrowHeight'] = [
			'tab'         => 'content',
			'group'       => 'arrows',
			'label'       => esc_html__( 'Height', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'height',
					'selector' => '.swiper-button',
				],
			],
			'placeholder' => 50,
			'required'    => [ 'arrows', '!=', '' ],
		];

		$controls['arrowWidth'] = [
			'tab'         => 'content',
			'group'       => 'arrows',
			'label'       => esc_html__( 'Width', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'width',
					'selector' => '.swiper-button',
				],
			],
			'placeholder' => 50,
			'required'    => [ 'arrows', '!=', '' ],
		];

		$controls['arrowBackground'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Background', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.swiper-button',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['arrowBorder'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.swiper-button',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['arrowTypography'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.swiper-button',
				],
			],
			'exclude'  => [
				'font-family',
				'font-weight',
				'font-style',
				'text-align',
				'letter-spacing',
				'line-height',
				'text-transform',
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['prevArrowSeparator'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Prev arrow', 'bricks' ),
			'type'     => 'separator',
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['prevArrow'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Prev arrow', 'bricks' ),
			'type'     => 'icon',
			'default'  => [
				'library' => 'ionicons',
				'icon'    => 'ion-ios-arrow-back',
			],
			'css'      => [
				[
					'selector' => '.bricks-swiper-button-prev > *',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
			'rerender' => true,
		];

		$controls['prevArrowTop'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Top', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'top',
					'selector' => '.bricks-swiper-button-prev',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['prevArrowRight'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Right', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'right',
					'selector' => '.bricks-swiper-button-prev',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['prevArrowBottom'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Bottom', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'bottom',
					'selector' => '.bricks-swiper-button-prev',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['prevArrowLeft'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Left', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'left',
					'selector' => '.bricks-swiper-button-prev',
				],
			],
			'default'  => '50px',
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['nextArrowSeparator'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Next arrow', 'bricks' ),
			'type'     => 'separator',
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['nextArrow'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Next arrow', 'bricks' ),
			'type'     => 'icon',
			'default'  => [
				'library' => 'ionicons',
				'icon'    => 'ion-ios-arrow-forward',
			],
			'css'      => [
				[
					'selector' => '.bricks-swiper-button-next > *',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
			'rerender' => true,
		];

		$controls['nextArrowTop'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Top', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'top',
					'selector' => '.bricks-swiper-button-next',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['nextArrowRight'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Right', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'right',
					'selector' => '.bricks-swiper-button-next',
				],
			],
			'default'  => '50px',
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['nextArrowBottom'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Bottom', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'bottom',
					'selector' => '.bricks-swiper-button-next',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		$controls['nextArrowLeft'] = [
			'tab'      => 'content',
			'group'    => 'arrows',
			'label'    => esc_html__( 'Left', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'left',
					'selector' => '.bricks-swiper-button-next',
				],
			],
			'required' => [ 'arrows', '!=', '' ],
		];

		// Dots

		$controls['dots'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Show dots', 'bricks' ),
			'type'     => 'checkbox',
			'inline'   => true,
			'rerender' => true,
		];

		$controls['dotsDynamic'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Dynamic dots', 'bricks' ),
			'type'     => 'checkbox',
			'inline'   => true,
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsVertical'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Vertical', 'bricks' ),
			'type'     => 'checkbox',
			'inline'   => true,
			'css'      => [
				[
					'property' => 'flex-direction',
					'selector' => '.swiper-pagination-bullets',
					'value'    => 'column',
				],
			],
			'rerender' => true,
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsHeight'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Height', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'units'    => [
				'px' => [
					'min' => 1,
					'max' => 100,
				],
			],
			'css'      => [
				[
					'property' => 'height',
					'selector' => '.swiper-pagination-bullet',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsWidth'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Width', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'units'    => [
				'px' => [
					'min' => 1,
					'max' => 100,
				],
			],
			'css'      => [
				[
					'property' => 'width',
					'selector' => '.swiper-pagination-bullet',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsTop'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Top', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'top',
					'selector' => '.bricks-swiper-container + .swiper-pagination-bullets',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsRight'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Right', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'right',
					'selector' => '.bricks-swiper-container + .swiper-pagination-bullets',
				],
				[
					'property' => 'left',
					'value'    => 'auto',
					'selector' => '.bricks-swiper-container + .swiper-pagination-bullets',
				],
				[
					'property' => 'transform',
					'selector' => '.bricks-swiper-container + .swiper-pagination-bullets',
					'value'    => 'translateX(0)',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsBottom'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Bottom', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'bottom',
					'selector' => '.bricks-swiper-container + .swiper-pagination-bullets',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsLeft'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Left', 'bricks' ),
			'type'     => 'number',
			'units'    => true,
			'css'      => [
				[
					'property' => 'left',
					'selector' => '.bricks-swiper-container + .swiper-pagination-bullets',
				],
				[
					'property' => 'transform',
					'selector' => '.bricks-swiper-container + .swiper-pagination-bullets',
					'value'    => 'translateX(0)',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsBorder'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Border', 'bricks' ),
			'type'     => 'border',
			'css'      => [
				[
					'property' => 'border',
					'selector' => '.swiper-pagination-bullet',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsColor'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.swiper-pagination-bullet',
				],
				[
					'property' => 'color',
					'selector' => '.swiper-pagination-bullet',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsActiveColor'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Active color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.swiper-pagination-bullet-active',
				],
				[
					'property' => 'color',
					'selector' => '.swiper-pagination-bullet-active',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		$controls['dotsSpacing'] = [
			'tab'      => 'content',
			'group'    => 'dots',
			'label'    => esc_html__( 'Margin', 'bricks' ),
			'type'     => 'spacing',
			'css'      => [
				[
					'property' => 'margin',
					'selector' => '.swiper-pagination-bullet',
				],
			],
			'required' => [ 'dots', '!=', '' ],
		];

		return $controls;
	}

	/**
	 * Render swiper nav: Navigation (arrows) & pagination (dots)
	 *
	 * Elements: Carousel, Slider, Team Members.
	 *
	 * @param array $options SwiperJS options.
	 *
	 * @since 1.4
	 */
	public function render_swiper_nav( $options = false ) {
		$options = $options ? $options : $this->settings;

		$output = '';

		// Dots (pagination)
		if ( isset( $options['dots'] ) ) {
			$output .= '<div class="swiper-pagination"></div>';
		}

		// ARROWS (navigation)
		if ( isset( $options['arrows'] ) ) {
			// Prev arrow
			$prev_arrow = false;

			// Check: Element & theme style settings
			if ( ! empty( $options['prevArrow'] ) ) {
				$prev_arrow = self::render_icon( $options['prevArrow'] );
			} elseif ( ! empty( Theme_Styles::$active_settings[ $this->name ]['prevArrow'] ) ) {
				$prev_arrow = self::render_icon( Theme_Styles::$active_settings[ $this->name ]['prevArrow'] );
			}

			if ( $prev_arrow ) {
				$output .= '<div class="swiper-button bricks-swiper-button-prev">' . $prev_arrow . '</div>';
			}

			// Next arrow
			$next_arrow = false;

			// Check: Element & theme style settings
			if ( ! empty( $options['nextArrow'] ) ) {
				$next_arrow = self::render_icon( $options['nextArrow'] );
			} elseif ( ! empty( Theme_Styles::$active_settings[ $this->name ]['nextArrow'] ) ) {
				$next_arrow = self::render_icon( Theme_Styles::$active_settings[ $this->name ]['nextArrow'] );
			}

			if ( $next_arrow ) {
				$output .= '<div class="swiper-button bricks-swiper-button-next">' . $next_arrow . '</div>';
			}
		}

		return $output;
	}

	/**
	 * Custom loop builder controls
	 *
	 * Shared between Container, Template, ...
	 *
	 * @since 1.3.7
	 */
	public function get_loop_builder_controls( $group = '' ) {
		$controls = [];

		$controls['hasLoop'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Use query loop', 'bricks' ),
			'type'  => 'checkbox',
		];

		$controls['query'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Query', 'bricks' ),
			'type'     => 'query',
			'popup'    => true,
			'inline'   => true,
			'required' => [ 'hasLoop', '!=', '' ],
		];

		if ( ! empty( $group ) ) {
			foreach ( $controls as $key => $control ) {
				$controls[ $key ]['group'] = $group;
			}
		}

		return $controls;
	}

	/**
	 * Render the query loop trail
	 *
	 * Trail enables infinite scroll
	 *
	 * @since 1.5
	 *
	 * @param Query  $query
	 * @param string $node_key The element key to add the query data attributes (used in the posts element)
	 *
	 * @return string
	 */
	public function render_query_loop_trail( $query, $node_key = '' ) {
		$settings = ! empty( $this->element['settings'] ) ? $this->element['settings'] : [];

		if ( ! $this->is_frontend || bricks_is_rest_call() || ! isset( $settings['query']['infinite_scroll'] ) ) {
			return '';
		}

		$render   = empty( $node_key );
		$node_key = empty( $node_key ) ? 'trail' : $node_key;

		$page = isset( $query->query_vars['paged'] ) ? $query->query_vars['paged'] : 1;

		if ( $page == 1 && $query->max_num_pages == 1 ) {
			return;
		}

		// Classes
		if ( $render ) {
			$this->set_attribute( $node_key, 'class', 'brx-query-trail' );
		}

		$this->set_attribute( $node_key, 'class', 'brx-infinite-scroll' );

		// Element ID
		$this->set_attribute( $node_key, 'data-query-element-id', $this->id );

		// Query vars: needed to make sure the context is the same if the query was merged with the global query (@since 1.5.1)
		$this->set_attribute( $node_key, 'data-query-vars', json_encode( $query->query_vars ) );

		// Pagination
		$this->set_attribute( $node_key, 'data-page', $page );
		$this->set_attribute( $node_key, 'data-max-pages', $query->max_num_pages );

		// Observer margin (only px or %)
		if ( ! empty( $settings['query']['infinite_scroll_margin'] ) ) {
			$offset = $settings['query']['infinite_scroll_margin'];

			if ( strpos( $offset, 'px' ) === false && strpos( $offset, '%' ) === false ) {
				$offset = intval( $offset ) . 'px';
			}

			$this->set_attribute( $node_key, 'data-observer-margin', $offset );
		}

		if ( $render ) {
			echo "<div {$this->render_attributes( 'trail' )}></div>";
		}
	}

	/**
	 * Get the dynamic data for a specific tag
	 *
	 * @param string $tag Dynamic Data tag
	 * @param string $context text, image, media, link
	 * @param array  $args Needed to set the size for image avatar
	 *
	 * @return mixed
	 */
	public function render_dynamic_data_tag( $tag = '', $context = 'text', $args = [] ) {
		$post_id = Query::is_looping() && Query::get_query_object_type() == 'post' ? Query::get_loop_object_id() : $this->post_id;

		return Integrations\Dynamic_Data\Providers::render_tag( $tag, $post_id, $context, $args );
	}

	/**
	 * Render dynamic data tags on a string
	 *
	 * @param string $content
	 * @return mixed
	 */
	public function render_dynamic_data( $content = '' ) {
		$post_id = Query::is_looping() && Query::get_query_object_type() == 'post' ? Query::get_loop_object_id() : $this->post_id;

		return Integrations\Dynamic_Data\Providers::render_content( $content, $post_id );
	}

	/**
	 * Set Post ID
	 *
	 * @param integer $post_id
	 * @return void
	 */
	public function set_post_id( $post_id = 0 ) {
		$this->post_id = $post_id;
	}

	/**
	 * Setup custom query for templates according to 'templatePreviewType'
	 *
	 * To alter builder template and template preview query. NOT the frontend!
	 *
	 * @param integer $post_id
	 *
	 * @since 1.0
	 */
	public function setup_query( $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		// Store $wp_query in $original_query to restore it via restore_query() after element has been rendered
		global $wp_query;

		$this->original_query = $wp_query;

		$query_args = [];

		$template_settings     = Helpers::get_template_settings( $post_id );
		$template_preview_type = Helpers::get_template_setting( 'templatePreviewType', $post_id );

		switch ( $template_preview_type ) {
			// Archive: Recent posts
			case 'archive-recent-posts':
				$query_args['post_type'] = 'post';
				break;

			// Archive: author
			case 'archive-author':
				$template_preview_author = Helpers::get_template_setting( 'templatePreviewAuthor', $post_id );

				if ( $template_preview_author ) {
					$query_args['author'] = $template_preview_author;
				}
				break;

			// Author date
			case 'archive-date':
				$query_args['year'] = date( 'Y' );
				break;

			// Archive CPT
			case 'archive-cpt':
				$template_preview_post_type = Helpers::get_template_setting( 'templatePreviewPostType', $post_id );

				if ( $template_preview_post_type ) {
					$query_args['post_type'] = $template_preview_post_type;
				}
				break;

			// Archive term
			case 'archive-term':
				$template_preview_term_id_parts = isset( $template_settings['templatePreviewTerm'] ) ? explode( '::', $template_settings['templatePreviewTerm'] ) : '';
				$template_preview_taxnomy       = isset( $template_preview_term_id_parts[0] ) ? $template_preview_term_id_parts[0] : '';
				$template_preview_term_id       = isset( $template_preview_term_id_parts[1] ) ? $template_preview_term_id_parts[1] : '';

				if ( $template_preview_taxnomy && $template_preview_term_id ) {
					$query_args['tax_query'] = [
						[
							'taxonomy' => $template_preview_taxnomy,
							'terms'    => $template_preview_term_id,
							'field'    => 'term_id',
						],
					];
				}
				break;

			// Search
			case 'search':
				$template_preview_search_term = Helpers::get_template_setting( 'templatePreviewSearchTerm', $post_id );

				if ( $template_preview_search_term ) {
					$query_args['s'] = $template_preview_search_term;
				}
				break;

			// Single
			case 'single':
				$template_preview_post_id = Helpers::get_template_setting( 'templatePreviewPostId', $post_id );

				// Set post ID to template preview ID
				if ( $template_preview_post_id ) {
					$query_args['p']         = $template_preview_post_id;
					$query_args['post_type'] = get_post_type( $template_preview_post_id );

					// Set the global $post to affect the entire WP environment (needed for WooCommerce)
					global $post;
					$post = get_post( $template_preview_post_id );
					setup_postdata( $post );
				}
				break;
		}

		// NOTE: Undocumented
		$query_args = apply_filters( 'bricks/element/builder_setup_query', $query_args, $post_id );

		// Init query with template preview args
		if ( ! empty( $query_args ) && is_array( $query_args ) ) {
			$wp_query = new \WP_Query( $query_args );
		}
	}

	/**
	 * Restore custom query after element render()
	 *
	 * @param integer $post_id
	 *
	 * @since 1.0
	 */
	public function restore_query() {
		if ( ! $this->original_query ) {
			return;
		}

		global $wp_query;

		$wp_query = $this->original_query;

		// Need to reset the global $post environment because on setup_query() we might have change it
		wp_reset_postdata();
	}

	/**
	 * Render control 'icon' HTML (either font icon 'i' or 'svg' HTML)
	 *
	 * @param array $icon Contains either 'icon' CSS class or 'svg' URL data.
	 * @param array $icon Additional icon HTML attributes.
	 *
	 * @see ControlIcon.vue
	 * @return string SVG HMTL string
	 *
	 * @since 1.2.1
	 */
	public static function render_icon( $icon, $attributes = [] ) {
		if ( ! is_array( $attributes ) ) {
			$attributes = [];
		}

		// Is flat array: Attribute items are the list of class names
		if ( count( $attributes ) === count( $attributes, COUNT_RECURSIVE ) ) {
			$attributes = [
				'class' => $attributes,
			];
		}

		$classes = [];

		// STEP: Render SVG
		$svg_url = ! empty( $icon['svg']['url'] ) ? $icon['svg']['url'] : false;

		if ( $svg_url ) {
			$svg = Helpers::get_file_contents( $svg_url );

			if ( ! $svg ) {
				return;
			}

			if ( isset( $icon['fill'] ) ) {
				$classes[] = 'fill';
			}

			if ( isset( $icon['stroke'] ) ) {
				$classes[] = 'stroke';
			}

			$attributes['class'] = empty( $attributes['class'] ) ? $classes : array_merge( $attributes['class'], $classes );

			// Add attributes to SVG HTML string
			return self::render_svg( $svg, $attributes );
		}

		// STEP: Render icon font
		elseif ( ! empty( $icon['icon'] ) ) {
			$classes[] = $icon['icon'];

			$attributes['class'] = empty( $attributes['class'] ) ? $classes : array_merge( $attributes['class'], $classes );

			$attributes = self::stringify_attributes( $attributes );

			return "<i {$attributes}></i>";
		}
	}

	/**
	 * Add attributes to SVG HTML string
	 *
	 * @since 1.4
	 */
	public static function render_svg( $svg = '', $attributes = [] ) {
		// STEP: Remove any potential "<xml " code before <svg
		$svg_tag_start = strpos( $svg, '<svg ' );
		$svg           = substr_replace( $svg, '', 0, $svg_tag_start );

		// STEP: Remove the custom HTML ID (if any) to avoid conflict with the default element ID
		preg_match( '/<svg ([a-z][a-z0-9]*)[^>]*?(\/?)>/i', $svg, $matches );

		$svg_tag = ! empty( $matches[0] ) ? $matches[0] : false;

		if ( $svg_tag ) {
			$svg_without_id = preg_replace( '/id="([\w-]*)"/i', '', $svg_tag );

			$svg = str_replace( $svg_tag, $svg_without_id, $svg );
		}

		// STEP: add the new attributes
		foreach ( $attributes as $key => $values ) {
			$start = strpos( $svg, $key . '="' );
			$end   = strpos( $svg, '>' );

			$value = is_array( $values ) ? join( ' ', $values ) : $values;

			// Add values to existing attribute
			if ( $start && $start < $end ) {
				$svg = substr_replace( $svg, "$value ", $start + strlen( $key ) + 2, 0 );
			}

			// Create attribute + value on node
			else {
				$attribute_string = $key . '="' . $value . '" ';

				$svg = substr_replace( $svg, $attribute_string, 5, 0 );
			}
		}

		return trim( $svg );
	}

	/**
	 * Change query if we are previewing a CPT archive template (set in-builder via "Populated Content")
	 *
	 * @since 1.4
	 */
	public function maybe_set_preview_query( $query_vars, $settings, $element_id ) {
		$post_id = $this->post_id;

		// Return: Not a template OR no 'post_type' condition set
		if ( get_post_type( $post_id ) !== BRICKS_DB_TEMPLATE_SLUG || ! empty( $query_vars['post_type'] ) ) {
			return $query_vars;
		}

		$preview_type = Helpers::get_template_setting( 'templatePreviewType', $post_id );

		if ( $preview_type === 'archive-cpt' ) {
			$preview_post_type = Helpers::get_template_setting( 'templatePreviewPostType', $post_id );

			if ( $preview_post_type ) {
				$query_vars['post_type'] = $preview_post_type;
			}
		}

		return $query_vars;
	}

	/**
	 * Is layout element: Section, Container, Block, Div
	 *
	 * For element control visibility in builder (flex controls, shape divider, etc.)
	 *
	 * @return boolean
	 *
	 * @since 1.5
	 */
	public function is_layout_element() {
		$layout_element_names = [ 'section', 'container', 'block', 'div' ];

		// NOTE: Undocumented
		$layout_element_names = apply_filters( 'bricks/is_layout_element', $layout_element_names );

		return in_array( $this->name, $layout_element_names );
	}
}
