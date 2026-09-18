<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Catalogo de crawlers de IA conocidos (Fase 11), fuente unica para las
 * piezas de lectura de robots.txt, generador de bloque, .htaccess y logs.
 *
 * Contenido y recomendaciones por defecto tomados literalmente de
 * `_dev/catalogo-crawlers-ia.md` (tabla resumen final) -- no reinterpretados.
 * `user_agent` es el token real a buscar por substring (case-insensitive)
 * en la cabecera User-Agent, NUNCA la cadena completa del navegador/bot.
 */
class Crawler_Catalog {

	/**
	 * Filtrable a proposito (roadmap Fase 11, pieza 2): permite anadir
	 * crawlers no listados aqui sin tocar el core del plugin, porque los
	 * operadores de IA cambian de nombre de user-agent con frecuencia.
	 */
	public static function all() {
		$catalog = array(
			// OpenAI.
			array(
				'user_agent'     => 'GPTBot',
				'operator'       => 'OpenAI',
				'category'       => 'model_training',
				'description'    => __( 'Recopila contenido para entrenar los modelos de OpenAI (GPT).', 'ai-knowledge' ),
				'default_action' => 'block',
			),
			array(
				'user_agent'     => 'ChatGPT-User',
				'operator'       => 'OpenAI',
				'category'       => 'user_requested_assistant',
				'description'    => __( 'Visita una URL concreta porque un usuario le pidió a ChatGPT que la leyera/resumiera.', 'ai-knowledge' ),
				'default_action' => 'allow',
			),
			array(
				'user_agent'     => 'OAI-SearchBot',
				'operator'       => 'OpenAI',
				'category'       => 'ai_search',
				'description'    => __( 'Indexa contenido para la función de búsqueda de ChatGPT (citas con enlace).', 'ai-knowledge' ),
				'default_action' => 'allow',
			),
			// Anthropic.
			array(
				'user_agent'     => 'ClaudeBot',
				'operator'       => 'Anthropic',
				'category'       => 'model_training',
				'description'    => __( 'Recopila contenido para entrenar los modelos de Anthropic (Claude).', 'ai-knowledge' ),
				'default_action' => 'block',
			),
			array(
				'user_agent'     => 'Claude-User',
				'operator'       => 'Anthropic',
				'category'       => 'user_requested_assistant',
				'description'    => __( 'Visita una URL porque un usuario se lo pidió a Claude en una conversación.', 'ai-knowledge' ),
				'default_action' => 'allow',
			),
			array(
				'user_agent'     => 'Claude-SearchBot',
				'operator'       => 'Anthropic',
				'category'       => 'ai_search',
				'description'    => __( 'Indexa contenido para que Claude pueda citarlo/buscarlo.', 'ai-knowledge' ),
				'default_action' => 'allow',
			),
			// Google.
			array(
				'user_agent'     => 'Googlebot',
				'operator'       => 'Google',
				'category'       => 'ai_search',
				'description'    => __( 'Rastreador normal de Google Search. Bloquearlo te saca de Google Search por completo.', 'ai-knowledge' ),
				'default_action' => 'allow',
			),
			array(
				'user_agent'     => 'Google-Extended',
				'operator'       => 'Google',
				'category'       => 'model_training',
				'description'    => __( 'Controla si tu contenido se usa para entrenar Gemini/Vertex AI, separado de Googlebot.', 'ai-knowledge' ),
				'default_action' => 'block',
			),
			// Microsoft / Bing.
			array(
				'user_agent'     => 'Bingbot',
				'operator'       => 'Microsoft',
				'category'       => 'ai_search',
				'description'    => __( 'Rastreador de Bing Search, también alimenta Copilot.', 'ai-knowledge' ),
				'default_action' => 'allow',
			),
			// Perplexity.
			array(
				'user_agent'     => 'PerplexityBot',
				'operator'       => 'Perplexity',
				'category'       => 'ai_search',
				'description'    => __( 'Indexa contenido para que Perplexity AI pueda citarte en sus respuestas.', 'ai-knowledge' ),
				'default_action' => 'allow',
			),
			array(
				'user_agent'     => 'Perplexity-User',
				'operator'       => 'Perplexity',
				'category'       => 'user_requested_assistant',
				'description'    => __( 'Visita una URL porque un usuario se lo pidió a Perplexity en el momento.', 'ai-knowledge' ),
				'default_action' => 'allow',
			),
			// Meta / Facebook.
			array(
				'user_agent'     => 'FacebookBot',
				'operator'       => 'Meta',
				'category'       => 'ai_search',
				'description'    => __( 'Genera las vistas previas de enlaces compartidos en Facebook/Instagram.', 'ai-knowledge' ),
				'default_action' => 'allow',
			),
			array(
				'user_agent'     => 'Meta-ExternalAgent',
				'operator'       => 'Meta',
				'category'       => 'model_training',
				'description'    => __( 'Recopila contenido para entrenar los modelos de Meta AI.', 'ai-knowledge' ),
				'default_action' => 'block',
			),
			array(
				'user_agent'     => 'Meta-ExternalFetcher',
				'operator'       => 'Meta',
				'category'       => 'user_requested_assistant',
				'description'    => __( 'Visita una URL porque un usuario se lo pidió a Meta AI.', 'ai-knowledge' ),
				'default_action' => 'allow',
			),
			// Apple.
			array(
				'user_agent'     => 'Applebot-Extended',
				'operator'       => 'Apple',
				'category'       => 'model_training',
				'description'    => __( 'Controla el uso de tu contenido para entrenar los modelos de IA de Apple, separado de Applebot.', 'ai-knowledge' ),
				'default_action' => 'block',
			),
			array(
				'user_agent'     => 'Applebot',
				'operator'       => 'Apple',
				'category'       => 'ai_search',
				'description'    => __( 'Alimenta Siri y Spotlight Search.', 'ai-knowledge' ),
				'default_action' => 'allow',
			),
			// DuckDuckGo.
			array(
				'user_agent'     => 'DuckAssistBot',
				'operator'       => 'DuckDuckGo',
				'category'       => 'ai_search',
				'description'    => __( 'Alimenta el asistente de IA de DuckDuckGo.', 'ai-knowledge' ),
				'default_action' => 'allow',
			),
			// You.com.
			array(
				'user_agent'     => 'YouBot',
				'operator'       => 'You.com',
				'category'       => 'ai_search',
				'description'    => __( 'Indexa contenido para el buscador con IA de You.com.', 'ai-knowledge' ),
				'default_action' => 'allow',
			),
			// Cohere.
			array(
				'user_agent'     => 'cohere-training-data-crawler',
				'operator'       => 'Cohere',
				'category'       => 'model_training',
				'description'    => __( 'Recopila contenido para entrenar los modelos de Cohere.', 'ai-knowledge' ),
				'default_action' => 'block',
			),
			array(
				'user_agent'     => 'cohere-ai',
				'operator'       => 'Cohere',
				'category'       => 'model_training',
				'description'    => __( 'Recopila contenido para entrenar los modelos de Cohere.', 'ai-knowledge' ),
				'default_action' => 'block',
			),
			// ByteDance / TikTok.
			array(
				'user_agent'     => 'Bytespider',
				'operator'       => 'ByteDance',
				'category'       => 'model_training',
				'description'    => __( 'Rastreador agresivo de ByteDance para entrenar sus modelos de IA.', 'ai-knowledge' ),
				'default_action' => 'block',
			),
			// Amazon: caso especial, sin recomendacion unica (uso mixto documentado).
			array(
				'user_agent'     => 'Amazonbot',
				'operator'       => 'Amazon',
				'category'       => 'ai_search',
				'description'    => __( 'Alimenta Alexa y otros productos IA de Amazon (uso mixto: también entrenamiento).', 'ai-knowledge' ),
				'default_action' => 'ask',
			),
			// xAI / Grok.
			array(
				'user_agent'     => 'xAI-Crawler',
				'operator'       => 'xAI',
				'category'       => 'model_training',
				'description'    => __( 'Recopila contenido para entrenar Grok, uso poco documentado públicamente.', 'ai-knowledge' ),
				'default_action' => 'block',
			),
			// Datasets / agregadores.
			array(
				'user_agent'     => 'CCBot',
				'operator'       => 'Common Crawl',
				'category'       => 'model_training',
				'description'    => __( 'Dataset usado por muchos laboratorios de IA para entrenar modelos.', 'ai-knowledge' ),
				'default_action' => 'block',
			),
			array(
				'user_agent'     => 'Diffbot',
				'operator'       => 'Diffbot',
				'category'       => 'model_training',
				'description'    => __( 'Extracción de datos comercial, vende acceso estructurado a tu contenido.', 'ai-knowledge' ),
				'default_action' => 'block',
			),
			array(
				'user_agent'     => 'ImagesiftBot',
				'operator'       => 'Imagesift',
				'category'       => 'model_training',
				'description'    => __( 'Recopila imágenes específicamente para datasets de entrenamiento de IA.', 'ai-knowledge' ),
				'default_action' => 'block',
			),
			array(
				'user_agent'     => 'Omgilibot',
				'operator'       => 'Webhose.io',
				'category'       => 'model_training',
				'description'    => __( 'Vende los datos recopilados a terceros (incluye entrenamiento de IA).', 'ai-knowledge' ),
				'default_action' => 'block',
			),
			array(
				'user_agent'     => 'Omgili',
				'operator'       => 'Webhose.io',
				'category'       => 'model_training',
				'description'    => __( 'Vende los datos recopilados a terceros (incluye entrenamiento de IA).', 'ai-knowledge' ),
				'default_action' => 'block',
			),
			array(
				'user_agent'     => 'Timpibot',
				'operator'       => 'Timpi',
				'category'       => 'model_training',
				'description'    => __( 'Recopila contenido para entrenamiento de IA (buscador Timpi).', 'ai-knowledge' ),
				'default_action' => 'block',
			),
		);

		/**
		 * Extensibilidad ya decidida en el roadmap: permite añadir/editar
		 * crawlers sin tocar el core del plugin cuando cambien alias o
		 * aparezcan bots nuevos.
		 */
		return apply_filters( 'wookb_crawler_catalog', $catalog );
	}

	/**
	 * Busca la entrada del catalogo cuyo user_agent aparece como substring
	 * (case-insensitive) dentro de la cadena User-Agent real de la peticion.
	 * Devuelve null si no hay coincidencia -- caso mas frecuente con mucha
	 * diferencia (trafico humano), por eso debe ser barato de comprobar.
	 */
	public static function find_by_user_agent( $ua_string ) {
		if ( '' === trim( (string) $ua_string ) ) {
			return null;
		}
		foreach ( self::all() as $entry ) {
			if ( false !== stripos( $ua_string, $entry['user_agent'] ) ) {
				return $entry;
			}
		}
		return null;
	}

	/**
	 * Fuente unica para robots.txt y .htaccess (revision UX 2026-09-16): la
	 * tabla de configuracion por bot (Scope::settings()['crawler_actions'])
	 * decide, y si un bot no tiene entrada guardada se usa su default_action
	 * del catalogo. 'ask' nunca bloquea por si solo (no hay decision tomada).
	 *
	 * @return string[] Lista de user_agent cuya accion resuelta es 'block'.
	 */
	public static function blocked_user_agents() {
		$saved   = Scope::settings()['crawler_actions'];
		$blocked = array();
		foreach ( self::all() as $entry ) {
			$action = isset( $saved[ $entry['user_agent'] ] ) ? $saved[ $entry['user_agent'] ] : $entry['default_action'];
			if ( 'block' === $action ) {
				$blocked[] = $entry['user_agent'];
			}
		}
		return $blocked;
	}

	public static function effective_actions() {
		$saved = Scope::settings()['crawler_actions'];
		$actions = array();
		foreach ( self::all() as $entry ) {
			$actions[ $entry['user_agent'] ] = isset( $saved[ $entry['user_agent'] ] ) ? $saved[ $entry['user_agent'] ] : $entry['default_action'];
		}
		return $actions;
	}
}
