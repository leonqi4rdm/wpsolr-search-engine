<?php

namespace wpsolr\core\classes\models;


/**
 * Class WPSOLR_Model_BP_Profile_Data
 * @package wpsolr\core\classes\models
 */
class WPSOLR_Model_BP_Profile_Data extends WPSOLR_Model {
	/**
	 * @inheritDoc
	 */
	public function __construct() {

		$this->set_label( 'BuddyPress profile data' )
		     ->set_table_name( 'bp_xprofile_data' )
		     ->set_column_id( 'id' )
		     ->set_column_last_updated( 'last_updated' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_indexing_sql( $debug_text, $batch_size = 100, $post = null, $is_debug_indexing = false, $is_only_exclude_ids = false ) {

		if ( ! empty( $this->indexing_sql ) ) {
			return $this->indexing_sql;
		}

		global $wpdb;

		$column_last_updated = $this->get_column_last_updated();

		$query_from       = $wpdb->prefix . $this->get_table_name() . ' AS ' . $this->get_table_name();
		$query_join_stmt  = '';
		$query_where_stmt = '';

		if ( 0 === $batch_size ) {
			// count only
			$query_select_stmt = 'count(ID) as TOTAL';
		} else {
			$query_select_stmt = sprintf( 'ID, %s', $column_last_updated );
		}

		if ( isset( $post ) ) {
			// Add condition on the $post

			$query_where_stmt = " ID = %d AND ( $query_where_stmt ) ";

		} else {
			// Condition on the date only for the batch, not for individual posts

			if ( $is_only_exclude_ids ) {

				$query_where_stmt = '(1 = 2)';

			} else {
				$query_where_stmt = sprintf( ' ((%s = %%s AND ID > %%d) OR (%s > %%s)) ', $column_last_updated, $column_last_updated );
			}
		}

		$query_order_by_stmt = sprintf( '%s ASC, ID ASC', $column_last_updated );

		return [
			'debug_info' => '',
			'SELECT'     => $query_select_stmt,
			'FROM'       => $query_from,
			'JOIN'       => $query_join_stmt,
			'WHERE'      => $query_where_stmt,
			'ORDER'      => $query_order_by_stmt,
			'LIMIT'      => $batch_size,
		];
	}

}