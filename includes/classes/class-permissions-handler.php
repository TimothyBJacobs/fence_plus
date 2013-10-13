<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_Permissions_Handler {
	/**
	 * @var Fence_Plus_Fencer|Fence_Plus_Coach
	 */
	public $object1;

	/**
	 * @var Fence_Plus_Fencer|Fence_Plus_Coach
	 */
	public $object2;

	/**
	 * @var array
	 */
	private $available_classes = array( 'Fence_Plus_Fencer', 'Fence_Plus_Coach' );

	/**
	 * Returns the relative permissions of each person against the other person
	 *
	 * @param $user1 int|WP_User|Fence_Plus_Fencer|Fence_Plus_Coach
	 * @param $user2 int|WP_User|Fence_Plus_Fencer|Fence_Plus_Coach
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $user1, $user2 ) {
		foreach ( func_get_args() as $count => $arg ) {
			if ( is_int( $arg ) ) {
				if ( Fence_Plus_Fencer::is_fencer( $arg ) )
					$object = Fence_Plus_Fencer::wp_id_db_load( $arg );
				elseif ( Fence_Plus_Coach::is_coach( $arg ) )
					$object = new Fence_Plus_Coach( $arg );
				else
					throw new InvalidArgumentException( "Invalid parameters", 1 );
			}
			elseif ( is_a( $arg, 'WP_User' ) && ( Fence_Plus_Fencer::is_fencer( $arg ) || Fence_Plus_Coach::is_coach( $arg ) ) ) {
				if ( Fence_Plus_Fencer::is_fencer( $arg ) )
					$object = Fence_Plus_Fencer::wp_id_db_load( $arg->ID );
				else
					$object = new Fence_Plus_Coach( $arg->ID );
			}
			elseif ( in_array( get_class( $arg ), $this->available_classes ) ) {
				$object = $arg;
			}
			else {
				throw new InvalidArgumentException( "Invalid parameters", 1 );
			}

			$property_name = 'object' . ( $count + 1 );

			$reflection = new ReflectionClass( 'Fence_Plus_Permissions_Handler' );
			$reflection->getProperty( $property_name )->setValue( $this, $object );
		}
	}

	/**
	 * Determine if the first person has permissions to edit the other
	 *
	 * @return bool
	 */
	public function can_object1_edit_object2() {
		if ( $this->object2->can_user_edit( $this->object1->get_wp_id() ) )
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
		if ( $this->object1->can_user_edit( $this->object2->get_wp_id() ) )
			return true;
		else
			return false;
	}
}