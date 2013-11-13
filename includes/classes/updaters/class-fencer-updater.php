<?php
/**
 * Class Fence_Plus_Fencer_Update
 *
 * @package Fence Plus
 * @subpackage Updaters
 * @since 0.1
 */

require_once ( FENCEPLUS_INCLUDES_UPDATERS_DIR . "interface-api-updater.php" );

class Fence_Plus_Fencer_Update implements Fence_Plus_API_Updater {

	/**
	 * @var string MD5 checksum of current fencer data
	 */
	private $md5_checksum_old;

	/**
	 * @var string MD5 checksum of new fencer data from API
	 */
	private $md5_checksum_new;

	/**
	 * @var Fence_Plus_Fencer object.
	 */
	private $fencer;

	/**
	 * @var array holds new data from the API
	 */
	private $data;

	/**
	 * @var bool whether to force data re-analysis or not
	 */
	private $force;

	/**
	 * @var Fence_Plus_Fencer holds the original fencer pre modification
	 */
	private $original_fencer;


	/**
	 * @param Fence_Plus_Fencer $fencer_object
	 * @param array|null $new_data data from askFRED API, if not provided will fetch
	 * @param bool $force whether to force data re-analysis or not
	 */
	public function __construct( Fence_Plus_Fencer $fencer_object, $new_data = null, $force = false ) {
		$this->fencer = $fencer_object;
		$this->data = $new_data;
		$this->force = $force;
		$this->md5_checksum_old = $this->fencer->get_md5_checksum();
		$this->original_fencer = clone $fencer_object;
	}

	/**
	 * Update the given fencer object
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException
	 */
	public function update() {
		if ( null === $this->data ) {
			try {
				$this->call_api();
			} catch (InvalidArgumentException $e) {
				throw $e;
			}
		}

		$this->create_md5_checksum();

		$this->fencer->set_md5_checksum( $this->md5_checksum_new );

		if ( true === $this->reprocessing_needed() || true === $this->force ) {
			$this->fencer->set_all_properties( $this->data );
			$this->process_results();
		}
	}

	/**
	 * Make actual API call to askFRED based on USFA ID
	 *
	 * @return array
	 *
	 * @throws InvalidArgumentException
	 */
	public function call_api() {
		require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . "class-askFRED_API.php" );

		$args = array(
			'version'  => 'v1',
			'resource' => 'fencer',
			'usfa_id'  => $this->fencer->get_usfa_id()
		);

		$args = apply_filters( 'fence_plus_fencer_update_args', $args, $this->fencer );

		try {
			$askfred_api = new askFRED_API( Fence_Plus_Options::get_instance()->api_key, $args );
			$data = $askfred_api->get_results();
		}
		catch ( InvalidArgumentException $e ) {
			throw $e;
		}

		$this->data = $data[0];
	}

	/**
	 * Generate an MD5 checksum
	 */
	public function create_md5_checksum() {
		$this->md5_checksum_new = hash( 'md5', serialize( $this->data ) );
	}

	/**
	 * Determine if data needs to be reprocessed
	 * Checks equivalency of MD5 hash of current data vs stored md5 hash
	 *
	 * @return bool
	 */
	public function reprocessing_needed() {
		if ( $this->md5_checksum_old == $this->md5_checksum_new ) {
			return false;
		}
		else {
			return true;
		}
	}

	/**
	 * Process results from API data
	 *
	 * Registers action to allow object to make all necessary processing updates
	 */
	public function process_results() {
		do_action( 'fence_plus_fencer_process_results', $this->fencer, $this->original_fencer );
	}
}