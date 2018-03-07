<?php
/**
 * Page listing all managed Solr account's indexes.
 */
?>

<div class="wdm-vertical-tabs-content">
	<div class="wrapper">

		<h4 class='head_div'>Select an index</h4>

		<?php

		// Add menu items for all the managed Solr account's indexes

		$subtabs = array();

		$result_object = $managed_solr_server->call_rest_account_indexes( $account_uuid );
		if ( OptionManagedSolrServer::is_response_ok( $result_object ) ) {
			foreach ( $managed_solr_server->get_response_results( $result_object ) as $result ) {
				$subtabs[ $managed_solr_server->get_id() . ':' . $result->uuid . ':' . $result->uuid ] = $result->label;
			}
		}

		// Display menu
		$subtab_composed = wpsolr_admin_sub_tabs( $subtabs );

		// Display index detail if index appears in parameters
		$subtab_exploded = explode( ':', $subtab_composed );
		if ( count( $subtab_exploded ) >= 3 ) {
			$subtab = $subtab_exploded[2];
		}

		?>

	</div>
</div>
