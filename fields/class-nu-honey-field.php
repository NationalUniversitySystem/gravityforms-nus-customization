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
		add_filter( 'gform_entry_detail_meta_boxes', [ $this, 'register_meta_box' ], 10, 3 );
		add_filter( 'gform_entry_meta', [ $this, 'meta_column' ], 10, 2 );
	}

	/**
	 * Return the field title, for use in the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'Honeypot', 'national-university' );
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
		case "nu_honey" :
			field.label = "Form Email Field";
			field.isRequired = false;
			break;
		<?php
	}

	/**
	 * Stop webhooks if honeypot filled
	 * If the field doesn't exist on the form, or if the field is blank, run our webhooks.
	 * Else do not run any of the feeds by returning a blank $feeds array and update the metadata of the field so it's easier to track these entries.
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

		if ( false === $honeypot || '' === $honeypot ) {
			return $feeds;
		} else {
			gform_update_meta( $entry['id'], 'killed_feeds_status', '1' );

			return [];
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
			$content .= '<span class="form__description sr-only">Instructions for ' . $field->label . ' input: ' . $field->description . '</span>';
		}

		$content .= '<label class="form__label" for="input_' . $form_id . '_' . $field->id . '">' . $field->label . '</label>';

		$content .= '<input class="input input--text input--styled" type="text"';
		$content .= ' name="' . $name . '"';
		$content .= ' value="" id="input_' . $form_id . '_' . $field->id . '"';
		$content .= ' autocomplete="off">';

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
	 * Register a meta box that will display whether or not the feeds were killed due to the field.
	 *
	 * @param array $meta_boxes The properties for the meta boxes.
	 * @param array $entry      The entry currently being viewed/edited.
	 * @param array $form       The form object used to process the current entry.
	 *
	 * @return array
	 */
	public function register_meta_box( $meta_boxes, $entry, $form ) {
		// If the form/entry doesn't have the field, don't register the meta box.
		$field_id = $this->get_field_id( $form, 'nu_honey' );

		if ( false === $field_id || ! isset( $entry[ $field_id ] ) ) {
			return $meta_boxes;
		}

		$meta_boxes['killed_feeds'] = [
			'title'       => 'Killed Feeds?',
			'description' => 'Whether the feeds (webhooks) were killed due to the custom honey pot field.',
			'context'     => 'side',
			'callback'    => [ $this, 'add_killed_feeds_box' ],
		];

		return $meta_boxes;
	}

	/**
	 * Display our meta box
	 *
	 * - Displays the SOAR UUID or a button to run the SOAR submission.
	 *
	 * @param array $args Gravity forms data for the entry + form.
	 *
	 * @return void
	 */
	public function add_killed_feeds_box( $args ) {
		$entry_id     = $args['entry']['id'];
		$feeds_status = gform_get_meta( $entry_id, 'killed_feeds_status' );

		if ( ! empty( $feeds_status ) ) {
			echo '<p>Entry <strong>does contain</strong> the metadata signaling it was stopped by the honey field.</p>';
		} else {
			echo '<p>Entry does <strong>NOT</strong> contain the metadata signaling it was stopped by the honey field.</p>';
		}
	}

	/**
	 * Add the soarUUID as a column option when viewing entries listing
	 *
	 * @param array   $entry_meta Entry meta array.
	 * @param integer $form_id    The ID of the form from which the entry value was submitted.
	 *
	 * @return array
	 */
	public function meta_column( $entry_meta, $form_id ) {
		$entry_meta['killed_feeds_status'] = [
			'label'             => 'Killed Feeds?',
			'is_numeric'        => false,
			'is_default_column' => false,
		];

		return $entry_meta;
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

	/**
	 * Gets the field ID from the type
	 *
	 * @param array  $form  GF data of form.
	 * @param string $value Value we are trying to find inside the form.
	 * @param string $key   The type we are trying to find.
	 */
	private function get_field_id( $form, $value, $key = 'type' ) {
		foreach ( $form['fields'] as $field ) {
			if ( strtolower( $field->$key ) === strtolower( $value ) ) {
				return $field->id;
			}
		}
		return false;
	}
}
