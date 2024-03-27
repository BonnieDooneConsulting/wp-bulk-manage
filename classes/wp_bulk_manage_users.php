<?php

class wp_bulk_manage_users
{

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
	public function delete_user_upload() {
		$tmpName = $_FILES['user-delete']['tmp_name'];
		$csv = array_map('str_getcsv', file($tmpName));

		foreach ($csv as $user_record) {
			if (!ctype_digit($user_record[0])) {
				continue;
			}
			$this->delete_user($user_record);
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
	private function delete_user(array $user_data) {
		// TODO: check that user can delete users
//		if (!wp_delete_user($user_data[0])) {
//			$this->log->error('unable to delete user: ' . $user_data[0]);
//		}
	}
}
