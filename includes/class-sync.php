<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hooks de alta/edición/stock/borrado -> encolado o borrado sincronizado.
 * Protección de rebote: descarta autosave/revision/no-publish antes de encolar.
 */
class Sync {

	public static function init() {
		add_action( 'woocommerce_update_product', array( __CLASS__, 'on_product_saved' ) );
		add_action( 'woocommerce_new_product', array( __CLASS__, 'on_product_saved' ) );
		add_action( 'woocommerce_product_set_stock_status', array( __CLASS__, 'on_product_saved' ) );

		add_action( 'wp_trash_post', array( __CLASS__, 'on_trash_or_delete' ) );
		add_action( 'before_delete_post', array( __CLASS__, 'on_trash_or_delete' ) );

		// CPTs no-Woo del alcance: save_post_{tipo}
		$settings = Scope::settings();
		foreach ( (array) $settings['post_types'] as $post_type ) {
			if ( 'product' === $post_type ) {
				continue;
			}
			add_action( 'save_post_' . $post_type, array( __CLASS__, 'on_generic_post_saved' ), 10, 3 );
		}
	}

	protected static function should_skip( $post_id ) {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return true;
		}
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return true;
		}
		return false;
	}

	public static function on_product_saved( $product_id ) {
		if ( self::should_skip( $product_id ) ) {
			return;
		}
		if ( ! Scope::is_included( $product_id ) ) {
			return;
		}
		self::enqueue_all_languages( $product_id );
	}

	public static function on_generic_post_saved( $post_id, $post, $update ) {
		if ( self::should_skip( $post_id ) ) {
			return;
		}
		if ( ! Scope::is_included( $post_id ) ) {
			return;
		}
		self::enqueue_all_languages( $post_id );
	}

	protected static function enqueue_all_languages( $post_id ) {
		$lang = Wpml::element_language( $post_id );
		Queue::enqueue( $post_id, $lang );

		// Encola también las traducciones existentes del mismo trid (guardado dispara varias veces con WPML,
		// pero cada idioma tiene su propio source_id real).
		foreach ( Wpml::active_languages() as $target_lang ) {
			if ( $target_lang === $lang ) {
				continue;
			}
			$translated_id = Wpml::get_translation_id( $post_id, $target_lang );
			if ( $translated_id && $translated_id !== $post_id ) {
				Queue::enqueue( $translated_id, $target_lang );
			}
		}
	}

	public static function on_trash_or_delete( $post_id ) {
		$post_type = get_post_type( $post_id );
		$settings  = Scope::settings();
		if ( ! in_array( $post_type, (array) $settings['post_types'], true ) ) {
			return;
		}
		self::delete_documents_for( $post_id );
	}

	/**
	 * Borrado sincronizado: localiza filas, borra .md, borra post sgkb-docs, borra filas, invalida llms.txt.
	 */
	public static function delete_documents_for( $source_id ) {
		$rows = Registry::find_all_by_source( $source_id );
		foreach ( $rows as $row ) {
			Markdown_Store::delete( $row->md_path );
			if ( $row->doc_post_id ) {
				Genix_Bridge::delete_document( $row->doc_post_id );
			}
			Registry::delete_row( $row->id );
		}
		if ( $rows ) {
			Llms_Txt::invalidate();
		}
	}
}
