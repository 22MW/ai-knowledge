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
	 * Fase AJAX 1: guardados simples. El action del formulario se conserva
	 * para que admin-post.php siga funcionando si JavaScript no esta activo.
	 */
	$( function () {
		var ajaxActions = [
			'wookb_save_content',
			'wookb_save_settings',
			'wookb_save_chatbot_settings',
			'wookb_save_queue_settings',
			'wookb_save_business_answers',
			'wookb_save_business_summary',
			'wookb_save_woocommerce_settings',
			'wookb_save_llms_faq',
			'wookb_save_crawler_actions',
			'wookb_save_crawler_visibility'
		];

		$( '.wookb-wrap' ).on( 'submit', 'form', function ( e ) {
			var form = this;
			var $form = $( form );
			var action = String( $form.find( 'input[name="action"]' ).first().val() || '' );
			if ( -1 === ajaxActions.indexOf( action ) || form.dataset.wookbAjaxBusy ) {
				return;
			}

			e.preventDefault();
			form.dataset.wookbAjaxBusy = '1';
			var $submit = $form.find( ':submit' );
			$submit.prop( 'disabled', true ).attr( 'aria-busy', 'true' );
			$form.next( '.wookb-ajax-notice' ).remove();

			fetch( window.ajaxurl, {
				method: 'POST',
				body: new FormData( form ),
				credentials: 'same-origin'
			} ).then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'HTTP ' + response.status );
				}
				return response.json();
			} ).then( function ( result ) {
				if ( ! result.success ) {
					throw new Error( result.data && result.data.message ? result.data.message : 'No se pudo guardar.' );
				}
				var message = result.data && result.data.message ? result.data.message : 'Guardado.';
				$form.after( '<div class="notice notice-success inline wookb-ajax-notice"><p></p></div>' );
				$form.next( '.wookb-ajax-notice' ).find( 'p' ).text( message );
			} ).catch( function ( error ) {
				$form.after( '<div class="notice notice-error inline wookb-ajax-notice"><p></p></div>' );
				$form.next( '.wookb-ajax-notice' ).find( 'p' ).text( 'No se pudo guardar sin recargar. Revisa la sesión y vuelve a intentarlo. (' + error.message + ')' );
			} ).finally( function () {
				delete form.dataset.wookbAjaxBusy;
				$submit.prop( 'disabled', false ).removeAttr( 'aria-busy' );
			} );
		} );
	} );

	$( function () {
		var $wrap = $( '.wookb-wrap' );
		var $drawer = $wrap.find( '[data-wookb-doc-drawer]' );
		var $backdrop = $wrap.find( '[data-wookb-doc-close]' ).filter( '.wookb-doc-backdrop' );
		function closeDocs() {
			$drawer.removeClass( 'is-open' ).attr( 'aria-hidden', 'true' );
			$backdrop.removeClass( 'is-open' );
		}
		$wrap.on( 'click', '[data-wookb-doc-open]', function () {
			$drawer.addClass( 'is-open' ).attr( 'aria-hidden', 'false' );
			$backdrop.addClass( 'is-open' );
			$drawer.find( '[data-wookb-doc-close]' ).not( '.wookb-doc-backdrop' ).trigger( 'focus' );
		} );
		$wrap.on( 'click', '[data-wookb-doc-close]', closeDocs );
		$( document ).on( 'keydown.wookbDocs', function ( e ) {
			if ( 'Escape' === e.key ) { closeDocs(); }
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
