<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Search extends Element {
	public $block        = 'core/search';
	public $category     = 'wordpress';
	public $name         = 'search';
	public $icon         = 'ti-search';
	public $css_selector = 'form';

	public function get_label() {
		return esc_html__( 'Search', 'bricks' );
	}

	public function set_controls() {
		$this->controls['searchType'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Search type', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'input'   => esc_html__( 'Input', 'bricks' ),
				'overlay' => esc_html__( 'Icon', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Input', 'bricks' ),
		];

		// Input

		$this->controls['inputSeparator'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Input', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['inputHeight'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Height', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'small' => false,
			'css'   => [
				[
					'property' => 'height',
					'selector' => 'input[type=search]',
				],
			],
		];

		$this->controls['inputWidth'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Width', 'bricks' ),
			'type'  => 'number',
			'units' => true,
			'small' => false,
			'css'   => [
				[
					'property' => 'width',
					'selector' => 'input[type=search]',
				],
			],
		];

		$this->controls['placeholder'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Placeholder', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'Search ...', 'bricks' ),
		];

		$this->controls['placeholderColor'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Placeholder color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => 'input[type=search]::placeholder',
				],
			],
		];

		$this->controls['inputBackgroundColor'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => 'input[type=search]',
				],
			],
		];

		$this->controls['inputBorder'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => 'input[type=search]',
				],
			],
		];

		$this->controls['inputBoxShadow'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => 'input[type=search]',
				],
			],
		];

		// Icon

		$this->controls['iconSeparator'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Icon', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['icon'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Icon', 'bricks' ),
			'type'  => 'icon',
			'info'  => esc_html__( 'Click on search icon opens search overlay.', 'bricks' ),
		];

		$this->controls['iconBackgroundColor'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Icon background', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.bricks-search-icon',
				],
			],
			'required' => [ 'icon', '!=', '' ],
		];

		$this->controls['iconTypography'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Icon typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.bricks-search-icon',
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
			'required' => [ 'icon.icon', '!=', '' ],
		];

		$this->controls['iconWidth'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Icon width', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'small'       => false,
			'css'         => [
				[
					'property' => 'width',
					'selector' => '.bricks-search-submit',
				],
				[
					'property' => 'width',
					'selector' => '.bricks-search-icon',
				],
			],
			'placeholder' => 60,
			'required'    => [ 'icon', '!=', '' ],
		];

		$this->controls['iconHeight'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Icon height', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'small'       => false,
			'css'         => [
				[
					'property' => 'height',
					'selector' => '.bricks-search-icon',
				],
			],
			'placeholder' => 40,
			'required'    => [ 'icon', '!=', '' ],
		];

		// Search Overlay
		$this->controls['searchOverlaySeparator'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Search Overlay', 'bricks' ),
			'type'        => 'separator',
			'description' => esc_html__( 'Disabled in builder.', 'bricks' ),
			'required'    => [ 'searchType', '=', 'overlay' ],
		];

		$this->controls['searchOverlayTitle'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Title', 'bricks' ),
			'type'     => 'text',
			'inline'   => true,
			'default'  => esc_html__( 'Search site', 'bricks' ),
			'required' => [ 'searchType', '=', 'overlay' ],
		];

		$this->controls['searchOverlayTitleTypography'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Title typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.title',
				],
			],
			'required' => [ 'searchType', '=', 'overlay' ],
		];

		$this->controls['searchOverlayBackground'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Background', 'bricks' ),
			'type'     => 'background',
			'css'      => [
				[
					'property' => 'background',
					'selector' => '.bricks-search-overlay',
				],
			],
			'required' => [ 'searchType', '=', 'overlay' ],
		];

		$this->controls['searchOverlayBackgroundOverlay'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Background Overlay', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'background-color',
					'selector' => '.bricks-search-overlay:after',
				],
			],
			'required' => [ 'searchType', '=', 'overlay' ],
		];
	}

	public function render() {
		$settings = $this->settings;

		$search_title = isset( $settings['searchOverlayTitle'] ) ? esc_html( $settings['searchOverlayTitle'] ) : esc_html__( 'Search site', 'bricks' );
		$search_type  = isset( $settings['searchType'] ) ? $settings['searchType'] : 'input';
		$icon         = isset( $settings['icon'] ) ? self::render_icon( $settings['icon'] ) : false;

		echo "<div {$this->render_attributes( '_root' )}>";

		if ( $search_type === 'input' ) {
			// Use include to pass $settings
			include locate_template( 'searchform.php' );
		} else {
			echo '<div class="bricks-search-icon overlay-trigger">' . $icon . '</div>';

			unset( $settings['icon'] );
			?>
			<div class="bricks-search-overlay">
				<div class="bricks-search-inner">
					<h4 class="title"><?php echo $search_title; ?></h4>
					<?php
					// Use include to pass $settings
					include locate_template( 'searchform.php' );
					?>
				</div>

				<?php echo '<span class="close">×</span>'; ?>
			</div>
			<?php
		}

		echo '</div>';
	}

	public function convert_element_settings_to_block( $settings ) {
		$attributes = [];

		if ( isset( $settings['inputWidth'] ) ) {
			$attributes['width'] = $settings['inputWidth'];
		}

		if ( isset( $settings['placeholder'] ) ) {
			$attributes['placeholder'] = $settings['placeholder'];
		}

		if ( isset( $settings['icon'] ) ) {
			$attributes['buttonUseIcon'] = true;
		}

		if ( isset( $settings['_cssClasses'] ) ) {
			$attributes['className'] = $settings['_cssClasses'];
		}

		$block = [
			'blockName'    => $this->block,
			'attrs'        => $attributes,
			'innerContent' => [],
		];

		return $block;
	}

	public function convert_block_to_element_settings( $block, $attributes ) {
		$element_settings = [];

		if ( isset( $attributes['width'] ) ) {
			$element_settings['inputWidth'] = $attributes['width'] . 'px';
		}

		if ( isset( $attributes['placeholder'] ) ) {
			$element_settings['placeholder'] = $attributes['placeholder'];
		}

		if ( isset( $attributes['buttonUseIcon'] ) ) {
			$element_settings['icon'] = [
				'library' => 'themify',
				'icon'    => 'ti-search',
			];
		}

		if ( isset( $attributes['className'] ) ) {
			$element_settings['_cssClasses'] = $attributes['className'];
		}

		return $element_settings;
	}
}
