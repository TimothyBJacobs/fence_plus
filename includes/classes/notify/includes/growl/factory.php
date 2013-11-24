<?php
/**
 *
 * @package Notify
 * @subpackage Growl
 * @since 0.2
 */

if ( ! class_exists( 'IBD_Notify_Growl_Factory' ) ) :

	class IBD_Notify_Growl_Factory implements IBD_Notify_Factory {
		/**
		 * @param $user_id int
		 * @param $title string
		 * @param $message string
		 * @param $args array
		 *
		 * @return IBD_Notify_Growl_Notification
		 */
		public function make( $user_id, $title, $message, $args = array() ) {
			return new IBD_Notify_Growl_Notification( $user_id, $title, $message, $args );
		}

	}

endif;