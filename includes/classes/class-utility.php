<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_Utility {
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
}