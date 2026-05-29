<?php

/**
 * Audit log for bulk member deletions. Each row captures the deleted user's
 * identity, a JSON snapshot of all their user_meta, and the admin who
 * performed the deletion.
 */
class wp_bulk_manage_deletion_log {

	protected wp_bulk_manage_log $log;

	public function __construct( wp_bulk_manage_log $log ) {
		$this->log = $log;
	}

	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'bulk_manage_deletion_log';
	}

	/**
	 * Create the audit table. Called from the plugin activation hook.
	 */
	public static function install_table(): void {
		global $wpdb;
		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			deleted_at DATETIME NOT NULL,
			deleted_user_id BIGINT UNSIGNED NOT NULL,
			deleted_user_login VARCHAR(60) NOT NULL,
			deleted_user_email VARCHAR(100) NOT NULL,
			deleted_user_meta_snapshot LONGTEXT NOT NULL,
			deleting_admin_id BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY  (id),
			KEY deleted_at_idx (deleted_at),
			KEY deleting_admin_idx (deleting_admin_id)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Record a deletion. Must be called BEFORE wp_delete_user() so the meta
	 * snapshot is still available.
	 */
	public function record( WP_User $user ): void {
		global $wpdb;

		$meta_snapshot = get_user_meta( $user->ID );
		$wpdb->insert(
			self::table_name(),
			array(
				'deleted_at'                 => current_time( 'mysql' ),
				'deleted_user_id'            => $user->ID,
				'deleted_user_login'         => $user->user_login,
				'deleted_user_email'         => $user->user_email,
				'deleted_user_meta_snapshot' => wp_json_encode( $meta_snapshot ),
				'deleting_admin_id'          => get_current_user_id(),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%d' )
		);
	}

	/**
	 * Most recent log entries for the audit view.
	 *
	 * @param int $limit
	 * @return array<int, object>
	 */
	public function get_recent( int $limit = 200 ): array {
		global $wpdb;
		$table = self::table_name();
		$limit = max( 1, min( 1000, $limit ) );
		$rows  = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} ORDER BY deleted_at DESC, id DESC LIMIT %d",
			$limit
		) );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Remove every row from the audit table. Returns the number of rows deleted.
	 */
	public function clear_log(): int {
		global $wpdb;
		$table = self::table_name();
		$deleted = $wpdb->query( "DELETE FROM {$table}" );
		return is_int( $deleted ) ? $deleted : 0;
	}

	public function ajax_clear_log(): void {
		if ( ! check_ajax_referer( 'wp_bulk_manage', '_wpnonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Bad nonce.' ), 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden.' ), 403 );
		}
		$deleted = $this->clear_log();
		wp_send_json_success( array( 'cleared' => $deleted ) );
	}
}
