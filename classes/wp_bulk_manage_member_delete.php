<?php

/**
 * AJAX handler that hard-deletes a chunk of users along with their content.
 *
 * Each user_id submitted in $_POST['user_ids'] is re-validated against the same
 * safety filters used to build the candidate list (protects against TOCTOU
 * between preview and the actual delete), then logged to the audit table and
 * error_log, then wp_delete_user() is called without a reassign target so all
 * posts/links the user authored are deleted as well.
 */
class wp_bulk_manage_member_delete {

	protected wp_bulk_manage_log $log;
	protected wp_bulk_manage_member_query $member_query;
	protected wp_bulk_manage_deletion_log $deletion_log;
	protected wp_bulk_manage_exclusions $exclusions;

	public function __construct(
		wp_bulk_manage_log $log,
		wp_bulk_manage_member_query $member_query,
		wp_bulk_manage_deletion_log $deletion_log,
		wp_bulk_manage_exclusions $exclusions
	) {
		$this->log          = $log;
		$this->member_query = $member_query;
		$this->deletion_log = $deletion_log;
		$this->exclusions   = $exclusions;
	}

	public function delete_chunk(): void {
		if ( ! check_ajax_referer( 'wp_bulk_manage', '_wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Bad nonce.' ), 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden.' ), 403 );
		}

		$raw_ids  = isset( $_POST['user_ids'] ) && is_array( $_POST['user_ids'] ) ? $_POST['user_ids'] : array();
		$user_ids = array_values( array_unique( array_filter( array_map( 'intval', $raw_ids ) ) ) );

		if ( empty( $user_ids ) ) {
			wp_send_json_success( array( 'deleted' => 0, 'failed' => array(), 'skipped' => array() ) );
		}

		require_once ABSPATH . 'wp-admin/includes/user.php';

		$deleted = 0;
		$failed  = array();
		$skipped = array();

		foreach ( $user_ids as $user_id ) {
			if ( ! $this->_is_safe( $user_id ) ) {
				$skipped[] = $user_id;
				$this->log->warn( 'Skipped unsafe user during bulk delete', array( 'user_id' => $user_id ) );
				continue;
			}

			$user = get_userdata( $user_id );
			if ( ! $user ) {
				$skipped[] = $user_id;
				continue;
			}

			// Record snapshot BEFORE deletion so the meta is still readable.
			$this->deletion_log->record( $user );
			$this->log->info( 'Deleting user', array(
				'user_id'    => $user->ID,
				'user_login' => $user->user_login,
				'user_email' => $user->user_email,
				'by_admin'   => get_current_user_id(),
			) );

			// No reassign argument => hard delete: posts/links authored by the user
			// are also removed (see wp_delete_user() docs).
			if ( wp_delete_user( $user_id ) ) {
				$deleted++;
			} else {
				$failed[] = $user_id;
				$this->log->error( 'wp_delete_user() failed', array( 'user_id' => $user_id ) );
			}
		}

		wp_send_json_success( array(
			'deleted' => $deleted,
			'failed'  => $failed,
			'skipped' => $skipped,
		) );
	}

	/**
	 * Re-validate the user against the same safety filters used to build the candidate list.
	 * The cutoff_year / member_type check is NOT re-applied here — once an admin has
	 * confirmed a specific user_id we trust their selection — but the hard protections
	 * (admin/editor/author/contributor, lifetime, excluded, current user, self) ARE.
	 */
	private function _is_safe( int $user_id ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}
		if ( $user_id === get_current_user_id() ) {
			return false;
		}
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}
		$protected_roles = array( 'administrator', 'editor', 'author', 'contributor' );
		foreach ( (array) $user->roles as $role ) {
			if ( in_array( $role, $protected_roles, true ) ) {
				return false;
			}
		}
		if ( (bool) get_user_meta( $user_id, 'membership_lifetime', true ) ) {
			return false;
		}
		if ( $this->exclusions->is_excluded( $user_id ) ) {
			return false;
		}
		return true;
	}
}
