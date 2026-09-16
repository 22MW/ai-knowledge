<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once AIKB_DIR . 'admin/class-registry-table.php';

$table = new Registry_Table();
$table->prepare_items();
?>
<p class="description">
	<?php esc_html_e( 'Todos los documentos generados: su estado, cuándo se actualizaron, y acceso a su contenido. Desde aquí puedes regenerar, borrar, fijar texto manual o filtrar por estado/idioma.', 'ai-knowledge' ); ?>
</p>
<?php if ( isset( $_GET['wookb_regen_error'] ) ) : // phpcs:ignore ?>
	<div class="notice notice-error is-dismissible"><p><?php echo esc_html( urldecode( wp_unslash( $_GET['wookb_regen_error'] ) ) ); // phpcs:ignore ?></p></div>
<?php endif; ?>
<?php if ( isset( $_GET['wookb_skipped_manual'] ) ) : // phpcs:ignore ?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<?php
			printf(
				/* translators: %d: numero de documentos en modo manual que se saltaron */
				esc_html( _n( '%d documento en modo manual se saltó (no se regenera).', '%d documentos en modo manual se saltaron (no se regeneran).', (int) $_GET['wookb_skipped_manual'], 'ai-knowledge' ) ), // phpcs:ignore
				(int) $_GET['wookb_skipped_manual'] // phpcs:ignore
			);
			?>
		</p>
	</div>
<?php endif; ?>
<?php if ( isset( $_GET['wookb_queue_remaining'] ) ) : // phpcs:ignore ?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<?php
			printf(
				/* translators: %d: numero de documentos que quedan por procesar en la cola */
				esc_html__( 'Lote de %1$d procesado. Quedan %2$d documentos en cola: continuando automáticamente…', 'ai-knowledge' ),
				(int) Admin::RESET_QUEUE_BATCH,
				(int) $_GET['wookb_queue_remaining'] // phpcs:ignore
			);
			?>
		</p>
	</div>
	<script>
		// Auto-continuar la cola: en vez de esperar un clic manual, se
		// reenvia el mismo formulario de "Reiniciar cola" solo, con un
		// pequeño margen (2s) para no saturar el servidor con peticiones
		// seguidas. Cada peticion sigue procesando solo <?php echo (int) Admin::RESET_QUEUE_BATCH; ?>
		// de golpe (limite de tiempo de ejecucion de PHP, sin tocar eso);
		// lo unico que cambia es que ya no hace falta pulsar el boton cada vez.
		setTimeout( function () {
			var form = document.getElementById( 'wookb-reset-queue-form' );
			if ( form ) {
				form.submit();
			}
		}, 2000 );
	</script>
<?php endif; ?>

<h2><?php esc_html_e( 'Generar por ID o URL', 'ai-knowledge' ); ?></h2>
<p class="description">
	<?php esc_html_e( 'Para un producto/página que ya no tiene fila en el Registro (por ejemplo, tras borrarlo aquí): genera de nuevo, en todos los idiomas activos, de forma inmediata y sin esperar al cron.', 'ai-knowledge' ); ?>
</p>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_force_generate" />
	<?php wp_nonce_field( 'wookb_force_generate' ); ?>
	<input type="text" name="force_id_or_url" placeholder="<?php esc_attr_e( 'ID del producto/página, o su URL', 'ai-knowledge' ); ?>" style="width:320px" />
	<?php submit_button( __( 'Generar ahora', 'ai-knowledge' ), 'secondary', '', false ); ?>
</form>
<hr />

<div class="wookb-toolbar-row">
	<form method="get" class="wookb-toolbar-form">
		<input type="hidden" name="page" value="ai-knowledge" />
		<input type="hidden" name="tab" value="registro" />
		<select name="status">
			<option value=""><?php esc_html_e( 'Todos los estados', 'ai-knowledge' ); ?></option>
			<?php foreach ( array( 'queued', 'generating', 'synced', 'error', 'orphan' ) as $s ) : ?>
				<option value="<?php echo esc_attr( $s ); ?>" <?php selected( isset( $_GET['status'] ) && $_GET['status'] === $s ); // phpcs:ignore ?>><?php echo esc_html( Registry_Table::status_label( $s ) ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php if ( count( Wpml::active_languages() ) > 1 ) : ?>
			<select name="lang">
				<option value=""><?php esc_html_e( 'Todos los idiomas', 'ai-knowledge' ); ?></option>
				<?php foreach ( Wpml::active_languages() as $l ) : ?>
					<option value="<?php echo esc_attr( $l ); ?>" <?php selected( isset( $_GET['lang'] ) && $_GET['lang'] === $l ); // phpcs:ignore ?>><?php echo esc_html( strtoupper( $l ) ); ?></option>
				<?php endforeach; ?>
			</select>
		<?php endif; ?>
		<input type="search" name="s" value="<?php echo isset( $_GET['s'] ) ? esc_attr( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore ?>" placeholder="<?php esc_attr_e( 'Buscar por título…', 'ai-knowledge' ); ?>" />
		<select name="per_page">
			<?php foreach ( array( 20, 50, 100 ) as $pp ) : ?>
				<option value="<?php echo esc_attr( $pp ); ?>" <?php selected( Registry_Table::current_per_page() === $pp ); ?>><?php echo esc_html( sprintf( /* translators: %d: numero de filas por pagina */ __( '%d por página', 'ai-knowledge' ), $pp ) ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php submit_button( __( 'Filtrar', 'ai-knowledge' ), '', '', false ); ?>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wookb-toolbar-form" onsubmit="return confirm('<?php echo esc_js( __( '¿Borrar todos los documentos que cumplen el filtro actual (o TODOS si no hay filtro puesto) y sus posts asociados? Sus orígenes se añadirán a "IDs a excluir" en Contenido, para que no se vuelvan a generar solos (quítalos de esa lista si quieres que se vuelvan a generar).', 'ai-knowledge' ) ); ?>');">
		<input type="hidden" name="action" value="wookb_delete_all" />
		<input type="hidden" name="status" value="<?php echo isset( $_GET['status'] ) ? esc_attr( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore ?>" />
		<input type="hidden" name="lang" value="<?php echo isset( $_GET['lang'] ) ? esc_attr( wp_unslash( $_GET['lang'] ) ) : ''; // phpcs:ignore ?>" />
		<input type="hidden" name="s" value="<?php echo isset( $_GET['s'] ) ? esc_attr( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore ?>" />
		<?php wp_nonce_field( 'wookb_delete_all' ); ?>
		<button class="button button-link-delete"><?php esc_html_e( 'Borrar todos', 'ai-knowledge' ); ?></button>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wookb-toolbar-form" id="wookb-reset-queue-form">
		<input type="hidden" name="action" value="wookb_reset_queue" />
		<?php wp_nonce_field( 'wookb_reset_queue' ); ?>
		<button class="button"><?php esc_html_e( 'Reiniciar cola', 'ai-knowledge' ); ?></button>
	</form>
</div>

<!--
	Bulk actions: el formulario se autoenvia a esta misma pagina (sin action=
	explicito), NO a admin-post.php. WP_List_Table::display() ya renderiza su
	propio <select name="action">/"action2" para el desplegable de bulk
	actions y su propio nonce (accion 'bulk-documentos', segun el 'plural' del
	constructor de Registry_Table). Un campo oculto name="action" adicional
	aqui colisionaria con ese <select> bajo la misma clave POST -- ver
	Admin::maybe_handle_bulk_action() (enganchada a load-{hook} de esta
	pagina de menu) para el procesamiento real y la explicacion completa.
	row_ids[] lo rellena Registry_Table::column_cb().
-->
<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=ai-knowledge&tab=registro' ) ); ?>" id="wookb-registro-bulk-form">
	<?php $table->display(); ?>
</form>
<script>
	// WP_List_Table no confirma el bulk action "Borrar seleccionados" por
	// defecto -- se añade aqui, mismo aviso que "Borrar todos"/borrado de
	// fila individual: tambien excluye los origenes en Contenido.
	( function () {
		var form = document.getElementById( 'wookb-registro-bulk-form' );
		if ( ! form ) {
			return;
		}
		form.addEventListener( 'submit', function ( e ) {
			var top    = form.querySelector( '#bulk-action-selector-top' );
			var bottom = form.querySelector( '#bulk-action-selector-bottom' );
			var action = ( top && '-1' !== top.value ) ? top.value : ( bottom ? bottom.value : '-1' );
			if ( 'delete' !== action ) {
				return;
			}
			var msg = <?php echo wp_json_encode( __( '¿Borrar los documentos seleccionados y sus posts asociados? Sus orígenes se añadirán a "IDs a excluir" en Contenido, para que no se vuelvan a generar solos (quítalos de esa lista si quieres que se vuelvan a generar).', 'ai-knowledge' ) ); ?>;
			if ( ! window.confirm( msg ) ) {
				e.preventDefault();
			}
		} );
	} )();
</script>
<?php
// Formularios de fila (Generar/Borrar) impresos AQUI, fuera del <form> de
// arriba a proposito: no se pueden anidar <form> dentro de otro <form> (HTML
// invalido -- el navegador cierra el exterior en el primer </form> interior,
// rompiendo la seleccion multiple de filas posteriores). Los botones de cada
// fila usan el atributo form="..." para enviarse a estos formularios aunque
// esten fuera de ellos en el DOM. Ver Registry_Table::row_actions_markup().
echo $table->render_out_of_band_forms(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
