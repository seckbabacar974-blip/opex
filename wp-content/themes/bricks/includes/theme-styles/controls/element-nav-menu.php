<?php
$controls = [];

// Top Level Menu

$controls['menuSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Top level menu', 'bricks' ),
];

$controls['menuMargin'] = [
	'label' => esc_html__( 'Margin', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'margin',
			'selector' => '.bricks-nav-menu > li',
		]
	],
];

$controls['menuPadding'] = [
	'label' => esc_html__( 'Padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.bricks-nav-menu > li',
		]
	],
];

$controls['menuAlignment'] = [
	'label'       => esc_html__( 'Alignment', 'bricks' ),
	'type'        => 'direction',
	'css'         => [
		[
			'property' => 'flex-direction',
			'selector' => '.bricks-nav-menu',
		],
	],
	'inline'      => true,
	'placeholder' => 'row',
];

$controls['menuTypography'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'Typography', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.bricks-nav-menu > li a',
		],
	],
];

$controls['menuActiveTypography'] = [
	'label' => esc_html__( 'Active typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.bricks-nav-menu > .current-menu-item > a',
		],
	],
];

$controls['menuActiveBorder'] = [
	'label' => esc_html__( 'Active border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.bricks-nav-menu > .current-menu-item',
		],
	],
];

// Sub Menu

$controls['subMenuSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Sub Menu', 'bricks' ),
];

$controls['subMenuPadding'] = [
	'type'  => 'spacing',
	'label' => esc_html__( 'Padding', 'bricks' ),
	'css'   => [
		[
			'property' => 'padding',
			'selector' => '.bricks-nav-menu .sub-menu > li > a',
		],
	],
];

$controls['subMenuTypography'] = [
	'type'  => 'typography',
	'label' => esc_html__( 'Typography', 'bricks' ),
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.bricks-nav-menu .sub-menu > li > a',
		],
	],
];

$controls['subMenuActiveTypography'] = [
	'label' => esc_html__( 'Active typography', 'bricks' ),
	'type'  => 'typography',
	'css'   => [
		[
			'property' => 'font',
			'selector' => '.bricks-nav-menu .current-menu-item .sub-menu a',
		],
	],
];

$controls['subMenuBackground'] = [
	'type'  => 'background',
	'label' => esc_html__( 'Background', 'bricks' ),
	'css'   => [
		[
			'property' => 'background',
			'selector' => '.bricks-nav-menu .sub-menu li.menu-item',
		]
	],
];

$controls['subMenuBorder'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => '.bricks-nav-menu .sub-menu',
		],
	],
];

$controls['subMenuBoxShadow'] = [
	'label' => esc_html__( 'Box shadow', 'bricks' ),
	'type'  => 'box-shadow',
	'css'   => [
		[
			'property' => 'box-shadow',
			'selector' => '.bricks-nav-menu .sub-menu',
		],
	],
];

return [
	'name'        => 'nav-menu',
	'controls'    => $controls,
	'cssSelector' => '.brxe-nav-menu',
];
