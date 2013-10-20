<?php
/**
 *
 * @package Fence Plus
 * @subpackage Includes
 * @since 0.1
 */

class Fence_Plus_Cron {
	/**
	 * Add cron actions
	 */
	public function __construct() {
		add_action( 'fence_plus_cron', array( 'Fence_Plus_Cron', 'update_fencers' ) );
	}

	/**
	 * Update fencers
	 */
	public static function update_fencers() {
		require_once FENCEPLUS_INCLUDES_CLASSES_DIR . "class-askFRED_API.php";

		$fencer_users = Fence_Plus_Utility::get_all_fencers();
		$askfred_fencer_ids = array();
		$fencer_objects = array();

		foreach ( $fencer_users as $fencer_user ) { // loop over all fencer users and create Fence_Plus_Fencer objects
			$fencer = Fence_Plus_Fencer::wp_id_db_load( $fencer_user->ID );
			$askfred_fencer_ids[] = $fencer->get_id();
			$fencer_objects[$fencer->get_id()] = $fencer; // store the object along with the ID to be accessed later
		}

		$chunked_ids = array_chunk( $askfred_fencer_ids, 100 ); // chunk ids into batches of 100 because of API restriction

		$results = array();

		for ( $chunk = 0; $chunk < count( $chunked_ids ); $chunk ++ ) {
			$askfred_ids = $chunked_ids[$chunk];

			$args = array(
				'resource'   => 'fencer',
				'version'    => 'v1',
				'fencer_ids' => implode( ",", $askfred_ids ) // ids must be a comma separated list
			);

			try {
				$api = new askFRED_API( Fence_Plus_Options::get_instance()->api_key, $args );
				$results = array_merge( $results, $api->get_results() );
			}
			catch ( InvalidArgumentException $e ) {
				Fence_Plus_Utility::add_admin_notification( $e->getMessage(), 'error' );
				return;
			}
		}

		foreach ( $results as $result ) {
			$fencer_objects[$result['id']]->update( $result );
			$fencer_objects[$result['id']]->save();
		}
		do_action( 'fence_plus_all_fencers_auto_update_complete' );
	}
}