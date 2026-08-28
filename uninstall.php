<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Los .md en wp-content/llm/ se conservan por defecto (contenido generado, no del plugin).
// Solo se limpian tabla y opciones. Opt-in a borrado de .md no implementado en esta fase.

$table = $wpdb->prefix . 'wookb_documents';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore

delete_option( 'wookb_settings' );
delete_option( 'wookb_daily_counter' );
delete_transient( 'wookb_llms_txt' );
