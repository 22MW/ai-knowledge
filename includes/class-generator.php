<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Genera el Markdown del documento mediante el transporte IA central.
 */
class Generator {

	// Tope de caracteres del cuerpo del documento (sin contar el titulo).
	// Igual para cualquier idioma: el motor de busqueda de Genix puntua por
	// coincidencia literal de palabras, no semantica, asi que un documento
	// mas largo gana artificialmente sobre uno mas corto aunque sea menos
	// relevante. Igualar la longitud nivela el terreno, pero debe seguir
	// siendo informativo (horarios, dias, precios por franja) — no un
	// telegrama de bullets sueltos. Ver investigacion-comportamiento-chatbot.md.
	// Editable en Ajustes (Scope::settings()['body_char_limit']); esta
	// constante es solo el valor de respaldo si el ajuste no existe.
	const BODY_CHAR_LIMIT = 1000;

	/**
	 * Configuración de la IA: clave, modelo y flag de origen.
	 */
	public static function ai_config() {
		return AI_Client::config();
	}

	/**
	 * Genera el Markdown del documento a partir de los datos extraídos.
	 * Devuelve WP_Error si falla.
	 *
	 * $char_limit: tope de caracteres del cuerpo para ESTA generacion concreta.
	 * Null (por defecto) usa BODY_CHAR_LIMIT. Lo usa la regeneracion individual
	 * desde el Registro (accion puntual bajo demanda del admin, valor de un
	 * solo uso que no se persiste en BD, ver Admin::regenerate_single()).
	 */
	public static function generate( array $data, array $links = array(), $char_limit = null ) {
		$config = self::ai_config();
		if ( ! $config ) {
			return new \WP_Error( 'wookb_no_ai_connection', __( 'El origen de IA seleccionado no está conectado.', 'ai-knowledge' ) );
		}

		$char_limit = self::resolve_char_limit( $char_limit );

		// El bloque de enlaces cruzados de idioma NO se pide a la IA: se anexa
		// aqui de forma deterministica tras la respuesta, para garantizar el
		// formato exacto (Tarea 3) sin depender de que el modelo lo reproduzca
		// bien. El prompt le pide explicitamente que NO genere esa seccion el
		// mismo, para no duplicarla.
		$prompt = self::build_prompt( $data, $links, $char_limit );

		$response = AI_Client::generate(
			'Eres un redactor técnico que genera documentos de base de conocimiento en Markdown para un chatbot de atención al cliente de un negocio o tienda online.',
			$prompt,
			(int) Scope::settings()['output_tokens'],
			0.5,
			60
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = trim( $response );
		$response = self::enforce_body_char_limit( $response, $char_limit );
		$link_block = self::build_language_links_block( $data['title'], $links );
		if ( $link_block ) {
			$response .= "\n\n## Disponible también en\n\n" . $link_block . "\n";
		}

		return $response;
	}

	/**
	 * Normaliza un limite de caracteres recibido desde fuera (puede venir de
	 * $_POST, o de char_limit por fila en el Registro): si es null, cae al
	 * ajuste general (Scope::settings()['body_char_limit']); si es cero,
	 * negativo o el ajuste no existe, cae a BODY_CHAR_LIMIT.
	 */
	protected static function resolve_char_limit( $char_limit ) {
		if ( null === $char_limit ) {
			$settings   = Scope::settings();
			$char_limit = ! empty( $settings['body_char_limit'] ) ? (int) $settings['body_char_limit'] : self::BODY_CHAR_LIMIT;
		} else {
			$char_limit = (int) $char_limit;
		}
		return $char_limit > 0 ? $char_limit : self::BODY_CHAR_LIMIT;
	}

	/**
	 * Fuerza el tope de caracteres del cuerpo (todo excepto la primera linea
	 * "# Titulo"), por si la IA no respeto la instruccion del prompt. Corta por
	 * el ultimo parrafo/linea completa que quepa, nunca a mitad de frase.
	 */
	protected static function enforce_body_char_limit( $markdown, $char_limit = null ) {
		$char_limit = self::resolve_char_limit( $char_limit );

		$lines = explode( "\n", $markdown );
		$title_line = array_shift( $lines );
		$body = implode( "\n", $lines );
		$body = ltrim( $body, "\n" );

		if ( mb_strlen( $body ) <= $char_limit ) {
			return $markdown;
		}

		$body_lines = explode( "\n", $body );
		$kept       = array();
		$len        = 0;
		foreach ( $body_lines as $line ) {
			$line_len = mb_strlen( $line ) + 1; // +1 por el salto de linea.
			if ( $len + $line_len > $char_limit && ! empty( $kept ) ) {
				break;
			}
			$kept[] = $line;
			$len   += $line_len;
		}

		return $title_line . "\n\n" . implode( "\n", $kept );
	}

	public static function build_prompt( array $data, array $links = array(), $char_limit = null ) {
		$char_limit = self::resolve_char_limit( $char_limit );
		$settings = Scope::settings();

		$datos = array();
		$datos[] = 'Título: ' . $data['title'];
		if ( ! empty( $data['price'] ) ) {
			$datos[] = 'Precio: ' . $data['price'];
		}
		if ( ! empty( $data['stock'] ) ) {
			$datos[] = 'Stock: ' . ( 'in_stock' === $data['stock'] ? 'disponible' : 'agotado' );
		}
		if ( ! empty( $data['short_description'] ) ) {
			$datos[] = 'Descripción corta: ' . $data['short_description'];
		}
		if ( ! empty( $data['content'] ) ) {
			$datos[] = 'Descripción completa: ' . mb_substr( $data['content'], 0, 4000 );
		}
		if ( ! empty( $data['variants'] ) ) {
			$variantes = array();
			foreach ( $data['variants'] as $v ) {
				$variantes[] = $v['attributes'] . ' (' . $v['price'] . ', ' . ( $v['in_stock'] ? 'disponible' : 'agotado' ) . ')';
			}
			$datos[] = 'Variantes: ' . implode( '; ', $variantes );
		}
		if ( ! empty( $data['taxonomies'] ) ) {
			foreach ( $data['taxonomies'] as $tax => $terms ) {
				if ( $terms ) {
					$datos[] = ucfirst( $tax ) . ': ' . implode( ', ', $terms );
				}
			}
		}
		if ( ! empty( $data['custom_fields'] ) ) {
			foreach ( $data['custom_fields'] as $key => $value ) {
				$datos[] = $key . ': ' . $value;
			}
		}

		$prompt  = "Genera una FICHA DE REFERENCIA en Markdown para \"{$data['title']}\", pensada como contexto de búsqueda para un chatbot de atención al cliente. No es texto de marketing, pero SÍ debe ser informativo y completo dentro del límite de longitud: prioriza datos concretos y accionables (horarios por idioma, días concretos, duración, precios por franja/grupo, condiciones de cambio) frente a adjetivos o relleno.\n\n";
		$prompt .= "Formato obligatorio:\n";
		$prompt .= "- Un encabezado # con el título, y nada más en esa línea. NO repitas el título dentro del cuerpo del texto.\n";
		$prompt .= "- Cuerpo en prosa clara y directa (párrafos cortos), no bullets sueltos de una palabra. Puedes usar una lista solo para enumerar horarios/franjas si hay varias, ej.: \"Castellano: miércoles y viernes a las 12:30h. Alemán: sábado a las 15:30h.\".\n";
		$prompt .= "- Si en \"Datos del producto\" aparecen horarios, días de la semana, duración, o precios por tramo (ej. adultos/niños, con/sin visita guiada), inclúyelos SIEMPRE de forma explícita — es la información que más se pregunta y no se puede perder por brevedad.\n";
		$prompt .= "- Incluye solo los datos que existan de verdad en \"Datos del producto\" más abajo. No inventes variedad, temperatura, maridaje, horarios ni notas de cata si no aparecen ahí.\n";
		$prompt .= '- El cuerpo completo (sin contar el título) no debe superar los ' . $char_limit . " caracteres. Si hay que recortar, quita primero adjetivos y frases de ambiente, nunca horarios, días, precios o duración.\n\n";
		$prompt .= "Reglas obligatorias:\n";
		$prompt .= '- Enlaza siempre a la URL del producto: ' . $data['url'] . ". No generes ni menciones ningún otro enlace. No añadas tú mismo ninguna sección de \"disponible en otros idiomas\": se añade automáticamente después de tu respuesta, no la dupliques. Nunca enlaces al propio documento.\n";
		$prompt .= "- NO incluyas avisos genéricos tipo \"precio orientativo\" o \"confirma la disponibilidad\": esos avisos los añade el propio chatbot en su respuesta cuando corresponde, no deben estar guardados en este documento.\n\n";

		$prompt .= "Datos del producto:\n" . implode( "\n", $datos ) . "\n\n";
		$prompt .= 'Idioma de salida: ' . strtoupper( $data['lang'] ) . ". Responde solo con el documento Markdown, sin explicaciones adicionales.";

		return $prompt;
	}

	/**
	 * Documento puente (sin IA): datos factuales breves + bloque de enlaces
	 * de idioma en el mismo formato determinista que el flujo normal. Sin
	 * avisos de precio/stock: esos los añade el chatbot en su respuesta.
	 */
	public static function build_bridge_markdown( array $data, array $links ) {
		$md  = "# {$data['title']}\n\n";
		if ( ! empty( $data['price'] ) ) {
			$md .= "Precio: {$data['price']}.  \n";
		}
		if ( ! empty( $data['stock'] ) ) {
			$md .= 'Estado: ' . ( 'in_stock' === $data['stock'] ? 'disponible' : 'agotado' ) . ".  \n";
		}

		$link_block = self::build_language_links_block( $data['title'], $links );
		if ( $link_block ) {
			$md .= "\n## Disponible también en\n\n" . $link_block . "\n";
		}

		return $md;
	}

	/**
	 * Nombre legible del idioma para el bloque de enlaces cruzados (Tarea 3).
	 */
	protected static function lang_label( $code ) {
		$labels = array(
			'es' => 'Español',
			'en' => 'English',
			'de' => 'Deutsch',
		);
		return isset( $labels[ $code ] ) ? $labels[ $code ] : strtoupper( $code );
	}

	/**
	 * Bloque "[Título]\n[Idioma](url) · [Idioma](url)..." en Markdown, formato
	 * que renderiza bien vía el Parsedown de Genix. Solo incluye idiomas con
	 * traducción real (el array $links ya viene filtrado por quien lo llama).
	 * Vacio si no hay ningun enlace cruzado.
	 */
	public static function build_language_links_block( $title, array $links ) {
		if ( empty( $links ) ) {
			return '';
		}
		$parts = array();
		foreach ( $links as $lang => $url ) {
			$parts[] = '[' . self::lang_label( $lang ) . '](' . $url . ')';
		}
		return '[' . $title . ']' . "\n" . implode( ' · ', $parts );
	}
}
