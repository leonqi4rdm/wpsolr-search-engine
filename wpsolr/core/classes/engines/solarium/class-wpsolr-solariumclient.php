<?php

namespace wpsolr\core\classes\engines\solarium;

/**
 * Some common methods of the Solr client.
 * @property \Solarium\Client $search_engine_client
 */
trait WPSOLR_SolariumClient {


	/**
	 * Execute an update query with the client.
	 *
	 * @param \Solarium\Client $search_engine_client
	 * @param \Solarium\Core\Query\QueryInterface $update_query
	 *
	 * @return WPSOLR_ResultsSolariumClient
	 */
	public function search_engine_client_execute( $search_engine_client, $update_query ) {

		$this->search_engine_client_pre_execute();

		return new WPSOLR_ResultsSolariumClient( $search_engine_client->execute( $update_query ) );
	}

	/**
	 * Prepare query execute
	 */
	abstract public function search_engine_client_pre_execute();


	/**
	 * @param $config
	 *
	 * @return \Solarium\Client
	 */
	protected function create_search_engine_client( $config ) {

		$solarium_config = [
			'endpoint' => [
				'localhost1' => [
					'scheme'   => $config['scheme'],
					'host'     => $config['host'],
					'port'     => $config['port'],
					'path'     => $config['path'],
					'username' => $config['username'],
					'password' => $config['password'],
					'timeout'  => $config['timeout'],
				],
			],
		];

		return new \Solarium\Client( $solarium_config );
	}

}