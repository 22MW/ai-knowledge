<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fase 4 del roadmap: anuncia el .md ya generado (el mismo que usa el
 * chatbot vía Document_Pipeline/Markdown_Store) al resto de buscadores y
 * agentes de IA, con un <link rel="alternate" type="text/markdown"> en el
 * <head> del contenido de origen (el post/producto real, NO el .md en sí).
 *
 * No genera nada nuevo ni duplica el pipeline: solo enlaza el .md que ya
 * existe si el contenido está sincronizado. Si no hay fila 'synced' para
 * ese post+idioma (no generado aún, en cola, o con error), no imprime nada
 * -- no hay nada real que enlazar.
 */
class Markdown_Discovery {

	public static function init() {
		// Prioridad 20: después de que WordPress y el resto de plugins hayan
		// impreso lo suyo en el <head> (meta tags, canonical, etc.), sin
		// competir por ser lo primero -- esto es solo un enlace adicional.
		add_action( 'wp_head', array( __CLASS__, 'print_link' ), 20 );
	}

	public static function print_link() {
		// Solo contenido singular (post/página/producto real): portada,
		// archivos y páginas de categoría no tienen un post de origen único
		// que enlazar a un .md concreto.
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_the_ID();
		if ( ! $post_id || ! Scope::is_included( $post_id ) ) {
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
}
