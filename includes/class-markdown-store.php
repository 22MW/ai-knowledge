<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Escritura/borrado de .md en wp-content/llm/{lang}/{slug}.md con front matter YAML.
 */
class Markdown_Store {

	public static function base_dir() {
		return WP_CONTENT_DIR . '/llm';
	}

	public static function base_url() {
		return content_url( '/llm' );
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
		return home_url( '/ai-knowledge-doc/' . $without_extension . '.md' );
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
		$absolute = self::absolute_path( $relative );
		if ( file_exists( $absolute ) ) {
			unlink( $absolute ); // phpcs:ignore
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
