<?php
/*
	Plugin Name: WP Bulk Manage
	Plugin URI: https://bonniedoone.ai
	Description: Provides a simple interface to upload a csv of users id's and delete them
	Author: Bonnie Doone Consulting, LLC
	Version: 1.0.0
	Text Domain: wp-bulk-manage
 */

require_once( 'classes/wp_bulk_manage_base.php' );
require_once( 'classes/wp_bulk_manage_user_export.php' );

/**
 * Main plugin class
 */
class wp_bulk_manage_plugin {

	/**
	 * A representation of general plugin functionalities and settings
	 *
	 * @var wp_bulk_manage_base $base
	 */
	protected wp_bulk_manage_base $base;

	/**
	 * @var string $plugin_basename
	 */
	protected string $plugin_basename;

	/**
	 * Set up a few plugin configurations and classes
	 */
	public function __construct() {
		$this->base            = wp_bulk_manage_base::get_instance();
		$this->plugin_basename = plugin_basename( __FILE__ );

		add_action( 'admin_init', array( $this, 'admin_initialize' ) );
		add_action( 'admin_menu', array( $this, 'initialize_menu' ) );

		register_activation_hook( __FILE__, array( $this, 'activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );

		$this->_wordpress_hooks();
	}

	/**
	 * @method initialize_menu
	 *
	 * @return void
	 * @author awilson
	 */
	public function initialize_menu(): void {
		add_menu_page(
			__( 'Bulk Manage' ),
			__( 'Bulk Manage' ),
			'manage_options',
			$this->base->plugin_admin_page,
			array( $this, 'admin_page' ),
			plugin_dir_url( __FILE__ ) . 'assets/img/favicon-16x16.png',
			98 );
		add_submenu_page(
			$this->base->plugin_admin_page,
			__( 'Bulk Delete Members' ),
			__( 'Bulk Delete Members' ),
			'manage_options',
			$this->base->user_management_admin_page,
			array( $this, 'admin_users_page' ) );
		add_submenu_page(
			$this->base->plugin_admin_page,
			__( 'User Export' ),
			__( 'User Export' ),
			'manage_options',
			$this->base->user_export_admin_page,
			array( $this, 'admin_users_export_page' ) );
		add_submenu_page(
			$this->base->plugin_admin_page,
			__( 'Protected Users' ),
			__( 'Protected Users' ),
			'manage_options',
			$this->base->exclusions_admin_page,
			array( $this, 'admin_exclusions_page' ) );
		add_submenu_page(
			$this->base->plugin_admin_page,
			__( 'Deletion Log' ),
			__( 'Deletion Log' ),
			'manage_options',
			$this->base->deletion_log_admin_page,
			array( $this, 'admin_deletion_log_page' ) );
	}

	/**
	 * @method admin_initialize
	 *
	 * @return void
	 * @author awilson
	 */
	public function admin_initialize(): void {
		register_setting( $this->base->config_name . '_group',
			$this->base->config_name,
			array( $this->base, '_sanitize_option_input' ) );
		register_setting( $this->base->users_config_name . '_group',
			$this->base->users_config_name,
			array( $this->base, '_sanitize_option_input' ) );
	}

	/**
	 *  Handler for Admin pages
	 *
	 * @return void
	 */
	public function admin_page(): void {
		$this->check_privilege();

		$content = array(
			'menuItems'         => apply_filters( 'wp_bulk_manage_admin_menu', array() ),
			'plugin_admin_page' => $this->base->plugin_admin_page,
			'config'            => $this->base->config,
			'config_name'       => $this->base->config_name
		);

		$this->base->view->load( 'admin_header', $content );
		$this->base->view->load( 'admin_base_form', $content );
		$this->base->view->load( 'admin_footer' );
	}

	/**
	 * Handler for the Bulk Delete Members multi-step flow.
	 * Step is driven by ?step= query arg: filter (default) -> preview -> confirm
	 *
	 * @return void
	 */
	public function admin_users_page(): void {
		$this->check_privilege();

		$step = isset( $_REQUEST['step'] ) ? sanitize_key( $_REQUEST['step'] ) : 'filter';

		// If the user refreshes the confirm page (or hits its URL directly) the original
		// POST/nonce won't be present. Fall back to the filter step with a notice instead
		// of dying with "Security check failed."
		$expired = false;
		if ( $step === 'confirm' ) {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wp_bulk_manage' ) ) {
				$step    = 'filter';
				$expired = true;
			}
		}

		$content = array(
			'menuItems'         => apply_filters( 'wp_bulk_manage_admin_menu', array() ),
			'plugin_admin_page' => $this->base->user_management_admin_page,
			'nonce'             => wp_create_nonce( 'wp_bulk_manage' ),
		);

		$this->base->view->load( 'admin_header', $content );

		if ( $expired ) {
			echo '<div class="notice notice-warning inline"><p>'
			     . esc_html__( 'Your previous confirmation expired (likely from a page refresh or back/forward navigation). Please re-run the filter to start a new delete.' )
			     . '</p></div>';
		}

		switch ( $step ) {
			case 'preview':
				$member_types  = isset( $_REQUEST['member_types'] ) && is_array( $_REQUEST['member_types'] )
					? array_map( 'sanitize_text_field', wp_unslash( $_REQUEST['member_types'] ) )
					: array();
				$cutoff_year   = isset( $_REQUEST['cutoff_year'] ) ? (int) $_REQUEST['cutoff_year'] : 0;
				$preview_limit = isset( $_REQUEST['preview_limit'] ) ? max( 0, (int) $_REQUEST['preview_limit'] ) : 0;

				$content['member_types']  = $member_types;
				$content['cutoff_year']   = $cutoff_year;
				$content['preview_limit'] = $preview_limit;
				$content['candidates']    = ( ! empty( $member_types ) && $cutoff_year > 0 )
					? $this->base->member_query->get_candidates( $member_types, $cutoff_year, $preview_limit )
					: array();

				$this->base->view->load( 'admin_member_delete_preview', $content );
				break;

			case 'confirm':
				// Nonce already verified above; failure would have rerouted to the filter step.
				$selected_ids = isset( $_POST['user_ids'] ) && is_array( $_POST['user_ids'] )
					? array_filter( array_map( 'intval', $_POST['user_ids'] ) )
					: array();

				$summaries = array();
				foreach ( $selected_ids as $uid ) {
					$summary = $this->base->member_query->get_user_summary( $uid );
					if ( $summary ) {
						$summaries[] = $summary;
					}
				}

				$content['selected_summaries'] = $summaries;
				$content['member_types']       = isset( $_POST['member_types'] ) && is_array( $_POST['member_types'] )
					? array_map( 'sanitize_text_field', wp_unslash( $_POST['member_types'] ) )
					: array();
				$content['cutoff_year']        = isset( $_POST['cutoff_year'] ) ? (int) $_POST['cutoff_year'] : 0;

				$this->base->view->load( 'admin_member_delete_confirm', $content );
				break;

			case 'filter':
			default:
				$content['available_types'] = $this->base->member_query->get_distinct_member_types();
				$this->base->view->load( 'admin_member_delete_filter', $content );
				break;
		}

		$this->base->view->load( 'admin_footer' );
	}

	/**
	 * Handler for the Protected Users (permanent exclusion list) page.
	 *
	 * @return void
	 */
	public function admin_exclusions_page(): void {
		$this->check_privilege();

		$content = array(
			'menuItems'         => apply_filters( 'wp_bulk_manage_admin_menu', array() ),
			'plugin_admin_page' => $this->base->exclusions_admin_page,
			'nonce'             => wp_create_nonce( 'wp_bulk_manage' ),
			'excluded_users'    => $this->base->exclusions->get_all(),
		);

		$this->base->view->load( 'admin_header', $content );
		$this->base->view->load( 'admin_member_exclusions', $content );
		$this->base->view->load( 'admin_footer' );
	}

	/**
	 * Handler for the Deletion Log page.
	 *
	 * @return void
	 */
	public function admin_deletion_log_page(): void {
		$this->check_privilege();

		$content = array(
			'menuItems'         => apply_filters( 'wp_bulk_manage_admin_menu', array() ),
			'plugin_admin_page' => $this->base->deletion_log_admin_page,
			'entries'           => $this->base->deletion_log->get_recent( 200 ),
		);

		$this->base->view->load( 'admin_header', $content );
		$this->base->view->load( 'admin_deletion_log', $content );
		$this->base->view->load( 'admin_footer' );
	}

	/**
	 * @method admin_users_export_page
	 *
	 * @return void
	 * @author awilson
	 */
	public function admin_users_export_page(): void {
		$this->check_privilege();

		$content = array(
			'menuItems'         => apply_filters( 'wp_bulk_manage_admin_menu', array() ),
			'plugin_admin_page' => $this->base->user_export_admin_page,
			'config'            => $this->base->user_export_config,
			'config_name'       => $this->base->users_config_name
		);

		$this->base->view->load( 'admin_header', $content );
		$this->base->view->load( 'admin_users_export_form', $content );
		$this->base->view->load( 'admin_footer' );
	}

	public function activation(): void {
		if ( ! wp_next_scheduled( 'bulk_manage_export_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'bulk_manage_export_cleanup' );
		}
		wp_bulk_manage_deletion_log::install_table();
	}

	public function deactivation(): void {
	}

	/**
	 * Enqueue some admin scripts for users page
	 *
	 * @param $hook
	 */
	public function _admin_enqueue( $hook ): void {
		// Export page keeps its original assets.
		$export_hooks = [
			'bulk-manage_page_wp-bulk-manage-user-export-settings',
			'bulk-manage-admin_page_wp-bulk-manage-user-export-settings',
		];
		if ( in_array( $hook, $export_hooks, true ) ) {
			wp_enqueue_script( 'wp_bulk_manage_users_settings',
				plugin_dir_url( __FILE__ ) . 'assets/js/bulk-manage-users.js',
				array( 'jquery' ) );
			wp_localize_script( 'wp_bulk_manage_users_settings',
				'wp_bulk_manage',
				array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
			wp_enqueue_style( 'wp_bulk_manage_users_css',
				plugin_dir_url( __FILE__ ) . 'assets/css/bulk-manage-users.css' );

			return;
		}

		// Bulk-delete flow + protected users + deletion log share new assets.
		$delete_hooks = [
			'bulk-manage_page_wp-bulk-manage-user-settings',
			'bulk-manage-admin_page_wp-bulk-manage-user-settings',
			'bulk-manage_page_wp-bulk-manage-exclusions-settings',
			'bulk-manage-admin_page_wp-bulk-manage-exclusions-settings',
			'bulk-manage_page_wp-bulk-manage-deletion-log',
			'bulk-manage-admin_page_wp-bulk-manage-deletion-log',
		];
		if ( in_array( $hook, $delete_hooks, true ) ) {
			wp_enqueue_script( 'wp_bulk_manage_member_delete',
				plugin_dir_url( __FILE__ ) . 'assets/js/bulk-manage-member-delete.js',
				array( 'jquery' ) );
			wp_localize_script( 'wp_bulk_manage_member_delete', 'wp_bulk_manage', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wp_bulk_manage' ),
			) );
			wp_enqueue_style( 'wp_bulk_manage_member_delete_css',
				plugin_dir_url( __FILE__ ) . 'assets/css/bulk-manage-member-delete.css' );
		}
	}

	/**
	 * @method _wordpress_hooks
	 *
	 * @return void
	 * @author awilson
	 */
	private function _wordpress_hooks(): void {
		add_action( 'admin_enqueue_scripts', array( $this, '_admin_enqueue' ) );
		add_action( 'wp_ajax_export_users', array( $this->base->user_export, 'export_users' ) );
		add_action( 'wp_ajax_download_user_export', array( $this->base->user_export, 'download_user_export' ) );
		add_action( 'bulk_manage_export_cleanup', array( $this, 'clean_up_exports' ) );

		// Bulk-delete flow AJAX endpoints (all verify nonce + manage_options internally).
		add_action( 'wp_ajax_bulk_member_delete_chunk', array( $this->base->member_delete, 'delete_chunk' ) );
		add_action( 'wp_ajax_bulk_member_exclude', array( $this->base->exclusions, 'ajax_exclude' ) );
		add_action( 'wp_ajax_bulk_member_unexclude', array( $this->base->exclusions, 'ajax_unexclude' ) );
		add_action( 'wp_ajax_bulk_member_clear_log', array( $this->base->deletion_log, 'ajax_clear_log' ) );

		// Per-user-profile "Exempt from bulk deletion" checkbox.
		add_action( 'show_user_profile', array( $this->base->exclusions, 'render_profile_field' ) );
		add_action( 'edit_user_profile', array( $this->base->exclusions, 'render_profile_field' ) );
		add_action( 'personal_options_update', array( $this->base->exclusions, 'save_profile_field' ) );
		add_action( 'edit_user_profile_update', array( $this->base->exclusions, 'save_profile_field' ) );
	}

	/**
	 * @method clean_up_exports
	 *
	 * @return void
	 * @author awilson
	 */
	public function clean_up_exports(): void {
		$path = dirname( __FILE__ ) . "/downloads/";
		if ( $handle = opendir( $path ) ) {
			while ( false !== ( $file = readdir( $handle ) ) ) {
				if ( '.' === $file ) {
					continue;
				}
				if ( '..' === $file ) {
					continue;
				}
				if ( '.gitignore' === $file ) {
					continue;
				}
				unlink( $path . $file );
			}
			closedir( $handle );
		}
	}

	/**
	 * @method check_privilege
	 *
	 * @return void
	 * @author awilson
	 */
	private function check_privilege(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}
	}
}

$orphan = new wp_bulk_manage_plugin();
