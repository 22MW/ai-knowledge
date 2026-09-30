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
			'url'         => \AIKB\Languages::permalink( $post_id ),
			'lang'        => \AIKB\Languages::post_language( $post_id ),
			'taxonomies'  => $this->taxonomy_terms( $post_id, $post->post_type ),
			'custom_fields' => $this->custom_fields( $post_id, $post->post_type ),
			'price'       => null,
			'stock'       => null,
			'variants'    => array(),
		);

		return $data;
	}

	/**
	 * Mismo criterio que el filtro de alcance, taxonomia por taxonomia
	 * (pedido explicito del usuario, 2026-09-30, para que "nada marcado"
	 * signifique lo mismo en los dos sitios y no sea un lio): sin ningun
	 * termino marcado para una taxonomia, esta devuelve TODOS sus terminos
	 * (igual que Scope::is_included() no filtra nada); en cuanto se marca
	 * uno o mas terminos de esa taxonomia, pasa a devolver SOLO los
	 * marcados. Es coherente con que marcar convierte el ajuste en un
	 * filtro activo, tanto para que posts generan documento como para que
	 * terminos aparecen dentro de el.
	 */
	protected function taxonomy_terms( $post_id, $post_type ) {
		$out          = array();
		$taxonomies   = get_object_taxonomies( $post_type, 'names' );
		$term_actions = \AIKB\Scope::settings()['term_actions'];
		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $post_id, $taxonomy );
			if ( ! is_array( $terms ) ) {
				continue;
			}
			$selected = isset( $term_actions[ $taxonomy ] ) ? $term_actions[ $taxonomy ] : array();
			$has_selection = false;
			foreach ( $selected as $action ) {
				if ( 'include' === $action ) {
					$has_selection = true;
					break;
				}
			}
			$names = array();
			foreach ( $terms as $term ) {
				$is_selected = isset( $selected[ $term->term_id ] ) && 'include' === $selected[ $term->term_id ];
				if ( ! $has_selection || $is_selected ) {
					$names[] = $term->name;
				}
			}
			if ( $names ) {
				$out[ $taxonomy ] = $names;
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
