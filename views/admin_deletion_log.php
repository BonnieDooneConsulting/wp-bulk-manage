<?php
/**
 * Deletion Log: read-only view of the audit table.
 *
 * Available variables:
 *   $entries  object[]  Rows from {prefix}bulk_manage_deletion_log
 */
?>
<div class="bulk-manage-deletion-log">
	<h2><?php esc_html_e( 'Deletion Log' ); ?></h2>

	<p class="description">
		<?php esc_html_e( 'Most recent bulk deletions (up to 200). Each entry captures a JSON snapshot of all user_meta at the time of deletion.' ); ?>
	</p>

	<?php if ( empty( $entries ) ) : ?>
		<p><em><?php esc_html_e( 'No deletions have been recorded yet.' ); ?></em></p>
	<?php else : ?>
		<p>
			<button type="button" class="button button-link-delete" id="clear-deletion-log-btn">
				<?php esc_html_e( 'Clear Deletion Log' ); ?>
			</button>
		</p>
		<table class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'When' ); ?></th>
					<th><?php esc_html_e( 'Deleted User ID' ); ?></th>
					<th><?php esc_html_e( 'Login' ); ?></th>
					<th><?php esc_html_e( 'Email' ); ?></th>
					<th><?php esc_html_e( 'Performed by' ); ?></th>
					<th><?php esc_html_e( 'Meta snapshot' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $entries as $entry ) :
					$admin_user = get_userdata( (int) $entry->deleting_admin_id );
					$admin_label = $admin_user ? $admin_user->user_login . ' (#' . $admin_user->ID . ')' : '#' . (int) $entry->deleting_admin_id;
					$snapshot = json_decode( $entry->deleted_user_meta_snapshot, true );
					$snapshot_pretty = is_array( $snapshot ) ? wp_json_encode( $snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) : $entry->deleted_user_meta_snapshot;
				?>
					<tr>
						<td><?php echo esc_html( $entry->deleted_at ); ?></td>
						<td><?php echo (int) $entry->deleted_user_id; ?></td>
						<td><?php echo esc_html( $entry->deleted_user_login ); ?></td>
						<td><?php echo esc_html( $entry->deleted_user_email ); ?></td>
						<td><?php echo esc_html( $admin_label ); ?></td>
						<td>
							<details>
								<summary><?php esc_html_e( 'view JSON' ); ?></summary>
								<pre style="max-height:300px; overflow:auto; background:#f6f7f7; padding:8px; font-size:11px;"><?php echo esc_html( $snapshot_pretty ); ?></pre>
							</details>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
