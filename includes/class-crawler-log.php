<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tabla propia de accesos de crawlers de IA conocidos (Fase 11, pieza 3).
 * Requisito explicito del usuario: bajo impacto de rendimiento, sin llenar
 * la base de datos -- por eso el hook de 'init' NUNCA hace una consulta de
 * escritura si el User-Agent no coincide con el catalogo (caso mas
 * frecuente, trafico humano), y el tope de filas se aplica en el cron
 * semanal ya existente (Queue::HOOK_INTEGRITY), no en cada peticion.
 */
class Crawler_Log {

	const MAX_ROWS = 500;

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'wookb_crawler_log';
	}

	public static function create_table() {
		global $wpdb;
		$table           = self::table();
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			bot_name VARCHAR(64) NOT NULL,
			category VARCHAR(32) NOT NULL,
			url TEXT NOT NULL,
			created_at DATETIME NOT NULL,
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	public static function init() {
		// Prioridad tardia a proposito: se compara el User-Agent DESPUES de que
		// WordPress ya resolvio la peticion, para no interferir con el
		// enrutado ni con nada que dependa de 'init' en prioridades normales.
		add_action( 'init', array( __CLASS__, 'maybe_log_request' ), 999 );
	}

	public static function maybe_log_request() {
		// Nunca en admin ni en peticiones AJAX/REST/cron: solo trafico real de
		// front-end es relevante para "que bot paso por aqui".
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( '' === $ua ) {
			return;
		}

		// Punto critico de rendimiento: si no coincide con el catalogo (el
		// caso mas comun con diferencia), no se hace NADA mas -- ni una sola
		// consulta de escritura.
		$entry = Crawler_Catalog::find_by_user_agent( $ua );
		if ( ! $entry ) {
			return;
		}

		self::insert( $entry['user_agent'], $entry['category'] );
	}

	/**
	 * Inserta una fila del acceso. Guarda solo el nombre del bot del catalogo
	 * (nunca la cadena de user-agent cruda completa) y la URL visitada -- sin
	 * IP, por decision explicita (dato personal innecesario para el proposito
	 * de "que bot paso por aqui").
	 */
	protected static function insert( $bot_name, $category ) {
		global $wpdb;
		$table = self::table();

		// URL actual reconstruida a mano (mismo patron que otras piezas del
		// plugin que necesitan la URL de la peticion en curso): $_SERVER['REQUEST_URI']
		// es solo la ruta, home_url() la completa con el dominio real.
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/'; // phpcs:ignore
		$url         = home_url( $request_uri );

		$wpdb->insert( // phpcs:ignore
			$table,
			array(
				'bot_name'   => $bot_name,
				'category'   => $category,
				'url'        => $url,
				'created_at' => current_time( 'mysql' ),
			)
		);
	}

	/** Filas mas recientes, para mostrar en el panel. */
	public static function recent( $limit = 100 ) {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d", $limit ) ); // phpcs:ignore
	}

	public static function count_rows() {
		global $wpdb;
		$table = self::table();
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore
	}

	/**
	 * Aplica el tope duro de filas (roadmap: 500), borrando las mas antiguas.
	 * Llamado desde el cron semanal existente (Queue::run_integrity_check()),
	 * no desde el hook de 'init' -- prioriza rendimiento en cada peticion, tal
	 * como pide el requisito explicito del usuario.
	 */
	public static function trim_to_limit() {
		global $wpdb;
		$table   = self::table();
		$total   = self::count_rows();
		$excess  = $total - self::MAX_ROWS;
		if ( $excess <= 0 ) {
			return;
		}

		$wpdb->query( // phpcs:ignore
			$wpdb->prepare( "DELETE FROM {$table} ORDER BY created_at ASC LIMIT %d", $excess )
		);
	}
}
