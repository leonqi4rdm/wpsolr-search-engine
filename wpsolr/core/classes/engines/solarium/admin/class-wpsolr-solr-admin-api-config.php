<?php

namespace wpsolr\core\classes\engines\solarium\admin;


/**
 * Class WPSOLR_Solr_Admin_Api_Config
 * @package wpsolr\core\classes\engines\solarium\admin
 */
class WPSOLR_Solr_Admin_Api_Config extends WPSOLR_Solr_Admin_Api_Abstract {

	/**
	 * Error messages returned by Solr. Do not change.
	 */
	const ERROR_MESSAGE_CORE_ALREADY_EXISTS = "Core with name '%s' already exists";
	const ERROR_MESSAGE_NOT_SOLRCLOUD_MODE = "Solr instance is not running in SolrCloud mode.";

	/**
	 * Solr/SolrCloud actions
	 */
	const API_CONFIG_UPDATE = '/solr/%s/config';


	/**
	 * Update solrconfig
	 */
	public function update_config() {

		try {

			// Retrieve the delta solrconfig setings
			$file_json = file_get_contents( plugin_dir_path( __FILE__ ) . '/conf/wpsolr_solrconfig_5.0.json' );
			$configs   = json_decode( $file_json, true );

			foreach ( $configs as $config ) {
				// Update each solrconfig settings.
				$result = $this->call_rest_post( sprintf( self::API_CONFIG_UPDATE, $this->core ), $config );

				if ( ! empty( $result->errorMessages ) && ! empty( $result->errorMessages[0]->errorMessages ) ) {
					throw new \Exception( sprintf( 'WPSOLR: Error while updating the index configuration: %s', $result->errorMessages[0]->errorMessages[0] ) );
				}
			}

		} catch ( \Exception $e ) {

			throw $e;
		}

	}

}