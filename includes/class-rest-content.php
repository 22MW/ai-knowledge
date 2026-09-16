<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fase 3 del roadmap: expone el contenido real de WordPress/WooCommerce en
 * JSON, sin pasar por IA -- el mismo dato que ya extrae
 * Extractors\Extractor_Base::for_post_type()/Extractor_Woo para generar el
 * Markdown (Document_Pipeline), servido tal cual vía REST. No duplica esa
 * lógica de extracción: reutiliza el mismo extractor objeto por objeto.
 *
 * Namespace REST propio 'ai-knowledge/v1' (distinto de 'wp/v2', que ya
 * protege Rest_Guard para sgkb-docs): estas rutas son solo lectura, públicas
 * a propósito (contenido ya pensado como público, filtrado por Scope), y no
 * tienen nada que ver con la protección de sgkb-docs.
 */
class Rest_Content {

	const NAMESPACE_ = 'ai-knowledge/v1';

	const DEFAULT_PER_PAGE = 20;
	const MAX_PER_PAGE     = 100;

	// Cabecera de caché HTTP (sin invalidación activa todavía, ver roadmap
	// Fase 3 paso 6 -- eso queda para una fase posterior): 5 minutos es
	// suficiente para absorber un crawler agresivo sin servir datos muy
	// desactualizados.
	const CACHE_CONTROL = 'public, max-age=300';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE_,
			'/content/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_content' ),
				// Público a propósito: solo lectura, y el propio callback filtra
				// por Scope::is_included() -- no se expone nada que el admin no
				// haya puesto ya dentro del alcance del plugin.
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'validate_callback' => function ( $value ) {
							return is_numeric( $value );
						},
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_,
			'/(?P<post_type>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_list' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE_,
			'/openapi.json',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_openapi_spec' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * GET /ai-knowledge/v1/content/{id} -- un contenido por ID.
	 *
	 * 404 genérico tanto si el ID no existe, no está publicado, como si
	 * existe pero está fuera del alcance configurado (Scope::is_included()):
	 * a propósito no se distingue el motivo en la respuesta, para no dar
	 * pistas de qué contenido oculto existe (mismo criterio que Rest_Guard).
	 */
	public static function get_content( \WP_REST_Request $request ) {
		$post_id = absint( $request['id'] );
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( ! $post || 'publish' !== $post->post_status || ! Scope::is_included( $post_id ) ) {
			return self::not_found();
		}

		$extractor = Extractors\Extractor_Base::for_post_type( $post->post_type );
		$data      = $extractor->extract( $post_id );
		if ( ! $data ) {
			return self::not_found();
		}

		$response = rest_ensure_response( $data );
		$response->header( 'Cache-Control', self::CACHE_CONTROL );
		return $response;
	}

	/**
	 * GET /ai-knowledge/v1/{post_type} -- listado paginado de ese post_type,
	 * solo IDs dentro del alcance configurado (Scope::resolve_ids() ya
	 * aplica Scope::is_included() a cada ID que devuelve).
	 *
	 * Paginación: parámetros 'page' (por defecto 1) y 'per_page' (por defecto
	 * 20, tope 100). Total expuesto en las cabeceras X-WP-Total/
	 * X-WP-TotalPages -- mismo patrón que ya usa WP_REST_Posts_Controller
	 * en /wp/v2/{post_type}, así que un cliente que ya sepa leer la
	 * paginación estándar de WordPress lo entiende sin nada nuevo que aprender.
	 */
	public static function get_list( \WP_REST_Request $request ) {
		$post_type = sanitize_key( $request['post_type'] );
		$post_type_obj = post_type_exists( $post_type ) ? get_post_type_object( $post_type ) : null;

		// post_type inexistente o no público (incluye el caso "WooCommerce
		// inactivo y se pide /product": sin WooCommerce, 'product' ni siquiera
		// está registrado como CPT, así que post_type_exists() ya es false) ->
		// mismo 404 genérico que un ID fuera de alcance.
		if ( ! $post_type_obj || ! $post_type_obj->public ) {
			return self::not_found();
		}

		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = (int) $request->get_param( 'per_page' );
		$per_page = $per_page > 0 ? min( self::MAX_PER_PAGE, $per_page ) : self::DEFAULT_PER_PAGE;

		// Base: TODOS los IDs ya dentro del alcance configurado (cualquier
		// post_type, con exclusiones ya aplicadas) -- se reutiliza tal cual en
		// vez de reimplementar el filtrado de tax_terms/exclusiones que ya
		// hace Scope::resolve_ids(), y se acota aquí al post_type pedido.
		$ids = array_values(
			array_filter(
				Scope::resolve_ids(),
				function ( $id ) use ( $post_type ) {
					return $post_type === get_post_type( $id );
				}
			)
		);

		$total       = count( $ids );
		$total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 0;
		$page_ids    = array_slice( $ids, ( $page - 1 ) * $per_page, $per_page );

		$items = array();
		if ( $page_ids ) {
			$extractor = Extractors\Extractor_Base::for_post_type( $post_type );
			foreach ( $page_ids as $id ) {
				$data = $extractor->extract( $id );
				if ( $data ) {
					$items[] = $data;
				}
			}
		}

		$response = rest_ensure_response( $items );
		$response->header( 'Cache-Control', self::CACHE_CONTROL );
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) $total_pages );
		return $response;
	}

	/**
	 * Fase 7: GET /ai-knowledge/v1/openapi.json -- documento OpenAPI 3.0
	 * REAL, escrito a mano (decisión de evaluar-cambio: el índice de
	 * descubrimiento nativo de WordPress en /wp-json/ai-knowledge/v1 no es
	 * formato OpenAPI, así que no vale renombrarlo). Describe exactamente
	 * las 2 rutas de arriba -- si cambian sus parámetros o su respuesta, hay
	 * que actualizar esto a mano tambien, no se genera solo.
	 */
	public static function get_openapi_spec() {
		$response = rest_ensure_response( self::openapi_document() );
		$response->header( 'Cache-Control', self::CACHE_CONTROL );
		return $response;
	}

	protected static function openapi_document() {
		$content_item_schema = array(
			'type'       => 'object',
			'properties' => array(
				'id'            => array( 'type' => 'integer' ),
				'post_type'     => array( 'type' => 'string' ),
				'title'         => array( 'type' => 'string' ),
				'content'       => array( 'type' => 'string' ),
				'excerpt'       => array( 'type' => 'string' ),
				'url'           => array(
					'type'   => 'string',
					'format' => 'uri',
				),
				'lang'          => array( 'type' => 'string' ),
				'taxonomies'    => array(
					'type'        => 'object',
					'description' => __( 'Mapa taxonomía => lista de nombres de término.', 'ai-knowledge' ),
					'additionalProperties' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
				),
				'custom_fields' => array(
					'type'                 => 'object',
					'description'          => __( 'Solo los campos personalizados seleccionados en Alcance.', 'ai-knowledge' ),
					'additionalProperties'  => true,
				),
				'price'         => array(
					'type'        => 'string',
					'nullable'    => true,
					'description' => __( 'Solo poblado si el contenido es un producto WooCommerce.', 'ai-knowledge' ),
				),
				'stock'         => array(
					'type'     => 'string',
					'enum'     => array( 'in_stock', 'out_of_stock' ),
					'nullable' => true,
				),
				'sku'           => array(
					'type'     => 'string',
					'nullable' => true,
				),
				'variants'      => array(
					'type'  => 'array',
					'items' => array( 'type' => 'object' ),
				),
			),
		);

		$not_found_response = array(
			'description' => __( 'No encontrado (origen inexistente, no publicado, fuera de alcance, o post_type inexistente/no público).', 'ai-knowledge' ),
			'content'     => array(
				'application/json' => array(
					'schema' => array(
						'type'       => 'object',
						'properties' => array(
							'code'    => array( 'type' => 'string' ),
							'message' => array( 'type' => 'string' ),
							'data'    => array(
								'type'       => 'object',
								'properties' => array( 'status' => array( 'type' => 'integer' ) ),
							),
						),
					),
				),
			),
		);

		return array(
			'openapi' => '3.0.3',
			'info'    => array(
				'title'       => __( 'AI Knowledge & Visibility — API de contenido', 'ai-knowledge' ),
				'description' => __( 'API pública de solo lectura del contenido dentro del alcance configurado del plugin, sin pasar por IA (Fase 3 del roadmap).', 'ai-knowledge' ),
				'version'     => WOOKB_VERSION,
			),
			'servers' => array(
				array( 'url' => home_url( '/wp-json/' . self::NAMESPACE_ ) ),
			),
			'paths'   => array(
				'/content/{id}' => array(
					'get' => array(
						'summary'    => __( 'Un contenido por ID.', 'ai-knowledge' ),
						'parameters' => array(
							array(
								'name'     => 'id',
								'in'       => 'path',
								'required' => true,
								'schema'   => array( 'type' => 'integer' ),
							),
						),
						'responses'  => array(
							'200' => array(
								'description' => __( 'Contenido encontrado.', 'ai-knowledge' ),
								'content'     => array(
									'application/json' => array(
										'schema' => array( '$ref' => '#/components/schemas/ContentItem' ),
									),
								),
							),
							'404' => $not_found_response,
						),
					),
				),
				'/{post_type}'  => array(
					'get' => array(
						'summary'    => __( 'Listado paginado de un post_type dentro del alcance.', 'ai-knowledge' ),
						'parameters' => array(
							array(
								'name'        => 'post_type',
								'in'          => 'path',
								'required'    => true,
								'schema'      => array( 'type' => 'string' ),
								'description' => __( 'Ej. "product" (WooCommerce) o cualquier otro post_type público dentro del alcance.', 'ai-knowledge' ),
							),
							array(
								'name'   => 'page',
								'in'     => 'query',
								'schema' => array(
									'type'    => 'integer',
									'default' => 1,
								),
							),
							array(
								'name'   => 'per_page',
								'in'     => 'query',
								'schema' => array(
									'type'    => 'integer',
									'default' => self::DEFAULT_PER_PAGE,
									'maximum' => self::MAX_PER_PAGE,
								),
							),
						),
						'responses'  => array(
							'200' => array(
								'description' => __( 'Listado paginado (cabeceras X-WP-Total/X-WP-TotalPages).', 'ai-knowledge' ),
								'content'     => array(
									'application/json' => array(
										'schema' => array(
											'type'  => 'array',
											'items' => array( '$ref' => '#/components/schemas/ContentItem' ),
										),
									),
								),
							),
							'404' => $not_found_response,
						),
					),
				),
			),
			'components' => array(
				'schemas' => array(
					'ContentItem' => $content_item_schema,
				),
			),
		);
	}

	/**
	 * 404 genérico, mismo formato para cualquier motivo (no existe, no
	 * publicado, fuera de alcance, post_type inexistente/no público): no se
	 * distingue el motivo en el mensaje, para no confirmar la existencia de
	 * contenido oculto a quien no debería verlo.
	 */
	protected static function not_found() {
		return new \WP_Error(
			'wookb_rest_not_found',
			__( 'No encontrado.', 'ai-knowledge' ),
			array( 'status' => 404 )
		);
	}
}
