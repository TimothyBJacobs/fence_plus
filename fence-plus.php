<?php
/**
 * Plugin Name: Fence Plus
 * Plugin URI: http://www.ironbounddesigns.com/fence-plus
 * Description: An interactive dashboard designed for fencers and their coaches, powered by askFRED
 * Version: 0.1
 * Author: Iron Bound Designs
 * Author URI: http://www.ironbounddesigns.com
 * License: GPL2
 */

define( 'FENCEPLUS_DIR', plugin_dir_path( __FILE__ ) );
define( 'FENCEPLUS_INCLUDES_DIR', FENCEPLUS_DIR . "includes/" );
define( 'FENCEPLUS_INCLUDES_JS_DIR', FENCEPLUS_INCLUDES_DIR . "js/" );
define( 'FENCEPLUS_INCLUDES_CSS_DIR', FENCEPLUS_INCLUDES_DIR . "css/" );
define( 'FENCEPLUS_INCLUDES_CLASSES_DIR', FENCEPLUS_INCLUDES_DIR . "classes/" );
define( 'FENCEPLUS_INCLUDES_UPDATERS_DIR', FENCEPLUS_INCLUDES_CLASSES_DIR . "updaters/" );
define( 'FENCEPLUS_INCLUDES_VIEWS_DIR', FENCEPLUS_INCLUDES_DIR . "views/" );
define( 'FENCEPLUS_INCLUDES_VIEWS_COACH_PROFILE_PAGES_DIR', FENCEPLUS_INCLUDES_VIEWS_DIR . "coach-profile-pages/" );
define( 'FENCEPLUS_INCLUDES_VIEWS_FENCER_PROFILE_PAGES_DIR', FENCEPLUS_INCLUDES_VIEWS_DIR . "fencer-profile-pages/" );

define( 'FENCEPLUS_URL', plugin_dir_url( __FILE__ ) );
define( 'FENCEPLUS_INCLUDES_URL', FENCEPLUS_URL . "includes/" );
define( 'FENCEPLUS_INCLUDES_JS_URL', FENCEPLUS_INCLUDES_URL . "js/" );
define( 'FENCEPLUS_INCLUDES_CSS_URL', FENCEPLUS_INCLUDES_URL . "css/" );
define( 'FENCEPLUS_INCLUDES_CLASSES_URL', FENCEPLUS_INCLUDES_URL . "classes/" );
define( 'FENCEPLUS_INCLUDES_UPDATERS_URL', FENCEPLUS_INCLUDES_CLASSES_URL . "updater/" );
define( 'FENCEPLUS_INCLUDES_VIEWS_URL', FENCEPLUS_INCLUDES_URL . "views/" );
define( 'FENCEPLUS_INCLUDES_VIEWS_COACH_PROFILE_PAGES_URL', FENCEPLUS_INCLUDES_VIEWS_URL . "coach-profile-pages/" );
define( 'FENCEPLUS_INCLUDES_VIEWS_FENCER_PROFILE_PAGES_URL', FENCEPLUS_INCLUDES_VIEWS_URL . "fencer-profile-pages/" );

define( 'AF_API_KEY', 'a8a854b2e3c3eac74bfda01f625182b8' );

require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . "class-utility.php" );
require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . "class-fencer.php" );
require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . "class-coach.php" );

/**
 * Class Fence_Plus
 */
class Fence_Plus {
	/**
	 *
	 */
	const VERSION = 0.1;
	/**
	 *
	 */
	const PREFIX = "fence_plus_";
	/**
	 *
	 */
	const NAME = "Fence Plus";
	/**
	 *
	 */
	const SLUG = "fence-plus";

	/**
	 *
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ), 1 );
		add_filter( 'map_meta_cap', array( $this, 'coach_edit_user' ), 10, 4 );
		add_action( 'check_passwords', array( $this, 'do_not_allow_coach_modify_passwords' ), 10, 3 );

		if ( is_admin() ) {
			require_once( FENCEPLUS_INCLUDES_DIR . "admin.php" );
			$admin = new Fence_Plus_Admin();
		}

		if ( defined( 'DOING_AJAX' ) ) {
			require_once FENCEPLUS_INCLUDES_CLASSES_DIR . "class-ajax.php";
			new Fence_Plus_AJAX();
		}
	}

	/**
	 *
	 */
	public function init() {
		require_once( FENCEPLUS_INCLUDES_DIR . "library.php" );
		require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . "class-permissions-handler.php" );
		$this->register_tournament_post_types();
	}

	/**
	 *
	 */
	public static function activate() {

		add_role( 'fencer', 'Fencer', array(
				'read'              => true,
				'edit_posts'        => false,
				'manage_posts'      => false,
				'publish_posts'     => false,
				'edit_others_posts' => false,
				'delete_posts'      => false,
			)
		);

		add_role( 'coach', 'Coach', array(
				'read'              => true,
				'edit_posts'        => false,
				'manage_posts'      => false,
				'publish_posts'     => false,
				'edit_others_posts' => false,
				'delete_posts'      => false,
				'promote_users'     => false,
			)
		);

		$fencer_role = get_role( 'fencer' );
		$fencer_role->add_cap( 'view_tournaments' );
		$fencer_role->add_cap( 'edit_dashboard' );

		$coach_role = get_role( 'coach' );
		$coach_role->add_cap( 'view_tournaments' );
		$coach_role->add_cap( 'edit_dashboard' );
	}

	/**
	 *
	 */
	public function register_tournament_post_types() {
		require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . "class-post-type.php" );

		$name_args = array(
			'post_type_name' => 'tournament',
			'singular'       => 'Tournament',
			'plural'         => 'Tournaments',
			'slug'           => 'tournament'
		);

		$args = array(
			'public'   => false,
			'show_ui'  => true,
			'supports' => array( false, false, false )
		);
		$args = apply_filters( "fence_plus_register_tournament_post_type_args", $args );

		new CPT( $name_args, $args );
	}

	/**
	 * Allows coaches to edit their fencers
	 *
	 * Modify capabilities so that if a fencer is being edited by a coach
	 * in its valid coach list, then that coach has permissions to edit
	 *
	 * @param $caps
	 * @param $cap
	 * @param $user_id
	 * @param $args
	 *
	 * @return string
	 */
	public function coach_edit_user( $caps, $cap, $user_id, $args ) {
		if ( $cap == 'edit_user' ) {
			$fencer_id = $args[0];

			try {
				$permissions = new Fence_Plus_Permissions_Handler( (int) $user_id, (int) $fencer_id );
			}
			catch ( InvalidArgumentException $e ) {
				return $caps;
			}

			if ( true === $permissions->can_object1_edit_object2() )
				return array( 'coach' );
		}

		return $caps;
	}

	/**
	 * Don't let coach edit passwords by emptying pass1 and pass2 values.
	 *
	 * @param $login
	 * @param $pass1
	 * @param $pass2
	 */
	public function do_not_allow_coach_modify_passwords( $login, &$pass1, &$pass2 ) {
		if ( Fence_Plus_Coach::is_coach( wp_get_current_user() ) ) {
			$pass1 = "";
			$pass2 = "";
		}
	}
}

$fence_plus = new Fence_Plus();

register_activation_hook( __FILE__, array( 'Fence_Plus', 'activate' ) );