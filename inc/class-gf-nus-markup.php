<?php
/**
 * Handle markup modifications
 */

/**
 * Gf_Nus_Markup class
 */
class Gf_Nus_Markup {
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
		// Filters.
		// add_filter( 'gform_field_content', [ $this, 'custom_html' ], 10, 5 );
		add_filter( 'gform_field_css_class', [ $this, 'modify_field_container_classes' ], 10, 3 );
		add_filter( 'gform_validation_message', [ $this, 'change_fail_message' ], 10, 2 );
		add_filter( 'gform_field_value_formID', [ $this, 'populate_form_id' ] );

		// Actions.
		add_action( 'gform_enqueue_scripts', [ $this, 'dequeue_gf_scripts' ], 11 );
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
		if ( is_admin() ) {
			return $field_content;
		}

		// Setup our autocomplete values - label values on left, autocomplete values on right.
		$auto_complete_values = [
			'First Name'    => 'given-name',
			'Last Name'     => 'family-name',
			'Name'          => 'name',
			'Email Address' => 'email',
			'Email'         => 'email',
		];

		$name               = 'input_' . esc_attr( $field->id ); // Get our input ID to use throughout.
		$content            = ''; // Create var to modify/use down the page.
		$aria_desc          = ''; // Define aria description var.
		$autocomplete_value = ''; // Setup blank autocomplete default value.

		// If field is set to require in admin dd our required html so Gravity Forms knows what to do.
		$required_aria = ( true === $field->isRequired ) ? ' aria-required="true"' : ' aria-required="false"'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
		$required      = ( true === $field->isRequired ) ? '<span class="required-label">*</span>' : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		// See if our label is in our autocomplete array.
		if ( in_array( $field->label, array_keys( $auto_complete_values ), true ) ) {
			// If it is, then assign the correct autocomplete value.
			$autocomplete_value = ' autocomplete="' . $auto_complete_values[ $field->label ] . '"';
		}

		// If field has a description, use it as the aria description and create markup for screen readers.
		if ( $field->description ) {
			$aria_desc = ' aria-describedby="' . $name . '_desc"';
			$content  .= '<span class="form__description sr-only"  id="' . $name . '_desc">Instructions for ' . $field->label . ' input: ' . $field->description . '</span>';
		}

		// Define the html for our input's label.
		$label_class = 'form__label' . ( 'hidden_label' === $field->labelPlacement ? ' sr-only' : '' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
		$content    .= ! empty( $field->label ) ? '<label class="' . $label_class . '" for="input_' . $form_id . '_' . $field->id . '">' . $field->label . $required . '</label>' : '';

		// Output different html for inputs depending on their type.
		switch ( $field->type ) {
			case 'email':
			case 'text':
				$placeholder = ! empty( $field->placeholder ) ? ' placeholder="' . $field->placeholder . '"' : '';
				$aria_hidden = ! empty( $field->visibility ) && 'hidden' === $field->visibility ? ' aria-hidden="true"' : '';
				$content    .= sprintf(
					'<input type="text" class="input input--text input--styled" name="%1$s" value="%2$s" id="input_%3$s_%4$s"%5$s%6$s%7$s%8$s%9$s>',
					$name,
					esc_attr( $value ),
					$form_id,
					$field->id,
					$aria_desc,
					$required_aria,
					$aria_hidden,
					$autocomplete_value,
					$placeholder
				);
				$content    .= "\n";
				break;
			case 'textarea':
				$placeholder = ! empty( $field->placeholder ) ? ' placeholder="' . $field->placeholder . '"' : '';

				$content .= sprintf(
					'<textarea class="input input--text input--styled" name="%s" value="%s" id="%s" rows="10" cols="50"%s%s%s></textarea>',
					$name,
					esc_textarea( $value ),
					'input_' . $form_id . '_' . $field->id,
					$autocomplete_value,
					$required_aria,
					$placeholder
				);
				break;
			case 'select':
				// Create our variable to store our <options>.
				$choices = '<option value="" selected disabled="disabled">' . $field->placeholder . '</option>';

				// If the field has choices, loop through them.
				if ( $field->choices ) {
					// Go through all the possible choices assigned to the select in the admin.
					foreach ( $field->choices as $choice ) {
						$selected = $choice['text'] === $value ? ' selected' : '';
						$value    = ! empty( $choice['value'] ) ? $choice['value'] : $choice['text'];
						// Add all the <options> to our variable to use below.
						$choices .= '<option value="' . $value . '"' . $selected . '>' . $choice['text'] . '</option>';
					}
				}

				if ( ! $field->enableEnhancedUI ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
					$content .= '<select ';
					$content .= 'class="input input--select input--styled" ';
					$content .= 'name="' . $name . '" ';
					$content .= $required_aria;
					$content .= ' value="' . esc_attr( $value );
					$content .= '" id="input_' . $form_id . '_' . $field->id . '">';
					$content .= $choices;
					$content .= '</select>';
				} else {
					$content .= sprintf(
						'<input class="input input--select input--styled" list="%s" id="%s" name="%s" %s />',
						'input_' . $field->id . '_' . $field->id . '-list',
						'input_' . $field->id . '_' . $field->id,
						$name,
						$required_aria
					);
					$content .= '<datalist id="input_' . $field->id . '_' . $field->id . '-list" >';
					$content .= $choices;
					$content .= '</datalist>';
				}

				break;
			case 'radio':
				$counter    = '0'; // Choice counter.
				$content    = ''; // Setup our variable to put stuff in below.
				$labelclass = ''; // Setup empty variable.

				// If field has a description.
				if ( $field->description ) {
					// Then lets show it.
					$content   .= '<label style="display:block; width: 100%;" class="desc--label">';
					$content   .= $field->description;
					$content   .= '</label>';
					$labelclass = 'class="has--label"';
				}

				$content .= '<fieldset role="group">';
				// Loop through our choices.
				foreach ( $field->choices as $choice ) {
					// Output our choices as html.
					$content .= '<label ' . $labelclass . ' ';
					$content .= 'for="choice_' . $form_id . '_' . $field->id . '_' . $counter . '" '; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
					$content .= 'id="label_' . $form_id . '_' . $field->id . '_' . $counter . '">'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
					$content .= '<input name="input_' . $field->id . '" type="radio" ';
					$content .= 'value="' . $choice['value'] . '" ';
					$content .= 'id="choice_' . $form_id . '_' . $field->id . '_' . $counter . '" '; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
					$content .= 'tabindex="1">' . $choice['text'] . '</label>';
					// Increase our counter.
					$counter++;
				}
				$content .= '</fieldset>';
				break;
			default:
				return $field_content;
		}

		// If field has a description, then lets show it.
		if ( $field->description ) {
			$content .= '<span class="form__description" aria-hidden="true">' . $field->description . '</span>';
		}

		// Return the individual fields/inputs.
		return $content;
	}

	/**
	 * Modify the string holding the classes for the container/wrapper of the field
	 * - Natively the container/wrapper is an li
	 *
	 * @param string $css_classes Class list for the field container.
	 * @param object $field       The GF field object with info.
	 * @param array  $form        The current GF form data.
	 *
	 * @return string
	 */
	public function modify_field_container_classes( $css_classes, $field, $form ) {
		// We don't need to modify the admin classes.
		if ( is_admin() ) {
			return $css_classes;
		}

		// Default class for container.
		$css_classes .= ' form__group';

		// Always add the label as a class.
		$css_classes .= ' ' . str_replace( ' ', '_', strtolower( $field->label ) );

		// If we have a description, add class to state it.
		if ( $field->description ) {
			$css_classes .= ' has-desc';
		}

		switch ( $field->type ) {
			case 'select':
				$css_classes .= ' form__group--select';
				break;
			case 'radio':
				$css_classes .= ' form__group--radios';
				break;
			case 'checkbox':
				$css_classes .= ' form__group--checkbox';
				break;
		}

		return $css_classes;
	}

	/**
	 * Update failed form submission message
	 *
	 * Adds aria-alert so screen readers will know something bad happened on failed submit
	 */
	public function change_fail_message( $message, $form ) {
		return str_replace( ' class', '  role="alert" aria-atomic="true" class', $message );
	}

	/**
	 * Dynamically populate the formID field.
	 * This will populate the field only if the option to populate dynamically is checked.
	 *
	 * @param string $value Value passed into the hook.
	 * @return string
	 */
	public function populate_form_id( $value ) {
		// Only populate value if there was not one preset
		// - Incase someone wants to have something other than the ID due to forms compatibility.
		if ( empty( $value ) ) {
			global $wp;
			$value = esc_html( trailingslashit( home_url( $wp->request ) ) );
		}

		return $value;
	}

	/**
	 * Remove Gravity Forms stylesheets and unnecessary scripts
	 *
	 * Gets rid of the nasty default css by removing the stylesheet and the datalist chosen JS.
	 */
	public function dequeue_gf_scripts() {
		if ( ! is_admin() ) {
			wp_dequeue_style( 'gforms_formsmain_css' );
			wp_dequeue_style( 'gforms_browsers_css' );
			wp_dequeue_script( 'gform_chosen' );
		}
	}
}
