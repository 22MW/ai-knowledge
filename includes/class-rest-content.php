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
		add_action( 'wp_head', array( __CLASS__, 'print_feed_links' ), 20 );
	}

	/**
	 * Fase 9, ampliación: enlaces de descubrimiento en el <head>, sitewide (no
	 * por post, a diferencia del <link> de Markdown de la Fase 4) porque un
	 * feed representa el catálogo entero, no un contenido concreto. Para
	 * herramientas que no leen llms.txt (mismo dato ya enlazado ahí, ver
	 * Llms_Txt::build()).
	 */
	public static function print_feed_links() {
		if ( class_exists( 'WooCommerce' ) ) {
			printf(
				'<link rel="alternate" type="application/rss+xml" title="%s" href="%s" />' . "\n",
				esc_attr__( 'Feed de productos (Google Merchant)', 'ai-knowledge' ),
				esc_url( rest_url( self::NAMESPACE_ . '/feeds/products.xml' ) )
			);
		}
		printf(
			'<link rel="alternate" type="application/json" title="%s" href="%s" />' . "\n",
			esc_attr__( 'Feed de contenido', 'ai-knowledge' ),
			esc_url( rest_url( self::NAMESPACE_ . '/feeds/content.json' ) )
		);
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

		// Fase 9: feeds especializados, formato que ya esperan otras
		// plataformas (comparadores, Google Merchant), en vez del JSON de
		// propósito general de arriba. Solo lectura, mismo filtro por Scope.
		if ( class_exists( 'WooCommerce' ) ) {
			register_rest_route(
				self::NAMESPACE_,
				'/feeds/products\.xml',
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_feed_products_xml' ),
					'permission_callback' => '__return_true',
				)
			);
		}

		register_rest_route(
			self::NAMESPACE_,
			'/feeds/content\.json',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_feed_content_json' ),
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
	 * Fase 9: GET /ai-knowledge/v1/feeds/products.xml -- feed en formato
	 * Google Merchant/comparadores (RSS 2.0 + espacio de nombres "g:"),
	 * reutilizando Extractor_Woo (mismo dato que ya extrae Fase 3, sin
	 * duplicar lógica). Solo productos dentro del alcance. Se escribe la
	 * salida directamente (mismo patrón que Llms_Txt/Markdown_Server) porque
	 * el servidor REST envuelve en JSON por defecto y este formato no lo es.
	 */
	public static function get_feed_products_xml() {
		$currency = get_woocommerce_currency();
		$ids      = array_values(
			array_filter(
				Scope::resolve_ids(),
				function ( $id ) {
					return 'product' === get_post_type( $id );
				}
			)
		);

		$rss = new \SimpleXMLElement( '<?xml version="1.0" encoding="UTF-8"?><rss xmlns:g="http://base.google.com/ns/1.0" version="2.0"></rss>' );
		$channel = $rss->addChild( 'channel' );
		$channel->addChild( 'title', esc_html( get_bloginfo( 'name' ) ) );
		$channel->addChild( 'link', esc_url( home_url( '/' ) ) );
		$channel->addChild( 'description', esc_html__( 'Catálogo de productos', 'ai-knowledge' ) );

		$extractor = Extractors\Extractor_Base::for_post_type( 'product' );
		foreach ( $ids as $id ) {
			$data = $extractor->extract( $id );
			if ( ! $data ) {
				continue;
			}
			$item = $channel->addChild( 'item' );
			$item->addChild( 'g:id', esc_html( $data['sku'] ? $data['sku'] : (string) $id ), 'http://base.google.com/ns/1.0' );
			$item->addChild( 'title', esc_html( $data['title'] ) );
			$item->addChild( 'description', esc_html( $data['short_description'] ? $data['short_description'] : $data['excerpt'] ) );
			$item->addChild( 'link', esc_url( $data['url'] ) );
			$image = get_the_post_thumbnail_url( $id, 'full' );
			if ( $image ) {
				$item->addChild( 'g:image_link', esc_url( $image ), 'http://base.google.com/ns/1.0' );
			}
			$item->addChild( 'g:availability', 'in_stock' === $data['stock'] ? 'in stock' : 'out of stock', 'http://base.google.com/ns/1.0' );
			if ( '' !== $data['price'] ) {
				$item->addChild( 'g:price', esc_html( $data['regular_price'] . ' ' . $currency ), 'http://base.google.com/ns/1.0' );
			}
			$item->addChild( 'g:condition', 'new', 'http://base.google.com/ns/1.0' );
			// Sin marca/GTIN/MPN propios: se declara explícitamente para que
			// Google no rechace el feed por identificador único ausente.
			$item->addChild( 'g:identifier_exists', 'no', 'http://base.google.com/ns/1.0' );
		}

		header( 'Content-Type: application/xml; charset=utf-8' );
		echo $rss->asXML(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- XML ya escapado campo a campo arriba.
		exit;
	}

	/**
	 * Fase 9: GET /ai-knowledge/v1/feeds/content.json -- feed genérico para
	 * el resto de post_types del alcance (no producto), mismo dato que ya
	 * extrae Extractor_Base. Sin paginar: pensado para un consumidor externo
	 * que se sincroniza entero, no para un crawler incremental (eso ya lo
	 * cubre /{post_type} de la Fase 3).
	 */
	public static function get_feed_content_json() {
		$ids = array_values(
			array_filter(
				Scope::resolve_ids(),
				function ( $id ) {
					return 'product' !== get_post_type( $id );
				}
			)
		);

		$items = array();
		foreach ( $ids as $id ) {
			$extractor = Extractors\Extractor_Base::for_post_type( get_post_type( $id ) );
			$data      = $extractor->extract( $id );
			if ( $data ) {
				$items[] = $data;
			}
		}

		header( 'Content-Type: application/json; charset=utf-8' );
		echo wp_json_encode( array( 'items' => $items ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode ya produce JSON seguro.
		exit;
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
