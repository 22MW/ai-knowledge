<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Red de seguridad: si alguien llega directo a la URL publica de un documento
 * sgkb-docs generado por nuestro plugin (son resumenes, no la ficha real),
 * redirige 301 al producto/pagina real al que pertenece.
 *
 * Solo afecta a documentos con meta 'wookb_source_url'/'wookb_source_id' --
 * es decir, unicamente los que crea Genix_Bridge::upsert_document(). Los
 * sgkb-docs ajenos (de otro origen/plugin) no tienen esa meta y quedan sin
 * tocar (ver investigacion-comportamiento-chatbot.md punto 3).
 *
 * Tarea 4 (idioma correcto en la redireccion, Opcion A): esta es una visita
 * normal de pagina, no la peticion REST del chat -- aqui SI es fiable
 * apply_filters('wpml_current_language', ...), a diferencia del caso del
 * chatbot (ver Chatbot_Language_Fix). Se detecta el idioma real en que el
 * visitante esta navegando en este momento y, si el producto tiene traduccion
 * real a ese idioma, se redirige ahi. Si no, cae a espanol; si tampoco existe,
 * usa la wookb_source_url original guardada en el documento.
 */
class Doc_Redirect {

	const CPT           = 'sgkb-docs';
	const META_URL      = 'wookb_source_url';
	const META_SOURCE_ID = 'wookb_source_id';
	const FALLBACK_LANG = 'es';

	public static function init() {
		// Prioridad muy temprana (0): con el fix WPML de hoy, los documentos
		// sgkb-docs tienen su propio trid real, asi que WPML ejecuta su propia
		// redireccion canonica de idioma sobre este CPT (a la traduccion "correcta"
		// del propio documento) en el mismo hook template_redirect. Si nuestra
		// redireccion corre despues (prioridad por defecto 10), WPML ya ha hecho
		// exit y la nuestra nunca se ejecuta -- confirmado empiricamente: la
		// version /en/ del documento redirigia a OTRO sgkb-docs en vez de al
		// producto real. Prioridad 0 gana la carrera.
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect' ), 0 );
	}

	public static function maybe_redirect() {
		if ( ! is_singular( self::CPT ) ) {
			return;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return;
		}

		$fallback_url = get_post_meta( $post_id, self::META_URL, true );
		if ( ! $fallback_url || ! is_string( $fallback_url ) ) {
			// Sin esta meta, el documento no es nuestro: no tocar.
			return;
		}

		$target = self::resolve_target_url( $post_id, $fallback_url );

		$target = esc_url_raw( $target );
		if ( ! $target ) {
			return;
		}

		wp_safe_redirect( $target, 301 );
		exit;
	}

	/**
	 * Decide a que URL redirigir segun el idioma real de navegacion del
	 * visitante en este momento (no el idioma en que se genero el documento).
	 */
	protected static function resolve_target_url( $post_id, $fallback_url ) {
		$source_id = (int) get_post_meta( $post_id, self::META_SOURCE_ID, true );
		if ( ! $source_id || ! Wpml::is_active() ) {
			return $fallback_url;
		}

		$visitor_lang = apply_filters( 'wpml_current_language', null );
		if ( ! $visitor_lang || ! is_string( $visitor_lang ) ) {
			$visitor_lang = self::FALLBACK_LANG;
		}

		$translated_id = Wpml::get_translation_id( $source_id, $visitor_lang );
		if ( $translated_id ) {
			$url = get_permalink( $translated_id );
			if ( $url ) {
				return $url;
			}
		}

		// El producto no tiene traduccion real al idioma de navegacion actual:
		// caer a espanol si existe traduccion ahi.
		if ( self::FALLBACK_LANG !== $visitor_lang ) {
			$es_id = Wpml::get_translation_id( $source_id, self::FALLBACK_LANG );
			if ( $es_id ) {
				$url = get_permalink( $es_id );
				if ( $url ) {
					return $url;
				}
			}
		}

		// Ultimo recurso: la URL guardada en el documento en el momento de generarlo.
		return $fallback_url;
	}
}
