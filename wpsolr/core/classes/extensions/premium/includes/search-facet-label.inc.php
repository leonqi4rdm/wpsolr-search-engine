<?php
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\utilities\WPSOLR_Option;

?>

<div class="wdm_row" style="top-margin:5px;">
    <div class='col_left'>
		<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Label', true ); ?>
    </div>
	<?php
	$facet_label = ! empty( $selected_facets_labels[ $selected_val ] ) ? $selected_facets_labels[ $selected_val ] : '';
	?>
    <div class='col_right'>
        <input type='text' class="wpsolr-remove-if-empty"
               name='wdm_solr_facet_data[<?php echo WPSOLR_Option::OPTION_FACET_FACETS_LABEL; ?>][<?php echo $selected_val; ?>]'
               value='<?php echo esc_attr( $facet_label ); ?>'
			<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ); ?>
        />
        <p>
            Will be shown on the front-end (and
            translated in WPML/POLYLANG string modules).
            Leave empty if you wish to use the current
            facet
            name "<?php echo $dis_text; ?>".
        </p>

    </div>
    <div class="clear"></div>
</div>
