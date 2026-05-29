<?php

/**
 * Read-only queries against wp_users / wp_usermeta used to find bulk-delete candidates.
 *
 * Candidate criteria (all must hold):
 *   - meta_key member_type matches one of the chosen types
 *   - has at least one membership_paid_YYYY = 1 row
 *   - MAX of those YYYY values is <= the chosen cutoff_year
 *   - is NOT a lifetime member (membership_lifetime != 1)
 *   - is NOT in the permanent exclusion list (bulk_delete_exempt != 1)
 *   - does NOT have administrator, editor, author, or contributor capability
 *   - is NOT the currently logged-in user
 */
class wp_bulk_manage_member_query {

	protected wp_bulk_manage_log $log;

	public function __construct( wp_bulk_manage_log $log ) {
		$this->log = $log;
	}

	/**
	 * @return string[] Distinct member_type values, alphabetically sorted.
	 */
	public function get_distinct_member_types(): array {
		global $wpdb;
		$sql = "SELECT DISTINCT meta_value
		        FROM {$wpdb->usermeta}
		        WHERE meta_key = 'member_type'
		          AND meta_value <> ''
		        ORDER BY meta_value ASC";
		$rows = $wpdb->get_col( $sql );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Build the candidate list.
	 *
	 * @param string[] $member_types Selected member_type values.
	 * @param int      $cutoff_year  Most-recent-paid year must be <= this.
	 * @param int      $limit        Optional row cap (>0). 0 = no limit.
	 *
	 * @return array<int, object> Rows with: ID, user_login, user_email, display_name,
	 *                            user_registered, member_type, last_paid_year.
	 */
	public function get_candidates( array $member_types, int $cutoff_year, int $limit = 0 ): array {
		global $wpdb;

		$member_types = array_values( array_filter( array_map( 'strval', $member_types ) ) );
		if ( empty( $member_types ) || $cutoff_year < 1 ) {
			return array();
		}

		$types_placeholders = implode( ', ', array_fill( 0, count( $member_types ), '%s' ) );
		$capabilities_key   = $wpdb->prefix . 'capabilities';
		$current_user_id    = get_current_user_id();

		$sql = "
			SELECT
				u.ID,
				u.user_login,
				u.user_email,
				u.display_name,
				u.user_registered,
				mt.meta_value AS member_type,
				MAX(CAST(SUBSTRING(mp.meta_key, 17) AS UNSIGNED)) AS last_paid_year
			FROM {$wpdb->users} u
			INNER JOIN {$wpdb->usermeta} mt
				ON mt.user_id = u.ID
				AND mt.meta_key = 'member_type'
				AND mt.meta_value IN ($types_placeholders)
			LEFT JOIN {$wpdb->usermeta} mp
				ON mp.user_id = u.ID
				AND mp.meta_key REGEXP '^membership_paid_[0-9]{4}\$'
				AND mp.meta_value = '1'
			LEFT JOIN {$wpdb->usermeta} lt
				ON lt.user_id = u.ID
				AND lt.meta_key = 'membership_lifetime'
				AND lt.meta_value = '1'
			LEFT JOIN {$wpdb->usermeta} ex
				ON ex.user_id = u.ID
				AND ex.meta_key = 'bulk_delete_exempt'
				AND ex.meta_value = '1'
			LEFT JOIN {$wpdb->usermeta} caps
				ON caps.user_id = u.ID
				AND caps.meta_key = %s
			WHERE lt.umeta_id IS NULL
			  AND ex.umeta_id IS NULL
			  AND u.ID <> %d
			  AND ( caps.meta_value IS NULL OR (
			        caps.meta_value NOT LIKE %s
			    AND caps.meta_value NOT LIKE %s
			    AND caps.meta_value NOT LIKE %s
			    AND caps.meta_value NOT LIKE %s
			  ))
			GROUP BY u.ID
			HAVING last_paid_year IS NOT NULL
			   AND last_paid_year <= %d
			ORDER BY last_paid_year ASC, u.user_login ASC
		";

		$args = array_merge(
			$member_types,
			array(
				$capabilities_key,
				$current_user_id,
				'%"administrator"%',
				'%"editor"%',
				'%"author"%',
				'%"contributor"%',
				$cutoff_year,
			)
		);

		if ( $limit > 0 ) {
			$sql .= " LIMIT %d";
			$args[] = $limit;
		}

		$prepared = $wpdb->prepare( $sql, $args );
		$rows = $wpdb->get_results( $prepared );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Fetch the same summary for a single user. Returns null if the user no longer
	 * exists or fails any of the safety filters.
	 *
	 * @param int $user_id
	 * @return object|null
	 */
	public function get_user_summary( int $user_id ): ?object {
		global $wpdb;

		if ( $user_id <= 0 ) {
			return null;
		}

		$capabilities_key = $wpdb->prefix . 'capabilities';
		$current_user_id  = get_current_user_id();

		$sql = "
			SELECT
				u.ID,
				u.user_login,
				u.user_email,
				u.display_name,
				u.user_registered,
				mt.meta_value AS member_type,
				MAX(CAST(SUBSTRING(mp.meta_key, 17) AS UNSIGNED)) AS last_paid_year
			FROM {$wpdb->users} u
			LEFT JOIN {$wpdb->usermeta} mt
				ON mt.user_id = u.ID
				AND mt.meta_key = 'member_type'
			LEFT JOIN {$wpdb->usermeta} mp
				ON mp.user_id = u.ID
				AND mp.meta_key REGEXP '^membership_paid_[0-9]{4}\$'
				AND mp.meta_value = '1'
			LEFT JOIN {$wpdb->usermeta} lt
				ON lt.user_id = u.ID
				AND lt.meta_key = 'membership_lifetime'
				AND lt.meta_value = '1'
			LEFT JOIN {$wpdb->usermeta} ex
				ON ex.user_id = u.ID
				AND ex.meta_key = 'bulk_delete_exempt'
				AND ex.meta_value = '1'
			LEFT JOIN {$wpdb->usermeta} caps
				ON caps.user_id = u.ID
				AND caps.meta_key = %s
			WHERE u.ID = %d
			  AND u.ID <> %d
			  AND lt.umeta_id IS NULL
			  AND ex.umeta_id IS NULL
			  AND ( caps.meta_value IS NULL OR (
			        caps.meta_value NOT LIKE %s
			    AND caps.meta_value NOT LIKE %s
			    AND caps.meta_value NOT LIKE %s
			    AND caps.meta_value NOT LIKE %s
			  ))
			GROUP BY u.ID
		";

		$prepared = $wpdb->prepare(
			$sql,
			$capabilities_key,
			$user_id,
			$current_user_id,
			'%"administrator"%',
			'%"editor"%',
			'%"author"%',
			'%"contributor"%'
		);

		$row = $wpdb->get_row( $prepared );
		return $row ?: null;
	}
}
