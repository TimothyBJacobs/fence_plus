<?php
/**
 * 
 * @package Fence Plus
 * @subpackage
 * @since
 */

abstract class Fence_Plus_Updater
{
	private $md5_checksum;

	public function create_md5_checksum() {

	}

	public function set_md5_checksum( $md5_checksum ) {
		$this->md5_checksum = $md5_checksum;
	}

	public function get_md5_checksum() {
		return $this->md5_checksum;
	}

	public abstract function update();

	public abstract function call_api();

	public abstract function process_results();

	public abstract function set_all_properties();
}