<?php
/**
 *
 * @package Notify
 * @subpackage Wrappers
 * @since 0.1
 */

if ( ! class_exists( 'IBD_Notify_Wrapper_WordPress' ) ) :

	/**
	 * Class IBD_Notify_Wrapper_WordPress
	 */
	class IBD_Notify_Wrapper_WordPress {
		/**
		 * Constructor. Hook at init to process all the notifications
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'process_notifications' ) );
		}

		/**
		 * Send all the notifications in the queue
		 */
		public function process_notifications() {
			$notifications = IBD_Notify_Util::get_all_notifications();
			$retriever = new IBD_Notify_Retriever();

			foreach ( $notifications as $user_id => $user_notifications ) {
				foreach ( $user_notifications as $notification ) {
					try {
						$notification = $retriever->retrieve( $user_id, $notification['id'] );
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