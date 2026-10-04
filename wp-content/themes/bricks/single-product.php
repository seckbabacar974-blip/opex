<?php
/*
 * This overrides the default WooCommerce file
 * @version     1.6.4
 */

get_header();

if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();

		$bricks_data = Bricks\Helpers::get_bricks_data( get_the_ID(), 'content' );

		// Bricks data
		if ( $bricks_data ) {
			$attributes['class'] = [ 'product' ];

			$html_after_begin = '<div class="woocommerce-notices-wrapper brxe-container">' . wc_print_notices( true ) . '</div>';

			Bricks\Frontend::render_content( $bricks_data, $attributes, $html_after_begin );
		}

		// Default WooCommerce single product template
		elseif ( function_exists( 'wc_get_template_part' ) ) {
			do_action( 'woocommerce_before_main_content' );

			wc_get_template_part( 'content', 'single-product' );

			do_action( 'woocommerce_after_main_content' );
		}

		// Default content
		else {
			the_content();
		}
	}
}

get_footer();
