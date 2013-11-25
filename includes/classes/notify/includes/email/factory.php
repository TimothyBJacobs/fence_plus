<?php
/**
 *
 * @package Notify
 * @subpackage Email
 * @since 0.3
 */

if ( ! class_exists( 'IBD_Notify_Email_Factory' ) ) :

	class IBD_Notify_Email_Factory implements IBD_Notify_Factory {
		/**
		 * @param $user_id int
		 * @param $title string
		 * @param $message string
		 * @param $args array
		 *
		 * @return IBD_Notify_Admin_Notification
		 *
		 * @throws InvalidArgumentException
		 */
		public function make( $user_id, $title, $message, $args = array() ) {
			if ( ! isset( $args['email'] ) )
				throw new InvalidArgumentException( 'No email provided', 1 );

			return new IBD_Notify_Email_Notification( $user_id, $title, $message, $args );
		}
	}

endif;