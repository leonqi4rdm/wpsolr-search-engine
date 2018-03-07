<?php

use wpsolr\core\classes\extensions\WpSolrExtensions;

?>

<?php

$subtabs2 = [];
foreach ( $subtabs1 as $indice => $subtab ) {
	if ( false !== strpos( $subtab['class'], 'wpsolr_is_available' ) ) {
		$subtabs2[ $indice ] = $subtab;
	}
}
foreach ( $subtabs1 as $indice => $subtab ) {
	if ( false === strpos( $subtab['class'], 'wpsolr_is_available' ) ) {
		$subtabs2[ $indice ] = $subtab;
	}
}
$subtabs = [];
foreach ( $subtabs2 as $indice => $subtab ) {
	if ( false !== strpos( $subtab['class'], 'wpsolr_tab_active' ) ) {
		$subtabs[ $indice ] = $subtab;
	}
}
foreach ( $subtabs2 as $indice => $subtab ) {
	if ( false === strpos( $subtab['class'], 'wpsolr_tab_active' ) ) {
		$subtabs[ $indice ] = $subtab;
	}
}

$subtab = wpsolr_admin_sub_tabs( $subtabs );

switch ( $subtab ) {
	case 'extension_groups_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_GROUPS );
		break;

	case 'extension_s2member_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_S2MEMBER );
		break;

	case 'extension_wpml_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_WPML );
		break;

	case 'extension_polylang_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_POLYLANG );
		break;

	case 'extension_qtranslatex_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_QTRANSLATEX );
		break;

	case 'extension_woocommerce_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_WOOCOMMERCE );
		break;

	case 'extension_acf_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_ACF );
		break;

	case 'extension_types_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_TYPES );
		break;

	case 'extension_bbpress_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_BBPRESS );
		break;

	case 'extension_embed_any_document_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_EMBED_ANY_DOCUMENT );
		break;

	case 'extension_pdf_embedder_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_PDF_EMBEDDER );
		break;

	case 'extension_google_doc_embedder_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_GOOGLE_DOC_EMBEDDER );
		break;

	case 'extension_tablepress_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_TABLEPRESS );
		break;

	case 'extension_geolocation_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_GEOLOCATION );
		break;

	case 'extension_premium_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_PREMIUM );
		break;

	case 'extension_theme_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::OPTION_THEME );
		break;

	case 'extension_yoast_seo_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_YOAST_SEO );
		break;

	case 'extension_all_in_one_seo_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_ALL_IN_ONE_SEO );
		break;

	case 'extension_wp_all_import_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_WP_ALL_IMPORT );
		break;

	case 'extension_scoring_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_SCORING );
		break;

	case 'extension_yith_woocommerce_ajax_search_free_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE );
		break;

	case 'extension_theme_listify_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_THEME_LISTIFY );
		break;

	case 'extension_cron_opt':
		WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::EXTENSION_CRON );
		break;


}

