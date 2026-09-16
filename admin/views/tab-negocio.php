<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$answers          = Chatbot_Prompt_Builder::get_saved_answers();
$summary_error    = get_transient( 'wookb_business_summary_error' );
delete_transient( 'wookb_business_summary_error' );
$summary_draft    = get_transient( 'wookb_business_summary_draft' );
if ( false === $summary_draft ) {
	$summary_draft = $answers['negocio'];
}
?>
<p class="description">
	<?php esc_html_e( 'Datos del negocio en sí: válidos con o sin WooCommerce (lo específico de WooCommerce vive en su propia pestaña). Se usan para el prompt del chatbot (pestaña Chatbot, si Genix está activo) y para llms.txt/llm/info.md.', 'ai-knowledge' ); ?>
</p>

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
	</table>
	<?php submit_button( __( 'Guardar', 'ai-knowledge' ) ); ?>
</form>

<hr />

<h2><?php esc_html_e( '2. Resumen para llms.txt (con IA)', 'ai-knowledge' ); ?></h2>
<p class="description">
	<?php esc_html_e( 'Genera o pule con IA el texto de "Enfoque del negocio" de arriba, a partir del resto de datos ya guardados (parte del texto actual si ya hay uno, no empieza de cero). Se usa como cita de apertura pública en /llms.txt, el archivo que leen los buscadores de IA. También produce llm/info.md. Si el resultado se queda corto, amplía los datos de arriba (guárdalos primero) y vuelve a generar — se puede repetir todas las veces que haga falta.', 'ai-knowledge' ); ?>
</p>

<?php if ( $summary_error ) : ?>
	<div class="notice notice-error"><p><?php echo esc_html( $summary_error ); ?></p></div>
<?php endif; ?>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_generate_business_summary_draft" />
	<?php wp_nonce_field( 'wookb_generate_business_summary_draft' ); ?>
	<p>
		<label><?php esc_html_e( 'Información extra (opcional)', 'ai-knowledge' ); ?></label><br />
		<textarea name="extra_info" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Pega aquí cualquier instrucción o dato adicional para esta generación.', 'ai-knowledge' ); ?>"></textarea>
	</p>
	<?php submit_button( __( 'Generar/pulir con IA', 'ai-knowledge' ), '' ); ?>
</form>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_save_business_summary" />
	<?php wp_nonce_field( 'wookb_save_business_summary' ); ?>
	<textarea name="summary_draft" rows="6" class="large-text"><?php echo esc_textarea( $summary_draft ); ?></textarea>
	<?php submit_button( __( 'Guardar resumen', 'ai-knowledge' ) ); ?>
</form>
