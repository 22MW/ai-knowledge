<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Defensa: esta vista se carga por nombre de archivo directamente desde
// Admin::render() (no filtra $_GET['tab'] contra la lista de pestañas
// visibles), así que una URL manipulada a mano con tab=woocommerce sin
// WooCommerce activo llegaría aquí igual. Sin WooCommerce no hay nada real
// que mostrar.
if ( ! class_exists( 'WooCommerce' ) ) {
	echo '<p class="description">' . esc_html__( 'Esta pestaña requiere WooCommerce activo.', 'ai-knowledge' ) . '</p>';
	return;
}

$settings     = Scope::settings();
$store_docs_err = get_transient( 'wookb_store_docs_error' );
$langs        = Wpml::active_languages();
$preview_lang = $langs ? $langs[0] : 'es';

$base_country_code = WC()->countries ? WC()->countries->get_base_country() : '';
$countries_list     = WC()->countries ? WC()->countries->get_countries() : array();
$base_country_label = isset( $countries_list[ $base_country_code ] ) ? $countries_list[ $base_country_code ] : $base_country_code;

$terms_page_id  = (int) get_option( 'woocommerce_terms_page_id' );
$refund_page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'refund_returns' ) : 0;

$contact_answers = class_exists( '\WOOKB\Chatbot_Prompt_Builder' ) ? Chatbot_Prompt_Builder::get_saved_answers() : array();
$contact_text     = isset( $contact_answers['contacto'] ) ? trim( (string) $contact_answers['contacto'] ) : '';
?>

<p class="description">
	<?php esc_html_e( 'Información de tu tienda que se genera como documentos propios (envíos, impuestos, pagos, condiciones) para que el chatbot y los buscadores de IA la conozcan. Lo que WooCommerce ya sabe se detecta solo; lo que no, se rellena a mano.', 'ai-knowledge' ); ?>
</p>

<h2><?php esc_html_e( 'Detectado automáticamente', 'ai-knowledge' ); ?></h2>
<p class="description"><?php esc_html_e( 'Datos leídos en tiempo real de la configuración de WooCommerce. Solo lectura: para cambiarlos, edítalos en WooCommerce.', 'ai-knowledge' ); ?></p>

<div class="wookb-wc-detected">
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Nombre de la tienda', 'ai-knowledge' ); ?></th>
			<td><?php echo esc_html( get_bloginfo( 'name' ) ); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Moneda', 'ai-knowledge' ); ?></th>
			<td>
				<?php
				if ( function_exists( 'get_woocommerce_currency' ) ) {
					echo esc_html( get_woocommerce_currency() );
					if ( function_exists( 'get_woocommerce_currency_symbol' ) ) {
						echo ' (' . esc_html( get_woocommerce_currency_symbol() ) . ')';
					}
				}
				?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'País base', 'ai-knowledge' ); ?></th>
			<td><?php echo esc_html( $base_country_label ? $base_country_label : __( '[pendiente]', 'ai-knowledge' ) ); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Envíos: zonas y métodos', 'ai-knowledge' ); ?></th>
			<td>
				<?php
				$zones = class_exists( 'WC_Shipping_Zones' ) ? \WC_Shipping_Zones::get_zones() : array();
				if ( empty( $zones ) ) :
					?>
					<p class="description"><?php esc_html_e( '[pendiente] No hay zonas de envío configuradas todavía.', 'ai-knowledge' ); ?></p>
					<?php
				else :
					foreach ( $zones as $zone ) :
						?>
						<p><strong><?php echo esc_html( $zone['zone_name'] ); ?></strong></p>
						<ul style="margin:0 0 8px 20px;">
							<?php
							$methods = isset( $zone['shipping_methods'] ) ? $zone['shipping_methods'] : array();
							if ( empty( $methods ) ) :
								?>
								<li class="description"><?php esc_html_e( 'Sin métodos de envío.', 'ai-knowledge' ); ?></li>
								<?php
							else :
								foreach ( $methods as $method ) :
									?>
									<li>
										<?php
										echo esc_html( $method->get_title() );
										// $method->enabled es un string 'yes'/'no' (WC_Settings_API), no un
										// booleano -- comparar contra 'no' explicitamente: 'no' como string
										// es "truthy" en PHP y con un ternario simple mostraria (desactivado)
										// al reves.
										echo 'no' === $method->enabled ? ' ' . esc_html__( '(desactivado)', 'ai-knowledge' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba
										?>
									</li>
									<?php
								endforeach;
							endif;
							?>
						</ul>
						<?php
					endforeach;
				endif;
				?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Impuestos / IVA', 'ai-knowledge' ); ?></th>
			<td>
				<?php
				if ( ! function_exists( 'wc_tax_enabled' ) || ! wc_tax_enabled() ) {
					echo '<p class="description">' . esc_html__( '[pendiente] Los impuestos están desactivados en WooCommerce (Ajustes > Impuestos).', 'ai-knowledge' ) . '</p>';
				} else {
					$tax_lines = Store_Info_Doc::tax_summary_lines( $preview_lang );
					if ( empty( $tax_lines ) ) {
						echo '<p class="description">' . esc_html__( '[pendiente] No hay ningún tipo de impuesto dado de alta todavía.', 'ai-knowledge' ) . '</p>';
					} else {
						echo '<ul style="margin:0 0 8px 20px;">';
						foreach ( $tax_lines as $line ) {
							echo '<li>' . esc_html( ltrim( $line, '- ' ) ) . '</li>';
						}
						echo '</ul>';
						if ( function_exists( 'wc_prices_include_tax' ) ) {
							echo '<p class="description">' . ( wc_prices_include_tax()
								? esc_html__( 'Los precios mostrados ya incluyen impuestos.', 'ai-knowledge' )
								: esc_html__( 'Los precios mostrados NO incluyen impuestos.', 'ai-knowledge' ) ) . '</p>';
						}
					}
				}
				?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Métodos de pago habilitados', 'ai-knowledge' ); ?></th>
			<td>
				<?php
				$payment_lines = Store_Info_Doc::payment_summary();
				if ( empty( $payment_lines ) ) {
					echo '<p class="description">' . esc_html__( '[pendiente] No hay pasarelas de pago habilitadas.', 'ai-knowledge' ) . '</p>';
				} else {
					echo '<ul style="margin:0 0 8px 20px;">';
					foreach ( $payment_lines as $line ) {
						echo '<li>' . esc_html( ltrim( $line, '- ' ) ) . '</li>';
					}
					echo '</ul>';
				}
				?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Condiciones de venta', 'ai-knowledge' ); ?></th>
			<td>
				<?php if ( $terms_page_id > 0 ) : ?>
					<a href="<?php echo esc_url( get_edit_post_link( $terms_page_id ) ); ?>" target="_blank"><?php echo esc_html( get_the_title( $terms_page_id ) ); ?></a>
				<?php else : ?>
					<span class="description"><?php esc_html_e( '[pendiente] No hay página de condiciones de venta configurada (Ajustes > Cuentas y privacidad).', 'ai-knowledge' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Política de devoluciones', 'ai-knowledge' ); ?></th>
			<td>
				<?php if ( $refund_page_id > 0 ) : ?>
					<a href="<?php echo esc_url( get_edit_post_link( $refund_page_id ) ); ?>" target="_blank"><?php echo esc_html( get_the_title( $refund_page_id ) ); ?></a>
				<?php else : ?>
					<span class="description"><?php esc_html_e( '[pendiente] No hay página de devoluciones/reembolsos configurada (Ajustes > Cuentas y privacidad).', 'ai-knowledge' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
	</table>
</div>

<hr />

<h2><?php esc_html_e( 'Rellenar a mano', 'ai-knowledge' ); ?></h2>
<p class="description"><?php esc_html_e( 'Datos que WooCommerce no puede saber por sí solo.', 'ai-knowledge' ); ?></p>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wookb-wc-manual">
	<input type="hidden" name="action" value="wookb_save_woocommerce_settings" />
	<?php wp_nonce_field( 'wookb_save_woocommerce_settings' ); ?>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Plazo de entrega', 'ai-knowledge' ); ?></th>
			<td>
				<textarea name="delivery_time_note" rows="3" class="large-text"><?php echo esc_textarea( $settings['delivery_time_note'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'WooCommerce no expone un plazo de entrega real; escríbelo en texto libre (ej. "2-4 días laborables en Península").', 'ai-knowledge' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Contacto y horario', 'ai-knowledge' ); ?></th>
			<td>
				<?php if ( '' !== $contact_text ) : ?>
					<p><?php echo nl2br( esc_html( $contact_text ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba ?></p>
				<?php else : ?>
					<p class="description"><?php esc_html_e( '[pendiente] Todavía no se ha rellenado en el cuestionario de Negocio.', 'ai-knowledge' ); ?></p>
				<?php endif; ?>
				<p class="description">
					<?php
					printf(
						/* translators: %s: enlace a la pestaña Negocio */
						esc_html__( 'Este dato se comparte con el prompt del chatbot: se edita en la pestaña %s.', 'ai-knowledge' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=woo-kb-generator&tab=negocio' ) ) . '">' . esc_html__( 'Negocio', 'ai-knowledge' ) . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba
					);
					?>
				</p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Notas legales adicionales', 'ai-knowledge' ); ?></th>
			<td>
				<textarea name="legal_notes_extra" rows="4" class="large-text"><?php echo esc_textarea( $settings['legal_notes_extra'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Opcional. Cualquier aviso legal adicional que quieras incluir en el documento de información de tienda.', 'ai-knowledge' ); ?></p>
			</td>
		</tr>
	</table>
	<?php submit_button( __( 'Guardar', 'ai-knowledge' ) ); ?>
</form>

<hr />

<h2><?php esc_html_e( 'Generar documentos de tienda', 'ai-knowledge' ); ?></h2>
<p class="description">
	<?php esc_html_e( 'Genera (o regenera) los documentos compuestos de "cómo comprar / condiciones de venta / envío y pago" y "catálogo de tienda", uno por idioma activo, a partir de la configuración real de WooCommerce (incluida esta pestaña). No dependen de ningún post concreto, así que se generan a mano con este botón — no se disparan solos al cambiar los ajustes de WooCommerce.', 'ai-knowledge' ); ?>
</p>
<?php if ( $store_docs_err ) : ?>
	<div class="notice notice-error inline"><p><?php echo esc_html( $store_docs_err ); ?></p></div>
<?php endif; ?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_sync_store_docs" />
	<?php wp_nonce_field( 'wookb_sync_store_docs' ); ?>
	<?php submit_button( __( 'Generar/actualizar ahora', 'ai-knowledge' ), 'secondary' ); ?>
</form>

<hr />

<h2><?php esc_html_e( 'Vista previa (lo que se publica)', 'ai-knowledge' ); ?></h2>
<p class="description"><?php esc_html_e( 'Markdown ya generado, tal cual se sirve. Solo lectura; para cambiarlo, usa el botón de arriba.', 'ai-knowledge' ); ?></p>

<?php foreach ( $langs as $lang ) : ?>
	<h3><?php echo esc_html( strtoupper( $lang ) ); ?></h3>
	<?php
	$store_info_row = Registry::find( Store_Info_Doc::SOURCE_ID_STORE_INFO, $lang );
	$catalog_row    = Registry::find( Store_Info_Doc::SOURCE_ID_SHOP_CATALOG, $lang );
	?>
	<p><strong><?php esc_html_e( 'Información de tienda', 'ai-knowledge' ); ?></strong></p>
	<?php if ( $store_info_row && $store_info_row->md_path ) : ?>
		<textarea readonly rows="10" class="large-text code"><?php echo esc_textarea( Markdown_Store::body_only( Markdown_Store::read( $store_info_row->md_path ) ) ); ?></textarea>
	<?php else : ?>
		<p class="description"><?php esc_html_e( 'Todavía no se ha generado.', 'ai-knowledge' ); ?></p>
	<?php endif; ?>

	<p><strong><?php esc_html_e( 'Catálogo de tienda', 'ai-knowledge' ); ?></strong></p>
	<?php if ( $catalog_row && $catalog_row->md_path ) : ?>
		<textarea readonly rows="10" class="large-text code"><?php echo esc_textarea( Markdown_Store::body_only( Markdown_Store::read( $catalog_row->md_path ) ) ); ?></textarea>
	<?php else : ?>
		<p class="description"><?php esc_html_e( 'Todavía no se ha generado.', 'ai-knowledge' ); ?></p>
	<?php endif; ?>
<?php endforeach; ?>
