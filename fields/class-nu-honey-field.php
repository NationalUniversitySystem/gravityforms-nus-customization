<?php
/**
 * Our custom Honey post input Field to check for bots
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Creates custom Honey Pot input field
 */
class Nu_Honey_Field extends GF_Field_Text {
	/**
	 * The field type
	 *
	 * @var string $type
	 */
	public $type = 'nu_honey';

	/**
	 * Register hooks.
	 */
	public function add_hooks() {
		add_action( 'gform_editor_js_set_default_values', [ $this, 'set_default_values' ] );
		add_action( 'gform_addon_pre_process_feeds', [ $this, 'stop_spam' ], 10, 3 );
		add_filter( 'gform_field_content', [ $this, 'custom_html' ], 10, 5 );
		add_filter( 'gform_field_container', [ $this, 'custom_field_container' ], 10, 6 );
	}

	/**
	 * Return the field title, for use in the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'NU Honeypot', 'national-university' );
	}

	/**
	 * Define the values/display field
	 *
	 * @return void
	 */
	public static function set_default_values() {
		?>
		case "nu_honey" :
			field.label = "Form Email Field";
			field.isRequired = false;
			break;
		<?php
	}

	/**
	 * Stop webhooks if honeypot filled
	 *
	 * @param array $feeds An array of $feed objects.
	 * @param array $entry Current entry for which feeds will be processed.
	 * @param array $form Current form object.
	 *
	 * @return mixed An array of $feeds or kill the process.
	 */
	public function stop_spam( $feeds, $entry, $form ) {
		// Get the value of our honeypot field.
		$honeypot = $this->get_value_by_label( $form, $entry, 'Form Email Field' );
		$honeypot = trim( $honeypot );

		// If the field doesn't exist on the form, or if the field is blank, run our webhooks.
		if ( false === $honeypot || '' === $honeypot ) {
			return $feeds;
		} else {
			// Else do not run any of the feeds.
			die();
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
		if ( is_admin() || 'nu_honey' !== $field->type ) {
			return $field_content;
		}

		$content = '';

		// Get our input ID to use throughout.
		$name = 'input_' . esc_attr( $field->id );

		if ( $field->description ) {
			// If field has a description FOR SCREEN READERS.
			$content .= '<span class="form__description sr-only" id="' . $name . '_desc">Instructions for ' . $field->label . ' input: ' . $field->description . '</span>';
		}

		$content .= '<label class="form__label" for="input_' . $form_id . '_' . $field->id . '">' . $field->label . '</label>';

		$content .= '<input class="input input--text input--styled" type="text" ';
		$content .= 'name="' . $name . '" ';
		$content .= 'value="" id="input_' . $form_id . '_' . $field->id . '" ';
		$content .= 'autocomplete="off">';

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
		if ( 'nu_honey' !== $field->type ) {
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

		$custom_classes .= 'form-email-field';

		// Setup how our field_id is displayed.
		$field_id = is_admin() || empty( $form ) ? "field_{$id}" : 'field_' . $form['id'] . "_$id";

		// Create our new <li>.
		return '<li id="' . $field_id . '" class="form__group ' . $custom_classes . ' ' . $css_class . '">{FIELD_CONTENT}</li>';
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
