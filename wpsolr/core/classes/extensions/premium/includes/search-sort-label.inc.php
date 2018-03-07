<?php
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\utilities\WPSOLR_Option;

?>

<div class="wdm_row" style="top-margin:5px;">
    <div class='col_left'>
		<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, sprintf( '%s label', ucfirst( $dis_text ) ), true ); ?>
    </div>
	<?php
	$sortby_label = ! empty( $selected_sortby_labels[ $sort_code ] ) ? $selected_sortby_labels[ $sort_code ] : '';
	?>
    <div class='col_right'>
        <input type='text'
               name='wdm_solr_sortby_data[<?php echo WPSOLR_Option::OPTION_SORTBY_ITEM_LABELS; ?>][<?php echo $sort_code; ?>]'
               value='<?php echo esc_attr( $sortby_label ); ?>'
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