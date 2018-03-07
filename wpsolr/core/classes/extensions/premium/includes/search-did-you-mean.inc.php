<?php
use wpsolr\core\classes\extensions\licenses\OptionLicenses;

?>

<div class="wdm_row">
    <div class='col_left'>
		<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Display "Did you mean?" in search results header ?', true ); ?>
    </div>
    <div class='col_right'>
        <input type='checkbox'
               name='wdm_solr_res_data[<?php echo 'spellchecker' ?>]'
               value='spellchecker'
			<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ); ?>
			<?php checked( 'spellchecker', isset( $solr_res_options['spellchecker'] ) ? $solr_res_options['spellchecker'] : '?' ); ?>>
    </div>
    <div class="clear"></div>
</div>
