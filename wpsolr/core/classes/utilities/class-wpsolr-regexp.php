<?php

namespace wpsolr\core\classes\utilities;

/**
 * Common Regexp expressions used in WPSOLR.
 *
 * Class WPSOLR_Regexp
 * @package wpsolr\core\classes\utilities
 */
class WPSOLR_Regexp {

	/**
	 * Extract values from a range query parameter
	 * '[5 TO 30]' => ['5', '30']
	 *
	 * @param $text
	 *
	 * @return array
	 */
	static function extract_filter_range_values( $text ) {

		// Replace separator literals by a single special character. Much easier, because negate a literal is difficult with regexp.
		$text = str_replace( [ ' TO ', '[', ']' ], ' | ', $text );

		// Negate all special caracters to get the 'field:value' array
		preg_match_all( '/[^|\s]+/', $text, $matches );

		// Trim results
		$results_with_some_empty_key = ! empty( $matches[0] ) ? array_map( 'trim', $matches[0] ) : [];

		// Remove empty array rows (it happens), prevent duplicates.
		$results = [];
		foreach ( $results_with_some_empty_key as $result ) {
			if ( ! empty( $result ) ) {
				array_push( $results, $result );
			}
		}

		return $results;
	}

	/**
	 * Extract last occurence of a separator
	 * 'field1' => ''
	 * 'field1_asc' => 'asc'
	 * 'field1_notme_asc' => 'asc'
	 *
	 * @param $text
	 * @param $text_to_find
	 *
	 * @return string
	 */
	static function extract_last_separator( $text, $separator ) {

		$separator_escaped = preg_quote( $separator, '/' );
		preg_match( sprintf( '/[%s]+[^%s]*$/', $separator_escaped, $separator_escaped ), $text, $matches );

		return ! empty( $matches ) ? substr( $matches[0], strlen( $separator ) ) : $text;
	}

	/**
	 * Extract first occurence of a separator
	 * 'field1' => 'field1'
	 * 'field1_asc' => 'field1'
	 * 'field1_notme_asc' => 'field1'
	 *
	 * @param $text
	 * @param $text_to_find
	 *
	 * @return string
	 */
	static function extract_first_separator( $text, $separator ) {

		if ( empty( $text ) || empty( $separator ) ) {
			return '';
		}

		$separator_escaped = preg_quote( $separator, '/' );
		preg_match( sprintf( '/^[^%s]+/', $separator_escaped ), $text, $matches );

		return ! empty( $matches ) ? $matches[0] : '';
	}

	/**
	 * Remove $text_to_remove at the end of $text
	 *
	 * @param $text
	 * @param $text_to_remove
	 *
	 * @return string
	 */
	static function remove_string_at_the_end( $text, $text_to_remove ) {

		if ( '' === $text ) {
			return '';
		}

		if ( '' === $text_to_remove ) {
			return $text;
		}

		return preg_replace( sprintf( '/%s$/', preg_quote( $text_to_remove, '/' ) ), '', $text );
	}

	/**
	 * Remove $text_to_remove at the beginning of $text
	 *
	 * @param $text
	 * @param $text_to_remove
	 *
	 * @return string
	 */
	static function remove_string_at_the_begining( $text, $text_to_remove ) {

		if ( '' === $text ) {
			return '';
		}

		if ( '' === $text_to_remove ) {
			return $text;
		}

		return preg_replace( sprintf( '/^%s/', preg_quote( $text_to_remove, '/' ) ), '', $text );
	}

	/**
	 * @param string $from
	 * @param string $to
	 * @param string $subject
	 *
	 * @return string
	 */
	public static function str_replace_first( $from, $to, $subject ) {
		$from = '/' . preg_quote( $from, '/' ) . '/';

		return preg_replace( $from, $to, $subject, 1 );
	}


	/**
	 * Escape control characters (Solr error)
	 *
	 * @param mixed $value_to_strip
	 *
	 * @return void
	 */
	public static function replace_recursive( &$value_to_strip, $pattern, $replacement ) {

		if ( empty( $value_to_strip ) || is_null( $value_to_strip ) ) {
			return;
		}

		if ( is_array( $value_to_strip ) ) {
			// recursive
			foreach ( $value_to_strip as $field_name => &$field_value ) {

				self::replace_recursive( $field_value, $pattern, $replacement );
			}

		} else {
			$value_to_strip = preg_replace( $pattern, $replacement, $value_to_strip );
		}

	}

}