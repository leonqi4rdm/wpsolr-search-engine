<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\extensions\WpSolrExtensions;

?>

<?php
$subtabs1 = [
	'extension_acf_opt'                               => [
		'name'  => '>> ACF',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_ACF, WpSolrExtensions::EXTENSION_ACF ),
	],
	'extension_scoring_opt'                           => [
		'name'  => '>> Advanced scoring',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_SCORING, WpSolrExtensions::EXTENSION_SCORING ),
	],
	'extension_all_in_one_seo_opt'                    => [
		'name'  => '>> All in One SEO',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_ALL_IN_ONE_SEO_PACK, WpSolrExtensions::EXTENSION_ALL_IN_ONE_SEO ),
	],
	'extension_bbpress_opt'                           => [
		'name'  => '>> bbPress',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_BBPRESS, WpSolrExtensions::EXTENSION_BBPRESS ),
	],
	'extension_cron_opt'                              => [
		'name'  => '>> Cron scheduling',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_CRON, WpSolrExtensions::EXTENSION_CRON ),
	],
	'extension_embed_any_document_opt'                => [
		'name'  => '>> Embed Any Document',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_EMBED_ANY_DOCUMENT, WpSolrExtensions::EXTENSION_EMBED_ANY_DOCUMENT ),
	],
	'extension_geolocation_opt'                       => [
		'name'  => '>> Geolocation',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_GEOLOCATION, WpSolrExtensions::EXTENSION_GEOLOCATION ),
	],
	'extension_google_doc_embedder_opt'               => [
		'name'  => '>> Google Doc Embedder',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_GOOGLE_DOC_EMBEDDER, WpSolrExtensions::EXTENSION_GOOGLE_DOC_EMBEDDER ),
	],
	'extension_groups_opt'                            => [
		'name'  => '>> Groups',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_GROUPS, WpSolrExtensions::EXTENSION_GROUPS ),
	],
	'extension_pdf_embedder_opt'                      => [
		'name'  => '>> PDF Embedder',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_PDF_EMBEDDER, WpSolrExtensions::EXTENSION_PDF_EMBEDDER ),
	],
	'extension_polylang_opt'                          => [
		'name'  => '>> Polylang',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_POLYLANG, WpSolrExtensions::EXTENSION_POLYLANG ),
	],
	'extension_premium_opt'                           => [
		'name'  => '>> Premium',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_PREMIUM, WpSolrExtensions::EXTENSION_PREMIUM ),
	],
	'extension_s2member_opt'                          => [
		'name'  => '>> s2Member',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_S2MEMBER, WpSolrExtensions::EXTENSION_S2MEMBER ),
	],
	// It seems impossible to map qTranslate X structure (1 post/many languages) in WPSOLR's (1 post/1 language)
	/* 'extension_qtranslatex_opt' => 'qTranslate X', */
	'extension_tablepress_opt'                        => [
		'name'  => '>> TablePress',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_TABLEPRESS, WpSolrExtensions::EXTENSION_TABLEPRESS ),
	],
	'extension_theme_opt'                             => [
		'name'  => '>> Theme',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_THEME, WpSolrExtensions::OPTION_THEME ),
	],
	'extension_types_opt'                             => [
		'name'  => '>> Toolset Types',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_TYPES, WpSolrExtensions::EXTENSION_TYPES ),
	],
	'extension_yith_woocommerce_ajax_search_free_opt' => [
		'name'  => '>> YITH Ajax Search (Free)',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE, WpSolrExtensions::EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE ),
	],
	'extension_yoast_seo_opt'                         => [
		'name'  => '>> Yoast SEO',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_YOAST_SEO, WpSolrExtensions::EXTENSION_YOAST_SEO ),
	],
	'extension_wp_all_import_opt'                     => [
		'name'  => '>> WP All Import',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::EXTENSION_WP_ALL_IMPORT, WpSolrExtensions::EXTENSION_WP_ALL_IMPORT ),
	],
	'extension_woocommerce_opt'                       => [
		'name'  => '>> WooCommerce',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_WOOCOMMERCE, WpSolrExtensions::EXTENSION_WOOCOMMERCE ),
	],
	'extension_wpml_opt'                              => [
		'name'  => '>> WPML',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_WPML, WpSolrExtensions::EXTENSION_WPML ),
	],

];

// Diplay the subtabs
include( 'dashboard_extensions.inc.php' );

