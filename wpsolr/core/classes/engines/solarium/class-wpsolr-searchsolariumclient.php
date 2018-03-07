<?php

namespace wpsolr\core\classes\engines\solarium;

use Solarium\QueryType\Select\Query\FilterQuery;
use Solarium\QueryType\Select\Query\Query;
use wpsolr\core\classes\engines\solarium\admin\WPSOLR_Solr_Admin_Api_Core;
use wpsolr\core\classes\engines\WPSOLR_AbstractSearchClient;
use wpsolr\core\classes\utilities\WPSOLR_Regexp;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Class WPSOLR_SearchSolariumClient
 *
 */
class WPSOLR_SearchSolariumClient extends WPSOLR_AbstractSearchClient {
	use WPSOLR_SolariumClient;

	// Multi-value sort
	const SORT_MULTIVALUE_FIELD = 'field(%s,%s)';

	// Constants for filter patterns
	const FILTER_PATTERN_EMPTY_OR_IN = '(*:* -%s:[* TO *]) OR %s:(%s)';
	const FILTER_PATTERN_EXISTS = '%s:*';

	// Template for the geolocation distance field(s)
	const TEMPLATE_NAMED_GEODISTANCE_QUERY_FOR_FIELD = '%s%s:%s';

	// Function to calculate distance
	const GEO_DISTANCE = 'geodist()';

	// Filter range Solr syntax
	const SOLR_FILTER_RANGE_UPPER_STRICT = '%s:[%s TO %s}';
	const SOLR_FILTER_RANGE_UPPER_INCLUDED = '%s:[%s TO %s]';

	// Template for the geolocation distance sort field(s)
	const TEMPLATE_ANONYMOUS_GEODISTANCE_QUERY_FOR_FIELD = 'geodist(%s,%s,%s)'; // geodist between field and 'lat,long'

	/* @var string[] $filter_queries_or */
	protected $filter_queries_or;

	/* @var \Solarium\QueryType\Select\Query\Query */
	protected $query_select;

	/* @var \Solarium\Client */
	protected $search_engine_client;

	/**
	 * Prepare query execute
	 */
	public function search_engine_client_pre_execute() {

		if ( ! empty( $this->filter_queries_or ) ) {

			foreach ( $this->filter_queries_or as $field_name => $filter_query_or ) {

				$this->query_select->addFilterQuery(
					[
						'key'   => $field_name,
						'query' => $filter_query_or['query'],
						'tag'   => $filter_query_or['tag'],
					]
				);
			}

			// Used: clear it.
			$this->filter_queries_or = [];
		}

	}


	/**
	 * Does index exists ?
	 *
	 * @param bool $is_throw_error
	 *
	 * @return bool
	 * @throws \Exception
	 */
	protected function admin_is_index_exists( $is_throw_error = false ) {
		$result = true;

		try {
			$this->search_engine_client->ping( $this->search_engine_client->createPing() );

		} catch ( \Exception $e ) {

			if ( $is_throw_error ) {
				throw $e;
			}

			$result = false;
		}

		return $result;
	}

	/**
	 * Create the index
	 */
	protected function admin_create_index() {

		$coreadmin_api = new WPSOLR_Solr_Admin_Api_Core( $this->search_engine_client );

		if ( self::ENGINE_SOLR_CLOUD === $this->config['index_engine'] ) {

			$coreadmin_api->create_solrcloud_index( $this->config['extra_parameters'][ self::ENGINE_SOLR_CLOUD ] );

		} else {

			$coreadmin_api->create_solr_index();
		}
	}

	/**
	 * Delete the index
	 */
	public function admin_delete_index() {

		$coreadmin_api = new WPSOLR_Solr_Admin_Api_Core( $this->search_engine_client );

		if ( self::ENGINE_SOLR_CLOUD === $this->config['index_engine'] ) {

			$coreadmin_api->delete_solrcloud_index();

		} else {

			$coreadmin_api->delete_solr_index();
		}
	}

	/**
	 * Add a configuration to the index if missing.
	 */
	protected function admin_index_update_configuration_if_missing() {

		//$solr_admin_api_schema = new WPSOLR_Solr_Admin_Api_Schema( $this->search_engine_client );
		//$solr_admin_api_schema->update_schema();

		//$solr_admin_api_config = new WPSOLR_Solr_Admin_Api_Config( $this->search_engine_client );
		//$solr_admin_api_config->update_config();
	}

	/**
	 * Create a select query.
	 *
	 * @return \Solarium\QueryType\Select\Query\Query
	 */
	protected function search_engine_client_create_query_select() {
		return $this->search_engine_client->createSelect();
	}

	/**
	 * Escape special characters in a query keywords.
	 *
	 * @param string $keywords
	 *
	 * @return string
	 */
	protected function search_engine_client_escape_term( $keywords ) {
		return $this->query_select->getHelper()->escapeTerm( $keywords );
	}

	/**
	 * Set keywords of a query select.
	 *
	 * @return string
	 */
	protected function search_engine_client_set_query_keywords( $keywords ) {
		$this->query_select->setQuery( $keywords );
	}

	/**
	 * Set query's default operator.
	 *
	 * @param string $operator
	 */
	protected function search_engine_client_set_default_operator( $operator = 'AND' ) {
		$this->query_select->setQueryDefaultOperator( $operator );
	}

	/**
	 * Set query's start.
	 *
	 * @param int $start
	 *
	 */
	protected function search_engine_client_set_start( $start ) {
		$this->query_select->setStart( $start );
	}

	/**
	 * Set query's rows.
	 *
	 * @param int $rows
	 *
	 */
	protected function search_engine_client_set_rows( $rows ) {
		$this->query_select->setRows( $rows );
	}

	/**
	 * Add a sort to the query
	 *
	 * @param string $sort
	 * @param string $sort_by
	 * @param $is_multivalue
	 */
	public function search_engine_client_add_sort( $sort, $sort_by, $is_multivalue ) {

		if ( $is_multivalue ) {
			// Use field(_, min|max) to be able to sort multi-value fields: https://cwiki.apache.org/confluence/display/solr/Function+Queries#FunctionQueries-field

			$this->query_select->addSort( sprintf( self::SORT_MULTIVALUE_FIELD, $sort, Query::SORT_ASC === $sort_by ? 'min' : 'max' ), $sort_by );

		} else {
			// Standard sort on single-value field

			$this->query_select->addSort( $sort, $sort_by );
		}
	}

	/**
	 * Add a simple filter term.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param $facet_is_or
	 * @param string $field_value
	 * @param string $filter_tag
	 */
	public function search_engine_client_add_filter_term( $filter_name, $field_name, $facet_is_or, $field_value, $filter_tag = '' ) {

		// In case the facet contains white space, we enclose it with "".
		$field_value_escaped = "\"$field_value\"";

		$this->search_engine_client_add_filter_any( $filter_name, $field_name, $facet_is_or, "$field_name:$field_value_escaped", $filter_tag );
	}

	/**
	 * Add a negative filter on terms.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param array $field_values
	 *
	 * @param string $filter_tag
	 *
	 */
	public function search_engine_client_add_filter_not_in_terms( $filter_name, $field_name, $field_values, $filter_tag = '' ) {

		$this->query_select->addFilterQuery(
			[
				'key'   => $filter_name,
				'query' => sprintf( '-%s:(%s)', $field_name, implode( ' OR ', $field_values ) ),
			]
		);

	}

	/**
	 * Add a 'OR' filter on terms.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param array $field_values
	 * @param string $filter_tag
	 *
	 */
	public function search_engine_client_add_filter_in_terms( $filter_name, $field_name, $field_values, $filter_tag = '' ) {

		$this->query_select->addFilterQuery(
			$this->search_engine_client_create_filter_in_terms( $field_name, $field_values )->setKey( $filter_name )
		);

	}

	/**
	 * Create a 'OR' filter on terms.
	 *
	 * @param string $field_name
	 * @param array $field_values
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_filter_in_terms( $field_name, $field_values ) {
		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( '%s:(%s)', $field_name, implode( ' OR ', $field_values ) ),
			]
		);
	}

	/**
	 * Create a 'NOR' filter on terms.
	 *
	 * @param string $field_name
	 * @param array $field_values
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_filter_not_in_terms( $field_name, $field_values ) {
		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( '-%s:(%s)', $field_name, implode( ' OR ', $field_values ) ),
			]
		);
	}

	/**
	 * Create a 'only numbers' filter.
	 *
	 * @param string $field_name
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_filter_only_numbers( $field_name ) {
		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( '(*:* -%s:[a TO z])', $field_name ),
			]
		);
	}

	/**
	 * Create a 'empty or absent' filter.
	 *
	 * @param string $field_name
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_filter_no_values( $field_name ) {
		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( '(*:* -%s:*)', $field_name ),
			]
		);
	}

	/**
	 * Create a 'OR' from filters.
	 *
	 * @param FilterQuery[] $queries
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_or( $queries ) {

		$query_texts = [];
		foreach ( $queries as $query ) {
			$query_texts[] = sprintf( '%s', $query->getQuery() );
		}

		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( '(%s)', implode( ' OR ', $query_texts ) ),
			]
		);
	}

	/**
	 * Add a filter
	 *
	 * @param string $filter_name
	 * @param FilterQuery $filter
	 */
	public function search_engine_client_add_filter( $filter_name, $filter ) {

		$this->query_select->addFilterQuery(
			[
				'key'   => $filter_name,
				'query' => $filter->getQuery(),
			]
		);
	}

	/**
	 * Create a 'AND' from filters.
	 *
	 * @param FilterQuery[] $queries
	 *
	 * @return FilterQuery
	 */
	public function search_engine_client_create_and( $queries ) {

		$query_texts = [];
		foreach ( $queries as $query ) {
			$query_texts[] = sprintf( '%s', $query->getQuery() );
		}

		return $this->query_select->createFilterQuery(
			[
				'query' => sprintf( '(%s)', implode( ' AND ', $query_texts ) ),
			]
		);
	}

	/**
	 * Add a filter on: empty or in terms.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param array $field_values
	 * @param string $filter_tag
	 *
	 */
	public function search_engine_client_add_filter_empty_or_in_terms( $filter_name, $field_name, $field_values, $filter_tag = '' ) {

		$this->query_select->addFilterQuery(
			[
				'key'   => $filter_name,
				'query' => sprintf( self::FILTER_PATTERN_EMPTY_OR_IN, $field_name, $field_name, implode( ' OR ', $field_values ) ),
			]
		);
	}

	/**
	 * Filter fields with values
	 *
	 * @param $filter_name
	 * @param $field_name
	 */
	public function search_engine_client_add_filter_exists( $filter_name, $field_name ) {

		$this->query_select->addFilterQuery(
			[
				'key'   => $filter_name,
				'query' => sprintf( self::FILTER_PATTERN_EXISTS, $field_name ),
			]
		);
	}

	/**
	 * Set highlighting.
	 *
	 * @param string[] $field_names
	 * @param string $prefix
	 * @param string $postfix
	 * @param int $fragment_size
	 *
	 */
	protected function search_engine_client_set_highlighting( $field_names, $prefix, $postfix, $fragment_size ) {

		$highlighting = $this->query_select->getHighlighting();

		foreach ( $field_names as $field_name ) {

			$highlighting->getField( $field_name )->setSimplePrefix( $prefix )->setSimplePostfix( $postfix );

			// Max size of each highlighting fragment for post content
			$highlighting->getField( $field_name )->setFragSize( $fragment_size );
		}

	}


	/**
	 * Set minimum count of facet items to retrieve a facet.
	 *
	 * @param $min_count
	 *
	 */
	protected function search_engine_client_set_facets_min_count( $facet_name, $min_count ) {

		// Only display facets that contain data
		$this->query_select->getFacetSet()->setMinCount( $min_count );
	}

	/**
	 * Create a facet field.
	 *
	 * @param $facet_name
	 * @param $field_name
	 *
	 */
	protected function search_engine_client_add_facet_field( $facet_name, $field_name ) {

		$this->query_select->getFacetSet()->createFacetField( "$facet_name" )->setField( "$field_name" );
	}

	/**
	 * Set facets limit.
	 *
	 * @param int $limit
	 *
	 */
	protected function search_engine_client_set_facets_limit( $facet_name, $limit ) {

		$this->query_select->getFacetSet()->setLimit( $limit );
	}

	/**
	 * @param string $facet_name
	 *
	 * @return null|\Solarium\QueryType\Select\Query\Component\Facet\AbstractFacet|\Solarium\QueryType\Select\Query\Component\Facet\Facet
	 */
	protected function get_facet( $facet_name ) {

		$facets = $this->query_select->getFacetSet()->getFacets();

		if ( ! empty( $facets[ $facet_name ] ) ) {
			return $facets[ $facet_name ];
		}

		return null;
	}

	/**
	 * Sort a facet field alphabetically.
	 *
	 * @param $facet_name
	 *
	 */
	protected function search_engine_client_set_facet_sort_alphabetical( $facet_name ) {

		/** @var \Solarium\QueryType\Select\Query\Component\Facet\Field $facet */
		$facet = $this->get_facet( $facet_name );

		if ( $facet ) {
			$facet->setSort( self::PARAMETER_FACET_SORT_ALPHABETICALLY );
		}

	}

	/**
	 * Set facet field excludes.
	 *
	 * @param string $facet_name
	 * @param string $exclude
	 *
	 */
	protected function search_engine_client_set_facet_excludes( $facet_name, $exclude ) {

		/** @var \Solarium\QueryType\Select\Query\Component\Facet\Field $facet */
		$facet = $this->get_facet( $facet_name );

		if ( $facet ) {
			$facet->setExcludes( [ sprintf( self::FILTER_QUERY_TAG_FACET_EXCLUSION, $exclude ) ] );
		}

	}

	/**
	 * Set the fields to be returned by the query.
	 *
	 * @param array $fields
	 *
	 */
	protected function search_engine_client_set_fields( $fields ) {
		$this->query_select->setFields( $fields );
	}

	/**
	 * Get suggestions from the engine.
	 *
	 * @param $query
	 *
	 * @return WPSOLR_ResultsSolariumClient
	 */
	protected function search_engine_client_get_suggestions_keywords( $query ) {

		$suggester_query = $this->search_engine_client->createSuggester();
		$suggester_query->setHandler( 'suggest' );
		$suggester_query->setDictionary( 'suggest' );
		$suggester_query->setQuery( $query );
		$suggester_query->setCount( 5 );
		$suggester_query->setCollate( true );
		$suggester_query->setOnlyMorePopular( true );

		return $this->search_engine_client_execute( $this->search_engine_client, $suggester_query );
	}

	/**
	 * Get suggestions for did you mean.
	 *
	 * @param string $keywords
	 *
	 * @return string Did you mean keyword
	 */
	protected function search_engine_client_get_did_you_mean_suggestions( $keywords ) {

		// Add spellcheck to current query
		$spell_check = $this->query_select->getSpellcheck();
		$spell_check->setCount( 10 );
		$spell_check->setCollate( true );
		$spell_check->setCollateExtendedResults( true );
		$spell_check->setExtendedResults( true );
		$spell_check->setQuery( $keywords ); // Mandatory for Solr >= 5.5

		// Excecute the query modified
		$result_set = $this->execute_query();

		// Parse spell check results
		$spell_check_results = $result_set->get_results()->getSpellcheck();

		$did_you_mean_keyword = ''; // original query

		if ( $spell_check_results && ! $spell_check_results->getCorrectlySpelled() ) {

			$collations = $spell_check_results->getCollations();
			foreach ( $collations as $collation ) {

				foreach ( $collation->getCorrections() as $input => $correction ) {
					$did_you_mean_keyword = str_replace( $input, is_array( $correction ) ? $correction[0] : $correction, $keywords );
					break;
				}
			}
		}

		return $did_you_mean_keyword;
	}

	/**
	 * Build the query
	 *
	 */
	public function search_engine_client_build_query() {
		// Nothing. Query is built incrementally.
	}

	/**
	 * Add a geo distance sort.
	 * The field is already in the sorts. It will be replaced with geo sort specific syntax.
	 *
	 * @param $field_name
	 * @param $geo_latitude
	 * @param $geo_longitude
	 *
	 */
	public function search_engine_client_add_sort_geolocation_distance( $field_name, $geo_latitude, $geo_longitude ) {

		$sorts = $this->query_select->getSorts();
		if ( ! empty( $sorts ) && ! empty( $sorts[ $field_name ] ) ) {

			// Use the sort by distance
			$this->query_select->addSort( $this->get_anonymous_geodistance_query_for_field( $field_name, $geo_latitude, $geo_longitude ), $sorts[ $field_name ] );

			// Filter out results without coordinates
			/*
			 * does not work with some Solr versions
			 $solarium_query->addFilterQuery(
				array(
					'key'   => 'geo_exclude_empty',
					'query' => sprintf( '%s:[-90,-180 TO 90,180]', $sort_field_name ),
				)
			);*/

		}

		// Remove the field from the sorts, as we use a function instead,
		// or we do not use the field as sort because geolocation is missing.
		$this->query_select->removeSort( $field_name );

	}

	/**
	 * Generate a distance query for a field
	 * 'field_name1' => geodist(field_name1_ll, center_point_lat, center_point_long)
	 *
	 * @param $field_name
	 * @param $geo_latitude
	 * @param $geo_longitude
	 *
	 * @return string
	 *
	 */
	public function get_anonymous_geodistance_query_for_field( $field_name, $geo_latitude, $geo_longitude ) {
		return sprintf( self::TEMPLATE_ANONYMOUS_GEODISTANCE_QUERY_FOR_FIELD,
			WpSolrSchema::replace_field_name_extension( $field_name ),
			$geo_latitude,
			$geo_longitude
		);
	}

	/**
	 * Generate a distance query for a field, and name the query
	 * 'field_name1' => wpsolr_distance_field_name1:geodist(field_name1_ll, center_point_lat, center_point_long)
	 *
	 * @param $field_prefix
	 * @param $field_name
	 * @param $geo_latitude
	 * @param $geo_longitude
	 *
	 * @return string
	 *
	 */
	public function get_named_geodistance_query_for_field( $field_prefix, $field_name, $geo_latitude, $geo_longitude ) {
		return sprintf( self::TEMPLATE_NAMED_GEODISTANCE_QUERY_FOR_FIELD,
			$field_prefix,
			WPSOLR_Regexp::remove_string_at_the_end( $field_name, WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ),
			$this->get_anonymous_geodistance_query_for_field( $field_name, $geo_latitude, $geo_longitude )
		);
	}

	/**
	 * Replace default query field by query fields, with their eventual boost.
	 *
	 * @param array $query_fields
	 *
	 */
	protected function search_engine_client_set_query_fields( array $query_fields ) {

		$this->query_select->getEDisMax()->setQueryFields( implode( ' ', $query_fields ) );
	}

	/**
	 * Set boosts field values.
	 *
	 * @param string $boost_field_values
	 *
	 */
	protected function search_engine_client_set_boost_field_values( $boost_field_values ) {

		$this->query_select->getEDisMax()->setBoostQuery( $boost_field_values );
	}

	/**
	 * Create a facet range regular.
	 *
	 * @param $facet_name
	 * @param $field_name
	 *
	 * @param string $range_start
	 * @param string $range_end
	 * @param string $range_gap
	 *
	 */
	protected function search_engine_client_add_facet_range_regular( $facet_name, $field_name, $range_start, $range_end, $range_gap ) {

		/**
		 * https://cwiki.apache.org/confluence/display/solr/Faceting#Faceting-IntervalFaceting
		 * https://cwiki.apache.org/confluence/display/solr/DocValues
		 *
		 * Intervals are requiring docValues and Solr 4.10. We're therefore using ranges with before and after sections.
		 */
		$this->query_select->getFacetSet()
		                   ->createFacetRange( "$facet_name" )
		                   ->setField( "$field_name" )
		                   ->setStart( $range_start )
		                   ->setEnd( $range_end )
		                   ->setGap( $range_gap )
		                   ->setInclude( 'lower' )
		                   ->setOther( 'all' );


		/*
		$intervals = [];

		// Add a range for values before start
		$intervals[ sprintf( '%s-%s', '*', $range_start ) ] = sprintf( '[%s,%s)', '*', $range_start );

		// No gap parameter. We build the ranges manually.
		for ( $start = $range_start; $start < $range_end; $start += $range_gap ) {
			$intervals[ sprintf( '%s-%s', $start, $start + $range_gap ) ] = sprintf( '[%s,%s)', $start, $start + $range_gap );
		}

		// Add a range for values after end
		$intervals[ sprintf( '%s-%s', $range_end, '*' ) ] = sprintf( '[%s,%s)', $range_end, '*' );


		$this->query_select->getFacetSet()
		                   ->createFacetInterval( "$facet_name" )
		                   ->setField( "$field_name" )
		                   ->setSet( $intervals );
		*/


	}

	/**
	 * Add a simple filter range.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param string $facet_is_or
	 * @param string $range_start
	 * @param string $range_end
	 * @param string $filter_tag
	 */
	public function search_engine_client_add_filter_range( $range_parameters, $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $filter_tag = '' ) {

		$this->search_engine_client_add_filter_any( $filter_name, $field_name, $facet_is_or, sprintf( $range_parameters, $field_name, $range_start, $range_end ), $filter_tag );
	}

	/**
	 * Add a simple filter range.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param string $facet_is_or
	 * @param string $range_start
	 * @param string $range_end
	 * @param string $filter_tag
	 */
	public function search_engine_client_add_filter_range_upper_strict( $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $filter_tag = '' ) {

		if ( $range_start === $range_end ) {

			$this->search_engine_client_add_filter_range_upper_included( $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $filter_tag );

		} else {

			$this->search_engine_client_add_filter_range( self::SOLR_FILTER_RANGE_UPPER_STRICT, $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $filter_tag );
		}
	}

	/**
	 * Add a simple filter range.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param string $facet_is_or
	 * @param string $range_start
	 * @param string $range_end
	 * @param string $filter_tag
	 */
	public function search_engine_client_add_filter_range_upper_included( $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $filter_tag = '' ) {

		$this->search_engine_client_add_filter_range( self::SOLR_FILTER_RANGE_UPPER_INCLUDED, $filter_name, $field_name, $facet_is_or, $range_start, $range_end, $filter_tag );
	}

	/**
	 * Add a simple filter range.
	 *
	 * @param string $filter_name
	 * @param string $field_name
	 * @param string $facet_is_or
	 * @param string $filter_query
	 * @param string $filter_tag
	 */
	public function search_engine_client_add_filter_any( $filter_name, $field_name, $facet_is_or, $filter_query, $filter_tag = '' ) {

		if ( $facet_is_or ) {

			if ( ! isset( $this->filter_queries_or[ $field_name ] ) ) {
				$this->filter_queries_or[ $field_name ] = [ 'query' => '', 'tag' => $filter_tag ];
			}

			$this->filter_queries_or[ $field_name ]['query'] .= sprintf( ' %s %s ', empty( $this->filter_queries_or[ $field_name ]['query'] ) ? '' : ' OR ', $filter_query );

		} else {

			$this->query_select->addFilterQuery(
				[
					'key'   => $filter_name,
					'query' => $filter_query,
					'tag'   => $filter_tag,
				]
			);
		}
	}

	/**
	 * Add decay functions to the search query
	 *
	 * @param array $decays
	 *
	 */
	public function search_engine_client_add_decay_functions( array $decays ) {
		// TODO: Implement search_engine_client_add_decay_functions() method.
	}

	/**
	 * Fix an error while querying the engine.
	 *
	 * @param \Exception $e
	 * @param $search_engine_client
	 * @param $update_query
	 */
	protected function search_engine_client_execute_fix_error( \Exception $e, $search_engine_client, $update_query ) {

		if ( strpos( $e->getMessage(), 'sort param could not be parsed as a query' ) ) {
			// Solr version does not accept multi-value sort

			$this->remove_multivalue_sort();

		} elseif ( strpos( $e->getMessage(), 'can not sort on multivalued field' ) ) {
			// The schema accepts multi-value sort

			$this->add_multivalue_sort();
		}

	}

	/**
	 * Add a geo distance filter.
	 *
	 * @param $field_name
	 * @param $geo_latitude
	 * @param $geo_longitude
	 *
	 */
	public function search_engine_client_add_filter_geolocation_distance( $field_name, $geo_latitude, $geo_longitude, $distance ) {

		$this->query_select->addFilterQuery(
			[
				'key'   => sprintf( 'distance %s', $field_name ),
				'query' => $this->query_select->getHelper()->geofilt(
					$field_name,
					$geo_latitude,
					$geo_longitude,
					$distance
				),
			] );
	}

	/**
	 * Create a facet stats.
	 *
	 * @param string $facet_name
	 * @param string $exclude
	 *
	 */
	protected function search_engine_client_add_facet_stats( $facet_name, $exclude ) {

		/**
		 * I applied manually a fix to Solarium to get the stats exclusion feature: https://github.com/solariumphp/solarium/pull/268
		 * /wpsolr-core/vendor/solarium/solarium/library/Solarium/QueryType/Select/Query/Component/Stats/Field.php
		 * /wpsolr-core/vendor/solarium/solarium/library/Solarium/QueryType/Select/RequestBuilder/Component/Stats.php
		 */

		$stats = $this->query_select->getStats();

		$field = $stats->createField( $facet_name );
		$field->addExclude( sprintf( self::FILTER_QUERY_TAG_FACET_EXCLUSION, $exclude ) );
	}
}
