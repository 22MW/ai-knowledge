<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sincroniza chatbot-system-prompt.md con el ajuste NATIVO de Support Genix
 * 'chatbot_custom_instructions'. Esta clase NO toca ningun archivo de Genix:
 * escribe la opcion con su propia API publica. Ojo: Genix Lite no trae de
 * fabrica el bloque que LEE esa opcion (Pro si); por eso, en Lite, el prompt
 * solo se aplica si Genix_Hooks_Guard ha parcheado el archivo del trait de
 * Genix (ver ese guard: avisa cuando el parche falta y permite reinstalarlo).
 *
 * Genix arma el system prompt del chatbot en Apbd_wps_knowledge_base_chatquery_trait.php,
 * metodo build_chatbot_system_prompt(): tiene un bloque fijo de reglas anti-alucinacion
 * que no se puede tocar (y no queremos tocarlo), y bajo el encabezado "## Additional
 * instructions" anexa textualmente la opcion 'chatbot_custom_instructions' si no esta vacia
 * (linea ~1133: $custom_instructions = trim($this->GetOption('chatbot_custom_instructions', ''))).
 * Esa opcion es un ajuste nativo del propio Genix pensado exactamente para esto. Sin embargo,
 * Genix Lite no incluye el bloque que la lee: sin el parche de Genix_Hooks_Guard el prompt se
 * guarda pero el chatbot de Lite no lo usa. Los filtros de Chatbot_Relevance_Guard tambien
 * dependen de ese parche.
 *
 * Persistencia: Apbd_wps_knowledge_base extiende ApbdWpsBaseModule, que expone
 * el metodo publico y estatico GetModuleInstance() (linea ~145 de ApbdWpsBaseModule.php)
 * para obtener la instancia viva del modulo ya cargado por Genix, y el metodo publico de
 * instancia AddOption($key, $value) (linea ~941) que actualiza la opcion en memoria y la
 * persiste con su propio UpdateOption(). Todo API publica de Genix, ninguna es un hook
 * documentado oficialmente pero ambas son metodos publicos reales invocables desde fuera.
 */
class Chatbot_Prompt {

	const GENIX_CLASS  = '\\Apbd_wps_knowledge_base';
	const GENIX_OPTION = 'chatbot_custom_instructions';
	const SYNCED_HASH_OPTION = 'wookb_chatbot_prompt_synced_hash';

	/** Espejo en base de datos: la carpeta del plugin se borra al actualizarlo. */
	const TEXT_OPTION = 'wookb_chatbot_prompt_text';

	/**
	 * Carpeta PRIVADA dentro del plugin (con index.php): el prompt del chatbot
	 * nunca vive en una carpeta servida por URL (antes: wp-content/ai-knowledge/
	 * y wp-content/llm/, accesibles directamente).
	 */
	public static function private_dir() {
		return AIKB_DIR . 'privado';
	}

	public static function file_path() {
		return self::private_dir() . '/chatbot-system-prompt.md';
	}

	/** Rutas antiguas (publica y la mas antigua en la raiz del plugin), solo para migrar. */
	protected static function old_file_paths() {
		$paths = array(
			Markdown_Store::base_dir() . '/chatbot-system-prompt.md',
			Markdown_Store::legacy_dir() . '/chatbot-system-prompt.md',
			AIKB_DIR . 'chatbot-system-prompt.md',
		);
		return array_unique( $paths );
	}

	/** Escribe el archivo privado (crea la carpeta y su index.php). false si no se puede. */
	protected static function write_file( $text ) {
		$dir = self::private_dir();
		if ( ! is_dir( $dir ) && ! @wp_mkdir_p( $dir ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return false;
		}
		if ( ! file_exists( $dir . '/index.php' ) ) {
			@file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_put_contents
		}
		return false !== @file_put_contents( self::file_path(), trim( (string) $text ) . "\n" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_put_contents
	}

	/**
	 * Unico punto de escritura del prompt: archivo privado + espejo en la
	 * base de datos. Devuelve true si el archivo se pudo escribir (la opcion
	 * se guarda siempre).
	 */
	public static function save( $text ) {
		update_option( self::TEXT_OPTION, trim( (string) $text ), false );
		return self::write_file( $text );
	}

	/**
	 * Importa el prompt desde las ubicaciones antiguas (idempotente) y BORRA el
	 * archivo antiguo, que es el que estaba expuesto. Si no se puede escribir
	 * en privado/, no borra nada y queda solo la opcion como respaldo.
	 */
	public static function migrate() {
		foreach ( self::old_file_paths() as $old ) {
			if ( ! is_file( $old ) || is_link( $old ) ) {
				continue;
			}
			$content = trim( (string) file_get_contents( $old ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( '' !== $content ) {
				if ( '' === trim( (string) get_option( self::TEXT_OPTION, '' ) ) ) {
					update_option( self::TEXT_OPTION, $content, false );
				}
				if ( ! file_exists( self::file_path() ) && ! self::write_file( $content ) ) {
					continue; // sin permisos: se conserva el antiguo, la opcion sirve de respaldo.
				}
			} elseif ( ! is_writable( $old ) ) {
				continue;
			}
			unlink( $old ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		}
	}

	public static function read() {
		$path = self::file_path();
		if ( file_exists( $path ) ) {
			return trim( (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		}

		// Sin archivo (p. ej. tras actualizar el plugin): espejo de la BD, y se recrea el archivo.
		$mirror = trim( (string) get_option( self::TEXT_OPTION, '' ) );
		if ( '' !== $mirror ) {
			self::write_file( $mirror );
			return $mirror;
		}

		// Ubicaciones antiguas.
		self::migrate();
		if ( file_exists( $path ) ) {
			return trim( (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		}
		return trim( (string) get_option( self::TEXT_OPTION, '' ) );
	}

	/**
	 * Añade al mensaje de sistema la nota de idiomas (responder en el idioma
	 * del usuario y disponibilidad de cada documento en otros idiomas). Solo
	 * con varios idiomas, y solo si el resultado cabe en el limite de
	 * Chatbot_Prompt_Builder::MAX_LENGTH (limite duro del sistema que lo
	 * recibe): si no cabe, el mensaje se envia tal cual, sin la nota. No se
	 * escribe en el .md editable, solo en lo que se sincroniza con Genix.
	 */
	protected static function with_languages_note( $content ) {
		$note = Languages::chatbot_note();
		if ( '' === $note ) {
			return $content;
		}
		$combined = $content . "\n" . $note;
		return mb_strlen( $combined ) <= Chatbot_Prompt_Builder::MAX_LENGTH ? $combined : $content;
	}

	/**
	 * ¿Existe el modulo de Genix y hay una instancia viva cargada?
	 */
	public static function is_genix_ready() {
		if ( ! class_exists( self::GENIX_CLASS ) ) {
			return false;
		}
		if ( ! method_exists( self::GENIX_CLASS, 'GetModuleInstance' ) ) {
			return false;
		}
		$instance = call_user_func( array( self::GENIX_CLASS, 'GetModuleInstance' ) );
		return ! empty( $instance );
	}

	/**
	 * ¿El .md actual está sincronizado con Genix? Se basa en el hash guardado
	 * al sincronizar (SYNCED_HASH_OPTION), no en que el archivo exista.
	 */
	public static function is_synced() {
		$content = self::read();
		if ( '' === $content || ! self::is_genix_ready() ) {
			return false;
		}
		$content = self::with_languages_note( $content );
		return hash_equals( (string) get_option( self::SYNCED_HASH_OPTION, '' ), md5( $content ) );
	}

	protected static function instance() {
		return call_user_func( array( self::GENIX_CLASS, 'GetModuleInstance' ) );
	}

	/**
	 * Sincroniza el .md hacia el ajuste de Genix si hay cambios (o si $force).
	 * Devuelve array [ 'status' => synced|skipped|unavailable|empty, 'message' => string ].
	 */
	public static function sync( $force = false ) {
		$content = self::read();
		if ( '' === $content ) {
			return array( 'status' => 'empty', 'message' => __( 'chatbot-system-prompt.md está vacío o no existe.', 'ai-knowledge' ) );
		}
		$content = self::with_languages_note( $content );

		if ( ! self::is_genix_ready() ) {
			return array( 'status' => 'unavailable', 'message' => __( 'Support Genix no está activo o su módulo de Knowledge Base aún no se ha cargado.', 'ai-knowledge' ) );
		}

		$hash = md5( $content );
		$synced_hash = get_option( self::SYNCED_HASH_OPTION, '' );

		$instance = self::instance();
		$current_genix_value = trim( (string) $instance->GetOption( self::GENIX_OPTION, '' ) );

		// Si el ajuste de Genix ya coincide y no hay force, no hacer nada (evita
		// escrituras de opcion en cada carga de admin_init).
		if ( ! $force && $hash === $synced_hash && $current_genix_value === $content ) {
			return array( 'status' => 'skipped', 'message' => __( 'Ya estaba sincronizado.', 'ai-knowledge' ) );
		}

		$ok = $instance->AddOption( self::GENIX_OPTION, $content );

		if ( false === $ok ) {
			// update_option() devuelve false tambien cuando el valor NO cambia:
			// se relee y solo es error si de verdad no coincide.
			$after = trim( (string) $instance->GetOption( self::GENIX_OPTION, '' ) );
			if ( $after !== $content ) {
				return array( 'status' => 'error', 'message' => __( 'Genix rechazó guardar la opción (AddOption devolvió false).', 'ai-knowledge' ) );
			}
			update_option( self::SYNCED_HASH_OPTION, $hash, false );
			return array( 'status' => $force ? 'synced' : 'skipped', 'message' => $force ? __( 'Prompt sincronizado con Support Genix.', 'ai-knowledge' ) : __( 'Ya estaba sincronizado.', 'ai-knowledge' ) );
		}

		update_option( self::SYNCED_HASH_OPTION, $hash, false );

		return array( 'status' => 'synced', 'message' => __( 'Prompt sincronizado con Support Genix.', 'ai-knowledge' ) );
	}

	/**
	 * Intento automatico y silencioso en cada carga de admin. No falla nunca
	 * ruidosamente: si Genix no esta listo todavia, simplemente no hace nada.
	 * El boton manual "Sincronizar prompt" en Ajustes es el mecanismo fiable
	 * de respaldo.
	 */
	public static function maybe_auto_sync() {
		if ( ! self::is_genix_ready() ) {
			return;
		}
		self::sync( false );
	}
}
