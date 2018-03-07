<?php
/**
 * Page listing all managed Solr accounts for the current user.
 */
?>

<div class="wdm-vertical-tabs-content">
	<div class="wrapper">

		<h4 class='head_div'>
			<span>Select an account</span>

			<div style="float: right;">
				<?php $form_action = sprintf( '?page=%s&tab=%s&subtab=%s', $_GET['page'], $_GET['tab'], $managed_solr_server->get_id() ); ?>
				<form action="<?php echo esc_url( $form_action ); ?>"
				      method="POST">
					<input name="submit-form-logout" type="submit"
					       class="button-primary wdm-save"
					       value="Logout from <?php echo $managed_solr_server->get_label(); ?>"/>
				</form>
			</div>
		</h4>

		<?php

		// Add menu items for all the managed Solr accounts

		$subtabs = array();

		$result_object = $managed_solr_server->call_rest_list_accounts();
		if ( OptionManagedSolrServer::is_response_ok( $result_object ) ) {
			foreach ( $managed_solr_server->get_response_results( $result_object ) as $result ) {
				$subtabs[ $managed_solr_server->get_id() . ':' . $result->uuid ] = $result->label;
			}
		}

		// Display menu
		$subtab = wpsolr_admin_sub_tabs( $subtabs );

		// Display account detail if account appears in parameters
		$subtab_exploded = explode( ':', $subtab );
		if ( count( $subtab_exploded ) >= 2 ) {
			$account_uuid = $subtab_exploded[1];


			$subtab_exploded = explode( ':', $subtab );
			WpSolrExtensions::require_with( WpSolrExtensions::get_option_template_file( WpSolrExtensions::OPTION_MANAGED_SOLR_SERVERS, 'template-my-account-indexes.php' ), array(
				'managed_solr_server' => $managed_solr_server,
				'account_uuid'        => $account_uuid
			) );
		}
		?>

	</div>
</div>
