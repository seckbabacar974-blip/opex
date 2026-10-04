<?php
$controls = [];

$link_css_selectors = [
	'.brxe-accordion .accordion-content-wrapper a',
	'.brxe-icon-box .content-wrapper a',
	'.brxe-list a',
	'.brxe-post-content a:not(.bricks-button)', // @since 1.5 (see: #2hjn8md)
	'.brxe-posts .dynamic p a',
	'.brxe-shortcode a',
	'.brxe-tabs .tab-content a',
	'.brxe-team-members .description a',
	'.brxe-testimonials .testimonial-content-wrapper a',

	'.brxe-text a',
	'a.brxe-text',

	'.brxe-text-basic a',
	'a.brxe-text-basic',
];

// https://academy.bricksbuilder.io/article/filter-bricks-link_css_selectors/
$link_css_selectors = apply_filters( 'bricks/link_css_selectors', $link_css_selectors );

$link_css_selectors = join( ', ', $link_css_selectors );

$controls['typography'] = [
	'type'    => 'typography',
	'label'   => esc_html__( 'Typography', 'bricks' ),
	'css'     => [
		[
			'property' => 'font',
			'selector' => $link_css_selectors,
		],
	],
	'exclude' => [
		'text-align',
		'line-height',
	],
];

$controls['background'] = [
	'label'   => esc_html__( 'Background', 'bricks' ),
	'type'    => 'background',
	'css'     => [
		[
			'property' => 'background',
			'selector' => $link_css_selectors,
		],
	],
	'exclude' => [
		'videoUrl',
		'videoScale',
	],
];

$controls['border'] = [
	'label' => esc_html__( 'Border', 'bricks' ),
	'type'  => 'border',
	'css'   => [
		[
			'property' => 'border',
			'selector' => $link_css_selectors,
		],
	],
];

$controls['padding'] = [
	'label' => esc_html__( 'Padding', 'bricks' ),
	'type'  => 'spacing',
	'css'   => [
		[
			'property' => 'padding',
			'selector' => $link_css_selectors,
		],
	],
];

$controls['textDecoration'] = [
	'label' => esc_html__( 'Text decoration', 'bricks' ),
	'type'  => 'text-decoration',
	'css'   => [
		[
			'property' => 'text-decoration',
			'selector' => $link_css_selectors,
		],
	],
];

$controls['transition'] = [
	'label'       => esc_html__( 'Transition', 'bricks' ),
	'css'         => [
		[
			'property' => 'transition',
			'selector' => $link_css_selectors,
		],
	],
	'type'        => 'text',
	'placeholder' => 'all 0.2s ease-in',
	'description' => sprintf( '<a href="https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Transitions/Using_CSS_transitions" target="_blank">%s</a>', esc_html__( 'Learn more about CSS transitions', 'bricks' ) ),
];

return [
	'name'     => 'links',
	'controls' => $controls,
];
