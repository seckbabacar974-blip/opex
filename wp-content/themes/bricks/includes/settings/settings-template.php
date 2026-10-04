<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Settings_Template extends Settings_Base {
	public function set_control_groups() {
		$this->control_groups['header'] = [
			'title'    => esc_html__( 'Header', 'bricks' ),
			'required' => [ 'templateType', '=', 'header', 'templateType' ],
		];

		$this->control_groups['template-conditions'] = [
			'title' => esc_html__( 'Conditions', 'bricks' ),
		];

		$this->control_groups['template-preview'] = [
			'title' => esc_html__( 'Populate Content', 'bricks' ),
		];
	}

	public function set_controls() {
		// PERFORMANCE: Run query to populate control options in builder only
		$all_terms = bricks_is_builder() ? Helpers::get_terms_options( null, null, true ) : [];
		$terms     = bricks_is_builder() ? Helpers::get_terms_options() : [];

		$registered_post_types = Helpers::get_registered_post_types();

		if ( Templates::get_template_type() === 'header' || Templates::get_template_type() === 'footer' ) {
			$registered_post_types[ BRICKS_DB_TEMPLATE_SLUG ] = esc_html__( 'Template', 'bricks' );
		}

		$supported_content_types = bricks_is_builder() ? Helpers::get_supported_content_types() : [];

		/**
		 * Header
		 */

		$this->controls['headerPosition'] = [
			'group'       => 'header',
			'label'       => esc_html__( 'Header location', 'bricks' ),
			'type'        => 'select',
			'options'     => [
				'right' => esc_html__( 'Right', 'bricks' ),
				'left'  => esc_html__( 'Left', 'bricks' ),
			],
			'inline'      => true,
			'placeholder' => esc_html__( 'Top', 'bricks' ),
		];

		$this->controls['headerWidth'] = [
			'group'       => 'header',
			'label'       => esc_html__( 'Header width', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'css'         => [
				[
					'property' => 'width',
					'selector' => '.brx-header-right #brx-header, .brx-header-left #brx-header',
				],

				// Header position: Right
				[
					'property' => 'margin-right',
					'selector' => '.brx-header-right #brx-content, .brx-header-right #brx-footer',
				],

				// Header position: Left
				[
					'property' => 'margin-left',
					'selector' => '.brx-header-left #brx-content, .brx-header-left #brx-footer',
				],
			],
			'placeholder' => '200px',
			'required'    => [ 'headerPosition', '!=', '' ],
		];

		$this->controls['headerAbsolute'] = [
			'group'      => 'header',
			'label'      => esc_html__( 'Absolute header', 'bricks' ),
			'type'       => 'checkbox',
			'css'        => [
				[
					'property' => 'position',
					'selector' => '#brx-header',
					'value'    => 'absolute',
				],
				[
					'property' => 'width',
					'selector' => '#brx-header',
					'value'    => '100%',
				],
			],
			'deprecated' => true, // @since 1.3.2 (set 'headerPos' instead)
			'required'   => [ 'headerPosition', '=', '' ],
		];

		// Sticky header

		$this->controls['headerStickySeparator'] = [
			'group'    => 'header',
			'label'    => esc_html__( 'Sticky header', 'bricks' ),
			'type'     => 'separator',
			'required' => [ 'headerPosition', '=', '' ],
		];

		$this->controls['headerSticky'] = [
			'group'    => 'header',
			'label'    => esc_html__( 'Sticky header', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [ 'headerPosition', '=', '' ],
		];

		// Position header 'relative' on page load to not cover the content below. Set to fixed on scroll.
		$this->controls['headerStickyOnScroll'] = [
			'group'    => 'header',
			'label'    => esc_html__( 'Sticky on scroll', 'bricks' ),
			'type'     => 'checkbox',
			'required' => [
				[ 'headerPosition', '=', '' ],
				[ 'headerSticky', '!=', '' ],
			],
		];

		$this->controls['headerStickySlideUpAfter'] = [
			'group'    => 'header',
			'label'    => esc_html__( 'Slide up after', 'bricks' ) . ' (px)',
			'type'     => 'number',
			'required' => [
				[ 'headerPosition', '=', '' ],
				[ 'headerSticky', '!=', '' ],
			],
		];

		$this->controls['headerStickyScrollingColor'] = [
			'group'    => 'header',
			'label'    => esc_html__( 'Scrolling text color', 'bricks' ),
			'type'     => 'color',
			'css'      => [
				[
					'property' => 'color',
					'selector' => '#brx-header.sticky.scrolling .bricks-nav-menu > li > a',
				],
				[
					'property' => 'color',
					'selector' => '#brx-header.sticky.scrolling .brxe-site-search',
				],
				[
					'property' => 'color',
					'selector' => '#brx-header.sticky.scrolling .brxe-nav-menu .bricks-mobile-menu-toggle'
				],
			],
			'required' => [
				[ 'headerPosition', '=', '' ],
				[ 'headerSticky', '!=', '' ],
			],
		];

		$this->controls['headerStickyScrollingBackground'] = [
			'group'    => 'header',
			'label'    => esc_html__( 'Scrolling background', 'bricks' ),
			'type'     => 'background',
			'css'      => [
				[
					'property' => 'background',
					'selector' => '
						#brx-header.sticky.scrolling > .brxe-section,
						#brx-header.sticky.scrolling > .brxe-container,
						#brx-header.sticky.scrolling > .brxe-block,
						#brx-header.sticky.scrolling > .brxe-div',
				],
			],
			'required' => [
				[ 'headerPosition', '=', '' ],
				[ 'headerSticky', '!=', '' ],
			],
		];

		$this->controls['headerStickyScrollingBoxShadow'] = [
			'group'    => 'header',
			'label'    => esc_html__( 'Scrolling box shadow', 'bricks' ),
			'type'     => 'box-shadow',
			'css'      => [
				[
					'property' => 'box-shadow',
					'selector' => '
						#brx-header.sticky.scrolling:not(.slide-up) > .brxe-section,
						#brx-header.sticky.scrolling:not(.slide-up) > .brxe-container,
						#brx-header.sticky.scrolling:not(.slide-up) > .brxe-block,
						#brx-header.sticky.scrolling:not(.slide-up) > .brxe-div',
				],
			],
			'required' => [
				[ 'headerPosition', '=', '' ],
				[ 'headerSticky', '!=', '' ],
			],
		];

		$this->controls['headerStickyTransition'] = [
			'group'          => 'header',
			'label'          => esc_html__( 'Transition', 'bricks' ),
			'type'           => 'text',
			'placeholder'    => 'background-color 0.2s, transform 0.4s',
			'hasDynamicData' => false,
			'css'            => [
				[
					'selector' => '#brx-header.sticky',
					'property' => 'transition',
				],
				[
					'selector' => '
						#brx-header.sticky > .brxe-section,
						#brx-header.sticky > .brxe-container,
						#brx-header.sticky > .brxe-block,
						#brx-header.sticky > .brxe-div',
					'property' => 'transition',
				],
				[
					'selector' => '#brx-header.sticky .bricks-nav-menu > li > a',
					'property' => 'transition',
				],
			],
			'required'       => [
				[ 'headerPosition', '=', '' ],
				[ 'headerSticky', '!=', '' ],
			],
		];

		/**
		 * Template Conditions
		 */

		$this->controls['templateConditionsInfo'] = [
			'group'   => 'template-conditions',
			'type'    => 'info',
			'content' => esc_html__( 'Set condition(s) to show template on specific areas of your site.', 'bricks' ),
		];

		$this->controls['templateConditions'] = [
			'group'         => 'template-conditions',
			'type'          => 'repeater',
			'placeholder'   => esc_html__( 'Condition', 'bricks' ),
			'titleProperty' => 'main',
			'fields'        => [
				'main'                        => [
					'type'        => 'select',
					'options'     => [
						'any'         => esc_html__( 'Entire website', 'bricks' ),
						'frontpage'   => esc_html__( 'Front page', 'bricks' ),
						'postType'    => esc_html__( 'Post type', 'bricks' ),
						'archiveType' => esc_html__( 'Archive', 'bricks' ),
						'search'      => esc_html__( 'Search results', 'bricks' ),
						'error'       => esc_html__( 'Error page', 'bricks' ),
						'terms'       => esc_html__( 'Terms', 'bricks' ),
						'ids'         => esc_html__( 'Individual', 'bricks' ),
					],
					'placeholder' => esc_html__( 'Select', 'bricks' ),
				],

				'archiveType'                 => [
					'type'        => 'select',
					'label'       => esc_html__( 'Archive type', 'bricks' ),
					'options'     => [
						'any'      => esc_html__( 'All archives', 'bricks' ),
						'postType' => esc_html__( 'Post type', 'bricks' ),
						'author'   => esc_html__( 'Author', 'bricks' ),
						'date'     => esc_html__( 'Date', 'bricks' ),
						'term'     => esc_html__( 'Categories & Tags', 'bricks' ),
					],
					'multiple'    => true,
					'placeholder' => esc_html__( 'Select archive type', 'bricks' ),
					'required'    => [ 'main', '=', 'archiveType' ],
				],

				'archivePostTypes'            => [
					'type'        => 'select',
					'label'       => esc_html__( 'Archive post types', 'bricks' ),
					'options'     => $registered_post_types,
					'multiple'    => true,
					'placeholder' => esc_html__( 'Select post type', 'bricks' ),
					'description' => esc_html__( 'Leave empty to apply template to all post types.', 'bricks' ),
					'required'    => [ 'archiveType', '=', 'postType' ],
				],

				'archiveTerms'                => [
					'type'        => 'select',
					'label'       => esc_html__( 'Archive terms', 'bricks' ),
					'options'     => $all_terms,
					'multiple'    => true,
					'searchable'  => true,
					'placeholder' => esc_html__( 'Select archive term', 'bricks' ),
					'description' => esc_html__( 'Leave empty to apply template to all archive terms.', 'bricks' ),
					'required'    => [ 'archiveType', '=', 'term' ],
				],

				'archiveTermsIncludeChildren' => [
					'type'     => 'checkbox',
					'label'    => esc_html__( 'Apply to child terms', 'bricks' ),
					'required' => [ 'archiveType', '=', 'term' ],
				],

				'postType'                    => [
					'type'        => 'select',
					'label'       => esc_html__( 'Post type', 'bricks' ),
					'options'     => $registered_post_types,
					'multiple'    => true,
					'placeholder' => esc_html__( 'Select post type', 'bricks' ),
					'required'    => [ 'main', '=', 'postType' ],
				],

				'terms'                       => [
					'type'        => 'select',
					'label'       => esc_html__( 'Terms', 'bricks' ),
					'options'     => $terms,
					'multiple'    => true,
					'searchable'  => true,
					'placeholder' => esc_html__( 'Select terms', 'bricks' ),
					'required'    => [ 'main', '=', 'terms' ],
				],

				'ids'                         => [
					'type'        => 'select',
					'label'       => esc_html__( 'Individual', 'bricks' ),
					'optionsAjax' => [
						'action'   => 'bricks_get_posts',
						'postType' => 'any',
					],
					'multiple'    => true,
					'searchable'  => true,
					'placeholder' => esc_html__( 'Select individual', 'bricks' ),
					'required'    => [ 'main', '=', 'ids' ],
				],

				'idsIncludeChildren'          => [
					'type'     => 'checkbox',
					'label'    => esc_html__( 'Apply to child pages', 'bricks' ),
					'required' => [ 'main', '=', 'ids' ],
				],

				'exclude'                     => [
					'type'  => 'checkbox',
					'label' => esc_html__( 'Exclude', 'bricks' ),
				],
			],
			// 'description' => esc_html__( 'Set condition(s) to show this template on specific areas of your site.', 'bricks' ),
		];

		/**
		 * Template Preview Content (Only visible when editing header or footer templates)
		 */

		$this->controls['templatePreviewInfo'] = [
			'group'   => 'template-preview',
			'type'    => 'info',
			'content' => esc_html__( 'Select type of content to show on canvas, then click "APPLY PREVIEW" to show the selected content on the canvas.', 'bricks' ),
		];

		$this->controls['templatePreviewType'] = [
			'group'       => 'template-preview',
			'type'        => 'select',
			'label'       => esc_html__( 'Content type', 'bricks' ),
			'options'     => $supported_content_types,
			'searchable'  => true,
			'placeholder' => esc_html__( 'Select content type', 'bricks' ),
		];

		$this->controls['templatePreviewAuthor'] = [
			'group'       => 'template-preview',
			'type'        => 'select',
			'label'       => esc_html__( 'Author', 'bricks' ),
			'optionsAjax' => [
				'action' => 'bricks_get_users',
			],
			'searchable'  => true,
			'placeholder' => esc_html__( 'Select author', 'bricks' ),
			'required'    => [ 'templatePreviewType', '=', 'archive-author' ],
		];

		$this->controls['templatePreviewPostType'] = [
			'group'       => 'template-preview',
			'type'        => 'select',
			'label'       => esc_html__( 'Post type', 'bricks' ),
			'options'     => $registered_post_types,
			'searchable'  => true,
			'placeholder' => esc_html__( 'Select post type', 'bricks' ),
			'required'    => [ 'templatePreviewType', '=', 'archive-cpt' ],
		];

		$this->controls['templatePreviewTerm'] = [
			'group'       => 'template-preview',
			'type'        => 'select',
			'label'       => esc_html__( 'Term', 'bricks' ),
			'options'     => $terms,
			'searchable'  => true,
			'placeholder' => esc_html__( 'Select term', 'bricks' ),
			'required'    => [ 'templatePreviewType', '=', 'archive-term' ],
		];

		$this->controls['templatePreviewSearchTerm'] = [
			'group'       => 'template-preview',
			'type'        => 'text',
			'label'       => esc_html__( 'Search term', 'bricks' ),
			'searchable'  => true,
			'placeholder' => esc_html__( 'Enter search term', 'bricks' ),
			'required'    => [ 'templatePreviewType', '=', 'search' ],
		];

		$this->controls['templatePreviewPostId'] = [
			'group'       => 'template-preview',
			'type'        => 'select',
			'label'       => esc_html__( 'Single post/page', 'bricks' ),
			'optionsAjax' => [
				'action'   => 'bricks_get_posts',
				'postType' => 'any',
			],
			'searchable'  => true,
			'placeholder' => esc_html__( 'Select', 'bricks' ),
			'required'    => [ 'templatePreviewType', '=', 'single' ],
		];

		$this->controls['apply'] = [
			'group'  => 'template-preview',
			'type'   => 'apply',
			'reload' => true,
			'label'  => esc_html__( 'Apply preview', 'bricks' ),
		];
	}
}
