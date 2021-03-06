<?php

/**
 * Fencer Class
 *
 * @package Fence Plus
 * @subpackage includes
 * @since 0.1
 */

class Fence_Plus_Fencer extends Fence_Plus_Person {

	/*=================
	   askFRED Values
	 =================*/

	/**
	 * @var int askFRED internal ID
	 */
	private $id;
	/**
	 * @var string USFA official fencer ID
	 */
	private $usfa_id;
	/**
	 * @var string CFF official fencer ID
	 */
	private $cff_id;
	/**
	 * @var string FIE official fencer ID
	 */
	private $fie_id;
	/**
	 * @var string first name of fencer from askFRED
	 */
	private $first_name;
	/**
	 * @var string last name of fencer from askFRED
	 */
	private $last_name;
	/**
	 * @var string M|F
	 */
	private $gender;
	/**
	 * @var int birth year of fencer from askFRED
	 */
	private $birthyear;
	/**
	 * @var array divisions that fencer is registered in
	 */
	private $division = array();
	/**
	 * @var array of clubs each club has
	 *  'id'        => (int) askFRED club ID,
	 *  'name'      => (string) askFRED club name,
	 *  'initials'  => (string) askFRED club initials
	 */
	private $clubs = array();
	/**
	 * @var int id of the fencer's primary club as indicated on askFRED
	 * @see $clubs
	 */
	private $primary_club_id;
	/**
	 * @var array
	 * 'weapon' => array(
	 *      'id'        => (int) askFRED rating ID
	 *      'weapon'    => (string) 'Weapon',
	 *      'letter'    => (string) A,B,C,D,E,U
	 *      'year'      => (string) Year letter was earned empty string if none
	 *      'authority' => (string) USFA, FIE )
	 */
	private $usfa_ratings;

	/*=================
	   Custom Values
	 =================*/

	/**
	 * Array of primary weapons
	 *
	 * @var array foil|epee|saber
	 * @see calculate_primary_weapon
	 */
	private $primary_weapon = array();
	/**
	 * @var string age brackets
	 * @see
	 * @see calculate_age_bracket
	 */
	private $age_bracket;

	/**
	 * @var array
	 *  'tournament_id' => askFRED tournament ID,
	 *  'coach'         => coach WP User ID,
	 *  'time'          => unix timestamp of date coach referred,
	 *  'events'        => array() askFRED array of event IDs
	 */
	private $coach_suggested_tournaments = array();

	/**
	 * Holds MD5 checksum of API raw data
	 * Used to check whether data has been updated since last API call
	 *
	 * @var string|null
	 */
	private $md5_checksum = null;

	/**
	 * Instantiate Fencer object from user meta database.
	 *
	 * If fencer data does not exist in database and USFA ID is provided,
	 * it will automatically update from API
	 *
	 * If USFA ID is not provided, will throw exception
	 *
	 * If API data is provided, a user ID must also be provided
	 * that way we can save the data from the API to the user.
	 *
	 * @param string $context
	 * @param array $args needed for instantiation
	 *
	 *
	 * Four general situations
	 *  1. Create object from a Fencer that is already in the database
	 *
	 *      a. from a USFA ID (db-usfa)
	 *          $args = array(
	 *                'usfa_id'   => USFA ID
	 *          )
	 *
	 *      b. from a WP User ID preferred (db-id)
	 *          $args = array(
	 *              'wp_id'     => WP User ID of Fencer
	 *          )
	 *
	 *  2. Create an object from a Fencer that is not in the database
	 *
	 *      a. from a USFA ID (create-usfa)
	 *          $args = array(
	 *              'usfa_id'   => USFA ID,
	 *              'userdata'  => array of userdata information to be passed to WP Insert User
	 *          )
	 *
	 *      b. from API data
	 *          $args = array(
	 *              'api_data'  => array of raw data from API,
	 *              'userdata'  => array of userdata information to be passed to WP Insert User
	 *          )
	 *
	 * @throws InvalidArgumentException
	 *  1. Load from DB by USFA ID and USFA ID wasn't provided
	 *  2. Load from DB by WP User ID and User ID wasn't provided
	 *  3. Create from askFRED DB and no USFA ID or Userdata provided
	 *  4. Error from WP Insert User
	 *  5. No API Data from Userdata provided
	 *  6. No WP user with that ID
	 *  7. WordPress user is not a fencer
	 *  8. WordPress User ID not found from USFA ID
	 */
	private function __construct( $context, $args ) {
		switch ( $context ) {
			case "db-usfa":
				if ( ! isset( $args['usfa_id'] ) ) {
					throw new InvalidArgumentException( "No USFA ID provided for instantiating from DB", 1 );
				}

				$wp_id = Fence_Plus_Utility::get_user_id_from_usfa_id( $args['usfa_id'] );

				if ( false === $wp_id ) {
					throw new InvalidArgumentException( "WordPress User ID not found from USFA ID", 8 );
				}

				$this->wp_id = $wp_id;
				$this->load_data();

				break;

			case "db-id":
				if ( ! isset( $args['wp_id'] ) )
					throw new InvalidArgumentException( "No WordPress User ID provided for instantiating from DB", 2 );

				if ( false === get_user_by( 'id', $args['wp_id'] ) )
					throw new InvalidArgumentException( "WordPress User ID not found", 6 );

				if ( ! Fence_Plus_Utility::is_fencer( $args['wp_id'] ) )
					throw new InvalidArgumentException( "WordPress user is not a fencer", 7 );

				$this->wp_id = $args['wp_id'];

				$this->load_data();

				break;

			case "create-usfa":
				if ( ! isset( $args['usfa_id'] ) || ! isset( $args['userdata'] ) ) {
					throw new InvalidArgumentException( "No USFA ID or Userdata provided for creating fencer from askFRED API", 3 );
				}

				$this->usfa_id = $args['usfa_id'];
				$this->update();

				$userdata = $args['userdata'];
				$userdata['role'] = 'fencer';
				$userdata['user_pass'] = $this->usfa_id;
				$userdata['user_login'] = $this->get_first_name() . " " . $this->get_last_name();
				$userdata['first_name'] = $this->get_first_name();
				$userdata['last_name'] = $this->get_last_name();
				$userdata['display_name'] = $userdata['user_login'];

				$userdata = apply_filters( 'fence_plus_insert_fencer_args', $userdata, $this->usfa_id );
				$user_id = wp_insert_user( $userdata );

				if ( is_wp_error( $user_id ) ) {
					throw new InvalidArgumentException( implode( ",", $user_id->get_error_messages() ), 4 );
				}

				$this->wp_id = $user_id;
				$this->save();

				do_action( 'fence_plus_fencer_created', $this );

				break;

			case "create-api-data":
				if ( ! isset( $args['api_data'] ) || ! isset( $args['userdata'] ) ) {
					throw new InvalidArgumentException( "No API Data or Userdata provided", 5 );
				}

				$this->update( $args['api_data'] );

				$userdata = $args['userdata'];
				$userdata['role'] = 'fencer';
				$userdata['user_pass'] = $this->usfa_id;
				$userdata['user_login'] = $this->get_first_name() . " " . $this->get_last_name();
				$userdata['first_name'] = $this->get_first_name();
				$userdata['last_name'] = $this->get_last_name();
				$userdata['display_name'] = $userdata['user_login'];

				$userdata = apply_filters( 'fence_plus_insert_fencer_args', $userdata, $this->usfa_id );

				$user_id = wp_insert_user( $userdata );

				if ( is_wp_error( $user_id ) ) {
					throw new InvalidArgumentException( implode( ",", $user_id->get_error_messages() ), 4 );
				}

				$this->wp_id = $user_id;
				$this->save();

				do_action( 'fence_plus_fencer_created', $this );

				break;

			case 'make-fencer':
				if ( ! isset( $args['usfa_id'] ) || empty( $args['usfa_id'] ) )
					throw new InvalidArgumentException ( "No USFA ID provided", 9 );

				if ( Fence_Plus_Utility::is_fencer( $args['wp_id'] ) )
					throw new InvalidArgumentException ( "User is already a fencer", 10 );

				$this->set_usfa_id( $args['usfa_id'] );
				$this->update();
				$this->set_wp_id( $args['wp_id'] );
				$this->save();
				$userdata = array(
					'ID'           => $args['wp_id'],
					'first_name'   => $this->get_first_name(),
					'last_name'    => $this->get_last_name(),
					'display_name' => $this->get_first_name() . " " . $this->get_last_name()
				);

				wp_update_user( $userdata );

				do_action( 'fence_plus_fencer_created', $this );

				break;
		}
	}

	/*========================
		Creation Functions
	=========================*/

	/**
	 * Create fencer object from USFA ID
	 *
	 * @param string $usfa_id
	 *
	 * @throws InvalidArgumentException|Exception
	 *
	 * @return Fence_Plus_Fencer
	 */
	public static function usfa_id_db_load( $usfa_id ) {
		try {
			return new Fence_Plus_Fencer( 'db-usfa', array(
				'usfa_id' => (string) $usfa_id
			) );
		}
		catch ( InvalidArgumentException $e ) {
			throw $e;
		}
	}

	/**
	 * Create Fencer Object from USFA ID
	 *
	 * @param int $user_id
	 *
	 * @throws InvalidArgumentException|Exception
	 * @return Fence_Plus_Fencer
	 */
	public static function wp_id_db_load( $user_id ) {
		try {
			return new Fence_Plus_Fencer( 'db-id', array(
				'wp_id' => (int) $user_id
			) );
		}
		catch ( InvalidArgumentException $e ) {
			throw $e;
		}
	}

	/**
	 * Creates a fencer from a USFA ID by calling askFRED API
	 *
	 * @param $usfa_id
	 * @param $userdata
	 *
	 * @throws InvalidArgumentException|Exception
	 *
	 * @return Fence_Plus_Fencer
	 */
	public static function usfa_id_create_fencer( $usfa_id, array $userdata ) {
		try {
			return new Fence_Plus_Fencer( 'create-usfa', array(
				'usfa_id'  => $usfa_id,
				'userdata' => $userdata
			) );
		}
		catch ( InvalidArgumentException $e ) {
			throw $e;
		}

	}

	/**
	 * Creates a WordPress user from data from API and
	 * returns a Fence_Plus_Fencer object
	 *
	 * @param $api_data array raw data from API
	 * @param $userdata array of user data to be used for insert user
	 *
	 * @throws InvalidArgumentException|Exception
	 *
	 * @return Fence_Plus_Fencer
	 */
	public static function insert_user_from_api_data( array $api_data, array $userdata ) {
		try {
			return new Fence_Plus_Fencer( 'create-api-data', array(
				'api_data' => $api_data,
				'userdata' => $userdata
			) );
		}
		catch ( InvalidArgumentException $e ) {
			throw $e;
		}

	}

	/**
	 * @param $wp_id
	 * @param $usfa_id
	 *
	 * @return Fence_Plus_Fencer
	 * @throws Exception|InvalidArgumentException
	 */
	public static function make_user_fencer( $wp_id, $usfa_id ) {
		try {
			return new Fence_Plus_Fencer( 'make-fencer', array(
				'usfa_id' => $usfa_id,
				'wp_id'   => $wp_id
			) );
		}
		catch ( InvalidArgumentException $e ) {
			throw $e;
		}
	}

	/*========================
		Database Functions
	=========================*/

	/**
	 * Saves current objects data o the database
	 */
	public function save() {
		$fencerdata = array();

		foreach ( $this as $key => $value ) {
			$fencerdata[$key] = $value;
		}

		update_user_meta( $this->wp_id, 'fence_plus_fencer_data', $fencerdata );

		do_action( 'fence_plus_fencer_saved', $this );
	}

	/**
	 * Update fencer from askFRED
	 */
	public function update( $data = null ) {
		require_once( FENCEPLUS_INCLUDES_UPDATERS_DIR . "class-fencer-updater.php" );

		$this->register_update_actions();

		$updater = new Fence_Plus_Fencer_Update( $this, $data );

		try {
			$updater->update();
		}
		catch ( InvalidArgumentException $e ) {
			Fence_Plus_Utility::add_admin_notification( $e->getMessage(), 'error' );
			do_action( 'fence_plus_fencer_update_failed', $this, $e );

			return;
		}

		do_action( 'fence_plus_fencer_updated', $this );
	}

	/**
	 * Register actions to be fired if we have to process the data,
	 * that is if the new data is different than the current data.
	 *
	 * Verified by an MD5 hash comparing the new data to the old data
	 *
	 * @see Fence_Plus_Fencer_Update
	 */
	public function register_update_actions() {
		add_action( 'fence_plus_fencer_process_results', array( $this, 'calculate_primary_weapon' ) );
		add_action( 'fence_plus_fencer_process_results', array( $this, 'calculate_age_bracket' ) );
	}

	/**
	 * Removes fencer from database
	 */
	public function delete() {
		if ( current_user_can( 'delete_users' ) ) {
			$this->remove_data();
			wp_delete_user( $this->wp_id );
		}
		else {
			wp_die( 'You don\'t have permissions to delete that user' );
			die();
		}
	}

	/**
	 * Remove all data associated with a fencer
	 */
	public function remove_data() {
		if ( current_user_can( 'delete_users' ) ) {
			$factory = new Fence_Plus_Person_Factory();

			foreach ( $this->get_editable_by_users() as $user_id ) {
				try {
					$person = $factory->make( (int) $user_id);
					$person->remove_editable_user($this->get_wp_id());
					$person->save();
				}
				catch ( InvalidArgumentException $e ) {
					continue;
				}
			}
			delete_user_meta( $this->wp_id, 'fence_plus_fencer_data' );
		}
	}

	/*========================
		Utility Functions
	=========================*/

	/**
	 * Load fencer data into the object's properties
	 *
	 * @return bool, false if at least one function did not properly fire
	 */
	private function load_data() {
		$fencerdata = get_user_meta( $this->wp_id, 'fence_plus_fencer_data', true );

		if ( ! empty( $fencerdata ) && is_array( $fencerdata ) ) {
			return $this->set_all_properties( $fencerdata );
		}
		else {
			return false;
		}
	}

	/**
	 * @param array $fencerdata
	 *
	 * @return bool|mixed
	 */
	public function set_all_properties( array $fencerdata ) {
		$state = true;
		foreach ( $fencerdata as $key => $data ) {
			$state = call_user_func( array( $this, 'set_' . $key ), $data );
			// set all properties by calling internal setters based on fencer user meta data key
		}

		return $state;
	}

	/**
	 * Returns array of fencer's highest rated weapons
	 * If the highest rating has two or more weapons they are all added to the array
	 * If the highest rating is a 'U' then an empty array is returned
	 */
	public function calculate_primary_weapon() {
		$ratings = $this->get_usfa_ratings();

		if ( is_array( $ratings ) ) {

			usort( $ratings, function ( $a, $b ) {
					return strcmp( $a['letter'] . abs( 3000 - (int) $a['year'] ), $b['letter'] . abs( 3000 - (int) $b['year'] ) ); // sort based on rating letter and year
				}
			);

			if ( $ratings[0]['letter'] == "U" ) {
				$primary_weapon = array();
			}
			else {
				$primary_weapon = array( $ratings[0]['weapon'] );

				if ( $ratings[0]['letter'] . $ratings[0]['year'] == $ratings[1]['letter'] . $ratings[1]['year'] ) {
					$primary_weapon[] = $ratings[1]['weapon'];

					if ( $ratings[1]['letter'] . $ratings[1]['year'] == $ratings[2]['letter'] . $ratings[2]['year'] ) { // if all ratings are equal to each other
						$primary_weapon[] = $ratings[2]['weapon'];
					}
				}
			}
		}
		else {
			$primary_weapon = array();
		}

		$this->set_primary_weapon( apply_filters( "fence_plus_calculate_primary_weapon", $primary_weapon, $this ) );
	}

	/**
	 * Returns array of age brackets the fencer belongs to
	 *
	 * Possible values: V40, V50, V60, V70, JR, CDT, Y14, Y12, Y10, Y8
	 */
	public function calculate_age_bracket() {
		$age_bracket = array();
		$this->set_age_bracket( apply_filters( "fence_plus_calculate_age_bracket", $age_bracket, $this ) );
	}

	/**
	 * Add a suggested tournament
	 *
	 * @param $tournament_id int askFRED tournament ID
	 * @param $event_ids array int askFRED event IDs
	 * @param $coach_id int WP Coach User ID
	 *
	 * @throws InvalidArgumentException if student doesn't have that coach listed
	 */
	public function add_tournament( $tournament_id, array $event_ids, $coach_id ) {

		if ( ! in_array( $coach_id, $this->get_editable_users() ) )
			throw new InvalidArgumentException( "Invalid permissions", 3 );

		$tournaments = $this->get_coach_suggested_tournaments();
		$tournaments[$tournament_id] = array(
			'tournament_id' => $tournament_id,
			'coach'         => $coach_id,
			'time'          => time(),
			'events'        => $event_ids
		);

		do_action( 'fence_plus_add_tournament_to_student', $this, $tournament_id, $event_ids, $coach_id );
	}

	/**
	 * Remove a suggested tournament
	 *
	 * @param $tournament_id int askFRED tournament ID
	 */
	public function remove_tournament( $tournament_id ) {
		$tournaments = $this->get_coach_suggested_tournaments();
		unset( $tournaments[$tournament_id] );
		$this->set_coach_suggested_tournaments( $tournaments );

		do_action( 'fence_plus_remove_tournament_from_student', $this, $tournament_id );
	}

	/**
	 * Add a tournament event to a previously suggested tournament
	 *
	 * @param $tournament_id int askFRED tournament ID
	 * @param $event_id int askFRED tournament ID
	 * @param $coach_id int WP Coach User ID
	 *
	 * @throws InvalidArgumentException if tournament DNE
	 */
	public function add_tournament_event( $tournament_id, $event_id, $coach_id ) {
		$tournaments = $this->get_coach_suggested_tournaments();

		if ( ! isset( $tournaments[$tournament_id] ) )
			throw new InvalidArgumentException( "Tournament does not exist", 2 );

		$tournaments[$tournament_id]['events'][] = $event_id;
		$this->set_coach_suggested_tournaments( $tournaments );

		do_action( 'fence_plus_add_tournament_event_to_student', $this, $tournament_id, $event_id, $coach_id );
	}

	/**
	 * Remove a tournament event from suggested tournament list
	 *
	 * @param $tournament_id
	 * @param $event_id
	 *
	 * @throws InvalidArgumentException if tournament DNE
	 */
	public function remove_tournament_event( $tournament_id, $event_id ) {
		$tournaments = $this->get_coach_suggested_tournaments();

		if ( ! isset( $tournaments[$tournament_id] ) )
			throw new InvalidArgumentException( "Tournament does not exist", 2 );

		unset( $tournaments[$tournament_id]['events'][$event_id] );
		$this->set_coach_suggested_tournaments( $tournaments );

		do_action( 'fence_plus_remove_tournament_event', $this, $tournament_id, $event_id );
	}

	/*============
		Views
	=============*/

	/**
	 * Larger summary box
	 */
	public function summary_box() {
		wp_enqueue_script( 'fence-plus-profile-overview' );
		wp_enqueue_style( 'fence-plus-profile-overview' );
		wp_enqueue_style( 'genericons' );

		wp_localize_script( 'fence-plus-profile-overview', 'fence_plus_ajax', array(
				'ajax_url' => admin_url( 'admin-ajax.php' )
			)
		);
		?>
		<div class="fence-plus-fencer-overview postbox" id="fencer-<?php echo $this->get_usfa_id(); ?>" data-wp-id="<?php echo $this->get_wp_id(); ?>">
        <div class="inside">

            <div class="fencer-overview spacing-wrapper">
                <div class="fencer-avatar left">
	                <?php echo get_avatar( $this->get_wp_id(), 160 ); ?>
                </div>

                <div class="fencer-data right">
                    <div class="fencer-primary-weapon" data-usfa-id="<?php echo $this->get_usfa_id(); ?>">
                        <span class="saved-weapon">
	                        <?php $primary_weapon = $this->get_primary_weapon();
	                        echo $none = empty( $primary_weapon ) ? __( "N/A", Fence_Plus::SLUG ) : implode( ", ", $primary_weapon ); ?>
                        </span>
	                    <?php if ( $none == true ) : ?>
		                    <select class="new-weapon" style="display: none;">
			                    <option name="n/a"><?php _e( 'N/A', Fence_Plus::SLUG ); ?></option>
			                    <option name="epee"><?php _e( "Epee", Fence_Plus::SLUG ); ?></option>
			                    <option name="foil"><?php _e( "Foil", Fence_Plus::SLUG ); ?></option>
			                    <option name="saber"><?php _e( "Saber", Fence_Plus::SLUG ); ?></option>
		                    </select>
	                    <?php endif; ?>
	                    <span class="genericon genericon-edit"></span>
                    </div>

                    <div class="fencer-rating">
                        <?php $primary_weapon_rating = $this->get_primary_weapon_rating();
                        echo empty( $primary_weapon ) ? "<br>" : implode( ", ", $primary_weapon_rating ); ?>
                    </div>

                    <div class="fencer-usfa-id">
                        <?php echo $this->get_usfa_id(); ?>
                    </div>
                </div>

                <div class="fencer-info">
                    <h2 class="fencer-display-name">
	                    <a href="<?php echo add_query_arg( array( 'fence_plus_fencer_data' => '1', 'fp_id' => $this->get_wp_id() ), get_edit_user_link( $this->wp_id ) ); ?>">
		                    <?php echo $this->get_first_name() . " " . $this->get_last_name(); ?>
	                    </a>
                    </h2>

                    <div class="fencer-birthyear">
                        <?php echo sprintf( __( "Born %d", Fence_Plus::SLUG ), $this->get_birthyear() ); ?>
                    </div>

                    <div class="fencer-performance">
                        <a class="fencer-show-more-info" href="#" data-usfa-id="<?php echo $this->get_usfa_id(); ?>"><?php _e( "View More Information", Fence_Plus::SLUG ); ?></a>
                    </div>
                </div>
            </div>
	        <div class="fencer-more-info-box spacing-wrapper">
		        <div class="fencer-more-info-container">
			        <div class="row-headings left">
				        <p><?php _e( 'Epee', Fence_Plus::SLUG ); ?></p>
				        <p><?php _e( 'Foil', Fence_Plus::SLUG ); ?></p>
				        <p><?php _e( 'Saber', Fence_Plus::SLUG ); ?></p>
				        <p><?php _e( 'Gender', Fence_Plus::SLUG ); ?></p>
				        <?php do_action( 'fence_plus_fencer_summary_box_row_heading', $this ); ?>
			        </div>
			        <div class="row-values left">
				        <p><?php echo $this->get_epee_letter() . $this->get_epee_year(); ?></p>
				        <p><?php echo $this->get_foil_letter() . $this->get_foil_year(); ?></p>
				        <p><?php echo $this->get_saber_letter() . $this->get_saber_year(); ?></p>
				        <p><?php echo $this->get_gender_full(); ?></p>
				        <?php do_action( 'fence_plus_fencer_summary_box_info_row_value', $this ); ?>
			        </div>

			        <?php do_action( 'fence_plus_summary_box_more_info_after', $this ); ?>
		        </div>
	        </div>
        </div>
    </div>
	<?php
	}

	/**
	 * Small profile box, used on coach summary pages
	 */
	public function short_box() {
		wp_enqueue_script( 'fence-plus-profile-overview' );
		wp_enqueue_style( 'fence-plus-profile-overview' );
		?>
		<div class="fence-plus-fencer-overview overview-small postbox" id="fencer-<?php echo $this->get_usfa_id(); ?>">
        <div class="inside">
            <div class="fencer-overview spacing-wrapper">
                <div class="fencer-data right">
                    <div class="fencer-primary-weapon">
	                    <?php $primary_weapon = $this->get_primary_weapon();
	                    echo empty( $primary_weapon ) ? __( "N/A", Fence_Plus::SLUG ) : implode( ", ", $primary_weapon ); ?>
                    </div>
                    <div class="fencer-rating">
	                    <?php $primary_weapon_rating = $this->get_primary_weapon_rating();
	                    echo empty( $primary_weapon ) ? "<br>" : implode( ", ", $primary_weapon_rating ); ?>
                    </div>
                </div>

                <div class="fencer-info">
                    <h2 class="fencer-display-name">
	                    <a href="<?php echo add_query_arg( array( 'fence_plus_fencer_data' => '1', 'fp_id' => $this->get_wp_id() ), get_edit_user_link( $this->wp_id ) ); ?>">
		                    <?php echo $this->get_first_name() . " " . $this->get_last_name(); ?>
	                    </a>
                    </h2>
                    <div class="fencer-birthyear"><?php echo sprintf( __( "Born %d", Fence_Plus::SLUG ), $this->get_birthyear() ); ?></div>
                </div>
            </div>
        </div>
    </div>
	<?php
	}

	/*========================
		Getters and Setters
	=========================*/

	/**
	 * @return string
	 */
	public function get_name() {
		return $this->get_first_name() . " " . $this->get_last_name();
	}

	/**
	 * @param array $coach_suggested_tournaments
	 */
	public function set_coach_suggested_tournaments( $coach_suggested_tournaments ) {
		$this->coach_suggested_tournaments = $coach_suggested_tournaments;
	}

	/**
	 * @return array
	 */
	public function get_coach_suggested_tournaments() {
		return $this->coach_suggested_tournaments;
	}

	/**
	 * @return mixed
	 */
	public function get_foil_letter() {
		if ( isset( $this->usfa_ratings['foil']['letter'] ) )
			$letter = $this->usfa_ratings['foil']['letter'];
		else
			$letter = "U";

		return $letter;
	}

	/**
	 * @return mixed
	 */
	public function get_foil_year() {
		return $this->usfa_ratings['foil']['year'];
	}

	/**
	 * @return mixed
	 */
	public function get_foil_authority() {
		return $this->usfa_ratings['foil']['authority'];
	}

	/**
	 * @return mixed
	 */
	public function get_saber_letter() {
		if ( isset( $this->usfa_ratings['saber']['letter'] ) )
			$letter = $this->usfa_ratings['saber']['letter'];
		else
			$letter = "U";

		return $letter;
	}

	/**
	 * @return mixed
	 */
	public function get_saber_year() {
		return $this->usfa_ratings['saber']['year'];
	}

	/**
	 * @return mixed
	 */
	public function get_saber_authority() {
		return $this->usfa_ratings['saber']['authority'];
	}

	/**
	 * @return mixed
	 */
	public function get_epee_letter() {
		if ( isset( $this->usfa_ratings['epee']['letter'] ) )
			$letter = $this->usfa_ratings['epee']['letter'];
		else
			$letter = "U";

		return $letter;
	}

	/**
	 * @return mixed
	 */
	public function get_epee_year() {
		return $this->usfa_ratings['epee']['year'];
	}

	/**
	 * @return mixed
	 */
	public function get_epee_authority() {
		return $this->usfa_ratings['epee']['authority'];
	}

	/**
	 * @return array
	 */
	public function get_primary_weapon_rating() {
		$weapons = $this->get_primary_weapon();
		$ratings = array();

		foreach ( $weapons as $weapon ) {
			$ratings[] = call_user_func( array( $this, 'get_' . strtolower( $weapon ) . "_letter" ) ) . call_user_func( array( $this, 'get_' . strtolower( $weapon ) . "_year" ) );
		}

		return $ratings;
	}

	/**
	 * @return array|bool
	 */
	public function get_primary_weapon_rating_year() {
		$weapons = $this->get_primary_weapon();

		if ( ! is_array( $weapons ) )
			return array();

		$ratings = array();

		foreach ( $weapons as $weapon ) {
			$ratings[] = call_user_func( array( $this, 'get_' . strtolower( $weapon ) . "_year" ) );
		}

		return $ratings;
	}

	/**
	 * @return array
	 */
	public function get_primary_weapon_rating_letter() {
		$weapons = $this->get_primary_weapon();

		if ( ! is_array( $weapons ) )
			return array();

		$ratings = array();

		foreach ( $weapons as $weapon ) {
			$ratings[] = call_user_func( array( $this, 'get_' . strtolower( $weapon ) . "_letter" ) );
		}

		return $ratings;
	}

	/**
	 * @return string
	 */
	public function get_gender_full() {
		if ( $this->get_gender() == "M" )
			return "Male";
		else
			return "Female";
	}

	/*====================================
	  Getters and setters for properties
	=====================================*/

	/**
	 * @param  $age_bracket
	 */
	public function set_age_bracket( $age_bracket ) {
		$this->age_bracket = $age_bracket;
	}

	/**
	 * @return string
	 */
	public function get_age_bracket() {
		return $this->age_bracket;
	}

	/**
	 * @param  $birthyear
	 */
	public function set_birthyear( $birthyear ) {
		$this->birthyear = $birthyear;
	}

	/**
	 * @return int
	 */
	public function get_birthyear() {
		return $this->birthyear;
	}

	/**
	 * @param  $cff_id
	 */
	public function set_cff_id( $cff_id ) {
		$this->cff_id = $cff_id;
	}

	/**
	 * @return string
	 */
	public function get_cff_id() {
		return $this->cff_id;
	}

	/**
	 * @param array $clubs
	 */
	public function set_clubs( $clubs ) {
		$this->clubs = $clubs;
	}

	/**
	 * @return array
	 */
	public function get_clubs() {
		return $this->clubs;
	}

	/**
	 * @param array $division
	 */
	public function set_division( $division ) {
		$this->division = $division;
	}

	/**
	 * @return array
	 */
	public function get_division() {
		return $this->division;
	}

	/**
	 * @param  $fie_id
	 */
	public function set_fie_id( $fie_id ) {
		$this->fie_id = $fie_id;
	}

	/**
	 * @return string
	 */
	public function get_fie_id() {
		return $this->fie_id;
	}

	/**
	 * @param  $first_name
	 */
	public function set_first_name( $first_name ) {
		$this->first_name = $first_name;
	}

	/**
	 * @return string
	 */
	public function get_first_name() {
		return $this->first_name;
	}

	/**
	 * @param  $gender
	 */
	public function set_gender( $gender ) {
		$this->gender = $gender;
	}

	/**
	 * @return string
	 */
	public function get_gender() {
		return $this->gender;
	}

	/**
	 * @param  $id
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @param  $last_name
	 */
	public function set_last_name( $last_name ) {
		$this->last_name = $last_name;
	}

	/**
	 * @return string
	 */
	public function get_last_name() {
		return $this->last_name;
	}

	/**
	 * @param  $primary_club_id
	 */
	public function set_primary_club_id( $primary_club_id ) {
		$this->primary_club_id = $primary_club_id;
	}

	/**
	 * @return int
	 */
	public function get_primary_club_id() {
		return $this->primary_club_id;
	}

	/**
	 * @param  $primary_weapon
	 */
	public function set_primary_weapon( $primary_weapon ) {
		if ( ! empty( $primary_weapon ) ) {
			$this->primary_weapon = $primary_weapon;
		}
	}

	/**
	 * @return array
	 */
	public function get_primary_weapon() {
		return $this->primary_weapon;
	}

	/**
	 * @param  $usfa_id
	 */
	public function set_usfa_id( $usfa_id ) {
		$this->usfa_id = $usfa_id;
	}

	/**
	 * @return string
	 */
	public function get_usfa_id() {
		return $this->usfa_id;
	}

	/**
	 * @param array $usfa_ratings
	 */
	public function set_usfa_ratings( $usfa_ratings ) {
		$this->usfa_ratings = $usfa_ratings;
	}

	/**
	 * @return array
	 */
	public function get_usfa_ratings() {
		return $this->usfa_ratings;
	}

	/**
	 * @param $md5_checksum
	 */
	public function set_md5_checksum( $md5_checksum ) {
		$this->md5_checksum = $md5_checksum;
	}

	/**
	 * @return string
	 */
	public function get_md5_checksum() {
		return $this->md5_checksum;
	}
}