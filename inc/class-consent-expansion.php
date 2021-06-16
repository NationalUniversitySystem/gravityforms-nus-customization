<?php
/**
 * Mod to expand the behavior of displaying the text box of the native Gravity Forms Consent field
 */

// We'll be doing a whole lot of HTML manipulation.
require GF_NUS_PATH . '/inc/vendor/simplehtmldom/simple_html_dom.php';
/**
 * Consent_Expansion class
 */
class Consent_Expansion {
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
		add_action( 'gform_field_appearance_settings', [ $this, 'consent_text_behavior_setting' ] );
		add_action( 'gform_editor_js', [ $this, 'editor_script' ] );
		add_filter( 'gform_tooltips', [ $this, 'add_tooltip' ] );
		add_filter( 'gform_field_content', [ $this, 'modify_field_content' ], 10, 5 );
		add_filter( 'gform_submit_button', [ $this, 'add_consent_below_submit' ], 99, 2 );
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
	 *
	 * @return void
	 */
	public function consent_text_behavior_setting( $position ) {
		if ( 100 === $position ) {
			$behavior_options = [
				''             => 'Full Display (default)',
				'below-submit' => 'Below Submit',
				'modal'        => 'Modal',
				'ab-test'      => 'A/B Test',
			];

			/**
			 * Filter the options to display in case a theme needs specific and unique behavior, or plugin.
			 *
			 * @param array $behavior_options The plugin defined behaviors.
			 */
			$behavior_options = apply_filters( 'gf_nus_consent_behavior_options', $behavior_options );
			?>
			<li class="consent_text_behavior_setting field_setting">
				<label for="consent_text_behavior" class="section_label">
					<?php esc_html_e( 'Consent Text Behavior', 'national-university' ); ?>
					<?php gform_tooltip( 'consent_text_behavior_tip_value' ); ?>
				</label>
				<select id="consent_text_behavior" onchange="SetFieldProperty( 'consentTextBehavior', this.value );">
					<?php
					foreach ( $behavior_options as $value => $behavior_text ) {
						printf(
							'<option value="%s">%s</option>',
							esc_attr( $value ),
							esc_html( $behavior_text )
						);
					}
					?>
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
			// Add setting to fields of type "radio"
			fieldSettings.consent += ', .consent_text_behavior_setting';

			// Binding to the load field settings event to initialize the checkbox
			jQuery( document ).bind( 'gform_load_field_settings', function( event, field, form ) {
				jQuery( '#consent_text_behavior' ).val( field['consentTextBehavior'] );
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
		$tooltips['consent_text_behavior_tip_value'] = '<h6>Consent Text Behavior</h6>Select behavior of the text box of the consent field. Main behavior functionality is in plugin but other styling should be handled in the theme.<br><br>If modal is selected, add ID of modal target accordingly (#gfield_consent_description_{form_id}_{field_id}).';
		return $tooltips;
	}


	/**
	 * Add the appropriate class and/or markup dependant of behavior setting
	 *
	 * @param string $field_content Markup of the field provided by GF.
	 * @param object $field         GF object with info about the field.
	 * @param string $value         Value of the input.
	 * @param int    $random        Unused parameter? Actual plugin has no documentation on this parameter.
	 * @param int    $form_id       Field's parent form ID.
	 *
	 * @return string
	 */
	public function modify_field_content( $field_content, $field, $value, $random = 0, $form_id ) {
		if ( is_admin() || ! in_array( $field->type, [ 'consent', 'gdpr' ], true ) || empty( $field->consentTextBehavior ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			return $field_content;
		}

		$consent_text_behavior = 'consent__' . $field->consentTextBehavior; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

		// Add the class to the description (consent text) div.
		$field_content = str_replace( ' gfield_consent_description', ' gfield_consent_description ' . $consent_text_behavior, $field_content );

		// Remove the consent text but store it as a property for use in submit button hook.
		if ( 'consent__below-submit' === $consent_text_behavior ) {
			$field_content = $this->field_below_submit_changes( $field_content, $consent_text_behavior );
		}

		if ( 'consent__modal' === $consent_text_behavior ) {
			$field_content = $this->field_modal_changes( $field_content, $consent_text_behavior );
		}

		if ( 'consent__ab-test' === $consent_text_behavior ) {
			$field_content = $this->field_ab_test_changes( $field_content, $consent_text_behavior );
		}

		return $field_content;
	}

	/**
	 * Attach the consent text when the setting "consentTextBehavior" is set to "below-submit"
	 *
	 * @param string $button_input Full submit button markup.
	 * @param array  $form         Full Gravity Forms form array in question.
	 *
	 * @return string
	 */
	public function add_consent_below_submit( $button_input, $form ) {
		$field_types = wp_list_pluck( $form['fields'], 'type' );

		if ( in_array( 'consent', $field_types, true ) ) {
			$consent_key   = array_search( 'consent', $field_types, true );
			$consent_field = $form['fields'][ $consent_key ];

			// phpcs:disable WordPress.NamingConventions.ValidVariableName
			if (
				! empty( $consent_field->consentTextBehavior )
				// && 'below-submit' === $consent_field->consentTextBehavior
				&& in_array( $consent_field->consentTextBehavior, [ 'below-submit', 'ab-test' ], true )
				&& ! empty( $this->consent_div )
			) {
				$button_input .= $this->consent_div;
			}
			 // phpcs:enable WordPress.NamingConventions.ValidVariableName
		}

		return $button_input;
	}

	/**
	 * Do the manipulation of the field content when the behavior is "below submit" option
	 *
	 * @param string $field_content         Full field content markup from Gravity Forms.
	 * @param string $consent_text_behavior The behavior option as the class.
	 *
	 * @return string
	 */
	private function field_below_submit_changes( $field_content, $consent_text_behavior ) {
		$field_content_dom = str_get_html( $field_content );
		$consent_div       = $field_content_dom->find( '.' . $consent_text_behavior, 0 );

		if ( $consent_div ) {
			$this->consent_div = $consent_div->save();

			$consent_div->outertext = '';

			$field_content = $field_content_dom->save();
			$field_content = str_replace( '<body>', '', $field_content );
			$field_content = str_replace( '</body>', '', $field_content );
			$field_content = str_replace( '<html>', '', $field_content );
			$field_content = str_replace( '</html>', '', $field_content );
		}

		$field_content = $field_content_dom->save();
		$field_content_dom->clear();
		unset( $field_content_dom );

		return $field_content;
	}

	/**
	 * Do the manipulation of the field content when the behavior is "modal" option
	 *
	 * @param string $field_content         Full field content markup from Gravity Forms.
	 * @param string $consent_text_behavior The behavior option as the class.
	 *
	 * @return string
	 */
	private function field_modal_changes( $field_content, $consent_text_behavior ) {
		/**
		 * Allow fully custom modal markup
		 *
		 * @return string
		 */
		$custom_modal_markup = apply_filters( 'gf_nus_custom_consent_modal_markup', '', $field_content );

		if ( '' !== $custom_modal_markup ) {
			return $custom_modal_markup;
		}

		$field_content_dom = str_get_html( $field_content );
		$modal_text_div    = $field_content_dom->find( '.' . $consent_text_behavior, 0 );
		$modal_trigger     = $field_content_dom->find( '.modal-launch', 0 );

		if ( $modal_trigger ) {
			$title = $modal_trigger->title;

			if ( ! empty( $title ) ) {
				$modal_title_classes = [
					'modal__title',
				];
				$modal_title_classes = apply_filters( 'gf_nus_consent_title_classes', $modal_title_classes );
				$modal_title_html    = sprintf(
					'<h2 class="%s" tabindex="-1">%s</h2>',
					implode( ' ', $modal_title_classes ),
					$title
				);
			}
		}

		if ( $modal_text_div ) {
			$modal_build_classes = [
				'body'  => 'modal__body',
				'copy'  => 'modal__copy',
				'close' => 'modal__close',
			];
			$modal_build_classes = apply_filters( 'gf_nus_consent_modal_classes', $modal_build_classes );

			$modal_text_div_class  = $modal_text_div->class;
			$modal_text_div->class = $modal_text_div_class . ' modal';
			$modal_text_div->role  = 'dialog';

			$modal_body_dom        = str_get_html( '<div></div>' );
			$modal_body_div        = $modal_body_dom->find( 'div', 0 );
			$modal_body_div->class = $modal_build_classes['body'];

			if ( isset( $modal_title_html ) ) {
				$modal_body_div->innertext .= $modal_title_html;
			}

			// Set the copy text/html.
			$modal_text      = apply_filters( 'the_content', $modal_text_div->innertext );
			$modal_copy_html = sprintf(
				'<div class="%s">%s</div>',
				$modal_build_classes['copy'],
				$modal_text
			);

			$modal_body_div->innertext .= $modal_copy_html;
			$modal_text_div->innertext  = $modal_body_div->outertext;

			// Add close trigger.
			$modal_close_html = sprintf(
				'<a href="#" title="Close Consent Modal" class="%s" aria-label="Close Consent Modal">&times;</a>',
				$modal_build_classes['close']
			);

			$modal_text_div->innertext .= $modal_close_html;

			$modal_body_dom->clear();
			unset( $modal_body_dom );
		}

		$field_content = $field_content_dom->save();
		$field_content_dom->clear();
		unset( $field_content_dom );

		return $field_content;
	}

	/**
	 * Do the manipulation of the field content when the behavior is "modal" option
	 * - Will display modal as default (control group) and the rest will be on "display: none;"
	 *
	 * @param string $field_content         Full field content markup from Gravity Forms.
	 * @param string $consent_text_behavior The behavior option as the class.
	 *
	 * @return string
	 */
	private function field_ab_test_changes( $field_content, $consent_text_behavior ) {
		// The description field. Duplicate the field to have multiple description/text fields.
		$field_content_dom = str_get_html( $field_content );
		$consent_div       = $field_content_dom->find( '.' . $consent_text_behavior, 0 );

		if ( $consent_div ) {
			$full_div_dom        = str_get_html( $consent_div->outertext );
			$full_div            = $full_div_dom->find( '.' . $consent_text_behavior, 0 );
			$full_div_classes    = $full_div->class;
			$full_div_id         = $full_div->id;
			$full_div->class     = $full_div_classes . ' consent__full-display';
			$full_div->id        = $full_div_id . '--full-display';
			$full_div->style     = 'display: none;';
			$full_display_markup = $full_div_dom->save();

			$below_submit_div_dom     = str_get_html( $consent_div->outertext );
			$below_submit_div         = $below_submit_div_dom->find( '.' . $consent_text_behavior, 0 );
			$below_submit_div_classes = $below_submit_div->class;
			$below_submit_div_id      = $below_submit_div->id;
			$below_submit_div->class  = $below_submit_div_classes . ' consent__below-submit';
			$below_submit_div->id     = $below_submit_div_id . '--below-submit';
			$below_submit_div->style  = 'display: none;';
			$below_input_markup       = $below_submit_div_dom->save();
			$this->consent_div        = $below_input_markup;
		}

		// Our default (control) markup.
		$modal_markup = $this->field_modal_changes( $field_content, $consent_text_behavior );

		if ( isset( $full_display_markup ) ) {
			$modal_markup .= $full_display_markup;
		}

		$field_content = $field_content_dom->save();
		$field_content_dom->clear();
		unset( $field_content_dom );

		return $modal_markup;
	}
}
