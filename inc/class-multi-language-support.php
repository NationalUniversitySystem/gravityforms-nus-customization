<?php
/**
 * Functionality for supporting multiple languages
 */

/**
 * Multi_Language_Support class
 */
class Multi_Language_Support {
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
		add_filter( 'gform_pre_render', [ $this, 'split_translations' ] );
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
	 * Split the text into translation if possible.
	 *
	 * @param array $form The full GF form array with data.
	 *
	 * @return array
	 */
	public function split_translations( $form ) {
		if ( is_admin() || empty( $form['fields'] ) ) {
			return $form;
		}

		$language = $this->get_language();

		foreach ( $form['fields'] as &$field ) {
			foreach ( $field as $attribute => &$value ) {
				if ( $this->exclude_field_attribute( $attribute, $value ) ) {
					continue;
				}

				$value = $this->get_language_text( $language, $value );
			}
		}

		return $form;
	}

	/**
	 * Make the language to translate to based on the locale.
	 *
	 * @return string
	 */
	private function get_language() {
		return explode( '_', get_locale() )[0];
	}

	/**
	 * Grouped logic for excluding a field's attribute from translation.
	 *
	 * @param string $key   The key of the field attribute.
	 * @param mixed  $value Value of the field attribute.
	 *
	 * @return boolean
	 */
	private function exclude_field_attribute( $key, $value ) {
		if ( empty( $value ) ) {
			return true;
		}

		if ( ! in_array( $key, [ 'label', 'checkboxLabel', 'placeholder', 'description', 'choices' ], true ) ) {
			return true;
		}

		if ( is_string( $value ) && false === strpos( $value, '[:' ) ) {
			return true;
		}

		if ( is_array( $value ) && false === strpos( wp_json_encode( wp_list_pluck( $value, 'text' ) ), '[:' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Try to get the appropriate language from a text block.
	 *
	 * @param string $language The language we want the specific translation for.
	 * @param mixed  $content  Text/array to search translations for.
	 *
	 * @return string
	 */
	private function get_language_text( $language, $content ) {
		$split_regex = '/\[:' . $language . '\](.*?)\[:/is';

		if ( is_string( $content ) ) {
			preg_match( $split_regex, $content, $matches );

			if ( ! empty( $matches ) ) {
				$content = $matches[1];
			}
		} elseif ( is_array( $content ) ) {
			foreach ( $content as &$choice ) {
				preg_match( $split_regex, $choice['text'], $matches );

				if ( ! empty( $matches ) ) {
					$choice['text'] = $matches[1];
				}
			}
		}

		return $content;
	}
}
