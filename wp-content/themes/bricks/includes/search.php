<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Search {
	public function __construct() {
		if ( Database::get_setting( 'searchResultsQueryBricksData', false ) ) {
			add_filter( 'posts_join', [ $this, 'search_postmeta_table' ] );
			add_filter( 'posts_where', [ $this, 'modify_search_for_postmeta' ] );
			add_filter( 'posts_distinct', [ $this, 'search_distinct' ] );
		}
	}

	/**
	 * Search 'posts' and 'postmeta' tables
	 *
	 * https://adambalee.com/search-wordpress-by-custom-fields-without-a-plugin/
	 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_join
	 *
	 * @since 1.3.7
	 */
	public function search_postmeta_table( $join ) {
		global $wpdb;

		if ( is_search() ) {
			$join .= ' LEFT JOIN ' . $wpdb->postmeta . ' bricksdata ON ' . $wpdb->posts . '.ID = bricksdata.post_id ';
		}

		return $join;
	}

	/**
	 * Modify search query
	 *
	 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
	 *
	 * @since 1.3.7
	 */
	public function modify_search_for_postmeta( $where ) {
		global $pagenow, $wpdb;

		if ( is_search() ) {
			$where = preg_replace(
				'/\(\s*' . $wpdb->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
				'(' . $wpdb->posts . '.post_title LIKE $1) OR (bricksdata.meta_value LIKE $1)',
				$where
			);
		}

		return $where;
	}

	/**
	 * Prevent duplicates
	 *
	 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_distinct
	 *
	 * @since 1.3.7
	 */
	public function search_distinct( $where ) {
		global $wpdb;

		if ( is_search() ) {
			return 'DISTINCT';
		}

		return $where;
	}
}
