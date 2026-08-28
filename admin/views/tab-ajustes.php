<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings        = Scope::settings();
$ai_cfg          = Generator::ai_config();
$store_docs_err  = get_transient( 'wookb_store_docs_error' );
$faq_content     = class_exists( '\WOOKB\Llms_Faq' ) ? Llms_Faq::read() : '';
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_save_settings" />
	<?php wp_nonce_field( 'wookb_save_settings' ); ?>

	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Límite diario de generaciones', 'woo-kb-generator' ); ?></th>
			<td>
				<input type="number" min="1" name="daily_limit" value="<?php echo esc_attr( $settings['daily_limit'] ); ?>" />
				<label style="margin-left:12px;">
					<input type="checkbox" name="no_limit" value="1" <?php checked( ! empty( $settings['no_limit'] ) ); ?> />
					<?php esc_html_e( 'Sin límite (usar solo en cargas manuales supervisadas — recuerda desactivarlo al terminar)', 'woo-kb-generator' ); ?>
				</label>
				<?php if ( ! empty( $settings['no_limit'] ) ) : ?>
					<p style="color:#b32d2e;font-weight:600;"><?php esc_html_e( 'Aviso: el límite diario está DESACTIVADO.', 'woo-kb-generator' ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Tamaño de lote (carga inicial)', 'woo-kb-generator' ); ?></th>
			<td><input type="number" min="1" name="batch_size" value="<?php echo esc_attr( $settings['batch_size'] ); ?>" /></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Retraso de debounce (segundos)', 'woo-kb-generator' ); ?></th>
			<td><input type="number" min="0" name="debounce_seconds" value="<?php echo esc_attr( $settings['debounce_seconds'] ); ?>" /></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Tokens de salida por documento', 'woo-kb-generator' ); ?></th>
			<td><input type="number" min="200" name="output_tokens" value="<?php echo esc_attr( $settings['output_tokens'] ); ?>" /></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Origen de la clave IA', 'woo-kb-generator' ); ?></th>
			<td>
				<label><input type="radio" name="ai_key_source" value="genix" <?php checked( 'genix' === $settings['ai_key_source'] ); ?> /> <?php esc_html_e( 'Reutilizar la de Support Genix', 'woo-kb-generator' ); ?></label><br />
				<label><input type="radio" name="ai_key_source" value="own" <?php checked( 'own' === $settings['ai_key_source'] ); ?> /> <?php esc_html_e( 'Clave propia', 'woo-kb-generator' ); ?></label>
				<p class="description">
					<?php
					if ( $ai_cfg ) {
						printf(
							/* translators: %1$s clave enmascarada, %2$s modelo, %3$s origen */
							esc_html__( 'Config. activa: %1$s (modelo %2$s, origen %3$s)', 'woo-kb-generator' ),
							esc_html( substr( $ai_cfg['api_key'], 0, 4 ) . '…' . substr( $ai_cfg['api_key'], -4 ) ),
							esc_html( $ai_cfg['model'] ),
							esc_html( $ai_cfg['source'] )
						);
					} else {
						esc_html_e( 'No hay clave IA configurada todavía.', 'woo-kb-generator' );
					}
					?>
				</p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Clave propia (fallback)', 'woo-kb-generator' ); ?></th>
			<td>
				<input type="password" name="own_api_key" class="regular-text" value="<?php echo esc_attr( $settings['own_api_key'] ); ?>" autocomplete="off" />
				<p class="description"><?php esc_html_e( 'Se guarda en claro, igual que la clave de Support Genix. Límite conocido y documentado.', 'woo-kb-generator' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Modelo (clave propia)', 'woo-kb-generator' ); ?></th>
			<td><input type="text" name="own_model" class="regular-text" value="<?php echo esc_attr( $settings['own_model'] ); ?>" /></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Instrucciones adicionales del prompt', 'woo-kb-generator' ); ?></th>
			<td><textarea name="extra_prompt" rows="4" class="large-text"><?php echo esc_textarea( $settings['extra_prompt'] ); ?></textarea></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Límite de "Documentos relacionados" en el chat', 'woo-kb-generator' ); ?></th>
			<td>
				<input type="number" min="0" name="chatbot_docs_list_limit" value="<?php echo esc_attr( $settings['chatbot_docs_list_limit'] ); ?>" />
				<p class="description"><?php esc_html_e( 'Máximo de enlaces mostrados bajo la respuesta del chatbot (Fase 2, pieza 3). 0 = sin límite.', 'woo-kb-generator' ); ?></p>
			</td>
		</tr>
	</table>
	<?php submit_button( __( 'Guardar ajustes', 'woo-kb-generator' ) ); ?>
</form>

<hr />

<p class="description">
	<?php
	printf(
		/* translators: %s: enlace a la pestaña Prompt */
		esc_html__( 'El prompt de sistema del chatbot se gestiona en la pestaña %s.', 'woo-kb-generator' ),
		'<a href="' . esc_url( admin_url( 'admin.php?page=woo-kb-generator&tab=prompt' ) ) . '">' . esc_html__( 'Prompt', 'woo-kb-generator' ) . '</a>'
	);
	?>
</p>

<hr />

<h2><?php esc_html_e( 'Documentos de información de tienda', 'woo-kb-generator' ); ?></h2>
<p class="description">
	<?php esc_html_e( 'Genera (o regenera) los documentos compuestos de "cómo comprar / condiciones de venta / envío y pago" y "catálogo de tienda", uno por idioma activo, a partir de la configuración real de WooCommerce. No dependen de ningún post concreto, así que se generan a mano con este botón — no se disparan solos al cambiar los ajustes de WooCommerce.', 'woo-kb-generator' ); ?>
</p>
<?php if ( $store_docs_err ) : ?>
	<div class="notice notice-error inline"><p><?php echo esc_html( $store_docs_err ); ?></p></div>
<?php endif; ?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_sync_store_docs" />
	<?php wp_nonce_field( 'wookb_sync_store_docs' ); ?>
	<?php submit_button( __( 'Generar/actualizar ahora', 'woo-kb-generator' ), 'secondary' ); ?>
</form>

<hr />

<h2><?php esc_html_e( 'FAQ pública (llms.txt)', 'woo-kb-generator' ); ?></h2>
<p class="description">
	<?php esc_html_e( 'Contenido libre en Markdown que se publica bajo el encabezado "## Preguntas frecuentes" de /llms.txt, visible para crawlers de IA. Distinto del prompt del chatbot: esto es contenido público, no instrucciones internas.', 'woo-kb-generator' ); ?>
</p>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_save_llms_faq" />
	<?php wp_nonce_field( 'wookb_save_llms_faq' ); ?>
	<textarea name="llms_faq" rows="10" class="large-text code" placeholder="### ¿Puedo devolver un producto?&#10;Respuesta..."><?php echo esc_textarea( $faq_content ); ?></textarea>
	<?php submit_button( __( 'Guardar FAQ', 'woo-kb-generator' ) ); ?>
</form>
