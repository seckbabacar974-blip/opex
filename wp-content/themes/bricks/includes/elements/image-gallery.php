<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Image_Gallery extends Element {
	public $block    = 'core/gallery';
	public $category = 'media';
	public $name     = 'image-gallery';
	public $icon     = 'ti-gallery';
	public $scripts  = [ 'bricksIsotope' ];

	public function get_label() {
		return esc_html__( 'Image Gallery', 'bricks' );
	}

	public function enqueue_scripts() {
		$layout = ! empty( $this->settings['layout'] ) ? $this->settings['layout'] : 'grid';

		if ( $layout === 'masonry' || $layout === 'metro' ) {
			wp_enqueue_script( 'bricks-isotope' );
			wp_enqueue_style( 'bricks-isotope' );
		}

		$link_to = ! empty( $this->settings['link'] ) ? $this->settings['link'] : false;

		if ( $link_to === 'lightbox' ) {
			wp_enqueue_script( 'bricks-photoswipe' );
			wp_enqueue_style( 'bricks-photoswipe' );
		}
	}

	public function set_controls() {
		$this->controls['_border']['css'][0]['selector']    = '.image';
		$this->controls['_boxShadow']['css'][0]['selector'] = '.image';

		$this->controls['items'] = [
			'tab'  => 'content',
			'type' => 'image-gallery',
		];

		// Settings

		$this->controls['settingsSeparator'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Settings', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['layout'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Layout', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'grid'    => esc_html__( 'Grid', 'bricks' ),
				'masonry' => esc_html__( 'Masonry', 'bricks' ),
				'metro'   => esc_html__( 'Metro', 'bricks' ),
			],
			'placeholder' => esc_html__( 'Grid', 'bricks' ),
			'inline'      => true,
			'rerender'    => true,
		];

		$this->controls['imageRatio'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Image ratio', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['imageRatio'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Square', 'bricks' ),
			'required'    => [ 'layout', '!=', [ 'masonry', 'metro' ] ],
		];

		$this->controls['imageHeight'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Image height', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property'  => 'padding-top',
					'selector'  => '.image',
					'important' => true,
				],
			],
			'placeholder' => '',
			'info'        => esc_html__( 'Precedes image ratio setting.', 'bricks' ),
			'required'    => [ 'layout', '!=', [ 'masonry', 'metro' ] ],
		];

		$this->controls['gutter'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Spacing', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'padding-right',
					'selector' => '.bricks-layout-item',
				],
				[
					'property' => 'padding-bottom',
					'selector' => '.bricks-layout-item',
				],

				// NOTE: Undocumented (invert)
				[
					'property' => 'margin-bottom',
					'invert'   => true,
				],
				[
					'property' => 'width',
					'value'    => 'calc(100% + %s)',
				],
			],
			'placeholder' => 0,
		];

		$this->controls['columns'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Columns', 'bricks' ),
			'type'        => 'number',
			'min'         => 2,
			'css'         => [
				[
					'selector' => 'li',
					'property' => 'width',
					'value'    => 'calc(100% / %s)', // NOTE: Undocumented (@since 1.3)
				],
			],
			'rerender'    => true,
			'placeholder' => 3,
			'required'    => [ 'layout', '!=', [ 'metro' ] ],
		];

		$this->controls['link'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Link to', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'lightbox'   => esc_html__( 'Lightbox', 'bricks' ),
				'attachment' => esc_html__( 'Attachment Page', 'bricks' ),
				'media'      => esc_html__( 'Media File', 'bricks' ),
				'custom'     => esc_html__( 'Custom URL', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'None', 'bricks' ),
		];

		$this->controls['lightboxImageSize'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Lightbox image size', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['imageSizes'],
			'placeholder' => esc_html__( 'Full', 'bricks' ),
			'required'    => [ 'link', '=', 'lightbox' ],
		];

		$this->controls['linkCustom'] = [
			'tab'         => 'content',
			'label'       => esc_html__( 'Custom links', 'bricks' ),
			'type'        => 'repeater',
			'fields'      => [
				'link' => [
					'label'   => esc_html__( 'Link', 'bricks' ),
					'type'    => 'link',
					'exclude' => [
						'lightboxImage',
						'lightboxVideo',
					],
				],
			],
			'placeholder' => esc_html__( 'Custom link', 'bricks' ),
			'required'    => [ 'link', '=', 'custom' ],
		];

		$this->controls['caption'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Caption', 'bricks' ),
			'type'  => 'checkbox',
		];
	}

	public function get_normalized_image_settings( $settings ) {
		$items = isset( $settings['items'] ) ? $settings['items'] : [];

		$size = ! empty( $items['size'] ) ? $items['size'] : BRICKS_DEFAULT_IMAGE_SIZE;

		// Dynamic Data
		if ( ! empty( $items['useDynamicData'] ) ) {
			$items['images'] = [];

			$images = $this->render_dynamic_data_tag( $items['useDynamicData'], 'image' );

			if ( is_array( $images ) ) {
				foreach ( $images as $image_id ) {
					$items['images'][] = [
						'id'   => $image_id,
						'full' => wp_get_attachment_image_url( $image_id, 'full' ),
						'url'  => wp_get_attachment_image_url( $image_id, $size )
					];
				}
			}
		}

		// Either empty OR old data structure used before 1.0 (images were saved as one array directly on $items)
		if ( ! isset( $items['images'] ) ) {
			$images = ! empty( $items ) ? $items : [];

			unset( $items );

			$items['images'] = $images;
		}

		// Get 'size' from first image if not set (previous to 1.4-RC)
		$first_image_size = ! empty( $items['images'][0]['size'] ) ? $items['images'][0]['size'] : false;
		$size             = empty( $items['size'] ) && $first_image_size ? $first_image_size : $size;

		// Calculate new image URL if size is not the same as from the Media Library
		if ( $first_image_size && $first_image_size !== $size ) {
			foreach ( $items['images'] as $key => $image ) {
				$items['images'][ $key ]['size'] = $size;
				$items['images'][ $key ]['url']  = wp_get_attachment_image_url( $image['id'], $size );
			}
		}

		$settings['items'] = $items;

		$settings['items']['size'] = $size;

		return $settings;
	}

	public function render() {
		$settings = $this->get_normalized_image_settings( $this->settings );
		$images   = ! empty( $settings['items']['images'] ) ? $settings['items']['images'] : false;
		$size     = ! empty( $settings['items']['size'] ) ? $settings['items']['size'] : BRICKS_DEFAULT_IMAGE_SIZE;
		$layout   = ! empty( $settings['layout'] ) ? $settings['layout'] : 'grid';
		$link_to  = ! empty( $settings['link'] ) ? $settings['link'] : false;
		$columns  = ! empty( $settings['columns'] ) ? $settings['columns'] : 3;
		$images   = ! empty( $settings['items']['images'] ) ? $settings['items']['images'] : false;

		// Return placeholder
		if ( ! $images ) {
			if ( ! empty( $settings['items']['useDynamicData'] ) ) {
				if ( BRICKS_DB_TEMPLATE_SLUG !== get_post_type( $this->post_id ) ) {
					return $this->render_element_placeholder(
						[
							'title' => esc_html__( 'Dynamic data is empty.', 'bricks' )
						]
					);
				}
			} else {
				return $this->render_element_placeholder(
					[
						'title' => esc_html__( 'No image selected.', 'bricks' ),
					]
				);
			}
		}

		$root_classes = [ 'bricks-layout-wrapper' ];

		// Set isotopeJS CSS class
		if ( $layout === 'masonry' || $layout === 'metro' ) {
			$root_classes[] = 'isotope';
		}

		$this->set_attribute( '_root', 'class', $root_classes );
		$this->set_attribute( '_root', 'data-layout', $layout );

		foreach ( $images as $index => $item ) {
			$item_classes  = [ 'bricks-layout-item' ];
			$image_styles  = [];
			$image_classes = [];

			$this->set_attribute( "item-{$index}", 'class', $item_classes );

			// Get image url, width and height (Fallback: Placeholder image)
			if ( isset( $item['id'] ) ) {
				$image_src = wp_get_attachment_image_src( $item['id'], $size );
			} elseif ( isset( $item['url'] ) ) {
				$image_src = [ $item['url'], 800, 600 ];
			}

			$image_src = ! empty( $image_src ) && is_array( $image_src ) ? $image_src : [ \Bricks\Builder::get_template_placeholder_image(), 800, 600 ];

			$image_url    = ! empty( $image_src[0] ) ? $image_src[0] : ( isset( $item['url'] ) ? $item['url'] : '' );
			$image_width  = ! empty( $image_src[1] ) ? $image_src[1] : 200;
			$image_height = ! empty( $image_src[2] ) ? $image_src[2] : 200;

			if ( $image_width ) {
				$this->set_attribute( "img-$index", 'width', $image_width );
			}

			if ( $image_height ) {
				$this->set_attribute( "img-$index", 'height', $image_height );
			}

			// Image lazy load
			if ( $this->lazy_load() ) {
				$image_classes[] = 'bricks-lazy-hidden';
				$image_classes[] = 'bricks-lazy-load-isotope';
			}

			// Layout-specific attributes (data atts for lazy load)
			if ( $layout === 'masonry' ) {
				if ( $this->lazy_load() ) {
					if ( ! empty( $image_url ) ) {
						$this->set_attribute( "img-$index", 'data-src', $image_url );
					}

					// Calc image ratio for img to take up proper space without src attribute
					$masonry_ratio = ( $image_height / $image_width * 100 ) . '%';
					$masonry_style = "padding-top: $masonry_ratio; position: relative";

					$this->set_attribute( "img-$index", 'style="' . $masonry_style . '"' );
				} else {
					if ( ! empty( $image_url ) ) {
						$this->set_attribute( "img-$index", 'src', $image_url );
					}
				}

				if ( isset( $item['id'] ) ) {
					$this->set_attribute( "img-$index", 'alt', get_post_meta( $item['id'], '_wp_attachment_image_alt', true ) );
				}

			} else {
				$image_classes[] = 'bricks-layout-inner';
				$image_classes[] = 'image';

				if ( $layout === 'grid' ) {
					// Precedes imageRatio setting
					if ( isset( $settings['imageRatio'] ) && ! empty( $settings['imageRatio'] ) ) {
						$image_classes[] = 'bricks-' . $settings['imageRatio'];
					} elseif ( isset( $settings['imageHeight'] ) ) {
						$image_styles[] = 'width: 100%';
						$image_styles[] = "height: {$settings['imageHeight']}";
					} else {
						// Default: Ratio square
						$image_classes[] = 'bricks-ratio-square';
						// $image_styles[] = "width: {$image_width}px";
						// $image_styles[] = "height: {$image_height}px";
					}
				}

				$image_styles[] = "background-image: url({$image_url})";

				$image_styles = join( '; ', $image_styles );

				if ( $this->lazy_load() ) {
					$this->set_attribute( "img-$index", 'data-style', $image_styles );
				} else {
					$this->set_attribute( "img-$index", 'style', $image_styles );
				}

				if ( isset( $item['id'] ) ) {
					$this->set_attribute( "img-$index", 'role', 'img' );
					$this->set_attribute( "img-$index", 'aria-label', get_post_meta( $item['id'], '_wp_attachment_image_alt', true ) );
				}
			}

			// CSS filters
			$image_classes[] = 'css-filter';

			if ( $link_to === 'lightbox' ) {
				$image_classes[] = 'bricks-lightbox';
			}

			$this->set_attribute( "img-$index", 'class', $image_classes );
		}

		// Item sizer (Isotope requirement)
		$item_sizer_classes = [ 'bricks-isotope-sizer' ];

		$this->set_attribute( 'item-sizer', 'class', $item_sizer_classes );

		// Used in case link_to = lightbox. It needs to be random because if the gallery is placed inside of a loop the lightbox fails (@since 1.5.1)
		$lightbox_id = Helpers::generate_random_id( false );

		// STEP: Render

		$layout = isset( $settings['layout'] ) ? $settings['layout'] : 'grid';
		$gutter = isset( $settings['gutter'] ) ? $settings['gutter'] : '0px';

		echo "<ul {$this->render_attributes( '_root' )}>";

		foreach ( $images as $index => $item ) {
			$close_a_tag = false;
			$caption     = isset( $settings['caption'] ) && isset( $item['id'] ) ? wp_get_attachment_caption( $item['id'] ) : false;

			echo "<li {$this->render_attributes( "item-{$index}" )}>";

			if ( $link_to === 'attachment' && isset( $item['id'] ) ) {
				$close_a_tag = true;

				echo '<a href="' . get_permalink( $item['id'] ) . '" target="_blank">';
			} elseif ( $link_to === 'media' ) {
				$close_a_tag = true;

				echo '<a href="' . esc_url( $item['url'] ) . '" target="_blank">';
			} elseif ( $link_to === 'custom' && isset( $settings['linkCustom'][ $index ]['link'] ) ) {
				$close_a_tag = true;

				$this->set_link_attributes( "a-$index", $settings['linkCustom'][ $index ]['link'] );

				echo "<a {$this->render_attributes( "a-$index" )}>";
			}

			// Lightbox attributes
			elseif ( $link_to === 'lightbox' ) {
				$lightbox_image_size = ! empty( $settings['lightboxImageSize'] ) ? $settings['lightboxImageSize'] : 'full';
				$lightbox_image      = wp_get_attachment_image_src( $item['id'], $lightbox_image_size );
				$lightbox_image      = ! empty( $lightbox_image ) && is_array( $lightbox_image ) ? $lightbox_image : [ $item['url'], 800, 600 ];

				$this->set_attribute( "img-$index", 'data-bricks-lightbox-source', $lightbox_image[0] );
				$this->set_attribute( "img-$index", 'data-bricks-lightbox-width', $lightbox_image[1] );
				$this->set_attribute( "img-$index", 'data-bricks-lightbox-height', $lightbox_image[2] );
				$this->set_attribute( "img-$index", 'data-bricks-lightbox-index', $index );
				$this->set_attribute( "img-$index", 'data-bricks-lightbox-id', $lightbox_id );
			}

			if ( $layout === 'masonry' ) {
				$image_atts = [ 'class' => implode( ' ', $image_classes ) ];

				if ( ! empty( $lightbox_image ) ) {
					$image_atts['data-bricks-lightbox-index']  = $index;
					$image_atts['data-bricks-lightbox-id']     = $lightbox_id;
					$image_atts['data-bricks-lightbox-source'] = $lightbox_image[0];
					$image_atts['data-bricks-lightbox-width']  = $lightbox_image[1];
					$image_atts['data-bricks-lightbox-height'] = $lightbox_image[2];
				}

				echo wp_get_attachment_image( $item['id'], $size, false, $image_atts );
			} else {
				$this->set_attribute( "img-$index", 'class', $image_classes );

				echo "<div {$this->render_attributes( "img-$index" )}></div>";
			}

			if ( $caption ) {
				echo '<div class="bricks-image-caption css-var" style="--gutter: ' . $gutter . '">' . $caption . '</div>';
			}

			if ( $close_a_tag ) {
				echo '</a>';
			}

			echo '</li>';
		}

		if ( $layout === 'metro' ) {
			echo '<li></li>';
		}

		if ( $layout === 'masonry' || $layout === 'metro' ) {
			echo "<li {$this->render_attributes( 'item-sizer' )}></li>";
		}

		// echo '<li class="bricks-gutter-sizer"></li>';

		echo '</ul>';
	}

	public function convert_element_settings_to_block( $settings ) {
		$settings = $this->get_normalized_image_settings( $settings );

		$images     = ! empty( $settings['items']['images'] ) ? $settings['items']['images'] : false;
		$image_size = $settings['items']['size'];

		if ( ! $images ) {
			return;
		}

		$columns = isset( $settings['columns'] ) ? intval( $settings['columns'] ) : 3;

		if ( count( $images ) < $columns ) {
			$columns = count( $images );
		}

		$block = [
			'blockName'    => $this->block,
			'attrs'        => [
				'ids'      => [],
				'columns'  => $columns,
				'sizeSlug' => $image_size,
			],
			'innerContent' => [],
		];

		$image_gallery_html  = '<figure class="wp-block-gallery columns-' . esc_attr( $columns ) . ' is-cropped">';
		$image_gallery_html .= '<ul class="blocks-gallery-grid">';

		foreach ( $images as $image ) {
			$image_id = isset( $image['id'] ) && ! empty( $image['id'] ) ? intval( $image['id'] ) : false;

			if ( $image_id ) {
				$block['attrs']['ids'][] = $image_id;

				$image_url           = wp_get_attachment_image_url( $image_id, $image_size );
				$image_url_full_size = wp_get_attachment_image_url( $image_id, 'full' );

				$image_gallery_html .= '<li class="blocks-gallery-item">';
				$image_gallery_html .= '<figure>';

				$image_gallery_html .= '<img src="' . $image_url . '" alt="" data-id="' . $image_id . '" data-full-url="' . $image_url_full_size . '" data-link="' . get_permalink( $image_id ) . '" class="wp-image-' . $image_id . '"/>';

				$image_gallery_html .= '</figure>';
				$image_gallery_html .= '</li>';
			}
		}

		$image_gallery_html .= '</ul></figure>';

		$block['innerContent'] = [ $image_gallery_html ];

		return $block;
	}

	public function convert_block_to_element_settings( $block, $attributes ) {
		$image_ids  = isset( $attributes['ids'] ) ? $attributes['ids'] : [];
		$image_size = isset( $attributes['sizeSlug'] ) ? $attributes['sizeSlug'] : 'large';
		$columns    = isset( $attributes['columns'] ) ? intval( $attributes['columns'] ) : 3;

		if ( ! count( $image_ids ) ) {
			return;
		}

		$element_settings = [
			'gutter'  => 15,
			'columns' => $columns,
		];

		if ( isset( $attributes['linkTo'] ) && in_array( $attributes['linkTo'], [ 'attachment', 'media' ] ) ) {
			$element_settings['link'] = $attributes['linkTo'];
		}

		$items = [];

		foreach ( $image_ids as $image_id ) {
			$image_url = wp_get_attachment_image_url( $image_id, $image_size );

			if ( $image_id && $image_url ) {
				$items['images'][] = [
					'full' => wp_get_attachment_image_url( $image_id, 'full' ),
					'id'   => $image_id,
					'size' => $image_size,
					'url'  => $image_url,
				];
			}
		}

		if ( ! empty( $items ) ) {
			$element_settings['items'] = $items;
		}

		return $element_settings;
	}
}
