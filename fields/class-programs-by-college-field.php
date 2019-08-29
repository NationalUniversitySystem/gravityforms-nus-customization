<?php
/**
 * Custom field: Programs_By_College_Field
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Hooks to set defaults and prepend the code are at bottom of file.
 * Creates a dropdown with programs specific to a college method
 */
class Programs_By_College_Field extends GF_Field_Select {
	/**
	 * The field type
	 *
	 * @var string $type
	 */
	public $type = 'programs-by-college';

	/**
	 * Register hooks.
	 */
	public function add_hooks() {
		add_action( 'gform_editor_js_set_default_values', array( $this, 'set_default_values' ) );

		add_filter( 'gform_pre_render', array( $this, 'populate_choices' ) );
		add_filter( 'gform_pre_validation', array( $this, 'populate_choices' ) );
		add_filter( 'gform_pre_submission_filter', array( $this, 'populate_choices' ) );
		add_filter( 'gform_admin_pre_render', array( $this, 'populate_choices' ) );
	}

	/**
	 * Return the field title, for use in the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'College Programs', 'national-university' );
	}

	/**
	 * Assign the field button to a fields group.
	 *
	 * @return array
	 */
	public function get_form_editor_button() {
		return array(
			'group' => 'standard_fields',
			'text'  => $this->get_form_editor_field_title(),
		);
	}

	/**
	 * The settings which should be available on the field in the form editor.
	 *
	 * @return array
	 */
	public function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'enable_enhanced_ui_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'size_setting',
			'choices_setting',
			'rules_setting',
			'placeholder_setting',
			'default_value_setting',
			'visibility_setting',
			'duplicate_setting',
			'description_setting',
			'css_class_setting',
			'college_setting', // Custom setting defined in this class.
		);
	}

	/**
	 * Enable this field for use with conditional logic.
	 *
	 * @return bool
	 */
	public function is_conditional_logic_supported() {
		return true;
	}

	/**
	 * Define the fields inner markup.
	 *
	 * @param array        $form The Form Object currently being processed.
	 * @param string|array $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array   $entry Null or the Entry Object currently being edited.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		$form_id         = absint( $form['id'] );
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$field_id      = $this->id;
		$field_id_attr = $is_entry_detail || $is_form_editor || 0 === $form_id ? "input_$field_id" : 'input_' . $form_id . "_$field_id";

		$size               = $this->size;
		$class_suffix       = $is_entry_detail ? '_admin' : '';
		$class              = $size . $class_suffix;
		$css_class          = trim( esc_attr( $class ) . ' input input--select input--styled' );
		$tabindex           = $this->get_tabindex();
		$disabled_text      = $is_form_editor ? 'disabled="disabled"' : '';
		$required_attribute = $this->isRequired ? 'aria-required="true"' : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
		$invalid_attribute  = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';

		return sprintf(
			"<select name='input_%d' id='%s' class='%s' $tabindex %s %s %s>%s</select>",
			$field_id,
			$field_id_attr,
			$css_class,
			$disabled_text,
			$required_attribute,
			$invalid_attribute,
			$this->get_choices( $value )
		);
	}

	/**
	 * Fetch the colleges as options for the dropdown
	 *
	 * @return string
	 */
	private static function get_college_option_fields() {
		$colleges_args = array(
			'post_type'              => 'college',
			'posts_per_page'         => 50,
			'order'                  => 'ASC',
			'orderby'                => 'menu_order',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		$colleges_query = new WP_Query( $colleges_args );

		$colleges = wp_list_pluck( $colleges_query->posts, 'post_title', 'ID' );

		// Blank option for on load event.
		$options_markup = '<option value="" selected disabled>-- Select College --</option>';

		foreach ( $colleges as $college_id => $college_title ) {
			$options_markup .= "<option value='{$college_id}'>{$college_title}</option>\n";
		}

		return $options_markup;
	}

	/**
	 * Returns the field markup; including field label, description, validation, and the form editor admin buttons.
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

		$required_div = $is_admin || $this->isRequired ? sprintf( "<span class='%s'>%s</span>", $required_class, $this->isRequired ? '*' : '' ) : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

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
	 * Returns the markup for the field description.
	 *
	 * @param string $description The field description.
	 * @param string $css_class   The css class to be assigned to the description container.
	 *
	 * @return string
	 */
	public function get_description( $description, $css_class ) {
		$is_form_editor  = $this->is_form_editor();
		$is_entry_detail = $this->is_entry_detail();
		$is_admin        = $is_form_editor || $is_entry_detail;

		$description_wrapper = $is_admin ? "<div class='$css_class'>%s</div>" : "<span class='form__description'>%s</span>";

		return $is_admin || ! empty( $description ) ? sprintf( $description_wrapper, $description ) : '';
	}

	/**
	 * Self explanatory...
	 *
	 * @return string
	 */
	public function get_field_label_class() {
		return is_admin() ? 'gfield_label' : 'form__label';
	}

	/**
	 * Add a custom setting as a dropdown of colleges available
	 *
	 * @param int $position The position that the settings should be displayed.
	 * @param int $form_id The ID of the form from which the entry value was submitted.
	 *
	 * @return void
	 */
	public static function add_standard_settings( $position, $form_id ) {
		if ( 5 === $position ) {
			?>
			<li class="college_setting field_setting">
				<label for="college_choice" class="section_label">
					<?php esc_html_e( 'College Programs', 'national-university' ); ?>
					<?php gform_tooltip( 'college_setting_tooltip_value' ); ?>
				</label>
				<select name='college_choice' id="college_choice" onchange="SetFieldProperty('college_id_for_programs', this.value);">
					<?php
					echo wp_kses( self::get_college_option_fields(), array(
						'option' => array(
							'value'    => array(),
							'selected' => array(),
							'disabled' => array(),
						),
					) );
					?>
				</select>
				<!-- <input type="checkbox" id="field_encrypt_value" onclick="SetFieldProperty('encryptField', this.checked);" /> encrypt field value -->
			</li>
			<?php
		}
	}

	/**
	 * Run the JS on the page to be able to save the setting
	 *
	 * @return void
	 */
	public static function save_settings() {
		?>
		<script>
			(function( $ ) {
				fieldSettings['programs-by-college'] += ', .college_setting';

				$( document ).bind( 'gform_load_field_settings', function( event, field, form ) {
					$( '#college_choice' ).val( field['college_id_for_programs'] );
				} );
			})(jQuery);
		</script>
		<?php
	}

	/**
	 * Tooltip definition for the custom college setting
	 *
	 * @param array $tooltips Associative array with the existing tooltips. Key is the tooltip name and the value is the tooltip.
	 *
	 * @return array
	 */
	public static function add_tooltip( $tooltips ) {
		$tooltips['college_setting_tooltip_value'] = '<h6>College Programs</h6>Select the college for which the programs should display.';

		return $tooltips;
	}

	/**
	 * Set the default label
	 * - Inputs are determined dynamically, no defaults.
	 *
	 * @return void
	 */
	public static function set_default_values() {
		?>
		case "programs-by-college" :
			field.label = <?php echo wp_json_encode( esc_html__( 'Program of Interest', 'national-university' ) ); ?>;
			field.inputs = null;
			field.isRequired = true;
			break;
		<?php
	}


	/**
	 * Set the Choices if they have not been set already.
	 *
	 * @param array $form Current form in admin/front-end.
	 *
	 * @return array
	 */
	public function populate_choices( $form ) {
		if ( ! isset( $form['fields'] ) ) {
			return $form;
		}

		foreach ( $form['fields'] as &$field ) {
			if ( 'programs-by-college' !== $field->type || ! empty( $field->choices ) || empty( $field->college_id_for_programs ) ) {
				continue;
			}

			$choices = array();

			$programs_args = array(
				'post_type'              => 'program',
				'posts_per_page'         => 100,
				'order'                  => 'ASC',
				'orderby'                => 'title',
				'meta_key'               => '_college',
				'meta_value'             => $field->college_id_for_programs,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			);

			$programs_query = new WP_Query( $programs_args );

			foreach ( $programs_query->posts as $post ) {
				$choices[] = array(
					'text'  => $post->post_title,
					'value' => $post->post_title,
				);
			}

			$field->choices = $choices;
		}

		return $form;
	}
}
