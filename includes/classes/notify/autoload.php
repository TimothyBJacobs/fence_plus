<?php
/**
 *
 * @package Notify
 * @subpackage
 * @since 0.1
 */

IBD_Notify_Autoloader::register();

/**
 * Class IBD_Notify_Autoloader
 */
class IBD_Notify_Autoloader {
	/**
	 * register the autoloader
	 *
	 * @return bool
	 */
	public static function register() {
		return spl_autoload_register( array( 'IBD_Notify_Autoloader', 'load' ), false );
	}

	/**
	 * Load the required class
	 *
	 * @param $class
	 *
	 * @return bool
	 */
	public static function load( $class ) {
		$class = strtolower( str_replace( "IBD_Notify", "", $class ) );

		$path = explode( "_", $class );
		$last = array_pop( $path );
		$path = implode( "/", $path );

		$path = IBD_NOTIFY_PATH . "includes/" . $path . "/" . $last . ".php";

		if ( file_exists( $path ) === false || is_readable( $path ) === false ) {
			return false;
		}

		require $path;

		return true;
	}
}
