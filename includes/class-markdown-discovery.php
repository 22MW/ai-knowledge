<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fases 4 y 5 del roadmap: cosas que se imprimen en el <head> para contenido
 * dentro de Scope, reutilizando datos ya existentes sin generar nada nuevo.
 *
 * - Fase 4: anuncia el .md ya generado (el mismo que usa el chatbot vía
 *   Document_Pipeline/Markdown_Store) con un
 *   <link rel="alternate" type="text/markdown"> apuntando al .md real.
 * - Fase 5: datos estructurados Schema.org (JSON-LD) por tipo de contenido,
 *   reutilizando el mismo extractor (Extractors\Extractor_Base/Extractor_Woo)
 *   que ya usa Document_Pipeline y el endpoint REST de la Fase 3.
 *
 * Ambas comparten el mismo gating (scoped_post_id()): solo contenido
 * singular y dentro de Scope::is_included(). No se duplica esa comprobación
 * en dos sitios.
 */
class Markdown_Discovery {

	public static function init() {
		// Prioridad 20: después de que WordPress y el resto de plugins hayan
		// impreso lo suyo en el <head> (meta tags, canonical, etc.), sin
		// competir por ser lo primero -- esto es solo un enlace/dato adicional.
		add_action( 'wp_head', array( __CLASS__, 'print_link' ), 20 );
		add_action( 'wp_head', array( __CLASS__, 'print_json_ld' ), 21 );
	}

	/**
	 * Gating compartido por Fase 4 y Fase 5: devuelve el post_id solo si el
	 * contenido actual es singular (portada/archivos/categorías quedan fuera,
	 * no tienen un post de origen único) Y está dentro del alcance
	 * configurado del plugin. Null en cualquier otro caso.
	 */
	protected static function scoped_post_id() {
		if ( ! is_singular() ) {
			return null;
		}

		$post_id = get_the_ID();
		if ( ! $post_id || ! Scope::is_included( $post_id ) ) {
			return null;
		}

		return $post_id;
	}

	public static function print_link() {
		$post_id = self::scoped_post_id();
		if ( ! $post_id ) {
			return;
		}

		// Idioma REAL de este post concreto (no el idioma por defecto del
		// sitio): con WPML activo, cada traducción es un post_id distinto con
		// su propia fila en Registry -- Wpml::element_language() ya resuelve
		// esto con fallback monolingüe 'es' si WPML no está activo.
		$lang = Wpml::element_language( $post_id );
		$row  = Registry::find( $post_id, $lang );

		if ( ! $row || 'synced' !== $row->status || empty( $row->md_path ) ) {
			// Sin fila sincronizada para este post+idioma (no generado aún, en
			// cola, con error, o huérfano): nada que enlazar todavía. El modo
			// manual (override_mode = 'manual') SÍ tiene fila 'synced' con
			// md_path real -- Admin::publish_manual_text() lo escribe igual que
			// el pipeline automático, así que se enlaza igual sin distinción.
			return;
		}

		printf(
			'<link rel="alternate" type="text/markdown" href="%s" />' . "\n",
			esc_url( Markdown_Store::public_url( $row->md_path ) )
		);
	}

	/**
	 * Fase 5: JSON-LD Schema.org. Mapeo fijo, no configurable todavía (tal
	 * como pide el roadmap): 'product' -> Product/Offer, resto -> Article.
	 *
	 * Decisión sobre RankMath (activo en este sitio, includes/modules/schema
	 * de seo-by-rank-math): su módulo "Schema" genera por defecto JSON-LD
	 * Product/Article para el mismo contenido singular, configurable por el
	 * usuario con más control del que tiene sentido replicar aquí (tipos de
	 * schema por post, campos adicionales, rich snippets). Emitir los dos a
	 * la vez arriesga JSON-LD duplicado o contradictorio en la misma página
	 * (dos bloques <script type="application/ld+json"> con el mismo @type
	 * para la misma URL), que es peor para SEO que no cubrirlo nosotros --
	 * evitar el conflicto pesa más que "cubrirlo siempre". Por eso, antes de
	 * imprimir nada, should_skip_schema() comprueba TODAS las fuentes
	 * conocidas que ya cubren esto: WooCommerce core (Product, siempre
	 * activo salvo que alguien desenganche su hook explícitamente), RankMath
	 * (Article y Product, si su módulo 'schema' está activo), Yoast SEO y
	 * AIOSEO (ambos activos por defecto sin módulo desactivable simple). Si
	 * ninguna de esas fuentes aplica, se imprime el nuestro como red de
	 * seguridad mínima (mejor Schema.org básico que ninguno).
	 */
	public static function print_json_ld() {
		$post_id = self::scoped_post_id();
		if ( ! $post_id ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		if ( self::should_skip_schema( $post_id, $post->post_type ) ) {
			return;
		}

		$extractor = Extractors\Extractor_Base::for_post_type( $post->post_type );
		$data      = $extractor->extract( $post_id );
		if ( ! $data ) {
			return;
		}

		$schema = ( 'product' === $post->post_type && class_exists( 'WooCommerce' ) )
			? self::build_product_schema( $post, $data )
			: self::build_article_schema( $post, $data );

		if ( ! $schema ) {
			return;
		}

		// wp_json_encode() (nunca concatenación de strings) para que todo el
		// texto quede correctamente escapado dentro del JSON; JSON_UNESCAPED_SLASHES
		// evita las barras invertidas feas en las URLs sin afectar la validez.
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON, no HTML; ver wp_json_encode() arriba.
	}

	/**
	 * True si alguna fuente conocida ya imprime JSON-LD para este mismo
	 * contenido -- decidido ANTES de generar/imprimir nada nuestro,
	 * comprobando si el plugin/hook responsable está activo, nunca
	 * inspeccionando el HTML ya generado (no es fiable: el orden de hooks
	 * varía y no hay garantía de examinar el output completo a tiempo).
	 */
	protected static function should_skip_schema( $post_id, $post_type ) {
		$skip = false;

		// WooCommerce core SIEMPRE imprime su propio Product/Offer JSON-LD en
		// wp_footer para productos (WC_Structured_Data::output_structured_data(),
		// enganchado sin condición en su constructor) -- no es un módulo
		// opcional como el de RankMath, así que no hace falta preguntarle a
		// RankMath para este caso: si WooCommerce está activo y ese hook sigue
		// enganchado (nadie lo ha desenganchado a mano), el suyo ya cubre
		// 'product' y el nuestro sería un duplicado exacto (mismo @type, misma
		// URL).
		if ( 'product' === $post_type && function_exists( 'WC' ) && WC()->structured_data
			&& has_action( 'wp_footer', array( WC()->structured_data, 'output_structured_data' ) ) ) {
			$skip = true;
		}

		// RankMath: solo si su módulo 'schema' está activo (es desactivable,
		// a diferencia de WooCommerce/Yoast/AIOSEO) -- misma comprobación que
		// usa el propio RankMath para decidir si generar su JSON-LD.
		if ( ! $skip && class_exists( '\RankMath\Helper' ) && \RankMath\Helper::is_module_active( 'schema' ) ) {
			$skip = true;
		}

		// Yoast SEO y AIOSEO: ambos imprimen schema por defecto en cuanto están
		// activos, sin un módulo "Schema" desactivable de forma simple como
		// RankMath -- su sola presencia ya es suficiente indicio de conflicto.
		if ( ! $skip && ( defined( 'WPSEO_VERSION' ) || defined( 'AIOSEO_VERSION' ) ) ) {
			$skip = true;
		}

		/**
		 * Fuerza (o desactiva) el salto de nuestro JSON-LD para un post/post_type
		 * concreto, para cubrir a mano cualquier caso no contemplado aquí (otro
		 * plugin de SEO no listado, o un hook de WooCommerce desenganchado a
		 * propósito) sin tener que tocar código.
		 *
		 * @param bool   $skip      Si se debe omitir nuestro JSON-LD.
		 * @param int    $post_id   ID del post actual.
		 * @param string $post_type post_type del post actual.
		 */
		return (bool) apply_filters( 'wookb_skip_schema', $skip, $post_id, $post_type );
	}

	/**
	 * Product/Offer: usa el mismo dato ya extraído por Extractor_Woo
	 * (price/regular_price/sale_price/stock/sku) sin volver a consultar
	 * WooCommerce -- salvo la imagen destacada, que el extractor no incluye
	 * (no hace falta para el Markdown/chatbot) y aquí sí para el schema.
	 */
	protected static function build_product_schema( $post, array $data ) {
		$price = '' !== (string) ( $data['sale_price'] ?? '' ) ? $data['sale_price'] : ( $data['regular_price'] ?? '' );

		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'Product',
			'name'        => $data['title'],
			'description' => $data['excerpt'] ? $data['excerpt'] : $data['content'],
			'url'         => esc_url_raw( $data['url'] ),
		);

		if ( ! empty( $data['sku'] ) ) {
			$schema['sku'] = $data['sku'];
		}

		$image = get_the_post_thumbnail_url( $post->ID, 'full' );
		if ( $image ) {
			$schema['image'] = esc_url_raw( $image );
		}

		$offer = array(
			'@type'         => 'Offer',
			'url'           => esc_url_raw( $data['url'] ),
			'priceCurrency' => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
			'availability'  => 'in_stock' === $data['stock']
				? 'https://schema.org/InStock'
				: 'https://schema.org/OutOfStock',
		);
		if ( '' !== (string) $price ) {
			$offer['price'] = (string) $price;
		}
		$schema['offers'] = $offer;

		return $schema;
	}

	/**
	 * Article: resto de post_types dentro de Scope. Fechas del post real
	 * (formato ISO 8601, el que espera Schema.org), autor si tiene nombre
	 * público visible.
	 */
	protected static function build_article_schema( $post, array $data ) {
		$author_name = get_the_author_meta( 'display_name', $post->post_author );

		$schema = array(
			'@context'      => 'https://schema.org',
			'@type'         => 'Article',
			'headline'      => $data['title'],
			'description'   => $data['excerpt'] ? $data['excerpt'] : mb_substr( $data['content'], 0, 300 ),
			'url'           => esc_url_raw( $data['url'] ),
			'datePublished' => mysql2date( 'c', $post->post_date_gmt, false ),
			'dateModified'  => mysql2date( 'c', $post->post_modified_gmt, false ),
		);

		if ( $author_name ) {
			$schema['author'] = array(
				'@type' => 'Person',
				'name'  => $author_name,
			);
		}

		$image = get_the_post_thumbnail_url( $post->ID, 'full' );
		if ( $image ) {
			$schema['image'] = esc_url_raw( $image );
		}

		return $schema;
	}
}
