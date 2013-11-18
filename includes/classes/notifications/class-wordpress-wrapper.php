<?php
/**
 *
 * @package Fence Plus
 * @subpackage Notifications
 * @since 0.1
 */

if ( ! class_exists( 'IBD_Notification_WordPress_Wrapper' ) ) :

	/**
	 * Class IBD_Notification_WordPress_Wrapper
	 */
	class IBD_Notification_WordPress_Wrapper {
		/**
		 * Constructor. Hook at init to process all the notifications
		 */
		public function __construct() {
			require_once 'class-notification-retriever.php';

			add_action( 'init', array( $this, 'process_notifications' ) );
		}

		/**
		 * Send all the notifications in the queue
		 */
		public function process_notifications() {
			$notifications = IBD_Notification_Util::get_all_notifications();
			$retriever = new IBD_Notification_Retriever();

			foreach ( $notifications as $user_id => $user_notifications ) {
				foreach ( $user_notifications as $notification ) {
					try {
						$notification = $retriever->retrieve( $user_id, $notification );
						$notification->send();
					}
					catch ( InvalidArgumentException $e ) {
						continue;
					}
				}
			}
		}
	}

endif;