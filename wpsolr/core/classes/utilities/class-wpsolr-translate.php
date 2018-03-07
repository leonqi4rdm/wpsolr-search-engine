<?php

namespace wpsolr\core\classes\utilities;

use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Translations utilities
 *
 * Class WPSOLR_Translate
 * @package wpsolr\core\classes\utilities
 */
class WPSOLR_Translate {


	/**
	 * Translate a field
	 *
	 * @param $translation_domain
	 * @param $field_name
	 * @param $field_value
	 *
	 * @return string
	 */
	static public function translate_field( $translation_domain, $field_name, $field_value ) {

		// Give plugins a chance to change the field name.
		$result = apply_filters( WPSOLR_Events::WPSOLR_FILTER_SEARCH_PAGE_FACET_NAME, $field_name );

		if ( $result === $field_name ) {
			// Field name not changed by filter

			if ( ! empty( $field_value ) ) {

				// Give plugins a chance to change the field value (WPML, POLYLANG).
				$result = apply_filters( WPSOLR_Events::WPSOLR_FILTER_TRANSLATION_STRING, $field_value,
					[
						'domain' => $translation_domain,
						'name'   => $field_name,
						'text'   => $field_value,
					]
				);

			}
		}

		return $result;
	}

	/**
	 * Translate a custom field
	 *
	 * @param $field_name
	 * @param $field_value
	 *
	 * @return string
	 */
	static public function translate_field_custom_field( $translation_domain, $field_name, $field_value ) {

		// Remove the ending WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING
		$field_name_without_str = WPSOLR_Regexp::remove_string_at_the_end( $field_name, WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING );

		// Give plugins a chance to change the facet name (ACF).
		$result = apply_filters( WPSOLR_Events::WPSOLR_FILTER_SEARCH_PAGE_FACET_NAME, $field_name_without_str );

		if ( $result === $field_name_without_str ) {
			// Facet label not changed by filter

			if ( ! empty( $field_value ) ) {
				// Facet label is defined in options
				$result = $field_value;

				// Give plugins a chance to change the facet name (WPML, POLYLANG).
				$result = apply_filters( WPSOLR_Events::WPSOLR_FILTER_TRANSLATION_STRING, $result,
					[
						'domain' => $translation_domain,
						'name'   => $field_name,
						'text'   => $result,
					]
				);

			} else {
				// Try to make a decent label from the facet raw id
				$result = str_replace( '_', ' ', $result );
				$result = ucfirst( $result );
			}
		}

		return $result;
	}
}
