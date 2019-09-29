<?php
/**
 * Custom field: Country_Code_Field
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Hooks to set defaults and prepend the code are at bottom of file.
 * Fetches country codes via class method
 *
 * Note: Functionality also adds a data attribute in the markup for ISO country codes.
 * To utilize this, add a hidden field with the label "iso-country-code" in the form fields.
 * Javascript from this plugin will take care of populating it's value when the select field from here
 * is populated.
 */
class Country_Code_Field extends GF_Field_Select {
	/**
	 * The field type
	 *
	 * @var string $type
	 */
	public $type = 'country-code';

	/**
	 * Register hooks.
	 */
	public function add_hooks() {
		add_action( 'gform_editor_js_set_default_values', [ $this, 'set_default_values' ] );
		add_action( 'gform_pre_submission', 'Country_Code_Field::add_country_to_webhooks', 10, 1 );
		add_filter( 'gform_field_css_class', [ $this, 'modify_field_container_classes' ], 10, 3 );
	}

	/**
	 * Return the field title, for use in the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'Country Code', 'national-university' );
	}

	/**
	 * Assign the field button to the Custom Fields group.
	 *
	 * @return array
	 */
	public function get_form_editor_button() {
		return [
			'group' => 'nu_fields',
			'text'  => $this->get_form_editor_field_title(),
		];
	}

	/**
	 * The settings which should be available on the field in the form editor.
	 *
	 * @return array
	 */
	public function get_form_editor_field_settings() {
		return [
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'enable_enhanced_ui_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'size_setting',
			'choices_setting',
			'rules_setting',
			'placeholder_setting',
			'default_value_setting',
			'visibility_setting',
			'duplicate_setting',
			'description_setting',
			'css_class_setting',
		];
	}

	/**
	 * Enable this field for use with conditional logic.
	 *
	 * @return bool
	 */
	public function is_conditional_logic_supported() {
		return true;
	}

	/**
	 * Define the fields inner markup.
	 *
	 * @param array        $form The Form Object currently being processed.
	 * @param string|array $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array   $entry Null or the Entry Object currently being edited.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		$form_id         = absint( $form['id'] );
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$id       = $this->id;
		$field_id = $is_entry_detail || $is_form_editor || 0 === $form_id ? "input_$id" : 'input_' . $form_id . "_$id";

		$size               = $this->size;
		$class_suffix       = $is_entry_detail ? '_admin' : '';
		$class              = $size . $class_suffix;
		$css_class          = trim( esc_attr( $class ) . ' input input--select input--styled' );
		$disabled_text      = $is_form_editor ? 'disabled="disabled"' : '';
		$required_attribute = $this->isRequired ? 'aria-required="true"' : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
		$invalid_attribute  = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';

		return sprintf(
			"<select name='input_%d' id='%s' class='%s' %s %s %s>%s</select>",
			$id,
			$field_id,
			$css_class,
			$disabled_text,
			$required_attribute,
			$invalid_attribute,
			$this->get_choices( $value )
		);
	}

	/**
	 * Fetch the choices for the countries
	 *
	 * @param string $selected_country The selected country value, if any.
	 *
	 * @return string
	 */
	public function get_choices( $selected_country ) {
		$choices          = '';
		$selected_country = strtolower( $selected_country );
		$countries        = array_merge( [ '' ], $this->get_country_codes() );
		foreach ( $countries as $country_data ) {
			$country_name  = ! empty( $country_data['country_name'] ) ? $country_data['country_name'] : '';
			$dialing_code  = ! empty( $country_data['dialing_code'] ) ? $country_data['dialing_code'] : '';
			$country_code  = ! empty( $country_data['iso_country_code'] ) ? esc_attr( $country_data['iso_country_code'] ) : '';
			$country_code  = '' !== $country_code ? ' data-iso-country-code="' . $country_code . '"' : '';
			$name_data     = '' !== $country_name ? ' data-country-name="' . $country_name . '"' : '';
			$country_label = $country_name;

			if ( is_numeric( $country_name ) ) {
				$country_name = $dialing_code;
			}
			if ( empty( $dialing_code ) ) {
				$country_name = ''; // Placeholder.
			}
			if ( '' !== $country_name ) {
				$country_label .= ' (+' . $dialing_code . ')';
			}

			$selected = strtolower( $country_name ) === $selected_country ? ' selected="selected"' : '';
			$choices .= sprintf(
				'<option value="%s"%s%s%s>%s</option>' . "\n",
				esc_attr( $dialing_code ),
				$country_code,
				$selected,
				$name_data,
				esc_html( $country_label )
			);
		}

		return $choices;
	}

	/**
	 * Class method to fetch the countries and country calling codes
	 * Sourced from
	 * - https://github.com/etjossem/country-codes-html/blob/master/_country_codes.html
	 * - Altered to fit our Eloqua and OnDemand list of countries.
	 *
	 * @return array
	 */
	public static function get_country_codes() {
		return [
			[
				'iso_country_code' => 'US',
				'dialing_code'     => '1',
				'country_name'     => 'USA',
			],
			[
				'iso_country_code' => 'IN',
				'dialing_code'     => '91',
				'country_name'     => 'India',
			],
			[
				'iso_country_code' => 'DE',
				'dialing_code'     => '49',
				'country_name'     => 'Germany',
			],
			[
				'iso_country_code' => 'GB',
				'dialing_code'     => '44',
				'country_name'     => 'United Kingdom',
			],
			[
				'iso_country_code' => 'AF',
				'dialing_code'     => '93',
				'country_name'     => 'Afghanistan',
			],
			[
				'iso_country_code' => 'AX',
				'dialing_code'     => '358',
				'country_name'     => 'Aland Islands',
			],
			[
				'iso_country_code' => 'AL',
				'dialing_code'     => '355',
				'country_name'     => 'Albania',
			],
			[
				'iso_country_code' => 'DZ',
				'dialing_code'     => '213',
				'country_name'     => 'Algeria',
			],
			[
				'iso_country_code' => 'AS',
				'dialing_code'     => '1684',
				'country_name'     => 'American Samoa',
			],
			[
				'iso_country_code' => 'AD',
				'dialing_code'     => '376',
				'country_name'     => 'Andorra',
			],
			[
				'iso_country_code' => 'AO',
				'dialing_code'     => '244',
				'country_name'     => 'Angola',
			],
			[
				'iso_country_code' => 'AI',
				'dialing_code'     => '1264',
				'country_name'     => 'Anguilla',
			],
			[
				'iso_country_code' => 'AG',
				'dialing_code'     => '1268',
				'country_name'     => 'Antigua and Barbuda',
			],
			[
				'iso_country_code' => 'AR',
				'dialing_code'     => '54',
				'country_name'     => 'Argentina',
			],
			[
				'iso_country_code' => 'AM',
				'dialing_code'     => '374',
				'country_name'     => 'Armenia',
			],
			[
				'iso_country_code' => 'AW',
				'dialing_code'     => '297',
				'country_name'     => 'Aruba',
			],
			[
				'iso_country_code' => 'SH-AC',
				'dialing_code'     => '247',
				'country_name'     => 'Ascension Island',
			],
			[
				'iso_country_code' => 'AU',
				'dialing_code'     => '61',
				'country_name'     => 'Australia',
			],
			[
				'iso_country_code' => 'AT',
				'dialing_code'     => '43',
				'country_name'     => 'Austria',
			],
			[
				'iso_country_code' => 'AZ',
				'dialing_code'     => '994',
				'country_name'     => 'Azerbaijan',
			],
			[
				'iso_country_code' => 'BS',
				'dialing_code'     => '1242',
				'country_name'     => 'Bahamas',
			],
			[
				'iso_country_code' => 'BH',
				'dialing_code'     => '973',
				'country_name'     => 'Bahrain',
			],
			[
				'iso_country_code' => 'BD',
				'dialing_code'     => '880',
				'country_name'     => 'Bangladesh',
			],
			[
				'iso_country_code' => 'BB',
				'dialing_code'     => '1246',
				'country_name'     => 'Barbados',
			],
			[
				'iso_country_code' => 'BY',
				'dialing_code'     => '375',
				'country_name'     => 'Belarus',
			],
			[
				'iso_country_code' => 'BE',
				'dialing_code'     => '32',
				'country_name'     => 'Belgium',
			],
			[
				'iso_country_code' => 'BZ',
				'dialing_code'     => '501',
				'country_name'     => 'Belize',
			],
			[
				'iso_country_code' => 'BJ',
				'dialing_code'     => '229',
				'country_name'     => 'Benin',
			],
			[
				'iso_country_code' => 'BM',
				'dialing_code'     => '1441',
				'country_name'     => 'Bermuda',
			],
			[
				'iso_country_code' => 'BT',
				'dialing_code'     => '975',
				'country_name'     => 'Bhutan',
			],
			[
				'iso_country_code' => 'BO',
				'dialing_code'     => '591',
				'country_name'     => 'Bolivia',
			],
			[
				'iso_country_code' => 'BQ',
				'dialing_code'     => '5997',
				'country_name'     => 'Bonaire, Sint Eustatius and Saba',
			],
			[
				'iso_country_code' => 'BA',
				'dialing_code'     => '387',
				'country_name'     => 'Bosnia and Herzegovina',
			],
			[
				'iso_country_code' => 'BW',
				'dialing_code'     => '267',
				'country_name'     => 'Botswana',
			],
			[
				'iso_country_code' => 'BV',
				'dialing_code'     => '55',
				'country_name'     => 'Bouvet Island',
			],
			[
				'iso_country_code' => 'BR',
				'dialing_code'     => '55',
				'country_name'     => 'Brazil',
			],
			[
				'iso_country_code' => 'IO',
				'dialing_code'     => '246',
				'country_name'     => 'British Indian Ocean Territory',
			],
			[
				'iso_country_code' => 'BN',
				'dialing_code'     => '673',
				'country_name'     => 'Brunei Darussalam',
			],
			[
				'iso_country_code' => 'BG',
				'dialing_code'     => '359',
				'country_name'     => 'Bulgaria',
			],
			[
				'iso_country_code' => 'BF',
				'dialing_code'     => '226',
				'country_name'     => 'Burkina Faso',
			],
			[
				'iso_country_code' => 'BI',
				'dialing_code'     => '257',
				'country_name'     => 'Burundi',
			],
			[
				'iso_country_code' => 'KH',
				'dialing_code'     => '855',
				'country_name'     => 'Cambodia',
			],
			[
				'iso_country_code' => 'CM',
				'dialing_code'     => '237',
				'country_name'     => 'Cameroon',
			],
			[
				'iso_country_code' => 'CA',
				'dialing_code'     => '1',
				'country_name'     => 'Canada',
			],
			[
				'iso_country_code' => 'CV',
				'dialing_code'     => '238',
				'country_name'     => 'Cape Verde',
			],
			[
				'iso_country_code' => 'KY',
				'dialing_code'     => '1345',
				'country_name'     => 'Cayman Islands',
			],
			[
				'iso_country_code' => 'CF',
				'dialing_code'     => '236',
				'country_name'     => 'Central African Republic',
			],
			[
				'iso_country_code' => 'TD',
				'dialing_code'     => '235',
				'country_name'     => 'Chad',
			],
			[
				'iso_country_code' => 'GB-CHA',
				'dialing_code'     => '44',
				'country_name'     => 'Channel Islands',
			],
			[
				'iso_country_code' => 'CL',
				'dialing_code'     => '56',
				'country_name'     => 'Chile',
			],
			[
				'iso_country_code' => 'CN',
				'dialing_code'     => '86',
				'country_name'     => 'China',
			],
			[
				'iso_country_code' => 'CX',
				'dialing_code'     => '61',
				'country_name'     => 'Christmas Island',
			],
			[
				'iso_country_code' => 'CC',
				'dialing_code'     => '61',
				'country_name'     => 'Cocos (Keeling) Islands',
			],
			[
				'iso_country_code' => 'CO',
				'dialing_code'     => '57',
				'country_name'     => 'Colombia',
			],
			[
				'iso_country_code' => 'KM',
				'dialing_code'     => '269',
				'country_name'     => 'Comoros',
			],
			[
				'iso_country_code' => 'CG',
				'dialing_code'     => '242',
				'country_name'     => 'Congo',
			],
			[
				'iso_country_code' => 'CD',
				'dialing_code'     => '243',
				'country_name'     => 'Congo, Democratic Republic of',
			],
			[
				'iso_country_code' => 'CK',
				'dialing_code'     => '682',
				'country_name'     => 'Cook Islands',
			],
			[
				'iso_country_code' => 'CR',
				'dialing_code'     => '506',
				'country_name'     => 'Costa Rica',
			],
			[
				'iso_country_code' => 'HR',
				'dialing_code'     => '385',
				'country_name'     => 'Croatia',
			],
			[
				'iso_country_code' => 'CU',
				'dialing_code'     => '53',
				'country_name'     => 'Cuba',
			],
			[
				'iso_country_code' => 'CW',
				'dialing_code'     => '599',
				'country_name'     => 'Curacao',
			],
			[
				'iso_country_code' => 'CW',
				'dialing_code'     => '599',
				'country_name'     => 'Curaçao',
			],
			[
				'iso_country_code' => 'CY',
				'dialing_code'     => '357',
				'country_name'     => 'Cyprus',
			],
			[
				'iso_country_code' => 'CZ',
				'dialing_code'     => '420',
				'country_name'     => 'Czech Republic',
			],
			[
				'iso_country_code' => 'DK',
				'dialing_code'     => '45',
				'country_name'     => 'Denmark',
			],
			[
				'iso_country_code' => 'DJ',
				'dialing_code'     => '253',
				'country_name'     => 'Djibouti',
			],
			[
				'iso_country_code' => 'DM',
				'dialing_code'     => '1809',
				'country_name'     => 'Dominica',
			],
			[
				'iso_country_code' => 'DO',
				'dialing_code'     => '1809',
				'country_name'     => 'Dominican Republic',
			],
			[
				'iso_country_code' => 'TL',
				'dialing_code'     => '670',
				'country_name'     => 'East Timor',
			],
			[
				'iso_country_code' => 'EC',
				'dialing_code'     => '593',
				'country_name'     => 'Ecuador',
			],
			[
				'iso_country_code' => 'EG',
				'dialing_code'     => '20',
				'country_name'     => 'Egypt',
			],
			[
				'iso_country_code' => 'SV',
				'dialing_code'     => '503',
				'country_name'     => 'El Salvador',
			],
			[
				'iso_country_code' => 'GQ',
				'dialing_code'     => '240',
				'country_name'     => 'Equatorial Guinea',
			],
			[
				'iso_country_code' => 'ER',
				'dialing_code'     => '291',
				'country_name'     => 'Eritrea',
			],
			[
				'iso_country_code' => 'EE',
				'dialing_code'     => '372',
				'country_name'     => 'Estonia',
			],
			[
				'iso_country_code' => 'ET',
				'dialing_code'     => '251',
				'country_name'     => 'Ethiopia',
			],
			[
				'iso_country_code' => 'FK',
				'dialing_code'     => '500',
				'country_name'     => 'Falkland Islands (Malvinas)',
			],
			[
				'iso_country_code' => 'FO',
				'dialing_code'     => '298',
				'country_name'     => 'Faroe Islands',
			],
			[
				'iso_country_code' => 'FJ',
				'dialing_code'     => '679',
				'country_name'     => 'Fiji',
			],
			[
				'iso_country_code' => 'FI',
				'dialing_code'     => '358',
				'country_name'     => 'Finland',
			],
			[
				'iso_country_code' => 'FR',
				'dialing_code'     => '33',
				'country_name'     => 'France',
			],
			[
				'iso_country_code' => 'GF',
				'dialing_code'     => '594',
				'country_name'     => 'French Guiana',
			],
			[
				'iso_country_code' => 'PF',
				'dialing_code'     => '689',
				'country_name'     => 'French Polynesia',
			],
			[
				'iso_country_code' => 'TF',
				'dialing_code'     => '262',
				'country_name'     => 'French Southern Territories',
			],
			[
				'iso_country_code' => 'GA',
				'dialing_code'     => '241',
				'country_name'     => 'Gabon',
			],
			[
				'iso_country_code' => 'GM',
				'dialing_code'     => '220',
				'country_name'     => 'Gambia',
			],
			[
				'iso_country_code' => 'GE',
				'dialing_code'     => '7880',
				'country_name'     => 'Georgia',
			],
			[
				'iso_country_code' => 'GH',
				'dialing_code'     => '233',
				'country_name'     => 'Ghana',
			],
			[
				'iso_country_code' => 'GI',
				'dialing_code'     => '350',
				'country_name'     => 'Gibraltar',
			],
			[
				'iso_country_code' => 'GR',
				'dialing_code'     => '30',
				'country_name'     => 'Greece',
			],
			[
				'iso_country_code' => 'GL',
				'dialing_code'     => '299',
				'country_name'     => 'Greenland',
			],
			[
				'iso_country_code' => 'GD',
				'dialing_code'     => '1473',
				'country_name'     => 'Grenada',
			],
			[
				'iso_country_code' => 'GP',
				'dialing_code'     => '590',
				'country_name'     => 'Guadeloupe',
			],
			[
				'iso_country_code' => 'GU',
				'dialing_code'     => '671',
				'country_name'     => 'Guam',
			],
			[
				'iso_country_code' => 'GT',
				'dialing_code'     => '502',
				'country_name'     => 'Guatemala',
			],
			[
				'iso_country_code' => 'GG',
				'dialing_code'     => '44',
				'country_name'     => 'Guernsey',
			],
			[
				'iso_country_code' => 'GN',
				'dialing_code'     => '224',
				'country_name'     => 'Guinea',
			],
			[
				'iso_country_code' => 'GW',
				'dialing_code'     => '245',
				'country_name'     => 'Guinea-bissau',
			],
			[
				'iso_country_code' => 'GY',
				'dialing_code'     => '592',
				'country_name'     => 'Guyana',
			],
			[
				'iso_country_code' => 'HT',
				'dialing_code'     => '509',
				'country_name'     => 'Haiti',
			],
			[
				'iso_country_code' => 'HM',
				'dialing_code'     => '672',
				'country_name'     => 'Heard and Mc Donald Islands',
			],
			[
				'iso_country_code' => 'VA',
				'dialing_code'     => '379',
				'country_name'     => 'Holy See (Vatican City State)',
			],
			[
				'iso_country_code' => 'HN',
				'dialing_code'     => '504',
				'country_name'     => 'Honduras',
			],
			[
				'iso_country_code' => 'HK',
				'dialing_code'     => '852',
				'country_name'     => 'Hong Kong',
			],
			[
				'iso_country_code' => 'HU',
				'dialing_code'     => '36',
				'country_name'     => 'Hungary',
			],
			[
				'iso_country_code' => 'IS',
				'dialing_code'     => '354',
				'country_name'     => 'Iceland',
			],
			[
				'iso_country_code' => 'ID',
				'dialing_code'     => '62',
				'country_name'     => 'Indonesia',
			],
			[
				'iso_country_code' => 'IR',
				'dialing_code'     => '98',
				'country_name'     => 'Iran',
			],
			[
				'iso_country_code' => 'IQ',
				'dialing_code'     => '964',
				'country_name'     => 'Iraq',
			],
			[
				'iso_country_code' => 'IE',
				'dialing_code'     => '353',
				'country_name'     => 'Ireland',
			],
			[
				'iso_country_code' => 'IM',
				'dialing_code'     => '44',
				'country_name'     => 'Isle of Man',
			],
			[
				'iso_country_code' => 'IL',
				'dialing_code'     => '972',
				'country_name'     => 'Israel',
			],
			[
				'iso_country_code' => 'IT',
				'dialing_code'     => '39',
				'country_name'     => 'Italy',
			],
			[
				'iso_country_code' => 'CI',
				'dialing_code'     => '225',
				'country_name'     => 'Ivory Coast',
			],
			[
				'iso_country_code' => 'JM',
				'dialing_code'     => '1876',
				'country_name'     => 'Jamaica',
			],
			[
				'iso_country_code' => 'JP',
				'dialing_code'     => '81',
				'country_name'     => 'Japan',
			],
			[
				'iso_country_code' => 'JE',
				'dialing_code'     => '44',
				'country_name'     => 'Jersey',
			],
			[
				'iso_country_code' => 'JO',
				'dialing_code'     => '962',
				'country_name'     => 'Jordan',
			],
			[
				'iso_country_code' => 'KZ',
				'dialing_code'     => '7',
				'country_name'     => 'Kazakhstan',
			],
			[
				'iso_country_code' => 'KE',
				'dialing_code'     => '254',
				'country_name'     => 'Kenya',
			],
			[
				'iso_country_code' => 'KI',
				'dialing_code'     => '686',
				'country_name'     => 'Kiribati',
			],
			[
				'iso_country_code' => 'KR',
				'dialing_code'     => '82',
				'country_name'     => 'Korea',
			],
			[
				'iso_country_code' => 'KP',
				'dialing_code'     => '850',
				'country_name'     => 'Korea, Democratic People\'s Rep',
			],
			[
				'iso_country_code' => 'XK',
				'dialing_code'     => '383',
				'country_name'     => 'Kosovo',
			],
			[
				'iso_country_code' => 'KW',
				'dialing_code'     => '965',
				'country_name'     => 'Kuwait',
			],
			[
				'iso_country_code' => 'KG',
				'dialing_code'     => '996',
				'country_name'     => 'Kyrgyzstan',
			],
			[
				'iso_country_code' => 'LA',
				'dialing_code'     => '856',
				'country_name'     => 'Laos',
			],
			[
				'iso_country_code' => 'LV',
				'dialing_code'     => '371',
				'country_name'     => 'Latvia',
			],
			[
				'iso_country_code' => 'LB',
				'dialing_code'     => '961',
				'country_name'     => 'Lebanon',
			],
			[
				'iso_country_code' => 'LS',
				'dialing_code'     => '266',
				'country_name'     => 'Lesotho',
			],
			[
				'iso_country_code' => 'LR',
				'dialing_code'     => '231',
				'country_name'     => 'Liberia',
			],
			[
				'iso_country_code' => 'LY',
				'dialing_code'     => '218',
				'country_name'     => 'Libyan Arab Jamahiriya',
			],
			[
				'iso_country_code' => 'LI',
				'dialing_code'     => '417',
				'country_name'     => 'Liechtenstein',
			],
			[
				'iso_country_code' => 'LT',
				'dialing_code'     => '370',
				'country_name'     => 'Lithuania',
			],
			[
				'iso_country_code' => 'LU',
				'dialing_code'     => '352',
				'country_name'     => 'Luxembourg',
			],
			[
				'iso_country_code' => 'MO',
				'dialing_code'     => '853',
				'country_name'     => 'Macao',
			],
			[
				'iso_country_code' => 'MO',
				'dialing_code'     => '853',
				'country_name'     => 'Macau',
			],
			[
				'iso_country_code' => 'MK',
				'dialing_code'     => '389',
				'country_name'     => 'Macedonia',
			],
			[
				'iso_country_code' => 'MG',
				'dialing_code'     => '261',
				'country_name'     => 'Madagascar',
			],
			[
				'iso_country_code' => 'MW',
				'dialing_code'     => '265',
				'country_name'     => 'Malawi',
			],
			[
				'iso_country_code' => 'MY',
				'dialing_code'     => '60',
				'country_name'     => 'Malaysia',
			],
			[
				'iso_country_code' => 'MV',
				'dialing_code'     => '960',
				'country_name'     => 'Maldives',
			],
			[
				'iso_country_code' => 'ML',
				'dialing_code'     => '223',
				'country_name'     => 'Mali',
			],
			[
				'iso_country_code' => 'MT',
				'dialing_code'     => '356',
				'country_name'     => 'Malta',
			],
			[
				'iso_country_code' => 'MH',
				'dialing_code'     => '692',
				'country_name'     => 'Marshall Islands',
			],
			[
				'iso_country_code' => 'MQ',
				'dialing_code'     => '596',
				'country_name'     => 'Martinique',
			],
			[
				'iso_country_code' => 'MR',
				'dialing_code'     => '222',
				'country_name'     => 'Mauritania',
			],
			[
				'iso_country_code' => 'MU',
				'dialing_code'     => '230',
				'country_name'     => 'Mauritius',
			],
			[
				'iso_country_code' => 'YT',
				'dialing_code'     => '269',
				'country_name'     => 'Mayotte',
			],
			[
				'iso_country_code' => 'MX',
				'dialing_code'     => '52',
				'country_name'     => 'Mexico',
			],
			[
				'iso_country_code' => 'FM',
				'dialing_code'     => '691',
				'country_name'     => 'Micronesia, Federated States o',
			],
			[
				'iso_country_code' => 'MD',
				'dialing_code'     => '373',
				'country_name'     => 'Moldova, Republic of',
			],
			[
				'iso_country_code' => 'MC',
				'dialing_code'     => '377',
				'country_name'     => 'Monaco',
			],
			[
				'iso_country_code' => 'MN',
				'dialing_code'     => '976',
				'country_name'     => 'Mongolia',
			],
			[
				'iso_country_code' => 'ME',
				'dialing_code'     => '382',
				'country_name'     => 'Montenegro',
			],
			[
				'iso_country_code' => 'MS',
				'dialing_code'     => '1664',
				'country_name'     => 'Montserrat',
			],
			[
				'iso_country_code' => 'MA',
				'dialing_code'     => '212',
				'country_name'     => 'Morocco',
			],
			[
				'iso_country_code' => 'MZ',
				'dialing_code'     => '258',
				'country_name'     => 'Mozambique',
			],
			[
				'iso_country_code' => 'MN',
				'dialing_code'     => '95',
				'country_name'     => 'Myanmar',
			],
			[
				'iso_country_code' => 'NA',
				'dialing_code'     => '264',
				'country_name'     => 'Namibia',
			],
			[
				'iso_country_code' => 'NR',
				'dialing_code'     => '674',
				'country_name'     => 'Nauru',
			],
			[
				'iso_country_code' => 'NP',
				'dialing_code'     => '977',
				'country_name'     => 'Nepal',
			],
			[
				'iso_country_code' => 'NL',
				'dialing_code'     => '31',
				'country_name'     => 'Netherlands',
			],
			[
				'iso_country_code' => 'ANT',
				'dialing_code'     => '599',
				'country_name'     => 'Netherlands Antilles',
			],
			[
				'iso_country_code' => 'NC',
				'dialing_code'     => '687',
				'country_name'     => 'New Caledonia',
			],
			[
				'iso_country_code' => 'NZ',
				'dialing_code'     => '64',
				'country_name'     => 'New Zealand',
			],
			[
				'iso_country_code' => 'NI',
				'dialing_code'     => '505',
				'country_name'     => 'Nicaragua',
			],
			[
				'iso_country_code' => 'NE',
				'dialing_code'     => '227',
				'country_name'     => 'Niger',
			],
			[
				'iso_country_code' => 'NG',
				'dialing_code'     => '234',
				'country_name'     => 'Nigeria',
			],
			[
				'iso_country_code' => 'NU',
				'dialing_code'     => '683',
				'country_name'     => 'Niue',
			],
			[
				'iso_country_code' => 'NF',
				'dialing_code'     => '672',
				'country_name'     => 'Norfolk Island',
			],
			[
				'iso_country_code' => 'NP',
				'dialing_code'     => '670',
				'country_name'     => 'Northern Mariana Islands',
			],
			[
				'iso_country_code' => 'NO',
				'dialing_code'     => '47',
				'country_name'     => 'Norway',
			],
			[
				'iso_country_code' => 'OM',
				'dialing_code'     => '968',
				'country_name'     => 'Oman',
			],
			[
				'iso_country_code' => 'PK',
				'dialing_code'     => '92',
				'country_name'     => 'Pakistan',
			],
			[
				'iso_country_code' => 'PW',
				'dialing_code'     => '680',
				'country_name'     => 'Palau',
			],
			[
				'iso_country_code' => 'PS',
				'dialing_code'     => '970',
				'country_name'     => 'Palestinian Authority',
			],
			[
				'iso_country_code' => 'PA',
				'dialing_code'     => '507',
				'country_name'     => 'Panama',
			],
			[
				'iso_country_code' => 'PG',
				'dialing_code'     => '675',
				'country_name'     => 'Papua New Guinea',
			],
			[
				'iso_country_code' => 'PY',
				'dialing_code'     => '595',
				'country_name'     => 'Paraguay',
			],
			[
				'iso_country_code' => 'PE',
				'dialing_code'     => '51',
				'country_name'     => 'Peru',
			],
			[
				'iso_country_code' => 'PH',
				'dialing_code'     => '63',
				'country_name'     => 'Philippines',
			],
			[
				'iso_country_code' => 'PN',
				'dialing_code'     => '64',
				'country_name'     => 'Pitcairn Islands',
			],
			[
				'iso_country_code' => 'PL',
				'dialing_code'     => '48',
				'country_name'     => 'Poland',
			],
			[
				'iso_country_code' => 'PT',
				'dialing_code'     => '351',
				'country_name'     => 'Portugal',
			],
			[
				'iso_country_code' => 'PR',
				'dialing_code'     => '1787',
				'country_name'     => 'Puerto Rico',
			],
			[
				'iso_country_code' => 'QA',
				'dialing_code'     => '974',
				'country_name'     => 'Qatar',
			],
			[
				'iso_country_code' => 'RE',
				'dialing_code'     => '262',
				'country_name'     => 'Reunion',
			],
			[
				'iso_country_code' => 'RO',
				'dialing_code'     => '40',
				'country_name'     => 'Romania',
			],
			[
				'iso_country_code' => 'RU',
				'dialing_code'     => '7',
				'country_name'     => 'Russian Federation',
			],
			[
				'iso_country_code' => 'RW',
				'dialing_code'     => '250',
				'country_name'     => 'Rwanda',
			],
			[
				'iso_country_code' => 'WS',
				'dialing_code'     => '685',
				'country_name'     => 'Samoa',
			],
			[
				'iso_country_code' => 'SM',
				'dialing_code'     => '378',
				'country_name'     => 'San Marino',
			],
			[
				'iso_country_code' => 'ST',
				'dialing_code'     => '239',
				'country_name'     => 'Sao Tome and Principe',
			],
			[
				'iso_country_code' => 'SA',
				'dialing_code'     => '966',
				'country_name'     => 'Saudi Arabia',
			],
			[
				'iso_country_code' => 'SN',
				'dialing_code'     => '221',
				'country_name'     => 'Senegal',
			],
			[
				'iso_country_code' => 'CS',
				'dialing_code'     => '381',
				'country_name'     => 'Serbia',
			],
			[
				'iso_country_code' => 'SCG',
				'dialing_code'     => '381',
				'country_name'     => 'Serbia and Montenegro',
			],
			[
				'iso_country_code' => 'SC',
				'dialing_code'     => '248',
				'country_name'     => 'Seychelles',
			],
			[
				'iso_country_code' => 'SL',
				'dialing_code'     => '232',
				'country_name'     => 'Sierra Leone',
			],
			[
				'iso_country_code' => 'SG',
				'dialing_code'     => '65',
				'country_name'     => 'Singapore',
			],
			[
				'iso_country_code' => 'SX',
				'dialing_code'     => '1721',
				'country_name'     => 'Sint Maarten',
			],
			[
				'iso_country_code' => 'SK',
				'dialing_code'     => '421',
				'country_name'     => 'Slovak Republic',
			],
			[
				'iso_country_code' => 'SK',
				'dialing_code'     => '421',
				'country_name'     => 'Slovakia',
			],
			[
				'iso_country_code' => 'SI',
				'dialing_code'     => '386',
				'country_name'     => 'Slovenia',
			],
			[
				'iso_country_code' => 'SB',
				'dialing_code'     => '677',
				'country_name'     => 'Solomon Islands',
			],
			[
				'iso_country_code' => 'SO',
				'dialing_code'     => '252',
				'country_name'     => 'Somalia',
			],
			[
				'iso_country_code' => 'ZA',
				'dialing_code'     => '27',
				'country_name'     => 'South Africa',
			],
			[
				'iso_country_code' => 'GS',
				'dialing_code'     => '500',
				'country_name'     => 'South Georgia and the South Sa',
			],
			[
				'iso_country_code' => 'SS',
				'dialing_code'     => '211',
				'country_name'     => 'South Sudan',
			],
			[
				'iso_country_code' => 'ES',
				'dialing_code'     => '34',
				'country_name'     => 'Spain',
			],
			[
				'iso_country_code' => 'LK',
				'dialing_code'     => '94',
				'country_name'     => 'Sri Lanka',
			],
			[
				'iso_country_code' => 'BL',
				'dialing_code'     => '590',
				'country_name'     => 'St. Barts',
			],
			[
				'iso_country_code' => 'SH',
				'dialing_code'     => '290',
				'country_name'     => 'St. Helena',
			],
			[
				'iso_country_code' => 'KN',
				'dialing_code'     => '1869',
				'country_name'     => 'St. Kitts and Nevis',
			],
			[
				'iso_country_code' => 'SC',
				'dialing_code'     => '1758',
				'country_name'     => 'St. Lucia',
			],
			[
				'iso_country_code' => 'MQ',
				'dialing_code'     => '596',
				'country_name'     => 'St. Martin',
			],
			[
				'iso_country_code' => 'PM',
				'dialing_code'     => '508',
				'country_name'     => 'St. Pierre and Miquelon',
			],
			[
				'iso_country_code' => 'VC',
				'dialing_code'     => '784',
				'country_name'     => 'St. Vincent and Grenadines',
			],
			[
				'iso_country_code' => 'SD',
				'dialing_code'     => '249',
				'country_name'     => 'Sudan',
			],
			[
				'iso_country_code' => 'SR',
				'dialing_code'     => '597',
				'country_name'     => 'Suriname',
			],
			[
				'iso_country_code' => 'SJ',
				'dialing_code'     => '4779',
				'country_name'     => 'Svalbard and Jan Mayen Islands',
			],
			[
				'iso_country_code' => 'SZ',
				'dialing_code'     => '268',
				'country_name'     => 'Swaziland',
			],
			[
				'iso_country_code' => 'SE',
				'dialing_code'     => '46',
				'country_name'     => 'Sweden',
			],
			[
				'iso_country_code' => 'CH',
				'dialing_code'     => '41',
				'country_name'     => 'Switzerland',
			],
			[
				'iso_country_code' => 'SY',
				'dialing_code'     => '963',
				'country_name'     => 'Syrian Arab Republic',
			],
			[
				'iso_country_code' => 'TW',
				'dialing_code'     => '886',
				'country_name'     => 'Taiwan',
			],
			[
				'iso_country_code' => 'TJ',
				'dialing_code'     => '992',
				'country_name'     => 'Tajikistan',
			],
			[
				'iso_country_code' => 'TZ',
				'dialing_code'     => '255',
				'country_name'     => 'Tanzania, United Republic of',
			],
			[
				'iso_country_code' => 'TH',
				'dialing_code'     => '66',
				'country_name'     => 'Thailand',
			],
			[
				'iso_country_code' => 'TL',
				'dialing_code'     => '670',
				'country_name'     => 'Timor-Leste',
			],
			[
				'iso_country_code' => 'TG',
				'dialing_code'     => '228',
				'country_name'     => 'Togo',
			],
			[
				'iso_country_code' => 'TK',
				'dialing_code'     => '690',
				'country_name'     => 'Tokelau',
			],
			[
				'iso_country_code' => 'TO',
				'dialing_code'     => '676',
				'country_name'     => 'Tonga',
			],
			[
				'iso_country_code' => 'TT',
				'dialing_code'     => '1868',
				'country_name'     => 'Trinidad and Tobago',
			],
			[
				'iso_country_code' => 'TN',
				'dialing_code'     => '216',
				'country_name'     => 'Tunisia',
			],
			[
				'iso_country_code' => 'TR',
				'dialing_code'     => '90',
				'country_name'     => 'Turkey',
			],
			[
				'iso_country_code' => 'TM',
				'dialing_code'     => '993',
				'country_name'     => 'Turkmenistan',
			],
			[
				'iso_country_code' => 'TC',
				'dialing_code'     => '1649',
				'country_name'     => 'Turks and Caicos Islands',
			],
			[
				'iso_country_code' => 'TV',
				'dialing_code'     => '688',
				'country_name'     => 'Tuvalu',
			],
			[
				'iso_country_code' => 'UG',
				'dialing_code'     => '256',
				'country_name'     => 'Uganda',
			],
			[
				'iso_country_code' => 'UA',
				'dialing_code'     => '380',
				'country_name'     => 'Ukraine',
			],
			[
				'iso_country_code' => 'AE',
				'dialing_code'     => '971',
				'country_name'     => 'United Arab Emirates',
			],
			[
				'iso_country_code' => 'UM',
				'dialing_code'     => '246',
				'country_name'     => 'United States Minor Outlying I',
			],
			[
				'iso_country_code' => 'UY',
				'dialing_code'     => '598',
				'country_name'     => 'Uruguay',
			],
			[
				'iso_country_code' => 'UZ',
				'dialing_code'     => '998',
				'country_name'     => 'Uzbekistan',
			],
			[
				'iso_country_code' => 'VU',
				'dialing_code'     => '678',
				'country_name'     => 'Vanuatu',
			],
			[
				'iso_country_code' => 'VE',
				'dialing_code'     => '58',
				'country_name'     => 'Venezuela',
			],
			[
				'iso_country_code' => 'VN',
				'dialing_code'     => '84',
				'country_name'     => 'Vietnam',
			],
			[
				'iso_country_code' => 'VG',
				'dialing_code'     => '1',
				'country_name'     => 'Virgin Islands (British)',
			],
			[
				'iso_country_code' => 'VI',
				'dialing_code'     => '1',
				'country_name'     => 'Virgin Islands (U.S.)',
			],
			[
				'iso_country_code' => 'WF',
				'dialing_code'     => '681',
				'country_name'     => 'Wallis and Futuna Islands',
			],
			[
				'iso_country_code' => 'EH',
				'dialing_code'     => '212',
				'country_name'     => 'Western Sahara',
			],
			[
				'iso_country_code' => 'YE',
				'dialing_code'     => '967',
				'country_name'     => 'Yemen',
			],
			[
				'iso_country_code' => 'ZM',
				'dialing_code'     => '260',
				'country_name'     => 'Zambia',
			],
			[
				'iso_country_code' => 'ZW',
				'dialing_code'     => '263',
				'country_name'     => 'Zimbabwe',
			],
		];
	}

	/**
	 * Returns the field markup; including field label, description, validation, and the form editor admin buttons.
	 *
	 * The {FIELD} placeholder will be replaced in GFFormDisplay::get_field_content with the markup returned by GF_Field::get_field_input().
	 *
	 * @param string|array $value                The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param bool         $force_frontend_label Should the frontend label be displayed in the admin even if an admin label is configured.
	 * @param array        $form                 The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_field_content( $value, $force_frontend_label, $form ) {
		$field_label = $this->get_field_label( $force_frontend_label, $value );

		$is_form_editor  = $this->is_form_editor();
		$is_entry_detail = $this->is_entry_detail();
		$is_admin        = $is_form_editor || $is_entry_detail;
		$required_class  = $is_admin ? 'gfield_required' : 'required-label';

		$required_div = $is_admin || $this->isRequired ? sprintf( "<span class='%s'>%s</span>", $required_class, $this->isRequired ? '*' : '' ) : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar

		$admin_buttons = $this->get_admin_buttons();

		$target_input_id = $this->get_first_input_id( $form );

		$for_attribute = empty( $target_input_id ) ? '' : "for='{$target_input_id}'";

		$description = $this->get_description( $this->description, 'gfield_description' );
		if ( $this->is_description_above( $form ) ) {
			$clear         = $is_admin ? "<div class='gf_clear'></div>" : '';
			$field_content = sprintf( "%s<label class='%s' $for_attribute >%s%s</label>%s{FIELD}$clear", $admin_buttons, esc_attr( $this->get_field_label_class() ), esc_html( $field_label ), $required_div, $description );
		} else {
			$field_content = sprintf( "%s<label class='%s' $for_attribute >%s%s</label>{FIELD}%s", $admin_buttons, esc_attr( $this->get_field_label_class() ), esc_html( $field_label ), $required_div, $description );
		}

		return $field_content;
	}

	/**
	 * Returns the markup for the field description.
	 *
	 * @param string $description The field description.
	 * @param string $css_class   The css class to be assigned to the description container.
	 *
	 * @return string
	 */
	public function get_description( $description, $css_class ) {
		$is_form_editor  = $this->is_form_editor();
		$is_entry_detail = $this->is_entry_detail();
		$is_admin        = $is_form_editor || $is_entry_detail;

		$description_wrapper = $is_admin ? "<div class='$css_class'>%s</div>" : "<span class='form__description'>%s</span>";

		return $is_admin || ! empty( $description ) ? sprintf( $description_wrapper, $description ) : '';
	}

	/**
	 * Self explanatory...
	 *
	 * @return string
	 */
	public function get_field_label_class() {
		return is_admin() ? 'gfield_label' : 'form__label';
	}

	/**
	 * Set the default that should load when adding a field to the form
	 *
	 * @return void
	 */
	public static function set_default_values() {
		?>
		case "country-code" :
			field.label = "Country Code";
			field.isRequired = true;
			field.enableChoiceValue = true;
			field.choices = new Array (
				<?php
				$country_codes = self::get_country_codes();
				foreach ( $country_codes as $country_data ) {
					$country_label = $country_data['country_name'] . ' (+' . $country_data['dialing_code'] . ')';
					?>
					new Choice( "<?php echo esc_html( $country_label ); ?>", "<?php echo esc_html( $country_data['dialing_code'] ); ?>" ),
					<?php
				}
				?>
			)
			break;
		<?php
	}

	/**
	 * Prepend the country code to the phone number
	 *
	 * @param array $form Form currently being processed.
	 *
	 * @return void
	 */
	public static function add_country_to_webhooks( $form ) {
		$phone_number_id = self::get_field_id( $form, 'nu_phone', 'type' );
		$country_code_id = self::get_field_id( $form, 'country-code', 'type' );

		// Both IDs need to exist so we can even try to pull this off.
		if ( false !== $phone_number_id && false !== $country_code_id ) {
			$phone_number = rgpost( 'input_' . $phone_number_id );
			$country_code = rgpost( 'input_' . $country_code_id );

			// Make sure there was a country code, and build out the new full phone number only if not from USA.
			if ( ! empty( $country_code ) && '1' !== $country_code ) {
				$_POST[ 'input_' . $phone_number_id ] = '+' . $country_code . $phone_number;
			}
		}
	}

	/**
	 * Add custom class(es) to field
	 *
	 * @param string $css_classes Class list for the field container.
	 * @param object $field       The GF field object with info.
	 * @param array  $form        The current GF form data.
	 *
	 * @return string
	 */
	public function modify_field_container_classes( $css_classes, $field, $form ) {
		// If is in the admin or not this field type, leave it be.
		if ( is_admin() || $this->type !== $field->type ) {
			return $css_classes;
		}

		$css_classes .= ' country-code--select';

		// If any of the choices is selected, add the active class for label display.
		$choices_is_selected = array_filter( array_column( $field->choices, 'isSelected' ) );

		if ( ! empty( $choices_is_selected ) ) {
			$css_classes .= ' form__group--active';
		}

		return $css_classes;
	}

	/**
	 * Gets the field ID from the type
	 *
	 * @param array  $form  GF data of form.
	 * @param string $value Value we are trying to find inside the form.
	 * @param string $key   The type we are trying to find.
	 */
	public static function get_field_id( $form, $value, $key = 'type' ) {
		foreach ( $form['fields'] as $field ) {
			if ( strtolower( $field->$key ) === strtolower( $value ) ) {
				return $field->id;
			}
		}
		return false;
	}
}
