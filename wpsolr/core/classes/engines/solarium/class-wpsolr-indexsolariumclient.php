<?php

namespace wpsolr\core\classes\engines\solarium;

use Solarium\Core\Query\Helper;
use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient;
use wpsolr\core\classes\utilities\WPSOLR_Regexp;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Class WPSOLR_IndexSolariumClient
 * @package wpsolr\core\classes\engines\solarium
 *
 * @property \Solarium\Client $search_engine_client
 */
class WPSOLR_IndexSolariumClient extends WPSOLR_AbstractIndexClient {
	use WPSOLR_SolariumClient;

	const PATTERN_CONTROL_CHARACTERS = '@[\x00-\x08\x0B\x0C\x0E-\x1F]@';

	/* @var Helper $helper */
	protected $helper;

	/**
	 * @inheritDoc
	 */
	public function __construct( $config, $solr_index_indice = null, $language_code = null ) {
		parent::__construct( $config, $solr_index_indice, $language_code );

		add_filter( WPSOLR_Events::WPSOLR_FILTER_SOLARIUM_DOCUMENT_FOR_UPDATE, [
			$this,
			'strip_control_characters',
		], 10, 5 );

	}

	/**
	 * Remove control characters that provoke indexing Solr errors
	 *
	 * @param array $document_for_update
	 * @param $solr_indexing_options
	 * @param $post
	 * @param $attachment_body
	 * @param WPSOLR_AbstractIndexClient $search_engine_client
	 *
	 * @return array Document updated with fields
	 */
	function strip_control_characters( $document_for_update, $solr_indexing_options, $post, $attachment_body, WPSOLR_AbstractIndexClient $search_engine_client ) {

		WPSOLR_Regexp::replace_recursive( $document_for_update, self::PATTERN_CONTROL_CHARACTERS, '' );

		return $document_for_update;
	}

	/**
	 * @param \Solarium\QueryType\Update\Query\Query $solarium_update_query
	 * @param array $documents
	 */
	protected function search_engine_client_prepare_documents_for_update( $solarium_update_query, array $documents ) {

		$formatted_document = [];

		foreach ( $documents as $document ) {
			$formatted_document[] = $solarium_update_query->createDocument( $document );
		}

		return $formatted_document;
	}

	/**
	 * @param array $documents
	 *
	 * @return mixed
	 */
	public function send_posts_or_attachments_to_solr_index( $documents ) {

		$solarium_update_query = $this->search_engine_client->createUpdate();

		$formatted_docs = $this->search_engine_client_prepare_documents_for_update( $solarium_update_query, $documents );

		$solarium_update_query->addDocuments( $formatted_docs );
		$solarium_update_query->addCommit();
		$result = $this->execute( $this->search_engine_client, $solarium_update_query );

		return $result->get_results()->getStatus();
	}

	/**
	 * @return int
	 */
	protected function search_engine_client_get_count_document() {

		$query = $this->search_engine_client->createSelect();
		$query->setQuery( '*:*' );
		$query->setRows( 0 );
		$result_set = $this->search_engine_client_execute( $this->search_engine_client, $query );

		return $result_set->get_nb_results();
	}

	/**
	 * Delete all documents for some post types
	 *
	 * @param string[] $post_types
	 */
	protected function search_engine_client_delete_all_documents( $post_types = null ) {

		if ( is_null( $post_types ) || empty( $post_types ) ) {

			$query = 'id:*';

		} else {

			$query = sprintf( '%s:(%s)', WpSolrSchema::_FIELD_NAME_TYPE, implode( ' OR ', $post_types ) );
		}

		// Execute delete query
		$delete_query = $this->search_engine_client->createUpdate();

		$delete_query->addDeleteQuery( $query );

		$delete_query->addCommit();

		$this->search_engine_client_execute( $this->search_engine_client, $delete_query );
	}

	/**
	 * Use Tika to extract a file content.
	 *
	 * @param $file
	 *
	 * @return string
	 */
	protected function search_engine_client_extract_document_content( $file ) {

		$solarium_extract_query = $this->search_engine_client->createExtract();

		// Set URL to attachment
		$solarium_extract_query->setFile( $file );
		$doc1 = $solarium_extract_query->createDocument();
		$solarium_extract_query->setDocument( $doc1 );
		// We don't want to add the document to the solr index now
		$solarium_extract_query->addParam( 'extractOnly', 'true' );
		// Try to extract the document body
		$client   = $this->search_engine_client;
		$results  = $this->execute( $client, $solarium_extract_query );
		$response = $results->get_results()->getResponse()->getBody();

		return $response;
	}

	/**
	 * Transform a string in a date.
	 *
	 * @param $date_str String date to convert from.
	 *
	 * @return string
	 */
	public function search_engine_client_format_date( $date_str ) {

		if ( null === $this->helper ) {
			$this->helper = new Helper( $this );
		}

		return $this->helper->formatDate( $date_str );
	}

	/**
	 * Delete a document.
	 *
	 * @param string $document_id
	 *
	 */
	protected function search_engine_client_delete_document( $document_id ) {

		$deleteQuery = $this->search_engine_client->createUpdate();
		$deleteQuery->addDeleteQuery( 'id:' . $document_id );
		$deleteQuery->addCommit();

		$this->execute( $this->search_engine_client, $deleteQuery );
	}

	/**
	 * Prepare query execute
	 */
	public function search_engine_client_pre_execute() {
		// TODO: Implement search_engine_client_pre_execute() method.
	}

	/**
	 * Fix an error while querying the engine.
	 *
	 * @param \Exception $e
	 * @param $search_engine_client
	 * @param $update_query
	 *
	 * @return
	 */
	protected function search_engine_client_execute_fix_error( \Exception $e, $search_engine_client, $update_query ) {
		// TODO: Implement search_engine_client_execute_fix_error() method.
	}
}
