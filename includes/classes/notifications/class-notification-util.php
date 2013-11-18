<?php
/**
 *
 * @package Fence Plus
 * @subpackage Notifications
 * @since 0.1
 */

if ( ! class_exists( 'IBD_Notification_Util' ) ) :

	/**
	 * Class IBD_Notification_Util
	 */
	/**
	 * Class IBD_Notification_Util
	 */
	class IBD_Notification_Util {
		/**
		 * The name of the notifications option
		 */
		const NOTIFICATION_NAME = 'ibd_notifications';

		/**
		 * Prevent construction
		 */
		private function __construct() {
		}

		/**
		 * Get all notifications
		 *
		 * @return mixed|void
		 */
		public static function get_all_notifications() {
			return get_option( self::NOTIFICATION_NAME, array() );
		}

		/**
		 * Get an array of all notifications for a user.
		 *
		 * @param $user_id int
		 *
		 * @return array
		 */
		public static function get_all_user_notifications( $user_id ) {
			$notifications = IBD_Notification_Util::get_all_notifications();

			if ( ! isset( $notifications[$user_id] ) || ! is_array( $notifications[$user_id] ) )
				$notifications[$user_id] = array();

			return $notifications[$user_id];
		}

		/**
		 * Update all notifications
		 *
		 * @param array $notifications
		 */
		public static function update_all_notifications( array $notifications ) {
			update_option( self::NOTIFICATION_NAME, $notifications );
		}

		/**
		 * Update all notifications for a user with a new value.
		 *
		 * @param $user_id int
		 * @param array $new_notifications
		 */
		public static function update_all_user_notifications( $user_id, array $new_notifications ) {
			$notifications = IBD_Notification_Util::get_all_user_notifications( $user_id );
			$notifications[$user_id] = $new_notifications;

			IBD_Notification_Util::update_all_notifications( $notifications );
		}

		/**
		 * Delete all notifications
		 */
		public static function delete_all_notifications() {
			delete_option( self::NOTIFICATION_NAME );
		}

		/**
		 * Delete all notifications for a user.
		 *
		 * @param $user_id int
		 */
		public static function delete_all_user_notifications( $user_id ) {
			$notifications = IBD_Notification_Util::get_all_user_notifications( $user_id );
			unset( $notifications[$user_id] );

			IBD_Notification_Util::update_all_notifications( $notifications );
		}

		public static function delete_user_notification($user_id, $notification_id) {
			$notifications = IBD_Notification_Util::get_all_user_notifications( $user_id );

			unset( $notifications[$notification_id] );

			IBD_Notification_Util::update_all_user_notifications($user_id, $notifications );
		}

		/**
		 * @param $user_id int
		 * @param $notification_id string
		 *
		 * @return array|bool
		 */
		public static function get_notification( $user_id, $notification_id ) {
			$notifications = IBD_Notification_Util::get_all_user_notifications( $user_id );

			if ( ( $key = array_search( $notification_id, $notifications ) ) !== false )
				return $notifications[$key];
			else
				return false;
		}
	}
endif;