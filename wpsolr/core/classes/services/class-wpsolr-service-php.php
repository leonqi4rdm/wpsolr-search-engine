<?php

namespace wpsolr\core\classes\services;


/**
 * Class WPSOLR_Service_PHP
 * @package wpsolr\core\classes\services
 */
class WPSOLR_Service_PHP {

	/**
	 */
	public function do_exit() {
		exit();
	}

	/**
	 */
	public function do_die() {
		die();
	}

	/**
	 * @return string
	 */
	public function get_server_request_uri() {
		return $_SERVER['REQUEST_URI'];
	}

	/**
	 * @return string
	 */
	public function get_server_query_string() {
		return $_SERVER['QUERY_STRING'];
	}

	/**
	 * @return array
	 */
	public function get_request() {
		return $_REQUEST;
	}
}