//=require functions/utility.js
//=require functions/validation.js
//=require components/submit-button.js
//=require components/degree-select.js
//=require components/country.js
//=require components/live-validation.js
//=require components/military-tooltip.js

/* global setActiveClass */
( function( d ) {
	/**
	 * Default year and month and country for forms
	 *
	 * Auto populates month and year selects with current month and year
	 */

	var monthNames = [ 'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUNE', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC' ];
	var date = new Date();

	// Get the month number, correspond to our array data
	var currentMonth = monthNames[ date.getMonth() ];

	// Get the year
	var currentYear = date.getFullYear();

	var monthSelects = d.querySelectorAll( '.form__group--month select' );
	var yearSelects = d.querySelectorAll( '.form__group--year select' );

	if ( monthSelects.length ) {
		monthSelects.forEach( function( element ) {
			element.value = currentMonth;
			element.dispatchEvent( new Event( 'change' ) );
		} );
	}

	if ( yearSelects.length ) {
		yearSelects.forEach( function( element ) {
			element.value = currentYear;
			element.dispatchEvent( new Event( 'change' ) );
		} );
	}

	// Check for active fields first, so it doesn't look terrible
	setActiveClass();

	/**
	 * Event handler for FireFox autofill form event
	 *
	 * Sets labels to active state when form is autofilled by the browser
	 */
	var gformFields = d.querySelectorAll( '.gform_body input:not(.gform_hidden), .gform_body select:not(.gform_hidden)' );
	if ( gformFields.length ) {
		gformFields.forEach( function( gformInput ) {
			gformInput.addEventListener( 'change', function() {
				gformInput.closest( 'li' ).classList.add( 'form__group--active' );
			} );
		} );
	}
} )( document );
