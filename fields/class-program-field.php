<?php
/**
 * Our custom Program select Field
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Creates custom Program select field
 */
class Program_Field extends GF_Field_Select {
	/**
	 * The field type
	 *
	 * @var string $type
	 */
	public $type = 'program';

	/**
	 * Register hooks.
	 */
	public function add_hooks() {
		add_action( 'gform_editor_js_set_default_values', [ $this, 'set_default_values' ] );
		add_filter( 'gform_field_content', [ $this, 'custom_html' ], 10, 5 );
		add_filter( 'gform_field_container', [ $this, 'custom_field_container' ], 10, 6 );
	}

	/**
	 * Return the field title, for use in the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'Program', 'national-university' );
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
		case "program" :
			field.label = "Degree Program";
			field.isRequired = true;
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
		if ( is_admin() || 'program' !== $field->type ) {
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

		// Create our variable to store our <options>.
		$choices = '<option value="" label=" " selected disabled="disabled"></option>';
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

		$content .= '<select';
		$content .= ' class="input input--select input--styled"';
		$content .= ' name="' . $name . '"';
		$content .= $required_attr;
		$content .= ' id="input_' . $form_id . '_' . $field->id . '">';
		$content .= $choices;
		$content .= '</select>';

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
		if ( 'program' !== $field->type ) {
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

		$custom_classes .= 'program--select';

		// Setup how our field_id is displayed.
		$field_id = is_admin() || empty( $form ) ? "field_{$id}" : 'field_' . $form['id'] . "_$id";

		// Create our new <li>.
		return '<li id="' . $field_id . '" class="form__group ' . $custom_classes . ' ' . $css_class . '">{FIELD_CONTENT}</li>';
	}
}
