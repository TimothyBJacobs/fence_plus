<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_Utility {
	/**
	 * @param null $user_id
	 *
	 * @return array
	 */
	public static function get_all_fencers( $user_id = null ) {
		if ( isset( $user_id ) && Fence_Plus_Coach::is_coach( $user_id ) ) {
			$coach = new Fence_Plus_Coach( $user_id );
			$fencer_ids = $coach->get_fencers();
			$args = array(
				'include' => $fencer_ids
			);
		}
		else {
			$args = array(
				'role' => 'fencer'
			);
		}

		return get_users( $args );
	}

	/**
	 * @param null $user_id
	 *
	 * @return array
	 */
	public static function get_all_coaches( $user_id = null ) {
		if ( isset( $user_id ) && Fence_Plus_Fencer::is_fencer( $user_id ) ) {
			$fencer = Fence_Plus_Fencer::wp_id_db_load( $user_id );
			$coach_ids = $fencer->get_coaches();
			$args = array(
				'include' => $coach_ids
			);
		}
		else {
			$args = array(
				'role' => 'coach'
			);
		}

		return get_users( $args );
	}

	/**
	 * Sort Fencers highest to lowest rating
	 *
	 * @param $a Fence_Plus_Fencer
	 * @param $b Fence_Plus_Fencer
	 *
	 * @return int
	 */
	public static function sort_fencers( $a, $b ) {
		if ( $a->get_primary_weapon() == array() )
			return 1;
		if ( $b->get_primary_weapon() == array() )
			return - 1;

		return strcmp( implode( "", $a->get_primary_weapon_rating_letter() ) . ( 3000 - (int) $a->get_primary_weapon_rating_year() ),
		  implode( "", $b->get_primary_weapon_rating_letter() ) . ( 3000 - (int) $b->get_primary_weapon_rating_year() )
		);
	}

	/**
	 * Removes all fencer data. Fires on delete_user hook.
	 *
	 * @param $fencer_id int
	 */
	public static function remove_fencer_data( $fencer_id ) {
		try {
			$fencer = Fence_Plus_Fencer::wp_id_db_load( $fencer_id );
		}
		catch ( InvalidArgumentException $e ) {
			return;
		}
		$fencer->remove_data();
	}

	/**
	 * Removes all coach data. Fires on delete_user hook.
	 *
	 * @param $coach_id int
	 */
	public static function remove_coach_data( $coach_id ) {
		try {
			$coach = new Fence_Plus_Coach( $coach_id );
		}
		catch ( InvalidArgumentException $e ) {
			return;
		}
		$coach->remove_data();
	}

	/**
	 * Add a notification to be added to next page load
	 *
	 * @param $text string text of the message to display
	 * @param $type string class of notification error|updated
	 */
	public static function add_admin_notification( $text, $type ) {
		$notifications = get_option( 'fence_plus_admin_notifications', array() );
		$notifications[] = array(
			'text' => $text,
			'type' => $type
		);

		update_option( 'fence_plus_admin_notifications', $notifications );
	}

	/**
	 * Delete admin notification option
	 */
	public static function delete_admin_notification() {
		delete_option( 'fence_plus_admin_notifications' );
	}

	/**
	 * Displays admin notifications in admin_notices
	 */
	public static function display_notification() {
		$notifications = get_option( 'fence_plus_admin_notifications', array() );

		foreach ( $notifications as $notification ) {
			echo "<div class=" . $notification['type'] . "><p><strong>" . __( 'Fence Plus:', Fence_Plus::SLUG ) . "</strong> " . $notification['text'] . "</p></div>";
		}

		self::delete_admin_notification();
	}
}