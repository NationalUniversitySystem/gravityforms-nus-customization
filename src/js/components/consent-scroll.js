( ( $ ) => {
	$( document ).ready( ( $ ) => {
		const gforms = $( 'form[id^=gform]' );

		gforms.each( function() {
			const textScrollWindows = $( this ).find( '.consent__scroll-window' );

			if ( textScrollWindows.length ) {
				const textScrollWindow = textScrollWindows.first();

				if ( textScrollWindow.is( ':visible' ) ) {
					const submitBtn = $( this ).find( '.gform_footer .btn' );

					submitBtn.first().attr( 'disabled', true );

					textScrollWindow.scroll( function() {
						if ( $( this )[0].scrollHeight - $( this ).scrollTop() === $( this ).innerHeight() ) {
							submitBtn.prop( 'disabled', false );
						}
					} );
				}
			}
		} );
	} );
} )( jQuery );
