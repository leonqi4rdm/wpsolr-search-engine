<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\ui\layout\WPSOLR_UI_Layout_Abstract;
use wpsolr\core\classes\utilities\WPSOLR_Option;

?>

<div style="display:none"
     class="wpsolr-remove-if-hidden wpsolr_facet_type <?php echo WPSOLR_UI_Layout_Abstract::get_css_class_feature_layouts( WPSOLR_UI_Layout_Abstract::FEATURE_SIZE ); ?>">

	<?php
	$facet_size = WPSOLR_Service_Container::getOption()->get_facets_size_value( $selected_val );
	?>

    <div class="wdm_row" style="top-margin:5px;">
        <div class='col_left'>
			<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Size', true ); ?>
        </div>
        <div class='col_right'>

            <input type="text"
                   name='wdm_solr_facet_data[<?php echo WPSOLR_Option::OPTION_FACET_FACETS_SIZE; ?>][<?php echo $selected_val; ?>]'
                   class="wpsolr-remove-if-empty"
                   data-wpsolr-empty-value=""
				<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ); ?>
                   value="<?php echo $facet_size; ?>"
            </input>

            Number of items shown without scrolling. Keep empty to use the browser's default.

        </div>
        <div class="clear"></div>
    </div>
</div>
