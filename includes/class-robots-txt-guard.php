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

	/**
	 * Rutas que quedan abiertas para los bots bloqueados en modo "llms_only"
	 * (pedido explicito del usuario, 2026-09-27): no solo el resumen de
	 * llms.txt, tambien los documentos .md que sirve el plugin -- el objetivo
	 * no es impedir que lean sobre la web, es que no rastreen el sitio real
	 * (paginas HTML completas). "Allow" en robots.txt hace match de prefijo,
	 * asi que la ruta del servidor de documentos cubre cualquier .md debajo.
	 */
	const LLMS_ONLY_ALLOWED_PATHS = array( '/llms.txt', '/ai-knowledge-doc/' );

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
	 * archivo físico si existe; si no, el virtual que genera WordPress.
	 * Devuelve [ content, source (physical|virtual|error), error ].
	 *
	 * NO se llama a do_robots() (bug real confirmado, 2026-09-27): esa función
	 * del núcleo envía una cabecera real `Content-Type: text/plain` con
	 * header() -- una llamada real, no algo que un ob_start() pueda capturar
	 * ni deshacer -- y aquí se ejecutaba desde dentro de una pantalla de
	 * administración ya en curso: esa cabecera se quedaba puesta para TODA la
	 * respuesta, sirviendo la pantalla entera como texto plano en vez de HTML.
	 * En su lugar se reproduce el mismo contenido por defecto de do_robots()
	 * (wp-includes/functions.php) y se le aplica el mismo filtro oficial
	 * `robots_txt` que ya usan los plugins SEO -- sin cabeceras y sin disparar
	 * la acción `do_robotstxt` (algunos plugins la usan para hacer echo directo
	 * y terminar la petición con exit/die, pensada solo para la petición real
	 * de /robots.txt; dispararla aquí podría cortar a mitad la carga del admin).
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

		try {
			$public  = (bool) get_option( 'blog_public' );
			$content = "User-agent: *\n";
			$content .= 'Disallow: ' . wp_parse_url( admin_url(), PHP_URL_PATH ) . "\n";
			$content .= 'Allow: ' . wp_parse_url( admin_url( 'admin-ajax.php' ), PHP_URL_PATH ) . "\n";
			$content  = (string) apply_filters( 'robots_txt', $content, $public );
		} catch ( \Throwable $e ) {
			return array( 'content' => '', 'source' => 'error', 'error' => $e->getMessage() );
		}

		return array( 'content' => $content, 'source' => 'virtual', 'error' => '' );
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

	public static function build_action_rules( array $actions, $visibility_mode = 'site' ) {
		$lines = array();
		foreach ( $actions as $ua => $action ) {
			if ( ! empty( $lines ) ) { $lines[] = ''; }
			$lines[] = 'User-agent: ' . trim( (string) $ua );
			if ( 'allow' === $action ) { $lines[] = 'Allow: /'; }
			else {
				$lines[] = 'Disallow: /';
				if ( 'llms_only' === $visibility_mode ) {
					foreach ( self::LLMS_ONLY_ALLOWED_PATHS as $allowed_path ) {
						$lines[] = 'Allow: ' . $allowed_path;
					}
				}
			}
		}
		return $lines;
	}

	public static function apply_actions( array $actions, $visibility_mode = 'site' ) {
		$path = self::path();
		if ( file_exists( $path ) && is_writable( $path ) ) { // phpcs:ignore
			$raw = (string) file_get_contents( $path ); // phpcs:ignore
			list( , $updated ) = self::scan_conflicts( $raw, $actions, $visibility_mode, true );
			if ( $updated !== $raw ) {
				file_put_contents( $path, $updated, LOCK_EX ); // phpcs:ignore
			}
		}
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

	/**
	 * Unica logica de deteccion + comentado de conflictos con grupos de
	 * terceros, para los dos casos posibles (bug real confirmado 2026-09-28,
	 * mismo hallazgo que en Htaccess_Guard: existian 3 copias distintas --
	 * esta, la de generate_full_file() y la de comment_action_conflicts() --
	 * y ninguna sabia detectar el caso mas habitual, un bot "Bloqueado" en
	 * modo "solo llms.txt y .md" al que un tercero ya bloquea del todo sin
	 * dejar la excepcion):
	 *
	 * - Bot "Permitido" (`allow`) que un grupo de terceros ya bloquea del
	 *   todo (`Disallow: /` sin ningun `Allow`).
	 * - Bot "Bloqueado" (`block`) en modo `llms_only` al que un grupo de
	 *   terceros bloquea sin dejar pasar `/llms.txt` ni `/ai-knowledge-doc/`.
	 * - Bot "Bloqueado" en modo `site` al que un grupo de terceros permite
	 *   del todo (`Allow: /`).
	 *
	 * Devuelve [ conflictos (array de "User-agent: linea"), contenido (con o
	 * sin comentar segun $apply) ]. La usan action_conflicts() (aviso, solo
	 * detecta), generate_full_file() (vista previa) y apply_actions() (boton
	 * real): las tres ven exactamente el mismo resultado.
	 */
	protected static function scan_conflicts( $content, array $actions, $visibility_mode, $apply = false ) {
		$lines  = preg_split( '/\r\n|\r|\n/', (string) $content );
		$out    = array();
		$found  = array();
		$inside = false;
		$group  = array();
		$ua     = '';
		$flush  = function () use ( &$out, &$found, &$group, &$ua, $actions, $visibility_mode, $apply ) {
			if ( empty( $group ) ) {
				return;
			}
			$action = null;
			foreach ( $actions as $known_ua => $known_action ) {
				if ( 0 === strcasecmp( $known_ua, $ua ) ) {
					$action = $known_action;
					break;
				}
			}
			$body     = implode( "\n", $group );
			$conflict = false;
			if ( 'allow' === $action ) {
				$conflict = (bool) preg_match( '/^\s*Disallow:\s*\/\s*$/mi', $body );
			} elseif ( 'block' === $action ) {
				if ( 'llms_only' === $visibility_mode ) {
					$has_disallow_all = (bool) preg_match( '/^\s*Disallow:\s*\/\s*$/mi', $body );
					$has_all_allowed  = true;
					foreach ( self::LLMS_ONLY_ALLOWED_PATHS as $allowed_path ) {
						if ( ! preg_match( '/^\s*Allow:\s*' . preg_quote( $allowed_path, '/' ) . '\s*$/mi', $body ) ) {
							$has_all_allowed = false;
							break;
						}
					}
					$conflict = $has_disallow_all && ! $has_all_allowed;
				} else {
					$conflict = (bool) preg_match( '/^\s*Allow:\s*\/\s*$/mi', $body );
				}
			}
			if ( $conflict ) {
				$found[] = $ua . ': ' . trim( $group[ count( $group ) - 1 ] );
				if ( $apply ) {
					$out[] = '# AI Knowledge conflict: grupo original conservado y desactivado por contradicción';
					foreach ( $group as $item ) {
						$out[] = '# ' . $item;
					}
					$group = array();
					$ua    = '';
					return;
				}
			}
			foreach ( $group as $item ) {
				$out[] = $item;
			}
			$group = array();
			$ua    = '';
		};
		foreach ( $lines as $line ) {
			if ( 0 === strpos( trim( $line ), '# BEGIN ' . self::MARKER ) ) {
				$flush();
				$inside = true;
				$out[]  = $line;
				continue;
			}
			if ( 0 === strpos( trim( $line ), '# END ' . self::MARKER ) ) {
				$inside = false;
				$out[]  = $line;
				continue;
			}
			if ( $inside ) {
				$out[] = $line;
				continue;
			}
			if ( 0 === strpos( ltrim( $line ), '#' ) ) {
				// Bug real confirmado (2026-09-27, mismo hallazgo que en
				// Htaccess_Guard): un grupo ya comentado no debe volver a
				// contarse como conflicto ni volver a comentarse.
				$flush();
				$out[] = $line;
				continue;
			}
			if ( preg_match( '/^\s*User-agent:\s*(.+)$/i', $line, $m ) ) {
				$flush();
				$ua      = trim( $m[1] );
				$group[] = $line;
				continue;
			}
			if ( '' !== $ua && '' !== trim( $line ) ) {
				$group[] = $line;
				continue;
			}
			$flush();
			$out[] = $line;
		}
		$flush();
		return array( array_values( array_unique( $found ) ), implode( "\n", $out ) );
	}

	public static function action_conflicts( array $actions, $visibility_mode = 'site' ) {
		$path = self::path();
		if ( ! file_exists( $path ) ) {
			return array();
		}
		list( $found ) = self::scan_conflicts( (string) file_get_contents( $path ), $actions, $visibility_mode, false ); // phpcs:ignore
		return $found;
	}

	public static function generate_full_file( array $bot_user_agents, $visibility_mode = 'site' ) {
		// Base: el robots.txt actual (físico o, si no existe, el virtual de WordPress).
		$current  = self::read_current();
		$original = $current['content'];
		$original = preg_replace( '/\R?# BEGIN ' . preg_quote( self::MARKER, '/' ) . '.*?# END ' . preg_quote( self::MARKER, '/' ) . '\R?/s', "\n", $original );
		list( , $original ) = self::scan_conflicts( $original, $bot_user_agents, $visibility_mode, true );
		$rules = self::build_action_rules( $bot_user_agents, $visibility_mode );
		if ( empty( $rules ) ) {
			return rtrim( $original ) . "\n";
		}
		$block = "# BEGIN " . self::MARKER . "\n" . implode( "\n", $rules ) . "\n# END " . self::MARKER;
		return rtrim( $original ) . "\n\n" . $block . "\n";
	}
}
