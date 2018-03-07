<?php

namespace wpsolr\core\classes\engines;

use wpsolr\core\classes\engines\elastica\WPSOLR_IndexElasticaClient;
use wpsolr\core\classes\engines\solarium\WPSOLR_IndexSolariumClient;
use wpsolr\core\classes\exceptions\WPSOLR_Exception_Locking;
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\extensions\WpSolrExtensions;
use wpsolr\core\classes\models\WPSOLR_Model;
use wpsolr\core\classes\models\WPSOLR_Model_Post_Type;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Class WPSOLR_AbstractIndexClient
 * @package wpsolr\core\classes\engines
 */
abstract class WPSOLR_AbstractIndexClient extends WPSOLR_AbstractEngineClient {


	// Posts table name
	const CONTENT_SEPARATOR = ' ';

	const SQL_DATE_NULL = '1000-01-01 00:00:00';
	const MAIN_SQL_LOOP = /** @lang text */
		'SELECT %s FROM %s %s WHERE %s ORDER BY %s %s';

	protected $solr_indexing_options;

	protected $last_post_infos_to_start = [
		'date' => self::SQL_DATE_NULL,
		'ID'   => '0',
	];
	const MODEL_LAST_POST_DATE_INDEXED = 'solr_last_post_date_indexed';

	const STOP_INDEXING_ID = 'action_stop_indexing';

	/**
	 * Use Tika to extract a file content.
	 *
	 * @param $file
	 *
	 * @return string
	 */
	abstract protected function search_engine_client_extract_document_content( $file );


	/**
	 * Execute a solarium query. Retry 2 times if an error occurs.
	 *
	 * @param $search_engine_client
	 * @param $update_query
	 *
	 * @return mixed
	 */
	protected function execute( $search_engine_client, $update_query ) {


		for ( $i = 0; ; $i ++ ) {

			try {

				$result = $this->search_engine_client_execute( $search_engine_client, $update_query );

				return $result;

			} catch ( \Exception $e ) {

				// Catch error here, to retry in next loop, or throw error after enough retries.
				if ( $i >= 3 ) {
					throw $e;
				}

				// Sleep 1 second before retrying
				sleep( 1 );
			}

		}

	}


	/**
	 * Retrieve the Solr index for a post (usefull for multi languages extensions).
	 *
	 * @param $post
	 *
	 * @return WPSOLR_IndexSolariumClient
	 */
	static function create_from_post( $post ) {

		// Get the current post language
		$post_language = apply_filters( WPSOLR_Events::WPSOLR_FILTER_POST_LANGUAGE, null, $post );

		return static::create( null, $post_language );
	}

	/**
	 * @param null $solr_index_indice
	 * @param null $post_language
	 *
	 * @return WPSOLR_IndexSolariumClient|WPSOLR_IndexElasticaClient
	 */
	static function create( $solr_index_indice = null, $post_language = null ) {

		// Build Solarium config from the default indexing Solr index
		WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
		$options_indexes = new WPSOLR_Option_Indexes();
		$config          = $options_indexes->build_config( $solr_index_indice, $post_language, self::DEFAULT_SEARCH_ENGINE_TIMEOUT_IN_SECOND );

		switch ( ! empty( $config['index_engine'] ) ? $config['index_engine'] : self::ENGINE_SOLR ) {

			case self::ENGINE_ELASTICSEARCH:
				return new WPSOLR_IndexElasticaClient( $config, $solr_index_indice, $post_language );
				break;

			default:
				return new WPSOLR_IndexSolariumClient( $config, $solr_index_indice, $post_language );
				break;

		}
	}

	/**
	 * WPSOLR_AbstractIndexClient constructor.
	 *
	 * @param $config
	 * @param null $solr_index_indice
	 * @param null $language_code
	 */
	public function __construct( $config, $solr_index_indice = null, $language_code = null ) {

		$this->init( $config );

		$path = plugin_dir_path( __FILE__ ) . '../../vendor/autoload.php';
		require_once $path;

		// Load options
		$this->solr_indexing_options = WPSOLR_Service_Container::getOption()->get_option_index();

		$this->index_indice = $solr_index_indice;

		$options_indexes = new WPSOLR_Option_Indexes();
		$this->index     = $options_indexes->get_index( $solr_index_indice );

		$this->search_engine_client = $this->create_search_engine_client( $config );
	}


	/**
	 * Delete all documents for some post types
	 *
	 * @param string[] $post_types
	 */
	abstract protected function search_engine_client_delete_all_documents( $post_types = null );

	/**
	 * Delete all documents for some models
	 *
	 * @param string $process_id
	 * @param WPSOLR_Model[] $models
	 */
	public
	function delete_documents(
		$process_id, $models = null
	) {

		// Reset docs first (and lock models)
		if ( is_null( $models ) ) {
			$this->reset_documents( $process_id, $this->models );
		} else {
			$this->reset_documents( $process_id, $models );
		}

		if ( $this->is_in_galaxy ) {
			// Delete only current site content
			//$deleteQuery->addDeleteQuery( sprintf( '%s:"%s"', WpSolrSchema::_FIELD_NAME_BLOG_NAME_STR, $this->galaxy_slave_filter_value ) );
		} else {
			// Delete all content
			if ( is_null( $models ) ) {
				$this->search_engine_client_delete_all_documents();
				$this->unlock_models( $process_id, $this->models );
			} else {
				$this->search_engine_client_delete_all_documents( $this->get_models_post_types( $models ) );
				$this->unlock_models( $process_id, $models );
			}
		}

	}

	/**
	 * Get post types for some models
	 *
	 * @param WPSOLR_Model[] $models
	 *
	 * @return string[] post types
	 */
	protected function get_models_post_types( $models ) {


		$results = [];

		if ( ! is_null( $models ) ) {
			foreach ( $models as $model ) {
				$results[] = $model->get_post_type();
			}
		}

		return $results;
	}

	/**
	 * @param string $process_id
	 * @param WPSOLR_Model[] $models
	 */
	public
	function reset_documents(
		$process_id, $models = null
	) {

		if ( is_null( $models ) ) {
			$models = $this->get_models();
		}


		if ( is_null( $models ) ) {
			throw new \Exception( 'WPSOLR: reset on empty models.' );
		}

		// Lock models
		$this->lock_models( $process_id, $models );

		// Store 0 in # of index documents
		self::set_index_indice_option_value( $models, 'solr_docs', 0 );

		// Reset last indexed post date
		self::reset_last_post_date_indexed( $models );

		// Update nb of documents updated/added
		self::set_index_indice_option_value( $models, 'solr_docs_added_or_updated_last_operation', - 1 );

	}

	/**
	 * How many documents were updated/added during last indexing operation
	 *
	 * @return int
	 */
	public
	function get_count_documents() {

		$nb_documents = $this->search_engine_client_get_count_document();

		// Store 0 in # of index documents
		self::set_index_indice_option_value( null, 'solr_docs', $nb_documents );

		return $nb_documents;

	}

	/**
	 * Delete a document.
	 *
	 * @param string $document_id
	 *
	 */
	abstract protected function search_engine_client_delete_document( $document_id );

	/**
	 * @param \WP_Post $post
	 */
	public function delete_document( $post ) {

		$this->search_engine_client_delete_document( $this->generate_unique_post_id( $post->ID ) );
	}

	/**
	 * @param WPSOLR_Model $model
	 *
	 * @return array
	 */
	public function get_last_post_date_indexed( WPSOLR_Model $model ) {

		$result = $this->get_index_indice_option_value( $model, self::MODEL_LAST_POST_DATE_INDEXED, $this->last_post_infos_to_start );

		return $result;
	}

	/**
	 * @param WPSOLR_Model[] $models
	 *
	 * @return mixed
	 */
	public function reset_last_post_date_indexed( $models ) {

		return $this->set_index_indice_option_value( $models, self::MODEL_LAST_POST_DATE_INDEXED, $this->last_post_infos_to_start );
	}

	/**
	 * @param WPSOLR_Model $model
	 * @param $option_value
	 *
	 * @return mixed
	 */
	public function set_last_post_date_indexed( WPSOLR_Model $model, $option_value ) {

		return $this->set_index_indice_option_value( [ $model ], self::MODEL_LAST_POST_DATE_INDEXED, $option_value );
	}

	/**
	 * Lock one model with a process
	 *
	 * @param string $process_id
	 * @param WPSOLR_Model $model
	 *
	 * @throws \Exception
	 *
	 */
	public function lock_model( $process_id, WPSOLR_Model $model ) {

		$locked_post_types = WPSOLR_Service_Container::getOption()->get_option_locking_index_models( $this->index_indice );

		if ( ! empty( $locked_post_types[ $model->get_post_type() ] ) && ( $locked_post_types[ $model->get_post_type() ] !== $process_id ) ) {
			// This process tries to lock a post type already locked by another process.

			$locking_process_id = $locked_post_types[ $model->get_post_type() ];
			if ( self::STOP_INDEXING_ID === $locking_process_id ) {
				// Stop now
				$this->unlock_process( self::STOP_INDEXING_ID );
				throw new WPSOLR_Exception_Locking( "Indexing stopped as requested, while indexing {$model->get_post_type()} of index {$this->config['index_label']}" );
			}

			$crons         = WPSOLR_Service_Container::getOption()->get_option_cron_indexing();
			$process_label = ( isset( $crons[ $locking_process_id ] ) && isset( $crons[ $locking_process_id ]['label'] ) ) ? $crons[ $locking_process_id ]['label'] : $locking_process_id;

			throw new WPSOLR_Exception_Locking( "{$process_label} is already indexing post type {$model->get_post_type()} of index {$this->config['index_label']}" );
		}

		$this->set_index_indice_option_value( [ $model ], WPSOLR_Option::OPTION_LOCKING, $process_id );
	}


	/**
	 * Lock models with a process
	 *
	 * @param string $process_id
	 * @param WPSOLR_Model[] $models
	 *
	 * @throws \Exception
	 *
	 */
	public function lock_models( $process_id, $models ) {

		foreach ( $models as $model ) {
			$this->lock_model( $process_id, $model );
		}
	}

	/**
	 * Unlock one model
	 *
	 * @param string $process_id
	 * @param WPSOLR_Model $model
	 *
	 */
	public function unlock_model( $process_id, WPSOLR_Model $model ) {

		// Release the model lock
		$this->set_index_indice_option_value( [ $model ], WPSOLR_Option::OPTION_LOCKING, '' );
	}

	/**
	 * Unlock models
	 *
	 * @param string $process_id
	 * @param WPSOLR_Model[] $models
	 *
	 */
	public function unlock_models( $process_id, $models ) {

		// Release the model lock
		$this->set_index_indice_option_value( $models, WPSOLR_Option::OPTION_LOCKING, '' );
	}

	/**
	 * Unlock all the models
	 */
	public function unlock_all_models() {

		delete_option( WPSOLR_Option::OPTION_LOCKING );
	}

	/**
	 * Is a cron locked ?
	 *
	 * @param $cron_uuid
	 */
	static function is_locked( $process_id ) {

		$lockings = WPSOLR_Service_Container::getOption()->get_option_locking();

		foreach ( $lockings as $index_uuid => $locking ) {
			if ( array_search( $process_id, $locking, true ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Unlock a process
	 *
	 * @param $cron_uuid
	 */
	static function unlock_process( $process_id ) {

		$lockings = WPSOLR_Service_Container::getOption()->get_option_locking();

		foreach ( $lockings as $index_uuid => &$locking ) {
			foreach ( $locking as $post_type => $locking_process_id ) {
				if ( $process_id === $locking_process_id ) {
					$locking[ $post_type ] = ( self::STOP_INDEXING_ID === $process_id ) ? '' : self::STOP_INDEXING_ID;
				}
			}
		}

		update_option( WPSOLR_Option::OPTION_LOCKING, $lockings );
	}

	/**
	 * @param WPSOLR_Model $model
	 * @param $option_name
	 * @param $option_value
	 *
	 * @return mixed
	 */
	public function get_index_indice_option_value( WPSOLR_Model $model, $option_name, $option_value ) {

		// Get option value. Replace by default value if undefined.
		//$option = WPSOLR_Service_Container::getOption()->get_option( $option_name, null );
		$option = get_option( $option_name, null );

		// Ensure compatibility
		$this->update_old_indice_format( $option, $this->index_indice );

		$result = $option_value;
		if ( isset( $option ) && isset( $option[ $this->index_indice ] ) && isset( $option[ $this->index_indice ][ $model->get_post_type() ] ) ) {

			$result = $option[ $this->index_indice ][ $model->get_post_type() ];
		}

		return $result;
	}

	/**
	 * @param WPSOLR_Model[] $models
	 * @param $option_name
	 * @param $option_value
	 *
	 * @return mixed
	 */
	public function set_index_indice_option_value( $models, $option_name, $option_value ) {

		$option = get_option( $option_name, null );

		if ( ! isset( $option ) ) {
			$option                        = [];
			$option[ $this->index_indice ] = [];
		}

		if ( is_null( $models ) ) {

			// Compatibility with post types models stored without the table name
			$option[ $this->index_indice ] = $option_value;

		} else {

			// Ensure compatibility
			$this->update_old_indice_format( $option, $this->index_indice );

			foreach ( $models as $model ) {
				$option[ $this->index_indice ][ $model->get_post_type() ] = $option_value;
			}
		}

		update_option( $option_name, $option );

		return $option_value;
	}


	/**
	 * @param array $option
	 * @param string $indice_uuid
	 */
	function update_old_indice_format( &$option, $indice_uuid ) {
		if ( ! isset( $option[ $indice_uuid ] ) || is_scalar( $option[ $indice_uuid ] ) ) {
			$option[ $indice_uuid ] = []; // Old format as a string, replaced by an array
		}
	}

	/**
	 * Count nb documents remaining to index for a solr index
	 *
	 * @param WPSOLR_Model $model
	 *
	 * @return int Nb documents remaining to index
	 */
	public
	function get_count_nb_documents_to_be_indexed(
		WPSOLR_Model $model
	) {

		return $this->index_data( false, 'default', [ $model ], 0, null );
	}

	/**
	 * @param bool $is_stopping
	 * @param string $process_id Process calling the indexing method
	 * @param WPSOLR_Model[] $models
	 * @param int $batch_size
	 * @param \WP_Post $post
	 *
	 * @param bool $is_debug_indexing
	 * @param bool $is_only_exclude_ids
	 *
	 * @return array|int
	 * @throws WPSOLR_Exception_Locking
	 * @throws \Exception
	 */
	public
	function index_data(
		$is_stopping, $process_id, $models, $batch_size = 100, $post = null, $is_debug_indexing = false, $is_only_exclude_ids = false
	) {

		global $wpdb;

		//$this->unlock_all_models();

		$models = ! is_null( $post )
			? WPSOLR_Model_Post_Type::create_models( [ $post->post_type ] )
			: ( is_null( $models ) ? $this->set_default_models() : $models );


		// Needs locking only on "real" indexing
		$is_needs_locking = is_null( $post ) && ! empty( $batch_size ) && ! $is_only_exclude_ids;

		// Debug variable containing debug text
		$debug_text = '';

		$doc_count         = 0;
		$no_more_posts     = 0;
		$models_nb_results = [];
		foreach ( $models as $model ) {

			try {
				$is_needs_unlocking = false;

				// Lock the model to prevent concurrent indexing between crons, or between crons and batches
				if ( $is_needs_locking ) {
					$this->lock_model( $process_id, $model );
				}

				$post_type                       = $model->get_post_type();
				$models_nb_results[ $post_type ] = 0;

				// Last post date set in previous call. We begin with posts published after.
				// Reset the last post date is reindexing is required.
				$last_post_date_indexed = $this->get_last_post_date_indexed( $model );

				$query_statements = $model->get_indexing_sql( $debug_text, $batch_size, $post, $is_debug_indexing, $is_only_exclude_ids );

				// Eventually, log some debug information
				if ( ! empty( $query_statements['debug_info'] ) ) {
					$this->add_debug_line( $debug_text, null, $query_statements['debug_info'] );
				}

				// Filter the query
				$query_statements = apply_filters( WPSOLR_Events::WPSOLR_FILTER_SQL_QUERY_STATEMENT,
					$query_statements,
					[
						'model'        => $model,
						'index_indice' => $this->index_indice,
					]
				);

				// Generate query string from the query statements
				$query = sprintf(
					self::MAIN_SQL_LOOP,
					$query_statements['SELECT'],
					$query_statements['FROM'],
					$query_statements['JOIN'],
					$query_statements['WHERE'],
					$query_statements['ORDER'],
					0 === $query_statements['LIMIT'] ? '' : 'LIMIT ' . $query_statements['LIMIT']
				);

				$documents = [];
				while ( true ) {

					if ( $is_debug_indexing ) {
						$this->add_debug_line( $debug_text, 'Beginning of new loop (batch size)' );
					}

					// Execute query (retrieve posts IDs, parents and types)
					if ( isset( $post ) ) {

						if ( $is_debug_indexing ) {
							$this->add_debug_line( $debug_text, 'Query document with post->ID', [
								'Query'   => $query,
								'Post ID' => $post->ID,
							] );
						}

						$ids_array = $wpdb->get_results( $wpdb->prepare( $query, $post->ID ), ARRAY_A );

					} elseif ( $is_only_exclude_ids ) {

						$ids_array = $wpdb->get_results( $query, ARRAY_A );

					} else {

						if ( $is_debug_indexing ) {
							$this->add_debug_line( $debug_text, 'Query documents from last post date', [
								'Query'          => $query,
								'Last post date' => $last_post_date_indexed['date'],
								'Last post ID'   => $last_post_date_indexed['ID'],
							] );
						}

						$ids_array = $wpdb->get_results( $wpdb->prepare( $query, $last_post_date_indexed['date'], $last_post_date_indexed['ID'], $last_post_date_indexed['date'] ), ARRAY_A );
					}

					if ( 0 === $batch_size ) {

						$nb_docs = $ids_array[0]['TOTAL'];

						if ( $is_debug_indexing ) {
							$this->add_debug_line( $debug_text, 'End of loop', [
								$is_only_exclude_ids ? 'Number of documents in database excluded from indexing' : 'Number of documents in database to be indexed' => $nb_docs,
							] );
						}

						// Just return the count
						return $nb_docs;
					}


					// Aggregate current batch IDs in one Solr update statement
					$post_count = count( $ids_array );

					if ( 0 === $post_count ) {
						// No more documents to index, stop now by exiting the loop

						if ( $is_debug_indexing ) {
							$this->add_debug_line( $debug_text, 'No more documents, end of document loop' );
						}

						$no_more_posts ++;
						$is_needs_unlocking = true;
						break;
					}

					for ( $idx = 0; $idx < $post_count; $idx ++ ) {
						$postid = $ids_array[ $idx ]['ID'];

						// If post is not an attachment
						if ( 'attachment' !== $ids_array[ $idx ]['post_type'] ) {

							// Count this post
							$doc_count ++;
							$models_nb_results[ $post_type ] ++;

							// Customize the attachment body, if attachments are linked to the current post
							$post_attachments = apply_filters( WPSOLR_Events::WPSOLR_FILTER_GET_POST_ATTACHMENTS, [], $postid );

							// Get the attachments body with a Solr Tika extract query
							$attachment_body = '';
							foreach ( $post_attachments as $post_attachment ) {
								$attachment_body .= ( empty( $attachment_body ) ? '' : '. ' ) . self::extract_attachment_text_by_calling_solr_tika( $postid, $post_attachment );
							}


							// Get the posts data
							$document = $this->create_solr_document_from_post_or_attachment( get_post( $postid ), $attachment_body );

							if ( $is_debug_indexing ) {
								$this->add_debug_line( $debug_text, null, [
									'Post to be sent' => wp_json_encode( $document, JSON_PRETTY_PRINT ),
								] );
							}

							$documents[] = $document;

						} else {
							// Post is of type "attachment"

							if ( $is_debug_indexing ) {
								$this->add_debug_line( $debug_text, null, [
									'Post ID to be indexed (attachment)' => $postid,
								] );
							}

							// Count this post
							$doc_count ++;
							$models_nb_results[ $post_type ] ++;

							// Get the attachments body with a Solr Tika extract query
							$attachment_body = self::extract_attachment_text_by_calling_solr_tika( $postid, [ 'post_id' => $postid ] );

							// Get the posts data
							$document = $this->create_solr_document_from_post_or_attachment( get_post( $postid ), $attachment_body );

							if ( $is_debug_indexing ) {
								$this->add_debug_line( $debug_text, null, [
									'Attachment to be sent' => wp_json_encode( $document, JSON_PRETTY_PRINT ),
								] );
							}

							$documents[] = $document;

						}
					}

					if ( empty( $documents ) || ! isset( $documents ) ) {
						// No more documents to index, stop now by exiting the loop

						if ( $is_debug_indexing ) {
							$this->add_debug_line( $debug_text, 'End of loop, no more documents' );
						}

						break;
					}

					// Send batch documents to Solr
					try {

						$res_final = $this->send_posts_or_attachments_to_solr_index( $documents );

					} catch ( \Exception $e ) {

						if ( $is_debug_indexing ) {
							// Echo debug text now, else it will be hidden by the exception
							echo $debug_text;
						}

						// Continue
						throw $e;
					}

					// Solr error, or only $post to index: exit loop
					if ( ( null === $res_final ) || isset( $post ) ) {
						break;
					}

					if ( ! isset( $post ) ) {
						// Store last post date sent to Solr (for batch only)
						$last_post = end( $ids_array );
						$this->set_last_post_date_indexed(
							$model,
							[
								'date' => $last_post['post_modified'],
								'ID'   => $last_post['ID'],
							] );
					}

					// AJAX: one loop by ajax call
					break;
				}
			} catch ( WPSOLR_Exception_Locking $e ) {
				// Do nothing. Continue
				throw ( $e );

			} catch ( \Exception $e ) {

				// force unlock the model if error, else would be stuck locked
				if ( $is_needs_locking ) {
					$this->unlock_model( $process_id, $model );
				}

				// Continue
				throw ( $e );
			}

			// unlock the model only if it contains no more data to index, or if the indexing is stopping
			if ( $is_needs_locking && ( $is_stopping || $is_needs_unlocking ) ) {
				$this->unlock_model( $process_id, $model );
			}
		}

		$status = ! isset( $res_final ) ? 0 : $res_final;

		// All models have no more data ?
		$indexing_complete = ( $no_more_posts === count( $models ) );

		return $res_final = [
			'models_nb_results' => $models_nb_results,
			'nb_results'        => $doc_count,
			'status'            => $status,
			'indexing_complete' => $indexing_complete,
			'debug_text'        => $is_debug_indexing ? $debug_text : null,
		];

	}

	/*
	 * Fetch posts and attachments,
	 * Transform them in Solr documents,
	 * Send them in packs to Solr
	 */

	/**
	 * Add a debug line to the current debug text
	 *
	 * @param $is_debug_indexing
	 * @param $debug_text
	 * @param $debug_text_header
	 * @param $debug_text_content
	 */
	public
	function add_debug_line(
		&$debug_text, $debug_line_header, $debug_text_header_content = null
	) {

		if ( isset( $debug_line_header ) ) {
			$debug_text .= '******** DEBUG ACTIVATED - ' . $debug_line_header . ' *******' . '<br><br>';
		}

		if ( isset( $debug_text_header_content ) ) {

			foreach ( $debug_text_header_content as $key => $value ) {
				$debug_text .= $key . ':' . '<br>' . '<b>' . $value . '</b>' . '<br><br>';
			}
		}
	}

	/**
	 * Transform a string in a date.
	 *
	 * @param $date_str String date to convert from.
	 *
	 * @return mixed
	 */
	abstract public function search_engine_client_format_date( $date_str );

	/**
	 * @param $solarium_update_query
	 * @param $post_to_index
	 * @param null $attachment_body
	 *
	 * @return mixed
	 * @internal param $solr_indexing_options
	 */
	public
	function create_solr_document_from_post_or_attachment(
		$post_to_index, $attachment_body = ''
	) {

		$solarium_document_for_update = [];

		$pid    = $post_to_index->ID;
		$ptitle = $post_to_index->post_title;
		// Post is NOT an attachment: we get the document body from the post object
		$pcontent = $post_to_index->post_content . ( empty( $attachment_body ) ? '' : ( '. ' . $attachment_body ) );

		$pexcerpt   = $post_to_index->post_excerpt;
		$pauth_info = get_userdata( $post_to_index->post_author );
		$pauthor    = isset( $pauth_info ) && isset( $pauth_info->display_name ) ? $pauth_info->display_name : '';
		$pauthor_s  = isset( $pauth_info ) && isset( $pauth_info->user_nicename ) ? get_author_posts_url( $pauth_info->ID, $pauth_info->user_nicename ) : '';

		// Get the current post language
		$post_language = apply_filters( WPSOLR_Events::WPSOLR_FILTER_POST_LANGUAGE, null, $post_to_index );
		$ptype         = $post_to_index->post_type;

		$pdate            = solr_format_date( $post_to_index->post_date_gmt );
		$pmodified        = solr_format_date( $post_to_index->post_modified_gmt );
		$pdisplaydate     = $this->search_engine_client_format_date( $post_to_index->post_date );
		$pdisplaymodified = $this->search_engine_client_format_date( $post_to_index->post_modified );
		$purl             = get_permalink( $pid );
		$comments_con     = [];
		$comm             = isset( $this->solr_indexing_options[ WpSolrSchema::_FIELD_NAME_COMMENTS ] ) ? $this->solr_indexing_options[ WpSolrSchema::_FIELD_NAME_COMMENTS ] : '';

		$numcomments = 0;
		if ( $comm ) {
			$comments_con = [];

			$comments = get_comments( "status=approve&post_id={$post_to_index->ID}" );
			foreach ( $comments as $comment ) {
				array_push( $comments_con, $comment->comment_content );
				$numcomments += 1;
			}

		}
		$pcomments    = $comments_con;
		$pnumcomments = $numcomments;


		/*
			Get all custom categories selected for indexing, including 'category'
		*/
		$cats                            = [];
		$categories_flat_hierarchies     = [];
		$categories_non_flat_hierarchies = [];
		$taxo                            = WPSOLR_Service_Container::getOption()->get_option_index_taxonomies_str();
		$aTaxo                           = explode( ',', $taxo );
		$newTax                          = []; // Add categories by default
		if ( is_array( $aTaxo ) && count( $aTaxo ) ) {
		}
		foreach ( $aTaxo as $a ) {

			if ( WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING === substr( $a, ( strlen( $a ) - 4 ), strlen( $a ) ) ) {
				$a = substr( $a, 0, ( strlen( $a ) - 4 ) );
			}

			// Add only non empty categories
			if ( strlen( trim( $a ) ) > 0 ) {
				array_push( $newTax, $a );
			}
		}


		// Get all categories ot this post
		$terms = wp_get_post_terms( $post_to_index->ID, [ 'category' ], [ 'fields' => 'all_with_object_id' ] );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {

				// Add category and it's parents
				$term_parents_names = [];
				// Add parents in reverse order ( top-bottom)
				$term_parents_ids = array_reverse( get_ancestors( $term->term_id, 'category' ) );
				array_push( $term_parents_ids, $term->term_id );

				foreach ( $term_parents_ids as $term_parent_id ) {
					$term_parent = get_term( $term_parent_id, 'category' );

					array_push( $term_parents_names, $term_parent->name );

					// Add the term to the non-flat hierarchy (for filter queries on all the hierarchy levels)
					array_push( $categories_non_flat_hierarchies, $term_parent->name );
				}

				// Add the term to the flat hierarchy
				array_push( $categories_flat_hierarchies, implode( WpSolrSchema::FACET_HIERARCHY_SEPARATOR, $term_parents_names ) );

				// Add the term to the categories
				array_push( $cats, $term->name );
			}
		}

		// Get all tags of this port
		$tag_array = [];
		$tags      = get_the_tags( $post_to_index->ID );
		if ( ! $tags == null ) {
			foreach ( $tags as $tag ) {
				array_push( $tag_array, $tag->name );

			}
		}

		if ( $this->is_in_galaxy ) {
			$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_BLOG_NAME_STR ] = $this->galaxy_slave_filter_value;
		}

		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_ID ]       = $this->generate_unique_post_id( $pid );
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_PID ]      = $pid;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_TITLE ]    = $ptitle;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_STATUS_S ] = $post_to_index->post_status;

		if ( isset( $this->solr_indexing_options['p_excerpt'] ) && ( ! empty( $pexcerpt ) ) ) {

			// Index post excerpt, by adding it to the post content.
			// Excerpt can therefore be: searched, autocompleted, highlighted.
			$pcontent .= self::CONTENT_SEPARATOR . $pexcerpt;
		}

		if ( ! empty( $pcomments ) ) {

			// Index post comments, by adding it to the post content.
			// Excerpt can therefore be: searched, autocompleted, highlighted.
			//$pcontent .= self::CONTENT_SEPARATOR . implode( self::CONTENT_SEPARATOR, $pcomments );
		}


		$content_with_shortcodes_expanded_or_stripped = $pcontent;
		if ( isset( $this->solr_indexing_options['is_shortcode_expanded'] ) && ( strpos( $pcontent, '[solr_search_shortcode]' ) === false ) ) {

			// Expand shortcodes which have a plugin active, and are not the search form shortcode (else pb).
			global $post;
			$post                                         = $post_to_index;
			$content_with_shortcodes_expanded_or_stripped = do_shortcode( $pcontent );
		}

		// Remove shortcodes tags remaining, but not their content.
		// strip_shortcodes() does nothing, probably because shortcodes from themes are not loaded in admin.
		// Credit: https://wordpress.org/support/topic/stripping-shortcodes-keeping-the-content.
		// Modified to enable "/" in attributes
		$content_with_shortcodes_expanded_or_stripped = preg_replace( "~(?:\[/?)[^\]]+/?\]~s", '', $content_with_shortcodes_expanded_or_stripped );  # strip shortcodes, keep shortcode content;

		// Remove HTML tags
		$stripped_content                                                  = strip_tags( $content_with_shortcodes_expanded_or_stripped );
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CONTENT ] = ! empty( $stripped_content ) ? $stripped_content : ' '; // Prevent empty content error with ES

		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_AUTHOR ]              = $pauthor;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_AUTHOR_S ]            = $pauthor_s;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_TYPE ]                = $ptype;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_DATE ]                = $pdate;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_MODIFIED ]            = $pmodified;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_DISPLAY_DATE ]        = $pdisplaydate;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_DISPLAY_DATE_DT ]     = $pdisplaydate;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_DISPLAY_MODIFIED ]    = $pdisplaymodified;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_DISPLAY_MODIFIED_DT ] = $pdisplaymodified;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_PERMALINK ]           = $purl;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_COMMENTS ]            = $pcomments;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_NUMBER_OF_COMMENTS ]  = $pnumcomments;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CATEGORIES_STR ]      = $cats;
		// Hierarchy of categories
		$solarium_document_for_update[ sprintf( WpSolrSchema::_FIELD_NAME_FLAT_HIERARCHY, WpSolrSchema::_FIELD_NAME_CATEGORIES_STR ) ]     = $categories_flat_hierarchies;
		$solarium_document_for_update[ sprintf( WpSolrSchema::_FIELD_NAME_NON_FLAT_HIERARCHY, WpSolrSchema::_FIELD_NAME_CATEGORIES_STR ) ] = $categories_non_flat_hierarchies;
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_TAGS ]                                                                    = $tag_array;

		// Index post thumbnail
		$this->index_post_thumbnails( $solarium_document_for_update, $pid );

		// Index post url
		$this->index_post_url( $solarium_document_for_update, $pid );

		$taxonomies = (array) get_taxonomies( [ '_builtin' => false ], 'names' );
		foreach ( $taxonomies as $parent ) {
			if ( in_array( $parent, $newTax, true ) ) {
				$terms = get_the_terms( $post_to_index->ID, $parent );
				if ( (array) $terms === $terms ) {
					$parent    = strtolower( str_replace( ' ', '_', $parent ) );
					$nm1       = $parent . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING;
					$nm1_array = [];

					$taxonomy_non_flat_hierarchies = [];
					$taxonomy_flat_hierarchies     = [];

					foreach ( $terms as $term ) {

						// Add taxonomy and it's parents
						$term_parents_names = [];
						// Add parents in reverse order ( top-bottom)
						$term_parents_ids = array_reverse( get_ancestors( $term->term_id, $parent ) );
						array_push( $term_parents_ids, $term->term_id );

						foreach ( $term_parents_ids as $term_parent_id ) {
							$term_parent = get_term( $term_parent_id, $parent );

							if ( $term_parent instanceof \WP_Error ) {
								throw new \Exception( sprintf( 'WPSOLR: error on term %s for taxonomy \'%s\': %s', $term_parent_id, $parent, $term_parent->get_error_message() ) );
							}

							array_push( $term_parents_names, $term_parent->name );

							// Add the term to the non-flat hierarchy (for filter queries on all the hierarchy levels)
							array_push( $taxonomy_non_flat_hierarchies, $term_parent->name );
						}

						// Add the term to the flat hierarchy
						array_push( $taxonomy_flat_hierarchies, implode( WpSolrSchema::FACET_HIERARCHY_SEPARATOR, $term_parents_names ) );

						// Add the term to the taxonomy
						array_push( $nm1_array, $term->name );

						// Add the term to the categories searchable
						array_push( $cats, $term->name );

					}

					if ( count( $nm1_array ) > 0 ) {
						$solarium_document_for_update[ $nm1 ] = $nm1_array;

						$solarium_document_for_update[ sprintf( WpSolrSchema::_FIELD_NAME_FLAT_HIERARCHY, $nm1 ) ]     = $taxonomy_flat_hierarchies;
						$solarium_document_for_update[ sprintf( WpSolrSchema::_FIELD_NAME_NON_FLAT_HIERARCHY, $nm1 ) ] = $taxonomy_non_flat_hierarchies;

					}
				}
			}
		}

		// Set categories and custom taxonomies as searchable
		$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CATEGORIES ] = $cats;

		// Add custom fields to the document
		$this->set_custom_fields( $solarium_document_for_update, $post_to_index );

		if ( isset( $this->solr_indexing_options['p_custom_fields'] ) && isset( $solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CUSTOM_FIELDS ] ) ) {

			$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CONTENT ] .= self::CONTENT_SEPARATOR . implode( ". ", $solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CUSTOM_FIELDS ] );
		}

		// Last chance to customize the solarium update document
		$solarium_document_for_update = apply_filters( WPSOLR_Events::WPSOLR_FILTER_SOLARIUM_DOCUMENT_FOR_UPDATE,
			$solarium_document_for_update,
			$this->solr_indexing_options,
			$post_to_index,
			$attachment_body,
			$this
		);

		return $solarium_document_for_update;
	}

	/**
	 * Set custom fields to the update document.
	 * HTML and php tags are removed.
	 *
	 * @param $solarium_document_for_update
	 * @param $post
	 */
	function set_custom_fields( &$solarium_document_for_update, $post ) {

		$custom_fields = WPSOLR_Service_Container::getOption()->get_option_index_custom_fields();

		if ( count( $custom_fields ) > 0 ) {
			if ( count( $post_custom_fields = get_post_custom( $post->ID ) ) ) {

				// Apply filters on custom fields
				$post_custom_fields = apply_filters( WPSOLR_Events::WPSOLR_FILTER_POST_CUSTOM_FIELDS, $post_custom_fields, $post->ID );

				$existing_custom_fields = isset( $solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CUSTOM_FIELDS ] )
					? $solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CUSTOM_FIELDS ]
					: [];

				foreach ( (array) $custom_fields as $field_name_with_str_ending ) {

					$field_name = WpSolrSchema::get_field_without_str_ending( $field_name_with_str_ending );

					if ( isset( $post_custom_fields[ $field_name ] ) ) {
						$field = (array) $post_custom_fields[ $field_name ];

						$field_name = strtolower( str_replace( ' ', '_', $field_name ) );

						// Add custom field array of values
						//$nm1       = $field_name . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING;
						$nm1       = WpSolrSchema::replace_field_name_extension( $field_name_with_str_ending );
						$array_nm1 = [];
						foreach ( $field as $field_value ) {

							$field_value_sanitized = WpSolrSchema::get_sanitized_value( $this, $field_name_with_str_ending, $field_value, $post );

							// Only index the field if it has a value.
							if ( ( '0' === $field_value_sanitized ) || ! empty( $field_value_sanitized ) ) {

								array_push( $array_nm1, $field_value_sanitized );

								// Add current custom field values to custom fields search field
								// $field being an array, we add each of it's element
								// Convert values to string, else error in the search engine if number, as a string is expected.
								array_push( $existing_custom_fields, is_array( $field_value_sanitized ) ? $field_value_sanitized : strval( $field_value_sanitized ) );
							}
						}

						$solarium_document_for_update[ $nm1 ] = $array_nm1;
					}
				}

				if ( count( $existing_custom_fields ) > 0 ) {
					$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_CUSTOM_FIELDS ] = $existing_custom_fields;
				}

			}

		}

	}

	/**
	 * @param string $postid
	 * @param array $post_attachement
	 *
	 * @return string
	 * @throws \Exception
	 */
	public
	function extract_attachment_text_by_calling_solr_tika(
		$postid, $post_attachement
	) {

		try {
			$post_attachement_file = ! empty( $post_attachement['post_id'] ) ? get_attached_file( $post_attachement['post_id'] ) : download_url( $post_attachement['url'] );

			if ( $post_attachement_file instanceof \WP_Error ) {
				throw new \Exception( sprintf( 'Could not access the attachement content. %s', $post_attachement_file->get_error_message() ) );
			}

			$response = $this->search_engine_client_extract_document_content( $post_attachement_file );

			$attachment_text_extracted_from_tika = preg_replace( '/^.*?\<body\>(.*)\<\/body\>.*$/i', '\1', $response );
			if ( PREG_NO_ERROR !== preg_last_error() ) {
				throw new \Exception( sprintf( 'Error code (%s) returned by preg_replace() on the extracted file.', PREG_NO_ERROR ) );
			}

			if ( empty( $attachment_text_extracted_from_tika ) ) {
				// Wrong preg_replace() result,. Use the original text.
				// Wrong preg_replace() result,. Use the original text.
				throw new \Exception( 'Wrong format returned for the extracted file, cannot extract the <body>.' );
			}

			$attachment_text_extracted_from_tika = str_replace( '\n', ' ', $attachment_text_extracted_from_tika );
		} catch ( \Exception $e ) {
			if ( ! empty( $post_attachement['post_id'] ) ) {

				$post = get_post( $post_attachement['post_id'] );

				throw new \Exception( 'Error on attached file ' . $post->post_title . ' (ID: ' . $post->ID . ')' . ': ' . $e->getMessage(), $e->getCode() );

			} else {

				throw new \Exception( sprintf( 'Error on embedded url "%s" of post_id %s. %s', $post_attachement['url'], $postid, $e->getMessage() ), $e->getCode() );
			}
		}

		// Last chance to customize the tika extracted attachment body
		$attachment_text_extracted_from_tika = apply_filters( WPSOLR_Events::WPSOLR_FILTER_ATTACHMENT_TEXT_EXTRACTED_BY_APACHE_TIKA, $attachment_text_extracted_from_tika, $post_attachement );

		return $attachment_text_extracted_from_tika;
	}

	/**
	 * @param array $documents
	 *
	 * @return mixed
	 */
	abstract public function send_posts_or_attachments_to_solr_index( $documents );

	/**
	 * Index a post thumbnail
	 *
	 * @param \Solarium\QueryType\Update\Query\Document\Document $document Solarium document
	 * @param $post_id
	 *
	 * @return array|false
	 */
	private
	function index_post_thumbnails(
		&$solarium_document_for_update, $post_id
	) {

		if ( $this->is_in_galaxy ) {

			// Master must get thumbnails from the index, as the $post_id is not in local database
			$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ) );
			if ( false !== $thumbnail ) {

				$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_POST_THUMBNAIL_HREF_STR ] = $thumbnail[0];
			}
		}

	}

	/**
	 * Index a post url
	 *
	 * @param \Solarium\QueryType\Update\Query\Document\Document $document Solarium document
	 * @param $post_id
	 *
	 * @return array|false
	 */
	private
	function index_post_url(
		&$solarium_document_for_update, $post_id
	) {

		if ( $this->is_in_galaxy ) {

			// Master must get urls from the index, as the $post_id is not in local database
			$url = get_permalink( $post_id );
			if ( false !== $url ) {

				$solarium_document_for_update[ WpSolrSchema::_FIELD_NAME_POST_HREF_STR ] = $url;
			}
		}
	}

	/**
	 * Get count of blacklisted post ids
	 * @return int
	 */
	public function get_count_blacklisted_ids() {

		$result = $this->index_data( false, 'default', null, 0, null, false, true );

		return $result;
	}
}
