<?php
/**
 *
 * @package Fence Plus
 * @subpackage Includes
 * @since 0.1
 */
class Fence_Plus_Admin {

	public function __construct() {
		add_action('init', array($this, 'requires'));
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
	}

	public function register_menus() {
		add_menu_page( 'Fence Plus', 'Fence Plus', 'manage_options', Fence_Plus::SLUG . "-options", array( 'Fence_Plus_Options', 'render' ) );
		add_submenu_page( Fence_Plus::SLUG . "-options", 'Fence Plus Importer', 'Importer', 'manage_options', Fence_Plus::SLUG . "-importer", array( new Fence_Plus_Importer_View, 'init' ) );
	}

	public function requires() {
		require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . 'class-importer.php' );
		require_once( FENCEPLUS_INCLUDES_VIEWS_DIR . 'class-options.php' );
		require_once( FENCEPLUS_INCLUDES_VIEWS_DIR . 'class-importer-view.php' );
		require_once( FENCEPLUS_INCLUDES_VIEWS_DIR . 'class-user-page.php' );
		require_once( FENCEPLUS_INCLUDES_VIEWS_DIR . 'class-user-table.php' );

		if ( defined( 'DOING_AJAX' ) ) {
			require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . 'class-importer-ajax.php' );
			new Fence_Plus_Importer_AJAX();
		}

		new Fence_Plus_Importer();
		new Fence_Plus_User_Table();
		new Fence_Plus_User_Page();
	}
}