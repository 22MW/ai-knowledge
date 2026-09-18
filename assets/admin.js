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
			'wookb_save_crawler_visibility',
			'wookb_generate_business_summary_draft',
			'wookb_generate_faqs_draft',
			'wookb_generate_prompt_draft',
			'wookb_normalize_prompt',
			'wookb_polish_store_doc'
		];

		$( '.wookb-wrap' ).on( 'submit', 'form', function ( e ) {
			var form = this;
			var $form = $( form );
			var action = String( $form.find( 'input[name="action"]' ).first().val() || '' );
			var submitter = e.originalEvent && e.originalEvent.submitter ? e.originalEvent.submitter : null;
			var submitterParams = null;
			if ( ! action && submitter && submitter.formAction ) {
				try {
					submitterParams = new URL( submitter.formAction, window.location.href ).searchParams;
					action = submitterParams.get( 'action' ) || '';
				} catch ( error ) {
					// El navegador no soporta URL: se conserva el POST tradicional.
				}
			}
			if ( -1 === ajaxActions.indexOf( action ) || form.dataset.wookbAjaxBusy ) {
				return;
			}

			e.preventDefault();
			form.dataset.wookbAjaxBusy = '1';
			var $submit = submitter ? $( submitter ) : $form.find( ':submit' ).first();
			var isInputSubmit = 'INPUT' === $submit.prop( 'tagName' );
			var originalSubmitText = isInputSubmit ? $submit.val() : $submit.html();
			$submit.data( 'wookb-original-text', originalSubmitText ).prop( 'disabled', true ).attr( 'aria-busy', 'true' );
			if ( isInputSubmit ) {
				$submit.val( 'Procesando…' );
				$submit.after( '<span class="wookb-spinner wookb-spinner-sibling" aria-hidden="true"></span>' );
			} else {
				$submit.html( '<span class="wookb-spinner" aria-hidden="true"></span><span>Procesando…</span>' );
			}
			$form.next( '.wookb-ajax-notice' ).remove();

			var formData = new FormData( form );
			formData.set( 'action', action );
			if ( submitterParams && submitterParams.get( '_wpnonce' ) ) {
				formData.set( '_wpnonce', submitterParams.get( '_wpnonce' ) );
			}
			fetch( window.ajaxurl, {
				method: 'POST',
				body: formData,
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
				var value = result.data && (result.data.draft || result.data.polished);
				if ( value ) {
					if ( 'wookb_generate_business_summary_draft' === action ) {
						$form.closest( '.wookb-card' ).find( 'textarea[name="summary_draft"]' ).val( value );
					} else if ( 'wookb_generate_faqs_draft' === action ) {
						$form.closest( '.wookb-card' ).find( 'textarea[name="llms_faq"]' ).val( value );
					} else if ( 'wookb_generate_prompt_draft' === action || 'wookb_normalize_prompt' === action ) {
						$form.closest( '.wookb-card' ).find( 'textarea[name="draft"]' ).val( value );
					} else if ( 'wookb_polish_store_doc' === action ) {
						$form.nextAll( 'textarea[readonly]' ).first().val( value );
					}
				}
				$form.after( '<div class="notice notice-success inline wookb-ajax-notice"><p></p></div>' );
				$form.next( '.wookb-ajax-notice' ).find( 'p' ).text( message );
			} ).catch( function ( error ) {
				$form.after( '<div class="notice notice-error inline wookb-ajax-notice"><p></p></div>' );
				$form.next( '.wookb-ajax-notice' ).find( 'p' ).text( 'No se pudo guardar sin recargar. Revisa la sesión y vuelve a intentarlo. (' + error.message + ')' );
			} ).finally( function () {
				delete form.dataset.wookbAjaxBusy;
				if ( isInputSubmit ) {
					$submit.val( $submit.data( 'wookb-original-text' ) || originalSubmitText );
					$submit.next( '.wookb-spinner-sibling' ).remove();
				} else {
					$submit.html( $submit.data( 'wookb-original-text' ) || originalSubmitText );
				}
				$submit.prop( 'disabled', false ).removeAttr( 'aria-busy' );
			} );
		} );
	} );

	$( function () {
		var $wrap = $( '.wookb-wrap' );
		var $drawer = $wrap.find( '[data-wookb-doc-drawer]' );
		var $backdrop = $wrap.find( '[data-wookb-doc-close]' ).filter( '.wookb-doc-backdrop' );
		var $content = $drawer.find( '.wookb-doc-drawer-content' );
		var $title = $drawer.find( '.wookb-doc-drawer-header h2' );
		var $back = $drawer.find( '[data-wookb-doc-back]' );
		var initialContent = $content.html();
		var initialTitle = $title.text();
		function slugifyHeading( text ) {
			return text.toString().toLowerCase().normalize( 'NFD' ).replace( /[\u0300-\u036f]/g, '' ).replace( /[^a-z0-9\s-]/g, '' ).trim().replace( /\s+/g, '-' );
		}
		$wrap.find( '.wookb-card h2, .wookb-card h3' ).each( function () {
			var $heading = $( this );
			if ( $heading.find( '[data-wookb-doc-open]' ).length ) { return; }
			var anchor = slugifyHeading( $heading.clone().children().remove().end().text() );
			var validInCurrent = $content.find( '#' + anchor ).length > 0;
			var validInTemplate = $drawer.find( 'template' ).toArray().some( function ( template ) {
				return $( template.innerHTML ).filter( '#' + anchor ).length > 0;
			} );
			if ( validInCurrent || validInTemplate ) {
				$heading.append( ' <button type="button" class="button-link wookb-doc-heading-link" data-wookb-doc-open="' + $wrap.data( 'wookb-doc-tab' ) + '" data-wookb-doc-anchor="' + anchor + '" aria-label="Abrir esta sección de documentación">?</button>' );
			}
		} );
		function closeDocs() {
			$drawer.removeClass( 'is-open' ).attr( 'aria-hidden', 'true' );
			$backdrop.removeClass( 'is-open' );
		}
		$wrap.on( 'click', '[data-wookb-doc-open]', function () {
			var alreadyOpen = $drawer.hasClass( 'is-open' );
			if ( ! alreadyOpen ) {
				initialContent = $content.html();
				initialTitle = $title.text();
				$back.prop( 'hidden', true );
			}
			$drawer.addClass( 'is-open' ).attr( 'aria-hidden', 'false' );
			$backdrop.addClass( 'is-open' );
			$drawer.find( '[data-wookb-doc-close]' ).not( '.wookb-doc-backdrop' ).trigger( 'focus' );
			var anchor = String( $( this ).data( 'wookb-doc-anchor' ) || '' );
			if ( anchor ) {
				window.setTimeout( function () {
					var $target = $content.find( '#' + anchor );
					if ( $target.length ) { $target[ 0 ].scrollIntoView( { block: 'start' } ); }
				}, 50 );
			}
		} );
		$wrap.on( 'click', '[data-wookb-doc-link]', function ( e ) {
			e.preventDefault();
			var file = $( this ).data( 'wookb-doc-link' );
			var template = $drawer.find( '[data-wookb-doc-template="' + file + '"]' )[ 0 ];
			if ( ! template ) { return; }
			$content.html( template.innerHTML );
			$title.text( 'Documentación' );
			$back.prop( 'hidden', false ).trigger( 'focus' );
			var anchor = String( $( this ).data( 'wookb-doc-anchor' ) || '' );
			if ( anchor ) {
				var $target = $content.find( '#' + anchor );
				if ( $target.length ) { $target[ 0 ].scrollIntoView( { block: 'start' } ); }
			}
		} );
		$wrap.on( 'click', '[data-wookb-doc-anchor-only]', function ( e ) {
			e.preventDefault();
			var target = document.getElementById( $( this ).data( 'wookb-doc-anchor-only' ) );
			if ( target ) { target.scrollIntoView( { block: 'start' } ); }
		} );
		$wrap.on( 'click', '[data-wookb-doc-back]', function () {
			$content.html( initialContent );
			$title.text( initialTitle );
			$back.prop( 'hidden', true );
		} );
		$wrap.on( 'click', '[data-wookb-doc-close]:not(.wookb-doc-backdrop)', closeDocs );
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
