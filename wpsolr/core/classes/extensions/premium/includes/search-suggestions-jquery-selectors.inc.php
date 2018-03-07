<?php
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\utilities\WPSOLR_Option;

?>

<div class="wdm_row">
    <div
            class='col_left'><?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Attach the suggestions list to your own search boxes', true, true ); ?>
		<?php echo WPSOLR_Help::get_help( WPSOLR_Help::HELP_JQUERY_SELECTOR ); ?>
    </div>
    <div class='col_right'>
        <input type='text'
               name='wdm_solr_res_data[<?php echo WPSOLR_Option::OPTION_SEARCH_SUGGEST_JQUERY_SELECTOR; ?>]'
               placeholder=".search_box1, #search_box2, input.text_edit"
			<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM, true ); ?>
               value="<?php echo( ! empty( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_SUGGEST_JQUERY_SELECTOR ] ) ? $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_SUGGEST_JQUERY_SELECTOR ] : '' ); ?>">
        Enter a jQuery selector for your search boxes.
    </div>
    <div class="clear"></div>
</div>
