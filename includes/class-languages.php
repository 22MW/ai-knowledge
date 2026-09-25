<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Servicio de idiomas. El resto del plugin pregunta aquí, nunca a un plugin
 * de idiomas concreto: solo este servicio (y sus proveedores) conocen WPML,
 * Polylang o TranslatePress.
 *
 * Contrato mínimo (estrategia-idiomas.md):
 *  1. main_language()               idioma principal.
 *  2. languages()                   código, nombre nativo y URL de portada.
 *  3. post_language( $id )          idioma de un contenido.
 *  4. original_id( $id )            original de un contenido.
 *  5. post_url( $id, $lang )        URL de un contenido en otro idioma.
 *  6. creates_post_per_language()   capacidad: decide si aparece el check.
 *
 * Extras (pequeños y necesarios para no dejar llamadas directas a WPML en el
 * resto del plugin): traducción de un post, traducción de un término, idioma
 * del visitante, trid y asignación de idioma a los documentos de Genix.
 *
 * Idioma principal, en este orden: Negocio -> plugin de idiomas -> idioma de
 * WordPress. Sin `es` fijo.
 */
class Languages {

	/** Opción: array( 'main' => código|'', 'languages' => códigos, 'per_language' => 0|1 ). */
	const OPTION = 'wookb_language_settings';

	/** Opción-aviso: hay que usar «Reiniciar todo» tras cambiar el check o el idioma principal. */
	const REGEN_FLAG = 'wookb_lang_regen_pending';

	protected static $provider          = null;
	protected static $languages_cache   = null;
	protected static $legacy_per_lang   = null;

	/* ------------------------------------------------------------------
	 * Proveedor
	 * ---------------------------------------------------------------- */

	/** Proveedor activo: WPML, Polylang, TranslatePress o «ninguno». */
	public static function provider() {
		if ( null === self::$provider ) {
			foreach ( array( '\AIKB\Wpml', '\AIKB\Polylang', '\AIKB\Translatepress' ) as $class ) {
				if ( $class::detect() ) {
					self::$provider = new $class();
					break;
				}
			}
			if ( null === self::$provider ) {
				self::$provider = new No_Language_Plugin();
			}
		}
		return self::$provider;
	}

	public static function provider_id() {
		return self::provider()->id();
	}

	public static function provider_label() {
		return self::provider()->label();
	}

	/** Capacidad 6: ¿el plugin de idiomas crea un post por idioma? */
	public static function creates_post_per_language() {
		return (bool) self::provider()->creates_post_per_language();
	}

	/* ------------------------------------------------------------------
	 * Ajustes (Negocio)
	 * ---------------------------------------------------------------- */

	public static function saved() {
		$saved = get_option( self::OPTION, array() );
		return is_array( $saved ) ? $saved : array();
	}

	protected static function flush_cache() {
		self::$languages_cache = null;
	}

	/** Idioma de WordPress como código corto ('es_ES' => 'es'). */
	protected static function wordpress_language() {
		$locale = (string) get_locale();
		$code   = sanitize_key( strtolower( strtok( $locale, '_' ) ) );
		return $code ? $code : 'en';
	}

	/** Contrato 1. Negocio -> plugin de idiomas -> idioma de WordPress. */
	public static function main_language() {
		$saved = self::saved();
		if ( ! empty( $saved['main'] ) && is_string( $saved['main'] ) ) {
			return $saved['main'];
		}
		return self::auto_main_language();
	}

	/** Idioma principal sin ajuste de Negocio: plugin de idiomas -> idioma de WordPress. */
	public static function auto_main_language() {
		$detected = self::provider()->default_language();
		if ( $detected && is_string( $detected ) ) {
			return $detected;
		}
		return self::wordpress_language();
	}

	/** ¿Está el check «Crear por idioma» activo? Requiere la capacidad 6. */
	public static function per_language_enabled() {
		if ( ! self::creates_post_per_language() ) {
			return false;
		}
		$saved = self::saved();
		if ( array_key_exists( 'per_language', $saved ) ) {
			return ! empty( $saved['per_language'] );
		}
		return self::legacy_generates_per_language();
	}

	/**
	 * Instalaciones anteriores a la estrategia de idiomas: ¿ya hay documentos
	 * de contenido en un idioma distinto del principal? (Se ignoran los
	 * documentos compuestos del plugin y los artículos de Genix.) Solo
	 * lectura; el resultado se cachea por petición.
	 */
	public static function legacy_generates_per_language() {
		if ( null !== self::$legacy_per_lang ) {
			return self::$legacy_per_lang;
		}
		global $wpdb;
		$table  = Registry::table();
		$errors = $wpdb->suppress_errors( true );
		$count  = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE lang <> %s AND source_type NOT LIKE %s AND source_type <> %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table es el nombre interno de la tabla del plugin.
				self::main_language(),
				$wpdb->esc_like( 'wookb-' ) . '%',
				'sgkb-docs'
			)
		);
		$wpdb->suppress_errors( $errors );
		self::$legacy_per_lang = ( (int) $count ) > 0;
		return self::$legacy_per_lang;
	}

	/**
	 * Migración (una sola vez): si todavía no hay ajustes guardados, fija el
	 * check según lo que la instalación ya generaba. Así, al actualizar, una
	 * instalación que ya generaba por idioma queda con el check marcado y no
	 * cambia nada de golpe; una instalación nueva queda desmarcada.
	 */
	public static function maybe_migrate() {
		if ( false !== get_option( self::OPTION, false ) ) {
			return;
		}

		$settings = array();

		// Sin plugin de idiomas que fije el principal, una instalacion que ya
		// tiene documentos en un unico idioma lo conserva como principal (antes
		// era 'es' fijo): asi no aparece un segundo idioma "nuevo" (el de
		// WordPress) que duplique los documentos ya generados.
		if ( ! self::provider()->default_language() ) {
			global $wpdb;
			$table  = Registry::table();
			$errors = $wpdb->suppress_errors( true );
			$langs  = $wpdb->get_col( "SELECT DISTINCT lang FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table es el nombre interno de la tabla del plugin.
			$wpdb->suppress_errors( $errors );
			if ( is_array( $langs ) && 1 === count( $langs ) && $langs[0] ) {
				$settings['main'] = sanitize_key( $langs[0] );
				update_option( self::OPTION, $settings, false );
				self::flush_cache();
			}
		}

		$settings['per_language'] = self::legacy_generates_per_language() ? 1 : 0;
		update_option( self::OPTION, $settings, false );
		self::flush_cache();
	}

	/**
	 * Guarda los ajustes de idioma de Negocio. $per_language null = no tocar
	 * el check (p. ej. sin la capacidad 6). Marca el aviso «usa Reiniciar
	 * todo» si cambia el check o el idioma principal. Devuelve true si el
	 * aviso queda pendiente.
	 */
	public static function save_settings( $main, array $codes, $per_language ) {
		$prev_main = self::main_language();
		$prev_per  = self::per_language_enabled();

		$saved              = self::saved();
		$saved['main']      = (string) $main;
		$saved['languages'] = array_values( $codes );
		if ( null !== $per_language && self::creates_post_per_language() ) {
			$saved['per_language'] = $per_language ? 1 : 0;
		} elseif ( ! array_key_exists( 'per_language', $saved ) ) {
			$saved['per_language'] = self::legacy_generates_per_language() ? 1 : 0;
		}
		update_option( self::OPTION, $saved, false );
		self::flush_cache();

		if ( self::main_language() !== $prev_main || self::per_language_enabled() !== $prev_per ) {
			update_option( self::REGEN_FLAG, 1, false );
		}
		return self::regen_pending();
	}

	/**
	 * Guarda solo el check «Crear por idioma» (formulario propio, por AJAX).
	 * Marca el aviso «Reiniciar todo» si cambia. Devuelve true si el aviso
	 * queda pendiente.
	 */
	public static function save_per_language( $per_language ) {
		if ( ! self::creates_post_per_language() ) {
			return self::regen_pending();
		}
		$prev  = self::per_language_enabled();
		$saved = self::saved();

		$saved['per_language'] = $per_language ? 1 : 0;
		update_option( self::OPTION, $saved, false );
		self::flush_cache();

		if ( self::per_language_enabled() !== $prev ) {
			update_option( self::REGEN_FLAG, 1, false );
		}
		return self::regen_pending();
	}

	/**
	 * Normaliza el idioma principal y los idiomas de la web elegidos en
	 * Negocio: lo que coincide con la detección automática (plugin de idiomas
	 * o idioma de WordPress) se guarda vacío para seguir detectando solo.
	 * Devuelve array( principal|'', códigos ).
	 */
	protected static function normalize_language_fields( $main, array $codes ) {
		$main = sanitize_key( $main );
		if ( '' === $main || $main === self::auto_main_language() ) {
			$main = '';
		}

		$codes    = array_values( array_unique( array_filter( array_map( 'sanitize_key', $codes ) ) ) );
		$detected = array_keys( self::detected_languages() );
		$a        = $codes;
		$b        = $detected;
		sort( $a );
		sort( $b );
		if ( $detected && $a === $b ) {
			$codes = array();
		}
		return array( $main, $codes );
	}

	/**
	 * Guarda el idioma principal y los idiomas de la web (campo estructurado de
	 * Negocio y del asistente). Deja el check «Crear por idioma» como está.
	 */
	public static function save_language_fields( $main, array $codes ) {
		$valid = array_keys( self::selectable_languages() );
		$main  = in_array( $main, $valid, true ) ? $main : '';
		$codes = array_values( array_intersect( array_map( 'sanitize_key', $codes ), $valid ) );

		list( $main, $codes ) = self::normalize_language_fields( $main, $codes );
		return self::save_settings( $main, $codes, null );
	}

	/**
	 * Opciones de idioma para el selector: primero los de la web, después el
	 * resto de la tabla propia de nombres. código => nombre completo.
	 */
	public static function selectable_languages() {
		$out = array();
		foreach ( self::languages() as $code => $language ) {
			$out[ $code ] = $language['name'];
		}
		$rest = array();
		foreach ( self::native_names() as $code => $name ) {
			if ( ! isset( $out[ $code ] ) ) {
				$rest[ $code ] = $name;
			}
		}
		asort( $rest );
		return $out + $rest;
	}

	/** «Español (principal), English, Català» (nombres, nunca códigos). Un solo idioma: solo su nombre. */
	public static function summary_text() {
		$languages = self::languages();
		$main      = self::main_language();
		$names     = array();
		foreach ( $languages as $code => $language ) {
			$names[] = ( $code === $main && count( $languages ) > 1 )
				? $language['name'] . ' (' . __( 'principal', 'ai-knowledge' ) . ')'
				: $language['name'];
		}
		return implode( ', ', $names );
	}

	/**
	 * Migración (una sola vez): la antigua pregunta libre «Idioma principal
	 * del negocio» pasa al campo estructurado si este aún no tiene ajustes
	 * propios, y se retira de las respuestas para no duplicar la fuente. Si el
	 * texto no se reconoce, se deja como está (sin uso).
	 */
	public static function maybe_migrate_language_answer() {
		if ( get_option( 'wookb_lang_answer_migrated' ) ) {
			return;
		}
		update_option( 'wookb_lang_answer_migrated', 1, false );

		$answers = get_option( Chatbot_Prompt_Builder::ANSWERS_OPTION, array() );
		if ( ! is_array( $answers ) || empty( $answers['idioma_principal'] ) || ! is_string( $answers['idioma_principal'] ) ) {
			return;
		}

		$aliases = array(
			'es' => array( 'espanol', 'castellano', 'spanish', 'es' ),
			'ca' => array( 'catalan', 'catala', 'catalonian', 'ca' ),
			'en' => array( 'ingles', 'english', 'en' ),
			'de' => array( 'aleman', 'deutsch', 'german', 'de' ),
			'fr' => array( 'frances', 'francais', 'french', 'fr' ),
			'it' => array( 'italiano', 'italian', 'it' ),
			'pt' => array( 'portugues', 'portuguese', 'pt' ),
			'eu' => array( 'euskera', 'euskara', 'vasco', 'basque', 'eu' ),
			'gl' => array( 'gallego', 'galego', 'galician', 'gl' ),
			'nl' => array( 'neerlandes', 'holandes', 'nederlands', 'dutch', 'nl' ),
		);
		$text   = strtolower( remove_accents( $answers['idioma_principal'] ) );
		$found  = array();
		foreach ( (array) preg_split( '/[^a-z]+/', $text, -1, PREG_SPLIT_NO_EMPTY ) as $token ) {
			foreach ( $aliases as $code => $words ) {
				if ( in_array( $token, $words, true ) && ! in_array( $code, $found, true ) ) {
					$found[] = $code;
				}
			}
		}
		if ( ! $found ) {
			return;
		}

		$saved = self::saved();
		if ( empty( $saved['main'] ) && empty( $saved['languages'] ) ) {
			list( $main, $codes ) = self::normalize_language_fields( $found[0], $found );
			$saved['main']        = $main;
			$saved['languages']   = $codes;
			update_option( self::OPTION, $saved, false );
			self::flush_cache();
		}

		unset( $answers['idioma_principal'] );
		update_option( Chatbot_Prompt_Builder::ANSWERS_OPTION, $answers, false );
	}

	public static function regen_pending() {
		return (bool) get_option( self::REGEN_FLAG, 0 );
	}

	public static function clear_regen_pending() {
		delete_option( self::REGEN_FLAG );
	}

	/* ------------------------------------------------------------------
	 * Idiomas de la web
	 * ---------------------------------------------------------------- */

	/** Nombres nativos completos (tabla propia). */
	protected static function native_names() {
		return array(
			'es' => 'Español',
			'ca' => 'Català',
			'en' => 'English',
			'de' => 'Deutsch',
			'eu' => 'Euskara',
			'fr' => 'Français',
			'it' => 'Italiano',
			'pt' => 'Português',
			'nl' => 'Nederlands',
			'gl' => 'Galego',
			'pl' => 'Polski',
			'ru' => 'Русский',
			'ja' => '日本語',
			'zh' => '中文',
			'ar' => 'العربية',
			'sv' => 'Svenska',
			'da' => 'Dansk',
			'no' => 'Norsk',
			'fi' => 'Suomi',
			'el' => 'Ελληνικά',
			'tr' => 'Türkçe',
			'ro' => 'Română',
		);
	}

	/**
	 * Nombre de un idioma: primero la tabla propia; si no está, el del plugin
	 * de idiomas solo si no parece el código (WPML puede dar «Es», «En»); si
	 * tampoco, el código en mayúsculas.
	 */
	protected static function native_name( $code, $provider_name = '' ) {
		$names = self::native_names();
		if ( isset( $names[ $code ] ) ) {
			return $names[ $code ];
		}
		$provider_name = trim( (string) $provider_name );
		if ( '' !== $provider_name && mb_strlen( $provider_name ) > 3 && 0 !== strcasecmp( $provider_name, $code ) ) {
			return $provider_name;
		}
		return strtoupper( $code );
	}

	/** Idiomas detectados por el plugin de idiomas (sin tocar lo editado en Negocio). */
	public static function detected_languages() {
		$found = self::provider()->languages();
		return is_array( $found ) ? $found : array();
	}

	/**
	 * Contrato 2. Idiomas de la web: código => array( code, name, url ).
	 * Orden: lo editado en Negocio -> lo detectado -> solo el principal. El
	 * principal siempre está incluido y va primero. `url` puede ir vacía si
	 * no hay plugin que la conozca.
	 */
	public static function languages() {
		if ( null !== self::$languages_cache ) {
			return self::$languages_cache;
		}

		$main     = self::main_language();
		$detected = self::detected_languages();
		$saved    = self::saved();

		$codes = ( ! empty( $saved['languages'] ) && is_array( $saved['languages'] ) ) ? $saved['languages'] : array_keys( $detected );
		$codes = array_values( array_diff( array_map( 'strval', $codes ), array( $main ) ) );
		array_unshift( $codes, $main );

		$out = array();
		foreach ( $codes as $code ) {
			$out[ $code ] = array(
				'code' => $code,
				'name' => self::native_name( $code, isset( $detected[ $code ]['name'] ) ? $detected[ $code ]['name'] : '' ),
				'url'  => isset( $detected[ $code ]['url'] ) ? $detected[ $code ]['url'] : ( $code === $main ? home_url( '/' ) : '' ),
			);
		}

		self::$languages_cache = $out;
		return $out;
	}

	/** Solo los códigos de idioma de la web (el principal primero). */
	public static function codes() {
		return array_keys( self::languages() );
	}

	/** Nombre nativo de un idioma. */
	public static function name( $code ) {
		$languages = self::languages();
		if ( isset( $languages[ $code ] ) ) {
			return $languages[ $code ]['name'];
		}
		return self::native_name( $code );
	}

	/* ------------------------------------------------------------------
	 * Contenido
	 * ---------------------------------------------------------------- */

	/** Contrato 3. Idioma de un contenido (idioma principal si el plugin no lo sabe). */
	public static function post_language( $post_id ) {
		$lang = self::provider()->post_language( $post_id );
		return ( $lang && is_string( $lang ) ) ? $lang : self::main_language();
	}

	/**
	 * ID de la traducción real del contenido en un idioma, o null si no
	 * existe. Si el contenido ya está en ese idioma, él mismo.
	 */
	public static function translation_id( $post_id, $lang ) {
		if ( self::post_language( $post_id ) === $lang ) {
			return (int) $post_id;
		}
		$translated = self::provider()->translation_id( $post_id, $lang );
		// Grupos con filas huérfanas (traducción registrada cuyo post ya no existe).
		if ( $translated && ! get_post_status( $translated ) ) {
			return null;
		}
		return $translated;
	}

	/**
	 * Contrato 4. Original de un contenido: de una traducción, el post de
	 * origen. Con un post por idioma se toma el del idioma principal si
	 * existe; si no, el original que marque el plugin de idiomas.
	 */
	public static function original_id( $post_id ) {
		if ( self::creates_post_per_language() ) {
			$in_main = self::translation_id( $post_id, self::main_language() );
			if ( $in_main ) {
				return (int) $in_main;
			}
		}
		$candidate = (int) self::provider()->original_id( $post_id );
		if ( $candidate === (int) $post_id || 'publish' === get_post_status( $candidate ) ) {
			return $candidate;
		}
		// El original marcado ya no existe (fila huérfana): el contenido se
		// documenta con la versión que exista, siguiendo el orden de idiomas.
		foreach ( self::codes() as $code ) {
			$found = self::translation_id( $post_id, $code );
			if ( $found ) {
				return (int) $found;
			}
		}
		return (int) $post_id;
	}

	/** Contrato 5. URL de un contenido en otro idioma, o null si no existe esa versión. */
	public static function post_url( $post_id, $lang ) {
		if ( self::post_language( $post_id ) === $lang ) {
			$url = self::permalink( $post_id );
			return $url ? $url : null;
		}
		return self::provider()->post_url( $post_id, $lang );
	}

	/** URL de un contenido en SU propio idioma (no en el de la petición). */
	public static function permalink( $post_id ) {
		return self::provider()->permalink( $post_id );
	}

	/** Ejecuta un callback bajo el idioma principal (p. ej. llms.txt) y restaura el idioma de la petición. */
	public static function in_main_language( $callback ) {
		return self::provider()->with_language( self::main_language(), $callback );
	}

	/** Versiones de un contenido: idioma => URL, solo las que existen. */
	public static function versions( $post_id ) {
		$out = array();
		foreach ( self::codes() as $code ) {
			$url = self::post_url( $post_id, $code );
			if ( $url ) {
				$out[ $code ] = $url;
			}
		}
		return $out;
	}

	/**
	 * Documento (post + idioma) que le corresponde a un contenido concreto:
	 * con «Crear por idioma» ese mismo post; sin él, el original.
	 */
	public static function document_target( $post_id ) {
		if ( self::per_language_enabled() ) {
			return array( 'id' => (int) $post_id, 'lang' => self::post_language( $post_id ) );
		}
		$original = self::original_id( $post_id );
		return array( 'id' => $original, 'lang' => self::post_language( $original ) );
	}

	/**
	 * Documentos que hay que generar para un contenido: uno (el original) por
	 * defecto; con «Crear por idioma», uno por cada traducción que existe (sin
	 * documentos puente). Cada elemento: array( 'id' => post, 'lang' => idioma ).
	 */
	public static function targets( $post_id ) {
		if ( ! self::per_language_enabled() ) {
			return array( self::document_target( $post_id ) );
		}

		$original = self::original_id( $post_id );
		$own_lang = self::post_language( $original );
		$out      = array( array( 'id' => (int) $original, 'lang' => $own_lang ) );
		foreach ( self::codes() as $code ) {
			if ( $code === $own_lang ) {
				continue;
			}
			$translated = self::provider()->translation_id( $original, $code );
			if ( $translated ) {
				$out[] = array( 'id' => (int) $translated, 'lang' => $code );
			}
		}
		return $out;
	}

	/* ------------------------------------------------------------------
	 * Visitante, términos, Genix
	 * ---------------------------------------------------------------- */

	/** Idioma en el que navega el visitante ahora (plugin de idiomas o cookie), o null. */
	public static function current_language() {
		$lang = self::provider()->current_language();
		if ( $lang && is_string( $lang ) ) {
			return sanitize_key( $lang );
		}
		foreach ( self::provider()->cookie_names() as $cookie_name ) {
			if ( ! empty( $_COOKIE[ $cookie_name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$lang = sanitize_key( wp_unslash( $_COOKIE[ $cookie_name ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( $lang ) {
					return $lang;
				}
			}
		}
		return null;
	}

	/**
	 * Idioma en el que están los documentos que busca el chatbot: el del
	 * visitante si hay un documento por idioma; si no, el principal.
	 */
	public static function documents_language() {
		if ( self::per_language_enabled() ) {
			$current = self::current_language();
			if ( $current ) {
				return $current;
			}
		}
		return self::main_language();
	}

	public static function term_id_in_language( $term_id, $taxonomy, $lang ) {
		return (int) self::provider()->term_id_in_language( $term_id, $taxonomy, $lang );
	}

	public static function trid( $post_id ) {
		return self::provider()->trid( $post_id );
	}

	public static function set_document_language( $post_id, $lang, $trid = null ) {
		self::provider()->set_document_language( $post_id, $lang, $trid );
	}

	/** Argumentos de consulta para no filtrar por idioma actual (Polylang). */
	public static function all_languages_query_args() {
		return self::provider()->all_languages_query_args();
	}

	/* ------------------------------------------------------------------
	 * Apartado «Idiomas» de los .md
	 * ---------------------------------------------------------------- */

	/** Textos del apartado, redactados en el idioma del documento (inglés si no hay plantilla). */
	protected static function section_texts( $doc_lang ) {
		$texts = array(
			'es' => array( 'Idiomas', 'Disponible en' ),
			'en' => array( 'Languages', 'Available in' ),
			'de' => array( 'Sprachen', 'Verfügbar auf' ),
			'ca' => array( 'Idiomes', 'Disponible en' ),
			'fr' => array( 'Langues', 'Disponible en' ),
			'eu' => array( 'Hizkuntzak', 'Eskuragarri hemen' ),
		);
		$set = isset( $texts[ $doc_lang ] ) ? $texts[ $doc_lang ] : $texts['en'];
		return array(
			'heading'   => $set[0],
			'available' => $set[1],
		);
	}

	/**
	 * Apartado «## Idiomas» de un .md. $versions: idioma => URL ('' si no se
	 * conoce), solo las versiones que existen. Solo «Disponible en: …» con el
	 * nombre completo de cada idioma; con una sola versión no se escribe el
	 * apartado (devuelve cadena vacía).
	 */
	public static function build_section( $doc_lang, array $versions ) {
		if ( count( $versions ) <= 1 ) {
			return '';
		}

		$t     = self::section_texts( $doc_lang );
		$parts = array();
		foreach ( $versions as $code => $url ) {
			$name    = self::name( $code );
			$parts[] = $url ? '[' . $name . '](' . $url . ')' : $name;
		}
		return '## ' . $t['heading'] . "\n\n" . $t['available'] . ': ' . implode( ' · ', $parts );
	}

	/** Versiones a nivel de web (portadas), para los documentos que no son un contenido concreto. */
	public static function site_versions() {
		$out = array();
		foreach ( self::languages() as $code => $language ) {
			$out[ $code ] = $language['url'];
		}
		return $out;
	}

	/** Apartado «Idiomas» de un documento propio del plugin (FAQ, tienda, Negocio). */
	public static function site_section( $doc_lang ) {
		return self::build_section( $doc_lang, self::site_versions() );
	}

	/**
	 * Nota de idiomas para el mensaje de sistema del chatbot. Vacía si la web
	 * tiene un solo idioma.
	 */
	public static function chatbot_note() {
		$languages = self::languages();
		if ( count( $languages ) <= 1 ) {
			return '';
		}
		$names = array();
		foreach ( $languages as $language ) {
			$names[] = $language['name'];
		}
		return sprintf(
			'Idiomas: la web está disponible en %1$s (idioma principal: %2$s). Responde siempre en el idioma en que escribe el usuario. Cada documento indica en qué idiomas está disponible y con qué enlace; usa el enlace del idioma del usuario cuando exista.',
			implode( ', ', $names ),
			self::name( self::main_language() )
		);
	}

	/* ------------------------------------------------------------------
	 * Limpieza tras cambiar el check («Reiniciar todo»)
	 * ---------------------------------------------------------------- */

	/**
	 * Filas de documentos que ya no corresponden: siempre los documentos
	 * puente; los de tipos de contenido que ya no están en el alcance; y, sin
	 * «Crear por idioma», los de traducciones (queda uno por contenido, el del
	 * original). No incluye documentos propios del plugin (FAQ, tienda,
	 * Negocio), artículos de Genix ni filas en modo manual (perderían el texto
	 * escrito a mano).
	 */
	public static function obsolete_rows() {
		global $wpdb;
		$table = Registry::table();
		$rows  = $wpdb->get_results( "SELECT * FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$per   = self::per_language_enabled();
		$out   = array();

		foreach ( (array) $rows as $row ) {
			if ( ! post_type_exists( $row->source_type ) || 'sgkb-docs' === $row->source_type ) {
				continue;
			}
			if ( 'manual' === $row->override_mode ) {
				continue;
			}
			$obsolete = ! empty( $row->is_bridge ) || ! Scope::has_post_type_in_scope( $row->source_type );
			if ( ! $obsolete && ! $per ) {
				$obsolete = self::original_id( (int) $row->source_id ) !== (int) $row->source_id;
			}
			if ( $obsolete ) {
				$out[] = $row;
			}
		}
		return $out;
	}

	/** Cuántos documentos borraría «Reiniciar todo» (para la confirmación). */
	public static function obsolete_count() {
		return count( self::obsolete_rows() );
	}

	/** Borra los documentos de obsolete_rows() (.md, post de Genix y fila). Devuelve cuántos. */
	public static function cleanup_obsolete_documents() {
		$deleted = 0;
		foreach ( self::obsolete_rows() as $row ) {
			if ( $row->md_path ) {
				Markdown_Store::delete( $row->md_path );
			}
			if ( $row->doc_post_id ) {
				Genix_Bridge::delete_document( $row->doc_post_id );
			}
			Registry::delete_row( $row->id );
			$deleted++;
		}

		if ( $deleted ) {
			Llms_Txt::invalidate();
		}
		return $deleted;
	}
}
