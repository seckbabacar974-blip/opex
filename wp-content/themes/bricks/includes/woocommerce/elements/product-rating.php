<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Product_Rating extends Element {
	public $category = 'woocommerce_product';
	public $name     = 'product-rating';
	public $icon     = 'ti-medall';

	public function get_label() {
		return esc_html__( 'Product rating', 'bricks' );
	}

	public function set_controls() {
		$this->controls['noRatingsText'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'No ratings text', 'bricks' ),
			'type'  => 'text',
		];

		$this->controls['starColor'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Star color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'selector' => '.star-rating span::before',
					'property' => 'color',
				],
			],
		];

		$this->controls['emptyStarColor'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Empty star color', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'selector' => '.star-rating::before',
					'property' => 'color',
				],
			],
		];

		// Show Reviews Link
		$this->controls['hideReviewsLink'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Hide reviews link', 'bricks' ),
			'type'  => 'checkbox',
			'css'   => [
				[
					'selector' => '.woocommerce-review-link',
					'property' => 'display',
					'value'    => 'none',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;

		if ( ! wc_review_ratings_enabled() ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'Product ratings are disabled.', 'bricks' ),
				]
			);
		}

		global $product;
		$product = wc_get_product( $this->post_id );

		if ( empty( $product ) ) {
			return $this->render_element_placeholder(
				[
					'title'       => esc_html__( 'For better preview select content to show.', 'bricks' ),
					'description' => esc_html__( 'Go to: Settings > Template Settings > Populate Content', 'bricks' ),
				]
			);
		}

		$rating_count = $product->get_rating_count();

		echo "<div {$this->render_attributes( '_root' )}>";

		if ( $rating_count ) {
			wc_get_template( 'single-product/rating.php' );
		} else {
			if ( ! empty( $settings['noRatingsText'] ) ) {
				echo $settings['noRatingsText'];
			} else {
				$this->render_element_placeholder( [ 'title' => esc_html__( 'No ratings yet.', 'bricks' ) ] );
			}
		}

		echo '</div>';
	}
}
