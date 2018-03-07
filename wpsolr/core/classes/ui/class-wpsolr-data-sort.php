<?php

namespace wpsolr\core\classes\ui;
use wpsolr\core\classes\extensions\localization\OptionLocalization;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Translate;

/**
 * Sort data
 *
 * Class WPSOLR_Data_Sort
 * @package wpsolr\core\classes\ui
 */
class WPSOLR_Data_Sort {

	// Labels for field values
	protected static $fields_items_labels;

	/**
	 * @param $sorts_selected
	 * @param $sorts_labels_selected
	 * @param $sort_selected_in_url
	 * @param $localization_options
	 *
	 * @return array    [
	 *                      ['id' => '_price_str_desc',         'name' => 'More expensive'],
	 *                      ['id' => '_price_str_asc',          'name' => 'Cheapest'],
	 *                      ['id' => 'sort_by_relevancy_desc',  'name' => 'More relevant'],
	 *                  ]
	 */
	public static function get_data( $sorts_selected, $sorts_labels_selected, $sort_selected_in_url, $localization_options ) {

		// Filter the sorts selected
		$sorts_selected = apply_filters( WPSOLR_Events::WPSOLR_FILTER_SORT_FIELDS, $sorts_selected );

		$results           = [];
		$results['items']  = [];
		$results['header'] = OptionLocalization::get_term( $localization_options, 'sort_header' );

		if ( is_array( $sorts_selected ) && ! empty( $sorts_selected ) ) {

			foreach ( $sorts_selected as $sort_code ) {

				$sort_label = WPSOLR_Translate::translate_field(
					WPSOLR_Option::TRANSLATION_DOMAIN_SORT_LABEL,
					$sort_code,
					! empty( $sorts_labels_selected[ $sort_code ] ) ? $sorts_labels_selected[ $sort_code ] : ''
				);

				if ( $sort_label === $sort_code ) {
					// Sort label not changed by filter
					// Try to make a decent label from the facet raw id
					$sort_label = OptionLocalization::get_term( $localization_options, $sort_code );
				}

				$sort             = [];
				$sort['id']       = $sort_code;
				$sort['name']     = $sort_label;
				$sort['selected'] = ( $sort_code === $sort_selected_in_url );

				// Add sort to results
				array_push( $results['items'], $sort );
			}


			return $results;
		}
	}
}
