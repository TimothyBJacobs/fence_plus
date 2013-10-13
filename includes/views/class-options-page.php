<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_Options_Page {
	/**
	 * @var array hold plugin options
	 */
	private $options;

	/**
	 * @var array
	 */
	private $defaults = array(
		'club_name'       => '',
		'club_initials'   => '',
		'club_address'    => '',
		'update_interval' => 24
	);

	/**
	 * Set up everything
	 */
	public function init() {
		wp_enqueue_style( 'fence-plus-admin' );

		$this->options = get_option( 'fence_plus_options' );

		wp_create_nonce( 'fence_plus_options_update' );

		if ( isset( $_POST['fence_plus_options_nonce'] ) )
			$this->get_data();

		$this->render();
	}

	/**
	 * Magic method to get data from options
	 *
	 * @param $key
	 *
	 * @return string
	 */
	public function __get( $key ) {
		if ( isset( $this->options[$key] ) )
			return $this->options[$key];
		elseif ( isset( $this->defaults[$key] ) )
			return $this->defaults[$key];
		else
			return "";
	}

	/**
	 * Grab POST data
	 */
	public function get_data() {
		if ( ! wp_verify_nonce( $_POST['fence_plus_options_nonce'], 'fence_plus_options_update' ) )
			wp_die( 'Permissions check failed.' );

		$values = array();

		foreach ( $_POST as $key => $value ) {
			if ( array_key_exists( $key, $this->defaults ) ) {
				$values[$key] = $value;
			}
		}

		$this->sanitize( $values );
	}

	/**
	 * Sanitize post data
	 *
	 * @param $values
	 */
	public function sanitize( $values ) {
		foreach ( $values as $key => $value ) {
			switch ($key) {
				case 'club_initials':
					$value = strtoupper($value);
					break;
				case 'update_interval':
					$value = (int) $value;
					break;
			}

			$values[$key] = sanitize_text_field( $value );
		}
		$this->save( $values );
	}

	/**
	 * Save data to options
	 *
	 * @param $values
	 */
	public function save( $values ) {
		$this->options = $values;
		update_option( 'fence_plus_options', $values );
	}

	/**
	 * Render the markup of the page
	 */
	public function render() {
		?>

		<div class="wrap">
			<h2><?php _e( "Fence Plus Options", Fence_Plus::SLUG ); ?></h2>

			<form id="fence-plus-options-form" action="#" method="post">
				<h3><?php _e( 'Club Information', Fence_Plus::SLUG ); ?></h3>

				<label for="club-name"><?php _e( 'Club Name', Fence_Plus::SLUG ); ?></label>
				<input type="text" name="club_name" id="club-name" value="<?php echo esc_attr($this->club_name); ?>">

				<label for="club-initials"><?php _e( 'Club Initials', Fence_Plus::SLUG ); ?></label>
				<input type="text" name="club_initials" id="club-initials" value="<?php echo esc_attr($this->club_initials); ?>">

				<label for="club-address"><?php _e( 'Club Address', Fence_Plus::SLUG ); ?></label>
				<input type="text" name="club_address" id="club-address" value="<?php echo esc_attr($this->club_address); ?>">
				<p><?php _e( "Make sure this is the same address used on askFRED.", Fence_Plus::SLUG ); ?></p>

				<h3><?php _e( "Extra configuration", Fence_Plus::SLUG ); ?></h3>

				<label for="update-interval"><?php _e( 'Update interval (hours)', Fence_Plus::SLUG ); ?></label>
				<input type="number" name="update_interval" id="update-interval" min="1" value="<?php echo esc_attr($this->update_interval); ?>">
				<p><?php _e( "How often fencers and tournaments are updated. Warning, this can be resource intensive. Recommended setting 24 hours.", Fence_Plus::SLUG ); ?></p>

				<input type="submit" class="button button-primary" value="Update">

				<?php wp_nonce_field( 'fence_plus_options_update', 'fence_plus_options_nonce' ); ?>
			</form>
		</div>

	<?php
	}
}