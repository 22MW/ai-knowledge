<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pieza 5: localiza artículos "sgkb-docs" que existen SOLO en Genix (el
 * admin los escribió directamente ahí, sin producto/página detrás en el
 * plugin). Camino de solo lectura, no toca nada de Genix.
 *
 * CRÍTICO (corrección de un intento anterior que falló): un sgkb-docs cuyo
 * ID ya aparece como doc_post_id en alguna fila del Registro es la COPIA
 * generada por Document_Pipeline para el chatbot de un producto/página real
 * -- no un artículo exclusivo de Genix. Si no se excluyen esos IDs, salen
 * duplicados sin sentido en el listado (el bug real de la vez anterior).
 *
 * Segunda exclusión (corrección posterior, confirmada por el usuario): un
 * sgkb-docs con el meta 'only_for_chatbot' activo (lo escribe Genix mismo,
 * support-genix-lite) NO es público ni siquiera dentro de Genix -- ver
 * Genix_Publish::is_chatbot_only(). No tiene sentido ofrecerlo aquí como
 * candidato a publicarse en llms.txt.
 */
class Genix_Reader {

	const CPT = 'sgkb-docs';

	public static function is_available() {
		return post_type_exists( self::CPT );
	}

	/**
	 * Lista de artículos sgkb-docs publicados que NO son la copia de un
	 * documento generado por el pipeline normal (ver docblock de la clase).
	 * Cada elemento: id, title, permalink, lang.
	 *
	 * El permalink se resuelve con get_permalink() real (no se asume que el
	 * slug público coincida con el post_type interno "sgkb-docs" -- ver
	 * Doc_Redirect, que existe precisamente porque la URL pública de este
	 * CPT puede no ser la que uno esperaría a simple vista).
	 */
	public static function find_exclusive_articles() {
		if ( ! self::is_available() ) {
			return array();
		}

		$excluded_ids = Registry::all_doc_post_ids();

		$posts = get_posts(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'numberposts'    => -1,
				'fields'         => 'ids',
				'orderby'        => 'title',
				'order'          => 'ASC',
				'suppress_filters' => false,
			)
		);

		$articles = array();
		foreach ( $posts as $post_id ) {
			if ( in_array( (int) $post_id, $excluded_ids, true ) ) {
				continue;
			}
			if ( Genix_Publish::is_chatbot_only( $post_id ) ) {
				continue;
			}
			$articles[] = array(
				'id'        => (int) $post_id,
				'title'     => get_the_title( $post_id ),
				'permalink' => get_permalink( $post_id ),
				'lang'      => Wpml::element_language( $post_id ),
			);
		}

		return $articles;
	}
}
