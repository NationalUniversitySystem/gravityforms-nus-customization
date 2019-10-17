( function( document, $ ) {
	var isoCodeField     = $( '.iso-country-code input, input.iso-country-code' );
	var countryNameField = $( '.country-name input, input.country-name' );
	if ( isoCodeField.length || countryNameField.length ) {
		/**
		 * Event handler for whenever the Country Code dropdown is changed
		 */
		$( document ).on( 'change', '.country-code--select select', function() {
			var selectedCountry = $( this ).children( 'option' ).filter( ':selected' );

			var isoCode     = selectedCountry.data( 'iso-country-code' );
			var countryName = selectedCountry.data( 'country-name' );

			// Populate the corresponding fields.
			if ( isoCodeField.length ) {
				isoCodeField.val( isoCode );
			}

			if ( countryNameField.length ) {
				countryNameField.val( countryName );
			}

			// Auto populate the zipcode field if it's another country other than USA.
			var zipcodeWrapper = $( '.form__group--zip' );
			if ( zipcodeWrapper.length && ( 'US' !== isoCode || 'USA' !== countryName ) ) {
				zipcodeWrapper.find( 'input' ).val( '00000' );
				zipcodeWrapper.addClass('form__group--active');
			} elseif ( zipcodeWrapper.length ) {
				zipcodeWrapper.find( 'input' ).val( '' );
				zipcodeWrapper.removeClass( 'form__group--active' );
			}
		} );
	}

	// Set the country
	$('.country--select select').val( 'USA' ).change();

	// Set the country code AFTER we add the on change behavior so it actually triggers.
	$( '.country-code--select select' ).val( '1' ).change();
} )( document, jQuery );
