<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\extensions\WpSolrExtensions;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Option;

/**
 * Included file to display admin options
 */
global $license_manager;

WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE, true );

$extension_options_name = WPSOLR_Option::OPTION_EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE;
$settings_fields_name   = 'extension_yith_woocommerce_ajax_search_free_opt';

$extension_options = WPSOLR_Service_Container::getOption()->get_option_yith_woocommerce_ajax_search_free();
$is_plugin_active  = WpSolrExtensions::is_plugin_active( WpSolrExtensions::EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE );

$plugin_name    = "YITH WooCommerce Ajax Search (Free)";
$plugin_link    = "https://wordpress.org/plugins/yith-woocommerce-ajax-search/";
$plugin_version = "(Last tested with version 1.5.3)";

?>

<div id="extension_groups-options" class="wdm-vertical-tabs-content">
    <form action="options.php" method="POST" id='extension_groups_settings_form'>
		<?php
		settings_fields( $settings_fields_name );
		?>

        <div class='wrapper'>
            <h4 class='head_div'><?php echo $plugin_name; ?> plugin Options</h4>

            <div class="wdm_note">

                In this section, you will configure WPSOLR to work with <?php echo $plugin_name; ?>.<br/>

				<?php if ( ! $is_plugin_active ): ?>
                    <p>
                        Status: <a href="<?php echo $plugin_link; ?>"
                                   target="_blank"><?php echo $plugin_name; ?>
                            plugin</a> is not activated. First, you need to install and
                        activate it to configure WPSOLR.
                    </p>
				<?php else : ?>
                    <p>
                        Status: <a href="<?php echo $plugin_link; ?>"
                                   target="_blank"><?php echo $plugin_name; ?>
                            plugin</a>
                        is activated. You can now configure WPSOLR to use it.
                    </p>
				<?php endif; ?>
            </div>

            <div class="wdm_row">
                <div class='col_left'>Use the <a
                            href="<?php echo $plugin_link; ?>"
                            target="_blank"><?php echo $plugin_name; ?> <?php echo $plugin_version; ?>
                        plugin</a>.
                </div>
                <div class='col_right'>
                    <input type='checkbox' <?php echo $is_plugin_active ? '' : 'readonly' ?>
                           name='<?php echo $extension_options_name; ?>[is_extension_active]'
                           value='is_extension_active'
						<?php checked( 'is_extension_active', isset( $extension_options['is_extension_active'] ) ? $extension_options['is_extension_active'] : '' ); ?>>
                </div>
                <div class="clear"></div>
            </div>

            <div class="wdm_row">
                <div class='col_left'>Replace search in the YITH search form widget
                </div>
                <div class='col_right'>
                    <input type='checkbox' class="wpsolr_collapser"
                           name='<?php echo $extension_options_name; ?>[<?php echo WPSOLR_Option::OPTION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE_IS_REPLACE_PRODUCT_SUGGESTIONS; ?>]'
                           value='y'
						<?php checked( WPSOLR_Service_Container::getOption()->get_yith_woocommerce_ajax_search_free_is_replace_product_suggestions() ); ?>>
                    <span class="wpsolr_collapsed">
                        Add the YITH WooCommerce Ajax search form widget to your sidebar, and let the magic happens.<br/>The look & feel is the same, but in the background WPSOLR has replaced the WordPress search.
                    </span>
                </div>
                <div class="clear"></div>
            </div>


            <div class='wdm_row'>
                <div class="submit">
					<?php if ( $license_manager->get_license_is_activated( OptionLicenses::LICENSE_PACKAGE_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE ) ) { ?>
                        <div class="wpsolr_premium_block_class">
							<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE, OptionLicenses::TEXT_LICENSE_ACTIVATED, true, true ); ?>
                        </div>
                        <input <?php echo $is_plugin_active ? '' : 'disabled' ?>
                                name="save_selected_options_res_form"
                                id="save_selected_extension_groups_form" type="submit"
                                class="button-primary wdm-save"
                                value="<?php echo $is_plugin_active ? 'Save Options' : sprintf( 'Install and activate the plugin %s first.', $plugin_name ); ?>"/>
					<?php } else { ?>
						<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE, 'Save Options', true, true ); ?>
                        <br/>
					<?php } ?>
                </div>
            </div>
        </div>

    </form>
</div>