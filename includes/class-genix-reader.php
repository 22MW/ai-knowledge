<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fase 1 del flujo de publicacion/AJAX/prompts por documento (ver
 * _dev/plan-publicacion-ajax-prompts.md): capa de LECTURA exclusiva sobre los
 * documentos sgkb-docs de Support Genix.
 *
 * No confundir con Genix_Bridge (includes/class-genix-bridge.php): ese puente
 * va en sentido contrario -- crea/actualiza sgkb-docs a partir de productos u
 * otros CPTs para el chatbot. Esta clase nunca escribe en Genix: solo localiza
 * y lee documentos ya publicados por Genix, para un flujo nuevo y aislado que
 * mas adelante los copiara integros a un .md propio.
 *
 * El post_type interno 'sgkb-docs' es distinto del slug publico de la URL:
 * ese slug esta reescrito por el propio Support Genix (y potencialmente
 * redirigido despues por Doc_Redirect si el documento tiene la meta
 * wookb_source_url de Genix_Bridge). Por eso el permalink de cada documento se
 * obtiene siempre con get_permalink(), nunca construido a mano.
 */
class Genix_Reader {

	const CPT = 'sgkb-docs';

	/**
	 * Documentos sgkb-docs publicados (post_status = 'publish') como WP_Post.
	 *
	 * @return \WP_Post[]
	 */
	public static function get_published_documents() {
		return get_posts( array(
			'post_type'      => self::CPT,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		) );
	}

	/**
	 * Un documento sgkb-docs publicado por su ID, o null si no existe o no
	 * esta publicado.
	 *
	 * @param int $post_id
	 * @return \WP_Post|null
	 */
	public static function get_published_document( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return null;
		}

		$post = get_post( $post_id );
		if ( ! $post || self::CPT !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}

		return $post;
	}

	/**
	 * Contenido crudo (post_content, sin filtrar) y permalink real de un
	 * documento publicado. Sin IA, sin resumen, sin limite de caracteres:
	 * la Fase 1 solo lee, la conversion a Markdown es Fase 2.
	 *
	 * @param int $post_id
	 * @return array{title:string,content:string,permalink:string}|null
	 */
	public static function get_document_data( $post_id ) {
		$post = self::get_published_document( $post_id );
		if ( ! $post ) {
			return null;
		}

		$permalink = get_permalink( $post );
		if ( ! $permalink ) {
			return null;
		}

		return array(
			'title'     => $post->post_title,
			'content'   => $post->post_content,
			'permalink' => $permalink,
		);
	}
}
