<?php

namespace wpsolr\core\classes\ui;

use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\utilities\WPSOLR_Translate;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Facets data
 * Class WPSOLR_Data_Facets
 * @package wpsolr\core\classes\ui
 *
 */
class WPSOLR_Data_Facets {

	// Labels for field values
	protected static $fields_items_labels;

	/**
	 * @param array $facets_selected
	 * @param array $facets_to_display
	 * @param array $facets_in_results
	 * @param array $options
	 *
	 * @return array [
	 *                  {"items":[{"name":"post","count":5,"selected":true}],"id":"type","name":"Type"},
	 * {"items":[{"name":"admin","count":6,"selected":false}],"id":"author","name":"Author"},
	 * {"items":[{"name":"Blog","count":13,"selected":true}],"id":"categories","name":"Categories"}
	 * ]
	 */
	public static function get_data( $facets_selected, $facets_to_display, $facets_in_results, $options = [] ) {

		$results = [];

		if ( count( $facets_in_results ) && count( $facets_to_display ) ) {

			$facets_labels = WPSOLR_Service_Container::getOption()->get_facets_labels();
			$facets_grids  = WPSOLR_Service_Container::getOption()->get_facets_grid();

			$facets_skins       = WPSOLR_Service_Container::getOption()->get_facets_skin();
			$facets_js          = WPSOLR_Service_Container::getOption()->get_facets_js();
			$facets_is_multiple = WPSOLR_Service_Container::getOption()->get_facets_is_multiple();
			$facet_placeholder  = WPSOLR_Service_Container::getOption()->get_facets_placeholder();
			$facets_size        = WPSOLR_Service_Container::getOption()->get_facets_size();

			if ( ! empty( $options['facets_skins'] ) ) {
				// Use specific facet skins (widget selection, shortcode selection) instead of skins stored on facets.
				$facets_skins = array_merge( $facets_skins, $options['facets_skins'] );
			}

			$facets_in_results = apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACETS_CONTENT_TO_DISPLAY, $facets_in_results );

			foreach ( $facets_to_display as $facet_to_display_id ) {

				if ( isset( $facets_in_results[ $facet_to_display_id ] ) && count( $facets_in_results[ $facet_to_display_id ]['values'] ) > 0 ) {

					$facet_with_no_blank_id = strtolower( str_replace( ' ', '_', $facet_to_display_id ) );

					$facet_to_display_name = WPSOLR_Translate::translate_field_custom_field(
						WPSOLR_Option::TRANSLATION_DOMAIN_FACET_LABEL,
						$facet_to_display_id,
						! empty( $facets_labels[ $facet_to_display_id ] ) ? $facets_labels[ $facet_to_display_id ] : ''
					);

					$facet                         = [];
					$facet['items']                = [];
					$facet['id']                   = $facet_to_display_id;
					$facet['name']                 = $facet_to_display_name;
					$facet['facet_type']           = $facets_in_results[ $facet_to_display_id ]['facet_type'];
					$facet['facet_layout_id']      = ! empty( $facets_in_results[ $facet_to_display_id ]['facet_layout_id'] ) ? $facets_in_results[ $facet_to_display_id ]['facet_layout_id'] : '';
					$facet['facet_layout_skin_id'] = ! empty( $facets_skins[ $facet_to_display_id ] ) ? $facets_skins[ $facet_to_display_id ] : '';
					$facet['facet_grid']           = ! empty( $facets_grids[ $facet_to_display_id ] ) ? $facets_grids[ $facet_to_display_id ] : '';
					$facet['facet_size']           = ! empty( $facets_size[ $facet_to_display_id ] ) ? $facets_size[ $facet_to_display_id ] : '';

					$facet_layout_skin_js = ! empty( $facets_js[ $facet_to_display_id ] ) ? $facets_js[ $facet_to_display_id ] : '';
					if ( ! empty( trim( $facet_layout_skin_js ) ) ) {
						// Give plugins a chance to change the facet name (WPML, POLYLANG).
						$facet_layout_skin_js = apply_filters(
							WPSOLR_Events::WPSOLR_FILTER_TRANSLATION_STRING,
							$facet_layout_skin_js,
							[
								'domain' => WPSOLR_Option::TRANSLATION_DOMAIN_FACET_JS,
								'name'   => $facet_to_display_id,
								'text'   => $facet_layout_skin_js,
							]
						);

					}
					$facet['facet_layout_skin_js'] = $facet_layout_skin_js;

					if ( ! empty( $facets_is_multiple[ $facet_to_display_id ] ) ) {
						$facet['facet_is_multiple'] = true;
					}
					$facet['facet_placeholder'] =
						! empty( $facet_placeholder[ $facet_to_display_id ] )
							? apply_filters( WPSOLR_Events::WPSOLR_FILTER_TRANSLATION_STRING, $facet_placeholder[ $facet_to_display_id ],
							[
								'domain' => WPSOLR_Option::TRANSLATION_DOMAIN_FACET_PLACEHOLDER,
								'name'   => $facet_to_display_id,
								'text'   => $facet_placeholder[ $facet_to_display_id ],
							]
						)
							: '';

					switch ( $facet['facet_type'] ) {
						case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_RANGE:
							$facet['facet_template'] = $facets_in_results[ $facet_to_display_id ]['facet_template'];
							break;

					}

					$items_hierarchy = [];
					self::build_hierarchies(
						$items_hierarchy,
						$facet_to_display_id,
						$facets_in_results[ $facet_to_display_id ]['values'],
						! empty( $facets_selected[ $facet_with_no_blank_id ] ) ? $facets_selected[ $facet_with_no_blank_id ] : []
					);

					foreach ( $items_hierarchy as $facet_in_results ) {

						$facet_item = [
							'value'    => $facet_in_results['value'],
							'count'    => $facet_in_results['count'],
							'items'    => $facet_in_results['items'],
							'selected' => $facet_in_results['selected'],
						];

						switch ( $facet['facet_type'] ) {
							case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_RANGE:

								$facet_item['value_localized'] = ( ! empty( $facet_in_results['value_localized'] ) && ( $facet_in_results['value_localized'] !== $facet_in_results['value'] ) ) ? $facet_in_results['value_localized'] : $facet['facet_template'];

								// Generate the end value for regular ranges, from the value and the gap
								$range                     = explode( '-', $facet_item['value'] );
								$facet_item['range_start'] = $range[0];
								$facet_item['range_end']   = $range[1];
								break;

							case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_MIN_MAX:

								// Generate the min/max values
								$min_max           = explode( '-', $facet_item['value'] );
								$facet_item['min'] = $min_max[0];
								$facet_item['max'] = $min_max[1];

								$from_top           = explode( '-', ! empty( $facets_selected[ $facet_with_no_blank_id ] ) ? $facets_selected[ $facet_with_no_blank_id ][0] : $facet_item['value'] );
								$facet_item['from'] = isset( $from_top[0] ) ? $from_top[0] : $facet_item['min'];
								$facet_item['to']   = isset( $from_top[1] ) ? $from_top[1] : $facet_item['max'];

								break;

							default:
								$facet_item['value_localized'] = ! empty( $facet_in_results['value_localized'] ) ? $facet_in_results['value_localized'] : $facet_in_results['value'];
								break;

						}

						array_push(
							$facet['items'],
							$facet_item
						);

					}

					// Add current facet to results
					array_push( $results, $facet );
				}
			}
		}

		// Update the facets data if necessary
		$results = apply_filters( WPSOLR_Events::WPSOLR_FILTER_UPDATE_FACETS_DATA, $results );

		return $results;
	}


	/**
	 * Build a hierachy of facets when facet name contains WpSolrSchema::FACET_HIERARCHY_SEPARATOR
	 * Recursive
	 *
	 * @param array $results
	 * @param string $facet_to_display_id
	 * @param string $facet_type
	 * @param array $items
	 * @param array $facets_selected
	 */
	public
	static function build_hierarchies(
		&$results, $facet_to_display_id, $items, $facets_selected
	) {

		$result = [];
		foreach ( $items as $item ) {

			$item_hierarcy_item_names = explode( WpSolrSchema::FACET_HIERARCHY_SEPARATOR, $item['value'] );
			$item_top_level_name      = array_shift( $item_hierarcy_item_names );

			if ( empty( $result[ $item_top_level_name ] ) ) {

				$result[ $item_top_level_name ]          = [
					'value'           => $item_top_level_name,
					'value_localized' => self::get_field_item_value_localization( $facet_to_display_id, $item_top_level_name, null ),
					'count'           => $item['count'],
					'selected'        => isset( $facets_selected ) && ( in_array( $item_top_level_name, $facets_selected, true ) ),
				];
				$result[ $item_top_level_name ]['items'] = [];
			}

			if ( ! empty( $item_hierarcy_item_names ) ) {

				array_push( $result[ $item_top_level_name ]['items'],
					[
						'value'    => implode( WpSolrSchema::FACET_HIERARCHY_SEPARATOR, $item_hierarcy_item_names ),
						'count'    => $item['count'],
						'selected' => isset( $facets_selected ) && ( in_array( $item_top_level_name, $facets_selected, true ) ),
					]
				);
			}
		}

		foreach ( $result as $top_name => $sub_items ) {

			$level = [
				'value'           => $sub_items['value'],
				'value_localized' => ! empty( $sub_items['value_localized'] ) ? $sub_items['value_localized'] : $sub_items['value'],
				'count'           => $sub_items['count'],
				'selected'        => $sub_items['selected'],
				'items'           => [],
			];

			if ( ! empty( $sub_items['items'] ) ) {

				self::build_hierarchies( $level['items'], $facet_to_display_id, $sub_items['items'], $facets_selected );
			}

			// Calculate the count by summing children count
			if ( ! empty( $level['items'] ) ) {

				$count = 0;
				foreach ( $level['items'] as $item ) {

					$count += $item['count'];
				}
				$level['count'] = $count;
			}

			array_push( $results, $level );
		}

	}

	/**
	 * Replace a field item value by it's localization.
	 * Example: on field 'color': '#81d742' => 'green', '#81d742' => 'vert'
	 *
	 * @param $field_name
	 * @param $field_value
	 * @param $language
	 *
	 * @return mixed
	 */
	public
	static function get_field_item_value_localization(
		$field_name, $field_value, $language
	) {

		$value = $field_value;

		if ( null === self::$fields_items_labels ) {

			// Init the items labels once, only for field WpSolrSchema::_FIELD_NAME_TYPE
			self::$fields_items_labels = WPSOLR_Service_Container::getOption()->get_facets_items_labels();
		}

		if ( ( ! empty( self::$fields_items_labels[ $field_name ] ) ) ) {

			if ( ! empty( self::$fields_items_labels[ $field_name ][ $field_value ] ) ) {

				$value = apply_filters( WPSOLR_Events::WPSOLR_FILTER_TRANSLATION_STRING, $field_value,
					[
						'domain'   => WPSOLR_Option::TRANSLATION_DOMAIN_FACET_LABEL,
						'name'     => $field_value,
						'text'     => self::$fields_items_labels[ $field_name ][ $field_value ],
						'language' => $language,
					]
				);

				if ( $value === $field_value ) {
					// No translation for this value, try to get the localization instead.

					$value = ! empty( self::$fields_items_labels[ $field_name ][ $field_value ] ) ? self::$fields_items_labels[ $field_name ][ $field_value ] : $field_value;
				}
			}
		}

		return $value;
	}

}
