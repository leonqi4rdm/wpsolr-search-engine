<?php

namespace wpsolr\core\classes\engines;

/**
 * Class WPSOLR_AbstractResultsClient
 *
 * Abstract class for search results.
 */
abstract class WPSOLR_AbstractResultsClient {

	protected $results;

	/**
	 * @return mixed
	 */
	public function get_results() {
		return $this->results;
	}

	/**
	 * @return mixed
	 */
	abstract public function get_suggestions();

	/**
	 * Get nb of results.
	 *
	 * @return int
	 * @throws \Exception
	 */
	abstract public function get_nb_results();

	/**
	 * Get a facet
	 *
	 * @param string $facet_name
	 *
	 * @return array
	 */
	abstract public function get_facet( $facet_name );

	/**
	 * Get highlighting
	 *
	 * @param \Solarium\QueryType\Select\Result\Document|\Elastica\Result $result
	 *
	 * @return array
	 */
	abstract public function get_highlighting( $result );

	/**
	 * Get stats
	 *
	 * @param string $facet_name
	 *
	 * @return array
	 */
	abstract public function get_stats( $facet_name );

}