<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pieza 5: conversor HTML -> Markdown propio, sin librerías externas,
 * determinístico. Copia íntegra del contenido de un sgkb-docs exclusivo de
 * Genix: sin IA, sin resumen, sin límite de caracteres.
 *
 * Cubre: h1-h6, p, ul/ol/li, strong/b, em/i, a[href], br. Cualquier otra
 * etiqueta se retira conservando su texto (nunca se pierde contenido, solo
 * el marcado que no sabemos traducir a Markdown).
 */
class Genix_Markdown {

	public static function convert( $html ) {
		$html = (string) $html;
		if ( '' === trim( $html ) ) {
			return '';
		}

		// Normaliza saltos de linea internos que WordPress a veces deja como
		// texto plano entre bloques, para no duplicarlos con los \n que añade
		// esta conversion.
		$html = str_replace( array( "\r\n", "\r" ), "\n", $html );

		libxml_use_internal_errors( true );
		$dom = new \DOMDocument( '1.0', 'UTF-8' );
		// wrapper propio: evita que DOMDocument reinterprete el fragmento como
		// documento HTML completo (le añadiría <html><body> que hay que pelar
		// despues) y fija la codificacion explicitamente.
		$wrapped = '<?xml encoding="UTF-8"?><div id="wookb-genix-root">' . $html . '</div>';
		$dom->loadHTML( $wrapped );
		libxml_clear_errors();

		$root = $dom->getElementById( 'wookb-genix-root' );
		if ( ! $root ) {
			// Fallback extremo: si ni siquiera esto parseo, se devuelve el
			// texto plano sin marcado en vez de perder el contenido.
			return trim( wp_strip_all_tags( $html ) );
		}

		$markdown = self::convert_children( $root );

		// Colapsa 3+ saltos de linea seguidos a como mucho 2 (un parrafo de
		// separacion), sin tocar el contenido en si.
		$markdown = preg_replace( "/\n{3,}/", "\n\n", $markdown );

		return trim( $markdown );
	}

	protected static function convert_children( \DOMNode $node ) {
		$out = '';
		foreach ( $node->childNodes as $child ) {
			$out .= self::convert_node( $child );
		}
		return $out;
	}

	protected static function convert_node( \DOMNode $node ) {
		if ( XML_TEXT_NODE === $node->nodeType ) {
			return $node->textContent;
		}

		if ( XML_ELEMENT_NODE !== $node->nodeType ) {
			return '';
		}

		$tag = strtolower( $node->nodeName );

		switch ( $tag ) {
			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6':
				$level = (int) substr( $tag, 1 );
				return "\n\n" . str_repeat( '#', $level ) . ' ' . trim( self::convert_children( $node ) ) . "\n\n";

			case 'p':
				return "\n\n" . trim( self::convert_children( $node ) ) . "\n\n";

			case 'br':
				return "  \n";

			case 'strong':
			case 'b':
				$inner = trim( self::convert_children( $node ) );
				return '' === $inner ? '' : '**' . $inner . '**';

			case 'em':
			case 'i':
				$inner = trim( self::convert_children( $node ) );
				return '' === $inner ? '' : '_' . $inner . '_';

			case 'a':
				$href  = $node->getAttribute( 'href' );
				$inner = trim( self::convert_children( $node ) );
				if ( '' === $inner ) {
					$inner = $href;
				}
				return $href ? '[' . $inner . '](' . $href . ')' : $inner;

			case 'ul':
				return "\n\n" . self::convert_list( $node, false ) . "\n";

			case 'ol':
				return "\n\n" . self::convert_list( $node, true ) . "\n";

			case 'li':
				// Las <li> sueltas (fuera de un ul/ol, marcado raro) se tratan
				// como una linea de lista simple, sin perder el contenido.
				return '- ' . trim( self::convert_children( $node ) ) . "\n";

			case 'script':
			case 'style':
				// Nunca es contenido real del artículo: se retira entero.
				return '';

			default:
				// Etiqueta no cubierta: se retira el marcado, se conserva el texto.
				return self::convert_children( $node );
		}
	}

	protected static function convert_list( \DOMNode $list_node, $ordered ) {
		$lines = array();
		$index = 1;
		foreach ( $list_node->childNodes as $child ) {
			if ( XML_ELEMENT_NODE !== $child->nodeType || 'li' !== strtolower( $child->nodeName ) ) {
				continue;
			}
			$text   = trim( preg_replace( '/\s+/', ' ', self::convert_children( $child ) ) );
			$prefix = $ordered ? ( $index . '. ' ) : '- ';
			$lines[] = $prefix . $text;
			$index++;
		}
		return implode( "\n", $lines );
	}
}
