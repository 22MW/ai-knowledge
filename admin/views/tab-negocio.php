<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$answers          = Chatbot_Prompt_Builder::get_saved_answers();
$summary_error    = get_transient( 'wookb_business_summary_error' );
delete_transient( 'wookb_business_summary_error' );
$summary_draft    = get_transient( 'wookb_business_summary_draft' );
if ( false === $summary_draft ) {
	$summary_draft = Chatbot_Prompt_Builder::get_business_summary();
}
?>
<p class="description">
	<?php esc_html_e( 'Datos del negocio en sí: válidos con o sin WooCommerce (lo específico de WooCommerce vive en su propia pestaña). Se usan para el prompt del chatbot (pestaña Chatbot, si Genix está activo) y para llms.txt/llm/info.md.', 'ai-knowledge' ); ?>
</p>
<?php Admin::documentation_link( 'negocio' ); ?>
<?php if ( Languages::creates_post_per_language() ) : ?>
	<p class="description"><a href="#wookb-per-language"><?php esc_html_e( 'Ir a «Crear por idioma»', 'ai-knowledge' ); ?></a></p>
<?php endif; ?>

<h2><?php esc_html_e( '1. Datos', 'ai-knowledge' ); ?></h2>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_save_business_answers" />
	<?php wp_nonce_field( 'wookb_save_business_answers' ); ?>

	<table class="form-table">
		<?php foreach ( Chatbot_Prompt_Builder::questions_by_group( 'negocio' ) as $key => $q ) : ?>
			<tr>
				<th><?php echo esc_html( $q['label'] ); ?></th>
				<td>
					<?php if ( 'textarea' === $q['type'] ) : ?>
						<textarea name="answers[<?php echo esc_attr( $key ); ?>]" rows="2" class="large-text"><?php echo esc_textarea( $answers[ $key ] ); ?></textarea>
					<?php else : ?>
						<input type="text" name="answers[<?php echo esc_attr( $key ); ?>]" class="regular-text" value="<?php echo esc_attr( $answers[ $key ] ); ?>" />
					<?php endif; ?>
					<p class="description"><?php echo esc_html( $q['placeholder'] ); ?></p>
				</td>
			</tr>
		<?php endforeach; ?>
		<?php echo Admin::render_language_fields( 'table' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado y escapado dentro del propio metodo. ?>
	</table>
	<?php submit_button( __( 'Guardar', 'ai-knowledge' ) ); ?>
</form>

<hr />

<h2><?php esc_html_e( '2. Resumen para llms.txt (con IA)', 'ai-knowledge' ); ?></h2>
<p class="description">
	<?php esc_html_e( 'Genera o pule un resumen independiente usando todos los datos guardados arriba, incluido "Enfoque del negocio". Se usa como cita de apertura pública en /llms.txt, el archivo que leen los buscadores de IA. Guardar o borrar este resumen no modifica los datos fuente. Si el resultado se queda corto, amplía los datos de arriba, guárdalos primero y vuelve a generar.', 'ai-knowledge' ); ?>
</p>

<?php if ( $summary_error ) : ?>
	<div class="notice notice-error"><p><?php echo esc_html( $summary_error ); ?></p></div>
<?php endif; ?>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_generate_business_summary_draft" />
	<?php wp_nonce_field( 'wookb_generate_business_summary_draft' ); ?>
	<p>
		<label><?php esc_html_e( 'Instrucciones para generar o pulir el texto (opcional)', 'ai-knowledge' ); ?></label><br />
		<textarea name="extra_info" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Indica el tono, formato, orden o estructura que quieres para el resumen.', 'ai-knowledge' ); ?>"></textarea>
		<span class="description"><?php esc_html_e( 'Puedes escribir una indicación breve o pegar un prompt completo. Estas instrucciones prevalecen sobre el formato predeterminado, pero solo pueden usar los datos guardados arriba. Revisa el borrador antes de guardarlo.', 'ai-knowledge' ); ?></span>
	</p>
	<?php submit_button( __( 'Generar/pulir con IA', 'ai-knowledge' ), '' ); ?>
</form>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_save_business_summary" />
	<?php wp_nonce_field( 'wookb_save_business_summary' ); ?>
	<textarea name="summary_draft" rows="6" class="large-text"><?php echo esc_textarea( $summary_draft ); ?></textarea>
	<?php submit_button( __( 'Guardar resumen', 'ai-knowledge' ) ); ?>
</form>

<hr />

<?php if ( Languages::creates_post_per_language() ) : ?>
<h2 id="wookb-per-language"><?php esc_html_e( '3. Crear por idioma', 'ai-knowledge' ); ?></h2>
<p class="description">
	<?php
	printf(
		/* translators: %s: nombre del plugin de idiomas, p. ej. WPML */
		esc_html__( 'Tu plugin de idiomas (%s) crea un contenido distinto por idioma. Desmarcado (recomendado): un solo .md por contenido, en el idioma principal, y todas las traducciones lo enlazan. Marcado: además se crea un .md por cada traducción que exista (sin documentos puente). Genix sigue este ajuste. Al cambiarlo, pulsa «Reiniciar todo» para borrar y regenerar lo que ya no corresponda.', 'ai-knowledge' ),
		esc_html( Languages::provider_label() )
	);
	?>
</p>
<?php Admin::render_language_regen_notice(); ?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_save_per_language" />
	<?php wp_nonce_field( 'wookb_save_per_language' ); ?>
	<p>
		<label>
			<input type="checkbox" name="per_language" value="1" <?php checked( Languages::per_language_enabled() ); ?> />
			<?php esc_html_e( 'Crear un .md por cada traducción que exista', 'ai-knowledge' ); ?>
		</label>
	</p>
	<?php submit_button( __( 'Guardar', 'ai-knowledge' ) ); ?>
</form>
<?php endif; ?>
