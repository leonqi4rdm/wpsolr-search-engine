<?php

namespace wpsolr\core\classes\ui\layout;

use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\ui\layout\checkboxes\WPSOLR_UI_Layout_Check_Box;
use wpsolr\core\classes\ui\layout\select\WPSOLR_UI_Layout_Select;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;


/**
 * Base class for all facet layouts
 *
 * Class WPSOLR_UI_Layout_Abstract
 * @package wpsolr\core\classes\ui\layout
 */
abstract class WPSOLR_UI_Layout_Abstract implements WPSOLR_UI_Layout_Interface {

	/**
	 * Layout features
	 */
	const FEATURE_GRID = 'feature_grid';
	const FEATURE_EXCLUSION = 'feature_exclusion';
	const FEATURE_HIERARCHY = 'feature_hierarchy';
	const FEATURE_OR = 'feature_or';
	const FEATURE_SORT_ALPHABETICALLY = 'feature_sort_alphabetically';
	const FEATURE_LOCALIZATION = 'feature_localization';
	const FEATURE_LOCALIZATION_FIELD = 'feature_localization_field';
	const FEATURE_SEO_TEMPLATE = 'feature_seo_template';
	const FEATURE_SEO_TEMPLATE_LOCALIZATION = 'feature_seo_template_localization';
	const FEATURE_SEO_TEMPLATE_RANGE = 'feature_seo_template_range';
	const FEATURE_JAVASCRIPT = 'feature_javascript';
	const FEATURE_MULTIPLE = 'feature_multiple';
	const FEATURE_PLACEHOLDER = 'feature_placeholder';
	const FEATURE_SKIN = 'feature_skin';
	const FEATURE_RANGE_REGULAR = 'feature_range_regular';
	const FEATURE_RANGE_IRREGULAR = 'feature_range_irregular';
	const FEATURE_SIZE = 'feature_size';


	const WPSOLR_FACET_SKIN_NONE_CLASS = 'wpsolr_facet_skin_none';

	const FIELD_LAYOUT_SKINS = 'skins';
	const FIELD_LABEL = 'label';
	const FIELD_SKIN_URL = 'url';
	const FIELD_OBJECT_CLASS_NAME = 'object_class_name';
	const FIELD_CSS_CLASS_NAME = 'css_class_name';
	const FIELD_LAYOUT_FILES = 'layout_files';
	const FIELD_CSS_FILES = 'css';
	const FIELD_JS_FILES = 'js';
	const FIELD_JS_HELP = 'js_help';

	// Layout class name parameter enqueued with the js layout script
	const FIELD_JS_LAYOUT_CLASS = 'js_layout_class';
	const FIELD_JS_LAYOUT_FILES = 'js_layout_files';
	const JS_FILE_ENQUEUED_PARAMETERS = 'wpsolr_localize_script_layout';


	const WPSOLR_FACET_RADIOBOX_CLASS = 'wpsolr_facet_radiobox';
	const WPSOLR_FACET_SELECT_CLASS = 'wpsolr_facet_select';
	const CLASS_PREFIX = 'wpsolr_facet';
	const TEMPLATE_LINK_REL_ATTRIBUTE = 'rel="%s"';
	const PERMALINK_LINK_TEMPLATE = '<a class="wpsolr_permalink" href="%s" %s title="%s">%s</a>';

	// Characters added to select items at the begining of each sublevel
	const SELECT_LEVEL_INDENTATION_CHARACTERS = "\u{00a0}"; // whitespace

	/**
	 * Constants to be defined in children layouts
	 */
	const CHILD_LAYOUT_ID = '';

	static protected $all_layouts = [];


	/**
	 * Get the button "localize" label
	 *
	 * @return string
	 */
	public function get_button_localize_label() {
		return 'Override each item label';
	}

	/**
	 * Get the layout help text for the custom javascript field
	 *
	 * @return string
	 */
	public static function get_js_help_text() {
		return '';
	}

	/**
	 * Get the layout id
	 *
	 * @return string
	 */
	public function get_layout_id() {
		return static::CHILD_LAYOUT_ID;
	}

	/**
	 * Unique uuid for each facet. Used to inject specific css/js to each layout.
	 * @return string
	 */
	public function get_class_uuid() {
		return sprintf( 'wpsolr_facet_class_%s', WPSOLR_Option_Indexes::generate_uuid() );
	}

	/**
	 * Get the skin id
	 *
	 * @param array $facet
	 *
	 * @return string
	 */
	public function get_skin_id( $facet ) {
		return ( ! empty( $facet['facet_layout_skin_id'] ) ) ? $facet['facet_layout_skin_id'] : '';
	}

	/**
	 * Get the sored skin custom javascript
	 *
	 * @param array $facet
	 *
	 * @return string
	 */
	public function get_skin_js( $facet ) {
		return ( ! empty( $facet['facet_layout_skin_js'] ) ) ? $facet['facet_layout_skin_js'] : '';
	}

	/**
	 * Generate the skin javascript, and load it's css/js files
	 *
	 * @param array $facet
	 *
	 * @return string
	 */
	public function generate_skin_js( $facet, $facet_class_uuid ) {

		$result               = '';
		$script               = '';
		$facet_layout_skin_id = $this->get_skin_id( $facet );
		$facet_layout_skin_js = $this->get_skin_js( $facet );


		// Load the facet required skin files (css and js)
		$result .= $this->load_layout_skin( $facet_layout_skin_id, $facet_class_uuid );

		// Javascript custom options
		if ( ! empty( trim( $facet_layout_skin_js ) ) ) {

			$script = preg_replace(
				'/wpsolr_(.*)_options/',
				sprintf( 'var wpsolr_$1_options = wpsolr_$1_options || []; wpsolr_$1_options["%s"]', $facet_class_uuid ),
				$facet_layout_skin_js
			);

			if ( ! empty( trim( $script ) ) ) {
				$result .= "<script>{$script}</script>";
			}
		}

		return $result;
	}

	/**
	 * Enqueue a layout skin
	 *
	 * @param string $skin_id
	 * @param string $facet_class_uuid
	 *
	 * @return string
	 */
	public function load_layout_skin( $skin_id, $facet_class_uuid ) {

		$result = '';

		if ( defined( 'WPSOLR_PLUGIN_PRO_DIR' ) ) {

			$dir = WPSOLR_PLUGIN_PRO_DIR . '/wpsolr/pro/extensions/theme/xxx';

		} else {

			$dir = plugin_dir_path( __FILE__ );
		}

		$skins = static::get_skins();
		if ( ! empty( $skins ) &&
		     ! empty( $skins[ $skin_id ] ) &&
		     ! empty( $skins[ $skin_id ][ self::FIELD_SKIN_URL ] ) ) {

			$skin_url = $skins[ $skin_id ][ self::FIELD_SKIN_URL ];
			wp_enqueue_style( $skin_url, plugins_url( $skin_url, $dir ), [], WPSOLR_PLUGIN_VERSION );
		}

		$files = static::get_files();
		if ( ! empty( $files ) ) {

			if ( ! empty( $files[ self::FIELD_CSS_FILES ] ) ) {
				foreach ( $files[ self::FIELD_CSS_FILES ] as $css_file ) {
					wp_enqueue_style( $css_file, plugins_url( $css_file, $dir ), [], WPSOLR_PLUGIN_VERSION );
				}
			}

			if ( ! empty( $files[ self::FIELD_JS_FILES ] ) ) {
				foreach ( $files[ self::FIELD_JS_FILES ] as $js_file => $js_file_data ) {

					// Enqueue script
					wp_enqueue_script( $js_file, plugins_url( $js_file, $dir ), [], WPSOLR_PLUGIN_VERSION, false );


					if ( ! empty( $js_file_data ) && ! empty( $js_file_data[ self::FIELD_JS_LAYOUT_FILES ] ) ) {
						foreach ( $js_file_data[ self::FIELD_JS_LAYOUT_FILES ] as $file_name => $file ) {
							$js_file_data[ self::FIELD_JS_LAYOUT_FILES ][ $file_name ] = plugins_url( $file, $dir );
						}
					}

					// Dynamically localize script with parameters
					if ( ! empty( $js_file_data ) ) {
						$script = sprintf( 'var %s_%s = {data: %s};', self::JS_FILE_ENQUEUED_PARAMETERS, $facet_class_uuid, wp_json_encode( $js_file_data ) );
						$result .= sprintf( "<script>$script</script>" );
					}
				}
			}

		} else {

			//throw new WPSOLR_Exception_Security( "No skin '{$skin_id}' found for layout '{$this->get_layout_id()}'." );
		}

		return $result;
	}

	/**
	 * @param $facet_class_uuid
	 * @param $facet_template
	 * @param $facet_grid_class
	 * @param $html
	 * @param $facet
	 * @param $items
	 * @param int $level
	 *
	 * @internal param $facets_layouts
	 */
	public function displayFacetHierarchy( $facet_class_uuid, $facet_template, $facet_grid_class, &$html, $facet, $items, $level = 0 ) {

		if ( empty( $items ) ) {
			return;
		}

		$data_facet_type = ! empty( $facet['facet_type'] ) ? $facet['facet_type'] : WPSOLR_Option::OPTION_FACET_FACETS_TYPE_FIELD;

		$is_facet_selected = false;

		$facet_id = strtolower( str_replace( ' ', '_', $facet['id'] ) );

		$facet_layout_id         = ( ! empty( $facet['facet_layout_id'] ) ) ? $facet['facet_layout_id'] : WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID;
		$facet_layout_class      = ( ! empty( $facet['facet_layout_class'] ) ) ? $facet['facet_layout_class'] : '';
		$facet_layout_skin_class = ( ! empty( $facet['facet_layout_skin_class'] ) ) ? $facet['facet_layout_skin_class'] : '';

		$html .= sprintf( '<ul class="%s %s %s %s_%s %s" data-wpsolr-facet-type="%s">', $facet_class_uuid, $facet_layout_class, $facet_layout_skin_class, self::CLASS_PREFIX, $facet['id'], $facet_grid_class, $data_facet_type );

		$this->child_facet_header( $html, $items, $facet, $level );

		foreach ( $items as $item ) {

			$item_name           = htmlentities( $item['value'] ); // '&' is transformed in '&amp;' to match the values in the index
			$item_localized_name = ! empty( $item['value_localized'] ) ? $item['value_localized'] : $item['value'];
			$item_count          = $item['count'];
			$item_selected       = isset( $item['selected'] ) ? $item['selected'] : false;
			$item_permalink_href = ( ! empty( $item['permalink'] ) && ! empty( $item['permalink']['href'] ) ) ? $item['permalink']['href'] : '';

			// Check if one facet item is selected (once only).
			if ( $item_selected && ! $is_facet_selected ) {
				$is_facet_selected = true;
			}

			$item_facet_class = sprintf( '%s', ( $item_selected ? ' checked' : '' ) );

			// Current class level
			$facet_level = sprintf( '%s_l%s %s_%s', self::CLASS_PREFIX, $level, self::CLASS_PREFIX, empty( $item['items'] ) ? 'no_l' : 'l' );

			$facet_label = '';
			$facet_data  = [
				'id'           => $facet_id,
				'type'         => $data_facet_type,
				'is_permalink' => isset( $facet['is_permalink'] ) ? $facet['is_permalink'] : false,
				'level'        => $level,
			];
			if ( ! empty( $item_permalink_href ) ) {
				$facet_data ['permalink_href'] = $item_permalink_href;
			}

			switch ( $data_facet_type ) {
				case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_RANGE:
					$facet_data ['item_value']  = $item_name;
					$facet_data ['range_start'] = $item['range_start'];
					$facet_data ['range_end']   = $item['range_end'];

					$facet_label = $item_localized_name;
					$facet_label = str_replace( WPSOLR_Option::FACET_LABEL_TEMPLATE_VAR_START, $item['range_start'], $facet_label );
					$facet_label = str_replace( WPSOLR_Option::FACET_LABEL_TEMPLATE_VAR_END, $item['range_end'], $facet_label );
					$facet_label = str_replace( WPSOLR_Option::FACET_LABEL_TEMPLATE_VAR_COUNT, $item_count, $facet_label );
					break;

				case WPSOLR_Option::OPTION_FACET_FACETS_TYPE_MIN_MAX:
					$facet_data ['item_value'] = $item_name;
					$facet_data ['min']        = $item['min'];
					$facet_data ['max']        = $item['max'];
					$facet_data ['from']       = $item['from'];
					$facet_data ['to']         = $item['to'];
					break;

				default:
					$facet_data ['item_value'] = $item_name;
					$facet_label               = ( $item_count < 0 ) ? $item_localized_name : sprintf( $facet_template, $item_localized_name, $item_count );
					break;
			}

			// Encode facet data in json for javascript manipulation.
			$facet_data_json = wp_json_encode( $facet_data );

			$html       .= '<li>';
			$item_value = $facet_data ['item_value'];

			$item_value      = str_replace( "'", '&#39;', $item_value ); // important, for "'" contained in json
			$facet_data_json = str_replace( "'", '&#39;', $facet_data_json ); // important, for "'" contained in json

			$this->html_add_facet_item( $html, $level, $facet_layout_id, $item_localized_name, $item, $facet_label, $facet_data, $item_selected, $item_facet_class, $facet_level, $facet_id, $item_value, $facet_data_json );

			if ( ! empty( $item['items'] ) ) {

				$this->displayFacetHierarchy( '', $facet_template, $facet_grid_class, $html, $facet, $item['items'], $level + 1 );
			}

			$html .= '</li>';

		}

		$this->child_facet_footer( $html, $level );

		$html .= '</ul>';

	}


	/**
	 * Generate the html link rel attribute.
	 * e.g: rel="noindex, nofollow"
	 *
	 * @param $rel
	 *
	 * @return string
	 */
	protected function get_html_rel( $rel ) {

		return ! empty( $rel ) ? sprintf( self::TEMPLATE_LINK_REL_ATTRIBUTE, $rel ) : '';
	}

	/**
	 * Get the CSS class of a skin
	 *
	 * @param string $skin_id
	 */
	public function get_css_skin_class_name( $skin_id ) {

		$result = self::WPSOLR_FACET_SKIN_NONE_CLASS;

		$skins = static::get_skins();
		if ( ! empty( $skins[ $skin_id ] ) && ! empty( $skins[ $skin_id ][ self::FIELD_CSS_CLASS_NAME ] ) ) {
			$result = $skins[ $skin_id ][ self::FIELD_CSS_CLASS_NAME ];
		}

		return $result;
	}

	/**
	 * @param string $html
	 * @param array $facet
	 * @param array $items
	 * @param int $level
	 *
	 */
	protected function child_facet_header( &$html, &$items, $facet, $level ) {
		// Do in children
	}

	/**
	 * @param string $html
	 * @param int $level
	 */
	protected function child_facet_footer( &$html, $level ) {
		// Do in children
	}

	/**
	 * @param $html
	 * @param $level
	 * @param $facet_layout_id
	 * @param $item_localized_name
	 * @param $item
	 * @param $facet_label
	 * @param $facet_data
	 * @param $item_selected
	 * @param $item_facet_class
	 * @param $facet_level
	 * @param $facet_id
	 * @param $item_value
	 * @param $facet_data_json
	 */
	protected function html_add_facet_item( &$html, $level, $facet_layout_id, $item_localized_name, &$item, $facet_label, $facet_data, $item_selected, $item_facet_class, $facet_level, $facet_id, $item_value, $facet_data_json ) {

		// Create the item
		$this->child_prepare_facet_item( $level, $facet_layout_id, $item_localized_name, $item, $facet_label, $facet_data, $html_item );

		// Generate (eventually) a permalink from the item
		$this->generate_html_permalink( $item, $html_item );

		$this->child_html_add_facet_item( $html, $facet_layout_id, $item_selected, $item_facet_class, $facet_level, $facet_id, $item_value, $facet_data_json, $html_item );
	}

	/**
	 * @param $level
	 * @param $facet_layout_id
	 * @param $item_localized_name
	 * @param $item
	 * @param $facet_label
	 * @param $facet_data
	 * @param $html_item
	 */
	protected function child_prepare_facet_item( $level, $facet_layout_id, $item_localized_name, &$item, $facet_label, $facet_data, &$html_item ) {

		$html_item = ( empty( $item['items'] ) ? $facet_label : $item_localized_name ); // only show count on leaf items (else count is false);
	}

	/**
	 * @param $item
	 * @param $html_item
	 */
	protected function generate_html_permalink( $item, &$html_item ) {
		if ( isset( $item['permalink'] ) ) {
			$rel       = $this->get_html_rel( $item['permalink']['rel'] );
			$html_item = sprintf( self::PERMALINK_LINK_TEMPLATE, $item['permalink']['href'], $rel, $html_item, $html_item );
		}
	}

	/**
	 * @param $html
	 * @param $facet_layout_id
	 * @param $item_selected
	 * @param $item_facet_class
	 * @param $facet_level
	 * @param $facet_id
	 * @param $item_value
	 * @param $facet_data_json
	 * @param $html_item
	 */
	protected function child_html_add_facet_item( &$html, $facet_layout_id, $item_selected, $item_facet_class, $facet_level, $facet_id, $item_value, $facet_data_json, $html_item ) {
		$html .= "<div class='select_opt $item_facet_class $facet_level' id='$facet_id:$item_value' data-wpsolr-facet-data='$facet_data_json'>$html_item</div>";
	}

	/**
	 * Does the facet support selecting several items ?
	 *
	 * @param bool $is_multiple
	 *
	 * @return bool
	 */
	public function get_is_multi_filter( $is_multiple = false ) {
		return true;
	}

	/**
	 * Inner class for layouts with js libraries
	 *
	 * @return string
	 */
	protected function get_inner_class( $is_multiple = false ) {
		// Override in children
		return '';
	}


	/**
	 * Generate the css classes for a feature's layout_id
	 *
	 * @param string $feature
	 *
	 * @return string
	 */
	public static function get_css_class_feature_layouts( $feature ) {
		return implode( ' ', apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACET_FEATURE_LAYOUTS, [
			WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID,
			WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID
		], $feature ) );
	}

}