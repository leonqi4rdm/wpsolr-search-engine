<?php

namespace wpsolr\core\classes\engines\elastica;

use wpsolr\core\classes\engines\WPSOLR_AbstractResultsClient;

/**
 * Class WPSOLR_ResultsElasticaClient
 *
 * @property \Elastica\ResultSet $results
 */
class WPSOLR_ResultsElasticaClient extends WPSOLR_AbstractResultsClient {

	/**
	 * WPSOLR_ResultsElasticaClient constructor.
	 *
	 * @param \Elastica\ResultSet $results
	 */
	public function __construct( \Elastica\ResultSet $results ) {

		$this->results = $results;
	}

	/**
	 * @return mixed
	 */
	public function get_suggestions() {
		if ( $this->results->hasSuggests() ) {

			$suggests = $this->results->getSuggests();

			$suggests_array = [];
			if ( isset( $suggests[ WPSOLR_SearchElasticaClient::SUGGESTER_NAME ] ) ) {
				foreach ( $suggests[ WPSOLR_SearchElasticaClient::SUGGESTER_NAME ][0]['options'] as $option ) {
					array_push( $suggests_array, [ 'text' => $option['text'] ] );
				}
			}

			return $suggests_array;
		}

		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function get_results() {

		return $this->results->getResults();
	}


	/**
	 * Get nb of results.
	 *
	 * @return int
	 */
	public function get_nb_results() {

		return $this->results->getTotalHits();
	}

	/**
	 * Get a facet
	 *
	 * @return array
	 */
	public function get_facet( $facet_name ) {
		try {
			$aggregation = $this->results->getAggregation( $facet_name );

			$buckets = [];
			if ( isset( $aggregation['buckets'] ) ) {
				$buckets = $aggregation['buckets'];
			} elseif ( isset( $aggregation[ $facet_name ] ) && isset( $aggregation[ $facet_name ]['buckets'] ) ) {
				$buckets = $aggregation[ $facet_name ]['buckets'];
			}

			// Convert.
			$facets = [];
			foreach ( $buckets as $bucket ) {
				// $bucket['key'] contains the range
				$facets[ $bucket['key'] ] = $bucket['doc_count'];
			}

			return $facets;

		} catch ( \Exception $e ) {
			// Prevent the error.
			return null;
		}
	}

	/**
	 * Get highlighting
	 *
	 * @param \Elastica\Result $result
	 *
	 * @return array
	 */
	public function get_highlighting( $result ) {
		// https://github.com/ruflin/Elastica/blob/4666078db27d2574171c9fe1bba5d7782b2ae7cf/test/lib/Elastica/Test/Query/HighlightTest.php
		return $result ? $result->getHighlights() : [];
	}

	/**
	 * Get stats
	 *
	 * @return array
	 */
	public function get_stats( $facet_name ) {

		$aggregation = $this->results->getAggregation( $facet_name );

		return [ sprintf( '%s-%s', $aggregation[ $facet_name ]['min'], $aggregation[ $facet_name ]['max'] ) => $aggregation[ $facet_name ]['count'] ];
	}

}