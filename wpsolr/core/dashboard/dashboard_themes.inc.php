<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;
use wpsolr\core\classes\extensions\WpSolrExtensions;

$subtabs1 = [
	'extension_theme_listify_opt' => [
		'name'  => '>> Listify 2.3.1',
		'class' => wpsolr_get_extension_tab_class( OptionLicenses::LICENSE_PACKAGE_LISTIFY, WpSolrExtensions::EXTENSION_THEME_LISTIFY ),
	],
];

// Diplay the subtabs
include( 'dashboard_extensions.inc.php' );
