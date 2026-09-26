<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cola de generación. Usa Action Scheduler si está disponible (WooCommerce),
 * si no cae a WP-Cron con locking por transient (mismo contrato, menos garantías).
 */
class Queue {

	const GROUP  = 'woo-kb';
	const HOOK_GENERATE = 'wookb_generate_document';
	const HOOK_SEED      = 'wookb_seed_batch';
	const HOOK_INTEGRITY = 'wookb_integrity_check';
	/** Segundos entre lotes de una carga masiva (semillas). */
	const SEED_BATCH_INTERVAL = 15;

	public static function has_action_scheduler() {
		return function_exists( 'as_schedule_single_action' ) && function_exists( 'as_unschedule_action' );
	}

	public static function init() {
		add_action( self::HOOK_GENERATE, array( __CLASS__, 'run_generate' ), 10, 3 );
		add_action( self::HOOK_SEED, array( __CLASS__, 'run_seed_batch' ), 10, 2 );
		add_action( self::HOOK_INTEGRITY, array( __CLASS__, 'run_integrity_check' ) );

		if ( ! self::has_action_scheduler() ) {
			add_action( self::HOOK_GENERATE, array( __CLASS__, 'noop' ) ); // ya cubierto arriba, WP-Cron dispara igual el hook.
		}

		if ( self::has_action_scheduler() && ! as_next_scheduled_action( self::HOOK_INTEGRITY, array(), self::GROUP ) ) {
			as_schedule_recurring_action( time() + DAY_IN_SECONDS, WEEK_IN_SECONDS, self::HOOK_INTEGRITY, array(), self::GROUP );
		}
	}

	/**
	 * Encola (con debounce) la generación de un documento para source_id+lang.
	 * $force: si true, Document_Pipeline::process() regenera aunque el hash
	 * del origen no haya cambiado (usado por "Reiniciar todo" en Carga
	 * inicial, ver Admin::start_seed_force()).
	 * $delay: segundos de espera; null = debounce de los ajustes (ediciones
	 * sueltas), 0 = inmediato (semillas, ver run_seed_batch()).
	 */
	public static function enqueue( $source_id, $lang, $force = false, $delay = null ) {
		if ( null === $delay ) {
			$settings = Scope::settings();
			$delay    = max( 0, (int) $settings['debounce_seconds'] );
		} else {
			$delay = max( 0, (int) $delay );
		}

		$args = array( 'source_id' => (int) $source_id, 'lang' => $lang, 'force' => (bool) $force );

		if ( self::has_action_scheduler() ) {
			// Debounce: desprogramar cualquier acción previa idéntica pendiente
			// (mismo source_id+lang+force -- una petición force=true no
			// desprograma una force=false pendiente ni viceversa, caso raro
			// aceptado).
			as_unschedule_action( self::HOOK_GENERATE, $args, self::GROUP );
			as_schedule_single_action( time() + $delay, self::HOOK_GENERATE, $args, self::GROUP );
		} else {
			wp_schedule_single_event( time() + $delay, self::HOOK_GENERATE, array( $source_id, $lang, $force ) );
		}

		Registry::upsert(
			array(
				'source_id'   => $source_id,
				'source_type' => get_post_type( $source_id ) ? get_post_type( $source_id ) : 'unknown',
				'lang'        => $lang,
				'status'      => 'queued',
				'source_hash' => Registry::find( $source_id, $lang ) ? Registry::find( $source_id, $lang )->source_hash : '',
			)
		);
	}

	/**
	 * Ejecuta la generación real de un documento. Comprueba origen, hash y límite diario.
	 */
	public static function run_generate( $source_id, $lang, $force = false ) {
		$source_id = (int) $source_id;

		$post = get_post( $source_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			// El origen ya no existe o no está publicado: sincronía estricta -> borrar si había fila.
			Sync::delete_documents_for( $source_id );
			return;
		}

		if ( ! Scope::is_included( $source_id ) ) {
			Sync::delete_documents_for( $source_id );
			return;
		}

		if ( ! self::daily_quota_available() ) {
			// Reprogramar para las 00:05 del día siguiente.
			$tomorrow = strtotime( 'tomorrow 00:05', current_time( 'timestamp' ) ); // phpcs:ignore
			if ( self::has_action_scheduler() ) {
				as_schedule_single_action( $tomorrow, self::HOOK_GENERATE, array( 'source_id' => $source_id, 'lang' => $lang, 'force' => (bool) $force ), self::GROUP );
			} else {
				wp_schedule_single_event( $tomorrow, self::HOOK_GENERATE, array( $source_id, $lang, $force ) );
			}
			return;
		}

		$result = Document_Pipeline::process( $source_id, $lang, null, $force );

		if ( ! is_wp_error( $result ) ) {
			self::increment_daily_counter();
		}
	}

	public static function run_seed_batch( $offset = 0, $force = false ) {
		// Carga cancelada (o ya terminada): no encadenar más lotes, aunque quede
		// un evento programado que no se pudo retirar.
		if ( ! get_option( 'wookb_seed_running' ) ) {
			return;
		}
		$settings = Scope::settings();
		$ids      = Scope::resolve_ids();
		$batch    = array_slice( $ids, $offset, (int) $settings['batch_size'] );

		if ( empty( $batch ) ) {
			delete_option( 'wookb_seed_running' ); // fin de la carga inicial
			return;
		}

		$seen = array();
		foreach ( $batch as $source_id ) {
			// Documentos de este contenido segun el servicio de idiomas: uno (el
			// original) por defecto; con "Crear por idioma", uno por traduccion
			// real que exista. Nunca se encola un ID etiquetado con un idioma
			// que no le corresponde (bug real de 2026-08-22: filas huerfanas en
			// cola para siempre) ni un idioma sin traduccion (sin puentes).
			foreach ( Languages::targets( $source_id ) as $target ) {
				$key = $target['id'] . ':' . $target['lang'];
				if ( isset( $seen[ $key ] ) ) {
					continue;
				}
				$seen[ $key ] = true;
				self::enqueue( $target['id'], $target['lang'], $force, 0 );
			}
		}

		$next_offset = $offset + (int) $settings['batch_size'];
		if ( $next_offset >= count( $ids ) ) {
			// Ultimo lote: la carga masiva termina aqui (no se programa otro vacio).
			delete_option( 'wookb_seed_running' );
			return;
		}
		if ( self::has_action_scheduler() ) {
			as_schedule_single_action( time() + self::SEED_BATCH_INTERVAL, self::HOOK_SEED, array( 'offset' => $next_offset, 'force' => (bool) $force ), self::GROUP );
		} else {
			wp_schedule_single_event( time() + self::SEED_BATCH_INTERVAL, self::HOOK_SEED, array( $next_offset, $force ) );
		}
	}

	/**
	 * $force: false = "Generar pendientes" (comportamiento de siempre, no
	 * regenera lo que no cambió). true = "Reiniciar todo" (Admin::
	 * start_seed_force()), fuerza regenerar también lo ya sincronizado.
	 */
	public static function start_seed( $force = false ) {
		if ( $force ) {
			// "Reiniciar todo" es el paso de "Regenerar todo" que pide el aviso
			// de idiomas: borra antes los documentos que ya no corresponden al
			// modo actual (puentes y, sin "Crear por idioma", los por idioma).
			Languages::cleanup_obsolete_documents();
			Languages::clear_regen_pending();
		}
		update_option( 'wookb_seed_running', 1, false );
		self::run_seed_batch( 0, $force );
	}

	public static function cancel_seed() {
		delete_option( 'wookb_seed_running' );
		if ( self::has_action_scheduler() ) {
			as_unschedule_all_actions( self::HOOK_SEED, array(), self::GROUP );
		}
		// WP-Cron: solo el hook de semillas (wookb_generate_document también lo
		// usan los guardados normales y no se toca).
		$crons = _get_cron_array();
		if ( is_array( $crons ) ) {
			foreach ( $crons as $timestamp => $hooks ) {
				if ( ! isset( $hooks[ self::HOOK_SEED ] ) ) {
					continue;
				}
				foreach ( $hooks[ self::HOOK_SEED ] as $event ) {
					wp_unschedule_event( $timestamp, self::HOOK_SEED, isset( $event['args'] ) ? $event['args'] : array() );
				}
			}
		}
	}

	public static function daily_quota_available() {
		$settings = Scope::settings();
		if ( ! empty( $settings['no_limit'] ) ) {
			return true;
		}
		$counter = get_option( 'wookb_daily_counter', array( 'date' => '', 'count' => 0 ) );
		$today   = current_time( 'Y-m-d' );
		if ( $counter['date'] !== $today ) {
			return true;
		}
		return $counter['count'] < (int) $settings['daily_limit'];
	}

	public static function increment_daily_counter() {
		$today   = current_time( 'Y-m-d' );
		$counter = get_option( 'wookb_daily_counter', array( 'date' => $today, 'count' => 0 ) );
		if ( $counter['date'] !== $today ) {
			$counter = array( 'date' => $today, 'count' => 0 );
		}
		$counter['count']++;
		update_option( 'wookb_daily_counter', $counter, false );
	}

	public static function run_integrity_check() {
		$orphans = Registry::find_orphans();
		foreach ( $orphans as $row ) {
			Registry::update_status( $row->id, 'orphan' );
		}
		// Limpieza de huérfanos confirmados (origen inexistente): borra documentos asociados.
		foreach ( $orphans as $row ) {
			Sync::delete_documents_for( $row->source_id );
		}

		// Fase 11: tope duro de filas del log de crawlers aplicado aqui (cron
		// semanal ya existente), no en el hook de 'init' de cada peticion --
		// prioriza rendimiento en el hook ligero, tal como pide el requisito
		// explicito del usuario.
		if ( class_exists( '\AIKB\Crawler_Log' ) ) {
			Crawler_Log::trim_to_limit();
		}
	}

	public static function noop() {}
}
