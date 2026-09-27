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
	 * Reglas RewriteCond/RewriteRule para los bots marcados "Bloqueado" del
	 * catalogo (los "Permitido" no generan regla propia: no se les bloquea).
	 *
	 * @param array $actions Mapa user_agent => 'allow'|'block'.
	 */
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
			// RewriteCond solo se aplica a la RewriteRule inmediatamente
			// siguiente: hace falta repetir las condiciones antes de cada
			// excepcion (llms.txt, documentos .md) y antes del bloqueo final.
			// No solo /llms.txt (pedido explicito del usuario, 2026-09-27):
			// tambien /ai-knowledge-doc/, los documentos .md que sirve el
			// plugin -- el objetivo es que no rastreen el sitio real, no que
			// no puedan leer nada sobre el.
			return array_merge(
				$conditions,
				array( 'RewriteRule ^llms\\.txt$ - [L]' ),
				$conditions,
				array( 'RewriteRule ^ai-knowledge-doc/ - [L]' ),
				$conditions,
				array( 'RewriteRule ^ - [R=404,L]' )
			);
		}
		return array_merge( $conditions, array( 'RewriteRule ^ - [R=404,L]' ) );
	}

	public static function apply_actions( array $actions, $visibility_mode = 'site' ) {
		$path = self::path();
		if ( file_exists( $path ) && is_writable( $path ) ) { // phpcs:ignore
			$raw     = (string) file_get_contents( $path ); // phpcs:ignore
			$updated = self::comment_all_conflicts_in_content( $raw, $actions, $visibility_mode );
			if ( $updated !== $raw ) {
				file_put_contents( $path, $updated, LOCK_EX ); // phpcs:ignore
			}
		}
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

	public static function generate_full_file( array $bot_user_agents, $visibility_mode = 'site' ) {
		$path = self::path();
		$original = file_exists( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore
		$original = preg_replace( '/\R?# BEGIN ' . preg_quote( self::MARKER, '/' ) . '.*?# END ' . preg_quote( self::MARKER, '/' ) . '\R?/s', "\n", $original );
		$original = self::comment_all_conflicts_in_content( $original, $bot_user_agents, $visibility_mode );
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

	/**
	 * Unica logica de deteccion de conflictos (bug real confirmado
	 * 2026-09-27: existian dos copias distintas -- esta y otra dentro de
	 * generate_full_file(), que solo sabia gestionar el caso "permitir" y
	 * dejaba la vista previa/descarga sin reflejar el caso "bloquear un bot
	 * que un tercero ya bloquea", el mas comun). Opera sobre CONTENIDO en
	 * memoria, no sobre el archivo: la usa conflicts() (para el aviso de la
	 * pestaña) y comment_all_conflicts_in_content() (usada tanto por
	 * apply_actions(), archivo real, como por generate_full_file(), vista
	 * previa y descarga).
	 */
	protected static function conflicts_in_content( $content, array $bot_user_agents, $visibility_mode = 'site' ) {
		$lines  = preg_split( '/\r\n|\r|\n/', (string) $content );
		$found  = array();
		$inside = false;
		$count  = count( $lines );
		// Bucle for con bandera $inside: en un foreach, reasignar la variable
		// de indice no salta lineas -- PHP no respeta ese salto, sigue
		// avanzando una a una por el puntero interno. Con foreach, el bloque
		// propio del plugin (# BEGIN ... # END) se recorria igual que el
		// resto del archivo y sus propias reglas se marcaban como
		// "conflicto" contra si mismas (bug real confirmado).
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
			// Bug real confirmado (2026-09-27): una linea ya comentada (empieza
			// por #, por ejemplo tras una aplicacion anterior) seguia
			// contando como conflicto -- stripos() encuentra igual el texto
			// dentro del comentario, y el aviso nunca desaparecia.
			if ( 0 === strpos( ltrim( $line ), '#' ) ) {
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

	public static function conflicts( array $bot_user_agents, $visibility_mode = 'site' ) {
		if ( ! file_exists( self::path() ) ) {
			return array();
		}
		return self::conflicts_in_content( (string) file_get_contents( self::path() ), $bot_user_agents, $visibility_mode ); // phpcs:ignore
	}

	/**
	 * Comenta cada linea en conflicto UNA A UNA (no la linea siguiente): un
	 * bloque de terceros como un antiescaner con muchos bots encadenados por
	 * "OR" (RewriteCond ... [NC,OR]) terminando en una unica RewriteRule no
	 * tiene la forma "condicion + su propia regla" que asumia la version
	 * anterior -- esa version comentaba la linea en conflicto MAS LA
	 * SIGUIENTE, que en un encadenado largo es simplemente la condicion del
	 * SIGUIENTE bot (no relacionado), dejandola comentada por error (bug real
	 * confirmado 2026-09-27: se vio comentar la linea de PetalBot sin que
	 * apareciera en el aviso). Comentar una sola RewriteCond de en medio de la
	 * cadena es seguro: Apache simplemente la ignora al parsear (un comentario
	 * no es una directiva) y sigue evaluando el resto de condiciones
	 * encadenadas con "OR" con normalidad.
	 */
	protected static function comment_lines_in_content( $content, array $lines_to_comment ) {
		$raw = (string) $content;
		foreach ( $lines_to_comment as $line ) {
			$quoted = preg_quote( $line, '/' );
			// $2 conserva el salto de linea real que ya captura (\R) -- antes
			// aqui habia un "\n" literal dentro de comillas simples (bug real
			// confirmado): no es un salto de linea en PHP, son los caracteres
			// "\" y "n", asi que el aviso y la regla quedaban pegados en una
			// sola linea rara en vez de dos lineas limpias.
			$raw = preg_replace(
				'/^([ \t]*)(' . $quoted . ')$/m',
				'$1# AI Knowledge conflict: regla original conservada y desactivada por contradicción' . "\n" . '$1# $2',
				$raw,
				1
			);
		}
		return $raw;
	}

	/**
	 * Unica funcion que decide que lineas antiguas de terceros comentar, para
	 * los dos casos posibles (bug real confirmado 2026-09-27: antes cada uno
	 * vivia por su cuenta, con codigo distinto en apply_actions() y en
	 * generate_full_file(), y ni siquiera coincidian entre si):
	 *
	 * - Bot marcado "Permitido" que un tercero bloquea con un 403 directo:
	 *   esa linea del tercero se comenta para que el permiso sea real.
	 * - Bot marcado "Bloqueado" en modo "solo llms.txt y .md": si un tercero
	 *   ya lo bloquea del todo (sin esa excepcion), esa linea tambien se
	 *   comenta para que la excepcion llegue a aplicarse.
	 *
	 * La usan tanto generate_full_file() (vista previa y descarga) como
	 * apply_actions() (el archivo real): lo que se ve en pantalla antes de
	 * aplicar es exactamente lo que queda escrito despues.
	 */
	protected static function comment_all_conflicts_in_content( $content, array $bot_user_agents, $visibility_mode = 'site' ) {
		$raw = (string) $content;
		foreach ( $bot_user_agents as $ua => $action ) {
			if ( 'allow' !== $action ) {
				continue;
			}
			$quoted_ua = preg_quote( $ua, '/' );
			$raw = preg_replace( '/^(RewriteCond %\{HTTP_USER_AGENT\} "' . $quoted_ua . '" \[NC\])$(\R)(RewriteRule .*\[F[^\r\n]*\])$/mi', '# AI Knowledge conflict: regla original conservada y desactivada$2# $1$2# $3', $raw );
		}
		$blocked_uas = array();
		foreach ( $bot_user_agents as $ua => $action ) {
			if ( 'allow' !== $action ) {
				$blocked_uas[] = $ua;
			}
		}
		if ( ! empty( $blocked_uas ) ) {
			$conflicts = self::conflicts_in_content( $raw, $blocked_uas, $visibility_mode );
			if ( ! empty( $conflicts ) ) {
				$raw = self::comment_lines_in_content( $raw, $conflicts );
			}
		}
		return $raw;
	}
}
