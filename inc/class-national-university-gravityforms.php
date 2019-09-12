<?php
/**
 * Holds our National_University_Gravityforms class
 */

/**
 * National_University_Gravityforms
 */
class National_University_Gravityforms {
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
		add_action( 'fm_post', [ $this, 'gravity_forms_meta' ] );
		add_filter( 'gform_field_groups_form_editor', [ $this, 'add_nu_fields_group' ] );
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
	 * Gravity Forms Meta Fields
	 *
	 * Creates the following fields:
	 * - Option to display the form or not
	 * - A dropdown list of all gravity forms for use on pages
	 * - An input for displaying the Call to Action heading
	 */
	public function gravity_forms_meta() {
		// If Gravity Forms plugin is not installed/active, bail.
		if ( ! class_exists( 'GFAPI' ) ) {
			return;
		}

		// Get all of our info on Gravity Forms in an array.
		$forms = GFAPI::get_forms();

		// Create an array to use as options for forms selection dropdown,
		// with a default null value in case no form is needed
		$gravity_forms = [ '' => '-- Select A Form --' ];

		// Loop through all the Gravity Forms and add them to the array that will be used for options.
		foreach ( $forms as $form ) {
			$gravity_forms[ $form['id'] ] = $form['title'];
		}

		// Sort our forms array alphabetically for easy organization within the select.
		asort( $gravity_forms );

		$fm = new Fieldmanager_Group( [
			'name'           => 'form_fields', // "name" id deceiving, used as the key/ID.
			'serialize_data' => false,
			'add_to_prefix'  => false,
			'children'       => [
				'gravity_forms_display' => new Fieldmanager_Checkbox( 'Display Form?', [
					'default_value'   => 'yes',
					'checked_value'   => 'yes',
					'unchecked_value' => 'no',
				] ),
				'gravity_forms'         => new Fieldmanager_Select( 'Form', [
					'options' => $gravity_forms,
				] ),
				'form_cta'              => new Fieldmanager_Textfield( 'Form Call To Action', [
					'attributes' => [ 'style' => 'width:100%' ],
				] ),
				'campaign_override'     => new Fieldmanager_Textfield( 'Campaign Activity field value', [
					'description' => 'Hidden input\'s value for tracking. Will default to form\'s value.',
					'attributes'  => [ 'style' => 'width:100%' ],
				] ),
			],
		] );

		// Add our meta box to the side rail.
		$fm->add_meta_box( 'Form Setup', [ 'page', 'post', 'program', 'college', 'department', 'event', 'location' ], 'side' );
	}


	/**
	 * Add a custom group for our fields to keep them organized in the admin panel
	 *
	 * @param array $field_groups The field groups, including group name, label and fields.
	 *
	 * @return array
	 */
	public function add_nu_fields_group( $field_groups ) {
		$nu_fields = [
			'nu_fields' => [
				'name'   => 'nu_fields',
				'label'  => __( 'NU Fields', 'national-university' ),
				'fields' => [],
			],
		];

		// Splice original array to insert after Advanced Fields and keep the array key 'nu_fields'.
		$temp_array = array_splice( $field_groups, 0, 2 );

		return array_merge( $temp_array, $nu_fields, $field_groups );
	}
}
