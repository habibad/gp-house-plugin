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

/* Gallery field: pick several photos in order. */
( function () {
	document.addEventListener( 'click', function ( e ) {
		var pick = e.target.closest( '[data-gp-gallery-pick]' );
		if ( ! pick ) return;
		e.preventDefault();
		var box = pick.closest( '[data-gp-gallery]' );
		var input = box.querySelector( 'input' );
		var list = box.querySelector( '.gp-core-gallery__list' );
		var frame = wp.media( { title: 'Select photos', multiple: 'add', library: { type: 'image' } } );
		frame.on( 'open', function () {
			var sel = frame.state().get( 'selection' );
			input.value.split( ',' ).filter( Boolean ).forEach( function ( id ) {
				var att = wp.media.attachment( id );
				att.fetch();
				sel.add( att );
			} );
		} );
		frame.on( 'select', function () {
			var atts = frame.state().get( 'selection' ).toJSON();
			input.value = atts.map( function ( a ) { return a.id; } ).join( ',' );
			list.innerHTML = atts.map( function ( a ) {
				return '<img src="' + ( a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail : a ).url + '" alt="">';
			} ).join( '' );
		} );
		frame.open();
	} );
}() );
