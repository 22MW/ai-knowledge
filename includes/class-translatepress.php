<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Proveedor TranslatePress: un solo post traducido al vuelo. Solo aporta la
 * lista de idiomas y la URL traducida; no crea posts por idioma.
 * El código de idioma es el slug de URL de TranslatePress (p. ej. 'es', 'en').
 */
class Translatepress extends Language_Provider {

	public static function detect() {
		return class_exists( 'TRP_Translate_Press' );
	}

	public function id() {
		return 'translatepress';
	}

	public function label() {
		return 'TranslatePress';
	}

	protected function trp_settings() {
		$settings = get_option( 'trp_settings', array() );
		return is_array( $settings ) ? $settings : array();
	}

	/** Slug de URL de un locale de TranslatePress ('es_ES' => 'es'). */
	protected function slug_for_locale( $locale ) {
		$settings = $this->trp_settings();
		if ( ! empty( $settings['url-slugs'][ $locale ] ) ) {
			return sanitize_key( $settings['url-slugs'][ $locale ] );
		}
		return sanitize_key( strtolower( strtok( (string) $locale, '_' ) ) );
	}

	/** Locale de TranslatePress de un slug de URL, o null. */
	protected function locale_for_slug( $slug ) {
		$settings = $this->trp_settings();
		foreach ( (array) ( isset( $settings['translation-languages'] ) ? $settings['translation-languages'] : array() ) as $locale ) {
			if ( $this->slug_for_locale( $locale ) === $slug ) {
				return $locale;
			}
		}
		return null;
	}

	protected function native_names( array $locales ) {
		try {
			if ( class_exists( 'TRP_Translate_Press' ) ) {
				$trp       = \TRP_Translate_Press::get_trp_instance();
				$component = $trp ? $trp->get_component( 'languages' ) : null;
				if ( $component && method_exists( $component, 'get_language_names' ) ) {
					$names = $component->get_language_names( $locales, 'native_name' );
					return is_array( $names ) ? $names : array();
				}
			}
		} catch ( \Throwable $e ) {
			return array();
		}
		return array();
	}

	public function languages() {
		$settings = $this->trp_settings();
		$locales  = isset( $settings['translation-languages'] ) ? (array) $settings['translation-languages'] : array();
		$names    = $this->native_names( $locales );
		$out      = array();
		foreach ( $locales as $locale ) {
			$slug         = $this->slug_for_locale( $locale );
			$out[ $slug ] = array(
				'name' => isset( $names[ $locale ] ) ? $names[ $locale ] : strtoupper( $slug ),
				'url'  => $this->url_for_locale( $locale, home_url( '/' ) ),
			);
		}
		return $out;
	}

	public function default_language() {
		$settings = $this->trp_settings();
		return ! empty( $settings['default-language'] ) ? $this->slug_for_locale( $settings['default-language'] ) : null;
	}

	protected function url_for_locale( $locale, $url ) {
		if ( ! function_exists( 'trp_get_url_for_language' ) ) {
			return '';
		}
		$settings = $this->trp_settings();
		if ( ! empty( $settings['default-language'] ) && $settings['default-language'] === $locale ) {
			return $url;
		}
		$translated = trp_get_url_for_language( $locale, $url );
		return is_string( $translated ) ? $translated : '';
	}

	/** El contenido es único: su idioma es el idioma por defecto de TranslatePress. */
	public function post_language( $post_id ) {
		return $this->default_language();
	}

	public function post_url( $post_id, $lang ) {
		$locale = $this->locale_for_slug( $lang );
		$url    = get_permalink( $post_id );
		if ( ! $locale || ! $url ) {
			return null;
		}
		$translated = $this->url_for_locale( $locale, $url );
		return '' !== $translated ? $translated : null;
	}

	public function current_language() {
		global $TRP_LANGUAGE; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		return ! empty( $TRP_LANGUAGE ) ? $this->slug_for_locale( $TRP_LANGUAGE ) : null;
	}
}
