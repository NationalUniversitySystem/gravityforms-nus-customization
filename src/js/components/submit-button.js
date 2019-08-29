( function( $ ) {
	/**
	 * Adds spinning icon to submit button
	 *
	 * Changes submit button icon class to add a rotating spinner so user knows something is happening
	 */

	// When click the form submit button
	$( document ).on( 'click', '.gform_footer button', function() {

		// Remove the class that shows the arrow
		$( this ).removeClass( 'icon--arrow-right' );

		// Add the class that shows the spinning icon
		$( this ).addClass( 'icon--spin' );

		// Get the value of the default RFI form
		var trackInput = $( '#gform_1 li.track input' ).val();

		// If the value is empty
		if ( trackInput === '' ) {

			// Set it manually
			$( '#gform_1 li.track input' ).val( 'rfi_nu.edu' );
		}
	} );
} )( jQuery );
