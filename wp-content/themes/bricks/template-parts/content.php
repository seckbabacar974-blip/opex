<?php
ob_start();
?>
<div class="brxe-container">
	<?php
	if ( have_posts() ) {
		global $wp_query;

		$post_type = is_home() ? 'post' : 'any';

		// Default search results page: Exclude Bricks templates
		if ( is_search() ) {
			$searchable_post_types = get_post_types( [ 'exclude_from_search' => false ] );

			if ( is_array( $searchable_post_types ) && in_array( BRICKS_DB_TEMPLATE_SLUG, $searchable_post_types ) ) {
				unset( $searchable_post_types[ BRICKS_DB_TEMPLATE_SLUG ] );
			}

			$post_type = $searchable_post_types;
		}

		$query_vars              = $wp_query->query_vars;
		$query_vars['post_type'] = $post_type;

		$current_page = $query_vars['paged'];

		$post_content = new Bricks\Element_Posts(
			[
				'settings' => [
					'query'           => $query_vars,
					'layout'          => 'grid',
					'columns'         => 2,
					'gutter'          => 30,
					'imageLink'       => true,
					'fields'          => [
						[
							'dynamicData' => '{post_title:link}',
							'tag'         => 'h3',
						],
						[
							'dynamicData' => '{post_date}',
						],
						[
							'dynamicData' => '{post_excerpt:20}',
						],
					],
					'postsNavigation' => true,
				]
			]
		);

		$post_content->load();
		$post_content->init();
	}

	// No posts
	else {
		$no_posts_html = '<div class="bricks-no-posts-wrapper">';

		$no_posts_html .= '<h3 class="title">' . esc_html__( 'Nothing found.', 'bricks' ) . '</h3>';

		if ( current_user_can( 'publish_posts' ) ) {
			$no_posts_html .= '<p>';
			$no_posts_html .= esc_html__( 'Ready to publish your first post?', 'bricks' );
			$no_posts_html .= ' <a href="' . admin_url( 'post-new.php' ) . '">' . esc_html__( 'Get started here', 'bricks' ) . '</a>.';
			$no_posts_html .= '</p>';
		}

		$no_posts_html .= '</div>';

		echo $no_posts_html;
	}
	?>
</div>
<?php
$attributes = [ 'class' => 'layout-default' ];

$html = ob_get_clean();

Bricks\Frontend::render_content( [], $attributes, $html );
