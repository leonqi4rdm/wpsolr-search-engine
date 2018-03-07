<?php

namespace wpsolr\core\classes\extensions\managed_solr_servers;

use wpsolr\core\classes\engines\WPSOLR_AbstractSearchClient;
use wpsolr\core\classes\extensions\WpSolrExtensions;
use wpsolr\core\classes\WPSOLR_Events;

/**
 * Class OptionGotosolr
 *
 * Manage Gotosolr hosting options
 */
class OptionManagedSolrServer extends WpSolrExtensions {

	private $_options;

	private $_api_path;

	private $_managed_solr_service;

	private $_managed_solr_service_id;

	/*
	 * REST api paths
	 *
	 */
	const PATH_USERS_SIGNIN = '/users/signin';
	const PATH_LIST_ACCOUNTS = '/accounts';
	const PATH_LIST_INDEXES = '/accounts/%s/indexes';

	// Rest api orders channel property
	const MANAGED_SOLR_SERVICE_CHANNEL_ORDER_URL = 'MANAGED_SOLR_SERVICE_CHANNEL_ORDER_URL';
	const MANAGED_SOLR_SERVICE_CHANNEL_GOOGLE_RECAPTCHA_TOKEN_URL = 'MANAGED_SOLR_SERVICE_CHANNEL_GOOGLE_RECAPTCHA_TOKEN_URL';
	const MANAGED_SOLR_SERVICE_LABEL = 'MANAGED_SOLR_SERVICE_LABEL';
	const MANAGED_SOLR_SERVICE_HOME_PAGE = 'MANAGED_SOLR_SERVICE_HOME_PAGE';
	const MANAGED_SOLR_SERVICE_API_PATH = 'MANAGED_SOLR_SERVICE_API_PATH';
	const MANAGED_SOLR_SERVICE_ORDERS_URLS = 'MANAGED_SOLR_SERVICE_ORDERS_URLS';
	const MANAGED_SOLR_SERVICE_ORDER_URL_BUTTON_LABEL = 'MANAGED_SOLR_SERVICE_ORDER_URL_BUTTON_LABEL';
	const MANAGED_SOLR_SERVICE_ORDER_URL_TEXT = 'MANAGED_SOLR_SERVICE_ORDER_URL_TEXT';
	const MANAGED_SOLR_SERVICE_ORDER_URL_LINK = 'MANAGED_SOLR_SERVICE_ORDER_URL_LINK';
	const MANAGED_SOLR_SERVICE_SEARCH_ENGINE = 'MANAGED_SOLR_SERVICE_SEARCH_ENGINE';

	// Order link parameter indicating the current temporary index core to buy
	const AVANGATE_ORDER_PARAMETER_ADDITIONAL_SOLR_INDEX_CORE = 'ADDITIONAL_SOLR_INDEX_CORE';

	/*
	 * Constructor
	 *
	 * Subscribe to actions
	 */

	function __construct( $managed_solr_service_id = null ) {

		if ( isset( $managed_solr_service_id ) ) {

			$this->_managed_solr_service_id = $managed_solr_service_id;

			//$this->set_service_option('token', '');

			$this->_options = self::get_option_data( self::OPTION_MANAGED_SOLR_SERVERS, null );

			$this->_managed_solr_service = $this->get_managed_solr_service();

			$this->_api_path = $this->_managed_solr_service[ self::MANAGED_SOLR_SERVICE_API_PATH ];
		}

	}

	/**
	 * @param $full_path
	 *
	 * @args array $args
	 *
	 * @return array|mixed|object
	 */
	public function call_rest_request( $full_path, $args ) {

		$default_args = array(
			'timeout' => 60,
			'verify'  => true,
			'headers' => array( 'Content-Type' => 'application/json' ),
		);

		$response = wp_remote_request(
			$full_path,
			array_merge( $default_args, $args )
		);

		if ( is_wp_error( $response ) ) {

			return (object) array(
				'status' => (object) array(
					'state'   => 'ERROR',
					'message' => $response->get_error_message(),
				),
			);
		}

		if ( 200 !== $response['response']['code'] ) {
			return (object) array( 'status' => (object) array( 'state' => 'ERROR', 'message' => $response['body'] ) );
		}

		return json_decode( $response['body'] );
	}


	/**
	 * @param $path
	 * @param array $data
	 *
	 * @return array|mixed|object
	 */
	public function call_rest_post( $path, $data = array() ) {

		$full_path = ( 'http' === substr( $path, 0, 4 ) ) ? $path : $this->_api_path . $path;

		$args = array(
			'method' => 'POST',
			'body'   => wp_json_encode( $data ),
		);

		return $this->call_rest_request( $full_path, $args );
	}

	/**
	 * Generic REST calls
	 *
	 * @param $path
	 *
	 * @return array|mixed|object
	 */
	public function call_rest_get( $path ) {

		$full_path = ( 'http' === substr( $path, 0, 4 ) ) ? $path : $this->_api_path . $path . '&access_token=' . $this->get_service_option( 'token' );

		$args = array(
			'method' => 'GET',
		);

		return $this->call_rest_request( $full_path, $args );
	}

	/**
	 * @param $path
	 *
	 * @return array|mixed|object
	 */
	public function call_rest_delete( $path ) {

		$full_path = ( 'http' === substr( $path, 0, 4 ) ) ? $path : $this->_api_path . $path;

		$args = array(
			'method' => 'DELETE',
		);

		return $this->call_rest_request( $full_path, $args );
	}

	public static function is_response_ok( $response_object ) {

		return ( 'OK' === $response_object->status->state );

	}

	public static function get_response_results( $response_object ) {

		return $response_object->results[0];
	}

	public static function get_response_error_message( $response_object ) {

		return htmlentities( $response_object->status->message );
	}

	public static function get_response_result( $response_object, $field ) {

		return isset( $response_object->results ) && isset( $response_object->results[0] )
			? is_array( $response_object->results[0] ) ? $response_object->results[0][0]->$field : $response_object->results[0]->$field
			: null;
	}

	/*
	 * Api REST calls
	 */
	public function call_rest_signin( $email, $password ) {

		$response_object = $this->call_rest_post(
			self::PATH_USERS_SIGNIN,
			array(
				'email'    => $email,
				'password' => $password,
			)
		);

		return $response_object;
	}

	public function call_rest_create_google_recaptcha_token() {

		$managed_solr_service = $this->get_managed_solr_service();

		$response_object = $this->call_rest_post(
			$managed_solr_service[ self::MANAGED_SOLR_SERVICE_CHANNEL_GOOGLE_RECAPTCHA_TOKEN_URL ]
		);

		return $response_object;
	}

	public function call_rest_create_solr_index( $g_recaptcha_response ) {

		$managed_solr_service = $this->get_managed_solr_service();

		$response_object = $this->call_rest_post(
			$managed_solr_service[ self::MANAGED_SOLR_SERVICE_CHANNEL_ORDER_URL ],
			array(
				'response' => $g_recaptcha_response,
				'remoteip' => $_SERVER['REMOTE_ADDR']
			)
		);

		return $response_object;
	}

	public function call_rest_activate_license( $url, $matching_license, $subscription_number ) {

		$response_object = $this->call_rest_post(
			$url,
			array(
				'matchingLicense'  => $matching_license,
				'subscriptionUuid' => $subscription_number,
				'siteUrl'          => home_url(),
				'version'          => WPSOLR_PLUGIN_VERSION,
			)
		);

		return $response_object;
	}

	public function call_rest_deactivate_license( $url, $license_activation_uuid ) {

		$response_object = $this->call_rest_delete(
			$url . '/' . $license_activation_uuid
		);

		return $response_object;
	}


	public function call_rest_verify_license( $url, $license_activation_uuid ) {

		$response_object = $this->call_rest_get(
			$url . '/' . $license_activation_uuid
		);

		return $response_object;
	}

	public function call_rest_get_temporary_solr_index_status( $solr_core ) {

		$managed_solr_service = $this->get_managed_solr_service();

		$response_object = $this->call_rest_get(
			sprintf( '%s/solr-cores/%s', $managed_solr_service[ self::MANAGED_SOLR_SERVICE_CHANNEL_ORDER_URL ], $solr_core )
		);

		return $response_object;
	}

	public function call_rest_list_accounts() {

		$response_object = $this->call_rest_get(
			sprintf( '%s?query=&orderBy=asc&start=1&limit=20', self::PATH_LIST_ACCOUNTS )
		);

		return $response_object;
	}

	public function call_rest_account_indexes( $account_uuid ) {

		$response_object = $this->call_rest_get(
			sprintf( '%s?query=&orderBy=asc&start=1&limit=20', sprintf( self::PATH_LIST_INDEXES, $account_uuid ) )
		);

		return $response_object;
	}

	/**
	 * Get a service option
	 *
	 * @return bool
	 */
	public function get_service_option( $option_name ) {

		$service_options = $this->get_service_options();

		return ( isset( $service_options ) && isset( $service_options[ $option_name ] ) ) ? $service_options[ $option_name ] : '';
	}

	/**
	 * Set a service option
	 *
	 * @return bool
	 */
	public function set_service_option( $option_name, $option_value ) {

		$options = isset( $this->_options ) ? $this->_options : array();

		$options[ $this->_managed_solr_service_id ][ $option_name ] = $option_value;

		// Save options
		$this->set_option_data( self::OPTION_MANAGED_SOLR_SERVERS, $options );

		// Refresh the options after save
		$this->_options = self::get_option_data( self::OPTION_MANAGED_SOLR_SERVERS, null );

	}

	/**
	 * Get the options stored for a managed Solr service
	 *
	 * @return option
	 */
	private function get_service_options() {

		return isset( $this->_options[ $this->_managed_solr_service_id ] ) ? $this->_options[ $this->_managed_solr_service_id ] : null;
	}

	/**
	 * Get all managed Solr services data
	 *
	 * @return array
	 */
	public static function get_managed_solr_services() {

		$result = [];

		$result['elastic'] = [
			self::MANAGED_SOLR_SERVICE_SEARCH_ENGINE                      => WPSOLR_AbstractSearchClient::ENGINE_ELASTICSEARCH,
			self::MANAGED_SOLR_SERVICE_LABEL                              => 'Elasticsearch',
			self::MANAGED_SOLR_SERVICE_HOME_PAGE                          => 'http://www.gotosolr.com/en',
			self::MANAGED_SOLR_SERVICE_API_PATH                           => 'https://api.gotosolr.com/v1/partners/24b7729e-02dc-47d1-9c15-f1310098f93f',
			self::MANAGED_SOLR_SERVICE_CHANNEL_ORDER_URL                  => 'https://api.gotosolr.com/v1/providers/8c25d2d6-54ae-4ff6-a478-e2c03f1e08a4/accounts/24b7729e-02dc-47d1-9c15-f1310098f93f/addons/71bc4b32-be17-4012-b4e9-299d47d7f86d/order-solr-index/eafe62fb-1ee1-4a64-ac33-b2b7b045feb0',
			self::MANAGED_SOLR_SERVICE_CHANNEL_GOOGLE_RECAPTCHA_TOKEN_URL => 'https://api.gotosolr.com/v1/providers/8c25d2d6-54ae-4ff6-a478-e2c03f1e08a4/accounts/24b7729e-02dc-47d1-9c15-f1310098f93f/addons/71bc4b32-be17-4012-b4e9-299d47d7f86d/google-recaptcha-token',
			self::MANAGED_SOLR_SERVICE_ORDERS_URLS                        => [],
		];

		$result['gotosolr'] = [
			self::MANAGED_SOLR_SERVICE_SEARCH_ENGINE                      => WPSOLR_AbstractSearchClient::ENGINE_SOLR,
			self::MANAGED_SOLR_SERVICE_LABEL                              => 'Solr',
			self::MANAGED_SOLR_SERVICE_HOME_PAGE                          => 'http://www.gotosolr.com/en',
			self::MANAGED_SOLR_SERVICE_API_PATH                           => 'https://api.gotosolr.com/v1/partners/24b7729e-02dc-47d1-9c15-f1310098f93f',
			self::MANAGED_SOLR_SERVICE_CHANNEL_ORDER_URL                  => 'https://api.gotosolr.com/v1/providers/8c25d2d6-54ae-4ff6-a478-e2c03f1e08a4/accounts/24b7729e-02dc-47d1-9c15-f1310098f93f/addons/f8622320-5a3b-48cf-a331-f52459c46573/order-solr-index/8037888b-501a-4200-9fb0-b4266434b161',
			self::MANAGED_SOLR_SERVICE_CHANNEL_GOOGLE_RECAPTCHA_TOKEN_URL => 'https://api.gotosolr.com/v1/providers/8c25d2d6-54ae-4ff6-a478-e2c03f1e08a4/accounts/24b7729e-02dc-47d1-9c15-f1310098f93f/addons/f8622320-5a3b-48cf-a331-f52459c46573/google-recaptcha-token',
			self::MANAGED_SOLR_SERVICE_ORDERS_URLS                        => [
				[
					self::MANAGED_SOLR_SERVICE_ORDER_URL_BUTTON_LABEL => 'Extend the trial with a Monthly Plan',
					self::MANAGED_SOLR_SERVICE_ORDER_URL_TEXT         => 'Monthly plan',
					self::MANAGED_SOLR_SERVICE_ORDER_URL_LINK         => 'https://secure.avangate.com/order/checkout.php?PRODS=4653966&QTY=1&CART=1&CARD=1',
				],
			],
		];

		$result = apply_filters( WPSOLR_Events::WPSOLR_FILTER_ENV_MANAGED_SERVICES, $result );

		return $result;
	}


	public function get_managed_solr_service() {
		$managed_services = $this->get_managed_solr_services();

		return $managed_services[ $this->_managed_solr_service_id ];
	}

	public function get_id() {

		return $this->_managed_solr_service_id;
	}

	/**
	 * @return string
	 */
	public function get_search_engine() {
		$managed_service = $this->get_managed_solr_service();

		return $managed_service[ self::MANAGED_SOLR_SERVICE_SEARCH_ENGINE ];
	}

	public function get_label() {
		$managed_service = $this->get_managed_solr_service();

		return $managed_service[ self::MANAGED_SOLR_SERVICE_LABEL ];
	}

	public function get_orders_urls() {
		$managed_service = $this->get_managed_solr_service();

		return $managed_service[ self::MANAGED_SOLR_SERVICE_ORDERS_URLS ];
	}


	/**
	 * Set the solr core parameter in the order link urls
	 *
	 * @param $index_solr_core
	 *
	 * @return mixed
	 */
	public function generate_convert_orders_urls( $index_solr_core ) {

		// Clone array
		$generated_orders_urls = $this->get_orders_urls();

		foreach ( $generated_orders_urls as &$generated_order_url ) {

			// Add the ADDITIONAL_SOLR_INDEX_CORE parameters to the order url
			$generated_order_url[ self::MANAGED_SOLR_SERVICE_ORDER_URL_LINK ] .= sprintf(
				'&%s=%s',
				self::AVANGATE_ORDER_PARAMETER_ADDITIONAL_SOLR_INDEX_CORE,
				$index_solr_core );

		}

		return $generated_orders_urls;
	}
}