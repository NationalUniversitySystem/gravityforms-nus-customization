<?php
/**
 * Add Meta fields for custom functionality.
 */

namespace NUSA\GravityForms;

/**
 * Metadata
 */
class Metadata {
	/**
	 * Use class construct method to define all filters & actions
	 */
	public function __construct() {
		add_action( 'fm_post', [ $this, 'gravity_forms_meta' ] );
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
		$forms = array_filter( \GFAPI::get_forms() );

		// Create an array with all form options, including a default null value
		// (so if no form is needed, one is not saved by default) to add our form data into as <option>.
		$gravity_forms = [ '' => '-- Select A Form --' ] + wp_list_pluck( $forms, 'title', 'id' );

		// Sort our forms array alphabetically for easy organization within the select.
		asort( $gravity_forms );

		$fm = new \Fieldmanager_Group( [
			'name'           => 'form_fields', // "name" id deceiving, used as the key/ID.
			'serialize_data' => false,
			'add_to_prefix'  => false,
			'children'       => [
				'gravity_forms_display' => new \Fieldmanager_Select( 'Display Form?', [
					'options' => [
						'yes' => 'Yes',
						'no'  => 'No',
					],
				] ),
				'gravity_forms'         => new \Fieldmanager_Select( 'Form', [
					'options' => $gravity_forms,
				] ),
				'form_cta'              => new \Fieldmanager_Textfield( 'Form Call To Action', [
					'attributes' => [ 'style' => 'width:100%' ],
				] ),
				'form_sub_cta'          => new \Fieldmanager_Textfield( 'Form Subtext', [
					'attributes' => [ 'style' => 'width:100%' ],
				] ),
				'campaign_override'     => new \Fieldmanager_Textfield( 'Campaign Activity field value', [
					'description' => 'Hidden input\'s value for tracking. Will default to form\'s value.',
					'attributes'  => [ 'style' => 'width:100%' ],
				] ),
				'organization_override' => new \Fieldmanager_Textfield( 'Organization field value', [
					'description' => 'Hidden input\'s value for organization.',
					'attributes'  => [ 'style' => 'width:100%' ],
				] ),
				'lead_group_override'   => new \Fieldmanager_Textfield( 'Lead Group field value', [
					'description' => 'Hidden input\'s value for leadgroup.',
					'attributes'  => [ 'style' => 'width:100%' ],
				] ),
			],
		] );

		// Add our meta box to the side rail.
		$fm->add_meta_box( 'Form Setup', [ 'page', 'post', 'program', 'college', 'department', 'event', 'location' ], 'side' );
	}
}
