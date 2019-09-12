<?php
/**
 * Our custom Zip input Field
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Creates custom Zip input field
 */
class Nu_Zip_Field extends GF_Field_Text {
	/**
	 * The field type
	 *
	 * @var string $type
	 */
	public $type = 'nu_zip';

	/**
	 * Register hooks.
	 */
	public function add_hooks() {
		add_action( 'gform_editor_js_set_default_values', [ $this, 'set_default_values' ] );
		add_action( 'gform_pre_submission', [ $this, 'add_state' ] );
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
		return esc_attr__( 'Zip Code', 'national-university' );
	}

	/**
	 * Assign the field button to the Custom Fields group.
	 *
	 * @return array
	 */
	public function get_form_editor_button() {
		return [
			'group' => 'nu_fields',
			'text'  => $this->get_form_editor_field_title(),
		];
	}

	/**
	 * Define the values/display field
	 *
	 * @return void
	 */
	public static function set_default_values() {
		?>
		case "nu_zip" :
			field.label = "Zip Code";
			field.isRequired = true;
			field.description = "International? Enter '00000'";
			break;
		<?php
	}

	/**
	 * Add state to fields
	 *
	 * @param array $form Form currently being processed.
	 *
	 * @return void
	 */
	public function add_state( $form ) {
		$zipcode_id = $this->get_field_id( $form, 'nu_zip', 'type' );
		$zipcode_id = ! empty( $zipcode_id ) ? $zipcode_id : $this->get_field_id( $form, 'Zip Code', 'label' );
		$state_id   = $this->get_field_id( $form, 'state', 'label' );

		// We need both IDs so we can fetch the zipcode from the submission and set the state.
		if ( false !== $zipcode_id && false !== $state_id ) {
			// If the form has a zipcode field, fetch it,
			// then try to get the state with the USPS API and add state to form submission if found.
			$zipcode = rgpost( 'input_' . $zipcode_id );

			if ( '00000' !== $zipcode ) {
				$fetch_url  = 'https://secure.shippingapis.com/ShippingApi.dll?API=CityStateLookup&XML=';
				$fetch_url .= '<CityStateLookupRequest USERID="951NATIO1026"><ZipCode ID="0"><Zip5>' . $zipcode . '</Zip5></ZipCode></CityStateLookupRequest>';
				$response   = vip_safe_wp_remote_get( $fetch_url, '', 3, 3 );

				if ( is_array( $response ) ) {
					$response_body  = $response['body'];
					$response_xml   = simplexml_load_string( $response_body );
					$response_json  = wp_json_encode( $response_xml );
					$response_array = json_decode( $response_json, true );

					if ( ! empty( $response_array['ZipCode'] ) && ! empty( $response_array['ZipCode']['State'] ) ) {
						$_POST[ 'input_' . $state_id ] = $response_array['ZipCode']['State'];
					}
				}
			}
		}
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
		if ( is_admin() || 'nu_zip' !== $field->type ) {
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

		// Define the html for our input's label.
		$label_class = 'form__label' . ( 'hidden_label' === $field->labelPlacement ? ' sr-only' : '' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
		$content    .= '<label class="' . $label_class . '" for="input_' . $form_id . '_' . $field->id . '">' . $field->label . $required . '</label>';

		$placeholder = ! empty( $field->placeholder ) ? ' placeholder="' . $field->placeholder . '"' : '';
		$content    .= sprintf(
			'<input type="text" class="input input--text input--styled" name="%1$s" value="%2$s" id="input_%3$s_%4$s" autocomplete="postal-code"%5$s%6$s>',
			$name,
			esc_attr( $value ),
			$form_id,
			$field->id,
			$required_aria,
			$placeholder
		);

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
		if ( 'nu_zip' !== $field->type ) {
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

		$custom_classes .= 'form__group--zip';

		// Setup how our field_id is displayed.
		$field_id = is_admin() || empty( $form ) ? "field_{$id}" : 'field_' . $form['id'] . "_$id";

		// Create our new <li>.
		return '<li id="' . $field_id . '" class="' . $css_class . ' ' . $custom_classes . '">{FIELD_CONTENT}</li>';
	}

	/**
	 * Makes sure value is 5 digits with no special chars or spaces
	 *
	 * @param array  $result The result of the validation.
	 * @param string $value  Incoming value of the field.
	 * @param array  $form   Full data and information about the current form.
	 * @param object $field  The GForm field information.
	 */
	public function validate_field( $result, $value, $form, $field ) {
		$pattern = '/^\d{5}$/';
		if ( ! is_array( $value ) && 'nu_zip' === $field->type && ! preg_match( $pattern, $value ) ) {
			$result['is_valid'] = false;
			$result['message']  = 'Please enter a valid Zip';
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
