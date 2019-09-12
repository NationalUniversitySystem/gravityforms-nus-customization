<?php
/**
 * Our custom Degree select Field
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Creates custom Degree select field
 */
class Degree_Field extends GF_Field_Select {
	/**
	 * The field type
	 *
	 * @var string $type
	 */
	public $type = 'degree';

	/**
	 * Register hooks.
	 */
	public function add_hooks() {
		add_action( 'gform_editor_js_set_default_values', [ $this, 'set_default_values' ] );
		add_filter( 'gform_field_content', [ $this, 'custom_html' ], 10, 5 );
		add_filter( 'gform_field_container', [ $this, 'custom_field_container' ], 10, 6 );

		add_filter( 'gform_pre_render', [ $this, 'populate_degree_type' ] );
		add_filter( 'gform_pre_validation', [ $this, 'populate_degree_type' ] );
		add_filter( 'gform_pre_submission_filter', [ $this, 'populate_degree_type' ] );
		add_filter( 'gform_admin_pre_render', [ $this, 'populate_degree_type' ] );


		// Ajax calls.
		add_action( 'wp_ajax_degree_select', [ $this, 'degree_select' ] );
		add_action( 'wp_ajax_nopriv_degree_select', [ $this, 'degree_select' ] );
	}

	/**
	 * Return the field title, for use in the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'Degree Type', 'national-university' );
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
		// Get all of our degree types as an array.
		$degree_types = get_terms( [
			'taxonomy' => 'degree-type',
		] );
		?>
		case "degree" :
			field.label = "Degree Type";
			field.isRequired = true;
			field.enableChoiceValue = true;
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
		if ( is_admin() || 'degree' !== $field->type ) {
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
		if ( 'degree' !== $field->type ) {
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

		$custom_classes .= 'degree--select';

		// Setup how our field_id is displayed.
		$field_id = is_admin() || empty( $form ) ? "field_{$id}" : 'field_' . $form['id'] . "_$id";

		// Create our new <li>.
		return '<li id="' . $field_id . '" class="' . $css_class . ' ' . $custom_classes . '">{FIELD_CONTENT}</li>';
	}

	/**
	 * Dynamically populate the degrees field
	 *
	 * @param array $form The GF form in question.
	 *
	 * @return array
	 */
	public function populate_degree_type( $form ) {
		if ( ! isset( $form['fields'] ) ) {
			return $form;
		}

		foreach ( $form['fields'] as &$field ) {
			if ( 'degree' !== $field->type ) {
				continue;
			}

			$degree_types = get_terms( [
				'taxonomy' => 'degree-type',
			] );

			$choices = [];

			foreach ( $degree_types as $degree_type ) {
				$choices[] = [
					'text'  => $degree_type->name,
					'value' => $degree_type->name,
				];
			}

			$choices[] = [
				'text'  => 'Undecided',
				'value' => 'Undecided',
			];

			// Update 'Select a Post' to whatever you'd like the instructive option to be.
			$field->placeholder = 'Select a Post';
			$field->choices     = $choices;
		}

		return $form;
	}

	/**
	 * Filter programs by taxonomy
	 *
	 * Creates a list of programs filtered by taxonomy, output as options
	 */
	public function degree_select() {
		// Make sure the value is in the request.
		if ( empty( $_POST['degree'] ) ) { // input var ok.
			wp_die();
		}

		// Get our ajax passed data.
		$degree_type     = sanitize_text_field( wp_unslash( $_POST['degree'] ) ); // input var ok.
		$modify_programs = ! empty( $_POST['modifyPrograms'] ) ? true : false; // input var ok.

		if ( 'Undecided' === $degree_type ) {
			echo '<option value="N/A">N/A</option>';
		} else {
			// Get the ID of our term from it's name.
			$term = get_term_by( 'name', $degree_type, 'degree-type' );

			// Setup array of current program types and their new names.
			$new_program_names = [
				'Associate of Arts'    => 'AA',
				'Associate of Science' => 'AS',
				'Bachelor of Arts'     => 'BA',
				'Bachelor of Science'  => 'BS',
				'Master of Arts'       => 'MA',
				'Master of Education'  => 'ME',
				'Master of Fine Arts'  => 'MFA',
				'Master of Science'    => 'MS',
			];

			// Setup our query args.
			$args     = [
				'order'                  => 'ASC',
				'orderby'                => 'title',
				'post_type'              => 'program',
				'post_status'            => 'publish',
				'posts_per_page'         => 100,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'tax_query'              => [
					[
						'taxonomy' => 'degree-type',
						'field'    => 'slug',
						'terms'    => $term->slug,
					],
				],
			];
			$programs = new WP_Query( $args );

			if ( $programs->have_posts() ) {
				while ( $programs->have_posts() ) {
					$programs->the_post();
					// Get our custom meta.
					$teachout = get_post_meta( get_the_ID(), 'teachout', true );

					// If program is not in teachout actually display it.
					if ( 'yes' !== $teachout ) {
						// Replace any dashes with spaces.
						$title = str_replace( '&#8211; ', ' ', get_the_title() );

						// Set our var to null.
						$shortened_title = null;

						// Loop through the program name array to shorten the display title names.
						foreach ( $new_program_names as $full_name => $abbreviation ) {
							if ( stripos( $title, $full_name ) !== false ) {
								$shortened_title = str_replace( $full_name, $abbreviation, $title );
								break;
							}
						}

						// Determine which title to display.
						$display_title = ( null !== $shortened_title && true === $modify_programs ) ? $shortened_title : $title;

						// Output all programs as options.
						echo '<option value="' . esc_attr( $title ) . '">' . esc_html( $display_title ) . '</option>';
					}
				}
			}
			wp_reset_postdata();
		}
		// RIP.
		wp_die();
	}
}
