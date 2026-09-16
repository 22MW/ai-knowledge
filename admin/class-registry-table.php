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
		$columns = array(
			'cb'     => '<input type="checkbox" />',
			'source' => __( 'Origen', 'ai-knowledge' ),
		);
		// Columna Idioma: solo si el sitio es multiidioma de verdad -- en un
		// sitio de un solo idioma, todas las filas dirían lo mismo, es
		// ruido. Mismo criterio que el sufijo "(ES)"/"(EN)" de llms.txt
		// (Llms_Txt::build()).
		if ( count( Wpml::active_languages() ) > 1 ) {
			$columns['lang'] = __( 'Idioma', 'ai-knowledge' );
		}
		$columns['status']  = __( 'Estado', 'ai-knowledge' );
		$columns['updated'] = __( 'Actualizado', 'ai-knowledge' );
		$columns['links']   = __( 'Enlaces', 'ai-knowledge' );
		$columns['manual']  = __( 'Control manual', 'ai-knowledge' );
		$columns['actions'] = __( 'Acciones', 'ai-knowledge' );
		return $columns;
	}

	/** Valores permitidos para el selector "por página" de la toolbar. */
	const PER_PAGE_OPTIONS = array( 20, 50, 100 );

	/** Lee 'per_page' de la URL con whitelist, igual que status/lang/s. */
	public static function current_per_page() {
		$requested = isset( $_GET['per_page'] ) ? (int) $_GET['per_page'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return in_array( $requested, self::PER_PAGE_OPTIONS, true ) ? $requested : self::PER_PAGE_OPTIONS[0];
	}

	public function prepare_items() {
		$per_page     = self::current_per_page();
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
				if ( class_exists( '\WOOKB\Llms_Faq' ) && Llms_Faq::SOURCE_TYPE === $item->source_type ) {
					return esc_html( Llms_Faq::title() );
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
				return $this->manual_badges_markup( $item );
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
	 * QA (bug real, tabla del Registro): con la columna "Control manual"
	 * llevando badges + <details> + textarea + botones dentro de una sola
	 * celda, y sin ancho propio en el CSS (a diferencia de TODAS sus
	 * columnas hermanas: lang/status/bridge/hash/updated/actions tienen
	 * "width" explicito en admin.css/wookb-theme.css), la tabla -- que
	 * WP_List_Table marca con la clase "fixed" por defecto
	 * (get_table_classes() de core, no sobreescrito aqui) y por tanto usa
	 * table-layout:fixed via wp-admin/css/list-tables.css (`table.fixed {
	 * table-layout: fixed; }`) -- reparte el ancho de columnas SIN
	 * constraint explicito segun el contenido de la fila de CABECERA
	 * (unico que cuenta en fixed layout), no el de las filas de datos. La
	 * cabecera "Control manual" es mas larga que "Origen", asi que esa
	 * columna se llevaba mas ancho que "Origen" -- y el titulo real de cada
	 * fila en "Origen" quedaba forzado a envolver letra por letra en una
	 * columna demasiado estrecha. Confirmado por lectura de
	 * wp-admin/includes/class-wp-list-table.php::get_table_classes()
	 * (siempre añade "fixed" salvo que se sobreescriba, y Registry_Table no
	 * lo hace) + wp-admin/css/list-tables.css línea ~291.
	 *
	 * Arreglo de layout: assets/wookb-theme.css fuerza table-layout:auto
	 * para esta tabla (columnas se miden por contenido real, como una tabla
	 * HTML normal).
	 *
	 * Arreglo de UX (ampliación del mismo encargo, ver UX1 de
	 * qa-resultados-fase-0-a-5.md): el bloque de edición ya NO vive dentro
	 * de la celda estrecha de "Control manual". Esta columna ahora solo
	 * muestra los badges (Manual/Auto + aviso de "stale"), cortos y sin
	 * necesidad de mucho ancho. El <details> con el textarea/botones se
	 * imprime en una fila aparte que ocupa TODO el ancho de la tabla
	 * (colspan, ver single_row()) -- no una celda de columna.
	 */
	protected function manual_badges_markup( $item ) {
		$is_manual       = 'manual' === $item->override_mode;
		$resolve_form_id = 'wookb-resolve-stale-' . (int) $item->id;

		ob_start();
		?>
		<div class="wookb-manual-badges">
			<?php if ( $is_manual ) : ?>
				<span class="wookb-badge-manual"><?php esc_html_e( 'Manual', 'ai-knowledge' ); ?></span>
			<?php else : ?>
				<span class="wookb-badge-auto"><?php esc_html_e( 'Auto', 'ai-knowledge' ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $item->stale ) ) : ?>
				<span class="wookb-badge-stale"><?php esc_html_e( 'Origen actualizado', 'ai-knowledge' ); ?></span>
				<button type="submit" form="<?php echo esc_attr( $resolve_form_id ); ?>" class="button button-small wookb-btn-success"><?php esc_html_e( 'Marcar revisado', 'ai-knowledge' ); ?></button>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Bloque completo de edición manual (textarea + límite + botones), a
	 * ancho completo de fila -- ver single_row(), que lo imprime en un
	 * <tr><td colspan="..."> aparte justo debajo de la fila normal del
	 * documento, no dentro de una columna. Mismas 4 acciones/formularios de
	 * siempre (wookb_set_manual, wookb_set_char_limit, wookb_back_to_auto,
	 * wookb_resolve_stale), sin cambios de lógica -- solo maquetación:
	 * textarea de 15 líneas (antes 6) y botones a tamaño normal "button"
	 * (antes "button-small"), pedido explícito por ser un bloque ahora a
	 * ancho completo, no una celda estrecha.
	 */
	protected function manual_expanded_row_markup( $item ) {
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

		// Bug real (Fase 10, pieza 5, detectado 2026-09-16): estos 4 <form>
		// se imprimian aqui mismo, DENTRO de la fila expandida -- que a su vez
		// esta DENTRO del <form> grande de bulk actions del Registro (ver
		// tab-registro.php). <form> anidado es HTML invalido: el navegador
		// cierra el <form> EXTERIOR en el primer </form> interior que
		// encuentra, dejando fuera de el los checkboxes de las filas
		// siguientes -- "Borrar seleccionados"/"Regenerar seleccionados" se
		// quedaba sin enviar row_ids[] de la mayoria de filas, fallando en
		// silencio. Mismo patron que ya usa row_actions_markup(): estos 4
		// forms se imprimen FUERA de banda (out_of_band_forms, ver
		// render_out_of_band_forms() en tab-registro.php), y los controles de
		// abajo (textarea/input/button) se asocian con el atributo
		// form="..." aunque esten en otro punto del DOM.
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
		<details>
			<summary><?php esc_html_e( 'Ajustes avanzados', 'ai-knowledge' ); ?></summary>
			<p class="wookb-manual-meta">
				<strong><?php esc_html_e( 'Puente:', 'ai-knowledge' ); ?></strong>
				<?php echo $item->is_bridge ? esc_html__( 'Sí', 'ai-knowledge' ) : '—'; ?>
				&nbsp;·&nbsp;
				<strong><?php esc_html_e( 'Hash:', 'ai-knowledge' ); ?></strong>
				<?php echo esc_html( substr( $item->source_hash, 0, 8 ) ); ?>
			</p>
			<textarea form="<?php echo esc_attr( $set_manual_form_id ); ?>" name="override_text" rows="15" class="wookb-manual-textarea" style="width:100%;"><?php echo esc_textarea( $current_text ); ?></textarea>
			<p class="wookb-manual-actions">
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
				<button type="submit" form="<?php echo esc_attr( $char_limit_form_id ); ?>" class="button"><?php esc_html_e( 'Guardar límite', 'ai-knowledge' ); ?></button>
				<button type="submit" form="<?php echo esc_attr( $set_manual_form_id ); ?>" class="button"><?php esc_html_e( 'Guardar cambios', 'ai-knowledge' ); ?></button>
				<?php if ( $is_manual ) : ?>
					<button type="submit" form="<?php echo esc_attr( $back_auto_form_id ); ?>" class="button"><?php esc_html_e( 'Volver a Auto', 'ai-knowledge' ); ?></button>
				<?php endif; ?>
			</p>
		</details>
		<?php
		return ob_get_clean();
	}

	/**
	 * Override de WP_List_Table::single_row(): imprime la fila normal del
	 * documento tal cual (parent::single_row(), sin tocar columnas,
	 * data-colname, columna primaria ni el toggle-row nativo de la vista
	 * movil -- todo eso lo sigue gestionando el core igual que antes), y
	 * justo debajo una fila EXTRA que ocupa toda la anchura de la tabla
	 * (colspan = numero total de columnas) con el bloque de edición manual
	 * completo. No es una columna mas: es una fila aparte, para que el
	 * textarea/botones no queden encerrados en una celda estrecha.
	 */
	public function single_row( $item ) {
		parent::single_row( $item );

		$colspan = count( $this->get_columns() );
		echo '<tr class="wookb-manual-expand-row">';
		echo '<td colspan="' . esc_attr( $colspan ) . '" class="wookb-manual-expand-cell">';
		echo $this->manual_expanded_row_markup( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado y escapado campo a campo dentro del propio metodo.
		echo '</td>';
		echo '</tr>';
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
		<form id="<?php echo esc_attr( $delete_form_id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:none" onsubmit="return confirm('<?php echo esc_js( __( '¿Borrar documento y post asociado? El origen se añadirá a "IDs a excluir" en Contenido, para que no se vuelva a generar solo (quítalo de esa lista si quieres que se vuelva a generar).', 'ai-knowledge' ) ); ?>');">
			<input type="hidden" name="action" value="wookb_row_action" />
			<input type="hidden" name="row_id" value="<?php echo esc_attr( $item->id ); ?>" />
			<input type="hidden" name="row_op" value="delete" />
			<?php wp_nonce_field( 'wookb_row_action' ); ?>
		</form>
		<?php
		$this->out_of_band_forms[] = ob_get_clean();

		ob_start();
		?>
		<div class="wookb-row-actions-stack">
			<button type="submit" form="<?php echo esc_attr( $regen_form_id ); ?>" class="button button-small"><?php esc_html_e( 'Generar', 'ai-knowledge' ); ?></button>
			<button type="submit" form="<?php echo esc_attr( $delete_form_id ); ?>" class="button button-small button-link-delete"><?php esc_html_e( 'Borrar', 'ai-knowledge' ); ?></button>
		</div>
		<?php
		return ob_get_clean();
	}
}
