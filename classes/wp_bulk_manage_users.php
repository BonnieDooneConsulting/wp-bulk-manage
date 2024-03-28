<?php

class wp_bulk_manage_users {

	/**
	 * @var wp_bulk_manage_log
	 */
	protected wp_bulk_manage_log $log;

	public function __construct( wp_bulk_manage_log $log ) {
		$this->log = $log;
	}

	/**
	 * @method delete_user_upload
	 *
	 * @return void
	 * @author awilson
	 */
	public function delete_user_upload(): void {
		$tmpName = $_FILES['user-delete']['tmp_name'];
		$csv     = array_map( 'str_getcsv', file( $tmpName ) );

		foreach ( $csv as $user_record ) {
			if ( ! ctype_digit( $user_record[0] ) ) {
				continue;
			}
			$this->delete_user( $user_record );
		}

		echo 'done';
		exit;
	}

	/**
	 * @method delete_user
	 *
	 * @param array $user_data
	 *
	 * @return void
	 * @author awilson
	 */
	private function delete_user( array $user_data ): void {
		if ( $this->user_has_role( get_current_user_id(), 'administrator' ) ) {
			return;
		}
		if ( ! wp_delete_user( $user_data[0] ) ) {
			$this->log->error( 'unable to delete user: ' . $user_data[0] );
		}
	}

	/**
	 * @method user_has_role
	 *
	 * @param int    $user_id
	 * @param string $role_name
	 *
	 * @return bool
	 * @author awilson
	 */
	private function user_has_role( int $user_id, string $role_name ): bool {
		$user_meta  = get_userdata( $user_id );
		$user_roles = $user_meta->roles;

		return in_array( $role_name, $user_roles );
	}
}
