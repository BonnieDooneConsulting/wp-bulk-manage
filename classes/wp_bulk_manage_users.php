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

	public function delete_user_upload() {
		// TODO: implement user and post delete from csv
		echo 'done';
		exit;
	}
}
