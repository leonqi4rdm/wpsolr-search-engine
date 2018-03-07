<?php

namespace wpsolr\core\classes\extensions\premium;

use wpsolr\core\classes\extensions\WpSolrExtensions;
use wpsolr\core\classes\services\WPSOLR_Service_Container_Factory;
use wpsolr\core\classes\ui\layout\checkboxes\WPSOLR_UI_Layout_Check_Box;
use wpsolr\core\classes\ui\layout\select\WPSOLR_UI_Layout_Select;
use wpsolr\core\classes\ui\layout\WPSOLR_UI_Layout_Abstract;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\WPSOLR_Events;

/**
 * Class WPSOLR_Option_Premium
 * @package wpsolr\core\classes\extensions\premium
 */
class WPSOLR_Option_Premium extends WpSolrExtensions {
	use WPSOLR_Service_Container_Factory;

	/*
	 * Constructor
	 * Subscribe to actions
	 */

	/**
	 * Constructor.
	 */
	function __construct() {

		add_action( WPSOLR_Events::WPSOLR_FILTER_LAYOUT_OBJECT, [
			$this,
			'wpsolr_filter_layout_object',
		], 10, 2 );

		add_filter( WPSOLR_Events::WPSOLR_FILTER_FACET_FEATURE_LAYOUTS, [
			$this,
			'wpsolr_filter_facet_feature_layouts'
		], 10, 2 );

		add_filter( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, [ $this, 'wpsolr_filter_include_file' ], 10, 1 );

		add_action( WPSOLR_Events::WPSOLR_ACTION_OPTION_SET_REALTIME_INDEXING, [
			$this,
			'wpsolr_action_option_set_realtime_indexing'
		], 10, 1 );
	}

	/**
	 * Activate/deactivate real-time indexing
	 *
	 * @param boolean $is_active
	 */
	public function wpsolr_action_option_set_realtime_indexing( $is_active ) {

		$service_options = $this->get_container()->get_service_option();

		$is_realtime_indexing = $service_options->get_index_is_real_time();

		// Change the settings only if it's different
		if ( $is_realtime_indexing !== $is_active ) {

			$option = $service_options->get_option_index();

			if ( ! $is_active ) {
				// Set as 'no real-time'

				$option[ $service_options::OPTION_INDEX_IS_REAL_TIME ] = '1';

			} else {
				// Set as 'real-time'

				unset( $option[ $service_options::OPTION_INDEX_IS_REAL_TIME ] );
			}

			update_option( $service_options::OPTION_INDEX, $option );

		}
	}

	/**
	 * Include the file containing the help feature.
	 *
	 * @param int $help_id
	 *
	 * @return string File name & path
	 */
	public function wpsolr_filter_include_file( $help_id ) {

		switch ( $help_id ) {
			case WPSOLR_Help::HELP_MULTI_SITE:
				$file_name = 'search-network.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_TEMPLATE:
				$file_name = 'search-template.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_PAGE_SLUG:
				$file_name = 'search-page-slug.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_INFINITE_SCROLL:
				$file_name = 'search-infinite-scroll.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_SUGGESTIONS:
				$file_name = 'search-suggestions.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_SUGGESTIONS_JQUERY_SELECTOR:
				$file_name = 'search-suggestions-jquery-selectors.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_DID_YOU_MEAN:
				$file_name = 'search-did-you-mean.inc.php';
				break;

			case WPSOLR_Help::HELP_INDEXING_STOP_REAL_TIME:
				$file_name = 'indexing-stop-real-time.inc.php';
				break;

			case WPSOLR_Help::HELP_INDEXING_POST_TYPES:
				$file_name = 'indexing-post-types.inc.php';
				break;

			case WPSOLR_Help::HELP_INDEXING_TAXONOMIES:
				$file_name = 'indexing-taxonomies.inc.php';
				break;

			case WPSOLR_Help::HELP_INDEXING_CUSTOM_FIELDS:
				$file_name = 'indexing-custom-fields.inc.php';
				break;

			case WPSOLR_Help::HELP_INDEXING_ATTACHMENTS:
				$file_name = 'indexing-attachments.inc.php';
				break;

			case WPSOLR_Help::HELP_SEARCH_BOOSTS:
				$file_name = 'search-boosts.inc.php';
				break;

			case WPSOLR_Help::HELP_FACET_LABEL:
				$file_name = 'search-facet-label.inc.php';
				break;

			case WPSOLR_Help::HELP_SORT_LABEL:
				$file_name = 'search-sort-label.inc.php';
				break;

			case WPSOLR_Help::HELP_BATCH_DEBUG:
				$file_name = 'batch-debug.inc.php';
				break;

			case WPSOLR_Help::HELP_BATCH_MODE_REPLACE:
				$file_name = 'batch-mode-replace.inc.php';
				break;

			case WPSOLR_Help::HELP_ACF_FIELD_FILE:
				$file_name = 'acf-field-file.inc.php';
				break;

			case WPSOLR_Help::HELP_LOCALIZE:
				$file_name = 'localize.inc.php';
				break;

			case WPSOLR_Help::HELP_MULTI_INDEX:
				$file_name = 'multi-index.inc.php';
				break;

			case WPSOLR_Help::HELP_TOOLSET_FIELD_FILE:
				$file_name = 'toolset-field-file.inc.php';
				break;

			case WPSOLR_Help::HELP_THEME_FACET_LAYOUT:
				$file_name = '/theme/facet-theme-layout.inc.php';
				break;

			case WPSOLR_Help::HELP_CHECKER:
				$file_name = '/utils/checker.inc.php';
				break;

			default:
				$file_name = '';
		}

		return ! empty( $file_name ) ? sprintf( '%s/includes/%s', dirname( __FILE__ ), $file_name ) : $help_id;
	}
}