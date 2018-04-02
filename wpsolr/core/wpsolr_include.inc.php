<?php

use wpsolr\core\classes\engines\solarium\WPSOLR_IndexSolariumClient;
use wpsolr\core\classes\engines\solarium\WPSOLR_SearchSolariumClient;
use wpsolr\core\classes\engines\WPSOLR_AbstractIndexClient;
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\extensions\localization\OptionLocalization;
use wpsolr\core\classes\extensions\WpSolrExtensions;
use wpsolr\core\classes\metabox\WPSOLR_Metabox;
use wpsolr\core\classes\models\WPSOLR_Model_Post_Type;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\ui\widget\WPSOLR_Widget;
use wpsolr\core\classes\ui\WPSOLR_Query_Parameters;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;

// Definitions
define( 'WPSOLR_PLUGIN_VERSION', '19.9' );
define( 'WPSOLR_PLUGIN_FILE', __FILE__ );
define( 'WPSOLR_PLUGIN_BASE_NAME', plugin_basename( __FILE__ ) );
define( 'WPSOLR_DEFINE_PLUGIN_DIR_URL', substr_replace( plugin_dir_url( __FILE__ ), '', - 1 ), false );
define( 'WPSOLR_PLUGIN_ANY_DIR', defined( 'WPSOLR_PLUGIN_PRO_DIR' ) ? WPSOLR_PLUGIN_PRO_DIR : WPSOLR_PLUGIN_DIR );

// Constants
const WPSOLR_AJAX_AUTO_COMPLETE_ACTION    = 'wdm_return_solr_rows';
const WPSOLR_AUTO_COMPLETE_NONCE_SELECTOR = 'wpsolr_autocomplete_nonce';

// WPSOLR autoloader
require_once( WPSOLR_PLUGIN_ANY_DIR . '/wpsolr/core/class-wpsolr-autoloader.php' );

// WPSOLR Filters (compatibility)
require_once( WPSOLR_PLUGIN_ANY_DIR . '/wpsolr/core/classes/class-wpsolrfilters-old.php' );

global $license_manager;
$license_manager                          = new OptionLicenses();

// Composer autoloader
require_once( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' );

require_once 'ajax_solr_services.php';
require_once 'dashboard/dashboard.php';
require_once 'autocomplete.php';

/* Register Solr settings from dashboard
 * Add menu page in dashboard - Solr settings
 * Add solr settings- solr host, post and path
 *
 */
add_action( 'wp_head', 'check_default_options_and_function' );
add_action( 'admin_menu', 'fun_add_solr_settings' );
add_action( 'admin_init', 'wpsolr_admin_init' );
add_action( 'wp_enqueue_scripts', 'wpsolr_enqueue_script' );

// Register WpSolr widgets when current theme's search is used.
if ( WPSOLR_Service_Container::getOption()->get_search_is_use_current_theme_search_template() ) {
	WPSOLR_Widget::Autoload();
}

if ( is_admin() ) {

	/*
	 * Register metabox
	 */
	WPSOLR_Metabox::register();
}

/*
 * Display Solr errors in admin when a save on a post can't index to Solr
 */
function solr_post_save_admin_notice() {
	if ( $out = get_transient( get_current_user_id() . 'error_solr_post_save_admin_notice' ) ) {
		delete_transient( get_current_user_id() . 'error_solr_post_save_admin_notice' );
		echo "<div class=\"error wpsolr_admin_notice_error\"><p>(WPSOLR) Error while indexing this post type:<br><br>$out</p></div>";
	}

	if ( $out = get_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice' ) ) {
		delete_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice' );
		echo "<div class=\"updated wpsolr_admin_notice_updated\"><p>(WPSOLR) $out</p></div>";
	}

	if ( $out = get_transient( get_current_user_id() . 'wpsolr_some_languages_have_no_solr_index_admin_notice' ) ) {
		delete_transient( get_current_user_id() . 'wpsolr_some_languages_have_no_solr_index_admin_notice' );
		echo "<div class=\"error wpsolr_admin_notice_error\"><p>(WPSOLR) $out</p></div>";
	}

	if ( $out = get_transient( get_current_user_id() . 'wpsolr_error_during_search' ) ) {
		delete_transient( get_current_user_id() . 'wpsolr_error_during_search' );
		echo "<div class=\"error wpsolr_admin_notice_error\"><p>(WPSOLR) Error while searching. WPSOLR search is not used, standard Wordpress search results are displayed instead.<br><br>$out</p></div>";
	}
}

add_action( 'admin_notices', "solr_post_save_admin_notice" );

if ( WPSOLR_Service_Container::getOption()->get_index_is_real_time() ) {
	// Index as soon as a save is performed.
	add_action( 'save_post', 'add_remove_document_to_solr_index', 11, 3 );
	add_action( 'add_attachment', 'add_attachment_to_solr_index', 10, 3 );
	add_action( 'edit_attachment', 'add_attachment_to_solr_index', 10, 3 );
	add_action( 'delete_attachment', 'delete_attachment_to_solr_index', 10, 3 );


	if ( WPSOLR_Service_Container::getOption()->get_index_are_comments_indexed() ) {
		// new comment
		add_action( 'comment_post', 'add_remove_comment_to_solr_index', 11, 1 );

		// approved, unaproved, trashed, untrashed, spammed, unspammed
		add_action( 'wp_set_comment_status', 'add_remove_comment_to_solr_index', 11, 1 );
	}
}

/*
 * Wp-cron call to index Solr documents, 200 per call
 */
function cron_solr_index_data() {

	$option_indexes_object = new WPSOLR_Option_Indexes();
	$indexes = [];
	foreach ( $option_indexes_object->get_indexes() as $index_indice => $index ) {
		$indexes[ $index_indice ] = isset( $index['index_name'] ) ? $index['index_name'] : 'Index with no name';
	}
	$current_index = key( $indexes );

	try {

		set_error_handler( 'wpsolr_my_error_handler' );
		register_shutdown_function( 'wpsolr_fatal_error_shutdown_handler' );

		// Indice of Solr index to index
		$solr_index_indice = $current_index;

		// Batch size
		$batch_size = 200;

		// nb of document sent until now
		$nb_results = 0;

		// Debug infos displayed on screen ?
		$is_debug_indexing = false;

		// Re-index all the data ?
		$is_reindexing_all_posts = false;


		// Stop indexing ?
		$is_stopping = false;

		$solr = WPSOLR_IndexSolariumClient::create( $solr_index_indice );

		$process_id = 'wpsolr-cron';


		$res_final = $solr->index_data( $is_stopping, $process_id, null, $batch_size, null, $is_debug_indexing );

		// Increment nb of document sent until now
		$res_final['nb_results'] += $nb_results;

		echo wp_json_encode( $res_final );

		$models = $solr->get_models();

		$solr->unlock_models($process_id, $models);

	} catch ( Exception $e ) {

		echo wp_json_encode(
			[
				'nb_results'        => 0,
				'status'            => $e->getCode(),
				'message'           => htmlentities( $e->getMessage() ),
				'indexing_complete' => false,
			]
		);

	}

}

/*
 * Solr Wp-cron recurrence set up, every 5 minutes or 15 minutes
 */
function wpsolr_add_cron_recurrence_interval( $schedules ) {
 
    $schedules['every_three_minutes'] = array(
            'interval'  => 300,
            'display'   => __( 'Every 5 Minutes', 'rdm-solr' )
    );
 
    $schedules['every_fifteen_minutes'] = array(
            'interval'  => 900,
            'display'   => __( 'Every 15 Minutes', 'rdm-solr' )
    );  
     
    return $schedules;
}
add_filter( 'cron_schedules', 'wpsolr_add_cron_recurrence_interval' );


/**
 * Reindex a post when one of it's comment is updated.
 *
 * @param $comment_id
 */
function add_remove_comment_to_solr_index( $comment_id ) {

	$comment = get_comment( $comment_id );

	if ( ! empty( $comment ) ) {

		add_remove_document_to_solr_index( $comment->comment_post_ID, get_post( $comment->comment_post_ID ) );
	}
}

/**
 * Add/remove document to/from Solr index when status changes to/from published
 * We have to use action 'save_post', as it is used by other plugins to trigger meta boxes save
 *
 * @param $post_id
 * @param $post
 */
function add_remove_document_to_solr_index( $post_id, $post ) {

	// If this is just a revision, don't go on.
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	// If this is just a new post opened in editor, don't go on.
	if ( 'auto-draft' === $post->post_status ) {
		return;
	}

	// If this post type is not indexable in setup, don't go on.
	if ( ! WPSOLR_Model_Post_Type::is_post_type_can_be_indexed( $post->post_type ) ) {
		return;
	}

	// Delete previous message first
	delete_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice' );

	try {
		$index_post_statuses = apply_filters( WPSOLR_Events::WPSOLR_FILTER_POST_STATUSES_TO_INDEX, array( 'publish' ), $post );

		if ( in_array( $post->post_status, $index_post_statuses, true ) && ! WPSOLR_Metabox::get_metabox_is_do_not_index( $post->ID ) ) {
			// post published, add/update it from Solr index

			$solr = WPSOLR_AbstractIndexClient::create_from_post( $post );

			$results = $solr->index_data( false, 'default', null, 1, $post );

			// Display confirmation in admin, if one doc at least has been indexed
			if ( ! empty( $results ) && ! empty( $results['nb_results'] ) ) {

				set_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice', sprintf( '%s updated in index \'%s\'', ucfirst( $post->post_type ), $solr->index['index_name'] ) );
			}

		} else {

			// post unpublished, or modified with 'do not index', remove it from Solr index
			$solr = WPSOLR_AbstractIndexClient::create_from_post( $post );

			$solr->delete_document( $post );

			// Display confirmation in admin
			set_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice', sprintf( '%s removed from index \'%s\'', ucfirst( $post->post_type ), $solr->index['index_name'] ) );
		}

	} catch ( Exception $e ) {
		set_transient( get_current_user_id() . 'error_solr_post_save_admin_notice', htmlentities( $e->getMessage() ) );
	}

}

/*
 * Add an attachment to Solr
 */
function add_attachment_to_solr_index( $attachment_id ) {

	// Index the new attachment
	try {
		$solr = WPSOLR_AbstractIndexClient::create();

		$results = $solr->index_data( false, 'default', null, 1, get_post( $attachment_id ) );

		// Display confirmation in admin, if one doc at least has been indexed
		if ( ! empty( $results ) && ! empty( $results['nb_results'] ) ) {

			set_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice', sprintf( 'Media file uploaded to index "%s".', $solr->index['index_name'] ) );
		}

	} catch ( Exception $e ) {

		set_transient( get_current_user_id() . 'error_solr_post_save_admin_notice', htmlentities( $e->getMessage() ) );
	}

}

/*
 * Delete an attachment from Solr
 */
function delete_attachment_to_solr_index( $attachment_id ) {

	// Remove the attachment from Solr index
	try {
		$solr = WPSOLR_AbstractIndexClient::create();

		$solr->delete_document( get_post( $attachment_id ) );

		set_transient( get_current_user_id() . 'updated_solr_post_save_admin_notice', 'Attachment deleted from Solr' );

	} catch ( Exception $e ) {

		set_transient( get_current_user_id() . 'error_solr_post_save_admin_notice', htmlentities( $e->getMessage() ) );
	}

}


/* Replace WordPress search
 * Default WordPress will be replaced with Solr search
 */


function check_default_options_and_function() {

	if ( WPSOLR_Service_Container::getOption()->get_search_is_replace_default_wp_search() && ! WPSOLR_Service_Container::getOption()->get_search_is_use_current_theme_search_template() ) {

		add_filter( 'get_search_form', 'solr_search_form' );

	}
}

add_filter( 'template_include', 'wpsolr_ajax_template_include', 99 );
function wpsolr_ajax_template_include( $template ) {

	if ( is_page( WPSOLR_Service_Container::getOption()->get_search_ajax_search_page_slug() ) ) {
		$new_template = locate_template( WPSOLR_SearchSolariumClient::_SEARCH_PAGE_TEMPLATE );
		if ( '' != $new_template ) {
			return $new_template;
		}
	}

	return $template;
}

/* Create default page template for search results
*/
add_shortcode( 'solr_search_shortcode', 'fun_search_indexed_data' );
add_shortcode( 'solr_form', 'fun_dis_search' );
function fun_dis_search() {
	echo solr_search_form();
}


register_activation_hook( __FILE__, 'my_register_activation_hook' );
function my_register_activation_hook() {

	/*
	 * Migrate old data on plugin update
	 */
	WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );
	$option_object = new WPSOLR_Option_Indexes();
	$option_object->migrate_data_from_v4_9();
}


add_action( 'admin_notices', 'curl_dependency_check' );
function curl_dependency_check() {
	if ( ! in_array( 'curl', get_loaded_extensions() ) ) {

		echo "<div class='updated'><p><b>cURL</b> is not installed on your server. In order to make <b>'Solr for WordPress'</b> plugin work, you need to install <b>cURL</b> on your server </p></div>";
	}


}


function solr_search_form() {

	ob_start();

	// Load current theme's wpsolr search form if it exists
	$search_form_template = locate_template( 'wpsolr-search-engine/searchform.php' );
	if ( '' !== $search_form_template ) {

		require( $search_form_template );
		$form = ob_get_clean();

	} else {

		$ad_url = admin_url();

		if ( isset( $_GET[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_Q ] ) ) {
			$search_que = $_GET[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_Q ];
		} else if ( isset( $_GET[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_SEARCH ] ) ) {
			$search_que = $_GET[ WPSOLR_Query_Parameters::SEARCH_PARAMETER_SEARCH ];
		} else {
			$search_que = '';
		}

		// Get localization options
		$localization_options = OptionLocalization::get_options();

		$wdm_typehead_request_handler = WPSOLR_AJAX_AUTO_COMPLETE_ACTION;

		$get_page_info = WPSOLR_SearchSolariumClient::get_search_page();
		$ajax_nonce    = wp_create_nonce( "nonce_for_autocomplete" );


		$url = get_permalink( $get_page_info->ID );
		// Filter the search page url. Used for multi-language search forms.
		$url = apply_filters( WPSOLR_Events::WPSOLR_FILTER_SEARCH_PAGE_URL, $url, $get_page_info->ID );

		$form = "<div class='cls_search' style='width:100%'><form action='$url' method='get'  class='search-frm2' >";
		$form .= '<input type="hidden" value="' . $wdm_typehead_request_handler . '" id="path_to_fold">';
		$form .= '<input type="hidden"  id="ajax_nonce" value="' . $ajax_nonce . '">';

		$form .= '<input type="hidden" value="' . $ad_url . '" id="path_to_admin">';
		$form .= '<input type="hidden" value="' . WPSOLR_Service_Container::get_query()->get_wpsolr_query( '', true ) . '" id="search_opt">';

		parse_str( parse_url( $url, PHP_URL_QUERY ), $url_params );
		if ( ! empty( $url_params ) && isset( $url_params['lang'] ) ) {
			$form .= '<input type="hidden" value="' . esc_attr( $url_params['lang'] ) . '" name="lang">';
		}

		$form .= '
       <div class="ui-widget search-box">
 	<input type="hidden"  id="ajax_nonce" value="' . $ajax_nonce . '">
        <input type="text" placeholder="' . OptionLocalization::get_term( $localization_options, 'search_form_edit_placeholder' ) . '" value="' . WPSOLR_Service_Container::get_query()->get_wpsolr_query( '', true ) . '" name="' . WPSOLR_Query_Parameters::SEARCH_PARAMETER_Q . '" id="search_que" class="' . WPSOLR_Option::OPTION_SEARCH_SUGGEST_CLASS_DEFAULT . ' sfl1" autocomplete="off"/>
	<input type="submit" value="' . OptionLocalization::get_term( $localization_options, 'search_form_button_label' ) . '" id="searchsubmit" style="position:relative;width:auto">
		         <input type="hidden" value="' . WPSOLR_Service_Container::getOption()->get_search_after_autocomplete_block_submit() . '" id="is_after_autocomplete_block_submit">'
		         . apply_filters( WPSOLR_Events::WPSOLR_FILTER_APPEND_FIELDS_TO_AJAX_SEARCH_FORM, '' )
		         . '<div style="clear:both"></div></div></form></div>';

	}

	return $form;
}

add_action( 'after_setup_theme', 'wpsolr_after_setup_theme' ); // Some plugins are loaded with the theme, like ACF. We need to wait till then.
function wpsolr_after_setup_theme() {

	// Load active extensions
	WpSolrExtensions::load();

	/*
	 * Load WPSOLR text domain to the Wordpress languages plugin directory (WP_LANG_DIR/plugins)
	 * Copy your .mo files there
	 * Example: /htdocs/wp-includes/languages/plugins/wpsolr-fr_FR.mo or /htdocs/wp-content/languages/plugins/wpsolr-fr_FR.mo
	 * You can find our .pot files in this plugin's /wpsolr-pro/wpsolr/core/languages/wpsolr.pot file
	 */
	load_plugin_textdomain( 'wpsolr', false, false );
}

function wpsolr_enqueue_script() {

	if ( ! WPSOLR_Service_Container::getOption()->get_search_is_prevent_loading_front_end_css() ) {
		wp_enqueue_style( 'solr_auto_css', plugins_url( 'css/bootstrap.min.css', __FILE__ ), array(), WPSOLR_PLUGIN_VERSION );
		wp_enqueue_style( 'solr_frontend', plugins_url( 'css/style.css', __FILE__ ), array(), WPSOLR_PLUGIN_VERSION );
	}

	if ( ! WPSOLR_Service_Container::getOption()->get_search_is_galaxy_slave() ) {
		// In this mode, suggestions do not work, as suggestions cannot be filtered by site.
		wp_enqueue_script( 'solr_auto_js1', plugins_url( 'js/bootstrap-typeahead.js', __FILE__ ), array( 'jquery' ), WPSOLR_PLUGIN_VERSION, true );
	}

	// Url utilities to manipulate the url parameters
	wp_enqueue_script( 'urljs', plugins_url( 'bower_components/jsurl/url.min.js', __FILE__ ), array( 'jquery' ), WPSOLR_PLUGIN_VERSION, true );
	wp_enqueue_script( 'autocomplete', plugins_url( 'js/autocomplete_solr.js', __FILE__ ), array(
		'solr_auto_js1',
		'urljs',
	), WPSOLR_PLUGIN_VERSION, true );

	wp_enqueue_script( 'loadingoverlay', plugins_url( 'js/loadingoverlay/loadingoverlay.min.js', __FILE__ ), array(
		'solr_auto_js1',
		'urljs',
	), WPSOLR_PLUGIN_VERSION, true );

	$is_ajax                   = WPSOLR_Service_Container::getOption()->get_search_is_use_current_theme_with_ajax();
	$container_page_title      = WPSOLR_Service_Container::getOption()->get_option_theme_ajax_page_title_jquery_selectors();
	$container_page_sort       = WPSOLR_Service_Container::getOption()->get_option_theme_ajax_sort_jquery_selectors();
	$container_results         = WPSOLR_Service_Container::getOption()->get_option_theme_ajax_results_jquery_selectors();
	$container_pagination      = WPSOLR_Service_Container::getOption()->get_option_theme_ajax_pagination_jquery_selectors();
	$container_pagination_page = WPSOLR_Service_Container::getOption()->get_option_theme_ajax_pagination_page_jquery_selectors();
	$container_results_count   = WPSOLR_Service_Container::getOption()->get_option_theme_ajax_results_count_jquery_selectors();
	wp_localize_script( 'autocomplete', 'wp_localize_script_autocomplete',
		apply_filters( WPSOLR_Events::WPSOLR_FILTER_JAVASCRIPT_FRONT_LOCALIZED_PARAMETERS,
			[
				'data' =>
					[
						'ajax_url'                           => admin_url( 'admin-ajax.php' ),
						'is_show_url_parameters'             => WPSOLR_Service_Container::getOption()->get_search_is_show_url_parameters(),
						'is_ajax'                            => (bool) $is_ajax,
						'SEARCH_PARAMETER_S'                 => WPSOLR_Query_Parameters::SEARCH_PARAMETER_S,
						'SEARCH_PARAMETER_SEARCH'            => WPSOLR_Query_Parameters::SEARCH_PARAMETER_SEARCH,
						'SEARCH_PARAMETER_Q'                 => WPSOLR_Query_Parameters::SEARCH_PARAMETER_Q,
						'SEARCH_PARAMETER_FQ'                => WPSOLR_Query_Parameters::SEARCH_PARAMETER_FQ,
						'SEARCH_PARAMETER_SORT'              => WPSOLR_Query_Parameters::SEARCH_PARAMETER_SORT,
						'SEARCH_PARAMETER_PAGE'              => WPSOLR_Query_Parameters::SEARCH_PARAMETER_PAGE,
						'SORT_CODE_BY_RELEVANCY_DESC'        => WPSOLR_SearchSolariumClient::SORT_CODE_BY_RELEVANCY_DESC,
						'wpsolr_autocomplete_selector'       => WPSOLR_Service_Container::getOption()->get_search_suggest_jquery_selector(),
						'wpsolr_autocomplete_action'         => WPSOLR_AJAX_AUTO_COMPLETE_ACTION,
						'wpsolr_autocomplete_nonce_selector' => ( '#' . WPSOLR_AUTO_COMPLETE_NONCE_SELECTOR ),
						'css_ajax_container_page_title'      => sprintf( '%s%s%s', WPSOLR_Option::OPTION_THEME_AJAX_PAGE_TITLE_JQUERY_SELECTOR_DEFAULT, empty( $container_page_title ) ? '' : ',', $container_page_title ),
						'css_ajax_container_page_sort'       => sprintf( '%s%s%s', WPSOLR_Option::OPTION_THEME_AJAX_SORT_JQUERY_SELECTOR_DEFAULT, empty( $container_page_sort ) ? '' : ',', $container_page_sort ),
						'css_ajax_container_results'         => sprintf( '%s%s%s', WPSOLR_Option::OPTION_THEME_AJAX_RESULTS_JQUERY_SELECTOR_DEFAULT, empty( $container_results ) ? '' : ',', $container_results ),
						'css_ajax_container_pagination'      => sprintf( '%s%s%s', WPSOLR_Option::OPTION_THEME_AJAX_PAGINATION_JQUERY_SELECTOR_DEFAULT, empty( $container_pagination ) ? '' : ',', $container_pagination ),
						'css_ajax_container_pagination_page' => sprintf( '%s%s%s', WPSOLR_Option::OPTION_THEME_AJAX_PAGINATION_PAGE_JQUERY_SELECTOR_DEFAULT, empty( $container_pagination_page ) ? '' : ',', $container_pagination_page ),
						'css_ajax_container_results_count'   => sprintf( '%s%s%s', WPSOLR_Option::OPTION_THEME_AJAX_RESULTS_COUNT_JQUERY_SELECTOR_DEFAULT, empty( $container_results_count ) ? '' : ',', $container_results_count ),
						'ajax_delay_ms'                      => WPSOLR_Service_Container::getOption()->get_option_theme_ajax_delay_ms(),
						'redirect_search_home'               => apply_filters( WPSOLR_Events::WPSOLR_FILTER_REDIRECT_SEARCH_HOME, '' ),
					],
			]
		),
		WPSOLR_PLUGIN_VERSION
	);

	/*
	 * Infinite scroll: load javascript if option is set.
	 */
	if ( WPSOLR_Service_Container::getOption()->get_search_is_infinitescroll() && ! WPSOLR_Service_Container::getOption()->get_search_is_infinitescroll_replace_js() ) {
		// Get localization options
		$localization_options = OptionLocalization::get_options();

		wp_register_script( 'infinitescroll', plugins_url( '/js/jquery.infinitescroll.js', __FILE__ ), array( 'jquery' ), WPSOLR_PLUGIN_VERSION, true );

		wp_enqueue_script( 'infinitescroll' );

		// loadingtext for translation
		// loadimage custom loading image url
		wp_localize_script( 'infinitescroll', 'wp_localize_script_infinitescroll',
			array(
				'ajax_url'           => admin_url( 'admin-ajax.php' ),
				'loadimage'          => plugins_url( '/images/infinitescroll.gif', __FILE__ ),
				'loadingtext'        => OptionLocalization::get_term( $localization_options, 'infinitescroll_loading' ),
				'SEARCH_PARAMETER_Q' => WPSOLR_Query_Parameters::SEARCH_PARAMETER_Q,
			),
			WPSOLR_PLUGIN_VERSION
		);
	}
}

/*
 *  Add hidden fields in footer containing the nonce for auto suggestions on non-wpsolr search boxes
 */
if ( WPSOLR_Option::OPTION_SEARCH_SUGGEST_CONTENT_TYPE_NONE !== WPSOLR_Service_Container::getOption()->get_search_suggest_content_type() ) {
	function wpsolr_footer() {
		?>

        <!-- wpsolr - ajax auto completion nonce -->
        <input type="hidden" id="<?php echo esc_attr( WPSOLR_AUTO_COMPLETE_NONCE_SELECTOR ); ?>"
               value="<?php echo esc_attr( wp_create_nonce( 'nonce_for_autocomplete' ) ); ?>">

		<?php
	}

	add_action( 'wp_footer', 'wpsolr_footer' );
}

function wpsolr_activate() {

	if ( ! is_multisite() ) {
		/**
		 * Mark licenses
		 */
		WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_LICENSES, true );
		OptionLicenses::upgrade_licenses();
	}
}

register_activation_hook( __FILE__, 'wpsolr_activate' );

