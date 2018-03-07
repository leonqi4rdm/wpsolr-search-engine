<?php
use wpsolr\core\classes\extensions\licenses\OptionLicenses;

?>

<div class='col_left'>
	<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Display debug infos during indexing', true ); ?>
</div>
<div class='col_right'>

    <input type='checkbox'
           id='is_debug_indexing'
           name='wdm_solr_operations_data[is_debug_indexing][<?php echo $current_index_indice ?>]'
           value='is_debug_indexing'
		<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ); ?>
		<?php checked( 'is_debug_indexing', isset( $solr_operations_options['is_debug_indexing'][ $current_index_indice ] ) ? $solr_operations_options['is_debug_indexing'][ $current_index_indice ] : '' ); ?>>
    <span class='res_err'></span><br>
</div>

<div class="clear"></div>
