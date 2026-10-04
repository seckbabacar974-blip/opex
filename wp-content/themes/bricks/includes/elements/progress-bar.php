<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Progress_Bar extends Element {
	public $category     = 'general';
	public $name         = 'progress-bar';
	public $icon         = 'ti-line-double';
	public $css_selector = '.bar';
	public $scripts      = [ 'bricksProgressBar' ];

	public function get_label() {
		return esc_html__( 'Progress Bar', 'bricks' );
	}

	public function set_controls() {
		$this->controls['_padding']['css'][0]['selector'] = '';

		// Group: 'bars'

		$this->controls['bars'] = [
			'tab'         => 'content',
			'type'        => 'repeater',
			'placeholder' => esc_html__( 'Bar', 'bricks' ),
			'selector'    => '.bar-wrapper',
			'fields'      => [
				'title'      => [
					'label' => esc_html__( 'Label', 'bricks' ),
					'type'  => 'text',
				],

				'percentage' => [
					'label' => esc_html__( 'Percentage', 'bricks' ),
					'type'  => 'number',
					'min'   => 0,
					'max'   => 100,
					'step'  => 1,
				],
				'color'      => [
					'label' => esc_html__( 'Bar color', 'bricks' ),
					'type'  => 'color',
					'css'   => [
						[
							'property' => 'background-color',
							'selector' => '.bar span',
						],
					],
				],
			],
			'default'     => [
				[
					'title'      => esc_html__( 'Web design', 'bricks' ),
					'percentage' => 80,
				],
				[
					'title'      => esc_html__( 'SEO', 'bricks' ),
					'percentage' => 90,
				],
			],
			'rerender'    => true,
		];

		// SETTINGS

		$this->controls['height'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Height', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'height',
				]
			],
			'placeholder' => 8,
		];

		$this->controls['barSpacing'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Spacing', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'gap',
					'selector' => '',
				]
			],
			'placeholder' => 20,
		];

		$this->controls['showPercentage'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Show percentage', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['barColor'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Bar color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.bar span',
				],
			],
		];

		$this->controls['barBackgroundColor'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Bar background color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.bar',
				],
			],
		];

		$this->controls['barBorder'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Bar border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bar',
				],
			],
		];

		$this->controls['labelTypography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Label typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.label',
				],
			],
		];

		$this->controls['percentageTypography'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Percentage typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.percentage',
				],
			],
		];
	}

	public function render() {
		$bars = empty( $this->settings['bars'] ) ? false : $this->settings['bars'];

		if ( ! $bars ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No progress bar created.', 'bricks' ),
				]
			);
		}

		echo "<div {$this->render_attributes( '_root' )}>";

		foreach ( $bars as $index => $bar ) {
			$this->set_attribute( "bar-inner-{$index}", 'data-width', "{$bar['percentage']}%" );
			?>
			<div class="bar-wrapper">
				<label>
					<?php
					if ( isset( $bar['title'] ) && ! empty( $bar['title'] ) ) {
						echo '<span class="label">' . $bar['title'] . '</span>';
					}

					if ( isset( $this->settings['showPercentage'] ) ) {
						echo '<span class="percentage">' . $bar['percentage'] . '%</span>';
					}
					?>
				</label>

				<div class="bar">
					<span <?php echo $this->render_attributes( "bar-inner-{$index}" ); ?>></span>
				</div>
			</div>
			<?php
		}

		echo '</div>';
	}
}
