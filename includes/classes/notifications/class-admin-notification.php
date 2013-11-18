<?php
/**
 *
 * @package Fence Plus
 * @subpackage Notifications
 * @since 0.1
 */

if ( ! class_exists( 'IBD_Admin_Notification' ) ) :
	require_once 'class-notification.php';

	/**
	 * Class IBD_Admin_Notification
	 */
	/**
	 * Class IBD_Admin_Notification
	 */
	class IBD_Admin_Notification extends IBD_Notification {
		/**
		 * @var string updated|error the class to be applied to the admin notification
		 */
		private $class = 'updated';

		/**
		 * Should setup all the properties, but should not save(),
		 * the client must call the save() method. Do not assume that all
		 * arguments are specified in the args array. Check if they are set
		 * using isset and provide defaults for all values. Also do not assume
		 * that the id value is populated, use uniqid() to generate it if it
		 * does not exist.
		 *
		 * @param $user_id int
		 * @param $title string
		 * @param $message string
		 * @param $args array
		 */
		public function __construct( $user_id, $title, $message, $args = array() ) {
			$this->user_id = $user_id;
			$this->title = $title;
			$this->message = $message;

			if ( isset( $args['class'] ) )
				$this->class = $args['class'];

			if ( isset( $args['id'] ) )
				$this->id = $args['id'];
			else
				$this->id = uniqid();
		}

		/**
		 * Send this notification using whatever method specified by extended class.
		 * Examples: growl, email, admin notification etc..
		 *
		 * In this case we implement it by adding an admin_notices action.
		 *
		 * @return boolean
		 */
		public function send() {
			if ( get_current_user_id() == $this->user_id && is_admin() ) {
				add_action( 'admin_notices', array( $this, 'display' ) );

				return true;
			}

			return false;
		}

		/**
		 * Response when the message is processed
		 *
		 * @param boolean $complete
		 * @param array $data
		 *
		 * @return boolean
		 */
		public function notify( $complete = true, $data = array() ) {
			if ( $complete === true )
				$this->delete();

			return true;
		}

		/**
		 * Sets up the data to be saved to the database
		 *
		 * @return void
		 */
		protected function setup_notification_data() {
			$this->notification_data = array(
				'id'           => $this->id,
				'user_id'      => $this->user_id,
				'title'        => $this->title,
				'message'      => $this->message,
				'class'        => $this->class,
				'handler'      => __CLASS__,
				'handler_file' => __FILE__
			);
		}

		/**
		 * Display the admin notification
		 */
		public function display() {
			echo '<div class="' . $this->class . '"><p><strong>' . $this->title . ':</strong> ' . $this->message . "</p></div>";

			$this->notify( true );
		}
	}

endif;
