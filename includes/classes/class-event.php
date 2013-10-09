<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */
class Fence_Plus_Event {

	/**
	 * @var int event ID
	 */
	private $id;
	/**
	 * @var int tournament ID
	 */
	private $tournament_id;
	/**
	 * @var string tournament name
	 */
	private $tournament;
	/**
	 * @var string event name
	 */
	private $full_name;
	/**
	 * @var string weapon "Foil"|"Epee"|"Saber"
	 */
	private $weapon;
	/**
	 * @var string "Men"|"Women"
	 */
	private $gender;
	/**
	 * @var string tournament authority "USFA"|"FIE"
	 */
	private $authority;
	/**
	 * @var string event age limit
	 * @see https://sites.google.com/a/countersix.com/fred-rest-api/request-response-reference
	 */
	private $age_limit;
	/**
	 * @var string rating limit
	 * @see https://sites.google.com/a/countersix.com/fred-rest-api/request-response-reference
	 */
	private $rating_limit;
	/**
	 * @var string todo find out how different from rating prediction
	 */
	private $rating;
	/**
	 * @var string prediction of event's rating based on current registrations
	 */
	private $rating_prediction;
	/**
	 * @var int number of pre-registrations todo find out difference from prereg_count
	 */
	private $entries;
	/**
	 * @var int number of fencers pre-registered for this event
	 */
	private $prereg_count;
	/**
	 * @var bool is this a team event
	 */
	private $is_team;

	/**
	 * @param string $age_limit
	 */
	public function set_age_limit( $age_limit ) {
		$this->age_limit = $age_limit;
	}

	/**
	 * @return string
	 */
	public function get_age_limit() {
		return $this->age_limit;
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
	 * @param string $close_of_reg
	 */
	public function set_close_of_reg( $close_of_reg ) {
		$this->close_of_reg = $close_of_reg;
	}

	/**
	 * @return string
	 */
	public function get_close_of_reg() {
		return $this->close_of_reg;
	}

	/**
	 * @param string $description
	 */
	public function set_description( $description ) {
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * @param int $entries
	 */
	public function set_entries( $entries ) {
		$this->entries = $entries;
	}

	/**
	 * @return int
	 */
	public function get_entries() {
		return $this->entries;
	}

	/**
	 * @param string $fee
	 */
	public function set_fee( $fee ) {
		$this->fee = $fee;
	}

	/**
	 * @return string
	 */
	public function get_fee() {
		return $this->fee;
	}

	/**
	 * @param string $full_name
	 */
	public function set_full_name( $full_name ) {
		$this->full_name = $full_name;
	}

	/**
	 * @return string
	 */
	public function get_full_name() {
		return $this->full_name;
	}

	/**
	 * @param string $gender
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
	 * @param boolean $is_team
	 */
	public function set_is_team( $is_team ) {
		$this->is_team = $is_team;
	}

	/**
	 * @return boolean
	 */
	public function get_is_team() {
		return $this->is_team;
	}

	/**
	 * @param int $prereg_count
	 */
	public function set_prereg_count( $prereg_count ) {
		$this->prereg_count = $prereg_count;
	}

	/**
	 * @return int
	 */
	public function get_prereg_count() {
		return $this->prereg_count;
	}

	/**
	 * @param array $preregs
	 */
	public function set_preregs( $preregs ) {
		$this->preregs = $preregs;
	}

	/**
	 * @return array
	 */
	public function get_preregs() {
		return $this->preregs;
	}

	/**
	 * @param string $rating
	 */
	public function set_rating( $rating ) {
		$this->rating = $rating;
	}

	/**
	 * @return string
	 */
	public function get_rating() {
		return $this->rating;
	}

	/**
	 * @param string $rating_limit
	 */
	public function set_rating_limit( $rating_limit ) {
		$this->rating_limit = $rating_limit;
	}

	/**
	 * @return string
	 */
	public function get_rating_limit() {
		return $this->rating_limit;
	}

	/**
	 * @param string $rating_prediction
	 */
	public function set_rating_prediction( $rating_prediction ) {
		$this->rating_prediction = $rating_prediction;
	}

	/**
	 * @return string
	 */
	public function get_rating_prediction() {
		return $this->rating_prediction;
	}

	/**
	 * @param string $tournament
	 */
	public function set_tournament( $tournament ) {
		$this->tournament = $tournament;
	}

	/**
	 * @return string
	 */
	public function get_tournament() {
		return $this->tournament;
	}

	/**
	 * @param int $tournament_id
	 */
	public function set_tournament_id( $tournament_id ) {
		$this->tournament_id = $tournament_id;
	}

	/**
	 * @return int
	 */
	public function get_tournament_id() {
		return $this->tournament_id;
	}

	/**
	 * @param string $weapon
	 */
	public function set_weapon( $weapon ) {
		$this->weapon = $weapon;
	}

	/**
	 * @return string
	 */
	public function get_weapon() {
		return $this->weapon;
	}

	/**
	 * @var string event description
	 */
	private $description;
	/**
	 * @var string ISO 8601 close of registration date
	 */
	private $close_of_reg;
	/**
	 * @var string event fee
	 */
	private $fee;
	/**
	 * @var array
	 *  askFRED fencer ID => Fence_Plus_Fencer objectt5
	 */
	private $preregs = array();

}
