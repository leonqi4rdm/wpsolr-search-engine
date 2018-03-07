<?php
use wpsolr\core\classes\engines\solarium\WPSOLR_SearchSolariumClient;
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\utilities\WPSOLR_Option;

?>

<div class="wdm_row">
    <div
            class='col_left'><?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Ajax search page slug', true, true ); ?></div>
    <div class='col_right'>
        <input type='text'
               name='wdm_solr_res_data[<?php echo WPSOLR_Option::OPTION_SEARCH_AJAX_SEARCH_PAGE_SLUG; ?>]'
               placeholder="<?php echo WPSOLR_SearchSolariumClient::_SEARCH_PAGE_SLUG; ?>"
			<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM, true ); ?>
               value="<?php echo( ! empty( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_AJAX_SEARCH_PAGE_SLUG ] ) ? $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_AJAX_SEARCH_PAGE_SLUG ] : '' ); ?>">
        <br/>Enter a slug for the search page containing the shortcode for the Ajax
        search results, [solr_search_shortcode].
        <br/>By default, if empty,
        '<?php echo WPSOLR_SearchSolariumClient::_SEARCH_PAGE_SLUG; ?>' will be used.
        <br/>This slug will be used as the target url in the WPSOLR Ajax search box
        form.
    </div>
    <div class="clear"></div>
</div>
