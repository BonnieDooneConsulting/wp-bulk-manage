<?php
/**
 * Step 1: Filter form for the bulk member deletion flow.
 *
 * Available variables:
 *   $available_types  string[]  Distinct member_type values from the DB
 *   $plugin_admin_page string   Current page slug (used as ?page= value)
 *   $nonce            string    Nonce for any subsequent AJAX calls (not strictly needed here)
 */
$current_year = (int) date( 'Y' );
?>
<div class="bulk-manage-member-delete-filter">
	<h2><?php esc_html_e( 'Bulk Delete Members — Step 1: Filter' ); ?></h2>

	<details class="bulk-delete-help" open style="background:#f6f7f7; border-left:4px solid #2271b1; padding:12px 16px; margin:12px 0;">
		<summary style="font-weight:600; cursor:pointer; font-size:14px;">
			<?php esc_html_e( 'How this works — read before running' ); ?>
		</summary>

		<h3><?php esc_html_e( 'What this tool does' ); ?></h3>
		<p>
			<?php esc_html_e( 'This is a destructive, multi-step purge for inactive members. It hard-deletes user accounts from the WordPress database AND deletes every post, abstract, page, link, and comment those users authored. There is no undo. Records of each deletion are kept in the Deletion Log tab.' ); ?>
		</p>

		<h3><?php esc_html_e( 'How a user becomes a candidate' ); ?></h3>
		<p><?php esc_html_e( 'A user shows up in the preview only if ALL of these are true:' ); ?></p>
		<ol>
			<li>
				<strong><?php esc_html_e( 'Membership type matches.' ); ?></strong>
				<?php esc_html_e( "Their user_meta key 'member_type' equals one of the values you checked above (e.g. Student, Member). The list is built dynamically from whatever distinct values exist in the database." ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'They have paid at some point.' ); ?></strong>
				<?php esc_html_e( "They have at least one user_meta row matching the pattern membership_paid_YYYY = 1 (e.g. membership_paid_2018 = 1). Users who have NEVER paid are NOT candidates — they are skipped entirely." ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Their most recent paid year is at or before the cutoff.' ); ?></strong>
				<?php esc_html_e( 'Among all of their membership_paid_YYYY = 1 rows, the highest year (their most recent payment) must be less than or equal to the cutoff year you set above. If their most recent payment is more recent than the cutoff, they are not a candidate.' ); ?>
			</li>
		</ol>

		<h3><?php esc_html_e( 'Who is always protected (never a candidate)' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Lifetime members (user_meta membership_lifetime = 1)' ); ?></li>
			<li><?php esc_html_e( 'Anyone with the role administrator, editor, author, or contributor' ); ?></li>
			<li><?php esc_html_e( 'Anyone on the permanent exclusion list (Protected Users tab, user_meta bulk_delete_exempt = 1)' ); ?></li>
			<li><?php esc_html_e( 'You — the currently logged-in admin' ); ?></li>
		</ul>

		<h3><?php esc_html_e( 'The deletion flow' ); ?></h3>
		<ol>
			<li>
				<strong><?php esc_html_e( 'Filter (this page).' ); ?></strong>
				<?php esc_html_e( 'Pick membership types and the cutoff year. Optionally cap the preview to a small number for testing.' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Preview.' ); ?></strong>
				<?php esc_html_e( 'You see every candidate in a table. Each row has a checkbox (checked by default — uncheck anyone you want to spare from THIS run) and an Exclude permanently button (adds them to the Protected Users list so they never appear in future runs either).' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Confirm.' ); ?></strong>
				<?php esc_html_e( 'You see a final summary of the users you selected. You must type DELETE before the delete button activates. The candidates are then deleted in chunks of 25 at a time via AJAX, with a progress bar.' ); ?>
			</li>
		</ol>

		<h3><?php esc_html_e( 'What is actually deleted' ); ?></h3>
		<p><?php esc_html_e( 'For each confirmed user the system runs WordPress\'s wp_delete_user() with NO reassign target. That means:' ); ?></p>
		<ul>
			<li><?php esc_html_e( 'The user row itself (wp_users) is removed.' ); ?></li>
			<li><?php esc_html_e( 'All of their user_meta rows (wp_usermeta) are removed.' ); ?></li>
			<li>
				<?php esc_html_e( 'Every post, abstract, page, attachment, and link they authored (anything in wp_posts/wp_links with post_author = their ID) is permanently deleted along with that post\'s meta and taxonomies.' ); ?>
			</li>
			<li><?php esc_html_e( 'Comments they posted are also removed.' ); ?></li>
		</ul>
		<p>
			<strong><?php esc_html_e( 'There is no soft-delete and no trash step.' ); ?></strong>
			<?php esc_html_e( 'Once the AJAX call returns, the content is gone from the database.' ); ?>
		</p>

		<h3><?php esc_html_e( 'Built-in safeguards' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Every AJAX endpoint requires both a valid WP nonce and the manage_options capability.' ); ?></li>
			<li><?php esc_html_e( 'Each user is re-validated server-side at delete time (admin role, lifetime flag, exclusion list, self-check) so a stale preview tab cannot trick the system into deleting a protected user.' ); ?></li>
			<li><?php esc_html_e( 'Before each deletion, a full snapshot of the user\'s user_meta is written to the Deletion Log table along with the timestamp, deleted login/email, and the admin who triggered the run. Visit the Deletion Log tab to review.' ); ?></li>
			<li><?php esc_html_e( 'The same delete event is also written to the PHP error log (visible via your hosting error log) when logging is enabled in Settings.' ); ?></li>
		</ul>

		<h3><?php esc_html_e( 'Recommended workflow for first-time use' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Pick a single membership type and cutoff year.' ); ?></li>
			<li><?php esc_html_e( 'Set Preview limit to 1.' ); ?></li>
			<li><?php esc_html_e( 'Run the full flow on that single user and verify the Deletion Log entry looks correct.' ); ?></li>
			<li><?php esc_html_e( 'Then increase the limit (or clear it for all candidates) for the real purge.' ); ?></li>
		</ol>
	</details>

	<form method="get" action="admin.php">
		<input type="hidden" name="page" value="<?php echo esc_attr( $plugin_admin_page ); ?>" />
		<input type="hidden" name="step" value="preview" />

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Membership type(s)' ); ?></th>
				<td>
					<?php if ( empty( $available_types ) ) : ?>
						<p><em><?php esc_html_e( 'No member_type values found in the database.' ); ?></em></p>
					<?php else : ?>
						<fieldset>
							<?php foreach ( $available_types as $type ) : ?>
								<label style="display:inline-block; margin-right:14px;">
									<input type="checkbox" name="member_types[]" value="<?php echo esc_attr( $type ); ?>" />
									<?php echo esc_html( $type ); ?>
								</label>
							<?php endforeach; ?>
						</fieldset>
						<p class="description">
							<?php esc_html_e( 'Select one or more. Typically Student; check Member as well to extend the purge.' ); ?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="cutoff_year"><?php esc_html_e( 'Last paid year is at or before' ); ?></label>
				</th>
				<td>
					<input type="number"
					       id="cutoff_year"
					       name="cutoff_year"
					       min="2000"
					       max="<?php echo esc_attr( $current_year ); ?>"
					       step="1"
					       value="<?php echo esc_attr( $current_year - 4 ); ?>"
					       required />
					<p class="description">
						<?php esc_html_e( 'A user is a candidate if their most recent membership_paid_YYYY = 1 row is at or before this year. Users who never paid are NOT candidates.' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="preview_limit"><?php esc_html_e( 'Preview limit (optional)' ); ?></label>
				</th>
				<td>
					<input type="number"
					       id="preview_limit"
					       name="preview_limit"
					       min="0"
					       step="1"
					       placeholder="<?php esc_attr_e( 'all' ); ?>" />
					<p class="description">
						<?php esc_html_e( 'For testing: cap the preview at this many candidates. Leave blank or 0 to load all matching users.' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Preview Candidates' ); ?></button>
		</p>
	</form>
</div>
