<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Element_Post_Comments extends Element {
	public $category = 'single';
	public $name     = 'post-comments';
	public $icon     = 'ti-comments';

	public function add_actions() {
		// Remove cookie consent checkbox
		remove_action( 'set_comment_cookies', 'wp_set_comment_cookies' );

		// Hide "logged in as" message in builder
		// if ( bricks_is_builder() ) {
		// add_filter( 'comment_form_logged_in', '__return_empty_string' );
		// }
	}

	public function get_label() {
		return esc_html__( 'Comments', 'bricks' );
	}

	public function enqueue_scripts() {
		// Comments reply: Move form below comment user wants to reply to
		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}

	public function set_control_groups() {
		$this->control_groups['title'] = [
			'title' => esc_html__( 'Title', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['avatar'] = [
			'title' => esc_html__( 'Avatar', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['comment'] = [
			'title' => esc_html__( 'Comment', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['form'] = [
			'title' => esc_html__( 'Form', 'bricks' ),
			'tab'   => 'content',
		];

		$this->control_groups['submitButton'] = [
			'title' => esc_html__( 'Submit button', 'bricks' ),
			'tab'   => 'content',
		];
	}

	public function set_controls() {
		// Group: title

		$this->controls['title'] = [
			'tab'     => 'content',
			'group'   => 'title',
			'label'   => esc_html__( 'Show title', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['titleTypography'] = [
			'tab'      => 'content',
			'group'    => 'title',
			'label'    => esc_html__( 'Typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '.comments-title',
				],
			],
			'required' => [ 'title', '!=', '' ],
		];

		// Group: avatar

		$this->controls['avatar'] = [
			'tab'     => 'content',
			'group'   => 'avatar',
			'label'   => esc_html__( 'Show avatar', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['avatarSize'] = [
			'tab'         => 'content',
			'group'       => 'avatar',
			'label'       => esc_html__( 'Size', 'bricks' ),
			'type'        => 'number',
			'units'       => true,
			'placeholder' => 60,
			'css'         => [
				[
					'property' => 'margin-left',
					'selector' => '.depth-2',
				],
				[
					'property' => 'margin-left',
					'selector' => '.depth-3',
				],
			],
			'rerender'    => true,
		];

		$this->controls['avatarBorder'] = [
			'tab'   => 'content',
			'group' => 'avatar',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.avatar',
				],
			],
		];

		$this->controls['avatarBoxShadow'] = [
			'tab'   => 'content',
			'group' => 'avatar',
			'label' => esc_html__( 'Box shadow', 'bricks' ),
			'type'  => 'box-shadow',
			'css'   => [
				[
					'property' => 'box-shadow',
					'selector' => '.avatar',
				],
			],
		];

		// Group: comment

		$this->controls['commentAuthorTypography'] = [
			'tab'   => 'content',
			'group' => 'comment',
			'label' => esc_html__( 'Author typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.comment-author .fn',
				],
			],
		];

		$this->controls['commentMetaTypography'] = [
			'tab'   => 'content',
			'group' => 'comment',
			'label' => esc_html__( 'Meta typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.comment-meta',
				],
			],
		];

		$this->controls['commentContentTypography'] = [
			'tab'   => 'content',
			'group' => 'comment',
			'label' => esc_html__( 'Content typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.comment-content',
				],
			],
		];

		// Group: form

		$this->controls['formTitle'] = [
			'tab'     => 'content',
			'group'   => 'form',
			'label'   => esc_html__( 'Show form title', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['formTitleText'] = [
			'tab'         => 'content',
			'group'       => 'form',
			'label'       => esc_html__( 'Form title', 'bricks' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => esc_html__( 'Leave your comment', 'bricks' ),
			'required'    => [ 'formTitle', '!=', '' ],
		];

		$this->controls['label'] = [
			'tab'     => 'content',
			'group'   => 'form',
			'label'   => esc_html__( 'Show label', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
		];

		$this->controls['labelTypography'] = [
			'tab'      => 'content',
			'group'    => 'form',
			'label'    => esc_html__( 'Label typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => 'label',
				],
			],
			'required' => [ 'label', '!=', '' ],
		];

		$this->controls['placeholderTypography'] = [
			'tab'      => 'content',
			'group'    => 'form',
			'label'    => esc_html__( 'Placeholder typography', 'bricks' ),
			'type'     => 'typography',
			'css'      => [
				[
					'property' => 'font',
					'selector' => '::placeholder',
				],
			],
			'required' => [ 'label', '=', '' ],
		];

		$this->controls['fieldBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'form',
			'label' => esc_html__( 'Field background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.form-group input',
				],
				[
					'property' => 'background-color',
					'selector' => '.form-group textarea',
				],
			],
		];

		$this->controls['fieldBorder'] = [
			'tab'   => 'content',
			'group' => 'form',
			'label' => esc_html__( 'Field border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.form-group input',
				],
				[
					'property' => 'border',
					'selector' => '.form-group textarea',
				],
			],
		];

		$this->controls['fieldTypography'] = [
			'tab'   => 'content',
			'group' => 'form',
			'label' => esc_html__( 'Field typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.form-group input',
				],
				[
					'property' => 'font',
					'selector' => '.form-group textarea',
				],
			],
		];

		// Group: submitButton

		$this->controls['submitButtonText'] = [
			'tab'            => 'content',
			'group'          => 'submitButton',
			'label'          => esc_html__( 'Text', 'bricks' ),
			'type'           => 'text',
			'inline'         => true,
			'hasDynamicData' => false,
			'placeholder'    => esc_html__( 'Submit Comment', 'bricks' ),
		];

		$this->controls['submitButtonSize'] = [
			'tab'         => 'content',
			'group'       => 'submitButton',
			'label'       => esc_html__( 'Size', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['buttonSizes'],
			'inline'      => true,
			'placeholder' => esc_html__( 'Default', 'bricks' ),
		];

		$this->controls['submitButtonStyle'] = [
			'tab'         => 'content',
			'group'       => 'submitButton',
			'label'       => esc_html__( 'Style', 'bricks' ),
			'type'        => 'select',
			'options'     => $this->control_options['styles'],
			'inline'      => true,
			'placeholder' => esc_html__( 'None', 'bricks' ),
			'default'     => 'primary',
		];

		$this->controls['submitButtonBackgroundColor'] = [
			'tab'   => 'content',
			'group' => 'submitButton',
			'label' => esc_html__( 'Background', 'bricks' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.bricks-button',
				],
			],
		];

		$this->controls['submitButtonBorder'] = [
			'tab'   => 'content',
			'group' => 'submitButton',
			'label' => esc_html__( 'Border', 'bricks' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.bricks-button',
				],
			],
		];

		$this->controls['submitButtonTypography'] = [
			'tab'   => 'content',
			'group' => 'submitButton',
			'label' => esc_html__( 'Typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'font',
					'selector' => '.bricks-button',
				],
			],
		];
	}

	public function render() {
		$settings = $this->settings;

		global $post;

		$post = get_post( $this->post_id );

		if ( post_password_required() ) {
			return $this->render_element_placeholder(
				[
					'icon-class' => 'ti-key',
					'title'      => esc_html__( 'Password required.', 'bricks' ),
				]
			);
		}

		if ( ! comments_open() ) {
			return $this->render_element_placeholder(
				[
					'icon-class' => 'ti-comments',
					'title'      => esc_html__( 'Comments are disabled.', 'bricks' ),
				]
			);
		}

		$title_tag = isset( $settings['titleTag'] ) ? $settings['titleTag'] : 'h3';

		echo "<div {$this->render_attributes( '_root' )}>";
		?>
		<div id="comments">
			<div class="bricks-comments-inner">
				<?php if ( get_comments_number() ) { ?>

					<?php if ( isset( $settings['title'] ) ) { ?>
					<h3 class="comments-title"><?php printf( esc_html( _n( '1 comment', '%1$s comments', get_comments_number(), 'bricks' ) ), number_format_i18n( get_comments_number() ) ); ?></h3>
					<?php } ?>

					<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
					<nav class="navigation comment-navigation" id="comment-nav-above" role="navigation">
						<h2 class="screen-reader-text"><?php esc_html_e( 'Comment navigation', 'bricks' ); ?></h2>
						<div class="nav-links">
							<div class="nav-previous"><?php previous_comments_link( esc_html__( 'Older Comments', 'bricks' ) ); ?></div>
							<div class="nav-next"><?php next_comments_link( esc_html__( 'Newer Comments', 'bricks' ) ); ?></div>
						</div>
					</nav>
					<?php endif; ?>

					<?php
						echo '<ul class="comment-list' . ( ! isset( $settings['avatar'] ) ? ' no-avatar' : '' ) . '">';

						$comments = get_comments(
							[
								'post_id' => get_the_ID(),
								'status'  => 'approve',
							]
						);

						wp_list_comments(
							[
								'walker'            => null,
								'max_depth'         => '10',
								'style'             => 'ul',
								'callback'          => 'bricks_list_comments',
								'end-callback'      => null,
								'type'              => 'comment',
								'reply_text'        => esc_html__( 'Reply', 'bricks' ),
								'page'              => '',
								'per_page'          => '',
								'avatar_size'       => isset( $settings['avatarSize'] ) ? intval( $settings['avatarSize'] ) : 60,
								'reverse_top_level' => null,
								'reverse_children'  => '',
								'format'            => 'html5',
								'short_ping'        => true,
								'echo'              => true,
								// Custom settings
								'bricks_avatar'     => isset( $settings['avatar'] ),
							],
							$comments
						);
					?>
					</ul>

					<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) { ?>
					<nav class="navigation comment-navigation" id="comment-nav-below" role="navigation">
						<h2 class="screen-reader-text"><?php esc_html_e( 'Comment navigation', 'bricks' ); ?></h2>

						<div class="nav-links">
							<div class="nav-previous"><?php previous_comments_link( esc_html__( 'Older Comments', 'bricks' ) ); ?></div>
							<div class="nav-next"><?php next_comments_link( esc_html__( 'Newer Comments', 'bricks' ) ); ?></div>
						</div>
					</nav>
						<?php
					}
				}

				// No comments
				else {
					// esc_html_e( 'No comments.', 'bricks' );
				}

				// Comments are closed
				if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) {
					echo '<p class="no-comments">' . esc_html__( 'Comments are closed.', 'bricks' ) . '</p>';
				}

				$commenter           = wp_get_current_commenter();
				$required_name_email = get_option( 'require_name_email' );
				$required_aria       = ( $required_name_email ? ' aria-required="true"' : '' );
				$required_star       = ( $required_name_email ? ' *' : '' );

				$field_keys = [ 'author', 'email', 'url' ];
				$fields     = [];

				foreach ( $field_keys as $field ) {
					$field_html = '<div class="form-group">';

					switch ( $field ) {
						case 'author':
							if ( isset( $settings['label'] ) ) {
								$field_html .= '<label>' . esc_html__( 'Name', 'bricks' ) . $required_star . '</label>';
								$field_html .= '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '"' . $required_aria . ' />';
							} else {
								$field_html .= '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '"' . $required_aria . ' placeholder="' . esc_html__( 'Name', 'bricks' ) . $required_star . '" />';
							}
							break;

						case 'email':
							if ( isset( $settings['label'] ) ) {
								$field_html .= '<label>' . esc_html__( 'Email', 'bricks' ) . $required_star . '</label>';
								$field_html .= '<input id="email" name="email" type="text" value="' . esc_attr( $commenter['comment_author_email'] ) . '"' . $required_aria . ' />';
							} else {
								$field_html .= '<input id="email" name="email" type="text" value="' . esc_attr( $commenter['comment_author_email'] ) . '"' . $required_aria . ' placeholder="' . esc_html__( 'Email', 'bricks' ) . $required_star . '" />';
							}
							break;

						case 'url':
							if ( isset( $settings['label'] ) ) {
								$field_html .= '<label>' . esc_html__( 'Website', 'bricks' ) . '</label>';
								$field_html .= '<input id="url" name="url" type="text" value="' . esc_attr( $commenter['comment_author_url'] ) . '" />';
							} else {
								$field_html .= '<input id="url" name="url" type="text" value="' . esc_attr( $commenter['comment_author_url'] ) . '" placeholder="' . esc_html__( 'Website', 'bricks' ) . '" />';
							}
							break;
					}

					$field_html .= '</div>';

					$fields[ $field ] = $field_html;
				}

				if ( isset( $settings['label'] ) ) {
					$comment_field =
					'<div class="form-group">
						<label>' . esc_html__( 'Comment', 'bricks' ) . ' *</label>
						<textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea>
					</div>';
				} else {
					$comment_field =
					'<div class="form-group">
						<textarea id="comment" name="comment" cols="45" rows="8" aria-required="true" placeholder="' . esc_html__( 'Comment', 'bricks' ) . ' *"></textarea>
					</div>';
				}

				$submit_text = isset( $settings['submitButtonText'] ) ? $settings['submitButtonText'] : esc_html__( 'Submit Comment', 'bricks' );

				$submit_button_classes = [ 'bricks-button' ];

				if ( isset( $settings['submitButtonStyle'] ) ) {
					$submit_button_classes[] = 'bricks-background-' . $settings['submitButtonStyle'];
				}

				if ( isset( $settings['submitButtonSize'] ) ) {
					$submit_button_classes[] = $settings['submitButtonSize'];
				}

				$custom_args = [
					'title_reply_before'   => '<h4 id="reply-title" class="comment-reply-title">',
					'title_reply_after'    => '</h4>',
					'cancel_reply_link'    => esc_html__( '(Cancel Reply)', 'bricks' ),
					'label_submit'         => $submit_text,
					'comment_notes_before' => '',
					'comment_notes_after'  => '',
					'fields'               => $fields,
					'comment_field'        => $comment_field,
					'class_submit'         => implode( ' ', $submit_button_classes ),
				];

				if ( isset( $settings['formTitle'] ) ) {
					if ( ! empty( $settings['formTitleText'] ) ) {
						$custom_args['title_reply'] = $settings['formTitleText'];
					} else {
						$custom_args['title_reply'] = get_comments_number() ? esc_html__( 'Leave your comment', 'bricks' ) : esc_html__( 'Leave the first comment', 'bricks' );
					}
				} else {
					$custom_args['title_reply'] = '';
				}

				comment_form( $custom_args );
				?>
			</div>
		</div>
		<?php
		echo '</div>';
	}
}
