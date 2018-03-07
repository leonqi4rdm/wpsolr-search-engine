<?php

namespace wpsolr\core\classes\engines;

use wpsolr\core\classes\models\WPSOLR_Model;
use wpsolr\core\classes\models\WPSOLR_Model_Post_Type;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Class WPSOLR_AbstractEngineClient
 * @package wpsolr\core\classes\engines
 */
abstract class WPSOLR_AbstractEngineClient {

	// Engine types
	const ENGINE = 'index_engine';
	const ENGINE_ELASTICSEARCH = 'engine_elasticsearch';
	const ENGINE_ELASTICSEARCH_NAME = 'Elasticsearch';
	const ENGINE_SOLR = 'engine_solr';
	const ENGINE_SOLR_NAME = 'Apache Solr';
	const ENGINE_SOLR_CLOUD = 'engine_solr_cloud';
	const ENGINE_SOLR_CLOUD_NAME = 'Apache SolrCloud';

	// Timeout in seconds when calling Solr
	const DEFAULT_SEARCH_ENGINE_TIMEOUT_IN_SECOND = 30;

	protected $search_engine_client;

	protected $search_engine_client_config;

	// Indice of the Solr index configuration in admin options
	protected $index_indice;

	// Index
	public $index;


	// Array of active extension objects
	protected $wpsolr_extensions;

	// Is blog in a galaxy
	protected $is_in_galaxy;

	// Is blog a slave search
	protected $is_galaxy_slave;

	// Is blog a master search
	protected $is_galaxy_master;

	// Galaxy slave filter value
	protected $galaxy_slave_filter_value;

	// Custom fields properties
	protected $custom_field_properties;

	/** @var WPSOLR_Model[] $models */
	protected $models;

	/** @var array */
	protected $config;

	/**
	 * @return WPSOLR_Model[]
	 */
	public function get_models() {
		return is_null( $this->models ) ? $this->set_default_models() : $this->models;
	}

	/**
	 * @param WPSOLR_Model[] $models
	 */
	public function set_models( $models ) {
		$this->models = $models;
	}

	/**
	 * How many documents are in the index ?
	 * @return int
	 * @throws \Exception
	 */
	protected function search_engine_client_get_count_document() {
		throw new \Exception( 'Not implemented.' );
	}

	/**
	 * Create an client
	 *
	 * @param array $config
	 *
	 * @return object
	 */
	abstract protected function create_search_engine_client( $config );

	/**
	 * Execute an update query with the client.
	 *
	 * @param $search_engine_client
	 * @param $update_query
	 *
	 * @return WPSOLR_AbstractResultsClient
	 */
	abstract protected function search_engine_client_execute( $search_engine_client, $update_query );

	/**
	 * Fix an error while querying the engine.
	 *
	 * @param \Exception $e
	 * @param $search_engine_client
	 * @param $update_query
	 *
	 * @return
	 */
	abstract protected function search_engine_client_execute_fix_error( \Exception $e, $search_engine_client, $update_query );

	/**
	 * Multivalue sort is not supported. Remove it.
	 */
	protected function remove_multivalue_sort() {
		WPSOLR_Service_Container::getOption()->set_sortby_is_multivalue( false );
	}

	/**
	 * Multivalue sort is supported. Add it.
	 */
	protected function add_multivalue_sort() {
		WPSOLR_Service_Container::getOption()->set_sortby_is_multivalue( true );
	}

	/**
	 * Init details
	 *
	 * @param $config
	 */
	protected function init( $config = null ) {

		$this->config = $config;

		$all_models = [];
		//$all_models[] = new WPSOLR_Model_User();
		//$all_models[] = new WPSOLR_Model_BP_Profile_Data();

		$this->custom_field_properties = WPSOLR_Service_Container::getOption()->get_option_index_custom_field_properties();

		$this->init_galaxy();
	}

	/**
	 * Set default models (all post types selected)
	 *
	 * @return WPSOLR_Model[]
	 */
	protected function set_default_models() {

		$models_to_index = WPSOLR_Model_Post_Type::create_models( WPSOLR_Service_Container::getOption()->get_option_index_post_types_and_attachments() );
		$this->set_models( $models_to_index );

		return $models_to_index;
	}

	/**
	 * Init galaxy details
	 */
	protected function init_galaxy() {

		$this->is_in_galaxy     = WPSOLR_Service_Container::getOption()->get_search_is_galaxy_mode();
		$this->is_galaxy_slave  = WPSOLR_Service_Container::getOption()->get_search_is_galaxy_slave();
		$this->is_galaxy_master = WPSOLR_Service_Container::getOption()->get_search_is_galaxy_master();

		// After
		$this->galaxy_slave_filter_value = get_bloginfo( 'blogname' );
	}

	/**
	 * Geenrate a unique post_id for sites in a galaxy, else keep post_id
	 *
	 * @param $post_id
	 *
	 * @return string
	 */
	protected function generate_unique_post_id( $post_id ) {

		if ( ! $this->is_in_galaxy ) {
			// Current site is not in a galaxy: post_id is already unique
			return $post_id;
		}

		// Create a unique id by adding the galaxy name to the post_id
		$result = sprintf( '%s_%s', $this->galaxy_slave_filter_value, $post_id );

		return $result;
	}

	/**
	 * Is a field sortable ?
	 *
	 * @param string $field_name Field name (like 'price_str')
	 *
	 * @return bool
	 */
	public
	function get_is_field_sortable(
		$field_name
	) {

		return ( ! empty( $this->custom_field_properties[ $field_name ] )
		         && ! empty( $this->custom_field_properties[ $field_name ][ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE ] )
		         && WpSolrSchema::get_solr_dynamic_entension_id_is_sortable( $this->custom_field_properties[ $field_name ][ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE ] )
		);

	}

}
