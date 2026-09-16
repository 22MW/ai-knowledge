<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bloqueo real de crawlers via robots.txt (Fase 11, revision UX 2026-09-16).
 * Mismo patron que Htaccess_Guard: deliberadamente NO registra ningun
 * admin_post aqui, eso vive en Admin (verify()/redirect()) -- esta clase solo
 * encapsula la logica de archivo.
 *
 * A diferencia de .htaccess, robots.txt siempre debe poder aplicarse: si el
 * archivo fisico no existe todavia (caso normal, WordPress sirve una version
 * virtual), is_available() comprueba que la carpeta raiz sea escribible para
 * poder crearlo; si ya existe, comprueba el archivo en si.
 *
 * Transient de backup confirmado propio y separado del de Htaccess_Guard:
 * son archivos distintos, cada uno con su propia confirmacion de descarga.
 */
class Robots_Txt_Guard {

	const TRANSIENT_PREFIX = 'wookb_robots_backup_confirmed_';
	const MARKER           = 'AI Knowledge crawlers';

	public static function path() {
		return ABSPATH . 'robots.txt';
	}

	/**
	 * Si el archivo ya existe, hace falta que sea escribible. Si todavia no
	 * existe, hace falta que la carpeta raiz lo sea (para poder crearlo).
	 */
	public static function is_available() {
		$path = self::path();
		if ( file_exists( $path ) ) {
			return is_writable( $path );
		}
		return is_writable( ABSPATH );
	}

	protected static function transient_key() {
		return self::TRANSIENT_PREFIX . get_current_user_id();
	}

	public static function mark_backup_confirmed() {
		set_transient( self::transient_key(), 1, 10 * MINUTE_IN_SECONDS );
	}

	public static function backup_confirmed() {
		return (bool) get_transient( self::transient_key() );
	}

	public static function clear_backup_confirmation() {
		delete_transient( self::transient_key() );
	}

	/**
	 * Un bloque `User-agent: X` + `Disallow: /` por bot, con linea en blanco
	 * entre bloques -- formato estandar de robots.txt.
	 *
	 * @param string[] $bot_user_agents Tokens de user_agent del catalogo.
	 */
	public static function build_rules( array $bot_user_agents ) {
		$lines = array();
		foreach ( $bot_user_agents as $ua ) {
			$ua = trim( (string) $ua );
			if ( '' === $ua ) {
				continue;
			}
			if ( ! empty( $lines ) ) {
				$lines[] = '';
			}
			$lines[] = 'User-agent: ' . $ua;
			$lines[] = 'Disallow: /';
		}
		return $lines;
	}

	/**
	 * Aplica el bloque via insert_with_markers() (nativa de WordPress). Si el
	 * archivo fisico no existe todavia, insert_with_markers() lo crea. El
	 * marcador propio evita pisar reglas de otros plugins, y permite quitar
	 * el bloque limpiamente si se desactiva.
	 */
	public static function apply_block( array $bot_user_agents ) {
		$rules = self::build_rules( $bot_user_agents );
		if ( empty( $rules ) ) {
			return false;
		}
		return insert_with_markers( self::path(), self::MARKER, $rules );
	}
}
