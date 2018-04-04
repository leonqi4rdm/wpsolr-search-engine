<div id="solr-option-tab">

	<?php

	use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
	use wpsolr\core\classes\extensions\WpSolrExtensions;
	use wpsolr\core\classes\models\WPSOLR_Model_Post_Type;
	use wpsolr\core\classes\services\WPSOLR_Service_Container;
	use wpsolr\core\classes\utilities\WPSOLR_Help;
	use wpsolr\core\classes\utilities\WPSOLR_Option;
	use wpsolr\core\classes\WPSOLR_Events;
	use wpsolr\core\classes\WpSolrSchema;

	$subtabs = [
		'result_opt'           => '2.1 Settings',
		'index_opt'            => '2.2 Indexed data',
		'field_opt'            => '2.3 Search fields boosts',
		'facet_opt'            => '2.4 Results facets',
		'sort_opt'             => '2.5 Results sort',
		'localization_options' => '2.6 Localization',
	];

	$subtab              = wpsolr_admin_sub_tabs( $subtabs );

	switch ( $subtab ) {
		case 'result_opt':

			WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
			$option_indexes = new WPSOLR_Option_Indexes();
			$solr_indexes   = $option_indexes->get_indexes();

			?>
            <div id="solr-results-options" class="wdm-vertical-tabs-content">
                <form action="options.php" method="POST" id='res_settings_form'>
					<?php
					settings_fields( 'solr_res_options' );
					$solr_res_options = WPSOLR_Service_Container::getOption()->get_option_search();

					?>

                    <div class='wrapper'>
                        <h4 class='head_div'>Result Options</h4>

                        <div class="wdm_note">

                            In this section, you will choose how to display the results returned by a
                            query to your Solr instance.

                        </div>
                        <div class="wdm_row">
                            <div class='col_left'>
                                Replace WordPress default search by WPSOLR's.<br/><br/>
                            </div>
                            <div class='col_right'>
                                <input type='checkbox' name='wdm_solr_res_data[default_search]'
                                       value='1'
									<?php checked( '1', isset( $solr_res_options['default_search'] ) ? $solr_res_options['default_search'] : '0' ); ?>>
                                Check this option only after tabs 0-3 are completed. The WordPress search will
                                then be replaced with WPSOLR. <br/><br/>
                                Warning: permalinks must be activated.
                            </div>
                            <div class="clear"></div>
                        </div>
                        <div class="wdm_row">
                            <div class='col_left'>Search with this search engine index<br/>

                            </div>
                            <div class='col_right'>
                                <select name='wdm_solr_res_data[default_solr_index_for_search]'>
									<?php
									// Empty option
									echo sprintf( "<option value='%s' %s>%s</option>",
										'',
										'',
										'Your search is not managed by a search engine index. Please select one here.'
									);

									foreach (
										$solr_indexes as $solr_index_indice => $solr_index
									) {

										echo sprintf( "
											<option value='%s' %s>%s</option>
											",
											$solr_index_indice,
											selected( $solr_index_indice, isset( $solr_res_options['default_solr_index_for_search'] ) ?
												$solr_res_options['default_solr_index_for_search'] : '' ),
											isset( $solr_index['index_name'] ) ? $solr_index['index_name'] : 'Unnamed
											Solr index' );

									}
									?>
                                </select>

                            </div>
                            <div class="clear"></div>
                        </div>

						<?php
						if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_MULTI_SITE ) ) ) {
							require_once $file_to_include;
						}
						?>

						<?php
						if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_TEMPLATE ) ) ) {
							require_once $file_to_include;
						}
						?>

						<?php
						if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_PAGE_SLUG ) ) ) {
							require_once $file_to_include;
						}
						?>

                        <div class="wdm_row">
                            <div class='col_left'>Do not load WPSOLR front-end css.<br/>You can then use
                                your
                                own theme css.
                            </div>
                            <div class='col_right'>
								<?php $is_prevent_loading_front_end_css = isset( $solr_res_options['is_prevent_loading_front_end_css'] ) ? '1' : '0'; ?>
                                <input type='checkbox'
                                       name='wdm_solr_res_data[is_prevent_loading_front_end_css]'
                                       value='1'
									<?php checked( '1', $is_prevent_loading_front_end_css ); ?>>
                            </div>
                            <div class="clear"></div>
                        </div>

						<?php
						if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_INFINITE_SCROLL ) ) ) {
							require_once $file_to_include;
						}
						?>

						<?php
						if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_SUGGESTIONS ) ) ) {
							require_once $file_to_include;
						}
						?>

						<?php
						if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_SUGGESTIONS_JQUERY_SELECTOR ) ) ) {
							require_once $file_to_include;
						}
						?>

                        <div class="wdm_row">
                            <div class='col_left'>Do not automatically trigger the search, when a user
                                clicks on the
                                autocomplete list
                            </div>
                            <div class='col_right'>
								<?php $is_after_autocomplete_block_submit = isset( $solr_res_options['is_after_autocomplete_block_submit'] ) ? '1' : '0'; ?>
                                <input type='checkbox'
                                       name='wdm_solr_res_data[is_after_autocomplete_block_submit]'
                                       value='1'
									<?php checked( '1', $is_after_autocomplete_block_submit ); ?>>
                            </div>
                            <div class="clear"></div>
                        </div>

						<?php
						if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_DID_YOU_MEAN ) ) ) {
							require_once $file_to_include;
						}
						?>

                        <div class="wdm_row">
                            <div class='col_left'>Display number of results and current page</div>
                            <div class='col_right'>
                                <input type='checkbox' name='wdm_solr_res_data[res_info]'
                                       value='res_info'
									<?php checked( 'res_info', isset( $solr_res_options['res_info'] ) ? $solr_res_options['res_info'] : '?' ); ?>>
                            </div>
                            <div class="clear"></div>
                        </div>
                        <div class="wdm_row">
                            <div class='col_left'>No. of results per page</div>
                            <div class='col_right'>
                                <input type='text' id='number_of_res' name='wdm_solr_res_data[no_res]'
                                       placeholder="Enter a Number"
                                       value="<?php echo empty( $solr_res_options['no_res'] ) ? '20' : $solr_res_options['no_res']; ?>">
                                <span class='res_err'></span><br>
                            </div>
                            <div class="clear"></div>
                        </div>
                        <div class="wdm_row">
                            <div class='col_left'>No. of values to be displayed by filters</div>
                            <div class='col_right'>
                                <input type='text' id='number_of_fac' name='wdm_solr_res_data[no_fac]'
                                       placeholder="Enter a Number"
                                       value="<?php echo ( isset( $solr_res_options['no_fac'] ) && ( '' !== trim( $solr_res_options['no_fac'] ) ) ) ? $solr_res_options['no_fac'] : '20'; ?>"><span
                                        class='fac_err'></span>
                                0 for unlimited values
                            </div>
                            <div class="clear"></div>
                        </div>
                        <div class="wdm_row">
                            <div class='col_left'>Maximum size of each snippet text in results</div>
                            <div class='col_right'>
                                <input type='text' id='highlighting_fragsize'
                                       name='wdm_solr_res_data[highlighting_fragsize]'
                                       placeholder="Enter a Number"
                                       value="<?php echo empty( $solr_res_options['highlighting_fragsize'] ) ? '100' : $solr_res_options['highlighting_fragsize']; ?>"><span
                                        class='highlighting_fragsize_err'></span> <br>
                            </div>
                            <div class="clear"></div>
                        </div>
                        <div class="wdm_row">
                            <div class='col_left'>Use partial keyword matches in results</div>
                            <div class='col_right'>
                                <input type='checkbox' class='wpsolr_checkbox_mono_wpsolr_is_partial'
                                       name='wdm_solr_res_data[<?php echo WPSOLR_Option::OPTION_SEARCH_ITEM_IS_PARTIAL_MATCHES; ?>]'
                                       value='1'
									<?php checked( isset( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_IS_PARTIAL_MATCHES ] ) ); ?>>
                                Warning: this will hurt both search performance and search accuracy !
                                <p>This adds '*' to all keywords.
                                    For instance, 'search apache' will return results
                                    containing 'searching apachesolr'</p>
                            </div>
                            <div class="clear"></div>
                        </div>
                        <div class="wdm_row">
                            <div class='col_left'>Use fuzzy keyword matches in results</div>
                            <div class='col_right'>
                                <input type='checkbox' class='wpsolr_checkbox_mono_wpsolr_is_partial other'
                                       name='wdm_solr_res_data[<?php echo WPSOLR_Option::OPTION_SEARCH_ITEM_IS_FUZZY_MATCHES; ?>]'
                                       value='1'
									<?php checked( isset( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_ITEM_IS_FUZZY_MATCHES ] ) ); ?>>
                                See <a
                                        href="https://cwiki.apache.org/confluence/display/solr/The+Standard+Query+Parser#TheStandardQueryParser-FuzzySearches"
                                        target="_new">Fuzzy description at Solr wiki</a>
                                <p>The search 'roam' will match terms like roams, foam, & foams. It will
                                    also
                                    match the word "roam" itself.</p>
                            </div>
                            <div class="clear"></div>
                        </div>
                        <div class='wdm_row'>
                            <div class="submit">
                                <input name="save_selected_options_res_form"
                                       id="save_selected_res_options_form" type="submit"
                                       class="button-primary wdm-save" value="Save Options"/>


                            </div>
                        </div>
                    </div>

                </form>
            </div>
			<?php
			break;

		case 'index_opt':

			$custom_fields_error_message = '';

			$posts = [ 'post', 'page', 'product' ];
			if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_INDEXING_POST_TYPES ) ) ) {
				require_once $file_to_include;
			}
			//$posts[] = ( new WPSOLR_Model_User() )->get_table_name();
			//$posts[] = ( new WPSOLR_Model_BP_Profile_Data() )->get_table_name();

			$args       = []; // Need everything
			$output     = 'names'; // or objects
			$operator   = 'and'; // 'and' or 'or'
			$taxonomies = get_taxonomies( $args, $output, $operator );
			global $wpdb;
			$keys = $wpdb->get_col( "
                                                                    SELECT distinct meta_key
                                                                    FROM $wpdb->postmeta
                                                                    WHERE meta_key!='bwps_enable_ssl' 
                                                                    ORDER BY meta_key" );

			try {// Filter custom fields to be indexed.
				$keys = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INDEX_CUSTOM_FIELDS, $keys );
			} catch ( Exception $e ) {
				$custom_fields_error_message = $e->getMessage();
			}

			$post_types = [];
			foreach ( $posts as $ps ) {
				if ( $ps != 'attachment' && $ps != 'revision' && $ps != 'nav_menu_item' ) {
					array_push( $post_types, $ps );
				}
			}

			$allowed_attachments_types = get_allowed_mime_types();

			WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
			$option_indexes = new WPSOLR_Option_Indexes();
			$solr_indexes   = $option_indexes->get_indexes();
			?>

            <div id="solr-indexing-options" class="wdm-vertical-tabs-content">
                <form action="options.php" method="POST" id='settings_form'>
					<?php
					settings_fields( 'solr_form_options' );
					$solr_options = WPSOLR_Service_Container::getOption()->get_option_index();
					?>


                    <div class='indexing_option wrapper'>
                        <h4 class='head_div'>Indexing Options</h4>

                        <div class="wdm_note">

                            In this section, you will choose among all the data stored in your Wordpress
                            site, which you want to load in your Solr index.

                        </div>

						<?php
						if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_INDEXING_STOP_REAL_TIME ) ) ) {
							require_once $file_to_include;
						}
						?>

                        <div class="wdm_row">
                            <div class='col_left'>
                                Index post excerpt.<br/>
                                Excerpt will be added to the post content, and be searchable, highlighted,
                                and
                                autocompleted.
                            </div>
                            <div class='col_right'>
                                <input type='checkbox' name='wdm_solr_form_data[p_excerpt]'
                                       value='1' <?php checked( '1', isset( $solr_options['p_excerpt'] ) ? $solr_options['p_excerpt'] : '' ); ?>>

                            </div>
                            <div class="clear"></div>
                        </div>
                        <div class="wdm_row">
                            <div class='col_left'>
                                Index custom fields and categories.<br/>
                                Custom fields and categories will be added to the post content, and be
                                searchable, highlighted,
                                and
                                autocompleted.
                            </div>
                            <div class='col_right'>
                                <input type='checkbox' name='wdm_solr_form_data[p_custom_fields]'
                                       value='1' <?php checked( '1', isset( $solr_options['p_custom_fields'] ) ? $solr_options['p_custom_fields'] : '' ); ?>>

                            </div>
                            <div class="clear"></div>
                        </div>
                        <div class="wdm_row">
                            <div class='col_left'>
                                Expand shortcodes of post content before indexing.<br/>
                                Else, shortcodes will simply be stripped.
                            </div>
                            <div class='col_right'>
                                <input type='checkbox' name='wdm_solr_form_data[is_shortcode_expanded]'
                                       value='1' <?php checked( '1', isset( $solr_options['is_shortcode_expanded'] ) ? $solr_options['is_shortcode_expanded'] : '' ); ?>>

                            </div>
                            <div class="clear"></div>
                        </div>

                        <div class="wdm_row">
                            <div class='col_left'>
                                Post types to be indexed

								<?php
								if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_CHECKER ) ) ) {
									require $file_to_include;
								}
								?>
                            </div>
                            <div class='col_right'>
                                <input type='hidden' name='wdm_solr_form_data[p_types]' id='p_types'>
								<?php
								$post_types_opt = implode( ',', apply_filters(
										WPSOLR_Events::WPSOLR_FILTER_INDEX_POST_TYPES_SELECTED,
										WPSOLR_Service_Container::getOption()->get_option_index_post_types()
									)
								);

								// Sort post types
								asort( $post_types );

								$models = WPSOLR_Model_Post_Type::create_models( $post_types );
								// Selected first
								foreach ( $models as $model ) {
									$type = $model->get_post_type();
									if ( strpos( $post_types_opt, $type ) !== false ) {
										$disabled = '';

										?>
                                        <input type='checkbox' name='post_tys' class="wpsolr_checked"
                                               value='<?php echo $type ?>'
											<?php echo $disabled; ?>
                                               checked> <?php echo $model->get_label() ?>
                                        <br>
										<?php
									}
								}

								// Unselected 2nd
								foreach ( $models as $model ) {
									$type = $model->get_post_type();
									if ( strpos( $post_types_opt, $type ) === false ) {
										$disabled = '';

										?>
                                        <input type='checkbox' name='post_tys' class="wpsolr_checked"
                                               value='<?php echo $type ?>'
											<?php echo $disabled; ?>
                                        > <?php echo $model->get_label() ?>
                                        <br>
										<?php
									}
								}

								?>

                            </div>
                            <div class="clear"></div>
                        </div>

						<?php
						if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_INDEXING_TAXONOMIES ) ) ) {
							require_once $file_to_include;
						}
						?>

						<?php
						if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_INDEXING_CUSTOM_FIELDS ) ) ) {
							require_once $file_to_include;
						}
						?>

						<?php
						if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_INDEXING_ATTACHMENTS ) ) ) {
							require_once $file_to_include;
						}
						?>

                        <div class="wdm_row">
                            <div class='col_left'>Index Comments</div>
                            <div class='col_right'>
                                <input type='checkbox' name='wdm_solr_form_data[comments]'
                                       value='1' <?php checked( '1', isset( $solr_options['comments'] ) ? $solr_options['comments'] : '' ); ?>>

                            </div>
                            <div class="clear"></div>
                        </div>
                        <div class="wdm_row">
                            <div class='col_left'>Exclude items (Posts,Pages,...)</div>
                            <div class='col_right'>
                                <input type='text' name='wdm_solr_form_data[exclude_ids]'
                                       placeholder="Comma separated ID's list"
                                       value="<?php echo empty( $solr_options['exclude_ids'] ) ? '' : $solr_options['exclude_ids']; ?>">
                                <br>
                                (Comma separated ids list)
                            </div>
                            <div class="clear"></div>
                        </div>
                        <div class='wdm_row'>
                            <div class="submit">
                                <input name="save_selected_index_options_form"
                                       id="save_selected_index_options_form" type="submit"
                                       class="button-primary wdm-save" value="Save Options"/>


                            </div>
                        </div>

                    </div>
                </form>
            </div>
			<?php
			break;

		case 'field_opt':
			if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SEARCH_BOOSTS ) ) ) {
				require_once $file_to_include;
			} else {
				?>
                <div id="solr-facets-options" class="wdm-vertical-tabs-content">
                    <div class='wrapper'>
                        <h4 class='head_div'>Boost Options</h4>

                        <div class="wdm_note">

                            With <?php echo sprintf( '<a href="%s" target="__new">WPSOLR PRO</a>', $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/' ) ) ?>
                            , you can add boosts (weights) to the fields you think are the most
                            important.
                        </div>
                    </div>
                </div>
				<?php
			}
			break;

		case 'facet_opt':
			$solr_options = WPSOLR_Service_Container::getOption()->get_option_index();
			$checked_fls = WPSOLR_Service_Container::getOption()->get_option_index_custom_fields_str() . ',' . WPSOLR_Service_Container::getOption()->get_option_index_taxonomies_str();

			$checked_fields = explode( ',', $checked_fls );
			$img_path       = plugins_url( '../images/plus.png', __FILE__ );
			$minus_path     = plugins_url( '../images/minus.png', __FILE__ );
			$built_in       = [ 'Type', 'Author', 'Categories', 'Tags' ];
			$built_in       = array_merge( $built_in, $checked_fields );

			$built_in_can_show_hierarchy = explode( ',', 'Categories' . ',' . WPSOLR_Service_Container::getOption()->get_option_index_taxonomies_str() );

			$facet_layout_skins_available = apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACET_LAYOUT_SKINS, [] );

			?>
            <div id="solr-facets-options" class="wdm-vertical-tabs-content">
                <form action="options.php" method="POST" id='fac_settings_form'>
					<?php
					settings_fields( 'solr_facet_options' );
					$solr_fac_options                    = WPSOLR_Service_Container::getOption()->get_option_facet();
					$selected_facets_value               = WPSOLR_Service_Container::getOption()->get_facets_to_display_str();
					$selected_array                      = WPSOLR_Service_Container::getOption()->get_facets_to_display();
					$selected_facets_is_hierarchy        = ! empty( $solr_fac_options[ WPSOLR_Option::OPTION_FACET_FACETS_TO_SHOW_AS_HIERARCH ] ) ? $solr_fac_options[ WPSOLR_Option::OPTION_FACET_FACETS_TO_SHOW_AS_HIERARCH ] : array();
					$selected_facets_labels              = WPSOLR_Service_Container::getOption()->get_facets_labels();
					$selected_facets_item_labels         = WPSOLR_Service_Container::getOption()->get_facets_items_labels();
					$selected_facets_item_is_default     = WPSOLR_Service_Container::getOption()->get_facets_items_is_default();
					$selected_facets_sorts               = WPSOLR_Service_Container::getOption()->get_facets_sort();
					$selected_facets_is_exclusions       = WPSOLR_Service_Container::getOption()->get_facets_is_exclusion();
					$selected_facets_layouts             = WPSOLR_Service_Container::getOption()->get_facets_layouts_ids();
					$selected_facets_is_or               = WPSOLR_Service_Container::getOption()->get_facets_is_or();
					$selected_facets_seo_is_permalink    = WPSOLR_Service_Container::getOption()->get_facets_seo_is_permalinks();
					$selected_facets_seo_templates       = WPSOLR_Service_Container::getOption()->get_facets_seo_permalink_templates();
					$selected_facets_seo_items_templates = WPSOLR_Service_Container::getOption()->get_facets_seo_permalink_items_templates();
					?>
                    <div class='wrapper'>
                        <h4 class='head_div'>Filters Options</h4>

                        <div class="wdm_note">

                            In this section, you will choose which data you want to display as filters in
                            your search results. filters are extra filters usually seen in the left hand
                            side of the results, displayed as a list of links. You can add filters only
                            to data you've selected to be indexed.

                        </div>
                        <div class="wdm_note">
                            <h4>Instructions</h4>
                            <ul class="wdm_ul wdm-instructions">
                                <li>Click on the 'Plus' icon to add the filters</li>
                                <li>Click on the 'Minus' icon to remove the filters</li>
                                <li>Sort the items in the order you want to display them by dragging and
                                    dropping them at the desired place
                                </li>
                            </ul>
                        </div>

						<?php
						if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_FACET_SEO_TEMPLATE_POSITIONS ) ) ) {
							require $file_to_include;
						}
						?>

                        <div class="wdm_row">
                            <div class='avail_fac' style="width:100%">
                                <h4>Available items for filters</h4>
                                <input type='hidden' id='select_fac' name='wdm_solr_facet_data[facets]'
                                       value='<?php echo $selected_facets_value ?>'>

                                <ul id="sortable1" class="wdm_ul connectedSortable">
									<?php

									if ( $selected_facets_value != '' ) {
										foreach ( $selected_array as $selected_val ) {
											if ( $selected_val != '' ) {
												if ( substr( $selected_val, ( strlen( $selected_val ) - 4 ), strlen( $selected_val ) ) == WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ) {
													$dis_text = substr( $selected_val, 0, ( strlen( $selected_val ) - 4 ) );
												} else {
													$dis_text = $selected_val;
												}
												?>
                                                <li id='<?php echo $selected_val; ?>'
                                                    class='ui-state-default facets facet_selected'>
															<span
                                                                    style="float:left;width: 300px;"><?php echo $dis_text; ?></span>
                                                    <img src='<?php echo $img_path; ?>'
                                                         class='plus_icon'
                                                         style='display:none'>
                                                    <img src='<?php echo $minus_path ?>'
                                                         class='minus_icon'
                                                         style='display:inline'
                                                         title='Click to Remove the filter'>
                                                    <br/>

													<?php
													if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_FACET_LABEL ) ) ) {
														require $file_to_include;
													}

													if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_THEME_FACET_LAYOUT ) ) ) {
														require $file_to_include;
													}
													?>

                                                </li>

											<?php }
										}
									}
									foreach ( $built_in as $built_fac ) {
										if ( $built_fac != '' ) {
											$buil_fac = strtolower( $built_fac );
											if ( substr( $buil_fac, ( strlen( $buil_fac ) - 4 ), strlen( $buil_fac ) ) == WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ) {
												$dis_text = substr( $buil_fac, 0, ( strlen( $buil_fac ) - 4 ) );
											} else {
												$dis_text = $buil_fac;
											}

											if ( ! in_array( $buil_fac, $selected_array )
											     && ( WpSolrSchema::_SOLR_DYNAMIC_TYPE_TEXT !== WpSolrSchema::get_custom_field_dynamic_type( $buil_fac ) ) // Long texts cannot be faceted (due to analysers)
											) {

												echo "<li id='$buil_fac' class='ui-state-default facets'>$dis_text
                                                                                                    <img src='$img_path'  class='plus_icon' style='display:inline' title='Click to Add the Facet'>
                                                                                                <img src='$minus_path' class='minus_icon' style='display:none'></li>";
											}
										}
									}
									?>


                                </ul>
                            </div>

                            <div class="clear"></div>
                        </div>

                        <div class='wdm_row'>
                            <div class="submit">
                                <input name="save_facets_options_form" id="save_facets_options_form"
                                       type="submit" class="button-primary wdm-save"
                                       value="Save Options"/>


                            </div>
                        </div>
                    </div>
                </form>
            </div>
			<?php
			break;

		case 'sort_opt':
			$img_path = plugins_url( '../images/plus.png', __FILE__ );
			$minus_path  = plugins_url( '../images/minus.png', __FILE__ );

			$built_in = WpSolrSchema::get_sort_fields();
			?>
            <div id="solr-sort-options" class="wdm-vertical-tabs-content">
                <form action="options.php" method="POST" id='sort_settings_form'>
					<?php
					settings_fields( 'solr_sort_options' );
					$selected_array         = apply_filters(
						WPSOLR_Events::WPSOLR_FILTER_INDEX_SORTS_SELECTED,
						WPSOLR_Service_Container::getOption()->get_sortby_items_as_array()
					);
					$selected_sort_value    = WPSOLR_Service_Container::getOption()->get_sortby_items();
					$selected_sortby_labels = WPSOLR_Service_Container::getOption()->get_sortby_items_labels();
					?>
                    <div class='wrapper'>
                        <h4 class='head_div'>Sort Options</h4>

                        <div class="wdm_note">

                            In this section, you will choose which elements will be displayed as sort
                            criteria for your search results, and in which order.

                        </div>
                        <div class="wdm_note">
                            <h4>Instructions</h4>
                            <ul class="wdm_ul wdm-instructions">
                                <li>Click on the 'Plus' icon to add the sort</li>
                                <li>Click on the 'Minus' icon to remove the sort</li>
                                <li>Sort the items in the order you want to display them by dragging and
                                    dropping them at the desired place
                                </li>
                            </ul>
                        </div>

                        <div class="wdm_row">
                            <div class='col_left'>Default when no sort is selected by the user</div>
                            <div class='col_right'>
                                <select name="wdm_solr_sortby_data[sort_default]">
									<?php foreach ( apply_filters( WPSOLR_Events::WPSOLR_FILTER_DEFAULT_SORT_FIELDS, $built_in ) as $sort ) {
										$selected = WPSOLR_Service_Container::getOption()->get_sortby_default() == $sort['code'] ? 'selected' : '';
										?>
                                        <option
                                                value="<?php echo $sort['code'] ?>" <?php echo $selected ?> ><?php echo $sort['label'] ?></option>
									<?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class='col_left'>
                            Sort on multi-value fields
                        </div>
                        <div class='col_right'>
                            <input type='checkbox'
                                   name='wdm_solr_sortby_data[<?php echo WPSOLR_Option::OPTION_SORTBY_IS_MULTIVALUE; ?>]'
                                   value='1'
								<?php checked( WPSOLR_Service_Container::getOption()->get_sortby_is_multivalue() ); ?>>
                        </div>
                        <div class="clear"></div>

                        <div class="wdm_row">
                            <div class='avail_fac'>
                                <h4>Activate/deactivate items in the sort list</h4>
                                <input type='hidden' id='select_sort' name='wdm_solr_sortby_data[sort]'
                                       value='<?php echo $selected_sort_value ?>'>


                                <ul id="sortable_sort" class="wdm_ul connectedSortable_sort">
									<?php
									foreach ( $selected_array

									as $selected_sort ) {
									foreach ( $built_in

									as $built ) {
									if ( ! empty( $built ) && ( $selected_sort === $built['code'] ) ) {
									$sort_code = $built['code'];
									$dis_text  = $built['label'];

									if ( in_array( $sort_code, $selected_array ) ) {

									?>
                                    <li id='<?php echo $sort_code; ?>'
                                        class='ui-state-default facets sort_selected'>
                                <span
                                        style="float:left;width: 300px;"><?php echo $dis_text; ?></span>
                                        <img src='<?php echo $img_path; ?>'
                                             class='minus_icon_sort'
                                             style='display:none'>
                                        <img src='<?php echo $minus_path ?>'
                                             class='minus_icon_sort'
                                             style='display:inline'
                                             title='Click to Remove the sort item'>
                                        <br/>

										<?php
										if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_SORT_LABEL ) ) ) {
											require $file_to_include;
										}
										?>

										<?php
										}
										}
										}
										}
										foreach ( $built_in as $built ) {
											if ( $built != '' ) {
												$buil_fac = $built['code'];
												$dis_text = $built['label'];

												if ( ! in_array( $buil_fac, $selected_array ) ) {

													echo "<li id='$buil_fac' class='ui-state-default facets'>$dis_text
                                                                                                    <img src='$img_path'  class='plus_icon_sort' style='display:inline' title='Click to Add the Sort'>
                                                                                                <img src='$minus_path' class='minus_icon_sort' style='display:none'></li>";
												}
											}
										}
										?>
                                    </li>

                                </ul>
                            </div>

                            <div class="clear"></div>
                        </div>

                        <div class='wdm_row'>
                            <div class="submit">
                                <input name="save_sort_options_form" id="save_sort_options_form"
                                       type="submit" class="button-primary wdm-save"
                                       value="Save Options"/>


                            </div>
                        </div>
                    </div>
                </form>
            </div>
			<?php
			break;

		case 'localization_options':
			WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::OPTION_LOCALIZATION );
			break;

	}

	?>

</div>
