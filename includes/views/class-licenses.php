<?php
/**
 *
 * @package Fence Plus
 * @subpackage Views
 * @since 0.1
 */

class Fence_Plus_Licenses_View {
	/**
	 * @var array
	 */
	private $extensions = array();
	/**
	 * @var array
	 */
	private $licenses = array();

	/**
	 * Initiate everything
	 */
	public function init() {
		$this->extensions = apply_filters( 'fence_plus_extensions', $this->extensions );

		if ( isset( $_POST['submit'] ) )
			$this->save();

		if ( isset( $_POST['deactivate'] ) )
			$this->deactivate();

		$this->licenses = get_option( 'fence_plus_licenses', array() );

		$this->render();
	}

	/**
	 * Save everything
	 */
	private function save() {
		$licenses = array();

		foreach ( $_POST as $key => $value ) {
			foreach ( $this->extensions as $extension ) {
				if ( $extension['slug'] == $key ) {
					$licenses[$key] = array(
						'key' => trim( wp_strip_all_tags( $value ) )
					);
				}
			}
		}

		update_option( 'fence_plus_licenses', $licenses );

		do_action( 'fence_plus_activate_licenses', $licenses, $this->extensions );

		$factory = new IBD_Notify_Admin_Factory();
		$notification = $factory->make( get_current_user_id(), 'Fence Plus', 'Licenses activated', array( 'class' => 'updated' ) );
		$notification->send();
	}

	/**
	 * Deactivate licenses.
	 */
	private function deactivate() {
		foreach ( $_POST as $key => $value ) {
			foreach ( $this->extensions as $extension ) {
				if ( $extension['slug'] == $key ) {
					Fence_Plus_Licenses::deactivate_extension( $key );
				}
			}
		}
	}

	/**
	 * Render the page
	 */
	private function render() {
		?>
		<div class="wrap">

			<?php do_action( 'admin_notices' ); ?>

			<h2>Fence Plus Licenses</h2>

			<form id="fence-plus-licenses" method="POST">
				<table class="form-table">

					<?php foreach ( $this->extensions as $extension ) : ?>
						<tr>
							<th>
								<label for="<?php echo $extension['slug']; ?>"><?php echo $extension['name']; ?></label>
							</th>
							<td>
								<input type="text" id="<?php echo $extension['slug']; ?>" name="<?php echo $extension['slug']; ?>" value="<?php echo isset( $this->licenses[$extension['slug']] ) ? $this->licenses[$extension['slug']]['key'] : ""; ?>">

								<?php $status = $this->licenses[$extension['slug']]['status']; ?>
								<?php if ( $status == 'valid' ) : ?>
									<input type="submit" name="deactivate" id="deactivate" class="button" value="<?php _e( 'Deactivate', Fence_Plus::SLUG ); ?>">
								<?php elseif ( $status == 'inactive' ) : ?>
									<span><?php _e( "Inactive", Fence_Plus::SLUG ); ?></span>
								<?php elseif ( $status = "invalid" ) : ?>
									<span><?php _e( "Invalid License Key", Fence_Plus::SLUG ); ?></span>
								<?php endif; ?>

							</td>
						</tr>
					<?php endforeach; ?>

				</table>
				<?php submit_button( __( "Activate", Fence_Plus::SLUG ) ); ?>
			</form>
		</div>
	<?php
	}
}