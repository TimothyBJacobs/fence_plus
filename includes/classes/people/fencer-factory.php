<?php
/**
 *
 * @package Fence Plus
 * @subpackage People
 * @since 0.1
 */
class Fence_Plus_Fencer_Factory implements Fence_Plus_Factory {

	/**
	 * @param WP_User $user
	 *
	 * @return Fence_Plus_Fencer
	 * @throws Exception|InvalidArgumentException
	 */
	public function make( $user ) {
		try {
			return Fence_Plus_Fencer::wp_id_db_load( $user->ID );
		}
		catch ( InvalidArgumentException $e ) {
			throw $e;
		}
	}

}