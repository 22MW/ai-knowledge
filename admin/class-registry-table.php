<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Registry_Table extends \WP_List_Table {

	public static function status_label( $status ) {
		$labels = array(
			'queued'     => __( 'En cola', 'ai-knowledge' ),
			'generating' => __( 'Generando', 'ai-knowledge' ),
			'synced'     => __( 'Listo', 'ai-knowledge' ),
			'error'      => __( 'Error', 'ai-knowledge' ),
			'orphan'     => __( 'Sin origen', 'ai-knowledge' ),
		);
		return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
	}

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'documento',
				'plural'   => 'documentos',
				'ajax'     => false,
			)
		);
	}

	public function get_columns() {
		return array(
			'cb'     => '<input type="checkbox" />',
			'source' => __( 'Origen', 'ai-knowledge' ),
			'lang'   => __( 'Idioma', 'ai-knowledge' ),
			'status' => __( 'Estado', 'ai-knowledge' ),
			'bridge' => __( 'Puente', 'ai-knowledge' ),
			'hash'   => __( 'Hash', 'ai-knowledge' ),
			'updated' => __( 'Actualizado', 'ai-knowledge' ),
			'links'  => __( 'Enlaces', 'ai-knowledge' ),
			'manual' => __( 'Control manual', 'ai-knowledge' ),
			'actions' => __( 'Acciones', 'ai-knowledge' ),
		);
	}

	public function prepare_items() {
		$per_page     = 20;
		$current_page = $this->get_pagenum();

		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore
		$lang   = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : ''; // phpcs:ignore
		$stale  = isset( $_GET['stale'] ) && '' !== $_GET['stale'] ? (int) $_GET['stale'] : ''; // phpcs:ignore
		// 's' es el nombre de parametro nativo que usa WP_List_Table::search_box().
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : ''; // phpcs:ignore

		$args = array(
			'status' => $status,
			'lang'   => $lang,
			'stale'  => $stale,
			'search' => $search,
			'limit'  => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
		);

		$this->items = Registry::query( $args );
		$total       = Registry::count( array( 'status' => $status, 'lang' => $lang, 'stale' => $stale, 'search' => $search ) );

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
			)
		);

		$this->_column_headers = array( $this->get_columns(), array(), array() );
	}

	/** Checkbox de seleccion para bulk actions (patron nativo WP_List_Table). */
	public function column_cb( $item ) {
		// class="form-check-input": clase REAL de Tabler para reskinar el
		// checkbox nativo (color de acento, tamaño), sin cambiar name=/value=.
		return sprintf( '<input type="checkbox" class="form-check-input" name="row_ids[]" value="%d" />', (int) $item->id );
	}

	/**
	 * Acciones en bloque disponibles. El handler real vive en
	 * Admin::maybe_handle_bulk_action(), enganchado a load-{hook} de la
	 * pagina de menu (NO a admin_post.php: ver el docblock de ese metodo
	 * para el porque). Usa WP_List_Table::current_action() para leer el
	 * desplegable de arriba o el de abajo indistintamente.
	 */
	public function get_bulk_actions() {
		return array(
			'delete'     => __( 'Borrar seleccionados', 'ai-knowledge' ),
			'regenerate' => __( 'Regenerar seleccionados', 'ai-knowledge' ),
		);
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'source':
				// Documentos compuestos (Store_Info_Doc): source_id es un ID
				// centinela sin post real detras, asi que get_the_title() siempre
				// devuelve vacio y se veia solo el numero -- se resuelve el
				// titulo fijo de la clase en su lugar (mismo patron que
				// Llms_Txt::link_line() usa para estos mismos source_type).
				if ( class_exists( '\WOOKB\Store_Info_Doc' ) ) {
					$composite_title = Store_Info_Doc::title_for( $item->source_type, $item->lang );
					if ( $composite_title ) {
						return esc_html( $composite_title );
					}
				}
				$title = get_the_title( $item->source_id );
				$edit  = get_edit_post_link( $item->source_id );
				return $edit ? '<a href="' . esc_url( $edit ) . '">' . esc_html( $title ? $title : ( '#' . $item->source_id ) ) . '</a>' : esc_html( '#' . $item->source_id );
			case 'lang':
				return esc_html( strtoupper( $item->lang ) );
			case 'status':
				// Texto plano, sin badge/pastilla -- pedido explicito del
				// usuario (mismo aspecto que la columna Idioma).
				return esc_html( self::status_label( $item->status ) );
			case 'bridge':
				return $item->is_bridge ? esc_html__( 'Sí', 'ai-knowledge' ) : '—';
			case 'hash':
				return esc_html( substr( $item->source_hash, 0, 8 ) );
			case 'updated':
				return esc_html( $item->updated_at );
			case 'links':
				$out = array();
				if ( $item->md_path ) {
					$out[] = '<a href="' . esc_url( Markdown_Store::public_url( $item->md_path ) ) . '" target="_blank">.md</a>';
				}
				if ( $item->doc_post_id ) {
					$edit = get_edit_post_link( $item->doc_post_id );
					if ( $edit ) {
						$out[] = '<a href="' . esc_url( $edit ) . '" target="_blank">sgkb-docs</a>';
					}
				}
				return implode( ' · ', $out );
			case 'manual':
				return $this->manual_control_markup( $item );
			case 'actions':
				return $this->row_actions_markup( $item );
			default:
				return '';
		}
	}

	/**
	 * Formularios "fuera de banda" pendientes de imprimir DESPUES del </form>
	 * grande de bulk actions (ver tab-registro.php). No se pueden anidar
	 * <form> dentro de otro <form> -- es HTML invalido, y los navegadores lo
	 * resuelven ignorando la apertura del <form> interior y cerrando el
	 * exterior en su primer </form>, rompiendo tanto los botones de fila como
	 * la seleccion multiple de las filas siguientes (bug real detectado en
	 * staging: el HTML servido era correcto, pero el DOM resultante en el
	 * navegador no, por esta regla de parseo). La solucion nativa de HTML5
	 * para "un control visualmente dentro de A, pero que envia a B" es el
	 * atributo form="id-del-formulario", que asocia el control con un <form>
	 * declarado en cualquier otro punto del documento sin necesidad de
	 * anidarlo.
	 */
	protected $out_of_band_forms = array();

	public function render_out_of_band_forms() {
		return implode( '', $this->out_of_band_forms );
	}

	/**
	 * Fase 1: control manual por fila -- textarea con el Markdown actual
	 * (override_text si ya esta en modo manual, si no el .md real via
	 * Markdown_Store), limite de caracteres propio, y los botones Pasar a
	 * manual / Volver a Auto / Marcar revisado. Colapsado en <details> para no
	 * romper el ancho de la tabla existente.
	 */
	protected function manual_control_markup( $item ) {
		$is_manual = 'manual' === $item->override_mode;

		if ( $is_manual && null !== $item->override_text && '' !== $item->override_text ) {
			$current_text = $item->override_text;
		} else {
			$raw          = $item->md_path ? Markdown_Store::read( $item->md_path ) : null;
			$current_text = $raw ? Markdown_Store::body_only( $raw ) : '';
		}

		$set_manual_form_id = 'wookb-set-manual-' . (int) $item->id;
		$char_limit_form_id = 'wookb-char-limit-' . (int) $item->id;
		$back_auto_form_id  = 'wookb-back-auto-' . (int) $item->id;
		$resolve_form_id    = 'wookb-resolve-stale-' . (int) $item->id;

		ob_start();
		?>
		<form id="<?php echo esc_attr( $set_manual_form_id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:none">
			<input type="hidden" name="action" value="wookb_set_manual" />
			<input type="hidden" name="row_id" value="<?php echo esc_attr( $item->id ); ?>" />
			<?php wp_nonce_field( 'wookb_set_manual' ); ?>
		</form>
		<form id="<?php echo esc_attr( $char_limit_form_id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:none">
			<input type="hidden" name="action" value="wookb_set_char_limit" />
			<input type="hidden" name="row_id" value="<?php echo esc_attr( $item->id ); ?>" />
			<?php wp_nonce_field( 'wookb_set_char_limit' ); ?>
		</form>
		<form id="<?php echo esc_attr( $back_auto_form_id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:none" onsubmit="return confirm('<?php echo esc_js( __( 'Vuelve a modo automatico y regenera este documento con IA ahora mismo. ¿Continuar?', 'ai-knowledge' ) ); ?>');">
			<input type="hidden" name="action" value="wookb_back_to_auto" />
			<input type="hidden" name="row_id" value="<?php echo esc_attr( $item->id ); ?>" />
			<?php wp_nonce_field( 'wookb_back_to_auto' ); ?>
		</form>
		<form id="<?php echo esc_attr( $resolve_form_id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:none">
			<input type="hidden" name="action" value="wookb_resolve_stale" />
			<input type="hidden" name="row_id" value="<?php echo esc_attr( $item->id ); ?>" />
			<?php wp_nonce_field( 'wookb_resolve_stale' ); ?>
		</form>
		<?php
		$this->out_of_band_forms[] = ob_get_clean();

		ob_start();
		?>
		<div>
			<?php if ( $is_manual ) : ?>
				<span class="wookb-badge-manual"><?php esc_html_e( 'Manual', 'ai-knowledge' ); ?></span>
			<?php else : ?>
				<span class="wookb-badge-auto"><?php esc_html_e( 'Auto', 'ai-knowledge' ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $item->stale ) ) : ?>
				<span class="wookb-badge-stale"><?php esc_html_e( 'Origen actualizado', 'ai-knowledge' ); ?></span>
				<button type="submit" form="<?php echo esc_attr( $resolve_form_id ); ?>" class="button button-small"><?php esc_html_e( 'Marcar revisado', 'ai-knowledge' ); ?></button>
			<?php endif; ?>
		</div>
		<details>
			<summary><?php esc_html_e( 'Ver/editar Markdown', 'ai-knowledge' ); ?></summary>
			<textarea form="<?php echo esc_attr( $set_manual_form_id ); ?>" name="override_text" rows="6" style="width:100%;"><?php echo esc_textarea( $current_text ); ?></textarea>
			<p>
				<label>
					<?php esc_html_e( 'Límite de caracteres', 'ai-knowledge' ); ?>
					<input
						type="number"
						form="<?php echo esc_attr( $char_limit_form_id ); ?>"
						name="char_limit"
						min="100"
						max="10000"
						step="50"
						value="<?php echo esc_attr( $item->char_limit ? $item->char_limit : '' ); ?>"
						placeholder="<?php echo esc_attr( Generator::BODY_CHAR_LIMIT ); ?>"
						style="width:6em"
					/>
				</label>
				<button type="submit" form="<?php echo esc_attr( $char_limit_form_id ); ?>" class="button button-small"><?php esc_html_e( 'Guardar límite', 'ai-knowledge' ); ?></button>
			</p>
			<p>
				<?php if ( $is_manual ) : ?>
					<button type="submit" form="<?php echo esc_attr( $back_auto_form_id ); ?>" class="button button-small"><?php esc_html_e( 'Volver a Auto', 'ai-knowledge' ); ?></button>
				<?php else : ?>
					<button type="submit" form="<?php echo esc_attr( $set_manual_form_id ); ?>" class="button button-small"><?php esc_html_e( 'Pasar a manual', 'ai-knowledge' ); ?></button>
				<?php endif; ?>
			</p>
		</details>
		<?php
		return ob_get_clean();
	}

	protected function row_actions_markup( $item ) {
		$regen_form_id   = 'wookb-regen-' . (int) $item->id;
		$delete_form_id  = 'wookb-delete-' . (int) $item->id;

		ob_start();
		?>
		<form id="<?php echo esc_attr( $regen_form_id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:none">
			<input type="hidden" name="action" value="wookb_regenerate_single" />
			<input type="hidden" name="row_id" value="<?php echo esc_attr( $item->id ); ?>" />
			<?php wp_nonce_field( 'wookb_regenerate_single' ); ?>
		</form>
		<form id="<?php echo esc_attr( $delete_form_id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:none" onsubmit="return confirm('<?php echo esc_js( __( '¿Borrar documento y post asociado?', 'ai-knowledge' ) ); ?>');">
			<input type="hidden" name="action" value="wookb_row_action" />
			<input type="hidden" name="row_id" value="<?php echo esc_attr( $item->id ); ?>" />
			<input type="hidden" name="row_op" value="delete" />
			<?php wp_nonce_field( 'wookb_row_action' ); ?>
		</form>
		<?php
		$this->out_of_band_forms[] = ob_get_clean();

		ob_start();
		?>
		<label>
			<span class="screen-reader-text"><?php esc_html_e( 'Límite de caracteres', 'ai-knowledge' ); ?></span>
			<input
				type="number"
				name="char_limit"
				form="<?php echo esc_attr( $regen_form_id ); ?>"
				min="100"
				max="10000"
				step="50"
				value="<?php echo esc_attr( $item->char_limit ? $item->char_limit : Generator::BODY_CHAR_LIMIT ); ?>"
				placeholder="<?php echo esc_attr( Generator::BODY_CHAR_LIMIT ); ?>"
				style="width:5.5em"
				title="<?php esc_attr_e( 'Límite de caracteres para esta generación (uso único, no se guarda)', 'ai-knowledge' ); ?>"
			/>
		</label>
		<button type="submit" form="<?php echo esc_attr( $regen_form_id ); ?>" class="button button-small"><?php esc_html_e( 'Generar', 'ai-knowledge' ); ?></button>
		<button type="submit" form="<?php echo esc_attr( $delete_form_id ); ?>" class="button button-small button-link-delete"><?php esc_html_e( 'Borrar', 'ai-knowledge' ); ?></button>
		<?php
		return ob_get_clean();
	}
}
