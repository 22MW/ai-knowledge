<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cierra el agujero REST de only_for_chatbot en sgkb-docs (comprobaciones-tecnicas.md §2).
 * Colección: excluye flagados de listados no autorizados. Detalle: 404 (no 403) para posts flagados.
 * Activo siempre que Genix esté presente, proteja también documentos creados a mano por el usuario.
 *
 * Nota de implementación: el detalle NO se corta devolviendo WP_Error desde
 * `rest_prepare_{cpt}` — el core sigue llamando métodos de WP_REST_Response
 * (add_links, etc.) sobre el valor devuelto por ese filtro y provoca un fatal
 * si es un WP_Error. El corte correcto es en `rest_request_before_callbacks`,
 * antes de que el controlador prepare la respuesta.
 */
class Rest_Guard {

	const CPT        = 'sgkb-docs';
	const META_KEY   = 'only_for_chatbot';
	const CAPABILITY = 'edit_others_posts';

	public static function init() {
		if ( ! post_type_exists( self::CPT ) ) {
			return;
		}
		add_filter( 'rest_' . self::CPT . '_query', array( __CLASS__, 'filter_collection' ), 10, 2 );
		add_filter( 'rest_request_before_callbacks', array( __CLASS__, 'guard_detail' ), 10, 3 );
	}

	public static function filter_collection( $args, $request ) {
		if ( current_user_can( self::CAPABILITY ) ) {
			return $args;
		}

		$meta_query = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
		$meta_query[] = array(
			'relation' => 'OR',
			array( 'key' => self::META_KEY, 'compare' => 'NOT EXISTS' ),
			array( 'key' => self::META_KEY, 'value' => '1', 'compare' => '!=' ),
		);
		$args['meta_query'] = $meta_query; // phpcs:ignore

		return $args;
	}

	/**
	 * Corta la petición de detalle (GET/PUT/DELETE de un post concreto) antes del controlador,
	 * si el post es de sgkb-docs y está flagado only_for_chatbot=1 y el usuario no tiene capability.
	 */
	public static function guard_detail( $response, $handler, $request ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$route = $request->get_route();
		if ( ! preg_match( '#^/wp/v2/' . preg_quote( self::CPT, '#' ) . '/(\d+)#', $route, $matches ) ) {
			return $response;
		}

		if ( current_user_can( self::CAPABILITY ) ) {
			return $response;
		}

		$post_id = (int) $matches[1];
		if ( '1' === get_post_meta( $post_id, self::META_KEY, true ) ) {
			return new \WP_Error(
				'rest_post_invalid_id',
				__( 'Invalid post ID.', 'woo-kb-generator' ),
				array( 'status' => 404 )
			);
		}

		return $response;
	}
}
