<?php
/**
 * askFRED API class, use to interact with askFRED API
 *
 * @package Fence Plus
 * @subpackage includes
 * @since 0.1
 */

class askFRED_API {
	/**
	 * @var string askFRED api key
	 */
	private $api_key;
	/**
	 * @var array holds the raw results, ie not the headers returned
	 */
	private $results = array();
	/**
	 * @var string holds the current request url
	 */
	private $request_url = "https://api.askfred.net/";
	/**
	 * @var array holds args for askFRED query
	 * @see https://sites.google.com/a/countersix.com/fred-rest-api/request-response-reference
	 */
	private $args;
	/**
	 * @var int current page we are iterating through
	 */
	private $current_page = 0;
	/**
	 * @var int how many results to load per page, max 100
	 */
	private $per_page = 100;
	/**
	 * @var int number of total results the api found
	 */
	private $total_results;

	/**
	 * @param $api_key
	 * @param $args
	 */
	public function __construct( $api_key, $args ) {
		$this->set_api_key( $api_key );
		$this->set_args( $args );
		$this->build_url();
	}

	/**
	 * Get resutls from askFRED api, handles paging of APi
	 *
	 * @uses $this->query
	 * @return array results from api
	 */
	public function get_results() {
		do {
			$this->query( $this->current_page + 1 );
		} while ( $this->total_results > $this->per_page * $this->current_page ); // loop through the paging of the api

		do_action( 'fence_plus_askFRED_api_queried' );

		return $this->results;
	}

	/**
	 * Performs the actual query against the askFRED api
	 *
	 * @uses $this->process_raw_results
	 *
	 */
	private function query( $page ) {
		$json_results = wp_remote_get( $this->request_url . "&_page=" . $page );
		$json_results = wp_remote_retrieve_body( $json_results );

		$this->process_raw_results( json_decode( $json_results, true ) );
	}

	/**
	 * Takes raw data from askFRED api and then
	 * sets the current page and total matched variables
	 * fills $this->results with current batch of data from api
	 *
	 * @param $raw_results array results from askFRED api
	 */
	private function process_raw_results( $raw_results ) {
		$this->current_page = (int) $raw_results['page'];
		$this->total_results = $raw_results['total_matched'];
		$this->results = array_merge( $this->results, array_pop( $raw_results ) ); // grab the actual data from the api request
	}

	/**
	 *
	 */
	private function build_url() {
		$args = apply_filters( 'fence_plus_askFRED_query_args', $this->args );
		$request_url = $this->request_url;
		$request_url .= $args['version'] . "/" . $args['resource'] . "/?_api_key=" . $this->api_key;
		unset( $args['version'] );
		unset( $args['resource'] );

		foreach ( $args as $arg_key => $arg_value ) {
			$request_url .= "&" . $arg_key . "=" . $arg_value;
		}
		$this->request_url = $request_url;
	}

	/**
	 * @param $api_key
	 */
	private function set_api_key( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * @param $args
	 */
	private function set_args( $args ) {
		$this->args = array_merge( array( '_per_page' => 100 ), $args );
	}

}