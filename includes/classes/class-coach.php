<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_Coach {
	/**
	 * @var string coach first name
	 */
	private $first_name;
	/**
	 * @var string coach last name
	 */
	private $last_name;
	/**
	 * @var int WP User ID
	 */
	private $wp_id;
	/**
	 * @var array fencers s/he coaches
	 */
	private $fencers = array();

	/**
	 *
	 */
	public function __construct( $coach_id ) {
		$this->wp_id = $coach_id;
	}

	/**
	 * @param $user WP_User|int
	 *
	 * @return bool
	 */
	public static function is_coach( $user ) {
		if ( ! is_a( $user, 'WP_User' ) ) {
			$user = get_user_by( 'id', $user );

			if ( false === $user )
				return false;
		}

		return $user->roles[0] == "coach";
	}

	/**
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function can_user_edit( $user_id ) {

	}

	/**
	 * @param $user_id
	 */
	public function add_fencer( $user_id ) {

	}

	/**
	 * @param $user_id
	 */
	public function remove_fencer( $user_id ) {

	}

	/**
	 * @param array $fencers
	 */
	public function set_fencers( $fencers ) {
		$this->fencers = $fencers;
	}

	/**
	 * @return array
	 */
	public function get_fencers() {
		return $this->fencers;
	}

	/**
	 * @param string $first_name
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
	 * @param string $last_name
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