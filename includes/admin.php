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
		add_action( 'init', array( $this, 'requires' ) );
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_filter( 'editable_roles', array( $this, 'modify_editable_roles' ) );
		add_filter( 'gettext', array( $this, 'modify_texts' ), 10, 3 );
		add_filter( 'show_password_fields', array( $this, 'remove_password_edit_fields' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_meta_links' ), 10, 2 );
		add_filter( 'plugin_action_links_' . FENCEPLUS_FILE, array( $this, 'add_plugin_action_links' ) );
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
	 * Add plugin actions links
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
	 *
	 */
	public function register_menus() {
		add_menu_page( 'Fence Plus', 'Fence Plus', 'manage_options', Fence_Plus::SLUG . "-options", array( new Fence_Plus_Options_Page, 'init' ) );
		add_submenu_page( Fence_Plus::SLUG . "-options", 'Fence Plus Importer', 'Importer', 'manage_options', Fence_Plus::SLUG . "-importer", array( new Fence_Plus_Importer_View, 'init' ) );
	}

	/**
	 *
	 */
	public function requires() {
		require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . 'class-importer.php' );
		require_once( FENCEPLUS_INCLUDES_VIEWS_DIR . 'class-options-page.php' );
		require_once( FENCEPLUS_INCLUDES_VIEWS_DIR . 'class-importer-view.php' );
		require_once( FENCEPLUS_INCLUDES_VIEWS_DIR . 'class-user-profile.php' );
		require_once( FENCEPLUS_INCLUDES_VIEWS_DIR . 'class-user-table.php' );

		if ( defined( 'DOING_AJAX' ) ) {
			require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . 'class-importer-ajax.php' );
			new Fence_Plus_Importer_AJAX();
		}

		new Fence_Plus_Importer();
		new Fence_Plus_User_Table();
		new Fence_Plus_User_Page();

		$this->styles_and_scripts();
	}

	/**
	 * Register admin styles and scripts
	 */
	public function styles_and_scripts() {
		wp_register_style( 'fence-plus-profile-overview', FENCEPLUS_INCLUDES_CSS_URL . 'profile-overview.css' );
		wp_register_script( 'fence-plus-profile-overview', FENCEPLUS_INCLUDES_JS_URL . 'profile-overview.js', array( 'jquery', 'jquery-effects-blind' ) );

		wp_register_style( 'select2', FENCEPLUS_INCLUDES_JS_URL . 'select2/select2.css' );
		wp_register_script( 'select2', FENCEPLUS_INCLUDES_JS_URL . 'select2/select2.min.js', array( 'jquery' ), '3.4.3' );

		wp_register_style( 'fence-plus-admin', FENCEPLUS_INCLUDES_CSS_URL . 'importer.css' );
		wp_register_script( 'fence-plus-importer', FENCEPLUS_INCLUDES_JS_URL . 'importer.js', array( 'jquery' ) );
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
		if ( Fence_Plus_Coach::is_coach( wp_get_current_user() ) && 'default' == $domain ) {
			if ( "Users" == $untranslated_text )
				$translated_text = __( 'Fencers', Fence_Plus::SLUG );
			elseif ( "All Users" == $untranslated_text )
				$translated_text = __( 'All Fencers', Fence_Plus::SLUG );
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