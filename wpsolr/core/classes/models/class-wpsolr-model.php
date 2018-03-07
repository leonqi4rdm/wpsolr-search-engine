<?php

namespace wpsolr\core\classes\models;


/**
 * Class WPSOLR_Model
 * @package wpsolr\core\classes\models
 */
class WPSOLR_Model {

	/* @var string Model label */
	protected $label;

	/* @var string Table name storing the model */
	protected $table_name;

	/* @var string Column containing the model id */
	protected $column_id;

	/* @var string Column containing the model timestamp */
	protected $column_last_updated;

	/* @var array SQL statement for the indexing loop */
	protected $indexing_sql;

	/** @var  string $post_type */
	protected $post_type;

	/**
	 * WPSOLR_Model constructor.
	 *
	 * @param array $params
	 */
	public function __construct( $params ) {
	}

	/**
	 * @return string
	 */
	public function get_table_name() {
		return $this->table_name;
	}

	/**
	 * @param string $table_name
	 *
	 * @return $this
	 */
	public function set_table_name( $table_name ) {
		$this->table_name = $table_name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_column_id() {
		return $this->column_id;
	}

	/**
	 * @param string $column_id
	 *
	 * @return $this
	 */
	public function set_column_id( $column_id ) {
		$this->column_id = $column_id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_column_last_updated() {
		return $this->column_last_updated;
	}

	/**
	 * @param string $column_last_updated
	 *
	 * @return $this
	 */
	public function set_column_last_updated( $column_last_updated ) {
		$this->column_last_updated = $column_last_updated;

		return $this;
	}

	/**
	 * @param $debug_text
	 * @param int $batch_size
	 * @param \WP_Post $post
	 * @param bool $is_debug_indexing
	 * @param bool $is_only_exclude_ids
	 *
	 * @return array
	 */
	public function get_indexing_sql( $debug_text, $batch_size = 100, $post = null, $is_debug_indexing = false, $is_only_exclude_ids = false ) {
		return $this->indexing_sql;
	}

	/**
	 * @param array $column_last_updated
	 *
	 * @return $this
	 */
	public function set_indexing_sql( $indexing_sql ) {
		$this->indexing_sql = $indexing_sql;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * @param string $label
	 *
	 * @return WPSOLR_Model
	 */
	public function set_label( $label ) {
		$this->label = $label;

		return $this;
	}

	/**
	 * @param string $post_type
	 *
	 * @return WPSOLR_Model
	 */
	public function set_post_type( $post_type ) {
		$this->post_type = $post_type;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_post_type() {
		return $this->post_type;
	}


}