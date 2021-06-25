<?php
/**
 * Plugin Name: Gravity Forms - NUS Customization
 * Description: Plugin that customizes and expands Gravity Forms' functionality to NUS specifications.
 * Version: 2.1.0
 *
 * @package gravityforms-nus-customization
 */

namespace NUSA\GravityForms;

if ( ! defined( 'WPINC' ) ) {
	die( 'YOU! SHALL NOT! PASS!' );
}

// Define a few constants we'll need.
define( 'GF_NUS_VER', '2.0.0' );
define( 'GF_NUS_PATH', plugin_dir_path( __FILE__ ) );
define( 'GF_NUS_URL', plugin_dir_url( __FILE__ ) );

if ( ! file_exists( plugin_dir_path( __FILE__ ) . '/vendor/autoload.php' ) ) {
	die( 'Missing something...' );
}

require plugin_dir_path( __FILE__ ) . '/vendor/autoload.php';

/**
 * If Gravity Forms is loaded, include most files in the include method and custom fields in the GForms Addon method.
 * Naming convention:
 * - File: class-example-name.php
 * - Class: Example_Name
 */
add_action( 'gform_loaded', function() {
	if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
		return;
	}

	new Assets();
	new Consent_Expansion();
	new Custom_Validation();
	new Custom_Markup();
	new Metadata();
	new Multi_Language_Support();
	new Security();
	new Settings();

	$files_list = [
		'tracking/class-gf-nus-fb-tracking.php',
	];

	foreach ( $files_list as $filename ) {
		if ( file_exists( GF_NUS_PATH . $filename ) ) {
			require GF_NUS_PATH . $filename;

			$class_name = preg_replace( '/^class-/', '', basename( $filename, '.php' ) );
			$class_name = implode( '_', array_map( 'ucfirst', explode( '-', $class_name ) ) );

			if ( class_exists( $class_name ) && method_exists( $class_name, 'singleton' ) ) {
				$class_name::singleton();

				if ( method_exists( $class_name, 'activate' ) ) {
					register_activation_hook( __FILE__, [ $class_name, 'activate' ] );
				}
			}
		}
	}

	if ( file_exists( GF_NUS_PATH . 'class-gf-nus-addon.php' ) ) {
		require_once GF_NUS_PATH . 'class-gf-nus-addon.php';

		\GFAddOn::register( 'Gf_Nus_Addon' );
	}
} );
