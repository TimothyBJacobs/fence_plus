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

	/**
	 * @var array
	 */
	private $errors = array();

	/**
	 * add ajax callback
	 */
	public function __construct() {
		add_action( 'wp_ajax_fence_plus_import_fencers', array( $this, 'ajax_import_fencers' ) );
	}

	/**
	 * AJAX Callback for importing fencers
	 */
	public function ajax_import_fencers() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			_e( 'Incorrect permissions', Fence_Plus::SLUG );
			die();
		}

		if ( $_POST['wipe'] == 'delete' && current_user_can( 'delete_users' ) ) {
			self::delete_all_fencers();
		}

		require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . 'class-askFRED_API.php' );

		// Using the first USFA ID, perform an askFRED API query to determine the Club ID

		$this->first_fencer_usfa_id = (int) $_POST['usfa_id'];

		// call askFRED api
		$api = new askFRED_API( AF_API_KEY, array_merge( $this->api_args, array( 'usfa_id' => $this->first_fencer_usfa_id ) ) );
		$results = $api->get_results();

		// set the club ID
		$this->club_id = $results[0]['primary_club_id'];

		// Begin import of all fencers listed by the Club's ID
		require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . 'class-fencer.php' );

		$fencers = $this->get_all_fencers();

		// CSV Processing
		$fencer_emails = array();

		if ( isset( $_POST['csv'] ) && ! empty($_POST['csv']) ) {
			// read data from CSV uploaded
			if ( ( $handle = fopen( $_POST['csv'], "r" ) ) !== false ) {
				while ( ( $data = fgetcsv( $handle, 1000, "," ) ) !== false ) {
					$fencer_emails[$data[0]] = $data[1]; // create an array of usfa_id => email
				}
				fclose( $handle );
			}
			else {
				$this->errors[] = __( "Invalid CSV", Fence_Plus::SLUG );
			}
		}

		apply_filters( 'fence_plus_fencer_email_import_csv', $fencer_emails );

		// loop through all fencers provided by API call
		foreach ( $fencers as $fencer ) {
			if ( empty( $fencer['usfa_id'] ) ) // if there is no USFA ID, ignore this fencer
				continue;

			$usfa_id = $fencer['usfa_id'];

			$user_data = array();

			if ( isset( $fencer_emails[$usfa_id] ) ) { // if an email exists in the CSV for the current fencer ID
				$user_data['user_email'] = $fencer_emails[$usfa_id]; // then grab the email address
				unset( $fencer_emails[$usfa_id] );
				// unset current fencer in CSV so we can import any other fencers provided there that aren't in the askFRED system
			}

			try {
				Fence_Plus_Fencer::insert_user_from_api_data( $fencer, $user_data );
			} catch ( InvalidArgumentException $e ){
				if ( $e->getCode() === 4 ) { // wp_insert_user error most likely if the user already exists
					try {
						$current_fencer = Fence_Plus_Fencer::usfa_id_db_load( $usfa_id ); // init from usfa id
						$current_fencer->update_from_data( $fencer ); // and update fencer with new data
					} catch (InvalidArgumentException $e) {
						// todo have someway of solving this exception, not just fail silently
					}

				}
			}
		}

		unset( $usfa_id );

		error_log(var_export($fencer_emails, true));

		if ( count( $fencer_emails ) > 0 ) { // if there are still usfa ids that haven't been assigned to a user
			foreach ( $fencer_emails as $usfa_id => $email ) {
				$user_data = array(
					'user_email' => $email
				);

				$user_data = apply_filters( 'fence_plus_insert_extra_fencer_userdata', $user_data, $usfa_id );

				Fence_Plus_Fencer::usfa_id_create_fencer( $usfa_id, $user_data );
			}
		}

		$output = "Completed";

		$errors = apply_filters( 'fence_plus_fencer_import_errors', $this->errors );

		foreach ( $errors as $error ) {
			$output .= "\n" . $error;
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
	 * Delete all fencers
	 */
	static function delete_all_fencers() {
		$fencers = get_users( array( 'role' => 'fencer' ) );

		foreach ( $fencers as $fencer ) {
			wp_delete_user( $fencer->ID );
		}

		do_action( 'fence_plus_all_fencers_deleted' );
	}
}