<?php
/**
 * Adds an option to display a tooltip next to checkboxes
 */

/**
 * Checkbox_Tooltip class
 */
class Checkbox_Tooltip {
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
		add_action( 'gform_field_standard_settings', [ $this, 'tooltip_text_setting' ], 10, 2 );
		add_action( 'gform_editor_js', [ $this, 'editor_script' ] );
		add_filter( 'gform_tooltips', [ $this, 'add_tooltip' ] );
		add_filter( 'gform_field_container', [ $this, 'add_tooltip_markup' ], 10, 6 );
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
	 * Add markup for for our custom Tooltip setting
	 *
	 * @param int $position Specify the position that the settings should be displayed.
	 * @param int $form_id  The ID of the form from which the entry value was submitted.
	 *
	 * @return void
	 */
	public function tooltip_text_setting( $position, $form_id ) {
		if ( 20 === $position ) {
			?>
			<li class="field_tooltip_setting field_setting">
				<label for="field_tooltip" class="section_label">
					<?php esc_html_e( 'Tooltip', 'national-university' ); ?>
					<?php gform_tooltip( 'field_tooltip' ); ?>
				</label>
				<textarea id="field_tooltip" class="fieldwidth-3 fieldheight-2" onblur="SetFieldProperty( 'tooltipText', this.value );"></textarea>
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
			// Add setting to fields of type "radio"
			fieldSettings.checkbox += ', .field_tooltip_setting';

			// Binding to the load field settings event to initialize the checkbox
			jQuery( document ).bind( 'gform_load_field_settings', function( event, field, form ) {
				jQuery( '#field_tooltip' ).val( field['tooltipText'] );
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
	public function add_tooltip( $tooltips ) {
		$tooltips['field_tooltip'] = '<h6>Tooltip</h6>Text to use for a tooltip helper. Leave blank to not generate markup.';
		return $tooltips;
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
	public function add_tooltip_markup( $field_container, $field, $form, $css_class, $style, $field_content ) {
		if ( is_admin() || 'checkbox' !== $field->type || empty( $field->tooltipText ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			return $field_container;
		}

		$tooltip_text = $field->tooltipText; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

		$tooltip_classes = [
			'gfield__tooltip',
			'icon',
			'icon--question-circle',
		];

		/**
		 * Filter the tooltip's classes.
		 *
		 * @param array $tooltip_classes Holds the classes for the actual tooltip.
		 * @param object $field          The Gravity Forms field object being processed.
		 * @param array $form            The current GF form data.
		 */
		$tooltip_classes = apply_filters( 'gf_nus_tooltip_classes', $tooltip_classes, $field, $form );

		$markup = sprintf(
			'<span data-tooltip-content="#field-tooltip-content-%1$s" class="%2$s"></span><div class="tooltip tooltip-content" id="field-tooltip-content-%1$s">%3$s</div>',
			$field->id,
			implode( ' ', $tooltip_classes ),
			$tooltip_text
		);

		/**
		 * Filter the full tooltip's markup.
		 *
		 * @param string $markup The full markup of the tooltip.
		 * @param object $field  The Gravity Forms field object being processed.
		 * @param array $form    The current GF form data.
		 */
		$markup = apply_filters( 'gf_nus_tooltip_markup', $markup, $field, $form );

		return str_replace( '</li>', $markup . '</li>', $field_container );
	}
}
