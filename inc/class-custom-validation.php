<?php

class Custom_Validation {
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
		add_filter( 'gform_field_validation', array( $this, 'validate_text_field' ), 10, 4 );
		add_action( 'gform_addon_pre_process_feeds', array( $this, 'halt_fake_email' ), 10, 3 );
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
	 * Validate text field
	 *
	 * Makes sure value only has letters, numbers, dashes, and spaces. No BS.
	 *
	 * @param array  $result The result of the validation.
	 * @param string $value  Incoming value of the field.
	 * @param array  $form   Full data and information about the current form.
	 * @param object $field  The GForm field information.
	 */
	public function validate_text_field( $result, $value, $form, $field ) {
		$pattern = '/[\w\sÀ-ú\-\'\.\,\!]*/';
		if ( 'text' === $field->type && ! preg_match( $pattern, $value ) ) {
			$result['is_valid'] = false;
			$result['message']  = 'Please enter a valid value for text';
		}

		return $result;
	}

	/**
	 * Stop webhooks if email contains blacklisted domain
	 *
	 * @param array $feeds An array of $feed objects.
	 * @param array $entry Current entry for which feeds will be processed.
	 * @param array $form Current form object.
	 *
	 * @return mixed An array of $feeds or kill the process.
	 */
	public function halt_fake_email( $feeds, $entry, $form ) {
		// Get the value of our email field.
		$email = $this->get_value_by_label( $form, $entry, 'Email Address' );
		// Remove any whitespace.
		$email = trim( $email );
		// Get our settings from the GF admin page.
		$add_on   = Gf_Nus_Addon::get_instance();
		$settings = $add_on->get_plugin_settings();
		// Make an array from our csv values via admin settings.
		$blocked_domains = explode( ',', $settings['blocked_domains'] );
		// Run through each value in the array.
		foreach ( $blocked_domains as $blocked_domain ) {
			// If the value of the email field matches the value in our blocked domains array.
			if ( strpos( $email, $blocked_domain ) !== false ) {
				// Still submit to WordPress and show thank you page, but don't perform webhook (sneaky).
				return array();
			}
		}
		// Otherwise, run as normal.
		return $feeds;
	}

	/**
	 * Gets the field value from the label
	 * - https://www.gravitygeek.com/knowledge-base/how-to-get-entry-value-by-label/
	 *
	 * @param array $form Current form object.
	 * @param array $entry Current entry for which feeds will be processed.
	 * @param array $label Label to fetch the field.
	 */
	private function get_value_by_label( $form, $entry, $label ) {
		foreach ( $form['fields'] as $field ) {
			$lead_key = $field->label;

			if ( strtolower( $lead_key ) === strtolower( $label ) ) {
				return $entry[ $field->id ];
			}
		}
		return false;
	}
}
