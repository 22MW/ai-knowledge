<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resuelve el alcance: CPTs + taxonomías/términos + IDs sueltos − exclusiones.
 */
class Scope {

	public static function settings() {
		$defaults = array(
			'post_types'       => array( 'product' ),
			'tax_terms'        => array(), // [ 'product_cat' => [12, 34] ]
			'extra_ids'        => array(),
			'exclude_ids'      => array(),
			'exclude_terms'    => array(), // [ 'product_cat' => [56] ]
			'custom_fields'    => array(), // [ post_type => [field keys] ]
			'daily_limit'      => 100,
			'no_limit'         => false,
			'batch_size'       => 20,
			'debounce_seconds' => 300,
			'output_tokens'    => 2500,
			'ai_key_source'    => 'genix', // genix|own
			'own_api_key'      => '',
			'own_model'        => 'gpt-4o-mini',
			'extra_prompt'     => '',
			'chatbot_docs_list_limit' => Chatbot_Relevance_Guard::DOCS_LIST_LIMIT_DEFAULT,
		);
		$saved = get_option( 'wookb_settings', array() );
		return wp_parse_args( $saved, $defaults );
	}

	public static function update_settings( array $data ) {
		$current = self::settings();
		update_option( 'wookb_settings', array_merge( $current, $data ), false );
	}

	/**
	 * Devuelve el listado de IDs de post (idioma por defecto / todos si WPML) dentro del alcance.
	 */
	public static function resolve_ids() {
		$settings = self::settings();
		$ids      = array();

		foreach ( (array) $settings['post_types'] as $post_type ) {
			$args = array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			);

			if ( ! empty( $settings['tax_terms'] ) ) {
				$tax_query = array();
				foreach ( $settings['tax_terms'] as $taxonomy => $terms ) {
					if ( empty( $terms ) ) {
						continue;
					}
					$tax_query[] = array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => array_map( 'intval', $terms ),
					);
				}
				if ( $tax_query ) {
					$args['tax_query'] = $tax_query; // phpcs:ignore
				}
			}

			$found = get_posts( $args );
			$ids   = array_merge( $ids, $found );
		}

		if ( ! empty( $settings['extra_ids'] ) ) {
			$ids = array_merge( $ids, array_map( 'intval', $settings['extra_ids'] ) );
		}

		$ids = array_unique( $ids );

		return array_values( array_filter( $ids, array( __CLASS__, 'is_included' ) ) );
	}

	/**
	 * Un ID individual está incluido si no está excluido por ID ni por término.
	 */
	public static function is_included( $post_id ) {
		$settings = self::settings();

		if ( in_array( (int) $post_id, array_map( 'intval', $settings['exclude_ids'] ), true ) ) {
			return false;
		}

		if ( ! empty( $settings['exclude_terms'] ) ) {
			foreach ( $settings['exclude_terms'] as $taxonomy => $terms ) {
				if ( empty( $terms ) ) {
					continue;
				}
				if ( has_term( array_map( 'intval', $terms ), $taxonomy, $post_id ) ) {
					return false;
				}
			}
		}

		return true;
	}

	public static function custom_fields_for( $post_type ) {
		$settings = self::settings();
		return isset( $settings['custom_fields'][ $post_type ] ) ? $settings['custom_fields'][ $post_type ] : array();
	}

	/**
	 * Ruido tecnico conocido: campos internos de Elementor, WooCommerce core (ya
	 * cubiertos por el extractor Woo dedicado), Bookings, RankMath, WPML/WCML,
	 * Google Listings & Ads, RedSys, Restrict Content Pro, YITH renewals,
	 * MonsterInsights, Complianz e Independent Analytics. No aportan nada al
	 * contenido generado y solo ensucian los selectores del admin.
	 *
	 * Fuente unica compartida entre pestañas (Alcance / Exclusiones) para no
	 * duplicar ni desincronizar criterios de filtrado.
	 */
	public static function noise_meta_prefixes() {
		return array(
			'_elementor_',
			'_wc_booking_',
			'_wc_gla_',
			'_jet_woo_product_',
			'_booking_',
			'_wc_average_rating',
			'_wc_display_cost',
			'_wc_rating_count',
			'_wc_review_count',
			'_wc_sc_',
			'_cmplz_',
			'rank_math_',
			'_wpml_',
			'_wcml_',
			'wcml_',
			'_redsys',
			'_rcp_',
			'_ywcars_',
			'_monsterinsights_',
			'cmplz_',
			'iawp_',
			'_alp_',
			'_ame_',
		);
	}

	public static function noise_meta_exact() {
		return array(
			'_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date', '_wp_page_template', '_thumbnail_id',
			'_price', '_regular_price', '_stock', '_stock_status', '_sku', '_manage_stock', '_backorders',
			'_sold_individually', '_virtual', '_downloadable', '_tax_class', '_tax_status',
			'_product_attributes', '_product_image_gallery', '_upsell_ids', '_crosssell_ids',
			'_children', '_default_attributes', '_download_expiry', '_download_limit',
			'_has_additional_costs', '_low_stock_amount', '_resource_base_costs', '_resource_block_costs',
			'_last_translation_edit_mode', 'copied_media_ids', 'referenced_media_ids',
			'attr_label_translations', 'send_coupons_on_renewals', 'total_sales', 'wc_booking_resource_label',
		);
	}

	/**
	 * Muestrea varios posts recientes (no solo 1) de un CPT y fusiona sus meta keys,
	 * filtrando ruido tecnico conocido. Usado por los selectores de campos custom
	 * del admin (Alcance, y Exclusiones si en el futuro lo necesita).
	 */
	public static function sampled_custom_field_keys( $post_type, $sample_size = 20 ) {
		$sample = get_posts(
			array(
				'post_type'      => $post_type,
				'posts_per_page' => $sample_size,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);

		$keys = array();
		foreach ( $sample as $sample_id ) {
			$keys = array_merge( $keys, array_keys( get_post_meta( $sample_id ) ) );
		}
		$keys = array_unique( $keys );

		$noise_prefixes = self::noise_meta_prefixes();
		$noise_exact    = self::noise_meta_exact();

		$keys = array_filter(
			$keys,
			function ( $k ) use ( $noise_prefixes, $noise_exact ) {
				if ( in_array( $k, $noise_exact, true ) ) {
					return false;
				}
				foreach ( $noise_prefixes as $prefix ) {
					if ( 0 === strpos( $k, $prefix ) ) {
						return false;
					}
				}
				return true;
			}
		);
		sort( $keys );

		return $keys;
	}
}
