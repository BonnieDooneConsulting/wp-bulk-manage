<?php
/**
 * Step 2: Preview candidate list. Admin checkboxes pick which users continue to
 * step 3; per-row "Exclude permanently" button adds the user to the
 * bulk_delete_exempt meta list via AJAX.
 *
 * Available variables:
 *   $member_types       string[]
 *   $cutoff_year        int
 *   $candidates         object[]  ID, user_login, user_email, display_name, user_registered, member_type, last_paid_year
 *   $plugin_admin_page  string
 *   $nonce              string    For the exclude AJAX call
 */
$count = count( $candidates );
?>
<div class="bulk-manage-member-delete-preview">
	<h2><?php esc_html_e( 'Bulk Delete Members — Step 2: Preview' ); ?></h2>

	<p>
		<strong><?php echo (int) $count; ?></strong>
		<?php esc_html_e( 'candidate(s) shown.' ); ?>
		<?php esc_html_e( 'Type(s):' ); ?>
		<code><?php echo esc_html( implode( ', ', $member_types ) ); ?></code>,
		<?php esc_html_e( 'last paid year ≤' ); ?>
		<code><?php echo (int) $cutoff_year; ?></code><?php
		if ( ! empty( $preview_limit ) ) {
			echo ', ';
			printf( esc_html__( 'limited to %d for testing' ), (int) $preview_limit );
		}
		?>.
	</p>

	<p>
		<a href="admin.php?page=<?php echo esc_attr( $plugin_admin_page ); ?>" class="button">
			<?php esc_html_e( '← Back to filter' ); ?>
		</a>
	</p>

	<?php if ( empty( $candidates ) ) : ?>
		<p><em><?php esc_html_e( 'No candidates match these criteria.' ); ?></em></p>
	<?php else : ?>
		<form method="post" action="admin.php?page=<?php echo esc_attr( $plugin_admin_page ); ?>&step=confirm">
			<?php wp_nonce_field( 'wp_bulk_manage' ); ?>
			<input type="hidden" name="step" value="confirm" />
			<input type="hidden" name="cutoff_year" value="<?php echo (int) $cutoff_year; ?>" />
			<?php foreach ( $member_types as $mt ) : ?>
				<input type="hidden" name="member_types[]" value="<?php echo esc_attr( $mt ); ?>" />
			<?php endforeach; ?>

			<p>
				<button type="submit" class="button button-primary" id="continue-to-confirm">
					<?php esc_html_e( 'Continue to Confirmation →' ); ?>
				</button>
				<span style="margin-left:14px;">
					<span id="selected-count">0</span> <?php esc_html_e( 'selected of' ); ?> <?php echo (int) $count; ?>
				</span>
			</p>

			<table class="wp-list-table widefat striped" id="candidate-table">
				<thead>
					<tr>
						<th class="check-column">
							<input type="checkbox" id="select-all-candidates" />
						</th>
						<th><?php esc_html_e( 'ID' ); ?></th>
						<th><?php esc_html_e( 'Login' ); ?></th>
						<th><?php esc_html_e( 'Email' ); ?></th>
						<th><?php esc_html_e( 'Name' ); ?></th>
						<th><?php esc_html_e( 'Type' ); ?></th>
						<th><?php esc_html_e( 'Last paid year' ); ?></th>
						<th><?php esc_html_e( 'Registered' ); ?></th>
						<th><?php esc_html_e( 'Actions' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $candidates as $row ) : ?>
						<tr data-user-id="<?php echo (int) $row->ID; ?>">
							<th class="check-column">
								<input type="checkbox" class="candidate-checkbox" name="user_ids[]" value="<?php echo (int) $row->ID; ?>" checked />
							</th>
							<td><?php echo (int) $row->ID; ?></td>
							<td><?php echo esc_html( $row->user_login ); ?></td>
							<td><?php echo esc_html( $row->user_email ); ?></td>
							<td><?php echo esc_html( $row->display_name ); ?></td>
							<td><?php echo esc_html( $row->member_type ); ?></td>
							<td><?php echo (int) $row->last_paid_year; ?></td>
							<td><?php echo esc_html( mysql2date( 'Y-m-d', $row->user_registered ) ); ?></td>
							<td>
								<button type="button"
								        class="button button-link-delete exclude-permanently-btn"
								        data-user-id="<?php echo (int) $row->ID; ?>">
									<?php esc_html_e( 'Exclude permanently' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</form>
	<?php endif; ?>
</div>
