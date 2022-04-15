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
     * @return void
     */
    public function initialize_menu() {
        add_menu_page(
            __('Bulk Manage Admin'),
            __('Bulk Manage'),
            'manage_options',
            $this->base->plugin_admin_page,
            array($this, 'admin_page'),
            plugin_dir_url(__FILE__) . 'assets/img/wp-bulk-favicon.png',
            98);
        add_submenu_page(
            $this->base->plugin_admin_page,
            __('User Settings'),
            __('User Settings'),
            'manage_options',
            $this->base->users_admin_page,
            array($this, 'admin_users_page'));
    }

    /**
     *  Function for admin init stuff
     *  @param void
     *  @return void
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
            'plugin_admin_page' => $this->base->users_admin_page,
            'config' => $this->base->users_config,
            'config_name' => $this->base->users_config_name
        );

        $this->base->view->load('admin_header', $content);
        $this->base->view->load('admin_users_form', $content);
        $this->base->view->load('admin_footer');
    }

    public function activation() {

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
        $hooks = array('wp-bulk-manage-users-settings');
        if (in_array($hook, $hooks)) {
            wp_enqueue_script('wp_bulk_manage_users_settings', plugin_dir_url( __FILE__ ) . 'assets/js/bulk-manage-users.js', array('jquery'));
            wp_localize_script('wp_bulk_manage_users_settings', 'wp_bulk_manage', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
            wp_enqueue_style('wp_bulk_manage_users_css', plugin_dir_url( __FILE__ ) . 'assets/css/bulk-manage-users.css');
        }
    }

    /**
     * @return void
     */
    private function _wordpress_hooks() {
        add_action( 'admin_enqueue_scripts', array($this, '_admin_enqueue'));
    }

    /**
     * @return void
     */
    private function check_privilege() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
    }
}

$orphan = new wp_bulk_manage_plugin();
