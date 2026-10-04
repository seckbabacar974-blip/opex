<?php
namespace Bricks\Integrations\Form;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Init {
	protected $uploaded_files;
	protected $form_settings;
	protected $form_fields;

	protected $results;

	public function __construct() {
		add_action( 'wp_ajax_bricks_form_submit', [ $this, 'form_submit' ] );
		add_action( 'wp_ajax_nopriv_bricks_form_submit', [ $this, 'form_submit' ] );
	}

	/**
	 * Element Form: Submit
	 *
	 * @since 1.0
	 */
	public function form_submit() {
		\Bricks\Ajax::verify_request();

		$this->form_settings = \Bricks\Helpers::get_element_settings( $_POST['postId'], $_POST['formId'] );

		if ( ! isset( $this->form_settings['actions'] ) || empty( $this->form_settings['actions'] ) ) {
			wp_send_json_error(
				[
					'code'    => 400,
					'action'  => '',
					'type'    => 'danger',
					'message' => esc_html__( 'No action has been set for this form.', 'bricks' ),
				]
			);
		}

		// Google ReCAPTCHA v3 (invisible)
		if ( isset( $this->form_settings['enableRecaptcha'] ) ) {
			$recaptcha_verified = false;

			$recaptcha_secret_key = \Bricks\Database::get_setting( 'apiSecretKeyGoogleRecaptcha', false );

			if ( ! empty( $_POST['recaptchaToken'] ) && $recaptcha_secret_key ) {
				// Verify token @see https://developers.google.com/recaptcha/docs/verify
				$recaptcha = \Bricks\Helpers::get_file_contents( 'https://www.google.com/recaptcha/api/siteverify?secret=' . $recaptcha_secret_key . '&response=' . $_POST['recaptchaToken'] );
				$recaptcha = json_decode( $recaptcha );

				// Google reCAPTCHA v3 returns a score (1.0 is very likely a good interaction, 0.0 is very likely a bot)
				// https://academy.bricksbuilder.io/article/form-element/#spam
				$score = apply_filters( 'bricks/form/recaptcha_score_threshold', 0.5 );

				// Action was set on the grecaptcha.execute (@see frontend.js)
				if ( $recaptcha->success && $recaptcha->score >= $score && $recaptcha->action == 'bricks_form_submit' ) {
					$recaptcha_verified = true;
				}
			}

			if ( ! $recaptcha_verified ) {
				$error = esc_html__( 'Invalid Google reCaptcha.', 'bricks' );

				if ( ! empty( $recaptcha->{'error-codes'} ) ) {
					$error .= ' [' . implode( ',', $recaptcha->{'error-codes'} ) . ']';
				}

				wp_send_json_error(
					[
						'code'    => 400,
						'action'  => '',
						'type'    => 'danger',
						'message' => $error,
					]
				);
			}
		}

		$this->form_fields = stripslashes_deep( $_POST );

		$this->uploaded_files = $this->handle_files();

		$available_actions = self::get_available_actions();

		// Run selected form submit 'actions'
		foreach ( $this->form_settings['actions'] as $form_action ) {
			if ( ! array_key_exists( $form_action, $available_actions ) ) {
				continue;
			}

			$action_class = 'Bricks\Integrations\Form\Actions\\' . str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $form_action ) ) );

			$action = new $action_class( $form_action );

			if ( ! method_exists( $action_class, 'run' ) ) {
				continue;
			}

			$action->run( $this );

			// Halts execution if some action reported one error
			$this->maybe_stop_processing();
		}

		// All fine, success
		$this->finish();
	}

	/**
	 * If there are any errors, stop execution
	 *
	 * @return void
	 */
	private function maybe_stop_processing() {
		if ( empty( $this->results['danger'] ) ) {
			return;
		}

		// Get last error
		$error = array_pop( $this->results['danger'] );

		// Remove uploaded files, if exist
		$this->remove_files();

		// Leave
		wp_send_json_error( $error );
	}


	private function finish() {
		$form_settings = $this->form_settings;

		// Remove uploaded files, if exist
		$this->remove_files();

		// Basic response
		$response = [
			'type'    => 'success',
			'message' => isset( $form_settings['successMessage'] ) ? $this->render_data( $form_settings['successMessage'] ) : esc_html__( 'Success', 'bricks' )
		];

		if ( empty( $this->results ) ) {
			wp_send_json_success( $response );
		}

		// Check for redirects
		if ( ! empty( $this->results['redirect'] ) ) {
			$redirect                    = array_pop( $this->results['redirect'] );
			$response['redirectTo']      = $redirect['redirectTo'];
			$response['redirectTimeout'] = $redirect['redirectTimeout'];
		}

		// Check for info messages (e.g. Mailchimp pending message)
		if ( ! empty( $this->results['info'] ) ) {
			foreach ( $this->results['info'] as $info ) {
				if ( ! isset( $info['message'] ) ) {
					continue;
				}
				$response['info'][] = $info['message'];
			}
		}

		// NOTE: Undocumented
		$response = apply_filters( 'bricks/form/response', $response, $this );

		// Evaluate results
		wp_send_json_success( $response );
	}

	/**
	 * Set action result
	 *
	 * @param array $result
	 * @return void
	 */
	public function set_result( $result ) {
		$type                     = isset( $result['type'] ) ? $result['type'] : 'success';
		$this->results[ $type ][] = $result;
	}

	/**
	 * Getters
	 */
	public function get_settings() {
		return $this->form_settings;
	}

	public function get_fields() {
		return $this->form_fields;
	}

	public function get_uploaded_files() {
		return $this->uploaded_files;
	}

	public function get_results() {
		return $this->results;
	}


	/**
	 * Handle with any files uploaded with form
	 *
	 * @param string $action
	 * @return void
	 */
	public function handle_files() {
		if ( empty( $_FILES ) ) {
			return [];
		}

		// https://developer.wordpress.org/reference/functions/wp_handle_upload/
		$overrides = [ 'action' => 'bricks_form_submit' ];

		$uploaded_files = [];

		// Each form may have more than one input file type, each may have multiple files
		foreach ( $_FILES as $input_name => $files ) {
			if ( empty( $files['name'] ) ) {
				continue;
			}
			foreach ( $files['name'] as $key => $value ) {

				if ( empty( $files['name'][ $key ] ) || $files['error'][ $key ] !== UPLOAD_ERR_OK ) {
					continue;
				}

				$file = [
					'name'     => $files['name'][ $key ],
					'type'     => $files['type'][ $key ],
					'tmp_name' => $files['tmp_name'][ $key ],
					'error'    => $files['error'][ $key ],
					'size'     => $files['size'][ $key ]
				];

				$uploaded = wp_handle_upload( $file, $overrides );

				// Upload success (uploaded to 'wp-content/uploads' folder)
				if ( $uploaded && ! isset( $uploaded['error'] ) ) {
					$uploaded_files[ $input_name ][] = $uploaded;
				}

				// TODO: Handle file errors, should we notify user?
			}
		}

		return $uploaded_files;
	}

	/**
	 * Eventually remove uploaded files
	 *
	 * @return void
	 */
	public function remove_files() {
		if ( empty( $this->uploaded_files ) ) {
			return;
		}

		// Remove uploaded files
		foreach ( $this->uploaded_files as $input_name => $files ) {
			foreach ( $files as $file ) {
				@unlink( $file['file'] );
			}
		}
	}

	/**
	 * Replace any {{field_id}} by the submitted form field content and after renders dynamic data
	 *
	 * @param string $content
	 * @return void
	 */
	public function render_data( $content ) {
		// \w: Matches any word character (alphanumeric & underscore).
		// Only matches low-ascii characters (no accented or non-roman characters).
		// Equivalent to [A-Za-z0-9_]
		// https://regexr.com/
		preg_match_all( '/{{(\w+)}}/', $content, $matches );

		if ( ! empty( $matches[0] ) ) {
			foreach ( $matches[1] as $key => $field_id ) {
				// Format: '{{zjkcdw}}' // Dyamic email data format
				$tag = $matches[0][ $key ];

				$value = $this->get_field_content( $field_id );

				$value = ! empty( $value ) && is_array( $value ) ? implode( ', ', $value ) : $value;

				$content = str_replace( $tag, $value, $content );
			}
		}

		$fields  = $this->get_fields();
		$post_id = isset( $fields['postId'] ) ? $fields['postId'] : 0;

		// Render Dynamic Data
		$content = \Bricks\Integrations\Dynamic_Data\Providers::render_content( $content, $post_id );

		return $content;
	}

	/**
	 * Get form field content
	 *
	 * @param string $field_id
	 * @return void
	 */
	public function get_field_content( $field_id = '' ) {
		$form_fields = $this->get_fields();

		// NOTE: Undocumented {{referrer_url}}
		if ( 'referrer_url' == $field_id && isset( $_POST['referrer'] ) ) {
			return esc_url( $_POST['referrer'] );
		}

		if ( empty( $field_id ) || ! array_key_exists( "form-field-{$field_id}", $form_fields ) ) {
			return '';
		}

		return $form_fields[ "form-field-{$field_id}" ];
	}


	/**
	 * Available actions after form submission
	 *
	 * @return void
	 */
	public static function get_available_actions() {
		return [
			'custom'       => esc_html__( 'Custom', 'bricks' ),
			'email'        => esc_html__( 'Email', 'bricks' ),
			'redirect'     => esc_html__( 'Redirect', 'bricks' ),
			'mailchimp'    => 'Mailchimp',
			'sendgrid'     => 'SendGrid',
			'login'        => esc_html__( 'User Login', 'bricks' ),
			'registration' => esc_html__( 'User Registration', 'bricks' ),
		];
	}
}
