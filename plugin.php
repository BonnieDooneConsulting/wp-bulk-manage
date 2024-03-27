<?php
/*
	Plugin Name: WP Bulk Manage
	Plugin URI: https://github.com/BonnieDooneConsulting/wp-bulk-manage
	Description: Provides a simple interface to upload a csv of users id's and delete them
	Author: Bonnie Doone Consulting, LLC
	Version: 0.9
	Text Domain: wp-bulk-manage
 */

require_once('classes/wp_bulk_manage_base.php');
require_once('classes/wp_bulk_manage_user_export.php');

/**
 * Main plugin class
 */
class wp_bulk_manage_plugin {

    /**
     * A representation of general plugin functionalities and settings
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

        add_action('admin_init', array($this, 'admin_initialize'));
        add_action('admin_menu', array($this, 'initialize_menu'));

        register_activation_hook( __FILE__, array($this, 'activation' ));
        register_deactivation_hook(__FILE__, array($this, 'deactivation'));

        $this->_wordpress_hooks();
    }

	/**
	 * @method initialize_menu
	 *
	 * @return void
	 * @author awilson
	 */
    public function initialize_menu() {
        add_menu_page(
            __('Bulk Manage Admin'),
            __('Bulk Manage Admin'),
            'manage_options',
            $this->base->plugin_admin_page,
            array($this, 'admin_page'),
            plugin_dir_url(__FILE__) . 'assets/img/favicon-16x16.png',
            98);
        add_submenu_page(
            $this->base->plugin_admin_page,
            __('Manage Users'),
            __('Manage Users'),
            'manage_options',
            $this->base->user_management_admin_page,
            array($this, 'admin_users_page'));
	    add_submenu_page(
		    $this->base->plugin_admin_page,
		    __('User Export'),
		    __('User Export'),
		    'manage_options',
		    $this->base->user_export_admin_page,
		    array($this, 'admin_users_export_page'));
    }

	/**
	 * @method admin_initialize
	 *
	 * @return void
	 * @author awilson
	 */
    public function admin_initialize(){
        register_setting($this->base->config_name . '_group', $this->base->config_name, array($this->base, '_sanitize_option_input'));
        register_setting($this->base->users_config_name . '_group', $this->base->users_config_name, array($this->base, '_sanitize_option_input'));
    }

    /**
     *  Handler for Admin pages
     *
     * @return void
     */
    public function admin_page() {
        $this->check_privilege();

        $content = array(
            'menuItems' => apply_filters('wp_bulk_manage_admin_menu', array() ),
            'plugin_admin_page' => $this->base->plugin_admin_page,
            'config' => $this->base->config,
            'config_name' => $this->base->config_name
        );

        $this->base->view->load('admin_header', $content);
        $this->base->view->load('admin_base_form', $content);
        $this->base->view->load('admin_footer');
    }

    /**
     * Handler for users admin page
     *
     * @return void
     */
    public function admin_users_page() {
        $this->check_privilege();

        $content = array(
            'menuItems' => apply_filters('wp_bulk_manage_admin_menu', array() ),
            'plugin_admin_page' => $this->base->user_management_admin_page,
            'config' => $this->base->user_export_config,
            'config_name' => $this->base->user_export_config_name
        );

        $this->base->view->load('admin_header', $content);
        $this->base->view->load('admin_users_form', $content);
        $this->base->view->load('admin_footer');
    }

	/**
	 * @method admin_users_export_page
	 *
	 * @return void
	 * @author awilson
	 */
	public function admin_users_export_page() {
		$this->check_privilege();

		$content = array(
			'menuItems' => apply_filters('wp_bulk_manage_admin_menu', array() ),
			'plugin_admin_page' => $this->base->user_export_admin_page,
			'config' => $this->base->user_export_config,
			'config_name' => $this->base->users_config_name
		);

		$this->base->view->load('admin_header', $content);
		$this->base->view->load('admin_users_export_form', $content);
		$this->base->view->load('admin_footer');
	}

    public function activation() {
		// add cron to clean up old user exports
    }

    public function deactivation() {

    }

    /**
     * Enqueue some admin scripts for users page
     *
     * @param $hook
     */
    public function _admin_enqueue($hook) {
        //TODO: fix the page name here
        $hooks = [
			'bulk-manage-admin_page_wp-bulk-manage-user-export-settings',
	        'bulk-manage-admin_page_wp-bulk-manage-user-settings',
	        ];

        if (in_array($hook, $hooks)) {
            wp_enqueue_script('wp_bulk_manage_users_settings', plugin_dir_url( __FILE__ ) . 'assets/js/bulk-manage-users.js', array('jquery'));
            wp_localize_script('wp_bulk_manage_users_settings', 'wp_bulk_manage', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
            wp_enqueue_style('wp_bulk_manage_users_css', plugin_dir_url( __FILE__ ) . 'assets/css/bulk-manage-users.css');
        }
    }

	/**
	 * @method _wordpress_hooks
	 *
	 * @return void
	 * @author awilson
	 */
    private function _wordpress_hooks() {
        add_action( 'admin_enqueue_scripts', array($this, '_admin_enqueue'));
	    add_action( 'wp_ajax_export_users', array($this->base->user_export, 'export_users'));
	    add_action( 'wp_ajax_download_user_export', array($this->base->user_export, 'download_user_export'));
	    add_action( 'wp_ajax_delete_user_upload', array($this->base->user_manage, 'delete_user_upload'));
    }

	/**
	 * @method check_privilege
	 *
	 * @return void
	 * @author awilson
	 */
    private function check_privilege() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
    }
}

$orphan = new wp_bulk_manage_plugin();
