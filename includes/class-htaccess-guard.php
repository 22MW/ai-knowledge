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
		$blocked = array();
		foreach ( $bot_user_agents as $ua ) {
			$ua = trim( (string) $ua );
			if ( '' !== $ua ) { $blocked[] = $ua; }
		}
		return self::build_grouped_rules( $blocked, $visibility_mode );
	}

	public static function build_action_rules( array $actions, $visibility_mode = 'site' ) {
		$blocked = array();
		foreach ( $actions as $ua => $action ) {
			if ( 'allow' === $action ) { continue; }
			$ua = trim( (string) $ua );
			if ( '' !== $ua ) { $blocked[] = $ua; }
		}
		$lines = self::build_grouped_rules( $blocked, $visibility_mode );
		return $lines;
	}

	/** Agrupa bots con el mismo comportamiento en una cadena OR compacta. */
	protected static function build_grouped_rules( array $bot_user_agents, $visibility_mode ) {
		$conditions = array();
		$last = count( $bot_user_agents ) - 1;
		foreach ( array_values( $bot_user_agents ) as $index => $ua ) {
			$flags = $index < $last ? '[NC,OR]' : '[NC]';
			$conditions[] = 'RewriteCond %{HTTP_USER_AGENT} "' . preg_quote( $ua, '/' ) . '" ' . $flags;
		}
		if ( empty( $conditions ) ) { return array(); }
		if ( 'llms_only' === $visibility_mode ) {
			return array_merge(
				$conditions,
				array( 'RewriteRule ^llms\\.txt$ - [L]' ),
				$conditions,
				array( 'RewriteRule ^ - [R=404,L]' )
			);
		}
		return array_merge( $conditions, array( 'RewriteRule ^ - [R=404,L]' ) );
	}

	public static function apply_actions( array $actions, $visibility_mode = 'site' ) {
		self::comment_action_conflicts( $actions );
		$rules = self::build_action_rules( $actions, $visibility_mode );
		array_unshift( $rules, 'RewriteEngine On' );
		$result = insert_with_markers( self::path(), self::MARKER, $rules );
		return $result ? self::move_block_before_wordpress() : false;
	}

	public static function managed_block_matches( $content, array $actions, $visibility_mode = 'site' ) {
		$pattern = '/# BEGIN ' . preg_quote( self::MARKER, '/' ) . '\R(.*?)\R# END ' . preg_quote( self::MARKER, '/' ) . '/s';
		if ( ! preg_match( $pattern, (string) $content, $match ) ) { return false; }
		$current_lines = preg_split( '/\R/', trim( $match[1] ) );
		$current_lines = array_values( array_filter( $current_lines, function ( $line ) { return '' !== trim( $line ) && '#' !== substr( trim( $line ), 0, 1 ); } ) );
		$current = implode( "\n", $current_lines );
		$expected_lines = array_values( array_filter( array_merge( array( 'RewriteEngine On' ), self::build_action_rules( $actions, $visibility_mode ) ), function ( $line ) { return '' !== trim( $line ); } ) );
		$expected = implode( "\n", $expected_lines );
		return hash_equals( $expected, $current );
	}

	/** Coloca el bloque propio antes de WordPress para que sus reglas se evalúen primero. */
	protected static function move_block_before_wordpress() {
		$path = self::path();
		$raw = (string) file_get_contents( $path ); // phpcs:ignore
		$pattern = '/\R?# BEGIN ' . preg_quote( self::MARKER, '/' ) . '.*?# END ' . preg_quote( self::MARKER, '/' ) . '\R?/s';
		if ( ! preg_match( $pattern, $raw, $match ) ) { return false; }
		$without = preg_replace( $pattern, "\n", $raw, 1 );
		$block = trim( $match[0] );
		if ( preg_match( '/^# BEGIN WordPress$/mi', $without, $wp_match, PREG_OFFSET_CAPTURE ) ) {
			$offset = $wp_match[0][1];
			$raw = rtrim( substr( $without, 0, $offset ) ) . "\n\n" . $block . "\n\n" . ltrim( substr( $without, $offset ) );
		} else {
			$raw = rtrim( $without ) . "\n\n" . $block . "\n";
		}
		return false !== file_put_contents( $path, $raw, LOCK_EX ); // phpcs:ignore
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
		if ( preg_match( '/^# BEGIN WordPress$/mi', $original, $match, PREG_OFFSET_CAPTURE ) ) {
			$offset = $match[0][1];
			return rtrim( substr( $original, 0, $offset ) ) . "\n\n" . $block . "\n\n" . ltrim( substr( $original, $offset ) );
		}
		return rtrim( $original ) . "\n\n" . $block . "\n";
	}

	public static function conflicts( array $bot_user_agents, $visibility_mode = 'site' ) {
		if ( ! file_exists( self::path() ) ) {
			return array();
		}
		$lines = preg_split( '/\r\n|\r|\n/', (string) file_get_contents( self::path() ) ); // phpcs:ignore
		$found  = array();
		$inside = false;
		$count  = count( $lines );
		// Bucle for con bandera $inside (mismo patron que comment_action_conflicts()
		// arriba): en un foreach, reasignar la variable de indice no salta lineas
		// -- PHP no respeta ese salto, sigue avanzando una a una por el puntero
		// interno. Con foreach, el bloque propio del plugin (# BEGIN ... # END)
		// se recorria igual que el resto del archivo y sus propias reglas se
		// marcaban como "conflicto" contra si mismas (bug real confirmado).
		for ( $index = 0; $index < $count; $index++ ) {
			$line = $lines[ $index ];
			if ( 0 === strpos( trim( $line ), '# BEGIN ' . self::MARKER ) ) {
				$inside = true;
				continue;
			}
			if ( 0 === strpos( trim( $line ), '# END ' . self::MARKER ) ) {
				$inside = false;
				continue;
			}
			if ( $inside ) {
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
