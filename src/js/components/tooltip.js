( ( d ) => {
	d.addEventListener( 'DOMContentLoaded', () => {
		d.querySelector( 'body' ).addEventListener( 'mouseenter', function( event ) {
			if ( event.target.closest( 'span[data-tooltip-content]' ) && event.target.dataset.tooltipContent ) {
				const tooltip = d.querySelector( event.target.dataset.tooltipContent );
				tooltip.style.display = 'block';
			}
		}, true );

		d.querySelector( 'body' ).addEventListener( 'touchstart', function( event ) {
			if ( event.target.closest( 'span[data-tooltip-content]' ) && event.target.dataset.tooltipContent ) {
				const tooltip = d.querySelector( event.target.dataset.tooltipContent );
				tooltip.style.display = 'block';
			}
		}, true );

		d.querySelector( 'body' ).addEventListener( 'mouseleave', function( event ) {
			if ( event.target.closest( 'span[data-tooltip-content]' ) && event.target.dataset.tooltipContent ) {
				const tooltip = d.querySelector( event.target.dataset.tooltipContent );
				tooltip.style.display = 'none';
			}
		}, true );

		d.querySelector( 'body' ).addEventListener( 'touchstart', function( event ) {
			if ( ! event.target.closest( 'span[data-tooltip-content]' ) && event.target.dataset.tooltipContent ) {
				const tooltip = d.querySelector( event.target.dataset.tooltipContent );
				tooltip.style.display = 'none';
			}
		}, true );
	} );
} )( document );
