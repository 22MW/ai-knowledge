<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integración WPML: idioma del elemento, traducciones disponibles, idiomas activos.
 * Todos los métodos tienen fallback monolingüe (es) si WPML no está activo.
 */
class Wpml {

	public static function is_active() {
		return defined( 'ICL_SITEPRESS_VERSION' ) || function_exists( 'wpml_get_active_languages_filter' );
	}

	public static function active_languages() {
		if ( ! self::is_active() ) {
			return array( 'es' );
		}
		$langs = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );
		return $langs ? array_keys( $langs ) : array( 'es' );
	}

	public static function element_language( $post_id ) {
		if ( ! self::is_active() ) {
			return 'es';
		}
		$lang = apply_filters( 'wpml_element_language_code', null, array(
			'element_id'   => $post_id,
			'element_type' => get_post_type( $post_id ),
		) );
		return $lang ? $lang : 'es';
	}

	/**
	 * Devuelve trid del elemento.
	 */
	public static function get_trid( $post_id ) {
		if ( ! self::is_active() ) {
			return null;
		}
		global $sitepress;
		if ( ! $sitepress ) {
			return null;
		}
		return apply_filters( 'wpml_element_trid', null, $post_id, 'post_' . get_post_type( $post_id ) );
	}

	/**
	 * Traducción real del post en un idioma dado, o null si no existe/no está completa.
	 */
	public static function get_translation_id( $post_id, $lang ) {
		if ( ! self::is_active() ) {
			return 'es' === $lang ? $post_id : null;
		}

		// Si el elemento ya está en el idioma solicitado, no hace falta preguntar a WPML:
		// wpml_object_id puede devolver false para una autoconsulta en el propio idioma.
		if ( self::element_language( $post_id ) === $lang ) {
			return (int) $post_id;
		}

		$translated_id = apply_filters( 'wpml_object_id', $post_id, get_post_type( $post_id ), false, $lang );
		return $translated_id ? (int) $translated_id : null;
	}

	/**
	 * Asigna idioma y trid a un post (usado para posts sgkb-docs generados).
	 */
	public static function set_language( $post_id, $lang, $trid = null ) {
		if ( ! self::is_active() ) {
			return;
		}
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
