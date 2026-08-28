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
		$btn.text( 'dark' === theme ? '☀️ Modo claro' : '🌙 Modo oscuro' );
	}

	$( function () {
		var $wrap = $( '.wookb-wrap' );
		if ( ! $wrap.length ) {
			return;
		}

		var saved = null;
		try {
			saved = window.localStorage.getItem( STORAGE_KEY );
		} catch ( e ) {
			// localStorage no disponible (privado/bloqueado): se sigue sin recordar la eleccion.
		}

		var theme = ( 'dark' === saved || 'light' === saved ) ? saved : detectDefaultTheme();
		applyTheme( $wrap, theme );

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
})(jQuery);
