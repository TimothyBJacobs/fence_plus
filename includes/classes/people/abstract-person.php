<?php
/**
 *
 * @package Fence Plus
 * @subpackage People
 * @since 0.1
 */

abstract class Fence_Plus_Person {
	/**
	 * @var int WP_User ID
	 */
	protected $wp_id;

	/**
	 * @var array of WP_User ids
	 */
	protected $editable_users = array();

	/**
	 * Can the current object
	 *
	 * @param $user_id int
	 *
	 * @return bool
	 */
	public function can_user_edit( $user_id ) {
		if ( in_array( $user_id, $this->editable_users ) )
			return true;
		else
			return false;
	}

	/**
	 * Add a user that the current object can edit
	 *
	 * @param $new_user_id int
	 *
	 * @return bool
	 */
	public function add_editable_user( $new_user_id ) {
		$current_editable_users = $this->get_editable_users();

		if ( in_array( $new_user_id, $current_editable_users ) )
			return false;

		array_unshift( $current_editable_users, $new_user_id );

		$this->set_editable_users( $current_editable_users );

		do_action( 'fence_plus_add_editable_user', $this, $new_user_id );

		return true;
	}

	/**
	 * Remove a user that the current object can edit
	 *
	 * @param $old_user_id int
	 */
	public function remove_editable_user( $old_user_id ) {
		$current_editable_users = $this->get_editable_users();

		if ( ( $key = array_search( $old_user_id, $current_editable_users ) ) !== false )
			unset( $current_editable_users[$key] );

		$this->set_editable_users( $current_editable_users );

		do_action( 'fence_plus_remove_editable_user', $this, $old_user_id );
	}

	abstract function get_name();

	/**
	 * @param $wp_id int
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

	/**
	 * @param $editable_users array
	 */
	public function set_editable_users( array $editable_users ) {
		$this->editable_users = $editable_users;
	}

	/**
	 * @return array
	 */
	public function get_editable_users() {
		return $this->editable_users;
	}
}