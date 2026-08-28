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
			'queued'     => __( 'En cola', 'woo-kb-generator' ),
			'generating' => __( 'Generando', 'woo-kb-generator' ),
			'synced'     => __( 'Listo', 'woo-kb-generator' ),
			'error'      => __( 'Error', 'woo-kb-generator' ),
			'orphan'     => __( 'Sin origen', 'woo-kb-generator' ),
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
			'source' => __( 'Origen', 'woo-kb-generator' ),
			'lang'   => __( 'Idioma', 'woo-kb-generator' ),
			'status' => __( 'Estado', 'woo-kb-generator' ),
			'bridge' => __( 'Puente', 'woo-kb-generator' ),
			'hash'   => __( 'Hash', 'woo-kb-generator' ),
			'updated' => __( 'Actualizado', 'woo-kb-generator' ),
			'links'  => __( 'Enlaces', 'woo-kb-generator' ),
			'actions' => __( 'Acciones', 'woo-kb-generator' ),
		);
	}

	public function prepare_items() {
		$per_page     = 20;
		$current_page = $this->get_pagenum();

		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore
		$lang   = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : ''; // phpcs:ignore
		// 's' es el nombre de parametro nativo que usa WP_List_Table::search_box().
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : ''; // phpcs:ignore

		$args = array(
			'status' => $status,
			'lang'   => $lang,
			'search' => $search,
			'limit'  => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
		);

		$this->items = Registry::query( $args );
		$total       = Registry::count( array( 'status' => $status, 'lang' => $lang, 'search' => $search ) );

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
			'delete'     => __( 'Borrar seleccionados', 'woo-kb-generator' ),
			'regenerate' => __( 'Regenerar seleccionados', 'woo-kb-generator' ),
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
				return $item->is_bridge ? esc_html__( 'Sí', 'woo-kb-generator' ) : '—';
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
		<form id="<?php echo esc_attr( $delete_form_id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:none" onsubmit="return confirm('<?php echo esc_js( __( '¿Borrar documento y post asociado?', 'woo-kb-generator' ) ); ?>');">
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
			<span class="screen-reader-text"><?php esc_html_e( 'Límite de caracteres', 'woo-kb-generator' ); ?></span>
			<input
				type="number"
				name="char_limit"
				form="<?php echo esc_attr( $regen_form_id ); ?>"
				min="100"
				max="10000"
				step="50"
				value="<?php echo esc_attr( Generator::BODY_CHAR_LIMIT ); ?>"
				placeholder="<?php echo esc_attr( Generator::BODY_CHAR_LIMIT ); ?>"
				style="width:5.5em"
				title="<?php esc_attr_e( 'Límite de caracteres para esta generación (uso único, no se guarda)', 'woo-kb-generator' ); ?>"
			/>
		</label>
		<button type="submit" form="<?php echo esc_attr( $regen_form_id ); ?>" class="button button-small"><?php esc_html_e( 'Generar', 'woo-kb-generator' ); ?></button>
		<button type="submit" form="<?php echo esc_attr( $delete_form_id ); ?>" class="button button-small button-link-delete"><?php esc_html_e( 'Borrar', 'woo-kb-generator' ); ?></button>
		<?php
		return ob_get_clean();
	}
}
