<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_Importer {

	/**
	 * Add actions
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'notify_import_complete' ) );
	}

	/**
	 * Print admin notice when import is completed
	 */
	public function notify_import_complete() {
		if ( 'completed' === self::check_database_completed() ) {
			?>

			<div class="updated">
				<?php echo apply_filters( "fence_plus_import_complete_notice", "<p><b>Fence Plus:</b> " . __( 'Fencer Import Completed', Fence_Plus::SLUG ) . "</p>" ); ?>
            </div>

			<?php

			self::remove_databse_update_completed();
		}
	}

	/**
	 * Update database to say import is complete
	 */
	static function notify_databse_import_complete() {
		update_option( Fence_Plus::SLUG . "-fencer-import", 'completed' );
	}

	/**
	 * Return the status of current import
	 *
	 * @return mixed|void
	 */
	static function check_database_completed() {
		return get_option( Fence_Plus::SLUG . '-fencer-import', false );
	}

	/**
	 *
	 */
	static function remove_databse_update_completed() {
		delete_option( Fence_Plus::SLUG . '-fencer-import' );
	}
}