<?php
/**
 * Fencer Class
 *
 * @package Fence Plus
 * @subpackage includes
 * @since 0.1
 */

class Fence_Plus_Fencer {

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
	 * @var int WordPress User ID
	 */
	private $wp_id;
	/**
	 * Array of primary weapons
	 *
	 * @var array foil|epee|saber
	 * @see calculate_primary_weapon
	 */
	private $primary_weapon;
	/**
	 * @var string age brackets
	 * @see
	 * @see calculate_age_bracket
	 */
	private $age_bracket;

	/**
	 * Holds array of coaches that student has by their WordPress user ID
	 * @var array
	 */
	private $coaches = array();

	/**
	 * @var array
	 *  'tournament_id' => askFRED tournament ID,
	 *  'coach'         => coach WP User ID,
	 *  'time'          => unix timestamp of date coach referred,
	 *  'events'        => array() askFRED array of event IDs
	 */
	private $coach_suggested_tournaments = array();

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
	 * @param int|null $user_id
	 * @param string|null $usfa_id
	 * @param array|null $raw_data
	 *
	 * @throws InvalidArgumentException
	 *  1. If the user does not exist and USFA ID not provided
	 *  2. Raw API data is provided, but not a User ID to save it to
	 */
	private function __construct( $user_id = null, $usfa_id = null, $raw_data = null ) {
		if ( $raw_data === null ) {
			if ( null === $user_id ) {
				$user_id = self::get_user_id_from_usfa_id( $usfa_id );
			}

			$this->wp_id = $user_id;

			$fencerdata = get_user_meta( $user_id, 'fence_plus_fencer_data', true );

			if ( ! empty( $fencerdata ) ) {
				foreach ( $fencerdata as $key => $data ) {
					call_user_func( array( $this, 'set_' . $key ), $data );
					// set all properties by calling internal setters based on fencer user meta data key
				}
			}
			else if ( $usfa_id != null ) {
				$this->usfa_id = $usfa_id;
				$this->update();
				$this->save();
			}
			else {
				throw new InvalidArgumentException( "Fencer data does not exist. Instantiate with USFA ID", 1 );
			}
		}
		else {
			if ( null == $user_id ) {
				throw new InvalidArgumentException( "User ID must be provided when instantiating with raw API data", 2 );
			}

			$this->wp_id = $user_id;
			$this->process_api_data( array( $raw_data ) );
			$this->interpret_data();
			$this->save();
		}
	}

	/*========================
		Creation Functions
	=========================*/

	/**
	 * @param $usfa_id
	 *
	 * @return Fence_Plus_Fencer
	 */
	public static function init_from_usfa_id( $usfa_id ) {
		return new Fence_Plus_Fencer( null, $usfa_id );
	}

	/**
	 * @param $user_id
	 *
	 * @return Fence_Plus_Fencer
	 */
	public static function init_from_wp_id( $user_id ) {
		return new Fence_Plus_Fencer( $user_id );
	}

	/**
	 * Creates a WordPress user from data from API and
	 * returns a Fence_Plus_Fencer object
	 *
	 * @param $data array raw data from API
	 * @param $email string user email address
	 *
	 * @throws LogicException
	 *
	 * @return Fence_Plus_Fencer
	 */
	public static function insert_user_from_api_data( $data, $email ) {
		$args = array(
			'role'       => 'fencer',
			'user_pass'  => $data['usfa_id'],
			'user_login' => $data['first_name'] . " " . $data['last_name'],
			'user_email' => $email
		);

		$args = apply_filters( 'fence_plus_insert_fencer_args', $args, $data );

		$user_id = wp_insert_user( $args );

		if ( is_wp_error( $user_id ) ) {
			throw new LogicException( 'User already exists', 1 );
		}

		do_action( 'fence_plus_fencer_user_created', $user_id );

		return new Fence_Plus_Fencer( $user_id, null, $data );
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

		do_action( 'fence_plus_fencer_saved', $this->wp_id );
	}

	/**
	 * Update fencer from askFRED
	 */
	public function update() {
		require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . "class-askFRED_API.php" );

		$args = array(
			'version'  => 'v1',
			'resource' => 'fencer',
			'usfa_id'  => $this->usfa_id
		);

		$args = apply_filters('fence_plus_fencer_update_args', $args, $this->get_id());

		$askfred_api = new askFRED_API( AF_API_KEY, $args );
		$results = $askfred_api->get_results();
		$this->process_api_data( $results );
		$this->interpret_data();

		do_action( 'fence_plus_fencer_updated', $this->wp_id );
	}

	/**
	 * @param $data
	 */
	public function update_from_data( $data ) {
		$this->process_api_data( $data );
		$this->interpret_data();
	}

	/**
	 * Removes fencer from database
	 */
	public function delete() {
		if ( current_user_can( 'delete_users' ) ) {
			$user = get_user_by( 'id', $this->wp_id );
			$user_email = $user->user_email;
			delete_user_meta( $this->wp_id, 'fence_plus_fencer_data' );
			wp_delete_user( $this->wp_id );
		}
		else {
			wp_die( 'You don\'t have permissions to delete that user' );
		}
		do_action( 'fence_plus_fencer_deleted', $user_email );
	}

	/*========================
		Utility Functions
	=========================*/

	/**
	 * @param $data
	 */
	private function process_api_data( $data ) {
		if ( isset( $data[0] ) )
			$data = $data[0];

		foreach ( $data as $key => $value ) {
			call_user_func( array( $this, 'set_' . $key ), $value );
		}
	}

	/**
	 * Return user ID from USFA ID
	 *
	 * @param $usfa_id
	 *
	 * @return string|bool WordPress user ID
	 */
	public static function get_user_id_from_usfa_id( $usfa_id ) {
		$fencers = get_users( array( "role" => "fencer" ) );
		foreach ( $fencers as $fencer ) {
			$fencer_meta = get_user_meta( $fencer->ID, 'fence_plus_fencer_data', true );
			if ( $usfa_id == $fencer_meta['usfa_id'] )
				return $fencer->ID;
		}
		return false;
	}

	/**
	 *
	 */
	public function interpret_data() {
		$this->set_primary_weapon( self::calculate_primary_wepaon( $this->get_usfa_ratings() ) );
		$this->set_age_bracket( self::calculate_age_bracket( $this->get_birthyear() ) );
	}

	/**
	 * Returns array of fencer's highest rated weapons
	 * If the highest rating has two or more weapons they are all added to the array
	 * If the highest rating is a 'U' then an empty array is returned
	 *
	 * @param $ratings
	 *
	 * @return array
	 */
	public static function calculate_primary_wepaon( $ratings ) {
		if ( ! is_array( $ratings ) ) {
			return array();
		}
		usort( $ratings, function ( $a, $b ) {
				return strcmp( $a['letter'], $b['letter'] );
			}
		);

		if ( $ratings[0]['letter'] == "U" )
			return array();

		$primary_weapon = array( $ratings[0]['weapon'] );

		if ( $ratings[0]['letter'] == $ratings[1]['letter'] ) {
			$primary_weapon[] = $ratings[1]['weapon'];

			if ( $ratings[1]['letter'] == $ratings[2]['letter'] ) { // if all ratings are equal to each other
				$primary_weapon[] = $ratings[2]['weapon'];
			}
		}

		return apply_filters( "fence_plus_calculate_primary_weapon", $primary_weapon, $ratings );
	}

	/**
	 * Returns array of age brackets the fencer belongs to
	 *
	 * Possible values: V40, V50, V60, V70, JR, CDT, Y14, Y12, Y10, Y8
	 *
	 * @param $birthyear int
	 *
	 * @return array
	 */
	public static function calculate_age_bracket( $birthyear ) {
		return array();
	}

	/**
	 * Add a coach to fencer
	 *
	 * @param $coach_user_id
	 */
	public function add_coach( $coach_user_id ) {
		$current_coaches = $this->get_coaches();
		$this->set_coaches( $current_coaches[$coach_user_id] );
		do_action( 'fence_plus_add_coach_to_student', $this->wp_id, $coach_user_id );
	}

	/**
	 * Remove coach from fencer
	 *
	 * @param $coach_user_id
	 */
	public function remove_coach( $coach_user_id ) {
		$existing_coaches = $this->get_coaches();
		unset( $existing_coaches[$coach_user_id] );
		$this->set_coaches( $existing_coaches );
		do_action( 'fence_plus_remove_coach_from_student', $this->wp_id, $coach_user_id );
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

		if ( ! in_array( $coach_id, $this->get_coaches() ) )
			throw new InvalidArgumentException( "Invalid permissions", 3 );

		$tournaments = $this->get_coach_suggested_tournaments();
		$tournaments[$tournament_id] = array(
			'tournament_id' => $tournament_id,
			'coach'         => $coach_id,
			'time'          => time(),
			'events'        => $event_ids
		);
		do_action( 'fence_plus_add_tournament_to_student', $this->wp_id, $tournament_id, $event_ids, $coach_id );
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
		do_action( 'fence_plus_remove_tournament_from_student', $this->wp_id, $tournament_id );
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
		do_action( 'fence_plus_add_tournament_event_to_student', $this->wp_id, $tournament_id, $event_id, $coach_id );
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
		do_action( 'fence_plus_remove_tournament_event', $this->wp_id, $tournament_id, $event_id );
	}

	/*========================
		Getters and Setters
	=========================*/

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
		$letter = $this->usfa_ratings['foil']['letter'];

		if ( ! isset( $letter ) ) {
			$letter = "U";
		}
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
		$letter = $this->usfa_ratings['saber']['letter'];

		if ( ! isset( $letter ) ) {
			$letter = "U";
		}
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
		$letter = $this->usfa_ratings['epee']['letter'];

		if ( ! isset( $letter ) ) {
			$letter = "U";
		}
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
	 * @param array $coaches
	 */
	public function set_coaches( $coaches ) {
		$this->coaches = $coaches;
	}

	/**
	 * @return array
	 */
	public function get_coaches() {
		return $this->coaches;
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
		$current_weapon = $this->get_primary_weapon();

		if ( empty( $current_weapon ) ) {
			$this->primary_weapon = $primary_weapon;
		}
		else if ( ! empty( $primary_weapon ) ) {
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
	 * @param int $wp_id
	 */
	public function set_wp_id( $wp_id ) {
		$this->wp_id = $wp_id;
	}

	/**
	 * @return int
	 */
	public function get_wp_id() {
		return $this->wp_id;
	}
}