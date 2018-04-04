<?php

namespace wpsolr\core\classes\engines\solarium\admin;


/**
 * Class WPSOLR_Solr_Admin_Api_Core
 * @package wpsolr\core\classes\engines\solarium\admin
 */
class WPSOLR_Solr_Admin_Api_Core extends WPSOLR_Solr_Admin_Api_Abstract {

	/**
	 * Error messages returned by Solr. Do not change.
	 */
	const ERROR_MESSAGE_CORE_ALREADY_EXISTS = "Core with name '%s' already exists";
	const ERROR_MESSAGE_NOT_SOLRCLOUD_MODE = "Solr instance is not running in SolrCloud mode.";
	const ERROR_MESSAGE_MISSING_CONFIGURATION = 'Could not load configuration from directory';
	const ERROR_MESSAGE_CORE_DOES_NOT_EXISTS = 'Cannot unload non-existent core [%s]';

	/**
	 * Solr/SolrCloud actions
	 */
	const API_CREATE_CORE = '/solr/admin/cores?action=CREATE&name=%s&configSet=%s&wt=json';
	const API_CREATE_COLLECTION = '/solr/admin/collections?action=CREATE&name=%s&collection.configName=%s&numShards=%s&replicationFactor=%s&maxShardsPerNode=%s&wt=json';

	const API_DELETE_CORE = '/solr/admin/cores?action=UNLOAD&core=%s&deleteInstanceDir=true&wt=json';
	const API_DELETE_COLLECTION = '/solr/admin/collections?action=DELETE&name=%s&wt=json';


	const HTML_PRE_CODE_TEMPLATE = '<pre><code><span id="%s">%s</span></code></pre>';

	/**
	 * Create an index Solr
	 */
	public function create_solr_index() {

		// Create a core.
		$this->create_core_or_collection( sprintf( self::API_CREATE_CORE, $this->core, $this->core ) );
	}

	/**
	 * Create an index SolrCloud
	 */
	public function create_solrcloud_index( $conf ) {

		// Create a configset with the collection name if it does not exist yet
		$solr_admin_api_configsets = new WPSOLR_Solr_Admin_Api_ConfigSets( $this->client );
		if ( ! $solr_admin_api_configsets->is_exists_configset() ) {
			$solr_admin_api_configsets->upload_configset();
		}

		// Create the collection/core with it's configset
		$this->create_core_or_collection( sprintf( self::API_CREATE_COLLECTION, $this->core, $this->core,
			$conf['index_solr_cloud_shards'], $conf['index_solr_cloud_replication_factor'], $conf['index_solr_cloud_max_shards_node'] ) );

	}

	/**
	 * Delete an index SolrCloud
	 */
	public function delete_solrcloud_index() {

		// Delete the collection/core
		$this->delete_core_or_collection( self::API_DELETE_COLLECTION );

		// Delete the configset with the collection name if it does exist yet
		$solr_admin_api_configsets = new WPSOLR_Solr_Admin_Api_ConfigSets( $this->client );
		if ( $solr_admin_api_configsets->is_exists_configset() ) {
			$solr_admin_api_configsets->delete_configset();
		}

	}

	/**
	 * Delete an index Solr
	 */
	public function delete_solr_index() {

		// Delete the core.
		$this->delete_core_or_collection( self::API_DELETE_CORE );
	}

	/**
	 * @param string $delete_action
	 */
	protected function delete_core_or_collection( $delete_action ) {

		try {

			// We suppose the configset is ready.
			$this->call_rest_get( sprintf( $delete_action, $this->core ) );

		} catch ( \Exception $e ) {


			if ( strpos( $e->getMessage(), sprintf( self::ERROR_MESSAGE_CORE_DOES_NOT_EXISTS, $this->core ) ) > 0 ) {
				// Core does not exist. Do nothing.
				return;
			}

			// Could not extract the data from error. Send the original error.
			throw $e;
		}

	}

	/**
	 * @param string $create_action
	 */
	protected
	function create_core_or_collection(
		$create_action
	) {

		try {

			// We suppose the configset is ready.
			$this->call_rest_get( $create_action );

		} catch ( \Exception $e ) {

			if ( strpos( $e->getMessage(), sprintf( self::ERROR_MESSAGE_CORE_ALREADY_EXISTS, $this->core ) ) > 0 ) {
				// Core exist already. This error should never be raised.
				throw new \Exception( 'This index already exist.' );
			}

			if ( strpos( $e->getMessage(), self::ERROR_MESSAGE_MISSING_CONFIGURATION ) > 0 ) {
				// Missing congiguration.
				$error = json_decode( $e->getMessage() );

				$message = $error->error->msg;
				preg_match( "/Could not load configuration from directory (.*)/", $message, $output_array );
				if ( isset( $output_array ) && ( 2 === count( $output_array ) ) ) {

					$folder        = $output_array[1]; // The expected folder with the wpsolr conf files
					$parent_folder = dirname( $folder );
					$dir_to_create = basename( $folder );
					$download_link = sprintf( '<a href="%s" target="_new">wpsolr configuration files</a>', 'https://www.wpsolr.com/knowledgebase/where-can-i-download-the-apache-solr-5-6-configuration-files/' );
					$message       = 'You first need to install the wpsolr config files:';
					$message       .= '<ol>';
					$message       .= '<li>Login to your Solr server</li><br/>';

					$message .= sprintf( '<li> Navigate to the folder: <br/><br/>%s</li><br/>',
						sprintf( self::HTML_PRE_CODE_TEMPLATE,
							'wpsolr_cmd_cd',
							sprintf( 'cd %s', $parent_folder )
						)
					);

					$message .= sprintf( '<li> Download the zip file: <br/><br/>%s</li><br/>',
						sprintf( self::HTML_PRE_CODE_TEMPLATE,
							'wpsolr_cmd_curl',
							sprintf( 'curl -L -o %s.zip https://www.dropbox.com/s/5qvzuf4iemokir9/wpsolr-v5.zip?dl=0', $dir_to_create ) // Directory wpsolr_files/5.0.1
						)
					);

					$message .= sprintf( '<li> Create the extraction folder: <br/><br/>%s</li><br/>',
						sprintf( self::HTML_PRE_CODE_TEMPLATE,
							'wpsolr_cmd_mkdir',
							sprintf( 'mkdir -p %s/conf', $dir_to_create )
						)
					);

					$message .= sprintf( '<li> Unzip the conf files: <br/><br/>%s</li><br/>',
						sprintf( self::HTML_PRE_CODE_TEMPLATE,
							'wpsolr_cmd_unzip',
							sprintf( 'unzip %s.zip -d %s/conf', $dir_to_create, $dir_to_create )
						)
					);

					$message .= '<li>Your are ready now. Retry !</li>';
					$message .= '</ol>';

					throw new \Exception( $message );
				}

				// Could not extract the data from error. Send the original error.
				throw $e;
			}

			throw $e;
		}

	}

}