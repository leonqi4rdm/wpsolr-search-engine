<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

?>

<div class="wdm_row">
    <div class='col_left'>
		<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Custom taxonomies to be indexed', true ); ?>

		<?php
		if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_CHECKER ) ) ) {
			require $file_to_include;
		}
		?>
    </div>
    <div class='col_right'>
        <div class='cust_tax'><!--new div class given-->
            <input type='hidden' name='wdm_solr_form_data[taxonomies]'
                   id='tax_types'>
			<?php
			$taxonomies_selected = apply_filters(
				WPSOLR_Events::WPSOLR_FILTER_INDEX_TAXONOMIES_SELECTED,
				WPSOLR_Service_Container::getOption()->get_option_index_taxonomies()
			);
			$disabled            = $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM );
			if ( count( $taxonomies ) > 0 ) {

				// Selected first
				foreach ( $taxonomies as $type ) {
					if ( in_array( $type . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING, $taxonomies_selected, true ) ) {
						?>

                        <input type='checkbox' name='taxon' class="wpsolr_checked"
                               value='<?php echo $type . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ?>'
							<?php echo $disabled; ?>
                               checked
                        > <?php echo $type ?> <br>
						<?php
					}
				}

				// Unselected 2nd
				foreach ( $taxonomies as $type ) {
					if ( ! in_array( $type . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING, $taxonomies_selected, true ) ) {
						?>

                        <input type='checkbox' name='taxon' class="wpsolr_checked"
                               value='<?php echo $type . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING ?>'
							<?php echo $disabled; ?>
                        > <?php echo $type ?> <br>
						<?php
					}
				}

			} else {
				echo 'None';
			} ?>
        </div>
    </div>
    <div class="clear"></div>
</div>
