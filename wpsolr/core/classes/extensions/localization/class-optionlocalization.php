<?php

namespace wpsolr\core\classes\extensions\localization;

use wpsolr\core\classes\engines\solarium\WPSOLR_SearchSolariumClient;
use wpsolr\core\classes\extensions\WpSolrExtensions;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\WPSOLR_Events;

/**
 * Class OptionLocalization
 *
 * Manage localization options
 */
class OptionLocalization extends WpSolrExtensions {


	/*
	 * Section code constants. Do not change.
	 */
	const TERMS = 'terms';
	const SECTION_CODE_SEARCH_FORM = 'section_code_search_form';
	const SECTION_CODE_SORT = 'section_code_sort';
	const SECTION_CODE_FACETS = 'section_code_facets';

	/*
	 * Array key constants. Do not change.
	 */
	const KEY_SECTION_NAME = 'section_name';
	const KEY_SECTION_TERMS = 'section_terms';


	/**
	 * Get the whole array of default options
	 *
	 * @return array Array of default options
	 */
	static function get_default_options() {

		return [
			/* Choice of localization method */
			'localization_method' => 'localization_by_admin_options',
			/* Localization terms */
			self::TERMS           => [
				/* Search Form */
				'search_form_button_label'                                     => self::_x( 'search_form_button_label', 'Search', 'Search form button label', 'wpsolr' ),
				'search_form_edit_placeholder'                                 => self::_x( 'search_form_edit_placeholder', 'Search ....', 'Search edit placeholder', 'wpsolr' ),
				'sort_header'                                                  => self::_x( 'sort_header', 'Sort by', 'Sort list header', 'wpsolr' ),
				/* Sort */
				WPSOLR_SearchSolariumClient::SORT_CODE_BY_RELEVANCY_DESC       => self::_x( WPSOLR_SearchSolariumClient::SORT_CODE_BY_RELEVANCY_DESC, 'More relevant', 'Sort list element', 'wpsolr' ),
				WPSOLR_SearchSolariumClient::SORT_CODE_BY_DATE_ASC             => self::_x( WPSOLR_SearchSolariumClient::SORT_CODE_BY_DATE_ASC, 'Oldest', 'Sort list element', 'wpsolr' ),
				WPSOLR_SearchSolariumClient::SORT_CODE_BY_DATE_DESC            => self::_x( WPSOLR_SearchSolariumClient::SORT_CODE_BY_DATE_DESC, 'Newest', 'Sort list element', 'wpsolr' ),
				WPSOLR_SearchSolariumClient::SORT_CODE_BY_NUMBER_COMMENTS_ASC  => self::_x( WPSOLR_SearchSolariumClient::SORT_CODE_BY_NUMBER_COMMENTS_ASC, 'The more commented', 'Sort list element', 'wpsolr' ),
				WPSOLR_SearchSolariumClient::SORT_CODE_BY_NUMBER_COMMENTS_DESC => self::_x( WPSOLR_SearchSolariumClient::SORT_CODE_BY_NUMBER_COMMENTS_DESC, 'The least commented', 'Sort list element', 'wpsolr' ),
				'facets_header'                                                => self::_x( 'facets_header', 'Filters', 'Facets list header', 'wpsolr' ),
				/* Facets */
				'facets_title'                                                 => self::_x( 'facets_title', 'By %s', 'Facets list title', 'wpsolr' ),
				'facets_element_all_results'                                   => self::_x( 'facets_element_all_results', 'All results', 'Facets list element all results', 'wpsolr' ),
				'facets_element'                                               => self::_x( 'facets_element', '%s (%d)', 'Facets list element name with #results', 'wpsolr' ),
				/* Results header */
				'results_header_did_you_mean'                                  => self::_x( 'results_header_did_you_mean', 'Did you mean: %s', 'Results header: did you mean ?', 'wpsolr' ),
				'results_header_pagination_numbers'                            => self::_x( 'results_header_pagination_numbers', 'Showing %d to %d results out of %d', 'Results header: pagination numbers', 'wpsolr' ),
				'infinitescroll_results_header_pagination_numbers'             => self::_x( 'infinitescroll_results_header_pagination_numbers', 'Showing %d results', 'Results header: infinitescroll pagination numbers', 'wpsolr' ),
				'results_header_no_results_found'                              => self::_x( 'results_header_no_results_found', 'No results found for %s', 'Results header: no results found', 'wpsolr' ),
				'results_row_by_author'                                        => self::_x( 'results_row_by_author', 'By %s', 'Result row information box: by author', 'wpsolr' ),
				'results_row_in_category'                                      => self::_x( 'results_row_in_category', ', in %s', 'Result row information box: in category', 'wpsolr' ),
				'results_row_on_date'                                          => self::_x( 'results_row_on_date', ', on %s', 'Result row information box: on date', 'wpsolr' ),
				'results_row_number_comments'                                  => self::_x( 'results_row_number_comments', ', %d comments', 'Result row information box: number of comments', 'wpsolr' ),
				'results_row_comment_link_title'                               => self::_x( 'results_row_comment_link_title', '-Comment match', 'Result row comment box: comment link title', 'wpsolr' ),
				'infinitescroll_loading'                                       => self::_x( 'infinitescroll_loading', 'Loading ...', 'Text displayed while infinite scroll is loading next page of results', 'wpsolr' ),
				'geolocation_ask_user'                                         => self::_x( 'geolocation_ask_user', 'Use my current location', 'Geolocation, ask user', 'wpsolr' ),
			]
		];
	}

	/**
	 * @param string $name
	 * @param string $text Text to translate.
	 * @param string $context Context information for the translators.
	 * @param string $domain Optional. Text domain. Unique identifier for retrieving translated strings.
	 *                        Default 'default'.
	 *
	 * @return string Translated context string without pipe.
	 */
	static function _x( $name, $text, $context, $domain ) {

		if ( WPSOLR_Service_Container::getOption()->get_localization_is_internal() ) {

			// No multi-language plugin
			$translated = _x( $text, $context, $domain );

		} else {

			// Creates/uses string
			$translated = apply_filters( WPSOLR_Events::WPSOLR_FILTER_TRANSLATION_LOCALIZATION_STRING, $text,
				[
					'context' => $context,
					'domain'  => $domain,
					'name'    => $name,
					'text'    => $text,
				] );

		}

		return $translated;
	}

	/**
	 * Get the presentation array
	 *
	 * @return array Array presentation options
	 */
	static function get_presentation_options() {

		return array(
			'Search Form box'            =>
				array(
					self::KEY_SECTION_TERMS => array(
						'search_form_button_label'     => array( 'Search form button label' ),
						'search_form_edit_placeholder' => array( 'Search edit placeholder' ),
					),
				),
			'Sort list box'              =>
				array(
					self::KEY_SECTION_TERMS => array(
						'sort_header'                                                  => array( 'Sort list header' ),
						WPSOLR_SearchSolariumClient::SORT_CODE_BY_RELEVANCY_DESC       => array( 'Sort list element' ),
						WPSOLR_SearchSolariumClient::SORT_CODE_BY_DATE_ASC             => array( 'Sort list element' ),
						WPSOLR_SearchSolariumClient::SORT_CODE_BY_DATE_DESC            => array( 'Sort list element' ),
						WPSOLR_SearchSolariumClient::SORT_CODE_BY_NUMBER_COMMENTS_ASC  => array( 'Sort list element' ),
						WPSOLR_SearchSolariumClient::SORT_CODE_BY_NUMBER_COMMENTS_DESC => array( 'Sort list element' ),
					),
				),
			'Facets box'                 =>
				array(
					self::KEY_SECTION_TERMS => array(
						'facets_header'              => array( 'Facets list header' ),
						'facets_title'               => array( 'Facets list title' ),
						'facets_element_all_results' => array( 'Facets list element all results' ),
						'facets_element'             => array( 'Facets list element name with #results' ),
					),
				),
			'Results Header box'         =>
				array(
					self::KEY_SECTION_TERMS => array(
						'results_header_did_you_mean'       => array( 'Did you mean (automatic keyword spell correction)' ),
						'results_header_pagination_numbers' => array( 'Pagination header on top of results' ),
						'results_header_no_results_found'   => array( 'Message no results found' ),
					),
				),
			'Result Row information box' =>
				array(
					self::KEY_SECTION_TERMS => array(
						'results_row_by_author'          => array( 'Author of the result row' ),
						'results_row_in_category'        => array( 'Category of the result row' ),
						'results_row_on_date'            => array( 'Date of the result row' ),
						'results_row_number_comments'    => array( 'Number of comments of the result row' ),
						'results_row_comment_link_title' => array( 'Comment link title' ),
					),
				),
			'Infinite Scroll'            =>
				array(
					self::KEY_SECTION_TERMS => array(
						'infinitescroll_loading'                           => array( 'Text displayed while Infinite Scroll is loading the next page' ),
						'infinitescroll_results_header_pagination_numbers' => array( 'Pagination header on top of results' ),
					),
				),
			'Geolocation'                =>
				array(
					self::KEY_SECTION_TERMS => array(
						'geolocation_ask_user' => array( 'Text accompanying the checkbox asking user agreement to use his location' ),
					),
				),
		);
	}

	/**
	 * Get the whole array of options.
	 * Merge between default options and customized options.
	 *
	 * @param $is_internal_localized boolean Force internal options
	 *
	 * @return array Array of options
	 */
	static function get_options( $is_internal_localized = null ) {

		$default_options = self::get_default_options();

		$database_options = WPSOLR_Service_Container::getOption()->get_option_localization();
		if ( $database_options !== null
		     && isset( $default_options[ self::TERMS ] )
		     && isset( $default_options[ self::TERMS ]['search_form_button_label'] )
		     && ( $default_options[ self::TERMS ]['search_form_button_label'] === 'Search' ) // Override only default language 'en', not translations
		) {
			// Replace default values with by database (customized) values with same key.
			// Why do that ? Because we can have added new terms in the default terms,
			// and they must be used even not customized by the user.

			return array_replace_recursive( $default_options, $database_options );
		}

		// Return default options not customized
		return $default_options;
	}


	/**
	 * Get the whole array of localized terms.
	 *
	 * @param array $options Array of options
	 *
	 * @return array Array of localized terms
	 */
	static function get_terms( $options ) {

		return ( isset( $options ) && isset( $options[ self::TERMS ] ) )
			? $options[ self::TERMS ]
			: [];
	}


	/**
	 * Get terms of a presentation section
	 *
	 * @param array $section Section
	 *
	 * @return array Terms of the section
	 */
	static function get_section_terms( $section ) {

		return
			( ! empty( $section ) )
				? $section[ self::KEY_SECTION_TERMS ]
				: [];
	}

	/**
	 * Get a localized term.
	 * If it does not exist, send the term code instead.
	 *
	 * @param array $option
	 * @param string $term_code A term code
	 *
	 * @return string Term
	 */
	static function get_term( $option, $term_code ) {

		$value = ( isset( $option[ self::TERMS ][ $term_code ] ) ) ? $option[ self::TERMS ][ $term_code ] : $term_code;

		return $value;
	}

}
