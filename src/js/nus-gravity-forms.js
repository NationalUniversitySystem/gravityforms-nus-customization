//=require components/functions.js
//=require components/submit-button.js
//=require components/degree-select.js
//=require components/country.js
//=require components/tooltip.js

/* global setActiveClass */
jQuery( document ).ready( function( $ ) {

	/**
	 * Default year and month and country for forms
	 *
	 * Auto populates month and year selects with current month and year, country select with USA
	 */

	// Create array of month names
	var monthNames = [ 'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUNE', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC' ];

	// Create a new date var
	var d = new Date();

	// Get the month number, correspond to our array data
	var n = monthNames[ d.getMonth() ];

	// Get the year
	var y = d.getFullYear();

	// Set the month
	$( '.form__group--month select' ).val( n ).change();

	// Set the year
	$( '.form__group--year select' ).val( y ).change();

	// Check for active fields first, so it doesn't look terrible
	setActiveClass();

	/**
	 * Event handler for FireFox autofill form event
	 *
	 * Sets labels to active state when form is autofilled by the browser
	 */
	$( 'input' ).bind( 'input', function() {
		$( this ).closest( 'li' ).addClass( 'form__group--active' );
	} );

	$( 'select' ).bind( 'select', function() {
		$( this ).closest( 'li' ).addClass( 'form__group--active' );
	} );
} );
