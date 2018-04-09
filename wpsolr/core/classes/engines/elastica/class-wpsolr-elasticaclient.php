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

	protected $FILE_CONF_TYPE_MAPPING_5 = 'wpsolr_type_mapping_5.0.json';
	protected $FILE_CONF_TYPE_MAPPING_6 = 'wpsolr_type_mapping_6.0.json';

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
	 * Retrieve the mapping file according to the version
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function get_type_mapping_file() {

		$version = $this->get_version();

		return version_compare( $version, '6', '>=' ) ? $this->FILE_CONF_TYPE_MAPPING_6 : $this->FILE_CONF_TYPE_MAPPING_5;
	}

	/**
	 * Retrieve the live Elasticsearch version
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function get_version() {

		$version = $this->search_engine_client->getVersion();

		if ( version_compare( $version, '5', '<' ) ) {
			throw new \Exception( sprintf( 'WPSOLR works only with Elasticsearch >= 5. Your version is %s.', $version ) );
		}

		return $version;
	}

}