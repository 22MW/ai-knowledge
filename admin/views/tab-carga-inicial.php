<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ids       = Scope::resolve_ids();
$langs     = Wpml::active_languages();
$total     = count( $ids ) * count( $langs );
$settings  = Scope::settings();
$counted   = Registry::count( array( 'status' => 'synced' ) );
?>
<p class="description">
	<?php esc_html_e( 'Genera de golpe los documentos de todo el contenido dentro del alcance, y ajusta aquí el límite diario, el tamaño de lote y el debounce de la cola.', 'ai-knowledge' ); ?>
</p>
<?php Admin::documentation_link( 'carga-inicial' ); ?>
<p>
	<?php
	printf(
		/* translators: 1: "X elemento(s)" ya pluralizado, 2: "X idioma(s)" ya pluralizado, 3: total documentos */
		esc_html__( 'Alcance actual: %1$s × %2$s = %3$d documentos posibles.', 'ai-knowledge' ),
		esc_html( sprintf( /* translators: %d: número de elementos */ _n( '%d elemento', '%d elementos', count( $ids ), 'ai-knowledge' ), count( $ids ) ) ),
		esc_html( sprintf( /* translators: %d: número de idiomas activos */ _n( '%d idioma', '%d idiomas', count( $langs ), 'ai-knowledge' ), count( $langs ) ) ),
		$total
	);
	?>
</p>
<p><?php printf( esc_html__( 'Documentos ya sincronizados: %d.', 'ai-knowledge' ), (int) $counted ); ?></p>
<?php if ( ! empty( $settings['no_limit'] ) ) : ?>
	<p style="color:#b32d2e;font-weight:600;"><?php esc_html_e( 'El límite diario está desactivado — la generación avanzará sin tope.', 'ai-knowledge' ); ?></p>
<?php else : ?>
	<p><?php printf( esc_html__( 'Límite diario actual: %d generaciones/día.', 'ai-knowledge' ), (int) $settings['daily_limit'] ); ?></p>
<?php endif; ?>

<?php
// Resumen de estado (Negocio/WooCommerce/FAQ/Chatbot/documentos), extraido a
// Admin::render_assistant_summary() para reutilizarlo tambien aqui -- mismo
// resumen que ya se ve en el paso "finish" del asistente.
echo Admin::render_assistant_summary(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado y escapado campo a campo dentro del propio metodo.
?>

<?php
// Botones "Generar pendientes"/"Reiniciar todo" (o "Cancelar generación"),
// extraidos a Admin::render_seed_controls() para reutilizarlos tambien en
// el paso "finish" del asistente de configuracion -- misma logica, mismo
// marcado, un solo sitio de mantenimiento.
echo Admin::render_seed_controls(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado y escapado campo a campo dentro del propio metodo.
?>

<p class="description">
	<?php esc_html_e( 'Revisa la pestaña Registro para ver el progreso, o Herramientas → Scheduled Actions (grupo woo-kb) para el detalle técnico.', 'ai-knowledge' ); ?>
</p>

<hr />

<h2><?php esc_html_e( 'Ajustes de la cola', 'ai-knowledge' ); ?></h2>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_save_queue_settings" />
	<?php wp_nonce_field( 'wookb_save_queue_settings' ); ?>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Límite diario de generaciones', 'ai-knowledge' ); ?></th>
			<td>
				<input type="number" min="1" name="daily_limit" value="<?php echo esc_attr( $settings['daily_limit'] ); ?>" />
				<label style="margin-left:12px;">
					<input type="checkbox" name="no_limit" value="1" <?php checked( ! empty( $settings['no_limit'] ) ); ?> />
					<?php esc_html_e( 'Sin límite (usar solo en cargas manuales supervisadas — recuerda desactivarlo al terminar)', 'ai-knowledge' ); ?>
				</label>
				<?php if ( ! empty( $settings['no_limit'] ) ) : ?>
					<p style="color:#b32d2e;font-weight:600;"><?php esc_html_e( 'Aviso: el límite diario está DESACTIVADO.', 'ai-knowledge' ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Tamaño de lote', 'ai-knowledge' ); ?></th>
			<td><input type="number" min="1" name="batch_size" value="<?php echo esc_attr( $settings['batch_size'] ); ?>" /></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Retraso de debounce (segundos)', 'ai-knowledge' ); ?></th>
			<td><input type="number" min="0" name="debounce_seconds" value="<?php echo esc_attr( $settings['debounce_seconds'] ); ?>" /></td>
		</tr>
	</table>
	<?php submit_button( __( 'Guardar', 'ai-knowledge' ) ); ?>
</form>
