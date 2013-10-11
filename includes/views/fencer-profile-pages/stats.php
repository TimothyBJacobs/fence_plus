<?php
/**
 *
 * @package Fence Plus
 * @subpackage Fencer Profile Pages
 * @since 0.1
 */

class Fence_Plus_Profile_Stats {
	/**
	 * @var Fence_Plus_Fencer
	 */
	public $fencer;

	/**
	 * @param Fence_Plus_Fencer $fencer
	 */
	public function __construct( Fence_Plus_Fencer $fencer ) {
		$this->fencer = $fencer;
		$this->render();
	}

	/**
	 * Render the profile page
	 */
	public function render() {
		echo "Stats will go here";
	}
}