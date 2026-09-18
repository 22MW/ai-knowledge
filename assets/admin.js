(function ($) {
	'use strict';

	/**
	 * Tema oscuro/claro (Tabler), encapsulado en .wookb-wrap: no se toca
	 * <html>/<body> para no afectar al resto de wp-admin. Orden de
	 * preferencia: eleccion guardada por el usuario (localStorage) ->
	 * preferencia del sistema operativo (prefers-color-scheme) -> oscuro por
	 * defecto si el navegador no expone esa preferencia.
	 */
	var STORAGE_KEY = 'wookb_theme';

	function detectDefaultTheme() {
		if ( window.matchMedia ) {
			if ( window.matchMedia( '(prefers-color-scheme: light)' ).matches ) {
				return 'light';
			}
			if ( window.matchMedia( '(prefers-color-scheme: dark)' ).matches ) {
				return 'dark';
			}
		}
		return 'dark'; // sin soporte de deteccion: oscuro por defecto (pedido explicito).
	}

	function applyTheme( $wrap, theme ) {
		$wrap.attr( 'data-bs-theme', theme );
		var $btn = $wrap.find( '.wookb-theme-toggle' );
		$btn.text( 'dark' === theme ? ' Modo claro' : ' Modo oscuro' );
	}

	// El tema inicial YA se fija con un <script> inline sincrono impreso por
	// Admin::render() al abrir .wookb-wrap (antes de que este archivo, en el
	// footer, llegue a ejecutarse) -- evita el salto claro->oscuro visible en
	// cada carga. Aqui solo queda actualizar el texto del boton acorde al
	// tema ya puesto, y gestionar el clic.
	$( function () {
		var $wrap = $( '.wookb-wrap' );
		if ( ! $wrap.length ) {
			return;
		}

		var current = $wrap.attr( 'data-bs-theme' );
		applyTheme( $wrap, ( 'dark' === current || 'light' === current ) ? current : detectDefaultTheme() );

		$wrap.on( 'click', '.wookb-theme-toggle', function ( e ) {
			e.preventDefault();
			var current = $wrap.attr( 'data-bs-theme' );
			var next = 'dark' === current ? 'light' : 'dark';
			applyTheme( $wrap, next );
			try {
				window.localStorage.setItem( STORAGE_KEY, next );
			} catch ( e2 ) {
				// no pasa nada si no se puede persistir: sigue funcionando en esta carga.
			}
		} );
	} );

	/**
	 * Fase 11 (revision UX): descarga de robots.txt/.htaccess por fetch() en
	 * vez de un submit normal, para poder habilitar el boton "Aplicar"
	 * hermano en cuanto termina, sin recargar la pestaña. La proteccion real
	 * sigue siendo server-side (el transient que comprueba class-admin.php
	 * al aplicar) -- esto es solo comodidad de interfaz, quitar el atributo
	 * "disabled" aqui no salta esa comprobacion.
	 */
	$( function () {
		$( '[data-wookb-copy-target]' ).on( 'click', function () {
			var target = document.getElementById( $( this ).data( 'wookb-copy-target' ) );
			if ( ! target ) {
				return;
			}
			navigator.clipboard.writeText( target.value ).then( function () {
				window.alert( 'Código copiado.' );
			} );
		} );

		$( '.wookb-download-form' ).on( 'submit', function ( e ) {
			e.preventDefault();
			var $form = $( this );
			var type  = $form.data( 'wookb-unlock' );
			var formData = new FormData( this );

			fetch( $form.attr( 'action' ), {
				method: 'POST',
				body: formData,
				credentials: 'same-origin'
			} ).then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'HTTP ' + response.status );
				}
				var disposition = response.headers.get( 'Content-Disposition' ) || '';
				var match = disposition.match( /filename="?([^"]+)"?/ );
				var filename = match ? match[1] : ( type + '-backup.txt' );
				return response.blob().then( function ( blob ) {
					return { blob: blob, filename: filename };
				} );
			} ).then( function ( result ) {
				var url = window.URL.createObjectURL( result.blob );
				var $link = $( '<a></a>' ).attr( { href: url, download: result.filename } ).hide();
				$( 'body' ).append( $link );
				$link[0].click();
				$link.remove();
				window.URL.revokeObjectURL( url );

				// Descarga confirmada: habilita el boton "Aplicar" hermano sin
				// recargar. El servidor ya marco el transient de confirmacion
				// dentro de la misma peticion fetch de arriba.
				$( '[data-wookb-apply="' + type + '"]' ).prop( 'disabled', false );
				$( '[data-wookb-unlock-notice="' + type + '"]' ).hide();
			} ).catch( function () {
				// Si falla la descarga por fetch, se cae al comportamiento normal
				// del navegador (submit real del formulario) como red de seguridad.
				HTMLFormElement.prototype.submit.call( $form[0] );
			} );
		} );
	} );
})(jQuery);
