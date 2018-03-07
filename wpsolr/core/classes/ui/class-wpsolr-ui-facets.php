<?php

namespace wpsolr\core\classes\ui;

use wpsolr\core\classes\extensions\localization\OptionLocalization;
use wpsolr\core\classes\ui\layout\checkboxes\WPSOLR_UI_Layout_Check_Box;
use wpsolr\core\classes\ui\layout\WPSOLR_UI_Layout_Abstract;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;


/**
 * Display facets
 *
 * Class WPSOLR_UI_Facets
 * @package wpsolr\core\classes\ui
 */
class WPSOLR_UI_Facets {

	/**
	 * Build facets UI
	 *
	 * @param array $facets
	 * @param $localization_options array
	 *
	 * @return string
	 */
	public static function Build( $facets, $localization_options ) {

		$html = apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACETS_REPLACE_HTML, null, $facets, $localization_options );
		if ( null !== $html ) {
			return $html;
		}

		// Starts with some custom css.
		$html = apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACETS_CSS, '' );

		if ( ! empty( $facets ) ) {

			$facets_template = OptionLocalization::get_term( $localization_options, 'facets_element' );
			$facet_title     = OptionLocalization::get_term( $localization_options, 'facets_title' );

			foreach ( $facets as &$facet ) {

				// Get the layout object
				$facet_layout_id = ( ! empty( $facet['facet_layout_id'] ) ) ? $facet['facet_layout_id'] : WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID;
				/** @var WPSOLR_UI_Layout_Abstract $layout_object */
				$layout_object = apply_filters( WPSOLR_Events::WPSOLR_FILTER_LAYOUT_OBJECT, null, $facet_layout_id );
				if ( is_null( $layout_object ) ) {
					// Back to default layout
					$layout_object = new WPSOLR_UI_Layout_Check_Box();
				}

				// Unique uuid for each facet. used to inject specific css/js to each facet.
				$facet_class_uuid = $layout_object->get_class_uuid();

				$facet_layout_skin_id = $layout_object->get_skin_id( $facet );
				if ( ! empty( $facet_layout_id ) ) {
					if ( 'wpsolr_no_skin' === $facet_layout_skin_id ) {
						// This facet is not to be displayed

						continue;

					} else {
						// Add layout javascript/css code and files
						$html .= $layout_object->generate_skin_js( $facet, $facet_class_uuid );
					}
				}

				$html .= sprintf( '<div class="wpsolr_facet_title %s_%s">%s</div>', WPSOLR_UI_Layout_Abstract::CLASS_PREFIX, $facet['id'], sprintf( $facet_title, $facet['name'] ) );

				// Use the current facet template, else use the general facets template.
				$facet_template = ! empty( $facet['facet_template'] ) ? $facet['facet_template'] : $facets_template;

				$facet_grid = ! empty( $facet['facet_grid'] ) ? $facet['facet_grid'] : '';
				switch ( $facet_grid ) {
					case WPSOLR_Option::OPTION_FACET_GRID_HORIZONTAL:
						$facet_grid_class = 'wpsolr_facet_column_horizontal';
						break;

					case WPSOLR_Option::OPTION_FACET_GRID_1_COLUMN:
						$facet_grid_class = 'wpsolr_facet_columns wpsolr_facet_column_1';
						break;

					case WPSOLR_Option::OPTION_FACET_GRID_2_COLUMNS:
						$facet_grid_class = 'wpsolr_facet_columns wpsolr_facet_column_2';
						break;

					case WPSOLR_Option::OPTION_FACET_GRID_3_COLUMNS:
						$facet_grid_class = 'wpsolr_facet_columns wpsolr_facet_column_3';
						break;

					default;
						$facet_grid_class = ''; //'wpsolr_facet_columns wpsolr_facet_column_1';
						break;
				}

				$facet_grid_class .= ' wpsolr_facet_scroll';

				$facet['facet_layout_class']      = $layout_object->get_css_class_name();
				$facet['facet_layout_skin_class'] = $layout_object->get_css_skin_class_name( $facet['facet_layout_skin_id'] );

				$layout_object->displayFacetHierarchy( $facet_class_uuid, $facet_template, $facet_grid_class, $html, $facet, ! empty( $facet['items'] ) ? $facet['items'] : [] );
			}

			$is_facet_selected           = true;
			$remove_item_localization    = OptionLocalization::get_term( $localization_options, 'facets_element_all_results' );
			$is_generate_facet_permalink = apply_filters( WPSOLR_Events::WPSOLR_FILTER_IS_GENERATE_FACET_PERMALINK, false );
			if ( $is_generate_facet_permalink ) {
				// Link to the current page or to the permalinks home ?

				$redirect_facets_home = apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACET_PERMALINK_HOME, '' );
				$html_remove_item     = sprintf( '<a class="wpsolr_permalink" href="%s" %s title="%s">%s</a>',
					! empty( $redirect_facets_home ) ? ( '/' . $redirect_facets_home ) : './', '',
					$remove_item_localization, $remove_item_localization );

			} else {

				$html_remove_item = $remove_item_localization;
			}

			$html = sprintf( "<div><label class='wdm_label'>%s</label>
                                    <input type='hidden' name='sel_fac_field' id='sel_fac_field' >
                                    <div class='wdm_ul' id='wpsolr_section_facets'>
                                    <div class='%s'><div class='select_opt' id='wpsolr_remove_facets' data-wpsolr-facet-data='%s'>%s</div></div>",
					OptionLocalization::get_term( $localization_options, 'facets_header' ),
					'wpsolr_facet_checkbox' . ( $is_facet_selected ? ' checked' : '' ),
					wp_json_encode( [ 'type' => WPSOLR_Option::OPTION_FACET_FACETS_TYPE_FIELD ] ),
					$html_remove_item
			        )
			        . $html;

			$html .= '</div></div>';
		}

		return $html;
	}

}
