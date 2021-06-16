<?php
/**
 * Our custom GDPR checkbox field for Gravity Forms
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

if ( class_exists( 'GF_Field_Consent' ) ) {
	/**
	 * Using the "consent" field type if available so our previously created forms have something to display/work with.
	 * Version 2.4.6 of Gravity Forms also broke the custom GDPR field with the choices so this one makes old data work with the new versions.
	 *
	 * This field should not be used!
	 * Will be deprecated in a future release.
	 * Use the "Consent" field appropriately.
	 */
	class Gdpr_Field extends GF_Field_Consent {
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
			add_filter( 'gform_field_css_class', [ $this, 'custom_container_class' ], 10, 3 );
			add_action( 'gform_editor_js_set_default_values', [ $this, 'set_default_values' ] );
			add_filter( 'gform_field_validation', [ $this, 'validate_field' ], 10, 4 );
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
		 * Returns the field inner markup.
		 *
		 * @since 2.4
		 *
		 * @param array      $form  The Form Object currently being processed.
		 * @param array      $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
		 * @param null|array $entry Null or the Entry Object currently being edited.
		 *
		 * @return string
		 */
		public function get_field_input( $form, $value = [], $entry = null ) {
			// Determine if this is an ancient incompatible entry/field.
			$incompatible_field = false;
			if ( empty( $this->inputs ) && ! empty( $this->choices[0] ) && ! empty( $this->choices[0]['text'] ) ) {
				$incompatible_field = true;
			}

			$is_entry_detail = $this->is_entry_detail();
			$is_form_editor  = $this->is_form_editor();
			$is_admin        = $is_form_editor || $is_entry_detail;

			$html_input_type = 'checkbox';

			$id                 = (int) $this->id;
			$disabled_text      = $is_form_editor ? 'disabled="disabled"' : '';
			$required_attribute = $this->isRequired ? 'required' : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$invalid_attribute  = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';

			$target_input_id       = GF_Field::get_first_input_id( $form );
			$for_attribute         = empty( $target_input_id ) ? '' : "for='{$target_input_id}'";
			$label_class_attribute = $is_admin ? 'class="gfield_consent_label"' : 'class="form__label--checkbox"';

			if ( $is_admin && 'hidden_label' === $this->labelPlacement ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$required_div = sprintf( "<span class='gfield_required'>%s</span>", $this->isRequired ? '*' : '' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			} else {
				$required_div = $this->isRequired ? '<span class="required-label">*</span>' : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}

			if ( $is_admin && ! GFCommon::is_entry_detail_edit() ) {
				$checkbox_label = ! is_array( $value ) || empty( $value[ $id . '.2' ] ) ? $this->checkboxLabel : $value[ $id . '.2' ]; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$checkbox_label = ! $incompatible_field ? $checkbox_label : $this->choices[0]['text'];
				$revision_id    = ! is_array( $value ) || empty( $value[ $id . '.3' ] ) ? GFFormsModel::get_latest_form_revisions_id( $form['id'] ) : $value[ $id . '.3' ];
				$value          = ! is_array( $value ) || empty( $value[ $id . '.1' ] ) ? '0' : esc_attr( $value[ $id . '.1' ] );
			} else {
				// Backwards compatibility until all our fields have been updated.
				$checkbox_label = ! $incompatible_field ? $this->checkboxLabel : $this->choices[0]['text']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

				$revision_id = GFFormsModel::get_latest_form_revisions_id( $form['id'] );
				// We compare if the description text from different revisions has been changed.
				$current_description   = $this->get_field_description_from_revision( $revision_id );
				$submitted_description = ! $incompatible_field ? $this->get_field_description_from_revision( $value[ $id . '.3' ] ) : '';

				$value = ! is_array( $value ) || empty( $value[ $id . '.1' ] ) || ( $this->checkboxLabel !== $value[ $id . '.2' ] ) || ( $current_description !== $submitted_description ) ? '0' : esc_attr( $value[ $id . '.1' ] ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}
			$checked = $is_form_editor ? '' : checked( '1', $value, false );

			$aria_describedby = '';
			$description      = $is_entry_detail ? $this->get_field_description_from_revision( $revision_id ) : $this->description;
			if ( ! empty( $description ) ) {
				$aria_describedby = "aria-describedby='gfield_consent_description_{$form['id']}_{$this->id}'";
			}

			$input_value   = ! $incompatible_field ? '1' : 'optIn';
			$input_classes = 'input input--checkbox input--gdpr';

			if ( ! $incompatible_field ) {
				$input = "<input name='input_{$id}.1' id='{$target_input_id}' type='{$html_input_type}' class='{$input_classes}' value='{$input_value }' {$aria_describedby} {$required_attribute} {$invalid_attribute} {$disabled_text} {$checked} /><label {$label_class_attribute} {$for_attribute} >{$checkbox_label}{$required_div}</label>";
			} else {
				// Old setup so that the value passes through to validation correctly.
				$input  = '<label class="form__label--checkbox" for="choice_' . $form['id'] . '_' . $this->id . '_1">';
				$input .= '<input type="checkbox"';
				$input .= ' class="' . $input_classes . '"';
				$input .= ' name="input_' . esc_attr( $this->id ) . '"  value="optIn"';
				$input .= ' id="choice_' . $form['id'] . '_' . $this->id . '_1">';
				$input .= $this->choices[0]['value'] . $required_div;
				$input .= '</label>';
			}

			$input .= "<input type='hidden' name='input_{$id}.2' value='" . esc_attr( $checkbox_label ) . "' class='gform_hidden' />";
			$input .= "<input type='hidden' name='input_{$id}.3' value='" . esc_attr( $revision_id ) . "' class='gform_hidden' />";

			if ( $is_entry_detail ) {
				$input .= $this->get_description( $this->get_field_description_from_revision( $revision_id ), '' );
			}

			return sprintf( "<div class='ginput_container ginput_container_consent'>%s</div>", $input );
		}

		/**
		 * Returns the field markup; including field label, description, and the form editor admin buttons.
		 *
		 * Basically the same method from GF_Field but without the validation message inline since we are trying to display it above the form.
		 *
		 * The {FIELD} placeholder will be replaced in GFFormDisplay::get_field_content with the markup returned by GF_Field::get_field_input().
		 *
		 * @param string|array $value                The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
		 * @param bool         $force_frontend_label Should the frontend label be displayed in the admin even if an admin label is configured.
		 * @param array        $form                 The Form Object currently being processed.
		 *
		 * @return string
		 */
		public function get_field_content( $value, $force_frontend_label, $form ) {
			$field_label = $this->get_field_label( $force_frontend_label, $value );

			$is_form_editor  = $this->is_form_editor();
			$is_entry_detail = $this->is_entry_detail();
			$is_admin        = $is_form_editor || $is_entry_detail;
			$required_class  = $is_admin ? 'gfield_required' : 'required-label';

			$required_div = $is_admin || $this->isRequired ? sprintf( "<span class='%s'>%s</span>", $required_class, $this->isRequired ? '*' : '' ) : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			$admin_buttons = $this->get_admin_buttons();

			$target_input_id = $this->get_first_input_id( $form );

			$for_attribute = empty( $target_input_id ) ? '' : "for='{$target_input_id}'";

			$description = $this->get_description( $this->description, 'gfield_description' );
			if ( $this->is_description_above( $form ) ) {
				$clear         = $is_admin ? "<div class='gf_clear'></div>" : '';
				$field_content = sprintf( "%s<label class='%s' $for_attribute >%s%s</label>%s{FIELD}$clear", $admin_buttons, esc_attr( $this->get_field_label_class() ), esc_html( $field_label ), $required_div, $description );
			} else {
				$field_content = sprintf( "%s<label class='%s' $for_attribute >%s%s</label>{FIELD}%s", $admin_buttons, esc_attr( $this->get_field_label_class() ), esc_html( $field_label ), $required_div, $description );
			}

			return $field_content;
		}

		/**
		 * Format the entry value for display on the entries list page.
		 *
		 * @param string|array $value    The field value.
		 * @param array        $entry    The Entry Object currently being processed.
		 * @param string       $field_id The field or input ID currently being processed.
		 * @param array        $columns  The properties for the columns being displayed on the entry list page.
		 * @param array        $form     The Form Object currently being processed.
		 *
		 * @return string
		 */
		public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {
			if ( is_array( $field_id ) ) {
				list( $field_id, $input_id ) = explode( '.', $field_id );

				switch ( $input_id ) {
					case '1':
						$value  = ! rgblank( $value ) ? $this->checked_indicator_markup : '';
						$value .= ! rgblank( $value ) ? ' ' . trim( $entry[ $this->id . '.2' ] ) : '';
						break;
				}
			}

			return $value;
		}

		/**
		 * Format the entry value before it is used in entry exports and by framework add-ons using GFAddOn::get_field_value().
		 *
		 * For CSV export return a string or array.
		 *
		 * @param array      $entry    The entry currently being processed.
		 * @param string     $input_id The field or input ID.
		 * @param bool|false $use_text When processing choice based fields should the choice text be returned instead of the value.
		 * @param bool|false $is_csv   Is the value going to be used in the .csv entries export.
		 *
		 * @return string|array
		 */
		public function get_value_export( $entry, $input_id = '', $use_text = false, $is_csv = false ) {
			$value = GF_Field::get_value_export( $entry, $input_id, $use_text, $is_csv );

			if ( is_array( $value ) ) {
				list( $field_id, $input_id ) = explode( '.', $input_id );

				switch ( $input_id ) {
					case '1':
						$value = ! rgblank( $value ) ? esc_html__( 'Checked', 'gravityforms' ) : esc_html__( 'Not Checked', 'gravityforms' );
						break;
					case '3':
						$value = ! rgblank( $value ) ? $this->get_field_description_from_revision( $value ) : '';
						break;
				}
			}

			return $value;
		}

		/**
		 * Add custom classes to field containers
		 *
		 * @param string $css_class       Class list for the field container.
		 * @param object $field           The GF field object with info.
		 * @param array  $form            The current GF form data.
		 *
		 * @return string
		 */
		public function custom_container_class( $css_class, $field, $form ) {
			if ( 'gdpr' !== $field->type ) {
				return $css_class;
			}

			$css_class .= ' form__group--gdpr';

			return $css_class;
		}

		/**
		 * Define the values/display field
		 *
		 * @return void
		 */
		public function set_default_values() {
			$label_string = 'By submitting this form, I agree to the <a href="#terms-modal" class="modal-launch" title="Terms &amp; Conditions">terms &amp; conditions.</a>';
			?>
			case 'gdpr':
				field.label = <?php echo wp_json_encode( esc_html( 'GDPR Checkbox' ) ); ?>;
				field.inputs = [
					new Input( field.id + '.1', <?php echo wp_json_encode( esc_html( 'GDPR Checkbox' ) ); ?> ),
					new Input( field.id + '.2', <?php echo wp_json_encode( esc_html( 'Text' ) ); ?> ),
					new Input( field.id + '.3', <?php echo wp_json_encode( esc_html( 'Description' ) ); ?> )
				];
				// Hide the description from select columns.
				field.inputs[1].isHidden = true;
				field.inputs[2].isHidden = true;
				field.checkboxLabel = <?php echo wp_json_encode( wp_kses_post( $label_string ) ); ?>;
				field.descriptionPlaceholder = <?php echo wp_json_encode( esc_html( 'Enter consent agreement text here.  The Consent Field will store this agreement text with the form entry in order to track what the user has consented to.' ) ); ?>;
				if ( ! field.inputType )
					field.inputType = 'gdpr';
				// Add choices so we have a dropdown in the conditional logic.
				if ( ! field.choices )
					field.choices = new Array(new Choice(<?php echo wp_json_encode( esc_html__( 'Checked', 'gravityforms' ) ); ?>, '1'));
				break;
			<?php
		}

		/**
		 * Makes sure this validates in both old and new method of data.
		 *
		 * @param array  $result The result of the validation.
		 * @param string $value  Incoming value of the field.
		 * @param array  $form   Full data and information about the current form.
		 * @param object $field  The GForm field information.
		 */
		public function validate_field( $result, $value, $form, $field ) {
			if ( 'gdpr' === $field->type && 'optIn' === $value ) {
				$result['is_valid'] = true;
				$result['message']  = '';
			}

			return $result;
		}
	}
}
