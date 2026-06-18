# WP Bulk Manage

A WordPress admin plugin for managing users in bulk. It provides a guided,
safety-first workflow for deleting members, plus tools for exporting users,
protecting accounts from deletion, and auditing what was removed.

All features live under a **Bulk Manage** menu in the WordPress admin and
require the `manage_options` capability.

## Features

### Bulk Delete Members
A multi-step flow designed to prevent accidental deletions:

1. **Filter** — choose which members to target by member type and a cutoff year.
2. **Preview** — review the matching candidates (with a link to each user's
   profile) and select exactly which accounts to delete.
3. **Confirm** — see a final summary of the selected accounts before the
   deletion runs.

Nonce checks guard each step; if a confirmation expires (e.g. from a page
refresh or back/forward navigation) you are routed back to the filter step
with a notice rather than hitting a hard error.

### User Export
Export users to CSV from the admin. Generated export files are cleaned up
automatically via a daily scheduled task (`bulk_manage_export_cleanup`).

### Protected Users
Maintain a permanent exclusion list of accounts that should never be deleted.
Protected users are kept out of the bulk-delete candidate results.

### Deletion Log
Every bulk deletion is recorded to a database table so removals can be audited
after the fact. The log page shows the most recent entries.

## Requirements

- WordPress with PHP 7.4+ (typed properties are used throughout).
- A user with the `manage_options` capability to access the admin pages.

## Installation

1. Copy the `wp-bulk-manage` directory into `wp-content/plugins/`.
2. Activate **WP Bulk Manage** from the Plugins screen. Activation creates the
   deletion-log table and schedules the daily export cleanup.

## Author

Bonnie Doone Consulting, LLC — https://bonniedoone.ai
