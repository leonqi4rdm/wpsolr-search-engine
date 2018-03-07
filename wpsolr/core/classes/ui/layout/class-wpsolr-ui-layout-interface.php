<?php

namespace wpsolr\core\classes\ui\layout;


/**
 * Base interface for all facet layouts
 *
 */
interface WPSOLR_UI_Layout_Interface {


	/**
	 * Get the CSS class
	 *
	 * @return string
	 */
	function get_css_class_name();

	/**
	 * Get layout facet type
	 *
	 * @return string
	 */
	static function get_facet_type();

	/**
	 * Get layout files definitions
	 *
	 * @return array
	 */
	static function get_files();

	/**
	 * Is the layout enabled ?
	 *
	 * @return bool
	 */
	public static function get_is_enabled();

	/**
	 * Get the layout label ?
	 *
	 * @return string
	 */
	public static function get_label();

	/**
	 * Get layout skin definitions
	 *
	 * @return array
	 */
	static function get_skins();


	/**
	 * Get the layout types
	 *
	 * @return string[]
	 */
	static function get_types();

}