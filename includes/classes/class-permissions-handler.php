<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_Permissions_Handler
{
	/**
	 * @var Fence_Plus_Fencer|Fence_Plus_Coach
	 */
	private $object1;

	/**
	 * @var Fence_Plus_Fencer|Fence_Plus_Coach
	 */
	private $object2;

	/**
	 * Returns the relative permissions of each person against the other person
	 *
	 * @param $user1 Fence_Plus_Person
	 * @param $user2 Fence_Plus_Person
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( Fence_Plus_Person $user1, Fence_Plus_Person $user2 ) {
		$this->object1 = $user1;
		$this->object2 = $user2;
	}

	/**
	 * Determine if the first person has permissions to edit the other
	 *
	 * @return bool
	 */
	public function can_object1_edit_object2() {
		if ( $this->object1->can_user_edit( $this->object2->get_wp_id() ) )
			return true;
		else
			return false;
	}

	/**
	 * Determine if the second person has permissions to edit the other
	 *
	 * @return bool
	 */
	public function can_object2_edit_object1() {
		if ( $this->object2->can_user_edit( $this->object1->get_wp_id() ) )
			return true;
		else
			return false;
	}
}