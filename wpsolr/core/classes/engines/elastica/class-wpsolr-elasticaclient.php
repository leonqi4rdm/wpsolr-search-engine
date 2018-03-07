<?php

namespace wpsolr\core\classes\engines\elastica;

/**
 * Some common methods of the Elastica client.
 *
 * @property \Elastica\Client $search_engine_client
 * @property \Elastica\Index $elastica_index
 */
trait WPSOLR_ElasticaClient {

	protected $wpsolr_type = 'wpsolr_types';

	// Unique id to store attached decoded files.
	protected $WPSOLR_DOC_ID_ATTACHMENT = 'wpsolr_doc_id_attachment';

	protected $elastica_index;

	protected $ELASTICA_MAPPING_FIELD_PROPERTIES_INGEST_ATTACHMENT = 'properties_ingest_attachment';
	protected $FILE_CONF_TYPE_MAPPING = 'wpsolr_type_mapping_5.0.json';

	/**
	 * @return \Elastica\Index
	 */
	public function get_elastica_index() {
		return $this->elastica_index;
	}

	/**
	 * @param \Elastica\Index $index
	 */
	public function set_elastica_index( $index ) {
		$this->elastica_index = $index;
	}

	/**
	 * @return \Elastica\Type
	 */
	public function get_elastica_type( $type = '' ) {
		return $this->elastica_index->getType( ( empty( $type ) ) ? $this->wpsolr_type : $type );
	}

	/**
	 * @return \Elastica\Type
	 */
	public function get_elastica_type_attachment() {
		return $this->get_elastica_type( 'wpsolr_type_ingest_attachment' );
	}


	/**
	 * @param $config
	 *
	 * @return \Elastica\Client
	 */
	protected function create_search_engine_client( $config ) {

		$elastica_config = empty( $config ) ? [] :
			[
				'transport' => $config['scheme'],
				'host'      => $config['host'],
				'port'      => $config['port'],
				'username'  => $config['username'],
				'password'  => $config['password'],
				'timeout'   => $config['timeout'],
			];

		$client = new \Elastica\Client( $elastica_config );

		$this->set_elastica_index( $client->getIndex( empty( $config ) ? '' : $config['index_label'] ) );

		return $client;
	}

	/**
	 * Load the content of a conf file.
	 *
	 * @param string $file
	 *
	 * @return array
	 */
	protected function get_and_decode_configuration_file( $file ) {

		$file_json = file_get_contents( plugin_dir_path( __FILE__ ) . $file );

		return json_decode( $file_json, true );
	}

	/**
	 * Add/replace an attachment mapping to the index.
	 *
	 * @param array $mapping_types
	 */
	protected function index_attachment_mapping( $mapping_types = [] ) {

		if ( empty( $mapping_types ) ) {
			$mapping_types = $this->get_and_decode_configuration_file( $this->FILE_CONF_TYPE_MAPPING );
		}

		$mapping = new \Elastica\Type\Mapping();
		$mapping->setType( $this->get_elastica_type_attachment() );

		// Set properties for field types and analysers
		$mapping->setProperties(
			$mapping_types[ $this->ELASTICA_MAPPING_FIELD_PROPERTIES_INGEST_ATTACHMENT ]
		);

		// Send mapping to type
		$response = $mapping->send();
	}

}