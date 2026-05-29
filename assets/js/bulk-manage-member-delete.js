/* global jQuery, wp_bulk_manage */
(function ($) {
	'use strict';

	var CHUNK_SIZE = 25;

	$(function () {
		bindPreviewTable();
		bindExclusionsTable();
		bindConfirmFlow();
		bindClearDeletionLog();
	});

	/* ---------- Preview (step 2) ---------- */

	function bindPreviewTable() {
		var $table = $('#candidate-table');
		if (!$table.length) return;

		updateSelectedCount();

		$table.on('change', '#select-all-candidates', function () {
			var checked = this.checked;
			$table.find('.candidate-checkbox').prop('checked', checked);
			updateSelectedCount();
		});

		$table.on('change', '.candidate-checkbox', function () {
			updateSelectedCount();
		});

		$table.on('click', '.exclude-permanently-btn', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var userId = parseInt($btn.data('user-id'), 10);
			if (!userId) return;

			if (!window.confirm('Permanently exclude user #' + userId + ' from all future bulk-delete runs?')) {
				return;
			}

			$btn.prop('disabled', true).text('Excluding…');

			$.post(wp_bulk_manage.ajaxurl, {
				action:   'bulk_member_exclude',
				user_id:  userId,
				_wpnonce: wp_bulk_manage.nonce
			})
				.done(function (response) {
					if (response && response.success) {
						$btn.closest('tr').fadeOut(200, function () {
							$(this).remove();
							updateSelectedCount();
						});
					} else {
						alert('Failed to exclude user: ' + (response && response.data && response.data.message ? response.data.message : 'unknown error'));
						$btn.prop('disabled', false).text('Exclude permanently');
					}
				})
				.fail(function () {
					alert('Network error excluding user.');
					$btn.prop('disabled', false).text('Exclude permanently');
				});
		});
	}

	function updateSelectedCount() {
		var $checked = $('#candidate-table .candidate-checkbox:checked');
		$('#selected-count').text($checked.length);
	}

	/* ---------- Protected Users page ---------- */

	function bindExclusionsTable() {
		var $rows = $('.bulk-manage-exclusions');
		if (!$rows.length) return;

		$rows.on('click', '.unexclude-btn', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var userId = parseInt($btn.data('user-id'), 10);
			if (!userId) return;

			$btn.prop('disabled', true).text('Removing…');

			$.post(wp_bulk_manage.ajaxurl, {
				action:   'bulk_member_unexclude',
				user_id:  userId,
				_wpnonce: wp_bulk_manage.nonce
			})
				.done(function (response) {
					if (response && response.success) {
						$btn.closest('tr').fadeOut(200, function () { $(this).remove(); });
					} else {
						alert('Failed to remove protection.');
						$btn.prop('disabled', false).text('Remove protection');
					}
				})
				.fail(function () {
					alert('Network error.');
					$btn.prop('disabled', false).text('Remove protection');
				});
		});
	}

	/* ---------- Confirmation (step 3) ---------- */

	function bindConfirmFlow() {
		var $form = $('#bulk-delete-confirm-form');
		if (!$form.length) return;

		var $input  = $('#delete-confirmation-text');
		var $submit = $('#bulk-delete-submit');

		$input.on('input', function () {
			$submit.prop('disabled', $input.val().trim().toUpperCase() !== 'DELETE');
		});

		$submit.on('click', function (e) {
			e.preventDefault();
			if ($input.val().trim().toUpperCase() !== 'DELETE') return;

			var userIds = $form.find('input[name="user_ids[]"]').map(function () {
				return parseInt(this.value, 10);
			}).get().filter(function (n) { return n > 0; });

			if (!userIds.length) return;

			$submit.prop('disabled', true);
			$input.prop('disabled', true);
			$('.bulk-manage-member-delete-confirm .notice-error').hide();
			$('#bulk-delete-progress').show();

			runChunkedDelete(userIds);
		});
	}

	function runChunkedDelete(userIds) {
		var total       = userIds.length;
		var processed   = 0;
		var totalDeleted = 0;
		var totalFailed  = [];
		var totalSkipped = [];
		var chunks       = chunkArray(userIds, CHUNK_SIZE);

		function next(i) {
			if (i >= chunks.length) {
				renderCompletion(totalDeleted, totalFailed, totalSkipped, total);
				return;
			}
			var chunk = chunks[i];
			$.post(wp_bulk_manage.ajaxurl, {
				action:   'bulk_member_delete_chunk',
				user_ids: chunk,
				_wpnonce: wp_bulk_manage.nonce
			})
				.done(function (response) {
					if (response && response.success && response.data) {
						totalDeleted += response.data.deleted || 0;
						if (response.data.failed && response.data.failed.length) {
							totalFailed = totalFailed.concat(response.data.failed);
						}
						if (response.data.skipped && response.data.skipped.length) {
							totalSkipped = totalSkipped.concat(response.data.skipped);
						}
					} else {
						totalFailed = totalFailed.concat(chunk);
					}
					processed += chunk.length;
					updateProgress(processed, total, totalDeleted, totalFailed.length, totalSkipped.length);
					next(i + 1);
				})
				.fail(function () {
					totalFailed = totalFailed.concat(chunk);
					processed += chunk.length;
					updateProgress(processed, total, totalDeleted, totalFailed.length, totalSkipped.length);
					next(i + 1);
				});
		}
		next(0);
	}

	function updateProgress(processed, total, deleted, failedCount, skippedCount) {
		$('#bulk-delete-progress-bar').attr('value', processed);
		var status = 'Processed ' + processed + ' of ' + total +
			' (deleted: ' + deleted +
			', failed: ' + failedCount +
			', skipped: ' + skippedCount + ')';
		$('#bulk-delete-progress-status').text(status);
	}

	function renderCompletion(deleted, failed, skipped, total) {
		var msg = 'Deleted ' + deleted + ' of ' + total + ' users.';
		if (failed.length) msg += ' Failed: ' + failed.length + ' (IDs: ' + failed.join(', ') + ').';
		if (skipped.length) msg += ' Skipped (no longer safe): ' + skipped.length + ' (IDs: ' + skipped.join(', ') + ').';
		$('#bulk-delete-result-summary').text(msg);

		// Collapse the user list down to just its headers, leave the rest of the page visible.
		$('.bulk-manage-member-delete-confirm table.wp-list-table tbody').hide();

		var $banner = $('#bulk-delete-complete').show();

		// Scroll so the Step 3 title (the immediately-preceding H2) sits at the top of the viewport,
		// which puts the Done banner right under it.
		var $title = $('.bulk-manage-member-delete-confirm h2').first();
		var $target = $title.length ? $title : $banner;
		var offset = $target.offset() ? $target.offset().top - 40 : 0;
		$('html, body').animate({ scrollTop: Math.max(0, offset) }, 200);
	}

	/* ---------- Deletion Log page ---------- */

	function bindClearDeletionLog() {
		var $btn = $('#clear-deletion-log-btn');
		if (!$btn.length) return;

		$btn.on('click', function (e) {
			e.preventDefault();
			if (!window.confirm('Permanently clear ALL deletion log entries? This cannot be undone.')) {
				return;
			}
			$btn.prop('disabled', true).text('Clearing…');

			$.post(wp_bulk_manage.ajaxurl, {
				action:   'bulk_member_clear_log',
				_wpnonce: wp_bulk_manage.nonce
			})
				.done(function (response) {
					if (response && response.success) {
						window.location.reload();
					} else {
						alert('Failed to clear log: ' + (response && response.data && response.data.message ? response.data.message : 'unknown error'));
						$btn.prop('disabled', false).text('Clear Deletion Log');
					}
				})
				.fail(function () {
					alert('Network error clearing log.');
					$btn.prop('disabled', false).text('Clear Deletion Log');
				});
		});
	}

	function chunkArray(arr, size) {
		var chunks = [];
		for (var i = 0; i < arr.length; i += size) {
			chunks.push(arr.slice(i, i + size));
		}
		return chunks;
	}
})(jQuery);
