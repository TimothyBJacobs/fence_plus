<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_Fencer_Profile_Coaches {
	/**
	 * @var Fence_Plus_Fencer
	 */
	private $fencer;

	/**
	 * @param Fence_Plus_Fencer $fencer
	 */
	public function __construct( Fence_Plus_Fencer $fencer ) {
		$this->fencer = $fencer;
		wp_enqueue_style( 'genericons' );
		wp_enqueue_style( 'fence-plus-coach-overview-fencer-box' );
		wp_enqueue_script( 'fence-plus-coach-overview-fencer-box' );

		wp_localize_script( 'fence-plus-coach-overview-fencer-box', 'fence_plus_ajax', array(
				'ajax_url'           => admin_url( 'admin-ajax.php' ),
				'fencer_id'          => isset( $_GET['fp_id'] ) ? $_GET['fp_id'] : get_current_user_id(),
				'select_placeholder' => __( 'Select a fencer', Fence_Plus::SLUG )
			)
		);

		$this->render();
	}

	/**
	 * Render coaches page
	 */
	public function render() {
		// set up current fencer's coaches
		$coach_ids = $this->fencer->get_coaches();
		$fencer_coaches = array();

		foreach ( $coach_ids as $coach_id ) {
			try {
				$fencer_coaches[] = new Fence_Plus_Coach( $coach_id );
			}
			catch ( InvalidArgumentException $e ) {
				continue;
			}
		}

		// set up coaches that can be added to a fencer
		$coach_users = Fence_Plus_Utility::get_all_coaches( $this->fencer->get_wp_id(), false );
		$new_coaches = array();
		foreach ( $coach_users as $coach ) {
			try {
				$new_coaches[] = new Fence_Plus_Coach( $coach->ID );
			}
			catch ( InvalidArgumentException $e ) {
				continue;
			}
		}

		?>

		<div id="old-coaches">
			<span id="no-coach-message" style="<?php echo ! empty( $fencer_coaches ) ? "display:none" : ""; ?>">
				<?php _e( "There are no new coaches assigned to your account.", Fence_Plus::SLUG ); ?>
			</span>

			<?php foreach ( $fencer_coaches as $coach ) : ?>
				<div class="fence-plus-coach-overview-fencer postbox old-coach" id="coach-<?php echo $coach->get_wp_id(); ?>">
					<div class="inside">
						<div class="spacing-wrapper">
							<div class="action right">
								<span data-coach-id="<?php echo $coach->get_wp_id(); ?>"></span>
							</div>
							<div class="fencer-info">
								<h2 class="coach-name"><?php echo $coach->display_name; ?></h2>
							</div>
						</div>
					</div>
				</div>

			<?php endforeach; ?>
	    </div>

		<hr class="light">

		<h3><?php _e( 'Add a coach', Fence_Plus::SLUG ); ?></h3>
		<div id="new-coaches">
			<span id="no-new-coach-message" style="<?php echo ! empty( $new_coaches ) ? "display:none" : ""; ?>">
				<?php _e( "There are no other coaches that are not assigned to your account.", Fence_Plus::SLUG ); ?>
			</span>

			<?php foreach ( $new_coaches as $coach ) : ?>
				<div class="fence-plus-coach-overview-fencer postbox new-coach" id="coach-<?php echo $coach->get_wp_id(); ?>">
					<div class="inside">
						<div class="spacing-wrapper">
							<div class="action right">
								<span data-coach-id="<?php echo $coach->get_wp_id(); ?>"></span>
							</div>
							<div class="fencer-info">
								<h2 class="coach-name"><?php echo $coach->display_name; ?></h2>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
	  </div>
	<?php

	}
}