<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orquesta un ciclo completo: extraer -> comprobar hash -> generar -> .md -> sgkb-docs -> registro -> llms.txt.
 */
class Document_Pipeline {

	/**
	 * Procesa un documento (source_id, lang). Devuelve true|WP_Error.
	 *
	 * $char_limit: tope de caracteres del cuerpo para esta generacion concreta
	 * (null = usar Generator::BODY_CHAR_LIMIT). Solo tiene efecto en el flujo
	 * normal con IA; el documento puente no lo usa (no llama a Generator::generate()).
	 *
	 * $force: si es true, se salta la comprobacion de hash de mas abajo y
	 * regenera aunque el contenido de origen no haya cambiado. Necesario para
	 * las acciones manuales del admin (boton "Generar" de una fila, "Regenerar
	 * seleccionados" en bloque): sin esto, pedir una regeneracion explicita de
	 * un producto que no ha cambiado no hacia nada -- el hash coincidia con el
	 * ya guardado y el metodo devolvia true sin llamar a la IA ni tocar nada
	 * (bug real confirmado por el usuario: el boton "Generar" no cambiaba
	 * fecha ni contenido con ningun valor de limite).
	 */
	public static function process( $source_id, $lang, $char_limit = null, $force = false ) {
		$post_type = get_post_type( $source_id );
		$extractor = Extractors\Extractor_Base::for_post_type( $post_type );

		// Versión real del contenido en ese idioma. Sin ella no se genera nada:
		// ya no hay documentos puente (solo se crean .md para lo que existe).
		$translated_id = Languages::translation_id( $source_id, $lang );
		if ( ! $translated_id ) {
			return new \WP_Error( 'wookb_no_translation', __( 'Este contenido no tiene versión en ese idioma.', 'ai-knowledge' ) );
		}

		// Sin "Crear por idioma", una traduccion no tiene documento propio: el
		// documento es el del original (todas las traducciones lo enlazan). Cubre
		// trabajos ya en cola o filas antiguas de antes de cambiar el ajuste; lo
		// que sobra lo borra "Reiniciar todo" (Languages::cleanup_obsolete_documents()).
		$target = Languages::document_target( $translated_id );
		if ( (int) $target['id'] !== (int) $translated_id ) {
			return new \WP_Error( 'wookb_translation_no_own_doc', __( 'Esta traducción no tiene documento propio: su contenido va en el .md del original (opción «Crear por idioma» desmarcada).', 'ai-knowledge' ) );
		}

		$data = $extractor->extract( $translated_id );
		if ( ! $data ) {
			return new \WP_Error( 'wookb_no_source', __( 'No se pudo extraer el origen.', 'ai-knowledge' ) );
		}

		// Versiones del contenido en cada idioma (idioma => URL): alimentan el
		// apartado "Idiomas" del .md y, si hay más de una, el hash (para que
		// añadir o quitar una traducción regenere el apartado).
		$versions = Languages::versions( $translated_id );

		$hash = self::compute_hash( $data, $versions );

		$existing = Registry::find( $translated_id, $lang );

		// Modo manual (Fase 1): el texto lo fija el admin a mano, nunca se
		// regenera con IA. Solo se comprueba si el origen cambio desde que se
		// fijo el modo manual (marcando 'stale'), sin tocar override_text ni
		// el .md ya escrito. Esta comprobacion es incondicional (no depende de
		// $force): $force existe para el flujo automatico, no para saltarse el
		// modo manual -- "Volver a Auto" cambia override_mode a 'auto' en BD
		// ANTES de volver a llamar a process(), asi que ese caso no pasa por aqui.
		if ( $existing && 'manual' === $existing->override_mode ) {
			Registry::upsert(
				array(
					'source_id' => $existing->source_id,
					'lang'      => $existing->lang,
					// Igual con el hash sin versiones: un texto manual fijado antes
					// de existir el apartado "Idiomas" no debe marcarse como
					// obsoleto solo por eso.
					'stale'     => ( $existing->source_hash === $hash || $existing->source_hash === self::compute_hash( $data ) ) ? 0 : 1,
				)
			);
			return true;
		}

		if ( ! $force && $existing && $existing->source_hash === $hash && 'synced' === $existing->status ) {
			// Nada cambió: no se llama a la IA (segunda capa de ahorro). $force
			// salta esta comprobacion a proposito para regeneraciones manuales.
			return true;
		}

		$real_source_id = $translated_id;

		// char_limit propio de la fila (Fase 1): si no se paso uno explicito
		// para esta llamada, usa el guardado en la fila; si tampoco hay,
		// Generator cae a BODY_CHAR_LIMIT (ver Generator::resolve_char_limit()).
		if ( null === $char_limit && $existing && ! empty( $existing->char_limit ) ) {
			$char_limit = (int) $existing->char_limit;
		}

		Registry::update_status(
			$existing ? $existing->id : Registry::upsert(
				array(
					'source_id'   => $real_source_id,
					'source_type' => $post_type,
					'lang'        => $lang,
					'source_hash' => $hash,
					'status'      => 'generating',
				)
			),
			'generating'
		);

		// Pieza 2: prompt propio de esta fila, si el admin escribio uno. isset()
		// por seguridad: la columna custom_prompt puede no existir aun en la
		// tabla si el sitio no ha pasado por la migracion de version que la
		// añade (dbDelta via wookb_db_version, ver ai-knowledge.php).
		$custom_prompt = ( $existing && isset( $existing->custom_prompt ) ) ? $existing->custom_prompt : '';

		$markdown = Generator::generate( $data, $versions, $char_limit, $custom_prompt );
		if ( is_wp_error( $markdown ) ) {
			Registry::update_status(
				Registry::find( $real_source_id, $lang )->id,
				'error',
				array( 'last_error' => $markdown->get_error_message() )
			);
			return $markdown;
		}

		$slug = Markdown_Store::slug_for( $real_source_id, $lang );
		$relative = Markdown_Store::write(
			$lang,
			$slug,
			$markdown,
			array(
				'source_id'    => $real_source_id,
				'source_type'  => $post_type,
				'lang'         => $lang,
				'source_hash'  => $hash,
				'generated_at' => current_time( 'mysql' ),
				'product_url'  => $data['url'],
				'bridge'       => false,
			)
		);

		$doc_post_id  = null;
		$product_trid = null;
		$doc_trid     = null;
		if ( Genix_Bridge::is_available() ) {
			// BUG WPML resuelto: los documentos sgkb-docs NO pueden reutilizar el trid
			// del producto de origen. Si el idioma del documento coincide con el del
			// producto, ese trid ya tiene ese idioma "ocupado" por el elemento
			// 'post_product' y WPML rechaza en silencio el registro del documento
			// (wpml_set_element_language_details no devuelve error; simplemente el
			// documento queda sin fila en icl_translations y el chatbot lo excluye
			// de cualquier busqueda por idioma). Los documentos necesitan su PROPIO
			// grupo de traduccion, independiente del trid del producto.
			$product_trid = Languages::trid( $real_source_id );
			$doc_trid     = Registry::find_doc_trid_by_product_trid( $product_trid );

			$existing_row = Registry::find( $real_source_id, $lang );
			$result = Genix_Bridge::upsert_document(
				$existing_row ? $existing_row->doc_post_id : null,
				$data,
				$markdown,
				$lang,
				$doc_trid // null en el primer documento del grupo: WPML autogenera un trid propio para 'post_sgkb-docs'.
			);
			if ( is_wp_error( $result ) ) {
				Registry::update_status(
					$existing_row->id,
					'error',
					array( 'last_error' => $result->get_error_message() )
				);
				return $result;
			}
			$doc_post_id = $result;

			if ( ! $doc_trid ) {
				// Primer documento del grupo: recupera el trid que WPML acaba de asignar
				// para que los siguientes idiomas del mismo producto lo reutilicen.
				$doc_trid = Languages::trid( $doc_post_id );
			}
		}

		$row_id = Registry::upsert(
			array(
				'source_id'    => $real_source_id,
				'source_type'  => $post_type,
				'lang'         => $lang,
				'md_path'      => $relative,
				'doc_post_id'  => $doc_post_id,
				'source_hash'  => $hash,
				'status'       => 'synced',
				'is_bridge'    => 0,
				'product_trid' => $product_trid,
				'doc_trid'     => $doc_trid,
				'generated_at' => current_time( 'mysql' ),
				'last_error'   => null,
			)
		);

		Llms_Txt::invalidate();

		return true;
	}

	protected static function compute_hash( array $data, array $versions = array() ) {
		$normalized = array(
			$data['title'],
			$data['content'],
			isset( $data['price'] ) ? $data['price'] : '',
			isset( $data['stock'] ) ? $data['stock'] : '',
			isset( $data['variants'] ) ? wp_json_encode( $data['variants'] ) : '',
			isset( $data['taxonomies'] ) ? wp_json_encode( $data['taxonomies'] ) : '',
			isset( $data['custom_fields'] ) ? wp_json_encode( $data['custom_fields'] ) : '',
			$data['url'],
		);

		// Datos de compra (solo productos WooCommerce, bloque "Datos de
		// compra" del documento): envio, impuestos, fecha de oferta, variaciones,
		// SKU... Se anaden SOLO si el documento los tiene: un elemento extra
		// vacio cambiaria igualmente el implode() y con ello el hash de TODAS las
		// paginas/entradas, forzando una regeneracion con IA que no hace falta.
		// Sin cantidades de stock (ver purchase_hash_subset()).
		if ( isset( $data['purchase'] ) && is_array( $data['purchase'] ) ) {
			$normalized[] = wp_json_encode( self::purchase_hash_subset( $data['purchase'] ) );
		}

		// Versiones por idioma (apartado "Idiomas" del .md): solo si hay mas de
		// una, por la misma razon que "Datos de compra" -- un elemento extra en
		// sitios de un solo idioma cambiaria el hash de TODOS los documentos y
		// forzaria una regeneracion con IA que no hace falta.
		if ( count( $versions ) > 1 ) {
			$normalized[] = wp_json_encode( $versions );
		}

		return hash( 'sha256', implode( '|', $normalized ) );
	}

	/**
	 * Parte estable de $data['purchase'] para el hash: todo salvo las cantidades
	 * de stock (del producto y de cada variacion). Cada venta cambia esa
	 * cantidad, y si entrara en el hash cada compra regeneraria el documento
	 * con una llamada de IA; el estado (en stock/agotado) si entra.
	 */
	protected static function purchase_hash_subset( array $purchase ) {
		unset( $purchase['stock_quantity'] );
		if ( ! empty( $purchase['variations'] ) && is_array( $purchase['variations'] ) ) {
			foreach ( $purchase['variations'] as $i => $variation ) {
				if ( is_array( $variation ) ) {
					unset( $purchase['variations'][ $i ]['stock_quantity'] );
				}
			}
		}
		return $purchase;
	}
}
