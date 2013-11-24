<?php
/**
 *
 * @package Notify
 * @subpackage Admin
 * @since 0.1
 */

if ( ! class_exists( 'IBD_Notify_Admin_Factory' ) ) :

	class IBD_Notify_Admin_Factory implements IBD_Notify_Factory {
		/**
		 * @param $user_id int
		 * @param $title string
		 * @param $message string
		 * @param $args array
		 *
		 * @return IBD_Notify_Admin_Notification
		 */
		public function make( $user_id, $title, $message, $args = array() ) {
			return new IBD_Notify_Admin_Notification( $user_id, $title, $message, $args );
		}

	}

endif;