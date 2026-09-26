<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Desinstalación. Comportamiento por defecto (sin marcar nada en Ajustes):
 * elimina la tabla wookb_documents, las opciones wookb_settings y
 * wookb_daily_counter y el transient wookb_llms_txt; los .md y el resto se
 * conservan.
 *
 * Con los checks de Ajustes (guardados en wookb_settings, que se lee ANTES de
 * borrar nada):
 * - uninstall_delete_data: borra las dos tablas, una lista cerrada de opciones,
 *   los transients wookb_*, las acciones pendientes de la cola, los eventos
 *   WP-Cron propios y los posts sgkb-docs que tengan fila en el Registro.
 * - uninstall_delete_files: borra wp-content/ai-knowledge/ (y la antigua llm/) y el llms.txt físico de la
 *   raíz solo si lo generó este plugin. Borra wp-content/ai-knowledge/ y la antigua llm/.
 * Nunca se tocan robots.txt, .htaccess ni la opción de Genix
 * chatbot_custom_instructions.
 *
 * Multisitio: se recorre cada sitio para los datos de base de datos; las
 * carpetas/archivos (compartidos por toda la instalación) solo se borran si
 * todos los sitios tienen marcado el check de archivos.
 */

/**
 * Datos de un sitio (el actual en el momento de la llamada).
 *
 * @return array{data: bool, files: bool} Qué checks estaban marcados.
 */
function aikb_uninstall_site() {
	global $wpdb;

	$settings = get_option( 'wookb_settings', array() );
	$settings = is_array( $settings ) ? $settings : array();
	$flags    = array(
		'data'  => ! empty( $settings['uninstall_delete_data'] ),
		'files' => ! empty( $settings['uninstall_delete_files'] ),
	);

	$table = $wpdb->prefix . 'wookb_documents';

	if ( $flags['data'] ) {
		// Posts sgkb-docs: solo los que tienen fila en el Registro (nunca todos:
		// los artículos exclusivos de Genix son del usuario).
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( $table_exists ) {
			$post_ids = $wpdb->get_col( "SELECT DISTINCT doc_post_id FROM {$table} WHERE doc_post_id IS NOT NULL AND doc_post_id > 0" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			foreach ( (array) $post_ids as $post_id ) {
				$post_id = absint( $post_id );
				if ( $post_id && 'sgkb-docs' === get_post_type( $post_id ) ) {
					wp_delete_post( $post_id, true );
				}
			}
		}

		// Acciones pendientes de la cola (Action Scheduler, grupo woo-kb) y eventos WP-Cron propios.
		$hooks = array( 'wookb_generate_document', 'wookb_seed_batch', 'wookb_integrity_check', 'wookb_indexnow_notify' );
		foreach ( $hooks as $hook ) {
			if ( function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( $hook, array(), 'woo-kb' );
			}
			wp_unschedule_hook( $hook );
		}

		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wookb_crawler_log" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Lista cerrada de opciones propias. La opción chatbot_custom_instructions
		// que se escribe en los ajustes de Genix NO se toca.
		$options = array(
			'wookb_settings',
			'wookb_daily_counter',
			'aikb_setup_assistant',
			'wookb_db_version',
			'wookb_seed_running',
			'wookb_lang_answer_migrated',
			'wookb_language_settings',
			'wookb_lang_regen_pending',
			'wookb_chatbot_prompt_answers',
			'wookb_business_summary',
			'wookb_chatbot_prompt_synced_hash',
			'wookb_genix_hooks_dismissed_at',
			'wookb_rewrite_flush_pending',
			'wookb_main_flat_migrated',
			'wookb_chatbot_prompt_text',
		);
		foreach ( $options as $option ) {
			delete_option( $option );
		}

		// Transients con prefijo wookb_ (y la caché de versiones de GitHub).
		foreach ( array( 'wookb_', 'aikb_' ) as $prefix ) {
			$like_value   = $wpdb->esc_like( '_transient_' . $prefix ) . '%';
			$like_timeout = $wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%';
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $like_value, $like_timeout ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		// Descarte del aviso del resumen (user meta propia).
		delete_metadata( 'user', 0, 'wookb_dismissed_summary_notice', '', true );
	}

	// Siempre (comportamiento histórico): tabla de documentos, ajustes y contador.
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	delete_option( 'wookb_settings' );
	delete_option( 'wookb_daily_counter' );
	delete_transient( 'wookb_llms_txt' );

	return $flags;
}

/** Borra recursivamente una carpeta de documentos directa de wp-content (ai-knowledge o la antigua llm), sin seguir enlaces simbólicos. */
function aikb_uninstall_remove_llm_dir( $name ) {
	$dir  = WP_CONTENT_DIR . '/' . $name;
	$real = realpath( $dir );
	if ( false === $real || is_link( $dir ) || $name !== basename( $real ) || dirname( $real ) !== realpath( WP_CONTENT_DIR ) ) {
		return;
	}
	$items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $real, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $items as $item ) {
		if ( $item->isLink() || $item->isFile() ) {
			unlink( $item->getPathname() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		} elseif ( $item->isDir() ) {
			rmdir( $item->getPathname() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rmdir_rmdir
		}
	}
	rmdir( $real ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rmdir_rmdir
}

/** Borra el llms.txt físico de la raíz SOLO si lo generó este plugin (marca o firma propia). */
function aikb_uninstall_remove_llms_txt() {
	$path = ABSPATH . 'llms.txt';
	if ( ! is_file( $path ) || is_link( $path ) ) {
		return;
	}
	$content = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	// Marca añadida por Llms_Txt::write_physical(); firma de versiones anteriores:
	// el enlace a la API propia que solo genera este plugin.
	$generated = false !== strpos( $content, 'Generated by AI Knowledge & Visibility' )
		|| false !== strpos( $content, 'ai-knowledge/v1/openapi.json' );
	if ( $generated ) {
		unlink( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
	}
}

$aikb_all_files = true;
$aikb_any_files = false;

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $aikb_blog_id ) {
		switch_to_blog( $aikb_blog_id );
		$aikb_flags     = aikb_uninstall_site();
		$aikb_any_files = $aikb_any_files || $aikb_flags['files'];
		$aikb_all_files = $aikb_all_files && $aikb_flags['files'];
		restore_current_blog();
	}
	$aikb_delete_files = $aikb_any_files && $aikb_all_files;
} else {
	$aikb_flags        = aikb_uninstall_site();
	$aikb_delete_files = $aikb_flags['files'];
}

if ( $aikb_delete_files ) {
	aikb_uninstall_remove_llm_dir( 'ai-knowledge' );
	aikb_uninstall_remove_llm_dir( 'llm' ); // carpeta antigua, si aún existe
	aikb_uninstall_remove_llms_txt();
}
