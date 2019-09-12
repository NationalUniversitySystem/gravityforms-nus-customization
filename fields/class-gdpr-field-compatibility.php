<?php
/**
 * Our custom GDPR checkbox field as a backwards compatibility for old versions of
 * this plugin and Gravity Forms plugin.
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Version 2.4 included a "Consent" Field.
 * This class adds compatibility for our previous method of implementing such functionality.
 */
if ( class_exists( 'GF_Field_Consent' ) ) {
	return;
}

/**
 * Basically recreate the new "consent" field type if it does not exist.
 *
 * If "consent" field type is not available, we are basically recreating the new consent field.
 * If the "consent" field type exists, extend it to use our old markup and so old data does not
 */
class Gdpr_Field_Compatibility extends GF_Field_Checkbox {
	/**
	 * The field type
	 *
	 * @var string $type
	 */
	public $type = 'gdpr';

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
		return esc_attr__( 'GDPR', 'national-university' );
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
	 * The settings which should be available on the field in the form editor.
	 *
	 * @return array
	 */
	public function get_form_editor_field_settings() {
		return [
			'conditional_logic_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'checkbox_label_setting',
			'rules_setting',
			'visibility_setting',
			'placeholder_setting',
			'description_setting',
			'css_class_setting',
		];
	}

	/**
	 * Define the values/display field
	 *
	 * @return void
	 */
	public static function set_default_values() {
		$label_string = 'By submitting this form, I agree to the <a href="#terms-modal" class="modal-launch" title="Terms &amp; Conditions">terms &amp; conditions.</a>';
		?>
		case "gdpr" :
			field.label = "GDPR Checkbox";
			field.isRequired = true;
			field.enableChoiceValue = true;
			field.choices = new Array(
				new Choice( '<?php echo wp_kses_post( $label_string ); ?>', "optIn" ),
			);
			field.inputs = null;
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
		if ( is_admin() || 'gdpr' !== $field->type ) {
			return $field_content;
		}

		// Create var to modify/use down the page.
		$content = null;

		// If field is set to require in admin add our required html so Gravity Forms knows what to do.
		$required = ( true === $field->isRequired ) ? '<span class="required-label">*</span>' : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		// Get our input ID to use throughout.
		$name = 'input_' . esc_attr( $field->id );

		// Define the html for our input's label.
		$content = '<label class="form__label--checkbox" for="choice_' . $form_id . '_' . $field->id . '_1">';
		// Define the html for the input itself.
		$content .= '<input type="checkbox" ';
		$content .= 'class="input input--checkbox input--gdpr"';
		$content .= 'name="' . $name . '"  value="optIn" ';
		$content .= 'id="choice_' . $form_id . '_' . $field->id . '_1">';
		$content .= $field->choices[0]['value'] . $required;
		$content .= '</label>';

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
		if ( 'gdpr' !== $field->type ) {
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

		$custom_classes .= 'form__group--gdpr';

		// Setup how our field_id is displayed.
		$field_id = is_admin() || empty( $form ) ? "field_{$id}" : 'field_' . $form['id'] . "_$id";

		// Create our new <li>.
		return '<li id="' . $field_id . '" class="' . $custom_classes . ' ' . $css_class . '">{FIELD_CONTENT}</li>';
	}
}
