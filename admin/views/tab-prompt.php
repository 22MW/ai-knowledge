<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Defensa: esta vista se carga por nombre de archivo directamente desde
// Admin::render() (no filtra $_GET['tab'] contra la lista de pestañas
// visibles), asi que una URL manipulada a mano con tab=prompt sin Genix
// activo llegaria aqui igual. chatbot-system-prompt.md solo lo consume
// Genix (Chatbot_Prompt::sync()) -- sin Genix no hay nada real que hacer
// aqui, mismo patron que tab-woocommerce.php.
if ( ! Chatbot_Prompt::is_genix_ready() ) {
	echo '<p class="description">' . esc_html__( 'Esta pestaña requiere Support Genix activo.', 'ai-knowledge' ) . '</p>';
	return;
}

$settings    = Scope::settings();
$answers     = Chatbot_Prompt_Builder::get_saved_answers();
$draft_error = get_transient( 'wookb_prompt_draft_error' );
delete_transient( 'wookb_prompt_draft_error' );
$draft = get_transient( 'wookb_prompt_draft' );
if ( false === $draft ) {
	$draft = Chatbot_Prompt::read();
}
?>
<p class="description">
	<?php esc_html_e( 'Cómo debe comportarse el chatbot de Support Genix: tono, qué hacer cuando no sabe algo, qué no debe hacer nunca. Los datos del negocio en sí (dirección, contacto, horario...) se rellenan en la pestaña Negocio — este cuestionario los combina automáticamente con estos al generar el borrador. Responde lo que sepas, opcionalmente añade páginas del sitio como referencia, y genera un primer borrador con IA. Puedes editarlo a mano antes de guardar, pulirlo con IA después de tus cambios, o añadir más respuestas al cuestionario y volver a generar para mejorar el resultado — no hace falta acertar a la primera.', 'ai-knowledge' ); ?>
</p>

<?php if ( $draft_error ) : ?>
	<div class="notice notice-error"><p><?php echo esc_html( $draft_error ); ?></p></div>
<?php endif; ?>

<h2><?php esc_html_e( '1. Ajustes del chat', 'ai-knowledge' ); ?></h2>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_save_chatbot_settings" />
	<?php wp_nonce_field( 'wookb_save_chatbot_settings' ); ?>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Límite de "Documentos relacionados"', 'ai-knowledge' ); ?></th>
			<td>
				<input type="number" min="0" name="chatbot_docs_list_limit" value="<?php echo esc_attr( $settings['chatbot_docs_list_limit'] ); ?>" />
				<p class="description"><?php esc_html_e( 'Máximo de enlaces mostrados bajo la respuesta del chatbot. 0 = sin límite.', 'ai-knowledge' ); ?></p>
			</td>
		</tr>
	</table>
	<?php submit_button( __( 'Guardar ajustes del chat', 'ai-knowledge' ) ); ?>
</form>

<hr />

<h2><?php esc_html_e( '2. Cuestionario', 'ai-knowledge' ); ?></h2>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_generate_prompt_draft" />
	<?php wp_nonce_field( 'wookb_generate_prompt_draft' ); ?>

	<table class="form-table">
		<?php foreach ( Chatbot_Prompt_Builder::questions_by_group( 'chatbot' ) as $key => $q ) : ?>
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
		<tr>
			<th><?php esc_html_e( 'Páginas de referencia (opcional)', 'ai-knowledge' ); ?></th>
			<td>
				<input type="text" name="reference_pages" class="large-text" placeholder="<?php esc_attr_e( 'IDs o URLs separados por coma, ej: 12, https://.../sobre-nosotros/', 'ai-knowledge' ); ?>" />
				<p class="description"><?php esc_html_e( 'Hasta 3. Se lee el contenido real de esas páginas/posts para dar más contexto a la IA (tono, datos concretos).', 'ai-knowledge' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Información extra (opcional)', 'ai-knowledge' ); ?></th>
			<td>
				<textarea name="extra_info" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Pega aquí cualquier instrucción o dato adicional para esta generación.', 'ai-knowledge' ); ?>"></textarea>
			</td>
		</tr>
	</table>
	<?php submit_button( __( 'Generar borrador con IA', 'ai-knowledge' ), 'primary' ); ?>
</form>

<hr />

<h2><?php esc_html_e( '3. Borrador editable', 'ai-knowledge' ); ?></h2>
<p class="description"><?php esc_html_e( 'Edita libremente. Cuando termines, puedes pulir la redacción con IA (sin cambiar el fondo) o guardar directamente.', 'ai-knowledge' ); ?></p>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="wookb-prompt-draft-form">
	<textarea name="draft" id="wookb-prompt-draft" rows="16" class="large-text code"><?php echo esc_textarea( $draft ); ?></textarea>

	<p>
		<button type="submit" formaction="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wookb_normalize_prompt' ), 'wookb_normalize_prompt' ) ); ?>" class="button">
			<?php esc_html_e( 'Pulir redacción con IA', 'ai-knowledge' ); ?>
		</button>
		<button type="submit" formaction="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wookb_save_prompt_draft' ), 'wookb_save_prompt_draft' ) ); ?>" class="button button-primary">
			<?php esc_html_e( 'Guardar y sincronizar con Genix', 'ai-knowledge' ); ?>
		</button>
	</p>
</form>

<p style="color:#008a20;"><?php esc_html_e( 'Support Genix detectado. Listo para sincronizar.', 'ai-knowledge' ); ?></p>
