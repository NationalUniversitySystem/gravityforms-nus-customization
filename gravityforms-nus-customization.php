<?php
/**
 * Plugin Name: Gravity Forms - NUS Customization
 * Description: Plugin that customizes and expands GF to NUS specifications.
 * Version: 1.2.1
 * Author: Chris Maust / Mike Estrada
 *
 * @package gravityforms-nus-customization
 */

if ( ! defined( 'WPINC' ) ) {
	die( 'YOU! SHALL NOT! PASS!' );
}

// Define a few constants we'll need.
define( 'GF_NUS_VER', '1.2.1' );
define( 'GF_NUS_PATH', plugin_dir_path( __FILE__ ) );
define( 'GF_NUS_URL', plugin_dir_url( __FILE__ ) );

// If Gravity Forms is loaded, load our files.
add_action( 'gform_loaded', 'gf_nus_load' );

/**
 * Include most files in the include method and custom fields in the GForms Addon method.
 * Naming convention:
 * - File: class-example-name.php
 * - Class: Example_Name
 *
 * Note: Might still rework this to an autoload class so it's cleaner.
 */
function gf_nus_load() {
	if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
		return;
	}

	$files_list = array(
		'tracking/class-gf-nus-fb-tracking.php',
		'inc/class-custom-validation.php',
		'inc/class-gf-nus-javascript.php',
		'inc/class-gf-nus-markup.php',
		'inc/class-gf-nus-security.php',
		'inc/class-national-university-gravityforms.php',
	);

	foreach ( $files_list as $filename ) {
		if ( file_exists( GF_NUS_PATH . $filename ) ) {
			require GF_NUS_PATH . $filename;

			$class_name = preg_replace( '/^class-/', '', basename( $filename, '.php' ) );
			$class_name = implode( '_', array_map( 'ucfirst', explode( '-', $class_name ) ) );

			if ( class_exists( $class_name ) && method_exists( $class_name, 'singleton' ) ) {
				$class_name::singleton();

				if ( method_exists( $class_name, 'activate' ) ) {
					register_activation_hook( __FILE__, array( $class_name, 'activate' ) );
				}
			}
		}
	}

	if ( file_exists( GF_NUS_PATH . 'class-gf-nus-addon.php' ) ) {
		require_once GF_NUS_PATH . 'class-gf-nus-addon.php';

		GFAddOn::register( 'Gf_Nus_Addon' );
	}
}
