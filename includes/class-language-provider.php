<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contrato de un proveedor de idiomas (WPML, Polylang, TranslatePress o
 * ninguno). Solo `Languages` habla con los proveedores: el resto del plugin
 * pregunta al servicio y nunca a un plugin de idiomas concreto.
 *
 * Cada método devuelve un valor neutro por defecto (sin idiomas, sin
 * traducciones) para que un proveedor solo tenga que implementar lo que su
 * plugin de idiomas sabe hacer.
 */
abstract class Language_Provider {

	/** ¿Está activo el plugin de idiomas que gestiona este proveedor? */
	abstract public static function detect();

	/** Identificador corto: 'wpml', 'polylang', 'translatepress', 'none'. */
	abstract public function id();

	/** Nombre legible del plugin de idiomas (solo para mostrar en el admin). */
	abstract public function label();

	/** ¿El plugin crea un post distinto por idioma? Decide si aparece el check "Crear por idioma". */
	public function creates_post_per_language() {
		return false;
	}

	/**
	 * Idiomas de la web según el plugin: código => array( 'name' => nombre
	 * nativo, 'url' => URL de portada en ese idioma ).
	 */
	public function languages() {
		return array();
	}

	/** Idioma por defecto del plugin de idiomas, o null. */
	public function default_language() {
		return null;
	}

	/** Idioma de un post, o null si el plugin no lo sabe. */
	public function post_language( $post_id ) {
		return null;
	}

	/** Post de origen de una traducción. Sin concepto de original, el propio post. */
	public function original_id( $post_id ) {
		return (int) $post_id;
	}

	/** ID de la traducción real del post en un idioma, o null. Solo proveedores con un post por idioma. */
	public function translation_id( $post_id, $lang ) {
		return null;
	}

	/** URL del contenido en otro idioma, o null si no existe esa versión. */
	public function post_url( $post_id, $lang ) {
		if ( ! $this->creates_post_per_language() ) {
			return null;
		}
		$translated_id = $this->translation_id( $post_id, $lang );
		if ( ! $translated_id ) {
			return null;
		}
		$url = $this->permalink( $translated_id );
		return $url ? $url : null;
	}

	/**
	 * URL de un contenido en SU idioma. Por defecto get_permalink(); un
	 * proveedor que reescribe la URL según el idioma de la petición (WPML) lo
	 * sobrescribe para devolver siempre la del idioma del propio contenido.
	 */
	public function permalink( $post_id ) {
		$url = get_permalink( $post_id );
		return $url ? $url : '';
	}

	/**
	 * Ejecuta un callback con el idioma de la petición cambiado a $lang y lo
	 * restaura después. Por defecto solo lo ejecuta (proveedores sin cambio de
	 * idioma en servidor).
	 */
	public function with_language( $lang, $callback ) {
		return call_user_func( $callback );
	}

	/** Idioma en el que navega el visitante en esta petición, o null. */
	public function current_language() {
		return null;
	}

	/** Nombres de cookie donde el plugin guarda el idioma del visitante. */
	public function cookie_names() {
		return array();
	}

	/** ID de un término en otro idioma. Sin traducción de términos, el mismo. */
	public function term_id_in_language( $term_id, $taxonomy, $lang ) {
		return (int) $term_id;
	}

	/** Grupo de traducción del post (solo WPML). */
	public function trid( $post_id ) {
		return null;
	}

	/** Asigna idioma (y grupo) a un post sgkb-docs generado por el plugin. */
	public function set_document_language( $post_id, $lang, $trid = null ) {
	}

	/** Argumentos de consulta para que el plugin no filtre por el idioma actual. */
	public function all_languages_query_args() {
		return array();
	}
}
