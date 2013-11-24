<?php
/**
 *
 * @package Notify
 * @subpackage Growl
 * @since 0.2
 */

if ( ! class_exists( 'IBD_Notify_Growl_Notification' ) ) :
	/**
	 * Class IBD_Notify_Admin_Notification
	 */
	class IBD_Notify_Growl_Notification extends IBD_Notify_Notification {
		/**
		 * @var string the class to be applied to the admin notification
		 */
		private $class = "";

		/**
		 * @var bool force the notification closed
		 */
		private $sticky = false;

		/**
		 * @var int time notification is displayed
		 */
		private $time = 4000;

		/**
		 * @var array|string AJAX callback function
		 */
		private $callback = array();

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

			if ( isset( $args['sticky'] ) )
				$this->sticky = $args['sticky'];

			if ( isset( $args['time'] ) )
				$this->time = $args['time'];

			if ( isset( $args['callback'] ) )
				$this->callback = $args['callback'];
			else
				$this->callback = array( $this, 'notify_ajax' );

			if ( isset( $args['id'] ) )
				$this->id = $args['id'];
			else
				$this->id = uniqid();

			add_action( 'wp_ajax_ibd_notify_growl_notify', $this->callback );
			add_action( 'wp_ajax_nopriv_ibd_notify_growl_notify', $this->callback );
		}

		/**
		 * Send this notification using whatever method specified by extended class.
		 * Examples: growl, email, admin notification etc..
		 *
		 * In this case we implement it via growl action.
		 *
		 * @return boolean
		 */
		public function send() {
			if ( get_current_user_id() == $this->user_id ) {
				$this->display();
			}
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
			if ( $complete === true ) {
				$this->delete();

				return true;
			}
			else {
				return false;
			}
		}

		/**
		 * Handle the AJAX response
		 */
		public function notify_ajax() {
			if ( ! isset( $_POST['notification'] ) || $_POST['notification']['id'] != $this->id )
				return;

			$complete = isset( $_POST['complete'] ) ? true : false;
			$data = isset( $_POST['data'] ) ? json_decode( $_POST['data'] ) : array();

			$this->setup_notification_data();

			$response = array(
				'complete'     => $this->notify( $complete, $data ),
				'notification' => $this->notification_data
			);

			echo json_encode( $response );
			die();
		}

		/**
		 * Sets up the data to be saved to the database
		 *
		 * @return void
		 */
		protected function setup_notification_data() {
			$this->notification_data = array(
				'id'       => $this->id,
				'user_id'  => $this->user_id,
				'title'    => $this->title,
				'message'  => $this->message,
				'class'    => $this->class,
				'sticky'   => $this->sticky,
				'time'     => $this->time,
				'callback' => $this->callback,
				'ajax'     => 'ibd_notify_growl_notify',
				'handler'  => __CLASS__
			);
		}

		/**
		 * Display the growl notification
		 */
		public function display() {
			if ( defined( 'DOING_AJAX' ) )
				return;

			add_action( 'wp_enqueue_scripts', array( $this, 'styles_and_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'styles_and_scripts' ) );
		}

		/**
		 * Register styles and scripts
		 */
		public function styles_and_scripts() {
			$this->setup_notification_data();
			wp_enqueue_style( 'gritter', IBD_NOTIFY_URL . "includes/growl/assets/gritter/css/jquery.gritter.css" );
			wp_enqueue_style( 'ibd-notify-growl-client', IBD_NOTIFY_URL . "includes/growl/assets/css/client.css" );

			wp_enqueue_script( 'gritter', IBD_NOTIFY_URL . "includes/growl/assets/gritter/js/jquery.gritter.min.js", array( 'jquery' ) );
			wp_enqueue_script( 'ibd-notify-growl-client', IBD_NOTIFY_URL . "includes/growl/assets/js/client.js", array( 'jquery' ) );
			wp_localize_script( 'ibd-notify-growl-client', 'ibd_notify', array(
					'ajax_url'     => admin_url( 'admin-ajax.php' ),
					'notification' => json_encode( $this->notification_data )
				)
			);
		}

	}

endif;
