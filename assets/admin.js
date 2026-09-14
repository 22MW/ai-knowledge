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
})(jQuery);
