<?php
/**
 * Custom namespace autoload
 */

namespace wpsolr;


/**
 * Class WPSOLR_Autoloader
 * @package wpsolr
 */
class WPSOLR_Autoloader {

	/**
	 * Autoload classes based on namespaces beginning with 'wpsolr'
	 *
	 * @param $className
	 *
	 * @return bool
	 */
	static function Load( $className ) {

		if ( substr( $className, 0, strlen( 'wpsolr' ) ) === 'wpsolr' ) {

			$file_name = str_replace( '\\', '/', $className );

			$base_name = basename( $file_name );
			$base_name = str_replace( '_', '-', $base_name );
			$base_name = strtolower( $base_name );
			$base_name = 'class-' . $base_name;

			$file_name = ( defined( 'WPSOLR_PLUGIN_PRO_DIR' ) ? WPSOLR_PLUGIN_PRO_DIR : WPSOLR_PLUGIN_DIR ) . '/' . dirname( $file_name ) . '/' . $base_name . '.php';

			if ( file_exists( $file_name ) ) {

				require_once( $file_name );

				if ( class_exists( $className ) || trait_exists( $className ) || interface_exists( $className ) ) {
					return true;
				} else {
					echo sprintf( 'WPSOLR autoload error: class %s not found in file %s', $className, $file_name );
				}

			} else {
				echo sprintf( 'WPSOLR autoload error: file %s not found for class %s', $file_name, $className );
			}

			die();

		}

		return false;
	}
}

// autoloader declaration for phpunit bootstrap script in phpunit.xml
spl_autoload_register( [ WPSOLR_Autoloader::CLASS, 'Load' ] );
