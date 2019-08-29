<?php
/**
 * Our custom Phone input Field
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Creates custom Phone input field
 */
class Nu_Phone_Field extends GF_Field_Text {
	/**
	 * The field type
	 *
	 * @var string $type
	 */
	public $type = 'nu_phone';

	/**
	 * Register hooks.
	 */
	public function add_hooks() {
		add_action( 'gform_editor_js_set_default_values', [ $this, 'set_default_values' ] );
		add_action( 'gform_addon_pre_process_feeds', [ $this, 'stop_spam' ], 10, 3 );
		add_filter( 'gform_field_content', [ $this, 'custom_html' ], 10, 5 );
		add_filter( 'gform_field_container', [ $this, 'custom_field_container' ], 10, 6 );
		add_filter( 'gform_field_validation', [ $this, 'validate_field' ], 10, 4 );
	}

	/**
	 * Return the field title, for use in the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'NU Phone', 'national-university' );
	}

	/**
	 * Define the values/display field
	 *
	 * @return void
	 */
	public static function set_default_values() {
		?>
		case "nu_phone" :
			field.label = "Phone";
			field.isRequired = true;
			field.description = "Numbers only. e.g. 8885551212";
			break;
		<?php
	}

	/**
	 * Stop webhooks if phone number is in our list of phone numbers to avoid.
	 *
	 * @param array $feeds An array of $feed objects.
	 * @param array $entry Current entry for which feeds will be processed.
	 * @param array $form Current form object.
	 *
	 * @return mixed An array of $feeds or kill the process.
	 */
	public function stop_spam( $feeds, $entry, $form ) {
		if ( ! empty( $feeds ) ) {
			$nu_phone_field_id = $this->get_field_id( $form, 'nu_phone' );

			// Make sure this field type exists.
			if ( false !== $nu_phone_field_id ) {
				$nu_phone_value  = rgpost( 'input_' . $nu_phone_field_id );
				$blocked_numbers = [
					'5551212',
				];

				foreach ( $blocked_numbers as $blocked_number ) {
					if ( false !== strpos( $nu_phone_value, $blocked_number ) ) {
						// Blank out the feeds so the entry does not get sent anywhere except our DB.
						$feeds = [];
						break;
					}
				}

				// We consider the phone number spam if the first three numbers are the same.
				// Note: Empty check for $feeds is here again because it might have been blanked out above.
				if ( ! empty( $feeds ) && preg_match( '/^(\d)\1{2}/', $nu_phone_value ) ) {
					$feeds = [];
				}
			}
		}

		return $feeds;
	}

	/**
	 * Create custom HTML form gravity forms
	 *
	 * Make the form output fit to our layout/standards
	 *
	 * @param string $field_content Markup of the field provided by GF.
	 * @param object $field         GF object with info about the field.
	 * @param string $value         Value of the input.
	 * @param int    $random        Unused parameter? Actual plugin has no documentation on this parameter.
	 * @param int    $form_id       Field's parent form ID.
	 */
	public function custom_html( $field_content, $field, $value, $random = 0, $form_id ) {
		// If is in the admin, leave it be.
		if ( is_admin() || 'nu_phone' !== $field->type ) {
			return $field_content;
		}

		$content   = '';
		$aria_desc = '';

		// If field is set to require in admin add our required html so Gravity Forms knows what to do.
		$required_aria = ( true === $field->isRequired ) ? 'aria-required="true"' : 'aria-required="false"'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
		$required      = ( true === $field->isRequired ) ? '<span class="required-label">*</span>' : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		// Get our input ID to use throughout.
		$name = 'input_' . esc_attr( $field->id );

		if ( $field->description ) {
			// If field has a description FOR SCREEN READERS.
			$content .= '<span class="form__description sr-only" id="' . $name . '_desc">Instructions for ' . $field->label . ' input: ' . $field->description . '</span>';
			// If field has a description, use it as the aria description as well.
			$aria_desc = ' aria-describedby="' . $name . '_desc"';
		}

		$content .= '<label class="form__label" for="input_' . $form_id . '_' . $field->id . '">' . $field->label . $required . '</label>';

		$content .= '<input' . $aria_desc . '';
		$content .= ' class="input input--text input--styled"';
		$content .= ' type="text" ';
		$content .= 'name="' . $name . '"' . $required_aria . ' ';
		$content .= 'value="' . esc_attr( $value ) . '" ';
		$content .= 'id="input_' . $form_id . '_' . $field->id . '"';
		$content .= ' autocomplete="tel">';

		// If field has a description.
		if ( $field->description ) {
			// Then lets show it.
			$content .= '<span class="form__description" aria-hidden="true">' . $field->description . '</span>';
		}

		return $content;
	}

	/**
	 * Add default classes to input containers
	 *
	 * Setup some default styling so manual entry isn't necessary
	 *
	 * @param string $field_container The field container's markup.
	 * @param object $field           The GF field object with info.
	 * @param array  $form            The current GF form data.
	 * @param string $css_class       Class list for the field container.
	 * @param string $style           Style attribute text.
	 * @param string $field_content   Full field content, including the label.
	 */
	public static function custom_field_container( $field_container, $field, $form, $css_class, $style, $field_content ) {
		if ( 'nu_phone' !== $field->type ) {
			return $field_container;
		}

		// Get the ID of our field.
		$id = $field->id;

		// Empty content variable.
		$custom_classes = '';

		// If we have a description, set our class as such.
		if ( ! empty( $field->description ) ) {
			$custom_classes .= 'has-desc ';
		}

		$custom_classes .= 'form__group--phone';

		// Setup how our field_id is displayed.
		$field_id = is_admin() || empty( $form ) ? "field_{$id}" : 'field_' . $form['id'] . "_$id";

		// Create our new <li>.
		return '<li id="' . $field_id . '" class="form__group ' . $custom_classes . ' ' . $css_class . '">{FIELD_CONTENT}</li>';
	}

	/**
	 * Makes sure value is 10 digits with no special chars or spaces
	 *
	 * @param array  $result The result of the validation.
	 * @param string $value  Incoming value of the field.
	 * @param array  $form   Full data and information about the current form.
	 * @param object $field  The GForm field information.
	 */
	public function validate_field( $result, $value, $form, $field ) {
		if ( 'nu_phone' === $field->type || 'field__phone-number' === $field->cssClass ) {
			/**
			 * A few things are happening here:
			 * - If there is no country-code field, we are assuming this is only for USA and should match 10 digits.
			 * - If it does exist in the form, if it's USA then has to be 10 digits.
			 * - If it exists but not USA then it should be at least 5 (like the original regex states).
			 */

			// Get the country code if available.
			$country_code_id    = $this->get_field_id( $form, 'country-code' );
			$country_code_value = rgpost( 'input_' . $country_code_id ); // Returns empty string if the ID didn't exist.

			if ( false !== $country_code_id && '' !== $country_code_value ) {
				if ( '1' === $country_code_value && ! preg_match( '/(^\d{10}$|^$)/', $value ) ) {
					$result['is_valid'] = false;
					$result['message']  = 'Please enter 10 digits.';
				} elseif ( ! preg_match( '/^\d{5,}$|^$/', $value ) ) {
					$result['is_valid'] = false;
					$result['message']  = 'Please enter a valid phone number.';
				}
			} elseif ( ! preg_match( '/(^\d{10}$|^$)/', $value ) ) {
				$result['is_valid'] = false;
				$result['message']  = 'Please enter a valid phone number.';
			}

			// Decide if a specific message should be used.
			if ( false === $result['is_valid'] && preg_match( '/[^0-9]/', $value ) ) {
				$result['message'] = 'No special characters allowed';
			}
		}

		return $result;
	}

	/**
	 * Gets the field ID from the type
	 *
	 * @param array  $form  GF data of form.
	 * @param string $value Value we are trying to find inside the form.
	 * @param string $key   The type we are trying to find.
	 */
	private function get_field_id( $form, $value, $key = 'type' ) {
		foreach ( $form['fields'] as $field ) {
			if ( strtolower( $field->$key ) === strtolower( $value ) ) {
				return $field->id;
			}
		}
		return false;
	}
}
