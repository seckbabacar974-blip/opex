<?php
namespace Bricks\Integrations\Dynamic_Data\Providers;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Provider_Acf extends Base {

	public static function load_me() {
		add_action( 'save_post_acf-field-group', [ __CLASS__, 'flush_cache' ] );
		return class_exists( 'ACF' );
	}

	public function register_tags() {
		$fields = self::get_fields();

		foreach ( $fields as $field ) {
			$this->register_tag( $field );
		}
	}

	public function register_tag( $field, $parent_field = [] ) {
		$contexts = self::get_fields_by_context();

		$type = $field['type'];

		if ( ! isset( $contexts[ $type ] ) ) {
			return;
		}

		foreach ( $contexts[ $type ] as $context ) {

			// Add parent field name to the field name if needed
			$name = ! empty( $parent_field['name'] ) ? 'acf_' . $parent_field['name'] . '_' . $field['name'] : 'acf_' . $field['name'];

			// Add the context to the field name (legacy)
			if ( $context !== self::CONTEXT_TEXT && $context !== self::CONTEXT_LOOP && empty( $parent_field ) ) {
				$name .= '_' . $context;
			}

			$label = ! empty( $parent_field['label'] ) ? $field['label'] . ' (' . $parent_field['label'] . ')' : $field['label'];

			if ( $context === self::CONTEXT_LOOP ) {
				$label = 'ACF ' . ucfirst( $type ) . ': ' . $label;
			}

			$tag = [
				'name'     => '{' . $name . '}',
				'label'    => $label,
				'group'    => 'ACF',
				'field'    => $field,
				'provider' => $this->name,
			];

			if ( ! empty( $parent_field ) ) {
				// Add the parent field attributes to the child tag so we could retrieve the value of group sub-fields
				$tag['parent'] = [
					'key'  => $parent_field['key'],
					'name' => $parent_field['name'],
					'type' => $parent_field['type'],
				];

				if ( ! empty( $parent_field['_bricks_locations'] ) ) {
					$tag['parent']['_bricks_locations'] = $parent_field['_bricks_locations'];
				}
			}

			// Register fields for the Loop context ( e.g. Repeater, Relationship..)
			if ( $context === self::CONTEXT_LOOP || $type === 'group' ) {

				// Register the group field tag as deprecated to be used in case groups are nested inside a repeater (@since 1.5.1)
				if ( $type === 'group' ) {
					$this->tags[ $name ]               = $tag;
					$this->tags[ $name ]['deprecated'] = 1;
				} else {
					$this->loop_tags[ $name ] = $tag;
				}

				// Check for sub-fields (including group field sub-fields)
				if ( ! empty( $field['sub_fields'] ) ) {
					foreach ( $field['sub_fields'] as $sub_field ) {
						$this->register_tag( $sub_field, $field ); // Recursive
					}
				}
			}

			// Only register fields from other contexts, other than CONTEXT_TEXT, if they are not sub-fields (legacy purposes)
			elseif ( $context === self::CONTEXT_TEXT || empty( $parent_field ) ) {
				$this->tags[ $name ] = $tag;

				if ( $context !== self::CONTEXT_TEXT ) {
					$this->tags[ $name ]['deprecated'] = 1;
				}
			}
		}
	}

	public static function get_fields() {
		if ( ! function_exists( 'acf_get_field_groups' ) || ! function_exists( 'acf_get_fields' ) || ! function_exists( 'get_field' ) ) {
			return [];
		}

		$last_changed = wp_cache_get_last_changed( 'bricks_acf-field-group' );
		$cache_key    = md5( 'acf_fields' . $last_changed );
		$acf_fields   = wp_cache_get( $cache_key, 'bricks' );

		if ( false === $acf_fields ) {

			// NOTE: Undocumented. This allows the user to remove some field groups from the picker
			$groups = apply_filters( 'bricks/acf/filter_field_groups', acf_get_field_groups() );

			if ( empty( $groups ) || ! is_array( $groups ) ) {
				return [];
			}

			$acf_fields = [];

			foreach ( $groups as $group ) {

				// Group fields
				$fields = acf_get_fields( $group );

				if ( ! is_array( $fields ) ) {
					continue;
				}

				$locations = self::get_fields_locations( $group );

				if ( ! empty( $locations ) ) {

					foreach ( $fields as $field ) {
						$field['_bricks_locations'] = $locations; // Save the field with a special bricks attribute
						$acf_fields[]               = $field;
					}

				} else {
					$acf_fields = array_merge( $acf_fields, $fields );
				}

			}

			wp_cache_set( $cache_key, $acf_fields, 'bricks', DAY_IN_SECONDS );
		}

		return $acf_fields;
	}

	public static function get_fields_locations( $group ) {
		if ( ! isset( $group['location'] ) || ! is_array( $group['location'] ) ) {
			return [];
		}

		$locations = [];

		foreach ( $group['location'] as $conditions ) {
			foreach ( $conditions as $condition ) {
				if ( ! isset( $condition['param'] ) ) {
					continue;
				}

				if ( 'options_page' == $condition['param'] ) {
					$locations['option'] = 1;
				}

				if ( in_array( $condition['param'], [ 'user_role', 'current_user', 'current_user_role', 'user_form' ] ) ) {
					$locations['user'] = 1;
				}

				if ( $condition['param'] == 'taxonomy' ) {
					$locations['term'] = 1;
				}
			}
		}

		return array_keys( $locations );
	}

	/**
	 * Get tag value main function
	 *
	 * @param string  $tag The tag name e.g. "acf_my_field"
	 * @param WP_Post $post
	 * @param array   $args The dynamic data tag arguments
	 * @param string  $context E.g. text, link, image, ..
	 * @return mixed The tag value
	 */
	public function get_tag_value( $tag, $post, $args, $context ) {

		$post_id = isset( $post->ID ) ? $post->ID : '';

		$field = $this->tags[ $tag ]['field'];

		// STEP: Check for filter args
		$filters = $this->get_filters_from_args( $args );

		// STEP: Get the value
		$value = $this->get_raw_value( $tag, $post_id );

		switch ( $field['type'] ) {

			case 'select':
			case 'checkbox':
			case 'radio':
			case 'button_group':
				$value = $this->process_choices_fields( $value, $field );
				break;

			case 'true_false':
				$value = $value ? esc_html__( 'True', 'bricks' ) : esc_html__( 'False', 'bricks' );
				break;

			case 'user':
				$filters['object_type'] = 'user';

				// ACF allows for single or multiple users
				$value = $field['multiple'] ? $value : [ $value ];

				$value = 'id' === $field['return_format'] ? $value : wp_list_pluck( $value, 'ID' );

				break;

			case 'google_map':
				$value = $this->process_google_map_field( $value, $field );
				break;

			case 'taxonomy':
				$filters['object_type'] = 'term';
				$filters['taxonomy']    = $field['taxonomy'];

				// NOTE: Undocumented
				$show_as_link = apply_filters( 'bricks/acf/taxonomy/show_as_link', true, $value, $field );

				if ( $show_as_link ) {
					$filters['link'] = true;
				}

				$value = is_array( $value ) ? $value : [ $value ];

				$value = 'id' === $field['return_format'] ? $value : wp_list_pluck( $value, 'term_id' );
				break;

			case 'image':
			case 'gallery':
				$filters['object_type'] = 'media';
				$filters['separator']   = '';

				$value = empty( $value ) ? [] : (array) $value;

				if ( ! empty( $field['return_format'] ) ) {

					if ( 'array' === $field['return_format'] ) {
						$value = isset( $value['id'] ) ? [ $value['id'] ] : wp_list_pluck( $value, 'id' );
					} elseif ( 'url' === $field['return_format'] ) {
						$value = array_map( 'attachment_url_to_postid', $value );
						$value = array_filter( $value );
					}

				}
				break;

			case 'oembed':
				// if context is not text get the link value (instead of the oembed iframe)
				if ( 'text' !== $context ) {
					$value = get_post_meta( $post_id, $field['name'], true );
				} else {
					$filters['skip_sanitize'] = true;
				}
				break;

			case 'file':
				$filters['object_type'] = 'media';
				$filters['link']        = true;

				if ( isset( $field['return_format'] ) ) {
					if ( 'array' === $field['return_format'] ) {
						$value = $value['id'];
					} elseif ( 'url' === $field['return_format'] ) {
						$value = attachment_url_to_postid( $value );
					}
				}
				break;

			case 'link':
				// Possible returns: url or array
				if ( 'array' === $field['return_format'] ) {
					$value = isset( $value['url'] ) ? $value['url'] : '';
				}
				break;

			case 'post_object':
			case 'relationship':
				$filters['object_type'] = 'post';
				$filters['link']        = true;

				if ( isset( $field['return_format'] ) && 'object' === $field['return_format'] ) {
					if ( isset( $value->ID ) ) {
						$value = $value->ID;
					} elseif ( is_array( $value ) ) {
						$value = wp_list_pluck( $value, 'ID' );
					}
				}
				break;

			// @see: https://www.advancedcustomfields.com/resources/date-picker/
			// @see: https://www.advancedcustomfields.com/resources/date-time-picker/
			case 'date_picker':
			case 'date_time_picker':
				if ( ! empty( $filters['meta_key'] ) ) {
					// It only works if the output value is set to Ymd or Y-m-d H:i:s (default)
					$default_format = $field['type'] == 'date_picker' ? 'Ymd' : 'Y-m-d H:i:s';

					$date = \DateTime::createFromFormat( $default_format, $value );

					$value = $date->format( 'U' );

					$filters['object_type'] = $field['type'] == 'date_picker' ? 'date' : 'datetime';
				}
				break;

				// @since 1.5.1
			case 'color_picker':
				// Possible return formats: 'string', 'array' (rgba)
				if ( ! empty( $field['return_format'] ) && $field['return_format'] === 'array' ) {
					$value = "rgba({$value['red']},{$value['green']},{$value['blue']},{$value['alpha']})";
				}
				break;
		}

		// STEP: Apply context (text, link, image, media)
		$value = $this->format_value_for_context( $value, $tag, $post_id, $filters, $context );

		return $value;
	}

	/**
	 * Get the field raw value
	 *
	 * @param array      $tag The tag name e.g. "acf_my_field"
	 * @param int|string $post_id
	 * @return void
	 */
	public function get_raw_value( $tag, $post_id ) {
		$tag_object = $this->tags[ $tag ];
		$field      = $tag_object['field'];

		// STEP: Check if in a Relationship, or Repeater loop (could have nested groups inside - @since 1.5.1)
		if ( \Bricks\Query::is_looping() ) {

			$query_type = \Bricks\Query::get_query_object_type();

			// Check if this loop belongs to this provider
			if ( array_key_exists( $query_type, $this->loop_tags ) ) {
				// Query Loop tag object
				$loop_tag_object = $this->loop_tags[ $query_type ];

				// Query Loop tag ACF field key
				$loop_tag_field_key = isset( $loop_tag_object['field']['key'] ) ? $loop_tag_object['field']['key'] : false;

				if ( empty( $loop_tag_field_key ) ) {
					return '';
				}

				// If in a loop created by an ACF Relationship field (@since 1.5.1)
				if ( isset( $loop_tag_object['field']['type'] ) && $loop_tag_object['field']['type'] === 'relationship' ) {
					// The loop already sets the global $post
					$post_id = get_the_ID();

					// Is a Group sub-field
					if ( isset( $tag_object['parent']['type'] ) && $tag_object['parent']['type'] === 'group' ) {
						return $this->get_acf_group_field_value( $tag_object, $post_id );
					}

					// Is a regular field
					return $this->get_acf_field_value( $field, $post_id );
				}

				// NOTE: Bricks needs to build a path to get the final value from the $loop_object
				// This is needed as we don't use the group field, but only the sub-fields (which could also be groups = nested groups!)
				// The iteration starts on the field to which we need to get the value and iterates its parent until it is the query loop field.

				// Store the field names while iterating
				$value_path = [];

				// Get the first parent field object
				$parent_field = isset( $tag_object['parent']['key'] ) ? get_field_object( $tag_object['parent']['key'] ) : false;

				// Check if the parent field is the loop field; if not, iterate up
				while ( isset( $parent_field['key'] ) && $parent_field['key'] !== $loop_tag_field_key ) {
					if ( isset( $parent_field['name'] ) ) {
						$value_path[] = $parent_field['name'];

						// Get the parent field tag object (as registered in Bricks)
						$parent_tag = isset( $this->tags[ $parent_field['name'] ] ) ? $this->tags[ $parent_field['name'] ] : false;
					} else {
						$parent_tag = false;
					}

					// Get the parent of the parent
					$parent_field = isset( $parent_tag['parent']['key'] ) ? get_field_object( $parent_tag['parent']['key'] ) : false;
				}

				// The current loop object (array of values)
				$narrow_values = \Bricks\Query::get_loop_object();

				if ( ! empty( $value_path ) ) {
					// Start with the top parent field name, and go deeper (groups inside of groups..)
					$value_path = array_reverse( $value_path );

					foreach ( $value_path as $name ) {
						if ( isset( $narrow_values[ $name ] ) ) {
							$narrow_values = $narrow_values[ $name ];
						}
					}
				}

				return isset( $narrow_values[ $field['name'] ] ) ? $narrow_values[ $field['name'] ] : '';
			}
		}

		// STEP: Is a Group sub-field
		if ( isset( $tag_object['parent']['type'] ) && $tag_object['parent']['type'] === 'group' ) {
			return $this->get_acf_group_field_value( $tag_object, $post_id );
		}

		// STEP: Still here, get the regular value for this field
		return $this->get_acf_field_value( $field, $post_id );
	}

	/**
	 * Get ACF group field value
	 *
	 * @since 1.5
	 */
	public function get_acf_group_field_value( $tag_object, $post_id ) {
		$field       = $tag_object['field'];
		$group_field = get_field_object( $tag_object['parent']['key'] );

		if ( ! empty( $tag_object['parent']['_bricks_locations'] ) ) {
			$group_field['_bricks_locations'] = $tag_object['parent']['_bricks_locations'];
		}

		$group_value = $this->get_acf_field_value( $group_field, $post_id );

		return isset( $group_value[ $field['name'] ] ) ? $group_value[ $field['name'] ] : '';
	}

	/**
	 * Get ACF field value
	 *
	 * @param array      $field ACF field settings
	 * @param int|string $post_id
	 * @return void
	 */
	public function get_acf_field_value( $field, $post_id ) {

		$acf_object_id = $this->get_object_id( $field, $post_id );

		remove_filter( 'acf_the_content', 'wpautop' );

		// @see https://www.advancedcustomfields.com/resources/get_field/
		$value = get_field( $field['key'], $acf_object_id );

		add_filter( 'acf_the_content', 'wpautop' );

		return $value;
	}

	/**
	 * Process the choice fields to return an array of choice labels
	 *
	 * @param [type] $value
	 * @param [type] $field
	 * @return void
	 */
	public function process_choices_fields( $value, $field ) {
		$value = (array) $value;

		// If return format is set to "Both (array)" return 'label' by default
		if ( ! empty( $field['return_format'] ) ) {
			if ( $field['return_format'] === 'array' ) {
				if ( isset( $value['label'] ) ) {
					unset( $value['value'] );
				} else {
					$value = wp_list_pluck( $value, 'label' );
				}
			}
		}

		return $value;
	}

	public function process_google_map_field( $value, $field ) {
		// NOTE: Undocumented. By default, the google map field will show as an address, if ACF version >= 5.6.8 @see https://www.advancedcustomfields.com/resources/google-map/
		$show_as_address = apply_filters( 'bricks/acf/google_map/show_as_address', defined( 'ACF_VERSION' ) && version_compare( ACF_VERSION, '5.6.8', '>=' ), $value, $field );

		$output = [];

		if ( $show_as_address ) {
			// NOTE: Undocumented. Filter or order the address parts
			$address_parts = apply_filters( 'bricks/acf/google_map/address_parts', [ 'street_name', 'street_number', 'city', 'state', 'post_code', 'country' ], $value, $field );

			foreach ( $address_parts as $key ) {
				if ( ! empty( $value[ $key ] ) ) {
					$output[] = sprintf( '<span class="acf-map-%s">%s</span>', $key, $value[ $key ] );
				}
			}

		} else {
			foreach ( [ 'lat', 'lng' ] as $key ) {
				if ( ! empty( $value[ $key ] ) ) {
					$output[] = sprintf( '<span class="acf-map-%s">%s</span>, ', $key, $value[ $key ] );
				}
			}
		}

		// NOTE: Undocumented.
		return apply_filters( 'bricks/acf/google_map/text_output', implode( ', ', $output ), $value, $field );
	}

	/**
	 * Get all fields supported and their contexts
	 *
	 * @return array
	 */
	private static function get_fields_by_context() {

		$fields = [

			// Basic
			'text'             => [ self::CONTEXT_TEXT ],
			'textarea'         => [ self::CONTEXT_TEXT ],
			'number'           => [ self::CONTEXT_TEXT ],
			'range'            => [ self::CONTEXT_TEXT ],
			'email'            => [ self::CONTEXT_TEXT, self::CONTEXT_LINK ],
			'url'              => [ self::CONTEXT_TEXT, self::CONTEXT_LINK ],
			'password'         => [ self::CONTEXT_TEXT ],

			// Content
			'image'            => [ self::CONTEXT_TEXT, self::CONTEXT_IMAGE ],
			'gallery'          => [ self::CONTEXT_TEXT, self::CONTEXT_IMAGE ],
			'file'             => [ self::CONTEXT_TEXT, self::CONTEXT_LINK, self::CONTEXT_VIDEO, self::CONTEXT_MEDIA ],
			'wysiwyg'          => [ self::CONTEXT_TEXT ],
			'oembed'           => [ self::CONTEXT_TEXT, self::CONTEXT_LINK, self::CONTEXT_VIDEO, self::CONTEXT_MEDIA ],

			// Choice
			'select'           => [ self::CONTEXT_TEXT ],
			'checkbox'         => [ self::CONTEXT_TEXT ],
			'radio'            => [ self::CONTEXT_TEXT ],
			'button_group'     => [ self::CONTEXT_TEXT ],
			'true_false'       => [ self::CONTEXT_TEXT ],

			// Relational
			'link'             => [ self::CONTEXT_TEXT, self::CONTEXT_LINK ],
			'post_object'      => [ self::CONTEXT_TEXT, self::CONTEXT_LINK ],
			'page_link'        => [ self::CONTEXT_TEXT, self::CONTEXT_LINK ],
			'relationship'     => [ self::CONTEXT_TEXT, self::CONTEXT_LINK, self::CONTEXT_LOOP ],
			'taxonomy'         => [ self::CONTEXT_TEXT, self::CONTEXT_LINK ],
			'user'             => [ self::CONTEXT_TEXT ],

			// jQuery
			'google_map'       => [ self::CONTEXT_TEXT ],
			'date_picker'      => [ self::CONTEXT_TEXT ],
			'date_time_picker' => [ self::CONTEXT_TEXT ],
			'time_picker'      => [ self::CONTEXT_TEXT ],
			'color_picker'     => [ self::CONTEXT_TEXT ],

			'group'            => [ self::CONTEXT_TEXT ],
			'repeater'         => [ self::CONTEXT_LOOP ],
		];

		return $fields;
	}

	/**
	 * Calculate the object ID to be used when fetching the field value
	 *
	 * @param array $field
	 * @param int   $post_id
	 * @return void
	 */
	public function get_object_id( $field, $post_id ) {

		$locations = isset( $field['_bricks_locations'] ) ? $field['_bricks_locations'] : [];

		// This field belongs to a Options page
		if ( in_array( 'option', $locations ) ) {
			return 'option';
		}

		// In a Query Loop
		if ( \Bricks\Query::is_looping() ) {
			$object_type = \Bricks\Query::get_loop_object_type();
			$object_id   = \Bricks\Query::get_loop_object_id();

			// Terms loop
			if ( $object_type == 'term' && in_array( $object_type, $locations ) ) {
				$object = \Bricks\Query::get_loop_object();

				return isset( $object->taxonomy ) ? $object->taxonomy . '_' . $object_id : $post_id;
			}

			// Users loop
			if ( $object_type == 'user' && in_array( $object_type, $locations ) ) {
				return 'user_' . $object_id;
			}
		}

		$queried_object = \Bricks\Helpers::get_queried_object( $post_id );

		if ( in_array( 'term', $locations ) && is_a( $queried_object, 'WP_Term' ) ) {
			if ( isset( $queried_object->taxonomy ) && isset( $queried_object->term_id ) ) {
				return $queried_object->taxonomy . '_' . $queried_object->term_id;
			}
		}

		if ( in_array( 'user', $locations ) ) {
			if ( is_a( $queried_object, 'WP_User' ) && isset( $queried_object->ID ) ) {
				return 'user_' . $queried_object->ID;
			}

			if ( count( $locations ) == 1 ) {
				return 'user_' . get_current_user_id();
			}
		}

		// Default
		return $post_id;
	}

	/**
	 * Set the loop query if exists
	 *
	 * @param array $results
	 * @param Query $query
	 * @return array
	 */
	public function set_loop_query( $results, $query ) {
		if ( ! array_key_exists( $query->object_type, $this->loop_tags ) ) {
			return $results;
		}

		$tag_object = $this->loop_tags[ $query->object_type ];

		$field = $this->loop_tags[ $query->object_type ]['field'];

		$looping_query_id = \Bricks\Query::is_any_looping();

		if ( $looping_query_id ) {

			$loop_query_object_type = \Bricks\Query::get_query_object_type( $looping_query_id );

			// Maybe it is a nested repeater
			if ( array_key_exists( $loop_query_object_type, $this->loop_tags ) ) {

				$loop_object = \Bricks\Query::get_loop_object( $looping_query_id );

				if ( is_array( $loop_object ) && array_key_exists( $field['name'], $loop_object ) ) {
					return $loop_object[ $field['name'] ];
				}
			}

			// Or maybe it is a post loop
			elseif ( $loop_query_object_type === 'post' ) {
				$acf_object_id = get_the_ID();
			}
		}

		if ( ! isset( $acf_object_id ) ) {
			// Get the $post_id or the template preview ID
			$post_id = \Bricks\Database::$page_data['preview_or_post_id'];

			$acf_object_id = $this->get_object_id( $field, $post_id );
		}

		// Check if it is a subfield of a group field (Repeater inside of a Group)
		if ( isset( $tag_object['parent']['type'] ) && $tag_object['parent']['type'] === 'group' ) {
			$post_id = isset( $loop_query_object_type ) && $loop_query_object_type === 'post' ? get_the_ID() : \Bricks\Database::$page_data['preview_or_post_id'];

			$results = $this->get_acf_group_field_value( $tag_object, $post_id );
		} else {
			// @see https://www.advancedcustomfields.com/resources/get_field/
			$results = get_field( $field['key'], $acf_object_id );
		}

		return ! empty( $results ) ? $results : [];
	}

	/**
	 * Manipulate the loop object
	 *
	 * @param array  $loop_object
	 * @param string $loop_key
	 * @param Query  $query
	 * @return array
	 */
	public function set_loop_object( $loop_object, $loop_key, $query ) {
		if ( ! array_key_exists( $query->object_type, $this->loop_tags ) ) {
			return $loop_object;
		}

		// Check if the ACF field is relationship (list of posts)
		$field = $this->loop_tags[ $query->object_type ]['field'];

		if ( $field['type'] == 'relationship' ) {
			global $post;
			$post = get_post( $loop_object );
			setup_postdata( $post );
		}

		return $loop_object;
	}

	public static function flush_cache( $post_id ) {
		wp_cache_set( 'last_changed', microtime(), 'bricks_' . get_post_type( $post_id ) );
	}
}
