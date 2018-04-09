<?php

namespace wpsolr\core\classes\extensions;

use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\extensions\localization\OptionLocalization;
use wpsolr\core\classes\extensions\managed_solr_servers\OptionManagedSolrServer;
use wpsolr\core\classes\extensions\premium\WPSOLR_Option_Premium;
use wpsolr\core\classes\extensions\yith_woocommerce_ajax_search_free\WPSOLR_Plugin_YITH_WooCommerce_Ajax_Search_Free;
use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\services\WPSOLR_Service_Container_Factory;
use wpsolr\core\classes\ui\layout\checkboxes\WPSOLR_UI_Layout_Check_Box;
use wpsolr\core\classes\ui\layout\select\WPSOLR_UI_Layout_Select;
use wpsolr\core\classes\ui\layout\WPSOLR_UI_Layout_Abstract;
use wpsolr\core\classes\utilities\WPSOLR_Option;
use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;
use wpsolr\pro\extensions\acf\WPSOLR_Plugin_Acf;
use wpsolr\pro\extensions\all_in_one_seo_pack\WPSOLR_Plugin_AllInOneSeoPack;
use wpsolr\pro\extensions\bbpress\WPSOLR_Plugin_BbPress;
use wpsolr\pro\extensions\cron\WPSOLR_Option_Cron;
use wpsolr\pro\extensions\embed_any_document\WPSOLR_Plugin_EmbedAnyDocument;
use wpsolr\pro\extensions\geolocation\WPSOLR_Option_GeoLocation;
use wpsolr\pro\extensions\google_doc_embedder\WPSOLR_Plugin_GoogleDocEmbedder;
use wpsolr\pro\extensions\groups\WPSOLR_Plugin_Groups;
use wpsolr\pro\extensions\import_export\WPSOLR_Option_Import_Export;
use wpsolr\pro\extensions\pdf_embedder\WPSOLR_Plugin_PdfEmbedder;
use wpsolr\pro\extensions\polylang\WPSOLR_Plugin_Polylang;
use wpsolr\pro\extensions\s2member\WPSOLR_Plugin_S2Member;
use wpsolr\pro\extensions\scoring\WPSOLR_Option_Scoring;
use wpsolr\pro\extensions\tablepress\WPSOLR_Plugin_TablePress;
use wpsolr\pro\extensions\theme\WPSOLR_Option_Theme;
use wpsolr\pro\extensions\theme_listify\WPSOLR_Theme_Listify;
use wpsolr\pro\extensions\types\WPSOLR_Plugin_Types;
use wpsolr\pro\extensions\woocommerce\WPSOLR_Plugin_WooCommerce;
use wpsolr\pro\extensions\wp_all_import\WPSOLR_Plugin_WPAllImport;
use wpsolr\pro\extensions\wpml\WPSOLR_Plugin_Wpml;
use wpsolr\pro\extensions\yoast_seo\WPSOLR_Plugin_YoastSeo;

/**
 * Base class for all WPSOLR extensions.
 * An extension is an encapsulation of a plugin that (if configured) might extend some features of WPSOLR.
 */

/**
 * Class WpSolrExtensions
 * @package wpsolr\core\classes\extensions
 */
class WpSolrExtensions {
	use WPSOLR_Service_Container_Factory;

	static $wpsolr_extensions;
	/*
    * Private constants
    */
	const _CONFIG_EXTENSION_DIRECTORY = 'config_extension_directory';
	const _CONFIG_EXTENSION_CLASS_NAME = 'config_extension_class_name';
	const _CONFIG_PLUGIN_CLASS_NAME = 'config_plugin_class_name';
	const _CONFIG_PLUGIN_FUNCTION_NAME = 'config_plugin_function_name';
	const _CONFIG_PLUGIN_CONSTANT_NAME = 'config_plugin_constant_name';
	const _CONFIG_PLUGIN_IS_AUTO_ACTIVE = 'config_plugin_is_auto_active';
	const _CONFIG_EXTENSION_FILE_PATH = 'config_extension_file_path';
	const _CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH = 'config_extension_admin_options_file_path';
	const _CONFIG_OPTIONS = 'config_extension_options';
	const _CONFIG_OPTIONS_DATA = 'data';
	const _CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME = 'is_active_field';
	const _CONFIG_IS_PRO = 'is_pro';

	const _SOLR_OR_OPERATOR = ' OR ';
	const _SOLR_AND_OPERATOR = ' AND ';

	const _METHOD_CUSTOM_QUERY = 'set_custom_query';

	/*
	 * Public constants
	 */

	// Option: localization
	const OPTION_INDEXES = 'Indexes';

	// Option: localization
	const OPTION_LOCALIZATION = 'Localization';

	// Extension: Groups
	const EXTENSION_GROUPS = 'Groups';

	// Extension: s2member
	const EXTENSION_S2MEMBER = 'S2Member';

	// Extension: WPML
	const EXTENSION_WPML = 'WPML';

	// Extension: POLYLANG
	const EXTENSION_POLYLANG = 'Polylang';

	// Extension: qTranslate X
	const EXTENSION_QTRANSLATEX = 'qTranslate X';

	// Extension: WooCommerce
	const EXTENSION_WOOCOMMERCE = 'WooCommerce';

	// Extension: Advanced Custom Fields
	const EXTENSION_ACF = 'ACF';

	// Extension: Types
	const EXTENSION_TYPES = 'Types';

	// Extension: Gotosolr hosting
	const OPTION_MANAGED_SOLR_SERVERS = 'Managed Solr Servers';

	// Option: licenses
	const OPTION_LICENSES = 'Licenses';

	// Extension: bbpress
	const EXTENSION_BBPRESS = 'bbpress';

	// Extension: Embed Any Document
	const EXTENSION_EMBED_ANY_DOCUMENT = 'embed any document';

	// Extension: Pdf Embedder
	const EXTENSION_PDF_EMBEDDER = 'pdf embedder';

	// Extension: Google Doc Embedder
	const EXTENSION_GOOGLE_DOC_EMBEDDER = 'google doc embedder';

	// Extension: TablePress
	const EXTENSION_TABLEPRESS = 'tablepress';

	// Extension Geolocation
	const EXTENSION_GEOLOCATION = 'wpsolr_geolocation';

	// Extension Premium
	const EXTENSION_PREMIUM = 'wpsolr_premium';

	// Option: theme
	const OPTION_THEME = 'wpsolr_theme';

	// Extension Yoast seo
	const EXTENSION_YOAST_SEO = 'wpsolr_yoast_seo';

	// Extension All in One SEO Pack
	const EXTENSION_ALL_IN_ONE_SEO = 'wpsolr_all_in_one_seo_pack';

	// Extension WP All Import
	const EXTENSION_WP_ALL_IMPORT = 'wpsolr_wp_all_import';

	// Option: Import / Export
	const OPTION_IMPORT_EXPORT = 'wpsolr_import_export';

	// Extension Scoring
	const EXTENSION_SCORING = 'wpsolr_scoring';

	// Extension: YITH WooCommerce Ajax Search (free)
	const EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE = 'EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE';

	// Theme: Listify
	const EXTENSION_THEME_LISTIFY = 'listify';

	// Extension crons
	const EXTENSION_CRON = 'wpsolr_cron';


	/**
	 * Mapping of layout id to layout class
	 * @var array $layout_classes
	 */
	static protected $layout_classes = [
		WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID => WPSOLR_UI_Layout_Check_Box::class,
		WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID    => WPSOLR_UI_Layout_Select::class,
	];

	/**
	 * Mapping of features to layout ids
	 * @var array $feature_layouts_ids
	 */
	static protected $feature_layouts_ids = [
		WPSOLR_UI_Layout_Abstract::FEATURE_GRID                      => [
			WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID,
		],
		WPSOLR_UI_Layout_Abstract::FEATURE_EXCLUSION                 => [
			WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID,
			WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
		],
		WPSOLR_UI_Layout_Abstract::FEATURE_HIERARCHY                 => [
			WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID,
			WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
		],
		WPSOLR_UI_Layout_Abstract::FEATURE_OR                        => [
			WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID,
			WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
		],
		WPSOLR_UI_Layout_Abstract::FEATURE_SORT_ALPHABETICALLY       => [
			WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID,
			WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
		],
		WPSOLR_UI_Layout_Abstract::FEATURE_LOCALIZATION              => [
			WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID,
			WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
		],
		WPSOLR_UI_Layout_Abstract::FEATURE_LOCALIZATION_FIELD        => [
			WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID,
			WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
		],
		WPSOLR_UI_Layout_Abstract::FEATURE_SEO_TEMPLATE              => [
			WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID,
			WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
		],
		WPSOLR_UI_Layout_Abstract::FEATURE_SEO_TEMPLATE_LOCALIZATION => [
			WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID,
			WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
		],
		WPSOLR_UI_Layout_Abstract::FEATURE_MULTIPLE                  => [
			WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
		],
		WPSOLR_UI_Layout_Abstract::FEATURE_PLACEHOLDER               => [
			WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
		],
		WPSOLR_UI_Layout_Abstract::FEATURE_SIZE                      => [
			WPSOLR_UI_Layout_Select::CHILD_LAYOUT_ID,
		],
	];

	/*
	 * Extensions configuration
	 */
	private static $extensions_array = [
		self::OPTION_INDEXES                              =>
			[
				self::_CONFIG_IS_PRO                            => false,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Option_Indexes::class,
				self::_CONFIG_PLUGIN_IS_AUTO_ACTIVE             => false,
				self::_CONFIG_PLUGIN_CLASS_NAME                 => WPSOLR_Option_Indexes::class,
				self::_CONFIG_EXTENSION_DIRECTORY               => 'indexes/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'indexes/class-wpsolr-option-indexes.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'indexes/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_OPTION::OPTION_INDEXES,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::OPTION_LOCALIZATION                         =>
			[
				self::_CONFIG_IS_PRO                            => false,
				self::_CONFIG_EXTENSION_CLASS_NAME              => OptionLocalization::class,
				self::_CONFIG_PLUGIN_IS_AUTO_ACTIVE             => false,
				self::_CONFIG_PLUGIN_CLASS_NAME                 => OptionLocalization::class,
				self::_CONFIG_EXTENSION_DIRECTORY               => 'localization/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'localization/class-optionlocalization.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'localization/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_OPTION::OPTION_LOCALIZATION,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_GROUPS                            =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Plugin_Groups::class,
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'Groups_WordPress',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'groups/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'groups/class-plugingroups.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'groups/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_EXTENSION_GROUPS,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_S2MEMBER                          =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Plugin_S2Member::class,
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'c_ws_plugin__s2member_utils_s2o',
				self::_CONFIG_EXTENSION_DIRECTORY               => 's2member/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 's2member/class-plugins2member.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 's2member/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_EXTENSION_S2MEMBER,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_WPML                              =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Plugin_Wpml::class,
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'SitePress',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'wpml/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'wpml/class-pluginwpml.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'wpml/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_EXTENSION_WPML,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_POLYLANG                          =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Plugin_Polylang::class,
				self::_CONFIG_PLUGIN_FUNCTION_NAME              => 'pll_get_post',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'polylang/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'polylang/class-pluginpolylang.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'polylang/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_EXTENSION_POLYLANG,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::OPTION_MANAGED_SOLR_SERVERS                 =>
			[
				self::_CONFIG_IS_PRO                            => false,
				self::_CONFIG_EXTENSION_CLASS_NAME              => OptionManagedSolrServer::class,
				self::_CONFIG_PLUGIN_FUNCTION_NAME              => 'OptionManagedSolrServers',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'managed_solr_servers/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'managed_solr_servers/class-optionmanagedsolrserver.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'managed_solr_servers/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => 'wdm_solr_extension_managed_solr_servers_data',
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_WOOCOMMERCE                       =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Plugin_WooCommerce::class,
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'WooCommerce',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'woocommerce/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'woocommerce/class-pluginwoocommerce.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'woocommerce/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_EXTENSION_WOOCOMMERCE,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_ACF                               =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Plugin_Acf::class,
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'acf',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'acf/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'acf/class-pluginacf.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'acf/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_EXTENSION_ACF,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_TYPES                             =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Plugin_Types::class,
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'WPCF_Field',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'types/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'types/class-plugintypes.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'types/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_EXTENSION_TYPES,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::OPTION_LICENSES                             =>
			[
				self::_CONFIG_IS_PRO                            => false,
				self::_CONFIG_EXTENSION_CLASS_NAME              => OptionLicenses::class,
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'OptionLicenses',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'licenses/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'licenses/class-optionlicenses.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'licenses/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_LICENSES,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_BBPRESS                           =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Plugin_BbPress::class,
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'bbPress',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'bbpress/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'bbpress/class-pluginbbpress.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'bbpress/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_EXTENSION_BBPRESS,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_EMBED_ANY_DOCUMENT                =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Plugin_EmbedAnyDocument::class,
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'Awsm_embed',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'embed_any_document/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'embed_any_document/class-pluginembedanydocument.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'embed_any_document/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_EXTENSION_EMBED_ANY_DOCUMENT,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_PDF_EMBEDDER                      =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Plugin_PdfEmbedder::class,
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'pdfemb_basic_pdf_embedder',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'pdf_embedder/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'pdf_embedder/class-pluginpdfembedder.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'pdf_embedder/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_EXTENSION_PDF_EMBEDDER,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_GOOGLE_DOC_EMBEDDER               =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Plugin_GoogleDocEmbedder::class,
				self::_CONFIG_PLUGIN_CONSTANT_NAME              => 'GDE_PLUGIN_DIR',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'google_doc_embedder/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'google_doc_embedder/class-plugingoogledocembedder.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'google_doc_embedder/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_EXTENSION_GOOGLE_DOC_EMBEDDER,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_TABLEPRESS                        =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Plugin_TablePress::class,
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'TablePress',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'tablepress/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'tablepress/class-plugintablepress.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'tablepress/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_EXTENSION_TABLEPRESS,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_GEOLOCATION                       =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Option_GeoLocation::class,
				self::_CONFIG_PLUGIN_IS_AUTO_ACTIVE             => true,
				self::_CONFIG_EXTENSION_DIRECTORY               => 'geolocation/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'geolocation/class-option-geolocation.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'geolocation/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_EXTENSION_GEOLOCATION,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_PREMIUM                           =>
			[
				self::_CONFIG_IS_PRO                            => false,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Option_Premium::class,
				self::_CONFIG_PLUGIN_IS_AUTO_ACTIVE             => true,
				self::_CONFIG_EXTENSION_DIRECTORY               => 'premium/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'premium/option-premium.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'premium/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_PREMIUM,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::OPTION_THEME                                =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Option_Theme::class,
				self::_CONFIG_PLUGIN_IS_AUTO_ACTIVE             => true,
				self::_CONFIG_EXTENSION_DIRECTORY               => 'theme/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'theme/class-optiontheme.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'theme/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_THEME,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_YOAST_SEO                         =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Plugin_YoastSeo::class,
				self::_CONFIG_PLUGIN_CONSTANT_NAME              => 'WPSEO_FILE',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'yoast_seo/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'yoast_seo/plugin-yoast-seo.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'yoast_seo/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_YOAST_SEO,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_ALL_IN_ONE_SEO                    =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Plugin_AllInOneSeoPack::class,
				self::_CONFIG_PLUGIN_CONSTANT_NAME              => 'AIOSEOP_VERSION',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'all_in_one_seo_pack/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'all_in_one_seo_pack/class-pluginallinoneseopack.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'all_in_one_seo_pack/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_ALL_IN_ONE_SEO_PACK,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_WP_ALL_IMPORT                     =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Plugin_WPAllImport::class,
				self::_CONFIG_PLUGIN_CONSTANT_NAME              => 'PMXI_VERSION',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'wp_all_import/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'wp_all_import/class-pluginwpallimport.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'wp_all_import/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_WP_ALL_IMPORT,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::OPTION_IMPORT_EXPORT                        =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Option_Import_Export::class,
				self::_CONFIG_PLUGIN_IS_AUTO_ACTIVE             => true,
				self::_CONFIG_EXTENSION_DIRECTORY               => 'import_export/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'import_export/class-option-import-export.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'import_export/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_IMPORT_EXPORT,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_SCORING                           =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Option_Scoring::class,
				self::_CONFIG_PLUGIN_IS_AUTO_ACTIVE             => true,
				self::_CONFIG_EXTENSION_DIRECTORY               => 'scoring/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'scoring/class-option-scoring.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'scoring/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_SCORING,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE =>
			[
				self::_CONFIG_IS_PRO                            => false,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Plugin_YITH_WooCommerce_Ajax_Search_Free::class,
				self::_CONFIG_PLUGIN_CONSTANT_NAME              => 'YITH_WCAS',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'yith_woocommerce_ajax_search_free/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'yith_woocommerce_ajax_search_free/class-wpsolr-plugin-yith-woocommerce-ajax-search-free.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'yith_woocommerce_ajax_search_free/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_EXTENSION_YITH_WOOCOMMERCE_AJAX_SEARCH_FREE,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_THEME_LISTIFY                     =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Theme_Listify::class,
				self::_CONFIG_PLUGIN_CLASS_NAME                 => 'Listify_Activation',
				self::_CONFIG_EXTENSION_DIRECTORY               => 'theme_listify/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'theme_listify/class-wpsolr-theme-listify.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'theme_listify/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_THEME_LISTIFY,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
		self::EXTENSION_CRON                              =>
			[
				self::_CONFIG_IS_PRO                            => true,
				self::_CONFIG_EXTENSION_CLASS_NAME              => WPSOLR_Option_Cron::class,
				self::_CONFIG_PLUGIN_IS_AUTO_ACTIVE             => true,
				self::_CONFIG_EXTENSION_DIRECTORY               => 'cron/',
				self::_CONFIG_EXTENSION_FILE_PATH               => 'cron/class-option-cron.php',
				self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH => 'cron/admin_options.inc.php',
				self::_CONFIG_OPTIONS                           => [
					self::_CONFIG_OPTIONS_DATA                 => WPSOLR_Option::OPTION_CRON,
					self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME => 'is_extension_active',
				],
			],
	];

	/*
	 * Array of active extension objects
	 */
	private $extension_objects = [];

	/**
	 * Factory to load extensions
	 * @return WpSolrExtensions
	 */
	static function load() {

		if ( ! isset( static::$wpsolr_extensions ) ) {

			static::$wpsolr_extensions = new self();
		}
	}

	/**
	 * Constructor.
	 */
	function __construct() {

		// Instantiate active extensions.
		$this->extension_objects = $this->instantiate_active_extension_objects();

	}

	protected function init_default_events() {

		if ( is_admin() ) {

			if ( ! empty( $this->get_default_custom_fields() ) ) {

				add_filter( WPSOLR_Events::WPSOLR_FILTER_INDEX_CUSTOM_FIELDS, [
					$this,
					'wpsolr_filter_index_custom_fields',
				], 10, 1 );

				add_filter( WPSOLR_Events::WPSOLR_FILTER_INDEX_CUSTOM_FIELDS_PROPERTIES_SELECTED, [
					$this,
					'wpsolr_filter_index_custom_fields_properties_selected',
				], 10, 1 );

				add_filter( WPSOLR_Events::WPSOLR_FILTER_INDEX_CUSTOM_FIELDS_SELECTED, [
					$this,
					'wpsolr_filter_index_custom_fields_selected',
				], 10, 1 );
			}

			if ( ! empty( $this->get_default_post_types() ) ) {

				add_filter( WPSOLR_Events::WPSOLR_FILTER_INDEX_POST_TYPES_SELECTED, [
					$this,
					'wpsolr_filter_index_post_types_selected',
				], 10, 1 );
			}

			if ( ! empty( $this->get_default_taxonomies() ) ) {

				add_filter( WPSOLR_Events::WPSOLR_FILTER_INDEX_TAXONOMIES_SELECTED, [
					$this,
					'wpsolr_filter_index_taxonomies_selected',
				], 10, 1 );
			}

			if ( ! empty( $this->get_default_sorts() ) ) {

				add_filter( WPSOLR_Events::WPSOLR_FILTER_INDEX_SORTS_SELECTED, [
					$this,
					'wpsolr_filter_index_sorts_selected',
				], 10, 1 );
			}

		}

	}

	/**
	 * Include a file with a set of parameters.
	 * All other parameters are not passed, because they are out of the function scope.
	 *
	 * @param string $pg File to include
	 * @param mixed $vars Parameters to pass to the file
	 */
	public static function require_with( $pg, $vars = null ) {

		if ( isset( $vars ) ) {
			extract( $vars );
		}

		require $pg;
	}

	/**
	 * Instantiate all active extension classes
	 *
	 * @return array extension objects instantiated
	 */
	private function instantiate_active_extension_objects() {

		$extension_objects = [];

		foreach ( $this->get_extensions_active() as $extension_class_name ) {

			$extension_objects[] = new $extension_class_name();
		}

		return $extension_objects;
	}

	/**
	 * Returns all extension class names which plugins are active. And load them.
	 *
	 * @return array[string]
	 */
	public function get_extensions_active() {
		$results = [];

		foreach ( self::$extensions_array as $key => $class ) {

			if ( $this->require_once_wpsolr_extension( $key, false ) ) {

				$results[] = $class[ self::_CONFIG_EXTENSION_CLASS_NAME ];
			}
		}

		return $results;
	}

	/**
	 * Include the admin options extension file.
	 *
	 * @param string $extension
	 *
	 * @return bool
	 */
	public static function require_once_wpsolr_extension_admin_options( $extension ) {

		// Called from admin: we active the extension, whatever.
		return self::load_file( self::$extensions_array[ $extension ][ self::_CONFIG_EXTENSION_ADMIN_OPTIONS_FILE_PATH ], true );
	}

	/**
	 * Is the extension's plugin active ?
	 *
	 * @param $extension
	 *
	 * @return bool
	 */
	public static function is_plugin_active( $extension ) {

		// Configuration array of $extension
		$extension_config_array = self::$extensions_array[ $extension ];

		// Is extension's plugin installed and activated ?
		if ( isset( $extension_config_array[ self::_CONFIG_PLUGIN_IS_AUTO_ACTIVE ] ) ) {

			return $extension_config_array[ self::_CONFIG_PLUGIN_IS_AUTO_ACTIVE ];

		} elseif ( isset( $extension_config_array[ self::_CONFIG_PLUGIN_CLASS_NAME ] ) ) {

			return class_exists( $extension_config_array[ self::_CONFIG_PLUGIN_CLASS_NAME ] );

		} elseif ( isset( $extension_config_array[ self::_CONFIG_PLUGIN_FUNCTION_NAME ] ) ) {

			return function_exists( $extension_config_array[ self::_CONFIG_PLUGIN_FUNCTION_NAME ] );

		} elseif ( isset( $extension_config_array[ self::_CONFIG_PLUGIN_CONSTANT_NAME ] ) ) {

			return defined( $extension_config_array[ self::_CONFIG_PLUGIN_CONSTANT_NAME ] );

		}

		return false;
	}

	/**
	 * @param string $extension
	 * @param string $custom_field_name
	 */
	public static function update_custom_field_capabilities(
		$extension,
		$custom_field_name
	) {

		// Get options contening custom fields
		$array_wdm_solr_form_data = WPSOLR_Service_Container::getOption()->get_option_index();

		// is extension active checked in options ?
		$extension_is_active = self::is_extension_option_activate( $extension );


		if ( $extension_is_active
		     && ! self::get_custom_field_capabilities( $custom_field_name )
		     && isset( $array_wdm_solr_form_data )
		     && isset( $array_wdm_solr_form_data['cust_fields'] )
		) {

			$custom_fields = explode( ',', $array_wdm_solr_form_data['cust_fields'] );

			if ( ! isset( $custom_fields[ $custom_field_name ] ) ) {

				$custom_fields[ $custom_field_name ] = $custom_field_name;

				$custom_fields_str = implode( ',', $custom_fields );

				$array_wdm_solr_form_data['cust_fields'] = $custom_fields_str;

				update_option( WPSOLR_Option::OPTION_INDEX, $array_wdm_solr_form_data );
			}
		}
	}

	/**
	 * Is the extension activated ?
	 *
	 * @param string $extension
	 *
	 * @return bool
	 */
	public
	static function is_extension_option_activate(
		$extension
	) {

		// Configuration array of $extension
		$extension_config_array = self::$extensions_array[ $extension ];

		// Configuration not set, return
		if ( ! is_array( $extension_config_array ) ) {
			return false;
		}

		// Configuration options array: setup in extension options tab admin
		$extension_options_array = WPSOLR_Service_Container::getOption()->get_option( true, $extension_config_array[ self::_CONFIG_OPTIONS ][ self::_CONFIG_OPTIONS_DATA ] );

		// Configuration option says that user did not choose to active this extension: return
		if ( isset( $extension_options_array ) && isset( $extension_options_array[ $extension_config_array[ self::_CONFIG_OPTIONS ][ self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME ] ] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param string $custom_field_name
	 *
	 * @return bool
	 */
	public
	static function get_custom_field_capabilities(
		$custom_field_name
	) {

		// Get custom fields selected for indexing
		$array_cust_fields = WPSOLR_Service_Container::getOption()->get_option_index_custom_fields();

		if ( ! is_array( $array_cust_fields ) ) {
			return false;
		}

		return false !== array_search( $custom_field_name, $array_cust_fields, true );
	}


	/*
	 * If extension is active, check its custom field in indexing options
	 */

	/**
	 * Include the extension file.
	 * If called from admin, always do.
	 * Else, do it if the extension options say so, and the extension's plugin is activated.
	 *
	 * @param string $extension
	 * @param bool $is_admin
	 *
	 * @return bool
	 */
	public
	static function require_once_wpsolr_extension(
		$extension, $is_admin = false
	) {

		// Configuration array of $extension
		$extension_config_array = self::$extensions_array[ $extension ];

		if ( ! defined( 'WPSOLR_PLUGIN_PRO_DIR' ) && $extension_config_array[ self::_CONFIG_IS_PRO ] ) {
			// Pro extension called in free plugin.
			return false;
		}

		if ( $is_admin ) {
			// Called from admin: we active the extension, whatever.
			return true; //self::load_file( $extension_config_array[ self::_CONFIG_EXTENSION_FILE_PATH ] );
		}

		// Configuration not set, return
		if ( ! is_array( $extension_config_array ) ) {
			return false;
		}

		// Is extension's plugin installed and activated ? Tested before options, before it discards unused plugins with very small load.
		$result = self::is_plugin_active( $extension );
		if ( ! $result ) {
			return false;
		}

		// Configuration options array: setup in extension options tab admin
		$extension_options_array = WPSOLR_Service_Container::getOption()->get_option( true, $extension_config_array[ self::_CONFIG_OPTIONS ][ self::_CONFIG_OPTIONS_DATA ] );

		// Configuration option says that user did not choose to active this extension: return
		// Exception to the Premium extension, always loaded because present in all packs.
		if ( ( self::EXTENSION_PREMIUM !== $extension ) && ( ! isset( $extension_options_array ) || ! isset( $extension_options_array[ $extension_config_array[ self::_CONFIG_OPTIONS ][ self::_CONFIG_OPTIONS_IS_ACTIVE_FIELD_NAME ] ] ) ) ) {
			return false;
		}

		// Load extension's plugin
		$result = true; //self::load_file( $extension_config_array[ self::_CONFIG_EXTENSION_FILE_PATH ] );

		return $result;
	}

	/**
	 * Load an extension file
	 *
	 * @param $file
	 * @param bool $is_admin_option
	 * @param array $vars
	 *
	 * @return bool
	 */
	static public function load_file( $file, $is_admin_option = false, $vars = null ) {

		if ( isset( $vars ) ) {
			extract( $vars );
		}

		if ( defined( 'WPSOLR_PLUGIN_PRO_DIR' ) && file_exists( WPSOLR_PLUGIN_PRO_DIR . '/wpsolr/pro/extensions/' . $file ) ) {
			require_once( WPSOLR_PLUGIN_PRO_DIR . '/wpsolr/pro/extensions/' . $file );

			return true;

		} elseif ( file_exists( plugin_dir_path( __FILE__ ) . $file ) ) {
			require_once( plugin_dir_path( __FILE__ ) . $file );

			return true;
		}

		if ( $is_admin_option ) {
			// Show a message when no extension is found.
			require_once( plugin_dir_path( __FILE__ ) . 'wpsolr-no-extension.inc.php' );
		}

		return false;
	}

	/**
	 * Get the option data of an extension
	 *
	 * @param $extension
	 *
	 * @return mixed
	 */
	public
	static function get_option_data(
		$extension, $default = false
	) {

		return WPSOLR_Service_Container::getOption()->get_option( true, self::get_option_name( $extension ), $default );
	}


	/**
	 * Get the option name of an extension
	 *
	 * @param $extension
	 *
	 * @return mixed
	 */
	public
	static function get_option_name(
		$extension
	) {

		return self::$extensions_array[ $extension ][ self::_CONFIG_OPTIONS ][ self::_CONFIG_OPTIONS_DATA ];
	}

	/**
	 * Get the option PRO/NOT PRO of an extension
	 *
	 * @param $extension
	 *
	 * @return mixed
	 */
	public
	static function get_option_is_pro(
		$extension
	) {

		return self::$extensions_array[ $extension ][ self::_CONFIG_IS_PRO ];
	}

	/**
	 * Set the option value of an extension
	 *
	 * @param $extension
	 * @param $option_value
	 *
	 * @return mixed
	 */
	public
	static function set_option_data(
		$extension, $option_value
	) {

		return update_option( self::$extensions_array[ $extension ][ self::_CONFIG_OPTIONS ][ self::_CONFIG_OPTIONS_DATA ], $option_value );
	}

	/**
	 * Get the extension template path
	 *
	 * @param $extension
	 *
	 * @param $template_file_name
	 *
	 * @return string Template file path
	 *
	 */
	public
	static function get_option_template_file(
		$extension, $template_file_name
	) {

		return plugin_dir_path( __FILE__ ) . self::$extensions_array[ $extension ][ self::_CONFIG_EXTENSION_DIRECTORY ] . 'templates/' . $template_file_name;
	}

	/**
	 * Get the extension file
	 *
	 * @param $extension
	 *
	 * @param $file_name
	 *
	 * @return string File path
	 *
	 */
	public
	static function get_option_file(
		$extension, $file_name
	) {

		$file = self::$extensions_array[ $extension ][ self::_CONFIG_EXTENSION_DIRECTORY ] . $file_name;

		if ( defined( 'WPSOLR_PLUGIN_PRO_DIR' ) && file_exists( WPSOLR_PLUGIN_PRO_DIR . '/wpsolr/pro/extensions/' . $file ) ) {
			$file = WPSOLR_PLUGIN_PRO_DIR . '/wpsolr/pro/extensions/' . $file;
		} else {
			$file = plugin_dir_path( __FILE__ ) . $file;
		}

		return $file;
	}

	/*
	 * Templates methods
	 */

	/**
	 * @param bool $is_submit
	 * @param array $fields
	 *
	 * @return array
	 */
	public
	static function extract_form_data(
		$is_submit, $fields
	) {

		$form_data = [];

		$is_error = false;

		foreach ( $fields as $key => $field ) {

			$value = isset( $_POST[ $key ] ) ? $_POST[ $key ] : $field['default_value'];
			$error = '';

			// Check format errors id it is a form post (submit)
			if ( $is_submit ) {

				$error = '';

				if ( isset( $field['can_be_empty'] ) && ! $field['can_be_empty'] ) {
					$error = empty( $value ) ? 'This field cannot be empty.' : '';
				}

				if ( isset( $field['is_email'] ) ) {
					$error = is_email( $value ) ? '' : 'This does not look like an email address.';
				}
			}

			$is_error = $is_error || ( '' !== $error );

			$form_data[ $key ] = array( 'value' => $value, 'error' => $error );
		}

		// Is there an error in any field ?
		$form_data['is_error'] = $is_error;

		return $form_data;
	}

	/**
	 * Get the dynamic strings to translate among the group data of all extensions translatable.
	 *
	 * @param array $translations
	 *
	 * @return array Translations
	 */
	protected function extract_strings_to_translate_for_all_extensions( &$translations = [] ) {

		// Translate SEO facet template(s)
		$labels = WPSOLR_Service_Container::getOption()->get_facets_seo_permalink_templates();
		if ( is_array( $labels ) && ! empty( $labels ) ) {
			foreach ( $labels as $facet_name => $facet_label ) {
				if ( ! empty( $facet_label ) ) {
					$translation           = [];
					$translation['domain'] = WPSOLR_Option::TRANSLATION_DOMAIN_FACET_SEO_TEMPLATE;
					$translation['name']   = $facet_name;
					$translation['text']   = $facet_label;

					array_push( $translations, $translation );
				}
			}
		}

		// Translate SEO facet item templates
		$facet_item_seo_templates = WPSOLR_Service_Container::getOption()->get_facets_seo_permalink_items_templates();
		if ( is_array( $facet_item_seo_templates ) && ! empty( $facet_item_seo_templates ) ) {
			foreach ( $facet_item_seo_templates as $facet_name => $facet_items_seo_templates ) {
				foreach ( $facet_items_seo_templates as $facet_item_name => $facet_item_seo_template ) {
					if ( ! empty( $facet_item_seo_template ) ) {
						$translation           = [];
						$translation['domain'] = WPSOLR_Option::TRANSLATION_DOMAIN_FACET_ITEM_SEO_TEMPLATE;
						$translation['name']   = $facet_item_name;
						$translation['text']   = $facet_item_seo_template;

						array_push( $translations, $translation );
					}
				}
			}
		}

		// Translate facet labels
		$labels = WPSOLR_Service_Container::getOption()->get_facets_labels();
		if ( is_array( $labels ) && ! empty( $labels ) ) {
			foreach ( $labels as $facet_name => $facet_label ) {
				if ( ! empty( $facet_label ) ) {
					$translation           = [];
					$translation['domain'] = WPSOLR_Option::TRANSLATION_DOMAIN_FACET_LABEL;
					$translation['name']   = $facet_name;
					$translation['text']   = $facet_label;

					array_push( $translations, $translation );
				}
			}
		}

		// Translate facet placeholders
		$labels = WPSOLR_Service_Container::getOption()->get_facets_placeholder();
		if ( is_array( $labels ) && ! empty( $labels ) ) {
			foreach ( $labels as $facet_name => $facet_label ) {
				if ( ! empty( $facet_label ) ) {
					$translation           = [];
					$translation['domain'] = WPSOLR_Option::TRANSLATION_DOMAIN_FACET_PLACEHOLDER;
					$translation['name']   = $facet_name;
					$translation['text']   = $facet_label;

					array_push( $translations, $translation );
				}
			}
		}

		// Translate facet js
		$labels = WPSOLR_Service_Container::getOption()->get_facets_js();
		if ( is_array( $labels ) && ! empty( $labels ) ) {
			foreach ( $labels as $facet_name => $facet_label ) {
				if ( ! empty( $facet_label ) ) {
					$translation           = [];
					$translation['domain'] = WPSOLR_Option::TRANSLATION_DOMAIN_FACET_JS;
					$translation['name']   = $facet_name;
					$translation['text']   = $facet_label;

					array_push( $translations, $translation );
				}
			}
		}

		// Translate facet items labels
		$labels = WPSOLR_Service_Container::getOption()->get_facets_items_labels();
		if ( is_array( $labels ) && ! empty( $labels ) ) {
			foreach ( $labels as $facet_name => $facet_items_labels ) {
				foreach ( $facet_items_labels as $facet_item_name => $facet_item_label ) {
					if ( ! empty( $facet_item_label ) ) {
						$translation           = [];
						$translation['domain'] = WPSOLR_Option::TRANSLATION_DOMAIN_FACET_LABEL;
						$translation['name']   = $facet_item_name;
						$translation['text']   = $facet_item_label;

						array_push( $translations, $translation );
					}
				}
			}
		}

		// Translate sort labels
		$labels = WPSOLR_Service_Container::getOption()->get_sortby_items_labels();
		if ( is_array( $labels ) && ! empty( $labels ) ) {
			foreach ( $labels as $facet_name => $facet_label ) {
				if ( ! empty( $facet_label ) ) {
					$translation           = [];
					$translation['domain'] = WPSOLR_Option::TRANSLATION_DOMAIN_SORT_LABEL;
					$translation['name']   = $facet_name;
					$translation['text']   = $facet_label;

					array_push( $translations, $translation );
				}
			}
		}

		// Translate geolocation labels
		$label = WPSOLR_Service_Container::getOption()->get_option_geolocation_user_aggreement_label();
		if ( ! empty( $label ) ) {
			$translation           = [];
			$translation['domain'] = WPSOLR_Option::TRANSLATION_DOMAIN_GEOLOCATION_LABEL;
			$translation['name']   = WPSOLR_Option::OPTION_GEOLOCATION_USER_AGREEMENT_LABEL;
			$translation['text']   = $label;

			array_push( $translations, $translation );
		}

		if ( count( $translations ) > 0 ) {

			// Translate
			do_action( WPSOLR_Events::ACTION_TRANSLATION_REGISTER_STRINGS,
				[
					'translations' => $translations,
				]
			);
		}

	}

	/**
	 * Default custom fields to add to the indexed custom fields
	 * @return array
	 */
	protected function get_default_custom_fields() {
		// Override in child
		return [];
	}

	/**
	 * Force custom fields selection if the custom fields are empty.
	 *
	 * @param array $custom_fields_properties
	 *
	 * @return array
	 */
	public function wpsolr_filter_index_custom_fields_properties_selected( $custom_fields_properties ) {

		$was_empty = empty( $custom_fields_properties );
		if ( $was_empty ) {
			$has_been_modified = false;
			foreach ( $this->get_default_custom_fields() as $custom_field_name => $custom_field_property ) {

				$custom_field_name = $custom_field_name . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING;

				if ( ! isset( $custom_fields_properties[ $custom_field_name ] ) ) {
					$custom_fields_properties[ $custom_field_name ] = [
						WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE               => $custom_field_property[ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_SOLR_TYPE ],
						WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION => $custom_field_property[ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTY_CONVERSION_ERROR_ACTION ],
					];
					$has_been_modified                              = true;
				}
			}

			if ( $was_empty && $has_been_modified ) {
				$option                                                        = $this->get_container()->get_service_option()->get_option_index();
				$option[ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELD_PROPERTIES ] = $custom_fields_properties;
				update_option( WPSOLR_Option::OPTION_INDEX, $option, true );
			}
		}

		return $custom_fields_properties;
	}

	/**
	 * Default sorts to add to the sorts
	 * @return array
	 */
	protected function get_default_sorts() {
		// Override in child
		return [];
	}

	/**
	 * Force sort properties selection if the sorts are empty.
	 *
	 * @param array $sorts_selected
	 *
	 * @return array
	 */
	public function wpsolr_filter_index_sorts_selected( $sorts_selected ) {

		$was_empty         = empty( $sorts_selected );
		$has_been_modified = false;
		if ( $was_empty ) {
			foreach ( $this->get_default_sorts() as $sort_field_name ) {

				if ( ! in_array( $sort_field_name, $sorts_selected, true ) ) {
					$sorts_selected[]  = $sort_field_name;
					$has_been_modified = true;
				}
			}

			if ( $was_empty && $has_been_modified ) {
				$option                                            = $this->get_container()->get_service_option()->get_option_sortby();
				$option[ WPSOLR_Option::OPTION_SORTBY_ITEM_ITEMS ] = implode( ',', $sorts_selected );
				update_option( WPSOLR_Option::OPTION_SORTBY, $option, true );
			}
		}

		return $sorts_selected;
	}

	/**
	 * Force listify custom fields properties selection if the custom fields are empty.
	 *
	 * @param array $custom_fields_properties
	 *
	 * @return array
	 */
	public function wpsolr_filter_index_custom_fields_selected( $custom_fields_selected ) {

		$was_empty         = empty( $custom_fields_selected );
		$has_been_modified = false;
		if ( $was_empty ) {
			foreach ( $this->get_default_custom_fields() as $custom_field_name => $custom_field_property ) {
				// Add the 2 geolocation custom fields automatically if they are missing.

				$custom_field_name = $custom_field_name . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING;

				if ( ! in_array( $custom_field_name, $custom_fields_selected, true ) ) {
					$custom_fields_selected[] = $custom_field_name;
					$has_been_modified        = true;
				}
			}

			if ( $was_empty && $has_been_modified ) {
				$option                                              = $this->get_container()->get_service_option()->get_option_index();
				$option[ WPSOLR_Option::OPTION_INDEX_CUSTOM_FIELDS ] = implode( ',', $custom_fields_selected );
				update_option( WPSOLR_Option::OPTION_INDEX, $option, true );
			}
		}

		return $custom_fields_selected;
	}


	/**
	 * Default post types to add to the indexed post types
	 * @return array
	 */
	protected function get_default_post_types() {
		// Override in child
		return [];
	}

	/**
	 * Force listify post types selection if the post types are empty.
	 *
	 * @param array $post_types_selected
	 *
	 * @return array
	 */
	public function wpsolr_filter_index_post_types_selected( $post_types_selected ) {

		$was_empty         = empty( $post_types_selected );
		$has_been_modified = false;
		foreach ( $this->get_default_post_types() as $post_type_name ) {

			if ( ! in_array( $post_type_name, $post_types_selected, true ) ) {
				$post_types_selected[] = $post_type_name;
				$has_been_modified     = true;
			}
		}

		if ( $was_empty && $has_been_modified ) {
			$option                                           = $this->get_container()->get_service_option()->get_option_index();
			$option[ WPSOLR_Option::OPTION_INDEX_POST_TYPES ] = implode( ',', $post_types_selected );
			update_option( WPSOLR_Option::OPTION_INDEX, $option, true );
		}

		return $post_types_selected;
	}

	/**
	 * Default taxonomies to add to the indexed taxonomies
	 * @return array
	 */
	protected function get_default_taxonomies() {
		// Override in child
		return [];
	}

	/**
	 * Force listify taxonomies selection if the taxonomies are empty.
	 *
	 * @param array $taxonomies_selected
	 *
	 * @return array
	 */
	public function wpsolr_filter_index_taxonomies_selected( $taxonomies_selected ) {

		$has_been_modified = false;
		if ( empty( $taxonomies_selected ) ) {
			foreach ( $this->get_default_taxonomies() as $taxonomy_name ) {

				$taxonomy_name = $taxonomy_name . WpSolrSchema::_SOLR_DYNAMIC_TYPE_STRING;

				if ( ! in_array( $taxonomy_name, $taxonomies_selected, true ) ) {
					$taxonomies_selected[] = $taxonomy_name;
					$has_been_modified     = true;
				}
			}

			if ( $has_been_modified ) {
				$option                                           = $this->get_container()->get_service_option()->get_option_index();
				$option[ WPSOLR_Option::OPTION_INDEX_TAXONOMIES ] = implode( ',', $taxonomies_selected );
				update_option( WPSOLR_Option::OPTION_INDEX, $option, true );
			}
		}

		return $taxonomies_selected;
	}

	/**
	 * Add custom geolocation field to list of fields
	 *
	 * @param string[] $custom_fields
	 *
	 * @return string[]
	 */
	function wpsolr_filter_index_custom_fields( $custom_fields ) {

		if ( ! isset( $custom_fields ) ) {
			$custom_fields = [];
		}

		foreach ( $this->get_default_custom_fields() as $custom_field_name => $custom_field_property ) {

			if ( ! in_array( $custom_field_name, $custom_fields, true ) ) {
				array_push( $custom_fields, $custom_field_name );
			}
		}

		return $custom_fields;
	}


	/**
	 * Layouts
	 */

	/**
	 * Layouts ids available for a facet feature
	 *
	 * @param string[] $default_layouts_ids
	 * @param string $facet_feature
	 *
	 * @return array
	 */

	public function wpsolr_filter_facet_feature_layouts( $default_layouts_ids, $facet_feature ) {

		if ( ! empty( static::$feature_layouts_ids[ $facet_feature ] ) ) {
			return static::$feature_layouts_ids[ $facet_feature ];
		}

		return ! empty( $default_layouts_ids ) ? $default_layouts_ids : [ WPSOLR_UI_Layout_Check_Box::CHILD_LAYOUT_ID ];
	}

	/**
	 * @param string $default_layout_object
	 * @param string $layout_id
	 *
	 * @return string CSS class name
	 */
	function wpsolr_filter_layout_object( $default_layout_object, $layout_id ) {

		return empty( static::$layout_classes[ $layout_id ] ) ? $default_layout_object : ( new static::$layout_classes[ $layout_id ]() );
	}

	/**
	 * @param $layout_id
	 *
	 * @return WPSOLR_UI_Layout_Abstract
	 * @throws \Exception
	 */
	protected static function get_layout_class( $layout_id ) {

		if ( ! empty( static::$layout_classes[ $layout_id ] ) ) {

			return static::$layout_classes[ $layout_id ];

		} else {

			throw new \Exception( "WPSOLR: unknown layout class for '{$layout_id}'" );
		}
	}

	/**
	 * Layouts available for a field type
	 *
	 * @param array $layouts
	 * @param string $field_type
	 *
	 * @return array
	 */
	public function get_layouts_for_field_type( $layouts, $field_type = '' ) {

		$results = [];
		/**
		 * @var string $layout_id
		 * @var WPSOLR_UI_Layout_Abstract $layout_class
		 */
		foreach ( static::$layout_classes as $layout_id => $layout_class ) {
			$types = $layout_class::get_types();

			if ( empty( $types ) || in_array( $field_type, $types, true ) ) {
				// All field types authorized, or our $field_type authorized
				$results[ $layout_id ] = new $layout_class();
			}
		}

		return $results;
	}

	/**
	 * Get all facet skins, by facet layout.
	 *
	 * @return array
	 */
	public function get_facet_layout_skins( $facet_layout_skins = [] ) {

		$results = [];
		/**
		 * @var string $layout_id
		 * @var WPSOLR_UI_Layout_Abstract $layout_class
		 */
		foreach ( static::$layout_classes as $layout_id => $layout_class ) {

			$layout_class = $this->get_layout_class( $layout_id );
			$skins        = $layout_class::get_skins();

			if ( ! empty( $skins ) ) {
				$results[ $layout_id ] = $skins;
			}
		}

		return $results;
	}

	/**
	 * Get js placeholder for a layout.
	 *
	 * @param string $layout_id
	 *
	 * @return string
	 */
	static public function get_layout_js_help( $layout_id ) {

		$layout_class = self::get_layout_class( $layout_id );

		return $layout_class::get_js_help_text();
	}

	/**
	 * Get the facet type from a facet layout
	 *
	 * @param string $default_value
	 * @param string $facet_name
	 *
	 * @return string
	 */
	public function wpsolr_filter_facet_type( $default_value, $facet_name ) {

		$facet_layout_id = $this->get_container()->get_service_option()->get_facets_layout_id( $facet_name );

		if ( ! empty( $facet_layout_id ) && ! empty( static::$layout_classes[ $facet_layout_id ] ) ) {
			// Get the facet type of the layout
			$layout_class = $this->get_layout_class( $facet_layout_id );

			return $layout_class::get_facet_type();
		}

		return isset( $default_value ) ? $default_value : WPSOLR_Option::OPTION_FACET_FACETS_TYPE_FIELD;
	}
}
