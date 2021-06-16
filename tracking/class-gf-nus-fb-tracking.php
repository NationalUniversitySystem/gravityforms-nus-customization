<?php
/**
 * Holds our Gf_Nus_Fb_Tracking class
 */

/**
 * Gf_Nus_Fb_Tracking
 */
class Gf_Nus_Fb_Tracking {
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
		add_action( 'wp_head', [ $this, 'add_pixel_base_code' ] );
		add_action( 'wp_footer', [ $this, 'add_fb_pixel_to_footer' ] );
		add_action( 'wp_footer', [ $this, 'add_completed_application_pixel' ] );
		// add_filter( 'gform_confirmation', [ $this, 'add_fb_pixel_to_confirmation' ], 10, 4 );
		add_filter( 'gform_field_value_fbtrack', [ $this, 'populate_fbtrack' ] );
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
	 * Add the base of the pixel code.
	 * The piece of code itself has a check if it was previous defined.
	 *
	 * @return void
	 */
	public function add_pixel_base_code() {
		// Check that our class and methods to fetch settings exist.
		if (
			! class_exists( 'Gf_Nus_Addon' )
			|| ! method_exists( 'Gf_Nus_Addon', 'get_instance' )
			|| ! method_exists( 'Gf_Nus_Addon', 'get_plugin_settings' )
			|| current_user_can( 'install_plugins' )
		) {
			return;
		}

		$add_on         = Gf_Nus_Addon::get_instance();
		$this->settings = $add_on->get_plugin_settings();

		// Check if the settings we need are defined (all of them).
		if (
			empty( $this->settings['fb_tracking_enabled'] )
			|| empty( $this->settings['fb_pixel_id'] )
			|| (
				empty( $this->settings['fb_tracking_thank_you_page_ids'] )
				&& empty( $this->settings['fb_tracking_completed_app_thank_you_page_ids'] )
			)
		) {
			return;
		}

		// Check if the parameters we need are defined.
		if ( empty( $_GET['oa2type'] ) && empty( $_GET['entry_id'] ) && empty( $_GET['fbtrack'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$this->thank_you_pages     = [];
		$this->completed_app_pages = [];
		$this->pages_ids           = [];

		if ( ! empty( $this->settings['fb_tracking_thank_you_page_ids'] ) ) {
			$this->thank_you_pages = array_filter( array_map( 'intval', explode( ',', $this->settings['fb_tracking_thank_you_page_ids'] ) ) );
		}

		if ( ! empty( $this->settings['fb_tracking_completed_app_thank_you_page_ids'] ) ) {
			$this->completed_app_pages = array_filter( array_map( 'intval', explode( ',', $this->settings['fb_tracking_completed_app_thank_you_page_ids'] ) ) );
		}

		$this->pages_ids = array_merge( $this->thank_you_pages, $this->completed_app_pages );

		if ( ! is_page( $this->pages_ids ) ) {
			return;
		}
		?>

		<!-- Facebook Pixel Code -->
		<script type='text/javascript'>
		!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
		n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
		n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
		t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
		document,'script','https://connect.facebook.net/en_US/fbevents.js');
		</script>
		<!-- End Facebook Pixel Code -->

		<?php
	}

	/**
	 * Trigger the FB tracking pixel push in the footer.
	 *
	 * @return void
	 */
	public function add_fb_pixel_to_footer() {
		// Avoid admins.
		if ( current_user_can( 'install_plugins' ) ) {
			return;
		}

		// Check if the settings and parameters we need are defined (all of them).
		if (
			empty( $this->settings['fb_tracking_enabled'] )
			|| empty( $this->settings['fb_tracking_thank_you_page_ids'] )
			|| empty( $this->settings['fb_pixel_id'] )
			|| empty( $_GET['entry_id'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			|| empty( $_GET['fbtrack'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		) {
			return;
		}

		$this->pages_ids = array_filter( array_map( 'intval', explode( ',', $this->settings['fb_tracking_thank_you_page_ids'] ) ) );

		if ( ! is_page( $this->pages_ids ) ) {
			return;
		}

		$entry_id = sanitize_text_field( wp_unslash( $_GET['entry_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$entry    = GFAPI::get_entry( $entry_id );
		$form     = ! empty( $entry['form_id'] ) ? GFAPI::get_form( $entry['form_id'] ) : null;

		$code = $this->get_pixel_code( $this->settings['fb_pixel_id'], $form, $entry );

		printf(
			'<script>%s</script>' . "\n",
			$code // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	/**
	 * Display the FB pixel code for the landing page(s) of completed applications.
	 *
	 * @return void
	 */
	public function add_completed_application_pixel() {
		// Avoid admins.
		if ( current_user_can( 'install_plugins' ) ) {
			return;
		}

		// Check if the settings we need are defined (all of them).
		if (
			empty( $this->settings['fb_tracking_enabled'] )
			|| empty( $this->settings['fb_pixel_id'] )
			|| empty( $this->settings['fb_tracking_completed_app_thank_you_page_ids'] )
			|| empty( $_GET['oa2type'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		) {
			return;
		}

		$application_type = sanitize_text_field( wp_unslash( $_GET['oa2type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$this->pages_ids = array_filter( array_map( 'intval', explode( ',', $this->settings['fb_tracking_completed_app_thank_you_page_ids'] ) ) );

		if ( ! is_page( $this->pages_ids ) ) {
			return;
		}

		$tracking_code_string = 'fbq("init", "%s");
		fbq("track", "PageView");
		fbq("track", "Purchase", {
			content_name: "%s"
		});';

		$code = sprintf(
			$tracking_code_string,
			$this->settings['fb_pixel_id'],
			$application_type
		);

		printf(
			'<script>%s</script>' . "\n",
			$code // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	/**
	 * Dynamically populate the Gravity Forms "fbtrack" field
	 *
	 * @param string $value Existing value for the field.
	 *
	 * @return string
	 */
	public function populate_fbtrack( $value ) {
		$add_on         = Gf_Nus_Addon::get_instance();
		$this->settings = $add_on->get_plugin_settings();

		// Only populate value if there was not one populated via the URL parameter and the setting is on for this form.
		if ( empty( $this->settings['fb_tracking_enabled'] ) || '0' === $value ) {
			return $value;
		}

		return '1';
	}

	/**
	 * Attach the FB tracking pixel push code to the confirmation
	 * ToDo: Need to come up with a way to actually use this. The code won't trigger because the redirect happens too fast.
	 *
	 * @param string  $confirmation The actual confirmation method/message for the form.
	 * @param array   $form         GF form corresponding to the entry.
	 * @param array   $entry        Specific entry we are tracking.
	 * @param boolean $ajax         Whether the confirmation is an ajax request.
	 *
	 * @return string
	 */
	public function add_fb_pixel_to_confirmation( $confirmation, $form, $entry, $ajax ) {
		$add_on   = Gf_Nus_Addon::get_instance();
		$settings = $add_on->get_plugin_settings();

		// Check if the settings we need are defined (all of them).
		if ( empty( $settings['fb_tracking_enabled'] ) || empty( $settings['fb_tracking_thank_you_form_ids'] ) || empty( $settings['fb_pixel_id'] ) ) {
			return $confirmation;
		}

		$fb_tracking_thank_you_form_ids = array_filter( array_map( 'intval', explode( ',', $settings['fb_tracking_thank_you_form_ids'] ) ) );

		// If this is not a form we want to track, bail.
		if ( ! in_array( $form['id'], $fb_tracking_thank_you_form_ids, true ) ) {
			return $confirmation;
		}

		$code = $this->get_pixel_code( $settings['fb_pixel_id'], $form, $entry );

		/**
		 * ToDo: Add an event listener to the "load" event
		 * ToDo: Said load event should trigger the function defintion of gformRedirect or define it?
		 */
		if ( false === stripos( $confirmation, 'gformRedirect' ) ) {
			$confirmation = sprintf( '<script>%s</script>', $code ) . $confirmation;
		} else {
			$confirmation = str_replace( 'gformRedirect(){', 'gformRedirect(){' . $code, $confirmation );
		}

		return $confirmation;
	}

	/**
	 * Get the tracking pixel code
	 *
	 * @param int   $fb_pixel_id The FB provided Pixel ID.
	 * @param array $form     The GF form array.
	 * @param array $entry    The specific GF entry.
	 *
	 * @return string
	 */
	private function get_pixel_code( $fb_pixel_id, $form, $entry ) {
		$tracking_code_string = 'fbq("init", "%s"%s);
		fbq("track", "PageView");
		fbq("track", "Lead"%s);';

		$tracking_code = sprintf(
			$tracking_code_string,
			$fb_pixel_id,
			$this->get_advanced_matching_fields( $form, $entry ),
			$this->get_object_properties( $form, $entry )
		);

		return $tracking_code;
	}

	/**
	 * Get the advanced matching fields for the entry (blank if none are going to get pushed).
	 * Ref: https://developers.facebook.com/docs/facebook-pixel/advanced/advanced-matching
	 *
	 * @param array $form     The GF form array.
	 * @param array $entry    The specific GF entry.
	 *
	 * @return string
	 */
	private function get_advanced_matching_fields( $form, $entry ) {
		$phone        = $this->get_field_value( [ 'phone number', 'phone' ], $entry, $form );
		$country_code = $this->get_field_value( 'country-code', $entry, $form );
		$phone_number = '1' === $country_code ? $country_code . $phone : str_replace( '+', '', $phone );
		$zipcode      = $this->get_field_value( [ 'zip', 'zipcode', 'zip code', 'nu_zip' ], $entry, $form );
		$zipcode      = intval( $zipcode ) ? $zipcode : '';

		$parameters = [
			'fn'      => $this->get_field_value( 'first name', $entry, $form, 'label' ),
			'ln'      => $this->get_field_value( 'last name', $entry, $form, 'label' ),
			'em'      => $this->get_field_value( [ 'email address', 'email' ], $entry, $form ),
			'ph'      => $phone_number,
			'st'      => $this->get_field_value( 'state', $entry, $form ),
			'zp'      => $zipcode,
			'country' => $this->get_field_value( 'iso-country-code', $entry, $form ),
		];

		// Remove any empty ones.
		foreach ( $parameters as $key => $value ) {
			if ( '' === trim( $value ) ) {
				unset( $parameters[ $key ] );
			}
		}

		return ! empty( $parameters ) ? ', ' . wp_json_encode( $parameters ) : '';
	}

	/**
	 * Get the object properties for the Lead (blank if none are going to get pushed).
	 * Ref: https://developers.facebook.com/docs/facebook-pixel/implementation/conversion-tracking
	 *
	 * @param array $form     The GF form array.
	 * @param array $entry    The specific GF entry.
	 *
	 * @return string
	 */
	private function get_object_properties( $form, $entry ) {
		$military = $this->get_field_value( [ 'military', 'military checkbox' ], $entry, $form );
		$military = ! empty( trim( $military ) ) ? 'yes' : 'no';
		$program  = $this->get_field_value( 'program', $entry, $form );
		$object   = [
			'content_type'     => $this->get_field_value( [ 'degree', 'degree type', 'populate-hidden-degree-type' ], $entry, $form ),
			'content_name'     => $program,
			'content_category' => '',
			'status'           => $military,
		];

		$program_object = wpcom_vip_get_page_by_title( $program, OBJECT, 'program' );
		if ( ! empty( $program_object->ID ) ) {
			$program_areas = get_the_terms( $program_object->ID, 'area-of-study' );
			if ( ! empty( $program_areas[0] ) ) {
				$object['content_category'] = wp_specialchars_decode( $program_areas[0]->name );
			}
		}

		// If for any reason the degree type is empty (e.g. "Area" marketing landing pages).
		if ( empty( $object['content_type'] ) && ! empty( $program_object->ID ) ) {
			$degree_types = get_the_terms( $program_object->ID, 'degree-type' );
			if ( ! empty( $program_areas[0] ) ) {
				$object['content_type'] = $degree_types[0]->slug;
			}
		}

		// Remove any empty ones.
		foreach ( $object as $key => $value ) {
			if ( '' === trim( $value ) && 'status' !== $key ) {
				unset( $object[ $key ] );
			}
		}

		return ! empty( $object ) ? ', ' . wp_json_encode( $object ) : '';
	}

	/**
	 * Utility function to get an entry's value for a specific field
	 *
	 * @param mixed $field_targets The strings to attempt to try to get, sometimes zip is defined as "zip", "zipcode", or "zip code".
	 * @param array $entry         Entry for which to search in.
	 * @param array $form          Corresponding form for the entry.
	 * @param array $field_types   Field types that we want to search the value as.
	 *
	 * @return string
	 */
	private function get_field_value( $field_targets, $entry, $form, $field_types = [ 'type', 'label', 'inputName' ] ) {
		$field_id      = false;
		$field_types   = is_array( $field_types ) ? $field_types : [ $field_types ];
		$field_targets = is_array( $field_targets ) ? $field_targets : [ $field_targets ];

		foreach ( $field_targets as $target ) {
			$found = false;

			foreach ( $field_types as $type ) {
				$field_id = $this->get_field_id( $form, $target, $type );
				if ( false !== $field_id ) {
					$found = true;
					break;
				}
			}

			if ( $found ) {
				break;
			}
		}

		if ( false !== $field_id && isset( $entry[ $field_id . '.1' ] ) ) {
			$field_value = $entry[ $field_id . '.1' ];
		} else {
			$field_value = false !== $field_id ? $entry[ $field_id ] : '';
		}

		return $field_value;
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
