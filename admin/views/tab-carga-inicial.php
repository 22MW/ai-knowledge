<?php
namespace WOOKB;

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
<p>
	<?php
	printf(
		/* translators: 1: nº elementos, 2: nº idiomas, 3: total documentos */
		esc_html__( 'Alcance actual: %1$d elementos × %2$d idiomas = %3$d documentos posibles.', 'woo-kb-generator' ),
		count( $ids ),
		count( $langs ),
		$total
	);
	?>
</p>
<p><?php printf( esc_html__( 'Documentos ya sincronizados: %d.', 'woo-kb-generator' ), (int) $counted ); ?></p>
<?php if ( ! empty( $settings['no_limit'] ) ) : ?>
	<p style="color:#b32d2e;font-weight:600;"><?php esc_html_e( 'El límite diario está desactivado — la carga avanzará sin tope.', 'woo-kb-generator' ); ?></p>
<?php else : ?>
	<p><?php printf( esc_html__( 'Límite diario actual: %d generaciones/día.', 'woo-kb-generator' ), (int) $settings['daily_limit'] ); ?></p>
<?php endif; ?>

<?php if ( $running ) : ?>
	<p><strong><?php esc_html_e( 'Carga inicial en curso (procesando por lotes vía Action Scheduler).', 'woo-kb-generator' ); ?></strong></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="wookb_cancel_seed" />
		<?php wp_nonce_field( 'wookb_cancel_seed' ); ?>
		<?php submit_button( __( 'Cancelar carga inicial', 'woo-kb-generator' ), 'delete' ); ?>
	</form>
<?php else : ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Esto encolará la generación de todos los documentos del alcance actual. ¿Continuar?', 'woo-kb-generator' ) ); ?>');">
		<input type="hidden" name="action" value="wookb_start_seed" />
		<?php wp_nonce_field( 'wookb_start_seed' ); ?>
		<?php submit_button( __( 'Iniciar carga inicial', 'woo-kb-generator' ), 'primary' ); ?>
	</form>
<?php endif; ?>

<p class="description">
	<?php esc_html_e( 'Revisa la pestaña Registro para ver el progreso, o Herramientas → Scheduled Actions (grupo woo-kb) para el detalle técnico.', 'woo-kb-generator' ); ?>
</p>
