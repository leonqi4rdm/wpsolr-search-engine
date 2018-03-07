<?php

namespace wpsolr\core\classes\services;

/**
 * Replace class WP_Query by the child class WPSOLR_query
 * Action called at the end of wp-settings.php, before $wp_query is processed
 */
use wpsolr\core\classes\engines\solarium\WPSOLR_SearchSolariumClient;
use wpsolr\core\classes\ui\WPSOLR_Query;
use wpsolr\core\classes\ui\WPSOLR_Query_Parameters;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;

add_action( 'wp_loaded', [ WPSOLR_Service_Container::class, 'action_wp_loaded' ] );


/**
 * Manage a list of singleton objects (global objects).
 */
class WPSOLR_Service_Container {

	private static $objects = [];

	public static function action_wp_loaded() {

		$is_replace_by_wpsolr_query = WPSOLR_Query_Parameters::is_wp_search() && ! is_admin() && is_main_query()
		                              && WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_search()
		                              && WPSOLR_Service_Container::getOption()->get_search_is_use_current_theme_search_template();
		$is_replace_by_wpsolr_query = apply_filters( WPSOLR_Events::WPSOLR_FILTER_IS_REPLACE_BY_WPSOLR_QUERY, $is_replace_by_wpsolr_query );

		if ( $is_replace_by_wpsolr_query ) {

			// Override global $wp_query with wpsolr_query
			$GLOBALS['wp_the_query'] = WPSOLR_Service_Container::get_query();
			$GLOBALS['wp_query']     = $GLOBALS['wp_the_query'];
		}
	}

	/**
	 * Get/create a singleton object from it's class.
	 */
	public static function getObject( $class_name, $parameter = null ) {

		if ( ! isset( self::$objects[ $class_name ] ) ) {

			self::$objects[ $class_name ] = method_exists( $class_name, "global_object" )
				? isset( $parameter ) ? $class_name::global_object( $parameter ) : $class_name::global_object()
				: new $class_name();
		}

		return self::$objects[ $class_name ];
	}

	/**
	 * @return WPSOLR_Option
	 */
	public static function getOption() {

		return self::getObject( WPSOLR_Option::class );
	}

	/**
	 * @return WPSOLR_Query_Parameters
	 */
	public static function get_query_parameters() {

		return self::getObject( WPSOLR_Query_Parameters::class );
	}


	/**
	 * @return WPSOLR_Query
	 */
	public static function get_query( WPSOLR_Query $wpsolr_query = null ) {

		return self::getObject( WPSOLR_Query::class, $wpsolr_query );
	}

	/**
	 * @return WPSOLR_SearchSolariumClient
	 */
	public static function get_solr_client() {

		return self::getObject( WPSOLR_SearchSolariumClient::class );
	}

	/**
	 * @return WPSOLR_Service_WPSOLR
	 */
	public function get_service_wpsolr() {

		return self::getObject( WPSOLR_Service_WPSOLR::class );
	}

	/**
	 * @return WPSOLR_Service_PHP
	 */
	public function get_service_php() {

		return self::getObject( WPSOLR_Service_PHP::class );
	}

	/**
	 * @return WPSOLR_Service_WP
	 */
	public function get_service_wp() {

		return self::getObject( WPSOLR_Service_WP::class );
	}

	/**
	 * @return WPSOLR_Option
	 */
	public function get_service_option() {

		return self::getObject( WPSOLR_Option::class );
	}

	/**
	 * @return WPSOLR_Query
	 */
	public function get_service_wpsolr_query() {

		return self::getObject( WPSOLR_Query::class );
	}

}

