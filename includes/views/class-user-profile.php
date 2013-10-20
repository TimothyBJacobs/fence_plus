<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_User_Page {
	/**
	 * @var int WordPress user ID of the current profile we are on
	 */
	private $profile_user_id;
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
		$this->current_user_id = get_current_user_id();

		if ( isset( $_GET['fp_id'] ) ) {
			$this->profile_user_id = (int) $_GET['fp_id'];
		}
		else if ( isset( $_GET['user_id'] ) ) {
			$this->profile_user_id = $_GET['user_id'];
		}
		else {
			$this->profile_user_id = $this->current_user_id;
		}

		if ( Fence_Plus_Fencer::is_fencer( $this->profile_user_id ) ) {

			try {
				$this->fencer = Fence_Plus_Fencer::wp_id_db_load( $this->profile_user_id );
			}
			catch ( InvalidArgumentException $e ) {
				return;
			}

			// switch views if on fecner data page
			add_filter( 'all_admin_notices', array( $this, 'load_fencer_data_page' ) );

			// add profile fields
			add_action( 'personal_options', array( $this, 'add_fencer_fields' ) );

			// clean up fencer profiles
			add_filter( 'user_contactmethods', array( $this, 'remove_contact_methods' ) );
			add_action( 'admin_head', array( $this, 'remove_colors' ) );
			add_action( 'admin_head', array( $this, 'remove_everything_else' ) );

			add_action( 'admin_head', array( $this, 'profile_admin_buffer_start' ) ); // remove bios
			add_action( 'admin_footer', array( $this, 'profile_admin_buffer_end' ) );
		}
		else if ( Fence_Plus_Coach::is_coach( $this->profile_user_id ) ) {
			add_action( 'personal_options', array( $this, 'add_coach_fields' ) );
			add_filter( 'all_admin_notices', array( $this, 'load_coach_data_page' ) );
		}
	}

	/**
	 * Load the fencer profile data page
	 */
	public function load_fencer_data_page() {
		if ( ( isset( $_GET['fence_plus_fencer_data'] ) && current_user_can( 'edit_users', $this->profile_user_id ) ) ) {

			include( FENCEPLUS_INCLUDES_VIEWS_DIR . "fencer-profile-pages/main-view.php" );

			new Fence_Plus_Fencer_Profile_Main( Fence_Plus_Fencer::wp_id_db_load( $this->profile_user_id ) );

			include( ABSPATH . 'wp-admin/admin-footer.php' );
			die();
		}
	}

	/**
	 * Load the coach profile data page
	 */
	public function load_coach_data_page() {
		if ( isset( $_GET['fence_plus_coach_data'] ) && Fence_Plus_Coach::is_coach( $this->profile_user_id ) ) {
			include ( FENCEPLUS_INCLUDES_VIEWS_DIR . "coach-profile-pages/main-view.php" );

			new Fence_Plus_Coach_Profile_Main( new Fence_Plus_Coach( $this->profile_user_id ) );

			include( ABSPATH . 'wp-admin/admin-footer.php' );
			die();
		}
	}

	/**
	 * Add view coach link
	 */
	public function add_coach_fields() {
		echo "<tr><th colspan='2'><a href='" . add_query_arg( array( 'fence_plus_coach_data' => 1, 'fp_id' => $this->profile_user_id ), get_edit_user_link( $this->profile_user_id ) ) . "'>" .
		  __( 'View Fencing Info', Fence_Plus::SLUG ) . "</a></th>";
	}

	/**
	 * Add fields to the user profile page
	 */
	public function add_fencer_fields() {
		echo "<tr><th colspan='2'><a href='" . add_query_arg( array( 'fence_plus_fencer_data' => 1, 'fp_id' => $this->profile_user_id ), get_edit_user_link( $this->profile_user_id ) ) . "'>" .
		  __( 'View Fencing Info', Fence_Plus::SLUG ) . "</a></th>";
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
