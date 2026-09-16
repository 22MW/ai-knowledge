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
			'post_types'                    => array( 'product' ),
			// Fase 10, pieza 4: 'explicit' (usa post_types de arriba) o
			// 'all_public' (todos los CPT publicos, excepto attachment y los
			// marcados en post_types_excluded_when_all).
			'post_types_mode'               => 'explicit',
			'post_types_excluded_when_all'  => array(),
			// Fase 10, pieza 2: sustituye a tax_terms/exclude_terms.
			// [ taxonomia => [ term_id => 'include'|'exclude' ] ]
			'term_actions'                  => array(),
			// Fase 10, pieza 2: sustituye a extra_ids/exclude_ids.
			// [ post_id => 'include'|'exclude' ]
			'id_actions'                    => array(),
			'custom_fields'    => array(), // [ post_type => [field keys] ]
			'daily_limit'      => 100,
			'no_limit'         => false,
			'batch_size'       => 20,
			'debounce_seconds' => 300,
			'output_tokens'    => 2500,
			'body_char_limit'  => 1000, // Generator::BODY_CHAR_LIMIT es solo el respaldo si esta clave faltara.
			'ai_key_source'    => 'genix', // genix|own
			'own_api_key'      => '',
			'own_model'        => 'gpt-4o-mini',
			'extra_prompt'     => '',
			'chatbot_docs_list_limit' => Chatbot_Relevance_Guard::DOCS_LIST_LIMIT_DEFAULT,
			// Fase 1: post_types donde aparece el meta box "Base de conocimiento
			// IA" en el editor. Null = no guardado todavia -> default real
			// (todos los post_types publicos) resuelto en tiempo de uso por
			// Editor_Metabox::allowed_post_types(), para que cubra tambien
			// CPTs de terceros registrados despues de instalar el plugin.
			'editor_button_post_types' => null,
			// Fase 2, pestaña WooCommerce, sección "Rellenar a mano": datos que
			// WooCommerce no puede saber por sí solo. El contacto/horario NO se
			// duplica aquí: se reutiliza tal cual desde Chatbot_Prompt_Builder::
			// get_saved_answers()['contacto'] (ver Store_Info_Doc::build_store_info_body()).
			'delivery_time_note' => '',
			'legal_notes_extra'  => '',
			// Selecciones de la pestaña WooCommerce (checkbox = incluido en el
			// documento generado, mismo patron que Contenido): null = nunca
			// guardado todavia -> se tratan TODOS los detectados como
			// pre-marcados (no perder contenido ya publicado sin decision
			// explicita); array = seleccion real ya guardada por el admin,
			// aunque este vacio (nada marcado a proposito).
			'wc_shipping_methods'  => null, // [ instance_id => 'include' ]
			'wc_tax_rates'         => null, // [ tax_rate_id => 'include' ]
			'wc_catalog_categories' => null, // [ term_id => 'include' ]
			'wc_payment_methods'   => null, // [ gateway_id => 'include' ]
			// Fallback manual (Rellenar a mano): solo se usan si WooCommerce
			// no tiene el dato como metodo de envio real detectable.
			'wc_min_order_note'   => '',
			'wc_pickup_available' => false,
			// Snapshot editable: precargado con el valor real de WooCommerce
			// la primera vez que se guarda esta pestaña, pero desde entonces
			// vive aqui -- si se borra la pagina o cambia el ajuste en
			// WooCommerce despues, el documento generado no lo pierde hasta
			// que el admin lo edite a mano otra vez.
			'wc_store_name'    => '',
			'wc_currency'      => '',
			'wc_base_country'  => '',
			'wc_terms_text'    => '',
			'wc_returns_text'  => '',
			// Contacto/horario PROPIO de la tienda online, distinto del
			// contacto general del negocio (pestaña Negocio, cuestionario
			// del chatbot): pueden ser diferentes (ej. soporte de pedidos
			// vs. atencion general). Vacio = usa el de Negocio como fallback.
			'wc_contact_hours' => '',
			// Fase 8: aviso a IndexNow. La clave se genera sola (Indexnow::get_key()),
			// nunca se rellena a mano desde aquí.
			'indexnow_enabled' => false,
			'indexnow_key'     => '',
			// Fase 11 (revision UX 2026-09-16): mapa user_agent => 'allow'|'block',
			// unica fuente de verdad para robots.txt y .htaccess. Si un bot del
			// catalogo no aparece aqui, se usa su default_action (Crawler_Catalog).
			'crawler_actions' => array(),
		);
		$saved = get_option( 'wookb_settings', array() );

		// Fase 10, pieza 2: migracion lazy de las 4 claves viejas
		// (tax_terms/exclude_terms/extra_ids/exclude_ids) a term_actions/
		// id_actions. No puede ir en el activation hook: no se ejecuta en
		// updates de un plugin ya activo, y el activador no toca
		// wookb_settings. Idempotente: solo se dispara si term_actions no
		// existe todavia en el option crudo; al terminar, term_actions
		// siempre existe (aunque vacio) y no se repite.
		if ( ! isset( $saved['term_actions'] ) ) {
			$saved = self::migrate_legacy_scope_settings( $saved );
		}

		return wp_parse_args( $saved, $defaults );
	}

	/**
	 * Migra tax_terms/exclude_terms/extra_ids/exclude_ids (formato viejo) a
	 * term_actions/id_actions (formato nuevo), reproduciendo exactamente la
	 * prioridad que tenia is_included(): exclude siempre pisa include, por
	 * eso las listas exclude_* se procesan DESPUES de las include_*.
	 */
	protected static function migrate_legacy_scope_settings( array $raw ) {
		$term_actions = array();
		if ( ! empty( $raw['tax_terms'] ) && is_array( $raw['tax_terms'] ) ) {
			foreach ( $raw['tax_terms'] as $taxonomy => $terms ) {
				foreach ( (array) $terms as $term_id ) {
					$term_actions[ $taxonomy ][ (int) $term_id ] = 'include';
				}
			}
		}
		if ( ! empty( $raw['exclude_terms'] ) && is_array( $raw['exclude_terms'] ) ) {
			foreach ( $raw['exclude_terms'] as $taxonomy => $terms ) {
				foreach ( (array) $terms as $term_id ) {
					$term_actions[ $taxonomy ][ (int) $term_id ] = 'exclude';
				}
			}
		}

		$id_actions = array();
		if ( ! empty( $raw['extra_ids'] ) ) {
			foreach ( (array) $raw['extra_ids'] as $post_id ) {
				$id_actions[ (int) $post_id ] = 'include';
			}
		}
		if ( ! empty( $raw['exclude_ids'] ) ) {
			foreach ( (array) $raw['exclude_ids'] as $post_id ) {
				$id_actions[ (int) $post_id ] = 'exclude';
			}
		}

		$raw['term_actions'] = $term_actions;
		$raw['id_actions']   = $id_actions;
		unset( $raw['tax_terms'], $raw['exclude_terms'], $raw['extra_ids'], $raw['exclude_ids'] );

		update_option( 'wookb_settings', $raw, false );

		return $raw;
	}

	public static function update_settings( array $data ) {
		$current = self::settings();
		update_option( 'wookb_settings', array_merge( $current, $data ), false );
	}

	/**
	 * Devuelve el listado de IDs de post (idioma por defecto / todos si WPML) dentro del alcance.
	 */
	/**
	 * Post types efectivos segun post_types_mode: la lista explicita guardada,
	 * o todos los CPT publicos (menos attachment y los marcados a excluir
	 * cuando el modo es 'all_public').
	 */
	public static function effective_post_types() {
		$settings = self::settings();

		if ( 'all_public' === $settings['post_types_mode'] ) {
			$all = array_keys( get_post_types( array( 'public' => true ), 'names' ) );
			$all = array_diff( $all, array( 'attachment' ), (array) $settings['post_types_excluded_when_all'] );
			return array_values( $all );
		}

		return (array) $settings['post_types'];
	}

	public static function resolve_ids() {
		$settings    = self::settings();
		$post_types  = self::effective_post_types();
		$ids         = array();

		foreach ( $post_types as $post_type ) {
			$args = array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			);

			$tax_query = array();
			foreach ( (array) $settings['term_actions'] as $taxonomy => $terms ) {
				$include_terms = array();
				foreach ( (array) $terms as $term_id => $action ) {
					if ( 'include' === $action ) {
						$include_terms[] = (int) $term_id;
					}
				}
				if ( $include_terms ) {
					$tax_query[] = array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => $include_terms,
					);
				}
			}
			if ( $tax_query ) {
				$args['tax_query'] = $tax_query; // phpcs:ignore
			}

			$found = get_posts( $args );
			$ids   = array_merge( $ids, $found );
		}

		foreach ( (array) $settings['id_actions'] as $post_id => $action ) {
			if ( 'include' === $action ) {
				$ids[] = (int) $post_id;
			}
		}

		$ids = array_unique( $ids );

		return array_values( array_filter( $ids, array( __CLASS__, 'is_included' ) ) );
	}

	/**
	 * Un ID individual está incluido si no está excluido por ID ni por término.
	 */
	public static function is_included( $post_id ) {
		$settings = self::settings();

		if ( isset( $settings['id_actions'][ (int) $post_id ] ) && 'exclude' === $settings['id_actions'][ (int) $post_id ] ) {
			return false;
		}

		foreach ( (array) $settings['term_actions'] as $taxonomy => $terms ) {
			$exclude_terms = array();
			foreach ( (array) $terms as $term_id => $action ) {
				if ( 'exclude' === $action ) {
					$exclude_terms[] = (int) $term_id;
				}
			}
			if ( $exclude_terms && has_term( $exclude_terms, $taxonomy, $post_id ) ) {
				return false;
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

	/**
	 * Taxonomias tecnicas internas (no contenido real) que no aportan nada
	 * como filtro de alcance y solo ensucian el selector: product_type es
	 * el tipo interno de WooCommerce (simple/grouped/external/variable, no
	 * una categoria), post_format es el formato de entrada de WordPress
	 * core (raramente usado y no es un filtro de contenido real).
	 */
	public static function noise_taxonomies() {
		return array( 'product_type', 'post_format' );
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

		// Fase 10, pieza 3: oculta el meta "espejo" de ACF (p.ej. "_nombre_del_campo"
		// junto a "nombre_del_campo") del listado que se muestra en el admin. Es
		// solo cosmetico: NO cambia el contrato de datos, sampled_custom_field_keys()
		// sigue devolviendo/guardando keys tecnicas crudas en custom_fields.
		$keys = array_values(
			array_filter(
				$keys,
				function ( $k ) use ( $keys ) {
					if ( 0 === strpos( $k, '_' ) && in_array( substr( $k, 1 ), $keys, true ) ) {
						return false;
					}
					return true;
				}
			)
		);

		return $keys;
	}

	/**
	 * Fase 10, pieza 3: etiqueta legible para una meta key tecnica, cuando se
	 * puede resolver via ACF, Meta Box o Pods. Si ninguno resuelve (o no estan
	 * activos), devuelve la key tal cual -- fallback seguro, sin romper nada
	 * en entornos donde estos plugins no existen.
	 */
	public static function custom_field_label( $post_type, $key ) {
		if ( function_exists( 'acf_get_field' ) ) {
			$field = acf_get_field( $key );
			if ( ! $field && function_exists( 'get_field_object' ) ) {
				// ACF suele indexar por nombre de campo, no por meta key directa:
				// intenta resolverlo contra un post de referencia del mismo CPT.
				$sample = get_posts(
					array(
						'post_type'      => $post_type,
						'posts_per_page' => 1,
						'fields'         => 'ids',
					)
				);
				if ( $sample ) {
					$field = get_field_object( $key, $sample[0] );
				}
			}
			if ( $field && ! empty( $field['label'] ) ) {
				return $field['label'];
			}
		}

		if ( function_exists( 'rwmb_get_field_settings' ) ) {
			$field = rwmb_get_field_settings( $key, array(), null );
			if ( $field && ! empty( $field['name'] ) ) {
				return $field['name'];
			}
		}

		if ( function_exists( 'pods_api' ) ) {
			$pods_api = pods_api();
			if ( $pods_api && method_exists( $pods_api, 'load_field' ) ) {
				$field = $pods_api->load_field( array( 'name' => $key ) );
				if ( $field && ! empty( $field['label'] ) ) {
					return $field['label'];
				}
			}
		}

		return $key;
	}
}
