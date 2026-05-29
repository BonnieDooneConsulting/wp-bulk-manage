<?php

/**
 * Permanent exclusion list. A user with user_meta bulk_delete_exempt = 1
 * never appears in the bulk-delete candidate list, regardless of filters.
 *
 * Two ways to manage entries:
 *   1. The Protected Users admin page (bulk view + un-exclude buttons)
 *   2. A checkbox on the standard WP user edit screen
 */
class wp_bulk_manage_exclusions {

	const META_KEY = 'bulk_delete_exempt';

	protected wp_bulk_manage_log $log;

	public function __construct( wp_bulk_manage_log $log ) {
		$this->log = $log;
	}

	public function add( int $user_id ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}
		return (bool) update_user_meta( $user_id, self::META_KEY, 1 );
	}

	public function remove( int $user_id ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}
		return (bool) delete_user_meta( $user_id, self::META_KEY );
	}

	public function is_excluded( int $user_id ): bool {
		return (bool) get_user_meta( $user_id, self::META_KEY, true );
	}

	/**
	 * @return array<int, object> Rows: ID, user_login, user_email, display_name.
	 */
	public function get_all(): array {
		global $wpdb;
		$sql = "
			SELECT u.ID, u.user_login, u.user_email, u.display_name
			FROM {$wpdb->users} u
			INNER JOIN {$wpdb->usermeta} m
				ON m.user_id = u.ID
				AND m.meta_key = %s
				AND m.meta_value = '1'
			ORDER BY u.user_login ASC
		";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, self::META_KEY ) );
		return is_array( $rows ) ? $rows : array();
	}

	public function ajax_exclude(): void {
		$this->_check_request();
		$user_id = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;
		if ( $user_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'Invalid user_id.' ), 400 );
		}
		$this->add( $user_id );
		wp_send_json_success( array( 'user_id' => $user_id, 'excluded' => true ) );
	}

	public function ajax_unexclude(): void {
		$this->_check_request();
		$user_id = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;
		if ( $user_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'Invalid user_id.' ), 400 );
		}
		$this->remove( $user_id );
		wp_send_json_success( array( 'user_id' => $user_id, 'excluded' => false ) );
	}

	/**
	 * Render the "Exempt from bulk member deletion" checkbox on the user-edit screen.
	 */
	public function render_profile_field( WP_User $user ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$checked = $this->is_excluded( $user->ID ) ? 'checked="checked"' : '';
		?>
		<h2><?php esc_html_e( 'Bulk Member Deletion' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="bulk_delete_exempt"><?php esc_html_e( 'Exempt from bulk deletion' ); ?></label>
				</th>
				<td>
					<input type="checkbox" id="bulk_delete_exempt" name="bulk_delete_exempt" value="1" <?php echo $checked; ?> />
					<p class="description">
						<?php esc_html_e( 'When checked, this user will never appear in the Bulk Delete Members candidate list.' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save the checkbox state when a user profile is updated.
	 */
	public function save_profile_field( int $user_id ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! empty( $_POST['bulk_delete_exempt'] ) ) {
			$this->add( $user_id );
		} else {
			$this->remove( $user_id );
		}
	}

	private function _check_request(): void {
		if ( ! check_ajax_referer( 'wp_bulk_manage', '_wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Bad nonce.' ), 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden.' ), 403 );
		}
	}
}
