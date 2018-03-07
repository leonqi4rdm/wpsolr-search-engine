<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\ui\layout\WPSOLR_UI_Layout_Abstract;
use wpsolr\core\classes\utilities\WPSOLR_Option;

?>

<div style="display:none"
     class="wpsolr-remove-if-hidden wpsolr_facet_type <?php echo WPSOLR_UI_Layout_Abstract::get_css_class_feature_layouts( WPSOLR_UI_Layout_Abstract::FEATURE_GRID ); ?>">

	<?php
	$facet_grid            = WPSOLR_Service_Container::getOption()->get_facets_grid_value( $selected_val );
	$facet_grids_available = [
		WPSOLR_Option::OPTION_FACET_GRID_1_COLUMN   => '1 column',
		WPSOLR_Option::OPTION_FACET_GRID_2_COLUMNS  => '2 columns',
		WPSOLR_Option::OPTION_FACET_GRID_3_COLUMNS  => '3 columns',
		WPSOLR_Option::OPTION_FACET_GRID_HORIZONTAL => 'Horizontal',
	]
	?>

    <div class="wdm_row" style="top-margin:5px;">
        <div class='col_left'>
			<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_THEME, 'Grid orientation', true ); ?>
        </div>
        <div class='col_right'>

            <select name='wdm_solr_facet_data[<?php echo WPSOLR_Option::OPTION_FACET_FACETS_GRID; ?>][<?php echo $selected_val; ?>]'
                    class="wpsolr-remove-if-empty"
                    data-wpsolr-empty-value="<?php echo WPSOLR_Option::OPTION_FACET_GRID_1_COLUMN; ?>"
				<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_THEME ); ?>
            >
				<?php foreach ( $facet_grids_available as $grid_id => $grid_label ) { ?>
                    <option value="<?php echo $grid_id; ?>" <?php echo selected( $facet_grid, $grid_id ); ?>><?php echo $grid_label; ?></option>
				<?php } ?>
            </select>

        </div>
        <div class="clear"></div>
    </div>
</div>
