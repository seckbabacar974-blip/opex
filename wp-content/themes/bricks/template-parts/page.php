<article id="brx-content" <?php post_class( 'wordpress' ); ?>>
	<?php
	the_content();

	wp_link_pages(
		[
			'before'      => '<div class="bricks-pagination"><ul><span class="title">' . esc_html__( 'Pages:', 'bricks' ) . '</span>',
			'after'       => '</ul></div>',
			'link_before' => '<span>',
			'link_after'  => '</span>',
		]
	);
	?>
</article>
