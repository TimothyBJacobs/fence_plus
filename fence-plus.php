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
define( 'FENCEPLUS_INCLUDES_VIEWS_DIR', FENCEPLUS_INCLUDES_DIR . "views/" );
define( 'FENCEPLUS_INCLUDES_CLASSES_DIR', FENCEPLUS_INCLUDES_DIR . "classes/" );

define( 'FENCEPLUS_URL', plugin_dir_url( __FILE__ ) );
define( 'FENCEPLUS_INCLUDES_URL', FENCEPLUS_URL . "includes/" );
define( 'FENCEPLUS_INCLUDES_JS_URL', FENCEPLUS_INCLUDES_URL . "js/" );
define( 'FENCEPLUS_INCLUDES_CSS_URL', FENCEPLUS_INCLUDES_URL . "css/" );
define( 'FENCEPLUS_INCLUDES_VIEWS_URL', FENCEPLUS_INCLUDES_URL . "views/" );
define( 'FENCEPLUS_INCLUDES_CLASSES_URL', FENCEPLUS_INCLUDES_URL . "classes/" );

define( 'AF_API_KEY', 'a8a854b2e3c3eac74bfda01f625182b8' );

class Fence_Plus {
	const VERSION = 0.1;
	const PREFIX = "fence_plus_";
	const NAME = "Fence Plus";
	const SLUG = "fence-plus";

	public function __construct() {
		add_action( 'init', array( $this, 'init' ), 1 );

		if ( is_admin() ) {
			require_once( FENCEPLUS_INCLUDES_DIR . "admin.php" );
			$admin = new Fence_Plus_Admin();
		}
	}

	public function init() {
		require_once( FENCEPLUS_INCLUDES_DIR . "library.php" );
		$this->register_tournament_post_types();
	}

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
			)
		);

		$fencer_role = get_role( 'fencer' );
		$fencer_role->add_cap( 'view_tournaments' );
		$fencer_role->add_cap( 'edit_dashboard' );

		$coach_role = get_role( 'coach' );
		$coach_role->add_cap( 'list_users' );
		$coach_role->add_cap( 'view_tournaments' );
		$coach_role->add_cap( 'edit_dashboard' );
	}

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

		$tournament = new CPT( $name_args, $args );
	}
}

$fence_plus = new Fence_Plus();

register_activation_hook( __FILE__, array( 'Fence_Plus', 'activate' ) );