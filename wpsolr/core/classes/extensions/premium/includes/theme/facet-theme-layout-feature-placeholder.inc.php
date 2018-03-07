<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\ui\layout\WPSOLR_UI_Layout_Abstract;
use wpsolr\core\classes\utilities\WPSOLR_Option;

?>

<div style="display:none"
     class="wpsolr-remove-if-hidden wpsolr_facet_type <?php echo WPSOLR_UI_Layout_Abstract::get_css_class_feature_layouts( WPSOLR_UI_Layout_Abstract::FEATURE_PLACEHOLDER ); ?>">

	<?php
	$facet_placeholder = WPSOLR_Service_Container::getOption()->get_facets_placeholder_value( $selected_val );
	?>

    <div class="wdm_row" style="top-margin:5px;">
        <div class='col_left'>
			<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Placeholder', true ); ?>
        </div>
        <div class='col_right'>

           <textarea
                   name='wdm_solr_facet_data[<?php echo WPSOLR_Option::OPTION_FACET_FACETS_PLACEHOLDER; ?>][<?php echo $selected_val; ?>]'
                   class="wpsolr-remove-if-empty"
                   data-wpsolr-empty-value=""
	           <?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ); ?>
           ><?php echo $facet_placeholder; ?></textarea>

            Text displayed when no value is selected in the select box.

        </div>
        <div class="clear"></div>
    </div>
</div>
