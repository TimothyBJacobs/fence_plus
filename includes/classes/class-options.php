<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_Options {
	/**
	 * @var array
	 */
	private $options = array();

	/**
	 * @var Fence_Plus_Options|null
	 */
	private static $instance = null;

	/**
	 * Class is responsible for holding the plugin's options.
	 *
	 * Is singleton to prevent options clashing
	 */
	private function __construct() {
		$controller = Fence_Plus_Options_Controller::get_instance();

		$this->options = array_merge( $controller->get_defaults(), get_option( 'fence_plus_options' ) );
	}

	/**
	 * @return Fence_Plus_Options
	 */
	public static function get_instance() {
		if ( self::$instance == null )
			self::$instance = new self;

		return self::$instance;
	}

	/**
	 * Save current object's state to the database
	 */
	public function save() {
		update_option( 'fence_plus_options', $this->options );
	}

	/**
	 * @param $data
	 */
	public function update( $data ) {
		$this->options = $data;
	}

	/**
	 * @return array
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Magic method to get data from options
	 *
	 * @param $key
	 *
	 * @return string
	 */
	public function __get( $key ) {
		if ( isset( $this->options[$key] ) )
			return $this->options[$key];
		else
			return "";
	}

	/**
	 * Magic method to set options data if that option exists.
	 * Does not allow for the addition of new values.
	 *
	 * @param $key
	 * @param $value
	 */
	public function __set( $key, $value ) {
		if ( isset( $this->options[$key] ) )
			$this->options[$key] = $value;
	}

}