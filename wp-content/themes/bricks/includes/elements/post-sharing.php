<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Post_Sharing extends Element {
	public $category     = 'single';
	public $name         = 'post-sharing';
	public $icon         = 'ti-share';
	public $css_selector = 'a';

	public function get_label() {
		return esc_html__( 'Social Sharing', 'bricks' );
	}

	public function enqueue_scripts() {
		// balloon.css tooltip library
		wp_enqueue_style( 'bricks-tooltips' );
	}

	public function set_controls() {
		$this->controls['_margin']['css'][0]['selector'] = 'li';
		$this->controls['_width']['css'][0]['selector']  = 'a';

		$this->controls['items'] = [
			'tab'           => 'content',
			'label'         => esc_html__( 'Share via', 'bricks' ),
			'titleProperty' => 'service',
			'type'          => 'repeater',
			'fields'        => [
				'service' => [
					'label'     => esc_html__( 'Service', 'bricks' ),
					'type'      => 'select',
					'clearable' => false,
					'options'   => [
						'facebook'  => 'Facebook',
						'twitter'   => 'Twitter',
						'linkedin'  => 'LinkedIn',
						'whatsapp'  => 'WhatsApp',
						'pinterest' => 'Pinterest',
						'telegram'  => 'Telegram',
						'vkontakte' => 'VKontakte',
						'email'     => esc_html__( 'Email', 'bricks' ),
					],
				],
			],
			'default'       => [
				[ 'service' => 'facebook' ],
				[ 'service' => 'twitter' ],
				[ 'service' => 'linkedin' ],
				[ 'service' => 'whatsapp' ],
				[ 'service' => 'pinterest' ],
				[ 'service' => 'telegram' ],
				[ 'service' => 'vkontakte' ],
				[ 'service' => 'email' ],
			],
		];

		$this->controls['brandColors'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Use brand colors', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['direction'] = [
			'tab'    => 'content',
			'label'  => esc_html__( 'Direction', 'bricks' ),
			'type'   => 'direction',
			'css'    => [
				[
					'property' => 'flex-direction',
					'selector' => '',
				],
			],
			'inline' => true,
		];

		// LINKS

		$this->controls['linksSeparator'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Links', 'bricks' ),
			'type'  => 'separator',
		];

		$this->controls['newTab'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Open in new tab', 'bricks' ),
			'type'  => 'checkbox',
		];

		$this->controls['linkRel'] = [
			'tab'            => 'content',
			'label'          => esc_html__( 'Rel attribute', 'bricks' ),
			'type'           => 'text',
			'inline'         => true,
			'hasDynamicData' => false,
			'placeholder'    => 'nofollow',
		];
	}

	public function render() {
		$settings = $this->settings;
		$items    = ! empty( $settings['items'] ) ? $settings['items'] : false;

		if ( ! $items ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'No sharing option selected.', 'bricks' ),
				]
			);
		}

		global $post;

		$post = get_post( $this->post_id );

		$url   = rawurlencode( html_entity_decode( get_the_permalink(), ENT_COMPAT, 'UTF-8' ) );
		$image = rawurlencode( html_entity_decode( wp_get_attachment_url( get_post_thumbnail_id() ), ENT_COMPAT, 'UTF-8' ) );
		$title = rawurlencode( html_entity_decode( get_the_title(), ENT_COMPAT, 'UTF-8' ) );

		// Ignore in builder MutationObserver
		if ( isset( $settings['brandColors'] ) ) {
			$this->set_attribute( '_root', 'class', 'brand-colors' );
		}

		// Link rel attribute (since 1.5)
		$rel_attribute = ! empty( $settings['linkRel'] ) ? trim( $settings['linkRel'] ) : 'nofollow';

		echo "<ul {$this->render_attributes( '_root' )}>";

		foreach ( $items as $index => $item ) {
			if ( empty( $item['service'] ) ) {
				continue;
			}

			$data = false;

			switch ( $item['service'] ) {
				case 'facebook':
					$data = [
						'url'     => wp_is_mobile() ? 'https://m.facebook.com/sharer.php?u=' . $url : "http://www.facebook.com/sharer.php?u=$url&amp;picture=$image&amp;title=$title",
						'tooltip' => sprintf( esc_html__( 'Share on %s', 'bricks' ), 'Facebook' ),
						'class'   => 'facebook',
						'icon'    => Helpers::get_file_contents( BRICKS_URL_ASSETS . 'svg/frontend/facebook.svg' ),
					];
					break;

				case 'twitter':
					$data = [
						'url'     => "https://twitter.com/share?text=$title&amp;url=$url",
						'tooltip' => sprintf( esc_html__( 'Share on %s', 'bricks' ), 'Twitter' ),
						'class'   => 'twitter',
						'icon'    => Helpers::get_file_contents( BRICKS_URL_ASSETS . 'svg/frontend/twitter.svg' ),
					];
					break;

				case 'linkedin':
					$data = [
						'url'     => "https://www.linkedin.com/shareArticle?mini=true&amp;url=$url&amp;title=$title",
						'tooltip' => sprintf( esc_html__( 'Share on %s', 'bricks' ), 'LinkedIn' ),
						'class'   => 'linkedin',
						'icon'    => Helpers::get_file_contents( BRICKS_URL_ASSETS . 'svg/frontend/linkedin.svg' ),
					];
					break;

				case 'whatsapp':
					$text = get_the_excerpt( $post );

					$data = [
						'url'     => "https://api.whatsapp.com/send?text=*{$title}*%0A{$text}%0A{$url}",
						'tooltip' => sprintf( esc_html__( 'Share on %s', 'bricks' ), 'WhatsApp' ),
						'class'   => 'whatsapp',
						'icon'    => Helpers::get_file_contents( BRICKS_URL_ASSETS . 'svg/frontend/whatsapp.svg' ),
					];
					break;

				case 'pinterest':
					$data = [
						'url'     => "http://pinterest.com/pin/create/button/?url=$url&amp;media=$image",
						'tooltip' => sprintf( esc_html__( 'Share on %s', 'bricks' ), 'Pinterest' ),
						'class'   => 'pinterest',
						'icon'    => Helpers::get_file_contents( BRICKS_URL_ASSETS . 'svg/frontend/pinterest.svg' ),
					];
					break;

				case 'telegram':
					$data = [
						'url'     => "https://t.me/share/url?url={$url}&text={$title}",
						'tooltip' => sprintf( esc_html__( 'Share on %s', 'bricks' ), 'Telegram' ),
						'class'   => 'telegram',
						'icon'    => Helpers::get_file_contents( BRICKS_URL_ASSETS . 'svg/frontend/telegram.svg' ),
					];
					break;

				case 'vkontakte':
					$data = [
						'url'     => "https://vk.com/share.php?url={$url}&title={$title}&image=$image",
						'tooltip' => sprintf( esc_html__( 'Share on %s', 'bricks' ), 'VKontakte' ),
						'class'   => 'vkontakte',
						'icon'    => Helpers::get_file_contents( BRICKS_URL_ASSETS . 'svg/frontend/vkontakte.svg' ),
					];
					break;

				case 'email':
					$data = [
						'url'     => "mailto:?subject=$title&amp;body=$url",
						'tooltip' => esc_html__( 'Share via email', 'bricks' ),
						'class'   => 'email',
						'icon'    => Helpers::get_file_contents( BRICKS_URL_ASSETS . 'svg/frontend/email.svg' ),
					];
					break;
			}

			if ( $data ) {
				echo "<li data-balloon=\"{$data['tooltip']}\" data-balloon-pos=\"top\">";

				$this->set_attribute( "link-{$index}", 'class', $data['class'] );
				$this->set_attribute( "link-{$index}", 'href', $data['url'] );
				$this->set_attribute( "link-{$index}", 'rel', $rel_attribute );

				if ( isset( $settings['newTab'] ) ) {
					$this->set_attribute( "link-{$index}", 'target', '_blank' );
				}

				echo "<a {$this->render_attributes( "link-{$index}" )}>{$data['icon']}</a>";

				echo '</li>';
			}
		}

		echo '</ul>';
	}
}
