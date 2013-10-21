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
		add_action( 'wp_ajax_fence_plus_add_coach_to_fencer', array( $this, 'add_coach_to_fencer' ) );
		add_action( 'wp_ajax_fence_plus_remove_coach', array( $this, 'remove_coach_from_fencer' ) );
	}

	/**
	 * Add fencer to coach
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

	/**
	 * Add a coach to a fencer, similar to add fencer to coach, but different return values
	 */
	public function add_coach_to_fencer() {
		$fencer_id = $_POST['fencer_id'];
		$coach_id = $_POST['coach_id'];

		try {
			$fencer = Fence_Plus_Fencer::wp_id_db_load( $fencer_id );
		}
		catch ( InvalidArgumentException $e ) {
			echo false;
			die();
		}

		if ( false === $fencer->add_coach( $coach_id ) ) {
			echo false;
			die();
		}

		$fencer->save();

		$coach = new Fence_Plus_Coach( $coach_id );

		$coach->add_fencer( $fencer_id );
		$coach->save();

		echo true;
		die();
	}

	/**
	 * Remove coach from fencers
	 */
	public function remove_coach_from_fencer() {
		$fencer_id = $_POST['fencer_id'];
		$coach_id = $_POST['coach_id'];

		if ( current_user_can( 'edit_users' ) || get_current_user_id() == $fencer_id ) {
			try {
				$fencer = Fence_Plus_Fencer::wp_id_db_load( $fencer_id );
			}
			catch ( InvalidArgumentException $e ) {
				echo false;
				die();
			}

			try {
				$coach = new Fence_Plus_Coach( $coach_id );
			}
			catch ( InvalidArgumentException $e ) {
				echo false;
				die();
			}

			$fencer->remove_coach( $coach_id );
			$fencer->save();

			$coach->remove_fencer( $fencer_id );
			$coach->save();

			echo true;
		}
		else {
			echo false;
		}

		die();
	}
}