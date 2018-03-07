<?php

namespace wpsolr\core\classes\ui\layout\select;

use wpsolr\core\classes\ui\layout\WPSOLR_UI_Layout_Abstract;
use wpsolr\core\classes\utilities\WPSOLR_Option;

/**
 * Class WPSOLR_UI_Layout_Select
 * @package wpsolr\pro\extensions\theme\layout\select
 */
class WPSOLR_UI_Layout_Select extends WPSOLR_UI_Layout_Abstract {

	const CHILD_LAYOUT_ID = 'id_select';

	// Class of all select objects
	const INNER_CLASS = 'wpsolr-select';
	const INNER_CLASS_MULTIPLE = 'wpsolr-select-multiple';

	/**
	 * @inheritdoc
	 */
	function get_css_class_name() {
		return 'wpsolr_facet_select';
	}

	/**
	 * @inheritdoc
	 */
	public static function get_label() {
		return 'Select box';
	}

	/**
	 * @inheritdoc
	 */
	public static function get_is_enabled() {
		return true;
	}

	/**
	 * @inheritdoc
	 */
	static function get_types() {
		return []; // All field types ok
	}

	/**
	 * @inheritdoc
	 */
	static function get_skins() {
		return [];
	}

	/**
	 * @inheritdoc
	 */
	static function get_facet_type() {
		return WPSOLR_Option::OPTION_FACET_FACETS_TYPE_FIELD;
	}

	/**
	 * @inheritdoc
	 */
	static function get_files() {
		return [];
	}

	/**
	 * @inheritdoc
	 */
	protected function child_facet_header( &$html, &$items, $facet, $level ) {

		if ( 0 === $level ) {

			$is_multiple            = ! empty( $facet ['facet_is_multiple'] );
			$html_property_multiple = $is_multiple ? 'multiple' : '';
			$facet_placeholder      = ! empty( $facet['facet_placeholder'] ) ? $facet['facet_placeholder'] : '';
			$facet_size             = ! empty( $facet['facet_size'] ) ? $facet['facet_size'] : '';
			$facet_size_property    = ! empty( $facet_size ) ? "size='{$facet_size}'" : '';

			if ( ! $is_multiple ) {
				// Add an empty value for single select boxes
				array_unshift( $items, [
					'value'           => '',
					'count'           => - 1,
					'items'           => [],
					'selected'        => false,
					'value_localized' => $facet_placeholder
				] );
			}

			$html .= "<select {$facet_size_property} class='{$this->get_inner_class($is_multiple)}' {$html_property_multiple} data-placeholder='{$facet_placeholder}'>";
		}

	}

	/**
	 * @inheritdoc
	 */
	protected function child_facet_footer( &$html, $level ) {

		if ( 0 === $level ) {
			$html .= "</select>";
		}

	}

	/**
	 * @inheritdoc
	 */
	protected function child_prepare_facet_item( $level, $facet_layout_id, $item_localized_name, &$item, $facet_label, $facet_data, &$html_item ) {

		$html_item = str_repeat( self::SELECT_LEVEL_INDENTATION_CHARACTERS, $level ) // Add an indentation for each sub level
		             . ( empty( $item['items'] ) ? $facet_label : $item_localized_name ); // only show count on leaf items (else count is false);
	}

	/**
	 * @inheritdoc
	 */
	protected function generate_html_permalink( $item, &$html_item ) {
		// Do nothing
	}


	/**
	 * @inheritdoc
	 */
	protected function child_html_add_facet_item( &$html, $facet_layout_id, $item_selected, $item_facet_class, $facet_level, $facet_id, $item_value, $facet_data_json, $html_item ) {

		$selected = ( $item_selected ? 'selected' : '' );
		$html     .= sprintf( "<option class='select_opt wpsolr_facet_option $item_facet_class $facet_level' id='$facet_id:$item_value' data-wpsolr-facet-data='$facet_data_json' value='$facet_id:$item_value' data-wpsolr-facet-data='$facet_data_json' $selected>%s</option>", $html_item );
	}

	/**
	 * @inheritdoc
	 */
	protected function get_inner_class( $is_multiple = false ) {
		return $is_multiple ? static::INNER_CLASS_MULTIPLE : static::INNER_CLASS;
	}

	/**
	 * @inheritdoc
	 */
	public function get_is_multi_filter( $is_multiple = false ) {
		return $is_multiple;
	}
}