<?php

/**
 *
 * @package Fence Plus
 * @subpackage People
 * @since 0.1
 */
require_once ( FENCEPLUS_INCLUDES_CLASSES_DIR . "people/interface-person-factory.php" );

class Fence_Plus_Person_Factory implements Fence_Plus_Factory {

	/**
	 * Holds array of the different mappings that the factory should follow
	 *
	 * @var array
	 */
	private $factory_mappings = array();

	/**
	 * Set up People Factory to return new people
	 */
	public function __construct() {
		$this->factory_mappings['fencer'] = array(
			'class_path' => FENCEPLUS_INCLUDES_CLASSES_DIR . 'people/fencer-factory.php',
			'class_name' => 'Fence_Plus_Fencer_Factory'
		);

		$this->factory_mappings['coach'] = array(
			'class_path' => FENCEPLUS_INCLUDES_CLASSES_DIR . 'people/coach-factory.php',
			'class_name' => 'Fence_Plus_Coach_Factory'
		);

		$this->factory_mappings = apply_filters( 'fence_plus_person_factory_register_concrete_factories', $this->factory_mappings );
	}

	/**
	 * @param $user WP_User|int|string
	 *
	 * @return Fence_Plus_Person
	 * @throws InvalidArgumentException|Exception
	 */
	public function make( $user ) {
		if ( is_string( $user ) ) // if we passed a USFA ID
			$user = Fence_Plus_Utility::get_user_id_from_usfa_id( $user );

		if ( is_int( $user ) )
			$user = $this->make_wp_user( $user );

		if ( $user === false )
			throw new InvalidArgumentException( "Invalid user provided", 1 );

		$role = $user->roles[0];

		if ( file_exists( $this->factory_mappings[$role]['class_path'] ) ) {
			require_once ( $this->factory_mappings[$role]['class_path'] );

			$reflection = new ReflectionClass( $this->factory_mappings[$role]['class_name'] );
			$factory = $reflection->newInstance();
			try {
				return $factory->make( $user );
			}
			catch ( InvalidArgumentException $e ) {
				throw $e;
			}
		}
		else {
			throw new InvalidArgumentException( "Invalid user provided", 1 );
		}
	}

	/**
	 * @param $user WP_User|int
	 *
	 * @return bool|WP_User
	 */
	private function make_wp_user( $user ) {
		if ( ! is_a( $user, 'WP_User' ) )
			$user = get_user_by( 'id', $user );

		return $user;
	}
}