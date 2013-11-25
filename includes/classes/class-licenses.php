<?php
/**
 *
 * @package Fence Plus
 * @subpackage Classes
 * @since 0.1
 */

class Fence_Plus_Licenses {

	/**
	 * Get the license key for an extension
	 *
	 * @param $extension_slug string
	 *
	 * @return string|bool
	 */
	public static function get_extension_license_key( $extension_slug ) {
		$licenses = get_option( 'fence_plus_licenses', array() );

		if ( isset( $licenses[$extension_slug] ) )
			return $licenses[$extension_slug]['key'];
		else
			return false;
	}

	/**
	 * Set the license key for an extension
	 *
	 * @param $extension_slug string
	 * @param $key string
	 *
	 * @return bool
	 */
	public static function set_extension_license_key( $extension_slug, $key ) {
		$licenses = get_option( 'fence_plus_licenses', array() );

		if ( isset( $licenses[$extension_slug] ) ) {
			$licenses[$extension_slug]['key'] = $key;
			update_option( 'fence_plus_licenses', $licenses );

			return true;
		}

		return false;
	}

	/**
	 * Get the license status for an extension
	 *
	 * @param $extension_slug string
	 *
	 * @return string|bool active/inactive
	 */
	public static function get_extension_license_status( $extension_slug ) {
		$licenses = get_option( 'fence_plus_licenses', array() );

		if ( isset( $licenses[$extension_slug] ) )
			return $licenses[$extension_slug]['status'];
		else
			return false;
	}

	/**
	 * Set the status for a license.
	 *
	 * @param $extension_slug string
	 * @param $status string
	 *
	 * @return bool
	 */
	public static function set_extension_license_status( $extension_slug, $status ) {
		$licenses = get_option( 'fence_plus_licenses', array() );

		if ( isset( $licenses[$extension_slug] ) ) {
			$licenses[$extension_slug]['status'] = $status;

			update_option( 'fence_plus_licenses', $licenses );

			return true;
		}

		return false;
	}

	/**
	 * Activate an extension.
	 *
	 * @param $extension_slug string
	 *
	 * @return bool
	 */
	public static function activate_extension( $extension_slug ) {
		$license = self::get_extension_license_key( $extension_slug );

		$extensions = apply_filters( 'fence_plus_extensions', array() );

		foreach ( $extensions as $extension ) {
			if ( $extension['slug'] == $extension_slug )
				$extension_name = $extension['name'];
		}

		if ( ! isset( $extension_name ) )
			return false;

		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'item_name'  => urlencode( $extension_name )
		);

		// Call the custom API.
		$response = wp_remote_get( add_query_arg( $api_params, Fence_Plus::STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! isset( $license_data->license ) )
			return false;

		self::set_extension_license_status( $extension_slug, $license_data->license );

		return true;
	}

	/**
	 * Deactivate an extension.
	 *
	 * @param $extension_slug string
	 *
	 * @return bool
	 */
	public static function deactivate_extension( $extension_slug ) {
		$license = self::get_extension_license_key( $extension_slug );

		$extensions = apply_filters( 'fence_plus_extensions', array() );

		foreach ( $extensions as $extension ) {
			if ( $extension['slug'] == $extension_slug )
				$extension_name = $extension['name'];
		}

		if ( ! isset( $extension_name ) )
			return false;

		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_name'  => urlencode( $extension_name )
		);

		// Call the custom API.
		$response = wp_remote_get( add_query_arg( $api_params, Fence_Plus::STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! isset( $license_data->license ) || $license_data->license == 'failed' )
			return false;

		self::set_extension_license_status( $extension_slug, 'inactive' );

		return true;
	}

	/**
	 * Determine if a license is active.
	 *
	 * @param $extension_slug string
	 *
	 * @return bool
	 */
	public static function is_extension_active( $extension_slug ) {
		$status = self::get_extension_license_status( $extension_slug );

		if ( isset( $status ) && $status = 'active' )
			return true;
		else
			return false;

	}

	/**
	 * Check if a license for an extension is valid against the API
	 *
	 * @param $extension_slug string
	 *
	 * @return bool
	 */
	public static function check_extension_license_validity( $extension_slug ) {
		$license = self::get_extension_license_key( $extension_slug );

		$extensions = apply_filters( 'fence_plus_extensions', array() );

		foreach ( $extensions as $extension ) {
			if ( $extension['slug'] == $extension_slug )
				$extension_name = $extension['name'];
		}

		if ( ! isset( $extension_name ) )
			return false;

		$api_params = array(
			'edd_action' => 'check_license',
			'license'    => $license,
			'item_name'  => urlencode( $extension_name )
		);

		// Call the custom API.
		$response = wp_remote_get( add_query_arg( $api_params, Fence_Plus::STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( isset( $license_data->license ) && $license_data->license == 'valid' ) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Update the validity of all licenses.
	 *
	 * @uses Fence_Plus_Licenses::check_extension_license_validity
	 */
	public static function update_license_validity() {
		$licenses = get_option( 'fence_plus_licenses', array() );

		foreach ( $licenses as $extension_slug => $license ) {
			if ( self::check_extension_license_validity( $extension_slug ) )
				self::set_extension_license_status( $extension_slug, 'active' );
			else
				self::set_extension_license_status( $extension_slug, 'inactive' );
		}

	}
}