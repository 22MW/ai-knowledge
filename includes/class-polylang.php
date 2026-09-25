<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Proveedor Polylang: un post por idioma. Usa solo la API pública de
 * funciones (`pll_*`, https://polylang.pro/doc/function-reference/).
 */
class Polylang extends Language_Provider {

	public static function detect() {
		return defined( 'POLYLANG_VERSION' ) || function_exists( 'pll_languages_list' );
	}

	public function id() {
		return 'polylang';
	}

	public function label() {
		return 'Polylang';
	}

	public function creates_post_per_language() {
		return function_exists( 'pll_get_post' ) && function_exists( 'pll_get_post_language' );
	}

	public function languages() {
		if ( ! function_exists( 'pll_languages_list' ) ) {
			return array();
		}
		$slugs = pll_languages_list();
		$names = pll_languages_list( array( 'fields' => 'name' ) );
		if ( ! is_array( $slugs ) ) {
			return array();
		}
		$out = array();
		foreach ( array_values( $slugs ) as $i => $slug ) {
			$out[ $slug ] = array(
				'name' => ( is_array( $names ) && isset( $names[ $i ] ) ) ? $names[ $i ] : strtoupper( $slug ),
				'url'  => function_exists( 'pll_home_url' ) ? (string) pll_home_url( $slug ) : '',
			);
		}
		return $out;
	}

	public function default_language() {
		if ( ! function_exists( 'pll_default_language' ) ) {
			return null;
		}
		$lang = pll_default_language();
		return $lang ? $lang : null;
	}

	public function post_language( $post_id ) {
		if ( ! function_exists( 'pll_get_post_language' ) ) {
			return null;
		}
		$lang = pll_get_post_language( $post_id );
		return $lang ? $lang : null;
	}

	/** Polylang no marca un original: se toma la traducción en su idioma por defecto. */
	public function original_id( $post_id ) {
		$default = $this->default_language();
		if ( $default ) {
			$translated = $this->translation_id( $post_id, $default );
			if ( $translated ) {
				return $translated;
			}
		}
		return (int) $post_id;
	}

	public function translation_id( $post_id, $lang ) {
		if ( ! function_exists( 'pll_get_post' ) ) {
			return null;
		}
		$translated = pll_get_post( $post_id, $lang );
		return $translated ? (int) $translated : null;
	}

	public function current_language() {
		if ( ! function_exists( 'pll_current_language' ) ) {
			return null;
		}
		$lang = pll_current_language();
		return $lang ? $lang : null;
	}

	public function cookie_names() {
		return array( 'pll_language' );
	}

	public function term_id_in_language( $term_id, $taxonomy, $lang ) {
		if ( ! function_exists( 'pll_get_term' ) ) {
			return (int) $term_id;
		}
		$translated = pll_get_term( $term_id, $lang );
		return $translated ? (int) $translated : (int) $term_id;
	}

	/** Solo si el CPT sgkb-docs es traducible en Polylang; si no, se deja sin idioma. */
	public function set_document_language( $post_id, $lang, $trid = null ) {
		if ( function_exists( 'pll_set_post_language' ) && function_exists( 'pll_is_translated_post_type' ) && pll_is_translated_post_type( 'sgkb-docs' ) ) {
			pll_set_post_language( $post_id, $lang );
		}
	}

	/** Polylang filtra las consultas por idioma actual: 'lang' vacío las pide en todos. */
	public function all_languages_query_args() {
		return array( 'lang' => '' );
	}
}
