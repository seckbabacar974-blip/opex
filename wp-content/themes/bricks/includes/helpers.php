<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Helpers {
	/**
	 * Get template data from post meta
	 *
	 * @since 1.0
	 */
	public static function get_template_settings( $post_id ) {
		$template_settings = get_post_meta( $post_id, BRICKS_DB_TEMPLATE_SETTINGS, true );

		return $template_settings;
	}

	/**
	 * Store template settings
	 *
	 * @since 1.0
	 */
	public static function set_template_settings( $post_id, $settings ) {
		update_post_meta( $post_id, BRICKS_DB_TEMPLATE_SETTINGS, $settings );
	}

	/**
	 * Remove template settings from store
	 *
	 * @since 1.0
	 */
	public static function delete_template_settings( $post_id ) {
		delete_post_meta( $post_id, BRICKS_DB_TEMPLATE_SETTINGS );
	}

	/**
	 * Get individual template setting by key
	 *
	 * @since 1.0
	 */
	public static function get_template_setting( $key, $post_id ) {
		$template_settings = self::get_template_settings( $post_id );

		return isset( $template_settings[ $key ] ) ? $template_settings[ $key ] : '';
	}

	/**
	 * Store a specific template setting
	 *
	 * @since 1.0
	 */
	public static function set_template_setting( $post_id, $key, $setting_value ) {
		$template_settings = self::get_template_settings( $post_id );

		if ( ! is_array( $template_settings ) ) {
			$template_settings = [];
		}

		$template_settings[ $key ] = $setting_value;

		self::set_template_settings( $post_id, $template_settings );
	}

	/**
	 * Get terms
	 *
	 * @param string $taxonomy
	 * @param string $post_type
	 * @param string $include_all Includes meta terms like "All terms (taxonomy name)"
	 *
	 * @since 1.0
	 */
	public static function get_terms_options( $taxonomy = null, $post_type = null, $include_all = false ) {
		$term_args = [ 'hide_empty' => false ];

		if ( isset( $taxonomy ) ) {
			$term_args['taxonomy'] = $taxonomy;
		}

		$cache_key = 'get_terms_options' . md5( 'taxonomy' . json_encode( $taxonomy ) . 'post_type' . json_encode( $post_type ) . 'include' . $include_all );

		$response = wp_cache_get( $cache_key, 'bricks' );

		if ( $response !== false ) {
			return $response;
		}

		$terms = get_terms( $term_args );

		$response = [];

		$all_terms = [];

		foreach ( $terms as $term ) {
			if (
				$term->taxonomy === 'nav_menu' ||
				$term->taxonomy === 'link_category' ||
				$term->taxonomy === 'post_format'
				// $term->taxonomy === BRICKS_DB_TEMPLATE_TAX_TAG
			) {
				continue;
			}

			// Skip term if term taxonomy is not a taxonomy of requested post type
			if ( isset( $post_type ) ) {
				$post_type_taxonomies = get_object_taxonomies( $post_type );

				if ( ! in_array( $term->taxonomy, $post_type_taxonomies ) ) {
					continue;
				}
			}

			// Store taxonomy name and term ID as WP_Query tax_query needs both (name and term ID)
			$taxonomy_object = get_taxonomy( $term->taxonomy );
			$taxonomy_label  = '';

			if ( gettype( $taxonomy_object ) === 'object' ) {
				$taxonomy_label = ' (' . $taxonomy_object->labels->name . ')';
			} else {
				if ( $term->taxonomy === BRICKS_DB_TEMPLATE_TAX_TAG ) {
					$taxonomy_label = ' (' . esc_html__( 'Template tag', 'bricks' ) . ')';
				}

				if ( $term->taxonomy === BRICKS_DB_TEMPLATE_TAX_BUNDLE ) {
					$taxonomy_label = ' (' . esc_html__( 'Template bundle', 'bricks' ) . ')';
				}
			}

			$all_terms[ $term->taxonomy . '::all' ] = esc_html__( 'All terms', 'bricks' ) . $taxonomy_label;

			$response[ $term->taxonomy . '::' . $term->term_id ] = $term->name . $taxonomy_label;
		}

		if ( $include_all ) {
			$response = array_merge( $all_terms, $response );
		}

		wp_cache_set( $cache_key, $response, 'bricks', 5 * MINUTE_IN_SECONDS );

		return $response;
	}

	/**
	 * Get users (for templatePreview)
	 *
	 * @param bool $show_role Show user role.
	 * @uses templatePreviewAuthor
	 *
	 * @since 1.0
	 */
	public static function get_users_options( $args, $show_role = false ) {
		$users = [];

		foreach ( get_users( $args ) as $user ) {
			$user_id = $user->ID;

			$user_roles = array_values( $user->roles );

			$value = get_the_author_meta( 'display_name', $user_id );

			if ( $show_role && ! empty( $user_roles[0] ) ) {
				global $wp_roles;

				$value .= ' (' . $wp_roles->roles[ $user_roles[0] ]['name'] . ')';
			}

			$users[ $user_id ] = $value;
		}

		return $users;
	}

	/**
	 * Get post edit link with appended query string to trigger builder
	 *
	 * @since 1.0
	 */
	public static function get_builder_edit_link( $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		return add_query_arg( BRICKS_BUILDER_PARAM, 'run', get_permalink( $post_id ) );
	}

	/**
	 * Get supported post types
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_supported_post_types() {
		$supported_post_types = Database::get_setting( 'postTypes', [] );
		$post_types_options   = [];

		foreach ( $supported_post_types as $post_type_slug ) {
			if ( $post_type_slug === 'attachment' ) {
				continue;
			}

			$post_type_object = get_post_type_object( $post_type_slug );

			$post_types_options[ $post_type_slug ] = is_object( $post_type_object ) ? $post_type_object->labels->name : ucwords( str_replace( '_', ' ', $post_type_slug ) );
		}

		return $post_types_options;
	}

	/**
	 * Get registered post types
	 *
	 * Key: Post type name
	 * Value: Post type label
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_registered_post_types() {
		$registered_post_types = get_post_types(
			[ 'public' => true ],
			'objects'
		);

		// Remove post type: Bricks template (always has builder support)
		unset( $registered_post_types[ BRICKS_DB_TEMPLATE_SLUG ] );

		// Remove post type: 'attachment' (add to ControlQuery manually)
		unset( $registered_post_types['attachment'] );

		$post_types = [];

		foreach ( $registered_post_types as $key => $object ) {
			$post_types[ $key ] = $object->label;
		}

		return $post_types;
	}

	/**
	 * Is current post type supported by builder
	 *
	 * @since 1.0
	 *
	 * @return boolean
	 */
	public static function is_post_type_supported( $post_id = 0 ) {
		$post_id = ! empty( $post_id ) ? $post_id : get_the_ID();

		// NOTE: Set post ID to posts page.
		if ( empty( $post_id ) && is_home() ) {
			$post_id = get_option( 'page_for_posts' );
		}

		$current_post_type = get_post_type( $post_id );

		// Bricks templates always have builder support
		if ( $current_post_type === BRICKS_DB_TEMPLATE_SLUG ) {
			return true;
		}

		$supported_post_types = Database::get_setting( 'postTypes', [] );

		return in_array( $current_post_type, $supported_post_types );
	}

	/**
	 * Return page-specific title
	 *
	 * @param int  $post_id
	 * @param bool $context
	 *
	 * @see https://developer.wordpress.org/reference/functions/get_the_archive_title/
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public static function get_the_title( $post_id = 0, $context = false ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$preview_type = '';

		// Check if loading a Bricks template
		if ( get_post_type( $post_id ) === BRICKS_DB_TEMPLATE_SLUG ) {
			$preview_type = self::get_template_setting( 'templatePreviewType', $post_id );

			if ( $preview_type === 'archive-term' ) {
				$preview_term = self::get_template_setting( 'templatePreviewTerm', $post_id );
				if ( ! empty( $preview_term ) ) {
					$preview_term      = explode( '::', $preview_term );
					$preview_taxonomy  = isset( $preview_term[0] ) ? $preview_term[0] : '';
					$preview_term_id   = isset( $preview_term[1] ) ? intval( $preview_term[1] ) : '';
					$preview_term      = get_term_by( 'id', $preview_term_id, $preview_taxonomy );
					$preview_term_name = $preview_term ? $preview_term->name : '';
				}
			} elseif ( $preview_type == 'archive-cpt' ) {
				$preview_post_type = self::get_template_setting( 'templatePreviewPostType', $post_id );
			}
		}

		if ( Query::is_looping() && Query::get_loop_object_type() === 'post' ) {
			$title = get_the_title( $post_id );
		} elseif ( is_home() ) {
			$post_id = get_option( 'page_for_posts' );
			$title   = get_the_title( $post_id );
		} elseif ( is_404() ) {
			$title = isset( Database::$active_templates['error'] ) ? get_the_title( Database::$active_templates['error'] ) : esc_html__( 'Page not found', 'bricks' );
		} elseif ( is_category() || ( isset( $preview_taxonomy ) && $preview_taxonomy === 'category' ) ) {
			$category = isset( $preview_term_name ) ? $preview_term_name : single_cat_title( '', false );
			$category = apply_filters( 'single_cat_title', $category );
			$title    = $context ? sprintf( esc_html__( 'Category: %s', 'bricks' ), $category ) : $category;
		} elseif ( is_tag() || ( isset( $preview_taxonomy ) && $preview_taxonomy === 'post_tag' ) ) {
			$tag   = isset( $preview_term_name ) ? $preview_term_name : single_tag_title( '', false );
			$tag   = apply_filters( 'single_tag_title', $tag );
			$title = $context ? sprintf( esc_html__( 'Tag: %s', 'bricks' ), $tag ) : $tag;
		} elseif ( is_author() || $preview_type === 'archive-author' ) {
			if ( $preview_type === 'archive-author' ) {
				// Get author ID from template preview (as no $authordata exists)
				$template_preview_author = self::get_template_setting( 'templatePreviewAuthor', $post_id );
				$author                  = get_the_author_meta( 'display_name', $template_preview_author );
			} else {
				$author = get_the_author();
			}
			$author = ! empty( $author ) ? $author : '';
			$title  = $context ? sprintf( esc_html__( 'Author: %s', 'bricks' ), $author ) : $author;
		} elseif ( is_year() || $preview_type === 'archive-date' ) {
			$date  = $preview_type === 'archive-date' ? date( 'Y' ) : get_the_date( _x( 'Y', 'yearly archives date format' ) );
			$title = $context ? sprintf( esc_html__( 'Year: %s', 'bricks' ), $date ) : $date;
		} elseif ( is_month() ) {
			$date  = get_the_date( _x( 'F Y', 'monthly archives date format' ) );
			$title = $context ? sprintf( esc_html__( 'Month: %s', 'bricks' ), $date ) : $date;
		} elseif ( is_day() ) {
			$date  = get_the_date( _x( 'F j, Y', 'daily archives date format' ) );
			$title = $context ? sprintf( esc_html__( 'Day: %s', 'bricks' ), $date ) : $date;
		} elseif ( is_tax( 'post_format' ) ) {
			if ( is_tax( 'post_format', 'post-format-aside' ) ) {
				$title = esc_html__( 'Asides', 'bricks' );
			} elseif ( is_tax( 'post_format', 'post-format-gallery' ) ) {
				$title = esc_html__( 'Galleries', 'bricks' );
			} elseif ( is_tax( 'post_format', 'post-format-image' ) ) {
				$title = esc_html__( 'Images', 'bricks' );
			} elseif ( is_tax( 'post_format', 'post-format-video' ) ) {
				$title = esc_html__( 'Videos', 'bricks' );
			} elseif ( is_tax( 'post_format', 'post-format-quote' ) ) {
				$title = esc_html__( 'Quotes', 'bricks' );
			} elseif ( is_tax( 'post_format', 'post-format-link' ) ) {
				$title = esc_html__( 'Links', 'bricks' );
			} elseif ( is_tax( 'post_format', 'post-format-status' ) ) {
				$title = esc_html__( 'Statuses', 'bricks' );
			} elseif ( is_tax( 'post_format', 'post-format-audio' ) ) {
				$title = esc_html__( 'Audio', 'bricks' );
			} elseif ( is_tax( 'post_format', 'post-format-chat' ) ) {
				$title = esc_html__( 'Chats', 'bricks' );
			}
		} elseif ( is_tax() || isset( $preview_taxonomy ) ) {
			$tax = isset( $preview_taxonomy ) ? $preview_taxonomy : get_queried_object()->taxonomy;
			$tax = get_taxonomy( $tax );

			$term  = isset( $preview_term_name ) ? $preview_term_name : single_term_title( '', false );
			$term  = apply_filters( 'single_term_title', $term );
			$title = $context ? $tax->labels->singular_name . ': ' . $term : $term;
		} elseif ( is_post_type_archive() || ! empty( $preview_post_type ) ) {
			if ( ! empty( $preview_post_type ) ) {
				$post_type_obj           = get_post_type_object( $preview_post_type );
				$post_type_archive_title = apply_filters( 'post_type_archive_title', $post_type_obj->labels->name, $preview_post_type );
			} else {
				$post_type_archive_title = post_type_archive_title( '', false );
			}

			$title = $context ? sprintf( esc_html__( 'Archives: %s', 'bricks' ), $post_type_archive_title ) : $post_type_archive_title;
		} elseif ( is_search() || $preview_type === 'search' ) {
			$search_query = $preview_type === 'search' ? self::get_template_setting( 'templatePreviewSearchTerm', $post_id ) : get_search_query();

			$title = $context ? sprintf( esc_html__( 'Results for: %s', 'bricks' ), $search_query ) : $search_query;

			if ( get_query_var( 'paged' ) ) {
				$title .= ' - ' . sprintf( esc_html__( 'Page %s', 'bricks' ), get_query_var( 'paged' ) );
			}
		} else {
			$preview_id = self::get_template_setting( 'templatePreviewPostId', $post_id );
			$preview_id = ! empty( $preview_id ) ? $preview_id : $post_id;
			$title      = get_the_title( $preview_id );
		}

		// NOTE: Undocumented
		return apply_filters( 'bricks/get_the_title', $title, $post_id );
	}


	/**
	 * Get the queried object which could also be set if previewing a template
	 *
	 * @see: https://developer.wordpress.org/reference/functions/get_queried_object/
	 *
	 * @param integer $post_id
	 * @return WP_Term|WP_User|WP_Post|WP_Post_Type
	 */
	public static function get_queried_object( $post_id ) {
		$queried_object = '';

		// Check if loading a Bricks template
		if ( get_post_type( $post_id ) === BRICKS_DB_TEMPLATE_SLUG ) {
			$preview_type = self::get_template_setting( 'templatePreviewType', $post_id );

			if ( $preview_type == 'single' ) {
				$preview_id     = self::get_template_setting( 'templatePreviewPostId', $post_id );
				$queried_object = get_post( $preview_id );
			} elseif ( $preview_type === 'archive-term' ) {
				$preview_term = self::get_template_setting( 'templatePreviewTerm', $post_id );

				if ( ! empty( $preview_term ) ) {
					$preview_term     = explode( '::', $preview_term );
					$preview_taxonomy = isset( $preview_term[0] ) ? $preview_term[0] : '';
					$preview_term_id  = isset( $preview_term[1] ) ? intval( $preview_term[1] ) : '';
					$queried_object   = get_term_by( 'id', $preview_term_id, $preview_taxonomy );
				}
			} elseif ( $preview_type == 'archive-cpt' ) {
				$preview_post_type = self::get_template_setting( 'templatePreviewPostType', $post_id );

				$queried_object = get_post_type_object( $preview_post_type );
			} elseif ( $preview_type == 'archive-author' ) {
				$template_preview_author = self::get_template_setting( 'templatePreviewAuthor', $post_id );

				$queried_object = get_user_by( 'id', $template_preview_author );
			}
		}

		// It is an ajax call but it is not inside a template
		elseif ( bricks_is_ajax_call() && isset( $_POST['action'] ) && strpos( $_POST['action'], 'bricks_' ) === 0 ) {
			$queried_object = get_post( $post_id );
		}

		// In a query loop
		elseif ( ( $looping_query_id = Query::is_any_looping() ) !== false ) {
			$queried_object = Query::get_loop_object( $looping_query_id );
		}

		if ( empty( $queried_object ) ) {
			$queried_object = get_queried_object();
		}

		return $queried_object;
	}

	/**
	 * Calculate the excerpt of a post (product, or any other cpt)
	 *
	 * @param WP_Post $post
	 * @param integer $num_words
	 * @return void
	 */
	public static function get_the_excerpt( $post, $num_words, $excerpt_more = null ) {
		$post = get_post( $post );

		if ( empty( $post ) ) {
			return '';
		}

		if ( post_password_required( $post ) ) {
			return esc_html__( 'There is no excerpt because this is a protected post.', 'bricks' );
		}

		$text = $post->post_excerpt;

		// No excerpt, generate one
		if ( $text == '' ) {
			$post = get_post( $post );

			$text = get_the_content( '', false, $post );
			$text = strip_shortcodes( $text );
			$text = excerpt_remove_blocks( $text );
			$text = str_replace( ']]>', ']]&gt;', $text );
		}

		$excerpt_length = apply_filters( 'excerpt_length', $num_words );

		$excerpt_more = isset( $excerpt_more ) ? $excerpt_more : '&hellip;';

		$excerpt_more = apply_filters( 'excerpt_more', $excerpt_more );

		$text = wp_trim_words( $text, $excerpt_length, $excerpt_more );

		/**
		 * Filters the trimmed excerpt string.v
		 *
		 * @param string $text The trimmed text.
		 * @param string $raw_excerpt The text prior to trimming.
		 *
		 * @since 2.8.0
		 */
		return apply_filters( 'wp_trim_excerpt', $text, $post->post_excerpt );
	}

	/**
	 * Posts navigation
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public static function posts_navigation( $current_page, $total_pages ) {
		if ( $total_pages < 2 ) {
			return;
		}

		$args = [
			'type'      => 'list',
			'current'   => $current_page,
			'total'     => $total_pages,
			'prev_text' => '&larr;',
			'next_text' => '&rarr;',
		];

		// NOTE: Undocumented
		$args = apply_filters( 'bricks/paginate_links_args', $args );

		$posts_navigation_html = '<div class="bricks-pagination" role="navigation" aria-label="' . esc_attr__( 'Pagination', 'bricks' ) . '">';

		$posts_navigation_html .= paginate_links( $args );

		$posts_navigation_html .= '</div>';

		return $posts_navigation_html;
	}

	/**
	 * Element placeholder HTML
	 *
	 * @since 1.0
	 */
	public static function get_element_placeholder( $data ) {
		// For custom context menu
		$element_id = ! empty( $data['id'] ) ? $data['id'] : '';

		$output = '<div class="bricks-element-placeholder" data-id="' . $element_id . '">';

		if ( ! empty( $data['icon-class'] ) ) {
			$output .= '<i class="' . sanitize_html_class( $data['icon-class'] ) . '"></i>';
		}

		$output .= '<div class="placeholder-inner">';

		if ( ! empty( $data['title'] ) ) {
			$output .= '<div class="placeholder-title">' . $data['title'] . '</div>';
		}

		if ( ! empty( $data['description'] ) ) {
			$output .= '<div class="placeholder-description">' . $data['description'] . '</div>';
		}

		$output .= '</div>';

		$output .= '</div>';

		return $output;
	}

	/**
	 * Retrieves the element, the complete set of elements and the template/page ID where element belongs to
	 *
	 * NOTE: This function does not check for global element settings
	 *
	 * @since 1.5
	 *
	 * @return void
	 */
	public static function get_element_data( $post_id, $element_id ) {
		if ( empty( $post_id ) || empty( $element_id ) ) {
			return false;
		}

		$output = [
			'element'   => [], // The element we want to find
			'elements'  => [], // The complete set of elements where the element is included
			'source_id' => 0   // The post_id of the page or template where the element was set
		];

		// Get page_data via passed post_id
		if ( bricks_is_ajax_call() || bricks_is_rest_call() ) {
			Database::set_active_templates( $post_id );
		}

		$templates = [];

		$areas = [ 'content', 'header', 'footer' ];

		foreach ( $areas as $area ) {
			$elements = Database::get_data( Database::$active_templates[ $area ], $area );

			if ( ! empty( $elements ) && is_array( $elements ) ) {

				foreach ( $elements as $element ) {
					if ( $element['id'] == $element_id ) {
						$output = [
							'element'   => $element,
							'elements'  => $elements,
							'source_id' => Database::$active_templates[ $area ]
						];

						break ( 2 );
					}

					if ( $element['name'] === 'template' && ! empty( $element['settings']['template'] ) ) {
						$templates[] = $element['settings']['template'];
					}

					if ( $element['name'] === 'post-content' && ! empty( $element['settings']['dataSource'] ) && $element['settings']['dataSource'] == 'bricks' ) {
						$templates[] = $post_id;
					}
				}

			}
		}

		// Not found yet?
		if ( empty( $output['element'] ) ) {

			// If we are still here, try to run through the found templates first, and remaining templates later
			$all_templates_query = Templates::get_templates_query( [ 'fields' => 'ids' ] );
			$all_templates       = ! empty( $all_templates_query->found_posts ) ? $all_templates_query->posts : [];

			$templates = array_merge( $templates, $all_templates );
			$templates = array_unique( $templates );

			foreach ( $templates as $template_id ) {
				$elements = get_post_meta( $template_id, BRICKS_DB_PAGE_CONTENT, true );

				if ( empty( $elements ) || ! is_array( $elements ) ) {
					continue;
				}

				foreach ( $elements as $element ) {
					if ( $element['id'] === $element_id ) {
						$output = [
							'element'   => $element,
							'elements'  => $elements,
							'source_id' => $template_id
						];

						break ( 2 );
					}
				}
			}
		}

		if ( empty( $output['element'] ) ) {
			return false;
		}

		return $output;
	}

	/**
	 * Get settings of specific element (for use in AJAX functions such as form submit)
	 *
	 * @since 1.0
	 */
	public static function get_element_settings( $post_id, $element_id ) {
		if ( ! isset( $post_id ) ) {
			return 'No postId provided';
		}

		if ( ! isset( $element_id ) ) {
			return 'No elementId provided';
		}

		$data = self::get_element_data( $post_id, $element_id );

		if ( ! $data || empty( $data['element']['settings'] ) ) {
			return false;
		}

		// Retrieve global settings if exist
		$global_settings = self::get_global_element_settings( $data['element'] );

		return is_array( $global_settings ) ? $global_settings : $data['element']['settings'];
	}

	/**
	 * Get the global settings of a possible global element
	 *
	 * @param array $element
	 *
	 * @return boolean|array false if no global element found, else return the global settings array
	 *
	 * @since 1.3.5
	 */
	public static function get_global_element_settings( $element ) {
		$settings = false;

		foreach ( Database::$global_data['elements'] as $global_element ) {
			// @since 1.2.1 (check against element 'global' property)
			if (
				! empty( $global_element['global'] ) &&
				! empty( $element['global'] ) &&
				$global_element['global'] === $element['global']
			) {
				$settings = $global_element['settings'];
			}

			// @pre 1.2.1 (check against element 'id' property)
			elseif (
				! empty( $global_element['id'] ) &&
				! empty( $element['id'] ) &&
				$global_element['id'] === $element['id']
			) {
				$settings = $global_element['settings'];
			}

			if ( $settings !== false ) {
				break;
			}
		}

		return $settings;
	}

	/**
	 * Get posts options (max 50 results)
	 *
	 * @param string
	 *
	 * @since 1.0
	 */
	public static function get_posts_by_post_id( $query_args = [] ) {

		// NOTE: Undocumented
		$query_args = apply_filters( 'bricks/helpers/get_posts_args', $query_args );

		$query_args = wp_parse_args(
			$query_args,
			[
				'post_type'      => 'any',
				'post_status'    => 'any',
				'posts_per_page' => 50,
				'orderby'        => 'post_type',
				'order'          => 'DESC',
				'no_found_rows'  => true
			]
		);

		// Query max. 50 posts to avoid running into any memory limits
		if ( $query_args['posts_per_page'] == -1 ) {
			$query_args['posts_per_page'] = 50;
		}

		unset( $query_args['fields'] ); // Make sure the output is standard

		// Don't specify meta_key to get all posts for 'templatePreviewPostId'
		$posts = get_posts( $query_args );

		$posts_options = [];

		foreach ( $posts as $post ) {
			// Skip non-content templates (header template, footer template)
			if ( $post->post_type === BRICKS_DB_TEMPLATE_SLUG && Templates::get_template_type( $post->ID ) !== 'content' ) {
				continue;
			}

			if ( $post->post_type === 'attachment' ) {
				continue;
			}

			$post_type_object = get_post_type_object( $post->post_type );

			$post_title  = get_the_title( $post );
			$post_title .= $post_type_object ? ' (' . $post_type_object->labels->singular_name . ')' : ' (' . ucfirst( $post->post_type ) . ')';

			$posts_options[ $post->ID ] = $post_title;
		}

		return $posts_options;
	}

	/**
	 * Get a list of supported content types for template preview
	 *
	 * @return array
	 */
	public static function get_supported_content_types() {
		$types = [
			'archive-recent-posts' => esc_html__( 'Archive (recent posts)', 'bricks' ),
			'archive-author'       => esc_html__( 'Archive (author)', 'bricks' ),
			'archive-date'         => esc_html__( 'Archive (date)', 'bricks' ),
			'archive-cpt'          => esc_html__( 'Archive (posts)', 'bricks' ),
			'archive-term'         => esc_html__( 'Archive (term)', 'bricks' ),
			'search'               => esc_html__( 'Search results', 'bricks' ),
			'single'               => esc_html__( 'Single post/page', 'bricks' ),
		];

		// NOTE: Undocumented
		$types = apply_filters( 'bricks/template_preview/supported_content_types', $types );

		return $types;
	}

	/**
	 * Get editor mode of requested page
	 *
	 * @param int $post_id
	 *
	 * @since 1.0
	 */
	public static function get_editor_mode( $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		return get_post_meta( $post_id, BRICKS_DB_EDITOR_MODE, true );
	}

	/**
	 * Check if post/page/cpt renders with Bricks
	 *
	 * @param integer $post_id
	 * @return void
	 */
	public static function render_with_bricks( $post_id = 0 ) {
		// When editing with Elementor we need to tell Bricks to render templates as WordPress
		// @see https://elementor.com/help/the-content-area-was-not-found-error/
		if ( isset( $_GET['elementor-preview'] ) ) {
			return false;
		}

		// Skip WooCommerce, if disabled on Bricks Settings in case is_shop
		if ( ! Woocommerce::is_woocommerce_active() && function_exists( 'is_shop' ) && is_shop() ) {
			return false;
		}

		// Password protected
		if ( post_password_required( $post_id ) ) {
			return false;
		}

		$editor_mode = self::get_editor_mode( $post_id );

		if ( $editor_mode === 'wordpress' ) {
			return false;
		}

		return true;
	}

	/**
	 * Get Bricks data for requested page
	 *
	 * @param integer $post_id The post ID.
	 * @param string  $type header, content, footer.
	 *
	 * @since 1.3.4
	 *
	 * @return boolean|array
	 */
	public static function get_bricks_data( $post_id = 0, $type = 'content' ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		// Return if requested post is not rendered with Bricks
		if ( ! self::render_with_bricks( $post_id ) ) {
			return false;
		}

		$bricks_data = Database::get_template_data( $type );

		if ( ! is_array( $bricks_data ) ) {
			return false;
		}

		if ( ! count( $bricks_data ) ) {
			return false;
		}

		return $bricks_data;
	}

	public static function delete_bricks_data_by_post_id( $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		return add_query_arg(
			[
				'bricks_delete_post_meta' => $post_id,
				'bricks_notice'           => 'post_meta_deleted',
			],
			get_edit_post_link()
		);
	}

	/**
	 * Generate random hash
	 *
	 * Default: 6 characters long
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public static function generate_hash( $string, $length = 6 ) {
		// Generate SHA1 hexadecimal string (40-characters)
		$sha1        = sha1( $string );
		$sha1_length = strlen( $sha1 );
		$hash        = '';

		// Generate random site hash based on SHA1 string
		for ( $i = 0; $i < $length; $i++ ) {
			$hash .= $sha1[ rand( 0, $sha1_length - 1 ) ];
		}

		// Convert site path to lowercase
		$hash = strtolower( $hash );

		return $hash;
	}

	public static function generate_random_id( $echo = true ) {
		$hash = self::generate_hash( md5( uniqid( rand(), true ) ) );

		if ( $echo ) {
			echo $hash;
		}

		return $hash;
	}

	/**
	 * Get file content via HTTP API instead of file_content file_get_contents()
	 *
	 * Avoid file_get_contents() SSL error on local installs.
	 * Disable 'verify_peer' and 'verify_peer_name' is not a solution due to security (https://stackoverflow.com/a/26151993/2009539)
	 *
	 * On version 1.3 insert file_get_contents() as last resort if wp_remote_get() fails (servers without internal DNS)
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public static function get_file_contents( $url, $args = [] ) {
		$args['sslverify'] = apply_filters( 'https_local_ssl_verify', false );

		$request = self::remote_get( esc_url_raw( $url ), $args );

		// Success
		if ( wp_remote_retrieve_response_code( $request ) === 200 ) {
			return wp_remote_retrieve_body( $request );
		}

		// If not, try last resort
		else {
			$file = self::file_url_to_path( $url );

			if ( $file ) {
				$content = file_get_contents( $file );
				return $content !== false ? $content : '';
			}
		}

		// Error
		return;
	}

	/**
	 * Convert URL into a path
	 *
	 * @since 1.3
	 *
	 * @param string $url
	 * @return string file path
	 */
	public static function file_url_to_path( $url ) {

		$parsed_url = parse_url( $url );

		if ( empty( $parsed_url['path'] ) ) {
			return false;
		}

		$file = ABSPATH . ltrim( $parsed_url['path'], '/' );

		if ( file_exists( $file ) ) {
			return $file;
		}

		return false;
	}

	/**
	 * Return WP dashboard Bricks settings url
	 *
	 * @since 1.0
	 */
	public static function settings_url( $params = '' ) {
		return admin_url( "/admin.php?page=bricks-settings$params" );
	}

	/**
	 * Return Bricks Academy link
	 *
	 * @since 1.0
	 */
	public static function article_link( $path, $text ) {
		return '<a href="https://academy.bricksbuilder.io/article/' . $path . '" target="_blank" rel="noopener">' . $text . '</a>';
	}

	/**
	 * Return the edit post link (ot the preview post link)
	 *
	 * @since 1.2.1
	 * @param $post_id
	 * @return string
	 */
	public static function get_preview_post_link( $post_id ) {
		$template_preview_post_id = self::get_template_setting( 'templatePreviewPostId', $post_id );

		if ( $template_preview_post_id ) {
			$post_id = $template_preview_post_id;
		}

		return get_edit_post_link( $post_id );
	}

	/**
	 * Dev helper to var dump nicely formatted
	 *
	 * @since 1.0
	 */
	public static function pre_dump( $data ) {
		echo '<pre>';
		var_dump( $data );
		echo '</pre>';
	}

	/**
	 * Dev helper to error log array values
	 *
	 * @since 1.0
	 */
	public static function log( $data ) {
		error_log( print_r( $data, true ) );
	}

	/**
	 * Custom wp_remote_get function
	 */
	public static function remote_get( $url, $args = [] ) {
		if ( ! isset( $args['timeout'] ) ) {
			$args['timeout'] = 30;
		}

		// Disable to avoid Let's Encrypt SSL root certificate expiration issue
		if ( ! isset( $args['sslverify'] ) ) {
			$args['sslverify'] = false;
		}

		$args = apply_filters( 'bricks/remote_get', $args, $url );

		return wp_remote_get( $url, $args );
	}

	/**
	 * Custom wp_remote_post function
	 *
	 * @since 1.3.5
	 */
	public static function remote_post( $url, $args = [] ) {
		if ( ! isset( $args['timeout'] ) ) {
			$args['timeout'] = 30;
		}

		// Disable to avoid Let's Encrypt SSL root certificate expiration issue
		if ( ! isset( $args['sslverify'] ) ) {
			$args['sslverify'] = false;
		}

		$args = apply_filters( 'bricks/remote_post', $args, $url );

		return wp_remote_post( $url, $args );
	}

	/**
	 * Generate swiperJS breakpoint data-options (carousel, testimonial)
	 *
	 * Set slides to show & scroll per breakpoint.
	 * Swiper breakpoint values use "min-width". so descent breakpoints from largest to smallest.
	 *
	 * https://swiperjs.com/swiper-api#param-breakpoints
	 *
	 * @since 1.3.5
	 *
	 * @since 1.5.1: removed old 'responsive' repeater controls due to custom breakpoints
	 */
	public static function generate_swiper_breakpoint_data_options( $settings ) {
		$slides_to_show   = ! empty( $settings['slidesToShow'] ) ? intval( $settings['slidesToShow'] ) : 1;
		$slides_to_scroll = ! empty( $settings['slidesToScroll'] ) ? intval( $settings['slidesToScroll'] ) : 1;

		$breakpoints = [];

		foreach ( Breakpoints::$breakpoints as $index => $breakpoint ) {
			$key   = $breakpoint['key'];
			$width = intval( $breakpoint['width'] );

			// Get min-width value from width of next smaller breakpoint
			$min_width = ! empty( Breakpoints::$breakpoints[ $index + 1 ]['width'] ) ? intval( Breakpoints::$breakpoints[ $index + 1 ]['width'] ) + 1 : 1;

			// Non-desktop breakpoint
			if ( ! empty( $settings[ "slidesToShow:{$key}" ] ) ) {
				$breakpoints[ $min_width ]['slidesPerView'] = $settings[ "slidesToShow:{$key}" ];
			}

			if ( ! empty( $settings[ "slidesToScroll:{$key}" ] ) ) {
				$breakpoints[ $min_width ]['slidesPerGroup'] = $settings[ "slidesToScroll:{$key}" ];
			}
		}

		return $breakpoints;
	}

	/**
	 * Sanitize Bricks data
	 *
	 * @since 1.3.7
	 */
	public static function sanitize_bricks_data( $elements ) {
		if ( is_array( $elements ) ) {
			foreach ( $elements as $index => $element ) {
				// STEP: Code element: Remove "Execute Code" setting to prevent executing potentially malicious code
				if ( isset( $element['settings']['executeCode'] ) ) {
					unset( $elements[ $index ]['settings']['executeCode'] );
				}
			}
		}

		return $elements;
	}

	/**
	 * Set is_frontend = false to a element
	 *
	 * Use: $elements = array_map( 'Bricks\Helpers::set_is_frontend_to_false', $elements );
	 *
	 * @since 1.4
	 */
	public static function set_is_frontend_to_false( $element ) {
		$element['is_frontend'] = false;

		return $element;
	}

	/**
	 * Get post IDs of all Bricks-enabled post types
	 *
	 * @see admin.php get_converter_items()
	 * @see files.php get_css_files_list()
	 *
	 * @since 1.4
	 */
	public static function get_all_bricks_post_ids() {
		return get_posts(
			[
				'post_type'              => array_keys( self::get_supported_post_types() ),
				'posts_per_page'         => -1,
				'post_status'            => 'any',
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'meta_query'             => [
					[
						'key'     => BRICKS_DB_PAGE_CONTENT,
						'value'   => '',
						'compare' => '!=',
					],
				],
			]
		);
	}

	/**
	 * Search & replace: Works for strings & arrays
	 *
	 * @param string $search  The value being searched for.
	 * @param string $replace The replacement value that replaces found search values
	 * @param string $search  The string or array being searched and replaced on, otherwise known as the haystack.
	 *
	 * @see templates.php import_template()
	 *
	 * @since 1.4
	 */
	public static function search_replace( $search, $replace, $data ) {
		$is_array = is_array( $data );

		// Stringify array
		if ( $is_array ) {
			$data = json_encode( $data );
		}

		// Replace string
		$data = str_replace( $search, $replace, $data );

		// Convert back to array
		if ( $is_array ) {
			$data = json_decode( $data, true );
		}

		return $data;
	}

	/**
	 * Google fonts are disabled (via filter OR Bricks setting)
	 *
	 * @see https://academy.bricksbuilder.io/article/filter-bricks-assets-load_webfonts
	 *
	 * @since 1.4
	 */
	public static function google_fonts_disabled() {
		return ! apply_filters( 'bricks/assets/load_webfonts', true ) || isset( Database::$global_settings['disableGoogleFonts'] );
	}

	/**
	 * Stringify HTML attributes
	 *
	 * @param array $attributes key = attribute key; value = attribute value (string|array)
	 *
	 * @see bricks/header/attributes
	 * @see bricks/footer/attributes
	 *
	 * @return string
	 *
	 * @since 1.5
	 */
	public static function stringify_html_attributes( $attributes ) {
		$strings = [];

		foreach ( $attributes as $key => $value ) {
			// Array: 'class', etc.
			if ( is_array( $value ) ) {
				$value = join( ' ', $value );
			}

			$strings[] = "{$key}=\"$value\"";
		}

		return join( ' ', $strings );
	}

	/**
	 * Return element ID (for )
	 *
	 * @since 1.5.1
	 */
	public static function get_element_id( $settings, $id ) {
		return empty( $settings['_cssId'] ) ? "brxe-{$id}" : sanitize_html_class( $settings['_cssId'] );
	}
}
