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
	 *
	 * @param string|array $value The fields input value.
	 * @param array        $lead  The current entry object.
	 * @param GF_Field     $field The current field object.
	 * @param array        $form  The current form object.
	 */
	public function gf_custom_save_field_value( $value, $lead, $field, $form ) {
		$key = $field->formId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

		// If the value is blank (no value set, e.g. no utm params).
		if ( '' === $value ) {
			// Set the value as an empty (space) string, so it returns as a string and not false (0 in eloqua).
			$value = ' ';
		}
		return GFCommon::openssl_encrypt( $value, $key );
	}

	/**
	 * Decrypt Gravity Forms Data
	 *
	 * Make it readable
	 *
	 * @param string|array $value The fields input value.
	 * @param array        $entry  The current entry object.
	 * @param GF_Field     $field The current field object.
	 * @param string       $input_id The ID of the input being saved or the field ID for single input field types.
	 */
	public function gf_custom_decode_field( $value, $entry, $field, $input_id ) {
		$key = $field->formId; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

		return GFCommon::openssl_decrypt( $value, $key );
	}
}
