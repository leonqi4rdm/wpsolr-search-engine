<?php

use wpsolr\core\classes\engines\solarium\WPSOLR_IndexSolariumClient;
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\extensions\managed_solr_servers\OptionManagedSolrServer;
use wpsolr\core\classes\extensions\WpSolrExtensions;
use wpsolr\core\classes\utilities\WPSOLR_Option;

define( 'WPSOLR_DASHBOARD_NONCE_SELECTOR', 'WPSOLR_DASHBOARD_NONCE_SELECTOR' );
define( 'WPSOLR_NONCE_FOR_DASHBOARD', 'wpsolr_nonce_for_dashboard' );

/**
 * Build the admin header
 * @return string
 */
function wpsolr_admin_header() {
	global $license_manager;

	$admin_header = 'If you like WPSOLR, thank you for letting others know with a <a href="https://wordpress.org/support/view/plugin-reviews/wpsolr-search-engine" target="__new">***** review</a>.';
	$admin_header .= ' Else, we\'d like very much your feedbacks throught our <a href="' . $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/' ) . '" target="__new">chat box</a> to improve the plugin.';

	$footer_version = 'You are using the free plugin.';
	if ( defined( 'WPSOLR_PLUGIN_PRO_DIR' ) ) {
		$footer_version = '<a style="color:red" href="?page=solr_settings&tab=solr_plugins&subtab=extension_premium_opt">You did not activate your extensions. Click here to proceed !</a>';
		$licenses       = OptionLicenses::get_activated_licenses_links( null );
		if ( is_array( $licenses ) && ! empty( $licenses ) ) {

			$footer_version = sprintf( '<div class="wpsolr_activated_packs">Extensions active: %s.</div>', implode( ', ', $licenses ) );
		}
	}

	if ( ! defined( 'WPSOLR_PLUGIN_PRO_DIR' ) ) {
		$footer_version .= ' Fancy more features with <a href="' . $license_manager->add_campaign_to_url( 'https://www.wpsolr.com/' ) . '" target="__new">WPSOLR PRO</a> ?';
	}

	$admin_header = sprintf( '%s <span>%s</span>', $footer_version, $admin_header );

	// Add nonce in all admin screens, for all wpsolr admin ajax calls.
	$admin_header .= sprintf(
		'<input type="hidden" id="%s" value="%s" >',
		esc_attr( WPSOLR_DASHBOARD_NONCE_SELECTOR ),
		esc_attr( wp_create_nonce( WPSOLR_NONCE_FOR_DASHBOARD ) )
	);

	return $admin_header;
}

/**
 * Build the admin version
 * @return string
 */
function wpsolr_admin_version() {

	$footer_version = sprintf( '%s %s', WPSOLR_PLUGIN_SHORT_NAME, WPSOLR_PLUGIN_VERSION );

	return $footer_version;
}

/**
 * GEt the class of an extension license tab.
 *
 * @param $license_code
 * @param $entension
 *
 * @return string
 */
function wpsolr_get_extension_tab_class( $license_code, $extension ) {
	$activated_licenses_titles = OptionLicenses::get_activated_licenses_links( $license_code );

	$result = empty( $activated_licenses_titles ) ? 'wpsolr_tab_inactive' : 'wpsolr_tab_active';

	$result .= ! defined( 'WPSOLR_PLUGIN_PRO_DIR' ) && WpSolrExtensions::get_option_is_pro( $extension ) ? ' wpsolr_is_not_available' : ' wpsolr_is_available';

	return $result;
}

const WPSOLR_ADMIN_MENU_FACETS = 'tab=solr_option&subtab=facet_opt';

/**
 * Return menus link html
 *
 * @param string $menu
 * @param string $text
 *
 * @return string
 */
function wpsolr_get_menu_html( $menu, $text, $is_new_target = false ) {
	$wpsolr_menu = 'page=solr_settings';

	return sprintf( '<a href="?%s&%s" target="%s">%s</a>', $wpsolr_menu, $menu, $is_new_target ? '_blank' : '_self', $text );
}


add_filter( 'init', function () {
	global $google_recaptcha_site_key, $google_recaptcha_token, $response_object1;
	/*
	 *  Route to controllers
	 */
	WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_MANAGED_SOLR_SERVERS, true );
	WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );

	switch ( isset( $_POST['wpsolr_action'] ) ? $_POST['wpsolr_action'] : '' ) {
		case 'wpsolr_admin_action_form_temporary_index':
			unset( $response_object );

			if ( isset( $_POST['submit_button_form_temporary_index'] ) ) {
				wpsolr_admin_action_form_temporary_index( $response_object1 );
			}

			if ( isset( $_POST['submit_button_form_temporary_index_select_managed_solr_service_id'] ) ) {

				$form_data = WpSolrExtensions::extract_form_data( true, [
						'managed_solr_service_id' => [ 'default_value' => '', 'can_be_empty' => false ]
					]
				);

				$managed_solr_server = new OptionManagedSolrServer( $form_data['managed_solr_service_id']['value'] );
				$response_object1    = $managed_solr_server->call_rest_create_google_recaptcha_token();

				if ( isset( $response_object1 ) && OptionManagedSolrServer::is_response_ok( $response_object1 ) ) {
					$google_recaptcha_site_key = OptionManagedSolrServer::get_response_result( $response_object1, 'siteKey' );
					$google_recaptcha_token    = OptionManagedSolrServer::get_response_result( $response_object1, 'token' );
				}

			}

			break;

	}
} );

/**
 * @param $response_object
 */
function wpsolr_admin_action_form_temporary_index( &$response_object ) {


	// recaptcha response
	$g_recaptcha_response = isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : '';

	// A recaptcha response must be set
	if ( empty( $g_recaptcha_response ) ) {

		return;
	}

	$form_data = WpSolrExtensions::extract_form_data( true, array(
			'managed_solr_service_id' => array( 'default_value' => '', 'can_be_empty' => false )
		)
	);

	$managed_solr_server = new OptionManagedSolrServer( $form_data['managed_solr_service_id']['value'] );
	$response_object     = $managed_solr_server->call_rest_create_solr_index( $g_recaptcha_response );

	if ( isset( $response_object ) && OptionManagedSolrServer::is_response_ok( $response_object ) ) {

		$option_indexes_object = new WPSOLR_Option_Indexes();

		$index_uuid = $option_indexes_object->create_managed_index(
			$managed_solr_server->get_search_engine(),
			$managed_solr_server->get_id(),
			WPSOLR_Option_Indexes::STORED_INDEX_TYPE_MANAGED_TEMPORARY,
			OptionManagedSolrServer::get_response_result( $response_object, 'urlCore' ),
			'Test index from ' . $managed_solr_server->get_label(),
			OptionManagedSolrServer::get_response_result( $response_object, 'urlScheme' ),
			OptionManagedSolrServer::get_response_result( $response_object, 'urlDomain' ),
			OptionManagedSolrServer::get_response_result( $response_object, 'urlPort' ),
			OptionManagedSolrServer::get_response_result( $response_object, 'urlPath' ),
			OptionManagedSolrServer::get_response_result( $response_object, 'key' ),
			OptionManagedSolrServer::get_response_result( $response_object, 'secret' )
		);

		if ( count( $option_indexes_object->get_indexes() ) === 1 ) {
			// Redirect automatically to Solr options if it is the first solr index created

			$redirect_location = '?page=solr_settings&tab=solr_option';
			header( "Location: $redirect_location", true, 302 ); // wp_redirect() is not found
			exit;
		} else {
			// Redirect to the index defineition tab
			$redirect_location = sprintf( '?page=solr_settings&tab=solr_indexes&subtab=%s', $index_uuid );
			header( "Location: $redirect_location", true, 302 ); // wp_redirect() is not found
		}
	}

}

function wpsolr_admin_init() {

	WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
	register_setting( WPSOLR_Option_Indexes::get_option_name( WpSolrExtensions::OPTION_INDEXES ), WPSOLR_Option_Indexes::get_option_name( WpSolrExtensions::OPTION_INDEXES ) );

	WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_LICENSES, true );
	register_setting( WPSOLR_Option_Indexes::get_option_name( WpSolrExtensions::OPTION_LICENSES ), OptionLicenses::get_option_name( WpSolrExtensions::OPTION_LICENSES ) );

	register_setting( 'solr_form_options', WPSOLR_Option::OPTION_INDEX );
	register_setting( 'solr_res_options', WPSOLR_Option::OPTION_SEARCH );
	register_setting( 'solr_facet_options', WPSOLR_Option::OPTION_FACET );
	register_setting( 'solr_search_field_options', WPSOLR_Option::OPTION_SEARCH_FIELDS );
	register_setting( 'solr_sort_options', WPSOLR_Option::OPTION_SORTBY );
	register_setting( 'solr_localization_options', 'wdm_solr_localization_data' );
	register_setting( 'solr_extension_groups_options', WPSOLR_Option::OPTION_EXTENSION_GROUPS );
	register_setting( 'solr_extension_s2member_options', WPSOLR_Option::OPTION_EXTENSION_S2MEMBER );
	register_setting( 'solr_extension_wpml_options', WPSOLR_Option::OPTION_EXTENSION_WPML );
	register_setting( 'solr_extension_polylang_options', WPSOLR_Option::OPTION_EXTENSION_POLYLANG );
	register_setting( 'solr_extension_qtranslatex_options', 'wdm_solr_extension_qtranslatex_data' );
	register_setting( 'solr_operations_options', WPSOLR_Option::OPTION_OPERATIONS );
	register_setting( 'solr_extension_woocommerce_options', WPSOLR_Option::OPTION_EXTENSION_WOOCOMMERCE );
	register_setting( 'solr_extension_acf_options', WPSOLR_Option::OPTION_EXTENSION_ACF );
	register_setting( 'solr_extension_types_options', WPSOLR_Option::OPTION_EXTENSION_TYPES );
	register_setting( 'solr_extension_bbpress_options', WPSOLR_Option::OPTION_EXTENSION_BBPRESS );
	register_setting( 'extension_embed_any_document_opt', WPSOLR_Option::OPTION_EXTENSION_EMBED_ANY_DOCUMENT );
	register_setting( 'extension_pdf_embedder_opt', WPSOLR_Option::OPTION_EXTENSION_PDF_EMBEDDER );
	register_setting( 'extension_google_doc_embedder_opt', WPSOLR_Option::OPTION_EXTENSION_GOOGLE_DOC_EMBEDDER );
	register_setting( 'extension_tablepress_opt', WPSOLR_Option::OPTION_EXTENSION_TABLEPRESS );
	register_setting( 'extension_geolocation_opt', WPSOLR_Option::OPTION_EXTENSION_GEOLOCATION );
	register_setting( 'extension_premium_opt', WPSOLR_Option::OPTION_PREMIUM );
	register_setting( 'extension_theme_opt', WPSOLR_Option::OPTION_THEME );
	register_setting( 'extension_yoast_seo_opt', WPSOLR_Option::OPTION_YOAST_SEO );
	register_setting( 'extension_all_in_one_seo_opt', WPSOLR_Option::OPTION_ALL_IN_ONE_SEO_PACK );
	register_setting( 'extension_wp_all_import_opt', WPSOLR_Option::OPTION_WP_ALL_IMPORT );
	register_setting( 'extension_import_export_opt', WPSOLR_Option::OPTION_IMPORT_EXPORT );
	register_setting( 'extension_scoring_opt', WPSOLR_Option::OPTION_SCORING );
	register_setting( 'extension_yith_woocommerce_ajax_search_free_opt', WPSOLR_Option::OPTION_EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE );
	register_setting( 'extension_theme_listify_opt', WPSOLR_Option::OPTION_THEME_LISTIFY );
	register_setting( 'extension_cron_opt', WPSOLR_Option::OPTION_CRON );
}

function fun_add_solr_settings() {
	$img_url = plugins_url( '../images/WPSOLRDashicon.png', __FILE__ );
	add_menu_page( WPSOLR_PLUGIN_SHORT_NAME, WPSOLR_PLUGIN_SHORT_NAME, 'manage_options', 'solr_settings', 'fun_set_solr_options', $img_url );
	wp_enqueue_style( 'dashboard_style', plugins_url( '../css/dashboard_css.css', __FILE__ ), [], WPSOLR_PLUGIN_VERSION );
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_script( 'dashboard_js1', plugins_url( '../js/dashboard.js', __FILE__ ),
		[
			'jquery',
			'jquery-ui-sortable',
		],
		WPSOLR_PLUGIN_VERSION
	);

	wp_localize_script( 'dashboard_js1', 'wpsolr_localize_script_dashboard',
		[
			'ajax_url'                        => admin_url( 'admin-ajax.php' ),
			'wpsolr_dashboard_nonce_selector' => ( '#' . WPSOLR_DASHBOARD_NONCE_SELECTOR ),
		]
	);

	$plugin_vals = [ 'plugin_url' => plugins_url( '../images/', __FILE__ ) ];
	wp_localize_script( 'dashboard_js1', 'plugin_data', $plugin_vals );

	// Google api recaptcha - Used for temporary indexes creation
	wp_enqueue_script( 'google-api-recaptcha', '//www.google.com/recaptcha/api.js', [], WPSOLR_PLUGIN_VERSION );

	/**
	 * Color picker for facets
	 */
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'iris', admin_url( '../js/iris.min.js' ), [
		'jquery-ui-draggable',
		'jquery-ui-slider',
		'jquery-touch-punch'
	], false, 1 );
	wp_enqueue_script( 'wp-color-picker', admin_url( '../js/color-picker.min.js' ), [ 'iris' ], false, 1 );
	$colorpicker_l10n = [
		'clear'         => __( 'Clear' ),
		'defaultString' => __( 'Default' ),
		'pick'          => __( 'Select Color' )
	];
	wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', $colorpicker_l10n );

	// Bootstrap tour
	/*
	wp_enqueue_style( 'bootstrap_tour_css', plugins_url( 'css/bootstrap-tour-standalone.css', __FILE__ ), array(), 'v0.10.3' );
	wp_enqueue_script( 'bootstrap_tour_js', plugins_url( 'js/bootstrap-tour-standalone.js', __FILE__ ), array( 'jquery' ), 'v0.10.3' );
	*/
}

function fun_set_solr_options() {
	global $license_manager;

	// Include license activation popup boxes in all admin tabs
	add_thickbox();
	if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		// Do not load in Ajax
		require_once __DIR__ . '/../classes/extensions/licenses/admin_options.inc.php';
	}

	// Button Index
	if ( isset( $_POST['solr_index_data'] ) ) {

		$solr = WPSOLR_IndexSolariumClient::create();

		try {
			$res = $solr->get_solr_status();

			$val = $solr->index_data( false, 'default', null );

			if ( count( $val ) == 1 || $val == 1 ) {
				echo "<script type='text/javascript'>
                jQuery(document).ready(function(){
                jQuery('.status_index_message').removeClass('loading');
                jQuery('.status_index_message').addClass('wpsolr_success');
                });
            </script>";
			} else {
				echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
                jQuery('.status_index_message').removeClass('loading');
                jQuery('.status_index_message').addClass('wpsolr_warning');
                });
            </script>";
			}

		} catch ( Exception $e ) {

			$errorMessage = $e->getMessage();

			echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_index_message').removeClass('loading');
               jQuery('.status_index_message').addClass('wpsolr_warning');
               jQuery('.wdm_note').html('<b>Error: <p>{$errorMessage}</p></b>');
            });
            </script>";

		}

	}

	// Button delete
	if ( isset( $_POST['solr_delete_index'] ) ) {
		$solr = WPSOLR_IndexSolariumClient::create();

		try {
			$res = $solr->get_solr_status();

			$val = $solr->delete_documents();

			if ( $val == 0 ) {
				echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_del_message').removeClass('wpsolr_loading');
               jQuery('.status_del_message').addClass('wpsolr_success');
            });
            </script>";
			} else {
				echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_del_message').removeClass('wpsolr_loading');
                              jQuery('.status_del_message').addClass('wpsolr_warning');
            });
            </script>";
			}

		} catch ( Exception $e ) {

			$errorMessage = $e->getMessage();

			echo "<script type='text/javascript'>
            jQuery(document).ready(function(){
               jQuery('.status_del_message').removeClass('wpsolr_loading');
               jQuery('.status_del_message').addClass('wpsolr_warning');
               jQuery('.wdm_note').html('<b>Error: <p>{$errorMessage}</p></b>');
            })
            </script>";
		}
	}


	?>
    <div class="wdm-wrap">
        <div class="wpsolr-page-header">
            <div class="wpsolr-page-title">
				<?php echo wpsolr_admin_header(); ?>
            </div>
            <div class="wpsolr-page-version">
				<?php echo wpsolr_admin_version(); ?>
            </div>
        </div>

		<?php
		if ( isset ( $_GET['tab'] ) ) {
			wpsolr_admin_tabs( $_GET['tab'] );
		} else {
			wpsolr_admin_tabs( 'solr_presentation' );
		}

		if ( isset ( $_GET['tab'] ) ) {
			$tab = $_GET['tab'];
		} else {
			$tab = 'solr_presentation';
		}

		switch ( $tab ) {
			case 'solr_presentation' :
				include( 'dashboard_presentation.inc.php' );
				break;

			case 'solr_indexes' :
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::OPTION_INDEXES );
				break;

			case 'solr_option':
				include( 'dashboard_settings.inc.php' );
				break;

			case 'solr_themes':
				include( 'dashboard_themes.inc.php' );
				break;

			case 'solr_plugins':
				include( 'dashboard_plugins.inc.php' );
				break;

			case 'solr_operations':
				include( 'dashboard_operations.inc.php' );
				break;

			case 'wpsolr_licenses' :
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::OPTION_LICENSES );
				break;

			case 'solr_import_export':
				WpSolrExtensions::require_once_wpsolr_extension_admin_options( WpSolrExtensions::OPTION_IMPORT_EXPORT );
				break;

			case 'solr_feedback':
				include( 'dashboard_feedbacks.inc.php' );
				break;
		}

		?>

    </div>
	<?php


}

function wpsolr_admin_tabs( $current = 'solr_indexes' ) {

	// Get default search solr index indice
	WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
	$option_indexes            = new WPSOLR_Option_Indexes();
	$default_search_solr_index = $option_indexes->get_default_search_solr_index();

	$nb_indexes        = count( $option_indexes->get_indexes() );
	$are_there_indexes = ( $nb_indexes >= 0 );

	$tabs                      = [];
	$tabs['solr_presentation'] = 'What is WPSOLR ?';
	$tabs['solr_indexes']      = $are_there_indexes ? '0. Connect your indexes' : '0. Connect your index';

	if ( defined( 'WPSOLR_PLUGIN_PRO_DIR' ) ) {
		$tabs['solr_plugins']       = '1. Activate extensions';
		$tabs['solr_themes']        = '1. Activate themes';
		$tabs['solr_option']        = sprintf( "2. Define your search with '%s'",
			! isset( $default_search_solr_index )
				? $are_there_indexes ? "<span class='text_error'>No index selected</span>" : ''
				: $option_indexes->get_index_name( $default_search_solr_index ) );
		$tabs['solr_operations']    = '3. Send your data';
		$tabs['solr_import_export'] = '4. Import / Export settings';
	} else {
		$tabs['solr_option']     = sprintf( "1. Define your search with '%s'",
			! isset( $default_search_solr_index )
				? $are_there_indexes ? "<span class='text_error'>No index selected</span>" : ''
				: $option_indexes->get_index_name( $default_search_solr_index ) );
		$tabs['solr_operations'] = '2. Send your data';
		$tabs['solr_plugins']    = '3. Activate extensions';
		$tabs['solr_themes']     = '3a. Activate themes';
	}

	$tabs['solr_feedback'] = 'Feedback';

	echo '<div id="icon-themes" class="icon32"><br></div>';
	echo '<h2 class="nav-tab-wrapper wpsolr-tour-navigation-tabs">';
	foreach ( $tabs as $tab => $name ) {
		$class = ( $tab == $current ) ? ' nav-tab-active' : '';
		echo "<a class='nav-tab$class' href='admin.php?page=solr_settings&tab=$tab'>$name</a>";

	}
	echo '</h2>';
}


function wpsolr_admin_sub_tabs( $subtabs, $before = null ) {

	// Tab selected by the user
	$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'solr_presentation';

	if ( isset ( $_GET['subtab'] ) ) {

		$current_subtab = $_GET['subtab'];

	} else {
		// No user selection: use the first subtab in the list
		$current_subtab = key( $subtabs );
	}

	echo '<div id="icon-themes" class="icon32"><br></div>';
	echo '<h2 class="nav-tab-wrapper wdm-vertical-tabs">';

	if ( isset( $before ) ) {
		echo "$before<div style='clear: both;margin-bottom: 10px;'></div>";
	}

	foreach ( $subtabs as $subtab_indice => $subtab ) {
		if ( is_array( $subtab ) ) {
			$name        = $subtab['name'];
			$extra_class = $subtab['class'];
		} else {
			$extra_class = '';
			$name        = $subtab;
		}
		$class = ( $subtab_indice == $current_subtab ) ? ' nav-tab-active' : '';

		if ( false === strpos( $name, 'wpsolr_premium_class' ) ) {
			echo "<a class='nav-tab$class $extra_class' href='admin.php?page=solr_settings&tab=$tab&subtab=$subtab_indice'>$name</a>";
		} else {
			echo $name;
		}

	}

	echo '</h2>';

	return $current_subtab;
}
