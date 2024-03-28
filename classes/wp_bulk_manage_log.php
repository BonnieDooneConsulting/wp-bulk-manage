<?php

class wp_bulk_manage_log {

	/**
	 * Boolean to indicate if logging is enabled or disabled
	 *
	 * @var boolean
	 */
	public bool $enabled = false;

	/**
	 *    Constructor
	 *
	 **/
	function __construct() {
		if ( defined( 'WP_BULK_MANAGE_LOG_ENABLED' ) && WP_BULK_MANAGE_LOG_ENABLED == 1 ) {
			$this->enabled = true;
		}
	}

	/**
	 *    __call() Magic method to call log methods depending on which named function was called.
	 *
	 * @param string $method The name of the nonexistent method called
	 * @param array  $args   [0 => 'A text message describing the event', 1 => $object An object or array of data to
	 *                       output to the log] An array of the arguments passed to the method
	 *
	 * @return void
	 **/
	public function __call( string $method, array $args = array() ): void {

		if ( ! in_array( strtolower( $method ),
			array( 'debug', 'info', 'notice', 'warn', 'error', 'crit', 'emerg' ) ) ) {
			return;
		}

		$method = ucwords( $method );

		if ( $this->enabled == true ) {
			// Ensure we don't log info that have 'password' in it
			if ( ! str_contains( $args[0], 'password' ) ) {
				error_log( '*** WP Bulk Manage Log: ' . $method . ': ' . $args[0] );

				if ( isset( $args[1] ) ) {
					error_log( print_r( $args[1], true ) );
				}
			}
		}
	}
}
