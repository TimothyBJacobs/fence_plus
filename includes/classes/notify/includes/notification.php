<?php
/**
 *
 * @package Notify
 * @subpackage Includes
 * @since 0.1
 */

if ( ! class_exists( 'IBD_Notify_Notification' ) ) :

	/**
	 * Class IBD_Notify_Notification
	 */
	abstract class IBD_Notify_Notification {
		/**
		 * @var string Unique ID
		 */
		protected $id;
		/**
		 * WP_User ID that this notification should be associated with.
		 *
		 * @var int
		 */
		protected $user_id;
		/**
		 * @var string
		 */
		protected $title;
		/**
		 * @var string
		 */
		protected $message;
		/**
		 * Data that is saved to the notifications user meta.
		 *
		 * @var array => {
		 *      'id'            => unique ID
		 *      'title'         => notification title should be short,
		 *      'message'       => notification message,
		 *      'handler'       => __CLASS__ class name that should be used to handle this notification
		 *      ... any additional arguments
		 *  }
		 */
		protected $notification_data = array();

		/**
		 * Should setup all the properties, but should not save(),
		 * the client must call the save() method. Do not assume that all
		 * arguments are specified in the args array. Check if they are set
		 * using isset() and provide defaults for all values. Also do not assume
		 * that the id value is populated, use uniqid() to generate one if it
		 * does not exist.
		 *
		 * @param $user_id int
		 * @param $title string
		 * @param $message string
		 * @param $args array
		 */
		public abstract function __construct( $user_id, $title, $message, $args = array() );

		/**
		 * Send this notification using whatever method specified by extended class.
		 * Examples: growl, email, admin notification etc..
		 *
		 * @return boolean
		 */
		public abstract function send();

		/**
		 * Response when the message is processed
		 *
		 * @param boolean $complete
		 * @param array $data
		 *
		 * @return array|boolean
		 */
		public abstract function notify( $complete = true, $data = array() );

		/**
		 * Sets up the data to be saved to the database
		 *
		 * @return void
		 */
		protected abstract function setup_notification_data();

		/**
		 * Save this notification to the database.
		 *
		 * @return void
		 */
		public function save() {
			$notifications = IBD_Notify_Util::get_all_user_notifications( $this->user_id );
			$this->setup_notification_data();
			$notifications[$this->id] = $this->notification_data;
			IBD_Notify_Util::update_all_user_notifications( $this->user_id, $notifications );
		}

		/**
		 * Delete this notification from the database.
		 *
		 * @return void
		 */
		public function delete() {
			IBD_Notify_Util::delete_user_notification( $this->user_id, $this->id );
		}
	}

endif;