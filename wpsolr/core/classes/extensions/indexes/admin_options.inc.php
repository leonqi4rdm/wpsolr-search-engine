<?php

use wpsolr\core\classes\engines\WPSOLR_AbstractEngineClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractSearchClient;
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\extensions\managed_solr_servers\OptionManagedSolrServer;
use wpsolr\core\classes\extensions\WpSolrExtensions;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\WPSOLR_Events;

/**
 * Included file to display admin options
 */
global $license_manager;

WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );

// Options name
$option_name = WPSOLR_Option_Indexes::get_option_name( WpSolrExtensions::OPTION_INDEXES );

// Options object
$option_object = new WPSOLR_Option_Indexes();

?>

<?php
global $response_object1, $response_object, $google_recaptcha_site_key, $google_recaptcha_token;
$is_submit_button_form_temporary_index = isset( $_POST['submit_button_form_temporary_index'] );
$form_data                             = WpSolrExtensions::extract_form_data( $is_submit_button_form_temporary_index, array(
		'managed_solr_service_id' => array( 'default_value' => '', 'can_be_empty' => false )
	)
);

?>


<div id="solr-hosting-tab">

	<?php

	// Options data. Loaded after the POST, to be sure it contains the posted data.
	$option_data = WPSOLR_Option_Indexes::get_option_data( WpSolrExtensions::OPTION_INDEXES );

	$subtabs = array();

	// Create the tabs from the Solr indexes already configured
	foreach ( $option_object->get_indexes() as $index_indice => $index ) {
		$subtabs[ $index_indice ] = isset( $index['index_name'] ) ? $index['index_name'] : 'Index with no name';
	}

	if ( count( $option_object->get_indexes() ) <= 0 ) {
		$subtabs[ $option_object->generate_uuid() ] = 'Configure your first index';
	}
	if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_MULTI_INDEX ) ) ) {
		require $file_to_include;
	}

	// Create subtabs on the left side
	$subtab = wpsolr_admin_sub_tabs( $subtabs );

	?>

    <div id="solr-results-options" class="wdm-vertical-tabs-content">

		<?php
		$is_new_index    = ! $option_object->has_index( $subtab );
		$class_collapsed = '';
		if ( $is_new_index ) {

		    // Prevent rare zombie deleted indexes error
			if ( empty( $option_data ) || ! is_array( $option_data ) ) {
				$option_data = [];
			}
			if ( empty( $option_data['solr_indexes'] ) || ! is_array( $option_data['solr_indexes'] ) ) {
				$option_data['solr_indexes'] = [];
			}

			$option_data['solr_indexes'][ $subtab ] = [];
			$class_collapsed                        = $option_object->has_index_type_temporary() ? '' : 'wpsolr_collapsed';

			if ( ! $option_object->has_index_type_temporary() ) {
				// No temporary index yet: display the form to create one.
				WpSolrExtensions::load_file(
					'managed_solr_servers/templates/template-temporary-account-form.php',
					false,
					array(
						'managed_solr_service_id'   => $form_data['managed_solr_service_id']['value'],
						'response_error'            => ( isset( $response_object1 ) && ! OptionManagedSolrServer::is_response_ok( $response_object1 ) ) ? OptionManagedSolrServer::get_response_error_message( $response_object1 ) : '',
						'google_recaptcha_site_key' => isset( $google_recaptcha_site_key ) ? $google_recaptcha_site_key : '',
						'google_recaptcha_token'    => isset( $google_recaptcha_token ) ? $google_recaptcha_token : '',
						'total_nb_indexes'          => $option_object->get_nb_indexes(),
					)
				);
			}
		} else {
			// Verify that current subtab is a Solr index indice.
			if ( ! $option_object->has_index( $subtab ) ) {
				// Use the first subtab element
				$subtab = key( $subtabs );
			}

		}

		?>

		<?php if ( $is_new_index && ! $option_object->has_index_type_temporary() ) {
			?>
            <input type="button" class="button-secondary wpsolr_collapser"
                   value="Connect to your Elasticsearch/Apache Solr server"/>
		<?php } ?>

        <div class="<?php echo $class_collapsed; ?>">
            <form action="options.php" method="POST" id='settings_conf_form'>

				<?php
				settings_fields( $option_name );
				?>

                <input type='hidden' id='adm_path' value='<?php echo admin_url(); ?>'>

				<?php
				foreach ( ( isset( $option_data['solr_indexes'] ) ? $option_data['solr_indexes'] : [] ) as $index_indice => $index ) {

					$is_index_type_temporary = false;
					$is_index_type_managed   = false;
					$is_index_readonly       = false;
					$is_index_in_creation    = false;
					$search_engine_name      = WPSOLR_AbstractSearchClient::ENGINE_SOLR;

					if ( $subtab === $index_indice ) {
						$is_index_in_creation    = $is_new_index;
						$is_index_type_temporary = $option_object->is_index_type_temporary( $option_data['solr_indexes'][ $index_indice ] );
						$is_index_type_managed   = $option_object->is_index_type_managed( $option_data['solr_indexes'][ $index_indice ] );
						$is_index_readonly       = $is_index_type_temporary;
						$search_engine           = $option_object->get_index_search_engine_name( $option_data['solr_indexes'][ $index_indice ] );
						$search_engine_name      = $option_object->get_index_search_engine_name( $option_data['solr_indexes'][ $index_indice ] );

						if ( $is_index_type_temporary ) {
							// Check that the temporary index is still temporary on the server.
							$managed_solr_server = new OptionManagedSolrServer( $option_object->get_index_managed_solr_service_id( $index ) );
							$response_object     = $managed_solr_server->call_rest_get_temporary_solr_index_status( $index_indice );

							if ( OptionManagedSolrServer::is_response_ok( $response_object ) ) {

								$is_index_unknown_on_server = OptionManagedSolrServer::get_response_result( $response_object, 'isUnknown' );

								if ( $is_index_unknown_on_server ) {

									// Change the solr index type to managed
									$option_object->update_index_property( $index_indice, WPSOLR_Option_Indexes::INDEX_TYPE, WPSOLR_Option_Indexes::STORED_INDEX_TYPE_UNMANAGED );

									// Display message
									$response_error = 'This test index has expired and was therefore deleted. You can delete this configuration.';

									// No more readonly therefore
									$is_index_type_temporary = false;
									$is_index_readonly       = false;

								} else {

									$is_index_type_temporary_on_server = OptionManagedSolrServer::get_response_result( $response_object, 'isTemporary' );
									if ( ! $is_index_type_temporary_on_server ) {

										// Change the solr index type to managed
										$option_object->update_index_property( $index_indice, WPSOLR_Option_Indexes::INDEX_TYPE, WPSOLR_Option_Indexes::STORED_INDEX_TYPE_MANAGED );

										// No more readonly therefore
										$is_index_type_temporary = false;
										$is_index_readonly       = false;
									}
								}

							} else {

								$response_error = ( isset( $response_object ) && ! OptionManagedSolrServer::is_response_ok( $response_object ) ) ? OptionManagedSolrServer::get_response_error_message( $response_object ) : '';
							}
						}
					}

					?>

                    <div
                            id="<?php echo $subtab != $index_indice ? $index_indice : "current_index_configuration_edited_id" ?>"
                            class="wrapper" <?php echo ( $subtab != $index_indice ) ? "style='display:none'" : "" ?> >

                        <input type='hidden'
                               name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][managed_solr_service_id]"
							<?php echo $subtab === $index_indice ? "id='managed_solr_service_id'" : "" ?>
                               value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['managed_solr_service_id'] ) ? '' : $option_data['solr_indexes'][ $index_indice ]['managed_solr_service_id']; ?>">
                        <input type='hidden'
                               name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_type]"
							<?php echo $subtab === $index_indice ? "id='index_type'" : "" ?>
                               value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_type'] ) ? '' : $option_data['solr_indexes'][ $index_indice ]['index_type']; ?>">

                        <h4 class='head_div'>
							<?php echo $is_index_type_temporary
								? 'This is your temporary (2 hours) index configuration for testing'
								: ( $is_index_type_managed
									? sprintf( 'This is your index configuration managed by %s', $option_object->get_index_managed_solr_service_id( $option_data['solr_indexes'][ $index_indice ] ) )
									: sprintf( 'Connect to your index', $search_engine_name ) );
							?>
                        </h4>

						<?php
						if ( $is_new_index ) {
							?>
                            <div class="wdm_note show_engine_solr show_engine_solr_cloud hide_engine_elasticsearch">

                                Important ! You must first have:
                                <ol>
                                    <li>
                                        <a
                                                href="<?php echo $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/install-apache-solr/' ); ?>"
                                                target="__wpsolr">Installed</a> your Apache Solr or SolrCloud server,
                                        or get one <a
                                                href="<?php echo $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/apache-solr-hosting/' ); ?>"
                                                target="__wpsolr">hosted</a>
                                    </li>
                                </ol>

                                WPSOLR is compatible with Apache Solr 5.5 and above.

                                Set your Solr index properties here, then save it.

                                <ol>
                                    <li>SolrCloud : if the index name does not exist on this Solr server, it will be
                                        automatically created with uploaded WPSOLR's configuration files as configset.
                                    </li>
                                    <li>Solr : easy to follow instructions will show you how to upload the WPSOLR's
                                        configuration files to your Solr server. Then the index will be created for you.
                                    </li>
                                    <li>Else, the index is not updated.</li>
                                </ol>

                                In all cases, the index connectivity will be tested: a green icon displayed with
                                success, a
                                red error message else.

                            </div>

                            <div class="wdm_note hide_engine_solr hide_engine_solr_cloud show_engine_elasticsearch"
                                 style="display:none">

                                Important ! You must first have:
                                <ol>
                                    <li>
                                        <a
                                                href="<?php echo $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/install-elasticsearch/' ); ?>"
                                                target="__wpsolr">Installed</a> your Elasticsearch server,
                                        or get one <a
                                                href="<?php echo $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/guide/configuration-step-by-step-schematic/elasticsearch-hosting/' ); ?>"
                                                target="__wpsolr">hosted</a>
                                    </li>
                                </ol>

                                WPSOLR is compatible with Elasticsearch 5.0 and above.

                                Set your Elasticsearch index properties here, then save it.

                                <ol>
                                    <li>If the index name does not exist on this Elasticsearch server, it will be
                                        created
                                        with WPSOLR's mappings "wpsolr_types" (dynamic templates and fields).
                                    </li>
                                    <li>If the index name exists on this Elasticsearch server, but has no mappings
                                        "wpsolr_types", his mappings will be updated with WPSOLR's mappings
                                        "wpsolr_types"
                                        (dynamic templates and fields).
                                    </li>
                                    <li>Else, the index is not updated.</li>
                                </ol>

                                In all cases, the index connectivity will be tested: a green icon displayed with
                                success, a
                                red error message else.

                            </div>
							<?php
						}
						?>

                        <div class="wdm_row">
                            <h4 class="solr_error" <?php echo $subtab != $index_indice ? "style='display:none'" : "" ?> >
								<?php
								if ( ! empty( $response_error ) ) {
									echo $response_error;
								}
								?>
                            </h4>
                        </div>

                        <div class="wdm_row">
                            <div class='col_left'>Search engine</div>

                            <div class='col_right'>
								<?php
								$is_engine_solr          = empty( $option_data['solr_indexes'][ $index_indice ][ WPSOLR_AbstractEngineClient::ENGINE ] )
								                           || ( WPSOLR_AbstractEngineClient::ENGINE_SOLR === $option_data['solr_indexes'][ $index_indice ][ WPSOLR_AbstractEngineClient::ENGINE ] )
								                           || ( WPSOLR_AbstractEngineClient::ENGINE_SOLR_CLOUD === $option_data['solr_indexes'][ $index_indice ][ WPSOLR_AbstractEngineClient::ENGINE ] );
								$search_engine           = empty( $option_data['solr_indexes'][ $index_indice ][ WPSOLR_AbstractEngineClient::ENGINE ] ) ? '' : $option_data['solr_indexes'][ $index_indice ][ WPSOLR_AbstractEngineClient::ENGINE ];
								$is_engine_solr_cloud    = ( $search_engine === WPSOLR_AbstractEngineClient::ENGINE_SOLR_CLOUD );
								$is_engine_elasticsearch = ( $search_engine === WPSOLR_AbstractEngineClient::ENGINE_ELASTICSEARCH );
								?>
								<?php if ( ! $is_index_readonly && $is_index_in_creation ) { ?>
                                    <select id="index_engine"
                                            name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][<?php echo WPSOLR_AbstractEngineClient::ENGINE; ?>]"
                                    >
                                        <option value="<?php echo WPSOLR_AbstractEngineClient::ENGINE_SOLR; ?>" <?php selected( $search_engine, WPSOLR_AbstractEngineClient::ENGINE_SOLR ); ?>>
                                            Apache Solr
                                        </option>
                                        <option value="<?php echo WPSOLR_AbstractEngineClient::ENGINE_SOLR_CLOUD; ?>" <?php selected( $search_engine, WPSOLR_AbstractEngineClient::ENGINE_SOLR_CLOUD ); ?>>
                                            Apache SolrCloud
                                        </option>
                                        <option
                                                value="<?php echo WPSOLR_AbstractEngineClient::ENGINE_ELASTICSEARCH; ?>" <?php selected( $search_engine, WPSOLR_AbstractEngineClient::ENGINE_ELASTICSEARCH ); ?>>
                                            Elasticsearch
                                        </option>
                                    </select>

                                    Choose between two giants: Apache Solr, or Elasticsearch.

								<?php } else { ?>
                                    <input type='hidden'
                                           name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_engine]"
										<?php echo ( $subtab === $index_indice ) ? "id='index_engine'" : "" ?>
                                           value="<?php echo $search_engine ?>"
                                    >
									<?php echo $search_engine_name; ?>
								<?php } ?>

                                <div class="clear"></div>
                            </div>
                            <div class="clear"></div>
                        </div>

                        <div class="wdm_row">
                            <div class='col_left'>WPSOLR index name</div>

                            <div class='col_right'><input
                                        type='text' <?php echo $is_index_readonly ? 'readonly' : ''; ?>
                                        placeholder="Give a label to your index"
                                        name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_name]"
									<?php echo $subtab === $index_indice ? "id='index_name'" : "" ?>
                                        value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_name'] ) ? '' : $option_data['solr_indexes'][ $index_indice ]['index_name']; ?>">

                                <p>The name of your index in WPSOLR.</p>
                                <div class="clear"></div>
								<?php if ( $subtab === $index_indice ) { ?>
                                    <span class='name_err'></span>
								<?php } ?>
                            </div>
                            <div class="clear"></div>
                        </div>

                        <div class="wdm_row hide_engine_solr hide_engine_solr_cloud show_engine_elasticsearch" <?php echo $is_engine_solr ? 'style="display:none;"' : ''; ?>>
                            <div class='col_left'>Elasticsearch index name</div>
                            <div class='col_right'>
                                <input class="wpsolr-remove-if-empty" type='text'
                                       type='text' <?php echo $is_index_readonly ? 'readonly' : ''; ?>
                                       placeholder="Index name in Elasticsearch."
                                       name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_label]"
									<?php echo $subtab === $index_indice ? "id='index_label'" : "" ?>
                                       value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_label'] ) ? '' : $option_data['solr_indexes'][ $index_indice ]['index_label']; ?>">

                                <p>The name of your index in Elasticsearch. If the index does not exist yes, it will be
                                    created. The index will be configured automatically with mappings and analysis.</p>
                                <div class="clear"></div>
								<?php if ( $subtab === $index_indice ) { ?>
                                    <span class='label_err'></span>
								<?php } ?>
                            </div>
                            <div class="clear"></div>
                        </div>

                        <div class="wdm_row">
                            <div class='col_left'>Server Protocol</div>
                            <div class='col_right'>
								<?php if ( ! $is_index_readonly ) { ?>
                                    <select
                                            name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_protocol]"
										<?php echo $subtab === $index_indice ? "id='index_protocol'" : "" ?>
                                    >
                                        <option
                                                value='http' <?php selected( 'http', empty( $option_data['solr_indexes'][ $index_indice ]['index_protocol'] ) ? 'http' : $option_data['solr_indexes'][ $index_indice ]['index_protocol'] ) ?>>
                                            http
                                        </option>
                                        <option
                                                value='https' <?php selected( 'https', empty( $option_data['solr_indexes'][ $index_indice ]['index_protocol'] ) ? 'http' : $option_data['solr_indexes'][ $index_indice ]['index_protocol'] ) ?>>
                                            https
                                        </option>
                                    </select>
								<?php } else { ?>
                                    <input type='text' type='text' readonly
                                           name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_protocol]"
										<?php echo $subtab === $index_indice ? "id='index_protocol'" : "" ?>
                                           value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_protocol'] ) ? '' : $option_data['solr_indexes'][ $index_indice ]['index_protocol']; ?>">
								<?php } ?>

                                <div class="clear"></div>
								<?php if ( $subtab === $index_indice ) { ?>
                                    <span class='protocol_err'></span>
								<?php } ?>
                            </div>
                            <div class="clear"></div>
                        </div>

                        <div class="wdm_row">
                            <div class='col_left'>Server Host</div>
                            <div class='col_right'>
                                <input type='text'
                                       class="wpsolr_blur" <?php echo $is_index_readonly ? 'readonly' : ''; ?>
                                       placeholder="localhost or ip adress or hostname. No 'http', no '/', no ':'"
                                       name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_host]"
									<?php echo $subtab === $index_indice ? "id='index_host'" : "" ?>
                                       value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_host'] ) ? '' : $option_data['solr_indexes'][ $index_indice ]['index_host']; ?>">

                                <div class="clear"></div>
								<?php if ( $subtab === $index_indice ) { ?>
                                    <span class='host_err'></span>
								<?php } ?>
                            </div>
                            <div class="clear"></div>
                        </div>

                        <div class="wdm_row">
                            <div class='col_left'>Server Port</div>
                            <div class='col_right'>
                                <input type="text" type='text' <?php echo $is_index_readonly ? 'readonly' : ''; ?>
                                       placeholder="8983 for Apache Solr, 9200 for Elasticsearch, or 443 for https, or any other port"
                                       name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_port]"
									<?php echo $subtab === $index_indice ? "id='index_port'" : "" ?>
                                       value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_port'] ) ? '' : $option_data['solr_indexes'][ $index_indice ]['index_port']; ?>">

                                <div class="clear"></div>
								<?php if ( $subtab === $index_indice ) { ?>
                                    <span class='port_err'></span>
								<?php } ?>
                            </div>
                            <div class="clear"></div>
                        </div>

                        <div class="wdm_row show_engine_solr hide_engine_elasticsearch" <?php echo ! $is_engine_solr ? 'style="display:none;"' : ''; ?>>
                            <div class='col_left'>Solr index path</div>
                            <div class='col_right'>
                                <input class="wpsolr-remove-if-empty" type='text'
                                       type='text' <?php echo $is_index_readonly ? 'readonly' : ''; ?>
                                       placeholder="For instance /solr/index_name. Begins with '/', no '/' at the end"
                                       name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_path]"
									<?php echo $subtab === $index_indice ? "id='index_path'" : "" ?>
                                       value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_path'] ) ? '' : $option_data['solr_indexes'][ $index_indice ]['index_path']; ?>">

                                <div class="clear"></div>
								<?php if ( $subtab === $index_indice ) { ?>
                                    <span class='path_err'></span>
								<?php } ?>
                            </div>
                            <div class="clear"></div>
                        </div>

                        <div class="wdm_row hide_engine_solr hide_engine_solr_cloud show_engine_elasticsearch" <?php echo ! $is_engine_elasticsearch ? 'style="display:none;"' : ''; ?>>
                            <div class='col_left'>Number of shards</div>
                            <div class='col_right'>
                                <input class="wpsolr-remove-if-empty"
                                       type='text' <?php echo ( $is_index_readonly || ! $is_new_index ) ? 'readonly' : ''; ?>
                                       name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_elasticsearch_shards]"
									<?php echo $subtab === $index_indice ? "id='index_elasticsearch_shards'" : "" ?>
                                       value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_elasticsearch_shards'] ) ? '5' : $option_data['solr_indexes'][ $index_indice ]['index_elasticsearch_shards']; ?>">

                                <div class="clear"></div>
								<?php if ( $subtab === $index_indice ) { ?>
                                    <span class='index_elasticsearch_shards_err'></span>
								<?php } ?>
                            </div>
                            <div class="clear"></div>
                        </div>

                        <div class="wdm_row hide_engine_solr hide_engine_solr_cloud show_engine_elasticsearch" <?php echo ! $is_engine_elasticsearch ? 'style="display:none;"' : ''; ?>>
                            <div class='col_left'>Number of replicas</div>
                            <div class='col_right'>
                                <input class="wpsolr-remove-if-empty"
                                       type='text' <?php echo ( $is_index_readonly || ! $is_new_index ) ? 'readonly' : ''; ?>
                                       name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_elasticsearch_replicas]"
									<?php echo $subtab === $index_indice ? "id='index_elasticsearch_replicas'" : "" ?>
                                       value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_elasticsearch_replicas'] ) ? '1' : $option_data['solr_indexes'][ $index_indice ]['index_elasticsearch_replicas']; ?>">

                                <div class="clear"></div>
								<?php if ( $subtab === $index_indice ) { ?>
                                    <span class='index_elasticsearch_replicas_err'></span>
								<?php } ?>
                            </div>
                            <div class="clear"></div>
                        </div>

                        <div class="wdm_row hide_engine_solr show_engine_solr_cloud hide_engine_elasticsearch" <?php echo ! $is_engine_solr_cloud ? 'style="display:none;"' : ''; ?>>
                            <div class='col_left'>Number of shards</div>
                            <div class='col_right'>
                                <input class="wpsolr-remove-if-empty"
                                       type='text' <?php echo ( $is_index_readonly || ! $is_new_index ) ? 'readonly' : ''; ?>
                                       name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_solr_cloud_shards]"
									<?php echo $subtab === $index_indice ? "id='index_solr_cloud_shards'" : "" ?>
                                       value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_solr_cloud_shards'] ) ? '1' : $option_data['solr_indexes'][ $index_indice ]['index_solr_cloud_shards']; ?>">

                                <div class="clear"></div>
								<?php if ( $subtab === $index_indice ) { ?>
                                    <span class='index_solr_cloud_shards_err'></span>
								<?php } ?>
                            </div>
                            <div class="clear"></div>
                        </div>

                        <div class="wdm_row hide_engine_solr show_engine_solr_cloud hide_engine_elasticsearch" <?php echo ! $is_engine_solr_cloud ? 'style="display:none;"' : ''; ?>>
                            <div class='col_left'>Replication Factor</div>
                            <div class='col_right'>
                                <input class="wpsolr-remove-if-empty"
                                       type='text' <?php echo ( $is_index_readonly || ! $is_new_index ) ? 'readonly' : ''; ?>
                                       name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_solr_cloud_replication_factor]"
									<?php echo $subtab === $index_indice ? "id='index_solr_cloud_replication_factor'" : "" ?>
                                       value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_solr_cloud_replication_factor'] ) ? '1' : $option_data['solr_indexes'][ $index_indice ]['index_solr_cloud_replication_factor']; ?>">

                                <div class="clear"></div>
								<?php if ( $subtab === $index_indice ) { ?>
                                    <span class='index_solr_cloud_replication_factor_err'></span>
								<?php } ?>
                            </div>
                            <div class="clear"></div>
                        </div>

                        <div class="wdm_row hide_engine_solr show_engine_solr_cloud hide_engine_elasticsearch" <?php echo ! $is_engine_solr_cloud ? 'style="display:none;"' : ''; ?>>
                            <div class='col_left'>Max shards per node</div>
                            <div class='col_right'>
                                <input class="wpsolr-remove-if-empty"
                                       type='text' <?php echo ( $is_index_readonly || ! $is_new_index ) ? 'readonly' : ''; ?>
                                       name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_solr_cloud_max_shards_node]"
									<?php echo $subtab === $index_indice ? "id='index_solr_cloud_max_shards_node'" : "" ?>
                                       value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_solr_cloud_max_shards_node'] ) ? '1' : $option_data['solr_indexes'][ $index_indice ]['index_solr_cloud_max_shards_node']; ?>">

                                <div class="clear"></div>
								<?php if ( $subtab === $index_indice ) { ?>
                                    <span class='index_solr_cloud_max_shards_node_err'></span>
								<?php } ?>
                            </div>
                            <div class="clear"></div>
                        </div>

                        <div class="wdm_row">
                            <div class='col_left'>Key</div>
                            <div class='col_right'>
                                <input type='text' type='text' <?php echo $is_index_readonly ? 'readonly' : ''; ?>
                                       placeholder="Optional security user if the index is protected with Http Basic Authentication"
                                       name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_key]"
									<?php echo $subtab === $index_indice ? "id='index_key'" : "" ?>
                                       value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_key'] ) ? '' : $option_data['solr_indexes'][ $index_indice ]['index_key']; ?>">

                                <div class="clear"></div>
								<?php if ( $subtab === $index_indice ) { ?>
                                    <span class='key_err'></span>
								<?php } ?>
                            </div>
                            <div class="clear"></div>
                        </div>

                        <div class="wdm_row">
                            <div class='col_left'>Secret</div>
                            <div class='col_right'>
                                <input type='password'
                                       class="wpsolr_password" <?php echo $is_index_readonly ? 'readonly' : ''; ?>
                                       placeholder="Optional security password if the index is protected with Http Basic Authentication"
                                       name="<?php echo $option_name ?>[solr_indexes][<?php echo $index_indice ?>][index_secret]"
									<?php echo $subtab === $index_indice ? "id='index_secret'" : "" ?>
                                       value="<?php echo empty( $option_data['solr_indexes'][ $index_indice ]['index_secret'] ) ? '' : $option_data['solr_indexes'][ $index_indice ]['index_secret']; ?>">
                                <br/><input type="checkbox" class="wpsolr_password_toggle"/> Show the secret

                                <div class="clear"></div>
								<?php if ( $subtab === $index_indice ) { ?>
                                    <span class='sec_err'></span>
								<?php } ?>
                            </div>
                            <div class="clear"></div>
                        </div>

						<?php
						// Display managed offers links
						if ( $is_index_type_temporary ) {
							?>

                            <div class='col_right' style='width:90%'>

								<?php
								$managed_solr_service_id = $option_object->get_index_managed_solr_service_id( $option_data['solr_indexes'][ $index_indice ] );

								$OptionManagedSolrServer = new OptionManagedSolrServer( $managed_solr_service_id );
								foreach ( $OptionManagedSolrServer->generate_convert_orders_urls( $index_indice ) as $managed_solr_service_orders_url ) {
									?>

                                    <input name="gotosolr_plan_yearly_trial"
                                           type="button" class="button-primary"
                                           value="<?php echo $managed_solr_service_orders_url[ OptionManagedSolrServer::MANAGED_SOLR_SERVICE_ORDER_URL_BUTTON_LABEL ]; ?>"
                                           onclick="window.open('<?php echo $managed_solr_service_orders_url[ OptionManagedSolrServer::MANAGED_SOLR_SERVICE_ORDER_URL_LINK ]; ?>', '__blank');"
                                    />

									<?php


									//echo $managed_solr_service_orders_url[ OptionManagedSolrServer::MANAGED_SOLR_SERVICE_ORDER_URL_TEXT ];

								}
								?>

                            </div>
                            <div class="clear"></div>

							<?php
						}
						?>


                    </div>
				<?php } // end foreach ?>

                <div class="wdm_row">
                    <div class="submit">
                        <input name="check_solr_status" id='check_index_status' type="button"
                               class="button-primary wdm-save"
                               value="Check the index status, then Save this configuration"/>
                        <span>
                            <div class='img-load'></div>

                                             <img
                                                     src='<?php echo WPSOLR_DEFINE_PLUGIN_DIR_URL . '/images/success.png'; ?>'
                                                     style='height:18px;width:18px;margin-top: 10px;display: none'
                                                     class='img-succ'/>
                                                <img
                                                        src='<?php echo WPSOLR_DEFINE_PLUGIN_DIR_URL . '/images/warning.png'; ?>'
                                                        style='height:18px;width:18px;margin-top: 10px;display: none'
                                                        class='img-err'/>
					</span>
                    </div>

					<?php if ( ! $is_new_index ) { ?>
                        <input name="delete_index_configuration" id='delete_index_configuration' type="button"
                               class="button-secondary wdm-delete"
                               value="Delete this configuration"/>
                        <input name="delete_index" id='delete_index' type="checkbox" class="wpsolr_collapser"/>
                        Delete the index
                        <span class="wpsolr_collapsed" style="color: red">
                            => Warning!! Selecting this checkbox will also delete the index on the Solr server, and all its content. No way to get the index back after you click on the delete button.
                        </span>
					<?php } // end if ?>

                </div>
                <div class="clear"></div>

            </form>
        </div>
    </div>

</div>
