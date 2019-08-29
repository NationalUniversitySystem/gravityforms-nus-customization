<?php
/**
 * Our custom Country select Field
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Creates custom Country select field
 */
class Nu_Country_Field extends GF_Field_Select {
	/**
	 * The field type
	 *
	 * @var string $type
	 */
	public $type = 'nu_country';

	/**
	 * Register hooks.
	 */
	public function add_hooks() {
		add_action( 'gform_editor_js_set_default_values', [ $this, 'set_default_values' ] );
		add_filter( 'gform_field_content', [ $this, 'custom_html' ], 10, 5 );
		add_filter( 'gform_field_container', [ $this, 'custom_field_container' ], 10, 6 );
	}

	/**
	 * Return the field title, for use in the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'NU Country', 'national-university' );
	}

	/**
	 * Define the values/display field
	 *
	 * @return void
	 */
	public static function set_default_values() {
		?>
		case "nu_country" :
			field.label = "Country";
			field.isRequired = true;
			field.choices = new Array (
				<?php
				// Create our array of countries.
				// Note: keys are currently not used.
				$countries = [
					'AF' => 'Afghanistan',
					'AL' => 'Albania',
					'DZ' => 'Algeria',
					'AS' => 'American Samoa',
					'AD' => 'Andorra',
					'AO' => 'Angola',
					'AI' => 'Anguilla',
					'AG' => 'Antigua And Barbuda',
					'AR' => 'Argentina',
					'AM' => 'Armenia',
					'AW' => 'Aruba',
					'AU' => 'Australia',
					'AT' => 'Austria',
					'AZ' => 'Azerbaijan',
					'BS' => 'Bahamas',
					'BH' => 'Bahrain',
					'BD' => 'Bangladesh',
					'BB' => 'Barbados',
					'BY' => 'Belarus',
					'BE' => 'Belgium',
					'BZ' => 'Belize',
					'BJ' => 'Benin',
					'BM' => 'Bermuda',
					'BT' => 'Bhutan',
					'BO' => 'Bolivia',
					'BA' => 'Bosnia And Herzegovina',
					'BW' => 'Botswana',
					'BR' => 'Brazil',
					'IO' => 'British Indian Ocean Territory',
					'BN' => 'Brunei',
					'BG' => 'Bulgaria',
					'BF' => 'Burkina Faso',
					'BI' => 'Burundi',
					'KH' => 'Cambodia',
					'CM' => 'Cameroon',
					'CA' => 'Canada',
					'CV' => 'Cape Verde',
					'KY' => 'Cayman Islands',
					'CF' => 'Central African Republic',
					'TD' => 'Chad',
					'CL' => 'Chile',
					'CN' => 'China',
					'CO' => 'Colombia',
					'CG' => 'Congo',
					'CK' => 'Cook Islands',
					'CR' => 'Costa Rica',
					'CI' => 'Cote D\'ivoire',
					'HR' => 'Croatia',
					'CU' => 'Cuba',
					'CY' => 'Cyprus',
					'CZ' => 'Czech Republic',
					'CD' => 'Democratic Republic of the Congo',
					'DK' => 'Denmark',
					'DJ' => 'Djibouti',
					'DM' => 'Dominica',
					'DO' => 'Dominican Republic',
					'EC' => 'Ecuador',
					'EG' => 'Egypt',
					'SV' => 'El Salvador',
					'GQ' => 'Equatorial Guinea',
					'ER' => 'Eritrea',
					'EE' => 'Estonia',
					'ET' => 'Ethiopia',
					'FO' => 'Faroe Islands',
					'FM' => 'Federated States Of Micronesia',
					'FJ' => 'Fiji',
					'FI' => 'Finland',
					'FR' => 'France',
					'GF' => 'French Guiana',
					'PF' => 'French Polynesia',
					'GA' => 'Gabon',
					'GM' => 'Gambia',
					'GE' => 'Georgia',
					'DE' => 'Germany',
					'GH' => 'Ghana',
					'GI' => 'Gibraltar',
					'GR' => 'Greece',
					'GL' => 'Greenland',
					'GD' => 'Grenada',
					'GP' => 'Guadeloupe',
					'GU' => 'Guam',
					'GT' => 'Guatemala',
					'GN' => 'Guinea',
					'GW' => 'Guinea Bissau',
					'GY' => 'Guyana',
					'HT' => 'Haiti',
					'HN' => 'Honduras',
					'HK' => 'Hong Kong',
					'HU' => 'Hungary',
					'IS' => 'Iceland',
					'IN' => 'India',
					'ID' => 'Indonesia',
					'IR' => 'Iran',
					'IE' => 'Ireland',
					'IL' => 'Israel',
					'IT' => 'Italy',
					'JM' => 'Jamaica',
					'JP' => 'Japan',
					'JO' => 'Jordan',
					'KZ' => 'Kazakhstan',
					'KE' => 'Kenya',
					'KW' => 'Kuwait',
					'KG' => 'Kyrgyzstan',
					'LA' => 'Laos',
					'LV' => 'Latvia',
					'LB' => 'Lebanon',
					'LS' => 'Lesotho',
					'LY' => 'Libyan Arab Jamahiriya',
					'LI' => 'Liechtenstein',
					'LT' => 'Lithuania',
					'LU' => 'Luxembourg',
					'MK' => 'Macedonia',
					'MG' => 'Madagascar',
					'MW' => 'Malawi',
					'MY' => 'Malaysia',
					'MV' => 'Maldives',
					'ML' => 'Mali',
					'MT' => 'Malta',
					'MQ' => 'Martinique',
					'MR' => 'Mauritania',
					'MU' => 'Mauritius',
					'MX' => 'Mexico',
					'MC' => 'Monaco',
					'MN' => 'Mongolia',
					'ME' => 'Montenegro',
					'MA' => 'Morocco',
					'MZ' => 'Mozambique',
					'MM' => 'Myanmar',
					'NA' => 'Namibia',
					'NP' => 'Nepal',
					'NL' => 'Netherlands',
					'AN' => 'Netherlands Antilles',
					'NC' => 'New Caledonia',
					'NZ' => 'New Zealand',
					'NI' => 'Nicaragua',
					'NE' => 'Niger',
					'NG' => 'Nigeria',
					'NF' => 'Norfolk Island',
					'MP' => 'Northern Mariana Islands',
					'NO' => 'Norway',
					'OM' => 'Oman',
					'PK' => 'Pakistan',
					'PW' => 'Palau',
					'PA' => 'Panama',
					'PG' => 'Papua New Guinea',
					'PY' => 'Paraguay',
					'PE' => 'Peru',
					'PH' => 'Philippines',
					'PL' => 'Poland',
					'PT' => 'Portugal',
					'PR' => 'Puerto Rico',
					'QA' => 'Qatar',
					'MD' => 'Republic Of Moldova',
					'RE' => 'Reunion',
					'RO' => 'Romania',
					'RU' => 'Russia',
					'RW' => 'Rwanda',
					'KN' => 'Saint Kitts And Nevis',
					'LC' => 'Saint Lucia',
					'VC' => 'Saint Vincent And The Grenadines',
					'WS' => 'Samoa',
					'SM' => 'San Marino',
					'ST' => 'Sao Tome And Principe',
					'SA' => 'Saudi Arabia',
					'SN' => 'Senegal',
					'RS' => 'Serbia',
					'SC' => 'Seychelles',
					'SG' => 'Singapore',
					'SK' => 'Slovakia',
					'SI' => 'Slovenia',
					'SB' => 'Solomon Islands',
					'ZA' => 'South Africa',
					'KR' => 'South Korea',
					'ES' => 'Spain',
					'LK' => 'Sri Lanka',
					'SD' => 'Sudan',
					'SR' => 'Suriname',
					'SZ' => 'Swaziland',
					'SE' => 'Sweden',
					'CH' => 'Switzerland',
					'SY' => 'Syrian Arab Republic',
					'TW' => 'Taiwan',
					'TJ' => 'Tajikistan',
					'TZ' => 'Tanzania',
					'TH' => 'Thailand',
					'TG' => 'Togo',
					'TO' => 'Tonga',
					'TT' => 'Trinidad And Tobago',
					'TN' => 'Tunisia',
					'TR' => 'Turkey',
					'TM' => 'Turkmenistan',
					'UG' => 'Uganda',
					'UA' => 'Ukraine',
					'AE' => 'United Arab Emirates',
					'GB' => 'United Kingdom',
					'US' => 'USA',
					'UY' => 'Uruguay',
					'UZ' => 'Uzbekistan',
					'VU' => 'Vanuatu',
					'VE' => 'Venezuela',
					'VN' => 'Vietnam',
					'VG' => 'Virgin Islands British',
					'VI' => 'Virgin Islands U.S.',
					'YE' => 'Yemen',
					'ZM' => 'Zambia',
					'ZW' => 'Zimbabwe',
				];

				foreach ( $countries as $country ) {
					?>
					new Choice("<?php echo esc_html( $country ); ?>"),
					<?php
				}
				?>
			)
			break;
		<?php
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
		if ( is_admin() || 'nu_country' !== $field->type ) {
			return $field_content;
		}

		$content = '';

		// If field is set to require in admin add our required html so Gravity Forms knows what to do.
		$required_aria = ( true === $field->isRequired ) ? 'aria-required="true"' : 'aria-required="false"'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
		$required      = ( true === $field->isRequired ) ? '<span class="required-label">*</span>' : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		// Get our input ID to use throughout.
		$name = 'input_' . esc_attr( $field->id );

		if ( $field->description ) {
			// If field has a description FOR SCREEN READERS.
			$content .= '<span class="form__description sr-only" id="' . $name . '_desc">Instructions for ' . $field->label . ' input: ' . $field->description . '</span>';
		}

		// Create our variable to store our <options>.
		$choices = '<option value="" selected disabled="disabled"></option>';
		// If the field has choices, loop through them.
		if ( $field->choices ) {
			// Go through all the possible choices assigned to the select in the admin.
			foreach ( $field->choices as $choice ) {
				$selected = $choice['text'] === $value ? ' selected' : '';
				// Add all the <options> to our variable to use below.
				$choices .= '<option value="' . $choice['text'] . '"' . $selected . '>' . $choice['text'] . '</option>';
			}
		}

		$content .= '<label class="form__label" for="input_' . $form_id . '_' . $field->id . '">' . $field->label . $required . '</label>';

		if ( ! $field->enableEnhancedUI ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
			$content .= '<select ';
			$content .= 'class="input input--select input--styled" ';
			$content .= 'name="' . $name . '" ';
			$content .= $required_aria;
			$content .= ' value="' . esc_attr( $value );
			$content .= '" id="input_' . $form_id . '_' . $field->id . '">';
			$content .= $choices;
			$content .= '</select>';
		} else {
			$content .= sprintf(
				'<input class="input input--select input--styled" list="%s" id="%s" name="%s" %s />',
				'input_' . $form_id . '_' . $field->id . '-list',
				'input_' . $form_id . '_' . $field->id,
				$name,
				$required_aria
			);
			$content .= '<datalist id="input_' . $form_id . '_' . $field->id . '-list" >';
			$content .= $choices;
			$content .= '</datalist>';
		}

		// If field has a description.
		if ( $field->description ) {
			// Then lets show it.
			$content .= '<span class="form__description" aria-hidden="true">' . $field->description . '</span>';
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
		if ( 'nu_country' !== $field->type ) {
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

		$custom_classes .= 'country--select';

		// Setup how our field_id is displayed.
		$field_id = is_admin() || empty( $form ) ? "field_{$id}" : 'field_' . $form['id'] . "_$id";

		// Create our new <li>.
		return '<li id="' . $field_id . '" class="form__group ' . $custom_classes . ' ' . $css_class . '">{FIELD_CONTENT}</li>';
	}
}
