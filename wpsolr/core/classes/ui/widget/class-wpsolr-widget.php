<?php

namespace wpsolr\core\classes\ui\widget;

use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\ui\WPSOLR_Query_Parameters;
use wpsolr\core\classes\WPSOLR_Events;

/**
 * Top level widget class from which all WPSOLR widgets inherit.
 * Class WPSOLR_Widget
 * @package wpsolr\core\classes\ui\widget
 */
class WPSOLR_Widget extends \WP_Widget {

	/**
	 * Load all widget classes in this very directory.
	 */
	public static function Autoload() {

		add_action( 'widgets_init', function () {

			// Loop on all widgets
			foreach ( [ WPSOLR_Widget_Facet::class, WPSOLR_Widget_Sort::class ] as $widget_class_name ) {

				// Register widget
				register_widget( $widget_class_name );
			}

		} );
	}

	/**
	 * Show ?
	 *
	 * @return bool
	 */
	public function get_is_show() {

		$is_replace_by_wpsolr_query = WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_search()
		                              && WPSOLR_Service_Container::getOption()->get_search_is_use_current_theme_search_template()
		                              && WPSOLR_Query_Parameters::is_wp_search();

		return apply_filters( WPSOLR_Events::WPSOLR_FILTER_IS_REPLACE_BY_WPSOLR_QUERY, $is_replace_by_wpsolr_query );
	}

}