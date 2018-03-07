<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\ui\layout\checkboxes\WPSOLR_UI_Layout_Check_Box;
use wpsolr\core\classes\ui\layout\select\WPSOLR_UI_Layout_Select;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

?>

<?php
global $is_facet_js_loaded;

$current_layout_id       = isset( $selected_facets_layouts[ $selected_val ] ) ? $selected_facets_layouts[ $selected_val ] : WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID;
$facets_layout_available = apply_filters(
	WPSOLR_Events::WPSOLR_FILTER_GET_FIELD_TYPE_LAYOUTS,
	[
		WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID => new WPSOLR_UI_Layout_Check_Box(),
		WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID    => new WPSOLR_UI_Layout_Select(),
	],
	WpSolrSchema::get_custom_field_solr_type( $selected_val )
);
?>

<?php if ( ! isset( $is_facet_js_loaded ) ) { ?>
    <script>
        jQuery(document).ready(function () {

            // Initiate color pickers
            jQuery('.wpsolr-color-picker').wpColorPicker();

            function display_facet_types(layout_element) {
                layout_element.parent().find(".wpsolr_facet_type").hide(); // hide all facet type sections
                layout_element.parent().find(".wpsolr_facet_type").addClass('wpsolr-remove-if-hidden'); // remove all facet type sections

                layout_element.parent().find(".wpsolr_facet_type." + layout_element.val()).show(); // show facet section type of the selected layout
                layout_element.parent().find(".wpsolr_facet_type." + layout_element.val()).removeClass('wpsolr-remove-if-hidden'); // do not remove facet section type of the selected layout
            }

            // Display facet sections depending on the select layout facet type
            jQuery(".wpsolr_layout_select").each(function () {
                display_facet_types(jQuery(this));
            });

            // Change facet layout selection
            jQuery(".wpsolr_layout_select").on("change", function (event) {
                display_facet_types(jQuery(this));
            });

        });
    </script>

	<?php
	// Load script once only
	$is_facet_js_loaded = true;
}
?>

<div class="wdm_row" style="top-margin:5px;">
    <div class='col_left'>
		<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_THEME, 'Layout', true ); ?>
    </div>
    <div class='col_right'>
        <select name='wdm_solr_facet_data[<?php echo WPSOLR_Option::OPTION_FACET_FACETS_LAYOUT; ?>][<?php echo $selected_val; ?>]'
                class="wpsolr-remove-if-empty wpsolr_layout_select"
                data-wpsolr-empty-value="<?php echo WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID; ?>">
			<?php /** @var \wpsolr\core\classes\ui\layout\WPSOLR_UI_Layout_Abstract $layout_class */
			foreach ( $facets_layout_available as $layout_id => $layout_class ) { ?>
                <option value="<?php echo $layout_id; ?>" <?php echo selected( $current_layout_id, $layout_id ); ?> <?php echo disabled( ! $layout_class::get_is_enabled() ); ?>>
					<?php echo sprintf( '%s %s', $layout_class::get_label(), $layout_class::get_is_enabled() ? '' : ' (coming soon)' ); ?>
                </option>
			<?php } ?>
        </select>
        Choose a layout to display your facet. The "Theme" extension must be activated first.

		<?php

		if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_FACET_SEO_TEMPLATE ) ) ) {
			require $file_to_include;
		}
		if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_FACET_THEME_SKIN_TEMPLATE ) ) ) {
			require $file_to_include;
		}
		if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_FACET_THEME_JS_TEMPLATE ) ) ) {
			require $file_to_include;
		}

		include 'facet-theme-layout-feature-multiple.inc.php';
		include 'facet-theme-layout-feature-size.inc.php';
		include 'facet-theme-layout-feature-placeholder.inc.php';

		include 'facet-theme-layout-feature-grid.inc.php';
		include 'facet-theme-layout-feature-hierarchy.inc.php';
		include 'facet-theme-layout-feature-or.inc.php';
		include 'facet-theme-layout-feature-sort-alphabetical.inc.php';
		include 'facet-theme-layout-feature-exclusion.inc.php';

		if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_FACET_THEME_RANGE_REGULAR_TEMPLATE ) ) ) {
			require $file_to_include;
		}
		if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_FACET_THEME_RANGE_IRREGULAR_TEMPLATE ) ) ) {
			require $file_to_include;
		}
		if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_FACET_THEME_COLOR_PICKER_TEMPLATE ) ) ) {
			require $file_to_include;
		}

		include 'facet-theme-layout-localizations.inc.php';
		?>

    </div>
    <div class="clear"></div>
</div>
