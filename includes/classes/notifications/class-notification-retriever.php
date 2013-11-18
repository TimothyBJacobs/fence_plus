<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

if ( ! class_exists( 'IBD_Notification_Retriever' ) ) :

	/**
	 * Class IBD_Notification_Retriever
	 *
	 * Retrieve an IBD_Notification object based on the notifications ID
	 */
	class IBD_Notification_Retriever {

		/**
		 * @param $user_id
		 * @param $id
		 *
		 * @return IBD_Notification
		 *
		 * @throws InvalidArgumentException
		 */
		public function retrieve( $user_id, $id ) {
			$notification = IBD_Notification_Util::get_notification( $user_id, $id );
			$handler = $notification['handler'];
			$handler_file = $notification['handler_file'];

			if ( ! file_exists( $handler_file ) )
				throw new InvalidArgumentException( 'Handler file does not exist', 1 );

			include_once $handler_file;

			if ( ! class_exists( $handler ) )
				throw new InvalidArgumentException( 'Handler class does not exist', 2 );

			$reflection = new ReflectionClass( $handler );

			$user_id = $notification['user_id'];
			$title = $notification['title'];
			$message = $notification['message'];
			unset( $notification['user_id'] );
			unset( $notification['title'] );
			unset( $notification['message'] );
			unset( $notification['handler'] );

			$handler = $reflection->newInstance( $user_id, $title, $message, $notification );

			return $handler;
		}
	}

endif;