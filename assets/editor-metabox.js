/**
 * Botón «Añadir a la base de conocimiento» del editor: AJAX (el metabox no
 * puede llevar un <form> propio dentro del formulario del editor).
 */
( function () {
	'use strict';

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-wookb-editor-add]' );
		var cfg = window.wookbEditorMetabox;
		if ( ! button || ! cfg || button.disabled ) {
			return;
		}
		event.preventDefault();

		var message = button.parentNode.querySelector( '[data-wookb-editor-msg]' );
		var label = button.textContent;
		button.disabled = true;
		button.textContent = cfg.working;

		var body = new FormData();
		body.append( 'action', 'wookb_editor_add_to_kb' );
		body.append( 'post_id', button.getAttribute( 'data-post-id' ) );
		body.append( 'nonce', button.getAttribute( 'data-nonce' ) );

		fetch( cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' } )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( result ) {
				if ( ! result || ! result.success ) {
					throw new Error( result && result.data && result.data.message ? result.data.message : cfg.error );
				}
				if ( message ) {
					message.textContent = result.data.message;
				}
				// Misma pantalla de edición, conservando el idioma.
				window.location.href = result.data.redirect;
			} )
			.catch( function ( error ) {
				if ( message ) {
					message.textContent = error.message || cfg.error;
				}
				button.disabled = false;
				button.textContent = label;
			} );
	} );
}() );
