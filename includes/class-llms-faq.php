<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contenido de la sección "## Preguntas frecuentes" de /llms.txt.
 *
 * Se guarda en un archivo propio (llms-faq.md, en la raíz del plugin, mismo
 * sitio donde vive chatbot-system-prompt.md) en vez de reutilizar ese mismo
 * archivo, porque el propósito y el destino son opuestos:
 * - chatbot-system-prompt.md es el prompt de SISTEMA del chatbot: se envía
 *   al modelo de IA en cada conversación, nunca se muestra públicamente
 *   (Chatbot_Prompt::sync() lo escribe en un ajuste interno de Support Genix).
 * - llms-faq.md es contenido PÚBLICO pensado para que lo lean crawlers de
 *   IA/LLM directamente desde /llms.txt (Llms_Txt::build()).
 * Mezclarlos en el mismo archivo obligaría a elegir entre exponer
 * instrucciones internas del chatbot en una URL pública, o filtrar el FAQ
 * fuera de lo que recibe el modelo -- ninguna opción es aceptable. Un
 * archivo por cada uno mantiene la responsabilidad y el nivel de exposición
 * separados, igual que el sistema ya separa Markdown_Store (documentos
 * públicos) de wp-content/llm/info.md (también público, pero con su propio
 * archivo por la misma razón de responsabilidad única).
 *
 * Formato: Markdown libre escrito por el administrador del sitio (igual que
 * el campo "Instrucciones adicionales" de Ajustes), sin parseo estructurado
 * de preguntas/respuestas: se inserta tal cual bajo el encabezado "##
 * Preguntas frecuentes" de llms.txt. Sin límite de caracteres forzado (es
 * contenido editorial y estático, no compite por relevancia de búsqueda
 * como las fichas de producto -- ver Generator::BODY_CHAR_LIMIT).
 */
class Llms_Faq {

	// Documento compuesto (mismo patron que Store_Info_Doc): sin post real
	// detras, source_id centinela. 900000003 -- Store_Info_Doc ya usa
	// 900000001/900000002, sin colision.
	const SOURCE_TYPE = 'wookb-faq';
	const SOURCE_ID   = 900000003;

	/**
	 * Origen editable (no publico) por idioma: guardado del textarea de la
	 * pestaña FAQs, distinto del .md PUBLICO generado (persist_doc(), mismo
	 * directorio pero nombre "preguntas-frecuentes.md"). Vive en
	 * wp-content/llm/{lang}/ junto al resto de contenido del plugin, en vez
	 * de suelto en la carpeta del plugin -- consolidado a partir del
	 * 2026-09-16. Ver read() para la migracion automatica de las 2 rutas
	 * antiguas (mono-idioma sin lang, y la intermedia con lang en la raiz
	 * del plugin).
	 */
	public static function file_path( $lang ) {
		return Markdown_Store::base_dir() . '/' . sanitize_key( $lang ) . '/faq-fuente.md';
	}

	/** Ruta intermedia (con idioma, pero aun en la raiz del plugin), solo para migrar. */
	protected static function legacy_file_path( $lang ) {
		return AIKB_DIR . 'llms-faq-' . sanitize_key( $lang ) . '.md';
	}

	/** Ruta original (mono-idioma, sin lang, en la raiz del plugin), solo para migrar. */
	protected static function legacy_global_file_path() {
		return AIKB_DIR . 'llms-faq.md';
	}

	public static function read( $lang ) {
		$path = self::file_path( $lang );
		if ( file_exists( $path ) ) {
			return trim( (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		}

		// Migracion lazy en cadena: primero la ruta intermedia (mismo idioma,
		// raiz del plugin), luego la original mono-idioma (solo para el
		// primer idioma activo). Se lee tal cual, sin borrar los archivos
		// viejos por si acaso, hasta el proximo save() en la ruta nueva.
		if ( file_exists( self::legacy_file_path( $lang ) ) ) {
			return trim( (string) file_get_contents( self::legacy_file_path( $lang ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		}

		$langs = Wpml::active_languages();
		if ( $langs && $lang === $langs[0] && file_exists( self::legacy_global_file_path() ) ) {
			return trim( (string) file_get_contents( self::legacy_global_file_path() ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		}

		return '';
	}

	/**
	 * Guarda el contenido y invalida la caché de llms.txt para que el cambio
	 * se vea en la siguiente petición (mismo patrón que Chatbot_Prompt_Builder::write_info_doc()).
	 */
	public static function save( $content, $lang ) {
		$content = trim( (string) $content );
		wp_mkdir_p( dirname( self::file_path( $lang ) ) );
		file_put_contents( self::file_path( $lang ), $content . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_put_contents

		if ( class_exists( '\AIKB\Llms_Txt' ) ) {
			Llms_Txt::invalidate();
		}

		return $content;
	}

	public static function registry_row( $lang ) {
		return Registry::find( self::SOURCE_ID, $lang );
	}

	public static function title() {
		return __( 'Preguntas frecuentes', 'ai-knowledge' );
	}

	public static function description() {
		return __( 'Preguntas frecuentes públicas del negocio.', 'ai-knowledge' );
	}

	/** Categoria fija en llms.txt, mismo patron que Store_Info_Doc::category_label_for(). */
	public static function category_label_for( $source_type ) {
		return self::SOURCE_TYPE === $source_type ? __( 'FAQ', 'ai-knowledge' ) : '';
	}

	/**
	 * Genera (o borra si está vacío) el .md público + fila de Registry del
	 * FAQ de un idioma, mismo patrón que Store_Info_Doc::persist(): así
	 * aparece en la pestaña Registro y se enlaza desde llms.txt como el
	 * resto de documentos (no como bloque inline aparte). Llamar tras save().
	 */
	public static function persist_doc( $lang ) {
		$content  = self::read( $lang );
		$existing = Registry::find( self::SOURCE_ID, $lang );

		if ( '' === $content ) {
			// Sin contenido en ESTE idioma: borra solo su fila/documento, no
			// los de otros idiomas (Sync::delete_documents_for() borraria
			// TODOS los idiomas del mismo source_id -- no vale aqui).
			if ( $existing ) {
				Markdown_Store::delete( $existing->md_path );
				if ( $existing->doc_post_id && class_exists( '\AIKB\Genix_Bridge' ) ) {
					Genix_Bridge::delete_document( $existing->doc_post_id );
				}
				Registry::delete_row( $existing->id );
				if ( class_exists( '\AIKB\Llms_Txt' ) ) {
					Llms_Txt::invalidate();
				}
			}
			return;
		}

		$hash = hash( 'sha256', $content );
		if ( $existing && $existing->source_hash === $hash && 'synced' === $existing->status ) {
			return;
		}

		$title    = self::title();
		$markdown = "# {$title}\n\n" . $content;
		$relative = Markdown_Store::write(
			$lang,
			'preguntas-frecuentes',
			$markdown,
			array(
				'source_id'    => self::SOURCE_ID,
				'source_type'  => self::SOURCE_TYPE,
				'lang'         => $lang,
				'source_hash'  => $hash,
				'generated_at' => current_time( 'mysql' ),
				'bridge'       => false,
			)
		);

		$doc_post_id = $existing ? $existing->doc_post_id : null;
		if ( class_exists( '\AIKB\Genix_Bridge' ) && Genix_Bridge::is_available() ) {
			$result = Genix_Bridge::upsert_document(
				$doc_post_id,
				array( 'id' => self::SOURCE_ID, 'title' => $title, 'url' => '' ),
				$markdown,
				$lang,
				null
			);
			if ( ! is_wp_error( $result ) ) {
				$doc_post_id = $result;
			}
		}

		Registry::upsert(
			array(
				'source_id'    => self::SOURCE_ID,
				'source_type'  => self::SOURCE_TYPE,
				'lang'         => $lang,
				'md_path'      => $relative,
				'doc_post_id'  => $doc_post_id,
				'source_hash'  => $hash,
				'status'       => 'synced',
				'is_bridge'    => 0,
				'generated_at' => current_time( 'mysql' ),
				'last_error'   => null,
			)
		);
	}
}
