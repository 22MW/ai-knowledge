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

		if ( 'genix' === $source && class_exists( '\Apbd_wps_settings' ) ) {
			$cfg = \Apbd_wps_settings::GetOpenAIConfig();
			if ( $cfg && ! empty( $cfg['api_key'] ) ) {
				return array(
					'source'  => 'genix',
					'api_key' => $cfg['api_key'],
					'model'   => ! empty( $cfg['model'] ) ? $cfg['model'] : 'gpt-4o-mini',
				);
			}
		}

		return null;
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

	protected static function generate_with_genix( array $config, $system, $prompt, $max_tokens, $temperature, $timeout ) {
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
