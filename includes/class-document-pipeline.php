<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orquesta un ciclo completo: extraer -> comprobar hash -> generar (o puente) -> .md -> sgkb-docs -> registro -> llms.txt.
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

		// Traducción real en ese idioma, si existe.
		$translated_id = Wpml::get_translation_id( $source_id, $lang );
		$is_bridge      = false;
		$data           = null;

		if ( $translated_id ) {
			$data = $extractor->extract( $translated_id );
		}

		if ( ! $data ) {
			// No hay traducción -> documento puente factual con enlaces es/en.
			$is_bridge = true;
			$data      = $extractor->extract( $source_id );
			if ( ! $data ) {
				return new \WP_Error( 'wookb_no_source', __( 'No se pudo extraer el origen.', 'ai-knowledge' ) );
			}
		}

		$hash = self::compute_hash( $data );

		$existing = Registry::find( $translated_id ? $translated_id : $source_id, $lang );

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
					'stale'     => ( $existing->source_hash === $hash ) ? 0 : 1,
				)
			);
			return true;
		}

		if ( ! $force && $existing && $existing->source_hash === $hash && 'synced' === $existing->status ) {
			// Nada cambió: no se llama a la IA (segunda capa de ahorro). $force
			// salta esta comprobacion a proposito para regeneraciones manuales.
			return true;
		}

		$real_source_id = $translated_id ? $translated_id : $source_id;

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

		// Enlaces a las versiones de este mismo producto en los demas idiomas activos
		// (si existen). Se usan tanto en el documento puente como en el flujo normal,
		// para que un visitante que aterrice en el documento de un idioma pueda
		// encontrar las fichas reales de los otros idiomas.
		$cross_language_links = self::build_cross_language_links( $source_id, $lang );

		// Pieza 2: prompt propio de esta fila, si el admin escribio uno. isset()
		// por seguridad: la columna custom_prompt puede no existir aun en la
		// tabla si el sitio no ha pasado por la migracion de version que la
		// añade (dbDelta via wookb_db_version, ver ai-knowledge.php).
		$custom_prompt = ( $existing && isset( $existing->custom_prompt ) ) ? $existing->custom_prompt : '';

		if ( $is_bridge ) {
			$markdown = Generator::build_bridge_markdown( $data, $cross_language_links );
		} else {
			$markdown = Generator::generate( $data, $cross_language_links, $char_limit, $custom_prompt );
			if ( is_wp_error( $markdown ) ) {
				Registry::update_status(
					Registry::find( $real_source_id, $lang )->id,
					'error',
					array( 'last_error' => $markdown->get_error_message() )
				);
				return $markdown;
			}
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
				'bridge'       => $is_bridge,
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
			$product_trid = Wpml::get_trid( $real_source_id );
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
				$doc_trid = Wpml::get_trid( $doc_post_id );
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
				'is_bridge'    => $is_bridge ? 1 : 0,
				'product_trid' => $product_trid,
				'doc_trid'     => $doc_trid,
				'generated_at' => current_time( 'mysql' ),
				'last_error'   => null,
			)
		);

		Llms_Txt::invalidate();

		return true;
	}

	/**
	 * Enlaces a las traducciones reales de $source_id en los demas idiomas activos
	 * (excluyendo $current_lang). Usado tanto por el documento puente como por el
	 * flujo normal, para que cualquier documento generado enlace a las versiones
	 * del mismo producto en otros idiomas cuando existan.
	 */
	protected static function build_cross_language_links( $source_id, $current_lang ) {
		$links = array();
		foreach ( Wpml::active_languages() as $target_lang ) {
			if ( $target_lang === $current_lang ) {
				continue;
			}
			$id = Wpml::get_translation_id( $source_id, $target_lang );
			if ( $id ) {
				$links[ $target_lang ] = get_permalink( $id );
			}
		}
		return $links;
	}

	protected static function compute_hash( array $data ) {
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
		return hash( 'sha256', implode( '|', $normalized ) );
	}
}
