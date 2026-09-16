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

		return $data;
	}
}
