( function( d ) {
	/**
	 * Adds spinning icon to submit button
	 *
	 * Changes submit button icon class to add a rotating spinner so user knows something is happening
	 */

	// When click the form submit button, if the hidden track input is empty, set the value of the default RFI form
	d.addEventListener( 'click', function( event ) {
		if ( event.target && event.target.closest( '.gform_footer button' ) ) {
			console.log( event.target.closest( 'form' ) );

			const trackInput = event.target.closest( 'form' ).querySelector( 'li.track input' );
			if ( trackInput && '' === trackInput.value ) {
				trackInput.value = 'rfi_nu.edu';
			}
		}
	} );

	/**
	 * Disable the submit button on forms submission to prevent duplicate submissions
	 * - Gravity Forms are dynamically generated
	 * - Not using jQuery so we can limit our dependency on it.
	 * - Backwards compatability for submit button in the footer of the form OR the input[type="submit"] of native GravityForms functionality.
	 */
	d.addEventListener( 'submit', function( event ) {
		if ( event.target && event.target.id.startsWith( 'gform' ) ) {
			const formElement   = d.getElementById( event.target.id );
			const submitElement = formElement.querySelector( 'input[type="submit"]' ) || formElement.querySelector( '.gform_footer button' );
			const iconElement = submitElement.querySelector( '.icon--arrow-right' );

			submitElement.setAttribute( 'disabled', 'disabled' );

			if ( iconElement ) {
				iconElement.classList.remove( 'icon--arrow-right' );
				iconElement.classList.add( 'icon--spin' );
			}
		}
	} );
} )( document, jQuery );
