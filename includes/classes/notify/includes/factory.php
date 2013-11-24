<?php
/**
 *
 * @package Notify
 * @subpackage Includes
 * @since 0.1
 */

if ( ! interface_exists( 'IBD_Notify_Factory' ) ) :

	/**
	 * Interface IBD_Notify_Factory
	 */
	interface IBD_Notify_Factory {
		/**
		 * @param $user_id int
		 * @param $title string
		 * @param $message string
		 * @param $args array
		 *
		 * @return IBD_Notify_Notification
		 */
		public function make( $user_id, $title, $message, $args = array() );
	}


endif;