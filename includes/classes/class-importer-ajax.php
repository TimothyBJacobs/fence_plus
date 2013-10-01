<?php
/**
 *
 * @package Fence Plus
 * @subpackage Includes
 * @since 0.1
 */

class Fence_Plus_Importer_AJAX {
	/**
	 * @var
	 */
	private $club_id;
	/**
	 * @var
	 */
	private $first_fencer_usfa_id;
	/**
	 * @var array
	 */
	private $api_args = array(
		'version'  => 'v1',
		'resource' => 'fencer'
	);

	private $errors = array();

	/**
	 *
	 */
	public function __construct() {
		add_action( 'wp_ajax_fence_plus_import_fencers', array( $this, 'ajax_import_fencers' ) );
	}

	/**
	 *
	 */
	public function ajax_import_fencers() {
		if ( $_POST['wipe'] == 'delete' ) {
			self::wipe_fencers();
		}

		require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . 'class-askFRED_API.php' );
		$this->first_fencer_usfa_id = (int) $_POST['usfa_id'];
		$api = new askFRED_API( AF_API_KEY, array_merge( $this->api_args, array( 'usfa_id' => $this->first_fencer_usfa_id ) ) );
		$results = $api->get_results();
		$this->club_id = $results[0]['primary_club_id'];

		require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . 'class-fencer.php' );
		$fencers = $this->get_all_fencers();

		// read data from CSV uploaded
		if ( ( $handle = fopen( $_POST['csv'], "r" ) ) !== FALSE ) {
			while ( ( $data = fgetcsv( $handle, 1000, "," ) ) !== FALSE ) {
				$fencer_emails[$data[0]] = $data[1]; // create an array of usfa_id => email
			}
			fclose( $handle );
		}
		else {
			$fencer_emails = array();
			$this->errors[] = __( "Invalid CSV", Fence_Plus::SLUG );
		}

		apply_filters( 'fence_plus_fencer_email_import_csv', $fencer_emails );

		foreach ( $fencers as $fencer ) {
			if ( empty( $fencer['usfa_id'] ) )
				continue;

			$usfa_id = $fencer['usfa_id'];

			if ( isset( $fencer_emails[$usfa_id] ) ) { // if an email exists for the current fencer ID
				$email = $fencer_emails[$usfa_id]; // then grab the email address and add email to instantiation
				unset( $fencer_emails[$usfa_id] );
			}
			else {
				$email = "";
			}

			try {
				Fence_Plus_Fencer::insert_user_from_api_data( $fencer, $email );
			} catch ( LogicException $e ){ // if the user already exists
				if ( $e->getCode() == 1 ) {
					$current_fencer = Fence_Plus_Fencer::init_from_usfa_id( $usfa_id ); // init from usfa id
					$current_fencer->update_from_data( $fencer ); // and update fencer with new data
				}
			}
		}

		if ( count( $fencer_emails ) > 1 ) { // if there are still usfa ids that haven't been assigned to a user
			foreach ( $fencer_emails as $usfa_id => $email ) {
				$fencer = Fence_Plus_Fencer::init_from_usfa_id( $usfa_id ); // create a new fencer object from that usfa id
				$args = array(
					'ID'         => $fencer->get_wp_id(),
					'user_email' => $email
				);

				$args = apply_filters( 'fence_plus_fencer_import_update', $args, $fencer->get_wp_id() );

				wp_update_user( $args );
			}
		}

		$output = "Completed";

		$errors = apply_filters( 'fence_plus_fencer_import_errors', $this->errors );

		foreach ( $errors as $error ) {
			$output .= "<br />" . $error;
		}

		echo $output;

		Fence_Plus_Importer::notify_databse_import_complete();

		do_action( 'fence_plus_fencer_import_completed' );

		die();
	}

	/**
	 * @return array
	 */
	private function get_all_fencers() {
		$api = new askFRED_API( AF_API_KEY, array_merge( $this->api_args, array( 'club_id' => $this->club_id ) ) );
		return $api->get_results();
	}

	/**
	 *
	 */
	static function wipe_fencers() {
		$fencers = get_users( array( 'role' => 'fencer' ) );

		foreach ( $fencers as $fencer ) {
			wp_delete_user( $fencer->ID );
		}

		do_action( 'fence_plus_all_fencers_deleted' );
	}
}