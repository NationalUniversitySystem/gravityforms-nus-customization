<?php
/**
 * Handle the plugin's Assets related functionality
 */

namespace NUSA\GravityForms;

/**
 * Assets class
 */
class Assets {
	/**
	 * Use class construct method to define all filters & actions
	 */
	public function __construct() {
		add_action( 'gform_enqueue_scripts', [ $this, 'dequeue_gf_scripts' ], 11 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 99 );
		add_action( 'script_loader_tag', [ $this, 'do_script_loader_tag' ], 10, 2 );
	}

	/**
	 * Remove Gravity Forms stylesheets and unnecessary scripts
	 *
	 * Gets rid of the nasty default css by removing the stylesheet and the datalist chosen JS.
	 */
	public function dequeue_gf_scripts() {
		if ( ! is_admin() && ! \GFCommon::is_preview() ) {
			wp_dequeue_style( 'gforms_formsmain_css' );
			wp_dequeue_style( 'gforms_browsers_css' );
			wp_dequeue_script( 'gform_chosen' );
		}
	}

	/**
	 * Enqueue Assets
	 *
	 * Enqueues the necessary css and js files when the theme is loaded.
	 */
	public function enqueue_scripts() {
		if ( ! wp_script_is( 'polyfill-service' ) ) {
			wp_enqueue_script( 'polyfill-service', 'https://polyfill.io/v3/polyfill.min.js?flags=gated&features=Array.prototype.forEach%2CNodeList.prototype.forEach%2CElement.prototype.matches%2CElement.prototype.closest%2Cfetch%2CHTMLTemplateElement', [], '3.0.0', true );
		}

		wp_enqueue_style( 'gravityforms-nus', GF_NUS_URL . 'assets/css/main.min.css', [], filemtime( GF_NUS_PATH . 'assets/css/main.min.css' ) );
		wp_enqueue_script( 'gravityforms-nus-vendor', GF_NUS_URL . 'assets/js/vendor.min.js', [ 'jquery', 'polyfill-service' ], filemtime( GF_NUS_PATH . 'assets/js/vendor.min.js' ), true );
		wp_enqueue_script( 'gravityforms-nus', GF_NUS_URL . 'assets/js/nus-gravity-forms.min.js', [ 'jquery', 'polyfill-service', 'gravityforms-nus-vendor' ], filemtime( GF_NUS_PATH . 'assets/js/nus-gravity-forms.min.js' ), true );
		wp_localize_script( 'gravityforms-nus', 'NuAjaxObject', [ 'ajax_url' => admin_url( 'admin-ajax.php' ) ] );
	}

	/**
	 * Do Script Loader Tag
	 *
	 * Allows enqueued scripts to be loaded asynchronously, thus preventing the
	 * page from being blocked by js calls.
	 *
	 * @param  string $tag    The <script> tag for the enqueued script.
	 * @param  string $handle The script's registered handle.
	 *
	 * @return string The formatted HTML script tag of the given enqueued script.
	 */
	public function do_script_loader_tag( $tag, $handle ) {
		// The handles of the enqueued scripts we want to defer.
		$defer_scripts = [
			'gravityforms-nus',
		];

		if ( in_array( $handle, $defer_scripts, true ) ) {
			return str_replace( ' src', ' defer="defer" src', $tag );
		}

		return $tag;
	}
}
