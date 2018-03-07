<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;

?>

<div class='col_left'>
	<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Re-index all the data in place.', true ); ?>
</div>
<div class='col_right'>

    <input type='checkbox'
           id='is_reindexing_all_posts'
           name='is_reindexing_all_posts'
           value='is_reindexing_all_posts'
		<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ); ?>
		<?php checked( true, false ); ?>>

    If you check this option, it will restart the indexing from start, without deleting the
    data already in the index.
    <span class='res_err'></span><br>
</div>
<div class="clear"></div>