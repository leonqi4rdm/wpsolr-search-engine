<?php

namespace wpsolr\core\classes\models;

use wpsolr\core\classes\metabox\WPSOLR_Metabox;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\WPSOLR_Events;


/**
 * Class WPSOLR_Model_Post_Type
 * @package wpsolr\core\classes\models
 */
class WPSOLR_Model_Post_Type extends WPSOLR_Model {

	/**
	 * @inheritDoc
	 */
	public function __construct( $params ) {

		if ( ! isset( $params['post_type'] ) ) {
			throw new \Exception( 'WPSOLR: Missing post type parameter in model constructor.' );
		}

		$post_type     = $params['post_type'];
		$post_type_obj = get_post_type_object( $post_type );
		if ( is_null( $post_type_obj ) ) {
			throw new \Exception( "WPSOLR: Undefined post type '{$post_type}'." );
		}
		if ( ! isset( $post_type_obj->label ) ) {
			throw new \Exception( "WPSOLR: no label for post type '{$post_type}'." );
		}

		$this->set_label( $post_type_obj->label )
		     ->set_table_name( 'posts' )
		     ->set_column_id( 'ID' )
		     ->set_column_last_updated( 'post_modified' )
		     ->set_post_type( $post_type );
	}


	/**
	 * @param string[] $post_types
	 *
	 * @return WPSOLR_Model[]
	 */
	static public function create_models( $post_types ) {
		$models = [];
		foreach ( $post_types as $post_type ) {

			$models[] = new WPSOLR_Model_Post_Type( [ 'post_type' => $post_type ] );
		}

		return $models;
	}

	/**
	 * Post type is authorized to be indexed ?
	 *
	 * @param string $post_type
	 *
	 * @return bool
	 */
	static public function is_post_type_can_be_indexed( $post_type ) {

		$post_types = WPSOLR_Service_Container::getOption()->get_option_index_post_types();

		return in_array( $post_type, $post_types, true );
	}

	/**
	 * @inheritDoc
	 */
	public function get_indexing_sql( $debug_text, $batch_size = 100, $post = null, $is_debug_indexing = false, $is_only_exclude_ids = false ) {

		if ( ! empty( $this->indexing_sql ) ) {
			return $this->indexing_sql;
		}

		global $wpdb;

		$query_from       = $wpdb->prefix . $this->get_table_name() . ' AS ' . $this->get_table_name();
		$query_join_stmt  = '';
		$query_where_stmt = '';
		$post_type        = $this->get_post_type();

		// Build the WHERE clause

		if ( 'attachment' !== $post_type ) {
			// Where clause for post types

			$where_p = " post_type = '{$post_type}' ";

		} else {
			// Build the attachment types clause

			$attachment_types = str_replace( ',', "','", WPSOLR_Service_Container::getOption()->get_option_index_attachment_types_str() );
			if ( isset( $attachment_types ) && ( '' !== $attachment_types ) ) {
				$where_a = " ( post_status='publish' OR post_status='inherit' ) AND post_type='attachment' AND post_mime_type in ('$attachment_types') ";
			} else {
				$where_a = ' (1 = 2) '; // No attachment type selected: should return nothing.
			}
		}


		if ( isset( $where_p ) ) {

			$index_post_statuses = implode( ',', apply_filters( WPSOLR_Events::WPSOLR_FILTER_POST_STATUSES_TO_INDEX, [ 'publish' ] ) );
			$index_post_statuses = str_replace( ',', "','", $index_post_statuses );
			$query_where_stmt    = "post_status IN ('$index_post_statuses') AND ( $where_p )";
			if ( isset( $where_a ) ) {
				$query_where_stmt = "( $query_where_stmt ) OR ( $where_a )";
			}

		} elseif ( isset( $where_a ) ) {

			$query_where_stmt = $where_a;
		}

		if ( 0 === $batch_size ) {
			// count only
			$query_select_stmt = 'count(ID) as TOTAL';

		} else {

			$query_select_stmt = 'ID, post_modified, post_parent, post_type';
		}

		if ( isset( $post ) ) {
			// Add condition on the $post

			$query_where_stmt = " ID = %d AND ( $query_where_stmt ) ";

		} elseif ( $is_only_exclude_ids ) {
			// No condition on the date for $is_only_exclude_ids

			$query_where_stmt = " ( $query_where_stmt ) ";

		} else {
			// Condition on the date only for the batch, not for individual posts

			$query_where_stmt = ' ((post_modified = %s AND ID > %d) OR (post_modified > %s)) ' . " AND ( $query_where_stmt ) ";
		}

		// Excluded ids from SQL
		$blacklisted_ids  = $this->get_blacklisted_ids();
		$debug_info       = [
			'Posts excluded from the index' => implode( ',', $blacklisted_ids ),
		];
		$query_where_stmt .= $this->get_sql_statement_blacklisted_ids( $blacklisted_ids, $is_only_exclude_ids );


		$query_order_by_stmt = 'post_modified ASC, ID ASC';

		return [
			'debug_info' => $debug_info,
			'SELECT'     => $query_select_stmt,
			'FROM'       => $query_from,
			'JOIN'       => $query_join_stmt,
			'WHERE'      => $query_where_stmt,
			'ORDER'      => $query_order_by_stmt,
			'LIMIT'      => $batch_size,
		];
	}

	/**
	 * Get blacklisted post ids
	 * @return array
	 */
	public function get_blacklisted_ids() {

		$excluded_meta_ids = WPSOLR_Metabox::get_blacklisted_ids();
		$excluded_list_ids = WPSOLR_Service_Container::getOption()->get_option_index_post_excludes_ids();

		$all_excluded_ids = array_merge( $excluded_meta_ids, $excluded_list_ids );

		return $all_excluded_ids;
	}

	/**
	 * Generate a SQL restriction on all blacklisted post ids
	 *
	 * @param array $blacklisted_ids Array of post ids blaclisted
	 *
	 * @param bool $is_only_exclude_ids Do we find only excluded posts ?
	 *
	 * @return string
	 */
	private function get_sql_statement_blacklisted_ids( $blacklisted_ids, $is_only_exclude_ids = false ) {

		if ( empty( $blacklisted_ids ) ) {

			$result = $is_only_exclude_ids ? ' AND (1 = 2) ' : '';

		} else {

			$result = sprintf( $is_only_exclude_ids ? ' AND ID IN (%s) ' : ' AND ID NOT IN (%s) ', implode( ',', $blacklisted_ids ) );
		}

		return $result;
	}

}