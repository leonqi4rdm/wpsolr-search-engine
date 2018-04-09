<?php

// Load WPML class
//WpSolrExtensions::load();

use wpsolr\core\classes\services\WPSOLR_Service_Container;

function wdm_return_solr_rows() {
	if ( isset( $_POST['security'] )
	     && wp_verify_nonce( $_POST['security'], 'nonce_for_autocomplete' )
	) {

		$input = isset( $_POST['word'] ) ? $_POST['word'] : '';

		if ( '' != $input ) {

			$input = strtolower( $input );

			try {

				$result = WPSOLR_Service_Container::get_solr_client()->get_suggestions( $input );

				echo json_encode( $result );

			} catch ( Exception $e ) {
				echo json_encode(
					array(
						'message' => htmlentities( $e->getMessage() )
					)
				);
			}
		}

	}

	die();
}

add_action( 'wp_ajax_' . WPSOLR_AJAX_AUTO_COMPLETE_ACTION, WPSOLR_AJAX_AUTO_COMPLETE_ACTION );
add_action( 'wp_ajax_nopriv_' . WPSOLR_AJAX_AUTO_COMPLETE_ACTION, WPSOLR_AJAX_AUTO_COMPLETE_ACTION );

