<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$answers = Chatbot_Prompt_Builder::get_saved_answers();
$draft_error = get_transient( 'wookb_prompt_draft_error' );
delete_transient( 'wookb_prompt_draft_error' );
$draft = get_transient( 'wookb_prompt_draft' );
if ( false === $draft ) {
	$draft = Chatbot_Prompt::read();
}
?>
<p class="description">
	<?php esc_html_e( 'Responde lo que sepas, opcionalmente añade páginas del sitio como referencia, y genera un primer borrador con IA. Puedes editarlo a mano antes de guardar, y pulirlo con IA después de tus cambios.', 'woo-kb-generator' ); ?>
</p>

<?php if ( $draft_error ) : ?>
	<div class="notice notice-error"><p><?php echo esc_html( $draft_error ); ?></p></div>
<?php endif; ?>

<h2><?php esc_html_e( '1. Cuestionario', 'woo-kb-generator' ); ?></h2>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_generate_prompt_draft" />
	<?php wp_nonce_field( 'wookb_generate_prompt_draft' ); ?>

	<table class="form-table">
		<?php foreach ( Chatbot_Prompt_Builder::questions() as $key => $q ) : ?>
			<tr>
				<th><?php echo esc_html( $q['label'] ); ?></th>
				<td>
					<?php if ( 'textarea' === $q['type'] ) : ?>
						<textarea name="answers[<?php echo esc_attr( $key ); ?>]" rows="2" class="large-text" placeholder="<?php echo esc_attr( $q['placeholder'] ); ?>"><?php echo esc_textarea( $answers[ $key ] ); ?></textarea>
					<?php else : ?>
						<input type="text" name="answers[<?php echo esc_attr( $key ); ?>]" class="regular-text" placeholder="<?php echo esc_attr( $q['placeholder'] ); ?>" value="<?php echo esc_attr( $answers[ $key ] ); ?>" />
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		<tr>
			<th><?php esc_html_e( 'Páginas de referencia (opcional)', 'woo-kb-generator' ); ?></th>
			<td>
				<input type="text" name="reference_pages" class="large-text" placeholder="<?php esc_attr_e( 'IDs o URLs separados por coma, ej: 12, https://.../sobre-nosotros/', 'woo-kb-generator' ); ?>" />
				<p class="description"><?php esc_html_e( 'Hasta 3. Se lee el contenido real de esas páginas/posts para dar más contexto a la IA (tono, datos concretos).', 'woo-kb-generator' ); ?></p>
			</td>
		</tr>
	</table>
	<?php submit_button( __( 'Generar borrador con IA', 'woo-kb-generator' ), 'primary' ); ?>
</form>

<hr />

<h2><?php esc_html_e( '2. Borrador editable', 'woo-kb-generator' ); ?></h2>
<p class="description"><?php esc_html_e( 'Edita libremente. Cuando termines, puedes pulir la redacción con IA (sin cambiar el fondo) o guardar directamente.', 'woo-kb-generator' ); ?></p>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="wookb-prompt-draft-form">
	<textarea name="draft" id="wookb-prompt-draft" rows="16" class="large-text code"><?php echo esc_textarea( $draft ); ?></textarea>

	<p>
		<button type="submit" formaction="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wookb_normalize_prompt' ), 'wookb_normalize_prompt' ) ); ?>" class="button">
			<?php esc_html_e( 'Pulir redacción con IA', 'woo-kb-generator' ); ?>
		</button>
		<button type="submit" formaction="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wookb_save_prompt_draft' ), 'wookb_save_prompt_draft' ) ); ?>" class="button button-primary">
			<?php esc_html_e( 'Guardar y sincronizar con Genix', 'woo-kb-generator' ); ?>
		</button>
	</p>
</form>

<?php
$genix_ready = Chatbot_Prompt::is_genix_ready();
if ( ! $genix_ready ) :
	?>
	<p style="color:#b32d2e;"><?php esc_html_e( 'Support Genix no está activo o su módulo de Knowledge Base todavía no se ha cargado. No se puede sincronizar ahora mismo.', 'woo-kb-generator' ); ?></p>
<?php else : ?>
	<p style="color:#008a20;"><?php esc_html_e( 'Support Genix detectado. Listo para sincronizar.', 'woo-kb-generator' ); ?></p>
<?php endif; ?>
