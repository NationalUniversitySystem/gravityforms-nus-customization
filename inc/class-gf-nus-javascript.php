<?php
/**
 * Handle the plugin's JS related functionality
 */

/**
 * Gf_Nus_Javascript class
 */
class Gf_Nus_Javascript {
	/**
	 * Instance of this class
	 *
	 * @var boolean
	 */
	public static $instance = false;

	/**
	 * Use class construct method to define all filters & actions
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 99 );
		add_action( 'script_loader_tag', [ $this, 'do_script_loader_tag' ], 10, 3 );
	}

	/**
	 * Singleton
	 *
	 * Returns a single instance of this class.
	 */
	public static function singleton() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Enqueue Assets
	 *
	 * Enqueues the necessary css and js files when the theme is loaded.
	 */
	public function enqueue_scripts() {
		if ( ! wp_script_is( 'polyfill-service' ) ) {
			wp_enqueue_script( 'polyfill-service', 'https://polyfill.io/v3/polyfill.min.js?flags=gated&features=Array.prototype.forEach%2CNodeList.prototype.forEach%2CElement.prototype.matches', [], '3.0.0', true );
		}
		wp_enqueue_script( 'gravityforms-nus', GF_NUS_URL . '/js/nus-gravity-forms.js', [ 'jquery', 'polyfill-service' ], filemtime( GF_NUS_PATH . '/js/nus-gravity-forms.js' ), true );
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
	 * @param  string $src    The script's source URL.
	 *
	 * @return string The formatted HTML script tag of the given enqueued script.
	 */
	public function do_script_loader_tag( $tag, $handle, $src ) {
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
