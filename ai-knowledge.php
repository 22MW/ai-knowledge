<?php
/**
 * Plugin Name: AI Knowledge & Visibility
 * Plugin URI: https://22mw.online/
 * Description: Genera documentos de base de conocimiento (.md + posts sgkb-docs de Support Genix) a partir de productos WooCommerce u otros CPTs, con cola, límite diario, WPML y publicación pública GEO vía llms.txt.
 * Version: 1.0.8.6
 * Author: 22MW
 * Author URI: https://22mw.online/
 * Text Domain: ai-knowledge
 * Requires PHP: 7.4
 *
 * Dependencias: ninguna dura. WooCommerce, Support Genix y WPML se detectan en runtime.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AIKB_VERSION', '1.0.8.6' );
define( 'AIKB_FILE', __FILE__ );
define( 'AIKB_DIR', plugin_dir_path( __FILE__ ) );
define( 'AIKB_URL', plugin_dir_url( __FILE__ ) );
define( 'AIKB_TABLE_DOCUMENTS', 'wookb_documents' );

/**
 * Autoload muy simple por convención de nombre de archivo (class-xxx.php).
 */
spl_autoload_register(
	function ( $class ) {
		if ( 0 !== strpos( $class, 'AIKB\\' ) ) {
			return;
		}
		$relative = substr( $class, strlen( 'AIKB\\' ) );
		$parts    = explode( '\\', $relative );
		$name     = array_pop( $parts );
		$subdir   = $parts ? strtolower( implode( '/', $parts ) ) . '/' : '';
		$filename = 'class-' . strtolower( str_replace( '_', '-', $name ) ) . '.php';

		$candidates = array(
			AIKB_DIR . 'includes/' . $subdir . $filename,
			AIKB_DIR . 'admin/' . $filename,
		);

		foreach ( $candidates as $path ) {
			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
);

/**
 * Activación: crea la tabla de registro y la carpeta wp-content/llm/.
 */
function wookb_activate() {
	require_once AIKB_DIR . 'includes/class-registry.php';
	\AIKB\Registry::create_table();

	$llm_dir = WP_CONTENT_DIR . '/llm';
	if ( ! file_exists( $llm_dir ) ) {
		wp_mkdir_p( $llm_dir );
	}
	if ( ! file_exists( $llm_dir . '/index.php' ) ) {
		file_put_contents( $llm_dir . '/index.php', "<?php\n// Silence is golden.\n" );
	}

	// Flush de reglas de rewrite para llms.txt y para la ruta virtual de los .md
	// (Markdown_Server: servidos vía PHP para fijar Content-Type: text/plain
	// sin depender de la configuración MIME del servidor -- ver esa clase).
	if ( class_exists( '\AIKB\Llms_Txt' ) ) {
		\AIKB\Llms_Txt::add_rewrite_rule();
	}
	if ( class_exists( '\AIKB\Markdown_Server' ) ) {
		\AIKB\Markdown_Server::add_rewrite_rule();
	}
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wookb_activate' );

function wookb_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wookb_deactivate' );

/**
 * Migraciones de esquema: dbDelta es idempotente, así que basta con
 * volver a ejecutar create_table() cuando cambia la versión del plugin
 * para que añada columnas nuevas sin perder datos existentes (p.ej.
 * product_trid/doc_trid añadidas en 1.0.1 para el fix WPML de documentos).
 */
add_action(
	'plugins_loaded',
	function () {
		if ( get_option( 'wookb_db_version' ) === AIKB_VERSION ) {
			return;
		}
		require_once AIKB_DIR . 'includes/class-registry.php';
		\AIKB\Registry::create_table();
		// Fase 11: tabla de logs de crawlers, creada por el mismo mecanismo de
		// migracion (dbDelta es idempotente) que la tabla de documentos, en vez
		// de un hook de activacion aparte.
		require_once AIKB_DIR . 'includes/class-crawler-log.php';
		\AIKB\Crawler_Log::create_table();
		update_option( 'wookb_db_version', AIKB_VERSION, false );
	},
	5
);

/**
 * Bootstrap principal, tras cargar todos los plugins (para detectar Woo/Genix/WPML).
 */
add_action(
	'plugins_loaded',
	function () {
		require_once AIKB_DIR . 'includes/class-plugin.php';
		\AIKB\Plugin::instance()->init();
	},
	20
);
