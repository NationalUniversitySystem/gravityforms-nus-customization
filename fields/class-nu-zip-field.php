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
		add_action( 'gform_pre_submission', [ $this, 'add_state' ], 10, 1 );
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
			// Fetch both zipcode and state fields.
			// If state is empty somehow (should have been filled in the front-end)
			// Then try to get the state with the USPS API and add state to form submission if found.
			// If the USPS API fails, use the WP DB values as a fallback.
			$zipcode = rgpost( 'input_' . $zipcode_id );
			$state   = rgpost( 'input_' . $state_id );

			if ( '00000' !== $zipcode && empty( $state ) ) {
				$api_response = $this->fetch_usps_api( $zipcode );

				if ( ! is_wp_error( $api_response ) ) {
					$_POST[ 'input_' . $state_id ] = $api_response['state'];

					return;
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

		$content = '';

		// If field is set to require in admin add our required html so Gravity Forms knows what to do.
		$required_attr = ( true === $field->isRequired ) ? ' required' : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$required      = ( true === $field->isRequired ) ? '<span class="required-label">*</span>' : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		// Get our input ID to use throughout.
		$name = 'input_' . esc_attr( $field->id );

		if ( $field->description ) {
			// If field has a description FOR SCREEN READERS.
			$content .= '<span class="form__description sr-only">Instructions for ' . $field->label . ' input: ' . $field->description . '</span>';
		}

		$aria_describedby = $name . '_desc';

		// Define the html for our input's label.
		$label_class = 'form__label' . ( 'hidden_label' === $field->labelPlacement ? ' sr-only' : '' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$content    .= '<label class="' . $label_class . '" for="input_' . $form_id . '_' . $field->id . '">' . $field->label . $required . '</label>';

		$placeholder = ! empty( $field->placeholder ) ? ' placeholder="' . $field->placeholder . '"' : '';
		$content    .= sprintf(
			'<input type="text" class="input input--text input--styled" name="%1$s" value="%2$s" id="input_%3$s_%4$s" autocomplete="postal-code"%5$s%6$s aria-describedby="%7$s">',
			$name,
			esc_attr( $value ),
			$form_id,
			$field->id,
			$required_attr,
			$placeholder,
			$aria_describedby
		);

		// If field has a description.
		if ( $field->description ) {
			// Then lets show it.
			$content .= '<span class="form__description" id="' . $name . '_desc" aria-hidden="true">' . $field->description . '</span>';
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
		return '<li id="' . $field_id . '" class="form__group ' . $custom_classes . ' ' . $css_class . '">{FIELD_CONTENT}</li>';
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

		/**
		 * Insight:
		 * - In order to run our checks for this field we need the following:
		 * -- has to be a string
		 * -- has to be either 'nu_zip' field type OR has the class 'nus-live-validation--zip'
		 * -- must not be the the value '00000' (international zip value)
		 */
		if (
			is_string( $value )
			&& (
				'nu_zip' === $field->type
				|| false !== strpos( $field->cssClass, 'nus-live-validation--zip' ) // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			)
			&& '00000' !== $value
		) {
			if ( 5 !== strlen( $value ) || ! preg_match( $pattern, $value ) ) {
				$result['is_valid'] = false;
				$result['message']  = 'Please enter a valid Zip';
			} else {
				$api_response = $this->fetch_usps_api( $value );

				if ( is_wp_error( $api_response ) ) {
					$result['is_valid'] = false;
					$result['message']  = $api_response->get_error_message();
				}
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

	/**
	 * Fetch a response from the USPS API with the zipcode as the value to check against.
	 *
	 * @param string $zipcode Value to search in the API.
	 * @return WP_Error|Array
	 */
	private function fetch_usps_api( $zipcode ) {
		$fetch_url  = 'https://secure.shippingapis.com/ShippingApi.dll?API=CityStateLookup&XML=';
		$fetch_url .= '<CityStateLookupRequest USERID="951NATIO1026"><ZipCode ID="0"><Zip5>' . $zipcode . '</Zip5></ZipCode></CityStateLookupRequest>';
		$response   = vip_safe_wp_remote_get( $fetch_url, '', 3, 3 );

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$response_body  = wp_remote_retrieve_body( $response );
			$response_xml   = simplexml_load_string( $response_body );
			$response_json  = wp_json_encode( $response_xml );
			$response_array = $this->array_change_key_case_recursive( json_decode( $response_json, true ) );

			if ( ! empty( $response_array['zipcode'] ) && ! empty( $response_array['zipcode']['state'] ) ) {
				$response = $response_array['zipcode'];
			} elseif ( isset( $response_array['zipcode']['error'], $response_array['zipcode']['error']['description'] ) ) {
				$response = new WP_Error( 'error', $response_array['zipcode']['error']['description'] );
			}
		} else {
			$response = $this->zip_db_lookup( $zipcode );
		}

		return $response;
	}

	/**
	 * Check for data corresponding to the zipcode
	 *
	 * @param string $zip_value The zipcode to search for in the DB.
	 *
	 * @return WP_Error|Array
	 */
	private function zip_db_lookup( $zip_value ) {
		global $wpdb;

		$result = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			'SELECT * FROM `%1$s` WHERE zipcode = %2$s', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder
			'wp_zip_data', // Table is top level (not site ID dependent) so not using the wpdb->prefix concatenation.
			$zip_value
		), ARRAY_A );

		// If the zipcode was actually not found (the table only stores actual existing zipcodes).
		if ( ! $result ) {
			$result = new WP_Error( 'empty', 'The zipcode was not found.' );
		} elseif ( empty( $result['state'] ) ) { // If the result didn't have state data.
			$result = new WP_Error( 'invalid', 'Invalid zipcode provided.' );
		}

		return $result;
	}

	/**
	 * Utility function to lower case keys in a multidimensional array
	 *
	 * @param array $array The multidimensional array in question.
	 * @param const $case Lower or upper case (CASE_UPPER|CASE_LOWER).
	 *
	 * @return array
	 */
	private static function array_change_key_case_recursive( $array, $case = CASE_LOWER ) {
		return array_map( function( $item ) use ( $case ) {
			if ( is_array( $item ) ) {
				$item = self::array_change_key_case_recursive( $item, $case );
			}
			return $item;
		}, array_change_key_case( $array, $case ) );
	}
}
