<?php
namespace WOOKB;

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

	const MAX_LENGTH = 2000;

	// Umbral orientativo (no un límite técnico) para animar a ampliar el
	// resumen de negocio: Llms_Txt::summary() lo usa tal cual como cita
	// inicial de llms.txt sin ningún mínimo hoy, así que un resumen de una
	// frase deja la cabecera del documento pública muy pobre. 1000 caracteres
	// iguala, a ojo, el tope de cuerpo de una ficha de producto normal
	// (Generator::BODY_CHAR_LIMIT) -- ni exacto ni forzado, solo una
	// referencia razonable para el aviso.
	const SUMMARY_MIN_LENGTH_RECOMMENDED = 1000;

	/**
	 * Preguntas del cuestionario. 'key' => [label, placeholder, required].
	 * El usuario pidió explícitamente: tono, dirección de negocio, qué hacer
	 * sin respuesta, "y las que creas necesario" -- se añaden idioma
	 * principal, límites (qué no debe hacer nunca) y contacto de reserva por
	 * si difiere del que ya usa el prompt actual.
	 */
	public static function questions() {
		return array(
			'tono'            => array(
				'label'       => __( 'Tono de la marca', 'woo-kb-generator' ),
				'placeholder' => __( 'Ej: cercano, profesional, cálido — bodega familiar, sin lenguaje robótico.', 'woo-kb-generator' ),
				'type'        => 'text',
			),
			'negocio'         => array(
				'label'       => __( 'Dirección / enfoque del negocio', 'woo-kb-generator' ),
				'placeholder' => __( 'Ej: bodega familiar en Mallorca, vende vino propio y experiencias enoturísticas (visitas, catas, eventos).', 'woo-kb-generator' ),
				'type'        => 'textarea',
			),
			'sin_respuesta'   => array(
				'label'       => __( 'Qué hacer cuando no hay información', 'woo-kb-generator' ),
				'placeholder' => __( 'Ej: no inventar ni dar largas, decir que no se tiene esa información y dar el contacto directo.', 'woo-kb-generator' ),
				'type'        => 'textarea',
			),
			'idioma_principal' => array(
				'label'       => __( 'Idioma principal del negocio', 'woo-kb-generator' ),
				'placeholder' => __( 'Ej: español, aunque la web está también en inglés y alemán.', 'woo-kb-generator' ),
				'type'        => 'text',
			),
			'limites'         => array(
				'label'       => __( 'Qué NO debe hacer nunca el chatbot', 'woo-kb-generator' ),
				'placeholder' => __( 'Ej: no gestionar pagos ni reclamaciones directamente, no prometer descuentos, no inventar precios ni stock.', 'woo-kb-generator' ),
				'type'        => 'textarea',
			),
			'contacto'        => array(
				'label'       => __( 'Datos de contacto directo (si difieren de los ya usados)', 'woo-kb-generator' ),
				'placeholder' => __( 'Teléfono, WhatsApp, email... deja vacío para mantener los que ya haya en el prompt actual.', 'woo-kb-generator' ),
				'type'        => 'textarea',
			),
		);
	}

	public static function get_saved_answers() {
		$defaults = array_fill_keys( array_keys( self::questions() ), '' );
		return wp_parse_args( get_option( self::ANSWERS_OPTION, array() ), $defaults );
	}

	public static function save_answers( array $answers ) {
		$clean = array();
		foreach ( self::questions() as $key => $q ) {
			$clean[ $key ] = isset( $answers[ $key ] ) ? sanitize_textarea_field( $answers[ $key ] ) : '';
		}
		update_option( self::ANSWERS_OPTION, $clean, false );
		return $clean;
	}

	/**
	 * Escribe wp-content/llm/info.md con la info general del negocio (mismo
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
		$lines   = array();
		$lines[] = '# ' . get_bloginfo( 'name' );
		if ( ! empty( $answers['idioma_principal'] ) ) {
			$lines[] = 'Idiomas: ' . $answers['idioma_principal'];
		}
		if ( ! empty( $answers['contacto'] ) ) {
			$lines[] = 'Contacto: ' . $answers['contacto'];
		}

		$content = implode( "\n", $lines ) . "\n";

		$dir = WP_CONTENT_DIR . '/llm';
		wp_mkdir_p( $dir );
		file_put_contents( $dir . '/info.md', $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_put_contents

		if ( class_exists( '\WOOKB\Llms_Txt' ) ) {
			Llms_Txt::invalidate();
		}

		return $content;
	}

	public static function info_doc_url() {
		return content_url( '/llm/info.md' );
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
	public static function generate_draft( array $answers, array $reference_pages = array() ) {
		$config = Generator::ai_config();
		if ( ! $config ) {
			return new \WP_Error( 'wookb_no_ai_key', __( 'No hay clave de IA configurada (ni Genix ni propia).', 'woo-kb-generator' ) );
		}

		$prompt  = "Genera el PROMPT DE SISTEMA de un chatbot de atención al cliente para un negocio real, a partir de estas respuestas del propio negocio:\n\n";
		foreach ( self::questions() as $key => $q ) {
			if ( ! empty( $answers[ $key ] ) ) {
				$prompt .= '- ' . $q['label'] . ': ' . $answers[ $key ] . "\n";
			}
		}

		if ( ! empty( $reference_pages ) ) {
			$prompt .= "\nContenido real de páginas del sitio para tono y datos adicionales (no copiar literal, usar como referencia):\n\n";
			foreach ( $reference_pages as $page ) {
				$prompt .= '### ' . $page['title'] . ' (' . $page['url'] . ")\n" . $page['content'] . "\n\n";
			}
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
			return new \WP_Error( 'wookb_empty_prompt', __( 'No hay texto que normalizar.', 'woo-kb-generator' ) );
		}

		$config = Generator::ai_config();
		if ( ! $config ) {
			return new \WP_Error( 'wookb_no_ai_key', __( 'No hay clave de IA configurada (ni Genix ni propia).', 'woo-kb-generator' ) );
		}

		$prompt  = "Pule la redacción del siguiente prompt de sistema de un chatbot (gramática, claridad, consistencia de formato y tono), SIN cambiar ninguna decisión de fondo: no añadas reglas nuevas, no quites ninguna instrucción existente, no cambies datos de contacto, precios ni condiciones. Solo mejora cómo está escrito.\n\n";
		$prompt .= "Texto a pulir:\n\n" . $text . "\n\n";
		$prompt .= 'El resultado (contando saltos de linea) no debe superar los ' . self::MAX_LENGTH . " caracteres: es un limite duro del sistema que lo recibe.\n";
		$prompt .= 'Responde solo con el texto final pulido, sin explicaciones ni comillas envolventes.';

		return self::enforce_length( self::call_ai( $config, $prompt ) );
	}

	protected static function call_ai( array $config, $prompt ) {
		$body = array(
			'model'    => $config['model'],
			'messages' => array(
				array( 'role' => 'system', 'content' => 'Eres un redactor experto en prompts de sistema para chatbots de atención al cliente.' ),
				array( 'role' => 'user', 'content' => $prompt ),
			),
		);
		$is_new_gen = ( 0 === strpos( $config['model'], 'gpt-5' ) || 0 === strpos( $config['model'], 'o' ) );
		$body[ $is_new_gen ? 'max_completion_tokens' : 'max_tokens' ] = 1200;
		if ( ! $is_new_gen ) {
			$body['temperature'] = 0.4;
		}

		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'timeout' => 40,
				'headers' => array(
					'Authorization' => 'Bearer ' . $config['api_key'],
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$json = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$msg = isset( $json['error']['message'] ) ? $json['error']['message'] : 'Error HTTP ' . $code;
			return new \WP_Error( 'wookb_openai_error', $msg );
		}

		$content = isset( $json['choices'][0]['message']['content'] ) ? trim( $json['choices'][0]['message']['content'] ) : '';
		if ( '' === $content ) {
			return new \WP_Error( 'wookb_openai_empty', __( 'Respuesta vacía de la IA.', 'woo-kb-generator' ) );
		}

		return $content;
	}

	/**
	 * Aviso (no bloqueante) cuando la respuesta "negocio" del cuestionario es
	 * corta: esa respuesta es la cita de resumen que abre llms.txt
	 * (Llms_Txt::summary()) y hoy no tiene ningún mínimo forzado, así que un
	 * resumen de una frase deja pobre la cabecera pública del documento que
	 * leen los crawlers de IA.
	 *
	 * Restringido a la propia pantalla del plugin (no admin_notices global):
	 * es un recordatorio de contenido, no una alerta de algo roto (a
	 * diferencia de Genix_Hooks_Guard::maybe_notice(), que sí avisa en todo
	 * wp-admin porque ahí el chatbot ha perdido funcionalidad real). Molestar
	 * en cada pantalla de admin por un campo de texto mejorable no se
	 * justifica.
	 */
	public static function maybe_short_summary_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_GET['page'] ) || 'woo-kb-generator' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$answers = self::get_saved_answers();
		$length  = mb_strlen( trim( (string) $answers['negocio'] ) );
		if ( $length >= self::SUMMARY_MIN_LENGTH_RECOMMENDED ) {
			return;
		}

		$prompt_tab_url = admin_url( 'admin.php?page=woo-kb-generator&tab=prompt' );

		printf(
			'<div class="notice notice-info is-dismissible"><p><strong>%1$s</strong> %2$s</p></div>',
			esc_html__( 'WOO Knowledge Base Generator:', 'woo-kb-generator' ),
			sprintf(
				/* translators: 1: longitud actual en caracteres, 2: mínimo recomendado, 3: enlace a la pestaña Prompt */
				esc_html__( 'El resumen del negocio (pestaña Prompt, "Dirección / enfoque del negocio") tiene %1$d caracteres. Se usa como cita de apertura en /llms.txt: ampliarlo a al menos %2$d caracteres da más contexto útil a los crawlers de IA. %3$s', 'woo-kb-generator' ),
				(int) $length,
				(int) self::SUMMARY_MIN_LENGTH_RECOMMENDED,
				'<a href="' . esc_url( $prompt_tab_url ) . '">' . esc_html__( 'Ir a la pestaña Prompt', 'woo-kb-generator' ) . '</a>'
			)
		);
	}
}
