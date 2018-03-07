<?php
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\WPSOLR_Events;

?>

<div class="wdm_row">
    <div class='col_left'>
		<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Medias to be indexed', true ); ?>

		<?php
		if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_CHECKER ) ) ) {
			require $file_to_include;
		}
		?>
    </div>
    <div class='col_right'>
        <input type='hidden' name='wdm_solr_form_data[attachment_types]'
               id='attachment_types'>
		<?php
		$attachment_types_opt = WPSOLR_Service_Container::getOption()->get_option_index_attachment_types_str();
		$disabled             = $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM );
		// sort attachments
		asort( $allowed_attachments_types );

		// Selected first
		foreach ( $allowed_attachments_types as $type ) {
			if ( strpos( $attachment_types_opt, $type ) !== false ) {
				?>
                <input type='checkbox' name='attachment_types' class="wpsolr_checked" value='<?php echo $type ?>'
					<?php echo $disabled; ?>
                       checked> <?php echo $type ?>
                <br>
				<?php
			}
		}

		// Unselected 2nd
		foreach ( $allowed_attachments_types as $type ) {
			if ( strpos( $attachment_types_opt, $type ) === false ) {
				?>
                <input type='checkbox' name='attachment_types' class="wpsolr_checked" value='<?php echo $type ?>'
					<?php echo $disabled; ?>
                > <?php echo $type ?>
                <br>
				<?php
			}
		}

		?>
    </div>
    <div class="clear"></div>
</div>
