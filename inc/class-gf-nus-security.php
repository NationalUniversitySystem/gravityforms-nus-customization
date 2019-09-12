<?php
/**
 * Handle form security functionality
 */

/**
 * Gf_Nus_Security class
 */
class Gf_Nus_Security {
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
		add_filter( 'gform_save_field_value', [ $this, 'gf_custom_save_field_value' ], 10, 4 );
		add_filter( 'gform_get_input_value', [ $this, 'gf_custom_decode_field' ], 10, 4 );
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
	 * Encrypt Gravity Forms Data
	 *
	 * Make it unreadable and safe
	 */
	public function gf_custom_save_field_value( $value, $lead, $field, $form ) {
		$key = $field->formId;

		// If the value is blank (no value set, e.g. no utm params).
		if ( '' === $value ) {

			// Then set the value as an empty (space) string, so it returns as a string and not false (0 in eloqua).
			$value = ' ';
		}
		return GFCommon::openssl_encrypt( $value, $key );
	}

	/**
	 * Decrypt Gravity Forms Data
	 *
	 * Make it readable
	 */
	public function gf_custom_decode_field( $value, $entry, $field, $input_id ) {
		$key = $field->formId;

		return GFCommon::openssl_decrypt( $value, $key );
	}
}
