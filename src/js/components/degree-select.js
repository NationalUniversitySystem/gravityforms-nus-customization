/* global NuAjaxObject, degreeSelect, setActiveClass */
( function( window, document, $ ) {
	$( function() {
		// Var used by optimizely. If set to true, will create a change in how
		// the programs are displayed in the ajax function degreeSelect().
		var modifyPrograms;

		/**
		* Re-usable Ajax function for our degree types select
		*
		* Based on the value of the degree type select, we need to populate our degree program select with the corresponding options.
		* It accepts two parameters, "degree" which is the value of the degree type select, and "repeat", which modifies some actions
		* on success, since we may not want to completely remove all the options every time.
		*/
		window.degreeSelect = function( degree, repeat, modifyPrograms ) {
			// Begin our ajax call
			$.ajax( {
				type: 'POST',
				url: NuAjaxObject.ajax_url,
				data: {
					degree: degree,
					modifyPrograms: modifyPrograms,
					action: 'degree_select'
				},
				success: function( programs ) { // The WP PHP AJAX action returns a list of programs as a string.

					// Enable the select after populated
					$( '.program--select select' ).prop( 'disabled', false );

					// Make it so user knows it's ready
					$( '.program--select' ).removeClass( 'disabled' );

					switch ( repeat ) {

						// First time
						case 'initial':

							// Remove all previously added programs as options from "Degree Program" select
							$( '.program--select select' ).find( 'option:not([disabled="disabled"])' ).remove();

							// Add all of our program posts as options to the "Degree Program" select
							$( programs ).appendTo( '.program--select select' );

							break;

						// Every other time
						case 'repeat':

							// Add all of our program posts as options to the "Degree Program" select
							$( programs ).appendTo( '.program--select select' );

							// Choose our previously chosen program, before the form re-rendered after failed validation
							$( '.program--select select' ).val( program );

							$( '.country-code--select select' ).val( countryCode );

							setActiveClass();

							break;
					}

					if ( true === modifyPrograms ) {
						$( '.program--select select' ).append( '<optgroup label="" class="d-none"></optgroup>' );
					}
				}
			} );
		};

		/**
		 * Event handler for whenever the Program Type dropdown option is selected
		 *
		 * Gets and sets the program value as a variable for use in our gform_post_render function
		 */
		// Set global variable
		var program;
		var countryCode;

		// When a program is selected
		$( document ).on( 'change', '.program--select select', function() {

			// Store that program's value in the variable for use elsewhere
			program = $( '.program--select select' ).val();

		} );

		// When a country code is selected
		$( document ).on( 'change', '.country-code--select', function() {

			// Store that value in the variable for use elsewhere
			countryCode = $( '.country-code--select select' ).val();

		} );

		// Since country code has a pre-selected default, also get the value on load.
		$( function() {
			countryCode = $( '.country-code--select select' ).val();
		} );

		/**
		 * Event handler for whenever the Degree Type dropdown is changed is rendered on the page
		 *
		 * Where all the magic happens.
		 */
		$( document ).on( 'change', '.degree--select select', function() {

			// If body has this class, set the var to true so we can modify the program names.
			if ( $( 'body' ).hasClass( 'modify-true' ) ) {
				modifyPrograms = true;
			}

			// Disable the select while ajax is running
			$( '.program--select select' ).prop( 'disabled', true );

			// Make it so user knows something is happening
			$( '.program--select' ).addClass( 'disabled' );

			// Get the value selected
			var degree = $( this ).children( 'option' ).filter( ':selected' ).text();

			// Remove all previously added programs as options from "Degree Program" select
			$( '.program--select select' ).find( 'option:not([disabled="disabled"])' ).remove();

			// Call our ajax function
			degreeSelect( degree, 'initial', modifyPrograms );

		} );

		/**
		 * Event handler for whenever the Gravity Form is rendered on the page
		 *
		 * Allows us to set inputs as active, run our ajax functions, etc.
		 * Especially helpful for returning form code after a failed validation.
		 */
		$( document ).on( 'gform_post_render', function( e, formId ) {

			// Stop form from re-submitting after hitting back button in browser
			$( '#gform_ajax_frame_' + formId ).attr( 'src', 'about:blank' );

			// Get the option selected
			var degree = $( '.degree--select select option:selected' ).val();

			degreeSelect( degree, 'repeat', modifyPrograms );

			$( '.gfield_error input, .gfield_error select' ).attr( 'aria-invalid', 'true' );

			if ( $( '#gform_wrapper_' + formId ).hasClass( 'gform_validation_error' ) ) {
				$( '#gform_' + formId + ' .validation_error' ).html( '<span class="heading--four">There was a problem with your submission.</span> Errors have been highlighted below.' );
			}

		} );

	} );
} )( window, document, jQuery );
