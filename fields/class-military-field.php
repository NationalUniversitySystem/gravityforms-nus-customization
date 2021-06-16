<?php
/**
 * Our custom Military Field
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Hooks to set defaults and prepend the code are at bottom of file.
 * Creates custom Military field
 */
class Military_Field extends GF_Field_Checkbox {
	/**
	 * The field type
	 *
	 * @var string $type
	 */
	public $type = 'military';

	/**
	 * Register hooks.
	 */
	public function add_hooks() {
		add_action( 'gform_editor_js_set_default_values', [ $this, 'set_default_values' ] );
		add_filter( 'gform_field_container', [ $this, 'custom_field_container' ], 10, 6 );
	}

	/**
	 * Return the field title, for use in the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'Military', 'national-university' );
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
		case "military" :
			field.label = "Military Checkbox";
			field.isRequired = false;
			field.enableChoiceValue = true;
			field.choices = new Array(new Choice(<?php echo wp_json_encode( wp_kses_post( 'Yes, I’m affiliated with the U.S. Military' ) ); ?>, 'YES_MIL_RFI') );
			field.inputs = new Array();
			for ( var i = 1; i <= field.choices.length; i++ ) {
				field.inputs.push(new Input(field.id + (i / 10), field.choices[i - 1].text));
			}
			break;
		<?php
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
		if ( 'military' !== $field->type ) {
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

		$custom_classes .= 'form__group--military form__group--tooltip';
		$tooltip_text    = 'es' === get_locale() ? 'Marque esta casilla si se encuentra en servicio activo, veterano, guardia, reserva o cónyuge/dependiente.' : 'Check this box if you are active duty, veteran, guard, reserve, or a spouse/dependent';
		$tooltip         = '<span tabindex="0" data-tool="#military-tooltip" data-placement="top" data-toggle="tooltip" title="' . $tooltip_text . '"><span class="sr-only">' . $tooltip_text . '</span><span class="icon icon--question-circle" aria-hidden="true"></span></span>';

		// Setup how our field_id is displayed.
		$field_id = is_admin() || empty( $form ) ? "field_{$id}" : 'field_' . $form['id'] . "_$id";

		// Create our new <li>.
		return '<li id="' . $field_id . '" class="form__group ' . $custom_classes . ' ' . $css_class . '">{FIELD_CONTENT}' . $tooltip . '</li>';
	}

	/**
	 * Kill the tabindex of the field so flow is natural
	 *
	 * @return string
	 */
	public function get_tabindex() {
		return '';
	}
}
