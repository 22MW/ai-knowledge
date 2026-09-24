(function ($) {
	'use strict';
	var __ = window.wp && window.wp.i18n ? window.wp.i18n.__ : function (text) { return text; };

	/**
	 * Notificaciones flotantes ("toast") para los guardados AJAX del admin
	 * (Generar, Guardar límite/cambios/prompt, y cualquier otro guardado
	 * AJAX existente: Ajustes, Negocio, FAQs, WooCommerce, crawlers...).
	 * Pedido explícito del usuario: sustituyen al aviso fijo que se
	 * imprimía junto al formulario (con `$form.after()`, o en el caso de
	 * las filas del Registro, dentro de "Ajustes avanzados") -- un solo
	 * punto centralizado, no un aviso por acción. Arriba a la izquierda,
	 * fuera de `.wookb-wrap` (position: fixed en <body>, mismo patrón de
	 * z-index alto que ya usa `.wookb-doc-drawer` en admin.css) para que
	 * flote por encima de todo sin importar dónde esté desplazado el
	 * usuario en la pantalla. Desaparece sola a los 10 segundos.
	 */
	var TOAST_DURATION = 10000;

	function getToastContainer() {
		var $container = $( '#wookb-toast-container' );
		if ( $container.length ) {
			return $container;
		}
		// Dentro de .wookb-wrap (no de <body>): así hereda las variables de
		// color de Tabler en modo oscuro/claro (el atributo data-bs-theme
		// vive en .wookb-wrap, ver applyTheme() mas abajo). position:fixed
		// sigue colocándolo respecto a la ventana igual, .wookb-wrap no crea
		// un contexto de posicionamiento propio (sin transform/filter).
		$container = $( '<div id="wookb-toast-container" aria-live="polite"></div>' );
		var $wrap = $( '.wookb-wrap' ).first();
		if ( $wrap.length ) {
			$wrap.append( $container );
		} else {
			$( 'body' ).append( $container );
		}
		return $container;
	}

	function showToast( message, type ) {
		var $container = getToastContainer();
		var $toast = $( '<div class="wookb-toast"><p></p><button type="button" class="wookb-toast-close" aria-label="' + __( 'Cerrar aviso', 'ai-knowledge' ) + '">&times;</button></div>' );
		$toast.addClass( 'wookb-toast-' + ( 'error' === type ? 'error' : 'success' ) );
		$toast.find( 'p' ).text( message );
		$container.append( $toast );

		var timer = window.setTimeout( function () {
			$toast.remove();
		}, TOAST_DURATION );

		$toast.on( 'click', '.wookb-toast-close', function () {
			window.clearTimeout( timer );
			$toast.remove();
		} );
	}
	// Expuesto para el resto de bloques de este mismo archivo (segunda IIFE
	// del asistente de configuración, mas abajo, tiene su propio scope).
	window.wookbShowToast = showToast;

	/**
	 * "Guardar cambios" del textarea de contenido (fila del Registro,
	 * pestaña "Contenido" de Ajustes avanzados) empieza deshabilitado
	 * (atributo `disabled` puesto por PHP, ver
	 * Registry_Table::manual_expanded_row_markup()) y solo se activa si el
	 * valor actual del textarea difiere del original guardado en
	 * `data-wookb-original-value`. Con la clase activa se añade
	 * `wookb-btn-success` (ya existente, la reutiliza "Marcar revisado" --
	 * pedido explícito: no inventar un estilo nuevo).
	 */
	function updateManualSaveButtonState( $textarea ) {
		if ( ! $textarea || ! $textarea.length ) {
			return;
		}
		var original = $textarea.attr( 'data-wookb-original-value' ) || '';
		var dirty = $textarea.val() !== original;
		var formId = $textarea.attr( 'form' );
		if ( ! formId ) {
			return;
		}
		$( 'button[form="' + formId + '"][data-wookb-save-changes-btn]' )
			.prop( 'disabled', ! dirty )
			.toggleClass( 'wookb-btn-success', dirty );
	}

	$( function () {
		$( '.wookb-wrap' ).on( 'input', '[data-wookb-manual-textarea]', function () {
			updateManualSaveButtonState( $( this ) );
		} );
	} );

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

	// Filtros locales de crawlers: combinan tipo y estado sin recargar la página.
	$( function () {
		var $wrap = $( '.wookb-wrap' );
		var $filters = $( '[data-wookb-crawler-filters]' );
		if ( ! $filters.length ) { return; }
		var $rows = $( '[data-wookb-crawler-row]' );
		var $count = $filters.find( '[data-wookb-crawler-count]' );
		var $toggle = $filters.closest( '.wookb-crawler-section' ).find( '[data-wookb-crawler-toggle]' );
		var expanded = false;
		function applyCrawlerFilters() {
			var category = $filters.find( '[data-wookb-crawler-filter="category"]' ).val();
			var action = $filters.find( '[data-wookb-crawler-filter="action"]' ).val();
			var filtering = 'all' !== category || 'all' !== action;
			var visible = 0;
			$rows.each( function () {
				var $row = $( this );
				var matches = ( 'all' === category || category === $row.attr( 'data-crawler-category' ) ) && ( 'all' === action || action === $row.attr( 'data-crawler-action' ) );
				var inInitialPage = parseInt( $row.attr( 'data-crawler-index' ), 10 ) < 10;
				var show = matches && ( expanded || filtering || inInitialPage );
				$row.toggle( show );
				if ( matches ) { visible++; }
			} );
			$count.text( visible + ' de ' + $rows.length );
			$toggle.toggle( ! filtering && $rows.length > 10 );
			$toggle.text( expanded ? __( 'Mostrar menos crawlers', 'ai-knowledge' ) : __( 'Ver todos los crawlers', 'ai-knowledge' ) );
		}
		$filters.on( 'change', 'select', applyCrawlerFilters );
		$toggle.on( 'click', function () {
			expanded = ! expanded;
			applyCrawlerFilters();
		} );
		$wrap.on( 'change', '[data-wookb-crawler-row] select[name^="crawler_action"]', function () {
			$( this ).closest( '[data-wookb-crawler-row]' ).attr( 'data-crawler-action', $( this ).val() );
			applyCrawlerFilters();
		} );
		// "Permitir/Bloquear todos (visibles)": solo las filas que el filtro
		// de tipo/estado (mas "Ver todos los crawlers") deja visibles ahora
		// mismo -- dispara 'change' en cada select para reutilizar el mismo
		// handler de arriba (actualiza data-crawler-action y refresca el
		// contador), en vez de duplicar esa logica aqui.
		$filters.on( 'click', '[data-wookb-crawler-bulk]', function () {
			var value = $( this ).attr( 'data-wookb-crawler-bulk' );
			$rows.filter( ':visible' ).find( 'select[name^="crawler_action"]' ).val( value ).trigger( 'change' );
		} );
		applyCrawlerFilters();
	} );

	// Interruptor "Incluir llms.txt para los modelos desactivados" (pestaña
	// Visibilidad IA y asistente): actualiza la etiqueta ACTIVADO/DESACTIVADO al
	// momento; el guardado lo hacen los botones de cada pantalla. Delegado en
	// document: el asistente inyecta sus pasos por AJAX.
	$( document ).on( 'change', '.wookb-visibility-switch input[type="checkbox"]', function () {
		$( this ).closest( '.wookb-visibility-switch' ).toggleClass( 'is-on', this.checked );
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
			'wookb_generate_business_summary_draft',
			'wookb_generate_faqs_draft',
			'wookb_generate_prompt_draft',
			'wookb_normalize_prompt',
			'wookb_polish_store_doc',
			// Pieza 1: boton "Generar" del Registro sin recargar la pagina.
			'wookb_regenerate_single',
			// Botones "Guardar límite"/"Guardar cambios" (ya existían antes
			// de esta tarea): mismo tratamiento AJAX, pedido explícito.
			'wookb_set_manual',
			'wookb_set_char_limit',
			// "Volver a Auto" (botones de Contenido y Prompt, mismo <form>).
			'wookb_back_to_auto',
			// Pieza 2: guardar el prompt propio de un documento.
			'wookb_save_custom_prompt',
			// Pieza 5: artículos exclusivos de Genix.
			'wookb_genix_generate',
			'wookb_genix_remove'
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
				$submit.val( __( 'Procesando…', 'ai-knowledge' ) );
				$submit.after( '<span class="wookb-spinner wookb-spinner-sibling" aria-hidden="true"></span>' );
			} else {
				$submit.html( '<span class="wookb-spinner" aria-hidden="true"></span><span>' + __( 'Procesando…', 'ai-knowledge' ) + '</span>' );
			}

			// Fila real de la que cuelga el boton pulsado, para las acciones
			// del Registro que necesitan actualizar estado/fecha/enlaces/
			// contenido en pantalla (ver mas abajo). El aviso de guardado ya
			// NO depende de esta fila: ahora es un toast flotante
			// (showToast()), independiente de donde este el boton.
			var $btn = submitter ? $( submitter ) : null;
			var $anchor = $btn && $btn.length ? $btn : $form;
			var $expandRow = $anchor.closest( '.wookb-manual-expand-row' );

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
					throw new Error( result.data && result.data.message ? result.data.message : __( 'No se pudo guardar.', 'ai-knowledge' ) );
				}
				var message = result.data && result.data.message ? result.data.message : __( 'Guardado.', 'ai-knowledge' );
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
				// Pieza 1 + botones "Guardar límite"/"Guardar cambios": el
				// boton de "Generar" (fila principal) vive dentro de la fila
				// normal de la tabla; esos dos botones viven dentro de la
				// fila expandida ".wookb-manual-expand-row" (el <details> de
				// "Ajustes avanzados"), justo debajo -- localizamos la fila
				// PRINCIPAL en ambos casos para actualizar estado/fecha/
				// enlaces ($expandRow/$anchor ya calculados mas arriba, antes
				// del fetch, para el aviso de guardado).
				var wookbRegistryRowActions = [ 'wookb_regenerate_single', 'wookb_set_manual', 'wookb_set_char_limit', 'wookb_back_to_auto' ];
				if ( -1 !== wookbRegistryRowActions.indexOf( action ) && result.data && result.data.row ) {
					var row = result.data.row;
					var $rowExpand = $expandRow;
					var $mainRow = $rowExpand.length ? $rowExpand.prev( 'tr' ) : $anchor.closest( 'tr' );
					if ( ! $rowExpand.length && $mainRow.length ) {
						$rowExpand = $mainRow.next( '.wookb-manual-expand-row' );
					}
					if ( $mainRow.length ) {
						$mainRow.find( '.column-status' ).text( row.status );
						$mainRow.find( '.column-updated' ).text( row.updated );
						$mainRow.find( '.column-links' ).html( row.links );
					}
					if ( $rowExpand.length ) {
						var $textarea = $rowExpand.find( 'textarea[name="override_text"]' );
						if ( $textarea.length ) {
							if ( ! row.is_manual && null !== row.content ) {
								$textarea.val( row.content );
							}
							// Se acaba de guardar/regenerar/volver a Auto: lo que hay
							// ahora en el textarea pasa a ser el nuevo valor "sin
							// cambios" -- "Guardar cambios" vuelve a deshabilitarse.
							$textarea.attr( 'data-wookb-original-value', $textarea.val() );
							updateManualSaveButtonState( $textarea );
						}
					}
				}
				showToast( message, 'success' );
			} ).catch( function ( error ) {
				var errorMessage = __( 'No se pudo guardar sin recargar. Revisa la sesión y vuelve a intentarlo.', 'ai-knowledge' ) + ' (' + error.message + ')';
				showToast( errorMessage, 'error' );
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
			$heading.append( ' <button type="button" class="button-link wookb-doc-heading-link" data-wookb-doc-open="' + $wrap.data( 'wookb-doc-tab' ) + '" data-wookb-doc-anchor="' + anchor + '" aria-label="' + __( 'Abrir esta sección de documentación', 'ai-knowledge' ) + '">?</button>' );
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
			$title.text( __( 'Documentación', 'ai-knowledge' ) );
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
	 * Sub-pestañas "Contenido"/"Prompt" dentro de "Ajustes avanzados" de una
	 * fila del Registro (pedido explícito del usuario: demasiado apilado en
	 * un solo bloque). Solo mostrar/ocultar, sin AJAX -- reutiliza las
	 * mismas clases nav-tab/nav-tab-active que las pestañas grandes del
	 * admin. Sin JavaScript, los dos paneles quedan simplemente visibles a
	 * la vez (el atributo hidden inicial solo se aplica en el HTML server-
	 * side del panel "prompt"): fallback aceptable, nada deja de poder
	 * guardarse.
	 */
	$( function () {
		$( '.wookb-wrap' ).on( 'click', '[data-wookb-subtab-link]', function () {
			var $btn  = $( this );
			var $bar  = $btn.closest( '[data-wookb-subtabs]' );
			var $wrap = $bar.parent();
			var target = $btn.data( 'wookb-subtab-link' );

			$bar.find( '[data-wookb-subtab-link]' ).removeClass( 'nav-tab-active' );
			$btn.addClass( 'nav-tab-active' );

			$wrap.find( '[data-wookb-subtab-panel]' ).each( function () {
				var $panel = $( this );
				$panel.prop( 'hidden', $panel.data( 'wookb-subtab-panel' ) !== target );
			} );
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
				window.alert( __( 'Código copiado.', 'ai-knowledge' ) );
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

(function ($) {
	'use strict';
	if (!window.aikbAssistant) return;
	var data = window.aikbAssistant;
	var steps = data.steps || {};
	window.setTimeout(function () { $('.wookb-assistant').removeClass('is-loading'); }, 350);
	function updateProgress(step, completed) {
		var keys = Object.keys(steps), currentIndex = keys.indexOf(step);
		$('.wookb-assistant-progress li').removeClass('is-active is-done is-past is-future').each(function (index) { var $li=$(this), key=$li.data('assistant-step'); if (key === step) $li.addClass('is-active'); if ((completed || []).indexOf(key) !== -1) $li.addClass('is-done'); if (index < currentIndex) $li.addClass('is-past'); if (index > currentIndex) $li.addClass('is-future'); });
	}
	function showPanel(response) {
		if (!response || !response.success || !response.data.panel) return false;
		$('[data-assistant-stage]').html(response.data.panel);
		if ($('#wookb-assistant-geo-prompt').length && !$('#wookb-geo-prompt-notice').length) {
			$('#wookb-assistant-geo-prompt').attr('rows', 8).css('font-size', '13px');
			$('<p id="wookb-geo-prompt-notice" style="color:#ff931e;font-size:13px;margin:10px 0 6px;">' + wp.i18n.__('Si hay documentos pendientes, la auditoría será más completa cuando terminen de generarse.', 'ai-knowledge') + '</p>').insertBefore($('#wookb-assistant-geo-prompt'));
		}
		updateProgress(response.data.step, response.data.completed || []);
		window.history.pushState({}, '', 'admin.php?page=ai-knowledge-assistant&step=' + encodeURIComponent(response.data.step));
		return true;
	}
	$(document).on('click', '[data-server-action]', function (e) {
		e.preventDefault();
		e.stopImmediatePropagation();
		var button = this, action = button.getAttribute('data-server-action');
		if ('wookb_apply_robots_block' === action && !window.confirm('Confirma que ya descargaste la copia y quieres actualizar robots.txt.')) return false;
		if ('wookb_download_robots_backup' === action) $('[data-server-action="wookb_apply_robots_block"]').prop('disabled', false).removeAttr('disabled');
		var detached = document.createElement('form');
		detached.method = 'post';
		detached.action = data.adminPostUrl;
		detached.style.display = 'none';
		[['action', action], ['_wpnonce', button.getAttribute('data-server-nonce')]].concat(button.getAttribute('data-return-assistant') ? [['wookb_return_assistant', '1']] : []).forEach(function (pair) { var input = document.createElement('input'); input.type = 'hidden'; input.name = pair[0]; input.value = pair[1]; detached.appendChild(input); });
		document.body.appendChild(detached);
		detached.submit();
		return false;
	});
	$(document).on('submit', '[data-assistant-form]', function (e) {
		e.preventDefault();
		$('.wookb-assistant').addClass('is-loading');
		var $form=$(this), submitter=e.originalEvent && e.originalEvent.submitter, action=submitter ? submitter.value : 'continue';
		var payload=$form.serializeArray();
		payload.push({name:'action',value:'aikb_assistant_navigate'},{name:'nonce',value:data.nonce},{name:'step',value:$form.find('[name="assistant_step"]').val()},{name:'assistant_action',value:action});
		$form.find('button').prop('disabled', true);
		$.post(data.ajaxUrl, payload).done(function (response) { if (!showPanel(response)) { $('[data-assistant-feedback]').addClass('is-error').text(response.data && response.data.message ? response.data.message : wp.i18n.__('No se pudo guardar.', 'ai-knowledge')).addClass('is-visible'); } }).fail(function () { $('[data-assistant-feedback]').addClass('is-error').text(wp.i18n.__('No se pudo guardar.', 'ai-knowledge')).addClass('is-visible'); }).always(function () { $('[data-assistant-form] button').prop('disabled', false); $('.wookb-assistant').removeClass('is-loading'); });
	});
	$(document).on('click', '[data-assistant-step-link]', function (e) {
		e.preventDefault();
		$('.wookb-assistant').addClass('is-loading');
		var step=$(this).closest('li').data('assistant-step');
		$.post(data.ajaxUrl,{action:'aikb_assistant_navigate',nonce:data.nonce,step:step,assistant_action:'goto'}).done(showPanel).always(function () { $('.wookb-assistant').removeClass('is-loading'); });
	});
	$(document).on('click', '[data-assistant-category]', function () {
		var $form=$(this).closest('form'), category=$(this).data('assistant-category'), step=$form.find('[name="assistant_step"]').val();
		$.post(data.ajaxUrl, $form.serializeArray().concat([{name:'action',value:'aikb_assistant_navigate'},{name:'nonce',value:data.nonce},{name:'step',value:step},{name:'assistant_action',value:'goto'},{name:'assistant_category',value:category}])).done(showPanel);
	});
	$(document).on('click', '[data-crawler-bulk]', function () {
		var value = $(this).data('crawler-bulk');
		$('[data-assistant-stage] .wookb-assistant-crawlers select[name^="crawler_action["]').val(value);
	});
	$(document).on('click', '[data-assistant-check-server]', function () {
		$('.wookb-assistant').addClass('is-loading');
		$.post(data.ajaxUrl, {action:'aikb_assistant_navigate', nonce:data.nonce, step:'server', assistant_action:'goto'}).done(showPanel).always(function () { $('.wookb-assistant').removeClass('is-loading'); });
	});
	if ($('#wookb-assistant-geo-prompt').length && !$('#wookb-geo-prompt-notice').length) { $('#wookb-assistant-geo-prompt').attr('rows', 8).css('font-size', '13px'); $('<p id="wookb-geo-prompt-notice" style="color:#ff931e;font-size:13px;margin:10px 0 6px;">' + wp.i18n.__('Si hay documentos pendientes, la auditoría será más completa cuando terminen de generarse.', 'ai-knowledge') + '</p>').insertBefore($('#wookb-assistant-geo-prompt')); }
})(jQuery);
