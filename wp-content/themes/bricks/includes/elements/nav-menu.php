<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Nav_Menu extends Element {
	public $category          = 'wordpress';
	public $name              = 'nav-menu';
	public $icon              = 'ti-menu';
	public $custom_attributes = false;

	public function get_label() {
		return esc_html__( 'Nav Menu', 'bricks' );
	}

	/**
	 * Generate breakpoint-specific @media rules for nav menu & mobile menu toggle visibility
	 *
	 * If not set to 'always' or 'never'
	 *
	 * @since 1.5.1 (allows for truly custom breakpoints)
	 */
	public function generate_mobile_menu_inline_css( $settings = [], $breakpoint = '' ) {
		$breakpoint_width    = ! empty( $breakpoint['width'] ) ? intval( $breakpoint['width'] ) : 0;
		$base_width          = Breakpoints::$base_width;
		$nav_menu_inline_css = '';

		if ( $breakpoint_width ) {
			if ( $breakpoint_width > $base_width ) {
				if ( Breakpoints::$is_mobile_first ) {
					$nav_menu_inline_css .= "@media (max-width: {$breakpoint_width}px) {\n";
				} else {
					$nav_menu_inline_css .= "@media (min-width: {$breakpoint_width}px) {\n";
				}
			} else {
				if ( Breakpoints::$is_mobile_first ) {
					$nav_menu_inline_css .= "@media (min-width: {$breakpoint_width}px) {\n";
				} else {
					$nav_menu_inline_css .= "@media (max-width: {$breakpoint_width}px) {\n";
				}
			}

			$element_id = $this->get_element_id( $this->settings );

			$nav_menu_inline_css .= "#{$element_id} .bricks-nav-menu-wrapper { display: none; }\n";
			$nav_menu_inline_css .= "#{$element_id} .bricks-mobile-menu-toggle { display: inline-block; }\n";

			$nav_menu_inline_css .= '}';
		}

		return $nav_menu_inline_css;
	}

	public function set_control_groups() {
		$this->control_groups['menu'] = [
			'title' => esc_html__( 'Top level menu', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['sub-menu'] = [
			'title' => esc_html__( 'Sub menu', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['mobile-menu'] = [
			'title' => esc_html__( 'Mobile menu', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		// @since 1.4: Apply transitions to menu items
		$this->controls['_cssTransition']['css'] = [
			[
				'property' => 'transition',
				'selector' => '.bricks-nav-menu li',
			],
			[
				'property' => 'transition',
				'selector' => '.bricks-nav-menu li a',
			],
			[
				'property' => 'transition',
				'selector' => '.bricks-mobile-menu li a',
			],
		];

		$nav_menus = [];

		if ( bricks_is_builder() ) {
			foreach ( wp_get_nav_menus() as $menu ) {
				$nav_menus[ $menu->term_id ] = $menu->name;
			}
		}

		$this->controls['menu'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Nav Menu', 'bricks' ),
			'type'        => 'select',
			'options'     => $nav_menus,
			'placeholder' => esc_html__( 'Select nav menu', 'bricks' ),
			'description' => sprintf( '<a href="' . admin_url( 'nav-menus.php' ) . '" target="_blank">' . esc_html__( 'Manage my menus in WordPress.', 'bricks' ) . '</a>' ),
		];

		// Get all breakpoints except base (@since 1.5.1)
		$breakpoints        = Breakpoints::$breakpoints;
		$breakpoint_options = [];

		foreach ( $breakpoints as $index => $breakpoint ) {
			if ( ! isset( $breakpoint['base'] ) ) {
				$breakpoint_options[ $breakpoint['key'] ] = $breakpoint['label'];
			}
		}

		$breakpoint_options['always'] = esc_html__( 'Always', 'bricks' );
		$breakpoint_options['never']  = esc_html__( 'Never', 'bricks' );

		$this->controls['mobileMenu'] = [
			'tab'         => 'content',
			'label'       => Breakpoints::$is_mobile_first ? esc_html__( 'Hide mobile menu toggle', 'bricks' ) : esc_html__( 'Show mobile menu toggle', 'bricks' ),
			'type'        => 'select',
			'options'     => $breakpoint_options,
			'rerender'    => true,
			'placeholder' => esc_html__( 'Mobile landscape', 'bricks' ),
		];

		$this->controls['menuAlignment'] = [
			'tab'    => 'content',
			'label'  => esc_html__( 'Alignment', 'bricks' ),
			'type'   => 'direction',
			'css'    => [
				[
					'property' => 'flex-direction',
					'selector' => '.bricks-nav-menu',
				],
			],
			'inline' => true,
		];

		// Group: Top level menu

		$this->controls['menuMargin'] = [
			'tab'         => 'content',
			'group'       => 'menu',
			'label'       => esc_html__( 'Margin', 'bricks' ),
			'type'        => 'spacing',
			'css'         => [
				[
					'property' => 'margin',
					'selector' => '.bricks-nav-menu > li',
				]
			],
			'placeholder' => [
				'top'    => 0,
				'right'  => 0,
				'bottom' => 0,
				'left'   => 30,
			],
		];

		$this->controls['menuPadding'] = [
			'tab'   => 'content',
			'group' => 'menu',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.bricks-nav-menu > li a',
				]
			],
		];

		$this->controls['menuBackground'] = [
			'tab'   => 'content',
			'group' => 'menu',
			'type'  => 'background',
			'label' => esc_html__( 'Background', 'bricks' ),
			'css'   => [
				[
					'property' => 'background',
					'selector' => '.bricks-nav-menu > li > a',
				],
			],
		];

		$this->controls['menuBorder'] = [
			'tab'   => 'content',
			'group' => 'menu',
			'type'  => 'border',
			'label' => esc_html__( 'Border', 'bricks' ),
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bricks-nav-menu > li > a',
				],
			],
		];

		$this->controls['menuTypography'] = [
			'tab'   => 'content',
			'group' => 'menu',
			'type'  => 'typography',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu > li > a',
				],
			],
		];

		$this->controls['menuActiveBackground'] = [
			'tab'   => 'content',
			'group' => 'menu',
			'label' => esc_html__( 'Active background', 'bricks' ),
			'type'  => 'background',
			'css'   => [
				[
					'property' => 'background',
					'selector' => '.bricks-nav-menu > .current-menu-item > a, .bricks-nav-menu > .current-menu-ancestor > a, .bricks-nav-menu > .current-menu-parent > a',
				],
			],
		];

		$this->controls['menuActiveBorder'] = [
			'tab'   => 'content',
			'group' => 'menu',
			'label' => esc_html__( 'Active border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bricks-nav-menu > .current-menu-item > a, .bricks-nav-menu > .current-menu-ancestor > a, .bricks-nav-menu > .current-menu-parent > a',
				],
			],
		];

		$this->controls['menuActiveTypography'] = [
			'tab'   => 'content',
			'group' => 'menu',
			'label' => esc_html__( 'Active typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu > .current-menu-item > a, .bricks-nav-menu > .current-menu-ancestor > a, .bricks-nav-menu > .current-menu-parent > a',
				],
			],
		];

		// Icon

		$this->controls['menuIcon'] = [
			'tab'      => 'content',
			'group'    => 'menu',
			'label'    => esc_html__( 'Icon', 'bricks' ),
			'type'     => 'icon',
			'css'      => [
				[
					'selector' => '.menu-icon-svg',
				],
			],
			'rerender' => true,
			'info'     => esc_html__( 'Shows if item has a sub menu.', 'bricks' ),
		];

		$this->controls['menuIconTypography'] = [
			'tab'      => 'content',
			'group'    => 'menu',
			'label'    => esc_html__( 'Icon typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu > li.menu-item-has-children > a > i',
				],
			],
			'exclude'  => [
				'font-family',
				'font-weight',
				'font-style',
				'text-align',
				'text-decoration',
				'text-transform',
				'letter-spacing',
			],
			'required' => [ 'menuIcon.icon', '!=', '' ],
		];

		$this->controls['menuIconPosition'] = [
			'tab'         => 'content',
			'group'       => 'menu',
			'label'       => esc_html__( 'Icon position', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['iconPosition'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Right', 'bricks' ),
			'required'    => [ 'menuIcon', '!=', '' ],
		];

		$this->controls['menuIconMargin'] = [
			'tab'      => 'content',
			'group'    => 'menu',
			'label'    => esc_html__( 'Icon margin', 'bricks' ),
			'type'     => 'spacing',
			'css'      => [
				[
					'property' => 'margin',
					'selector' => 'li.menu-item-has-children > a .icon-right',
				],
				[
					'property' => 'margin',
					'selector' => 'li.menu-item-has-children > a .icon-left',
				],
			],
			'required' => [ 'menuIcon', '!=', '' ],
		];

		// Group: 'sub menu'

		// @since 1.4 to apply default nav menu background to 'ul', not 'li'
		$this->controls['subMenuBackgroundList'] = [
			'tab'   => 'content',
			'group' => 'sub-menu',
			'type'  => 'background',
			'label' => esc_html__( 'Background', 'bricks' ),
			'css'   => [
				[
					'property' => 'background',
					'selector' => '.bricks-nav-menu .sub-menu',
				]
			],
		];

		$this->controls['subMenuBorder'] = [
			'tab'   => 'content',
			'group' => 'sub-menu',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bricks-nav-menu .sub-menu',
				]
			],
		];

		$this->controls['subMenuBoxShadow'] = [
			'tab'   => 'content',
			'group' => 'sub-menu',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => '.bricks-nav-menu .sub-menu',
				],
			],
		];

		// Sub menu - Item
		$this->controls['subMenuItemSeparator'] = [
			'tab'   => 'content',
			'group' => 'sub-menu',
			'label' => esc_html__( 'Sub menu', 'bricks' ) . ' - ' . esc_html__( 'Item', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['subMenuPadding'] = [
			'tab'   => 'content',
			'group' => 'sub-menu',
			'type'  => 'spacing',
			'label' => esc_html__( 'Padding', 'bricks' ),
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.bricks-nav-menu .sub-menu > li.menu-item > a',
				],
			],
		];

		$this->controls['subMenuBackground'] = [
			'tab'   => 'content',
			'group' => 'sub-menu',
			'type'  => 'background',
			'label' => esc_html__( 'Background', 'bricks' ),
			'css'   => [
				[
					'property' => 'background',
					'selector' => '.bricks-nav-menu .sub-menu li.menu-item',
				]
			],
		];

		$this->controls['subMenuActiveBackground'] = [
			'tab'   => 'content',
			'group' => 'sub-menu',
			'type'  => 'background',
			'label' => esc_html__( 'Active background', 'bricks' ),
			'css'   => [
				[
					'property' => 'background',
					'selector' => '.bricks-nav-menu .sub-menu > li.current-menu-item',
				]
			],
		];

		$this->controls['subMenuItemBorder'] = [
			'tab'   => 'content',
			'group' => 'sub-menu',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bricks-nav-menu .sub-menu > li',
				],
			],
		];

		$this->controls['subMenuTypography'] = [
			'tab'   => 'content',
			'group' => 'sub-menu',
			'type'  => 'typography',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu .sub-menu > li.menu-item > a',
				],
			],
		];

		$this->controls['subMenuActiveTypography'] = [
			'tab'   => 'content',
			'group' => 'sub-menu',
			'label' => esc_html__( 'Active typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu .sub-menu > .current-menu-item > a',
				],
			],
		];

		$this->controls['subMenuIcon'] = [
			'tab'         => 'content',
			'group'       => 'sub-menu',
			'label'       => esc_html__( 'Icon', 'bricks' ),
			'type'        => 'icon',
			'css'         => [
				[
					'selector' => '.sub-menu-icon-svg',
				],
			],
			'rerender'    => true,
			'description' => esc_html__( 'Shows if item has a sub menu.', 'bricks' ),
		];

		$this->controls['subMenuIconTypography'] = [
			'tab'      => 'content',
			'group'    => 'sub-menu',
			'label'    => esc_html__( 'Icon typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.bricks-nav-menu .sub-menu li.menu-item-has-children > a > i',
				],
			],
			'exclude'  => [
				'font-family',
				'font-weight',
				'font-style',
				'text-align',
				'text-decoration',
				'text-transform',
				'letter-spacing',
			],
			'required' => [ 'subMenuIcon.icon', '!=', '' ],
		];

		$this->controls['subMenuIconPosition'] = [
			'tab'         => 'content',
			'group'       => 'sub-menu',
			'label'       => esc_html__( 'Icon position', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['iconPosition'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Right', 'bricks' ),
			'required'    => [ 'subMenuIcon', '!=', '' ],
		];

		$this->controls['subMenuIconMargin'] = [
			'tab'      => 'content',
			'group'    => 'sub-menu',
			'label'    => esc_html__( 'Icon margin', 'bricks' ),
			'type'     => 'spacing',
			'css'      => [
				[
					'property' => 'margin',
					'selector' => '.bricks-nav-menu .sub-menu li.menu-item-has-children > a .icon-right',
				],
				[
					'property' => 'margin',
					'selector' => '.bricks-nav-menu .sub-menu li.menu-item-has-children > a .icon-left',
				],
			],
			'required' => [ 'subMenuIcon', '!=', '' ],
		];

		// Group: 'mobile menu'

		$this->controls['mobileMenuPosition'] = [
			'tab'         => 'content',
			'group'       => 'mobile-menu',
			'label'       => esc_html__( 'Position', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'right' => esc_html__( 'Right', 'bricks' ),
				'left'  => esc_html__( 'Left', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Left', 'bricks' ),
		];

		$this->controls['mobileMenuFadeIn'] = [
			'tab'   => 'content',
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Fade in', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['mobileMenuAlignment'] = [
			'tab'         => 'content',
			'group'       => 'mobile-menu',
			'label'       => esc_html__( 'Vertical', 'bricks' ),
			'type'        => 'justify-content',
			'exclude'     => 'space',
			'css'         => [
				[
					'property' => 'justify-content',
					'selector' => '.bricks-mobile-menu-wrapper',
				]
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Top', 'bricks' ),
		];

		$this->controls['mobileMenuAlignItems'] = [
			'tab'         => 'content',
			'group'       => 'mobile-menu',
			'label'       => esc_html__( 'Horizontal', 'bricks' ),
			'type'        => 'align-items',
			'exclude'     => 'stretch',
			'css'         => [
				[
					'property' => 'align-items',
					'selector' => '.bricks-mobile-menu-wrapper',
				]
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Top', 'bricks' ),
		];

		$this->controls['mobileMenuWidth'] = [
			'tab'         => 'content',
			'group'       => 'mobile-menu',
			'label'       => esc_html__( 'Width', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'width',
					'selector' => '.bricks-mobile-menu-wrapper',
				],
			],
			'placeholder' => '300px',
		];

		$this->controls['mobileMenuBackground'] = [
			'tab'   => 'content',
			'group' => 'mobile-menu',
			'type'  => 'background',
			'label' => esc_html__( 'Background', 'bricks' ),
			'css'   => [
				[
					'property' => 'background',
					'selector' => '.bricks-mobile-menu-wrapper:before',
				]
			],
		];

		$this->controls['mobileMenuBackgroundFilters'] = [
			'tab'           => 'content',
			'group'         => 'mobile-menu',
			'label'         => esc_html__( 'Background filters', 'bricks' ),
			'titleProperty' => 'type',
			'type'          => 'filters',
			'css'           => [
				[
					'property' => 'filter',
					'selector' => '.bricks-mobile-menu-wrapper:before',
				],
			],
			'inline'        => true,
			'small'         => true,
		];

		$this->controls['mobileMenuBoxShadow'] = [
			'tab'   => 'content',
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => '.bricks-mobile-menu-wrapper:before',
				],
			],
		];

		// Top level mobile menu

		$this->controls['_topMenuSeparator'] = [
			'tab'   => 'content',
			'group' => 'mobile-menu',
			'type'  => 'separator',
			'label' => esc_html__( 'Top level menu', 'bricks' ),
		];

		$this->controls['mobileMenuPadding'] = [
			'tab'         => 'content',
			'group'       => 'mobile-menu',
			'type'        => 'spacing',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'css'         => [
				[
					'property' => 'padding',
					'selector' => '.bricks-mobile-menu > li > a',
				],
			],
			'placeholder' => [
				'top'    => 0,
				'right'  => 30,
				'bottom' => 0,
				'left'   => 30,
			],
		];

		$this->controls['mobileMenuBorder'] = [
			'tab'   => 'content',
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bricks-mobile-menu > li',
				],
			],
		];

		$this->controls['mobileMenuTypography'] = [
			'tab'   => 'content',
			'group' => 'mobile-menu',
			'type'  => 'typography',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-mobile-menu > li > a',
				],
			],
		];

		$this->controls['mobileMenuActiveTypography'] = [
			'tab'   => 'content',
			'group' => 'mobile-menu',
			'type'  => 'typography',
			'label' => esc_html__( 'Active typography', 'bricks' ),
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-mobile-menu > .current-menu-item > a',
				],
			],
		];

		// Toggle sub menu
		$this->controls['mobileMenuIcon'] = [
			'tab'      => 'content',
			'group'    => 'mobile-menu',
			'label'    => esc_html__( 'Icon', 'bricks' ),
			'type'     => 'icon',
			'css'      => [
				[
					'selector' => '.mobile-menu-icon-svg',
				],
			],
			'rerender' => true,
			'info'     => esc_html__( 'Shows if item has a sub menu.', 'bricks' ),
		];

		// Toggle sub menu
		$this->controls['mobileMenuCloseIcon'] = [
			'tab'         => 'content',
			'group'       => 'mobile-menu',
			'label'       => esc_html__( 'Close icon', 'bricks' ),
			'type'        => 'icon',
			'css'         => [
				[
					'selector' => '.mobile-menu-icon-svg',
				],
			],
			'rerender'    => true,
			'description' => esc_html__( 'Shows if item has a sub menu.', 'bricks' ),
			'required'    => [ 'mobileMenuIcon', '!=', '' ],
		];

		$this->controls['mobileMenuIconTypography'] = [
			'tab'      => 'content',
			'group'    => 'mobile-menu',
			'label'    => esc_html__( 'Icon typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.bricks-mobile-menu > li.menu-item-has-children button > i',
				],
			],
			'exclude'  => [
				'font-family',
				'font-weight',
				'font-style',
				'text-align',
				'text-decoration',
				'text-transform',
				'letter-spacing',
			],
			'inline'   => true,
			'small'    => true,
			'required' => [ 'mobileMenuIcon.icon', '!=', '' ],
		];

		$this->controls['mobileMenuIconPosition'] = [
			'tab'         => 'content',
			'group'       => 'mobile-menu',
			'label'       => esc_html__( 'Icon position', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['iconPosition'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Right', 'bricks' ),
			'required'    => [ 'mobileMenuIcon', '!=', '' ],
		];

		$this->controls['mobileMenuIconMargin'] = [
			'tab'      => 'content',
			'group'    => 'mobile-menu',
			'label'    => esc_html__( 'Icon margin', 'bricks' ),
			'type'     => 'spacing',
			'css'      => [
				[
					'property' => 'margin',
					'selector' => '.bricks-mobile-menu li.menu-item-has-children button.bricks-mobile-submenu-toggle',
				]
			],
			'required' => [ 'mobileMenuIcon', '!=', '' ],
		];

		// Mobile menu: Sub menu

		$this->controls['_subMenuSeparator'] = [
			'tab'         => 'content',
			'group'       => 'mobile-menu',
			'type'        => 'separator',
			'label'       => esc_html__( 'Sub menu', 'bricks' ),
			'description' => esc_html__( 'Always shows in builder for you to style.', 'bricks' ),
		];

		$this->controls['mobileSubMenuPadding'] = [
			'tab'         => 'content',
			'group'       => 'mobile-menu',
			'type'        => 'spacing',
			'label'       => esc_html__( 'Padding', 'bricks' ),
			'css'         => [
				[
					'property' => 'padding',
					'selector' => '.bricks-mobile-menu .sub-menu > li.menu-item > a',
				],
			],
			'placeholder' => [
				'top'    => 0,
				'right'  => 45,
				'bottom' => 0,
				'left'   => 45,
			],
		];

		$this->controls['mobileSubMenuBorder'] = [
			'tab'   => 'content',
			'group' => 'mobile-menu',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bricks-mobile-menu .sub-menu > li.menu-item',
				],
			],
		];

		$this->controls['mobileSubMenuTypography'] = [
			'tab'   => 'content',
			'group' => 'mobile-menu',
			'type'  => 'typography',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-mobile-menu .sub-menu > li.menu-item > a',
				],
			],
		];

		$this->controls['mobileSubMenuActiveTypography'] = [
			'tab'   => 'content',
			'group' => 'mobile-menu',
			'type'  => 'typography',
			'label' => esc_html__( 'Active typography', 'bricks' ),
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-mobile-menu .sub-menu > .current-menu-item > a',
				],
			],
		];

		// Hamburger toggle

		$this->controls['_toggleSeparator'] = [
			'tab'   => 'content',
			'group' => 'mobile-menu',
			'type'  => 'separator',
			'label' => esc_html__( 'Hamburger toggle', 'bricks' ),
		];

		$this->controls['mobileMenuToggleWidth'] = [
			'tab'   => 'content',
			'group' => 'mobile-menu',
			'type'  => 'number',
			'units' => true,
			'label' => esc_html__( 'Toggle width', 'bricks' ),
			'css'   => [
				[
					'property'  => 'width',
					'selector'  => '.bricks-mobile-menu-toggle',
					'important' => true,
				],
				[
					'property'  => 'width',
					'selector'  => '.bricks-mobile-menu-toggle .bar-top',
					'important' => true,
				],
				[
					'property'  => 'width',
					'selector'  => '.bricks-mobile-menu-toggle .bar-center',
					'important' => true,
				],
				[
					'property'  => 'width',
					'selector'  => '.bricks-mobile-menu-toggle .bar-bottom',
					'important' => true,
				],
			],
		];

		$this->controls['mobileMenuToggleColor'] = [
			'tab'   => 'content',
			'group' => 'mobile-menu',
			'type'  => 'color',
			'label' => esc_html__( 'Color', 'bricks' ),
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.bricks-mobile-menu-toggle',
				]
			],
		];

		$this->controls['mobileMenuToggleHide'] = [
			'tab'   => 'content',
			'group' => 'mobile-menu',
			'type'  => 'checkbox',
			'label' => esc_html__( 'Hide close', 'bricks' ),
			'css'   => [
				[
					'selector'  => '&.show-mobile-menu .bricks-mobile-menu-toggle',
					'property'  => 'display',
					'value'     => 'none',
					'important' => true,
				]
			],
		];

		$this->controls['mobileMenuToggleColorClose'] = [
			'tab'   => 'content',
			'group' => 'mobile-menu',
			'type'  => 'color',
			'label' => esc_html__( 'Color close', 'bricks' ),
			'css'   => [
				[
					'property'  => 'color',
					'selector'  => '&.show-mobile-menu .bricks-mobile-menu-toggle',
					'important' => true,
				]
			],
		];

		$this->controls['mobileMenuToggleClosePosition'] = [
			'tab'   => 'content',
			'group' => 'mobile-menu',
			'type'  => 'spacing',
			'label' => esc_html__( 'Close position', 'bricks' ),
			'css'   => [
				[
					'selector' => '&.show-mobile-menu .bricks-mobile-menu-toggle',
					'property' => '',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;
		$menu     = ! empty( $settings['menu'] ) ? $settings['menu'] : '';

		if ( ! $menu || ! is_nav_menu( $menu ) ) {
			// Use first registered menu
			foreach ( wp_get_nav_menus() as $menu ) {
				$menu = $menu->term_id;
			}

			if ( ! $menu || ! is_nav_menu( $menu ) ) {
				return $this->render_element_placeholder(
					[
						'title' => esc_html__( 'No nav menu found.', 'bricks' ),
					]
				);
			}
		}

		// Hooks
		add_filter( 'nav_menu_item_title', [ $this, 'nav_menu_item_title' ], 10, 4 );
		add_filter( 'walker_nav_menu_start_el', [ $this, 'walker_nav_menu_start_el' ], 10, 4 );

		echo "<div {$this->render_attributes( '_root' )}>";

		$mobile_menu_on_breakpoint = isset( $settings['mobileMenu'] ) ? $settings['mobileMenu'] : 'mobile_landscape';

		// Is mobile-first: Swap always <> never
		if ( Breakpoints::$is_mobile_first ) {
			if ( $mobile_menu_on_breakpoint === 'always' ) {
				$mobile_menu_on_breakpoint = 'never';
			} elseif ( $mobile_menu_on_breakpoint === 'never' ) {
				$mobile_menu_on_breakpoint = 'always';
			}
		}

		if ( $mobile_menu_on_breakpoint !== 'always' ) {
			$this->set_attribute( 'nav', 'class', [ 'bricks-nav-menu-wrapper', $mobile_menu_on_breakpoint ] );

			echo "<nav {$this->render_attributes( 'nav', true )}>";

			wp_nav_menu(
				[
					'container'  => false,
					'menu_class' => 'bricks-nav-menu',
					'menu'       => $menu,
					'items_wrap' => '<ul id="%1$s" class="%2$s" role="menubar">%3$s</ul>',
					'walker'     => new \Aria_Walker_Nav_Menu(),
				]
			);

			// Builder: Add nav menu & mobile menu visibility via inline style
			if ( bricks_is_builder_call() ) {
				$breakpoint          = Breakpoints::get_breakpoint_by( 'key', $mobile_menu_on_breakpoint );
				$nav_menu_inline_css = $this->generate_mobile_menu_inline_css( $settings, $breakpoint );

				echo "<style>$nav_menu_inline_css</style>";
			}

			echo '</nav>';
		}

		$mobile_menu_toggle_classes = [ 'bricks-mobile-menu-toggle' ];

		if ( ! empty( $settings['mobileMenuToggleClosePosition'] ) ) {
			$mobile_menu_toggle_classes[] = 'fixed';
		}

		if ( $mobile_menu_on_breakpoint === 'always' ) {
			$mobile_menu_toggle_classes[] = 'always';
		}

		if ( $mobile_menu_on_breakpoint !== 'never' ) {
			?>
			<button class="<?php echo join( ' ', $mobile_menu_toggle_classes ); ?>" aria-haspopup="true" aria-label="<?php esc_attr_e( 'Mobile menu', 'bricks' ); ?>" aria-pressed="false">
				<span class="bar-top"></span>
				<span class="bar-center"></span>
				<span class="bar-bottom"></span>
			</button>
			<?php

			$mobile_menu_classes = [ 'bricks-mobile-menu-wrapper' ];

			$mobile_menu_classes[] = ! empty( $settings['mobileMenuPosition'] ) ? $settings['mobileMenuPosition'] : 'left';

			// Fade in
			if ( isset( $settings['mobileMenuFadeIn'] ) ) {
				$mobile_menu_classes[] = 'fade-in';
			}

			$this->set_attribute( 'nav-mobile', 'class', $mobile_menu_classes );

			echo "<nav {$this->render_attributes( 'nav-mobile', true )}>";

			wp_nav_menu(
				[
					'container'  => false,
					'menu_class' => 'bricks-mobile-menu',
					'menu'       => $menu,
					'items_wrap' => '<ul id="%1$s" class="%2$s" role="menubar">%3$s</ul>',
					'walker'     => new \Aria_Walker_Nav_Menu(),
				]
			);

			echo '</nav>';

			echo '<div class="bricks-mobile-menu-overlay"></div>';
		}

		echo '</div>'; // Closing '_root'

		// Remove the filter after rending this element to prevent conflicts with other Nav Menu elements in the same page
		remove_filter( 'nav_menu_item_title', [ $this, 'nav_menu_item_title' ], 10, 4 );
		remove_filter( 'walker_nav_menu_start_el', [ $this, 'walker_nav_menu_start_el' ], 10, 4 );
	}

	/**
	 * Add icon to mobile menu items with children (to toggle the submenu)
	 */
	public function walker_nav_menu_start_el( $output, $item, $depth, $args ) {
		$classes = empty( $item->classes ) ? [] : (array) $item->classes;

		if ( $depth !== 0 || ! in_array( 'menu-item-has-children', $classes ) || $args->menu_class !== 'bricks-mobile-menu' ) {
			return $output;
		}

		$settings = $this->settings;

		$menu_icon_position = isset( $settings['mobileMenuIconPosition'] ) ? $settings['mobileMenuIconPosition'] : 'right';
		$menu_icon_html     = isset( $settings['mobileMenuIcon'] ) ? self::render_icon( $settings['mobileMenuIcon'], [ 'mobile-menu-icon-svg open' ] ) : false;
		$close_icon_html    = isset( $settings['mobileMenuCloseIcon'] ) ? self::render_icon( $settings['mobileMenuCloseIcon'], [ 'mobile-menu-icon-svg close' ] ) : '';

		if ( $menu_icon_html ) {
			$menu_icon_html = "<button class=\"bricks-mobile-submenu-toggle icon-$menu_icon_position\">" . $close_icon_html . $menu_icon_html . '</button>';

			if ( $menu_icon_position === 'right' ) {
				$output = $output . $menu_icon_html;
			} else {
				$output = $menu_icon_html . $output;
			}
		}

		return $output;
	}

	/**
	 * Add icon to nav menu items with children
	 */
	public function nav_menu_item_title( $title, $item, $args, $depth ) {
		// Return if icon for title is already set
		if ( strpos( $title, '<i class=' ) !== false || strpos( $title, '<svg' ) !== false ) {
			return $title;
		}

		// Leave if mobile menu
		if ( $args->menu_class === 'bricks-mobile-menu' ) {
			return $title;
		}

		$settings = $this->settings;

		$classes = empty( $item->classes ) ? [] : (array) $item->classes;

		if ( $depth === 0 ) {
			// Top level menu item
			if ( in_array( 'menu-item-has-children', $classes ) ) {
				$menu_icon_position = isset( $settings['menuIconPosition'] ) ? $settings['menuIconPosition'] : 'right';
				$menu_icon_html     = isset( $settings['menuIcon'] ) ? self::render_icon( $settings['menuIcon'], [ "icon-{$menu_icon_position}", 'menu-icon-svg' ] ) : false;

				if ( $menu_icon_html ) {
					if ( $menu_icon_position === 'right' ) {
						$title = $title . $menu_icon_html;
					} else {
						$title = $menu_icon_html . $title;
					}
				}
			}
		}

		// Sub menu item
		else {
			if ( in_array( 'menu-item-has-children', $classes ) ) {
				$sub_menu_icon_position = isset( $settings['subMenuIconPosition'] ) ? $settings['subMenuIconPosition'] : 'right';
				$sub_menu_icon_html     = isset( $settings['subMenuIcon'] ) ? self::render_icon( $settings['subMenuIcon'], [ "icon-{$sub_menu_icon_position}", 'sub-menu-icon-svg' ] ) : false;

				if ( $sub_menu_icon_html ) {
					if ( $sub_menu_icon_position === 'right' ) {
						$title = $title . $sub_menu_icon_html;
					} else {
						$title = $sub_menu_icon_html . $title;
					}
				}
			}
		}

		return $title;
	}
}
