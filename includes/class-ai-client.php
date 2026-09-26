<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transporte IA central: Conectores de WordPress 7.0 o Support Genix.
 */
class AI_Client {

	const MODEL_AUTO = 'auto';

	/** Proveedores admitidos por el selector del plugin. */
	protected static function allowed_providers() {
		return array( 'anthropic', 'openai', 'google' );
	}

	public static function wordpress_available() {
		return version_compare( get_bloginfo( 'version' ), '7.0', '>=' )
			&& function_exists( 'wp_ai_client_prompt' )
			&& function_exists( 'wp_supports_ai' )
			&& wp_supports_ai()
			&& class_exists( '\WordPress\AiClient\AiClient' );
	}

	/**
	 * Devuelve el catálogo real de modelos de texto ya configurados.
	 * Formato: [ "provider:model" => [ provider, provider_name, model, name ] ].
	 */
	public static function available_models() {
		if ( ! self::wordpress_available() ) {
			return array();
		}

		try {
			$registry     = \WordPress\AiClient\AiClient::defaultRegistry();
			$requirements = new \WordPress\AiClient\Providers\Models\DTO\ModelRequirements(
				array( \WordPress\AiClient\Providers\Models\Enums\CapabilityEnum::textGeneration() ),
				array()
			);
			$models = array();

			foreach ( self::allowed_providers() as $provider_id ) {
				if ( ! $registry->hasProvider( $provider_id ) || ! $registry->isProviderConfigured( $provider_id ) ) {
					continue;
				}
				$provider_class = $registry->getProviderClassName( $provider_id );
				$provider_meta  = $provider_class::metadata();
				foreach ( $registry->findProviderModelsMetadataForSupport( $provider_id, $requirements ) as $model ) {
					$key = $provider_id . ':' . $model->getId();
					$models[ $key ] = array(
						'provider'      => $provider_id,
						'provider_name' => $provider_meta->getName(),
						'model'         => $model->getId(),
						'name'          => $model->getName(),
					);
				}
			}

			return $models;
		} catch ( \Throwable $e ) {
			return array();
		}
	}

	/** Configuración activa, sin exponer credenciales. */
	public static function config() {
		$settings = Scope::settings();
		$source   = isset( $settings['ai_key_source'] ) ? $settings['ai_key_source'] : 'genix';

		if ( 'wp_connectors' === $source ) {
			if ( ! self::wordpress_available() ) {
				return null;
			}
			$model  = ! empty( $settings['wp_ai_model'] ) ? $settings['wp_ai_model'] : self::MODEL_AUTO;
			$models = self::available_models();
			if ( empty( $models ) ) {
				return null;
			}
			if ( self::MODEL_AUTO !== $model && ! isset( $models[ $model ] ) ) {
				return null;
			}
			return array(
				'source' => 'wp_connectors',
				'model'  => $model,
			);
		}

		if ( 'genix' === $source ) {
			return self::genix_config();
		}

		return null;
	}

	/**
	 * Configuracion de IA de Support Genix: OpenAI o Claude. Se respeta el
	 * proveedor elegido en el chatbot de Genix (`chatbot_ai_tool`): con 'claude'
	 * y clave se usa Claude; con 'openai' o cualquier otro valor, OpenAI si hay
	 * clave y, si no, Claude si hay clave. Devuelve null si no hay ninguna.
	 */
	protected static function genix_config() {
		if ( ! class_exists( '\Apbd_wps_settings' ) ) {
			return null;
		}

		$tool = 'ai_proxy';
		if ( class_exists( '\Apbd_wps_knowledge_base' ) && method_exists( '\Apbd_wps_knowledge_base', 'GetModuleInstance' ) ) {
			$instance = call_user_func( array( '\Apbd_wps_knowledge_base', 'GetModuleInstance' ) );
			if ( $instance && method_exists( $instance, 'GetOption' ) ) {
				$tool = (string) $instance->GetOption( 'chatbot_ai_tool', 'ai_proxy' );
			}
		}

		$found = array();
		$openai = \Apbd_wps_settings::GetOpenAIConfig();
		if ( $openai && ! empty( $openai['api_key'] ) ) {
			$found['openai'] = array(
				'source'   => 'genix',
				'provider' => 'openai',
				'api_key'  => $openai['api_key'],
				'model'    => ! empty( $openai['model'] ) ? $openai['model'] : 'gpt-4o-mini',
			);
		}
		if ( method_exists( '\Apbd_wps_settings', 'GetClaudeConfig' ) ) {
			$claude = \Apbd_wps_settings::GetClaudeConfig();
			if ( $claude && ! empty( $claude['api_key'] ) ) {
				$found['claude'] = array(
					'source'   => 'genix',
					'provider' => 'claude',
					'api_key'  => $claude['api_key'],
					'model'    => ! empty( $claude['model'] ) ? $claude['model'] : 'claude-haiku-4-5',
				);
			}
		}

		$order = 'claude' === $tool ? array( 'claude', 'openai' ) : array( 'openai', 'claude' );
		foreach ( $order as $provider ) {
			if ( isset( $found[ $provider ] ) ) {
				return $found[ $provider ];
			}
		}
		return null;
	}

	/**
	 * Estado de la conexión de IA sin hacer ninguna petición de red: misma
	 * condición que usa el generador (config() !== null) más el motivo cuando
	 * falla. Devuelve [ ok, source, model, reason ].
	 */
	public static function status() {
		$config = self::config();
		if ( $config ) {
			return array(
				'ok'     => true,
				'source'   => $config['source'],
				'model'    => $config['model'],
				'provider' => isset( $config['provider'] ) ? $config['provider'] : '',
				'reason'   => '',
			);
		}

		$settings = Scope::settings();
		$source   = ( isset( $settings['ai_key_source'] ) && 'wp_connectors' === $settings['ai_key_source'] ) ? 'wp_connectors' : 'genix';

		if ( 'wp_connectors' === $source ) {
			if ( version_compare( get_bloginfo( 'version' ), '7.0', '<' ) ) {
				$reason = __( 'Los Conectores de WordPress requieren WordPress 7.0 o superior.', 'ai-knowledge' );
			} elseif ( ! self::wordpress_available() ) {
				$reason = __( 'Los Conectores de WordPress no están disponibles o la IA está desactivada en este sitio.', 'ai-knowledge' );
			} elseif ( empty( self::available_models() ) ) {
				$reason = __( 'No hay modelos de texto conectados en Conectores de WordPress.', 'ai-knowledge' );
			} else {
				$reason = __( 'El modelo elegido ya no está disponible en Conectores de WordPress.', 'ai-knowledge' );
			}
		} elseif ( ! class_exists( '\Apbd_wps_settings' ) ) {
			$reason = __( 'Support Genix no está activo.', 'ai-knowledge' );
		} else {
			$reason = __( 'Support Genix no tiene una clave de OpenAI ni de Claude configurada.', 'ai-knowledge' );
		}

		return array(
			'ok'     => false,
			'source' => $source,
			'model'  => '',
			'reason' => $reason,
		);
	}

	/**
	 * Prueba de conexión real (llamada mínima, pocos tokens). Solo se llama
	 * bajo demanda desde el botón «Probar conexión»; nunca automática.
	 * Devuelve true o WP_Error con el mensaje real.
	 */
	public static function test_connection() {
		$result = self::generate( 'Responde solo con la palabra OK.', 'Responde OK.', 32, 0, 20 );
		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * Genera texto usando exclusivamente el origen seleccionado.
	 */
	public static function generate( $system, $prompt, $max_tokens, $temperature, $timeout = 40 ) {
		$config = self::config();
		if ( ! $config ) {
			return new \WP_Error( 'wookb_no_ai_connection', __( 'El origen de IA seleccionado no está conectado o el modelo ya no está disponible.', 'ai-knowledge' ) );
		}

		if ( 'wp_connectors' === $config['source'] ) {
			return self::generate_with_wordpress( $config, $system, $prompt, $max_tokens, $temperature, $timeout );
		}

		return self::generate_with_genix( $config, $system, $prompt, $max_tokens, $temperature, $timeout );
	}

	protected static function generate_with_wordpress( array $config, $system, $prompt, $max_tokens, $temperature, $timeout ) {
		$models = self::available_models();
		if ( empty( $models ) ) {
			return new \WP_Error( 'wookb_no_wp_ai_models', __( 'No hay modelos de texto conectados en WordPress para Anthropic, OpenAI o Google.', 'ai-knowledge' ) );
		}

		$builder = wp_ai_client_prompt( (string) $prompt )
			->using_system_instruction( (string) $system )
			->using_max_tokens( (int) $max_tokens )
			->using_request_options(
				\WordPress\AiClient\Providers\Http\DTO\RequestOptions::fromArray(
					array( \WordPress\AiClient\Providers\Http\DTO\RequestOptions::KEY_TIMEOUT => (float) $timeout )
				)
			);

		if ( self::MODEL_AUTO === $config['model'] ) {
			$preferences = array();
			foreach ( $models as $model ) {
				$preferences[] = array( $model['provider'], $model['model'] );
			}
			$builder->using_model_preference( ...$preferences );
		} else {
			$model    = $models[ $config['model'] ];
			$registry = \WordPress\AiClient\AiClient::defaultRegistry();
			$builder->using_model( $registry->getProviderModel( $model['provider'], $model['model'] ) );
		}

		$result = $builder->generate_text();
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$result = trim( (string) $result );
		return '' !== $result ? $result : new \WP_Error( 'wookb_ai_empty', __( 'Respuesta vacía de la IA.', 'ai-knowledge' ) );
	}

	/** Cuerpo de la peticion a la API de mensajes de Anthropic. */
	protected static function claude_request_body( $model, $system, $prompt, $max_tokens, $temperature ) {
		return array(
			'model'       => $model,
			'max_tokens'  => (int) $max_tokens,
			'temperature' => (float) $temperature,
			'system'      => (string) $system,
			'messages'    => array(
				array( 'role' => 'user', 'content' => (string) $prompt ),
			),
		);
	}

	/**
	 * Anthropic (clave de Claude de Support Genix). Mismo tratamiento de
	 * errores que el camino de OpenAI. NO probado contra la API real.
	 */
	protected static function generate_with_claude( array $config, $system, $prompt, $max_tokens, $temperature, $timeout ) {
		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => (int) $timeout,
				'headers' => array(
					'x-api-key'         => $config['api_key'],
					'anthropic-version' => '2023-06-01',
					'content-type'      => 'application/json',
				),
				'body'    => wp_json_encode( self::claude_request_body( $config['model'], $system, $prompt, $max_tokens, $temperature ) ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$json = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 ) {
			$message = isset( $json['error']['message'] ) ? $json['error']['message'] : 'Error HTTP ' . $code;
			return new \WP_Error( 'wookb_genix_ai_error', $message );
		}
		$content = isset( $json['content'][0]['text'] ) ? trim( $json['content'][0]['text'] ) : '';
		return '' !== $content ? $content : new \WP_Error( 'wookb_ai_empty', __( 'Respuesta vacía de la IA.', 'ai-knowledge' ) );
	}

	protected static function generate_with_genix( array $config, $system, $prompt, $max_tokens, $temperature, $timeout ) {
		if ( isset( $config['provider'] ) && 'claude' === $config['provider'] ) {
			return self::generate_with_claude( $config, $system, $prompt, $max_tokens, $temperature, $timeout );
		}
		$model      = $config['model'];
		$is_new_gen = ( 0 === strpos( $model, 'gpt-5' ) || 0 === strpos( $model, 'o' ) );
		$body       = array(
			'model'    => $model,
			'messages' => array(
				array( 'role' => 'system', 'content' => (string) $system ),
				array( 'role' => 'user', 'content' => (string) $prompt ),
			),
			$is_new_gen ? 'max_completion_tokens' : 'max_tokens' => (int) $max_tokens,
		);
		if ( ! $is_new_gen ) {
			$body['temperature'] = (float) $temperature;
		}

		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'timeout' => (int) $timeout,
				'headers' => array(
					'Authorization' => 'Bearer ' . $config['api_key'],
					'Content-Type'  => 'application/json',
				),
				'body' => wp_json_encode( $body ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$json = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 ) {
			$message = isset( $json['error']['message'] ) ? $json['error']['message'] : 'Error HTTP ' . $code;
			return new \WP_Error( 'wookb_genix_ai_error', $message );
		}
		$content = isset( $json['choices'][0]['message']['content'] ) ? trim( $json['choices'][0]['message']['content'] ) : '';
		return '' !== $content ? $content : new \WP_Error( 'wookb_ai_empty', __( 'Respuesta vacía de la IA.', 'ai-knowledge' ) );
	}
}
