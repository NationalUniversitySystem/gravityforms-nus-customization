<?php
/**
 * File structure and definition can be found over at:
 * https://github.com/richardW8k/simplefieldaddon
 *
 * READ THIS!!!
 * - the file names are all held in a protected variable.
 * -- The file names get used for the following purposes:
 * --- To include the file
 * --- From this class we call specific methods (e.g. tooltip) kept in the individual class/file for organization.
 */

GFForms::include_addon_framework();

/**
 * Gf_Nus_Addon class
 */
class Gf_Nus_Addon extends GFAddOn {
	/**
	 * Version number of the Add-On
	 *
	 * @var string
	 */
	protected $_version = GF_NUS_VER;

	/**
	 * Gravity Forms minimum version requirement
	 *
	 * @var string
	 */
	protected $_min_gravityforms_version = '2';

	/**
	 * URL-friendly identifier used for form settings, add-on settings, text domain localization...
	 *
	 * @var string
	 */
	protected $_slug = 'gf-nus-addon';

	/**
	 * Relative path to the plugin from the plugins folder
	 *
	 * @var string
	 */
	protected $_path = 'gravityforms-nus-customization/gravityforms-nus-customization.php';

	/**
	 * Full path the the plugin
	 *
	 * @var string
	 */
	protected $_full_path = __FILE__;

	/**
	 * Title of the plugin to be used on the settings page, form settings and plugins page
	 *
	 * @var string
	 */
	protected $_title = 'Gravity Forms - NUS Customization';

	/**
	 * Short version of the plugin title to be used on menus and other places where a less verbose string is useful
	 *
	 * @var string
	 */
	protected $_short_title = 'GF Nus Addon';

	/**
	 * If available, contains an instance of this class.
	 *
	 * @var object
	 */
	private static $_instance = null;

	/**
	 * Class names of each field so methods have easy access to them for loops.
	 *
	 * @var array
	 */
	private $class_names = [];

	/**
	 * All the files that hold our custom fields
	 *
	 * @var array
	 */
	private $custom_field_files = [
		'fields/class-gdpr-field.php',
		'fields/class-military-field.php',
		'fields/class-nu-country-field.php',
		'fields/class-nu-state-field.php',
		'fields/class-degree-field.php',
		'fields/class-program-field.php',
		'fields/class-start-month-field.php',
		'fields/class-start-year-field.php',
		'fields/class-nu-phone-field.php',
		'fields/class-nu-honey-field.php',
		'fields/class-nu-zip-field.php',
		'fields/class-country-code-field.php',
		'fields/class-programs-by-college-field.php',
	];

	/**
	 * Returns an instance of this class, and stores it in the $_instance property.
	 *
	 * @return object $_instance An instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Include the fields early so they are available when entry exports are being performed.
	 */
	public function pre_init() {
		parent::pre_init();

		if ( $this->is_gravityforms_supported() && class_exists( 'GF_Field' ) ) {
			foreach ( $this->custom_field_files as $file_name ) {
				if ( file_exists( GF_NUS_PATH . $file_name ) ) {
					require_once GF_NUS_PATH . $file_name;

					// Save all the class names in a property for the rest of the methods to access.
					$class_name = preg_replace( '/^class-/', '', basename( $file_name, '.php' ) );
					$class_name = implode( '_', array_map( 'ucfirst', explode( '-', $class_name ) ) );

					if ( class_exists( $class_name ) ) {
						$this->class_names[] = $class_name;
					}
				}
			}
		}

		$this->register_fields_and_call_hooks();
	}

	/**
	 * Register the fields using the field classes that exist
	 *
	 * @return void
	 */
	private function register_fields_and_call_hooks() {
		foreach ( $this->class_names as $class_name ) {

			${ $class_name } = new $class_name();
			GF_Fields::register( ${ $class_name } );

			if ( method_exists( $class_name, 'add_hooks' ) ) {
				${ $class_name }->add_hooks();
			}
		}
	}

	/**
	 * Override this function to add initialization code (i.e. hooks) for the admin site (WP dashboard)
	 */
	public function init_admin() {
		parent::init_admin();

		add_filter( 'gform_tooltips', [ $this, 'tooltips' ] );
		add_action( 'gform_field_standard_settings', [ $this, 'add_standard_settings' ], 10, 2 );
		add_action( 'gform_editor_js', [ $this, 'call_save_settings_methods' ], 10, 2 );
	}

	/**
	 * Add the tooltips for the fields
	 * The actual definition is on a per custom field/file basis,
	 * to keep things related to that field together and our codebase organized.
	 *
	 * @param array $tooltips An associative array of tooltips where the key is the tooltip name and the value is the tooltip.
	 *
	 * @return array
	 */
	public function tooltips( $tooltips ) {
		$addon_tooltips = [];

		foreach ( $this->class_names as $class_name ) {
			if ( method_exists( $class_name, 'add_tooltip' ) ) {
				$addon_tooltips = $class_name::add_tooltip( $addon_tooltips );
			}
		}

		return array_merge( $tooltips, $addon_tooltips );
	}

	/**
	 * Add the custom setting for the fields by calling the correspoding method in each file/class.
	 *
	 * @param int $position The position the settings should be located at.
	 * @param int $form_id The ID of the form currently being edited.
	 *
	 * @return void
	 */
	public function add_standard_settings( $position, $form_id ) {
		foreach ( $this->class_names as $class_name ) {
			if ( method_exists( $class_name, 'add_standard_settings' ) ) {
				$class_name::add_standard_settings( $position, $form_id );
			}
		}
	}

	/**
	 * Add the custom setting for the fields by calling the correspoding method in each file/class.
	 *
	 * @return void
	 */
	public function call_save_settings_methods() {
		foreach ( $this->class_names as $class_name ) {
			if ( method_exists( $class_name, 'save_settings' ) ) {
				$class_name::save_settings();
			}
		}
	}

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return [
			[
				'title'       => esc_html__( 'FB Pixel Tracking', 'national-university' ),
				'description' => esc_html__( 'These settings are the setup for using the tracking pixel on the thank you pages/messages.', 'national-university' ),
				'fields'      => [
					[
						'name'    => 'status',
						'type'    => 'checkbox',
						'label'   => esc_html__( 'Global status', 'national-university' ),
						'tooltip' => esc_html__( 'If this is not checked, no tracking will be added to <em>any</em> form.', 'national-university' ),
						'choices' => [
							[
								'name'  => 'fb_tracking_enabled',
								'label' => esc_html__( 'Enabled', 'national-university' ),
							],
						],
					],
					[
						'name'        => 'fb_tracking_thank_you_page_ids',
						'type'        => 'text',
						'class'       => 'medium',
						'label'       => esc_html__( 'Page IDs', 'national-university' ),
						'description' => esc_html__( 'Comma delimited values.', 'national-university' ),
						'tooltip'     => esc_html__( 'The IDs of the pages we want to add the tracking pixel to. This also depends if the form is passing the entry ID to the thank you page.', 'national-university' ),
					],
					/* [
						'name'        => 'fb_tracking_fb_tracking_thank_you_form_ids',
						'type'        => 'text',
						'class'       => 'medium',
						'label'       => esc_html__( 'Form IDs', 'national-university' ),
						'description' => esc_html__( 'Comma delimited values.', 'national-university' ),
						'tooltip'     => esc_html__( 'The IDs of the forms we want to add the tracking pixel to.', 'national-university' ),
					], */
					[
						'name'    => 'fb_pixel_id',
						'type'    => 'text',
						'class'   => 'small',
						'label'   => esc_html__( 'Pixel ID', 'national-university' ),
						'tooltip' => esc_html__( 'Get this from analytics team or FB yourself.', 'national-university' ),
					],
				],
			],
			[
				'title'       => esc_html__( 'Blocked Domains', 'national-university' ),
				'description' => esc_html__( 'These settings are entering CSV blocked domains for the RFI. Entries will still submit to WP database, but will be prevented from having any webhooks firing', 'national-university' ),
				'fields'      => [
					[
						'name'        => 'blocked_domains',
						'type'        => 'text',
						'class'       => 'medium',
						'label'       => esc_html__( 'Blocked Domains', 'national-university' ),
						'description' => esc_html__( 'Comma delimited values. ( e.g. @qq.com )', 'national-university' ),
					],
				],
			],
		];
	}
}
