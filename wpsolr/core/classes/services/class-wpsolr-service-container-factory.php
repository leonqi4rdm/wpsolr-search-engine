<?php

namespace wpsolr\core\classes\services;


/**
 * Custom service container factory included (as a trait) in all classes needing services.
 *
 * Class WPSOLR_Service_Container_Factory
 * @package wpsolr\core\classes\services
 */
trait WPSOLR_Service_Container_Factory {

	/**
	 * @return WPSOLR_Service_Container
	 */
	public function get_container() {
		global $wpsolr_container;

		// Singleton
		// Use the container set with set_container() (mostly for unit tests), else use global container.
		return isset( $this->wpsolr_container ) ? $this->wpsolr_container : ( isset( $wpsolr_container ) ? $wpsolr_container : ( $wpsolr_container = new WPSOLR_Service_Container() ) );
	}

	/**
	 * @param WPSOLR_Service_Container $container
	 */
	public function set_container( $container ) {

		// Do not set the global container here. Unit tests need local containers. Else it would require to reset the global container for each test.
		$this->wpsolr_container = $container;
	}

}