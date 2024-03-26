<?php

class wp_bulk_manage_user_export {

	/**
	 * @var wp_bulk_manage_log
	 */
	protected $log;

	public function __construct( wp_bulk_manage_log $log ) {

		$this->log = $log;
	}

	/**
	 * @method export_users
	 *
	 * @return void
	 * @author awilson
	 */
	public function export_users() {
		$user_query = $this->get_wp_user_query( [] );

		if ( ! empty( $user_query->get_results() ) ) {
			$filename = sprintf( '%s-%s', time(), 'users.csv' );
			$dir      = dirname( __DIR__ ) . "/downloads/" . $filename;
			$handle   = fopen( $dir, "w" );
			// get header from keys
			$headers   = $user_query->query_vars['fields'];
			$headers[] = 'posts';
			fputcsv( $handle, $headers );

			foreach ( $user_query->get_results() as $user ) {
				$user->posts = json_encode( wp_list_pluck( $this->get_post_query( $user->id )->get_posts(),
					'post_title', 'ID' ) );
				unset( $user->id );
				fputcsv( $handle, (array) $user );
			}

			fclose( $handle );
			echo json_encode( [
				'user_count'  => $user_query->get_total(),
				'export_name' => $filename,
			] );
		} else {
			echo json_encode( [
				'user_count'  => 'No users found.',
				'export_name' => '',
			] );
		}
		exit;
	}

	/**
	 * download_user_export
	 *
	 * @return void
	 * @author awilson
	 */
	public function download_user_export() {
		if ( ! isset( $_GET['export_name'] ) ) {
			exit;
		}

		$filename = $_GET['export_name'];
		$this->download_csv_file( $filename );
		exit;
	}

	/**
	 * get_wp_user_query
	 *
	 * @param array $args
	 *
	 * @return WP_User_Query
	 * @author awilson
	 */
	private function get_wp_user_query( array $args ): WP_User_Query {
		$args = array_merge( $args, [
			// limit to five users for now
			//'number' => 5,
			'role'   => 'Subscriber',
			'fields' => [
				'id',
				'user_login',
				'user_email',
			]
		] );

		return new WP_User_Query( $args );
	}

	/**
	 * get_post_query
	 *
	 * @param int   $user_id
	 * @param array $args
	 *
	 * @return WP_Query
	 * @author awilson
	 */
	private function get_post_query( int $user_id, array $args = [] ): WP_Query {
		$args = array_merge( $args, [
			'author'    => $user_id,
			'post_type' => 'abstracts',
			'fields'    => [
				'ID',
				'post_title',
			]
		] );

		return new WP_Query( $args );
	}

	/**
	 * download_csv_file
	 *
	 * @param string $filename
	 *
	 * @return void
	 * @author awilson
	 */
	private function download_csv_file( string $filename ) {
		$full_file_name = dirname( __DIR__ ) . "/downloads/" . $filename;
		$f              = fopen( $full_file_name, 'r' );
		// tell the browser it's going to be a csv file
		header( 'Content-Type: text/csv' );
		// tell the browser we want to save it instead of displaying it
		header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
		// make php send the generated csv lines to the browser
		fpassthru( $f );
	}

}
