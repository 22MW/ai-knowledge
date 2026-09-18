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
	public static function build_rules( array $bot_user_agents, $visibility_mode = 'site' ) {
		$lines = array();
		foreach ( $bot_user_agents as $ua ) {
			$ua = trim( (string) $ua );
			if ( '' === $ua ) {
				continue;
			}
			if ( 'llms_only' === $visibility_mode ) {
				$lines[] = 'RewriteCond %{HTTP_USER_AGENT} "' . $ua . '" [NC]';
				$lines[] = 'RewriteRule ^llms\\.txt$ - [L]';
				$lines[] = 'RewriteCond %{HTTP_USER_AGENT} "' . $ua . '" [NC]';
				$lines[] = 'RewriteRule ^ - [R=404,L]';
			} else {
				$lines[] = 'RewriteCond %{HTTP_USER_AGENT} "' . $ua . '" [NC]';
				$lines[] = 'RewriteRule .* - [F,L]';
			}
		}
		return $lines;
	}

	public static function build_action_rules( array $actions, $visibility_mode = 'site' ) {
		$lines = array();
		foreach ( $actions as $ua => $action ) {
			if ( 'allow' === $action ) { continue; }
			$ua = trim( (string) $ua );
			if ( 'llms_only' === $visibility_mode ) { $lines[] = 'RewriteCond %{HTTP_USER_AGENT} "' . $ua . '" [NC]'; $lines[] = 'RewriteRule ^llms\\.txt$ - [L]'; }
			$lines[] = 'RewriteCond %{HTTP_USER_AGENT} "' . $ua . '" [NC]';
			$lines[] = 'RewriteRule ^ - [R=404,L]';
		}
		return $lines;
	}

	public static function apply_actions( array $actions, $visibility_mode = 'site' ) {
		self::comment_action_conflicts( $actions );
		$rules = self::build_action_rules( $actions, $visibility_mode );
		array_unshift( $rules, 'RewriteEngine On' );
		return insert_with_markers( self::path(), self::MARKER, $rules );
	}

	protected static function comment_action_conflicts( array $actions ) {
		$path = self::path();
		if ( ! file_exists( $path ) || ! is_writable( $path ) ) { return; }
		$lines = preg_split( '/\r\n|\r|\n/', (string) file_get_contents( $path ) ); // phpcs:ignore
		$out = array(); $inside = false; $count = count( $lines );
		for ( $i = 0; $i < $count; $i++ ) {
			$line = $lines[ $i ];
			if ( 0 === strpos( trim( $line ), '# BEGIN ' . self::MARKER ) ) { $inside = true; $out[] = $line; continue; }
			if ( 0 === strpos( trim( $line ), '# END ' . self::MARKER ) ) { $inside = false; $out[] = $line; continue; }
			if ( $inside || false === stripos( $line, 'HTTP_USER_AGENT' ) ) { $out[] = $line; continue; }
			$matched_ua = null;
			foreach ( $actions as $ua => $action ) {
				if ( 'allow' === $action && false !== stripos( $line, $ua ) ) { $matched_ua = $ua; break; }
			}
			if ( null !== $matched_ua && isset( $lines[ $i + 1 ] ) && false !== stripos( $lines[ $i + 1 ], 'RewriteRule' ) && false !== stripos( $lines[ $i + 1 ], '[F' ) ) {
				$out[] = '# AI Knowledge conflict: regla original conservada y desactivada';
				$out[] = '# ' . $line;
				$out[] = '# ' . $lines[ ++$i ];
				continue;
			}
			$out[] = $line;
		}
		file_put_contents( $path, implode( "\n", $out ), LOCK_EX ); // phpcs:ignore
	}

	/**
	 * Aplica el bloque via insert_with_markers() (nativa de WordPress, ya
	 * disponible sin require: se carga en wp-admin, y las acciones que llaman
	 * a este metodo son siempre admin_post, dentro de wp-admin). El marcador
	 * propio evita pisar reglas de otros plugins o de WordPress core, y
	 * permite quitar el bloque limpiamente si se desactiva.
	 */
	public static function apply_block( array $bot_user_agents, $visibility_mode = 'site' ) {
		$rules = self::build_rules( $bot_user_agents, $visibility_mode );
		self::comment_conflicts( $bot_user_agents, $visibility_mode );
		// Un bloque vacio elimina solo el bloque del plugin y conserva el resto.
		// RewriteEngine On es necesario si el bloque va antes de las reglas de
		// WordPress; insert_with_markers no lo añade por si solo.
		array_unshift( $rules, 'RewriteEngine On' );
		return insert_with_markers( self::path(), self::MARKER, $rules );
	}

	public static function generate_full_file( array $bot_user_agents, $visibility_mode = 'site' ) {
		$path = self::path();
		$original = file_exists( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore
		$original = preg_replace( '/\R?# BEGIN ' . preg_quote( self::MARKER, '/' ) . '.*?# END ' . preg_quote( self::MARKER, '/' ) . '\R?/s', "\n", $original );
		foreach ( $bot_user_agents as $ua => $action ) {
			if ( 'allow' !== $action ) { continue; }
			$quoted_ua = preg_quote( $ua, '/' );
			$original = preg_replace( '/^(RewriteCond %\{HTTP_USER_AGENT\} "' . $quoted_ua . '" \[NC\])$(\R)(RewriteRule .*\[F[^\r\n]*\])$/mi', '# AI Knowledge conflict: regla original conservada y desactivada$2# $1$2# $3', $original );
		}
		$rules = self::build_action_rules( $bot_user_agents, $visibility_mode );
		if ( empty( $rules ) ) {
			return rtrim( $original ) . "\n";
		}
		$block = "# BEGIN " . self::MARKER . "\n" . implode( "\n", array_merge( array( 'RewriteEngine On' ), $rules ) ) . "\n# END " . self::MARKER;
		return rtrim( $original ) . "\n\n" . $block . "\n";
	}

	public static function conflicts( array $bot_user_agents, $visibility_mode = 'site' ) {
		if ( ! file_exists( self::path() ) ) {
			return array();
		}
		$lines = preg_split( '/\r\n|\r|\n/', (string) file_get_contents( self::path() ) ); // phpcs:ignore
		$found = array();
		foreach ( $lines as $index => $line ) {
			if ( preg_match( '/^# BEGIN ' . preg_quote( self::MARKER, '/' ) . '/', trim( $line ) ) ) {
				$end = $index;
				while ( isset( $lines[ $end ] ) && false === strpos( $lines[ $end ], '# END ' . self::MARKER ) ) { $end++; }
				$index = $end;
				continue;
			}
			foreach ( $bot_user_agents as $ua ) {
				if ( false === stripos( $line, 'HTTP_USER_AGENT' ) || false === stripos( $line, $ua ) ) {
					continue;
				}
				$next = isset( $lines[ $index + 1 ] ) ? $lines[ $index + 1 ] : '';
				if ( 'llms_only' === $visibility_mode && false === stripos( $next, 'llms' ) ) {
					$found[] = trim( $line );
				}
				if ( 'site' === $visibility_mode && false !== stripos( $next, 'llms' ) ) {
					$found[] = trim( $line );
				}
			}
		}
		return array_values( array_unique( $found ) );
	}

	protected static function comment_conflicts( array $bot_user_agents, $visibility_mode ) {
		$path = self::path();
		if ( ! file_exists( $path ) || ! is_writable( $path ) ) {
			return;
		}
		$conflicts = self::conflicts( $bot_user_agents, $visibility_mode );
		if ( empty( $conflicts ) ) {
			return;
		}
		$raw = (string) file_get_contents( $path ); // phpcs:ignore
		foreach ( $conflicts as $line ) {
			$quoted = preg_quote( $line, '/' );
			$replacement = '# AI Knowledge conflict: regla original conservada y desactivada por contradicción\n# $1';
			$raw = preg_replace( '/^(' . $quoted . ')$(\R)([^#\r\n]+)$/m', $replacement . '$2# $3', $raw, 1 );
		}
		file_put_contents( $path, $raw, LOCK_EX ); // phpcs:ignore
	}
}
