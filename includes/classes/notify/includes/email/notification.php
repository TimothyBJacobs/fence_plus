<?php
/**
 *
 * @package Notify
 * @subpackage Email
 * @since 0.3
 */

if ( ! class_exists( 'IBD_Notify_Email_Notification' ) ) :
	/**
	 * Class IBD_Notify_Email_Notification
	 */
	class IBD_Notify_Email_Notification extends IBD_Notify_Notification {
		/**
		 * @var string email address this notification should be sent for
		 */
		private $email;

		/**
		 * @var string|null the name this email should be addressed to
		 */
		private $to_name = null;

		/**
		 * @var array any additional headers to be sent
		 */
		private $headers = array();

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
		 * @param $args array {
		 *
		 * @type string email
		 * }
		 */
		public function __construct( $user_id, $title, $message, $args = array() ) {
			$this->user_id = $user_id;
			$this->title = $title;
			$this->message = $message;

			if ( isset( $args['email'] ) )
				$this->email = $args['email'];

			if ( isset( $args['to_name'] ) )
				$this->to_name = $args['to_name'];

			if ( isset( $args['headers'] ) && is_array( $args['header'] ) )
				$this->headers = $args['headers'];

			if ( isset( $args['id'] ) )
				$this->id = $args['id'];
			else
				$this->id = uniqid();
		}

		/**
		 * Make the to field for the email.
		 *
		 * @return string
		 */
		private function get_to_email() {
			if ( isset( $this->to_name ) ) {
				return $this->to_name . "<" . $this->email . ">";
			}
			else {
				return $this->email;

			}
		}

		/**
		 * Send this notification using whatever method specified by extended class.
		 * Examples: growl, email, admin notification etc..
		 *
		 * In this case we implement it by adding an admin_notices action.
		 *
		 * @uses wp_mail()
		 *
		 * @return boolean
		 */
		public function send() {
			$response = wp_mail( $this->get_to_email(), $this->title, $this->message, $this->headers );
			$this->notify( $response );

			return $response;
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
				'id'      => $this->id,
				'user_id' => $this->user_id,
				'title'   => $this->title,
				'message' => $this->message,
				'email'   => $this->email,
				'to_name' => $this->to_name,
				'headers' => $this->headers,
				'handler' => __CLASS__
			);
		}
	}

endif;