<?php
/**
 * Class Fence_Plus_Fencer_Update
 *
 * @package Fence Plus
 * @subpackage Updaters
 * @since 0.1
 */

require_once ( FENCEPLUS_INCLUDES_UPDATERS_DIR . "abstract-class-api-updater.php" );

class Fence_Plus_Fencer_Update implements Fence_Plus_API_Updater {

	/**
	 * @var string MD5 checksum of new API data
	 */
	private $md5_checksum;

	/**
	 * @var Fence_Plus_Fencer object.
	 */
	private $object;

	/**
	 * @var array holds new data from the API
	 */
	private $data;

	/**
	 * @param Fence_Plus_Fencer $fencer_object
	 * @param array|null $new_data data from askFRED API, if not provided will fetch
	 */
	public function __construct( Fence_Plus_Fencer $fencer_object, $new_data = null ) {
		$this->object = $fencer_object;
		$this->data = $new_data;
		$this->md5_checksum = $this->object->get_md5_checksum();
	}

	/**
	 * Update the given fencer object
	 *
	 * @return void
	 */
	public function update() {
		if ( null === $this->data ) {
			$this->call_api();
		}

		$this->object->set_all_properties( $this->data );

		if ( $this->reprocessing_needed() ) {
			$this->object->set_md5_checksum( $this->md5_checksum );
			$this->process_results();
		}
	}

	/**
	 * Make actual API call to askFRED based on USFA ID
	 *
	 * @return array
	 */
	public function call_api() {
		require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . "class-askFRED_API.php" );

		$args = array(
			'version'  => 'v1',
			'resource' => 'fencer',
			'usfa_id'  => $this->object->get_usfa_id()
		);

		$args = apply_filters( 'fence_plus_fencer_update_args', $args, $this->object );

		$askfred_api = new askFRED_API( AF_API_KEY, $args );
		$data = $askfred_api->get_results();
		$this->data = $data[0];
	}

	/**
	 * Generate an MD5 checksum
	 *
	 * @param $data
	 */
	public function create_md5_checksum( $data ) {
		$this->md5_checksum = hash( 'md5', $data );
	}

	/**
	 * Determine if data needs to be reprocessed
	 * Checks equivalency of MD5 hash of current data vs stored md5 hash
	 *
	 * @return bool
	 */
	public function reprocessing_needed() {
		return $this->md5_checksum == hash( 'md5', serialize( $this->data ) );
	}

	/**
	 * Process results from API data
	 *
	 * Registers action to allow object to make all necessary processing updates
	 */
	public function process_results() {
		error_log("reprocessing");
		do_action( 'fence_plus_fencer_process_results', $this->object );
	}
}