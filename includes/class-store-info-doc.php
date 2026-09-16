<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Documentos "compuestos" (no derivados de un único post) sobre la tienda en
 * general: cómo comprar (carrito/checkout), condiciones de venta reales, y el
 * catálogo de la tienda (categorías y productos). Uno por idioma activo.
 *
 * Por qué una clase nueva y no reutilizar Generator/Extractor_Base:
 * - Extractor_Base + Generator asumen un único post de origen con hash de
 *   cambio propio; aquí el "origen" es la combinación de varias fuentes
 *   (páginas de ajustes de WooCommerce, zonas de envío, pasarelas de pago,
 *   listado de productos) que no tiene un post_id único que vigilar.
 * - El contenido es legal/factual (condiciones de venta, métodos de pago
 *   reales) y NO se redacta vía IA: parafrasear con un modelo introduce
 *   riesgo de alucinación sobre datos que el cliente puede tomar como
 *   vinculantes. Se compone de forma determinista a partir de la
 *   configuración real de WooCommerce, igual que Generator::build_bridge_markdown()
 *   ya hace para el documento puente (factual, sin pasar por IA).
 * - Aun así reutiliza el resto del pipeline: Markdown_Store para el .md,
 *   Genix_Bridge para subir a sgkb-docs, y Registry para aparecer en
 *   llms.txt -- solo el paso de "generar contenido" es propio.
 *
 * Estas filas de Registry usan un source_id "centinela" fijo (no corresponde
 * a ningún post real) porque no hay un post que las respalde. Registry::
 * find_orphans() ya está generalizado (ver class-registry.php) para no tratar
 * estos source_type como huérfanos aunque get_post() no encuentre nada.
 */
class Store_Info_Doc {

	const SOURCE_TYPE_STORE_INFO   = 'wookb-store-info';
	const SOURCE_TYPE_SHOP_CATALOG = 'wookb-shop-catalog';

	// IDs centinela: muy por encima de cualquier post_id real de este sitio.
	// BIGINT UNSIGNED en la tabla de registro, así que deben ser positivos.
	const SOURCE_ID_STORE_INFO   = 900000001;
	const SOURCE_ID_SHOP_CATALOG = 900000002;

	// Tope de caracteres del cuerpo, mayor que Generator::BODY_CHAR_LIMIT (1000):
	// estos documentos no compiten por relevancia palabra-a-palabra contra fichas
	// de producto (ver comentario de Generator::BODY_CHAR_LIMIT) -- son la única
	// fuente de esta información en toda la base de conocimiento, así que cortar
	// agresivamente perdería condiciones de venta o métodos de pago reales antes
	// que "nivelar el terreno" aporte nada. Se mantiene un tope para no crecer sin
	// control (zonas de envío/pasarelas mal configuradas no deben desbordar el doc).
	// Subido de 2500 a 20000: con el catálogo real (181 productos en 6 categorías)
	// el límite anterior cortaba build_shop_catalog_body() antes de llegar a la
	// última categoría alfabética (get_terms() las devuelve ordenadas por nombre),
	// dejando fuera productos completos del documento -- bug confirmado en
	// producción, no una hipótesis. Afecta a ambos documentos porque comparten
	// esta misma constante (store-info y shop-catalog).
	const CHAR_LIMIT = 20000;

	/**
	 * Genera (o regenera) los documentos de información de tienda y catálogo
	 * para todos los idiomas activos. Pensado para lanzarse manualmente desde
	 * el admin (botón en Ajustes), no desde el ciclo de guardado de posts: su
	 * origen (ajustes de WooCommerce) no dispara ningún hook de save_post.
	 *
	 * Devuelve un resumen array( 'ok' => int, 'errores' => array ).
	 */
	public static function generate_all() {
		$ok      = 0;
		$errores = array();

		foreach ( Wpml::active_languages() as $lang ) {
			$result = self::generate_store_info( $lang );
			if ( is_wp_error( $result ) ) {
				$errores[] = 'store-info (' . $lang . '): ' . $result->get_error_message();
			} else {
				$ok++;
			}

			$result = self::generate_shop_catalog( $lang );
			if ( is_wp_error( $result ) ) {
				$errores[] = 'shop-catalog (' . $lang . '): ' . $result->get_error_message();
			} else {
				$ok++;
			}
		}

		Llms_Txt::invalidate();

		return array( 'ok' => $ok, 'errores' => $errores );
	}

	/**
	 * Documento "cómo funciona la compra + condiciones de venta + envío y
	 * pago" para un idioma. Devuelve true|WP_Error.
	 */
	public static function generate_store_info( $lang ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new \WP_Error( 'wookb_no_woocommerce', __( 'WooCommerce no está activo.', 'ai-knowledge' ) );
		}

		$body = self::build_store_info_body( $lang );
		$body = self::enforce_char_limit( $body );

		$title = self::title_store_info( $lang );
		$url   = self::url_store_info( $lang );

		return self::persist(
			self::SOURCE_ID_STORE_INFO,
			self::SOURCE_TYPE_STORE_INFO,
			$lang,
			$title,
			$url,
			$body
		);
	}

	/**
	 * Documento de catálogo: productos activos agrupados por categoría +
	 * explicación de qué se puede hacer en la página de tienda. Para un
	 * idioma. Devuelve true|WP_Error.
	 */
	public static function generate_shop_catalog( $lang ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new \WP_Error( 'wookb_no_woocommerce', __( 'WooCommerce no está activo.', 'ai-knowledge' ) );
		}

		$body = self::build_shop_catalog_body( $lang );
		$body = self::enforce_char_limit( $body );

		$title = self::title_shop_catalog( $lang );
		$url   = self::url_shop_catalog( $lang );

		return self::persist(
			self::SOURCE_ID_SHOP_CATALOG,
			self::SOURCE_TYPE_SHOP_CATALOG,
			$lang,
			$title,
			$url,
			$body
		);
	}

	/**
	 * Escritura común: .md -> sgkb-docs (si Genix está disponible) -> fila de
	 * Registry. Con detección de cambios por hash, igual que Document_Pipeline,
	 * para no reescribir/resubir si el contenido no cambió.
	 */
	protected static function persist( $source_id, $source_type, $lang, $title, $url, $body ) {
		$hash     = hash( 'sha256', $body );
		$existing = Registry::find( $source_id, $lang );

		if ( $existing && $existing->source_hash === $hash && 'synced' === $existing->status ) {
			return true;
		}

		// Modo manual (ej. tras "Pulir redacción con IA", ver Admin::polish_store_doc()):
		// el texto lo fijó el admin a mano, no se regenera aquí. Mismo criterio
		// que Document_Pipeline::process() para filas de producto.
		if ( $existing && 'manual' === $existing->override_mode ) {
			return true;
		}

		$markdown = "# {$title}\n\n" . $body;

		$slug     = 'store-info' === self::type_slug( $source_type ) ? 'informacion-tienda' : 'catalogo-tienda';
		$relative = Markdown_Store::write(
			$lang,
			$slug,
			$markdown,
			array(
				'source_id'    => $source_id,
				'source_type'  => $source_type,
				'lang'         => $lang,
				'source_hash'  => $hash,
				'generated_at' => current_time( 'mysql' ),
				'product_url'  => $url,
				'bridge'       => false,
			)
		);

		$data = array(
			'id'    => $source_id,
			'title' => $title,
			'url'   => $url,
		);

		$doc_post_id = $existing ? $existing->doc_post_id : null;
		if ( Genix_Bridge::is_available() ) {
			// Cada documento compuesto es independiente por idioma: no forma parte
			// de un trid de producto, así que no hay doc_trid que reutilizar entre
			// idiomas (a diferencia de Document_Pipeline::process()). Cada fila
			// mantiene su propio post sgkb-docs, enlazado por idioma vía Wpml::set_language()
			// sin trid compartido -- Genix no necesita agruparlos para el chatbot.
			$result = Genix_Bridge::upsert_document( $doc_post_id, $data, $markdown, $lang, null );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$doc_post_id = $result;
		}

		Registry::upsert(
			array(
				'source_id'   => $source_id,
				'source_type' => $source_type,
				'lang'        => $lang,
				'md_path'     => $relative,
				'doc_post_id' => $doc_post_id,
				'source_hash' => $hash,
				'status'      => 'synced',
				'is_bridge'   => 0,
				'generated_at' => current_time( 'mysql' ),
				'last_error'  => null,
			)
		);

		return true;
	}

	protected static function type_slug( $source_type ) {
		return self::SOURCE_TYPE_STORE_INFO === $source_type ? 'store-info' : 'shop-catalog';
	}

	/**
	 * Cuerpo del documento de información de tienda: proceso de compra,
	 * páginas reales de WooCommerce, condiciones de venta (si hay página de
	 * términos), envío y pago tal como están configurados en el sitio.
	 */
	protected static function build_store_info_body( $lang ) {
		$lines = array();

		$lines[] = self::label( $lang, 'intro' );
		$lines[] = '';

		// Páginas reales de WooCommerce (Ajustes > Páginas de WooCommerce), traducidas
		// al idioma solicitado vía WPML si existe traducción (mismo mecanismo que
		// Wpml::get_translation_id() usa para productos: funciona igual para 'page').
		$cart_id     = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'cart' ) : 0;
		$checkout_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'checkout' ) : 0;
		$account_id  = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'myaccount' ) : 0;

		$lines[] = '## ' . self::label( $lang, 'heading_process' );
		$lines[] = self::label( $lang, 'process_body' );
		if ( $cart_id > 0 ) {
			$lines[] = '- ' . self::label( $lang, 'label_cart' ) . ': ' . self::translated_url( $cart_id, $lang );
		}
		if ( $checkout_id > 0 ) {
			$lines[] = '- ' . self::label( $lang, 'label_checkout' ) . ': ' . self::translated_url( $checkout_id, $lang );
		}
		if ( $account_id > 0 ) {
			$lines[] = '- ' . self::label( $lang, 'label_account' ) . ': ' . self::translated_url( $account_id, $lang );
		}
		$lines[] = '';

		// Condiciones de venta: la página de términos y condiciones que WooCommerce
		// ya obliga a aceptar en el checkout si está configurada (Ajustes > Cuentas
		// y privacidad). Si no está configurada, se documenta como pendiente en vez
		// de inventar un texto legal genérico.
		$terms_id = (int) get_option( 'woocommerce_terms_page_id' );
		$lines[]  = '## ' . self::label( $lang, 'heading_terms' );
		if ( $terms_id > 0 ) {
			$translated_terms_id = Wpml::get_translation_id( $terms_id, $lang );
			$translated_terms_id = $translated_terms_id ? $translated_terms_id : $terms_id;
			$terms_post = get_post( $translated_terms_id );
			if ( $terms_post && 'publish' === $terms_post->post_status ) {
				$excerpt = wp_strip_all_tags( $terms_post->post_content );
				$excerpt = preg_replace( '/\s+/', ' ', trim( $excerpt ) );
				$lines[] = mb_substr( $excerpt, 0, 800 );
				$lines[] = '';
				$lines[] = self::label( $lang, 'terms_link' ) . ': ' . get_permalink( $translated_terms_id );
			} else {
				$lines[] = self::label( $lang, 'terms_pending' );
			}
		} else {
			// [pendiente]: no hay página de condiciones de venta configurada en
			// WooCommerce (Ajustes > Cuentas y privacidad > Página de términos y
			// condiciones). No se inventa texto legal: se marca explícitamente.
			$lines[] = self::label( $lang, 'terms_not_configured' );
		}
		$lines[] = '';

		// Política de devoluciones: página real de WooCommerce (wc_get_page_id()
		// acepta 'refund_returns' desde WooCommerce 5.8+, disponible en este sitio
		// -- verificado por MCP execute-php antes de usarla, no asumido). Mismo
		// patrón que heading_terms: extracto + enlace si hay página publicada, y
		// [pendiente] si no hay nada configurado en vez de inventar una política.
		$refund_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'refund_returns' ) : 0;
		$lines[]   = '## ' . self::label( $lang, 'heading_returns' );
		if ( $refund_id > 0 ) {
			$translated_refund_id = Wpml::get_translation_id( $refund_id, $lang );
			$translated_refund_id = $translated_refund_id ? $translated_refund_id : $refund_id;
			$refund_post = get_post( $translated_refund_id );
			if ( $refund_post && 'publish' === $refund_post->post_status ) {
				$excerpt = wp_strip_all_tags( $refund_post->post_content );
				$excerpt = preg_replace( '/\s+/', ' ', trim( $excerpt ) );
				$lines[] = mb_substr( $excerpt, 0, 800 );
				$lines[] = '';
				$lines[] = self::label( $lang, 'returns_link' ) . ': ' . get_permalink( $translated_refund_id );
			} else {
				$lines[] = self::label( $lang, 'returns_pending' );
			}
		} else {
			// [pendiente]: no hay página de devoluciones/reembolsos configurada en
			// WooCommerce (Ajustes > Cuentas y privacidad > Página de devoluciones y
			// reembolsos). No se inventa una política genérica.
			$lines[] = self::label( $lang, 'returns_not_configured' );
		}
		$lines[] = '';

		// Envíos: métodos realmente habilitados y seleccionados por el admin
		// (pestaña WooCommerce, checkbox por método -- ver shipping_summary_lines()).
		// Envío gratis y recogida en tienda se detectan solos si están
		// configurados como método de envío real; si no, se usan los campos
		// manuales de fallback (pedido mínimo / recogida) en vez de inventar.
		$shipping_lines = self::shipping_summary_lines( $lang );
		$lines[]        = '## ' . self::label( $lang, 'heading_shipping' );
		if ( empty( $shipping_lines ) ) {
			$lines[] = self::label( $lang, 'shipping_not_configured' );
		} else {
			$lines = array_merge( $lines, $shipping_lines );
		}
		$wc_settings = Scope::settings();
		if ( ! self::has_shipping_method_type( 'free_shipping' ) ) {
			$min_order_note = trim( (string) $wc_settings['wc_min_order_note'] );
			if ( '' !== $min_order_note ) {
				$lines[] = '- ' . $min_order_note;
			}
		}
		if ( ! self::has_shipping_method_type( 'local_pickup' ) && ! empty( $wc_settings['wc_pickup_available'] ) ) {
			$lines[] = '- ' . self::label( $lang, 'pickup_available_manual' );
		}
		$lines[] = '';

		// Plazos de entrega: WooCommerce no expone un campo de plazo en los
		// métodos de envío activos (verificado por MCP execute-php inspeccionando
		// WC_Shipping_Zones::get_zones()), así que es un dato de texto libre que
		// el propio negocio rellena a mano (Fase 2, pestaña WooCommerce, sección
		// "Rellenar a mano"). Si no lo ha rellenado todavía, se marca
		// [pendiente]/[pending] en vez de inventar un plazo que nadie confirmó.
		$delivery_note = trim( (string) Scope::settings()['delivery_time_note'] );
		$lines[]       = '## ' . self::label( $lang, 'heading_delivery_times' );
		$lines[]       = '' !== $delivery_note ? $delivery_note : self::label( $lang, 'delivery_times_pending' );
		$lines[]       = '';

		// Contacto y horario: propio de la tienda online (pestaña WooCommerce,
		// wc_contact_hours) si se ha rellenado -- puede diferir del contacto
		// general del negocio. Si no, cae al del cuestionario del chatbot
		// (Chatbot_Prompt_Builder, pestaña Negocio), ya validado por el
		// cliente, para no duplicar el dato si es el mismo.
		$contact = trim( (string) Scope::settings()['wc_contact_hours'] );
		if ( '' === $contact && class_exists( '\WOOKB\Chatbot_Prompt_Builder' ) ) {
			$answers = \WOOKB\Chatbot_Prompt_Builder::get_saved_answers();
			$contact = isset( $answers['contacto'] ) ? trim( (string) $answers['contacto'] ) : '';
		}
		if ( '' !== $contact ) {
			$lines[] = '## ' . self::label( $lang, 'heading_contact' );
			$lines[] = $contact;
			$lines[] = '';
		}

		// Pago: pasarelas realmente habilitadas (enabled === 'yes'), no todas las
		// instaladas.
		$lines[] = '## ' . self::label( $lang, 'heading_payment' );
		$payment_lines = self::payment_summary();
		if ( $payment_lines ) {
			$lines = array_merge( $lines, $payment_lines );
		} else {
			$lines[] = self::label( $lang, 'payment_not_configured' );
		}
		$lines[] = '';

		// Impuestos/IVA (Fase 2, nuevo): tipos configurados de verdad en
		// WooCommerce (Ajustes > Impuestos), no inventados. Si los impuestos
		// estan desactivados en WooCommerce, o activados pero sin ningun tipo
		// dado de alta, se marca [pendiente]/[pending] en vez de asumir un
		// porcentaje.
		$lines[] = '## ' . self::label( $lang, 'heading_tax' );
		if ( ! function_exists( 'wc_tax_enabled' ) || ! wc_tax_enabled() ) {
			$lines[] = self::label( $lang, 'tax_disabled' );
		} else {
			$tax_lines = self::tax_summary_lines( $lang );
			if ( $tax_lines ) {
				$lines = array_merge( $lines, $tax_lines );
				$lines[] = '';
				$lines[] = function_exists( 'wc_prices_include_tax' ) && wc_prices_include_tax()
					? self::label( $lang, 'tax_prices_include' )
					: self::label( $lang, 'tax_prices_exclude' );
			} else {
				$lines[] = self::label( $lang, 'tax_not_configured' );
			}
		}
		$lines[] = '';

		// Notas legales adicionales (Fase 2, "Rellenar a mano"): texto libre
		// del negocio, no inventado. Sin seccion si no se ha rellenado nada.
		$legal_notes = trim( (string) Scope::settings()['legal_notes_extra'] );
		if ( '' !== $legal_notes ) {
			$lines[] = '## ' . self::label( $lang, 'heading_legal_notes' );
			$lines[] = $legal_notes;
			$lines[] = '';
		}

		if ( function_exists( 'get_woocommerce_currency' ) ) {
			$lines[] = self::label( $lang, 'label_currency' ) . ': ' . get_woocommerce_currency();
		}

		return implode( "\n", $lines );
	}

	/**
	 * Cuerpo del documento de catálogo: productos activos agrupados por
	 * categoría + qué se puede hacer en la página de tienda (filtros,
	 * categorías disponibles).
	 */
	protected static function build_shop_catalog_body( $lang ) {
		$lines = array();

		$lines[] = self::label( $lang, 'shop_intro' );
		$lines[] = '';

		$shop_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
		if ( $shop_id > 0 ) {
			$lines[] = self::label( $lang, 'shop_url_label' ) . ': ' . self::translated_url( $shop_id, $lang );
			$lines[] = '';
		}

		$lines[] = '## ' . self::label( $lang, 'heading_browsing' );
		$lines[] = self::label( $lang, 'browsing_body' );
		$lines[] = '';

		// Categorías reales con productos, en el idioma de destino: usa
		// wpml_object_id sobre product_cat igual que Llms_Txt::category_label()
		// hace para no mezclar nombres de categoría de idiomas distintos.
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
			)
		);
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			$lines[] = self::label( $lang, 'catalog_empty' );
			return implode( "\n", $lines );
		}

		$lines[] = '## ' . self::label( $lang, 'heading_catalog' );

		$selection = Scope::settings()['wc_catalog_categories'];

		foreach ( $terms as $term ) {
			if ( ! self::is_selected( $selection, $term->term_id ) ) {
				continue;
			}
			$term_id = $term->term_id;
			if ( class_exists( 'SitePress' ) ) {
				$translated_term_id = apply_filters( 'wpml_object_id', $term_id, 'product_cat', false, $lang );
				if ( $translated_term_id ) {
					$term_id = $translated_term_id;
				}
			}
			$term_obj = get_term( $term_id, 'product_cat' );
			if ( ! $term_obj || is_wp_error( $term_obj ) ) {
				continue;
			}

			$products = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'tax_query'      => array( // phpcs:ignore
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'term_id',
							'terms'    => array( $term->term_id ), // filtra por el término en el idioma por defecto, consistente con la resolución de $terms.
						),
					),
				)
			);
			if ( empty( $products ) ) {
				continue;
			}

			$lines[] = '### ' . $term_obj->name;
			foreach ( $products as $product_id ) {
				$translated_id = Wpml::get_translation_id( $product_id, $lang );
				$translated_id = $translated_id ? $translated_id : $product_id;
				if ( 'publish' !== get_post_status( $translated_id ) ) {
					continue;
				}
				$title   = wp_strip_all_tags( get_the_title( $translated_id ) );
				$link    = get_permalink( $translated_id );
				$lines[] = '- [' . $title . '](' . $link . ')';
			}
			$lines[] = '';
		}

		return implode( "\n", $lines );
	}

	/**
	 * true si un elemento (por su id -- instance_id de envío, tax_rate_id,
	 * term_id de categoría) esta seleccionado para entrar en el documento.
	 * null en la settings = nunca se guardo una seleccion todavia -> se
	 * trata TODO como seleccionado (no perder contenido ya publicado sin
	 * decision explicita del admin); array = seleccion real, aunque vacia.
	 */
	protected static function is_selected( $selection, $id ) {
		if ( null === $selection ) {
			return true;
		}
		return isset( $selection[ $id ] ) && 'include' === $selection[ $id ];
	}

	/**
	 * Metodos de envio realmente habilitados, filtrados por la seleccion
	 * del admin (pestaña WooCommerce, checkbox por metodo). Enriquece los
	 * metodos "Envio gratis" con su importe minimo real (WC_Shipping_Free_Shipping,
	 * opcion 'min_amount') en vez de dejarlo como dato manual -- WooCommerce
	 * ya lo tiene configurado si el metodo esta activo.
	 */
	public static function shipping_summary_lines( $lang ) {
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			return array();
		}

		$selection = Scope::settings()['wc_shipping_methods'];
		$lines     = array();

		foreach ( \WC_Shipping_Zones::get_zones() as $zone ) {
			foreach ( (array) $zone['shipping_methods'] as $method ) {
				if ( 'no' === $method->enabled ) {
					continue;
				}
				if ( ! self::is_selected( $selection, $method->instance_id ) ) {
					continue;
				}
				$line = '- ' . $zone['zone_name'] . ': ' . $method->get_title();
				if ( 'free_shipping' === $method->id ) {
					$min_amount = $method->get_option( 'min_amount' );
					if ( '' !== $min_amount ) {
						$line .= ' (' . sprintf( self::label( $lang, 'shipping_free_from' ), $min_amount ) . ')';
					}
				}
				$lines[] = $line;
			}
		}

		return $lines;
	}

	/** true si hay algun metodo seleccionado de tipo $type (free_shipping|local_pickup). */
	public static function has_shipping_method_type( $type ) {
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			return false;
		}
		$selection = Scope::settings()['wc_shipping_methods'];
		foreach ( \WC_Shipping_Zones::get_zones() as $zone ) {
			foreach ( (array) $zone['shipping_methods'] as $method ) {
				if ( 'no' === $method->enabled || ! self::is_selected( $selection, $method->instance_id ) ) {
					continue;
				}
				if ( $type === $method->id ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Pasarelas de pago realmente habilitadas (WC()->payment_gateways()
	 * expone TODAS las instaladas; se filtra por enabled === 'yes').
	 */
	/**
	 * Fase 2: publica (no protected) para que la pestaña WooCommerce
	 * (admin/views/tab-woocommerce.php) reutilice exactamente el mismo dato
	 * en su sección "Detectado automáticamente" sin duplicar la lógica.
	 */
	public static function payment_summary() {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return array();
		}

		$selection = Scope::settings()['wc_payment_methods'];
		$lines     = array();
		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			if ( 'yes' !== $gateway->enabled ) {
				continue;
			}
			if ( ! self::is_selected( $selection, $gateway->id ) ) {
				continue;
			}
			$lines[] = '- ' . $gateway->get_title();
		}

		return $lines;
	}

	/**
	 * Fase 2: tipos de impuesto/IVA realmente configurados en WooCommerce
	 * (Ajustes > Impuestos), uno por clase fiscal (Standard + clases
	 * personalizadas). NO usa WC_Tax::get_rates(): esa función calcula los
	 * tipos que aplicarían a un pedido/cliente concreto según su ubicación
	 * (pensada para el checkout), no para listar "lo que hay configurado" --
	 * el mismo patrón que usa la propia pantalla de ajustes de impuestos de
	 * WooCommerce es WC_Tax::get_rates_for_tax_class() por cada clase fiscal
	 * (WC_Tax::get_tax_rate_classes()), así que se reutiliza ese.
	 */
	public static function tax_summary_lines( $lang ) {
		if ( ! class_exists( 'WC_Tax' ) ) {
			return array();
		}

		$classes = array( (object) array( 'slug' => '', 'name' => 'Standard' ) );
		foreach ( \WC_Tax::get_tax_rate_classes() as $class_obj ) {
			$classes[] = $class_obj;
		}

		$selection = Scope::settings()['wc_tax_rates'];
		$lines     = array();
		foreach ( $classes as $class_obj ) {
			$rates = \WC_Tax::get_rates_for_tax_class( $class_obj->slug );
			if ( empty( $rates ) || is_wp_error( $rates ) ) {
				continue;
			}
			foreach ( $rates as $rate ) {
				if ( ! self::is_selected( $selection, (int) $rate->tax_rate_id ) ) {
					continue;
				}
				$country = ! empty( $rate->tax_rate_country ) ? $rate->tax_rate_country : self::label( $lang, 'tax_all_countries' );
				$percent = rtrim( rtrim( number_format( (float) $rate->tax_rate, 4, '.', '' ), '0' ), '.' );
				$line    = '- ' . $class_obj->name . ' (' . $country . '): ' . $percent . '%';
				if ( ! empty( $rate->tax_rate_shipping ) && 'yes' === $rate->tax_rate_shipping ) {
					$line .= ' · ' . self::label( $lang, 'tax_applies_shipping' );
				}
				$lines[] = $line;
			}
		}

		return $lines;
	}

	protected static function translated_url( $post_id, $lang ) {
		$translated_id = Wpml::get_translation_id( $post_id, $lang );
		$translated_id = $translated_id ? $translated_id : $post_id;
		return get_permalink( $translated_id );
	}

	/**
	 * URL "ancla" del documento compuesto: no existe un post propio, así que
	 * se enlaza a la página más representativa del contenido (checkout/cart o
	 * la home) para que el enlace de llms.txt lleve a algo navegable real.
	 */
	protected static function url_store_info( $lang ) {
		$checkout_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'checkout' ) : 0;
		if ( $checkout_id > 0 ) {
			return self::translated_url( $checkout_id, $lang );
		}
		return home_url( '/' );
	}

	protected static function url_shop_catalog( $lang ) {
		$shop_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
		if ( $shop_id > 0 ) {
			return self::translated_url( $shop_id, $lang );
		}
		return home_url( '/' );
	}

	public static function title_store_info( $lang ) {
		return self::label( $lang, 'title_store_info' );
	}

	public static function title_shop_catalog( $lang ) {
		return self::label( $lang, 'title_shop_catalog' );
	}

	/**
	 * Título/descripción legibles para el link de llms.txt cuando el
	 * source_type es uno de los nuestros (Llms_Txt no puede usar
	 * get_the_title()/get_the_excerpt() porque no hay post real detrás).
	 */
	public static function title_for( $source_type, $lang ) {
		if ( self::SOURCE_TYPE_STORE_INFO === $source_type ) {
			return self::title_store_info( $lang );
		}
		if ( self::SOURCE_TYPE_SHOP_CATALOG === $source_type ) {
			return self::title_shop_catalog( $lang );
		}
		return '';
	}

	public static function description_for( $source_type, $lang ) {
		if ( self::SOURCE_TYPE_STORE_INFO === $source_type ) {
			return self::label( $lang, 'desc_store_info' );
		}
		if ( self::SOURCE_TYPE_SHOP_CATALOG === $source_type ) {
			return self::label( $lang, 'desc_shop_catalog' );
		}
		return '';
	}

	/**
	 * Etiqueta de categoría (agrupación en llms.txt) para estos source_type.
	 */
	public static function category_label_for( $source_type ) {
		if ( self::SOURCE_TYPE_STORE_INFO === $source_type ) {
			return 'Información de la tienda';
		}
		if ( self::SOURCE_TYPE_SHOP_CATALOG === $source_type ) {
			return 'Catálogo de productos';
		}
		return '';
	}

	/**
	 * Textos fijos en ES/EN/DE. Contenido corto y estructural (títulos,
	 * cabeceras de sección), no redactado por IA por la misma razón que el
	 * resto del documento: es texto de referencia, no marketing, y debe ser
	 * estable entre regeneraciones. Cae a español si el idioma no está aquí.
	 */
	protected static function label( $lang, $key ) {
		$labels = array(
			'es' => array(
				'intro'                   => 'Información sobre cómo comprar en esta tienda online, condiciones de venta, envío y pago.',
				'heading_process'         => 'Proceso de compra',
				'process_body'            => 'Los productos se añaden al carrito y la compra se completa en la página de pago (checkout), donde se introducen los datos de envío y se elige el método de pago.',
				'label_cart'              => 'Carrito',
				'label_checkout'          => 'Finalizar compra',
				'label_account'           => 'Mi cuenta',
				'heading_terms'           => 'Condiciones de venta',
				'terms_link'              => 'Condiciones completas',
				'terms_pending'           => '[pendiente] La página de condiciones de venta está configurada pero no se pudo leer su contenido.',
				'terms_not_configured'    => '[pendiente] Esta tienda todavía no tiene configurada una página de condiciones de venta en WooCommerce (Ajustes > Cuentas y privacidad).',
				'heading_returns'         => 'Política de devoluciones',
				'returns_link'            => 'Política completa',
				'returns_pending'         => '[pendiente] La página de devoluciones y reembolsos está configurada pero no se pudo leer su contenido.',
				'returns_not_configured'  => '[pendiente] Esta tienda todavía no tiene configurada una página de devoluciones y reembolsos en WooCommerce (Ajustes > Cuentas y privacidad).',
				'heading_shipping'        => 'Envíos',
				'shipping_not_configured' => '[pendiente] No hay métodos de envío seleccionados todavía (pestaña WooCommerce).',
				'shipping_free_from'      => 'gratis a partir de %s',
				'pickup_available_manual' => 'Recogida en tienda disponible.',
				'heading_delivery_times'  => 'Plazos de entrega',
				'delivery_times_pending'  => '[pendiente] No hay un plazo de entrega en texto confirmado por el negocio todavía (los métodos de envío configurados no incluyen una estimación de tiempo).',
				'heading_contact'         => 'Contacto y horario',
				'heading_payment'         => 'Métodos de pago',
				'payment_not_configured'  => '[pendiente] No hay pasarelas de pago habilitadas todavía.',
				'heading_tax'             => 'Impuestos / IVA',
				'tax_disabled'            => '[pendiente] Esta tienda tiene los impuestos desactivados en WooCommerce (Ajustes > Impuestos).',
				'tax_not_configured'      => '[pendiente] Los impuestos están activados en WooCommerce pero todavía no hay ningún tipo dado de alta (Ajustes > Impuestos).',
				'tax_prices_include'      => 'Los precios mostrados en la tienda ya incluyen impuestos.',
				'tax_prices_exclude'      => 'Los precios mostrados en la tienda NO incluyen impuestos; se añaden en el proceso de pago.',
				'tax_all_countries'       => 'Todos los países',
				'tax_applies_shipping'    => 'aplica también al envío',
				'heading_legal_notes'     => 'Notas legales adicionales',
				'label_currency'          => 'Moneda',
				'title_store_info'        => 'Cómo comprar, condiciones, envío y pago',
				'desc_store_info'         => 'Proceso de compra, condiciones de venta, métodos de envío y de pago de la tienda.',
				'shop_intro'              => 'Qué se puede hacer en la página de tienda y qué productos hay disponibles.',
				'shop_url_label'          => 'Página de tienda',
				'heading_browsing'        => 'Navegación por la tienda',
				'browsing_body'           => 'La página de tienda permite filtrar y navegar los productos por categoría. Cada categoría agrupa productos relacionados.',
				'heading_catalog'         => 'Catálogo por categoría',
				'catalog_empty'           => '[pendiente] No hay categorías de producto con productos publicados todavía.',
				'title_shop_catalog'      => 'Catálogo de la tienda',
				'desc_shop_catalog'       => 'Listado de productos disponibles agrupados por categoría, y cómo navegar la tienda.',
			),
			'en' => array(
				'intro'                   => 'Information on how to buy in this online store, terms of sale, shipping and payment.',
				'heading_process'         => 'Purchase process',
				'process_body'            => 'Products are added to the cart and the purchase is completed on the checkout page, where shipping details are entered and a payment method is chosen.',
				'label_cart'              => 'Cart',
				'label_checkout'          => 'Checkout',
				'label_account'           => 'My account',
				'heading_terms'           => 'Terms of sale',
				'terms_link'              => 'Full terms',
				'terms_pending'           => '[pending] The terms and conditions page is configured but its content could not be read.',
				'terms_not_configured'    => '[pending] This store does not have a terms and conditions page configured in WooCommerce yet (Settings > Accounts & Privacy).',
				'heading_returns'         => 'Returns policy',
				'returns_link'            => 'Full policy',
				'returns_pending'         => '[pending] The returns and refunds page is configured but its content could not be read.',
				'returns_not_configured'  => '[pending] This store does not have a returns and refunds page configured in WooCommerce yet (Settings > Accounts & Privacy).',
				'heading_shipping'        => 'Shipping',
				'shipping_not_configured' => '[pending] No shipping methods selected yet (WooCommerce tab).',
				'shipping_free_from'      => 'free from %s',
				'pickup_available_manual' => 'In-store pickup available.',
				'heading_delivery_times'  => 'Delivery times',
				'delivery_times_pending'  => '[pending] There is no delivery time confirmed by the business in free text yet (the configured shipping methods do not include a time estimate).',
				'heading_contact'         => 'Contact and opening hours',
				'heading_payment'         => 'Payment methods',
				'payment_not_configured'  => '[pending] No payment gateways are enabled yet.',
				'heading_tax'             => 'Tax / VAT',
				'tax_disabled'            => '[pending] This store has taxes disabled in WooCommerce (Settings > Tax).',
				'tax_not_configured'      => '[pending] Taxes are enabled in WooCommerce but no tax rate has been set up yet (Settings > Tax).',
				'tax_prices_include'      => 'Prices shown in the store already include tax.',
				'tax_prices_exclude'      => 'Prices shown in the store do NOT include tax; it is added at checkout.',
				'tax_all_countries'       => 'All countries',
				'tax_applies_shipping'    => 'also applies to shipping',
				'heading_legal_notes'     => 'Additional legal notes',
				'label_currency'          => 'Currency',
				'title_store_info'        => 'How to buy, terms, shipping and payment',
				'desc_store_info'         => 'Purchase process, terms of sale, shipping and payment methods of the store.',
				'shop_intro'              => 'What you can do on the shop page and which products are available.',
				'shop_url_label'          => 'Shop page',
				'heading_browsing'        => 'Browsing the shop',
				'browsing_body'           => 'The shop page lets you filter and browse products by category. Each category groups related products.',
				'heading_catalog'         => 'Catalog by category',
				'catalog_empty'           => '[pending] No product categories with published products yet.',
				'title_shop_catalog'      => 'Shop catalog',
				'desc_shop_catalog'       => 'List of available products grouped by category, and how to browse the shop.',
			),
			'de' => array(
				'intro'                   => 'Informationen zum Einkaufen in diesem Online-Shop, Verkaufsbedingungen, Versand und Zahlung.',
				'heading_process'         => 'Kaufvorgang',
				'process_body'            => 'Produkte werden dem Warenkorb hinzugefügt und der Kauf wird auf der Checkout-Seite abgeschlossen, wo Versanddaten eingegeben und eine Zahlungsmethode gewählt werden.',
				'label_cart'              => 'Warenkorb',
				'label_checkout'          => 'Kasse',
				'label_account'           => 'Mein Konto',
				'heading_terms'           => 'Verkaufsbedingungen',
				'terms_link'              => 'Vollständige Bedingungen',
				'terms_pending'           => '[ausstehend] Die Seite mit den Geschäftsbedingungen ist konfiguriert, ihr Inhalt konnte aber nicht gelesen werden.',
				'terms_not_configured'    => '[ausstehend] Für diesen Shop ist noch keine Seite mit Geschäftsbedingungen in WooCommerce konfiguriert (Einstellungen > Konten & Datenschutz).',
				'heading_returns'         => 'Rückgaberichtlinie',
				'returns_link'            => 'Vollständige Richtlinie',
				'returns_pending'         => '[ausstehend] Die Seite zu Rückgabe und Erstattung ist konfiguriert, ihr Inhalt konnte aber nicht gelesen werden.',
				'returns_not_configured'  => '[ausstehend] Für diesen Shop ist noch keine Seite zu Rückgabe und Erstattung in WooCommerce konfiguriert (Einstellungen > Konten & Datenschutz).',
				'heading_shipping'        => 'Versand',
				'shipping_not_configured' => '[ausstehend] Noch keine Versandmethoden ausgewählt (Reiter WooCommerce).',
				'shipping_free_from'      => 'kostenlos ab %s',
				'pickup_available_manual' => 'Abholung im Laden möglich.',
				'heading_delivery_times'  => 'Lieferzeiten',
				'delivery_times_pending'  => '[ausstehend] Es gibt noch keine vom Unternehmen bestätigte Lieferzeit als Freitext (die konfigurierten Versandmethoden enthalten keine Zeitschätzung).',
				'heading_contact'         => 'Kontakt und Öffnungszeiten',
				'heading_payment'         => 'Zahlungsmethoden',
				'payment_not_configured'  => '[ausstehend] Es sind noch keine Zahlungsmethoden aktiviert.',
				'heading_tax'             => 'Steuern / MwSt.',
				'tax_disabled'            => '[ausstehend] Dieser Shop hat Steuern in WooCommerce deaktiviert (Einstellungen > Steuern).',
				'tax_not_configured'      => '[ausstehend] Steuern sind in WooCommerce aktiviert, aber es ist noch kein Steuersatz angelegt (Einstellungen > Steuern).',
				'tax_prices_include'      => 'Die im Shop angezeigten Preise enthalten bereits die Steuer.',
				'tax_prices_exclude'      => 'Die im Shop angezeigten Preise enthalten KEINE Steuer; sie wird beim Checkout hinzugefügt.',
				'tax_all_countries'       => 'Alle Länder',
				'tax_applies_shipping'    => 'gilt auch für den Versand',
				'heading_legal_notes'     => 'Zusätzliche rechtliche Hinweise',
				'label_currency'          => 'Währung',
				'title_store_info'        => 'Einkauf, Bedingungen, Versand und Zahlung',
				'desc_store_info'         => 'Kaufvorgang, Verkaufsbedingungen, Versand- und Zahlungsmethoden des Shops.',
				'shop_intro'              => 'Was auf der Shop-Seite möglich ist und welche Produkte verfügbar sind.',
				'shop_url_label'          => 'Shop-Seite',
				'heading_browsing'        => 'Im Shop stöbern',
				'browsing_body'           => 'Auf der Shop-Seite können Produkte nach Kategorie gefiltert und durchsucht werden. Jede Kategorie fasst verwandte Produkte zusammen.',
				'heading_catalog'         => 'Katalog nach Kategorie',
				'catalog_empty'           => '[ausstehend] Noch keine Produktkategorien mit veröffentlichten Produkten.',
				'title_shop_catalog'      => 'Shop-Katalog',
				'desc_shop_catalog'       => 'Liste der verfügbaren Produkte nach Kategorie sowie Hinweise zum Stöbern im Shop.',
			),
		);

		$set = isset( $labels[ $lang ] ) ? $labels[ $lang ] : $labels['es'];
		return isset( $set[ $key ] ) ? $set[ $key ] : ( isset( $labels['es'][ $key ] ) ? $labels['es'][ $key ] : '' );
	}

	/**
	 * Corta el cuerpo al límite de caracteres por el último salto de línea
	 * completo que quepa (nunca a mitad de frase), igual criterio que
	 * Generator::enforce_body_char_limit() pero duplicado aquí porque ese
	 * método es protected de otra clase y este documento no pasa por IA (no
	 * tiene sentido compartir la llamada a call_openai() de Generator). Si en
	 * el futuro hay un tercer sitio que necesite este recorte, se debería
	 * extraer a un trait/helper común.
	 */
	protected static function enforce_char_limit( $body ) {
		if ( mb_strlen( $body ) <= self::CHAR_LIMIT ) {
			return $body;
		}

		$body_lines = explode( "\n", $body );
		$kept       = array();
		$len        = 0;
		foreach ( $body_lines as $line ) {
			$line_len = mb_strlen( $line ) + 1;
			if ( $len + $line_len > self::CHAR_LIMIT && ! empty( $kept ) ) {
				break;
			}
			$kept[] = $line;
			$len   += $line_len;
		}

		return implode( "\n", $kept );
	}
}
