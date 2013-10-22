<?php
/**
 *
 * @package Fence Plus
 * @subpackage Includes
 * @since 0.1
 */
class Fence_Plus_Admin {

	/**
	 *
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'current_screen', array( $this, 'requires' ) );
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_notices', array( 'Fence_Plus_Utility', 'display_notification' ) );
		add_action( 'delete_user', array( 'Fence_Plus_Utility', 'remove_fencer_data' ) );
		add_action( 'delete_user', array( 'Fence_Plus_Utility', 'remove_coach_data' ) );

		add_filter( 'editable_roles', array( $this, 'modify_editable_roles' ) );
		add_filter( 'gettext', array( $this, 'modify_texts' ), 10, 3 );
		add_filter( 'show_password_fields', array( $this, 'remove_password_edit_fields' ), 10, 2 );

		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_meta_links' ), 10, 2 );
		add_filter( 'plugin_action_links_' . FENCEPLUS_FILE, array( $this, 'add_plugin_action_links' ) );
		add_filter( 'set-screen-option', array( $this, 'save_fencer_per_page' ), 10, 3 );
	}

	/**
	 * Add links under plugin description
	 *
	 * @param $links
	 * @param $file
	 *
	 * @return array
	 */
	function add_plugin_meta_links( $links, $file ) {
		if ( $file == FENCEPLUS_FILE ) {
			$links[] = '<a href="http://www.ironbounddesigns.com/fence-plus" target="_blank">' . __( 'Purchase Add-ons', Fence_Plus::SLUG ) . '</a>';
		}

		return $links;
	}

	/**
	 * Add plugin actions links next to activate/deactivate
	 *
	 * @param $links
	 *
	 * @return array
	 */
	function add_plugin_action_links( $links ) {
		$links[] = '<a href="' . get_admin_url( null, 'admin.php?page=fence-plus-options' ) . '">' . __( 'Settings', Fence_Plus::SLUG ) . '</a>';
		$links[] = '<a href="' . get_admin_url( null, 'admin.php?page=fence-plus-importer' ) . '">' . __( 'Importer', Fence_Plus::SLUG ) . '</a>';

		return $links;
	}

	/**
	 * Register admin menus
	 */
	public function register_menus() {
		add_menu_page( 'Fence Plus', 'Fence Plus', 'manage_options', Fence_Plus::SLUG . "-options", array( new Fence_Plus_Options_Page, 'init' ) );
		add_submenu_page( Fence_Plus::SLUG . "-options", 'Fence Plus Importer', 'Importer', 'manage_options', Fence_Plus::SLUG . "-importer", array( new Fence_Plus_Importer_View, 'init' ) );

		add_users_page( __( "Fencers", Fence_Plus::SLUG ), __( "Fencers", Fence_Plus::SLUG ), 'view_fencers', 'fence_plus_fencers_list_page', 'fence_plus_fencers_list_page' );
	}

	/**
	 * Dynamically require any necessary files
	 */
	public function requires() {
		global $current_screen;

		switch ( $current_screen->base ) {
			case 'users_page_fence_plus_fencers_list_page';
			case 'profile_page_fence_plus_fencers_list_page':
				add_screen_option( 'per_page', array( 'label' => __( 'Fencers', Fence_Plus::SLUG ), 'default' => 20, 'option' => 'fencer_list_table_per_page' ) );
				require_once( FENCEPLUS_INCLUDES_VIEWS_DIR . "class-fencer-list-table.php" );
				break;

			case 'user-edit';
			case 'profile' :
				require_once( FENCEPLUS_INCLUDES_VIEWS_DIR . 'class-user-profile.php' );
				new Fence_Plus_User_Page();
				break;

			case 'users':
				require_once( FENCEPLUS_INCLUDES_VIEWS_DIR . 'class-user-table.php' );
				new Fence_Plus_User_Table();
				break;
		}
	}

	/**
	 * Require files that must be included before current_screen is populated,
	 * and anything else that needs to be included on init.
	 */
	public function init() {
		require_once( FENCEPLUS_INCLUDES_VIEWS_DIR . 'class-options-page.php' );
		require_once( FENCEPLUS_INCLUDES_VIEWS_DIR . 'class-importer-view.php' );

		if ( defined( 'DOING_AJAX' ) ) {
			require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . 'class-importer-ajax.php' );
			new Fence_Plus_Importer_AJAX();
		}

		$this->styles_and_scripts();
	}

	/**
	 * Register admin styles and scripts
	 */
	public function styles_and_scripts() {
		wp_register_style( 'fence-plus-profile', FENCEPLUS_INCLUDES_CSS_URL . 'profile.css', array( 'fence-plus-admin' ) );
		wp_register_style( 'fence-plus-profile-overview', FENCEPLUS_INCLUDES_CSS_URL . 'fencer-overview-box.css', array( 'fence-plus-admin' ) );
		wp_register_script( 'fence-plus-profile-overview', FENCEPLUS_INCLUDES_JS_URL . 'profile-overview.js', array( 'jquery', 'jquery-effects-blind' ) );

		wp_register_style( 'fence-plus-coach-overview-fencer-box', FENCEPLUS_INCLUDES_CSS_URL . 'coach-overview-fencer-box.css', array( 'fence-plus-admin' ) );
		wp_register_script( 'fence-plus-coach-overview-fencer-box', FENCEPLUS_INCLUDES_JS_URL . 'coach-overview-fencer-box.js', array( 'jquery', 'jquery-effects-blind', 'jquery-effects-fade' ) );

		wp_register_style( 'select2', FENCEPLUS_INCLUDES_JS_URL . 'select2/select2.css' );
		wp_register_script( 'select2', FENCEPLUS_INCLUDES_JS_URL . 'select2/select2.min.js', array( 'jquery' ), '3.4.3' );

		wp_register_style( 'fence-plus-admin', FENCEPLUS_INCLUDES_CSS_URL . 'admin.css' );
		wp_register_script( 'fence-plus-importer', FENCEPLUS_INCLUDES_JS_URL . 'importer.js', array( 'jquery' ) );

		wp_register_style( 'genericons', FENCEPLUS_INCLUDES_CSS_URL . 'genericons/genericons.css', array( 'fence-plus-admin' ), '3.0.1' );
	}

	public function save_fencer_per_page( $status, $option, $value ) {
		if ( 'fencer_list_table_per_page' == $option )
			return $value;
	}

	/**
	 * Don't allow coaches to see any other roles for fencers except for 'fencer'
	 *
	 * @param $roles
	 *
	 * @return array
	 */
	public function modify_editable_roles( $roles ) {
		if ( true === Fence_Plus_Coach::is_coach( wp_get_current_user() ) ) {
			$fencer = $roles['fencer'];
			$roles = array( 'fencer' => $fencer );
		}

		return $roles;
	}

	/**
	 * Modify instances of 'user' to 'fencer' for all coaches
	 *
	 * @param $translated_text
	 * @param $untranslated_text
	 * @param $domain
	 *
	 * @return string
	 */
	public function modify_texts( $translated_text, $untranslated_text, $domain ) {
		global $current_screen;

		if ( Fence_Plus_Coach::is_coach( wp_get_current_user() ) && 'default' == $domain ) {
			if ( "Users" == $untranslated_text )
				$translated_text = __( 'Fencers', Fence_Plus::SLUG );
			elseif ( "All Users" == $untranslated_text || "Profile" == $untranslated_text )
				$translated_text = __( 'All Fencers', Fence_Plus::SLUG );

		}
		elseif ( "Bulk Actions" == $untranslated_text && $current_screen->base == 'users_page_fence_plus_fencers_list_page' ) {
			$translated_text = __( 'Add Fencers to Coach', Fence_Plus::SLUG );
		}

		return $translated_text;
	}

	/**
	 * Remove password fields from pages coaches have access to
	 *
	 * @param $show
	 * @param $profile_user int|null
	 *
	 * @return bool
	 */
	public function remove_password_edit_fields( $show, $profile_user = null ) {
		if ( null !== $profile_user && Fence_Plus_Coach::is_coach( wp_get_current_user() && Fence_Plus_Fencer::is_fencer( $profile_user ) ) )
			return false;
		else
			return $show;
	}


}