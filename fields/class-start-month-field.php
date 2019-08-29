<?php
/**
 * Our custom Start Month select Field
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Creates custom Start Month select field
 */
class Start_Month_Field extends GF_Field_Select {
	/**
	 * The field type
	 *
	 * @var string $type
	 */
	public $type = 'start_month';

	/**
	 * Register hooks.
	 */
	public function add_hooks() {
		add_action( 'gform_editor_js_set_default_values', array( $this, 'set_default_values' ) );
		add_filter( 'gform_field_content', array( $this, 'custom_html' ), 10, 5 );
		add_filter( 'gform_field_container', array( $this, 'custom_field_container' ), 10, 99 );
	}

	/**
	 * Return the field title, for use in the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'NU Start Month', 'national-university' );
	}

	/**
	 * Define the values/display field
	 *
	 * @return void
	 */
	public static function set_default_values() {
		?>
		case "start_month" :
			field.label = "Start Month";
			field.isRequired = true;
			field.enableChoiceValue = true;
			field.choices = new Array(
				new Choice("January", "JAN"),
				new Choice("February", "FEB"),
				new Choice("March", "MAR"),
				new Choice("April", "APR"),
				new Choice("May", "MAY"),
				new Choice("June", "JUNE"),
				new Choice("July", "JULY"),
				new Choice("August", "AUG"),
				new Choice("September", "SEP"),
				new Choice("October", "OCT"),
				new Choice("November", "NOV"),
				new Choice("December", "DEC"),
			)
			break;
		<?php
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
		if ( is_admin() || 'start_month' !== $field->type ) {
			return $field_content;
		}

		$content = '';

		// If field is set to require in admin add our required html so Gravity Forms knows what to do.
		$required_aria = ( true === $field->isRequired ) ? 'aria-required="true"' : 'aria-required="false"'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
		$required      = ( true === $field->isRequired ) ? '<span class="required-label">*</span>' : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		// Get our input ID to use throughout.
		$name = 'input_' . esc_attr( $field->id );

		if ( $field->description ) {
			// If field has a description FOR SCREEN READERS.
			$content .= '<span class="form__description sr-only" id="' . $name . '_desc">Instructions for ' . $field->label . ' input: ' . $field->description . '</span>';
		}

		// Create our variable to store our <options>.
		$choices = '<option value="" selected disabled="disabled"></option>';
		// If the field has choices, loop through them.
		if ( $field->choices ) {
			// Go through all the possible choices assigned to the select in the admin.
			foreach ( $field->choices as $choice ) {
				$selected = $choice['text'] === $value ? ' selected' : '';
				// Add all the <options> to our variable to use below.
				$choices .= '<option value="' . $choice['text'] . '"' . $selected . '>' . $choice['text'] . '</option>';
			}
		}

		$content .= '<label class="form__label" for="input_' . $form_id . '_' . $field->id . '">' . $field->label . $required . '</label>';

		$content .= '<select ';
		$content .= 'class="input input--select input--styled" ';
		$content .= 'name="' . $name . '" ';
		$content .= $required_aria;
		$content .= ' value="' . esc_attr( $value );
		$content .= ' " id="input_' . $form_id . '_' . $field->id . '">';
		$content .= $choices;
		$content .= '</select>';

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
		if ( 'start_month' !== $field->type ) {
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

		$custom_classes .= 'form__group--month';

		// Setup how our field_id is displayed.
		$field_id = is_admin() || empty( $form ) ? "field_{$id}" : 'field_' . $form['id'] . "_$id";

		// Create our new <li>.
		return '<li id="' . $field_id . '" class="form__group ' . $custom_classes . ' ' . $css_class . '">{FIELD_CONTENT}</li>';
	}
}
