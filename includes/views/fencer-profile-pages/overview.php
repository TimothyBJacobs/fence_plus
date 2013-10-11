<?php
/**
 *
 * @package Fence Plus
 * @subpackage Fencer Profile Pages
 * @since 0.1
 */

class Fence_Plus_Profile_Overview {
	/**
	 * @var Fence_Plus_Fencer
	 */
	public $fencer;

	/**
	 * @param Fence_Plus_Fencer $fencer
	 */
	public function __construct( Fence_Plus_Fencer $fencer ) {
		$this->fencer = $fencer;
		$this->styles_and_scripts();
		$this->render();
	}

	public function styles_and_scripts() {
		wp_enqueue_style( 'fence-plus-profile-overview' );
		wp_enqueue_script( 'fence-plus-profile-overview' );
	}

	/**
	 * Render the profile page
	 *
	 * Credit to iThemes Exchange WP Plugin for design and CSS
	 */
	public function render() {
		$this->fencer->summary_box();
		$fencer = Fence_Plus_Fencer::usfa_id_db_load('100029488');
		$fencer->summary_box();
	}
}