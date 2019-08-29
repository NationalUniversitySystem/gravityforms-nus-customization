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
		add_action( 'fm_post', array( $this, 'gravity_forms_meta' ) );
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
	 * Creates a dropdown list of all gravity forms for use on pages
	 * and an input for displaying the Call to Action title
	 */
	public function gravity_forms_meta() {

		// If Gravity Forms plugin is not installed/active, bail.
		if ( ! class_exists( 'GFAPI' ) ) {
			return;
		}

		// Get all of our info on Gravity Forms in an array.
		$forms = GFAPI::get_forms();

		// Create an array with a default null value
		// (so if no form is needed, one is not saved by default)
		// to add our form data into as <option>.
		$gravity_forms = array( '' => '-- Select A Form --' );

		// Loop through all the Gravity Forms data.
		foreach ( $forms as $form ) {

			// Add our Gravity Forms as <options> into the select,
			// with the form ID as the value, and the form name as the option label.
			$gravity_forms[ $form['id'] ] = $form['title'];
		}

		// Sort our forms array alphabetically for easy organization within the select.
		asort( $gravity_forms );

		$fm = new Fieldmanager_Group( array(
			'name'           => 'form_fields', // "name" id deceiving, used as the key/ID.
			'serialize_data' => false,
			'add_to_prefix'  => false,
			'children'       => array(
				'gravity_forms_display' => new Fieldmanager_Select( 'Display Form?', array(
					'options' => array(
						'yes' => 'Yes',
						'no'  => 'No',
					),
				) ),
				'gravity_forms'         => new Fieldmanager_Select( 'Form', array(
					'options' => $gravity_forms,
				) ),
				'form_cta'              => new Fieldmanager_Textfield( 'Form Call To Action', array(
					'attributes' => array( 'style' => 'width:100%' ),
				) ),
				'campaign_override'     => new Fieldmanager_Textfield( 'Campaign Activity field value', array(
					'description' => 'Hidden input\'s value for tracking. Will default to form\'s value.',
					'attributes'  => array( 'style' => 'width:100%' ),
				) ),
			),
		) );

		// Add our meta box to the side rail.
		$fm->add_meta_box( 'Form Setup', array( 'page', 'post', 'program', 'college', 'department', 'event', 'location' ), 'side' );
	}
}
