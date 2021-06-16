( function( $ ) {
	$( function() {
		$( '[data-tool="#military-tooltip"]' ).tooltip( {
			template: '<div class="tooltip tooltip--military" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>',
			container: '.form__group--military'
		} );
	} );
} )( jQuery );
