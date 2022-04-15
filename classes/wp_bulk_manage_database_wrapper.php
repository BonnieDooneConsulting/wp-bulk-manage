<?php

class wp_bulk_manage_database_wrapper {

    public function __construct() {
        global $table_prefix, $wpdb;

        $this->wpdb                    = $wpdb;
        $this->table_prefix            = $table_prefix;
    }

    /**
     * Method to wrap the Wordpress add_option function
     *
     * @param string $option A string value for the option to write
     * @param string $value A Value for the option to save
     * @param boolean $deprecated It's deprecated, check the wordpress docs
     * @param boolean $autoload Check the wordpress docs
     * @return mixed
     */
    public function add_option($option, $value ='', $deprecated = null, $autoload = null){
        return add_option($option, $value, $deprecated, $autoload);
    }

    /**
     * Method to wrap the Wordpress get_option function
     *
     * @param string $option A string value for the option to write
     * @param string/obj/array $default a default value to return if none is found
     * @return mixed
     */
    public function get_option($option, $default = null){
        return get_option($option, $default);
    }

    /**
     * Method to wrap the Wordpress update_option function
     *
     * @param string $option A string value for the option to write
     * @param string/obj/array $newvalue A Value for the option to save
     * @return mixed
     */
    public function update_option($option, $new_value){
        return update_option($option, $new_value);
    }

    /**
     * Method to wrap the Wordpress delete_option function
     *
     * @param string $option A string value for wordpress to delete
     * @return mixed
     */
    public function delete_option($option){
        return delete_option($option);
    }
}

