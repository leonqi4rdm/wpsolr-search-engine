<?php

use wpsolr\core\classes\engines\solarium\WPSOLR_IndexSolariumClient;
use wpsolr\core\classes\engines\solarium\WPSOLR_SearchSolariumClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractSearchClient;
use wpsolr\core\classes\extensions\localization\OptionLocalization;
use wpsolr\core\classes\extensions\WpSolrExtensions;
use wpsolr\core\classes\models\WPSOLR_Model_Post_Type;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\ui\WPSOLR_Data_Facets;
use wpsolr\core\classes\ui\WPSOLR_Data_Sort;
use wpsolr\core\classes\ui\WPSOLR_UI_Facets;
use wpsolr\core\classes\ui\WPSOLR_UI_Sort;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;

// Load localization class
WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_LOCALIZATION, true );
//WpSolrExtensions::load();


/**
 * @param $thedate
 *
 * @return mixed
 */
function solr_format_date( $thedate ) {
	$datere  = '/(\d{4}-\d{2}-\d{2})\s(\d{2}:\d{2}:\d{2})/';
	$replstr = '${1}T${2}Z';

	return preg_replace( $datere, $replstr, $thedate );
}

function fun_search_indexed_data() {

	$ad_url = admin_url();

	// Retrieve search form page url
	$get_page_info = WPSOLR_SearchSolariumClient::get_search_page();
	$url           = get_permalink( $get_page_info->ID );
	// Filter the search page url. Used for multi-language search forms.
	$url = apply_filters( WPSOLR_Events::WPSOLR_FILTER_SEARCH_PAGE_URL, $url, $get_page_info->ID );

	// Load localization options
	$localization_options = OptionLocalization::get_options();

	$wdm_typehead_request_handler = WPSOLR_AJAX_AUTO_COMPLETE_ACTION;

	echo "<div class='cls_search' style='width:100%'> <form action='$url' method='get'  class='search-frm' >";
	echo '<input type="hidden" value="' . $wdm_typehead_request_handler . '" id="path_to_fold">';
	echo '<input type="hidden" value="' . $ad_url . '" id="path_to_admin">';
	echo '<input type="hidden" value="' . WPSOLR_Service_Container::get_query()->get_wpsolr_query( '', true ) . '" id="search_opt">';

	$ajax_nonce = wp_create_nonce( "nonce_for_autocomplete" );

	echo '<div class="ui-widget">';
	echo '<input type="hidden"  id="ajax_nonce" value="' . $ajax_nonce . '">';
	echo '<input type="text" placeholder="' . OptionLocalization::get_term( $localization_options, 'search_form_edit_placeholder' ) . '" value="';
	echo WPSOLR_Service_Container::get_query()->get_wpsolr_query( '', true ) . '" name="search" id="search_que" class="' . WPSOLR_Option::OPTION_SEARCH_SUGGEST_CLASS_DEFAULT;
	echo ' sfl2" autocomplete="off"/>';
	echo '<input type="submit" value="' . OptionLocalization::get_term( $localization_options, 'search_form_button_label' ) . '" id="searchsubmit" style="position:relative;width:auto">';
	echo '<input type="hidden" value="' . WPSOLR_Service_Container::getOption()->get_search_after_autocomplete_block_submit() . '" id="is_after_autocomplete_block_submit">';
	echo '<input type="hidden" value="' . WPSOLR_Service_Container::get_query()->get_wpsolr_paged() . '" id="paginate">';

	parse_str( parse_url( $url, PHP_URL_QUERY ), $url_params );
	if ( ! empty( $url_params ) && isset( $url_params['lang'] ) ) {
		echo '<input type="hidden" value="' . esc_attr( $url_params['lang'] ) . '" name="lang">';
	}

	// Filter to add fields to the search form
	echo apply_filters( WPSOLR_Events::WPSOLR_FILTER_APPEND_FIELDS_TO_AJAX_SEARCH_FORM, '' );

	echo '<div style="clear:both"></div></div></form>';


	echo '</div>';
	echo "<div class='cls_results'>";

	try {

		try {

			$final_result = WPSOLR_Service_Container::get_solr_client()->display_results( WPSOLR_Service_Container::get_query() );

		} catch ( Exception $e ) {

			$message = $e->getMessage();
			echo "<span class='infor'>$message</span>";
			die();
		}

		if ( $final_result[2] == 0 ) {
			echo "<span class='infor'>" . sprintf( OptionLocalization::get_term( $localization_options, 'results_header_no_results_found' ), WPSOLR_Service_Container::get_query()->get_wpsolr_query( '', true ) ) . "</span>";
		} else {
			echo '<div class="wdm_resultContainer">
                    <div class="wdm_list">';

			// Display sort list UI
			echo WPSOLR_UI_Sort::build(
				WPSOLR_Data_Sort::get_data(
					WPSOLR_Service_Container::getOption()->get_sortby_items_as_array(),
					WPSOLR_Service_Container::getOption()->get_sortby_items_labels(),
					WPSOLR_Service_Container::get_query()->get_wpsolr_sort(),
					$localization_options
				)
			);


			// Display facets UI
			echo '<div id="res_facets">' . WPSOLR_UI_Facets::Build(
					WPSOLR_Data_Facets::get_data(
						WPSOLR_Service_Container::get_query()->get_filter_query_fields_group_by_name(),
						WPSOLR_Service_Container::getOption()->get_facets_to_display(),
						$final_result[1] ),
					$localization_options,
					WPSOLR_Service_Container::getOption()->get_facets_layouts_ids() ) . '</div>';


			echo '</div>
                    <div class="wdm_results">';
			if ( $final_result[0] != '0' ) {
				echo $final_result[0];
			}

			$ui_result_rows = $final_result[3];
			if ( WPSOLR_Service_Container::getOption()->get_search_is_display_results_info() && $ui_result_rows != 0 ) {
				echo '<div class="res_info">' . $final_result[4] . '</div>';
			}

			if ( $ui_result_rows != 0 ) {
				$img = plugins_url( 'images/gif-load.gif', __FILE__ );
				echo '<div class="loading_res"><img src="' . $img . '"></div>';
				echo "<div class='results-by-facets'>";
				foreach ( $ui_result_rows as $resarr ) {
					echo $resarr;
				}
				echo "</div>";
				echo "<div class='paginate_div'>";
				$total         = $final_result[2];
				$number_of_res = WPSOLR_Service_Container::getOption()->get_search_max_nb_results_by_page();
				if ( $total > $number_of_res ) {
					$pages = ceil( $total / $number_of_res );
					echo '<ul id="pagination-flickr" class="wdm_ul">';
					for ( $k = 1; $k <= $pages; $k ++ ) {
						echo "<li ><a class='paginate' href='javascript:void(0)' id='$k'>$k</a></li>";
					}
				}
				echo '</ul></div>';

			}


			echo '</div>';
			echo '</div><div style="clear:both;"></div>';
		}
	} catch
	( Exception $e ) {

		echo sprintf( 'The search could not be performed. An error occured while trying to connect to the Apache Solr server. <br/><br/>%s<br/>', $e->getMessage() );
	}

	echo '</div>';
}


function return_solr_instance() {

	if ( isset( $_POST['security'] ) && wp_verify_nonce( $_POST['security'], WPSOLR_NONCE_FOR_DASHBOARD ) ) {

		$path = plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
		require_once $path;


		$index_engine = isset( $_POST['sindex_engine'] ) ? $_POST['sindex_engine'] : WPSOLR_AbstractEngineClient::ENGINE_SOLR;

		$slabel   = $_POST['slabel'];
		$spath    = $_POST['spath'];
		$port     = $_POST['sport'];
		$host     = $_POST['shost'];
		$username = $_POST['skey'];
		$password = $_POST['spwd'];
		$protocol = $_POST['sproto'];

		$solr_cloud_extra_parameters = [];
		switch ( $index_engine ) {
			case WPSOLR_AbstractEngineClient::ENGINE_SOLR_CLOUD:
				$solr_cloud_extra_parameters['index_solr_cloud_shards']             = isset( $_POST['index_solr_cloud_shards'] ) ? $_POST['index_solr_cloud_shards'] : '2';
				$solr_cloud_extra_parameters['index_solr_cloud_replication_factor'] = isset( $_POST['index_solr_cloud_replication_factor'] ) ? $_POST['index_solr_cloud_replication_factor'] : '2';
				$solr_cloud_extra_parameters['index_solr_cloud_max_shards_node']    = isset( $_POST['index_solr_cloud_max_shards_node'] ) ? $_POST['index_solr_cloud_max_shards_node'] : '2';
				break;

			case WPSOLR_AbstractEngineClient::ENGINE_ELASTICSEARCH:
				$solr_cloud_extra_parameters['index_elasticsearch_shards']   = isset( $_POST['index_elasticsearch_shards'] ) ? $_POST['index_elasticsearch_shards'] : '5';
				$solr_cloud_extra_parameters['index_elasticsearch_replicas'] = isset( $_POST['index_elasticsearch_replicas'] ) ? $_POST['index_elasticsearch_replicas'] : '1';
				break;
		}

		$client = WPSOLR_AbstractSearchClient::create_from_config( [
				'index_engine'     => $index_engine,
				'index_label'      => $slabel,
				'scheme'           => $protocol,
				'host'             => $host,
				'port'             => $port,
				'path'             => $spath,
				'username'         => $username,
				'password'         => $password,
				'timeout'          => WPSOLR_AbstractSearchClient::DEFAULT_SEARCH_ENGINE_TIMEOUT_IN_SECOND,
				'extra_parameters' => [ $index_engine => $solr_cloud_extra_parameters ]
			]
		);

		try {

			$action = isset( $_POST['wpsolr_index_action'] ) ? $_POST['wpsolr_index_action'] : 'wpsolr_index_action_ping';


			if ( 'wpsolr_index_action_delete' === $action ) {

				// Delete the index and it's data.
				$client->admin_delete_index();

			} else {

				// Just trigger an exception if bad ping.
				$client->admin_ping();
			}

		} catch ( Exception $e ) {

			$str_err      = '';
			$solr_code    = $e->getCode();
			$solr_message = $e->getMessage();

			switch ( $e->getCode() ) {

				case 401:
					$str_err .= "<br /><span>The server authentification failed. Please check your user/password (Solr code http $solr_code)</span><br />";
					break;

				case 400:
				case 404:

					$str_err .= "<br /><span>We could not join your Solr server. Your Solr path could be malformed, or your Solr server down (Solr code $solr_code)</span><br />";
					break;

				default:

					// Try to interpret some special errors with code "0"
					if ( ( method_exists( $e, 'getStatusMessage' ) ) && ( strpos( $e->getStatusMessage(), 'Failed to connect' ) > 0 ) && ( strpos( $e->getStatusMessage(), 'Connection refused' ) > 0 ) ) {

						$str_err .= "<br /><span>We could not connect to your Solr server. It's probably because the port is blocked. Please try another port, for instance 443, or contact your hosting provider/network administrator to unblock your port.</span><br />";

					} else {

						// Nothing.
					}

					break;

			}


			echo $str_err;
			echo '<br>';
			//echo htmlentities( $solr_message );
			echo $solr_message;
			die();
		}
	}

	echo 0;
	die();
}

add_action( 'wp_ajax_' . 'return_solr_instance', 'return_solr_instance' );


function return_solr_status() {

	if ( isset( $_POST['security'] ) && wp_verify_nonce( $_POST['security'], WPSOLR_NONCE_FOR_DASHBOARD ) ) {
		echo WPSOLR_Service_Container::get_solr_client()->get_solr_status();
	}

	die();
}

add_action( 'wp_ajax_' . 'return_solr_status', 'return_solr_status' );


function return_solr_results() {

	$final_result = WPSOLR_Service_Container::get_solr_client()->display_results( WPSOLR_Service_Container::get_query() );

	// Add result rows as html
	$res1[] = $final_result[3];

	// Add pagination html
	$total         = $final_result[2];
	$number_of_res = WPSOLR_Service_Container::getOption()->get_search_max_nb_results_by_page();
	$paginat_var   = '';
	if ( $total > $number_of_res ) {
		$pages       = ceil( $total / $number_of_res );
		$paginat_var .= '<ul id="pagination-flickr"class="wdm_ul">';
		for ( $k = 1; $k <= $pages; $k ++ ) {
			$paginat_var .= "<li ><a class='paginate' href='javascript:void(0)' id='$k'>$k</a></li>";
		}
		$paginat_var .= '</ul>';
	}
	$res1[] = $paginat_var;

	// Add results infos html ('showing x to y results out of n')
	$res1[] = $final_result[4];

	// Add facets data
	$res1[] = WPSOLR_UI_Facets::Build(
		WPSOLR_Data_Facets::get_data(
			WPSOLR_Service_Container::get_query()->get_filter_query_fields_group_by_name(),
			WPSOLR_Service_Container::getOption()->get_facets_to_display(),
			$final_result[1] ),
		OptionLocalization::get_options(),
		WPSOLR_Service_Container::getOption()->get_facets_layouts_ids()
	);

	// Output Json response to Ajax call
	echo json_encode( $res1 );


	die();
}

add_action( 'wp_ajax_nopriv_' . 'return_solr_results', 'return_solr_results' );
add_action( 'wp_ajax_' . 'return_solr_results', 'return_solr_results' );

/**
 * Fatal errors not captured by try/catch in Ajax calls.
 */
/**
 * Handler for fatal errors.
 *
 * @param $code
 * @param $message
 * @param $file
 * @param $line
 */
function wpsolr_my_error_handler( $code, $message, $file, $line ) {

	echo wp_json_encode(
		[
			'nb_results'        => 0,
			'status'            => $code,
			'message'           => sprintf( 'Error on line %s of file %s: %s', $line, $file, $message ),
			'indexing_complete' => false,
		]
	);

	die();
}

/**
 * Catch fatal errors, and call the handler.
 */
function wpsolr_fatal_error_shutdown_handler() {

	$last_error = error_get_last();
	if ( E_ERROR === $last_error['type'] ) {
		// fatal error
		wpsolr_my_error_handler( E_ERROR, $last_error['message'], $last_error['file'], $last_error['line'] );
	}
}

/*
 * Ajax call to index Solr documents
 */
function return_solr_index_data() {

	if ( isset( $_POST['security'] ) && wp_verify_nonce( $_POST['security'], WPSOLR_NONCE_FOR_DASHBOARD ) ) {
		try {

			set_error_handler( 'wpsolr_my_error_handler' );
			register_shutdown_function( 'wpsolr_fatal_error_shutdown_handler' );

			// Indice of Solr index to index
			$solr_index_indice = $_POST['solr_index_indice'];

			// Batch size
			$batch_size = intval( $_POST['batch_size'] );

			// nb of document sent until now
			$nb_results = intval( $_POST['nb_results'] );

			// Debug infos displayed on screen ?
			$is_debug_indexing = isset( $_POST['is_debug_indexing'] ) && ( 'true' === $_POST['is_debug_indexing'] );

			// Re-index all the data ?
			$is_reindexing_all_posts = isset( $_POST['is_reindexing_all_posts'] ) && ( 'true' === $_POST['is_reindexing_all_posts'] );

			// Post types to reindex
			$post_types = $_POST['post_types'];

			// Stop indexing ?
			$is_stopping = isset( $_POST['is_stopping'] ) & ( 'true' === $_POST['is_stopping'] ) ? true : false;

			$solr = WPSOLR_IndexSolariumClient::create( $solr_index_indice );

			$current_user = wp_get_current_user();

			// Reset documents if requested
			if ( $is_reindexing_all_posts ) {
				$solr->reset_documents( $current_user->user_email );
			}

			$res_final = $solr->index_data( $is_stopping, $current_user->user_email, WPSOLR_Model_Post_Type::create_models( $post_types ), $batch_size, null, $is_debug_indexing );

			// Increment nb of document sent until now
			$res_final['nb_results'] += $nb_results;

			echo wp_json_encode( $res_final );

		} catch ( Exception $e ) {

			echo wp_json_encode(
				[
					'nb_results'        => 0,
					'status'            => $e->getCode(),
					'message'           => htmlentities( $e->getMessage() ),
					'indexing_complete' => false,
				]
			);

		}
	}

	die();
}

add_action( 'wp_ajax_' . 'return_solr_index_data', 'return_solr_index_data' );


/*
 * Ajax call to clear Solr documents
 */
function return_solr_delete_index() {

	set_error_handler( 'wpsolr_my_error_handler' );
	register_shutdown_function( 'wpsolr_fatal_error_shutdown_handler' );

	if ( isset( $_POST['security'] ) && wp_verify_nonce( $_POST['security'], WPSOLR_NONCE_FOR_DASHBOARD ) ) {
		try {

			// Indice of Solr index to delete
			$solr_index_indice = $_POST['solr_index_indice'];

			// Post types to delete
			$post_types = isset( $_POST['post_types'] ) ? $_POST['post_types'] : null;

			$models = null;
			if ( ! is_null( $post_types ) ) {
				$models = WPSOLR_Model_Post_Type::create_models( $post_types );
			}

			$current_user = wp_get_current_user();

			$solr = WPSOLR_IndexSolariumClient::create( $solr_index_indice );
			$solr->delete_documents( $current_user->user_email, $models );

		} catch ( Exception $e ) {

			echo wp_json_encode(
				[
					'nb_results'        => 0,
					'status'            => $e->getCode(),
					'message'           => htmlentities( $e->getMessage() ),
					'indexing_complete' => false,
				]
			);

		}
	}

	die();
}

add_action( 'wp_ajax_' . 'return_solr_delete_index', 'return_solr_delete_index' );

/**
 * Ajax call to clear an indexing lock
 **/
function wpsolr_ajax_remove_process_lock() {

	try {

		if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], WPSOLR_NONCE_FOR_DASHBOARD ) ) {
			throw new \Exception( 'Unauthorized Ajax call.' );
		}

		// Process to stop/unlock
		$process_id = isset( $_POST['process_id'] ) ? sanitize_text_field( $_POST['process_id'] ) : '';

		WPSOLR_AbstractIndexClient::unlock_process( $process_id );


		echo wp_json_encode(
			[
				'status'  => 0,
				'message' => 'Indexing process has been stopped.',
			]
		);


	} catch ( \Exception $e ) {

		echo wp_json_encode(
			[
				'status'  => $e->getCode(),
				'message' => htmlentities( $e->getMessage() ),
			]
		);
	}

	die();
}

// Ajax to remove locks
add_action( 'wp_ajax_' . 'wpsolr_ajax_remove_process_lock', 'wpsolr_ajax_remove_process_lock' );
