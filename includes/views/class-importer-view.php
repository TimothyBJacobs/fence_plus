<?php
/**
 *
 * @package Fence Plus
 * @subpackage Views
 * @since 0.1
 */

class Fence_Plus_Importer_View {
	/**
	 * Runs on 'admin_init'
	 */
	public function init() {
		$this->styles_and_scripts();
		$this->render();
	}

	/**
	 * Enqueue JS and CSS
	 */
	public function styles_and_scripts() {
		wp_register_style( 'fence-plus-admin', FENCEPLUS_INCLUDES_CSS_URL . 'admin.css' );
		wp_enqueue_style( 'fence-plus-admin' );

		wp_register_script( 'fence-plus-importer', FENCEPLUS_INCLUDES_JS_URL . 'importer.js', array( 'jquery' ) );
		wp_enqueue_script( 'fence-plus-importer' );
		wp_localize_script( 'fence-plus-importer', 'fence_plus_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

		wp_enqueue_media();
	}

	/**
	 * Render the actual page
	 */
	public function render() {
		?>

		<div class="wrap">
	<table style="width: 100%">
		<tbody>
		<tr>
			<td valign="top">
				<h2><?php _e( "Fence Plus Importer", Fence_Plus::SLUG ); ?></h2>

				<form id="<?php echo Fence_Plus::SLUG . "-options"; ?>" action="#" method="post" style="padding-top: 30px">
					<div class="step usfa-id">
						<h4><span class="number"><?php _e( '1.', Fence_Plus::SLUG ); ?> </span><?php _e( 'Enter the USFA ID of a fencer who attends your club', Fence_Plus::SLUG ); ?></h4>
						<label for="usfa-club-id"><?php _e( 'USFA ID', Fence_Plus::SLUG ); ?></label>
						<input type="text" id="usfa-club-id"/>
					</div>

					<div class="step wipe">
						<h4><span class="number"><?php _e( '2.', Fence_Plus::SLUG ); ?> </span><?php _e( 'Delete all of the fencers already in your installation', Fence_Plus::SLUG ); ?></h4>
						<input type="checkbox" id="wipe-fencers"/>
						<label class="inline" for="wipe-fencers"><?php _e( 'Yes, Delete all fencers', Fence_Plus::SLUG ); ?></label>
					</div>

					<div class="step csv">
						<h4><span class="number"><?php _e( '3.', Fence_Plus::SLUG ); ?> </span><?php _e( 'Import USFA IDs and email address from CSV (optional)', Fence_Plus::SLUG ); ?></h4>
						<label for="csv-import"><?php _e( 'CSV', Fence_Plus::SLUG ); ?></label>
						<input type="text" name="csv-import" id="csv-import"/>
						<input class="button" name="csv-import_button" id="csv-import_button" value="<?php _e( "Upload", Fence_Plus::SLUG ); ?>"/>
					</div>

					<div class="step save">
						<button class="big" id="import-fencers"><?php _e( 'Import Fencers', Fence_Plus::SLUG ); ?></button>
					</div>

					<hr style="width: 100%;">
					<div class="response">
						<img src=" <?php echo admin_url( '/images/wpspin_light.gif' ); ?> " class='waiting' id='ajax_loading' style='display:none;margin: 0 0 -3px 5px'/>
						<span id="response-data"></span>
					</div>


				</form>
			</td>
			<td valign="top" style="padding-top: 60px; width: 150px">
				<div class="metabox-holder postbox" style="padding-top: 0; margin-top: 10px; cursor: auto;">
				<h3 class="hndle" style="cursor: auto;"><span>Fence Plus</span></h3>
				<div class="inside">

				</div>
			</div>
			</td>
		</tr>
		</tbody>
	</table>
</div>

	<?php
	}
}