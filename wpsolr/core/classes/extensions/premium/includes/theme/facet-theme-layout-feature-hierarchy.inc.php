<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\ui\layout\WPSOLR_UI_Layout_Abstract;
use wpsolr\core\classes\utilities\WPSOLR_Option;

?>

<div style="display:none"
     class="wpsolr-remove-if-hidden wpsolr_facet_type <?php echo WPSOLR_UI_Layout_Abstract::get_css_class_feature_layouts( WPSOLR_UI_Layout_Abstract::FEATURE_HIERARCHY ); ?>">

	<?php
	$is_hierarchy       = isset( $selected_facets_is_hierarchy[ $selected_val ] );
	$can_show_hierarchy = in_array( $selected_val, array_map( 'strtolower', $built_in_can_show_hierarchy ), true );
	$disabled           = $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM );
	if ( $can_show_hierarchy ) { ?>
        <div class="wdm_row" style="top-margin:5px;">
            <div class='col_left'>
				<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Show the hierarchy', true ); ?>
            </div>
            <div class='col_right'>
                <input type='checkbox'
                       name='wdm_solr_facet_data[<?php echo WPSOLR_Option::OPTION_FACET_FACETS_TO_SHOW_AS_HIERARCH; ?>][<?php echo $selected_val; ?>]'
                       value='1'
					<?php echo checked( $is_hierarchy ); ?>
					<?php echo ( empty( $disabled ) && $can_show_hierarchy ) ? '' : $disabled; ?>
                />
                Select to display the facet as a tree

            </div>
            <div class="clear"></div>
        </div>
	<?php } ?>

</div>