<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\ui\layout\WPSOLR_UI_Layout_Abstract;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;

?>

<div style="display:none"
     class="wpsolr-remove-if-hidden wpsolr_facet_type <?php echo WPSOLR_UI_Layout_Abstract::get_css_class_feature_layouts( WPSOLR_UI_Layout_Abstract::FEATURE_LOCALIZATION ); ?>">

	<?php

	$facet_name_standard = ( 'categories' === $dis_text ) ? 'category' : ( 'tags' === $dis_text ? 'post_tag' : $dis_text );

	// Let others a chance to tell us what facet items are.
	$facet_items = apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACET_ITEMS, [], $facet_name_standard, $selected_val );

	// Well, it's up to us then.
	if ( empty( $facet_items ) ) {
		if ( taxonomy_exists( $facet_name_standard ) ) {
			$facet_items = get_terms( [ 'taxonomy' => $facet_name_standard, 'fields' => 'names', 'number' => '50' ] );

		} elseif ( 'type' === $selected_val ) {

			$post_types  = get_post_types();
			$facet_items = [ 'attachment' ];
			foreach ( $post_types as $post_type ) {
				if ( 'attachment' !== $post_type && 'revision' !== $post_type && 'nav_menu_item' !== $post_type ) {
					array_push( $facet_items, $post_type );
				}
			}
		} else {
			// Custom fields
			global $wpdb;

			$facet_items = $wpdb->get_col( $wpdb->prepare( "
                              SELECT distinct meta_value
                                  FROM {$wpdb->prefix}postmeta
                                  WHERE meta_key = %s
                                  AND char_length(meta_value) < 100 /* Prevent overflow with huge custom field values */
                                  ORDER BY meta_value ASC
                                  LIMIT 50
                                  ", $dis_text )
			);

		}
	}

	?>

	<?php

	if ( ! empty( $facet_items ) && ! empty( $facets_layout_available[ $current_layout_id ] ) ) {

		/** @var WPSOLR_UI_Layout_Abstract $layout_object */
		$layout_object = $facets_layout_available[ $current_layout_id ];

		$button_open_localizations = $layout_object->get_button_localize_label();
		?>

        <input name="collapser" type="button" class="button-primary wpsolr_collapser <?php echo $selected_val; ?>"
               value="<?php echo $button_open_localizations; ?>">

        <div class="wpsolr_collapsed">

			<?php foreach ( $facet_items as $facet_item_label ) {
				if ( ! empty( $facet_item_label ) ) {
					?>

                    <div class="wdm_row" style="top-margin:5px;">
                        <div class='col_left'>
							<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, sprintf( '%s', ucfirst( $facet_item_label ) ), true ); ?>
                        </div>
						<?php
						$facet_label = ( ! empty( $selected_facets_item_labels[ $selected_val ] ) && ! empty( $selected_facets_item_labels[ $selected_val ][ $facet_item_label ] ) )
							? $selected_facets_item_labels[ $selected_val ][ $facet_item_label ] : '';
						?>
                        <div class='col_right'>
							<?php
							include 'facet-theme-layout-localizations-field.inc.php';
							if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_FACET_THEME_COLOR_PICKER_TEMPLATE_LOCALIZATION ) ) ) {
								require $file_to_include;
							}
							if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_FACET_SEO_TEMPLATE_LOCALIZATION ) ) ) {
								require $file_to_include;
							}
							?>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="wdm_row" style="top-margin:5px;">
                        <div class='col_left'>
                        </div>
						<?php
						$is_default = ( ! empty( $selected_facets_item_is_default[ $selected_val ] ) && ! empty( $selected_facets_item_is_default[ $selected_val ][ $facet_item_label ] )
						                && ! empty( $selected_facets_item_is_default[ $selected_val ][ $facet_item_label ] ) );
						?>
                        <div class='col_right'>
                            <input type='checkbox' class="wpsolr-remove-if-empty"
                                   name='wdm_solr_facet_data[<?php echo WPSOLR_Option::OPTION_FACET_FACETS_ITEMS_IS_DEFAULT; ?>][<?php echo $selected_val; ?>][<?php echo $facet_item_label; ?>]'
                                   value='1'
								<?php echo checked( $is_default ); ?>
								<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ); ?>
                            />
                            Pre-select "<?php echo $facet_item_label; ?>".

                        </div>
                        <div class="clear"></div>
                    </div>
				<?php }
			} ?>
        </div>
	<?php } ?>

</div>
