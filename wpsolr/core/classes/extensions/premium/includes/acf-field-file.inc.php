<?php
use wpsolr\core\classes\extensions\licenses\OptionLicenses;

?>

<div class="wpsolr-metabox-row-content">
    <label for="<?php echo esc_attr( self::METABOX_FIELD_IS_DO_INDEX_ACF_FIELD_FILES ); ?>">
        <input type="checkbox"
               name="<?php echo esc_attr( self::METABOX_FIELD_IS_DO_INDEX_ACF_FIELD_FILES ); ?>"
               id="<?php echo esc_attr( self::METABOX_FIELD_IS_DO_INDEX_ACF_FIELD_FILES ); ?>"
               value="<?php echo esc_attr( self::METABOX_CHECKBOX_YES ); ?>" <?php if ( isset ( $post_meta[ self::METABOX_FIELD_IS_DO_INDEX_ACF_FIELD_FILES ] ) ) {
			checked( $post_meta[ self::METABOX_FIELD_IS_DO_INDEX_ACF_FIELD_FILES ][0], self::METABOX_CHECKBOX_YES );
		} ?>
			<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_ACF ); ?>
        />
		<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_ACF, _x( 'Search in ACF fields file', 'wpsolr' ), true, true ); ?>
    </label>
</div>
