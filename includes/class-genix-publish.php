<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fase 3 del flujo de publicacion/AJAX/prompts por documento (ver
 * _dev/plan-publicacion-ajax-prompts.md): gestion del estado publico
 * (is_public) de un documento sgkb-docs de Support Genix.
 *
 * Separada de Genix_Markdown (Fase 2, solo conversion HTML->MD y escritura
 * del .md) porque esta clase ademas gestiona la fila de Registry y el
 * archivo fisico segun el estado publico -- misma separacion de
 * responsabilidades que ya existe entre Markdown_Store (persistencia) y
 * Document_Pipeline (orquestacion) para el resto de source_type.
 *
 * No reutiliza Document_Pipeline::process(): ese metodo es del flujo de
 * productos/CPTs con Generator (IA) y hash de contenido para detectar
 * cambios, y no debe tocarse ni reutilizarse aqui. En su lugar usa
 * Registry::upsert() directamente, tal cual esta pensada para cualquier
 * source_type (ver su firma generica en class-registry.php).
 */
class Genix_Publish {

	/**
	 * Aplica el estado publico de un documento Genix (sgkb-docs):
	 *
	 * - is_public = true: genera/actualiza el .md via
	 *   Genix_Markdown::write_from_post() y crea o actualiza la fila de
	 *   Registry con status 'synced' e is_public = 1. A partir de ahi,
	 *   Registry::get_synced_public_urls() ya lo incluye en llms.txt sin
	 *   cambios adicionales (requiere is_public = 1 explicito para
	 *   source_type que sea un post_type real, como 'sgkb-docs').
	 * - is_public = false: pone is_public = 0 en la fila (si existe) y
	 *   borra el .md fisico -- no lo deja huerfano. Ver nota de riesgo mas
	 *   abajo sobre por que se borra en vez de dejarlo huerfano.
	 *
	 * @param int  $post_id   ID del post sgkb-docs.
	 * @param bool $is_public Estado publico deseado.
	 * @return int|true|\WP_Error ID de fila de Registry al publicar, true al
	 *                            despublicar, o WP_Error si el documento no
	 *                            existe/no esta publicado.
	 */
	public static function set_public( $post_id, $is_public ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return new \WP_Error(
				'aikb_genix_publish_invalid_id',
				__( 'ID de documento Genix invalido.', 'ai-knowledge' )
			);
		}

		$data = Genix_Reader::get_document_data( $post_id );
		if ( ! $data ) {
			return new \WP_Error(
				'aikb_genix_publish_not_found',
				__( 'El documento Genix no existe o no esta publicado.', 'ai-knowledge' )
			);
		}

		$lang = Wpml::element_language( $post_id );

		if ( $is_public ) {
			return self::publish( $post_id, $lang, $data );
		}

		return self::unpublish( $post_id, $lang );
	}

	/**
	 * Genera/actualiza el .md y crea o localiza la fila de Registry para
	 * source_id = $post_id, lang = $lang (Registry::upsert() hace el
	 * upsert por source_id+lang tal cual la usa el resto del plugin; no
	 * existe fila hasta que un documento Genix se publica por primera vez).
	 */
	protected static function publish( $post_id, $lang, array $data ) {
		$relative = Genix_Markdown::write_from_post( $post_id );
		if ( is_wp_error( $relative ) ) {
			return $relative;
		}

		$row_id = Registry::upsert( array(
			'source_id'    => $post_id,
			'source_type'  => Genix_Reader::CPT,
			'lang'         => $lang,
			'md_path'      => $relative,
			'source_hash'  => self::compute_hash( $data ),
			'status'       => 'synced',
			'is_public'    => 1,
			'generated_at' => current_time( 'mysql' ),
		) );

		return $row_id;
	}

	/**
	 * Despublica: is_public = 0 en la fila (si existe) y borra el .md
	 * fisico.
	 *
	 * Riesgo detectado y por que se borra en vez de dejarlo huerfano:
	 * Markdown_Server::maybe_serve() sirve cualquier .md que exista bajo
	 * Markdown_Store::base_dir() y cumpla el patron lang/slug.md, sin
	 * comprobar en ningun momento is_public ni el estado de la fila de
	 * Registry -- solo valida que la ruta no escape del directorio base.
	 * Si el archivo se dejara huerfano tras desmarcar, seguiria siendo
	 * accesible por su URL directa (/ai-knowledge-doc/{lang}/{slug}.md)
	 * aunque ya no apareciera en llms.txt. Igualmente,
	 * Markdown_Discovery::print_link() solo depende de que la fila tenga
	 * status 'synced' y md_path no vacio -- no de is_public -- asi que con
	 * el archivo aun en disco y sin limpiar la fila, el <link
	 * rel="alternate"> de la propia pagina del documento podria seguir
	 * anunciandolo. Borrar el archivo y vaciar md_path evita ambos casos
	 * sin tocar Markdown_Server ni Markdown_Discovery (que sirven/anuncian
	 * por igual a todos los source_type, no solo a Genix, y tocarlos aqui
	 * seria alcance fuera de esta fase).
	 */
	protected static function unpublish( $post_id, $lang ) {
		$row = Registry::find( $post_id, $lang );
		if ( ! $row ) {
			return true;
		}

		if ( ! empty( $row->md_path ) ) {
			Markdown_Store::delete( $row->md_path );
		}

		Registry::upsert( array(
			'source_id'   => $post_id,
			'source_type' => Genix_Reader::CPT,
			'lang'        => $lang,
			'md_path'     => '',
			'is_public'   => 0,
		) );

		return true;
	}

	/**
	 * Hash simple de titulo+contenido para source_hash (columna NOT NULL de
	 * Registry). No reutiliza Document_Pipeline::compute_hash() (protected,
	 * y pensada para el array de datos del extractor de productos/CPTs) --
	 * aqui basta con detectar si el post de Genix cambio desde la ultima
	 * publicacion, sin logica de staleness adicional en esta fase.
	 */
	protected static function compute_hash( array $data ) {
		return hash( 'sha256', $data['title'] . '|' . $data['content'] );
	}
}
