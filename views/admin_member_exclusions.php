<?php
/**
 * Protected Users page: lists every user currently marked
 * bulk_delete_exempt = 1, with a button to remove the exemption.
 *
 * Available variables:
 *   $excluded_users   object[]  ID, user_login, user_email, display_name
 *   $nonce            string
 */
?>
<div class="bulk-manage-exclusions">
	<h2><?php esc_html_e( 'Protected Users' ); ?></h2>

	<p class="description">
		<?php esc_html_e( 'These users are permanently excluded from the Bulk Delete Members candidate list. They will never appear, regardless of filters. You can also toggle this on a per-user basis from the standard WordPress user edit screen.' ); ?>
	</p>

	<?php if ( empty( $excluded_users ) ) : ?>
		<p><em><?php esc_html_e( 'No users are currently protected.' ); ?></em></p>
	<?php else : ?>
		<table class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID' ); ?></th>
					<th><?php esc_html_e( 'Login' ); ?></th>
					<th><?php esc_html_e( 'Email' ); ?></th>
					<th><?php esc_html_e( 'Name' ); ?></th>
					<th><?php esc_html_e( 'Actions' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $excluded_users as $row ) : ?>
					<tr data-user-id="<?php echo (int) $row->ID; ?>">
						<td><?php echo (int) $row->ID; ?></td>
						<td><?php echo esc_html( $row->user_login ); ?></td>
						<td><?php echo esc_html( $row->user_email ); ?></td>
						<td><?php echo esc_html( $row->display_name ); ?></td>
						<td>
							<button type="button"
							        class="button unexclude-btn"
							        data-user-id="<?php echo (int) $row->ID; ?>">
								<?php esc_html_e( 'Remove protection' ); ?>
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
