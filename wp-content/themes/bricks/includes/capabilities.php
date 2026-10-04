<?php
namespace Bricks;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Builder access 'bricks_full_access' or 'bricks_edit_content'
 *
 * Set per user role under 'Bricks > Settings > Builder access OR by editing a user profile individually.
 *
 * 'bricks_edit_content' capability can't:
 *
 * - Add, clone, delete, save elements/templates
 * - Resize elements (width, height)
 * - Adjust element spacing (padding, margin)
 * - Access custom context menu
 * - Edit any CSS controls (property 'css' check)
 * - Edit any controls under "Style" tab
 * - Edit any controls with 'fullAccess' set to true
 * - Delete revisions
 * - Edit template settings
 * - Edit any page settings except 'SEO' (default panel)
 */

class Capabilities {
	const FULL_ACCESS  = 'bricks_full_access';
	const EDIT_CONTENT = 'bricks_edit_content';
	const UPLOAD_SVG   = 'bricks_upload_svg';
	const EXECUTE_CODE = 'bricks_execute_code';

	public function __construct() {
		add_action( 'edit_user_profile', [ $this, 'user_profile' ] );
		add_action( 'edit_user_profile_update', [ $this, 'update_user_profile' ] );

		add_filter( 'manage_users_columns', [ $this, 'manage_users_columns' ] );
		add_filter( 'manage_users_custom_column', [ $this, 'manage_users_custom_column' ], 10, 3 );
	}

	public function manage_users_columns( $columns ) {
		$columns['bricks_builder_access'] = esc_html__( 'Builder access', 'bricks' );

		return $columns;
	}

	public function manage_users_custom_column( $output, $column_name, $user_id ) {
		if ( $column_name !== 'bricks_builder_access' ) {
			return $output;
		}

		$user             = get_user_by( 'ID', $user_id );
		$user_capabilites = array_keys( $user->allcaps );

		$output = [];

		if ( in_array( self::FULL_ACCESS, $user_capabilites ) || in_array( 'manage_options', $user_capabilites ) ) {
			$output[] = esc_html__( 'Full access', 'bricks' );
		} elseif ( in_array( self::EDIT_CONTENT, $user_capabilites ) ) {
			$output[] = esc_html__( 'Edit content', 'bricks' );
		} else {
			$output[] = esc_html__( 'No access', 'bricks' );
		}

		$other_capabilities = [
			self::UPLOAD_SVG   => esc_html__( 'Upload SVG', 'bricks' ),
			self::EXECUTE_CODE => esc_html__( 'Code Execution', 'bricks' ),
		];

		foreach ( $other_capabilities as $key => $label ) {
			if ( in_array( $key, $user_capabilites ) ) {
				$output[] = $label;
			}
		}

		return implode( ', ', $output );
	}

	/**
	 * Update user capability for Bricks access
	 */
	public function update_user_profile( $user_id ) {
		$user = get_user_by( 'ID', $user_id );

		$user->remove_cap( self::FULL_ACCESS );
		$user->remove_cap( self::EDIT_CONTENT );
		$user->remove_cap( self::UPLOAD_SVG );
		$user->remove_cap( self::EXECUTE_CODE );

		$caps = [
			'bricks_cap_builder_access' => isset( $_POST['bricks_cap_builder_access'] ) ? $_POST['bricks_cap_builder_access'] : '',
			'bricks_cap_upload_svg'     => self::UPLOAD_SVG,
			'bricks_cap_execute_code'   => self::EXECUTE_CODE
		];

		foreach ( $caps as $key => $cap ) {
			if ( ! empty( $_POST[ $key ] ) ) {
				$user->add_cap( $cap );
			}
		}
	}

	public function user_profile( $user ) {
		$user_capabilites = array_keys( $user->allcaps );

		$current_builder_capability = false;

		// Get user capability (builder access)
		if ( in_array( self::FULL_ACCESS, $user_capabilites ) || in_array( 'manage_options', $user_capabilites ) ) {
			$current_builder_capability = self::FULL_ACCESS;
		} elseif ( in_array( self::EDIT_CONTENT, $user_capabilites ) ) {
			$current_builder_capability = self::EDIT_CONTENT;
		}

		echo '<h2>' . BRICKS_NAME . '</h2>';
		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th><label for="bricks_cap_builder_access"><?php esc_html_e( 'Builder access', 'bricks' ); ?></label></th>
					<td>
						<select name="bricks_cap_builder_access" id="bricks_cap_builder_access">
							<option value=""><?php echo esc_html__( 'Default', 'bricks' ) . ' (' . esc_html__( 'Builder access', 'bricks' ) . ')'; ?></option>
							<option value="<?php echo self::FULL_ACCESS; ?>" <?php selected( $current_builder_capability === self::FULL_ACCESS ); ?>><?php esc_html_e( 'Full access', 'bricks' ); ?></option>
							<option value="<?php echo self::EDIT_CONTENT; ?>" <?php selected( $current_builder_capability === self::EDIT_CONTENT ); ?>><?php esc_html_e( 'Edit content', 'bricks' ); ?></option>
						</select>
					</td>
				</tr>

				<tr>
					<th><label for="bricks_cap_upload_svg"><?php esc_html_e( 'Upload SVG', 'bricks' ); ?></label></th>
					<td>
						<label for="bricks_cap_upload_svg">
							<input name="bricks_cap_upload_svg" type="checkbox" id="bricks_cap_upload_svg" value="1" <?php checked( in_array( self::UPLOAD_SVG, $user_capabilites ) ); ?>>
							<?php esc_html_e( 'Allow user to upload SVG files', 'bricks' ); ?>
						</label>
						<br>
					</td>
				</tr>

				<tr>
					<th><label for="bricks_cap_execute_code"><?php esc_html_e( 'Code Execution', 'bricks' ); ?></label></th>
					<td>
						<label for="bricks_cap_execute_code">
							<input name="bricks_cap_execute_code" type="checkbox" id="bricks_cap_execute_code" value="1" <?php checked( in_array( self::EXECUTE_CODE, $user_capabilites ) ); ?>>
							<?php esc_html_e( 'Allow user to change and execute code through the Code element', 'bricks' ); ?>
						</label>
						<br>
					</td>
				</tr>

			</tbody>
		</table>
		<?php
	}

	/**
	 * Check if user role has capability to use builder (full access OR edit content)
	 *
	 * @return boolean
	 */
	public static function current_user_can_use_builder( $post_id = 0 ) {
		// Post status: 'trash'
		if ( 'trash' === get_post_status( $post_id ) ) {
			return false;
		}

		// User not logged in
		if ( ! is_user_logged_in() ) {
			return false;
		}

		// Required capabilities to edit with builder
		if ( self::current_user_has_full_access() ) {
			return true;
		}

		// Builder access: User capability has been set to "Edit content" or "Full access"
		if ( current_user_can( self::EDIT_CONTENT ) || current_user_can( self::FULL_ACCESS ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Required capabilities to edit with builder (full access)
	 *
	 * Administrator always has full access.
	 *
	 * @return boolean
	 */
	public static function current_user_has_full_access() {
		return is_super_admin() || current_user_can( 'manage_options' ) || current_user_can( self::FULL_ACCESS );
	}

	/**
	 * Logged-in user has no access to Bricks
	 *
	 * @since 1.4
	 *
	 * @return boolean
	 */
	public static function current_user_has_no_access() {
		return ! self::current_user_has_full_access() && ! current_user_can( self::EDIT_CONTENT );
	}

	/**
	 * Reset user role capabilities for Bricks
	 */
	public static function set_defaults() {
		$bricks_caps = [
			self::EDIT_CONTENT,
			self::FULL_ACCESS
		];

		$roles = wp_roles()->get_names();

		// Remove Bricks capabilities for all user roles
		foreach ( $roles as $role_key => $role_name ) {
			foreach ( $bricks_caps as $cap ) {
				wp_roles()->remove_cap( $role_key, $cap );
			}
		}

		// Set defaults: Administrator always has full access to Bricks
		wp_roles()->add_cap( 'administrator', self::FULL_ACCESS );
	}

	/**
	 * Capabilities for access to the builder
	 *
	 * @return array
	 */
	public static function builder_caps() {
		return [
			[
				'capability' => '',
				'label'      => esc_html__( 'No access', 'bricks' ),
			],
			[
				'capability' => self::EDIT_CONTENT,
				'label'      => esc_html__( 'Edit content', 'bricks' ),
			],
			[
				'capability' => self::FULL_ACCESS,
				'label'      => esc_html__( 'Full access', 'bricks' ),
			],
		];
	}

	public static function save_builder_capabilities( $capabilities = [] ) {
		$allowed_caps = array_filter( array_column( self::builder_caps(), 'capability' ) );

		foreach ( $capabilities as $role => $capability ) {
			if ( ! empty( $capability ) && ! in_array( $capability, $allowed_caps ) ) {
				continue;
			}

			// Reset Bricks capabilities for this role
			foreach ( $allowed_caps as $allowed_cap ) {
				wp_roles()->remove_cap( $role, $allowed_cap );
			}

			// Set the selected Bricks capability for this role
			if ( ! empty( $capability ) ) {
				wp_roles()->add_cap( $role, $capability );
			}
		}
	}

	public static function save_capabilities( $capability, $add_to_roles = [] ) {
		if ( empty( $capability ) ) {
			return;
		}

		$roles = wp_roles()->get_names();

		foreach ( $roles as $role_key => $label ) {
			if ( in_array( $role_key, $add_to_roles ) ) {
				wp_roles()->add_cap( $role_key, $capability );
			} else {
				wp_roles()->remove_cap( $role_key, $capability );
			}
		}
	}
}
