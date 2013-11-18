<?php
/**
 *
 * @package Fence Plus
 * @subpackage Notifications
 * @since 0.1
 */

if ( ! interface_exists( 'IBD_Notification_Factory' ) ) :

	/**
	 * Interface IBD_Notification_Factory
	 */
	interface IBD_Notification_Factory {
		/**
		 * @param $user_id int
		 * @param $title string
		 * @param $message string
		 * @param $args array
		 *
		 * @return IBD_Notification
		 */
		public function make( $user_id, $title, $message, $args = array() );
	}


endif;