/* global runValidation, validatePhoneStrict */

( () => {

	// When we type in the 'inputs'.
	document.addEventListener( 'keyup', ( e ) => {
		runValidation( e );
	} );

	// When we focus in the 'inputs'.
	document.addEventListener( 'focus', ( e ) => {
		runValidation( e );
	} );

	// When we click into the 'inputs'.
	document.addEventListener( 'click', ( e ) => {
		runValidation( e );
	} );

	/**
	 * Updates the phone validation based on country code selected.
	 */

	// When a 'select' input is changed.
	document.addEventListener( 'change', ( e ) => {
		// Set the phone input as an object.
		const phone = document.querySelector( '.nus-live-validation--phone-strict input' );

		// If our country code select was changed, re-run validation on the phone input,
		// as the validation type is dependant on the country code.
		if ( e.target.parentNode.classList.contains( 'country-code--select' ) ) {
			validatePhoneStrict( phone );
		}

		// When we select a degree or program, add our valid class to the select.
		if ( e.target.parentNode.classList.contains( 'degree--select' ) || e.target.parentNode.classList.contains( 'program--select' ) ) {
			if ( e.target.value !== 'undefined' ) {
				e.target.parentNode.classList.add( 'gfield_correct' );
			} else {
				e.target.parentNode.classList.remove( 'gfield_correct' );
			}
		}

		// If we set a program / degree type, AND our dropdown's container class has an error, wipe the error.
		// Useful because if you hit submit without filling anything out, the degree / program boxes highlight with an error,
		// but without this snippet won't clear the error after selecting a value.
		if ( e.target.parentNode.classList.contains( 'degree--select' ) && e.target.parentNode.classList.contains( 'gfield_error' ) ) {
			e.target.parentNode.classList.remove( 'gfield_error' );
		}
	} );
} )();
