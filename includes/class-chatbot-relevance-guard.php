<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fase 2 del plan de mejoras del chatbot (06-implementacion/addon-support-genix/plan-mejoras-chatbot-fase2.md).
 * Engancha los 2 filtros minimos anadidos a Support Genix
 * (traits/Apbd_wps_knowledge_base_chatquery_trait.php, ~lineas 122 y 236):
 *
 *   apply_filters('apbd-wps/filter/chatbot-search-results', $docs, $query)
 *   apply_filters('apbd-wps/filter/chatbot-docs-list', $docs, $query)
 *
 * Toda la logica real vive aqui, no en Genix.
 *
 * Piezas implementadas:
 *  1. Filtro de relevancia (DESACTIVADO por defecto, ajuste
 *     `chatbot_relevance_filter`): si NINGUN documento encontrado tiene relacion
 *     real con la pregunta (por titulo, tras quitar palabras vacias), se
 *     devuelve un array vacio -- esto hace que Genix crea que "no encontro
 *     nada" y dispare su propio mecanismo de recuperacion por historial de
 *     conversacion (is_chatbot_followup_query/build_chatbot_followup_query,
 *     ya existente en Genix, no se reimplementa aqui). Desactivado porque
 *     compara solo con el TITULO: vaciaba los documentos de preguntas cuya
 *     respuesta esta en el contenido («envios a Canarias», «devoluciones»)
 *     aunque el titulo no coincidiera. Genix ya exige por su cuenta que
 *     coincida al menos el 50 % de los terminos, asi que era redundante.
 *  2. Idioma no soportado: si el texto de la pregunta parece estar escrito
 *     en un idioma fuera de los activos del sitio (es/en/de), se inyecta un
 *     documento sintetico con una instruccion directa de traduccion en el
 *     propio contexto que lee la IA (mas fiable que solo pedirlo en el
 *     prompt general del sistema).
 *  3. Limite configurable de "Documentos relacionados" (ademas del efecto
 *     de limpieza que ya aporta la pieza 1 al compartir el mismo array).
 */
class Chatbot_Relevance_Guard {

	const DOCS_LIST_LIMIT_DEFAULT = 3;

	// Palabras funcionales sin valor semantico, es/en (Genix ya filtra stopwords
	// en ingles en su propio motor; aqui cubrimos tambien espanol, que Genix NO
	// filtra -- ver investigacion-comportamiento-chatbot.md punto 2).
	const STOP_WORDS = array(
		'hola', 'que', 'qué', 'cual', 'cuál', 'cuales', 'cuáles', 'tienes', 'tiene', 'tenéis',
		'puedes', 'puede', 'podrias', 'podrías', 'quiero', 'quisiera', 'quería', 'queria',
		'me', 'te', 'se', 'nos', 'les', 'lo', 'la', 'el', 'los', 'las', 'un', 'una', 'unos', 'unas',
		'y', 'o', 'de', 'del', 'al', 'en', 'a', 'es', 'son', 'esta', 'está', 'estan', 'están',
		'como', 'cómo', 'donde', 'dónde', 'cuando', 'cuándo', 'porque', 'porqué', 'por', 'para',
		'con', 'sin', 'hay', 'algo', 'algun', 'algún', 'alguna', 'favor', 'gracias', 'muchas',
		'the', 'a', 'an', 'is', 'are', 'do', 'you', 'have', 'has', 'can', 'could', 'would', 'want',
		'what', 'which', 'where', 'when', 'how', 'please', 'and', 'or', 'with', 'for', 'to', 'of',
		'in', 'on', 'me', 'i', 'we', 'they', 'about', 'some', 'any', 'other', 'others',
	);

	public static function init() {
		add_filter( 'apbd-wps/filter/chatbot-search-results', array( __CLASS__, 'filter_search_results' ), 10, 2 );
		add_filter( 'apbd-wps/filter/chatbot-docs-list', array( __CLASS__, 'limit_docs_list' ), 10, 2 );
	}

	/**
	 * Pieza 1 + Pieza 2. Recibe los documentos ya encontrados por Genix y la
	 * pregunta original.
	 */
	public static function filter_search_results( $docs, $query ) {
		if ( ! is_array( $docs ) ) {
			return $docs;
		}

		// Pieza 2: idioma del texto de la pregunta fuera de los idiomas activos.
		// La busqueda original de Genix ya se ejecuto con el texto TAL CUAL
		// (ruso, p.ej.) contra titulos/contenido en espanol -- por diseño de un
		// buscador por coincidencia literal, eso normalmente no encuentra nada.
		// "Forzar la busqueda en español" significa entonces: traducir la
		// pregunta a español y volver a buscar con ese texto, para que SI haya
		// contenido real que la IA pueda traducir de vuelta al idioma del
		// visitante. Sin este paso, el documento sintetico de instruccion queda
		// solo, sin nada que traducir, y el bot responde "no tengo informacion".
		$unsupported_lang = self::detect_unsupported_query_language( $query );
		if ( $unsupported_lang ) {
			// Se SUSTITUYE $docs, no se anexa: los resultados de la busqueda
			// original (con el texto en el idioma no soportado, contra
			// titulos en español) son ruido por diseño -- coincidencias de
			// keyword casi arbitrarias. Si se anteponen al array, el limite
			// de "documentos relacionados" (Pieza 3) los recorta ANTES de
			// llegar a los documentos en español realmente relevantes,
			// aunque el contexto completo que lee la IA si los incluya.
			// Se traduce y busca primero SOLO con la pregunta actual. Fusionar
			// el historial siempre (incluso cuando la pregunta ya es
			// autosuficiente, ej. "¿que vino tinto teneis?") diluye la
			// busqueda con temas anteriores no relacionados y hace perder el
			// tema nuevo -- bug real detectado en pruebas: tras preguntar por
			// el tren, "que vino tinto teneis" fusionado con el turno del
			// tren ya no encontraba los vinos. Solo se fusiona con el
			// historial (1-2 turnos previos) como reintento, igual que el
			// mecanismo nativo de follow-up de Genix
			// (is_chatbot_followup_query/build_chatbot_followup_query) pero
			// aplicado ANTES de traducir -- el mecanismo nativo no sirve aqui
			// porque reintenta con el texto ORIGINAL (aun en ruso) sin pasar
			// por este filtro, asi que nunca traduce.
			$target_lang      = self::current_navigation_language();
			$translated_query = self::translate_to_language( $query, $target_lang );
			$docs = $translated_query ? self::search_docs_in_language( $translated_query, $target_lang ) : array();

			if ( empty( $docs ) ) {
				$query_with_history = self::merge_recent_queries( $query );
				if ( $query_with_history !== $query ) {
					$retry_translated = self::translate_to_language( $query_with_history, $target_lang );
					$retry_docs       = $retry_translated ? self::search_docs_in_language( $retry_translated, $target_lang ) : array();
					if ( ! empty( $retry_docs ) ) {
						$translated_query = $retry_translated;
						$docs             = $retry_docs;
					}
				}
			}

			// El ranking de Genix (LIKE + conteo de coincidencias) puntua por
			// igual documentos de tours con prosa larga que mencionan "vino"
			// muchas veces que fichas de producto reales -- la misma debilidad
			// de fondo ya documentada (investigacion-comportamiento-chatbot.md
			// punto 2). Sin reordenar, el contexto de la IA es correcto (recibe
			// todo) pero la lista visible de "Documentos relacionados" (Pieza 3,
			// limitada a N) podia mostrar solo tours y ningun vino aunque la
			// respuesta si los usara. Reordenamos: coincidencia real de titulo
			// con la pregunta traducida primero, resto despues, orden estable.
			if ( $translated_query && count( $docs ) > 1 ) {
				$docs = self::sort_by_title_relevance( $docs, $translated_query );
			}

			$docs[] = self::synthetic_language_instruction_doc( $unsupported_lang, $target_lang );
		}

		// Pieza 1: si NINGUN documento real (excluyendo el sintetico de arriba)
		// tiene relacion con la pregunta, vaciar para que Genix dispare su
		// mecanismo de historial. El documento sintetico de idioma, si existe,
		// se mantiene aparte y se re-adjunta despues: la instruccion de idioma
		// debe sobrevivir aunque no haya contenido relevante que mostrar.
		//
		// Se omite este chequeo cuando hay idioma no soportado: comparar
		// palabras de la pregunta (ruso, p.ej.) contra titulos en español
		// nunca puede coincidir por diseño -- vaciaria siempre los documentos
		// en español que acabamos de encontrar traduciendo la pregunta arriba.
		$relevance_filter = ! empty( Scope::settings()['chatbot_relevance_filter'] );
		if ( $relevance_filter && ! $unsupported_lang ) {
			$real_docs = array_filter( $docs, array( __CLASS__, 'is_not_synthetic' ) );
			if ( ! self::any_doc_relevant( $query, $real_docs ) ) {
				$docs = array();
			}
		}

		// Pieza 4: contacto/direccion/horario. La regla anti-alucinacion fija
		// de Genix ("cada dato que digas viene del material de referencia...
		// nada mas") hace que el modelo IGNORE los datos de contacto puestos
		// en las "Additional instructions" del prompt de sistema -- para el,
		// eso son instrucciones de comportamiento, no material de referencia
		// consultable. Confirmado en real: preguntas directas de direccion/
		// telefono respondian "no tengo esa informacion" aunque el prompt SI
		// los tuviera. Unica forma fiable: que el contacto llegue como un
		// documento mas (reference material real), igual que ya hacemos con
		// la instruccion de idioma.
		if ( self::is_contact_query( $query ) ) {
			$contact_doc = self::contact_doc();
			if ( $contact_doc ) {
				$docs[] = $contact_doc;
			}
		}

		return $docs;
	}

	const CONTACT_KEYWORDS = array(
		'direccion', 'dirección', 'ubicacion', 'ubicación', 'donde estais', 'dónde estáis',
		'donde esta', 'dónde está', 'como llegar', 'cómo llegar', 'localizacion', 'localización',
		'telefono', 'teléfono', 'whatsapp', 'contacto', 'contactar', 'email', 'correo',
		'horario', 'horarios', 'abierto', 'cerrado', 'address', 'location', 'phone', 'contact',
		'opening hours', 'adresse', 'kontakt', 'telefon',
	);

	/**
	 * ¿La pregunta pide datos de contacto/ubicacion/horario? Primero
	 * coincidencia exacta de substring (barata); si no hay, tolerancia a
	 * erratas por distancia de edicion palabra a palabra -- confirmado en
	 * real: "contsctar" (typo de "contactar") no activaba el documento de
	 * contacto con solo substring, y el bot respondia una contradiccion
	 * ("puedes contactar por telefono... pero no tengo esos datos"). Un
	 * falso positivo aqui es inofensivo (documento de mas en el contexto);
	 * no perder la inyeccion por un falso negativo es lo que importa.
	 */
	protected static function is_contact_query( $query ) {
		$normalized = self::normalize( $query );

		foreach ( self::CONTACT_KEYWORDS as $keyword ) {
			if ( false !== mb_strpos( $normalized, self::normalize( $keyword ) ) ) {
				return true;
			}
		}

		$words = preg_split( '/[^a-z0-9ñáéíóúüàèìòùâêîôûäëïöü]+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY );
		foreach ( (array) $words as $word ) {
			if ( mb_strlen( $word ) < 5 ) {
				continue;
			}
			foreach ( self::CONTACT_KEYWORDS as $keyword ) {
				$keyword_norm = self::normalize( $keyword );
				if ( false !== mb_strpos( $keyword_norm, ' ' ) ) {
					continue;
				}
				if ( levenshtein( $word, $keyword_norm ) <= 2 ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Documento de contacto real (no marcado como sintetico): usa la misma
	 * respuesta "contacto" del cuestionario de la pestaña Prompt. URL =
	 * home_url() para que no se filtre en limit_docs_list (que trata url
	 * vacia como marca de sintetico).
	 */
	protected static function contact_doc() {
		if ( ! class_exists( '\AIKB\Chatbot_Prompt_Builder' ) ) {
			return null;
		}
		$answers = Chatbot_Prompt_Builder::get_saved_answers();
		if ( empty( $answers['contacto'] ) ) {
			return null;
		}

		return array(
			'id'               => 0,
			'title'            => 'Contacto',
			'content'          => $answers['contacto'],
			'url'              => home_url( '/' ),
			'only_for_chatbot' => true,
		);
	}

	/**
	 * Pieza 3: limite configurable de documentos mostrados en "Documentos
	 * relacionados". El documento sintetico de idioma (Pieza 2) no debe
	 * mostrarse nunca en esa lista (no es un enlace real) -- se excluye aqui
	 * independientemente del limite.
	 */
	public static function limit_docs_list( $docs, $query ) {
		if ( ! is_array( $docs ) ) {
			return $docs;
		}

		// Genix ya redujo cada doc a solo title\/url en este punto -- 'wookb_synthetic' ya no existe.
		// El sintetico es el UNICO con url vacia (ningun doc real la tiene): se usa como marca.
		$docs = array_values( array_filter( $docs, function( $doc ) { return ! ( is_array( $doc ) && empty( $doc['url'] ) ); } ) );

		$limit = self::docs_list_limit();
		if ( $limit > 0 && count( $docs ) > $limit ) {
			$docs = array_slice( $docs, 0, $limit );
		}

		return $docs;
	}

	protected static function docs_list_limit() {
		$settings = Scope::settings();
		$limit    = isset( $settings['chatbot_docs_list_limit'] ) ? (int) $settings['chatbot_docs_list_limit'] : self::DOCS_LIST_LIMIT_DEFAULT;
		return apply_filters( 'wookb_chatbot_docs_list_limit', $limit );
	}

	protected static function is_not_synthetic( $doc ) {
		return ! ( is_array( $doc ) && ! empty( $doc['wookb_synthetic'] ) );
	}

	/**
	 * ¿Alguno de los documentos encontrados tiene relacion real con la
	 * pregunta? Compara palabras significativas de la pregunta (sin
	 * stopwords, minimo 3 caracteres) contra el titulo de cada documento.
	 * Sin terminos significativos que comparar (pregunta compuesta solo de
	 * palabras funcionales), no se bloquea nada -- no hay señal suficiente
	 * para decidir que es irrelevante.
	 */
	protected static function any_doc_relevant( $query, array $docs ) {
		if ( empty( $docs ) ) {
			return true; // Ya vacio: nada que filtrar, Genix ya gestiona ese caso.
		}

		$terms = self::significant_terms( $query );
		if ( empty( $terms ) ) {
			return true;
		}

		foreach ( $docs as $doc ) {
			$title = isset( $doc['title'] ) ? self::normalize( $doc['title'] ) : '';
			if ( '' === $title ) {
				continue;
			}
			foreach ( $terms as $term ) {
				if ( false !== mb_strpos( $title, $term ) ) {
					return true;
				}
			}
		}

		return false;
	}

	protected static function significant_terms( $query ) {
		$normalized = self::normalize( $query );
		$words      = preg_split( '/[^a-z0-9ñáéíóúüàèìòùâêîôûäëïöü]+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY );

		$terms = array();
		foreach ( (array) $words as $word ) {
			if ( mb_strlen( $word ) < 3 ) {
				continue;
			}
			if ( in_array( $word, self::STOP_WORDS, true ) ) {
				continue;
			}
			$terms[] = $word;
		}
		return $terms;
	}

	protected static function normalize( $text ) {
		$text = mb_strtolower( trim( (string) $text ) );
		if ( function_exists( 'remove_accents' ) ) {
			$text = remove_accents( $text );
		}
		return $text;
	}

	/**
	 * Pieza 2. Detecta si el TEXTO de la pregunta parece escrito en un
	 * idioma/alfabeto fuera de los idiomas activos del sitio (es/en/de,
	 * todos alfabeto latino). Usamos rangos Unicode por script en vez de
	 * fiarnos del idioma de NAVEGACION de WPML (Chatbot_Language_Fix), que
	 * detecta en que URL/idioma esta navegando el visitante, no en que
	 * idioma ESCRIBE su pregunta -- son cosas distintas (un visitante puede
	 * estar en la version en/es del sitio y escribir en ruso). Cubre con
	 * fiabilidad los casos de alfabeto no latino (ruso, arabe, chino,
	 * japones, coreano, griego, hebreo). Limitacion honesta: un idioma no
	 * soportado pero de alfabeto latino (frances, italiano...) no se detecta
	 * por script y sigue el comportamiento normal (busqueda en el idioma de
	 * navegacion WPML).
	 *
	 * Devuelve el nombre legible del idioma detectado, o null si no aplica.
	 */
	protected static function detect_unsupported_query_language( $query ) {
		$query = (string) $query;
		if ( '' === trim( $query ) ) {
			return null;
		}

		$scripts = array(
			'ruso'    => '\x{0400}-\x{04FF}', // Cirilico
			'árabe'   => '\x{0600}-\x{06FF}',
			'chino'   => '\x{4E00}-\x{9FFF}',
			'japonés' => '\x{3040}-\x{30FF}', // Hiragana/Katakana
			'coreano' => '\x{AC00}-\x{D7A3}',
			'griego'  => '\x{0370}-\x{03FF}',
			'hebreo'  => '\x{0590}-\x{05FF}',
		);

		foreach ( $scripts as $lang_name => $range ) {
			if ( preg_match( '/[' . $range . ']/u', $query ) ) {
				return $lang_name;
			}
		}

		return null;
	}

	/**
	 * Reordena $docs (estable) poniendo primero los que tienen coincidencia
	 * real de titulo con los terminos significativos de $query.
	 */
	protected static function sort_by_title_relevance( array $docs, $query ) {
		$terms = self::significant_terms( $query );
		if ( empty( $terms ) ) {
			return $docs;
		}

		$scored = array();
		foreach ( $docs as $index => $doc ) {
			$title    = isset( $doc['title'] ) ? self::normalize( $doc['title'] ) : '';
			$matches  = 0;
			foreach ( $terms as $term ) {
				if ( '' !== $title && false !== mb_strpos( $title, $term ) ) {
					$matches++;
			}
			}
			$scored[] = array( 'doc' => $doc, 'matches' => $matches, 'index' => $index );
		}

		usort(
			$scored,
			function ( $a, $b ) {
				if ( $a['matches'] === $b['matches'] ) {
					return $a['index'] <=> $b['index']; // estable.
				}
				return $b['matches'] <=> $a['matches']; // mas coincidencias primero.
			}
		);

		return array_map(
			function ( $item ) {
				return $item['doc'];
			},
			$scored
		);
	}

	/**
	 * Junta la pregunta actual con las 1-2 anteriores de la misma sesion
	 * (misma tabla e idea que build_chatbot_followup_query de Genix, pero
	 * self-contained: aqui hace falta ANTES de traducir, no despues).
	 */
	protected static function merge_recent_queries( $query, $limit = 2 ) {
		global $wpdb;

		$session_id = sanitize_text_field( ApbdWps_PostValue( 'session_id', '' ) );
		if ( '' === $session_id ) {
			return $query;
		}

		$table = $wpdb->prefix . 'apbd_wps_chatbot_history';
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT query FROM {$table} WHERE session_id = %s ORDER BY id DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table es $wpdb->prefix interno.
				$session_id,
				$limit
			),
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			return $query;
		}

		$parts = array_reverse( wp_list_pluck( $rows, 'query' ) );
		$parts = array_map(
			function ( $q ) {
				return trim( wp_strip_all_tags( $q ) );
			},
			$parts
		);
		$parts[] = trim( $query );

		return implode( ' ', array_filter( $parts ) );
	}

	/**
	 * Idioma en el que estan los documentos que busca el chatbot
	 * (Languages::documents_language()): el de navegacion del visitante si hay
	 * un documento por idioma; si no, el idioma principal. No es el idioma en
	 * que escribe su pregunta -- son cosas distintas, ver
	 * detect_unsupported_query_language(). Traducir y buscar en ESE idioma es
	 * lo que realmente coincide con los documentos disponibles.
	 */
	protected static function current_navigation_language() {
		return Languages::documents_language();
	}

	/**
	 * Traduce el texto de la pregunta al idioma indicado usando la misma
	 * IA que ya usa el plugin para generar documentos. Llamada corta y barata
	 * (1 frase). Null si no hay conexión configurada o la llamada falla -- en ese caso simplemente no
	 * se añaden documentos extra, pero el documento sintetico de instruccion
	 * de idioma se mantiene igualmente.
	 */
	protected static function translate_to_language( $text, $target_lang ) {
		if ( ! AI_Client::config() ) {
			return null;
		}

		$target_name = Languages::name( $target_lang ) . ' (' . $target_lang . ')';

		$translated = AI_Client::generate(
			'Traduce el siguiente texto al idioma ' . $target_name . '. Responde solo con la traducción, sin comillas ni explicaciones.',
			(string) $text,
			80,
			0.2,
			20
		);
		if ( is_wp_error( $translated ) ) {
			return null;
		}
		$translated = trim( $translated );

		return '' !== $translated ? $translated : null;
	}

	/**
	 * Reejecuta la busqueda de documentos de Genix (search_chatbot_docs,
	 * privada) con el texto ya traducido, via reflexion sobre la instancia
	 * real del modulo -- API interna de Genix, no publica, pero es la unica
	 * via sin reimplementar su logica de busqueda/ranking.
	 *
	 * Genix ya establecio el contexto de idioma de WPML ANTES de esto
	 * (switch_language_context en ApbdWpsAPI_Chatbot::chatbot_query, segun el
	 * idioma de NAVEGACION del visitante -- 'lang' del request), y su query
	 * nativa usa 'suppress_filters' => false, es decir SI aplica ese filtro.
	 * $target_lang (current_navigation_language) YA coincide con ese
	 * contexto, asi que no hace falta cambiar nada aqui -- solo traducir la
	 * pregunta al mismo idioma en que Genix ya esta buscando.
	 */
	protected static function search_docs_in_language( $translated_query, $target_lang ) {
		if ( ! class_exists( '\Apbd_wps_knowledge_base' ) || ! method_exists( '\Apbd_wps_knowledge_base', 'GetModuleInstance' ) ) {
			return array();
		}
		$instance = \Apbd_wps_knowledge_base::GetModuleInstance();
		if ( ! $instance ) {
			return array();
		}

		try {
			$ref    = new \ReflectionClass( $instance );
			$method = $ref->getMethod( 'search_chatbot_docs' );
			$method->setAccessible( true );
			$docs = $method->invoke( $instance, $translated_query );
			return is_array( $docs ) ? $docs : array();
		} catch ( \Throwable $e ) {
			return array();
		}
	}

	protected static function synthetic_language_instruction_doc( $lang_name, $docs_lang ) {
		$docs_lang_name = Languages::name( $docs_lang );
		$content        = sprintf(
			'INSTRUCCIÓN DEL SISTEMA: el visitante escribe en %1$s. Traduce tu respuesta completa a ese idioma usando el contexto en %2$s disponible más abajo. No respondas en %2$s.',
			$lang_name,
			$docs_lang_name
		);

		return array(
			'id'               => 0,
			'title'            => 'Instrucción de idioma (' . $lang_name . ')',
			'content'          => $content,
			'url'              => '',
			'only_for_chatbot' => true,
			'wookb_synthetic'  => true,
		);
	}
}
