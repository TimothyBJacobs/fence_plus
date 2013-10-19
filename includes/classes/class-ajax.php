<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_AJAX {
	/**
	 *
	 */
	public function __construct() {
		add_action( 'wp_ajax_fence_plus_add_fencer_to_coach', array( $this, 'add_fencer_to_coach' ) );
	}

	/**
	 *
	 */
	public function add_fencer_to_coach() {
		if ( ! current_user_can( 'edit_users' ) )
			die();

		$fencer_id = $_POST['fencer_id'];
		$coach_id = $_POST['coach_id'];

		try {
			$fencer = Fence_Plus_Fencer::wp_id_db_load( $fencer_id );
		}
		catch ( InvalidArgumentException $e ) {
			echo "Fencer not found.";
			die();
		}

		if ( false === $fencer->add_coach( $coach_id ) ) {
			echo (int) $fencer->get_usfa_id(); // if this fencer is already in the coach db, return the fencer's USFA ID
			die();
		}

		$fencer->save();

		$coach = new Fence_Plus_Coach( $coach_id );

		$coach->add_fencer( $fencer_id );
		$coach->save();

		$fencer->short_box();

		die();
	}
}