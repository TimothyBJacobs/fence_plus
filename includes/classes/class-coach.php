<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_Coach {
	/**
	 * @var WP_User
	 */
	private $wp_user;
	/**
	 * @var array fencers s/he coaches
	 */
	private $fencers = array();

	/**
	 * Holds array of tournament IDs that the system auto suggested tournaments to you
	 *
	 * @var array
	 */
	private $auto_suggested_tournaments = array();

	/**
	 * @param array $auto_suggested_tournaments
	 */
	public function set_auto_suggested_tournaments( $auto_suggested_tournaments ) {
		$this->auto_suggested_tournaments = $auto_suggested_tournaments;
	}

	/**
	 * @return array
	 */
	public function get_auto_suggested_tournaments() {
		return $this->auto_suggested_tournaments;
	}

	/**
	 * @param \WP_User $wp_user
	 */
	public function set_wp_user( $wp_user ) {
		$this->wp_user = $wp_user;
	}

	/**
	 * @return \WP_User
	 */
	public function get_wp_user() {
		return $this->wp_user;
	}

	/**
	 *
	 */
	public function __construct( $coach_id ) {
		$user = get_user_by( 'id', $coach_id );

		if ( false === $user || ! Fence_Plus_Coach::is_coach( $coach_id ) )
			throw new InvalidArgumentException( "Invalid WordPress user ID", 1 );

		$this->wp_user = $user;

		$coach_data = get_user_meta( $this->wp_user->ID, 'fence_plus_coach_data', true );

		if ( is_array( $coach_data ) )
			$this->set_all_properties( $coach_data );
	}

	/*========================
		Database Functions
	=========================*/

	/**
	 * Saves current objects data o the database
	 */
	public function save() {
		$coach_data = array();

		foreach ( $this as $key => $value ) {
			if ( $key != 'wp_user' ) // don't save the WP User object to the database
				$coach_data[$key] = $value;
		}

		update_user_meta( $this->ID, 'fence_plus_coach_data', $coach_data );

		do_action( 'fence_plus_coach_saved', $this );
	}

	/**
	 * Removes fencer from database
	 */
	public function delete() {
		if ( current_user_can( 'delete_users' ) ) {
			$user = get_user_by( 'id', $this->wp_id );
			$user_email = $user->user_email;
			delete_user_meta( $this->wp_id, 'fence_plus_coach_data' );
			wp_delete_user( $this->wp_id );
		}
		else {
			wp_die( 'You don\'t have permissions to delete that user' );
			die();
		}

		do_action( 'fence_plus_coach_deleted', $user_email );
	}

	/**
	 * @param array $coachdata
	 *
	 * @return bool|mixed
	 */
	public function set_all_properties( array $coachdata ) {
		if ( empty( $coachdata ) )
			return false;

		$state = true;
		foreach ( $coachdata as $key => $data ) {
			$state = call_user_func( array( $this, 'set_' . $key ), $data );
			// set all properties by calling internal setters based on fencer user meta data key
		}

		return $state;
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

		if ( ! isset( $user->roles[0] ) )
			return false;

		return $user->roles[0] == "coach";
	}

	/**
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function can_user_edit( $user_id ) {
		return false;
	}

	/**
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function add_fencer( $user_id ) {
		$fencers = $this->get_fencers();

		if ( in_array( $user_id, $fencers ) ) {
			return false;
		}

		array_unshift( $fencers, $user_id );

		$this->set_fencers( $fencers );

		do_action( 'fence_plus_add_fencer_to_coach', $this->get_wp_id(), $user_id );

		return true;
	}

	/**
	 * Remove fencer from coaches list
	 *
	 * @param $user_id
	 */
	public function remove_fencer( $user_id ) {
		$fencers = $this->get_fencers();

		if ( ( $key = array_search( $user_id, $fencers ) ) !== false ) {
			unset( $fencers[$key] );
		}

		$this->set_fencers( $fencers );
	}

	/**
	 * Magic Method for retrieving WP User properties
	 *
	 * @param $key
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( isset( $this->wp_user->$key ) )
			return $this->wp_user->$key;
		else
			return "";
	}

	/**
	 * @return mixed
	 */
	public function get_wp_id() {
		return $this->wp_user->ID;
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
}