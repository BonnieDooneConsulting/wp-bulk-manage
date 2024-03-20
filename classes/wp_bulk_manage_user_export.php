<?php

class wp_bulk_manage_user_export {

	/**
	 * @var wp_bulk_manage_log
	 */
	protected $log;

	public function __construct( wp_bulk_manage_log $log ) {

		$this->log = $log;
	}

	public function export_users() {
		$user_query = $this->get_wp_user_query([]);

		if ( ! empty( $user_query->get_results() ) ) {
			$dir = dirname(__DIR__) . "/downloads/users.csv";
			$handle = fopen($dir, "w");
			foreach ( $user_query->get_results() as $user ) {
				unset($user->id);
				fputcsv($handle, (array) $user);
			}
			fclose($handle);
		} else {
			echo 'No users found.';
		}
	}

	private function get_wp_user_query( array $args ): WP_User_Query {
		$args       = array_merge( $args, [
			'role' => 'Subscriber',
			'fields' => [
				'id',
				'user_login',
				'user_email',
			]
		] );
		return new WP_User_Query( $args );
	}

	private function get_post_query( int $user_id, array $args): WP_Query {
		$args       = array_merge( $args, [
			'author' => $user_id,
			'posts_per_page' => -1,
		] );
		return new WP_Query( $args );
	}

}
