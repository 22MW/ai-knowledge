<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Proveedor WPML: un post por idioma. Solo `Languages` usa esta clase; el
 * resto del plugin pregunta al servicio, nunca a WPML.
 */
class Wpml extends Language_Provider {

	public static function detect() {
		return defined( 'ICL_SITEPRESS_VERSION' ) || function_exists( 'wpml_get_active_languages_filter' );
	}

	public function id() {
		return 'wpml';
	}

	public function label() {
		return 'WPML';
	}

	public function creates_post_per_language() {
		return true;
	}

	public function languages() {
		$active = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );
		if ( ! is_array( $active ) ) {
			return array();
		}
		$out = array();
		foreach ( $active as $code => $info ) {
			$name = ( is_array( $info ) && ! empty( $info['native_name'] ) ) ? $info['native_name'] : strtoupper( $code );
			$url  = apply_filters( 'wpml_permalink', home_url( '/' ), $code );
			$out[ $code ] = array(
				'name' => $name,
				'url'  => is_string( $url ) ? $url : '',
			);
		}
		return $out;
	}

	/** Idioma por defecto vía el filtro estándar 'wpml_default_language'. */
	public function default_language() {
		$default = apply_filters( 'wpml_default_language', null );
		return ( $default && is_string( $default ) ) ? $default : null;
	}

	public function post_language( $post_id ) {
		$lang = apply_filters(
			'wpml_element_language_code',
			null,
			array(
				'element_id'   => $post_id,
				'element_type' => get_post_type( $post_id ),
			)
		);
		return $lang ? $lang : null;
	}

	/** Post original del grupo de traducción (el que no tiene idioma de origen). */
	public function original_id( $post_id ) {
		$type = 'post_' . get_post_type( $post_id );
		$trid = apply_filters( 'wpml_element_trid', null, $post_id, $type );
		if ( $trid ) {
			$translations = apply_filters( 'wpml_get_element_translations', null, $trid, $type );
			if ( is_array( $translations ) ) {
				foreach ( $translations as $translation ) {
					if ( ! is_object( $translation ) || empty( $translation->element_id ) ) {
						continue;
					}
					$flagged = ! empty( $translation->original );
					$no_src  = property_exists( $translation, 'source_language_code' ) && empty( $translation->source_language_code );
					if ( $flagged || $no_src ) {
						return (int) $translation->element_id;
					}
				}
			}
		}
		return (int) $post_id;
	}

	public function translation_id( $post_id, $lang ) {
		$translated_id = apply_filters( 'wpml_object_id', $post_id, get_post_type( $post_id ), false, $lang );
		return $translated_id ? (int) $translated_id : null;
	}

	/**
	 * get_permalink() devuelve la URL del idioma de la petición (WPML la
	 * reescribe), no la del idioma del post: se cambia de idioma solo alrededor
	 * de la llamada. Un único sitio para todas las URLs de contenido.
	 */
	public function permalink( $post_id ) {
		$lang = $this->post_language( $post_id );
		if ( ! $lang ) {
			$url = get_permalink( $post_id );
			return $url ? $url : '';
		}
		$url = $this->with_language(
			$lang,
			function () use ( $post_id ) {
				return get_permalink( $post_id );
			}
		);
		return $url ? $url : '';
	}

	/** Cambia el idioma de WPML alrededor del callback y lo restaura siempre. */
	public function with_language( $lang, $callback ) {
		$current = $this->current_language();
		if ( ! $lang || $current === $lang ) {
			return call_user_func( $callback );
		}
		do_action( 'wpml_switch_language', $lang );
		try {
			return call_user_func( $callback );
		} finally {
			do_action( 'wpml_switch_language', $current ? $current : null );
		}
	}

	public function current_language() {
		$lang = apply_filters( 'wpml_current_language', null );
		return ( $lang && is_string( $lang ) ) ? $lang : null;
	}

	public function cookie_names() {
		return array( '_icl_current_language', 'wp-wpml_current_language' );
	}

	public function term_id_in_language( $term_id, $taxonomy, $lang ) {
		$translated = apply_filters( 'wpml_object_id', $term_id, $taxonomy, false, $lang );
		return $translated ? (int) $translated : (int) $term_id;
	}

	/** trid del elemento (solo con $sitepress cargado). */
	public function trid( $post_id ) {
		global $sitepress;
		if ( ! $sitepress ) {
			return null;
		}
		return apply_filters( 'wpml_element_trid', null, $post_id, 'post_' . get_post_type( $post_id ) );
	}

	/** Asigna idioma y trid a un post sgkb-docs generado. */
	public function set_document_language( $post_id, $lang, $trid = null ) {
		do_action(
			'wpml_set_element_language_details',
			array(
				'element_id'           => $post_id,
				'element_type'         => 'post_sgkb-docs',
				'trid'                 => $trid,
				'language_code'        => $lang,
				'source_language_code' => null,
			)
		);
	}
}
