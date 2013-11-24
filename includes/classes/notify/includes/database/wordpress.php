<?php
/**
 *
 * @package Notifications
 * @subpackage Database
 * @since 0.3
 */
if ( ! class_exists( 'IBD_Notify_Database_WordPress' ) ) :

	class IBD_Notify_Database_WordPress implements IBD_Notify_Database {
		/**
		 * @param $option_name string
		 * @param $default mixed value to be returned if option is not set
		 *
		 * @return mixed
		 */
		public function get_option( $option_name, $default = "" ) {
			return get_option( $option_name, $default );
		}

		/**
		 * @param $option_name string
		 * @param $option_values mixed
		 *
		 * @return boolean
		 */
		public function update_option( $option_name, $option_values ) {
			update_option( $option_name, $option_values );
		}

		/**
		 * @param $option_name string
		 *
		 * @return boolean
		 */
		public function delete_option( $option_name ) {
			delete_option( $option_name );
		}

	}

endif;