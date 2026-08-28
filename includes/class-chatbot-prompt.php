<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sincroniza chatbot-system-prompt.md con el ajuste NATIVO de Support Genix
 * 'chatbot_custom_instructions'. NO toca ningun archivo de Genix: usa su
 * propia API publica.
 *
 * Genix arma el system prompt del chatbot en Apbd_wps_knowledge_base_chatquery_trait.php,
 * metodo build_chatbot_system_prompt(): tiene un bloque fijo de reglas anti-alucinacion
 * que no se puede tocar (y no queremos tocarlo), y bajo el encabezado "## Additional
 * instructions" anexa textualmente la opcion 'chatbot_custom_instructions' si no esta vacia
 * (linea ~1133: $custom_instructions = trim($this->GetOption('chatbot_custom_instructions', ''))).
 * Esa opcion es un ajuste nativo del propio Genix pensado exactamente para esto -- no hace
 * falta ningun filtro ni parche.
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

	public static function file_path() {
		return WOOKB_DIR . 'chatbot-system-prompt.md';
	}

	public static function read() {
		$path = self::file_path();
		if ( ! file_exists( $path ) ) {
			return '';
		}
		return trim( (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
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
			return array( 'status' => 'empty', 'message' => __( 'chatbot-system-prompt.md está vacío o no existe.', 'woo-kb-generator' ) );
		}

		if ( ! self::is_genix_ready() ) {
			return array( 'status' => 'unavailable', 'message' => __( 'Support Genix no está activo o su módulo de Knowledge Base aún no se ha cargado.', 'woo-kb-generator' ) );
		}

		$hash = md5( $content );
		$synced_hash = get_option( self::SYNCED_HASH_OPTION, '' );

		$instance = self::instance();
		$current_genix_value = trim( (string) $instance->GetOption( self::GENIX_OPTION, '' ) );

		// Si el ajuste de Genix ya coincide y no hay force, no hacer nada (evita
		// escrituras de opcion en cada carga de admin_init).
		if ( ! $force && $hash === $synced_hash && $current_genix_value === $content ) {
			return array( 'status' => 'skipped', 'message' => __( 'Ya estaba sincronizado.', 'woo-kb-generator' ) );
		}

		$ok = $instance->AddOption( self::GENIX_OPTION, $content );

		if ( false === $ok ) {
			return array( 'status' => 'error', 'message' => __( 'Genix rechazó guardar la opción (AddOption devolvió false).', 'woo-kb-generator' ) );
		}

		update_option( self::SYNCED_HASH_OPTION, $hash, false );

		return array( 'status' => 'synced', 'message' => __( 'Prompt sincronizado con Support Genix.', 'woo-kb-generator' ) );
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
