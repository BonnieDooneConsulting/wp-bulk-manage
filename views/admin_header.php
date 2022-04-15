<div class="wrap">
<h2><?php _e('WP Bulk Manage'); ?></h2>

<?php
$get_settings_updated = ( ! empty( $_GET[ 'settings-updated' ] ) ? sanitize_text_field( $_GET[ 'settings-updated' ] ) : '' );
$get_page = ( ! empty( $_GET[ 'page' ] ) ? sanitize_text_field( $_GET[ 'page' ] ) : '' );

if($get_settings_updated && $get_settings_updated == 'true'): ?>
	<div id="setting-error-settings_updated" class="updated settings-error"> 
		<p><strong><?php _e('Settings Saved'); ?></strong></p>
	</div>
<?php endif; ?>

<?php
if ( is_array( $content['menuItems'] ) ) {
	?>
	<h2 class="nav-tab-wrapper">
		<?php foreach($content['menuItems'] as $item): ?>
			<a href="admin.php?page=<?php echo $item['page']; ?>" class="nav-tab <?php if ($get_page == $item['page']) echo 'nav-tab-active'; ?>">
				<?php echo $item['title']; ?>
			</a>
		<?php endforeach; ?>
	</h2>
	<?php
}
?>
