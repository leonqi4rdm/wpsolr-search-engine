<?php

namespace wpsolr\core\classes\extensions\licenses;

use wpsolr\core\classes\extensions\managed_solr_servers\OptionManagedSolrServer;
use wpsolr\core\classes\extensions\WpSolrExtensions;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\WPSOLR_Events;

/**
 * Class OptionLicenses
 *
 * Manage licenses options
 */
class OptionLicenses extends WpSolrExtensions {

	// Ajax methods
	const AJAX_ACTIVATE_LICENCE = 'ajax_activate_licence';
	const AJAX_DEACTIVATE_LICENCE = 'ajax_deactivate_licence';
	const AJAX_VERIFY_LICENCE = 'ajax_verify_licence';

	// License types
	const LICENSE_PACKAGE_PREMIUM = 'LICENSE_PACKAGE_CORE';
	const LICENSE_PACKAGE_WOOCOMMERCE = 'LICENSE_PACKAGE_WOOCOMMERCE';
	const LICENSE_PACKAGE_ACF = 'LICENSE_PACKAGE_ACF';
	const LICENSE_PACKAGE_TYPES = 'LICENSE_PACKAGE_TYPES';
	const LICENSE_PACKAGE_WPML = 'LICENSE_PACKAGE_WPML';
	const LICENSE_PACKAGE_POLYLANG = 'LICENSE_PACKAGE_POLYLANG';
	const LICENSE_PACKAGE_GROUPS = 'LICENSE_PACKAGE_GROUPS';
	const LICENSE_PACKAGE_S2MEMBER = 'LICENSE_PACKAGE_S2MEMBER';
	const LICENSE_PACKAGE_BBPRESS = 'LICENSE_PACKAGE_BBPRESS';
	const LICENSE_PACKAGE_EMBED_ANY_DOCUMENT = 'LICENSE_PACKAGE_EMBED_ANY_DOCUMENT';
	const LICENSE_PACKAGE_PDF_EMBEDDER = 'LICENSE_PACKAGE_PDF_EMBEDDER';
	const LICENSE_PACKAGE_GOOGLE_DOC_EMBEDDER = 'LICENSE_PACKAGE_GOOGLE_DOC_EMBEDDER';
	const LICENSE_PACKAGE_TABLEPRESS = 'LICENSE_PACKAGE_TABLEPRESS';
	const LICENSE_PACKAGE_GEOLOCATION = 'LICENSE_PACKAGE_GEOLOCATION';
	const LICENSE_PACKAGE_THEME = 'LICENSE_PACKAGE_THEME';
	const LICENSE_PACKAGE_YOAST_SEO = 'LICENSE_PACKAGE_YOAST_SEO';
	const LICENSE_PACKAGE_ALL_IN_ONE_SEO_PACK = 'LICENSE_PACKAGE_ALL_IN_ONE_SEO_PACK';
	const LICENSE_PACKAGE_WP_ALL_IMPORT_PACK = 'LICENSE_PACKAGE_WP_ALL_IMPORT_PACK';
	const LICENSE_PACKAGE_SCORING = 'LICENSE_PACKAGE_SCORING';
	const LICENSE_EXTENSION = 'LICENSE_EXTENSION';
	const LICENSE_PACKAGE_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE = 'LICENSE_PACKAGE_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE';
	const LICENSE_PACKAGE_LISTIFY = 'LICENSE_PACKAGE_LISTIFY';
	const LICENSE_PACKAGE_CRON = 'LICENSE_PACKAGE_CRON';

	// License type fields
	const FIELD_LICENSE_SUBSCRIPTION_NUMBER = 'license_subscription_number';
	const FIELD_LICENSE_PACKAGE = 'license_package';
	const FIELD_DESCRIPTION = 'description';
	const FIELD_IS_ACTIVATED = 'is_activated';
	const FIELD_ORDERS_URLS = 'orders_urls';
	const FIELD_ORDER_URL_BUTTON_LABEL = 'order_url_button_label';
	const FIELD_ORDER_URL_TEXT = 'order_url_text';
	const FIELD_ORDER_URL_LINK = 'order_url_link';
	const FIELD_FEATURES = 'features';
	const FIELD_LICENSE_TITLE = 'LICENSE_TITLE';
	const FIELD_LICENSE_MATCHING_REFERENCE = 'matching_license_reference';
	const FIELD_NEEDS_VERIFICATION = 'needs_verification';
	const FIELD_LICENSE_ACTIVATION_UUID = 'activation_uuid';

	// Texts
	const TEXT_LICENSE_ACTIVATED = 'License is activated';
	const TEXT_LICENSE_DEACTIVATED = 'License is not activated. Click to activate.';

	public $is_installed;
	private $_options;

	// Order link
	const FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE = '7 days free trial (Premium pack only)';
	const ORDER_LINK_URL_BESPOKE = 'https://secure.avangate.com/order/trial.php?PRODS=4687291&QTY=1&PRICES4687291[EUR]=0&TPERIOD=7&PHASH=af1373521d3efd46f8db12dfde45c91d';
	const FIELD_ORDER_URL_BUTTON_LABEL_ALL_INCLUDED = 'Take the WPSOLR PRO plugin free trial';
	const ORDER_LINK_URL_ALL_INCLUDED = 'https://secure.avangate.com/order/trial.php?PRODS=4699867&QTY=1&PAY_TYPE=CCVISAMC&PRICES4699867[EUR]=0&TPERIOD=7&PHASH=fe0b336dbb4e61f9acc564925ed34622';
	const FIELD_ORDER_URL_BUTTON_LABEL_MANAGED = 'In a hurry ? We manage WPSOLR and Solr for you';
	const ORDER_LINK_URL_MANAGED = 'https://secure.avangate.com/order/checkout.php?PRODS=4701516&QTY=1';

	// Features
	const FEATURE_ZENDESK_SUPPORT = 'Get support via Zendesk <br/>(Apache Solr setup/installation not supported)';
	const FEATURE_FREE_UPGRADE_ONE_YEAR = 'Get free upgrades during one year';
	const LICENSE_API_URL = 'https://api.gotosolr.com/v1/providers/8c25d2d6-54ae-4ff6-a478-e2c03f1e08a4/accounts/24b7729e-02dc-47d1-9c15-f1310098f93f/addons/b553e78c-3af8-4c97-9157-db77bfa6d909/license-manager/83e214e6-54f8-4f59-ba95-889de756ebee/licenses';

	/**
	 * Constructor.
	 */
	function __construct() {
		$this->_options     = self::get_option_data( self::OPTION_LICENSES, [] );
		$this->is_installed = true;
	}


	/**
	 * Return all activated licenses
	 */
	function get_licenses() {
		$results = $this->_options;

		return $results;
	}


	/**
	 * Upgrade all licenses
	 */
	static function upgrade_licenses() {

		// Upgrade licenses
		$licenses = self::get_option_data( self::OPTION_LICENSES, [] );

		if ( ! empty( $licenses ) ) {

			foreach ( $licenses as $license_package => $license ) {

				$licenses[ $license_package ][ self::FIELD_NEEDS_VERIFICATION ] = true;
			}

			self::set_option_data( self::OPTION_LICENSES, $licenses );

		} else {

			// Installation
			WPSOLR_Service_Container::getOption()->get_option_installation();
		}

	}

	/**
	 * Get any license
	 */
	function get_any_license() {

		foreach ( $this->get_licenses() as $license_package_installed => $license ) {

			if ( ! empty( $license[ self::FIELD_LICENSE_SUBSCRIPTION_NUMBER ] ) && ! empty( $license[ self::FIELD_LICENSE_ACTIVATION_UUID ] ) ) {
				return $license[ self::FIELD_LICENSE_SUBSCRIPTION_NUMBER ];
			}
		}

		return '';
	}

	/**
	 * Is a license activated ?
	 */
	function get_license_is_activated( $license_type ) {

		switch ( $license_type ) {
			case  self::LICENSE_PACKAGE_PREMIUM:
			case self::LICENSE_PACKAGE_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE:
				// Premium is part of the free version now !
				return true;
		}

		$licenses = $this->get_licenses();

		return isset( $licenses[ $license_type ] )
		       && isset( $licenses[ $license_type ][ self::FIELD_IS_ACTIVATED ] )
		       && ! isset( $licenses[ $license_type ][ self::FIELD_NEEDS_VERIFICATION ] );
	}

	/**
	 * Get a license
	 */
	function get_license( $license_type ) {
		$licenses = $this->get_licenses();

		return isset( $licenses[ $license_type ] ) ? $licenses[ $license_type ] : [];
	}

	/**
	 * Is a license need to be verified ?
	 */
	function get_license_is_need_verification( $license_type ) {
		$licenses = $this->get_licenses();

		return isset( $licenses[ $license_type ] )
		       && isset( $licenses[ $license_type ][ self::FIELD_IS_ACTIVATED ] )
		       && isset( $licenses[ $license_type ][ self::FIELD_NEEDS_VERIFICATION ] );
	}

	/**
	 * Is a license can be deactivated ?
	 */
	function get_license_is_can_be_deactivated( $license_type ) {
		$licenses = $this->get_licenses();

		return isset( $licenses[ $license_type ] )
		       && isset( $licenses[ $license_type ][ self::FIELD_IS_ACTIVATED ] );
	}


	/**
	 * Get licanse activation api url
	 */
	static function get_license_api_url() {

		return apply_filters( WPSOLR_Events::WPSOLR_FILTER_ENV_LICENSE_API_URL, self::LICENSE_API_URL );
	}

	/**
	 * Return all license types
	 */
	static function get_license_types() {

		return [
			self::LICENSE_PACKAGE_PREMIUM                           => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_PREMIUM,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_premium',
				self::FIELD_LICENSE_TITLE              => 'Premium',
				self::FIELD_DESCRIPTION                => '',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Create a test Solr index, valid 2 hours',
					'Configure several Solr indexes',
					'Select your theme search page',
					'Select Infinite Scroll navigation in Ajax search',
					'Display suggestions (Did you mean?)',
					'Index custom post types',
					'Index attachments',
					'Index custom taxonomies',
					'Index custom fields',
					'Show facets hierarchies',
					'Localize (translate) the front search page with your .po files',
					'Display debug infos during indexing',
					'Reindex all your data in-place',
					'Deactivate real-time indexing to load huge external datafeeds',
				],
			],
			self::LICENSE_PACKAGE_WOOCOMMERCE                       => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_WOOCOMMERCE,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_woocommerce',
				self::FIELD_LICENSE_TITLE              => 'WooCommerce',
				self::FIELD_DESCRIPTION                => 'WooCommerce Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Index product attributes/variations',
					'Search in product attributes/variations',
					'Create facets on product attributes/variations',
					WPSOLR_Help::get_help( WPSOLR_Help::HELP_SEARCH_ORDERS ) . 'Replace admin orders search with WPSOLR search',
				],
			],
			self::LICENSE_PACKAGE_ACF                               => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_ACF,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_acf',
				self::FIELD_LICENSE_TITLE              => 'ACF',
				self::FIELD_DESCRIPTION                => 'ACF Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Replace facet names with their ACF label',
					'Decode ACF field values before indexing a post',
					'Index ACF field files content inside the post',
					'Group ACF repeater rows under one single facet field (requires ACF Pro 5.0.0)',
					WPSOLR_Help::get_help( WPSOLR_Help::HELP_ACF_REPEATERS_AND_FLEXIBLE_CONTENT_LAYOUTS ) . 'Manage ACF Repeaters and Flexible Content Layouts',
					WPSOLR_Help::get_help( WPSOLR_Help::HELP_ACF_GOOGLE_MAP ) . 'Manage ACF Google Map fields (requires ACF Pro 5.0.0, and our Geolocation Pack )',
				],
			],
			self::LICENSE_PACKAGE_TYPES                             => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_TYPES,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_types',
				self::FIELD_LICENSE_TITLE              => 'Types',
				self::FIELD_DESCRIPTION                => 'Types Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Replace facet names with their Types label',
				],
			],
			self::LICENSE_PACKAGE_WPML                              => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_WPML,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_wpml',
				self::FIELD_LICENSE_TITLE              => 'WPML',
				self::FIELD_DESCRIPTION                => 'WPML Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'User can associate WPML languages to their own Solr index',
					'Indexing process send each data to it\'s language related Solr index',
					'Search results are displayed in each WPML languages',
				],
			],
			self::LICENSE_PACKAGE_POLYLANG                          => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_POLYLANG,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_polylang',
				self::FIELD_LICENSE_TITLE              => 'Polylang',
				self::FIELD_DESCRIPTION                => 'Polylang Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'User can associate Polylang languages to their own Solr index',
					'Indexing process send each data to it\'s language related Solr index',
					'Search results are displayed in each Polylang languages',
				],
			],
			self::LICENSE_PACKAGE_GROUPS                            => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_GROUPS,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_groups',
				self::FIELD_LICENSE_TITLE              => 'Groups',
				self::FIELD_DESCRIPTION                => 'Groups Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Results are indexed and filtered with Groups user\'s groups/capabilities',
				],
			],
			self::LICENSE_PACKAGE_S2MEMBER                          => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_S2MEMBER,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_s2member',
				self::FIELD_LICENSE_TITLE              => 's2Member',
				self::FIELD_DESCRIPTION                => 's2Member Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Results are indexed and filtered with s2Member user\'s levels/capabilities capabilities',
				],
			],
			self::LICENSE_PACKAGE_BBPRESS                           => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_BBPRESS,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_bbpress',
				self::FIELD_LICENSE_TITLE              => 'bbPress',
				self::FIELD_DESCRIPTION                => 'bbPress Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Benefit from the Solr search features (speed, relevancy, partial match, fuzzy match ...), while keeping your current bbPress theme.',
				],
			],
			self::LICENSE_PACKAGE_EMBED_ANY_DOCUMENT                => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_EMBED_ANY_DOCUMENT,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_embed_any_document',
				self::FIELD_LICENSE_TITLE              => 'Embed Any Document',
				self::FIELD_DESCRIPTION                => 'Embed Any Document Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Automatically index and search embedded documents with the plugin shortcode.',
				],
			],
			self::LICENSE_PACKAGE_PDF_EMBEDDER                      => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_PDF_EMBEDDER,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_pdf_embedder',
				self::FIELD_LICENSE_TITLE              => 'Pdf Embedder',
				self::FIELD_DESCRIPTION                => 'Pdf Embedder Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Automatically index and search embedded pdfs with the plugin shortcode.',
				],
			],
			self::LICENSE_PACKAGE_GOOGLE_DOC_EMBEDDER               => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_GOOGLE_DOC_EMBEDDER,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_google_doc_embedder',
				self::FIELD_LICENSE_TITLE              => 'Google Doc Embedder',
				self::FIELD_DESCRIPTION                => 'Google Doc Embedder Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Automatically index and search embedded documents with the plugin shortcode.',
				],
			],
			self::LICENSE_PACKAGE_TABLEPRESS                        => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_TABLEPRESS,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_tablepress',
				self::FIELD_LICENSE_TITLE              => 'TablePress',
				self::FIELD_DESCRIPTION                => 'TablePress Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Index TablePress shortcodes content',
					'Format TablePress shortcodes content to remove html tags, before indexing',
				],
			],
			self::LICENSE_PACKAGE_GEOLOCATION                       => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_GEOLOCATION,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_geolocation',
				self::FIELD_LICENSE_TITLE              => 'Geolocation',
				self::FIELD_DESCRIPTION                => 'Geolocation Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Work with latitude and longitude locations (a product\'s store coordinates)',
					'A location is simply a custom field containing a string "latitude,longitude"',
					'Can manage multi-locations configurations (a product with several stores)',
					'Automatic gathering of visitor\'s location',
					'Sort results by distance from the visitor\'s location',
					'Add distance(s) from the visitor\'s location to results\' locations',
					//'Filter results by distance from the visitor\'s location',
				],
			],
			self::LICENSE_PACKAGE_THEME                             => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::OPTION_THEME,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_theme',
				self::FIELD_LICENSE_TITLE              => 'Theme',
				self::FIELD_DESCRIPTION                => 'Theme Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Collapse/Uncollapse facets',
				],
			],
			self::LICENSE_PACKAGE_YOAST_SEO                         => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_YOAST_SEO,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_yoast_seo',
				self::FIELD_LICENSE_TITLE              => 'Yoast SEO',
				self::FIELD_DESCRIPTION                => 'Yoast SEO Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Replace search urls with beautiful permalinks',
					'Add metas to search pages'
				],
			],
			self::LICENSE_PACKAGE_ALL_IN_ONE_SEO_PACK               => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_ALL_IN_ONE_SEO,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_all_in_one_seo_pack',
				self::FIELD_LICENSE_TITLE              => 'All in One SEO Pack',
				self::FIELD_DESCRIPTION                => 'All in One SEO Pack Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Replace search urls with beautiful permalinks',
					'Add metas to search pages'
				],
			],
			self::LICENSE_PACKAGE_WP_ALL_IMPORT_PACK                => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_WP_ALL_IMPORT,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_wp_all_import_pack',
				self::FIELD_LICENSE_TITLE              => 'WP All Import Pack',
				self::FIELD_DESCRIPTION                => 'WP All Import Pack Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Fix posts not removed from the search engine index while deleted by import',
				],
			],
			self::LICENSE_PACKAGE_SCORING                           => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_SCORING,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_scoring',
				self::FIELD_LICENSE_TITLE              => 'Advanced scoring',
				self::FIELD_DESCRIPTION                => 'Advanced scoring Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Add advanced scoring functions to get absolutly crazy search results',
				],
			],
			self::LICENSE_PACKAGE_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_yith_woocommerce_ajax_search_free',
				self::FIELD_LICENSE_TITLE              => 'YITH WooCommerce Ajax Search (Free)',
				self::FIELD_DESCRIPTION                => '',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Replace product suggestions template with YITH WooCommerce Ajax Search\'s template',
				],
			],
			self::LICENSE_PACKAGE_LISTIFY                           => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_THEME_LISTIFY,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_listify',
				self::FIELD_LICENSE_TITLE              => 'Listify Theme',
				self::FIELD_DESCRIPTION                => 'Listify Theme Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Index product attributes/variations',
					'Search in product attributes/variations',
					'Create facets on product attributes/variations',
					WPSOLR_Help::get_help( WPSOLR_Help::HELP_SEARCH_ORDERS ) . 'Replace admin orders search with WPSOLR search',
				],
			],
			self::LICENSE_PACKAGE_CRON                              => [
				self::LICENSE_EXTENSION                => WpSolrExtensions::EXTENSION_CRON,
				self::FIELD_LICENSE_MATCHING_REFERENCE => 'wpsolr_package_cron',
				self::FIELD_LICENSE_TITLE              => 'Cron',
				self::FIELD_DESCRIPTION                => 'Cron Extension description',
				self::FIELD_ORDERS_URLS                => [
					[
						self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
						self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
						self::FIELD_ORDER_URL_LINK         => self::ORDER_LINK_URL_BESPOKE,
					],
				],
				self::FIELD_FEATURES                   => [
					self::FEATURE_ZENDESK_SUPPORT,
					self::FEATURE_FREE_UPGRADE_ONE_YEAR,
					'Define one, or several crons, to index your data',
					'Each cron is called with it\'s own REST url. cURL command is provided',
					'Each cron REST url is protected by a Basic authentication',
					'Each cron REST url returns a JSON detailing how many documents where sent, agregated by index',
					'Call sequentially any index in each cron. Reorder the sequence by drag&drop',
					'Call crons in parallel',
				],
			],
		];

	}


	/**
	 * Show premium link in place of a text if not licensed
	 *
	 * @param $license_type
	 * @param $text_to_show
	 * @param $is_show_link
	 *
	 * @return string
	 */
	function show_premium_link( $license_type, $text_to_show, $is_show_link, $is_new_feature = false ) {

		if ( ( ! $this->is_installed && ! $is_new_feature ) || $this->get_license_is_activated( $license_type ) ) {

			if ( ( ! $is_show_link ) || ( ! $this->is_installed && ! $is_new_feature ) ) {
				return ( self::TEXT_LICENSE_ACTIVATED === $text_to_show ) ? '' : $text_to_show;
			}

			$img_url = plugins_url( 'images/success.png', WPSOLR_PLUGIN_FILE );

		} else {

			$img_url      = plugins_url( 'images/warning.png', WPSOLR_PLUGIN_FILE );
			$text_to_show .= '<p>(Feature-limited version, click to activate)</p>';
		}

		$result = sprintf(
			'<a href="#TB_inline?width=800&height=700&inlineId=%s" class="thickbox wpsolr_premium_class" ><img src="%s" class="wpsolr_premium_text_class" style="display:inline"><span>%s</span></a>',
			$license_type,
			$img_url,
			$text_to_show
		);

		return $result;
	}

	/**
	 * Output a disable html code if not licensed
	 *
	 * @param $license_type
	 *
	 * @param bool $is_new_feature
	 *
	 * @return string
	 */
	function get_license_enable_html_code( $license_type, $is_new_feature = false ) {

		return ( ( ! $this->is_installed && ! $is_new_feature ) || $this->get_license_is_activated( $license_type ) ) ? '' : 'disabled';
	}


	/**
	 * Output a readonly html code if not licensed
	 *
	 * @param $license_type
	 *
	 * @param bool $is_new_feature
	 *
	 * @return string
	 */
	function get_license_readonly_html_code( $license_type, $is_new_feature = false ) {

		return ( ( ! $this->is_installed && ! $is_new_feature ) || $this->get_license_is_activated( $license_type ) ) ? '' : 'readonly';
	}

	/**
	 * Get a license type order urls
	 * @return mixed
	 */
	public
	function get_license_orders_urls(
		$license_type
	) {
		$license_types = $this->get_license_types();

		//return $license_types[ $license_type ][ self::FIELD_ORDERS_URLS ];

		return array(
			array(
				self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_ALL_INCLUDED,
				self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
				self::FIELD_ORDER_URL_LINK         => $this->add_campaign_to_url( self::ORDER_LINK_URL_ALL_INCLUDED ),
			),/*
			array(
				self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_BESPOKE,
				self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
				self::FIELD_ORDER_URL_LINK         => $this->add_campaign_to_url( self::ORDER_LINK_URL_BESPOKE ),
			),
			array(
				self::FIELD_ORDER_URL_BUTTON_LABEL => self::FIELD_ORDER_URL_BUTTON_LABEL_MANAGED,
				self::FIELD_ORDER_URL_TEXT         => 'Order a pack now',
				self::FIELD_ORDER_URL_LINK         => $this->add_campaign_to_url( self::ORDER_LINK_URL_MANAGED ),
			),*/
		);

	}

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	public function add_campaign_to_url( $url ) {

		return sprintf( '%s%scamp=plugin_wpsolr&wpsolr_v=%s', $url, ( false === strpos( $url, '?' ) ) ? '?' : '&', WPSOLR_PLUGIN_VERSION );
	}

	/**
	 * Get a license matching reference
	 * @return mixed
	 */
	public
	function get_license_matching_reference(
		$license_type
	) {
		$license_types = $this->get_license_types();

		return $license_types[ $license_type ][ self::FIELD_LICENSE_MATCHING_REFERENCE ];
	}

	/**
	 * Get a license activation uuid
	 * @return string
	 */
	public
	function get_license_activation_uuid(
		$license_type
	) {
		$licenses = $this->get_licenses();

		return isset( $licenses[ $license_type ][ self::FIELD_LICENSE_ACTIVATION_UUID ] ) ? $licenses[ $license_type ][ self::FIELD_LICENSE_ACTIVATION_UUID ] : '';
	}

	/**
	 * Get a license subscription number
	 * @return string
	 */
	public
	function get_license_subscription_number(
		$license_type
	) {
		$licenses = $this->get_licenses();

		return isset( $licenses[ $license_type ][ self::FIELD_LICENSE_SUBSCRIPTION_NUMBER ] ) ? $licenses[ $license_type ][ self::FIELD_LICENSE_SUBSCRIPTION_NUMBER ] : '';
	}

	/**
	 * Get a license type features
	 * @return mixed
	 */
	public
	function get_license_features(
		$license_type
	) {
		$license_types = $this->get_license_types();

		return $license_types[ $license_type ][ self::FIELD_FEATURES ];
	}


	/**
	 * Ajax call to activate a license
	 */
	public
	static function ajax_activate_licence() {

		$subscription_number        = isset( $_POST['data'] ) && isset( $_POST['data'][ self::FIELD_LICENSE_SUBSCRIPTION_NUMBER ] ) ? $_POST['data'][ self::FIELD_LICENSE_SUBSCRIPTION_NUMBER ] : null;
		$license_package            = isset( $_POST['data'] ) && isset( $_POST['data'][ self::FIELD_LICENSE_PACKAGE ] ) ? $_POST['data'][ self::FIELD_LICENSE_PACKAGE ] : null;
		$license_matching_reference = isset( $_POST['data'] ) && isset( $_POST['data'][ self::FIELD_LICENSE_MATCHING_REFERENCE ] ) ? $_POST['data'][ self::FIELD_LICENSE_MATCHING_REFERENCE ] : null;

		$managed_solr_server = new OptionManagedSolrServer();
		$response_object     = $managed_solr_server->call_rest_activate_license( self::get_license_api_url(), $license_matching_reference, $subscription_number );

		if ( isset( $response_object ) && OptionManagedSolrServer::is_response_ok( $response_object ) ) {

			// Save the license type activation
			$licenses                     = self::get_option_data( self::OPTION_LICENSES, [] );
			$licenses[ $license_package ] = [
				self::FIELD_IS_ACTIVATED                => true,
				self::FIELD_LICENSE_SUBSCRIPTION_NUMBER => $subscription_number,
				self::FIELD_LICENSE_ACTIVATION_UUID     => OptionManagedSolrServer::get_response_result( $response_object, 'uuid' ),
			];
			self::set_option_data( self::OPTION_LICENSES, $licenses );

		} else {

			$response_object = $managed_solr_server->call_rest_activate_license( self::get_license_api_url(), 'wpsolr_package_multi', $subscription_number );

			if ( isset( $response_object ) && OptionManagedSolrServer::is_response_ok( $response_object ) ) {

				// Save the license type activation
				$licenses = self::get_option_data( self::OPTION_LICENSES, [] );
				foreach ( self::get_license_types() as $license_package => $license_definition ) {

					$licenses[ $license_package ] = [
						self::FIELD_IS_ACTIVATED                => true,
						self::FIELD_LICENSE_SUBSCRIPTION_NUMBER => $subscription_number,
						self::FIELD_LICENSE_ACTIVATION_UUID     => OptionManagedSolrServer::get_response_result( $response_object, 'uuid' ),
					];
				}
				self::set_option_data( self::OPTION_LICENSES, $licenses );
			}
		}

		// Return the whole object
		echo json_encode( $response_object );

		die();
	}

	/**
	 * Ajax call to deactivate a license
	 */
	public
	static function ajax_deactivate_licence() {

		$option_licenses = new OptionLicenses();
		$licenses        = $option_licenses->get_licenses();

		$license_package         = isset( $_POST['data'] ) && isset( $_POST['data'][ self::FIELD_LICENSE_PACKAGE ] ) ? $_POST['data'][ self::FIELD_LICENSE_PACKAGE ] : null;
		$license_activation_uuid = $option_licenses->get_license_activation_uuid( $license_package );

		if ( empty( $license_activation_uuid ) ) {

			$licenses[ $license_package ] = [
				self::FIELD_LICENSE_SUBSCRIPTION_NUMBER => $licenses[ $license_package ][ self::FIELD_LICENSE_SUBSCRIPTION_NUMBER ],
			];
			self::set_option_data( self::OPTION_LICENSES, $licenses );

			echo json_encode( (object) array(
				'status' => (object) array(
					'state'   => 'ERROR',
					'message' => 'This license activation code is missing. Try to unactivate manually, by signin to your subscription account.'
				)
			) );

			die();
		}

		$managed_solr_server = new OptionManagedSolrServer();
		$response_object     = $managed_solr_server->call_rest_deactivate_license( self::get_license_api_url(), $license_activation_uuid );

		if ( isset( $response_object ) && OptionManagedSolrServer::is_response_ok( $response_object ) ) {

		}

		// Always remove the activation, else we're stuck forever
		$licenses = self::get_option_data( self::OPTION_LICENSES, [] );
		foreach ( $licenses as $license_package_installed => $license ) {

			if ( $license_activation_uuid === $license[ self::FIELD_LICENSE_ACTIVATION_UUID ] ) {
				$licenses[ $license_package_installed ] = array(
					self::FIELD_LICENSE_SUBSCRIPTION_NUMBER => $licenses[ $license_package ][ self::FIELD_LICENSE_SUBSCRIPTION_NUMBER ],
				);
			}
		}
		self::set_option_data( self::OPTION_LICENSES, $licenses );

		// Return the whole object
		echo json_encode( $response_object );

		die();

	}

	/**
	 * Ajax call to verify a license
	 */
	public
	static function ajax_verify_licence() {

		$option_licenses = new OptionLicenses();
		$licenses        = $option_licenses->get_licenses();

		$license_package         = isset( $_POST['data'] ) && isset( $_POST['data'][ self::FIELD_LICENSE_PACKAGE ] ) ? $_POST['data'][ self::FIELD_LICENSE_PACKAGE ] : null;
		$license_activation_uuid = $option_licenses->get_license_activation_uuid( $license_package );

		if ( empty( $license_activation_uuid ) ) {

			$licenses[ $license_package ] = array(
				self::FIELD_LICENSE_SUBSCRIPTION_NUMBER => $licenses[ $license_package ][ self::FIELD_LICENSE_SUBSCRIPTION_NUMBER ],
			);
			self::set_option_data( self::OPTION_LICENSES, $licenses );

			echo json_encode( (object) array(
				'status' => (object) array(
					'state'   => 'ERROR',
					'message' => 'This license activation code is missing. Try to unactivate manually, by signin to your subscription account.'
				)
			) );

			die();
		}

		$managed_solr_server = new OptionManagedSolrServer();
		$response_object     = $managed_solr_server->call_rest_verify_license( self::get_license_api_url(), $license_activation_uuid );

		if ( isset( $response_object ) && OptionManagedSolrServer::is_response_ok( $response_object ) ) {

			if ( isset( $licenses[ $license_package ] ) ) {

				// Remove the license type activation
				$licenses = self::get_option_data( self::OPTION_LICENSES, [] );
				foreach ( $licenses as $license_package_installed => $license ) {

					if ( $license_activation_uuid === $license[ self::FIELD_LICENSE_ACTIVATION_UUID ] ) {
						unset( $licenses[ $license_package_installed ][ self::FIELD_NEEDS_VERIFICATION ] );
					}
				}
				self::set_option_data( self::OPTION_LICENSES, $licenses );
			}

		}

		// Return the whole object
		echo json_encode( $response_object );

		die();
	}

	/**
	 * Get all activated licenses
	 *
	 * @return array
	 */
	public static function get_activated_licenses_links( $license_type = null ) {

		$results = [];

		$option_licenses = new OptionLicenses();
		$license_types   = $option_licenses->get_license_types();
		$licenses        = empty( $license_type ) ? $option_licenses->get_licenses() : [ $license_type => $option_licenses->get_license( $license_type ) ];

		foreach ( $licenses as $license_code => $license ) {

			if ( $option_licenses->get_license_is_activated( $license_code ) ) {

				if ( isset( $license_types[ $license_code ] )
				     && isset( $license_types[ $license_code ][ self::LICENSE_EXTENSION ] )
				     && ( ( self::LICENSE_PACKAGE_PREMIUM === $license_code ) || WpSolrExtensions::is_extension_option_activate( $license_types[ $license_code ][ self::LICENSE_EXTENSION ] ) )
				) {
					$license_link = $option_licenses->show_premium_link( $license_code, $option_licenses->get_license_title( $license_code ), true );
					array_push( $results, $license_link );
				}
			}
		}

		return $results;
	}


	/**
	 * Get a license title
	 *
	 * @param $license_code
	 *
	 * @return array
	 */
	public function get_license_title(
		$license_code
	) {

		$license_defs = self::get_license_types();

		return ! empty( $license_defs[ $license_code ] ) && ! empty( $license_defs[ $license_code ][ self::FIELD_LICENSE_TITLE ] ) ? $license_defs[ $license_code ][ self::FIELD_LICENSE_TITLE ] : $license_code;
	}

}

// Register Ajax events
add_action( 'wp_ajax_' . OptionLicenses::AJAX_ACTIVATE_LICENCE, [
	OptionLicenses::class,
	OptionLicenses::AJAX_ACTIVATE_LICENCE
] );

add_action( 'wp_ajax_' . OptionLicenses::AJAX_DEACTIVATE_LICENCE, [
	OptionLicenses::class,
	OptionLicenses::AJAX_DEACTIVATE_LICENCE
] );

add_action( 'wp_ajax_' . OptionLicenses::AJAX_VERIFY_LICENCE, [
	OptionLicenses::class,
	OptionLicenses::AJAX_VERIFY_LICENCE
] );
