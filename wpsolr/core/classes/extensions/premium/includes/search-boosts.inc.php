<?php
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WpSolrSchema;

?>

<?php
$solr_options = WPSOLR_Service_Container::getOption()->get_option_index();
$checked_fls  = WPSOLR_Service_Container::getOption()->get_option_index_custom_fields_str() . ',' . WPSOLR_Service_Container::getOption()->get_option_index_taxonomies_str();

$checked_fields = explode( ',', $checked_fls );
$img_path       = plugins_url( 'images/plus.png', WPSOLR_PLUGIN_FILE );
$minus_path     = plugins_url( 'images/minus.png', WPSOLR_PLUGIN_FILE );
$built_in       = [
	WpSolrSchema::_FIELD_NAME_CONTENT,
	WpSolrSchema::_FIELD_NAME_TITLE,
	WpSolrSchema::_FIELD_NAME_COMMENTS,
	WpSolrSchema::_FIELD_NAME_TYPE,
	WpSolrSchema::_FIELD_NAME_AUTHOR,
	WpSolrSchema::_FIELD_NAME_CATEGORIES,
	WpSolrSchema::_FIELD_NAME_TAGS
];
$built_in       = array_merge( $built_in, $checked_fields );

?>
<div id="solr-facets-options" class="wdm-vertical-tabs-content">
    <form action="options.php" method="POST" id='fac_settings_form'>
		<?php
		settings_fields( 'solr_search_field_options' );
		$solr_search_fields_is_active            = WPSOLR_Service_Container::getOption()->get_search_fields_is_active();
		$solr_search_fields_boosts_options       = WPSOLR_Service_Container::getOption()->get_search_fields_boosts();
		$solr_search_fields_terms_boosts_options = WPSOLR_Service_Container::getOption()->get_search_fields_terms_boosts();
		$selected_values                         = WPSOLR_Service_Container::getOption()->get_option_search_fields_str();
		$selected_array                          = WPSOLR_Service_Container::getOption()->get_option_search_fields();
		?>
        <div class='wrapper'>
            <h4 class='head_div'>Search fields boosts Options</h4>

            <div class="wdm_row">
                <div class='col_left'>Activate the boosts</div>
                <div class='col_right'>
                    <input type='checkbox'
                           name='<?php echo WPSOLR_Option::OPTION_SEARCH_FIELDS; ?>[<?php echo WPSOLR_Option::OPTION_SEARCH_FIELDS_IS_ACTIVE; ?>]'
                           value='1' <?php checked( $solr_search_fields_is_active ); ?>>

                    First, select among the fields indexed (see below) those you want to search
                    in,
                    then define their boosts.
                    Select none if you want to use the default search configuration.

                </div>
                <div class="clear"></div>
            </div>

            <div class="wdm_row">
                <div class='avail_fac' style="width:90%">
                    <input type='hidden' id='select_fac'
                           name='<?php echo WPSOLR_Option::OPTION_SEARCH_FIELDS; ?>[<?php echo WPSOLR_Option::OPTION_SEARCH_FIELDS_FIELDS; ?>]'
                           value='<?php echo $selected_values ?>'>

                    <ul id="sortable1" class="wdm_ul connectedSortable">
						<?php
						if ( $selected_values != '' ) {
							foreach ( $selected_array as $selected_val ) {
								if ( $selected_val !== '' ) {
									if ( substr( $selected_val, ( strlen( $selected_val ) - 4 ), strlen( $selected_val ) ) == WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ) {
										$dis_text = substr( $selected_val, 0, ( strlen( $selected_val ) - 4 ) );
									} else {
										$dis_text = $selected_val;
									}
									?>
                                    <li id='<?php echo $selected_val; ?>'
                                        class='ui-state-default facets facet_selected'>

                                        <img src='<?php echo $img_path; ?>'
                                             class='plus_icon'
                                             style='display:none'>
                                        <img src='<?php echo $minus_path ?>'
                                             class='minus_icon'
                                             style='display:inline'
                                             title='Click to remove the field from the search'>
                                        <span style="float:left;width: 80%;">
																<?php echo $dis_text; ?>
															</span>

                                        <div>&nbsp;</div>

										<?php
										$search_field_boost = empty( $solr_search_fields_boosts_options[ $selected_val ] )
											? '' : $solr_search_fields_boosts_options[ $selected_val ];
										?>
                                        <div class="wdm_row" style="top-margin:5px;">
                                            <div class='col_left'>Boost field</div>
                                            <div class='col_right'>
                                                <input type='input'
													<?php echo empty( $search_field_boost ) ? 'style="border-color:red;"' : ''; ?>
                                                       class='wpsolr_field_boost_factor_class'
                                                       name='<?php echo WPSOLR_Option::OPTION_SEARCH_FIELDS; ?>[<?php echo WPSOLR_Option::OPTION_SEARCH_FIELDS_BOOST; ?>][<?php echo $selected_val; ?>]'
                                                       value='<?php echo esc_attr( $search_field_boost ); ?>'
                                                />
												<?php echo empty( $search_field_boost ) ? "<span class='res_err'>Please enter a number > 0. Examples: '0.5', '2', '3.1'</span>" : ''; ?>
                                                <p>
                                                    Set a boost factor to increase or decrease
                                                    that
                                                    particular field's importance in the search.
                                                    Like '0.4', '2', '3.5'. Default value is
                                                    '1'.
                                                    <a target="__new"
                                                       href="https://cwiki.apache.org/confluence/display/solr/The+DisMax+Query+Parser#TheDisMaxQueryParser-Theqf(QueryFields)Parameter">See
                                                        Solr boost</a>
                                                </p>

                                            </div>
                                            <div class="clear"></div>
                                        </div>

										<?php
										$solr_search_fields_terms_boosts = empty( $solr_search_fields_terms_boosts_options[ $selected_val ] )
											? '' : $solr_search_fields_terms_boosts_options[ $selected_val ];
										?>
                                        <div class="wdm_row" style="top-margin:5px;">
                                            <div class='col_left'>Boost values</div>
                                            <div class='col_right'>
																	<textarea
                                                                            class='wpsolr_field_boost_term_factor_class'
                                                                            rows="5"
                                                                            placeholder="solr^0.5&#10;apache solr^2.5&#10;apache solr search^3"
                                                                            name="<?php echo WPSOLR_Option::OPTION_SEARCH_FIELDS; ?>[<?php echo WPSOLR_Option::OPTION_SEARCH_FIELDS_TERMS_BOOST; ?>][<?php echo $selected_val; ?>]"
                                                                    ><?php echo esc_attr( $solr_search_fields_terms_boosts ); ?></textarea>

                                                <p>
                                                    Boost results that have
                                                    field '<?php echo $selected_val; ?>' that
                                                    matches
                                                    a specific value
                                                    <a target="__new"
                                                       href="https://cwiki.apache.org/confluence/display/solr/The+DisMax+Query+Parser#TheDisMaxQueryParser-Thebq(BoostQuery)Parameter">See
                                                        Solr boost query</a>
                                                </p>

                                            </div>
                                            <div class="clear"></div>
                                        </div>

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

								if ( ! in_array( $buil_fac, $selected_array ) ) {

									echo "<li id='$buil_fac' class='ui-state-default facets'>$dis_text
                                                                                                    <img src='$img_path'  class='plus_icon' style='display:inline' title='Click to add the field from the search'>
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
					<?php if ( $license_manager->get_license_is_activated( OptionLicenses::LICENSE_PACKAGE_PREMIUM ) ) { ?>
                        <div
                                class="wpsolr_premium_block_class"><?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, OptionLicenses::TEXT_LICENSE_ACTIVATED, true, true ); ?></div>
                        <input name="save_fields_options_form" id="save_fields_options_form"
                               type="submit" class="button-primary wdm-save"
                               value="Save Options"/>
					<?php } else { ?>
						<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Save Options', true, true ); ?>
                        <br/>
					<?php } ?>
                </div>
            </div>
        </div>
    </form>
</div>
