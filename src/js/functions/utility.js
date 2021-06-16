/* eslint-disable no-unused-vars */
function hasClass( el, className ) {
	return el.classList ? el.classList.contains( className ) : new RegExp( '\\b' + className + '\\b' ).test( el.className );
}

function addClass( el, className ) {
	if ( el.classList ) {
		el.classList.add( className );
	} else if (	! hasClass( el, className ) ) {
		el.className += ' ' + className;
	}
}

function removeClass( el, className ) {
	if ( el.classList ) {
		el.classList.remove( className );
	} else {
		el.className = el.className.replace( new RegExp( '\\b' + className + '\\b', 'g' ), '' );
	}
}

/**
 * Re-Usable check for setting form labels to active state
 *
 * Loops through all fields and checks for value, if value, sets form field to active via class
 */
function setActiveClass() {
	var fieldWrappers = document.querySelectorAll( '.gform_fields li:not(.col-wrapper)' );

	fieldWrappers.forEach( function( element ) {
		var fieldInput = element.querySelector( 'input' );
		if ( fieldInput && fieldInput.value ) {
			addClass( element, 'form__group--active' );
		}

		var fieldSelect = element.querySelector( 'select' );
		if ( fieldSelect && fieldSelect.value ) {
			addClass( element, 'form__group--active' );
		}
	} );
}
