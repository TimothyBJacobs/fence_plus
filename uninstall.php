<?php
/**Uninstall File**/

//if uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();

/****Change all roles and remove specific user transients and user metadata***/
$args = array(
	'role' => 'fencer'
);

$fencers = get_users( $args );

foreach ( $fencers as $fencer ) {
	$userid = $fencer->ID;
	$fencer->remove_role( 'fencer' ); // Change role to subscriber
	$fencer->add_role( 'subscriber' );
	delete_user_meta( $userid, 'tj_fence_plus_usfaID' );
	delete_user_meta( $userid, 'tj_fence_plus_id' );
	delete_transient( $userid . 'AF_USFA_KEY' );
}

/****Delete Transients*****/
delete_transient( 'AF_CLUB_TOURNAMENT_KEY' );
delete_transient( 'AF_ALL_TOURNAMENT_KEY' );

/****Delete Options*******/
delete_option( 'widget_af_askfred_widget_upcoming_tournaments' );
delete_option( 'number_tournaments' );
delete_option( 'show_description' );
delete_option( 'none_text' );
delete_option( 'fence_plus_plugin_fencer_options' );
delete_option( 'fence_plus_plugin_coach_options' );

/****Remove User Roles*****/
remove_role( 'fencer' );
remove_role( 'coach' );

/*****Remove CRON Jobs****/
wp_clear_scheduled_hook( 'tj_fence_plus_cron_refresh_hook' );

