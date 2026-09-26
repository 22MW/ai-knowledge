<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Genera un borrador de chatbot-system-prompt.md a partir de respuestas
 * cortas del usuario (tono, negocio, qué hacer sin respuesta, límites...) y,
 * opcionalmente, del contenido real de unas páginas/posts de referencia.
 * También "normaliza" (pule redacción sin cambiar el fondo) un texto que el
 * usuario ya ha editado a mano.
 *
 * No sustituye la edición manual del .md: solo rellena un primer borrador
 * editable. El guardado real sigue pasando por Chatbot_Prompt::sync().
 */
class Chatbot_Prompt_Builder {

	const ANSWERS_OPTION = 'wookb_chatbot_prompt_answers';
	const BUSINESS_SUMMARY_OPTION = 'wookb_business_summary';

	const MAX_LENGTH = 2000;

	/**
	 * Preguntas del cuestionario. 'key' => [label, placeholder, type, group].
	 * 'group' separa qué pregunta vive en qué pestaña del admin: 'negocio'
	 * (datos del negocio en si, validos con o sin WooCommerce -- lo
	 * especifico de WooCommerce vive en su propia pestaña) o 'chatbot'
	 * (como debe comportarse el bot). generate_draft() recorre TODAS sin
	 * distinguir grupo: el prompt final combina ambos.
	 *
	 * Se guardan todas en una sola option (ANSWERS_OPTION) pase lo que pase
	 * -- save_answers() solo sobreescribe las keys presentes en cada envio,
	 * para que guardar la pestaña Negocio no borre lo ya guardado en
	 * Chatbot y viceversa.
	 */
	public static function questions() {
		return array(
			// --- Grupo "negocio": tambien alimenta llm/info.md y llms.txt
			// (ver write_info_doc()), no solo el prompt del chatbot.
			'nombre_negocio'    => array(
				'label'       => __( 'Nombre del negocio / marca', 'ai-knowledge' ),
				'placeholder' => __( 'Ej: el nombre con el que el chatbot debe referirse al negocio.', 'ai-knowledge' ),
				'type'        => 'text',
				'group'       => 'negocio',
			),
			'direccion'         => array(
				'label'       => __( 'Dirección', 'ai-knowledge' ),
				'placeholder' => __( 'Dirección postal del negocio, si es relevante para el cliente (local físico, recogida, visitas...).', 'ai-knowledge' ),
				'type'        => 'text',
				'group'       => 'negocio',
			),
			'negocio'           => array(
				'label'       => __( 'Enfoque del negocio', 'ai-knowledge' ),
				'placeholder' => __( 'Ej: negocio familiar, qué vende o qué servicios ofrece (productos propios, experiencias, citas...).', 'ai-knowledge' ),
				'type'        => 'textarea',
				'group'       => 'negocio',
			),
			'publico_objetivo'  => array(
				'label'       => __( 'Público objetivo', 'ai-knowledge' ),
				'placeholder' => __( 'Ej: tipo de cliente habitual — ayuda al chatbot a calibrar tono y nivel de detalle.', 'ai-knowledge' ),
				'type'        => 'textarea',
				'group'       => 'negocio',
			),
			'horario_atencion'  => array(
				'label'       => __( 'Horario de atención humana', 'ai-knowledge' ),
				'placeholder' => __( 'Cuándo hay alguien real disponible, aparte del chatbot (que suele estar activo 24/7).', 'ai-knowledge' ),
				'type'        => 'text',
				'group'       => 'negocio',
			),
			'contacto'          => array(
				'label'       => __( 'Datos de contacto directo', 'ai-knowledge' ),
				'placeholder' => __( 'Teléfono, WhatsApp, email... deja vacío para mantener los que ya haya en el prompt actual.', 'ai-knowledge' ),
				'type'        => 'textarea',
				'group'       => 'negocio',
			),
			// --- Grupo "chatbot": como debe comportarse el bot.
			'tono'              => array(
				'label'       => __( 'Tono de la marca', 'ai-knowledge' ),
				'placeholder' => __( 'Ej: cercano, profesional, cálido, sin lenguaje robótico.', 'ai-knowledge' ),
				'type'        => 'text',
				'group'       => 'chatbot',
			),
			'sin_respuesta'     => array(
				'label'       => __( 'Qué hacer cuando no hay información', 'ai-knowledge' ),
				'placeholder' => __( 'Ej: no inventar ni dar largas, decir que no se tiene esa información y dar el contacto directo.', 'ai-knowledge' ),
				'type'        => 'textarea',
				'group'       => 'chatbot',
			),
			'limites'           => array(
				'label'       => __( 'Qué NO debe hacer nunca el chatbot', 'ai-knowledge' ),
				'placeholder' => __( 'Ej: no gestionar pagos ni reclamaciones directamente, no prometer descuentos, no inventar precios ni stock.', 'ai-knowledge' ),
				'type'        => 'textarea',
				'group'       => 'chatbot',
			),
			'cierre_conversacion' => array(
				'label'       => __( 'Cómo cerrar una conversación', 'ai-knowledge' ),
				'placeholder' => __( 'Ej: despedirse con cordialidad e invitar a seguir preguntando si hace falta.', 'ai-knowledge' ),
				'type'        => 'text',
				'group'       => 'chatbot',
			),
			'longitud_respuesta'  => array(
				'label'       => __( 'Longitud de respuesta preferida', 'ai-knowledge' ),
				'placeholder' => __( 'Ej: respuestas cortas y directas, o explicadas con más detalle.', 'ai-knowledge' ),
				'type'        => 'text',
				'group'       => 'chatbot',
			),
			'uso_emojis'        => array(
				'label'       => __( 'Uso de emojis', 'ai-knowledge' ),
				'placeholder' => __( 'Ej: sin emojis, o alguno puntual con moderación.', 'ai-knowledge' ),
				'type'        => 'text',
				'group'       => 'chatbot',
			),
		);
	}

	/** Preguntas de un grupo concreto ('negocio'|'chatbot'), mismo orden. */
	public static function questions_by_group( $group ) {
		return array_filter(
			self::questions(),
			function ( $q ) use ( $group ) {
				return $group === $q['group'];
			}
		);
	}

	public static function get_saved_answers() {
		$defaults = array_fill_keys( array_keys( self::questions() ), '' );
		return wp_parse_args( get_option( self::ANSWERS_OPTION, array() ), $defaults );
	}

	/**
	 * Parte de lo ya guardado (no de un array vacio): las pestañas Negocio y
	 * Chatbot envian cada una solo las keys de su propio grupo, y guardar
	 * una no debe borrar lo ya guardado de la otra.
	 */
	public static function save_answers( array $answers ) {
		$clean = self::get_saved_answers();
		foreach ( self::questions() as $key => $q ) {
			if ( isset( $answers[ $key ] ) ) {
				$clean[ $key ] = sanitize_textarea_field( $answers[ $key ] );
			}
		}
		update_option( self::ANSWERS_OPTION, $clean, false );
		return $clean;
	}

	/**
	 * Resumen publico independiente del campo fuente "Enfoque del negocio".
	 * Si aun no existe la opcion nueva, conserva el comportamiento historico.
	 */
	public static function get_business_summary() {
		$summary = get_option( self::BUSINESS_SUMMARY_OPTION, null );
		if ( null !== $summary ) {
			return (string) $summary;
		}

		$answers = self::get_saved_answers();
		return (string) $answers['negocio'];
	}

	public static function save_business_summary( $summary ) {
		return update_option( self::BUSINESS_SUMMARY_OPTION, sanitize_textarea_field( $summary ), false );
	}

	/**
	 * Escribe wp-content/ai-knowledge/info.md con la info general del negocio (mismo
	 * cuestionario que arma el prompt: nombre, resumen, contacto, horario,
	 * idiomas) -- una sola fuente para el prompt del chatbot Y para el
	 * resumen de llms.txt, en vez de mantener el dato en tres sitios. Se
	 * regenera cada vez que se guarda el prompt (Admin::save_prompt_draft()).
	 */
	public static function write_info_doc() {
		$answers = self::get_saved_answers();

		// El resumen ("negocio") ya se muestra en la cita "> ..." de llms.txt
		// (Llms_Txt::summary(), misma fuente) -- no se repite aquí para evitar
		// que el mismo texto aparezca dos veces seguidas en llms.txt.
		$titulo  = ! empty( $answers['nombre_negocio'] ) ? $answers['nombre_negocio'] : get_bloginfo( 'name' );
		$lines   = array();
		$lines[] = '# ' . $titulo;
		if ( ! empty( $answers['direccion'] ) ) {
			$lines[] = 'Dirección: ' . $answers['direccion'];
		}
		if ( ! empty( $answers['publico_objetivo'] ) ) {
			$lines[] = 'Público objetivo: ' . $answers['publico_objetivo'];
		}
		$lines[] = ( count( Languages::languages() ) > 1 ? 'Idiomas: ' : 'Idioma: ' ) . Languages::summary_text();
		if ( ! empty( $answers['horario_atencion'] ) ) {
			$lines[] = 'Horario de atención humana: ' . $answers['horario_atencion'];
		}
		if ( ! empty( $answers['contacto'] ) ) {
			$lines[] = 'Contacto: ' . $answers['contacto'];
		}

		// Este documento no tiene version por idioma (siempre es el mismo, en el
		// idioma principal del sitio): el apartado "Idiomas" dice en que idioma
		// esta y, si la web tiene varios, donde encontrar los demas.
		$section = Languages::site_section( Languages::main_language() );
		if ( '' !== $section ) {
			$lines[] = '';
			$lines[] = $section;
		}

		$content = implode( "\n", $lines ) . "\n";

		$dir = Markdown_Store::base_dir();
		wp_mkdir_p( $dir );
		file_put_contents( $dir . '/info.md', $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_put_contents

		if ( class_exists( '\AIKB\Llms_Txt' ) ) {
			Llms_Txt::invalidate();
		}

		return $content;
	}

	public static function info_doc_url() {
		return trailingslashit( Markdown_Store::base_url() ) . 'info.md';
	}

	/**
	 * Genera/pule con IA el resumen de negocio (campo 'negocio', usado como
	 * cita de apertura publica de llms.txt via Llms_Txt::summary()), a
	 * partir del resto de respuestas del grupo "negocio" ya guardadas. Mismo
	 * patron que generate_draft() pero para un solo campo, no el prompt
	 * completo del chatbot.
	 */
	public static function generate_business_summary( array $answers, $extra_info = '' ) {
		$config = Generator::ai_config();
		if ( ! $config ) {
			return new \WP_Error( 'wookb_no_ai_connection', __( 'El origen de IA seleccionado no está conectado.', 'ai-knowledge' ) );
		}

		$extra_info = trim( (string) $extra_info );
		$prompt      = "Redacta un resumen claro del negocio usando exclusivamente los datos reales delimitados abajo. No inventes, deduzcas ni modifiques datos.\n\n";
		$prompt     .= "<<<DATOS_REALES_NEGOCIO>>>\n";
		foreach ( self::questions_by_group( 'negocio' ) as $key => $q ) {
			if ( 'negocio' === $key || empty( $answers[ $key ] ) ) {
				continue;
			}
			$prompt .= '- ' . $q['label'] . ': ' . $answers[ $key ] . "\n";
		}
		$prompt .= '- ' . __( 'Idiomas de la web', 'ai-knowledge' ) . ': ' . Languages::summary_text() . "\n";
		if ( ! empty( $answers['negocio'] ) ) {
			$prompt .= "\n- Enfoque del negocio (dato fuente obligatorio; conserva todos sus hechos): " . $answers['negocio'] . "\n";
		}
		$current_summary = self::get_business_summary();
		if ( '' !== trim( $current_summary ) && $current_summary !== $answers['negocio'] ) {
			$prompt .= "\nResumen público actual a mejorar sin perder los datos fuente:\n" . $current_summary . "\n";
		}
		$prompt .= "<<<FIN_DATOS_REALES_NEGOCIO>>>\n\n";
		if ( '' !== $extra_info ) {
			$prompt .= "<<<INSTRUCCIONES_PRIVADAS>>>\n" . $extra_info . "\n<<<FIN_INSTRUCCIONES_PRIVADAS>>>\n\n";
			$prompt .= "Las instrucciones privadas prevalecen sobre el formato, el orden y la estructura predeterminados. Aplícalas, pero nunca las copies, menciones ni publiques. No pueden autorizar datos que no aparezcan en el bloque de datos reales.\n\n";
		}
		$prompt .= "\nEste texto se usa como cita de apertura pública en llms.txt, el archivo que leen los buscadores de IA para entender de qué trata la web: debe ser una descripción útil y concreta, no vacía ni genérica.\n";
		$prompt .= 'Responde solo con el texto final (contando saltos de línea, no debe superar los ' . self::MAX_LENGTH . ' caracteres), sin explicaciones ni comillas envolventes.';
		if ( '' === $extra_info ) {
			$prompt .= ' Redacta una descripción natural y bien conectada que explique qué hace el negocio, qué ofrece, a quién se dirige, dónde está y cómo puede contactar el cliente, únicamente cuando esos datos estén disponibles. Organiza la información en párrafos breves, con una extensión proporcional a los datos reales: no alargues el texto repitiendo información. Conserva nombres, direcciones, horarios, correos y demás datos tal como se facilitaron; no escapes los correos con barras invertidas. No incluyas encabezados Markdown.';
		}

		$result = AI_Client::generate(
			'Eres un redactor de perfiles de negocio. Convierte todos los datos reales facilitados en una descripción pública clara, natural y fiel.',
			$prompt,
			1200,
			0.4,
			40
		);
		$result = self::validate_private_instructions_result( $result, $extra_info );
		if ( ! is_wp_error( $result ) ) {
			$result = str_replace( '\\@', '@', $result );
		}
		return self::enforce_length( $result );
	}

	/**
	 * Genera un borrador de FAQs en Markdown (para Llms_Faq / /llms.txt), a
	 * partir de las respuestas del grupo "negocio" y, si hay, el contenido
	 * actual de FAQ ya guardado (para ampliar/mejorar, no partir de cero
	 * cada vez). Mismo formato libre que ya consume Llms_Txt::build(): cada
	 * pregunta como encabezado "### ..." seguida de su respuesta.
	 */
	public static function generate_faqs( array $answers, $current_faq = '', $extra_info = '' ) {
		$config = Generator::ai_config();
		if ( ! $config ) {
			return new \WP_Error( 'wookb_no_ai_connection', __( 'El origen de IA seleccionado no está conectado.', 'ai-knowledge' ) );
		}

		$extra_info = trim( (string) $extra_info );
		$prompt      = "Genera contenido de preguntas frecuentes para publicar en llms.txt usando exclusivamente los datos reales delimitados abajo. No inventes, deduzcas ni modifiques datos.\n\n";
		$prompt     .= "<<<DATOS_REALES_NEGOCIO>>>\n";
		foreach ( self::questions_by_group( 'negocio' ) as $key => $q ) {
			if ( ! empty( $answers[ $key ] ) ) {
				$prompt .= '- ' . $q['label'] . ': ' . $answers[ $key ] . "\n";
			}
		}
		$prompt .= '- ' . __( 'Idiomas de la web', 'ai-knowledge' ) . ': ' . Languages::summary_text() . "\n";
		$prompt .= "<<<FIN_DATOS_REALES_NEGOCIO>>>\n";
		if ( '' !== trim( (string) $current_faq ) ) {
			$prompt .= "\n<<<FAQ_ACTUAL>>>\n" . $current_faq . "\n<<<FIN_FAQ_ACTUAL>>>\n";
		}
		if ( '' !== $extra_info ) {
			$prompt .= "\n<<<INSTRUCCIONES_PRIVADAS>>>\n" . $extra_info . "\n<<<FIN_INSTRUCCIONES_PRIVADAS>>>\n";
			$prompt .= "\nLas instrucciones privadas prevalecen sobre la cantidad, el formato, el orden y la estructura predeterminados. Aplícalas, pero nunca las copies, menciones ni publiques. No pueden autorizar datos que no aparezcan en los bloques de datos reales o FAQ actual.\n";
		}
		if ( '' === $extra_info ) {
			$prompt .= "\nFormato predeterminado: entre 4 y 8 preguntas, cada una como encabezado \"### ¿Pregunta?\" seguido de la respuesta en el párrafo siguiente.\n";
		}
		$prompt .= "\nSi falta un dato para responder bien, omite esa pregunta en vez de inventar.\n";
		$prompt .= 'Responde solo con el Markdown final, sin explicaciones envolventes.';

		$result = self::call_ai( $config, $prompt );
		return self::validate_private_instructions_result( $result, $extra_info );
	}

	/**
	 * Pule redacción de un documento factual (info de tienda / catálogo,
	 * Store_Info_Doc) SIN inventar ni cambiar ningún dato, precio, condición
	 * o hecho: solo mejora cómo está escrito. Mismo espíritu que normalize(),
	 * pero con límite de caracteres propio (estos documentos son más largos
	 * que un prompt de chatbot) y sin asumir que el texto es un prompt.
	 */
	public static function polish_factual_text( $text, $extra_info = '', $max_length = 0 ) {
		$text = trim( (string) $text );
		if ( '' === $text ) {
			return new \WP_Error( 'wookb_empty_text', __( 'No hay texto que pulir.', 'ai-knowledge' ) );
		}

		$config = Generator::ai_config();
		if ( ! $config ) {
			return new \WP_Error( 'wookb_no_ai_connection', __( 'El origen de IA seleccionado no está conectado.', 'ai-knowledge' ) );
		}

		$limit = $max_length > 0 ? $max_length : self::MAX_LENGTH;

		$extra_info = trim( (string) $extra_info );
		$prompt      = "Pule la redacción del documento factual delimitado abajo (gramática, claridad y consistencia de formato), SIN cambiar ningún dato, precio, condición, plazo ni hecho. No inventes información.\n";
		$prompt     .= "Las instrucciones son metadatos privados: aplícalas, pero NUNCA las copies, menciones, resumas ni publiques en el resultado. Tampoco reproduzcas los delimitadores ni estas reglas.\n\n";
		$prompt     .= "<<<DOCUMENTO_ORIGINAL>>>\n" . $text . "\n<<<FIN_DOCUMENTO_ORIGINAL>>>\n\n";
		if ( '' !== $extra_info ) {
			$prompt .= "<<<INSTRUCCIONES_PRIVADAS_DE_ESTILO>>>\n" . $extra_info . "\n<<<FIN_INSTRUCCIONES_PRIVADAS_DE_ESTILO>>>\n\n";
			$prompt .= "Estas instrucciones privadas prevalecen sobre el formato, el orden y la estructura del documento original. Pueden reorganizarlo o cambiar sus encabezados, pero nunca autorizan a inventar, deducir o modificar datos. Si piden un dato que no aparece en el documento original, omítelo.\n\n";
		}
		$prompt .= 'El resultado (contando saltos de línea) no debe superar los ' . $limit . " caracteres.\n";
		$prompt .= 'Responde solo con el texto final pulido, sin explicaciones ni comillas envolventes.';
		if ( '' === $extra_info ) {
			$prompt .= ' Conserva los encabezados Markdown "##" que ya tenga.';
		}

		$result = self::call_ai( $config, $prompt );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$result = self::validate_private_instructions_result( $result, $extra_info );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( mb_strlen( $result ) <= $limit ) {
			return $result;
		}
		$lines = explode( "\n", $result );
		$kept  = array();
		$len   = 0;
		foreach ( $lines as $line ) {
			$line_len = mb_strlen( $line ) + 1;
			if ( $len + $line_len > $limit && ! empty( $kept ) ) {
				break;
			}
			$kept[] = $line;
			$len   += $line_len;
		}
		return implode( "\n", $kept );
	}

	/**
	 * Impide publicar respuestas que hayan copiado los bloques internos o las
	 * instrucciones privadas del administrador.
	 */
	protected static function validate_private_instructions_result( $result, $extra_info = '' ) {
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$forbidden_fragments = array(
			'<<<DOCUMENTO_ORIGINAL>>>',
			'<<<FIN_DOCUMENTO_ORIGINAL>>>',
			'<<<DATOS_REALES_NEGOCIO>>>',
			'<<<FIN_DATOS_REALES_NEGOCIO>>>',
			'<<<FAQ_ACTUAL>>>',
			'<<<FIN_FAQ_ACTUAL>>>',
			'<<<INSTRUCCIONES_PRIVADAS>>>',
			'<<<FIN_INSTRUCCIONES_PRIVADAS>>>',
			'<<<INSTRUCCIONES_PRIVADAS_DE_ESTILO>>>',
			'<<<FIN_INSTRUCCIONES_PRIVADAS_DE_ESTILO>>>',
			'Información extra a tener en cuenta',
			'El resultado (contando saltos de línea)',
		);
		foreach ( $forbidden_fragments as $fragment ) {
			if ( false !== mb_stripos( $result, $fragment ) ) {
				return new \WP_Error( 'wookb_ai_leaked_instructions', __( 'La IA devolvió instrucciones internas dentro del contenido. No se ha publicado el resultado.', 'ai-knowledge' ) );
			}
		}

		$extra_info = trim( (string) $extra_info );
		if ( '' !== $extra_info && false !== mb_stripos( $result, $extra_info ) ) {
			return new \WP_Error( 'wookb_ai_leaked_instructions', __( 'La IA copió las instrucciones de redacción dentro del contenido. No se ha publicado el resultado.', 'ai-knowledge' ) );
		}

		return $result;
	}

	/**
	 * Lee el contenido real (extraído a texto plano) de hasta 3 páginas/posts
	 * de referencia, a partir de IDs o URLs sueltas separadas por coma/salto
	 * de línea. Silencioso ante entradas que no resuelven a un post real.
	 */
	public static function fetch_reference_content( $raw_input ) {
		$raw_input = trim( (string) $raw_input );
		if ( '' === $raw_input ) {
			return array();
		}

		$tokens = preg_split( '/[\s,]+/', $raw_input, -1, PREG_SPLIT_NO_EMPTY );
		$tokens = array_slice( $tokens, 0, 3 );

		$pages = array();
		foreach ( $tokens as $token ) {
			$post_id = is_numeric( $token ) ? (int) $token : url_to_postid( $token );
			if ( ! $post_id ) {
				continue;
			}
			$post = get_post( $post_id );
			if ( ! $post || 'publish' !== $post->post_status ) {
				continue;
			}
			$text = wp_strip_all_tags( $post->post_content );
			$text = preg_replace( '/\s+/', ' ', $text );
			$pages[] = array(
				'title'   => $post->post_title,
				'url'     => get_permalink( $post ),
				'content' => mb_substr( trim( $text ), 0, 3000 ),
			);
		}
		return $pages;
	}

	/**
	 * Genera el borrador completo a partir de las respuestas del cuestionario
	 * y, si hay, el contenido de las páginas de referencia.
	 */
	public static function generate_draft( array $answers, array $reference_pages = array(), $extra_info = '' ) {
		$config = Generator::ai_config();
		if ( ! $config ) {
			return new \WP_Error( 'wookb_no_ai_connection', __( 'El origen de IA seleccionado no está conectado.', 'ai-knowledge' ) );
		}

		$prompt  = "Genera el PROMPT DE SISTEMA de un chatbot de atención al cliente para un negocio real, a partir de estas respuestas del propio negocio:\n\n";
		foreach ( self::questions() as $key => $q ) {
			if ( ! empty( $answers[ $key ] ) ) {
				$prompt .= '- ' . $q['label'] . ': ' . $answers[ $key ] . "\n";
			}
		}
		$prompt .= '- ' . __( 'Idiomas de la web', 'ai-knowledge' ) . ': ' . Languages::summary_text() . "\n";

		if ( ! empty( $reference_pages ) ) {
			$prompt .= "\nContenido real de páginas del sitio para tono y datos adicionales (no copiar literal, usar como referencia):\n\n";
			foreach ( $reference_pages as $page ) {
				$prompt .= '### ' . $page['title'] . ' (' . $page['url'] . ")\n" . $page['content'] . "\n\n";
			}
		}

		if ( '' !== trim( (string) $extra_info ) ) {
			$prompt .= "\nInformación extra a tener en cuenta:\n" . trim( $extra_info ) . "\n";
		}

		$prompt .= "\nInstrucciones de formato:\n";
		$prompt .= "- Texto en español, en prosa clara dividida en párrafos cortos por tema (identidad, idioma, precisión/no inventar, precios, enlaces, qué hacer con otras opciones, qué hacer sin respuesta, tono, límites).\n";
		$prompt .= "- No uses encabezados Markdown (#, ##): son párrafos normales, cada uno empieza indicando el tema en negrita simple con dos puntos, como ya hacen los prompts de este tipo de chatbot (ej. \"Idioma: responde siempre...\").\n";
		$prompt .= "- Un solo salto de linea entre parrafos, SIN linea en blanco de por medio (nada de doble salto de linea).\n";
		$prompt .= "- Incluye SIEMPRE, tal cual las dio el negocio, las instrucciones de qué hacer cuando no hay información y los datos de contacto si se han dado.\n";
		$prompt .= "- No inventes datos de contacto, precios ni políticas que no se hayan dado.\n";
		$prompt .= '- El texto completo (contando saltos de linea) no debe superar los ' . self::MAX_LENGTH . " caracteres: es un limite duro del sistema que lo recibe. Prioriza que quepan enteros los datos de contacto y las instrucciones de que hacer sin respuesta; recorta antes las frases de tono/tema si hace falta.\n";
		$prompt .= "- Responde solo con el texto final del prompt, sin explicaciones ni comillas envolventes.";

		return self::enforce_length( self::call_ai( $config, $prompt ) );
	}

	/**
	 * Borrador determinista (sin IA) a partir de las respuestas del cuestionario:
	 * una línea «Etiqueta: valor» por cada campo rellenado, con un máximo de
	 * MAX_LENGTH caracteres. Lo usa el asistente cuando no hay IA y aún no
	 * existe chatbot-system-prompt.md; el ajuste fino se hace después en la
	 * pestaña Genix. Devuelve '' si no hay ninguna respuesta rellenada.
	 */
	public static function build_template_draft( array $answers ) {
		$filled = array();
		foreach ( self::questions() as $key => $q ) {
			if ( 'nombre_negocio' === $key ) {
				continue;
			}
			$value = isset( $answers[ $key ] ) ? trim( preg_replace( '/\s+/', ' ', (string) $answers[ $key ] ) ) : '';
			if ( '' !== $value ) {
				$filled[] = $q['label'] . ': ' . $value;
			}
		}
		if ( empty( $filled ) ) {
			return '';
		}

		$name    = ! empty( $answers['nombre_negocio'] ) ? trim( (string) $answers['nombre_negocio'] ) : get_bloginfo( 'name' );
		$lines   = array();
		/* translators: %s: nombre del negocio */
		$lines[] = sprintf( __( 'Identidad: eres el asistente virtual de %s. Responde solo con la información de su base de conocimiento.', 'ai-knowledge' ), $name );
		$lines[] = __( 'Precisión: no inventes datos, precios ni políticas que no estén en la información disponible.', 'ai-knowledge' );
		$lines   = array_merge( $lines, $filled );
		$lines[] = __( 'Idiomas de la web', 'ai-knowledge' ) . ': ' . Languages::summary_text();

		$text = self::enforce_length( implode( "\n", $lines ) );
		return mb_substr( $text, 0, self::MAX_LENGTH );
	}

	protected static function enforce_length( $text ) {
		if ( is_wp_error( $text ) ) {
			return $text;
		}
		if ( mb_strlen( $text ) <= self::MAX_LENGTH ) {
			return $text;
		}
		$lines = explode( "\n", $text );
		$kept  = array();
		$len   = 0;
		foreach ( $lines as $line ) {
			$line_len = mb_strlen( $line ) + 1;
			if ( $len + $line_len > self::MAX_LENGTH && ! empty( $kept ) ) {
				break;
			}
			$kept[] = $line;
			$len   += $line_len;
		}
		return implode( "\n", $kept );
	}

	/**
	 * Pule redacción de un texto ya editado por el usuario (gramática,
	 * consistencia, formato) sin cambiar su contenido/decisiones de fondo.
	 */
	public static function normalize( $text ) {
		$text = trim( (string) $text );
		if ( '' === $text ) {
			return new \WP_Error( 'wookb_empty_prompt', __( 'No hay texto que normalizar.', 'ai-knowledge' ) );
		}

		$config = Generator::ai_config();
		if ( ! $config ) {
			return new \WP_Error( 'wookb_no_ai_connection', __( 'El origen de IA seleccionado no está conectado.', 'ai-knowledge' ) );
		}

		$prompt  = "Pule la redacción del siguiente prompt de sistema de un chatbot (gramática, claridad, consistencia de formato y tono), SIN cambiar ninguna decisión de fondo: no añadas reglas nuevas, no quites ninguna instrucción existente, no cambies datos de contacto, precios ni condiciones. Solo mejora cómo está escrito.\n\n";
		$prompt .= "Texto a pulir:\n\n" . $text . "\n\n";
		$prompt .= 'El resultado (contando saltos de linea) no debe superar los ' . self::MAX_LENGTH . " caracteres: es un limite duro del sistema que lo recibe.\n";
		$prompt .= 'Responde solo con el texto final pulido, sin explicaciones ni comillas envolventes.';

		return self::enforce_length( self::call_ai( $config, $prompt ) );
	}

	protected static function call_ai( array $config, $prompt ) {
		return AI_Client::generate(
			'Eres un redactor experto en prompts de sistema para chatbots de atención al cliente.',
			$prompt,
			1200,
			0.4,
			40
		);
	}

	/**
	 * Aviso (no bloqueante) cuando no existe un resumen público propio
	 * (opción BUSINESS_SUMMARY_OPTION). Ese resumen es la cita de apertura
	 * pública de /llms.txt (Llms_Txt::summary()); sin él se usa como sustituto
	 * el campo «Enfoque del negocio», que puede ser una sola frase. No cuenta
	 * ese sustituto ni aplica ningún umbral de longitud.
	 *
	 * Restringido a la propia pantalla del plugin (no admin_notices global):
	 * es un recordatorio de contenido, no una alerta de algo roto. Descarte
	 * persistente por usuario (user meta), con un formulario POST propio.
	 */
	const DISMISS_ACTION = 'wookb_dismiss_summary_notice';
	const DISMISS_META   = 'wookb_dismissed_summary_notice';

	public static function init_notice() {
		add_action( 'admin_post_' . self::DISMISS_ACTION, array( __CLASS__, 'handle_dismiss' ) );
	}

	public static function handle_dismiss() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No autorizado.', 'ai-knowledge' ) );
		}
		check_admin_referer( self::DISMISS_ACTION );
		update_user_meta( get_current_user_id(), self::DISMISS_META, 1 );
		$back = wp_get_referer();
		wp_safe_redirect( $back ? $back : admin_url( 'admin.php?page=ai-knowledge' ) );
		exit;
	}

	public static function maybe_short_summary_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_GET['page'] ) || 'ai-knowledge' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		if ( get_user_meta( get_current_user_id(), self::DISMISS_META, true ) ) {
			return;
		}

		// Solo el resumen público propio cuenta, no el sustituto «Enfoque del negocio».
		$own_summary = get_option( self::BUSINESS_SUMMARY_OPTION, null );
		if ( null !== $own_summary && '' !== trim( (string) $own_summary ) ) {
			return;
		}

		$negocio_tab_url = admin_url( 'admin.php?page=ai-knowledge&tab=negocio' );

		printf(
			'<div class="notice notice-info"><p><strong>%1$s</strong> %2$s</p><form method="post" action="%3$s" class="wookb-toolbar-form"><input type="hidden" name="action" value="%4$s" />%5$s<button type="submit" class="button">%6$s</button></form></div>',
			esc_html__( 'AI Knowledge & Visibility:', 'ai-knowledge' ),
			sprintf(
				/* translators: %s: enlace a la pestaña Negocio */
				esc_html__( 'Todavía no has escrito el resumen público de tu negocio. Es la cita de apertura pública de /llms.txt: lo primero que leen las IA sobre ti. Mientras no lo escribas se usa el campo «Enfoque del negocio», que puede ser muy breve. Es solo una sugerencia: no rompe nada si lo ignoras. %s', 'ai-knowledge' ),
				'<a href="' . esc_url( $negocio_tab_url ) . '">' . esc_html__( 'Ir a la pestaña Negocio', 'ai-knowledge' ) . '</a>'
			),
			esc_url( admin_url( 'admin-post.php' ) ),
			esc_attr( self::DISMISS_ACTION ),
			wp_nonce_field( self::DISMISS_ACTION, '_wpnonce', true, false ),
			esc_html__( 'No volver a mostrar', 'ai-knowledge' )
		);
	}
}
