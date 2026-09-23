<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fase 2 del flujo de publicacion/AJAX/prompts por documento (ver
 * _dev/plan-publicacion-ajax-prompts.md): copia integra de un documento
 * sgkb-docs de Support Genix a un .md propio de AI Knowledge.
 *
 * Sin IA, sin resumen, sin limite de caracteres: toma el HTML crudo que ya
 * entrega Genix_Reader::get_document_data(), lo convierte a Markdown de forma
 * deterministica (conversor propio, sin librerias externas) y lo escribe con
 * la misma infraestructura que usa Document_Pipeline (Markdown_Store).
 *
 * Esta clase nunca escribe en Genix (eso es Genix_Bridge, en sentido
 * contrario) ni llama a ningun proveedor de IA.
 */
class Genix_Markdown {

	/**
	 * Lee un documento sgkb-docs publicado, lo convierte a Markdown y lo
	 * escribe en el .md propio de AI Knowledge.
	 *
	 * @param int $post_id ID del post sgkb-docs.
	 * @return string|\WP_Error Ruta relativa del .md escrito, o WP_Error si el
	 *                          documento no existe o no esta publicado.
	 */
	public static function write_from_post( $post_id ) {
		$data = Genix_Reader::get_document_data( $post_id );
		if ( ! $data ) {
			return new \WP_Error(
				'aikb_genix_markdown_not_found',
				__( 'El documento Genix no existe o no esta publicado.', 'ai-knowledge' )
			);
		}

		$lang = Wpml::element_language( $post_id );

		$markdown = self::build_markdown( $data['title'], $data['content'] );

		$slug = Markdown_Store::slug_for( $post_id, $lang );

		return Markdown_Store::write(
			$lang,
			$slug,
			$markdown,
			array(
				'source_id'    => $post_id,
				'source_type'  => Genix_Reader::CPT,
				'lang'         => $lang,
				'generated_at' => current_time( 'mysql' ),
				'product_url'  => $data['permalink'],
				'full_copy'    => true,
			)
		);
	}

	/**
	 * Titulo en H1 + cuerpo convertido, integros.
	 */
	protected static function build_markdown( $title, $html_content ) {
		$body = self::html_to_markdown( (string) $html_content );

		$title = trim( wp_strip_all_tags( (string) $title ) );
		if ( '' === $title ) {
			return $body;
		}

		return '# ' . $title . "\n\n" . $body;
	}

	/**
	 * Conversor HTML -> Markdown propio, sin librerias externas, simetrico
	 * (mismo enfoque a mano con expresiones regulares) al conversor inverso
	 * Markdown -> HTML de admin/class-admin.php (markdown_to_html()).
	 *
	 * No es un parser HTML generico: el origen es siempre post_content del
	 * editor de WordPress, con etiquetas predecibles y acotadas. Cubre
	 * h1-h6, p, ul/ol/li, strong/b, em/i, a[href], br. Cualquier etiqueta
	 * fuera de ese conjunto se descarta conservando su texto plano (nunca
	 * rompe, nunca falta contenido).
	 */
	public static function html_to_markdown( $html ) {
		if ( '' === trim( $html ) ) {
			return '';
		}

		$html = str_replace( array( "\r\n", "\r" ), "\n", $html );

		// Normaliza saltos de linea dentro de bloques para no confundirlos
		// con separadores de parrafo antes de trabajar etiqueta a etiqueta.
		$html = preg_replace( '/\n+/', ' ', $html );

		// <br> -> salto de linea simple dentro del mismo bloque.
		$html = preg_replace( '/<br\s*\/?>/i', "\n", $html );

		// Bloques de nivel superior: h1-h6, p, ul, ol. Se extraen en orden de
		// aparicion para conservar la secuencia original del documento.
		$pattern = '/<(h[1-6]|p|ul|ol)\b[^>]*>(.*?)<\/\1>/is';

		if ( ! preg_match_all( $pattern, $html, $matches, PREG_SET_ORDER ) ) {
			// Sin bloques reconocibles: se trata todo como un unico parrafo
			// de texto, para no perder contenido.
			return self::inline_to_markdown( $html );
		}

		$blocks = array();
		foreach ( $matches as $match ) {
			$tag   = strtolower( $match[1] );
			$inner = $match[2];

			if ( 'ul' === $tag || 'ol' === $tag ) {
				$blocks[] = self::list_to_markdown( $inner, $tag );
				continue;
			}

			if ( preg_match( '/^h([1-6])$/', $tag, $level_match ) ) {
				$level = (int) $level_match[1];
				$text  = trim( self::inline_to_markdown( $inner ) );
				if ( '' !== $text ) {
					$blocks[] = str_repeat( '#', $level ) . ' ' . $text;
				}
				continue;
			}

			// 'p'
			$text = trim( self::inline_to_markdown( $inner ) );
			if ( '' !== $text ) {
				$blocks[] = $text;
			}
		}

		$markdown = implode( "\n\n", array_filter( $blocks, 'strlen' ) );

		return trim( $markdown );
	}

	/**
	 * <li> de una lista (ul o ol) a lineas Markdown con "- " o "1. ".
	 */
	protected static function list_to_markdown( $html, $list_tag ) {
		if ( ! preg_match_all( '/<li\b[^>]*>(.*?)<\/li>/is', $html, $items ) ) {
			return trim( self::inline_to_markdown( $html ) );
		}

		$lines  = array();
		$number = 1;
		foreach ( $items[1] as $item_html ) {
			$text = trim( self::inline_to_markdown( $item_html ) );
			if ( '' === $text ) {
				continue;
			}
			if ( 'ol' === $list_tag ) {
				$lines[] = $number . '. ' . $text;
				++$number;
			} else {
				$lines[] = '- ' . $text;
			}
		}

		return implode( "\n", $lines );
	}

	/**
	 * Marcado inline dentro de un bloque: strong/b, em/i, a[href], y
	 * cualquier otra etiqueta se retira conservando su texto. Entidades HTML
	 * decodificadas al final para que el resultado sea texto plano legible.
	 */
	protected static function inline_to_markdown( $html ) {
		$text = $html;

		// strong/b -> **texto**
		$text = preg_replace( '/<(strong|b)\b[^>]*>(.*?)<\/\1>/is', '**$2**', $text );

		// em/i -> *texto*
		$text = preg_replace( '/<(em|i)\b[^>]*>(.*?)<\/\1>/is', '*$2*', $text );

		// a[href] -> [texto](url)
		$text = preg_replace_callback(
			'/<a\b[^>]*href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/is',
			static function ( $match ) {
				$url  = trim( $match[1] );
				$link = trim( wp_strip_all_tags( $match[2] ) );
				if ( '' === $url ) {
					return $link;
				}
				if ( '' === $link ) {
					$link = $url;
				}
				return '[' . $link . '](' . $url . ')';
			},
			$text
		);

		// Cualquier etiqueta restante (span, div, code, etc.) se descarta
		// conservando su contenido de texto, para no perder informacion.
		$text = wp_strip_all_tags( $text );

		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );

		// Colapsa espacios repetidos que pueda dejar el HTML original, sin
		// tocar los saltos de linea explicitos (procedentes de <br>).
		$text = preg_replace( '/[ \t]+/', ' ', $text );
		$text = preg_replace( '/ *\n */', "\n", $text );

		return trim( $text );
	}
}
