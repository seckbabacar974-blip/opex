<?php
$controls = [];

$controls['boxShadow'] = [
	'label' => esc_html__( 'Box shadow', 'bricks' ),
	'type'  => 'box-shadow',
	'css'   => [
		[
			'property' => 'box-shadow',
			'selector' => '',
		],
	],
];

$controls['overlay'] = [
	'label'   => esc_html__( 'Overlay', 'bricks' ),
	'type'    => 'background',
	'exclude' => [
		'parallax',
		'videoUrl',
		'videoScale',
		'videoAspectRatio',
	],
	'css'     => [
		[
			'property' => 'background',
			'selector' => '.bricks-video-overlay',
		],
	],
];

$controls['overlayIcon'] = [
	'label' => esc_html__( 'Icon', 'bricks' ),
	'type'  => 'icon',
];

$controls['overlayIconTypography'] = [
	'label'    => esc_html__( 'Icon typography', 'bricks' ),
	'type'     => 'typography',
	'css'      => [
		[
			'property' => 'font',
			'selector' => '.bricks-video-overlay-icon',
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
	'required' => [ 'overlayIcon.icon', '!=', '' ],
];

// Custom player (plyr.io)
$controls['customPlayer'] = [
	'type'        => 'checkbox',
	'label'       => esc_html__( 'Enable custom video player', 'bricks' ),
	'description' => esc_html__( 'If enabled an additional JS & CSS file is loaded.', 'bricks' ),
];

$controls['fileRestart'] = [
	'label'         => esc_html__( 'Restart', 'bricks' ),
	'type'          => 'checkbox',
	'required'      => [ 'customPlayer', '!=', '' ],
	'reloadScripts' => true,
];

$controls['fileRewind'] = [
	'label'    => esc_html__( 'Rewind', 'bricks' ),
	'type'     => 'checkbox',
	'required' => [ 'customPlayer', '!=', '' ],
];

$controls['fileFastForward'] = [
	'label'    => esc_html__( 'Fast forward', 'bricks' ),
	'type'     => 'checkbox',
	'required' => [ 'customPlayer', '!=', '' ],
];
$controls['fileSpeed']       = [
	'label'    => esc_html__( 'Speed', 'bricks' ),
	'type'     => 'checkbox',
	'required' => [ 'customPlayer', '!=', '' ],
];

$controls['filePip'] = [
	'label'    => esc_html__( 'Picture in picture', 'bricks' ),
	'type'     => 'checkbox',
	'required' => [ 'customPlayer', '!=', '' ],
];

$controls['apply'] = [
	'type'   => 'apply',
	'reload' => true,
	'label'  => esc_html__( 'Apply controls & reload', 'bricks' ),
];

return [
	'name'        => 'video',
	'controls'    => $controls,
	'cssSelector' => '.brxe-video',
];
