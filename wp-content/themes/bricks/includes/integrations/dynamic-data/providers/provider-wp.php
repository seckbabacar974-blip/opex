<?php
namespace Bricks\Integrations\Dynamic_Data\Providers;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Provider_Wp extends Base {
	public function register_tags() {
		$tags = $this->get_tags_config();

		foreach ( $tags as $key => $tag ) {
			$this->tags[ $key ] = [
				'name'     => '{' . $key . '}',
				'label'    => $tag['label'],
				'group'    => $tag['group'],
				'provider' => $this->name
			];

			if ( ! empty( $tag['deprecated'] ) ) {
				$this->tags[ $key ]['deprecated'] = $tag['deprecated'];
			}

			if ( ! empty( $tag['render'] ) ) {
				$this->tags[ $key ]['render'] = $tag['render'];
			}
		}
	}

	public function get_tags_config() {
		$tags = [
			// Post
			'post_title'          => [
				'label' => esc_html__( 'Post title', 'bricks' ),
				'group' => 'post'
			],

			'post_id'             => [
				'label' => esc_html__( 'Post ID', 'bricks' ),
				'group' => 'post'
			],

			'post_url'            => [
				'label' => esc_html__( 'Post link', 'bricks' ),
				'group' => 'post'
			],

			'post_date'           => [
				'label' => esc_html__( 'Post date', 'bricks' ),
				'group' => 'post',
			],

			'post_modified'       => [
				'label' => esc_html__( 'Post modified date', 'bricks' ),
				'group' => 'post',
			],

			'post_time'           => [
				'label' => esc_html__( 'Post time', 'bricks' ),
				'group' => 'post',
			],

			'post_comments_count' => [
				'label' => esc_html__( 'Post comments count', 'bricks' ),
				'group' => 'post'
			],

			'post_comments'       => [
				'label' => esc_html__( 'Post comments', 'bricks' ),
				'group' => 'post'
			],

			'post_content'        => [
				'label' => esc_html__( 'Post content', 'bricks' ),
				'group' => 'post'
			],

			'post_excerpt'        => [
				'label' => esc_html__( 'Post excerpt', 'bricks' ),
				'group' => 'post'
			],

			'read_more'           => [
				'label' => esc_html__( 'Read more', 'bricks' ),
				'group' => 'post',
			],

			// Image
			'featured_image'      => [
				'label' => esc_html__( 'Featured image', 'bricks' ),
				'group' => 'post',
			],

			'featured_image_tag'  => [
				'label'      => esc_html__( 'Featured image tag', 'bricks' ),
				'group'      => 'post',
				'deprecated' => 1
			],

			// Author
			'author_name'         => [
				'label' => esc_html__( 'Author name', 'bricks' ),
				'group' => 'author',
			],

			'author_bio'          => [
				'label' => esc_html__( 'Author bio', 'bricks' ),
				'group' => 'author',
			],

			'author_email'        => [
				'label' => esc_html__( 'Author email', 'bricks' ),
				'group' => 'author',
			],

			'author_website'      => [
				'label' => esc_html__( 'Author website', 'bricks' ),
				'group' => 'author',
			],

			'author_avatar'       => [
				'label' => esc_html__( 'Author avatar', 'bricks' ),
				'group' => 'author',
			],

			// Site
			'site_title'          => [
				'label' => esc_html__( 'Site title', 'bricks' ),
				'group' => 'site',
			],

			'site_tagline'        => [
				'label' => esc_html__( 'Site tagline', 'bricks' ),
				'group' => 'site',
			],

			'site_url'            => [
				'label' => esc_html__( 'Site URL', 'bricks' ),
				'group' => 'site',
			],

			'url_parameter'       => [
				'label' => esc_html__( 'URL parameter - add key after :', 'bricks' ),
				'group' => 'site',
			],

			// Archive
			'archive_title'       => [
				'label' => esc_html__( 'Archive title', 'bricks' ),
				'group' => 'archive',
			],

			'archive_description' => [
				'label' => esc_html__( 'Archive description', 'bricks' ),
				'group' => 'archive',
			],

			// Terms
			'term_id'             => [
				'label'  => esc_html__( 'Term id', 'bricks' ),
				'group'  => 'terms',
				'render' => 'terms',
			],

			'term_name'           => [
				'label'  => esc_html__( 'Term name', 'bricks' ),
				'group'  => 'terms',
				'render' => 'terms',
			],

			'term_url'            => [
				'label'  => esc_html__( 'Term archive URL', 'bricks' ),
				'group'  => 'terms',
				'render' => 'terms',
			],

			'term_description'    => [
				'label'  => esc_html__( 'Term description', 'bricks' ),
				'group'  => 'terms',
				'render' => 'terms',
			],

			'term_meta'           => [
				'label'  => esc_html__( 'Term meta - add key after :', 'bricks' ),
				'group'  => 'terms',
				'render' => 'terms',
			],

			// Date
			'current_date'        => [
				'label' => esc_html__( 'Current date', 'bricks' ),
				'group' => 'date',
			]
		];

		// User Profile fields
		$user_fields = [
			'id'           => esc_html__( 'User ID', 'bricks' ),
			'login'        => esc_html__( 'Username', 'bricks' ),
			'email'        => esc_html__( 'Email', 'bricks' ),
			'url'          => esc_html__( 'Website', 'bricks' ),
			'nicename'     => esc_html__( 'Nickname', 'bricks' ),
			'description'  => esc_html__( 'Bio', 'bricks' ),
			'first_name'   => esc_html__( 'First name', 'bricks' ),
			'last_name'    => esc_html__( 'Last name', 'bricks' ),
			'display_name' => esc_html__( 'Display name', 'bricks' ),
			'picture'      => esc_html__( 'Profile picture', 'bricks' ),
			'meta'         => esc_html__( 'User meta - add key after :', 'bricks' ),
		];

		foreach ( $user_fields as $key => $label ) {
			$tags[ 'wp_user_' . $key ] = [
				'label' => $label ,
				'group' => 'userProfile'
			];
		}

		// Add taxonomies related tags
		$taxs = get_taxonomies(
			[
				// 'public'  => true, (commented out @since 1.5, see: #38kj7az)
				'show_ui' => true,
			],
			'objects'
		);

		foreach ( $taxs as $tax ) {
			if ( in_array( $tax->name, [ BRICKS_DB_TEMPLATE_TAX_TAG, BRICKS_DB_TEMPLATE_TAX_BUNDLE ] ) ) {
				continue;
			}
			$tags[ 'post_terms_' . $tax->name ] = [
				'label'  => $tax->label,
				'group'  => 'terms',
				'render' => 'post_terms',
			];
		}

		// Echo
		$tags['echo'] = [
			'label' => esc_html__( 'Output PHP function', 'bricks' ),
			'group' => 'advanced',
		];

		// ACF not present: Default to WordPress custom fields
		$metas = $this->get_post_meta_keys();

		foreach ( $metas as $key ) {
			$label                = ucwords( str_replace( '_', ' ', $key ) );
			$tags[ 'cf_' . $key ] = [
				'label'  => $label,
				'group'  => 'customFields',
				'render' => 'post_metas',
			];
		}

		return $tags;
	}

	/**
	 * Returns a list of post meta keys (uses $post context)
	 *
	 * @return array
	 */
	public function get_post_meta_keys() {
		$list = [];

		// @since 1.5.1
		$meta_keys = $this->get_site_meta_keys();

		if ( empty( $meta_keys ) ) {
			return $list;
		}

		$exclude = [];

		// Exclude the ACF custom fields from the custom fields list
		if ( Provider_Acf::load_me() ) {

			$patterns = [];

			$acf_fields = Provider_Acf::get_fields();

			foreach ( $acf_fields as $field ) {
				$exclude[] = $field['name'];

				// Note: for the sake of simplification the nested repeaters are excluded based on the parent prefix
				if ( $field['type'] == 'repeater' && ! empty( $field['sub_fields'] ) ) {
					foreach ( $field['sub_fields'] as $sub_field ) {
						$patterns[] = "/{$field['name']}_(\d+)_{$sub_field['name']}(.?)/";
					}
				}
			}

			if ( ! empty( $patterns ) ) {
				foreach ( $patterns as $pattern ) {
					// Excludes meta keys based on the patterns
					$meta_keys = preg_grep( $pattern, $meta_keys, PREG_GREP_INVERT );
				}
			}

			unset( $patterns );
		}

		// Exclude the Pods custom fields from the custom fields list
		if ( Provider_Pods::load_me() ) {
			$pods_fields = Provider_Pods::get_fields();

			foreach ( $pods_fields as $field ) {
				$exclude[] = $field['name'];
			}
		}

		// Exclude the Meta Box custom fields from the custom fields list
		if ( Provider_Metabox::load_me() ) {
			$metabox_fields = Provider_Metabox::get_fields();

			foreach ( $metabox_fields as $field ) {
				$exclude[] = $field['id'];

				if ( $field['type'] == 'group' && ! empty( $field['fields'] ) ) {
					foreach ( $field['fields'] as $sub_field ) {
						$exclude[] = "{$field['id']}_{$sub_field['id']}";
					}
				}
			}
		}

		// Exclude the CMB2 custom fields from the custom fields list
		if ( Provider_Cmb2::load_me() ) {
			$cmb2_fields = Provider_Cmb2::get_fields();

			foreach ( $cmb2_fields as $field ) {
				$exclude[] = $field['name'];
			}
		}

		// Exclude the Toolset custom fields from the custom fields list
		if ( Provider_Toolset::load_me() ) {
			$toolset_fields = Provider_Toolset::get_fields();

			foreach ( $toolset_fields as $field ) {
				$exclude[] = $field['meta_key'];
			}
		}

		if ( Provider_Jetengine::load_me() ) {
			$jetengine_fields = Provider_Jetengine::get_fields();

			foreach ( $jetengine_fields as $field ) {
				$exclude[] = $field['name'];
			}
		}

		// ignore post meta keys that start with '_' (invisible)
		foreach ( $meta_keys as $key ) {
			if ( '_' !== substr( $key, 0, 1 ) && ! in_array( $key, $exclude ) ) {
				$list[] = $key;
			}
		}

		return $list;
	}

	/**
	 * Main function to render the tag value for WordPress provider
	 *
	 * @param [type] $tag
	 * @param [type] $post
	 * @param [type] $args
	 * @param [type] $context
	 * @return void
	 */
	public function get_tag_value( $tag, $post, $args, $context ) {

		$post_id = isset( $post->ID ) ? $post->ID : '';

		// STEP: Check for filter args
		$filters = $this->get_filters_from_args( $args );

		// STEP: Get the value
		$value = '';

		$render = isset( $this->tags[ $tag ]['render'] ) ? $this->tags[ $tag ]['render'] : $tag;

		switch ( $render ) {

			// Post
			case 'post_id':
				$value = $post_id;
				break;

			case 'post_url':
				$value = get_permalink( $post_id );
				break;

			case 'post_title':
				$value = get_the_title( $post_id );
				break;

			case 'read_more':
				$value           = apply_filters( 'bricks/dynamic_data/read_more', __( 'Read more', 'bricks' ), $post );
				$filters['link'] = true;
				break;

			case 'post_date':
				$filters['object_type'] = 'date';
				$value                  = get_post_time( 'U', false, $post, false );
				break;

			case 'post_modified':
				$filters['object_type'] = 'date';
				$value                  = get_post_modified_time( 'U', false, $post, false );
				break;

			case 'post_time':
				$filters['object_type'] = 'date';
				$filters['meta_key']    = isset( $filters['meta_key'] ) ? $filters['meta_key'] : get_option( 'time_format' );
				$value                  = get_post_time( 'U', false, $post, false );
				break;

			case 'post_comments_count':
				$value = get_comments_number( $post );
				break;

			case 'post_comments':
				$comments_number = get_comments_number( $post );
				// Translators: %s = the number of comments
				$value = sprintf( _nx( '%s comment', '%s comments', $comments_number, 'Translators: %s = the number of comments', 'bricks' ), $comments_number );
				break;

			case 'post_content':
				wp_enqueue_style( 'wp-block-library' );
				$value = $this->get_the_content( $post );

				// To prevent issues with embeded content (e.g. youtube videos)
				$filters['skip_sanitize'] = true;
				break;

			case 'post_excerpt':
				$value = \Bricks\Helpers::get_the_excerpt( $post, ! empty( $filters['num_words'] ) ? $filters['num_words'] : 55 );
				break;

			// Image
			case 'featured_image':
			case 'featured_image_tag':
				$filters['object_type'] = 'media';
				$filters['image']       = 'true';
				$value                  = get_post_thumbnail_id( $post_id );

				// Get the WooCommerce placeholder image if featured image is empty (@since 1.5.1)
				if ( empty( $value ) && get_post_type( $post ) === 'product' ) {
					$value = get_option( 'woocommerce_placeholder_image', 0 );
				}
				break;

			// Author
			case 'author_id':
			case 'author_name':
			case 'author_bio':
			case 'author_email':
			case 'author_website':
			case 'author_avatar':
				$user  = isset( $post->post_author ) ? get_user_by( 'id', $post->post_author ) : false;
				$value = $user ? $this->get_user_tag_value( $tag, $user, $filters, $context ) : '';
				break;

			// User Profile fields
			case 'wp_user_id':
			case 'wp_user_login':
			case 'wp_user_email':
			case 'wp_user_url':
			case 'wp_user_nicename':
			case 'wp_user_description':
			case 'wp_user_first_name':
			case 'wp_user_last_name':
			case 'wp_user_display_name':
			case 'wp_user_picture':
			case 'wp_user_meta':
				$user = \Bricks\Query::get_loop_object_type() == 'user' ? \Bricks\Query::get_loop_object() : wp_get_current_user();

				$value = $this->get_user_tag_value( $tag, $user, $filters, $context );
				break;

			// Site
			case 'site_title':
				$value = get_bloginfo( 'name', 'display' );
				break;

			case 'site_tagline':
				$value = get_bloginfo( 'description', 'display' );
				break;

			case 'site_url':
				$value = get_bloginfo( 'url', 'display' );
				break;

			case 'url_parameter':
				$parameter = isset( $filters['meta_key'] ) ? $filters['meta_key'] : false;
				$value     = $parameter && isset( $_GET[ $parameter ] ) ? esc_attr( $_GET[ $parameter ] ) : '';
				break;

			case 'current_date':
				$filters['object_type'] = 'date';
				$value                  = time();
				break;

			// Terms
			case 'terms':
				$value = $this->get_term_tag_value( $tag, $filters, $context );

				if ( ! empty( $filters['link'] ) ) {
					$object_type = \Bricks\Query::get_loop_object_type();

					if ( $object_type == 'term' ) {
						$filters['object_type'] = $object_type;
						$filters['object']      = \Bricks\Query::get_loop_object();
					}
				}

				break;

			// Archive
			case 'archive_title':
				if ( empty( $filters['add_context'] ) ) {
					add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
				}

				$value = get_the_archive_title();

				if ( empty( $filters['add_context'] ) ) {
					remove_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
				}
				break;

			case 'archive_description':
				$value = get_the_archive_description();
				break;

			case 'post_terms':
				$value = $this->get_post_terms_value( $tag, $post, $filters, $context );
				break;

			case 'post_metas':
				$meta_key = str_replace( 'cf_', '', $tag );

				$value = get_post_meta( $post_id, $meta_key, true );

				// NOTE: Undocumented
				$value = apply_filters( 'bricks/dynamic_data/meta_value/' . $meta_key, $value, $post );

				break;

			case 'echo':
				$value = $this->get_echo_callback_value( $filters, $context, $post );
				break;
		}

		// STEP: Apply context (text, link, image, media)
		$value = $this->format_value_for_context( $value, $tag, $post_id, $filters, $context );

		return $value;
	}

	public function get_echo_callback_value( $filters, $context, $post ) {
		if ( empty( $filters['meta_key'] ) ) {
			return '';
		}

		$callback = $filters['meta_key'];
		$args     = [];

		if ( strpos( $callback, '(' ) !== false ) {
			$parts    = explode( '(', $callback );
			$callback = trim( $parts[0] );
			$args     = explode( ',', rtrim( rtrim( $parts[1] ), ')' ) );

			foreach ( $args as $key => $arg ) {
				$new_arg      = trim( $arg );
				$new_arg      = str_replace( '\'', '', $new_arg );
				$args[ $key ] = $new_arg;
			}

		} else {
			$callback = trim( $callback );
		}

		return function_exists( $callback ) ? call_user_func_array( $callback, $args ) : '';
	}

	/**
	 * Helper function to get the post content
	 *
	 * @param [type] $post
	 * @return void
	 */
	public function get_the_content( $post ) {
		if ( empty( $post ) ) {
			return '';
		}

		$content = get_the_content( null, false, $post );

		$content = apply_filters( 'the_content', $content );

		return $content;
	}

	/**
	 * Render user/author related data
	 *
	 * @param WP_User $user
	 * @param [type]  $tag
	 * @param [type]  $args
	 * @param [type]  $context
	 * @return void
	 */
	public function get_user_tag_value( $tag, $user, $filters, $context ) {
		if ( empty( $user->ID ) ) {
			return '';
		}

		$value = '';

		$field_type = str_replace( [ 'wp_user_','author_' ], '', $tag );

		switch ( $field_type ) {

			case 'id':
				$value = $user->ID;
				break;

			case 'login':
			case 'email':
			case 'url':
			case 'website':
			case 'nicename':
				$field_type = 'website' == $field_type ? 'url' : $field_type; // Legacy
				$field      = 'user_' . $field_type;
				$value      = isset( $user->{$field} ) ? $user->{$field} : '';
				break;

			case 'bio':
			case 'description':
			case 'first_name':
			case 'last_name':
			case 'display_name':
				$field_type = 'bio' == $field_type ? 'description' : $field_type; // Legacy
				$value      = isset( $user->{$field_type} ) ? $user->{$field_type} : '';
				break;

			case 'name':
				if ( ! empty( $user->first_name ) && ! empty( $user->last_name ) ) {
					$value = trim( $user->first_name . ' ' . $user->last_name );
				} else {
					$value = trim( $user->display_name );
				}
				break;

			case 'picture':
			case 'avatar':
				// If context = image, increase the default image size to 512px
				$default_size = $context === 'image' ? 512 : 96;

				$size = empty( $filters['num_words'] ) ? $default_size : $filters['num_words'];

				$alt = sprintf( esc_html__( 'Avatar image of %s', 'bricks' ), get_the_author_meta( 'display_name', $user->ID ) );

				$value = $context === 'link' || $context === 'image' ? get_avatar_url( $user->ID, [ 'size' => $size ] ) : get_avatar( $user->ID, $size, '', $alt );
				break;

			case 'meta':
				if ( ! empty( $filters['meta_key'] ) ) {
					$value = get_user_meta( $user->ID, $filters['meta_key'], true );
				}

				break;
		}

		// NOTE: Undocumented
		$value = apply_filters( 'bricks/dynamic_data/user_value', $value, $field_type, $filters );

		return $value;
	}

	/**
	 * Render Post Terms value
	 *
	 * @param [type] $tag
	 * @param [type] $post
	 * @param [type] $filters
	 * @param [type] $context
	 * @return void
	 */
	public function get_post_terms_value( $tag, $post, $args, $context ) {

		if ( ! isset( $post->ID ) ) {
			return '';
		}

		$taxonomy = str_replace( 'post_terms_', '', $tag );

		$terms = wp_get_post_terms( $post->ID, $taxonomy );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return '';
		}

		// NOTE: Undocumented. Let Bricks know how to render the terms: with links to their archives or not
		$has_links = apply_filters( 'bricks/dynamic_data/post_terms_links', true, $post, $taxonomy );

		$output = [];

		foreach ( $terms as $term ) {

			$item = $term->name;

			if ( $has_links ) {
				$url = get_term_link( $term );

				if ( ! empty( $url ) && ! is_wp_error( $url ) ) {
					$item = '<a href="' . esc_url( $url ) . '">' . $item . '</a>';
				}
			}

			$output[] = $item;
		}

		$sep = isset( $filters['meta_key'] ) ? $filters['meta_key'] : ', ';

		// NOTE: Undocumented.
		$sep = apply_filters( 'bricks/dynamic_data/post_terms_separator', $sep, $post, $taxonomy );

		return implode( $sep, $output );
	}

	public function get_term_tag_value( $tag, $filters, $context ) {
		$looping_query_id = \Bricks\Query::is_any_looping();

		if ( ! empty( $looping_query_id ) ) {
			$object = \Bricks\Query::get_loop_object( $looping_query_id );
		}

		// Is tax archive?
		if ( empty( $object ) && is_tax() ) {
			$object = get_queried_object();
		}

		// Not a WP_Term, leave
		if ( empty( $object ) || ! is_a( $object, 'WP_Term' ) ) {
			return '';
		}

		switch ( $tag ) {

			case 'term_id':
				$value = $object->term_id;
				break;

			case 'term_name':
				$value = $object->name;
				break;

			case 'term_description':
				$value = $object->description;
				break;

			case 'term_url':
				$value = get_term_link( $object );
				break;

			case 'term_meta':
				if ( ! empty( $filters['meta_key'] ) ) {
					$value = get_term_meta( $object, $filters['meta_key'], true );
				}

				break;
		}

		return $value;
	}

	/**
	 * Get all the the unique postmeta keys
	 *
	 * @since 1.5.1
	 *
	 * @return array
	 */
	private function get_site_meta_keys() {
		$cache_key = 'brx_all_meta_keys';

		$meta_keys = wp_cache_get( $cache_key, 'bricks' );

		if ( $meta_keys === false ) {
			global $wpdb;

			// Query DB for all the unique meta keys (@see https://developer.wordpress.org/reference/classes/wpdb/get_results/)
			$meta_keys_results = $wpdb->get_results( "SELECT DISTINCT($wpdb->postmeta.meta_key) FROM $wpdb->postmeta WHERE 1", OBJECT );

			if ( empty( $meta_keys_results ) || ! is_array( $meta_keys_results ) ) {
				return [];
			}

			$meta_keys = wp_list_pluck( $meta_keys_results, 'meta_key' );

			wp_cache_set( $cache_key, $meta_keys, 'bricks', 5 * MINUTE_IN_SECONDS );
		}

		return $meta_keys;
	}
}
