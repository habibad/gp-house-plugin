/* GP House Core — image field picker. */
( function () {
	document.addEventListener( 'click', function ( e ) {
		var pick = e.target.closest( '[data-gp-image-pick]' );
		var clear = e.target.closest( '[data-gp-image-clear]' );
		var box = ( pick || clear ) && ( pick || clear ).closest( '[data-gp-image]' );
		if ( ! box ) return;
		e.preventDefault();

		var img = box.querySelector( 'img' );
		var input = box.querySelector( 'input' );
		var remove = box.querySelector( '[data-gp-image-clear]' );

		if ( clear ) {
			input.value = '';
			img.hidden = true;
			remove.hidden = true;
			return;
		}

		var frame = wp.media( { title: 'Select image', multiple: false, library: { type: 'image' } } );
		frame.on( 'select', function () {
			var att = frame.state().get( 'selection' ).first().toJSON();
			input.value = att.id;
			img.src = ( att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail : att ).url;
			img.hidden = false;
			remove.hidden = false;
		} );
		frame.open();
	} );
}() );
