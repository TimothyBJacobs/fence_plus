<?php
/**
 *
 * @package Fence Plus
 * @subpackage Notifications
 * @since 0.1
 */

if ( ! class_exists( 'IBD_Admin_Notification_Factory' ) ) :
	require_once 'interface-notification-factory.php';
	require_once 'class-admin-notification.php';

	class IBD_Admin_Notification_Factory implements IBD_Notification_Factory {
		/**
		 * @param $user_id int
		 * @param $title string
		 * @param $message string
		 * @param $args array
		 *
		 * @return IBD_Notification
		 */
		public function make( $user_id, $title, $message, $args = array() ) {
			return new IBD_Admin_Notification( $user_id, $title, $message, $args );
		}

	}

endif;