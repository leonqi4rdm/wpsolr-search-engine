<?php

namespace wpsolr\core\classes;

/**
 * When we need to hack some $wpdb methods (like direct query, without using $wp_query).
 *
 * Class WPSOLR_Db
 * @package wpsolr\core\classes
 */
abstract class WPSOLR_Db extends \wpdb {

	/** @var  \wpdb $old_wpdb */
	protected static $old_wpdb;

	/** @var  bool $wpsolr_is_custom */
	protected $wpsolr_is_custom = false;

	/**
	 * @param string $query Query
	 * @param array|mixed $args
	 * @param mixed $args,...
	 *
	 * @return string|void
	 */
	abstract protected function wpsolr_custom_prepare( $query, $args );

	/**
	 * @param string $query
	 * @param string $output
	 *
	 * @return array|object|null
	 */
	abstract protected function wpsolr_custom_get_results( $query = null, $output = OBJECT );

	/**
	 * @param object $object_initiating
	 */
	static public function wpsolr_replace_wpdb( $object_initiating ) {
		global $wpdb;

		$replacing_class_name = static::class;
		/** @var static $replacing_object */
		$replacing_object = new $replacing_class_name(
			$wpdb->dbuser,
			$wpdb->dbpassword,
			$wpdb->dbname,
			$wpdb->dbhost
		);

		$replacing_object->wpsolr_set_is_custom( true );

		// Replace now, and init
		$wpdb = $replacing_object;
		wp_set_wpdb_vars();
		wp_start_object_cache();

		return $replacing_object;
	}

	/**
	 * @inheritDoc
	 */
	public function prepare( $query, $args ) {

		$args = func_get_args();
		array_shift( $args );
		// If args were passed as an array (as in vsprintf), move them up
		if ( isset( $args[0] ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}

		if ( $this->wpsolr_get_is_custom() ) {

			return $this->wpsolr_custom_prepare( $query, $args );

		} else {

			return parent::prepare( $query, $args );
		}

	}


	/**
	 * @inheritDoc
	 */
	public function get_results( $query = null, $output = OBJECT ) {

		if ( $this->wpsolr_get_is_custom() ) {

			// Done.
			$this->wpsolr_set_is_custom( false );

			return $this->wpsolr_custom_get_results( $query, $output );

		} else {

			return parent::get_results( $query, $output );
		}

	}

	/**
	 * @return bool
	 */
	public function wpsolr_get_is_custom() {
		return $this->wpsolr_is_custom;
	}

	/**
	 * @param bool $wpsolr_is_custom
	 */
	public function wpsolr_set_is_custom( $wpsolr_is_custom ) {
		$this->wpsolr_is_custom = $wpsolr_is_custom;
	}


}