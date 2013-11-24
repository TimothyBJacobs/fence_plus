<?php
/**
 *
 * @package Notify
 * @subpackage
 * @since 0.1
 */

if ( ! defined( 'IBD_NOTIFY_URL' ) )
	define( 'IBD_NOTIFY_URL', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'IBD_NOTIFY_PATH' ) )
	define( 'IBD_NOTIFY_PATH', plugin_dir_path( __FILE__ ) );

if ( ! defined( "IBD_NOTIFY_DATABASE_CLASS" ) )
	define( 'IBD_NOTIFY_DATABASE_CLASS', 'IBD_Notify_Database_WordPress' );