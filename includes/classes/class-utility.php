<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_Utility {
	public static function get_all_fencers() {
		return get_users( array( 'role' => 'fencer' ) );
	}
}