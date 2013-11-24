<?php
/**
 *
 * @package Notify
 * @subpackage Includes
 * @since 0.1
 */

if ( ! class_exists( 'IBD_Notify_Retriever' ) ) :

	/**
	 * Class IBD_Notify_Retriever
	 *
	 * Retrieve an IBD_Notify_Notification object based on the notifications ID
	 */
	class IBD_Notify_Retriever {

		/**
		 * @param $user_id
		 * @param $id
		 *
		 * @return IBD_Notify_Notification
		 *
		 * @throws InvalidArgumentException
		 */
		public function retrieve( $user_id, $id ) {
			$notification = IBD_Notify_Util::get_notification( $user_id, $id );
			$handler = $notification['handler'];

			if ( ! class_exists( $handler ) )
				throw new InvalidArgumentException( 'Handler class does not exist', 2 );

			$user_id = $notification['user_id'];
			$title = $notification['title'];
			$message = $notification['message'];
			unset( $notification['user_id'] );
			unset( $notification['title'] );
			unset( $notification['message'] );
			unset( $notification['handler'] );

			$handler = str_replace( "Notification", "Factory", $handler );

			if ( ! class_exists( $handler ) )
				throw new InvalidArgumentException( 'Handler factory class does not exist', 2 );

			$reflection = new ReflectionClass( $handler );
			$handler_factory = $reflection->newInstance();

			try {
				return $handler_factory->make( $user_id, $title, $message, $notification );
			}
			catch ( InvalidArgumentException $e ) {
				throw $e;
			}
		}
	}

endif;