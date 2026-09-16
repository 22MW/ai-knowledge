<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bloqueo real de crawlers via .htaccess (Fase 11, pieza 5). Deliberadamente
 * NO registra ningun admin_post aqui: eso vive en Admin (mismo patron
 * verify()/redirect() que el resto del plugin) para no duplicar el checkeo
 * de nonce/capability -- esta clase solo encapsula la logica de archivo.
 *
 * Requisito de seguridad explicito del usuario: descarga obligatoria de la
 * copia actual antes de poder aplicar el bloqueo, verificada en servidor
 * (transient de uso unico), no solo deshabilitando el boton en el HTML.
 */
class Htaccess_Guard {

	const TRANSIENT_PREFIX = 'wookb_htaccess_backup_confirmed_';
	const MARKER           = 'AI Knowledge crawlers';

	public static function path() {
		return ABSPATH . '.htaccess';
	}

	/**
	 * Solo true si el archivo existe Y es escribible. En nginx (sin .htaccess
	 * real) o con permisos que lo impiden, la UI debe ofrecer el bloque de
	 * codigo para pegar a mano en vez de ocultar la funcion (decision del
	 * roadmap: nunca ocultar, siempre avisar).
	 */
	public static function is_available() {
		$path = self::path();
		return file_exists( $path ) && is_writable( $path );
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
	 * Reglas RewriteCond/RewriteRule para el conjunto de bots a bloquear,
	 * una condicion + una regla por bot (no una condicion OR gigante) para
	 * que cada bot sea facil de identificar/quitar a mano si hiciera falta.
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
			$lines[] = 'RewriteCond %{HTTP_USER_AGENT} "' . $ua . '" [NC]';
			$lines[] = 'RewriteRule .* - [F,L]';
		}
		return $lines;
	}

	/**
	 * Aplica el bloque via insert_with_markers() (nativa de WordPress, ya
	 * disponible sin require: se carga en wp-admin, y las acciones que llaman
	 * a este metodo son siempre admin_post, dentro de wp-admin). El marcador
	 * propio evita pisar reglas de otros plugins o de WordPress core, y
	 * permite quitar el bloque limpiamente si se desactiva.
	 */
	public static function apply_block( array $bot_user_agents ) {
		$rules = self::build_rules( $bot_user_agents );
		if ( empty( $rules ) ) {
			return false;
		}
		// RewriteEngine On es necesario si el bloque va antes de las reglas de
		// WordPress; insert_with_markers no lo añade por si solo.
		array_unshift( $rules, 'RewriteEngine On' );
		return insert_with_markers( self::path(), self::MARKER, $rules );
	}
}
