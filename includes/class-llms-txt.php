<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sirve /llms.txt por rewrite dinámico desde el registro (no archivo físico).
 * Si existe un llms.txt físico en la raíz, Apache lo sirve antes que el rewrite (se avisa en el panel).
 */
class Llms_Txt {

	public static function add_rewrite_rule() {
		add_rewrite_rule( '^llms\.txt$', 'index.php?wookb_llms_txt=1', 'top' );
	}

	public static function register_query_var( $vars ) {
		$vars[] = 'wookb_llms_txt';
		return $vars;
	}

	public static function maybe_serve( $wp ) {
		if ( empty( $wp->query_vars['wookb_llms_txt'] ) ) {
			return;
		}

		header( 'Content-Type: text/plain; charset=utf-8' );
		echo self::build(); // phpcs:ignore
		exit;
	}

	public static function build() {
		$cached = get_transient( 'wookb_llms_txt' );
		if ( false !== $cached ) {
			return $cached;
		}

		$rows = Registry::get_synced_public_urls();

		$lines   = array();
		$lines[] = '# ' . get_bloginfo( 'name' );
		$lines[] = '';
		$lines[] = '> ' . self::summary();
		$lines[] = '> ' . __( 'Última actualización:', 'ai-knowledge' ) . ' ' . wp_date( 'c' );
		$lines[] = '';

		// info.md: mismo resumen+contacto que ya lleva la cita de arriba,
		// pero como documento aparte y enlazado -- por si un lector solo
		// sigue enlaces de "## secciones" en vez de leer el bloque inicial.
		// Se genera junto al prompt del chatbot (Chatbot_Prompt_Builder::
		// write_info_doc(), pestaña Prompt); si aun no existe, se omite la
		// seccion sin romper nada.
		$info_path = WP_CONTENT_DIR . '/llm/info.md';
		if ( class_exists( '\AIKB\Chatbot_Prompt_Builder' ) && file_exists( $info_path ) ) {
			$info_body = trim( (string) file_get_contents( $info_path ) );
			$info_lines = explode( "\n", $info_body );
			array_shift( $info_lines );
			foreach ( $info_lines as $info_line ) {
				if ( '' !== trim( $info_line ) ) {
					$lines[] = $info_line;
				}
			}
			$lines[] = '';
		}

		// Fase 7: enlace a la documentacion OpenAPI, para que un crawler que
		// ya esta leyendo llms.txt (el punto de entrada real) descubra la API
		// tecnica sin depender de que la visite por su cuenta -- publicar
		// openapi.json solo no basta si nada enlaza a el (ver _dev/decisiones.md).
		$lines[] = '## API';
		$lines[] = '';
		$lines[] = '- [' . __( 'Documentación técnica de la API (OpenAPI)', 'ai-knowledge' ) . '](' . rest_url( 'ai-knowledge/v1/openapi.json' ) . '): ' . __( 'para desarrolladores e integraciones.', 'ai-knowledge' );
		$lines[] = '';

		// Fase 9: mismo criterio que la sección API de arriba -- publicar el
		// endpoint no basta si nada enlaza a él.
		$lines[] = '## Feeds';
		$lines[] = '';
		if ( class_exists( 'WooCommerce' ) ) {
			$lines[] = '- [' . __( 'Feed de productos (Google Merchant)', 'ai-knowledge' ) . '](' . rest_url( 'ai-knowledge/v1/feeds/products.xml' ) . '): ' . __( 'para comparadores y plataformas de shopping.', 'ai-knowledge' );
		}
		$lines[] = '- [' . __( 'Feed de contenido (JSON)', 'ai-knowledge' ) . '](' . rest_url( 'ai-knowledge/v1/feeds/content.json' ) . '): ' . __( 'el resto del contenido del alcance, sin paginar.', 'ai-knowledge' );
		$lines[] = '';

		// Categoria+idioma combinados (pedido explicito): "## Vinos (ES)",
		// "## Enoturismo (EN)", etc. -- mas cercano al formato de la spec de
		// llms.txt (secciones tematicas con descripcion corta por enlace) que
		// la lista plana anterior, solo agrupada por idioma sin contexto.
		// Sufijo "(ES)"/"(EN)" solo en sitios multiidioma de verdad: en un
		// sitio de un solo idioma no aporta nada y es ruido en cada
		// encabezado -- Wpml::active_languages() ya cae a array('es') sin
		// WPML activo, así que basta con contar cuántos hay.
		$show_lang_suffix = count( Wpml::active_languages() ) > 1;

		$groups = array();
		foreach ( $rows as $row ) {
			$category = self::category_label( $row );
			$groups[ $category ][ $row->lang ][] = $row;
		}
		ksort( $groups );

		foreach ( $groups as $category => $by_lang ) {
			ksort( $by_lang );
			foreach ( $by_lang as $lang => $items ) {
				$lines[] = '## ' . $category . ( $show_lang_suffix ? ' (' . strtoupper( $lang ) . ')' : '' );
				foreach ( $items as $row ) {
					$lines[] = '- ' . self::link_line( $row );
				}
				$lines[] = '';
			}
		}


		$output = implode( "\n", $lines );
		set_transient( 'wookb_llms_txt', $output, DAY_IN_SECONDS );

		return $output;
	}

	public static function invalidate() {
		delete_transient( 'wookb_llms_txt' );
		// El archivo físico es la publicación oficial de llms.txt.
		self::write_physical();
	}

	/**
	 * Categoria real del documento: para productos, el nombre de la primera
	 * taxonomia (product_cat -- Vino, Enoturismo...) del producto de origen;
	 * para el resto (paginas), "Páginas". Se usa la taxonomia real en vez
	 * del post_type crudo porque "product" agruparia vinos y experiencias de
	 * enoturismo juntos, que es justo la distincion que se pidio separar.
	 *
	 * SIEMPRE se devuelve el nombre del termino en el idioma por defecto de
	 * WPML (via wpml_object_id), nunca el del idioma de $row -- confirmado
	 * en real: agrupar "por idioma del row" dejaba categorias mezcladas
	 * (ej. "Weinprobe (ES)": el termino de esa fila ES no tenia traduccion
	 * WPML propia y WPML devolvia el nombre en aleman tal cual). La seccion
	 * ya indica el idioma aparte con "(ES)"/"(EN)"/"(DE)", asi que el nombre
	 * de categoria no necesita traducirse el mismo -- solo ser consistente.
	 */
	protected static function category_label( $row ) {
		// Documentos compuestos (Store_Info_Doc): no tienen taxonomía real que
		// consultar, así que se resuelven aparte antes de caer al camino de producto.
		if ( class_exists( '\AIKB\Store_Info_Doc' ) ) {
			$composite_label = Store_Info_Doc::category_label_for( $row->source_type );
			if ( $composite_label ) {
				return $composite_label;
			}
		}
		if ( class_exists( '\AIKB\Llms_Faq' ) ) {
			$faq_label = Llms_Faq::category_label_for( $row->source_type );
			if ( $faq_label ) {
				return $faq_label;
			}
		}

		// Artículos exclusivos de Genix (Genix_Reader/Genix_Publish): categoría
		// propia, no se agrupan con las páginas normales.
		if ( 'sgkb-docs' === $row->source_type ) {
			return __( 'Documentación', 'ai-knowledge' );
		}

		if ( 'product' !== $row->source_type || ! function_exists( 'wc_get_product' ) ) {
			return 'Páginas';
		}

		$terms = get_the_terms( $row->source_id, 'product_cat' );
		if ( ! $terms || is_wp_error( $terms ) ) {
			return 'Páginas';
		}

		$term = $terms[0];
		if ( class_exists( 'SitePress' ) ) {
			$canonical_id = apply_filters( 'wpml_object_id', $term->term_id, 'product_cat', false, 'es' );
			if ( $canonical_id ) {
				$canonical_term = get_term( $canonical_id, 'product_cat' );
				if ( $canonical_term && ! is_wp_error( $canonical_term ) ) {
					return $canonical_term->name;
				}
			}
		}

		return $term->name;
	}

	/**
	 * Línea "[Título](url): descripción corta" para una fila del registro,
	 * siguiendo el formato de la spec (enlace + descripción de una frase),
	 * en vez de solo la URL desnuda que había antes.
	 */
	protected static function link_line( $row ) {
		$url = Markdown_Store::public_url( $row->md_path );

		// Documentos compuestos (Store_Info_Doc): no hay post real detrás de
		// source_id, así que get_the_title()/get_the_excerpt() no sirven aquí.
		// Título y descripción salen de las etiquetas fijas de la propia clase.
		if ( class_exists( '\AIKB\Store_Info_Doc' ) ) {
			$composite_title = Store_Info_Doc::title_for( $row->source_type, $row->lang );
			if ( $composite_title ) {
				$line = '[' . $composite_title . '](' . $url . ')';
				$description = Store_Info_Doc::description_for( $row->source_type, $row->lang );
				return $description ? $line . ': ' . $description : $line;
			}
		}
		if ( class_exists( '\AIKB\Llms_Faq' ) && Llms_Faq::SOURCE_TYPE === $row->source_type ) {
			$line = '[' . Llms_Faq::title() . '](' . $url . ')';
			return $line . ': ' . Llms_Faq::description();
		}

		$title = wp_strip_all_tags( get_the_title( $row->source_id ) ); // quita <br> y similares sueltos en el titulo.
		$title = html_entity_decode( $title, ENT_QUOTES, 'UTF-8' );
		$title = trim( preg_replace( '/\s+/', ' ', $title ) );
		if ( '' === $title ) {
			return $url;
		}

		$excerpt = get_the_excerpt( $row->source_id );
		if ( '' === trim( wp_strip_all_tags( (string) $excerpt ) ) ) {
			$excerpt = get_post_field( 'post_content', $row->source_id );
		}
		$description = html_entity_decode( wp_trim_words( wp_strip_all_tags( (string) $excerpt ), 15 ), ENT_QUOTES, 'UTF-8' );
		$description = preg_replace( '/\s+/', ' ', trim( $description ) );

		$line = '[' . $title . '](' . $url . ')';
		if ( $description ) {
			$line .= ': ' . $description;
		}
		return $line;
	}

	/**
	 * Resumen corto del negocio para la cabecera de llms.txt, siguiendo el
	 * formato de la spec de llms.txt (H1 + cita de resumen). Reutiliza la
	 * respuesta "negocio" del mismo cuestionario que arma el prompt del
	 * chatbot (pestaña Prompt) -- una sola fuente para ambos usos en vez de
	 * escribir el resumen dos veces. Cae al tagline del sitio si esa
	 * respuesta aun no se ha rellenado.
	 *
	 * Si la respuesta es corta no se avisa aquí ni se marca en el propio
	 * llms.txt: el output público debe quedar limpio para los crawlers que lo
	 * leen, sin comentarios de mantenimiento interno mezclados con contenido
	 * real. El aviso vive en el admin (Chatbot_Prompt_Builder::
	 * maybe_short_summary_notice(), pestaña Prompt) para que solo lo vea
	 * quien puede corregirlo.
	 */
	protected static function summary() {
		if ( class_exists( '\AIKB\Chatbot_Prompt_Builder' ) ) {
			$summary = Chatbot_Prompt_Builder::get_business_summary();
			if ( '' !== trim( $summary ) ) {
				return $summary;
			}
		}
		$tagline = get_bloginfo( 'description' );
		return $tagline ? $tagline : get_bloginfo( 'name' );
	}

	/** Detecta si existe un llms.txt físico que taparía el rewrite. */
	public static function physical_file_exists() {
		return file_exists( ABSPATH . 'llms.txt' );
	}

	public static function physical_path() {
		return ABSPATH . 'llms.txt';
	}

	public static function write_physical() {
		$path = self::physical_path();
		if ( file_exists( $path ) && ! is_writable( $path ) ) {
			return false;
		}
		if ( ! file_exists( $path ) && ! is_writable( ABSPATH ) ) {
			return false;
		}
		return false !== file_put_contents( $path, self::build(), LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
	}
}
