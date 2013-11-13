<?php

/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_Coach extends Fence_Plus_Person {
	/**
	 * @var WP_User
	 */
	private $wp_user;

	/**
	 * Holds array of tournament IDs that the system auto suggested tournaments to you
	 *
	 * @var array
	 */
	private $auto_suggested_tournaments = array();

	/**
	 *
	 */
	public function __construct( $coach_id ) {
		$user = get_user_by( 'id', $coach_id );

		if ( false === $user || ! Fence_Plus_Utility::is_coach( $coach_id ) )
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
			wp_delete_user( $this->wp_id );
		}
		else {
			wp_die( 'You don\'t have permissions to delete that user' );
			die();
		}
	}

	/**
	 * Remove all data associated to this user
	 */
	public function remove_data() {
		if ( current_user_can( 'delete_users' ) ) {
			foreach ( $this->get_editable_users() as $fencer_id ) {
				try {
					$fencer = Fence_Plus_Fencer::wp_id_db_load( $fencer_id );
				}
				catch ( InvalidArgumentException $e ) {
					continue;
				}
				$fencer->remove_editable_user( $this->get_wp_id() );
				$fencer->save();
			}

			delete_user_meta( $this->wp_id, 'fence_plus_coach_data' );
		}
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
	 * @return string
	 */
	public function get_name() {
		return $this->display_name;
	}

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
	 * @param WP_User $wp_user
	 */
	public function set_wp_user( $wp_user ) {
		$this->wp_user = $wp_user;
	}

	/**
	 * @return WP_User
	 */
	public function get_wp_user() {
		return $this->wp_user;
	}

	/**
	 * @return int
	 */
	public function get_wp_id() {
		return $this->wp_user->ID;
	}
}