<?php

namespace wpsolr\core\classes\ui;

use WP_Query;
use wpsolr\core\classes\engines\WPSOLR_AbstractResultsClient;
use wpsolr\core\classes\exceptions\WPSOLR_Exception_Security;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Manage Solr query parameters.
 *
 * @property WPSOLR_AbstractResultsClient $results_set
 */
class WPSOLR_Query extends \WP_Query {

	protected $solr_client;

	/** @var  WP_Query $wp_query */
	protected $wp_query;

	//protected $query;
	protected $wpsolr_query;
	protected $wpsolr_filter_query;

	/* @var int $wpsolr_paged */
	protected $wpsolr_paged;
	protected $wpsolr_sort;
	protected $wpsolr_latitude;
	protected $wpsolr_longitude;
	protected $wpsolr_is_geo;

	protected $results_set;

	/** @var  int $wpsolr_nb_results_by_page */
	protected $wpsolr_nb_results_by_page;


	/**
	 * Constructor used by factory WPSOLR_Service_Container
	 *
	 * @return WPSOLR_Query
	 */
	static function global_object( WPSOLR_Query $wpsolr_query = null ) {

		// Create/Update query from parameters
		return WPSOLR_Query_Parameters::CreateQuery( $wpsolr_query );
	}


	/**
	 * @param WP_Query $wp_query
	 *
	 * @return WPSOLR_Query
	 */
	public static function Create() {

		$wpsolr_query = new WPSOLR_Query();

		$wpsolr_query->set_defaults();

		return $wpsolr_query;
	}

	public function set_defaults() {

		$this->set_wpsolr_query( '' );
		$this->set_filter_query_fields( [] );
		$this->set_wpsolr_paged( '0' );
		$this->set_wpsolr_sort( '' );
		$this->wpsolr_set_nb_results_by_page( '0' );
	}

	/**
	 * @param string $default
	 * @param bool $is_escape Prevent xss attacks when outputing the value in html
	 *
	 * @return string
	 */
	public function get_wpsolr_query( $default = '', $is_escape = false ) {

		// Prevent Solr error by replacing empty query by default value
		return empty( $this->wpsolr_query ) ? $default : ( $is_escape ? esc_attr( $this->wpsolr_query ) : $this->wpsolr_query ); // Prevent xss
	}

	/**
	 * @param string $query
	 */
	public function set_wpsolr_query( $query ) {
		$this->wpsolr_query = $query;
	}

	/**
	 * @return array
	 */
	public function get_filter_query_fields() {
		return ! empty( $this->wpsolr_filter_query ) ? $this->wpsolr_filter_query : [];
	}

	/**
	 * @param array $fq
	 */
	public function set_filter_query_fields( $fq ) {
		// Ensure fq is always an array
		$this->wpsolr_filter_query = empty( $fq ) ? [] : ( is_array( $fq ) ? $fq : [ $fq ] );
	}

	/**
	 * @return int
	 */
	public function get_wpsolr_paged() {
		return $this->wpsolr_paged;
	}

	/**
	 * Calculate the start of pagination
	 * @return integer
	 */
	public function get_start() {
		return ( $this->get_wpsolr_paged() === 0 || $this->get_wpsolr_paged() === 1 ) ? 0 : ( ( $this->get_wpsolr_paged() - 1 ) * $this->get_nb_results_by_page() );
	}


	/**
	 * Set the nb of results by page
	 *
	 * @param string $nb_results_by_page
	 *
	 */
	public function wpsolr_set_nb_results_by_page( $nb_results_by_page ) {
		return $this->wpsolr_nb_results_by_page = intval( $nb_results_by_page );
	}

	/**
	 * Get the nb of results by page
	 * @return integer
	 */
	public function get_nb_results_by_page() {
		return ( $this->wpsolr_nb_results_by_page > 0 ) ? $this->wpsolr_nb_results_by_page : WPSOLR_Service_Container::getOption()->get_search_max_nb_results_by_page();
	}

	/**
	 * @param string $wpsolr_paged
	 */
	public function set_wpsolr_paged( $wpsolr_paged ) {
		$this->wpsolr_paged = intval( $wpsolr_paged );
	}

	/**
	 * @return string
	 */
	public function get_wpsolr_sort() {

		if ( empty( $this->wpsolr_sort ) ) {
			$this->wpsolr_sort = apply_filters( WPSOLR_Events::WPSOLR_FILTER_DEFAULT_SORT, WPSOLR_Service_Container::getOption()->get_sortby_default(), $this );
		}

		return $this->wpsolr_sort;
	}

	/**
	 * @param string $wpsolr_sort
	 */
	public function set_wpsolr_sort( $wpsolr_sort ) {
		$this->wpsolr_sort = $wpsolr_sort;
	}

	/**
	 * @param string $wpsolr_sort
	 */
	public function set_wpsolr_latitude( $wpsolr_latitude ) {
		$this->wpsolr_latitude = $wpsolr_latitude;
	}

	/**
	 * @return string
	 */
	public function get_wpsolr_latitude() {
		return $this->wpsolr_latitude;
	}

	/**
	 * @param string $wpsolr_sort
	 */
	public function set_wpsolr_longitude( $wpsolr_longitude ) {
		$this->wpsolr_longitude = $wpsolr_longitude;
	}

	/**
	 * @return string
	 */
	public function get_wpsolr_longitude() {
		return $this->wpsolr_longitude;
	}


	/**
	 * @param boolean $is_geo
	 *
	 */
	public function set_wpsolr_is_geo( $is_geo ) {
		$this->wpsolr_is_geo = $is_geo;
	}

	/**
	 * @return boolean
	 */
	public function get_wpsolr_is_geo() {
		return $this->wpsolr_is_geo;
	}

	/**************************************************************************
	 *
	 * Override WP_Query methods
	 *
	 *************************************************************************/

	function get_posts() {

		try {//return parent::get_posts();

			// Let WP extract parameters
			if ( apply_filters( WPSOLR_Events::WPSOLR_FILTER_IS_PARSE_QUERY, true ) ) {
				$this->parse_query();
			}

			$q     = $this->query_vars;
			$query = isset( $this->query[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_S ] ) ? $this->query[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_S ] : '';
			if ( empty( $q[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_S ] ) ) {
				$q[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_S ] = $query;
			}
			$this->parse_search( $q );

			// Copy WP standard query to WPSOLR query
			$this->set_wpsolr_query( $query );

			// Copy WP standard paged to WPSOLR paged
			$this->set_wpsolr_paged( isset( $this->query_vars['paged'] ) ? $this->query_vars['paged'] : 1 );

			// $_GET['s'] is used internally by some themes
			//$_GET['s'] = $query;

			// Set variable 's', so that get_search_query() and other standard WP_Query methods still work with our own search parameter
			//$this->set( 's', $query );

			$this->solr_client = WPSOLR_Service_Container::get_solr_client();
			$this->results_set = $this->solr_client->execute_wpsolr_query( $this );

			// Create posts from Solr PIDs
			$posts_in_results = $this->solr_client->get_posts_from_pids();

			foreach ( $posts_in_results['posts'] as $position => $post ) {
				$this->set_the_title( $post, $posts_in_results['documents'][ $position ] );
				$this->set_the_excerpt( $post, $posts_in_results['documents'][ $position ] );
			}

			$this->posts       = $posts_in_results['posts'];
			$this->post_count  = count( $this->posts );
			$this->found_posts = $this->results_set->get_nb_results();

			$this->posts_per_page = $this->get_nb_results_by_page();
			$this->set( "posts_per_page", $this->posts_per_page );
			$this->max_num_pages = ceil( $this->found_posts / $this->posts_per_page );

			if ( ! isset( $this->query_vars['name'] ) ) {
				// Prevent error later in WP code
				$this->query_vars['name'] = '';
			}

			// Action for updating post before getting back to the theme's search page.
			do_action( WPSOLR_Events::WPSOLR_ACTION_POSTS_RESULTS, $this, $this->results_set );

			return $this->posts;

		} catch ( WPSOLR_Exception_Security $e ) {

			// Show nothing
			$this->posts = [];

			return $this->posts;

		} catch ( \Exception $e ) {

			if ( is_admin() ) {
				set_transient( get_current_user_id() . 'wpsolr_error_during_search', htmlentities( $e->getMessage() ) );
			}

			// Error: revert to standard WP search.
			return parent::get_posts();
		}

	}

	/**
	 * @param $field_name
	 * @param $document
	 *
	 * @return string
	 */
	protected function get_highlighting_of_field( $field_name, $document ) {

		$highlighting = $this->results_set->get_highlighting( $document );

		$highlighted_field = isset( $highlighting[ $field_name ] ) ? $highlighting[ $field_name ] : null;
		if ( $highlighted_field ) {

			return empty( $highlighted_field ) ? '' : implode( ' (...) ', $highlighted_field );
		}


		return '';
	}

	/**
	 * @param \WP_Post $post
	 * @param $document
	 */
	protected function set_the_title( \WP_Post $post, $document ) {

		if ( isset( $document ) ) {

			$title = $this->get_highlighting_of_field( WpSolrSchema::_FIELD_NAME_TITLE, $document );

			if ( ! empty( $title ) ) {

				$post->post_title = $title;
			}
		}
	}


	/**
	 * @param \WP_Post $post
	 * @param $document
	 */
	protected function set_the_excerpt( \WP_Post $post, $document ) {

		if ( isset( $document ) ) {

			$content = $this->get_highlighting_of_field( WpSolrSchema::_FIELD_NAME_CONTENT, $document );

			if ( ! empty( $content ) ) {

				$post->post_excerpt = $content;
			}
		}
	}

	/**
	 * Regroup filter query fields by field
	 * ['type:post', 'type:page', 'category:cat1'] => ['type' => ['post', 'page'], 'category' => ['cat1']]
	 * @return array
	 */
	public function get_filter_query_fields_group_by_name() {

		$results = [];

		foreach ( $this->get_filter_query_fields() as $field_encoded ) {

			// Convert 'type:post' in ['type', 'post']
			$field = explode( ':', $field_encoded );

			if ( count( $field ) === 2 ) {

				if ( ! isset( $results[ $field[0] ] ) ) {

					$results[ $field[0] ] = [ $field[1] ];

				} else {

					$results[ $field[0] ][] .= $field[1];
				}
			}
		}

		return $results;
	}

	/**
	 * @return WP_Query
	 */
	public function wpsolr_get_wp_query() {
		return $this->wp_query;
	}

	/**
	 * @param WP_Query $wp_query
	 */
	public function wpsolr_set_wp_query( $wp_query ) {
		$this->wp_query = $wp_query;
	}


}