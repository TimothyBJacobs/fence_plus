<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_Coach_Fencers {
	private $coach;

	/**
	 * @param Fence_Plus_Coach $coach
	 */
	public function __construct( Fence_Plus_Coach $coach ) {
		$this->coach = $coach;
		$this->styles_and_scripts();
		$this->render();
	}

	public function styles_and_scripts() {
		wp_enqueue_script( 'select2' );
		wp_enqueue_style( 'select2' );

		wp_register_style( 'add-fencer', FENCEPLUS_INCLUDES_CSS_URL . 'add-fencer.css' );
		wp_register_script( 'add-fencer', FENCEPLUS_INCLUDES_JS_URL . 'add-fencer.js', array( 'jquery' ) );
		wp_localize_script( 'add-fencer', 'fence_plus_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'coach_id' => $_GET['user_id'], 'select_placeholder' => __( 'Select a fencer', Fence_Plus::SLUG ) ) );
		wp_enqueue_style( 'add-fencer' );
		wp_enqueue_script( 'add-fencer' );

		wp_enqueue_style( 'fence-plus-admin' );
		wp_enqueue_script( 'fence-plus-profile-overview' );
		wp_enqueue_style( 'fence-plus-profile-overview' );
	}

	public function render() {
		if ( current_user_can( 'edit_users' ) ) :
			$fencers = Fence_Plus_Utility::get_all_fencers();
			?>
			<form id="fence-plus-add-fencer">
				<label for="fence-plus-select-fencer"><?php _e( 'Add a fencer', Fence_Plus::SLUG ); ?></label>
				<select id='fence-plus-select-fencer' style='width: 300px'>
					<option></option>
					<?php foreach ( $fencers as $fencer ) : ?>
						<option value="<?php echo $fencer->ID; ?>"><?php echo $fencer->display_name; ?></option>
					<?php endforeach; ?>
				</select>
				<input type="submit" id="fence-plus-submit" class="button" value="<?php _ex( 'Add', Fence_Plus::SLUG ); ?>">
				<img src=" <?php echo admin_url( '/images/wpspin_light.gif' ); ?> " class="waiting" id="fence-plus-ajax-loading" style="display:none;margin: 0 0 -3px 5px"/>
			</form>

			<div id="fence-plus-ajax-results"></div>
		<?php endif; ?>

		<?php foreach ( $this->coach->get_fencers() as $fencer_id ) : $fencer = Fence_Plus_Fencer::wp_id_db_load( $fencer_id ); ?>
			<?php $fencer->short_box(); ?>
		<?php endforeach; ?>
	<?php
	}
}