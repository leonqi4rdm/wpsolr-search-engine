<?php

namespace wpsolr\core\classes\services;


use wpsolr\core\classes\ui\WPSOLR_Query_Parameters;

/**
 * Proxy to WPSOLR static methods.
 *
 * Class WPSOLR_Service_WPSOLR
 * @package wpsolr\core\classes\services
 */
class WPSOLR_Service_WPSOLR {

	/**
	 * @return bool
	 */
	public function is_wp_search() {
		return WPSOLR_Query_Parameters::is_wp_search();
	}

	/**
	 * Redirect 404
	 */
	function wp_redirect_404() {
		global $wp_query;
		$wp_query->set_404();
	}

	/**
	 * @return bool
	 */
	public function starts_with( $text, $starts_with ) {

		if ( '' === $starts_with ) {
			return false;
		}

		return ( 0 === strpos( $text, $starts_with ) );
	}

	/**
	 * $url is the folder, or contains the folder.
	 *
	 * @return bool
	 */
	public function starts_with_folder( $url, $folder ) {

		$url    = trim( $url, ' /' );
		$url    = current( explode( '?', $url ) ); // remove ?= parameters
		$folder = trim( $folder, ' /' );

		if ( ( '' === $url ) || ( '' === $folder ) ) {
			return false;
		}

		if ( $url === $folder ) {
			return true;
		}

		return $this->starts_with( $url, $folder . '/' );
	}

}