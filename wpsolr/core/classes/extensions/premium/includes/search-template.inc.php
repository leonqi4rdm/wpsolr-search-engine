<?php
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Help;

?>

<div class="wdm_row">
    <div class='col_left'>
		<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Search template', true ); ?>
		<?php echo WPSOLR_Help::get_help( WPSOLR_Help::HELP_SEARCH_TEMPLATE ); ?>
    </div>
    <div class='col_right'>
        <select name="wdm_solr_res_data[search_method]">
			<?php
			$options = [
				[
					'code'     => 'use_current_theme_search_template',
					'label'    => 'Use my current theme search template without ajax (with widget Facets and widget Sort)',
					'disabled' => $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ),
				],
				[
					'code'     => 'use_current_theme_search_template_with_ajax',
					'label'    => 'Use my current theme search template with Ajax (with widget Facets and widget Sort)',
					'disabled' => $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM ),
				],
				[
					'code'  => 'ajax',
					'label' => 'Use WPSOLR custom Ajax search templates',
				],
				[
					'code'  => 'ajax_with_parameters',
					'label' => 'Use WPSOLR custom Ajax search templates and show parameters in url',
				],
			];

			$search_method = WPSOLR_Service_Container::getOption()->get_search_method();
			foreach ( $options as $option ) {
				$selected = $option['code'] === $search_method ? 'selected' : '';
				$disabled = isset( $option['disabled'] ) ? $option['disabled'] : '';
				?>
                <option
                        value="<?php echo $option['code'] ?>"
					<?php echo $selected ?>
					<?php echo $disabled ?>>
					<?php echo $option['label'] ?>
                </option>
			<?php } ?>
        </select>

        <p>Select a tempate to show your search results. Eventually use the Theme extension options to choose more
            presentation options.</p>

    </div>
    <div class="clear"></div>
</div>

