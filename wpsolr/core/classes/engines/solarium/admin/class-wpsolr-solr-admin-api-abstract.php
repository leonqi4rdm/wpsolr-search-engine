<?php

namespace wpsolr\core\classes\engines\solarium\admin;

use wpsolr\core\classes\utilities\WPSOLR_Regexp;


/**
 * Class WPSOLR_Solr_Admin_Api_Abstract
 * @package wpsolr\core\classes\engines\solarium\admin
 */
abstract class WPSOLR_Solr_Admin_Api_Abstract {

	/**
	 * @var \Solarium\Client
	 */
	protected $client;

	/** @var string */
	protected $core;

	/**
	 * WPSOLR_Solr_Admin_Api_Abstract constructor.
	 *
	 * @param \Solarium\Client $client
	 */
	public function __construct( \Solarium\Client $client ) {
		$this->client = $client;
		$this->core   = $this->extract_core_from_path( $this->client->getEndpoint()->getPath() );
	}

	/**
	 * @param string $path_core '/solr/core'
	 *
	 * @return string 'core'
	 */
	protected function extract_core_from_path( $path_core ) {

		$result = WPSOLR_Regexp::extract_last_separator( $path_core, '/' );

		return $result;
	}

	/**
	 * @return string
	 */
	protected function get_endpoint_path() {
		$endpoint = $this->client->getEndpoint();

		$result = sprintf( '%s://%s:%s', $endpoint->getScheme(), $endpoint->getHost(), $endpoint->getPort() );

		return $result;
	}

	/**
	 * @param $full_path
	 *
	 * @args array $args
	 *
	 * @return array|mixed|object
	 */
	protected function call_rest_request( $path, $args ) {

		$full_path = sprintf( '%s%s', $this->get_endpoint_path(), $path );

		$default_args = [
			'timeout' => 60,
			'verify'  => true,
			'headers' => [ 'Content-Type' => 'application/json' ],
		];

		$response = wp_remote_request(
			$full_path,
			array_merge( $default_args, $args )
		);

		if ( is_wp_error( $response ) ) {
			throw new \Exception( $response->get_error_message() );
		}

		if ( 200 !== $response['response']['code'] ) {
			throw new \Exception( $response['body'], $response['response']['code'] );
		}

		return json_decode( $response['body'] );
	}

	/**
	 * @param $path
	 * @param array $data
	 *
	 * @return array|mixed|object
	 */
	protected function call_rest_post( $path, $data = [] ) {

		$args = [
			'method' => 'POST',
			'body'   => wp_json_encode( $data ),
		];

		return $this->call_rest_request( $path, $args );
	}

	/**
	 * @param $path
	 * @param string $data
	 *
	 * @return array|mixed|object
	 */
	protected function call_rest_upload( $path, $data ) {

		$args = [
			'method'  => 'POST',
			'headers' => [
				'accept'       => 'application/json', // The API returns JSON
				'content-type' => 'application/binary', // Set content type to binary
			],
			'body'    => $data
		];

		return $this->call_rest_request( $path, $args );
	}

	/**
	 * Generic REST calls
	 *
	 * @param $path
	 *
	 * @return array|mixed|object
	 */
	protected function call_rest_get( $path ) {

		$args = [
			'method' => 'GET',
		];

		return $this->call_rest_request( $path, $args );
	}

	/**
	 * @param $path
	 *
	 * @return array|mixed|object
	 */
	protected function call_rest_delete( $path ) {

		$args = [
			'method' => 'DELETE',
		];

		return $this->call_rest_request( $path, $args );
	}


}