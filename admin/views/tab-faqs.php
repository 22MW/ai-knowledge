<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active_langs = Wpml::active_languages();
$lang         = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : ''; // phpcs:ignore
if ( ! $lang || ! in_array( $lang, $active_langs, true ) ) {
	$lang = $active_langs ? $active_langs[0] : 'es';
}

$faq_error = get_transient( 'wookb_faqs_error_' . $lang );
delete_transient( 'wookb_faqs_error_' . $lang );
$faq_content = get_transient( 'wookb_faqs_draft_' . $lang );
if ( false === $faq_content ) {
	$faq_content = class_exists( '\WOOKB\Llms_Faq' ) ? Llms_Faq::read( $lang ) : '';
}
?>
<p class="description">
	<?php esc_html_e( 'Contenido en Markdown que se publica como documento propio (enlazado desde /llms.txt, igual que el resto de documentos) y aparece en la pestaña Registro. Distinto del prompt del chatbot: esto es contenido público, no instrucciones internas.', 'ai-knowledge' ); ?>
</p>

<?php if ( count( $active_langs ) > 1 ) : ?>
	<form method="get" class="wookb-toolbar-form">
		<input type="hidden" name="page" value="woo-kb-generator" />
		<input type="hidden" name="tab" value="faqs" />
		<select name="lang" onchange="this.form.submit()">
			<?php foreach ( $active_langs as $l ) : ?>
				<option value="<?php echo esc_attr( $l ); ?>" <?php selected( $l, $lang ); ?>><?php echo esc_html( strtoupper( $l ) ); ?></option>
			<?php endforeach; ?>
		</select>
		<noscript><?php submit_button( __( 'Cambiar', 'ai-knowledge' ), '', '', false ); ?></noscript>
	</form>
	<p class="description"><?php esc_html_e( 'Sitio multiidioma: una FAQ (y un documento) por idioma, igual que el resto del contenido.', 'ai-knowledge' ); ?></p>
<?php endif; ?>

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
