<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Compatibility {
	public function __construct() {}

	public static function register() {
		$instance = new self();

		add_action( 'litespeed_init', [ $instance, 'litespeed_no_cache' ] );

		// Polylang
		if ( function_exists( 'pll_the_languages' ) ) {
			add_filter( 'bricks/helpers/get_posts_args', [ $instance, 'polylang_get_posts_args' ] );
			add_filter( 'bricks/ajax/get_pages_args', [ $instance, 'polylang_get_posts_args' ] );
		}
	}

	/**
	 * LiteSpeed Cache plugin: Ignore Bricks builder
	 *
	 * Tested with version 3.6.4
	 *
	 * @return void
	 */
	public function litespeed_no_cache() {
		if ( isset( $_GET['bricks'] ) && $_GET['bricks'] === 'run' ) {
			do_action( 'litespeed_disable_all', 'bricks editor' );
		}
	}

	/**
	 * Polylang - set the query arg to get all the posts/pages languages
	 *
	 * @param array $query_args
	 * @return array
	 */
	public function polylang_get_posts_args( $query_args ) {

		if ( ! isset( $query_args['lang'] ) ) {
			$query_args['lang'] = 'all';
		}

		return $query_args;
	}
}
