<?php

namespace wpsolr\core\classes\ui\layout\checkboxes;

use wpsolr\core\classes\ui\layout\WPSOLR_UI_Layout_Abstract;
use wpsolr\core\classes\utilities\WPSOLR_Option;

/**
 * Class WPSOLR_UI_Layout_Check_Box
 * @package wpsolr\core\classes\ui\layout\checkboxes
 */
class WPSOLR_UI_Layout_Check_Box extends WPSOLR_UI_Layout_Abstract {

	const CHILD_LAYOUT_ID = 'id_checkboxes';

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
	public static function get_is_enabled() {
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public static function get_label() {
		return 'Check boxes';
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
	static function get_types() {
		return []; // All field types ok,
	}

	/**
	 * @inheritdoc
	 */
	function get_css_class_name() {
		return 'wpsolr_facet_checkbox';
	}

}