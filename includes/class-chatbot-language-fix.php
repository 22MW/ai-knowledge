<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fuerza el idioma correcto del visitante en la peticion REST del chatbot de
 * Support Genix, via su propio filtro publico 'support_genix_current_language_key'
 * (ApbdWpsBaseModule::SetMultiLangProps(), core/ApbdWpsBaseModule.php ~linea 908).
 * NO toca ningun archivo de Genix.
 *
 * Causa raiz documentada en 06-implementacion/addon-support-genix/investigacion-comportamiento-chatbot.md
 * punto 1: la peticion del chat viaja como REST independiente
 * (/wp-json/apbd-wps/v1/chatbot), sin prefijo de idioma en la URL y sin
 * parametro 'lang' explicito. Genix resuelve el idioma con
 * apply_filters('wpml_current_language', 'en') en cada carga del modulo -- si
 * WPML no consigue resolverlo de forma fiable para esa peticion concreta (caso
 * tipico de negociacion de idioma por directorio + llamada AJAX/REST sin
 * prefijo), cae al idioma por defecto del sitio en vez del idioma real del
 * visitante.
 *
 * Estrategia: en vez de confiar en la resolucion de Genix, resolvemos el
 * idioma real nosotros mismos -- primero re-preguntando directamente a WPML
 * (misma via que usa Genix, pero evaluada aqui explicitamente), y si eso no
 * da nada util, inspeccionando la cookie de idioma de WPML que el navegador
 * del visitante SI envia en la peticion REST (misma pestaña, mismo origen).
 */
class Chatbot_Language_Fix {

	const FILTER = 'support_genix_current_language_key';

	public static function init() {
		add_filter( self::FILTER, array( __CLASS__, 'resolve_language' ), 10, 1 );
	}

	/**
	 * Idioma de navegacion real del visitante (WPML/cookie), sin fallback al
	 * valor de Genix. Null si no se pudo determinar. Reutilizado por
	 * Chatbot_Relevance_Guard (Fase 2, pieza 2) para decidir si el idioma de
	 * navegacion esta entre los idiomas activos del sitio.
	 */
	public static function detect_visitor_language() {
		return self::resolve_language( null );
	}

	public static function resolve_language( $current ) {
		// 1) Pregunta directa a WPML en el momento de esta peticion concreta.
		if ( function_exists( 'apply_filters' ) && ( defined( 'ICL_SITEPRESS_VERSION' ) || function_exists( 'wpml_get_active_languages_filter' ) ) ) {
			$lang = apply_filters( 'wpml_current_language', null );
			if ( $lang && is_string( $lang ) ) {
				return sanitize_text_field( $lang );
			}
		}

		// 2) Cookie de idioma de WPML (la que fija el visitante al navegar y que
		// SI viaja en la peticion REST del chat, mismo origen).
		$cookie_names = array( '_icl_current_language', 'wp-wpml_current_language' );
		foreach ( $cookie_names as $cookie_name ) {
			if ( ! empty( $_COOKIE[ $cookie_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				$lang = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( $lang ) {
					return $lang;
				}
			}
		}

		// 3) Nada resuelto: no imponer un idioma inventado, dejar que Genix
		// use su propio valor por defecto tal cual.
		return $current;
	}
}
