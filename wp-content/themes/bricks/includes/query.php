<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Query {
	/**
	 * The query unique ID
	 */
	private $id = '';

	/**
	 * Element ID
	 */
	public $element_id = '';

	/**
	 * Element settings
	 */
	public $settings = [];

	/**
	 * Query vars
	 */
	public $query_vars = [];

	/**
	 * Type of object queried: 'post', 'term', 'user'
	 */
	public $object_type = 'post';

	/**
	 * Query result (WP_Posts | WP_Term_Query | WP_User_Query | Other)
	 */
	public $query_result;

	/**
	 * Query results total
	 */
	public $count = 0;

	/**
	 * Query results total pages
	 */
	public $max_num_pages = 1;

	/**
	 * Is looping
	 *
	 * @var boolean
	 */
	public $is_looping = false;

	/**
	 * When looping, keep the iteration index
	 */
	public $loop_index = 0;

	/**
	 * When looping, keep the object
	 */
	public $loop_object = null;

	/**
	 * Store the original post before looping to restore the context (nested loops)
	 */
	private $original_post_id = 0;

	/**
	 * Cache key
	 */
	private $cache_key = false;

	/**
	 * Class constructor
	 *
	 * @param array $element
	 */
	public function __construct( $element = [] ) {

		// Register query
		$this->register_query();

		$this->element_id  = ! empty( $element['id'] ) ? $element['id'] : '';
		$this->object_type = ! empty( $element['settings']['query']['objectType'] ) ? $element['settings']['query']['objectType'] : 'post';

		// Remove object type from query vars to avoid future conflicts
		unset( $element['settings']['query']['objectType'] );

		$this->settings = ! empty( $element['settings'] ) ? $element['settings'] : [];

		// STEP: Perform the query (it also populates the total count)
		$this->query_result = $this->run();
	}

	/**
	 * Add this query to the global store
	 */
	public function register_query() {
		global $bricks_loop_query;

		$this->id = Helpers::generate_random_id( false );

		if ( ! is_array( $bricks_loop_query ) ) {
			$bricks_loop_query = [];
		}

		$bricks_loop_query[ $this->id ] = $this;
	}

	/**
	 * Calling unset( $query ) does not destroy query quickly enough
	 *
	 * Have to call the 'destroy' method explicitly before unset.
	 */
	public function __destruct() {
		$this->destroy();
	}

	/**
	 * Use the destroy method to remove the query from the global store
	 *
	 * @return void
	 */
	public function destroy() {
		global $bricks_loop_query;

		unset( $bricks_loop_query[ $this->id ] );
	}

	/**
	 * Get the query cache
	 *
	 * @since 1.5
	 *
	 * @return mixed
	 */
	public function get_query_cache() {
		if ( ! isset( Database::$global_settings['cacheQueryLoops'] ) || ! bricks_is_frontend() || bricks_is_builder_call() ) {
			return false;
		}

		// Check: Nesting query?
		$parent_query_id  = self::is_any_looping();
		$parent_object_id = $parent_query_id ? self::get_loop_object_id( $parent_query_id ) : 0;

		// Include in the cache key a representation of the query vars to break cache for certain scenarios like pagination or search keywords
		$query_vars = json_encode( $this->query_vars );

		// Get & set query loop cache (@since 1.5)
		$this->cache_key = md5( "brx_query_{$this->element_id}_{$query_vars}_{$parent_object_id}" );

		return wp_cache_get( $this->cache_key, 'bricks' );
	}

	/**
	 * Set the query cache
	 *
	 * @since 1.5
	 *
	 * @return void
	 */
	public function set_query_cache( $object ) {
		if ( ! $this->cache_key ) {
			return;
		}

		wp_cache_set( $this->cache_key, $object, 'bricks', MINUTE_IN_SECONDS );
	}

	/**
	 * Perform the query (maybe cache)
	 *
	 * @return mixed
	 */
	public function run() {
		$this->query_vars = isset( $this->settings['query'] ) ? $this->settings['query'] : [];

		if ( isset( $this->query_vars['infinite_scroll'] ) ) {
			unset( $this->query_vars['infinite_scroll'] );
		}

		if ( $this->object_type === 'post' ) {
			return $this->run_wp_query();
		} elseif ( $this->object_type === 'term' ) {
			return $this->run_wp_term_query();
		} elseif ( $this->object_type === 'user' ) {
			return $this->run_wp_user_query();
		}

		// Allow other query providers to return a query result (Woo Cart, ACF, Metabox...)
		else {
			// NOTE: Undocumented
			$result = apply_filters( 'bricks/query/run', [], $this );

			$this->count = ! empty( $result ) && is_array( $result ) ? count( $result ) : 0;

			return $result;
		}
	}

	/**
	 * Run WP_Term_Query
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_term_query/
	 *
	 * @return array Terms (WP_Term)
	 */
	public function run_wp_term_query() {
		// Number. Default is "0" (all) but as a safety procedure we limit the number
		$this->query_vars['number'] = isset( $this->query_vars['number'] ) ? $this->query_vars['number'] : get_option( 'posts_per_page' );

		// Get pagination (not native for the terms query)
		$paged = $this->get_paged_query_var();

		// Pagination: Fix the offset value  (@since 1.5)
		$offset = ! empty( $this->query_vars['offset'] ) ? $this->query_vars['offset'] : 0;

		// If pagination exists, and number is limited (!= 0), use $offset as the pagination trigger
		if ( $paged !== 1 && ! empty( $this->query_vars['number'] ) ) {
			$this->query_vars['offset'] = ( $paged - 1 ) * $this->query_vars['number'] + $offset;
		}

		// Hide empty
		if ( isset( $this->query_vars['show_empty'] ) ) {
			$this->query_vars['hide_empty'] = false;

			unset( $this->query_vars['show_empty'] );
		}

		if ( isset( $this->query_vars['child_of'] ) ) {
			$this->query_vars['child_of'] = Integrations\Dynamic_Data\Providers::render_content( $this->query_vars['child_of'] );
		}

		if ( isset( $this->query_vars['parent'] ) ) {
			$this->query_vars['parent'] = Integrations\Dynamic_Data\Providers::render_content( $this->query_vars['parent'] );
		}

		// Include & Exclude terms
		if ( isset( $this->query_vars['tax_query'] ) ) {
			$this->query_vars['include'] = self::convert_terms_to_ids( $this->query_vars['tax_query'] );

			unset( $this->query_vars['tax_query'] );
		}

		if ( isset( $this->query_vars['tax_query_not'] ) ) {
			$this->query_vars['exclude'] = self::convert_terms_to_ids( $this->query_vars['tax_query_not'] );

			unset( $this->query_vars['tax_query_not'] );
		}

		// Meta Query vars
		$this->query_vars = self::parse_meta_query_vars( $this->query_vars );

		// @see: https://academy.bricksbuilder.io/article/filter-bricks-terms-query_vars/
		$this->query_vars = apply_filters( 'bricks/terms/query_vars', $this->query_vars, $this->settings, $this->element_id );

		// Cache?
		$result = $this->get_query_cache();

		if ( $result === false ) {
			$terms_query = new \WP_Term_Query( $this->query_vars );

			$result = $terms_query->get_terms();

			$this->set_query_cache( $result );
		}

		// STEP: Populate the total count
		if ( empty( $this->query_vars['number'] ) ) {
			$this->count = ! empty( $result ) && is_array( $result ) ? count( $result ) : 0;
		} else {
			$args = $this->query_vars;

			unset( $args['offset'] );
			unset( $args['number'] );

			// Numeric string containing the number of terms in that taxonomy or WP_Error if the taxonomy does not exist.
			$count = wp_count_terms( $args );

			if ( is_wp_error( $count ) ) {
				$this->count = 0;
			} else {
				$count = (int) $count;

				$this->count = $offset <= $count ? $count - $offset : 0;
			}
		}

		// STEP : Populate the max number of pages
		$this->max_num_pages = empty( $this->query_vars['number'] ) ? 1 : ceil( $this->count / $this->query_vars['number'] );

		return $result;
	}

	/**
	 * Run WP_User_Query
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
	 *
	 * @return array Users (WP_Term)
	 */
	public function run_wp_user_query() {
		unset( $this->query_vars['post_type'] );

		// Pagination (number, offset, paged). Default is "-1" but as a safety procedure we limit the number (0 is not allowed)
		$this->query_vars['number'] = ! empty( $this->query_vars['number'] ) ? $this->query_vars['number'] : get_option( 'posts_per_page' );

		$this->query_vars['paged'] = $this->get_paged_query_var();

		// Pagination: Fix the offset value  (@since 1.5)
		$offset = ! empty( $this->query_vars['offset'] ) ? $this->query_vars['offset'] : 0;

		if ( ! empty( $offset ) && $this->query_vars['paged'] !== 1 ) {
			$this->query_vars['offset'] = ( $this->query_vars['paged'] - 1 ) * $this->query_vars['number'] + $offset;
		}

		// Meta Query vars
		$this->query_vars = self::parse_meta_query_vars( $this->query_vars );

		// @see: https://academy.bricksbuilder.io/article/filter-bricks-users-query_vars/
		$this->query_vars = apply_filters( 'bricks/users/query_vars', $this->query_vars, $this->settings, $this->element_id );

		// Cache?
		$users_query = $this->get_query_cache();

		if ( $users_query === false ) {
			$users_query = new \WP_User_Query( $this->query_vars );

			$this->set_query_cache( $users_query );
		}

		// STEP: The query result
		$result = $users_query->get_results();

		// STEP: Populate the total count of the users in this query
		$this->count = $users_query->get_total();

		// Subtract the $offset to fix pagination
		$this->count = $offset <= $this->count ? $this->count - $offset : 0;

		// STEP : Populate the max number of pages
		$this->max_num_pages = empty( $this->query_vars['number'] ) ? 1 : ceil( $this->count / $this->query_vars['number'] );

		return $result;
	}

	/**
	 * Run WP_Query
	 *
	 * @return object
	 */
	public function run_wp_query() {
		$is_attachment_query = false;

		// post_type can be 'string' or 'array'
		$post_type = ! empty( $this->query_vars['post_type'] ) ? $this->query_vars['post_type'] : false;

		if ( $post_type ) {
			if ( is_array( $post_type ) ) {
				$is_attachment_query = in_array( 'attachment', $post_type ) && count( $post_type ) == 1;
			} else {
				$is_attachment_query = $post_type === 'attachment';
			}
		}

		// @since 1.5: If the post type is 'attachment', change default post status to 'inherit' @see: https://developer.wordpress.org/reference/classes/wp_query/#post-type-parameters
		$this->query_vars['post_status'] = $is_attachment_query ? 'inherit' : 'publish';

		// Page & Pagination
		if ( get_query_var( 'page' ) ) {
			// Check for 'page' on static front page
			$this->query_vars['paged'] = get_query_var( 'page' );
		} elseif ( get_query_var( 'paged' ) ) {
			$this->query_vars['paged'] = get_query_var( 'paged' );
		} else {
			$this->query_vars['paged'] = ! empty( $this->query_vars['paged'] ) ? intval( abs( $this->query_vars['paged'] ) ) : 1;
		}

		// Value must be -1 or > 1 (0 is not allowed)
		$this->query_vars['posts_per_page'] = ! empty( $this->query_vars['posts_per_page'] ) ? intval( $this->query_vars['posts_per_page'] ) : get_option( 'posts_per_page' );

		// Exclude current post
		if ( isset( $this->query_vars['exclude_current_post'] ) ) {
			if ( is_single() || is_page() ) {
				$this->query_vars['post__not_in'][] = get_the_ID();
			}

			unset( $this->query_vars['exclude_current_post'] );
		}

		// post_mime_type: used only for post_type = 'attachment' (@since 1.5)
		if ( $is_attachment_query ) {
			$mime_types = isset( $this->query_vars['post_mime_type'] ) ? Integrations\Dynamic_Data\Providers::render_content( $this->query_vars['post_mime_type'] ) : 'image';

			$mime_types = explode( ',', $mime_types );

			$this->query_vars['post_mime_type'] = $mime_types;
		}

		// @since 1.5
		if ( isset( $this->query_vars['post_parent'] ) ) {
			$this->query_vars['post_parent'] = (int) Integrations\Dynamic_Data\Providers::render_content( $this->query_vars['post_parent'] );
		}

		$this->query_vars = self::set_tax_query_vars( $this->query_vars );

		// Meta Query vars
		$this->query_vars = self::parse_meta_query_vars( $this->query_vars );

		// @see: https://academy.bricksbuilder.io/article/filter-bricks-posts-merge_query/
		$merge_query = apply_filters( 'bricks/posts/merge_query', true, $this->element_id );

		// Merge wp_query vars and posts element query vars
		if ( $merge_query && ( is_archive() || is_author() || is_search() || is_home() ) ) {
			global $wp_query;

			$this->query_vars = wp_parse_args( $this->query_vars, $wp_query->query );
		}

		/**
		 * REST API /load_query_page adds "_merge_vars" to the query to make sure the archive context is maintained on infinite scroll
		 *
		 * @since 1.5.1
		 */
		if ( ! empty( $this->query_vars['_merge_vars'] ) ) {
			$merge_query_vars = $this->query_vars['_merge_vars'];

			unset( $this->query_vars['_merge_vars'] );

			$this->query_vars = wp_parse_args( $this->query_vars, $merge_query_vars );
		}

		// @see: https://academy.bricksbuilder.io/article/filter-bricks-posts-query_vars/
		// @since 1.3.6 Added $element_id
		$this->query_vars = apply_filters( 'bricks/posts/query_vars', $this->query_vars, $this->settings, $this->element_id );

		// Cache?
		$posts_query = $this->get_query_cache();

		if ( $posts_query === false ) {
			add_action( 'pre_get_posts', [ $this, 'set_pagination_with_offset' ], 5 );
			add_filter( 'found_posts', [ $this, 'fix_found_posts_with_offset' ], 5, 2 );

			$posts_query = new \WP_Query( $this->query_vars );

			remove_action( 'pre_get_posts', [ $this, 'set_pagination_with_offset' ], 5 );
			remove_filter( 'found_posts', [ $this, 'fix_found_posts_with_offset' ], 5, 2 );

			$this->set_query_cache( $posts_query );
		}

		// STEP: Populate the total count
		$this->count = empty( $this->query_vars['no_found_rows'] ) ? $posts_query->found_posts : ( is_array( $posts_query->posts ) ? count( $posts_query->posts ) : 0 );

		// STEP : Populate the max number of pages
		$this->max_num_pages = empty( $this->query_vars['posts_per_page'] ) ? 1 : ceil( $this->count / $this->query_vars['posts_per_page'] );

		return $posts_query;
	}

	/**
	 * Get the page number for a query based on the query var "paged"
	 *
	 * @since 1.5
	 *
	 * @return integer
	 */
	public function get_paged_query_var() {
		$paged = 1;

		if ( get_query_var( 'page' ) ) {
			// Check for 'page' on static front page
			$paged = get_query_var( 'page' );
		} elseif ( get_query_var( 'paged' ) ) {
			$paged = get_query_var( 'paged' );
		} else {
			$paged = ! empty( $this->query_vars['paged'] ) ? abs( $this->query_vars['paged'] ) : 1;
		}

		return intval( $paged );
	}

	/**
	 * Parse the Meta Query vars through the DD logic
	 *
	 * @Since 1.5
	 *
	 * @param array $query_vars
	 * @return array
	 */
	public static function parse_meta_query_vars( $query_vars ) {

		if ( empty( $query_vars['meta_query'] ) ) {
			return $query_vars;
		}

		foreach ( $query_vars['meta_query'] as $key => $query_item ) {
			unset( $query_vars['meta_query'][ $key ]['id'] );

			if ( empty( $query_vars['meta_query'][ $key ]['value'] ) ) {
				continue;
			}

			$query_vars['meta_query'][ $key ]['value'] = Integrations\Dynamic_Data\Providers::render_content( $query_vars['meta_query'][ $key ]['value'] );
		}

		if ( ! empty( $query_vars['meta_query_relation'] ) ) {
			$query_vars['meta_query']['relation'] = $query_vars['meta_query_relation'];
		}

		unset( $query_vars['meta_query_relation'] );

		return $query_vars;
	}

	/**
	 * Set 'tax_query' vars (e.g. Carousel, Posts, Related Posts)
	 *
	 * Include & exclude terms of different taxonomies
	 *
	 * @since 1.3.2
	 */
	public static function set_tax_query_vars( $query_vars ) {
		// Include terms
		if ( isset( $query_vars['tax_query'] ) ) {
			$terms     = $query_vars['tax_query'];
			$tax_query = [];

			foreach ( $terms as $term ) {
				if ( ! is_string( $term ) ) {
					continue;
				}

				$term_parts = explode( '::', $term );
				$taxonomy   = isset( $term_parts[0] ) ? $term_parts[0] : false;
				$term       = isset( $term_parts[1] ) ? $term_parts[1] : false;

				if ( ! $taxonomy || ! $term ) {
					continue;
				}

				if ( isset( $tax_query[ $taxonomy ] ) ) {
					$tax_query[ $taxonomy ]['terms'][] = $term;
				} else {
					$tax_query[ $taxonomy ] = [
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => [ $term ],
					];
				}
			}

			$tax_query = array_values( $tax_query );

			if ( count( $tax_query ) > 1 ) {
				$tax_query['relation'] = 'OR';

				$query_vars['tax_query'] = [ $tax_query ];
			} else {
				$query_vars['tax_query'] = $tax_query;
			}
		}

		// Exclude terms
		if ( isset( $query_vars['tax_query_not'] ) ) {
			$terms             = $query_vars['tax_query_not'];
			$tax_query_exclude = [];

			foreach ( $query_vars['tax_query_not'] as $term ) {
				if ( ! is_string( $term ) ) {
					continue;
				}

				$term_parts = explode( '::', $term );
				$taxonomy   = $term_parts[0];
				$term       = $term_parts[1];

				if ( isset( $tax_query_exclude[ $taxonomy ] ) ) {
					$tax_query_exclude[ $taxonomy ]['terms'][] = $term;
				} else {
					$tax_query_exclude[ $taxonomy ] = [
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => [ $term ],
						'operator' => 'NOT IN',
					];
				}
			}

			$tax_query_exclude = array_values( $tax_query_exclude );

			if ( count( $tax_query_exclude ) > 1 ) {
				$tax_query_exclude['relation'] = 'AND';

				$query_vars['tax_query'][] = [ $tax_query_exclude ];
			} else {
				$query_vars['tax_query'][] = $tax_query_exclude;
			}

			unset( $query_vars['tax_query_not'] );
		}

		if ( isset( $query_vars['tax_query_advanced'] ) ) {
			foreach ( $query_vars['tax_query_advanced'] as $tax_query ) {

				if ( empty( $tax_query['terms'] ) ) {
					continue;
				}

				$tax_query['terms'] = Integrations\Dynamic_Data\Providers::render_content( $tax_query['terms'] );

				if ( strpos( $tax_query['terms'], ',' ) ) {
					$tax_query['terms'] = explode( ',', $tax_query['terms'] );
					$tax_query['terms'] = array_map( 'trim', $tax_query['terms'] );
				}

				unset( $tax_query['id'] );

				$query_vars['tax_query'][] = $tax_query;
			}
		}

		if ( isset( $query_vars['tax_query'] ) && is_array( $query_vars['tax_query'] ) && count( $query_vars['tax_query'] ) > 1 ) {
			$query_vars['tax_query']['relation'] = isset( $query_vars['tax_query_relation'] ) ? $query_vars['tax_query_relation'] : 'AND';
		}

		unset( $query_vars['tax_query_relation'] );
		unset( $query_vars['tax_query_advanced'] );

		return $query_vars;
	}

	/**
	 * Modifies $query offset variable to make pagination work in combination with offset.
	 *
	 * @see https://codex.wordpress.org/Making_Custom_Queries_using_Offset_and_Pagination
	 * Note that the link recommends exiting the filter if $query->is_paged returns false,
	 * but then max_num_pages on the first page is incorrect.
	 *
	 * @param \WP_Query $query WordPress query.
	 */
	public function set_pagination_with_offset( $query ) {
		if ( ! isset( $this->query_vars['offset'] ) ) {
			return;
		}

		$new_offset = $this->query_vars['offset'] + ( $query->get( 'paged', 1 ) - 1 ) * $query->get( 'posts_per_page' );
		$query->set( 'offset', $new_offset );
	}

	/**
	 * By default, WordPress includes offset posts into the final post count.
	 * This method excludes them.
	 *
	 * @see https://codex.wordpress.org/Making_Custom_Queries_using_Offset_and_Pagination
	 * Note that the link recommends exiting the filter if $query->is_paged returns false,
	 * but then max_num_pages on the first page is incorrect.
	 *
	 * @param int       $found_posts Found posts.
	 * @param \WP_Query $query WordPress query.
	 * @return int Modified found posts.
	 */
	public function fix_found_posts_with_offset( $found_posts, $query ) {
		if ( ! isset( $this->query_vars['offset'] ) ) {
			return $found_posts;
		}

		return $found_posts - $this->query_vars['offset'];
	}

	/**
	 * Set the initial loop index (needed for the infinite scroll)
	 *
	 * @since 1.5
	 *
	 * @return void
	 */
	public function init_loop_index() {
		if ( $this->object_type == 'post' ) {
			$offset = isset( $this->query_vars['offset'] ) ? $this->query_vars['offset'] : 0;

			return $offset + ( $this->query_vars['posts_per_page'] > 0 ? ( $this->query_vars['paged'] - 1 ) * $this->query_vars['posts_per_page'] : 0 );
		} elseif ( $this->object_type == 'term' ) {
			return isset( $this->query_vars['offset'] ) ? $this->query_vars['offset'] : 0;
		} elseif ( $this->object_type == 'user' ) {
			$offset = isset( $this->query_vars['offset'] ) ? $this->query_vars['offset'] : 0;
			$page   = isset( $this->query_vars['paged'] ) ? $this->query_vars['paged'] : 1;

			return $offset + ( $this->query_vars['number'] > 0 ? ( $page - 1 ) * $this->query_vars['number'] : 0 );
		}

		return 0;
	}

	/**
	 * Main render function
	 *
	 * @param string  $callback to render each item
	 * @param array   $args callback function args
	 * @param boolean $return_array whether returns a string or an array of all the iterations
	 * @return mixed
	 */
	public function render( $callback, $args, $return_array = false ) {
		// Remove array keys
		$args = array_values( $args );

		// Query results
		$query_result = $this->query_result;

		$content = [];

		$this->loop_index = $this->init_loop_index();

		$this->is_looping = true;

		// Query is empty
		if ( empty( $this->count ) ) {
			$content[] = $this->get_no_results_content();
		}

		// Iterate
		else {
			// STEP: Loop posts
			if ( $this->object_type == 'post' ) {

				$this->original_post_id = get_the_ID();

				while ( $query_result->have_posts() ) {
					$query_result->the_post();

					$this->loop_object = get_post();

					$part = call_user_func_array( $callback, $args );

					$content[] = self::parse_dynamic_data( $part, get_the_ID() );

					$this->loop_index++;
				}
			}

			// STEP: Loop terms
			elseif ( $this->object_type == 'term' ) {
				foreach ( $query_result as $term_object ) {
					$this->loop_object = $term_object;

					$part = call_user_func_array( $callback, $args );

					$content[] = self::parse_dynamic_data( $part, get_the_ID() );

					$this->loop_index++;
				}
			}

			// STEP: Loop users
			elseif ( $this->object_type == 'user' ) {
				foreach ( $query_result as $user_object ) {
					$this->loop_object = $user_object;

					$part = call_user_func_array( $callback, $args );

					$content[] = self::parse_dynamic_data( $part, get_the_ID() );

					$this->loop_index++;
				}
			}

			// STEP: Other render providers (wooCart, ACF repeater, Meta Box groups)
			else {
				$this->original_post_id = get_the_ID();

				foreach ( $query_result as $loop_key => $loop_object ) {
					// NOTE: Undocumented
					$this->loop_object = apply_filters( 'bricks/query/loop_object', $loop_object, $loop_key, $this );

					$part = call_user_func_array( $callback, $args );

					$content[] = self::parse_dynamic_data( $part, get_the_ID() );

					$this->loop_index++;
				}
			}
		}

		$this->loop_object = null;

		$this->is_looping = false;

		$this->reset_postdata();

		return $return_array ? $content : implode( '', $content );
	}

	public static function parse_dynamic_data( $content, $post_id ) {
		if ( is_array( $content ) ) {
			if ( isset( $content['background']['image']['useDynamicData'] ) ) {
				$size = isset( $content['background']['image']['size'] ) ? $content['background']['image']['size'] : BRICKS_DEFAULT_IMAGE_SIZE;

				$images = Integrations\Dynamic_Data\Providers::render_tag( $content['background']['image']['useDynamicData'], $post_id, 'image', [ 'size' => $size ] );

				if ( isset( $images[0] ) ) {
					$content['background']['image']['url'] = is_numeric( $images[0] ) ? wp_get_attachment_image_url( $images[0], $size ) : $images[0];

					unset( $content['background']['image']['useDynamicData'] );
				}
			}

			return map_deep( $content, [ 'Bricks\Integrations\Dynamic_Data\Providers', 'render_content' ] );
		} else {
			return Integrations\Dynamic_Data\Providers::render_content( $content, $post_id );
		}
	}

	/**
	 * Reset the global $post to the parent query or the global $wp_query
	 *
	 * @since 1.5
	 *
	 * @return void
	 */
	public function reset_postdata() {

		// Reset is not needed
		if ( empty( $this->original_post_id ) ) {
			return;
		}

		$looping_query_id = self::is_any_looping();

		// Not a nested query, reset global query
		if ( ! $looping_query_id ) {
			wp_reset_postdata();
		}

		// Set the parent query context
		global $post;

		$post = get_post( $this->original_post_id );

		setup_postdata( $post );
	}

	/**
	 * Get the current Query object
	 *
	 * @return Query
	 */
	public static function get_query_object( $query_id = false ) {
		global $bricks_loop_query;

		if ( ! is_array( $bricks_loop_query ) || $query_id && ! array_key_exists( $query_id, $bricks_loop_query ) ) {
			return false;
		}

		return $query_id ? $bricks_loop_query[ $query_id ] : end( $bricks_loop_query );
	}

	/**
	 * Get the current Query object type
	 *
	 * @return string
	 */
	public static function get_query_object_type( $query_id = '' ) {
		$query = self::get_query_object( $query_id );

		return $query ? $query->object_type : '';
	}

	/**
	 * Get the object of the current loop iteration
	 *
	 * @return mixed
	 */
	public static function get_loop_object( $query_id = '' ) {
		$query = self::get_query_object( $query_id );

		return $query ? $query->loop_object : null;
	}

	/**
	 * Get the object ID of the current loop iteration
	 *
	 * @return mixed
	 */
	public static function get_loop_object_id( $query_id = '' ) {
		$object = self::get_loop_object( $query_id );

		if ( empty( $object ) ) {
			return null;
		}

		if ( is_a( $object, 'WP_Post' ) ) {
			return $object->ID;
		}

		if ( is_a( $object, 'WP_Term' ) ) {
			return $object->term_id;
		}

		if ( is_a( $object, 'WP_User' ) ) {
			return $object->ID;
		}

		return null;
	}

	/**
	 * Get the object type of the current loop iteration
	 *
	 * @return mixed
	 */
	public static function get_loop_object_type( $query_id = '' ) {
		$object = self::get_loop_object( $query_id );

		if ( empty( $object ) ) {
			return null;
		}

		if ( is_a( $object, 'WP_Post' ) ) {
			return 'post';
		}

		if ( is_a( $object, 'WP_Term' ) ) {
			return 'term';
		}

		if ( is_a( $object, 'WP_User' ) ) {
			return 'user';
		}

		return null;
	}

	/**
	 * Get the current loop iteration index
	 *
	 * @return mixed
	 */
	public static function get_loop_index() {
		$query = self::get_query_object();

		return $query && $query->is_looping ? $query->loop_index : '';
	}

	/**
	 * Check if the render function is looping (in the current query)
	 *
	 * @param string $element_id if specificed checks if the element_id matches the element that is set to loop (e.g. container)
	 * @return boolean
	 */
	public static function is_looping( $element_id = '', $query_id = '' ) {
		$query = self::get_query_object( $query_id );

		if ( ! $query ) {
			return false;
		}

		if ( empty( $element_id ) ) {
			return $query->is_looping;
		}

		// Still here, search for the element_id query
		$query = self::get_query_for_element_id( $element_id );

		return $query ? $query->is_looping : false;
	}

	/**
	 * Get query object created for a specific element ID
	 *
	 * @param string $element_id
	 * @return mixed
	 */
	public static function get_query_for_element_id( $element_id = '' ) {
		if ( empty( $element_id ) ) {
			return false;
		}

		global $bricks_loop_query;

		if ( empty( $bricks_loop_query ) ) {
			return false;
		}

		foreach ( $bricks_loop_query as $key => $query ) {
			if ( $query->element_id == $element_id ) {
				return $query;
			}
		}

		return false;
	}

	/**
	 * Get element ID of query loop element
	 *
	 * @param object $query Defaults to current query.
	 *
	 * @since 1.4
	 *
	 * @return string|boolean Element ID or false
	 */
	public static function get_query_element_id( $query = '' ) {
		$query = self::get_query_object( $query );

		return ! empty( $query->element_id ) ? $query->element_id : false;
	}

	/**
	 * Check if there is any active query looping (nested queries) and if yes, return the query ID of the most deep query
	 *
	 * @return mixed
	 */
	public static function is_any_looping() {
		global $bricks_loop_query;

		if ( empty( $bricks_loop_query ) ) {
			return false;
		}

		$query_ids = array_reverse( array_keys( $bricks_loop_query ) );

		foreach ( $query_ids as $query_id ) {
			if ( $bricks_loop_query[ $query_id ]->is_looping ) {
				return $query_id;
			}
		}

		return false;
	}

	/**
	 * Convert a list of option strings taxonomy::term_id into a list of term_ids
	 */
	public static function convert_terms_to_ids( $terms = [] ) {
		if ( empty( $terms ) ) {
			return [];
		}

		$options = [];

		foreach ( $terms as $term ) {
			if ( ! is_string( $term ) ) {
				continue;
			}

			$term_parts = explode( '::', $term );
			// $taxonomy   = $term_parts[0];

			$options[] = $term_parts[1];
		}

		return $options;
	}

	public function get_no_results_content() {
		$content = isset( $this->settings['query']['no_results_text'] ) ? $this->settings['query']['no_results_text'] : '';

		if ( ! empty( $content ) ) {
			$content = '<div class="bricks-posts-nothing-found"><p>' . $content . '</p></div>';

			$content = Integrations\Dynamic_Data\Providers::render_content( $content );

			$content = do_shortcode( $content );
		}

		$content = apply_filters( 'bricks/query/no_results_content', $content, $this->settings, $this->element_id );

		return $content;
	}
}
