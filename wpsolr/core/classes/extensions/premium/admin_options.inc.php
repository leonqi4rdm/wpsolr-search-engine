<?php
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\extensions\WpSolrExtensions;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\utilities\WPSOLR_Option;

/**
 * Included file to display admin options
 */
global $license_manager;

WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::EXTENSION_PREMIUM, true );

$extension_options_name = WPSOLR_Option::OPTION_PREMIUM;
$settings_fields_name   = 'extension_premium_opt';

$options          = WPSOLR_Service_Container::getOption()->get_option_premium();
$is_plugin_active = WpSolrExtensions::is_plugin_active( WpSolrExtensions::EXTENSION_PREMIUM );

?>

<div id="extension_groups-options" class="wdm-vertical-tabs-content">
    <form action="options.php" method="POST" id='extension_groups_settings_form'>
		<?php
		settings_fields( $settings_fields_name );
		?>

        <div class='wrapper'>
            <h4 class='head_div'>Premium Pack</h4>

            <div class="wdm_note">
                This pack activates the following features:
                <ol>
                    <li>Manage more than one Solr index</li>
                    <li>Multi-domain search</li>
                    <li>Use my current theme's search template, instead of WPSOLR's</li>
                    <li>Change ajax search page slug</li>
                    <li>Infinite scroll navigation</li>
                    <li>Products suggestions</li>
                    <li>Attach suggestions to any search box with jQuery selectors</li>
                    <li>Did you mean?</li>
                    <li>Stop real-time indexing</li>
                    <li>Index/search any post type</li>
                    <li>Index/search custom taxonomies</li>
                    <li>Index/search custom fields</li>
                    <li>Index/search attached files from the medial library</li>
                    <li>Boost fields</li>
                    <li>Localize facets</li>
                    <li>Show facets hierarchies</li>
                    <li>Localize sort items</li>
                    <li>Display debug infos during indexing</li>
                    <li>Re-index all the data in place</li>
                </ol>
            </div>

            <div class="wdm_row">
                <div class='col_left'>
                    Activate the Premium Pack
					<?php echo WPSOLR_Help::get_help( WPSOLR_Help::HELP_GEOLOCATION ); ?>
                </div>
                <div class='col_right'>
                    <input type='checkbox' <?php echo $is_plugin_active ? '' : 'readonly' ?>
                           name='<?php echo $extension_options_name; ?>[is_extension_active]'
                           value='is_extension_active'
						<?php checked( 'is_extension_active', isset( $options['is_extension_active'] ) ? $options['is_extension_active'] : '' ); ?>>

                </div>
                <div class="clear"></div>
            </div>


            <div class='wdm_row'>
                <div class="submit">
					<?php if ( $license_manager->get_license_is_activated( OptionLicenses::LICENSE_PACKAGE_PREMIUM ) ) { ?>
                        <div class="wpsolr_premium_block_class">
							<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, OptionLicenses::TEXT_LICENSE_ACTIVATED, true, true ); ?>
                        </div>
                        <input
                                name="save_selected_options_res_form"
                                id="save_selected_extension_groups_form" type="submit"
                                class="button-primary wdm-save"
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