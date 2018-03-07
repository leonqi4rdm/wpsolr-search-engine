<?php
use wpsolr\core\classes\extensions\indexes\WPSOLR_Option_Indexes;
use wpsolr\core\classes\extensions\licenses\OptionLicenses;

global $license_manager;

if ( count( $option_object->get_indexes() ) > 0 ) {
	$option_object                              = new WPSOLR_Option_Indexes();
	$subtabs[ $option_object->generate_uuid() ] = $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_PREMIUM, 'Configure another index', false );
}
