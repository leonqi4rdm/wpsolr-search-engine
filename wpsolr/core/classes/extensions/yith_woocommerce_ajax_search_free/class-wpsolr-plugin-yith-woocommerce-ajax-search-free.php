<?php

namespace wpsolr\core\classes\extensions\yith_woocommerce_ajax_search_free;

use WP_Query;
use wpsolr\core\classes\engines\WPSOLR_AbstractSearchClient;
use wpsolr\core\classes\extensions\WpSolrExtensions;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\ui\WPSOLR_Query;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;

/**
 * Class WPSOLR_Plugin_YITH_WooCommerce_Ajax_Search_Free
 * @package wpsolr\core\classes\extensions\yith_woocommerce_ajax_search_free
 */
class WPSOLR_Plugin_YITH_WooCommerce_Ajax_Search_Free extends WpSolrExtensions {

	/** @var bool $is_ajax */
	protected $is_ajax = false;

	/** @var bool $is_ajax */
	protected $is_ajax_processing = false;

	/**
	 * @inheritDoc
	 */
	public function __construct() {

		if ( WPSOLR_Service_Container::getOption()->get_yith_woocommerce_ajax_search_free_is_replace_product_suggestions() ) {

			// Intercept YITH Ajax before YITH_WCAS::ajax_search_products()
			add_action( 'wp_ajax_yith_ajax_search_products', [ $this, 'ajax_search_products' ], 9 );
			add_action( 'wp_ajax_nopriv_yith_ajax_search_products', [ $this, 'ajax_search_products' ], 9 );

			// Intercept get_products() in YITH_WCAS::ajax_search_products()
			add_filter( 'posts_pre_query', [ $this, 'query' ], 10, 2 );

			add_action( WPSOLR_Events::WPSOLR_ACTION_SOLARIUM_QUERY, [
				$this,
				'wpsolr_action_query',
			], 10, 1 );


		}

	}

	public function ajax_search_products() {

		$this->is_ajax = true;
	}

	/**
	 * Stop WordPress performing a DB query for its main loop.
	 *
	 * As of WordPress 4.6, it is possible to bypass the main WP_Query entirely.
	 * This saves us one unnecessary database query! :)
	 *
	 * @since 2.7.0
	 *
	 * @param  null $retval Current return value for filter.
	 * @param  WP_Query $query Current WordPress query object.
	 *
	 * @return null|array
	 */
	function query( $retval, $query ) {
		if ( ! $this->is_ajax || $this->is_ajax_processing ) {
			// This is not a YITH Ajax, or it's a recurse call.
			return $retval;
		}

		// To prevent recursive infinite calls
		$this->is_ajax_processing = true;

		$wpsolr_query                          = new WPSOLR_Query(); // Potential recurse here
		$wpsolr_query->query['post_type']      = 'product';
		$wpsolr_query->query['s']              = $query->query['s'];
		$wpsolr_query->query['posts_per_page'] = $query->query['posts_per_page'];
		$products                              = $wpsolr_query->get_posts();

		// Return $results, which prevents standard $wp_query to execute it's SQL.
		return $products;
	}


	/**
	 *
	 * Add a filter on product post type.
	 *
	 * @param array $parameters
	 *
	 */
	public function wpsolr_action_query( $parameters ) {

		/* @var WPSOLR_Query $wpsolr_query */
		$wpsolr_query = $parameters[ WPSOLR_Events::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_WPSOLR_QUERY ];
		/* @var WPSOLR_AbstractSearchClient $search_engine_client */
		$search_engine_client = $parameters[ WPSOLR_Events::WPSOLR_ACTION_SOLARIUM_QUERY__PARAM_SOLARIUM_CLIENT ];

		// post_type url parameter
		if ( ! empty( $wpsolr_query->query['post_type'] ) ) {

			$search_engine_client->search_engine_client_add_filter_term( sprintf( 'WPSOLR_Plugin_YITH_WooCommerce_Ajax_Search_Free type:%s', $wpsolr_query->query['post_type'] ), WpSolrSchema::_FIELD_NAME_TYPE, false, $wpsolr_query->query['post_type'] );
		}

	}

}