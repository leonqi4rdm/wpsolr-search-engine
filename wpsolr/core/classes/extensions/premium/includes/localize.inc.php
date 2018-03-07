<?php
use wpsolr\core\classes\extensions\licenses\OptionLicenses;

?>

<div class='wdm_row'>
    <div class='col_left'>
		<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Choose how the front-end texts are localized', true ); ?>
    </div>
    <div class='col_right'>

		<?php
		$select_options = [
			'localization_by_admin_options' => 'Use this page to localize all front-end texts',
			'localization_by_other_means'   => 'Use your theme/plugin .mo files or WPML string module to localize all front-end texts'
		];
		?>

        <select name='wdm_solr_localization_data[localization_method]' id='wpsolr_localization_method'>
			<?php foreach ( $select_options as $option_code => $option_label ) {

				echo sprintf( "<option value='%s' %s %s>%s</option>",
					$option_code,
					isset( $options['localization_method'] ) && $options['localization_method'] === $option_code ? "selected" : "",
					( 'localization_by_other_means' === $option_code ) ? $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ) : '',
					$option_label );

			}
			?>
        </select>

        <br/><br/>
        You can find a wpsolr.pot file in WPSOLR's /languages folder.
        <br/>
        Use it to create your .po and .mo files (wpsolr-fr_FR.mo and wpsolr-fr_FR.po).
        <br/>
        Copy your .mo files in the Wordpress languages plugin directory (WP_LANG_DIR/plugins).
        <br/>
        Example: /htdocs/wp-includes/languages/plugins/wpsolr-fr_FR.mo or
        /htdocs/wp-content/languages/plugins/wpsolr-fr_FR.mo
        <br/>

    </div>
</div>
<div style="clear:both"></div>