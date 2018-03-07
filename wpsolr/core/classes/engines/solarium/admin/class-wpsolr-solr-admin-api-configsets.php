<?php

namespace wpsolr\core\classes\engines\solarium\admin;


/**
 * Class WPSOLR_Solr_Admin_Api_ConfigSets
 * @package wpsolr\core\classes\engines\solarium\admin
 */
class WPSOLR_Solr_Admin_Api_ConfigSets extends WPSOLR_Solr_Admin_Api_Abstract {

	/**
	 * Error messages returned by Solr. Do not change.
	 */
	const ERROR_MESSAGE_CORE_ALREADY_EXISTS = "Core with name '%s' already exists";
	const ERROR_MESSAGE_NOT_SOLRCLOUD_MODE = "Solr instance is not running in SolrCloud mode.";

	/**
	 * SolrCloud actions
	 */
	const API_CONFIGSETS_UPLOAD = '/solr/admin/configs?action=UPLOAD&name=%s';
	const API_CONFIGSETS_LIST = '/solr/admin/configs?action=LIST&wt=json';
	const API_CONFIGSETS_DELETE = '/solr/admin/configs?action=DELETE&name=%s&wt=json';

	/**
	 * Returns the configuration file path (zip)
	 * @return string
	 */
	static function get_config_file_path() {
		return plugin_dir_path( __FILE__ ) . 'conf/5.0/wpsolr-v5.zip';
	}

	/**
	 * Returns the configuration file url for download (zip)
	 * @return string
	 */
	static function get_config_file_url() {
		return plugin_dir_url( __FILE__ ) . 'conf/5.0/wpsolr-v5.zip';
	}

	/**
	 * Upload configset
	 */
	public function upload_configset() {

		try {

			// Retrieve the configset file path
			$file = self::get_config_file_path();


			$file_data = file_get_contents( $file );

			// Upload the confisets files.
			$result = $this->call_rest_upload( sprintf( self::API_CONFIGSETS_UPLOAD, $this->core ), $file_data );

		} catch ( \Exception $e ) {

			throw $e;
		}

	}

	/**
	 * Delete a configset
	 */
	public function delete_configset() {

		try {

			// Retrieve the configset file path
			$file = self::get_config_file_path();


			$file_data = file_get_contents( $file );

			// Delete the confisets files.
			$result = $this->call_rest_get( sprintf( self::API_CONFIGSETS_DELETE, $this->core ) );

		} catch ( \Exception $e ) {

			throw $e;
		}

	}

	/**
	 * Does a configset already exist ?
	 *
	 * @param string $configset
	 *
	 * @return bool
	 */
	public function is_exists_configset() {

		// List of all configsets
		$result = $this->call_rest_get( self::API_CONFIGSETS_LIST );

		if ( isset( $result ) && ! empty( $result->configSets ) && in_array( $this->core, $result->configSets, true ) ) {
			return true;
		}

		return false;
	}
}