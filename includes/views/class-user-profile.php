<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_User_Page {
	/**
	 * @var int WordPress user ID
	 */
	private $fencer_user_id;
	/**
	 * @var int current user ID
	 * @uses get_currentuser
	 */
	private $current_user_id;

	/**
	 * Holds Fencer object
	 *
	 * @var Fence_Plus_Fencer object
	 */
	private $fencer;

	/**
	 *
	 */
	public function __construct() {
		add_action( 'personal_options_update', array( $this, 'update_fields' ) );
		add_action( 'profile_update', array( $this, 'update_fields' ) );

		$this->current_user_id = get_current_user_id();

		if ( isset( $_GET['user_id'] ) ) {
			$this->fencer_user_id = (int) $_GET['user_id'];
		}
		else {
			$this->fencer_user_id = $this->current_user_id;
		}

		require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . 'class-fencer.php' );

		try {
			$this->fencer = Fence_Plus_Fencer::wp_id_db_load( $this->fencer_user_id );
		}
		catch ( InvalidArgumentException $e ) {
			return;
		}

		add_action( 'personal_options', array( $this, 'add_fields' ) );

		// clean up fencer profiles
		add_filter( 'user_contactmethods', array( $this, 'remove_contact_methods' ) );
		add_action( 'admin_head', array( $this, 'remove_colors' ) );
		add_action( 'admin_head', array( $this, 'remove_everything_else' ) );

		add_action( 'admin_head', array( $this, 'profile_admin_buffer_start' ) ); // remove bios
		add_action( 'admin_footer', array( $this, 'profile_admin_buffer_end' ) );
	}

	/**
	 * Add fields to the user profile page
	 */
	public function add_fields() {
		echo "<tr><th colspan='2'><a href='" . add_query_arg( array( 'fence_plus_fencer_data' => 1 ), get_edit_user_link( $this->fencer_user_id ) ) . "'>" .
		  __( 'View Fencing Info', Fence_Plus::SLUG ) . "</a></th>";
	}

	/**
	 * Update user profile fields
	 */
	public function update_fields( $user_id ) {
		if ( isset( $_POST['fence_plus_primary_weapon'] ) && current_user_can( 'edit_users' ) ) {
			$primary_weapon = $_POST['fence_plus_primary_weapon'];

			if ( $primary_weapon != 'Epee' && $primary_weapon != 'Foil' && $primary_weapon != 'Saber' ) {
				return; // make sure primary weapon is valid input
			}

			require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . 'class-fencer.php' );
			$fencer = Fence_Plus_Fencer::wp_id_db_load( $user_id );
			$fencer->set_primary_weapon( array( $primary_weapon ) );
			$fencer->save();
		}
	}

	/**
	 * Remove contact methods
	 *
	 * @return array
	 */
	public function remove_contact_methods() {
		return array();
	}

	/**
	 * Remove color options
	 */
	public function remove_colors() {
		global $_wp_admin_css_colors;
		$_wp_admin_css_colors = 0;
	}

	/**
	 * CSS and JS to remove other elements of the page
	 */
	public function remove_everything_else() {
		echo <<<EOT
			<style type="text/css">
				.show-admin-bar {
					display: none;
				}
			</style>
EOT;

	}

	/**
	 * Remove bio field
	 *
	 * @param $buffer
	 *
	 * @return mixed
	 */
	public function remove_plain_bio( $buffer ) {
		$titles = array( '#<h3>About Yourself</h3>#', '#<h3>About the user</h3>#' );
		$buffer = preg_replace( $titles, '<h3>Password</h3>', $buffer, 1 );
		$biotable = '#<h3>Password</h3>.+?<table.+?/tr>#s';
		$buffer = preg_replace( $biotable, '<h3>Password</h3> <table class="form-table">', $buffer, 1 );

		return $buffer;
	}

	/**
	 * Start buffer
	 */
	public function profile_admin_buffer_start() {
		ob_start( array( $this, "remove_plain_bio" ) );
	}

	/**
	 * End buffer
	 */
	public function profile_admin_buffer_end() {
		ob_end_flush();
	}

}
