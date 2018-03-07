<?php
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Option;

?>

<div class="wdm_row">
    <div class='col_left'>
		<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Show suggestions in the search box', true, true ); ?>
    </div>
    <div class='col_right'>
        <select
                name="wdm_solr_res_data[<?php echo WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE; ?>]">
			<?php
			$options = [
				[
					'code'     => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_NONE,
					'label'    => 'No suggestions',
					'disabled' => $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM, true ),
				],
				[
					'code'  => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_KEYWORDS,
					'label' => 'Suggest Keywords',
				],
				[
					'code'     => WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_POSTS,
					'label'    => 'Suggest Products',
					'disabled' => $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_PREMIUM, true ),
				],
			];

			$search_suggest_content_type = WPSOLR_Service_Container::getOption()->get_search_suggest_content_type();
			foreach ( $options as $option ) {
				$selected = ( $option['code'] === $search_suggest_content_type ) || ( empty( $solr_res_options[ WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE ] ) && WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_KEYWORDS === $option['code'] ) ? 'selected' : '';
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

        By default, suggestions are shown only with the WPSOLR Ajax theme's search
        form.
        Use the jQuery selectors field below to show suggestions on your own theme's
        search forms.
    </div>
    <div class="clear"></div>
</div>
