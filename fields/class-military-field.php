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
		add_filter( 'gform_field_css_class', [ $this, 'modify_field_container_classes' ], 10, 3 );
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
	 * Add custom class(es) to field
	 *
	 * @param string $css_classes Class list for the field container.
	 * @param object $field       The GF field object with info.
	 * @param array  $form        The current GF form data.
	 *
	 * @return string
	 */
	public function modify_field_container_classes( $css_classes, $field, $form ) {
		// If is in the admin or not this field type, leave it be.
		if ( is_admin() || $this->type !== $field->type ) {
			return $css_classes;
		}

		return $css_classes .= ' form__group--military form__group--tooltip';
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
	public function custom_field_container( $field_container, $field, $form, $css_class, $style, $field_content ) {
		if ( is_admin() || $this->type !== $field->type ) {
			return $field_container;
		}

		$tooltip_text = 'Check this box if you are active duty, veteran, guard, reserve, or a spouse/dependent';
		$tooltip_text = apply_filters( 'gf_nus_military_tooltip_text', $tooltip_text );

		$tooltip_classes = 'icon icon--question-circle';
		$tooltip_classes = apply_filters( 'gf_nus_military_tooltip_classes', $tooltip_classes );

		$tooltip  = '<span data-tool="#military-tooltip" class="icon icon--question-circle"></span>';
		$tooltip .= '<div class="tooltip" id="military-tooltip">' . $tooltip_text . '</div>';

		$tooltip = apply_filters( 'gf_nus_military_tooltip_markup', $tooltip );

		return str_replace( '</li>', $tooltip . '</li>', $field_container );
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
