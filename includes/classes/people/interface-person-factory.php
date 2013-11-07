<?php
/**
 * Fencer Plus Factory needs to be implemented by all factory classes
 *
 * @package Fence Plus
 * @subpackage People
 * @since 0.1
 */
interface Fence_Plus_Factory {

	/**
	 * @param $user WP_User
	 *
	 * @return Fence_Plus_Person
	 * @throws InvalidArgumentException
	 */
	public function make( $user );
}