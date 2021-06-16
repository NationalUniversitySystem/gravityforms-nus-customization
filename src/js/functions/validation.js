/* eslint-disable no-unused-vars, complexity */
/* global NuAjaxObject, Qs, axios  */

/**
 * Validate First & Last Name
 *
 * Only letters and hyphens.
 */
function validateName( input, language = 'english' ) {
	const regex = /^[A-Za-z, .-]+$/i;
	const message = 'english' === language ? '<span>Please enter a valid name.</span> Must contains only letters and hyphens.' : '<span>Por favor introduzca un nombre válido.</span> Debe contener solo letras y guiones.';

	if ( input.value.length > 1 && regex.test( input.value ) ) {
		fieldValid( input );
	} else {
		fieldNotValid( input, message );
	}
}

/**
 * Validate Email Address
 *
 * Allow any # of letters, followed by '@', followed by at least 2 letters,
 * followed by '.' and then 3 letters.
 */
function validateEmail( input, language = 'english' ) {
	const regex = /^\w+([+.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/;
	const message = 'english' === language ? '<span>Please enter a valid email address.</span> e.g.: example@website.com' : '<span>Por favor, introduzca una dirección de correo electrónico válida.</span> ej.: ejemplo@website.com';

	if ( input.value.length > 1 && regex.test( input.value ) ) {
		fieldValid( input );
	} else {
		fieldNotValid( input, message );
	}
}

/**
 * Validate Phone #
 *
 * Only a formatted phone number (e.g. +1-555-555-5555, 555-555-5555, 55555555555).
 */
function validatePhone( input, language = 'english' ) {
	const countryCode = document.querySelector( '.nus-live-validation--country select' );
	const message = 'english' === language ? '<span>Please enter a valid phone number.</span>' : '<span>Por favor introduzca un número de teléfono válido.</span>';

	// Default is if not USA country code, then remove char limit of 10
	let regex = /^(\+?1-?)?(\([2-9]([02-9]\d|1[02-9])\)|[2-9]([02-9]\d|1[02-9]))-?[2-9]([02-9]\d|1[02-9])-?\d{4,15}$/;
	let errorMessage = 'english' === language ? 'Only digits, hyphens, and spaces.' : 'Solo dígitos, guiones y espacios';

	// If the country code is set to USA ( 1 ), then check for a 10 digit number with only numbers.
	if ( countryCode === null || countryCode.value === '1' ) {
		regex = /^(\+?1-?)?(\([2-9]([02-9]\d|1[02-9])\)|[2-9]([02-9]\d|1[02-9]))-?[2-9]([02-9]\d|1[02-9])-?\d{4}$/;
		errorMessage = 'english' === language ? 'Only 10 digits, hyphens, and spaces.' : 'Solo 10 dígitos, guiones y espacios';
	}

	if ( input.value.length > 0 && regex.test( input.value ) ) {
		fieldValid( input );
	} else {
		fieldNotValid( input, `${ message } ${ errorMessage }` );
	}
}

/**
 * A more strict phone number validation that does not allow any characters other than digits
 *
 * @param {DOM element} input
 */
function validatePhoneStrict( input, language = 'english' ) {
	const countryCode = document.querySelector( '.country-code--select select' );
	let isFieldValid  = true;
	let message = 'english' === language ? '<span>Please enter a valid phone number.</span>' : '<span>Por favor introduzca un número de teléfono válido</span>';

	if ( ! input.value ) {
		isFieldValid = false;
		fieldNotValid( input, message );
	} else if ( countryCode && '' !== countryCode.value ) {
		if ( '1' === countryCode.value && ! /^\d{10}$|^$/.test( input.value ) ) {
			isFieldValid = false;
			fieldNotValid( input, `${ message } Please enter 10 digits.` );
		} else if ( ! input.value || ! /^\d{5,}$|^$/.test( input.value ) ) {
			isFieldValid = false;
			fieldNotValid( input, message );
		}
	} else if ( ! /^\d{10}$|^$/.test( input.value ) ) {
		isFieldValid = false;
		fieldNotValid( input, message );
	}

	// Decide if a specific message should be used.
	if ( false === isFieldValid && /[^0-9]/.test( input.value ) ) {
		fieldNotValid( input, `${ message } No special characters allowed.` );
	}

	if ( isFieldValid ) {
		fieldValid( input );
	}
}

/**
 * Validate Zip Code
 *
 * Only allow 5 numbers.
 */
function validateZip( input, language = 'english' ) {
	const regex = /^\d{5}$/;
	const message = 'english' === language ? 'Please enter a valid zip' : 'Por favor introduzca un código postal válido.';
	const errorDescription = 'english' === language ? '5 digits, numbers only' : '5 dígitos, solo números.';

	if ( ! regex.test( input.value ) ) {
		fieldNotValid( input, `<span>${ message }</span> ${ errorDescription }` );
	} else if ( input.value === '00000' ) {
		fieldValid( input );
	} else {
		validateZipViaUSPS( input, message );
	}
}

/**
 * Validate the ZIP through USPS API
 *
 * @param {DOM element} input
 *
 * @return {boolean|object|Error}
 */
function validateZipViaUSPS( input, message ) {
	const uspsApiUrl = 'https://secure.shippingapis.com/ShippingApi.dll?API=CityStateLookup&XML=';
	const requestXML = `<CityStateLookupRequest USERID="951NATIO1026"><ZipCode ID="0"><Zip5>${ input.value }</Zip5></ZipCode></CityStateLookupRequest>`;

	axios( {
		method: 'get',
		timeout: 750,
		url: uspsApiUrl + requestXML
	} ).then( response => {
		if ( response.status !== 200 ) {
			throw new Error( response.statusText );
		}

		return new window.DOMParser().parseFromString( response.data, 'text/xml' );
	} ).then( data => {
		if ( data.querySelector( 'Error' ) ) {
			const errorDescription = data.querySelector( 'Description' ).innerHTML;

			fieldNotValid( input, `<span>${ message }.</span> ${ errorDescription }` );
		} else {
			fieldValid( input );
			populateZipRelatedFields( data, input );
		}
	} ).catch( error => {
		// If the response fails, timesout, etc. then try to do a request in our backup DB.
		validateZipViaDB( input, message );
	} );
}

/**
 * Fallback zip code check in our WP DB incase the USPS service is not available or times out
 *
 * @param {DOM element} input
 */
function validateZipViaDB( input, message ) {
	axios( {
		method: 'post',
		url: NuAjaxObject.ajax_url,
		data: Qs.stringify( {
			action: 'zip_lookup',
			zipValue: input.value
		} )
	} ).then( response => {
		// If our DB was not reached.
		if ( response.status !== 200 ) {
			throw new Error( response.statusText );
		} else if ( ! response.data.success ) {
			throw new Error( response.data.data );
		}

		fieldValid( input );
		populateZipRelatedFields( response.data.data, input );
	} ).catch( error => {
		fieldNotValid( input, `<span>${ message }.</span> ${ error.message }` );
	} );
}

function populateZipRelatedFields( data, input ) {
	const form = input.closest( 'form' );
	let state = '';
	if ( typeof data.state !== 'undefined' ) {
		state = data.state;
	} else if ( data.querySelector( 'State' ) ) {
		state = data.querySelector( 'State' ).innerHTML;
	}

	if ( state && form ) {
		const stateInput = form.querySelector( '.gfield.state input' );

		if ( stateInput && stateInput.getAttribute( 'type' ) === 'hidden' ) {
			stateInput.value = state;
		}
	}
}

/**
 * JS to run if field comes back valid
 *
 * Remove the error class if it exists, then add our success class,
 * finally kill the error tooltip.
 */
function fieldValid( input ) {
	if ( input.parentNode.classList.contains( 'gfield_error' ) ) {
		input.parentNode.classList.remove( 'gfield_error' );
	}
	input.parentNode.classList.add( 'gfield_correct' );
	jQuery( '.gfield_correct [data-toggle="tooltip"]' ).tooltip( 'dispose' );
}

/**
 * JS to run if field comes back NOT valid
 *
 * Remove the correct class if it exists, then add our error class.
 */
function fieldNotValid( input, message ) {
	// Remove the correct class if it exists, then add our error class.
	input.parentNode.classList.remove( 'gfield_correct' );
	input.parentNode.classList.add( 'gfield_error' );

	// Set the attributes to show the error tooltip, show the tooltip.
	input.setAttribute( 'data-toggle', 'tooltip' );
	input.setAttribute( 'data-html', 'true' );
	input.setAttribute( 'title', message );
	input.setAttribute( 'data-placement', 'top' );
	input.setAttribute( 'data-original-title', message );
	input.setAttribute( 'data-template', '<div class="tooltip tooltip--error" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>' );
	jQuery( '.gfield_error [data-toggle="tooltip"]:focus' ).tooltip( 'show' );

}

/**
 * JS to perform the actual validation on our inputs
 *
 * Used within event listener functions.
 */
function runValidation( event ) {
	if ( null === event.target.parentNode ) {
		return;
	}

	const classList = event.target.parentNode.classList;
	const langAttribute = document.querySelector( 'html' ).getAttribute( 'lang' );
	const language = 'es' === langAttribute ? 'spanish' : 'english';

	if ( classList.contains( 'nus-live-validation--name' ) ) {
		validateName( event.target, language );
	} else if ( classList.contains( 'nus-live-validation--email' ) ) {
		validateEmail( event.target, language );
	} else if ( classList.contains( 'nus-live-validation--phone' ) ) {
		validatePhone( event.target, language );
	} else if ( classList.contains( 'nus-live-validation--phone-strict' ) ) {
		validatePhoneStrict( event.target, language );
	} else if ( classList.contains( 'nus-live-validation--zip' ) ) {
		validateZip( event.target, language );
	}
}
