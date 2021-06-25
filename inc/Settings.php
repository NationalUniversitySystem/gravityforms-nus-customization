<?php
/**
 * Manage custom settings.
 */

namespace NUSA\GravityForms;

/**
 * Settings
 */
class Settings {
	/**
	 * Use class construct method to define all filters & actions
	 */
	public function __construct() {
		add_filter( 'gform_field_groups_form_editor', [ $this, 'add_nu_fields_group' ] );
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
