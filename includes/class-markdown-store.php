<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Escritura/borrado de .md en wp-content/ai-knowledge/{lang}/{slug}.md con front matter YAML.
 * El nombre de la carpeta se define solo aqui (DIR_NAME); la antigua `llm` se
 * migra con rename() la primera vez (migrate()).
 */
class Markdown_Store {

	/** Nombre de la carpeta de documentos dentro de wp-content. */
	const DIR_NAME = 'ai-knowledge';
	/** Nombre antiguo (hasta 1.3.x), solo para migrar/redirigir/desinstalar. */
	const LEGACY_DIR_NAME = 'llm';
	/** Opcion: pide un flush de rewrite rules tras migrar (lo hace Plugin). */
	const FLUSH_OPTION = 'wookb_rewrite_flush_pending';

	/** Ruta usada tras resolver la migracion (null = aun no resuelta en esta peticion). */
	protected static $resolved = null;

	public static function legacy_dir() {
		return WP_CONTENT_DIR . '/' . self::LEGACY_DIR_NAME;
	}

	/**
	 * Si existe la carpeta antigua y no la nueva, la renombra. Idempotente. Si
	 * rename() falla (permisos), sigue usando la antigua sin romper nada.
	 * Devuelve la ruta que debe usarse.
	 */
	public static function migrate() {
		if ( null !== self::$resolved ) {
			return self::$resolved;
		}
		$new = WP_CONTENT_DIR . '/' . self::DIR_NAME;
		$old = self::legacy_dir();
		if ( ! is_dir( $new ) && is_dir( $old ) && ! is_link( $old ) ) {
			if ( @rename( $old, $new ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
				update_option( self::FLUSH_OPTION, 1, false );
				self::$resolved = $new;
			} else {
				self::$resolved = $old;
			}
			return self::$resolved;
		}
		self::$resolved = $new;
		return $new;
	}

	public static function base_dir() {
		return self::migrate();
	}

	public static function base_url() {
		return content_url( '/' . basename( self::migrate() ) );
	}

	public static function relative_path( $lang, $slug ) {
		return $lang . '/' . $slug . '.md';
	}

	public static function absolute_path( $relative ) {
		return self::base_dir() . '/' . $relative;
	}

	/**
	 * URL pública del .md, servida vía Markdown_Server (ruta virtual de
	 * WordPress) en vez de apuntar al archivo físico directamente: así el
	 * Content-Type (text/plain) lo fija PHP y funciona igual en Apache o
	 * nginx, sin depender de la configuración MIME del servidor.
	 */
	public static function public_url( $relative ) {
		$without_extension = preg_replace( '/\.md$/', '', $relative );
		return self::root_url( '/ai-knowledge-doc/' . $without_extension . '.md' );
	}

	/**
	 * URL bajo la raíz del sitio, sin prefijo de idioma (home_url() lo añade con
	 * WPML/Polylang según la petición): una sola URL canónica para los .md y
	 * llms.txt.
	 */
	public static function root_url( $path ) {
		return trailingslashit( (string) get_option( 'home' ) ) . ltrim( $path, '/' );
	}

	/**
	 * Escribe el .md con front matter. Devuelve la ruta relativa.
	 */
	public static function write( $lang, $slug, $body_markdown, array $front_matter ) {
		$relative = self::relative_path( $lang, $slug );
		$absolute = self::absolute_path( $relative );

		wp_mkdir_p( dirname( $absolute ) );

		$yaml = "---\n";
		foreach ( $front_matter as $key => $value ) {
			if ( is_bool( $value ) ) {
				$value = $value ? 'true' : 'false';
			}
			$yaml .= $key . ': ' . self::yaml_escape( $value ) . "\n";
		}
		$yaml .= "---\n\n";

		$content = $yaml . $body_markdown;
		file_put_contents( $absolute, $content ); // phpcs:ignore

		return $relative;
	}

	public static function delete( $relative ) {
		$relative = (string) $relative;
		// Guard: filas sin md_path (p. ej. en cola) llegan aqui con ruta vacia y
		// la ruta absoluta seria la propia carpeta base.
		if ( '' === $relative || '.md' !== substr( $relative, -3 ) || false !== strpos( $relative, '..' ) ) {
			return;
		}
		$absolute = self::absolute_path( $relative );
		$base     = wp_normalize_path( self::base_dir() );
		if ( 0 !== strpos( wp_normalize_path( $absolute ), trailingslashit( $base ) ) || ! is_file( $absolute ) ) {
			return;
		}
		unlink( $absolute ); // phpcs:ignore
		// Carpeta de idioma vacia: se elimina solo si es hija directa de la base.
		$dir = dirname( $absolute );
		if ( wp_normalize_path( dirname( $dir ) ) === $base && is_dir( $dir ) && 2 === count( scandir( $dir ) ) ) {
			rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rmdir_rmdir
		}
	}

	public static function read( $relative ) {
		$absolute = self::absolute_path( $relative );
		if ( ! file_exists( $absolute ) ) {
			return null;
		}
		return file_get_contents( $absolute ); // phpcs:ignore
	}

	/** Cuerpo sin front matter. */
	public static function body_only( $raw ) {
		if ( 0 === strpos( $raw, '---' ) ) {
			$parts = preg_split( '/^---\s*$/m', $raw, 3 );
			if ( count( $parts ) >= 3 ) {
				return trim( $parts[2] );
			}
		}
		return $raw;
	}

	protected static function yaml_escape( $value ) {
		$value = (string) $value;
		if ( '' === $value ) {
			return '""';
		}
		if ( preg_match( '/[:#\'"\n]/', $value ) ) {
			return '"' . str_replace( '"', '\\"', $value ) . '"';
		}
		return $value;
	}

	public static function slug_for( $post_id, $lang ) {
		$post = get_post( $post_id );
		$base = $post ? $post->post_name : 'doc-' . $post_id;
		return sanitize_title( $base . '-' . $post_id );
	}
}
