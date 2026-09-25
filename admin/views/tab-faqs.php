<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// La FAQ es contenido del propio plugin: solo se genera en el idioma principal.
$active_langs = array( Languages::main_language() );
$lang         = $active_langs[0];

$faq_error = get_transient( 'wookb_faqs_error_' . $lang );
delete_transient( 'wookb_faqs_error_' . $lang );
$faq_content = get_transient( 'wookb_faqs_draft_' . $lang );
if ( false === $faq_content ) {
	$faq_content = class_exists( '\AIKB\Llms_Faq' ) ? Llms_Faq::read( $lang ) : '';
}
?>
<p class="description">
	<?php esc_html_e( 'Contenido en Markdown que se publica como documento propio (enlazado desde /llms.txt, igual que el resto de documentos) y aparece en la pestaña Registro. Distinto del prompt del chatbot: esto es contenido público, no instrucciones internas.', 'ai-knowledge' ); ?>
</p>
<?php Admin::documentation_link( 'faqs' ); ?>

<?php if ( $faq_error ) : ?>
	<div class="notice notice-error"><p><?php echo esc_html( $faq_error ); ?></p></div>
<?php endif; ?>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_generate_faqs_draft" />
	<input type="hidden" name="lang" value="<?php echo esc_attr( $lang ); ?>" />
	<?php wp_nonce_field( 'wookb_generate_faqs_draft' ); ?>
	<p>
		<label><?php esc_html_e( 'Instrucciones para generar o ampliar las FAQs (opcional)', 'ai-knowledge' ); ?></label><br />
		<textarea name="extra_info" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Indica el tono, número de preguntas, formato, orden o estructura que quieres.', 'ai-knowledge' ); ?>"></textarea>
		<span class="description"><?php esc_html_e( 'Puedes escribir una indicación breve o pegar un prompt completo. Estas instrucciones prevalecen sobre el formato predeterminado, pero solo pueden usar los datos de Negocio y la FAQ actual. Revisa el borrador antes de guardarlo.', 'ai-knowledge' ); ?></span>
	</p>
	<?php submit_button( __( 'Generar/ampliar con IA', 'ai-knowledge' ), '' ); ?>
	<p class="description"><?php esc_html_e( 'Usa los datos guardados en la pestaña Negocio. Si ya hay FAQ guardada (en este idioma), la amplía o mejora en vez de partir de cero — puedes repetir esto todas las veces que haga falta: amplía los datos de Negocio (guárdalos primero) o edita la FAQ a mano abajo, guarda, y vuelve a generar para mejorar el resultado.', 'ai-knowledge' ); ?></p>
</form>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_save_llms_faq" />
	<input type="hidden" name="lang" value="<?php echo esc_attr( $lang ); ?>" />
	<?php wp_nonce_field( 'wookb_save_llms_faq' ); ?>
	<textarea name="llms_faq" rows="10" class="large-text code" placeholder="### ¿Puedo devolver un producto?&#10;Respuesta..."><?php echo esc_textarea( $faq_content ); ?></textarea>
	<?php submit_button( __( 'Guardar FAQ', 'ai-knowledge' ) ); ?>
</form>
