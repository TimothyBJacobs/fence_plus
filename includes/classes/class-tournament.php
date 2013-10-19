<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_Tournament {
	/**
	 * @var int tournament ID
	 */
	private $id;
	/**
	 * @var string
	 */
	private $name;
	/**
	 * @var string ISO 8601 tournament start date
	 */
	private $start_date;
	/**
	 * @var string ISO 8601 tournament end date
	 */
	private $end_date;
	/**
	 * @var string short comments for displaying in search results
	 */
	private $comments;
	/**
	 * @var string ISO 8601 when preregistration opens
	 */
	private $prereg_open;
	/**
	 * @var string ISO 8601 when preregistration closes
	 */
	private $prereg_close;
	/**
	 * @var string USFA|FIE| todo find out additional possible values
	 */
	private $authority;
	/**
	 * @var bool is a fee required to pre-register, ie
	 * can you register online and pay for event in-person
	 */
	private $is_fee_required;
	/**
	 * @var bool is the tournament a ROC event
	 */
	private $is_roc;
	/**
	 * @var bool is the tournament a baycup event
	 */
	private $is_baycup;
	/**
	 * @var bool is the tournament visible through askFRED search
	 */
	private $is_visible;
	/**
	 * @var bool has the tournament been cancelled
	 */
	private $is_cancelled;
	/**
	 * @var bool can you pay tournament fees online
	 */
	private $is_accepting_fees_online;
	/**
	 * @var int tournament fee
	 * might not be set for tournaments that don't have a tournament fee
	 * property initiated to handle this case
	 */
	private $fee = 0;
	/**
	 * @var string fee currency "USD"| todo find out other possible values
	 */
	private $fee_currency;
	/**
	 * @var string tournament extended info
	 */
	private $more_info;

	/**
	 * @var array
	 *  name        => (string) Venue Name
	 *  address     => (string) Venue address
	 *  city        => (string) Venue City
	 *  state       => (string) two letter state initials
	 *  zip         => (string) zip code
	 *  country     => (string) country initials
	 *  timezone    => (string) timezone eg. America/Chicago
	 *  latitude    => (int) latitude
	 *  longitude   => (int) longitude
	 *  geocode_precision   => (string) "street"|"zip_code"|
	 *
	 */
	private $venue = array();

	/**
	 * @var array division
	 *  id      => (int) division ID
	 *  name    => (string) Division Name
	 *  abbrev  => (string) Division abbreviation
	 */
	private $division = array();

	/**
	 * @var array event_id => Fence_Plus_Event object
	 */
	private $events = array();

	/**
	 * @var
	 */
	private $wp_post_id;

	/**
	 * @param int|null $post_id WP Post ID of tournament
	 * @param int|null $tournament_id askFRED tournament resource ID
	 * @param array|null $api_data raw data from API
	 *
	 * @throws InvalidArgumentException
	 *  1. If the post meta does not exist and askFRED tournament ID not provided
	 *  2. Raw API data is provided, but not a Post ID to save it to
	 */
	private function __construct( $post_id = null, $tournament_id = null, $api_data = null ) {
		if ( null !== $api_data ) {
			if ( null === $post_id ) {
				throw new InvalidArgumentException( "Post ID must be provided when instantiating with raw API data", 2 );
			}
			$this->set_wp_post_id( $post_id );
			$this->set_all_properties( $api_data );
			$this->interpret_data();
		}

		$tournament_data = get_post_meta( $post_id, "fence_plus_tournament_data", true );

		if ( empty( $tournament_data ) ) { // if the post does not exist yet
			if ( null == $tournament_id ) {
				throw new InvalidArgumentException( "Tournament data does not exist", 1 );
			}
			else {
				$this->update(); // poll the API
			}
		}
		else {
			$this->set_all_properties( $tournament_data );
		}
	}

	/*========================
		Creation Functions
	=========================*/
	/**
	 * @param $post_id
	 *
	 * @return Fence_Plus_Tournament
	 */
	public static function init_from_post_id( $post_id ) {
		return new Fence_Plus_Tournament( $post_id );
	}

	/**
	 * @param $tournament_id
	 *
	 * @return Fence_Plus_Tournament
	 */
	public static function init_from_tournament_id( $tournament_id ) {
		return new Fence_Plus_Tournament( $tournament_id );
	}

	/**
	 * @param $api_data
	 *
	 * @return Fence_Plus_Tournament
	 */
	public static function insert_tournament_from_api_data( array $api_data ) {
		$args = array(
			'post_type'  => 'tournament',
			'post_title' => $api_data['name']
		);

		$args = apply_filters( 'fence_plus_insert_tournament_args', $args, $api_data );

		$post_id = wp_insert_post( $args );

		do_action( 'fence_plus_tournament_post_created', $post_id );

		return new Fence_Plus_Tournament( $post_id, null, $api_data );
	}

	/*========================
		Database Functions
	=========================*/

	/**
	 * Saves current instance of object to the database
	 */
	public function save() {
		$tournament_data = array();
		foreach ( $this as $key => $value ) {
			$tournament_data[$key] = $value;
		}
		update_post_meta( $this->get_wp_post_id(), 'fence_plus_tournament_data', $tournament_data );

		do_action( 'fence_plus_tournament_post_saved', $this->wp_post_id );
	}

	/**
	 * Updated current instance of object from API
	 *
	 * @uses askFRED_API
	 */
	public function update() {
		$args = array(
			'version'        => 'v1',
			'resource'       => 'tournament',
			'tournament_ids' => $this->get_id()
		);

		$args = apply_filters( 'fence_plus_tournament_post_update_args', $args, $this->get_id() );

		try {
			$askFRED_api = new askFRED_API( Fence_Plus_Options::get_instance()->api_key, $args );
			$results = $askFRED_api->get_results();
		} catch (InvalidArgumentException $e) {
			Fence_Plus_Utility::add_admin_notification($e->getMessage(), 'error');
			wp_die($e->getMessage());
			die();
		}


		$this->set_all_properties( $results );
		$this->interpret_data();

		do_action( 'fence_plus_tournament_post_updated', $this->get_id() );
	}

	/**
	 *
	 */
	public function delete() {
		if ( current_user_can( 'delete_posts' ) ) {
			wp_delete_post( $this->wp_post_id );
		}
		else {
			wp_die( 'You don\'t have permissions to delete that tournament post' );
		}
		do_action( 'fence_plus_tournament_post_deleted' );
	}

	/*========================
		Utility Functions
	=========================*/

	/**
	 *
	 */
	private function interpret_data() {

	}

	/*========================
		Getters and Setters
	=========================*/

	/**
	 * @param $data
	 */
	private function set_all_properties( $data ) {
		foreach ( $data as $key => $value ) {
			call_user_func( array( $this, 'set_' . $key ), $value );
			// set all properties by calling internal setters based on key value pairs
		}
	}

	/**
	 * @param string $authority
	 */
	public function set_authority( $authority ) {
		$this->authority = $authority;
	}

	/**
	 * @return string
	 */
	public function get_authority() {
		return $this->authority;
	}

	/**
	 * @param string $comments
	 */
	public function set_comments( $comments ) {
		$this->comments = $comments;
	}

	/**
	 * @return string
	 */
	public function get_comments() {
		return $this->comments;
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
	 * @param string $end_date
	 */
	public function set_end_date( $end_date ) {
		$this->end_date = $end_date;
	}

	/**
	 * @return string
	 */
	public function get_end_date() {
		return $this->end_date;
	}

	/**
	 * @param array $events
	 */
	public function set_events( $events ) {
		$this->events = $events;
	}

	/**
	 * @return array
	 */
	public function get_events() {
		return $this->events;
	}

	/**
	 * @param int $fee
	 */
	public function set_fee( $fee ) {
		$this->fee = $fee;
	}

	/**
	 * @return int
	 */
	public function get_fee() {
		return $this->fee;
	}

	/**
	 * @param string $fee_currency
	 */
	public function set_fee_currency( $fee_currency ) {
		$this->fee_currency = $fee_currency;
	}

	/**
	 * @return string
	 */
	public function get_fee_currency() {
		return $this->fee_currency;
	}

	/**
	 * @param int $id
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
	 * @param boolean $is_accepting_fees_online
	 */
	public function set_is_accepting_fees_online( $is_accepting_fees_online ) {
		$this->is_accepting_fees_online = $is_accepting_fees_online;
	}

	/**
	 * @return boolean
	 */
	public function get_is_accepting_fees_online() {
		return $this->is_accepting_fees_online;
	}

	/**
	 * @param boolean $is_baycup
	 */
	public function set_is_baycup( $is_baycup ) {
		$this->is_baycup = $is_baycup;
	}

	/**
	 * @return boolean
	 */
	public function get_is_baycup() {
		return $this->is_baycup;
	}

	/**
	 * @param boolean $is_cancelled
	 */
	public function set_is_cancelled( $is_cancelled ) {
		$this->is_cancelled = $is_cancelled;
	}

	/**
	 * @return boolean
	 */
	public function get_is_cancelled() {
		return $this->is_cancelled;
	}

	/**
	 * @param boolean $is_fee_required
	 */
	public function set_is_fee_required( $is_fee_required ) {
		$this->is_fee_required = $is_fee_required;
	}

	/**
	 * @return boolean
	 */
	public function get_is_fee_required() {
		return $this->is_fee_required;
	}

	/**
	 * @param boolean $is_roc
	 */
	public function set_is_roc( $is_roc ) {
		$this->is_roc = $is_roc;
	}

	/**
	 * @return boolean
	 */
	public function get_is_roc() {
		return $this->is_roc;
	}

	/**
	 * @param boolean $is_visible
	 */
	public function set_is_visible( $is_visible ) {
		$this->is_visible = $is_visible;
	}

	/**
	 * @return boolean
	 */
	public function get_is_visible() {
		return $this->is_visible;
	}

	/**
	 * @param string $more_info
	 */
	public function set_more_info( $more_info ) {
		$this->more_info = $more_info;
	}

	/**
	 * @return string
	 */
	public function get_more_info() {
		return $this->more_info;
	}

	/**
	 * @param string $name
	 */
	public function set_name( $name ) {
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * @param string $prereg_close
	 */
	public function set_prereg_close( $prereg_close ) {
		$this->prereg_close = $prereg_close;
	}

	/**
	 * @return string
	 */
	public function get_prereg_close() {
		return $this->prereg_close;
	}

	/**
	 * @param string $prereg_open
	 */
	public function set_prereg_open( $prereg_open ) {
		$this->prereg_open = $prereg_open;
	}

	/**
	 * @return string
	 */
	public function get_prereg_open() {
		return $this->prereg_open;
	}

	/**
	 * @param string $start_date
	 */
	public function set_start_date( $start_date ) {
		$this->start_date = $start_date;
	}

	/**
	 * @return string
	 */
	public function get_start_date() {
		return $this->start_date;
	}

	/**
	 * @param array $venue
	 */
	public function set_venue( $venue ) {
		$this->venue = $venue;
	}

	/**
	 * @return array
	 */
	public function get_venue() {
		return $this->venue;
	}

	/**
	 * @param $wp_post_id
	 */
	public function set_wp_post_id( $wp_post_id ) {
		$this->wp_post_id = $wp_post_id;
	}

	/**
	 * @return mixed
	 */
	public function get_wp_post_id() {
		return $this->wp_post_id;
	}

}