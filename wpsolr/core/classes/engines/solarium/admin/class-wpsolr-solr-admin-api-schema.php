<?php

namespace wpsolr\core\classes\engines\solarium\admin;


/**
 * Class WPSOLR_Solr_Admin_Api_Schema
 * @package wpsolr\core\classes\engines\solarium\admin
 */
class WPSOLR_Solr_Admin_Api_Schema extends WPSOLR_Solr_Admin_Api_Abstract {

	/**
	 * Solr/SolrCloud actions
	 */
	const API_SCHEMA_UPDATE = '/solr/%s/schema';


	/**
	 * Update solrconfig
	 */
	public function update_schema() {

		try {

			// Retrieve the delta solrconfig setings
			$file_json = file_get_contents( plugin_dir_path( __FILE__ ) . '/conf/wpsolr_schema_5.0.json' );
			$file_json_delete = str_replace('add-', 'delete-', $file_json);

			$configs   = json_decode( $file_json, true );
			$result = $this->call_rest_post( sprintf( self::API_SCHEMA_UPDATE, $this->core ), $configs );

			if ( ! empty( $result->errors ) && ! empty( $result->errors[0]->errorMessages ) ) {
				throw new \Exception( sprintf( 'WPSOLR: Error while updating the schema configuration: %s', $result->errors[0]->errorMessages[0] ) );
			}

		} catch ( \Exception $e ) {

			throw $e;
		}

	}

}