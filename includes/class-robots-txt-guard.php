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

	public static function physical_exists() {
		return file_exists( self::path() );
	}

	/**
	 * Lee el robots.txt actual con una única lógica (pestaña Visibilidad IA,
	 * paso «Archivos del servidor» del asistente y descarga de copia): el
	 * archivo físico si existe; si no, el virtual que genera WordPress,
	 * capturando do_robots() con un buffer (sin petición HTTP). Devuelve
	 * [ content, source (physical|virtual|error), error ].
	 */
	public static function read_current() {
		$path = self::path();
		if ( file_exists( $path ) ) {
			$content = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( false === $content ) {
				return array( 'content' => '', 'source' => 'error', 'error' => __( 'No se pudo leer el archivo robots.txt físico de la raíz del sitio.', 'ai-knowledge' ) );
			}
			return array( 'content' => (string) $content, 'source' => 'physical', 'error' => '' );
		}

		if ( ! function_exists( 'do_robots' ) ) {
			return array( 'content' => '', 'source' => 'error', 'error' => __( 'WordPress no ha podido generar el robots.txt virtual en este momento.', 'ai-knowledge' ) );
		}

		$level = ob_get_level();
		ob_start();
		try {
			// do_robots() envía la cabecera Content-Type: en una pantalla de
			// administración las cabeceras ya pueden estar enviadas y PHP
			// avisaría; el aviso es inocuo (el contenido se captura igual).
			@do_robots(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$content = ob_get_clean();
		} catch ( \Throwable $e ) {
			while ( ob_get_level() > $level ) {
				ob_end_clean();
			}
			return array( 'content' => '', 'source' => 'error', 'error' => $e->getMessage() );
		}

		return array( 'content' => (string) $content, 'source' => 'virtual', 'error' => '' );
	}

	/** Texto del origen del robots.txt actual (para mostrarlo junto al contenido). */
	public static function source_label( $source ) {
		if ( 'physical' === $source ) {
			return __( 'Archivo físico', 'ai-knowledge' );
		}
		if ( 'virtual' === $source ) {
			return __( 'Generado por WordPress (no hay archivo físico)', 'ai-knowledge' );
		}
		return '';
	}

	/**
	 * Crea el robots.txt físico cuando todavía no existe: robots virtual actual
	 * de WordPress + bloque de AI Knowledge. Nunca sobrescribe un archivo que
	 * ya exista.
	 */
	public static function create_from_virtual( array $actions, $visibility_mode = 'site' ) {
		if ( file_exists( self::path() ) || ! is_writable( ABSPATH ) ) {
			return false;
		}
		$content = self::generate_full_file( $actions, $visibility_mode );
		return false !== file_put_contents( self::path(), $content, LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
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
	public static function build_rules( array $bot_user_agents, $visibility_mode = 'site' ) {
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
			if ( 'llms_only' === $visibility_mode ) {
				$lines[] = 'Allow: /llms.txt';
			}
		}
		return $lines;
	}

	public static function build_action_rules( array $actions, $visibility_mode = 'site' ) {
		$lines = array();
		foreach ( $actions as $ua => $action ) {
			if ( ! empty( $lines ) ) { $lines[] = ''; }
			$lines[] = 'User-agent: ' . trim( (string) $ua );
			if ( 'allow' === $action ) { $lines[] = 'Allow: /'; }
			else { $lines[] = 'Disallow: /'; if ( 'llms_only' === $visibility_mode ) { $lines[] = 'Allow: /llms.txt'; } }
		}
		return $lines;
	}

	public static function apply_actions( array $actions, $visibility_mode = 'site' ) {
		self::comment_action_conflicts( $actions );
		return insert_with_markers( self::path(), self::MARKER, self::build_action_rules( $actions, $visibility_mode ) );
	}

	public static function managed_block_matches( $content, array $actions, $visibility_mode = 'site' ) {
		$pattern = '/# BEGIN ' . preg_quote( self::MARKER, '/' ) . '\R(.*?)\R# END ' . preg_quote( self::MARKER, '/' ) . '/s';
		if ( ! preg_match( $pattern, (string) $content, $match ) ) { return false; }
		$current_lines = preg_split( '/\R/', trim( $match[1] ) );
		$current_lines = array_values( array_filter( $current_lines, function ( $line ) { return '' !== trim( $line ) && '#' !== substr( trim( $line ), 0, 1 ); } ) );
		$current = implode( "\n", $current_lines );
		$expected_lines = array_values( array_filter( self::build_action_rules( $actions, $visibility_mode ), function ( $line ) { return '' !== trim( $line ); } ) );
		$expected = implode( "\n", $expected_lines );
		return hash_equals( $expected, $current );
	}

	public static function action_conflicts( array $actions ) {
		$path = self::path();
		if ( ! file_exists( $path ) ) { return array(); }
		$lines = preg_split( '/\r\n|\r|\n/', (string) file_get_contents( $path ) ); // phpcs:ignore
		$found = array(); $inside = false; $ua = '';
		foreach ( $lines as $line ) {
			if ( 0 === strpos( trim( $line ), '# BEGIN ' . self::MARKER ) ) { $inside = true; continue; }
			if ( 0 === strpos( trim( $line ), '# END ' . self::MARKER ) ) { $inside = false; $ua = ''; continue; }
			if ( $inside ) { continue; }
			if ( preg_match( '/^\s*User-agent:\s*(.+)$/i', $line, $m ) ) { $ua = trim( $m[1] ); continue; }
			$matched = null;
			foreach ( $actions as $known_ua => $action ) { if ( 0 === strcasecmp( $known_ua, $ua ) ) { $matched = $action; break; } }
			if ( null === $matched ) { continue; }
			if ( 'allow' === $matched && preg_match( '/^\s*Disallow:\s*\/\s*$/i', $line ) ) { $found[] = $ua . ': ' . trim( $line ); }
			if ( 'block' === $matched && preg_match( '/^\s*Allow:\s*\/\s*$/i', $line ) ) { $found[] = $ua . ': ' . trim( $line ); }
		}
		return array_values( array_unique( $found ) );
	}

	protected static function comment_action_conflicts( array $actions ) {
		$path = self::path();
		if ( ! file_exists( $path ) || ! is_writable( $path ) ) { return; }
		$lines = preg_split( '/\r\n|\r|\n/', (string) file_get_contents( $path ) ); // phpcs:ignore
		$out = array(); $inside = false; $group = array(); $ua = '';
		$flush = function () use ( &$out, &$group, &$ua, $actions ) {
			if ( empty( $group ) ) { return; }
			$action = null;
			foreach ( $actions as $known_ua => $known_action ) { if ( 0 === strcasecmp( $known_ua, $ua ) ) { $action = $known_action; break; } }
			$body = implode( "\n", $group );
			$conflict = ( 'allow' === $action && preg_match( '/^\s*Disallow:\s*\/\s*$/mi', $body ) ) || ( 'block' === $action && preg_match( '/^\s*Allow:\s*\/\s*$/mi', $body ) );
			if ( $conflict ) { $out[] = '# AI Knowledge conflict: grupo original conservado y desactivado'; foreach ( $group as $item ) { $out[] = '# ' . $item; } }
			else { foreach ( $group as $item ) { $out[] = $item; } }
			$group = array(); $ua = '';
		};
		foreach ( $lines as $line ) {
			if ( 0 === strpos( trim( $line ), '# BEGIN ' . self::MARKER ) ) { $flush(); $inside = true; $out[] = $line; continue; }
			if ( 0 === strpos( trim( $line ), '# END ' . self::MARKER ) ) { $inside = false; $out[] = $line; continue; }
			if ( $inside ) { $out[] = $line; continue; }
			if ( preg_match( '/^\s*User-agent:\s*(.+)$/i', $line, $m ) ) { $flush(); $ua = trim( $m[1] ); $group[] = $line; continue; }
			if ( '' !== $ua && '' !== trim( $line ) ) { $group[] = $line; continue; }
			$flush(); $out[] = $line;
		}
		$flush();
		file_put_contents( $path, implode( "\n", $out ), LOCK_EX ); // phpcs:ignore
	}

	/**
	 * Aplica el bloque via insert_with_markers() (nativa de WordPress). Si el
	 * archivo fisico no existe todavia, insert_with_markers() lo crea. El
	 * marcador propio evita pisar reglas de otros plugins, y permite quitar
	 * el bloque limpiamente si se desactiva.
	 */
	public static function apply_block( array $bot_user_agents, $visibility_mode = 'site' ) {
		$rules = self::build_rules( $bot_user_agents, $visibility_mode );
		self::comment_conflicts( $bot_user_agents, $visibility_mode );
		// Un bloque vacio elimina solo el bloque del plugin y conserva el resto.
		return insert_with_markers( self::path(), self::MARKER, $rules );
	}

	public static function conflicts( array $bot_user_agents, $visibility_mode = 'site' ) {
		$path = self::path();
		if ( ! file_exists( $path ) ) {
			return array();
		}
		$lines = preg_split( '/\r\n|\r|\n/', (string) file_get_contents( $path ) ); // phpcs:ignore
		$found = array();
		$current = '';
		$inside_plugin_block = false;
		foreach ( $lines as $line ) {
			if ( preg_match( '/^# BEGIN ' . preg_quote( self::MARKER, '/' ) . '\s*$/', trim( $line ) ) ) {
				$inside_plugin_block = true;
				continue;
			}
			if ( preg_match( '/^# END ' . preg_quote( self::MARKER, '/' ) . '\s*$/', trim( $line ) ) ) {
				$inside_plugin_block = false;
				$current = '';
				continue;
			}
			if ( $inside_plugin_block ) {
				continue;
			}
			if ( preg_match( '/^\s*User-agent:\s*(.+)$/i', $line, $m ) ) {
				$current = trim( $m[1] );
				continue;
			}
			if ( ! in_array( $current, $bot_user_agents, true ) || preg_match( '/AI Knowledge crawlers/i', $line ) ) {
				continue;
			}
			if ( preg_match( '/^\s*(Allow|Disallow):\s*(.*)$/i', $line, $m ) ) {
				$expected = ( 'llms_only' === $visibility_mode && 'Allow' === ucfirst( strtolower( $m[1] ) ) && '/llms.txt' === trim( $m[2] ) ) || ( 'site' === $visibility_mode && 'Disallow' === ucfirst( strtolower( $m[1] ) ) && '/' === trim( $m[2] ) );
				if ( ! $expected ) {
					$found[] = trim( $line );
				}
			}
		}
		return array_values( array_unique( $found ) );
	}

	public static function generate_full_file( array $bot_user_agents, $visibility_mode = 'site' ) {
		// Base: el robots.txt actual (físico o, si no existe, el virtual de WordPress).
		$current  = self::read_current();
		$original = $current['content'];
		$original = preg_replace( '/\R?# BEGIN ' . preg_quote( self::MARKER, '/' ) . '.*?# END ' . preg_quote( self::MARKER, '/' ) . '\R?/s', "\n", $original );
		foreach ( $bot_user_agents as $ua => $action ) {
			$quoted_ua = preg_quote( $ua, '/' );
			if ( 'allow' === $action ) {
				$original = preg_replace( '/^(User-agent:\s*' . $quoted_ua . '\s*)\R(Disallow:\s*\/\s*)$/mi', '# AI Knowledge conflict: grupo original conservado y desactivado' . "\n" . '# $1' . "\n" . '# $2', $original );
			} elseif ( 'block' === $action ) {
				$original = preg_replace( '/^(User-agent:\s*' . $quoted_ua . '\s*)\R(Allow:\s*\/\s*)$/mi', '# AI Knowledge conflict: grupo original conservado y desactivado' . "\n" . '# $1' . "\n" . '# $2', $original );
			}
		}
		$rules = self::build_action_rules( $bot_user_agents, $visibility_mode );
		if ( empty( $rules ) ) {
			return rtrim( $original ) . "\n";
		}
		$block = "# BEGIN " . self::MARKER . "\n" . implode( "\n", $rules ) . "\n# END " . self::MARKER;
		return rtrim( $original ) . "\n\n" . $block . "\n";
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
			$raw = preg_replace( '/^(' . $quoted . ')$/m', '# AI Knowledge conflict: regla original conservada y desactivada por contradicción\n# $1', $raw, 1 );
		}
		file_put_contents( $path, $raw, LOCK_EX ); // phpcs:ignore
	}
}
