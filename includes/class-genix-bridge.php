<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MD -> HTML (Apbd_Wps_Parsedown de Support Genix) -> crea/actualiza post sgkb-docs.
 * Si Genix no está activo, no hace nada (el pipeline sigue generando .md igualmente).
 */
class Genix_Bridge {

	const CPT = 'sgkb-docs';

	public static function is_available() {
		return post_type_exists( self::CPT ) && self::parsedown_class();
	}

	protected static function parsedown_class() {
		if ( class_exists( '\Apbd_Wps_Parsedown' ) ) {
			return '\Apbd_Wps_Parsedown';
		}
		return null;
	}

	public static function markdown_to_html( $markdown ) {
		$class = self::parsedown_class();
		if ( ! $class ) {
			return wpautop( esc_html( $markdown ) );
		}
		$parser = new $class();
		return $parser->text( $markdown );
	}

	/**
	 * Crea o actualiza el post sgkb-docs. Devuelve el post_id o WP_Error.
	 */
	public static function upsert_document( $existing_post_id, array $data, $markdown, $lang, $trid = null ) {
		if ( ! self::is_available() ) {
			return new \WP_Error( 'wookb_genix_unavailable', __( 'Support Genix no está activo o no expone sgkb-docs.', 'ai-knowledge' ) );
		}

		$html = self::markdown_to_html( $markdown );

		$postarr = array(
			'post_type'    => self::CPT,
			'post_title'   => $data['title'],
			'post_content' => $html,
			'post_status'  => 'publish',
		);

		if ( $existing_post_id && get_post( $existing_post_id ) ) {
			$postarr['ID'] = $existing_post_id;
			$post_id       = wp_update_post( $postarr, true );
		} else {
			$post_id = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, 'only_for_chatbot', '1' );
		update_post_meta( $post_id, 'wookb_source_id', $data['id'] );
		update_post_meta( $post_id, 'wookb_source_url', $data['url'] );

		Wpml::set_language( $post_id, $lang, $trid );

		return $post_id;
	}

	public static function delete_document( $post_id ) {
		if ( $post_id && get_post( $post_id ) ) {
			wp_delete_post( $post_id, true );
		}
	}
}
