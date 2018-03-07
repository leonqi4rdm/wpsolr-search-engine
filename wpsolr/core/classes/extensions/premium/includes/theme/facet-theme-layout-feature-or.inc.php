<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\ui\layout\WPSOLR_UI_Layout_Abstract;
use wpsolr\core\classes\utilities\WPSOLR_Option;

?>

<div style="display:none"
     class="wpsolr-remove-if-hidden wpsolr_facet_type <?php echo WPSOLR_UI_Layout_Abstract::get_css_class_feature_layouts( WPSOLR_UI_Layout_Abstract::FEATURE_OR ); ?>">

	<?php
	$is_or = isset( $selected_facets_is_or[ $selected_val ] );
	?>

    <div class="wdm_row" style="top-margin:5px;">
        <div class='col_left'>
			<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'OR on multiple selections', true ); ?>
        </div>
        <div class='col_right'>
            <input type='checkbox'
                   name='wdm_solr_facet_data[<?php echo WPSOLR_Option::OPTION_FACET_FACETS_IS_OR; ?>][<?php echo $selected_val; ?>]'
                   value='1'
				<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ); ?>
				<?php echo checked( $is_or ); ?>
            />
            When several items of the facet are selected, use 'OR'. Default is 'AND'.

        </div>
        <div class="clear"></div>
    </div>

</div>