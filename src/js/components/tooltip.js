( function( $ ) {
	$( document ).ready( function( $ ) {
		$( 'body' ).on( 'mouseenter', '.gfield__tooltip', function() {
			var tooltip = $( this ).data( 'tooltip-content' );

			$( tooltip ).fadeIn( 200 );
		} ).on( 'mouseleave', '.gfield__tooltip', function() {
			var tooltip = $( this ).data( 'tooltip-content' );

			$( tooltip ).fadeOut( 200 );
		} );
	} );
} )( jQuery );

