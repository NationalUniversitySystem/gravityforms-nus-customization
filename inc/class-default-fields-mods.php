<?php
/**
 * Modifications done to the default Gravity Form fields
 */

/**
 * Default_Fields_mods class
 */
class Default_Fields_Mods {
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
		add_action( 'gform_field_appearance_settings', [ $this, 'radio_choices_label_setting' ], 10, 2 );
		add_action( 'gform_editor_js', [ $this, 'editor_script' ] );
		add_filter( 'gform_tooltips', [ $this, 'add_encryption_tooltips' ] );
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
	 * Add markup for for our custom Appearance setting
	 *
	 * @param int $position Specify the position that the settings should be displayed.
	 * @param int $form_id  The ID of the form from which the entry value was submitted.
	 *
	 * @return void
	 */
	public function radio_choices_label_setting( $position, $form_id ) {
		if ( 100 === $position ) {
			?>
			<li class="radio_choices_label_setting field_setting">
				<label for="radio_choices_label_placement" class="section_label">
					<?php esc_html_e( 'Label Position/Behavior', 'national-university' ); ?>
					<?php gform_tooltip( 'radio_choices_label_tip_value' ); ?>
				</label>
				<select id="radio_choices_label_placement" onchange="SetFieldProperty( 'radiosLabel', this.value );">
					<option value=""><?php esc_html_e( 'Below Option', 'national-university' ); ?></option>
					<option value="wrapped"><?php esc_html_e( 'Wrapped Around Option', 'national-university' ); ?></option>
				</select>
			</li>
			<?php
		}
	}

	/**
	 * Add JS required to add and save the settings
	 *
	 * @return void
	 */
	public function editor_script() {
		?>
		<script type='text/javascript'>
			// ADD setting to fields of type "radio"
			fieldSettings.radio += ', .radio_choices_label_setting';

			// Binding to the load field settings event to initialize the checkbox
			jQuery( document ).bind( 'gform_load_field_settings', function( event, field, form ) {
				jQuery( '#radio_choices_label_placement' ).val( field['radiosLabel'] );
			} );
		</script>
		<?php
	}

	/**
	 * Add a description for the setting's tooltip.
	 *
	 * @param array $tooltips All the tooltips markup/descriptions for the fields.
	 *
	 * @return array
	 */
	public function add_encryption_tooltips( $tooltips ) {
		$tooltips['radio_choices_label_tip_value'] = '<h6>Label Position/Behavior</h6>Select if the label should be echoed out below the actual radio option markup or it should be wrapped around the option.';
		return $tooltips;
	}
}
