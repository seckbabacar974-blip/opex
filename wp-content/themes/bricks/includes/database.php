<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Database {
	public static $posts_per_page   = 0;
	public static $active_templates = [
		'header'  => 0,
		'footer'  => 0,
		'content' => 0,
		'section' => 0, // Use in "Template" element
		'archive' => 0,
		'search'  => 0,
		'error'   => 0,
	];

	public static $default_template_types = [
		'header',
		'footer',
		'archive',
		'search',
		'error',
		'wc_archive',
		'wc_product',
		'wc_cart',
		'wc_cart_empty',
		'wc_form_checkout',
		'wc_form_pay',
		'wc_thankyou',
		'wc_order_receipt',
	];

	public static $header_position = 'top';
	public static $global_data     = [];
	public static $page_data       = [];
	public static $global_settings = [];
	public static $page_settings   = [];

	public function __construct() {
		self::get_global_data();

		add_action( 'pre_get_posts', [ $this, 'custom_pagination' ] );

		// Set active templates
		add_action( 'wp', [ $this, 'set_active_templates' ] );

		// Set page data (AJAX)
		add_action( 'wp_loaded', [ $this, 'set_ajax_page_data' ] );

		// Set page data (no AJAX)
		add_action( 'wp', [ $this, 'set_page_data' ] );

		// Set page data on REST API calls
		add_action( 'rest_api_init', [ $this, 'set_page_data' ] );
	}

	/**
	 * Customize WP_Query: Set 'posts_per_page' for archive/search/error template pages
	 */
	public function custom_pagination( $query ) {
		if ( bricks_is_builder() || is_admin() || ! $query->is_main_query() || ! $query->is_paged ) {
			return;
		}

		$post_id = 0;

		// Check: Is Bricks template?
		// NOTE: Not working as WP redirects the singular with /page/X to singular URL.
		if (
			$query->is_singular &&
			isset( $query->query_vars['post_type'] ) &&
			$query->query_vars['post_type'] == BRICKS_DB_TEMPLATE_SLUG &&
			! empty( $query->query_vars[ BRICKS_DB_TEMPLATE_SLUG ] )
		) {
			$post = get_page_by_path( $query->query_vars[ BRICKS_DB_TEMPLATE_SLUG ], OBJECT, BRICKS_DB_TEMPLATE_SLUG );

			$post_id = isset( $post->ID ) ? $post->ID : 0;
		}

		// Check: Is Archive page?
		elseif ( $query->is_archive || $query->is_search || $query->is_error || $query->is_home ) {

			// Set active templates
			self::set_active_templates( $query );

			$post_id = ! empty( self::$active_templates['content'] ) ? self::$active_templates['content'] : 0;
		}

		if ( $post_id ) {
			$bricks_data = get_post_meta( $post_id, BRICKS_DB_PAGE_CONTENT, true );

			if ( is_array( $bricks_data ) ) {
				// Loop through elements to get "Posts" element setting value for 'posts_per_page'
				foreach ( $bricks_data as $element ) {
					if ( ! empty( $element['settings']['query']['posts_per_page'] ) ) {
						$posts_per_page = $element['settings']['query']['posts_per_page'];

						$query->set( 'posts_per_page', $posts_per_page );
					}
				}
			}
		}
	}

	/**
	 * Set active templates for use throughout the theme
	 */
	public static function set_active_templates( $post_id = 0 ) {
		// Check if set_active_templates already ran
		if ( isset( self::$active_templates['post_id'] ) ) {
			return;
		}

		if ( ! $post_id || is_object( $post_id ) ) {
			$post_id = get_the_ID();
		}

		// NOTE: Set post ID to posts page. Code will try to find templates for the page defined as the blog page
		if ( is_home() ) {
			$post_id = get_option( 'page_for_posts' );
		}

		$post_id = intval( $post_id );

		$post_type = get_post_type( $post_id );

		$preview_type = ''; // Only applicable to templates

		$content_type = 'content'; // = default content type

		// Check if post is Bricks template
		if ( is_singular( BRICKS_DB_TEMPLATE_SLUG ) ) {
			$template_type = get_post_meta( $post_id, BRICKS_DB_TEMPLATE_TYPE, true );

			if ( in_array( $template_type, [ 'header', 'footer' ] ) ) {
				self::$active_templates[ $template_type ] = $post_id;

				$preview_type = Helpers::get_template_setting( 'templatePreviewType', $post_id );

				switch ( $preview_type ) {
					case 'single':
						$preview_id = Helpers::get_template_setting( 'templatePreviewPostId', $post_id );

						$content_type                      = 'content';
						self::$active_templates['content'] = $preview_id;
						break;

					case 'search':
						$content_type = 'search';
						break;

					case 'archive-recent-posts':
					case 'archive-author':
					case 'archive-date':
					case 'archive-cpt':
					case 'archive-term':
						$content_type = 'archive';
						break;
				}

			} else {
				self::$active_templates['content'] = $post_id;
				$content_type                      = $template_type;
			}
		}

		// All other cases (builder & frontend)
		else {
			// Find content type needed given the current page load query
			$tag_templates = [
				'is_404'               => 'error',
				'is_search'            => 'search',
				'is_home'              => 'content',
				'is_front_page'        => 'content',
				'is_singular'          => 'content',
				'is_product_taxonomy'  => 'wc_archive',
				'is_post_type_archive' => 'archive',
				'is_tax'               => 'archive',
				'is_author'            => 'archive',
				'is_date'              => 'archive',
				'is_archive'           => 'archive',
			];

			foreach ( $tag_templates as $tag => $type ) {
				if ( function_exists( $tag ) && call_user_func( $tag ) ) {
					$content_type = $type;

					if ( 'content' != $type ) {
						$post_type = '';
						$post_id   = 0;
					}

					break;
				}
			}
		}

		// NOTE: Undocumented
		$content_type = apply_filters( 'bricks/database/content_type', $content_type, $post_id );

		// NOTE: Undocumented
		$post_id = apply_filters( 'bricks/builder/data_post_id', $post_id );

		self::$active_templates['post_id']      = $post_id;
		self::$active_templates['post_type']    = $post_type;
		self::$active_templates['content_type'] = $content_type;

		// Get all available templates
		$template_ids = self::get_all_templates_by_type();

		// Preview id is only set if template is using populate content as single (with templatePreviewPostId)
		$preview_id = isset( $preview_id ) ? $preview_id : $post_id;

		// For each template part, try to find the best template available
		foreach ( [ 'header', 'footer', 'content' ] as $template_part ) {
			if ( ! empty( self::$active_templates[ $template_part ] ) ) {
				continue;
			}

			self::$active_templates[ $template_part ] = self::find_template_id( $template_ids, $template_part, $content_type, $preview_id, $preview_type );
		}

		// If $content_type != header, footer, content, section; set $active_template = content
		if ( isset( $content_type ) && ! in_array( $content_type, [ 'header', 'footer', 'section', 'content' ] ) ) {
			self::$active_templates[ $content_type ] = self::$active_templates['content'];
		}

		// Set header position (to use in bricksData.headerPosition)
		if ( self::$active_templates['header'] > 0 ) {
			$header_position       = Helpers::get_template_setting( 'headerPosition', intval( self::$active_templates['header'] ) );
			self::$header_position = isset( $header_position ) && ! empty( $header_position ) ? $header_position : 'top';
		}

		// No templates defined, set page/cpt content if Bricks is supported
		if ( ! empty( $post_id ) && Helpers::is_post_type_supported( $post_id ) && empty( self::$active_templates['content'] ) ) {
			self::$active_templates['content'] = $post_id;
		}
	}

	/**
	 * Finds the most suitable template id for a specific context
	 *
	 * @param array  $template_ids (organized by type)
	 * @param string $template_part (header, footer or content)
	 * @param string $content_type (what type of content is expected: content, archive, search, error)
	 * @param string $post_id (current post_id or preview_id)
	 * @param string $preview_type (if template, and populate content is set)
	 *
	 * @return void
	 */
	public static function find_template_id( $template_ids, $template_part, $content_type, $post_id, $preview_type ) {
		$found_templates = []; // Hold all the found template ids for the context, with score 0.low XX.high [score=>template id]

		$disable_default_templates = self::get_setting( 'defaultTemplatesDisabled', false );

		$post_type = get_post_type( $post_id );

		// Loop for all the templates and template conditions and assign scores
		// 0 - Default (no condition set)
		// 1 - Default to a specific template type (I'm looking for a search template, and this is type search)
		// 2 - Entire website (condition = any)
		// 8 - Terms, specific archives, children of specific Post ID
		// 9 - Front page
		// 10 - Specific Post ID (best match)

		// 'body' list includes all template types != header, footer & section
		$template_loop_type = $template_part === 'content' ? 'body' : $template_part;

		if ( empty( $template_ids[ $template_loop_type ] ) ) {
			return 0;
		}

		// Check template conditions
		foreach ( $template_ids[ $template_loop_type ] as $template_id ) {
			$template_conditions = Helpers::get_template_setting( 'templateConditions', $template_id );

			if ( ! $template_conditions ) {
				if ( ! $disable_default_templates ) {
					// No conditions, if defaults are possible, set it as default (but don't set a Search template as fallback of a Page content)
					if ( in_array( $template_part, [ 'header', 'footer' ] ) ) {
						$found_templates[0] = $template_id;
					}

					// If template_part is content, and this template type = content_type (search = search) then it might be a good default
					if ( 'content' === $template_part && 'content' !== $content_type && ! empty( $template_ids[ $content_type ] ) && in_array( $template_id, $template_ids[ $content_type ] ) ) {
						$found_templates[1] = $template_id;
					}
				}

				continue;
			}

			$found_templates = self::screen_conditions( $found_templates, $template_id, $template_conditions, $post_id, $preview_type );
		}

		// Return template id with highest score.
		if ( ! empty( $found_templates ) ) {
			$max = max( array_keys( $found_templates ) );

			return $found_templates[ $max ];
		}

		// No template found
		return 0;
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public static function get_all_templates_by_type() {
		// Last changed timestamp is set on Templates::flush_templates_cache()
		$last_changed = wp_cache_get_last_changed( 'bricks_' . BRICKS_DB_TEMPLATE_SLUG );

		$cache_key = 'all_templates_' . $last_changed;

		$output = wp_cache_get( $cache_key, 'bricks' );

		if ( $output === false ) {
			$template_ids = get_posts(
				[
					'post_type'      => BRICKS_DB_TEMPLATE_SLUG,
					'posts_per_page' => -1,
					'meta_query'     => [
						[
							'key'     => BRICKS_DB_TEMPLATE_TYPE,
							'compare' => 'EXISTS',
						],
					],
					'post_status'    => 'publish',
					'fields'         => 'ids',
				]
			);

			$output = [];

			// Organize templates by type
			foreach ( $template_ids as $t_id ) {
				$type              = get_post_meta( $t_id, BRICKS_DB_TEMPLATE_TYPE, true );
				$output[ $type ][] = $t_id;

				if ( ! in_array( $type, [ 'header', 'footer', 'section' ] ) ) {
					$output['body'][] = $t_id; // Adds to the 'body' template type all the other types like Content, Archive, Search Results, Error Page as they are a kind of body content
				}
			}

			wp_cache_set( $cache_key, $output, 'bricks', DAY_IN_SECONDS );
		}

		return $output;
	}

	/**
	 * Set default header/footer template
	 *
	 * If no template with matching templateCondition(s) has been set.
	 *
	 * Can be disabled via admin setting 'defaultTemplatesDisabled'.
	 *
	 * @since 1.0
	 */
	public static function set_default_template( $template_type = '' ) {
		if ( ! $template_type ) {
			return;
		}

		$disable_default_templates = self::get_setting( 'defaultTemplatesDisabled', false );

		// Return if 'defaultTemplatesDisabled' is set
		$current_template_type = get_post_meta( get_the_ID(), BRICKS_DB_TEMPLATE_TYPE, true );

		if ( $disable_default_templates && $current_template_type !== $template_type ) {
			return;
		}

		$template_ids = get_posts(
			[
				'post_type'      => BRICKS_DB_TEMPLATE_SLUG,
				'posts_per_page' => -1,
				'meta_query'     => [
					[
						'key'   => BRICKS_DB_TEMPLATE_TYPE,
						'value' => $template_type,
					],
				],
				'post_status'    => 'publish',
				'fields'         => 'ids',
			]
		);

		$template_id = count( $template_ids ) ? $template_ids[0] : false;

		if ( $template_id ) {
			self::$active_templates[ $template_type ] = intval( $template_id );
		}
	}

	/**
	 * Helper function to screen a set of template or theme style conditions and check if they apply given the context
	 *
	 * @param array   $found Holds array of found object IDs (the key is the score)
	 * @param string  $object_id Could be template_id or the style_id
	 * @param array   $conditions Template or Theme Style conditions
	 * @param integer $post_id (Real or Preview)
	 * @param string  $preview_type
	 *
	 * @return array Found conditions array ($score => $object_id)
	 */
	public static function screen_conditions( $found, $object_id, $conditions, $post_id, $preview_type ) {
		$post_type = get_post_type( $post_id );

		$is_valid = true; // Used to exclude this object if an excluding condition applies

		$scores = []; // Holds scores of this object_id

		foreach ( $conditions as $condition ) {
			if ( ! $is_valid ) {
				break;
			}

			// Check if main template condition is set
			if ( ! isset( $condition['main'] ) ) {
				continue;
			}

			$exclude = isset( $condition['exclude'] );

			if ( ! empty( $post_id ) ) {
				// 1. Check if template was set for a specific post ID or children
				if ( $condition['main'] === 'ids' && isset( $condition['ids'] ) ) {

					// Specific post ID
					if ( in_array( $post_id, $condition['ids'] ) ) {
						$is_valid = ! $exclude;
						$scores[] = 10;
					}

					// Apply to child pages
					elseif ( isset( $condition['idsIncludeChildren'] ) ) {
						$ancestors = get_post_ancestors( $post_id );

						foreach ( $ancestors as $ancestor_id ) {
							if ( in_array( $ancestor_id, $condition['ids'] ) ) {
								$is_valid = ! $exclude;
								$scores[] = 8; // Less important than a template set for a specific ID
								break;
							}
						}
					}
				}

				// 2. Check if template was set for a specific term assigned to the post
				if ( $condition['main'] === 'terms' && isset( $condition['terms'] ) ) {
					$terms = $condition['terms'];

					foreach ( $terms as $term ) {
						$tax_term = explode( '::', $term );
						$taxonomy = $tax_term[0];
						$term     = $tax_term[1];

						$post_terms = wp_get_post_terms( $post_id, $taxonomy, [ 'fields' => 'ids' ] );

						if ( is_array( $post_terms ) && in_array( $term, $post_terms ) ) {
							$is_valid = ! $exclude;
							$scores[] = 8;
						}
					}
				}

				// 3. Check if template applies to a specific post type
				if ( $condition['main'] === 'postType' && isset( $condition['postType'] ) && in_array( $post_type, $condition['postType'] ) ) {
					$is_valid = ! $exclude;
					$scores[] = 7;
				}
			}

			// Archive (any/author/data/term)
			if ( is_archive() && $condition['main'] === 'archiveType' ) {
				if ( ! isset( $condition['archiveType'] ) ) {
					continue;
				}

				// Archive pages include category, tag, author, date, custom post type, and custom taxonomy based archives.
				if ( in_array( 'any', $condition['archiveType'] ) && ( is_archive() || strpos( $preview_type, 'archive' ) !== false ) ) {
					$is_valid = ! $exclude;
					$scores[] = 3;
				}

				// This condition allows for multiple values. Since is_archive includes all the following conditions we need to test them as well
				if ( in_array( 'postType', $condition['archiveType'] ) && ( is_post_type_archive() || $preview_type === 'archive-cpt' ) ) {
					if ( empty( $condition['archivePostTypes'] ) ) {
						$is_valid = ! $exclude;
						$scores[] = 7;
					} else {
						// Previewing a template with content set to a CPT archive
						if ( $preview_type === 'archive-cpt' ) {
							$preview_cpt = Helpers::get_template_setting( 'templatePreviewPostType', $post_id );

							if ( $preview_cpt && in_array( $preview_cpt, $condition['archivePostTypes'] ) ) {
								$is_valid = ! $exclude;
								$scores[] = 8;
							}
						}
						// or, check if the post type archive matches the post type condition
						elseif ( is_post_type_archive( $condition['archivePostTypes'] ) ) {
							$is_valid = ! $exclude;
							$scores[] = 8;
						}
					}
				} elseif ( in_array( 'author', $condition['archiveType'] ) && ( is_author() || $preview_type === 'archive-author' ) ) {
					$is_valid = ! $exclude;
					$scores[] = 8;
				} elseif ( in_array( 'date', $condition['archiveType'] ) && ( is_date() || $preview_type === 'archive-date' ) ) {
					$is_valid = ! $exclude;
					$scores[] = 8;
				} elseif ( in_array( 'term', $condition['archiveType'] ) && ( is_category() || is_tag() || is_tax() || $preview_type === 'archive-term' ) ) {
					// Apply template to selected archive terms
					if ( isset( $condition['archiveTerms'] ) && is_array( $condition['archiveTerms'] ) ) {

						// Previewing a template, with populate content set to archive of term
						if ( $preview_type === 'archive-term' ) {
							// Note the post_id here is the template post Id (because in this archive situation the preview_id was not set)
							$preview_term = Helpers::get_template_setting( 'templatePreviewTerm', $post_id );

							if ( ! empty( $preview_term ) ) {
								$preview_term     = explode( '::', $preview_term );
								$queried_taxonomy = isset( $preview_term[0] ) ? $preview_term[0] : '';
								$queried_term_id  = isset( $preview_term[1] ) ? intval( $preview_term[1] ) : '';
							}
						}

						// All the other situations in frontend: is_category() || is_tag() || is_tax()
						else {
							$queried_object = get_queried_object();

							if ( is_object( $queried_object ) ) {
								$queried_term_id  = intval( $queried_object->term_id );
								$queried_taxonomy = $queried_object->taxonomy;
							}
						}

						// Check if queried taxonomy and term_id matches any of the selected archive terms
						if ( ! empty( $queried_term_id ) && ! empty( $queried_taxonomy ) ) {
							foreach ( $condition['archiveTerms'] as $archive_term ) {
								$term_parts = explode( '::', $archive_term );
								$taxonomy   = $term_parts[0];
								$term_id    = $term_parts[1];

								if ( $queried_taxonomy === $taxonomy ) {
									if ( $queried_term_id === intval( $term_id ) ) {
										$is_valid = ! $exclude;
										$scores[] = 8;
										break;
									}

									// Applied for taxonomy::all (all terms of a taxonomy)
									elseif ( 'all' == $term_id ) {
										$is_valid = ! $exclude;
										$scores[] = 7;
										break;
									}

									// The condition includes child terms, check if the queried term id is child of the term id set in the condition
									elseif ( isset( $condition['archiveTermsIncludeChildren'] ) && term_is_ancestor_of( $term_id, $queried_term_id, $queried_taxonomy ) ) {
										$is_valid = ! $exclude;
										$scores[] = 8;
										break;
									}
								}
							}
						}
					}

					// Apply template to all archives terms
					else {
						$is_valid = ! $exclude;
						$scores[] = 4;
					}
				}

			} // End archive test

			// Check for search
			elseif ( $condition['main'] === 'search' && ( is_search() || $preview_type === 'search' ) ) {
				$is_valid = ! $exclude;
				$scores[] = 8;
			}

			// Check for error
			elseif ( $condition['main'] === 'error' && ( is_404() || $preview_type === 'error' ) ) {
				$is_valid = ! $exclude;
				$scores[] = 8;
			}

			// Check for front page (it might compete with single post rules)
			if ( $condition['main'] === 'frontpage' && is_front_page() ) {
				$is_valid = ! $exclude;
				$scores[] = 9;
			}

			// Check for entire website
			if ( $condition['main'] === 'any' ) {
				$is_valid = ! $exclude;
				$scores[] = 2;
			}
		}

		if ( $is_valid ) {
			$scores = array_unique( $scores );

			foreach ( $scores as $score ) {
				$found[ $score ] = $object_id;
			}
		}

		return $found;
	}

	/**
	 * Get template elements
	 *
	 * @since 1.0
	 */
	public static function get_template_data( $content_type ) {
		switch ( $content_type ) {
			case 'header':
				$meta_key = BRICKS_DB_PAGE_HEADER;

				if ( isset( self::$page_settings['headerDisabled'] ) ) {
					return;
				}
				break;

			case 'footer':
				$meta_key = BRICKS_DB_PAGE_FOOTER;

				if ( isset( self::$page_settings['footerDisabled'] ) ) {
					return;
				}
				break;

			default:
				$meta_key = BRICKS_DB_PAGE_CONTENT;
				break;
		}

		$template_id = isset( self::$active_templates[ $content_type ] ) ? self::$active_templates[ $content_type ] : false;

		// No template found: Return Bricks content data
		if (
			! is_archive() &&
			! is_search() &&
			! $template_id &&
			$content_type !== 'header' &&
			$content_type !== 'footer'
		) {
			$data = get_post_meta( get_the_ID(), BRICKS_DB_PAGE_CONTENT, true );

			return $data;
		}

		$data = get_post_meta( $template_id, $meta_key, true );

		return $data;
	}

	/**
	 * Get Bricks data by post_id and content_area (header/content/footer)
	 *
	 * @since 1.0
	 */
	public static function get_data( $post_id = 0, $content_area = '' ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$meta_key = self::get_bricks_data_key( $content_area );

		$elements = get_post_meta( $post_id, $meta_key, true );

		return is_array( $elements ) ? $elements : [];
	}

	/**
	 * Get the Bricks data key for a specific post_id and template type (header/content/footer)
	 *
	 * @since 1.5.1
	 *
	 * @param integer $post_id
	 * @param string  $content_area
	 * @return string
	 */
	public static function get_bricks_data_key( $content_area = '' ) {
		switch ( $content_area ) {
			case 'header':
				$meta_key = BRICKS_DB_PAGE_HEADER;
				break;

			case 'footer':
				$meta_key = BRICKS_DB_PAGE_FOOTER;
				break;

			default:
				$meta_key = BRICKS_DB_PAGE_CONTENT;
				break;
		}

		return $meta_key;
	}

	/**
	 * Get global settings from options table
	 *
	 * @since 1.0
	 */
	public static function get_setting( $key, $default = false ) {
		return isset( self::$global_settings[ $key ] ) ? self::$global_settings[ $key ] : $default;
	}

	/**
	 * Get global data from options table
	 *
	 * @since 1.0
	 */
	public static function get_global_data() {
		// Color palette
		if ( is_multisite() && BRICKS_MULTISITE_USE_MAIN_SITE_COLOR_PALETTE ) {
			self::$global_data['colorPalette'] = get_blog_option( get_main_site_id(), BRICKS_DB_COLOR_PALETTE, [] );
		} else {
			self::$global_data['colorPalette'] = get_option( BRICKS_DB_COLOR_PALETTE, [] );
		}

		// Global classes
		if ( is_multisite() && BRICKS_MULTISITE_USE_MAIN_SITE_CLASSES ) {
			self::$global_data['globalClasses'] = get_blog_option( get_main_site_id(), BRICKS_DB_GLOBAL_CLASSES, [] );
		} else {
			self::$global_data['globalClasses'] = get_option( BRICKS_DB_GLOBAL_CLASSES, [] );
		}

		// Builder: Global classes locked (@since 1.4)
		if ( bricks_is_builder() ) {
			if ( is_multisite() && BRICKS_MULTISITE_USE_MAIN_SITE_CLASSES ) {
				self::$global_data['globalClassesLocked'] = get_blog_option( get_main_site_id(), BRICKS_DB_GLOBAL_CLASSES_LOCKED, [] );
			} else {
				self::$global_data['globalClassesLocked'] = get_option( BRICKS_DB_GLOBAL_CLASSES_LOCKED, [] );
			}
		}

		if ( is_multisite() && BRICKS_MULTISITE_USE_MAIN_SITE_CLASSES ) {
			self::$global_data['pseudoClasses'] = get_blog_option( get_main_site_id(), BRICKS_DB_PSEUDO_CLASSES, [] );
		} else {
			self::$global_data['pseudoClasses'] = get_option( BRICKS_DB_PSEUDO_CLASSES, Builder::default_pseudo_classes() );
		}

		// Global elements
		if ( is_multisite() && BRICKS_MULTISITE_USE_MAIN_SITE_GLOBAL_ELEMENTS ) {
			self::$global_data['elements'] = get_blog_option( get_main_site_id(), BRICKS_DB_GLOBAL_ELEMENTS, [] );
		} else {
			self::$global_data['elements'] = get_option( BRICKS_DB_GLOBAL_ELEMENTS, [] );
		}

		// Global settings
		self::$global_data['settings'] = get_option( BRICKS_DB_GLOBAL_SETTINGS, [] );

		// Remove slashes from custom CSS & JS
		if ( is_array( self::$global_data['settings'] ) ) {
			self::$global_data['settings'] = stripslashes_deep( self::$global_data['settings'] );
		}

		// Set global gettings
		self::$global_settings = self::$global_data['settings'];
	}

	/**
	 * Set page data needed for AJAX calls (builder)
	 *
	 * @since 1.3
	 */
	public static function set_ajax_page_data() {
		if (
			! bricks_is_ajax_call() ||
			empty( $_POST['action'] ) ||
			strpos( $_POST['action'], 'bricks_' ) !== 0
		) {
			return;
		}

		// In the "bricks_regenerate_css_file" ajax call, the post ID is set in the "data" property
		$post_id = isset( $_POST['postId'] ) ? $_POST['postId'] : ( isset( $_POST['data'] ) && is_numeric( $_POST['data'] ) ? $_POST['data'] : 0 );

		self::$page_data['original_post_id'] = $post_id;

		self::$page_data['post_id'] = $post_id;

		// Check for template preview post ID
		$template_preview_post_id = Helpers::get_template_setting( 'templatePreviewPostId', $post_id );

		self::$page_data['preview_or_post_id'] = empty( $template_preview_post_id ) ? $post_id : $template_preview_post_id;
	}

	/**
	 * Get page data from post meta
	 *
	 * @since 1.0
	 */
	public static function set_page_data( $post_id = 0 ) {
		if ( ! $post_id || is_object( $post_id ) ) {
			$post_id = get_the_ID();
		}

		// NOTE: Set post ID to posts page.
		if ( is_home() ) {
			$post_id = get_option( 'page_for_posts' );
		}

		// NOTE: Undocumented
		$post_id = apply_filters( 'bricks/builder/data_post_id', $post_id );

		// Keep $original_post_id integrity. set_page_data() also runs on Assets::generate_inline_css() for inner templates
		self::$page_data['original_post_id'] = ! empty( self::$page_data['original_post_id'] ) ? self::$page_data['original_post_id'] : $post_id;

		// $preview_or_post_id gets populated with template preview post ID OR original post ID
		$template_preview_post_id = get_post_type( self::$page_data['original_post_id'] ) === BRICKS_DB_TEMPLATE_SLUG ? Helpers::get_template_setting( 'templatePreviewPostId', self::$page_data['original_post_id'] ) : 0;

		self::$page_data['preview_or_post_id'] = empty( $template_preview_post_id ) ? self::$page_data['original_post_id'] : $template_preview_post_id;

		self::$page_data['post_id'] = $post_id;

		// Page header
		$page_header               = self::get_data( $post_id, 'header' );
		self::$page_data['header'] = is_array( $page_header ) && count( $page_header ) ? $page_header : [];

		// Page content
		$page_content               = self::get_data( $post_id, 'content' );
		self::$page_data['content'] = is_array( $page_content ) && count( $page_content ) ? $page_content : [];

		// Page footer
		$page_footer               = self::get_data( $post_id, 'footer' );
		self::$page_data['footer'] = is_array( $page_footer ) && count( $page_footer ) ? $page_footer : [];

		// Page settings
		$page_settings               = get_post_meta( $post_id, BRICKS_DB_PAGE_SETTINGS, true );
		self::$page_data['settings'] = is_array( $page_settings ) && count( $page_settings ) ? $page_settings : [];

		// Remove slashes from custom CSS & JS
		if ( is_array( self::$page_data['settings'] ) ) {
			self::$page_data['settings'] = stripslashes_deep( self::$page_data['settings'] );
		}

		// Set page gettings
		self::$page_settings = self::$page_data['settings'];
	}
}
