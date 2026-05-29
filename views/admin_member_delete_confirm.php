<?php
/**
 * Step 3: Final confirmation. Admin must type DELETE before the submit button
 * activates; submit fires chunked AJAX delete with a progress bar.
 *
 * Available variables:
 *   $selected_summaries object[]  Same shape as preview rows
 *   $member_types       string[]
 *   $cutoff_year        int
 *   $plugin_admin_page  string
 *   $nonce              string
 */
$count = count( $selected_summaries );
?>
<div class="bulk-manage-member-delete-confirm">
	<h2><?php esc_html_e( 'Bulk Delete Members — Step 3: Confirm' ); ?></h2>

	<div id="bulk-delete-complete" class="notice notice-success inline" style="display:none;">
		<p>
			<strong><?php esc_html_e( 'Done.' ); ?></strong>
			<span id="bulk-delete-result-summary"></span>
		</p>
		<p>
			<a href="admin.php?page=wp-bulk-manage-deletion-log" class="button button-primary">
				<?php esc_html_e( 'Review Deletion Log →' ); ?>
			</a>
			<a href="admin.php?page=<?php echo esc_attr( $plugin_admin_page ); ?>" class="button" style="margin-left:8px;">
				<?php esc_html_e( 'Run another delete' ); ?>
			</a>
		</p>
	</div>

	<?php if ( $count === 0 ) : ?>
		<div class="notice notice-warning inline">
			<p><?php esc_html_e( 'No valid users were selected. Go back and choose at least one.' ); ?></p>
		</div>
		<p>
			<a href="admin.php?page=<?php echo esc_attr( $plugin_admin_page ); ?>" class="button">
				<?php esc_html_e( '← Back to filter' ); ?>
			</a>
		</p>
	<?php else : ?>
		<div class="notice notice-error inline">
			<p>
				<strong><?php esc_html_e( 'WARNING:' ); ?></strong>
				<?php
				printf(
					esc_html__( 'You are about to permanently delete %d user(s) and ALL of their associated content (posts, abstracts, links, comments). This cannot be undone.' ),
					(int) $count
				);
				?>
			</p>
		</div>

		<table class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID' ); ?></th>
					<th><?php esc_html_e( 'Login' ); ?></th>
					<th><?php esc_html_e( 'Email' ); ?></th>
					<th><?php esc_html_e( 'Type' ); ?></th>
					<th><?php esc_html_e( 'Last paid year' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $selected_summaries as $row ) : ?>
					<tr>
						<td><?php echo (int) $row->ID; ?></td>
						<td><?php echo esc_html( $row->user_login ); ?></td>
						<td><?php echo esc_html( $row->user_email ); ?></td>
						<td><?php echo esc_html( $row->member_type ); ?></td>
						<td><?php echo $row->last_paid_year ? (int) $row->last_paid_year : '—'; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<form id="bulk-delete-confirm-form" method="post" onsubmit="return false;">
			<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>" />
			<?php foreach ( $selected_summaries as $row ) : ?>
				<input type="hidden" name="user_ids[]" value="<?php echo (int) $row->ID; ?>" />
			<?php endforeach; ?>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="delete-confirmation-text"><?php esc_html_e( 'Type DELETE to enable the button' ); ?></label>
					</th>
					<td>
						<input type="text" id="delete-confirmation-text" autocomplete="off" />
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="button" class="button button-primary" id="bulk-delete-submit" disabled>
					<?php printf( esc_html__( 'Delete %d user(s)' ), (int) $count ); ?>
				</button>
				<a href="admin.php?page=<?php echo esc_attr( $plugin_admin_page ); ?>" class="button" style="margin-left:8px;">
					<?php esc_html_e( 'Cancel' ); ?>
				</a>
			</p>

			<div id="bulk-delete-progress" style="display:none;">
				<h3><?php esc_html_e( 'Deleting…' ); ?></h3>
				<progress id="bulk-delete-progress-bar" value="0" max="<?php echo (int) $count; ?>"></progress>
				<p>
					<span id="bulk-delete-progress-status">
						<?php esc_html_e( 'Deleted 0 of' ); ?> <?php echo (int) $count; ?>
					</span>
				</p>
			</div>
		</form>
	<?php endif; ?>
</div>
