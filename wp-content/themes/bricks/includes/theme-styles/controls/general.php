<?php
$controls = [];

// NOTE: Check 'siteLayout' setting in page settings and theme styles in builder/frontend
$controls['siteLayout'] = [
	'type'        => 'select',
	'label'       => esc_html__( 'Site layout', 'bricks' ),
	'options'     => [
		'boxed' => esc_html__( 'Boxed', 'bricks' ),
		'wide'  => esc_html__( 'Wide', 'bricks' ),
	],
	'inline'      => true,
	'placeholder' => esc_html__( 'Wide', 'bricks' ),
];

$controls['siteLayoutBoxedMaxWidth'] = [
	'label'    => esc_html__( 'Boxed max. width', 'bricks' ),
	'type'     => 'number',
	'units'    => true,
	'css'      => [
		[
			'property' => 'max-width',
			'selector' => '.brx-boxed',
		],
		[
			'property' => 'max-width',
			'selector' => '.brx-boxed #brx-header.sticky',
		],
		[
			'property' => 'margin-left',
			'selector' => '.brx-boxed #brx-header.sticky',
			'value'    => 'auto',
		],
		[
			'property' => 'margin-right',
			'selector' => '.brx-boxed #brx-header.sticky',
			'value'    => 'auto',
		],
	],
	'required' => [ 'siteLayout', '=', 'boxed' ],
];

$controls['contentBoxShadow'] = [
	'type'     => 'box-shadow',
	'label'    => esc_html__( 'Content box shadow', 'bricks' ),
	'css'      => [
		[
			'property' => 'box-shadow',
			'selector' => '.brx-boxed',
		],
	],
	'required' => [ 'siteLayout', '=', 'boxed' ],
];

$controls['contentBackground'] = [
	'type'     => 'background',
	'label'    => esc_html__( 'Content background', 'bricks' ),
	'css'      => [
		[
			'property' => 'background',
			'selector' => '.brx-boxed',
		],
	],
	'exclude'  => [
		'videoUrl',
		'videoScale',
		'videoAspectRatio',
	],
	'required' => [ 'siteLayout', '=', 'boxed' ],
];

$controls['siteBackground'] = [
	'type'    => 'background',
	'label'   => esc_html__( 'Site background', 'bricks' ),
	'css'     => [
		[
			'property' => 'background',
			'selector' => 'html',
		],
		[
			'property' => 'background',
			'selector' => 'body',
		],
	],
	'exclude' => [
		'videoUrl',
		'videoScale',
		'videoAspectRatio',
	],
];

$controls['siteBorder'] = [
	'type'     => 'border',
	'label'    => esc_html__( 'Site border', 'bricks' ),
	'css'      => [
		[
			'property' => 'border',
			'selector' => '.brx-boxed',
		],
	],
	'required' => [ 'siteLayout', '=', 'boxed' ],
];

$controls['elementMargin'] = [
	'deprecated' => true, // @since 1.5 as nestable elements now allow for brxe- inside each other
	'label'      => esc_html__( 'Element margin', 'bricks' ),
	'type'       => 'spacing',
	'css'        => [
		[
			'property' => 'margin',
			'selector' => '[class*="brxe-"]:not(.brxe-section):not(.brxe-container):not(.brxe-div)',
		],
	],
];

/**
 * Container: Deprecated @since 1.5 as container now has it's own "Element - Container" theme style group
 *
 * Run converter with: 'Convert "Container" to new "Section" & "Block" elements' to convert these theme style settings to the new 'Section' element.
 */

$controls['containerSeparator'] = [
	'deprecated' => true, // @since 1.5
	'type'       => 'separator',
	'label'      => esc_html__( 'Container', 'bricks' ),
];

$controls['sectionMargin'] = [
	'deprecated' => true, // @since 1.5
	'label'      => esc_html__( 'Root container margin', 'bricks' ),
	'type'       => 'spacing',
	'css'        => [
		[
			'property' => 'margin',
			'selector' => '.brxe-container.root',
		],
	],
];

$controls['sectionPadding'] = [
	'deprecated' => true, // @since 1.5
	'label'      => esc_html__( 'Root container padding', 'bricks' ),
	'type'       => 'spacing',
	'css'        => [
		[
			'property' => 'padding',
			'selector' => '.brxe-container.root:not(.stretch)',
		],

		[
			'property' => 'padding',
			'selector' => '.brxe-container.root.stretch > .brxe-container',
		],

		[
			'property' => 'padding',
			'selector' => '.brxe-container.root.stretch > .brxe-div',
		],
	],
];

$controls['containerMaxWidth'] = [
	'deprecated'  => true, // @since 1.5
	'type'        => 'number',
	'units'       => true,
	'label'       => esc_html__( 'Root container width', 'bricks' ),
	'css'         => [
		[
			'property' => 'width',
			'selector' => '.brxe-container.root',
		],

		[
			'property' => 'width',
			'selector' => '.brxe-container.root.stretch > .brxe-container',
		],

		[
			'property' => 'width',
			'selector' => '.brxe-container.root.stretch > .brxe-div',
		],

		// WooCommerce wrapper
		[
			'property' => 'width',
			'selector' => '.woocommerce main.site-main',
		],

		// Single post, WooCommerce cart, etc.
		[
			'property' => 'width',
			'selector' => '#brx-content.wordpress',
		],
	],
	'placeholder' => '1100px',
];

// Lightbox

$controls['lightboxSeparator'] = [
	'type'  => 'separator',
	'label' => esc_html__( 'Lightbox', 'bricks' ),
];

$controls['lightboxHeight'] = [
	'type'  => 'number',
	'units' => true,
	'label' => esc_html__( 'Lightbox height', 'bricks' ),
	'css'   => [
		[
			'property' => 'height',
			'selector' => '#bricks-lightbox .inner',
		],
	],
];

$controls['lightboxWidth'] = [
	'type'  => 'number',
	'units' => true,
	'label' => esc_html__( 'Lightbox width', 'bricks' ),
	'css'   => [
		[
			'property' => 'width',
			'selector' => '#bricks-lightbox .inner',
		],
	],
];

$controls['lightboxBackground'] = [
	'type'    => 'background',
	'label'   => esc_html__( 'Lightbox background', 'bricks' ),
	'css'     => [
		[
			'property' => 'background-color',
			'selector' => '#bricks-lightbox',
		],
		[
			'property' => 'background-color',
			'selector' => '.pswp .pswp__bg',
		],
	],
	'exclude' => [
		'videoUrl',
		'videoScale',
		'videoAspectRatio',
	],
];

$controls['lightboxCloseColor'] = [
	'type'  => 'color',
	'label' => esc_html__( 'Lightbox close color', 'bricks' ),
	'css'   => [
		[
			'property' => 'color',
			'selector' => '#bricks-lightbox .close',
		],
	],
];

$controls['lightboxCloseSize'] = [
	'type'        => 'number',
	'units'       => true,
	'label'       => esc_html__( 'Lightbox close size', 'bricks' ),
	'css'         => [
		[
			'property' => 'font-size',
			'selector' => '#bricks-lightbox .close',
		],
	],
	'placeholder' => 60,
];

return [
	'name'     => 'general',
	'controls' => $controls,
];
