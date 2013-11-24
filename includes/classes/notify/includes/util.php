<?php
/**
 *
 * @package Notify
 * @subpackage Includes
 * @since 0.1
 */

if ( ! class_exists( 'IBD_Notify_Util' ) ) :

	/**
	 * Class IBD_Notify_Util
	 */

	class IBD_Notify_Util {
		/**
		 * The name of the notifications option
		 */
		const NOTIFICATION_NAME = 'ibd_notifications';

		/**
		 * @var IBD_Notify_Database|null
		 */
		private static $database = null;

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
			return self::get_database_instance()->get_option( self::NOTIFICATION_NAME, array() );
		}

		/**
		 * Get an array of all notifications for a user.
		 *
		 * @param $user_id int
		 *
		 * @return array
		 */
		public static function get_all_user_notifications( $user_id ) {
			$notifications = IBD_Notify_Util::get_all_notifications();

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
			self::get_database_instance()->update_option( self::NOTIFICATION_NAME, $notifications );
		}

		/**
		 * Update all notifications for a user with a new value.
		 *
		 * @param $user_id int
		 * @param array $new_notifications
		 */
		public static function update_all_user_notifications( $user_id, array $new_notifications ) {
			$notifications = IBD_Notify_Util::get_all_notifications( $user_id );
			$notifications[$user_id] = $new_notifications;

			IBD_Notify_Util::update_all_notifications( $notifications );
		}

		/**
		 * Delete all notifications
		 */
		public static function delete_all_notifications() {
			self::get_database_instance()->delete_option( self::NOTIFICATION_NAME );
		}

		/**
		 * Delete all notifications for a user.
		 *
		 * @param $user_id int
		 */
		public static function delete_all_user_notifications( $user_id ) {
			$notifications = IBD_Notify_Util::get_all_user_notifications( $user_id );
			unset( $notifications[$user_id] );

			IBD_Notify_Util::update_all_notifications( $notifications );
		}

		/**
		 * @param $user_id int
		 * @param $notification_id string
		 */
		public static function delete_user_notification( $user_id, $notification_id ) {
			$notifications = IBD_Notify_Util::get_all_user_notifications( $user_id );

			unset( $notifications[$notification_id] );

			IBD_Notify_Util::update_all_user_notifications( $user_id, $notifications );
		}

		/**
		 * @param $user_id int
		 * @param $notification_id string
		 *
		 * @return array|bool
		 */
		public static function get_notification( $user_id, $notification_id ) {
			$notifications = IBD_Notify_Util::get_all_user_notifications( $user_id );

			if ( isset( $notifications[$notification_id] ) )
				return $notifications[$notification_id];
			else
				return false;
		}

		/**
		 * Get an instance of the database class.
		 *
		 * Pulls from the config.php file
		 *
		 * @return IBD_Notify_Database
		 */
		public static function get_database_instance() {
			if ( ! is_a( self::$database, 'IBD_Notify_Database' ) ) {
				$reflection = new ReflectionClass( IBD_NOTIFY_DATABASE_CLASS );

				self::$database = $reflection->newInstance();
			}

			return self::$database;
		}

	}
endif;