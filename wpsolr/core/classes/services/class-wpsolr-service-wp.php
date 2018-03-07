<?php

namespace wpsolr\core\classes\services;

use wpsolr\core\classes\WPSOLR_Events;

/**
 * Class WPSOLR_Service_WP
 * @package wpsolr\core\classes\services
 */
class WPSOLR_Service_WP {

	/**
	 * @return bool
	 */
	public function is_admin() {
		return is_admin();
	}

	/**
	 * @return bool
	 */
	public function is_main_query() {
		return is_main_query();
	}

	/**
	 * Adds a rewrite rule that transforms a URL structure to a set of query vars.
	 *
	 * @param string $regex Regular expression to match request against.
	 * @param string|array $query The corresponding query vars for this rewrite rule.
	 * @param string $after Optional. Priority of the new rule. Accepts 'top'
	 *                            or 'bottom'. Default 'bottom'.
	 */
	public function add_rewrite_rule( $regex, $query, $after = 'bottom' ) {
		add_rewrite_rule( $regex, $query, $after );
	}

	/**
	 * Redirects to another page.
	 *
	 * @param string $location The path to redirect to.
	 * @param int $status Status code to use.
	 *
	 * @return bool False if $location is not provided, true otherwise.
	 */
	function wp_redirect( $location, $status = 302 ) {
		wp_redirect( $location, $status );
		exit();
	}

	/**
	 * Perform a MySQL database query, using current database connection.
	 *
	 * @param string $query Database query
	 *
	 * @return int|false Number of rows affected/selected or false on error
	 */
	function query( $sql_statement ) {
		global $wpdb;

		$wpdb->show_errors( false );

		return $wpdb->query( $sql_statement );
	}

	/**
	 * @return \QM_DB|\wpdb
	 */
	function get_wpdb() {
		global $wpdb;

		return $wpdb;
	}

	/**
	 * Prepares a SQL query for safe execution. Uses sprintf()-like syntax.
	 *
	 * @param string $query Query statement with sprintf()-like placeholders
	 * @param array|mixed $args The array of variables to substitute into the query's placeholders if being called like
	 *                              {@link https://secure.php.net/vsprintf vsprintf()}, or the first variable to substitute into the query's placeholders if
	 *                              being called like {@link https://secure.php.net/sprintf sprintf()}.
	 * @param mixed $args,... further variables to substitute into the query's placeholders if being called like
	 *                              {@link https://secure.php.net/sprintf sprintf()}.
	 *
	 * @return string Sanitized query string, if there is a query to prepare.
	 */
	public function prepare( $query, $args ) {
		global $wpdb;

		return $wpdb->prepare( $query, $args );
	}

	/**
	 * Retrieve one column from the database.
	 *
	 * @param string|null $query Optional. SQL query. Defaults to previous query.
	 * @param int $x Optional. Column to return. Indexed from 0.
	 *
	 * @return array Database query result. Array indexed from 0 by SQL result row number.
	 */
	public function get_col( $query = null, $x = 0 ) {
		global $wpdb;

		return $wpdb->get_col( $query );
	}

	/**
	 * Get all Term data from database by Term field and data.
	 *
	 * @param string $field Either 'slug', 'name', 'id' (term_id), or 'term_taxonomy_id'
	 * @param string|int $value Search for this term value
	 * @param string $taxonomy Taxonomy name. Optional, if `$field` is 'term_taxonomy_id'.
	 * @param string $output Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N, which correspond to
	 *                             a WP_Term object, an associative array, or a numeric array, respectively. Default OBJECT.
	 * @param string $filter Optional, default is raw or no WordPress defined filter will applied.
	 *
	 * @return WP_Term|array|false WP_Term instance (or array) on success. Will return false if `$taxonomy` does not exist
	 *                             or `$term` was not found.
	 */
	function get_term_by( $field, $value, $taxonomy = '', $output = OBJECT, $filter = 'raw' ) {
		return get_term_by( $field, $value, $taxonomy, $output, $filter );
	}

	/**
	 * Get an array of ancestor IDs for a given object.
	 *
	 * @param int $object_id Optional. The ID of the object. Default 0.
	 * @param string $object_type Optional. The type of object for which we'll be retrieving
	 *                              ancestors. Accepts a post type or a taxonomy name. Default empty.
	 * @param string $resource_type Optional. Type of resource $object_type is. Accepts 'post_type'
	 *                              or 'taxonomy'. Default empty.
	 *
	 * @return array An array of ancestors from lowest to highest in the hierarchy.
	 */
	function get_ancestors( $object_id = 0, $object_type = '', $resource_type = '' ) {
		return get_ancestors( $object_id, $object_type, $resource_type );
	}

	/**
	 * Retrieve the terms in a given taxonomy or list of taxonomies.
	 *
	 * @param array|string $args
	 * @param array $deprecated Argument array, when using the legacy function parameter format. If present, this
	 *                          parameter will be interpreted as `$args`, and the first function parameter will
	 *                          be parsed as a taxonomy or array of taxonomies.
	 *
	 * @return array|int|\WP_Error List of WP_Term instances and their children. Will return WP_Error, if any of $taxonomies
	 *                            do not exist.
	 */
	public function get_terms( $args = [], $deprecated = '' ) {
		return get_terms( $args, $deprecated );
	}

	/**
	 * Call the functions added to a filter hook.
	 *
	 * @param string $tag The name of the filter hook.
	 * @param mixed $value The value on which the filters hooked to `$tag` are applied on.
	 * @param mixed $var,... Additional variables passed to the functions hooked to `$tag`.
	 *
	 * @return mixed The filtered value after all hooked functions are applied to it.
	 */
	function apply_filters( $tag, $value ) {
		$args = func_get_args();

		array_shift( $args );
		array_shift( $args );

		return apply_filters( $tag, $value, $args );
	}

	/**
	 * @param $default_facet_type
	 * @param $facet_name
	 *
	 * @return string
	 */
	public function apply_filters__wpsolr_filter_facet_type( $default_facet_type, $facet_name ) {
		return apply_filters( WPSOLR_Events::WPSOLR_FILTER_FACET_TYPE, $default_facet_type, $facet_name );
	}

}