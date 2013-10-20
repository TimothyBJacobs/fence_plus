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
		$coach_ids = $this->fencer->get_coaches();
		$coaches = array();

		foreach ( $coach_ids as $coach_id ) {
			try {
				$coaches[] = new Fence_Plus_Coach( $coach_id );
			}
			catch ( InvalidArgumentException $e ) {
				continue;
			}
		}

		if ( empty( $coaches ) )
			_e( "There are no coaches assigned to your account.", Fence_Plus::SLUG );

		foreach ( $coaches as $coach ) {
			?>

			<div class="fence-plus-coach-overview-fencer postbox" id="coach-<?php echo $coach->get_wp_id(); ?>">
				<div class="inside">
					<div class="spacing-wrapper">
						<div class="remove right">
							<span data-coach-id="<?php echo $coach->get_wp_id(); ?>">x</span>
						</div>
						<div class="fencer-info">
							<h2 class="coach-name"><?php echo $coach->display_name; ?></h2>
						</div>
					</div>
				</div>
			</div>

		<?php
		}
	}
}