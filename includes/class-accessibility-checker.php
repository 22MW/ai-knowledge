<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fase 6: comprueba si una URL propia está bloqueada por robots.txt y si
 * lleva noindex (meta o cabecera X-Robots-Tag), avisando si ambas señales se
 * contradicen. Solo lectura, ninguna escritura de archivos.
 */
class Accessibility_Checker {

	/**
	 * @param string $url URL absoluta del propio sitio (ya validada por el llamador contra Scope/Registry).
	 * @return array|\WP_Error
	 */
	public static function check( $url ) {
		$robots_response = wp_remote_get( home_url( '/robots.txt' ), array( 'timeout' => 10 ) );
		if ( is_wp_error( $robots_response ) ) {
			return $robots_response;
		}

		$page_response = wp_remote_get( $url, array( 'timeout' => 10 ) );
		if ( is_wp_error( $page_response ) ) {
			return $page_response;
		}

		$robots_body    = wp_remote_retrieve_body( $robots_response );
		$robots_allowed = self::is_allowed_by_robots( $robots_body, wp_parse_url( $url, PHP_URL_PATH ) );

		$headers        = wp_remote_retrieve_headers( $page_response );
		$x_robots_tag   = isset( $headers['x-robots-tag'] ) ? (string) $headers['x-robots-tag'] : '';
		$header_noindex = false !== stripos( $x_robots_tag, 'noindex' );

		$body         = wp_remote_retrieve_body( $page_response );
		$meta_noindex = false;
		if ( preg_match( '/<meta[^>]+name=["\']robots["\'][^>]+content=["\']([^"\']*)["\']/i', $body, $m ) ) {
			$meta_noindex = false !== stripos( $m[1], 'noindex' );
		}

		$noindex = $header_noindex || $meta_noindex;

		// Conflicto: robots.txt permite rastrear pero la página pide no
		// indexarla, o al revés (robots.txt la bloquea pero no lleva noindex,
		// dejándola indexable igualmente si algún crawler ignora robots.txt).
		$conflict = $robots_allowed ? $noindex : ! $noindex;

		return array(
			'url'            => $url,
			'robots_allowed' => $robots_allowed,
			'noindex'        => $noindex,
			'conflict'       => $conflict,
		);
	}

	/**
	 * Interpretación simple: solo el grupo "User-agent: *" (aplica a
	 * cualquier bot, IA incluida, salvo que tenga su propio grupo -- fuera
	 * de alcance de este MVP). Varias declaraciones "User-agent: *" se tratan
	 * como una sola lista de Disallow acumulada.
	 */
	protected static function is_allowed_by_robots( $robots_txt, $path ) {
		$path          = $path ? $path : '/';
		$applies       = false;
		$disallows     = array();

		foreach ( preg_split( '/\r\n|\r|\n/', (string) $robots_txt ) as $line ) {
			$line = trim( $line );
			if ( '' === $line || '#' === $line[0] ) {
				continue;
			}
			if ( 0 === stripos( $line, 'user-agent:' ) ) {
				$agent   = trim( substr( $line, strlen( 'user-agent:' ) ) );
				$applies = ( '*' === $agent );
				continue;
			}
			if ( $applies && 0 === stripos( $line, 'disallow:' ) ) {
				$value = trim( substr( $line, strlen( 'disallow:' ) ) );
				if ( '' !== $value ) {
					$disallows[] = $value;
				}
			}
		}

		foreach ( $disallows as $prefix ) {
			if ( 0 === strpos( $path, $prefix ) ) {
				return false;
			}
		}

		return true;
	}
}
