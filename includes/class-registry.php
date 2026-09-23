<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Acceso a la tabla wp_wookb_documents. Una fila = un documento (producto x idioma).
 */
class Registry {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . AIKB_TABLE_DOCUMENTS;
	}

	public static function create_table() {
		global $wpdb;
		$table           = self::table();
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			source_id BIGINT UNSIGNED NOT NULL,
			source_type VARCHAR(32) NOT NULL,
			lang VARCHAR(8) NOT NULL,
			md_path VARCHAR(255) NOT NULL,
			doc_post_id BIGINT UNSIGNED NULL,
			source_hash CHAR(64) NOT NULL,
			status VARCHAR(16) NOT NULL DEFAULT 'queued',
			is_bridge TINYINT(1) NOT NULL DEFAULT 0,
			product_trid BIGINT UNSIGNED NULL,
			doc_trid BIGINT UNSIGNED NULL,
			last_error TEXT NULL,
			generated_at DATETIME NULL,
			updated_at DATETIME NOT NULL,
			override_mode VARCHAR(8) NOT NULL DEFAULT 'auto',
			override_text LONGTEXT NULL,
			char_limit INT UNSIGNED NULL,
			stale TINYINT(1) NOT NULL DEFAULT 0,
			is_public TINYINT(1) NOT NULL DEFAULT 0,
			custom_prompt LONGTEXT NULL,
			UNIQUE KEY source_lang (source_id, lang),
			KEY status (status),
			KEY source_type (source_type),
			KEY product_trid (product_trid)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/** Busca la fila por source_id + lang. */
	public static function find( $source_id, $lang ) {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE source_id = %d AND lang = %s", $source_id, $lang ) // phpcs:ignore
		);
	}

	public static function find_by_id( $id ) {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore
	}

	public static function find_all_by_source( $source_id ) {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE source_id = %d", $source_id ) ); // phpcs:ignore
	}

	/** Crea o actualiza una fila (upsert por source_id+lang). Devuelve el id. */
	public static function upsert( array $data ) {
		global $wpdb;
		$table = self::table();
		$data['updated_at'] = current_time( 'mysql' );

		$existing = self::find( $data['source_id'], $data['lang'] );
		if ( $existing ) {
			$wpdb->update( $table, $data, array( 'id' => $existing->id ) ); // phpcs:ignore
			return (int) $existing->id;
		}

		$defaults = array(
			'md_path'       => '',
			'source_hash'   => '',
			'status'        => 'queued',
			'is_bridge'     => 0,
			'doc_post_id'   => null,
			'override_mode' => 'auto',
			'override_text' => null,
			'char_limit'    => null,
			'stale'         => 0,
			'is_public'     => 0,
			'custom_prompt' => null,
		);
		$data = wp_parse_args( $data, $defaults );
		$wpdb->insert( $table, $data ); // phpcs:ignore
		return (int) $wpdb->insert_id;
	}

	public static function update_status( $id, $status, $extra = array() ) {
		global $wpdb;
		$table         = self::table();
		$extra['status'] = $status;
		$extra['updated_at'] = current_time( 'mysql' );
		$wpdb->update( $table, $extra, array( 'id' => $id ) ); // phpcs:ignore
	}

	public static function delete_row( $id ) {
		global $wpdb;
		$table = self::table();
		$wpdb->delete( $table, array( 'id' => $id ) ); // phpcs:ignore
	}

	public static function delete_by_source( $source_id ) {
		$rows = self::find_all_by_source( $source_id );
		foreach ( $rows as $row ) {
			self::delete_row( $row->id );
		}
		return $rows;
	}

	/** Filas listas para llms.txt: sincronizadas y marcadas como públicas. */
	public static function get_synced_public_urls() {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_results( "SELECT * FROM {$table} WHERE status = 'synced' AND is_bridge = 0 AND is_public = 1 ORDER BY lang, source_type" ); // phpcs:ignore
	}

	/** Para el WP_List_Table del panel, con filtros básicos. */
	/**
	 * Busqueda por titulo: JOIN con wp_posts por source_id. Los documentos
	 * compuestos (Store_Info_Doc, source_id centinela sin post real) no
	 * tienen fila en wp_posts, asi que una busqueda por texto nunca los
	 * encuentra por este camino -- limitacion aceptada, son solo 6 filas
	 * fijas y facil de localizar sin buscador (siempre arriba por fecha).
	 */
	protected static function apply_search( $where, $vals, $search ) {
		if ( '' === trim( (string) $search ) ) {
			return array( $where, $vals );
		}
		global $wpdb;
		$where[] = "source_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE %s )";
		$vals[]  = '%' . $wpdb->esc_like( $search ) . '%';
		return array( $where, $vals );
	}

	public static function query( $args = array() ) {
		global $wpdb;
		$table = self::table();
		$where = array( '1=1' );
		$vals  = array();

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$vals[]  = $args['status'];
		}
		if ( ! empty( $args['lang'] ) ) {
			$where[] = 'lang = %s';
			$vals[]  = $args['lang'];
		}
		if ( ! empty( $args['source_type'] ) ) {
			$where[] = 'source_type = %s';
			$vals[]  = $args['source_type'];
		}
		if ( isset( $args['stale'] ) && '' !== $args['stale'] ) {
			$where[] = 'stale = %d';
			$vals[]  = (int) $args['stale'];
		}
		if ( ! empty( $args['search'] ) ) {
			list( $where, $vals ) = self::apply_search( $where, $vals, $args['search'] );
		}

		$limit  = isset( $args['limit'] ) ? (int) $args['limit'] : 20;
		$offset = isset( $args['offset'] ) ? (int) $args['offset'] : 0;

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY updated_at DESC LIMIT %d OFFSET %d';
		$vals[] = $limit;
		$vals[] = $offset;

		if ( $vals ) {
			$sql = $wpdb->prepare( $sql, $vals ); // phpcs:ignore
		}
		return $wpdb->get_results( $sql ); // phpcs:ignore
	}

	/**
	 * IDs de fila que cumplen los mismos filtros que query(), sin paginar --
	 * usado por "Borrar todos"/"Reiniciar cola" para operar sobre TODO lo
	 * filtrado, no solo la pagina de 20 visible.
	 */
	public static function query_all_ids( $args = array() ) {
		global $wpdb;
		$table = self::table();
		$where = array( '1=1' );
		$vals  = array();

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$vals[]  = $args['status'];
		}
		if ( ! empty( $args['lang'] ) ) {
			$where[] = 'lang = %s';
			$vals[]  = $args['lang'];
		}
		if ( ! empty( $args['search'] ) ) {
			list( $where, $vals ) = self::apply_search( $where, $vals, $args['search'] );
		}

		$sql = "SELECT id FROM {$table} WHERE " . implode( ' AND ', $where );
		if ( $vals ) {
			$sql = $wpdb->prepare( $sql, $vals ); // phpcs:ignore
		}
		return array_map( 'intval', $wpdb->get_col( $sql ) ); // phpcs:ignore
	}

	public static function count( $args = array() ) {
		global $wpdb;
		$table = self::table();
		$where = array( '1=1' );
		$vals  = array();

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$vals[]  = $args['status'];
		}
		if ( ! empty( $args['lang'] ) ) {
			$where[] = 'lang = %s';
			$vals[]  = $args['lang'];
		}
		if ( isset( $args['stale'] ) && '' !== $args['stale'] ) {
			$where[] = 'stale = %d';
			$vals[]  = (int) $args['stale'];
		}
		if ( ! empty( $args['search'] ) ) {
			list( $where, $vals ) = self::apply_search( $where, $vals, $args['search'] );
		}

		$sql = "SELECT COUNT(*) FROM {$table} WHERE " . implode( ' AND ', $where );
		if ( $vals ) {
			$sql = $wpdb->prepare( $sql, $vals ); // phpcs:ignore
		}
		return (int) $wpdb->get_var( $sql ); // phpcs:ignore
	}

	/**
	 * Resumen para la cabecera de la pestaña Registro: total, desglose por
	 * idioma y desglose por estado. Sin filtros (siempre sobre TODA la tabla,
	 * no lo que este filtrado en pantalla) para que sirva de referencia fija.
	 */
	public static function summary() {
		global $wpdb;
		$table = self::table();
		return array(
			'total'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ), // phpcs:ignore
			'por_idioma' => $wpdb->get_results( "SELECT lang, COUNT(*) c FROM {$table} GROUP BY lang ORDER BY lang" ), // phpcs:ignore
			'por_estado' => $wpdb->get_results( "SELECT status, COUNT(*) c FROM {$table} GROUP BY status ORDER BY status" ), // phpcs:ignore
		);
	}

	/**
	 * Devuelve el doc_trid ya asignado al grupo de documentos sgkb-docs de un producto
	 * (identificado por su product_trid, el trid del propio producto WPML), si existe.
	 * Null si aun no se ha generado ningun documento para ese producto en ningun idioma.
	 */
	public static function find_doc_trid_by_product_trid( $product_trid ) {
		if ( ! $product_trid ) {
			return null;
		}
		global $wpdb;
		$table = self::table();
		$value = $wpdb->get_var(
			$wpdb->prepare( "SELECT doc_trid FROM {$table} WHERE product_trid = %d AND doc_trid IS NOT NULL LIMIT 1", $product_trid ) // phpcs:ignore
		);
		return $value ? (int) $value : null;
	}

	/**
	 * Filas huérfanas: origen ya no existe. Solo aplica a filas cuyo
	 * source_type es un post_type real (product, page, etc.): los documentos
	 * "compuestos" sin post de origen (ver Store_Info_Doc, source_type
	 * 'wookb-store-info'/'wookb-shop-catalog') usan un source_id centinela
	 * que nunca resuelve a un post real -- si no se excluyeran aquí,
	 * Queue::run_integrity_check() los borraría cada semana por error.
	 * Generalizado por post_type_exists() en vez de una lista de exclusión
	 * a mano, para que cubra automáticamente cualquier documento compuesto
	 * futuro con el mismo patrón.
	 */
	public static function find_orphans() {
		global $wpdb;
		$table = self::table();
		$rows  = $wpdb->get_results( "SELECT * FROM {$table}" ); // phpcs:ignore
		$orphans = array();
		foreach ( $rows as $row ) {
			if ( ! post_type_exists( $row->source_type ) ) {
				continue;
			}
			if ( ! get_post( $row->source_id ) ) {
				$orphans[] = $row;
			}
		}
		return $orphans;
	}
}
