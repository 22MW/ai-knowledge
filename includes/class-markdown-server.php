<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sirve los .md vía rewrite de WordPress con Content-Type: text/plain
 * explícito. Necesario porque servir el archivo físico directo (como se
 * hacía antes) depende de la configuración MIME del servidor: en nginx
 * (Local by Flywheel, y muchos hostings) los .md sin tipo declarado se
 * sirven como application/octet-stream y el navegador fuerza descarga en
 * vez de mostrarlos. Esta ruta funciona igual en Apache o nginx porque el
 * Content-Type lo decide PHP, no el servidor.
 */
class Markdown_Server {

	const QUERY_VAR = 'wookb_md_doc';
	/** Redirect 301 de las URL antiguas wp-content/llm/... a la carpeta nueva. */
	const LEGACY_QUERY_VAR = 'wookb_legacy_llm';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_rewrite_rule' ) );
		add_filter( 'query_vars', array( __CLASS__, 'register_query_var' ) );
		add_action( 'parse_request', array( __CLASS__, 'maybe_serve' ) );
	}

	public static function add_rewrite_rule() {
		add_rewrite_rule( '^ai-knowledge-doc/(.+)\.md$', 'index.php?' . self::QUERY_VAR . '=$matches[1]', 'top' );
		// Solo actua si el servidor ya no sirve el archivo antiguo (WordPress
		// enruta unicamente lo que no existe como archivo real).
		add_rewrite_rule( '^wp-content/' . Markdown_Store::LEGACY_DIR_NAME . '/(.+)$', 'index.php?' . self::LEGACY_QUERY_VAR . '=$matches[1]', 'top' );
	}

	/** 301 a la carpeta nueva con lista blanca estricta; 404 si la ruta no cumple. */
	protected static function redirect_legacy( $path ) {
		if ( ! preg_match( '#^[a-z0-9._-]+(?:/[a-z0-9._-]+)*$#', $path ) || false !== strpos( $path, '..' ) ) {
			status_header( 404 );
			exit;
		}
		wp_safe_redirect( content_url( '/' . Markdown_Store::DIR_NAME . '/' . $path ), 301 );
		exit;
	}

	public static function register_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;
		$vars[] = self::LEGACY_QUERY_VAR;
		return $vars;
	}

	public static function maybe_serve( $wp ) {
		if ( ! empty( $wp->query_vars[ self::LEGACY_QUERY_VAR ] ) ) {
			self::redirect_legacy( (string) $wp->query_vars[ self::LEGACY_QUERY_VAR ] );
		}
		if ( empty( $wp->query_vars[ self::QUERY_VAR ] ) ) {
			return;
		}

		$relative = sanitize_text_field( wp_unslash( $wp->query_vars[ self::QUERY_VAR ] ) ) . '.md';

		// Blindaje contra path traversal: solo permite el patrón exacto que
		// generamos nosotros (lang/slug.md, sin .. ni barras sueltas al inicio).
		if ( ! preg_match( '#^[a-z]{2}(-[a-z]{2})?/[a-z0-9\-]+\.md$#i', $relative ) ) {
			status_header( 404 );
			exit;
		}

		$absolute = Markdown_Store::absolute_path( $relative );
		$base_dir = wp_normalize_path( Markdown_Store::base_dir() );
		$real     = wp_normalize_path( $absolute );

		if ( 0 !== strpos( $real, $base_dir ) || ! file_exists( $real ) ) {
			status_header( 404 );
			exit;
		}

		$content = file_get_contents( $real ); // phpcs:ignore
		if ( false === $content ) {
			status_header( 404 );
			exit;
		}

		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Cache-Control: public, max-age=300' );
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markdown plano, no HTML.
		exit;
	}
}
