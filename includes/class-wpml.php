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

	/**
	 * Idioma PRINCIPAL real del sitio (distinto de "idiomas activos"): el que
	 * WPML tiene configurado como idioma por defecto, vía el filtro estándar
	 * 'wpml_default_language'. Usado por los documentos "compuestos" del
	 * propio plugin (FAQ, información de tienda) que no tienen una
	 * traducción real por idioma como un producto/página -- no tiene sentido
	 * generarlos en todos los idiomas activos, solo en el principal.
	 *
	 * Fallback sin WPML (o si el filtro no devuelve nada, caso no esperado
	 * pero posible si el modulo de idiomas de WPML no está inicializado
	 * todavía): primer idioma de active_languages(), que ya cae a 'es' en
	 * ese caso -- mismo patrón de fallback monolingüe que el resto de la clase.
	 */
	public static function default_language() {
		if ( self::is_active() ) {
			$default = apply_filters( 'wpml_default_language', null );
			if ( $default && is_string( $default ) ) {
				return $default;
			}
		}
		$active = self::active_languages();
		return $active ? $active[0] : 'es';
	}

	/** Nombre nativo de un idioma (para listarlo dentro de otro idioma sin tener que traducirlo). */
	protected static function native_language_name( $code ) {
		$names = array(
			'es' => 'español',
			'ca' => 'català',
			'en' => 'English',
			'de' => 'Deutsch',
			'eu' => 'euskara',
			'fr' => 'français',
		);
		return isset( $names[ $code ] ) ? $names[ $code ] : strtoupper( $code );
	}

	/**
	 * Frase "Esta web está disponible también en: ..." (Markdown, una línea),
	 * redactada en $doc_lang -- el idioma real del documento que la incluye.
	 * Usada por los documentos "compuestos" del propio plugin (FAQ,
	 * información de tienda, Negocio) que ahora solo se generan en el idioma
	 * principal del sitio: sin esta nota, una IA que lea ese documento no
	 * tendría forma de saber que existen otras versiones del sitio en otros
	 * idiomas, ya que este documento en concreto no está traducido.
	 *
	 * Vacío si el sitio es monoidioma (ruido innecesario) o si $doc_lang es
	 * el único idioma activo.
	 */
	public static function languages_note( $doc_lang ) {
		$active = self::active_languages();
		$others = array_values( array_diff( $active, array( $doc_lang ) ) );
		if ( empty( $others ) ) {
			return '';
		}

		$templates = array(
			'es' => 'Esta web también está disponible en: %s.',
			'ca' => 'Aquest lloc també està disponible en: %s.',
			'en' => 'This website is also available in: %s.',
			'de' => 'Diese Website ist auch verfügbar auf: %s.',
			'eu' => 'Webgune hau eskuragarri dago ere: %s.',
			'fr' => 'Ce site est également disponible en : %s.',
		);
		$template = isset( $templates[ $doc_lang ] ) ? $templates[ $doc_lang ] : $templates['es'];

		$names = array_map( array( __CLASS__, 'native_language_name' ), $others );
		return sprintf( $template, implode( ', ', $names ) );
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
