<?php
/**
 *
 * @package Fence Plus
 * @subpackage Includes
 * @since 0.1
 */

class Fence_Plus_Importer_AJAX {
	/**
	 * @var int askFRED club ID
	 */
	private $club_id;
	/**
	 * @var string USFA ID of first fencer to determine the club ID
	 */
	private $first_fencer_usfa_id;
	/**
	 * @var array default args sent to the askFRED API as args
	 */
	private $api_args = array(
		'version'  => 'v1',
		'resource' => 'fencer'
	);

	/**
	 * @var array of errors to be displayed to the user
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
		try {
			$api = new askFRED_API( Fence_Plus_Options::get_instance()->api_key, array_merge( $this->api_args, array( 'usfa_id' => $this->first_fencer_usfa_id ) ) );
			$results = $api->get_results();
		}
		catch ( InvalidArgumentException $e ) {
			Fence_Plus_Utility::add_admin_notification( $e->getMessage(), 'error' );
			echo $e->getMessage();
			die();
		}

		// set the club ID
		$this->club_id = $results[0]['primary_club_id'];

		// Begin import of all fencers listed by the Club's ID
		require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . 'class-fencer.php' );

		$fencers = $this->get_all_fencers();

		if ( $fencers === false ) {
			echo "API Key required";
			die();
		}

		// CSV Processing
		$fencer_emails = array();

		if ( isset( $_POST['csv'] ) && ! empty( $_POST['csv'] ) ) {
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
		foreach ( $fencers as $fencer_data ) {
			if ( empty( $fencer_data['usfa_id'] ) ) // if there is no USFA ID, ignore this fencer_data
				continue;

			$usfa_id = $fencer_data['usfa_id'];

			$user_data = array();

			if ( isset( $fencer_emails[$usfa_id] ) ) { // if an email exists in the CSV for the current fencer_data ID
				$user_data['user_email'] = $fencer_emails[$usfa_id]; // then grab the email address
				unset( $fencer_emails[$usfa_id] ); // remove so later we can import any fencer's who weren't listed in askFRED as being a part of current club
			}

			try {
				Fence_Plus_Fencer::insert_user_from_api_data( $fencer_data, $user_data );
			}
			catch ( InvalidArgumentException $e ) {
				if ( $e->getCode() === 4 ) { // wp_insert_user error most likely if the user already exists
					try {
						$current_fencer = Fence_Plus_Fencer::usfa_id_db_load( $usfa_id ); // init from usfa id
						$current_fencer->update( $fencer_data ); // and update fencer_data with new data
					}
					catch ( InvalidArgumentException $e ) {
						/* translators: %s is the fencer's name */
						$this->errors[] = sprintf( __( 'Fencer %s failed to import', Fence_Plus::SLUG ), $fencer_data['first_name'] . ' ' . $fencer_data['last_name'] );
						// todo have someway of solving this exception, not just fail silently
					}

				}
			}
		}

		unset( $usfa_id );

		if ( count( $fencer_emails ) > 0 ) { // if there are still usfa ids that haven't been assigned to a user
			$user_ids = array();
			foreach ( $fencer_emails as $usfa_id => $email ) {
				$user_data = array(
					'user_email' => $email
				);

				$user_data = apply_filters( 'fence_plus_insert_extra_fencer_userdata', $user_data, $usfa_id );

				try {
					$fencer_data = Fence_Plus_Fencer::usfa_id_create_fencer( $usfa_id, $user_data );
					$user_ids[] = $fencer_data->get_wp_id();
				}
				catch ( InvalidArgumentException $e ) {
					// todo solve exception
				}

			}

			do_action( 'fence_plus_inserted_extra_fencers', $user_ids );
		}

		$output = "Completed";

		$errors = apply_filters( 'fence_plus_fencer_import_errors', $this->errors );

		foreach ( $errors as $error ) {
			$output .= "<br>" . $error;
		}

		echo $output;

		Fence_Plus_Importer::notify_databse_import_complete();

		do_action( 'fence_plus_fencer_import_completed' );

		die();
	}

	/**
	 * Get all fencers for the set club ID
	 *
	 * @return array
	 */
	private function get_all_fencers() {
		try {
			$api = new askFRED_API( Fence_Plus_Options::get_instance()->api_key, array_merge( $this->api_args, array( 'club_id' => $this->club_id ) ) );

			return $api->get_results();
		}
		catch ( InvalidArgumentException $e ) {
			Fence_Plus_Utility::add_admin_notification( $e->getMessage(), 'error' );
		}

		return false;
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