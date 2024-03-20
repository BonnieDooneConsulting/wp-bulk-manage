<?php

class wp_bulk_manage_base {

    public function __construct() {
        include_once('wp_bulk_manage_database_wrapper.php');
        include_once('wp_bulk_manage_log.php');
        include_once('wp_bulk_manage_view.php');

        $this->config_name       = 'wp_bulk_manage_base_config';
        $this->users_config_name = 'wp_bulk_manage_users_config';
	    $this->user_export_config_name = 'wp_bulk_manage_user_export_config';

        $this->wp                = new wp_bulk_manage_database_wrapper();

        $base_config        = parse_ini_file( dirname(__FILE__) . '/../base_config.ini');
        $this->config       = $this->wp->get_option($this->config_name, $base_config);
        $users_config       = parse_ini_file( dirname(__FILE__) . '/../users_config.ini');
        $this->users_config = $this->wp->get_option($this->users_config_name, $users_config);
		$user_export_config = parse_ini_file( dirname(__FILE__) . '/../user_export.ini');
	    $this->user_export_config = $this->wp->get_option($this->user_export_config_name, $user_export_config);

        $this->plugin_admin_page = 'wp-bulk-manage-settings';
        $this->users_admin_page  = 'wp-bulk-manage-user-settings';
		$this->user_export_admin_page = 'wp-bulk-manage-user-export-settings';

        if($this->config['logging'] == 1 && !defined('WP_BULK_MANAGE_LOG_ENABLED')){
            define('WP_BULK_MANAGE_LOG_ENABLED', $this->config['logging']);
        }

        $this->log  = new wp_bulk_manage_log();
        $this->view = new wp_bulk_manage_view($this->log);
		$this->user_export = new wp_bulk_manage_user_export($this->log);
        add_filter( 'wp_bulk_manage_admin_menu', array($this, 'add_tab_items'), 1, 1);

    }

    /**
     * Class: add_tab_items constructor.
     * @method add_tab_items
     * @param $menu
     * @return array
     */
    public function add_tab_items($menu){
        $menu[] = array('title' => __('Settings'), 'page' => $this->plugin_admin_page);
        $menu[] = array('title' => __('Bulk Manage Users'), 'page' => $this->users_admin_page);
	    $menu[] = array('title' => __('User Export'), 'page' => $this->user_export_admin_page);
        return $menu;
    }

    /**
     * Sanitize input from admin forms
     *
     * @param $input
     * @return mixed
     */
    public function _sanitize_option_input($input){

        //TODO: clean the input here
        $output = $input;
        return apply_filters( 'wp_bulk_manage_sanitize_option_input', $output, $input );
    }

    /**
     * This class follows a singleton pattern.
     * @return static
     */
    public static function get_instance() : wp_bulk_manage_base {

        static $instance = null;

        if (null === $instance) {
            $instance = new static();
        }

        return $instance;
    }

}
