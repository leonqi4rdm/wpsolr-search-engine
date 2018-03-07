<?php

namespace wpsolr\core\classes;

use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\services\WPSOLR_Service_PHP;
use wpsolr\core\classes\services\WPSOLR_Service_WP;
use wpsolr\core\classes\services\WPSOLR_Service_WPSOLR;
use wpsolr\core\classes\ui\WPSOLR_Query;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Regexp;


/**
 * Trait WPSOLR_UnitTestCase_Utils
 * @package wpsolr\core\classes
 */
trait WPSOLR_UnitTestCase_Utils {

	protected $mock_global;

	/* @var array */
	protected $mock_services;

	// Mocked services
	protected $MOCK_SERVICE_WP = 'MOCK_SERVICE_WP';
	protected $MOCK_SERVICE_WPSOLR = 'MOCK_SERVICE_WPSOLR';
	protected $MOCK_SERVICE_WPSOLR_QUERY = 'MOCK_SERVICE_WPSOLR_QUERY';
	protected $MOCK_SERVICE_PHP = 'MOCK_SERVICE_PHP';
	protected $MOCK_SERVICE_OPTION = 'MOCK_SERVICE_OPTION';
	protected $MOCK_SERVICE_GLOBAL = 'MOCK_SERVICE_GLOBAL';


	// Mocked container methods to get services
	protected $MOCK_CONTAINER_METHOD_SERVICE_WP = 'get_service_wp';
	protected $MOCK_CONTAINER_METHOD_SERVICE_PHP = 'get_service_php';
	protected $MOCK_CONTAINER_METHOD_SERVICE_OPTION = 'get_service_option';
	protected $MOCK_CONTAINER_METHOD_SERVICE_WPSOLR = 'get_service_wpsolr';
	protected $MOCK_CONTAINER_METHOD_SERVICE_WPSOLR_QUERY = 'get_service_wpsolr_query';

	// Mock parameters
	protected $MOCK_WILL_RETURN = 'willReturn';
	protected $MOCK_WILL_RETURN_VALUE_MAP = 'willReturnValueMap';
	protected $MOCK_EXPECTS = 'expects';
	protected $MOCK_HOW_MUCH = 'how_much';
	protected $MOCK_METHOD = 'method';
	protected $MOCK_WITH_CONSECUTIVE = 'with_consecutive';
	protected $MOCK_EQUAL_TO = 'equal_to';
	protected $MOCK_WITH = 'with_consecutive'; // Same as MOCK_WITH_CONSECUTIVE, but contains only one array of args rather then several.
	protected $MOCK_HOW_MUCH_ONCE = 'once';
	protected $MOCK_HOW_MUCH_NEVER = 'never';

	/* @var string $sql_statement_to_catch A SQL statement to replace by a bad SQL statement to trigger an error on purpose */
	protected $sql_statement_to_catch;


	/**
	 * @inheritDoc
	 */
	function tearDown() {

		// Insure all global variables are unset after each method call
		unset( $GLOBALS['current_screen'] );


		parent::tearDown();
	}

	/**
	 * @inheritDoc
	 */
	function setUp() {

		$this->mock_services = $this->wpsolr_get_mock_services();

		if ( ! defined( 'WPSOLR_OPTIONS_NO_CACHE' ) ) {
			// Prevent issues with options caching.
			define( 'WPSOLR_OPTIONS_NO_CACHE', true );
		}

		// Init common data
		$this->wpsolr_init();

		parent::setUp();
	}

	/**
	 * Override in children
	 */
	protected function wpsolr_init() {
	}


	/**
	 * Mock services definitions
	 * @return array
	 */
	protected function wpsolr_get_mock_services() {
		return [
			$this->MOCK_SERVICE_WP           => [
				$this->MOCK_METHOD => $this->MOCK_CONTAINER_METHOD_SERVICE_WP,
				'class'            => WPSOLR_Service_WP::class,
			],
			$this->MOCK_SERVICE_WPSOLR       => [
				$this->MOCK_METHOD => $this->MOCK_CONTAINER_METHOD_SERVICE_WPSOLR,
				'class'            => WPSOLR_Service_WPSOLR::class,
			],
			$this->MOCK_SERVICE_WPSOLR_QUERY => [
				$this->MOCK_METHOD => $this->MOCK_CONTAINER_METHOD_SERVICE_WPSOLR_QUERY,
				'class'            => WPSOLR_Query::class,
			],
			$this->MOCK_SERVICE_PHP          => [
				$this->MOCK_METHOD => $this->MOCK_CONTAINER_METHOD_SERVICE_PHP,
				'class'            => WPSOLR_Service_PHP::class,
			],
			$this->MOCK_SERVICE_OPTION       => [
				$this->MOCK_METHOD => $this->MOCK_CONTAINER_METHOD_SERVICE_OPTION,
				'class'            => WPSOLR_Option::class,
			],
		];
	}

	/**
	 * Automatic testing of a class instanciation, for all tests.
	 */
	function test_create() {

		$class_name = $this->wpsolr_get_class_name();
		if ( ! is_null( $class_name ) ) {
			$object = new $class_name();

			self::assertTrue( $object instanceof $class_name );
		}
	}

	/**
	 * Retrieve the class name. TBD in each child.
	 * @return string
	 */
	abstract function wpsolr_get_class_name();

	/**
	 * Retrieve the option name. TBD in each child.
	 * @return string
	 */
	function wpsolr_get_option_name() {
		return '';
	}

	/**
	 * Verify that a table, even a temporary table exist.
	 * Just select it, and check no error occurred.
	 * Temporary tables are not visible with classic catalog browsing.
	 *
	 * @return bool
	 */
	function wpsolr_check_table_exists() {
		global $wpdb;

		// Silent errors on select on non existent tables
		$wpdb->suppress_errors();

		$wpdb->query( "SELECT * FROM {$wpdb->prefix}wpsolr_permalinks LIMIT 1" );

		return empty( $wpdb->last_error );
	}

	/**
	 * Prepare container services for injection
	 */
	function wpsolr_mock_services( $object_tested, array $service_methods ) {

		$results = [];

		// For each service, get each container method that returns the service.
		$container_methods = [];
		foreach ( $service_methods as $service_name => $service_configs ) {
			$container_methods[] = $this->mock_services[ $service_name ][ $this->MOCK_METHOD ];
		}

		// Mock container service, with methods to get other mock services
		$mock_global = $this->getMockBuilder( WPSOLR_Service_Container::class )
		                    ->setMethods( $container_methods )
		                    ->getMock();

		// Add the mock container to the results
		$results[ $this->MOCK_SERVICE_GLOBAL ] = $mock_global;

		foreach ( $service_methods as $service_name => $service_configs ) {

			// Create the mock service
			$methods      = array_column( $service_configs, $this->MOCK_METHOD );
			$mock_service = $this->getMockBuilder( $this->mock_services[ $service_name ]['class'] )
			                     ->setMethods( $methods )
			                     ->getMock();

			// Add methods to the mock service
			foreach ( $service_configs as $service_config ) {

				if ( ! empty( $service_config[ $this->MOCK_WILL_RETURN ] ) ) {
					$mock_service->method( $service_config[ $this->MOCK_METHOD ] )->willReturn( $service_config[ $this->MOCK_WILL_RETURN ] );
				}

				if ( ! empty( $service_config[ $this->MOCK_WILL_RETURN_VALUE_MAP ] ) ) {
					$mock_service->method( $service_config[ $this->MOCK_METHOD ] )->will( $this->returnValueMap( $service_config[ $this->MOCK_WILL_RETURN_VALUE_MAP ] ) );
				}

				if ( ! empty( $service_config[ $this->MOCK_EXPECTS ] ) ) {

					if ( ! empty( $service_config[ $this->MOCK_EXPECTS ][ $this->MOCK_HOW_MUCH ] ) ) {

						if ( is_int( $service_config[ $this->MOCK_EXPECTS ][ $this->MOCK_HOW_MUCH ] ) ) {
							$method = $mock_service->expects( $this->exactly( $service_config[ $this->MOCK_EXPECTS ][ $this->MOCK_HOW_MUCH ] ) )->method( $service_config[ $this->MOCK_METHOD ] );
						} else {
							switch ( $service_config[ $this->MOCK_EXPECTS ][ $this->MOCK_HOW_MUCH ] ) {
								case $this->MOCK_HOW_MUCH_ONCE:
									$method = $mock_service->expects( self::once() )->method( $service_config[ $this->MOCK_METHOD ] );
									break;

								case $this->MOCK_HOW_MUCH_NEVER:
									$method = $mock_service->expects( self::never() )->method( $service_config[ $this->MOCK_METHOD ] );
									break;

								default:
									throw new \Exception( sprintf( 'Parameter "how_much" has value "%s" unknown.', $service_config[ $this->MOCK_EXPECTS ][ $this->MOCK_HOW_MUCH ] ) );
							}
						}

					}

					if ( ! empty( $service_config[ $this->MOCK_EXPECTS ][ $this->MOCK_WITH_CONSECUTIVE ] ) ) {

						$with_consecutive = [];

						foreach ( $service_config[ $this->MOCK_EXPECTS ][ $this->MOCK_WITH_CONSECUTIVE ] as $args ) {
							$with_consecutive_current = [];
							foreach ( $args as $arg ) {
								foreach ( $arg as $operator => $value ) {
									switch ( $operator ) {
										case $this->MOCK_EQUAL_TO:
											$with_consecutive_current[] = $this->equalTo( $value );
											break;

										default:
											throw new \Exception( sprintf( 'Operator "%s" is unknow.', $operator ) );
											break;
									}

								}
							}
							$with_consecutive[] = $with_consecutive_current;
						}

						call_user_func_array( [ $method, 'withConsecutive' ], $with_consecutive );
					}

				}

			}

			// Add method to the mock container to get the mock service
			$mock_global->method( $this->mock_services[ $service_name ][ $this->MOCK_METHOD ] )->willReturn( $mock_service );

			// Add the mock service to the results
			$results[ $service_name ] = $mock_service;

			// Set the container to the tested object
			$object_tested->set_container( $mock_global );

		}

		return $results;
	}

	/**
	 * @return int User id
	 */
	function wpsolr_create_user_id() {
		return $this->factory->user->create( [ 'role' => 'administrator' ] );
	}

	/**
	 * Set the current user
	 *
	 * @param $user_id
	 *
	 * @return \WP_User
	 */
	function wpsolr_set_current_user( $user_id ) {
		return wp_set_current_user( $user_id );
	}

	/**
	 * Login a new user
	 *
	 * @return \WP_User
	 */
	function wpsolr_log_in() {
		return wp_set_current_user( $this->wpsolr_create_user_id() );
	}

	function wpsolr_set_is_admin() {
		// Manage to get is_admin() true
		set_current_screen( 'solr_settings' );
	}

	/**
	 * Trigger a sql error for a specific SQL statement
	 *
	 * @param string $sql_statement_to_catch
	 */
	function wpsolr_trigger_sql_error( $sql_statement_to_catch ) {

		$this->sql_statement_to_catch = $sql_statement_to_catch;

		// Provoke a SQL error on the drop table SQL execution
		add_filter( 'query', function ( $query ) {

			// For table statements (drop, create), phpunit will add 'TEMPORARY': remove it.
			if ( str_replace( ' TEMPORARY ', ' ', $query ) === $this->sql_statement_to_catch ) {
				// Our sql: replace it by an obvious bad sql
				return 'wpsolr unit test: not a sql query to trigger an error';

			} else {
				// Let other sql in peace
				return $query;
			}
		} );

	}

	/**
	 * Assert the file include filter is working fine.
	 *
	 * @param $help_id
	 * @param $file_name_expected
	 */
	function wpsolr_assert_filter_include( $help_id, $file_name_expected ) {

		$file_name = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, $help_id );
		$this->assertEquals( $file_name_expected, WPSOLR_Regexp::extract_last_separator( $file_name, '/' ) );
		$this->assertTrue( file_exists( $file_name ) );
	}


}
