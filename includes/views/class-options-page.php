<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_Options_Page {
	/**
	 * @var array hold plugin options fields
	 */
	private $options_fields = array();

	/**
	 * Holds data from options
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Set up everything
	 */
	public function init() {
		wp_enqueue_style( 'fence-plus-admin' );

		$controller = Fence_Plus_Options_Controller::get_instance();
		$this->options_fields = $controller->get_fields();

		$options = Fence_Plus_Options::get_instance();
		$this->options = $options->get_options();

		wp_create_nonce( 'fence_plus_options_update' );

		if ( isset( $_POST['fence_plus_options_nonce'] ) ) {
			$data = $this->get_data();
			$this->options = $data;
			$controller->save( $data );
			$this->admin_notice();
		}

		$this->render();
	}

	/**
	 * Grab POST data
	 */
	public function get_data() {
		if ( ! wp_verify_nonce( $_POST['fence_plus_options_nonce'], 'fence_plus_options_update' ) )
			wp_die( 'Permissions check failed.' );

		$values = array();

		foreach ( $this->options_fields as $key => $value ) {
			if ( isset( $_POST[$key] ) )
				$values[$key] = $_POST[$key];
			else if ( isset( $this->options_fields[$key]['default'] ) )
				$values[$key] = $this->options_fields[$key]['default'];
		}

		return $values;
	}

	/**
	 * Print Options Updated Notice
	 */
	public function admin_notice() {
		echo '<div class="updated"><p>' . __( 'Options Updated', Fence_Plus::SLUG ) . '</p></div>';
	}

	/**
	 * Render option fields with type text
	 *
	 * @param $option_field
	 */
	public function render_text( $option_field ) {
		?>

		<label for="<?php echo esc_attr( $option_field['slug'] ); ?>"><?php echo $option_field['label']; ?></label>
		<input type="text" id="<?php echo esc_attr( $option_field['slug'] ); ?>" name="<?php echo esc_attr( $option_field['slug'] ); ?>" value="<?php echo esc_attr( $this->options[$option_field['slug']] ); ?>"

		<?php foreach ( $option_field['field_args'] as $attr => $value ) : ?>
			<?php echo $attr; ?>="<?php echo $value; ?>"
		<?php endforeach; ?>
		  >
		<p><?php echo $option_field['description'];  ?></p>

	<?php
	}

	/**
	 * Render options fields with type number
	 *
	 * @param $option_field
	 */
	public function render_number( $option_field ) {
		?>

		<label for="<?php echo esc_attr( $option_field['slug'] ); ?>"><?php echo $option_field['label']; ?></label>
		<input type="number" id="<?php echo esc_attr( $option_field['slug'] ); ?>" name="<?php echo esc_attr( $option_field['slug'] ); ?>" value="<?php echo esc_attr( $this->options[$option_field['slug']] ); ?>"

		<?php foreach ( $option_field['field_args'] as $attr => $value ) : ?>
			<?php echo $attr; ?>="<?php echo $value; ?>"
		<?php endforeach; ?>
		  >
		<p><?php echo $option_field['description'];  ?></p>

	<?php
	}

	/**
	 * Render options fields with type checkbox
	 *
	 * @param $option_field
	 */
	public function render_checkbox( $option_field ) {
		?>

		<label for="<?php echo esc_attr( $option_field['slug'] ); ?>"><?php echo $option_field['label']; ?></label>
		<input type="checkbox" id="<?php echo esc_attr( $option_field['slug'] ); ?>" name="<?php echo esc_attr( $option_field['slug'] ); ?>"
		       <?php echo true === (bool) $this->options[$option_field['slug']] ? 'checked="checked"' : ""; ?>

		<?php foreach ( $option_field['field_args'] as $attr => $value ) : ?>
			<?php echo $attr; ?>="<?php echo $value; ?>"
		<?php endforeach; ?>
		  >

		<span><?php echo $option_field['description'];  ?></span>

	<?php
	}

	/**
	 * Render section titles
	 *
	 * @param $option_field
	 */
	public function render_section_title( $option_field ) {
		echo "<h3>" . $option_field['title'] . "</h3>";
	}

	/**
	 * Render dividers
	 */
	public function render_hr() {
		echo '<hr class="light">';
	}

	/**
	 * Render the markup of the page
	 */
	public function render() {
		?>

		<div class="wrap">
			<h2><?php _e( "Fence Plus Options", Fence_Plus::SLUG ); ?></h2>

			<form id="fence-plus-options-form" action="#" method="post">

				<?php foreach ( $this->options_fields as $option_field ) : ?>

					<?php call_user_func( array( $this, 'render_' . $option_field['field_type'] ), $option_field ); ?>

				<?php endforeach; ?>

				<input type="submit" class="button button-primary" value="Update">

				<?php wp_nonce_field( 'fence_plus_options_update', 'fence_plus_options_nonce' ); ?>
			</form>
		</div>

	<?php
	}
}