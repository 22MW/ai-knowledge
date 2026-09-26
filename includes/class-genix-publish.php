<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pieza 5: publica/despublica un artículo exclusivo de Genix (sgkb-docs sin
 * producto/página real detrás, ver Genix_Reader) como documento propio de la
 * base de conocimiento -- copia íntegra vía Genix_Markdown, sin IA.
 *
 * Corrección tras revisión del usuario: el criterio real de "¿puede ser
 * público?" NO es un estado manual inventado -- es el meta 'only_for_chatbot'
 * que Genix (support-genix-lite) ya escribe de verdad en el post sgkb-docs.
 * Confirmado en support-genix-lite/modules/Apbd_wps_knowledge_base.php,
 * método docs_single_templates(): si ese meta está activo, Genix MISMO trata
 * el articulo como no publico (redirige a cualquier visitante que no pueda
 * escribir docs). No tiene sentido que AI Knowledge lo publique en llms.txt
 * si ni siquiera es publico dentro de Genix.
 *
 * Simplificación (opción "b" del encargo): sin checkbox manual de "Público"
 * aparte. El propio meta decide si el articulo es siquiera candidato
 * (Genix_Reader::find_exclusive_articles() ya lo excluye del listado si
 * esta activo). La publicación real es simplemente "existe/no existe fila
 * en Registry para este articulo", controlada por los botones "Generar
 * contenido"/"Quitar" del listado -- SIN tocar
 * Registry::get_synced_public_urls() (solo mira status='synced' AND
 * is_bridge=0, y sigue sin tocarse): status siempre es 'synced' mientras la
 * fila existe, y al despublicar se borra la fila entera en vez de dejarla
 * con un status inventado.
 */
class Genix_Publish {

	const META_ONLY_FOR_CHATBOT = 'only_for_chatbot';

	/** true si Genix marca este articulo como "solo para el chatbot" (no publico dentro de Genix). */
	public static function is_chatbot_only( $post_id ) {
		return (bool) get_post_meta( $post_id, self::META_ONLY_FOR_CHATBOT, true );
	}

	/**
	 * Genera/actualiza la copia publica (Registry + .md) de un articulo
	 * exclusivo de Genix. Devuelve true|WP_Error. Rechaza explicitamente los
	 * articulos marcados 'only_for_chatbot' -- freno de seguridad ademas de
	 * la exclusion ya aplicada en Genix_Reader (por si esta funcion se llama
	 * directamente con un ID que ya no cumple el criterio, p.ej. si alguien
	 * activo ese meta en Genix DESPUES de cargar el listado en pantalla).
	 */
	public static function publish( $post_id ) {
		if ( ! post_type_exists( Genix_Reader::CPT ) ) {
			return new \WP_Error( 'wookb_genix_unavailable', __( 'Genix no está activo.', 'ai-knowledge' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post || Genix_Reader::CPT !== $post->post_type ) {
			return new \WP_Error( 'wookb_genix_not_found', __( 'No se encontró el artículo de Genix.', 'ai-knowledge' ) );
		}

		if ( self::is_chatbot_only( $post_id ) ) {
			return new \WP_Error( 'wookb_genix_chatbot_only', __( 'Este artículo está marcado en Genix como "solo para el chatbot": no es público ni siquiera dentro de Genix, así que no puede publicarse aquí.', 'ai-knowledge' ) );
		}

		$lang = Languages::post_language( $post_id );

		$markdown_body = Genix_Markdown::convert( $post->post_content );
		$markdown      = '# ' . $post->post_title . "\n\n" . $markdown_body;

		$slug     = Markdown_Store::slug_for( $post->ID, $lang );
		$relative = Markdown_Store::write(
			$lang,
			$slug,
			$markdown,
			array(
				'source_id'    => $post->ID,
				'source_type'  => Genix_Reader::CPT,
				'lang'         => $lang,
				'source_hash'  => hash( 'sha256', $post->post_content ),
				'generated_at' => current_time( 'mysql' ),
				'product_url'  => Languages::permalink( $post->ID ),
				'bridge'       => false,
			)
		);

		$previous = Registry::find( $post->ID, $lang );
		Markdown_Store::delete_if_moved( $previous ? $previous->md_path : '', $relative );

		// Registry::find()/upsert() no están filtrados por source_type (solo
		// query()/count()/query_all_ids() lo están, ver
		// Registry::apply_registry_scope()), así que esta escritura SI
		// encuentra/actualiza una fila previa de este mismo artículo.
		Registry::upsert(
			array(
				'source_id'    => $post->ID,
				'source_type'  => Genix_Reader::CPT,
				'lang'         => $lang,
				'md_path'      => $relative,
				'source_hash'  => hash( 'sha256', $post->post_content ),
				'status'       => 'synced',
				'is_bridge'    => 0,
				'generated_at' => current_time( 'mysql' ),
				'last_error'   => null,
			)
		);

		Llms_Txt::invalidate();

		return true;
	}

	/**
	 * Quita la publicación: borra el .md físico y la fila del Registro por
	 * completo (no deja un status "oculto" inventado -- si se quiere volver
	 * a publicar, "Generar contenido" la vuelve a crear desde cero).
	 */
	public static function unpublish( $post_id ) {
		if ( ! post_type_exists( Genix_Reader::CPT ) ) {
			return new \WP_Error( 'wookb_genix_unavailable', __( 'Genix no está activo.', 'ai-knowledge' ) );
		}

		$lang = Languages::post_language( $post_id );
		$row  = Registry::find( $post_id, $lang );
		if ( $row ) {
			if ( $row->md_path ) {
				Markdown_Store::delete( $row->md_path );
			}
			Registry::delete_row( $row->id );
			Llms_Txt::invalidate();
		}

		return true;
	}

	/** true si el artículo ya tiene fila publicada (Registry, status 'synced'). */
	public static function is_public( $post_id, $lang ) {
		$row = Registry::find( $post_id, $lang );
		return (bool) ( $row && 'synced' === $row->status );
	}
}
