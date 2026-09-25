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
	 *
	 * $custom_prompt: instrucciones propias de ESTE documento concreto
	 * (columna custom_prompt de la fila del Registro, Pieza 2). Vacio/null usa
	 * el prompt generico de siempre, sin cambios. Nunca sustituye los datos
	 * reales de $data: ver build_prompt(), que lo añade como instrucciones
	 * adicionales con el límite explícito de no inventar ni sustituir datos.
	 */
	public static function generate( array $data, array $versions = array(), $char_limit = null, $custom_prompt = '' ) {
		$config = self::ai_config();
		if ( ! $config ) {
			return new \WP_Error( 'wookb_no_ai_connection', __( 'El origen de IA seleccionado no está conectado.', 'ai-knowledge' ) );
		}

		$char_limit = self::resolve_char_limit( $char_limit );

		// El apartado "Idiomas" (idioma del documento y versiones en otros
		// idiomas) NO se pide a la IA: se anexa aqui de forma deterministica
		// tras la respuesta, para garantizar el formato exacto sin depender de
		// que el modelo lo reproduzca bien. El prompt le pide explicitamente que
		// NO genere esa seccion el mismo, para no duplicarla.
		$prompt = self::build_prompt( $data, $versions, $char_limit, $custom_prompt );

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

		// "Datos de compra": precio, envio, impuestos, variaciones y campos
		// personalizados los anexa el CODIGO (sin IA, desde la BD), igual que
		// el bloque "Disponible tambien en" de abajo: asi estan si o si, sin
		// depender del prompt ni del limite de caracteres (este bloque NO
		// cuenta para $char_limit: se añade DESPUES de enforce_body_char_limit).
		$data['store_info_url'] = self::store_info_url();
		$purchase_block = self::build_purchase_data_block( $data );
		if ( '' !== $purchase_block ) {
			$response .= "\n\n" . $purchase_block;
		}

		$section = Languages::build_section( $data['lang'], $versions );
		if ( '' !== $section ) {
			$response .= "\n\n" . $section;
		}
		$response .= "\n";

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

	public static function build_prompt( array $data, array $versions = array(), $char_limit = null, $custom_prompt = '' ) {
		$char_limit = self::resolve_char_limit( $char_limit );
		$settings = Scope::settings();

		// Si el extractor aporto el bloque factual de compra (productos
		// WooCommerce, ver Extractor_Woo::purchase_data()), precio/stock/
		// variantes NO se le dan a la IA: los añade el codigo despues
		// (build_purchase_data_block()). Sin ese bloque (otros tipos de
		// contenido, o un extractor antiguo) se conserva el comportamiento de siempre.
		$has_purchase = ! empty( $data['purchase'] );

		$datos = array();
		$datos[] = 'Título: ' . $data['title'];
		if ( ! $has_purchase && ! empty( $data['price'] ) ) {
			$datos[] = 'Precio: ' . $data['price'];
		}
		if ( ! $has_purchase && ! empty( $data['stock'] ) ) {
			$datos[] = 'Stock: ' . ( 'in_stock' === $data['stock'] ? 'disponible' : 'agotado' );
		}
		if ( ! empty( $data['short_description'] ) ) {
			$datos[] = 'Descripción corta: ' . $data['short_description'];
		}
		if ( ! empty( $data['content'] ) ) {
			$datos[] = 'Descripción completa: ' . mb_substr( $data['content'], 0, 4000 );
		}
		if ( ! $has_purchase && ! empty( $data['variants'] ) ) {
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
		$prompt .= '- El cuerpo completo (sin contar el título) no debe superar los ' . $char_limit . " caracteres. Si hay que recortar, quita primero adjetivos y frases de ambiente, nunca horarios, días, precios por tramo o duración.\n\n";
		$prompt .= "Reglas obligatorias:\n";
		$prompt .= '- Enlaza siempre a la URL del producto: ' . $data['url'] . ". No generes ni menciones ningún otro enlace. No añadas tú mismo ninguna sección de idiomas ni de \"disponible en otros idiomas\": se añade automáticamente después de tu respuesta, no la dupliques. Nunca enlaces al propio documento.\n";
		$prompt .= "- NO incluyas avisos genéricos tipo \"precio orientativo\" o \"confirma la disponibilidad\": esos avisos los añade el propio chatbot en su respuesta cuando corresponde, no deben estar guardados en este documento.\n";
		if ( $has_purchase ) {
			$prompt .= "- Tu texto es SOLO la descripción del producto. NO menciones el precio de la tienda, descuentos ni ofertas, stock o disponibilidad, envío, impuestos ni variaciones/formatos: todo eso se añade automáticamente después, en un bloque \"Datos de compra\" generado desde la base de datos. Sí conserva los precios por tramo o condiciones (ej. adultos/niños, con/sin visita guiada) que aparezcan en la descripción o en los campos de \"Datos del producto\".\n";
		}
		$prompt .= "\n";

		$prompt .= "Datos del producto:\n" . implode( "\n", $datos ) . "\n\n";

		// Pieza 2: instrucciones propias de este documento (columna
		// custom_prompt de la fila del Registro). Se añaden DESPUES de "Datos
		// del producto" y con un límite explícito: complementan el estilo o
		// el enfoque, pero nunca sustituyen ni inventan datos reales -- los
		// datos de arriba mandan siempre si hay contradicción.
		if ( ! empty( $custom_prompt ) && is_string( $custom_prompt ) && '' !== trim( $custom_prompt ) ) {
			$prompt .= "Instrucciones adicionales específicas para este documento (aplícalas solo como estilo o énfasis; NUNCA sustituyen, contradicen ni inventan los datos reales de \"Datos del producto\" de arriba, que siempre tienen prioridad):\n" . trim( $custom_prompt ) . "\n\n";
		}

		$prompt .= 'Idioma de salida: ' . Languages::name( $data['lang'] ) . ' (' . strtoupper( $data['lang'] ) . "). Responde solo con el documento Markdown, sin explicaciones adicionales.";

		return $prompt;
	}

	/**
	 * Etiquetas del bloque "Datos de compra". Por decision del usuario, en
	 * español fijo y SIN __() por ahora (el bloque va dentro del contenido
	 * generado, no es interfaz). Un unico array para poder traducirlas
	 * despues (PENDIENTE: internacionalizar).
	 */
	const PURCHASE_LABELS = array(
		'heading'          => 'Datos de compra',
		'h_price'          => 'Precio y disponibilidad',
		'h_shipping'       => 'Envío',
		'h_tax'            => 'Impuestos',
		'h_variations'     => 'Variaciones',
		'h_custom'         => 'Campos personalizados',
		'price'            => 'Precio',
		'regular_price'    => 'Precio anterior',
		'discount'         => 'Descuento',
		'sale_until'       => 'Oferta válida hasta',
		'availability'     => 'Disponibilidad',
		'in_stock'         => 'En stock',
		'out_of_stock'     => 'Agotado',
		'on_backorder'     => 'Disponible bajo reserva',
		'units'            => 'unidades',
		'physical'         => 'Producto físico: requiere envío',
		'virtual'          => 'Producto virtual: no requiere envío',
		'downloadable'     => 'Descargable',
		'shipping_class'   => 'Clase de envío',
		'weight'           => 'Peso',
		'dimensions'       => 'Dimensiones',
		'shipping_zones'   => 'Zonas y tarifas de envío',
		'tax_status'       => 'Estado fiscal',
		'tax_taxable'      => 'Sujeto a impuestos',
		'tax_shipping'     => 'Solo el envío está sujeto a impuestos',
		'tax_none'         => 'Sin impuestos',
		'tax_class'        => 'Clase fiscal',
		'tax_rates'        => 'Tipos aplicables',
		'prices_incl'      => 'Los precios de la tienda incluyen impuestos',
		'prices_excl'      => 'Los precios de la tienda no incluyen impuestos',
		'display_incl'     => 'Se muestran con impuestos incluidos',
		'display_excl'     => 'Se muestran sin impuestos',
		'variation'        => 'Variación',
		'sku'              => 'SKU',
		'more_variations'  => 'y %d más',
		'store_info_link'  => 'Información de tienda',
	);

	/** URL publica del documento "informacion de tienda" si ya existe, o ''. */
	protected static function store_info_url() {
		if ( ! class_exists( '\AIKB\Store_Info_Doc' ) ) {
			return '';
		}
		$row = Registry::find( Store_Info_Doc::SOURCE_ID_STORE_INFO, Languages::main_language() );
		return ( $row && $row->md_path ) ? Markdown_Store::public_url( $row->md_path ) : '';
	}

	protected static function stock_label( $status ) {
		$l = self::PURCHASE_LABELS;
		if ( 'outofstock' === $status || 'out_of_stock' === $status ) {
			return $l['out_of_stock'];
		}
		if ( 'onbackorder' === $status ) {
			return $l['on_backorder'];
		}
		return $l['in_stock'];
	}

	/**
	 * Bloque Markdown "## Datos de compra": precio, envio, impuestos,
	 * variaciones y campos personalizados. PURA: solo formatea $data (no
	 * llama a WooCommerce ni a WP, salvo la etiqueta de campos
	 * personalizados vía Scope si existe), para poder probarla desde CLI.
	 * Cada seccion sin datos se omite sin ruido; devuelve '' si no queda nada.
	 *
	 * Productos (existe $data['purchase']): precio y disponibilidad, envio,
	 * impuestos, variaciones y campos personalizados. Resto de tipos: solo
	 * campos personalizados.
	 */
	public static function build_purchase_data_block( array $data ) {
		$l        = self::PURCHASE_LABELS;
		$sections = array();
		$p        = ! empty( $data['purchase'] ) && is_array( $data['purchase'] ) ? $data['purchase'] : array();

		if ( $p ) {
			// Precio y disponibilidad.
			$rows = array();
			if ( ! empty( $p['price'] ) ) {
				$rows[] = '- ' . $l['price'] . ': ' . $p['price'];
			}
			if ( ! empty( $p['regular_price'] ) ) {
				$rows[] = '- ' . $l['regular_price'] . ': ' . $p['regular_price'];
			}
			if ( isset( $p['discount_percent'] ) && null !== $p['discount_percent'] ) {
				$rows[] = '- ' . $l['discount'] . ': ' . (int) $p['discount_percent'] . '%';
			}
			if ( ! empty( $p['sale_until'] ) ) {
				$rows[] = '- ' . $l['sale_until'] . ': ' . $p['sale_until'];
			}
			if ( ! empty( $p['stock_status'] ) ) {
				$avail = self::stock_label( $p['stock_status'] );
				if ( isset( $p['stock_quantity'] ) && null !== $p['stock_quantity'] ) {
					$avail .= ' (' . (int) $p['stock_quantity'] . ' ' . $l['units'] . ')';
				}
				$rows[] = '- ' . $l['availability'] . ': ' . $avail;
			}
			if ( $rows ) {
				$sections[] = "### " . $l['h_price'] . "\n\n" . implode( "\n", $rows );
			}

			// Envio (solo lo del producto; zonas/tarifas -> documento de tienda).
			$rows = array();
			if ( ! empty( $p['shipping'] ) && is_array( $p['shipping'] ) ) {
				$s = $p['shipping'];
				if ( isset( $s['needs_shipping'] ) ) {
					$rows[] = '- ' . ( $s['needs_shipping'] ? $l['physical'] : $l['virtual'] );
				}
				if ( ! empty( $s['downloadable'] ) ) {
					$rows[] = '- ' . $l['downloadable'];
				}
				if ( ! empty( $s['class'] ) ) {
					$rows[] = '- ' . $l['shipping_class'] . ': ' . $s['class'];
				}
				if ( ! empty( $s['weight'] ) ) {
					$rows[] = '- ' . $l['weight'] . ': ' . $s['weight'];
				}
				if ( ! empty( $s['dimensions'] ) ) {
					$rows[] = '- ' . $l['dimensions'] . ': ' . $s['dimensions'];
				}
			}
			if ( $rows && ! empty( $data['store_info_url'] ) ) {
				$rows[] = '- ' . $l['shipping_zones'] . ': [' . $l['store_info_link'] . '](' . $data['store_info_url'] . ')';
			}
			if ( $rows ) {
				$sections[] = "### " . $l['h_shipping'] . "\n\n" . implode( "\n", $rows );
			}

			// Impuestos.
			$rows = array();
			if ( ! empty( $p['tax'] ) && is_array( $p['tax'] ) && ! empty( $p['tax']['enabled'] ) ) {
				$t = $p['tax'];
				$status_map = array( 'taxable' => 'tax_taxable', 'shipping' => 'tax_shipping', 'none' => 'tax_none' );
				if ( ! empty( $t['status'] ) && isset( $status_map[ $t['status'] ] ) ) {
					$rows[] = '- ' . $l['tax_status'] . ': ' . $l[ $status_map[ $t['status'] ] ];
				}
				if ( ! empty( $t['class'] ) ) {
					$rows[] = '- ' . $l['tax_class'] . ': ' . $t['class'];
				}
				if ( ! empty( $t['rates'] ) ) {
					$rows[] = '- ' . $l['tax_rates'] . ': ' . implode( ', ', $t['rates'] );
				}
				if ( isset( $t['prices_include'] ) ) {
					$rows[] = '- ' . ( $t['prices_include'] ? $l['prices_incl'] : $l['prices_excl'] );
				}
				if ( ! empty( $t['display_shop'] ) ) {
					$rows[] = '- ' . ( 'incl' === $t['display_shop'] ? $l['display_incl'] : $l['display_excl'] );
				}
			}
			if ( $rows ) {
				$sections[] = "### " . $l['h_tax'] . "\n\n" . implode( "\n", $rows );
			}

			// Variaciones (tabla).
			if ( ! empty( $p['variations'] ) && is_array( $p['variations'] ) ) {
				$table   = array();
				$table[] = '| ' . $l['variation'] . ' | ' . $l['price'] . ' | ' . $l['regular_price'] . ' | ' . $l['discount'] . ' | ' . $l['availability'] . ' | ' . $l['sku'] . ' |';
				$table[] = '|---|---|---|---|---|---|';
				foreach ( $p['variations'] as $v ) {
					$avail = self::stock_label( isset( $v['stock_status'] ) ? $v['stock_status'] : 'instock' );
					if ( isset( $v['stock_quantity'] ) && null !== $v['stock_quantity'] ) {
						$avail .= ' (' . (int) $v['stock_quantity'] . ')';
					}
					$cells = array(
						isset( $v['attributes'] ) ? $v['attributes'] : '',
						isset( $v['price'] ) ? $v['price'] : '',
						isset( $v['regular_price'] ) ? $v['regular_price'] : '',
						( isset( $v['discount_percent'] ) && null !== $v['discount_percent'] ) ? (int) $v['discount_percent'] . '%' : '',
						$avail,
						isset( $v['sku'] ) ? $v['sku'] : '',
					);
					// Un "|" dentro de una celda rompe la tabla Markdown.
					$cells   = array_map(
						function ( $c ) {
							return str_replace( array( '|', "\n" ), array( '/', ' ' ), (string) $c );
						},
						$cells
					);
					$table[] = '| ' . implode( ' | ', $cells ) . ' |';
				}
				if ( ! empty( $p['variations_more'] ) ) {
					$table[] = '';
					$table[] = sprintf( $l['more_variations'], (int) $p['variations_more'] ) . '.';
				}
				$sections[] = "### " . $l['h_variations'] . "\n\n" . implode( "\n", $table );
			}
		}

		// Campos personalizados (cualquier tipo de contenido).
		if ( ! empty( $data['custom_fields'] ) && is_array( $data['custom_fields'] ) ) {
			$rows      = array();
			$post_type = isset( $data['post_type'] ) ? $data['post_type'] : '';
			foreach ( $data['custom_fields'] as $key => $value ) {
				if ( is_array( $value ) || '' === trim( (string) $value ) ) {
					continue;
				}
				$label  = class_exists( '\AIKB\Scope' ) ? Scope::custom_field_label( $post_type, $key ) : $key;
				$rows[] = '- ' . $label . ': ' . trim( (string) $value );
			}
			if ( $rows ) {
				$sections[] = "### " . $l['h_custom'] . "\n\n" . implode( "\n", $rows );
			}
		}

		if ( ! $sections ) {
			return '';
		}

		return '## ' . $l['heading'] . "\n\n" . implode( "\n\n", $sections );
	}
}
