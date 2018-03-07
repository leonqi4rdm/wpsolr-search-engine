<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\ui\layout\WPSOLR_UI_Layout_Abstract;
use wpsolr\core\classes\utilities\WPSOLR_Option;

?>


<div style="display:none"
     class="wpsolr-remove-if-hidden wpsolr_facet_type <?php echo WPSOLR_UI_Layout_Abstract::get_css_class_feature_layouts( WPSOLR_UI_Layout_Abstract::FEATURE_LOCALIZATION_FIELD ); ?>">

    <input type='text' class="wpsolr-remove-if-empty"
           name='wdm_solr_facet_data[<?php echo WPSOLR_Option::OPTION_FACET_FACETS_ITEMS_LABEL; ?>][<?php echo $selected_val; ?>][<?php echo $facet_item_label; ?>]'
           value='<?php echo esc_attr( $facet_label ); ?>'
		<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ); ?>
    />
    <p>
        Will be shown on the front-end (and
        translated in WPML/POLYLANG string
        modules).
        Leave empty if you wish to use the
        current facet
        value "<?php echo $facet_item_label; ?>".
    </p>
</div>