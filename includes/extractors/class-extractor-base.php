<?php
namespace AIKB\Extractors;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extractor genérico: cualquier CPT. Título, contenido, taxonomías, URL, idioma, campos custom seleccionados.
 */
class Extractor_Base {

	/** @return array Datos normalizados para el prompt + hash. */
	public function extract( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return null;
		}

		$data = array(
			'id'          => $post_id,
			'post_type'   => $post->post_type,
			// get_the_title() pasa por wptexturize y devuelve entidades HTML
			// (p.ej. &#8211; en vez de "–"): se decodifican para que el
			// documento lleve el titulo real. Nota: compute_hash() incluye el
			// titulo, asi que los documentos con guiones/comillas en el
			// titulo cambian de hash y se regeneran la proxima vez.
			'title'       => html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, 'UTF-8' ),
			'content'     => wp_strip_all_tags( $post->post_content ),
			'excerpt'     => wp_strip_all_tags( $post->post_excerpt ),
			'url'         => get_permalink( $post_id ),
			'lang'        => \AIKB\Wpml::element_language( $post_id ),
			'taxonomies'  => $this->taxonomy_terms( $post_id, $post->post_type ),
			'custom_fields' => $this->custom_fields( $post_id, $post->post_type ),
			'price'       => null,
			'stock'       => null,
			'variants'    => array(),
		);

		return $data;
	}

	protected function taxonomy_terms( $post_id, $post_type ) {
		$out = array();
		$taxonomies = get_object_taxonomies( $post_type, 'names' );
		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $post_id, $taxonomy );
			if ( is_array( $terms ) ) {
				$out[ $taxonomy ] = wp_list_pluck( $terms, 'name' );
			}
		}
		return $out;
	}

	protected function custom_fields( $post_id, $post_type ) {
		$keys = \AIKB\Scope::custom_fields_for( $post_type );
		$out  = array();
		foreach ( (array) $keys as $key ) {
			$value = get_post_meta( $post_id, $key, true );
			if ( '' !== $value && null !== $value && ! is_array( $value ) ) {
				$out[ $key ] = $value;
			}
		}
		return $out;
	}

	/** Factory por post_type. */
	public static function for_post_type( $post_type ) {
		if ( 'product' === $post_type && class_exists( 'WooCommerce' ) ) {
			return new Extractor_Woo();
		}
		return new self();
	}
}
