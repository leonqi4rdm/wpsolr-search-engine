<?php

namespace wpsolr\core\classes\ui;

/**
 * Display sort list
 *
 * Class WPSOLR_UI_Sort
 * @package wpsolr\core\classes\ui
 */
class WPSOLR_UI_Sort {

	/**
	 * Build sort list UI
	 *
	 * @param $sorts
	 *
	 * @return string
	 */
	public
	static function build(
		$sorts
	) {

		$html = '';

		// Build the sort list
		if ( is_array( $sorts ) && ! empty( $sorts ) && ! empty( $sorts['items'] ) ) {

			$html .= sprintf( '<label class=\'wdm_label\'>%s</label><select class=\'select_field\'>', $sorts['header'] );

			foreach ( $sorts['items'] as $sort ) {

				$html .= sprintf( '<option value=\'%s\' %s>%s</option>', $sort['id'], $sort['selected'] ? 'selected' : '', $sort['name'] );
			}

			$html .= '</select>';

			$html = '<div>' . $html . '</div>';

		}

		return $html;
	}
}
