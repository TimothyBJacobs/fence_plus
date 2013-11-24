<?php
/**
 *
 * @package Notifications
 * @subpackage
 * @since 0.3
 */

if ( ! interface_exists( 'IBD_Notify_Database' ) ) :

	/**
	 * Interface IBD_Notify_Database
	 */
	interface IBD_Notify_Database {
		/**
		 * @param $option_name string
		 * @param $default mixed value to be returned if option is not set
		 *
		 * @return mixed
		 */
		public function get_option( $option_name, $default = "" );

		/**
		 * @param $option_name string
		 * @param $option_values mixed
		 *
		 * @return boolean
		 */
		public function update_option( $option_name, $option_values );

		/**
		 * @param $option_name string
		 *
		 * @return boolean
		 */
		public function delete_option( $option_name );
	}

endif;