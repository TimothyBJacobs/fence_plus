<?php
/**
 *
 * @package Fence Plus
 * @subpackage People
 * @since 0.1
 */
class Fence_Plus_Coach_Factory implements Fence_Plus_Factory {

	/**
	 * @param WP_User $user
	 *
	 * @return Fence_Plus_Coach
	 * @throws Exception|InvalidArgumentException
	 */
	public function make( $user ) {
		try {
			return new Fence_Plus_Coach( $user->ID );
		}
		catch ( InvalidArgumentException $e ) {
			throw $e;
		}
	}

}