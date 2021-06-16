<?php
/**
 * Manage custom Gravityforms validation for our forms
 */

/**
 * Custom_Validation class
 */
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
		add_filter( 'gform_field_validation', [ $this, 'validate_text_field' ], 10, 4 );
		add_action( 'gform_addon_pre_process_feeds', [ $this, 'halt_fake_email' ], 10, 3 );

		add_action( 'wp_ajax_zip_lookup', [ $this, 'zip_lookup' ] );
		add_action( 'wp_ajax_nopriv_zip_lookup', [ $this, 'zip_lookup' ] );
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
		// Get our settings from the GF admin page.
		$add_on   = Gf_Nus_Addon::get_instance();
		$settings = $add_on->get_plugin_settings();

		if ( empty( $settings['blocked_domains'] ) ) {
			return $feeds;
		}

		// Make an array from our csv values via admin settings.
		$blocked_domains = array_filter( explode( ',', $settings['blocked_domains'] ) );

		$email = $this->get_value_by_label( $form, $entry, 'Email Address' );
		$email = trim( $email );

		if ( ! empty( $blocked_domains ) && ! empty( $email ) ) {
			// Run through each value in the array.
			foreach ( $blocked_domains as $blocked_domain ) {
				// If the value of the email field matches the value in our blocked domains array.
				if ( false !== strpos( $email, $blocked_domain ) ) {
					// Still submit to WordPress and show thank you page, but don't perform webhook (sneaky).
					return [];
				}
			}
		}

		return $feeds;
	}

	/**
	 * Check for data corresponding to the zipcode
	 *
	 * Note: "wp_send_json_error" and "wp_send_json_success" print and then die
	 * so no need for the else statement (nor an extra die call).
	 *
	 * @return void
	 */
	public function zip_lookup() {
		// Make sure the value is in the request.
		if ( empty( $_POST['zipValue'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wp_send_json_error( 'No zipcode provided.' );
		}

		global $wpdb;

		$zip_value = sanitize_text_field( wp_unslash( $_POST['zipValue'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$result    = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			'SELECT * FROM `%1$s` WHERE zipcode = %2$s', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder
			'wp_zip_data', // Table is top level (not site ID dependent) so not using the wpdb->prefix concatenation.
			$zip_value
		), ARRAY_A );

		// If the zipcode was actually not found (the table only stores actual existing zipcodes).
		if ( ! $result ) {
			wp_send_json_error( 'The zipcode was not found.' );
		}

		// If the result didn't have state data.
		if ( empty( $result['state'] ) ) {
			wp_send_json_error( 'Invalid zipcode provided.' );
		}

		wp_send_json_success( $result );
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
