<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aviso a buscadores compatibles con IndexNow (Bing y otros; Google no lo
 * soporta) cuando cambia contenido del alcance. Clave del sitio servida por
 * rewrite dinámico, mismo patrón que Llms_Txt/Markdown_Server. Debounce
 * reutilizando el patrón de Queue (Action Scheduler o WP-Cron con el mismo
 * contrato).
 */
class Indexnow {

	const HOOK_NOTIFY = 'wookb_indexnow_notify';
	const ENDPOINT     = 'https://api.indexnow.org/indexnow';

	public static function init() {
		add_action( self::HOOK_NOTIFY, array( __CLASS__, 'run_notify' ) );
		add_filter( 'query_vars', array( __CLASS__, 'register_query_var' ) );
		add_action( 'parse_request', array( __CLASS__, 'maybe_serve' ) );
	}

	public static function is_enabled() {
		return ! empty( Scope::settings()['indexnow_enabled'] );
	}

	/** Clave del sitio: se genera una vez y se persiste, nunca editable a mano (evita claves inválidas). */
	public static function get_key() {
		$settings = Scope::settings();
		if ( ! empty( $settings['indexnow_key'] ) ) {
			return $settings['indexnow_key'];
		}
		$key = wp_generate_password( 32, false, false );
		Scope::update_settings( array( 'indexnow_key' => $key ) );
		return $key;
	}

	public static function add_rewrite_rule() {
		$key = self::get_key();
		add_rewrite_rule( '^' . $key . '\.txt$', 'index.php?wookb_indexnow_key=1', 'top' );
	}

	public static function register_query_var( $vars ) {
		$vars[] = 'wookb_indexnow_key';
		return $vars;
	}

	public static function maybe_serve( $wp ) {
		if ( empty( $wp->query_vars['wookb_indexnow_key'] ) ) {
			return;
		}
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo self::get_key(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- clave alfanumérica generada por wp_generate_password(), sin entrada de usuario.
		exit;
	}

	/** Encola el aviso con el mismo debounce que ya usa Queue para la generación de documentos. */
	public static function enqueue_notify( $url ) {
		if ( ! self::is_enabled() || ! $url ) {
			return;
		}
		$settings = Scope::settings();
		$delay    = max( 0, (int) $settings['debounce_seconds'] );
		$args     = array( 'url' => $url );

		if ( Queue::has_action_scheduler() ) {
			as_unschedule_action( self::HOOK_NOTIFY, $args, Queue::GROUP );
			as_schedule_single_action( time() + $delay, self::HOOK_NOTIFY, $args, Queue::GROUP );
		} else {
			wp_schedule_single_event( time() + $delay, self::HOOK_NOTIFY, array( $url ) );
		}
	}

	/** Envío real, no bloqueante: si IndexNow falla, no debe afectar al guardado del post. */
	public static function run_notify( $url ) {
		if ( ! self::is_enabled() ) {
			return;
		}
		$key = self::get_key();

		wp_remote_post(
			self::ENDPOINT,
			array(
				'timeout'  => 3,
				'blocking' => false,
				'headers'  => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'     => wp_json_encode(
					array(
						'host'        => wp_parse_url( home_url(), PHP_URL_HOST ),
						'key'         => $key,
						'keyLocation' => home_url( '/' . $key . '.txt' ),
						'urlList'     => array( $url ),
					)
				),
			)
		);
	}
}
