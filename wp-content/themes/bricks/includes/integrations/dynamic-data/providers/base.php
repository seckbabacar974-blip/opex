<?php
namespace Bricks\Integrations\Dynamic_Data\Providers;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

abstract class Base implements Provider_Interface {

	/**
	 * Provider name
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Holds the Tags instances for this provider's tags
	 *
	 * @var array
	 */
	public $tags = [];

	/**
	 * Holds the tags to be used in the Query Loop control (e.g. Repeater, Relationship...)
	 *
	 * @var array
	 */
	public $loop_tags = [];

	/**
	 * The Contructor
	 *
	 * @param string $name Provider Name
	 */
	public function __construct( $name ) {
		$this->name = $name;

		// Add the supported fields to the Query type control
		add_filter( 'bricks/setup/control_options', [ $this, 'add_control_options' ], 10, 1 );

		// Calculate and set the query results
		add_filter( 'bricks/query/run', [ $this, 'set_loop_query' ], 10, 2 );

		// Manage the loop object during loop iteration
		add_filter( 'bricks/query/loop_object', [ $this, 'set_loop_object' ], 10, 3 );
	}

	/**
	 * Useful to the check if the provider should be loaded or not.
	 *
	 * @return boolean
	 */
	public static function load_me() {
		return true;
	}

	/**
	 * This method loads the tags of this provider
	 *
	 * @return void
	 */
	public function register_tags() {}


	/**
	 * Getter for the list of tags instance of this provider
	 *
	 * @return array
	 */
	public function get_tags() {
		if ( empty( $this->tags ) ) {
			$this->register_tags();
		}
		return $this->tags;
	}

	/**
	 * Get the tag value based on the context
	 *
	 * @param string  $tag
	 * @param WP_Post $post
	 * @param array   $args
	 * @param string  $context text, link, image, media
	 * @return array|string
	 */
	public function get_tag_value( $tag, $post, $args, $context ) {
		return '';
	}

	/**
	 * Calculate dynamic data filters according to the args received
	 *
	 * @param array $args
	 * @return array
	 */
	public function get_filters_from_args( $args ) {

		$filters = [
			'object_type' => '',
		];

		if ( empty( $args ) || ! is_array( $args ) ) {
			return $filters;
		}

		foreach ( $args as $arg ) {
			// Trim number of words or avatar size (in px)
			if ( is_numeric( $arg ) ) {
				$filters['num_words'] = $arg;
			}

			// Add context to the archive title
			elseif ( $arg == 'context' || $arg == 'prefix' ) {
				$filters['add_context'] = true;
			}

			// Output as image tag
			elseif ( $arg == 'image' ) {
				$filters['image'] = true;
			}

			// Wrap value in a link
			elseif ( $arg == 'link' ) {
				$filters['link'] = true;
			}

			// Open link in newTab
			elseif ( $arg == 'newTab' ) {
				$filters['newTab'] = true;
			}

			// Create a callable link
			elseif ( $arg == 'tel' ) {
				$filters['tel'] = true;
			}

			// Default key: used for 1) user meta_key, 2) post terms separator or 3) image size, 4) date format
			else {
				$filters['meta_key'][] = $arg;
			}
		}

		// Note: Use case where the date format contains a colon. E.g. "{post_date:jS F Y h:ia}"
		if ( isset( $filters['meta_key'] ) ) {
			$filters['meta_key'] = implode( ':', $filters['meta_key'] );
		}

		return $filters;
	}

	/**
	 * Format the dynamic data value according to the context
	 *
	 * @param string|integer $value
	 * @param string         $tag
	 * @param integer        $post_id
	 * @param array          $filters
	 * @param string         $context
	 * @return string|array
	 */
	public function format_value_for_context( $value, $tag, $post_id, $filters, $context = 'text' ) {
		switch ( $context ) {
			case 'text':
				$value = is_array( $value ) ? $value : (array) $value;

				foreach ( $value as $key => $item ) {
					$value[ $key ] = $this->format_value_for_text( $item, $tag, $post_id, $filters );
				}

				$sep = isset( $filters['separator'] ) ? $filters['separator'] : ', ';

				// NOTE: Undocumented.
				$sep = apply_filters( 'bricks/dynamic_data/text_separator', $sep, $tag, $post_id, $filters );

				$value = implode( $sep, $value );

				// Sanitize
				if ( empty( $filters['skip_sanitize'] ) ) {

					add_filter( 'wp_kses_allowed_html', [ $this, 'expand_allowed_html' ], 10, 2 );

					$value = wp_kses_post( $value );

					remove_filter( 'wp_kses_allowed_html', [ $this, 'expand_allowed_html' ], 10, 2 );
				}
				break;

			case 'link':
				// This is a single link field. If field returns multiple, choose the first one.
				if ( is_array( $value ) ) {
					$value = current( $value );
				}

				// Retrieve the image URL
				if ( ! empty( $filters['image'] ) || $tag === 'featured_image' || $tag === 'featured_image_tag' ) {
					$image_size = isset( $filters['meta_key'] ) ? $filters['meta_key'] : 'full';

					$value = wp_get_attachment_image_url( $value, $image_size );
				} elseif ( 'media' === $filters['object_type'] ) {
					$value = wp_get_attachment_url( $value );
				}

				// Link to email
				elseif ( is_email( $value ) ) {
					$value = 'mailto:' . trim( $value );
				}

				// Create a callable link
				elseif ( ! empty( $filters['tel'] ) ) {
					$value = 'tel:' . trim( $value );
				}

				// Link to author / user archive page
				elseif ( strpos( $value, 'http' ) !== 0 && ( strpos( $tag, 'author_' ) === 0 || strpos( $tag, 'wp_user_' ) === 0 ) ) {

					if ( $tag !== 'author_avatar' && $tag !== 'wp_user_picture' ) {
						$user_id = strpos( $tag, 'wp_user_' ) === 0 ? get_current_user_id() : ( isset( $post->post_author ) ? $post->post_author : 0 );

						$value = get_author_posts_url( $user_id );
					}
				} elseif ( 'post' === $filters['object_type'] ) {
					$value = get_permalink( $value );
				} elseif ( 'user' === $filters['object_type'] ) {
					$value = get_author_posts_url( $value );
				} elseif ( 'term' === $filters['object_type'] ) {
					$taxonomy = isset( $filters['taxonomy'] ) ? $filters['taxonomy'] : '';
					$value    = get_term_link( (int) $value, $taxonomy );
				}

				break;

			case 'image':
				$value = array_filter( (array) $value );
				$value = is_array( $value ) ? $value : [ $value ];
				break;

			case 'media':
				$value = is_array( $value ) ? $value : (array) $value;

				foreach ( $value as $key => $media_id ) {
					if ( is_numeric( $media_id ) ) {
						$value[ $key ] = [
							'id'  => $media_id,
							'url' => wp_get_attachment_url( $media_id )
						];
					} else {
						$value[ $key ] = [
							'url' => $media_id
						];
					}
				}
				break;
		}

		// NOTE: Undocumented
		$value = apply_filters( 'bricks/dynamic_data/format_value', $value, $tag, $post_id, $filters, $context );

		return $value;
	}

	/**
	 * Format value for the text context
	 *
	 * @param string|integer $value
	 * @param string         $tag
	 * @param WP_Post        $post
	 * @param array          $filters
	 * @return string
	 */
	public function format_value_for_text( $value, $tag, $post_id, $filters ) {
		$object = false;

		if ( empty( $filters['object_type'] ) ) {
			$object = false;
		} elseif ( 'term' == $filters['object_type'] ) {
			if ( ! empty( $filters['object'] ) ) {
				$object = $filters['object'];
			} elseif ( isset( $filters['taxonomy'] ) ) {
				$object = get_term_by( 'id', $value, $filters['taxonomy'] );
				$value  = is_a( $object, 'WP_Term' ) ? $object->name : $value;
			}
		} elseif ( 'user' == $filters['object_type'] ) {
			$object = get_user_by( 'id', $value );
			$value  = $object ? $object->display_name : $value;
		} elseif ( 'post' == $filters['object_type'] || 'media' == $filters['object_type'] ) {
			$object = get_post( $value );
			$value  = get_the_title( $value );
		}

		// Trim number of words
		if ( ! empty( $filters['num_words'] ) && ! in_array( $tag, [ 'author_avatar', 'wp_user_picture' ] ) ) {
			$value = wp_trim_words( $value, $filters['num_words'], '' );
		}

		// Transform image into a tag
		if ( ! empty( $filters['image'] ) ) {
			$image_size = ! empty( $filters['meta_key'] ) ? $filters['meta_key'] : 'thumbnail';

			$value = ! empty( $object->ID ) ? wp_get_attachment_image( $object->ID, $image_size, false, [] ) : '';
		}

		if ( in_array( $filters['object_type'], [ 'date', 'datetime' ] ) ) {
			if ( isset( $filters['meta_key'] ) ) {
				if ( $filters['meta_key'] == 'human_time_diff' ) {
					$value = human_time_diff( $value );
				} else {
					$date_format = $filters['meta_key'];
				}
			} else {
				$date_format = 'datetime' == $filters['object_type'] ? get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) : get_option( 'date_format' );
			}

			$value = isset( $date_format ) ? date_i18n( $date_format, $value ) : $value;

			// Apply core hooks so plugins could tweak date and time, if date_format is not provided in the filters
			if ( ! isset( $filters['meta_key'] ) && in_array( $tag, [ 'post_date', 'post_modified' ] ) ) {
				$value = apply_filters( 'get_the_date', $value, $date_format, get_post( $post_id ) );
			} elseif ( ! isset( $filters['meta_key'] ) && in_array( $tag, [ 'post_time' ] ) ) {
				$value = apply_filters( 'get_the_time', $value, $date_format, get_post( $post_id ) );
			}
		}

		// Render html anchor
		if ( ! empty( $filters['link'] ) ) {
			// New tab (target="_blank")
			$target = ! empty( $filters['newTab'] ) ? ' target="_blank"' : '';

			// Email link
			if ( is_email( $value ) ) {
				$aria_label = sprintf( __( 'Send email to %s', 'bricks' ), $value );

				$value = '<a href="mailto:' . trim( $value ) . '" aria-label="' . esc_attr( $aria_label ) . '">' . esc_html( $value ) . '</a>';
			}

			// Website url
			elseif ( strpos( $value, 'http' ) === 0 ) {
				$aria_label = sprintf( __( 'Visit the website %s', 'bricks' ), $value );

				$value = '<a href="' . esc_url( $value ) . '" aria-label="' . esc_attr( $aria_label ) . '"' . $target . '>' . esc_html( $value ) . '</a>';
			}

			// User/author link
			elseif ( strpos( $tag, 'author_' ) === 0 || strpos( $tag, 'wp_user_' ) === 0 || $filters['object_type'] === 'user' ) {
				if ( strpos( $tag, 'wp_user_' ) === 0 ) {
					$user_id = get_current_user_id();
				} elseif ( strpos( $tag, 'author_' ) === 0 ) {
					$post    = get_post( $post_id );
					$user_id = isset( $post->post_author ) ? $post->post_author : 0;
				} elseif ( $filters['object_type'] === 'user' ) {
					$user_id = isset( $object->ID ) ? $object->ID : 0;
				}

				$aria_label = sprintf( __( 'Read more about %s', 'bricks' ), get_the_author_meta( 'display_name', $user_id ) );

				$label = in_array( $tag, [ 'wp_user_picture', 'author_avatar' ] ) ? wp_kses_post( $value ) : esc_html( $value );

				$value = '<a href="' . esc_url( get_author_posts_url( $user_id ) ) . '" aria-label="' . esc_attr( $aria_label ) . '"' . $target . '>' . $label . '</a>';
			}

			// Link to an image or attachment
			elseif ( $object && $filters['object_type'] === 'media' ) {
				$filename = get_the_title( $object->ID );

				$aria_label = sprintf( __( 'Download %s', 'bricks' ), $filename );

				$url = in_array( $tag, [ 'featured_image', 'featured_image_tag' ] ) ? get_permalink( $post_id ) : wp_get_attachment_url( $object->ID );

				$value = '<a href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $aria_label ) . '"' . $target . '>' . $value . '</a>';
			}

			// Object is a WP_Term
			elseif ( $filters['object_type'] === 'term' ) {
				$link = is_a( $object, 'WP_Term' ) ? get_term_link( $object ) : '';

				$value = '<a href="' . esc_url( $link ) . '" rel="tag"' . $target . '>' . $value . '</a>';
			}

			// {post_title:link} or {read_more} or 'post' == $filters['object_type']
			else {
				$post_id = $filters['object_type'] === 'post' ? $object->ID : $post_id;

				$aria_label = sprintf( __( 'Read more about %s', 'bricks' ), get_the_title( $post_id ) );

				$value = '<a href="' . get_permalink( $post_id ) . '" aria-label="' . esc_attr( $aria_label ) . '"' . $target . '>' . $value . '</a>';
			}
		}

		return $value;
	}

	/**
	 * Expand the wp_kses_post sanitization function to allow iframe HTML tags
	 *
	 * @param array  $tags
	 * @param string $context
	 * @return array
	 */
	public function expand_allowed_html( $tags, $context ) {
		if ( ! isset( $tags['iframe'] ) ) {
			$tags['iframe'] = [
				'src'             => true,
				'height'          => true,
				'width'           => true,
				'frameborder'     => true,
				'allowfullscreen' => true,
			];
		}

		return $tags;
	}

	/**
	 * Adds the loop fields to the Query Loop builder
	 *
	 * @param array $control_options
	 * @return array
	 */
	public function add_control_options( $control_options ) {

		if ( empty( $this->loop_tags ) ) {
			return $control_options;
		}

		foreach ( $this->loop_tags as $name => $tag ) {
			$control_options['queryTypes'][ $name ] = $tag['label'];
		}

		return $control_options;
	}

	/**
	 * Should be overridden by the provider if needed
	 *
	 * @param array $results
	 * @param Query $query
	 * @return array
	 */
	public function set_loop_query( $results, $query ) {
		return $results;
	}

	/**
	 * Should be overridden by the provider if needed
	 *
	 * @param array  $loop_object
	 * @param string $loop_key
	 * @param Query  $query
	 * @return array
	 */
	public function set_loop_object( $loop_object, $loop_key, $query ) {
		return $loop_object;
	}

}
