<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ids       = Scope::resolve_ids();
$langs     = Wpml::active_languages();
$total     = count( $ids ) * count( $langs );
$settings  = Scope::settings();
$running   = (bool) get_option( 'wookb_seed_running' );
$counted   = Registry::count( array( 'status' => 'synced' ) );
?>
<p class="description">
	<?php esc_html_e( 'Genera de golpe los documentos de todo el contenido dentro del alcance, y ajusta aquí el límite diario, el tamaño de lote y el debounce de la cola.', 'ai-knowledge' ); ?>
</p>
<p>
	<?php
	printf(
		/* translators: 1: nº elementos, 2: nº idiomas, 3: total documentos */
		esc_html__( 'Alcance actual: %1$d elementos × %2$d idiomas = %3$d documentos posibles.', 'ai-knowledge' ),
		count( $ids ),
		count( $langs ),
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

<?php if ( $running ) : ?>
	<p><strong><?php esc_html_e( 'Generación en curso (procesando por lotes vía Action Scheduler).', 'ai-knowledge' ); ?></strong></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="wookb_cancel_seed" />
		<?php wp_nonce_field( 'wookb_cancel_seed' ); ?>
		<?php submit_button( __( 'Cancelar generación', 'ai-knowledge' ), 'delete' ); ?>
	</form>
<?php else : ?>
	<p class="description"><?php esc_html_e( '"Generar pendientes" es seguro repetirlo: no regenera lo que ya está sincronizado y sin cambios, solo lo nuevo o lo que falló.', 'ai-knowledge' ); ?></p>
	<div class="wookb-toolbar-row">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wookb-toolbar-form">
			<input type="hidden" name="action" value="wookb_start_seed" />
			<?php wp_nonce_field( 'wookb_start_seed' ); ?>
			<?php submit_button( __( 'Generar pendientes', 'ai-knowledge' ), 'primary', 'submit', false ); ?>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wookb-toolbar-form" onsubmit="return confirm('<?php echo esc_js( __( 'Esto va a REGENERAR también el contenido que ya está sincronizado, no solo lo pendiente. Gasta IA de más y no se puede deshacer. ¿Seguro que quieres continuar?', 'ai-knowledge' ) ); ?>');">
			<input type="hidden" name="action" value="wookb_start_seed_force" />
			<?php wp_nonce_field( 'wookb_start_seed_force' ); ?>
			<?php submit_button( __( 'Reiniciar todo', 'ai-knowledge' ), 'secondary', 'submit', false ); ?>
		</form>
	</div>
<?php endif; ?>

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
