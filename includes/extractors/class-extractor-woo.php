<?php
namespace AIKB\Extractors;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extractor WooCommerce: añade precio, stock y variantes al extractor genérico.
 */
class Extractor_Woo extends Extractor_Base {

	public function extract( $post_id ) {
		$data = parent::extract( $post_id );
		if ( ! $data ) {
			return null;
		}

		$product = wc_get_product( $post_id );
		if ( ! $product ) {
			return $data;
		}

		$data['price']       = $product->get_price_html() ? wp_strip_all_tags( $product->get_price_html() ) : $product->get_price();
		$data['regular_price'] = $product->get_regular_price();
		$data['sale_price']  = $product->get_sale_price();
		$data['stock']       = $product->is_in_stock() ? 'in_stock' : 'out_of_stock';
		$data['sku']         = $product->get_sku();
		$data['short_description'] = wp_strip_all_tags( $product->get_short_description() );

		$data['variants'] = array();
		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_available_variations() as $variation ) {
				$attrs = array();
				foreach ( $variation['attributes'] as $k => $v ) {
					$attrs[] = str_replace( 'attribute_', '', $k ) . ': ' . $v;
				}
				$data['variants'][] = array(
					'attributes' => implode( ', ', $attrs ),
					'price'      => $variation['display_price'],
					'in_stock'   => ! empty( $variation['is_in_stock'] ),
				);
			}
		}

		// Datos "factuales" de compra (precio, envio, impuestos, TODAS las
		// variaciones). Los añade el codigo, no la IA (ver
		// Generator::build_purchase_data_block()). Clave nueva aparte: NO
		// entra en Document_Pipeline::compute_hash() ni toca ninguna de las
		// claves existentes (price, regular_price, sale_price, stock, sku,
		// variants...), que usan el hash, products.xml y la REST.
		$data['purchase'] = $this->purchase_data( $product );

		return $data;
	}

	/** Precio como texto plano ("12,50 €"): wc_price() sin HTML, entidades decodificadas. */
	protected function price_text( $amount ) {
		if ( '' === $amount || null === $amount ) {
			return '';
		}
		$text = html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
		return trim( str_replace( "\xC2\xA0", ' ', $text ) );
	}

	/** Precio tal como lo ve el cliente (respeta "mostrar precios con/sin IVA" de la tienda). */
	protected function display_amount( $product, $raw_price ) {
		if ( '' === $raw_price || null === $raw_price ) {
			return '';
		}
		return wc_get_price_to_display( $product, array( 'price' => $raw_price ) );
	}

	/** Porcentaje de descuento redondeado, o null si no aplica. */
	protected function discount_percent( $regular, $current ) {
		$regular = (float) $regular;
		$current = (float) $current;
		if ( $regular <= 0 || $current < 0 || $current >= $regular ) {
			return null;
		}
		return (int) round( ( $regular - $current ) / $regular * 100 );
	}

	protected function purchase_data( $product ) {
		$is_variable = $product->is_type( 'variable' );
		$purchase    = array();

		// Precio actual / anterior / descuento / oferta hasta.
		if ( $is_variable ) {
			// Rango ("Desde X hasta Y"): el detalle esta en cada variacion.
			$range = html_entity_decode( wp_strip_all_tags( $product->get_price_html() ), ENT_QUOTES, 'UTF-8' );
			$purchase['price']            = trim( str_replace( "\xC2\xA0", ' ', $range ) );
			$purchase['regular_price']    = '';
			$purchase['on_sale']          = false;
			$purchase['discount_percent'] = null;
			$purchase['sale_until']       = '';
		} else {
			$current_raw = $product->get_price();
			$purchase['price'] = $this->price_text( $this->display_amount( $product, $current_raw ) );
			$on_sale = $product->is_on_sale();
			$purchase['on_sale'] = (bool) $on_sale;
			$purchase['regular_price']    = '';
			$purchase['discount_percent'] = null;
			$purchase['sale_until']       = '';
			if ( $on_sale ) {
				$regular_display = $this->display_amount( $product, $product->get_regular_price() );
				$current_display = $this->display_amount( $product, $current_raw );
				$purchase['regular_price']    = $this->price_text( $regular_display );
				$purchase['discount_percent'] = $this->discount_percent( $regular_display, $current_display );
				$until = $product->get_date_on_sale_to();
				if ( $until ) {
					$purchase['sale_until'] = wc_format_datetime( $until );
				}
			}
		}

		// Disponibilidad.
		$purchase['stock_status']   = $product->get_stock_status();
		$purchase['stock_quantity'] = ( $product->managing_stock() && null !== $product->get_stock_quantity() ) ? (int) $product->get_stock_quantity() : null;

		// Envio.
		$class_slug  = $product->get_shipping_class();
		$class_name  = '';
		if ( '' !== $class_slug ) {
			$term = get_term_by( 'slug', $class_slug, 'product_shipping_class' );
			$class_name = ( $term && ! is_wp_error( $term ) ) ? $term->name : $class_slug;
		}
		$weight     = $product->get_weight();
		$dimensions = $product->get_dimensions( false );
		$dim_text   = ( array_filter( $dimensions ) ) ? html_entity_decode( wc_format_dimensions( $dimensions ), ENT_QUOTES, 'UTF-8' ) : '';
		$purchase['shipping'] = array(
			'needs_shipping' => (bool) $product->needs_shipping(),
			'virtual'        => (bool) $product->is_virtual(),
			'downloadable'   => (bool) $product->is_downloadable(),
			'class'          => $class_name,
			'weight'         => ( '' !== (string) $weight ) ? html_entity_decode( wc_format_weight( $weight ), ENT_QUOTES, 'UTF-8' ) : '',
			'dimensions'     => $dim_text,
		);

		// Impuestos.
		$tax = array( 'enabled' => (bool) wc_tax_enabled() );
		if ( $tax['enabled'] ) {
			$class_slug_tax = $product->get_tax_class();
			$tax_class_name = 'Estándar';
			if ( '' !== $class_slug_tax ) {
				$tax_class_name = $class_slug_tax;
				foreach ( \WC_Tax::get_tax_rate_classes() as $class_obj ) {
					if ( $class_obj->slug === $class_slug_tax ) {
						$tax_class_name = $class_obj->name;
						break;
					}
				}
			}
			$rates_text = array();
			// get_rates_for_tax_class(), NO get_rates(): esta ultima depende de
			// la ubicacion del cliente (ver Store_Info_Doc::tax_summary_lines()).
			$rates = \WC_Tax::get_rates_for_tax_class( $class_slug_tax );
			if ( is_array( $rates ) ) {
				foreach ( $rates as $rate ) {
					$percent = rtrim( rtrim( number_format( (float) $rate->tax_rate, 4, '.', '' ), '0' ), '.' );
					$country = ! empty( $rate->tax_rate_country ) ? $rate->tax_rate_country : '';
					$rates_text[] = $percent . '%' . ( '' !== $country ? ' (' . $country . ')' : '' );
				}
			}
			$tax['status']         = $product->get_tax_status(); // taxable | shipping | none.
			$tax['class']          = $tax_class_name;
			$tax['rates']          = $rates_text;
			$tax['prices_include'] = (bool) wc_prices_include_tax();
			$tax['display_shop']   = get_option( 'woocommerce_tax_display_shop' ); // incl | excl.
		}
		$purchase['tax'] = $tax;

		// Variaciones: TODAS las publicadas (incluidas agotadas y sin precio).
		// get_children() SIN argumentos devuelve 'all' (publish + private, sin
		// filtrar por stock) -- confirmado en WC_Product_Variable_Data_Store_CPT::
		// read_children(); get_available_variations() se descarta porque omite
		// variaciones sin precio/no compra-ables. Se descartan las 'private'
		// (variaciones desactivadas en el admin) comprobando post_status.
		$purchase['variations']      = array();
		$purchase['variations_more'] = 0;
		if ( $is_variable ) {
			$max = 100;
			foreach ( $product->get_children() as $child_id ) {
				if ( 'publish' !== get_post_status( $child_id ) ) {
					continue;
				}
				if ( count( $purchase['variations'] ) >= $max ) {
					$purchase['variations_more']++;
					continue;
				}
				$variation = wc_get_product( $child_id );
				if ( ! $variation ) {
					continue;
				}
				$purchase['variations'][] = $this->variation_data( $variation );
			}
		}

		return $purchase;
	}

	protected function variation_data( $variation ) {
		$attrs = array();
		foreach ( $variation->get_variation_attributes( false ) as $name => $value ) {
			$label = wc_attribute_label( $name, $variation );
			if ( '' === $value ) {
				$shown = 'cualquiera';
			} elseif ( taxonomy_exists( $name ) ) {
				$term  = get_term_by( 'slug', $value, $name );
				$shown = ( $term && ! is_wp_error( $term ) ) ? $term->name : $value;
			} else {
				$shown = $value;
			}
			$attrs[] = $label . ': ' . $shown;
		}

		$current_raw     = $variation->get_price();
		$current_display = $this->display_amount( $variation, $current_raw );
		$regular         = '';
		$discount        = null;
		if ( $variation->is_on_sale() ) {
			$regular_display = $this->display_amount( $variation, $variation->get_regular_price() );
			$regular         = $this->price_text( $regular_display );
			$discount        = $this->discount_percent( $regular_display, $current_display );
		}

		return array(
			'attributes'       => html_entity_decode( implode( ', ', $attrs ), ENT_QUOTES, 'UTF-8' ),
			'price'            => $this->price_text( $current_display ),
			'regular_price'    => $regular,
			'discount_percent' => $discount,
			'stock_status'     => $variation->get_stock_status(),
			'stock_quantity'   => ( $variation->managing_stock() && null !== $variation->get_stock_quantity() ) ? (int) $variation->get_stock_quantity() : null,
			'sku'              => $variation->get_sku(),
		);
	}
}
