<?php
namespace AIKB;

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
// Los documentos de tienda solo se generan en el idioma principal (ver Store_Info_Doc::generate_all()).
$langs        = array( Languages::main_language() );

$base_country_code = WC()->countries ? WC()->countries->get_base_country() : '';
$countries_list     = WC()->countries ? WC()->countries->get_countries() : array();
$base_country_label = isset( $countries_list[ $base_country_code ] ) ? $countries_list[ $base_country_code ] : $base_country_code;

$terms_page_id  = (int) get_option( 'woocommerce_terms_page_id' );
$refund_page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'refund_returns' ) : 0;

$contact_answers = class_exists( '\AIKB\Chatbot_Prompt_Builder' ) ? Chatbot_Prompt_Builder::get_saved_answers() : array();
$contact_text     = isset( $contact_answers['contacto'] ) ? trim( (string) $contact_answers['contacto'] ) : '';

// Valores en vivo de WooCommerce, usados solo como precarga de los campos
// editables de abajo cuando todavia no hay nada guardado -- ver Scope::settings()
// (wc_store_name/wc_currency/wc_base_country/wc_terms_text/wc_returns_text):
// una vez guardado, el campo ya no vuelve a leer esto, vive con lo guardado.
$live_currency = '';
if ( function_exists( 'get_woocommerce_currency' ) ) {
	$live_currency = get_woocommerce_currency();
	if ( function_exists( 'get_woocommerce_currency_symbol' ) ) {
		$live_currency .= ' (' . get_woocommerce_currency_symbol() . ')';
	}
}
$live_terms_text = '';
if ( $terms_page_id > 0 ) {
	$terms_post = get_post( $terms_page_id );
	if ( $terms_post && 'publish' === $terms_post->post_status ) {
		$live_terms_text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $terms_post->post_content ) ) );
	}
}
$live_returns_text = '';
if ( $refund_page_id > 0 ) {
	$refund_post = get_post( $refund_page_id );
	if ( $refund_post && 'publish' === $refund_post->post_status ) {
		$live_returns_text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $refund_post->post_content ) ) );
	}
}

$store_name_value = '' !== $settings['wc_store_name'] ? $settings['wc_store_name'] : get_bloginfo( 'name' );
$currency_value    = '' !== $settings['wc_currency'] ? $settings['wc_currency'] : $live_currency;
$country_value     = '' !== $settings['wc_base_country'] ? $settings['wc_base_country'] : $base_country_label;
$terms_value       = '' !== $settings['wc_terms_text'] ? $settings['wc_terms_text'] : $live_terms_text;
$returns_value     = '' !== $settings['wc_returns_text'] ? $settings['wc_returns_text'] : $live_returns_text;
?>

<p class="description">
	<?php esc_html_e( 'Información de tu tienda que se genera como documentos propios (envíos, impuestos, pagos, condiciones, catálogo) para que el chatbot y los buscadores de IA la conozcan. WooCommerce te lo detecta solo, pero todos los campos se guardan y son editables: lo que se guarda una vez ya no desaparece aunque cambies o borres algo en WooCommerce.', 'ai-knowledge' ); ?>
</p>
<?php Admin::documentation_link( 'woocommerce' ); ?>

<hr />

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_save_woocommerce_settings" />
	<?php wp_nonce_field( 'wookb_save_woocommerce_settings' ); ?>

	<h2><?php esc_html_e( 'Datos generales', 'ai-knowledge' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Precargados con el valor real de WooCommerce; edítalos si hace falta. Una vez guardados, no se pierden aunque cambies o borres algo en WooCommerce.', 'ai-knowledge' ); ?></p>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Nombre de la tienda', 'ai-knowledge' ); ?></th>
			<td><input type="text" name="wc_store_name" class="regular-text" value="<?php echo esc_attr( $store_name_value ); ?>" /></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Moneda', 'ai-knowledge' ); ?></th>
			<td><input type="text" name="wc_currency" class="regular-text" value="<?php echo esc_attr( $currency_value ); ?>" /></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'País base', 'ai-knowledge' ); ?></th>
			<td><input type="text" name="wc_base_country" class="regular-text" value="<?php echo esc_attr( $country_value ); ?>" /></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Condiciones de venta', 'ai-knowledge' ); ?></th>
			<td>
				<textarea name="wc_terms_text" rows="3" class="large-text"><?php echo esc_textarea( $terms_value ); ?></textarea>
				<?php if ( $terms_page_id > 0 ) : ?>
					<p class="description"><a href="<?php echo esc_url( get_edit_post_link( $terms_page_id ) ); ?>" target="_blank"><?php esc_html_e( 'Editar la página real en WordPress', 'ai-knowledge' ); ?></a></p>
				<?php else : ?>
					<p class="description"><?php esc_html_e( '[pendiente] No hay página de condiciones de venta configurada en WooCommerce (Ajustes > Cuentas y privacidad) — precarga vacía, escríbelo a mano si hace falta.', 'ai-knowledge' ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Política de devoluciones', 'ai-knowledge' ); ?></th>
			<td>
				<textarea name="wc_returns_text" rows="3" class="large-text"><?php echo esc_textarea( $returns_value ); ?></textarea>
				<?php if ( $refund_page_id > 0 ) : ?>
					<p class="description"><a href="<?php echo esc_url( get_edit_post_link( $refund_page_id ) ); ?>" target="_blank"><?php esc_html_e( 'Editar la página real en WordPress', 'ai-knowledge' ); ?></a></p>
				<?php else : ?>
					<p class="description"><?php esc_html_e( '[pendiente] No hay página de devoluciones/reembolsos configurada en WooCommerce (Ajustes > Cuentas y privacidad) — precarga vacía, escríbelo a mano si hace falta.', 'ai-knowledge' ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Métodos de pago', 'ai-knowledge' ); ?></th>
			<td>
				<?php
				$payment_selection = $settings['wc_payment_methods'];
				$gateways           = function_exists( 'WC' ) && WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
				if ( empty( $gateways ) ) :
					?>
					<p class="description"><?php esc_html_e( '[pendiente] No hay pasarelas de pago instaladas todavía.', 'ai-knowledge' ); ?></p>
				<?php else : ?>
					<div class="wookb-chip-group">
					<?php foreach ( $gateways as $gateway ) : ?>
						<?php
						$disabled   = 'yes' !== $gateway->enabled;
						$is_checked = null === $payment_selection ? ! $disabled : isset( $payment_selection[ $gateway->id ] );
						?>
						<label class="wookb-chip">
							<input type="checkbox" name="wc_payment_methods[]" value="<?php echo esc_attr( $gateway->id ); ?>" <?php checked( $is_checked ); ?> />
							<?php echo esc_html( $gateway->get_title() ); ?>
							<?php echo $disabled ? ' ' . esc_html__( '(desactivado)', 'ai-knowledge' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba ?>
						</label>
					<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<p class="description"><?php esc_html_e( 'Si nunca has guardado esta pestaña, salen todas marcadas por defecto; si guardas sin marcar ninguna, no entra ninguna.', 'ai-knowledge' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Envíos', 'ai-knowledge' ); ?></th>
			<td>
				<?php
				$shipping_selection = $settings['wc_shipping_methods'];
				$zones              = class_exists( 'WC_Shipping_Zones' ) ? \WC_Shipping_Zones::get_zones() : array();
				if ( empty( $zones ) ) :
					?>
					<p class="description"><?php esc_html_e( '[pendiente] No hay zonas de envío configuradas todavía.', 'ai-knowledge' ); ?></p>
				<?php else : ?>
					<div class="wookb-chip-group">
					<?php foreach ( $zones as $zone ) : ?>
						<?php foreach ( (array) $zone['shipping_methods'] as $method ) : ?>
							<?php
							$disabled   = 'no' === $method->enabled;
							$is_checked = null === $shipping_selection ? ! $disabled : isset( $shipping_selection[ $method->instance_id ] );
							?>
							<label class="wookb-chip">
								<input type="checkbox" name="wc_shipping_methods[]" value="<?php echo esc_attr( $method->instance_id ); ?>" <?php checked( $is_checked ); ?> />
								<?php echo esc_html( $zone['zone_name'] . ': ' . $method->get_title() ); ?>
								<?php echo $disabled ? ' ' . esc_html__( '(desactivado)', 'ai-knowledge' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba ?>
							</label>
						<?php endforeach; ?>
					<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<p class="description"><?php esc_html_e( 'Si nunca has guardado esta pestaña, salen todos marcados por defecto; si guardas sin marcar ninguno, no entra ninguno.', 'ai-knowledge' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Impuestos / IVA', 'ai-knowledge' ); ?></th>
			<td>
				<?php
				if ( ! function_exists( 'wc_tax_enabled' ) || ! wc_tax_enabled() ) :
					?>
					<p class="description"><?php esc_html_e( '[pendiente] Los impuestos están desactivados en WooCommerce (Ajustes > Impuestos).', 'ai-knowledge' ); ?></p>
				<?php else : ?>
					<?php
					$tax_selection = $settings['wc_tax_rates'];
					$tax_classes   = class_exists( 'WC_Tax' ) ? array_merge( array( (object) array( 'slug' => '', 'name' => 'Standard' ) ), \WC_Tax::get_tax_rate_classes() ) : array();
					$has_any_rate  = false;
					?>
					<div class="wookb-chip-group">
					<?php foreach ( $tax_classes as $class_obj ) : ?>
						<?php
						$rates = class_exists( 'WC_Tax' ) ? \WC_Tax::get_rates_for_tax_class( $class_obj->slug ) : array();
						if ( empty( $rates ) || is_wp_error( $rates ) ) {
							continue;
						}
						foreach ( $rates as $rate ) :
							$has_any_rate = true;
							$rate_id      = (int) $rate->tax_rate_id;
							$is_checked   = null === $tax_selection || isset( $tax_selection[ $rate_id ] );
							$country      = ! empty( $rate->tax_rate_country ) ? $rate->tax_rate_country : __( 'Todos los países', 'ai-knowledge' );
							?>
							<label class="wookb-chip">
								<input type="checkbox" name="wc_tax_rates[]" value="<?php echo esc_attr( $rate_id ); ?>" <?php checked( $is_checked ); ?> />
								<?php echo esc_html( $class_obj->name . ' (' . $country . '): ' . rtrim( rtrim( number_format( (float) $rate->tax_rate, 4, '.', '' ), '0' ), '.' ) . '%' ); ?>
							</label>
						<?php endforeach; ?>
					<?php endforeach; ?>
					</div>
					<?php if ( ! $has_any_rate ) : ?>
						<p class="description"><?php esc_html_e( '[pendiente] No hay ningún tipo de impuesto dado de alta todavía.', 'ai-knowledge' ); ?></p>
					<?php endif; ?>
					<?php if ( function_exists( 'wc_prices_include_tax' ) ) : ?>
						<p class="description"><?php echo wc_prices_include_tax()
							? esc_html__( 'Los precios mostrados ya incluyen impuestos.', 'ai-knowledge' )
							: esc_html__( 'Los precios mostrados NO incluyen impuestos.', 'ai-knowledge' ); ?></p>
					<?php endif; ?>
					<p class="description"><?php esc_html_e( 'Si nunca has guardado esta pestaña, salen todos marcados por defecto; si guardas sin marcar ninguno, no entra ninguno.', 'ai-knowledge' ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Catálogo: categorías a incluir', 'ai-knowledge' ); ?></th>
			<td>
				<?php
				$catalog_selection = $settings['wc_catalog_categories'];
				$product_cats       = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
				if ( is_wp_error( $product_cats ) || empty( $product_cats ) ) :
					?>
					<p class="description"><?php esc_html_e( '[pendiente] No hay categorías de producto con productos publicados todavía.', 'ai-knowledge' ); ?></p>
				<?php else : ?>
					<div class="wookb-chip-group">
					<?php foreach ( $product_cats as $cat ) : ?>
						<?php $is_checked = null === $catalog_selection || isset( $catalog_selection[ $cat->term_id ] ); ?>
						<label class="wookb-chip">
							<input type="checkbox" name="wc_catalog_categories[]" value="<?php echo esc_attr( $cat->term_id ); ?>" <?php checked( $is_checked ); ?> />
							<?php echo esc_html( $cat->name ); ?>
						</label>
					<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<p class="description"><?php esc_html_e( 'Si nunca has guardado esta pestaña, salen todas marcadas por defecto; si guardas sin marcar ninguna, no entra ninguna.', 'ai-knowledge' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Pedido mínimo / envío gratis', 'ai-knowledge' ); ?></th>
			<td>
				<textarea name="wc_min_order_note" rows="2" class="large-text"><?php echo esc_textarea( $settings['wc_min_order_note'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Solo se usa si ningún método de envío marcado arriba es de tipo "Envío gratis" con importe mínimo configurado en WooCommerce (si lo es, se detecta solo). Ej: "Envío gratis a partir de 50€".', 'ai-knowledge' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Recogida en tienda', 'ai-knowledge' ); ?></th>
			<td>
				<label class="wookb-chip">
					<input type="checkbox" name="wc_pickup_available" value="1" <?php checked( ! empty( $settings['wc_pickup_available'] ) ); ?> />
					<?php esc_html_e( 'Disponible', 'ai-knowledge' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'Solo se usa si ningún método de envío marcado arriba es de tipo "Recogida local" de WooCommerce (si lo es, se detecta solo).', 'ai-knowledge' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Plazo de entrega', 'ai-knowledge' ); ?></th>
			<td>
				<textarea name="delivery_time_note" rows="3" class="large-text"><?php echo esc_textarea( $settings['delivery_time_note'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'WooCommerce no expone un plazo de entrega real; escríbelo en texto libre (ej. "2-4 días laborables en Península").', 'ai-knowledge' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Contacto y horario de la tienda online', 'ai-knowledge' ); ?></th>
			<td>
				<textarea name="wc_contact_hours" rows="3" class="large-text"><?php echo esc_textarea( $settings['wc_contact_hours'] ); ?></textarea>
				<p class="description">
					<?php
					printf(
						/* translators: %s: enlace a la pestaña Negocio */
						esc_html__( 'Solo si el contacto/horario de la tienda online es distinto del general del negocio (pestaña %s). Vacío = se usa el de Negocio.', 'ai-knowledge' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=ai-knowledge&tab=negocio' ) ) . '">' . esc_html__( 'Negocio', 'ai-knowledge' ) . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba
					);
					?>
				</p>
				<?php if ( '' === $settings['wc_contact_hours'] ) : ?>
					<p class="description">
						<?php if ( '' !== $contact_text ) : ?>
							<?php esc_html_e( 'Actualmente se usa (de Negocio):', 'ai-knowledge' ); ?> <?php echo nl2br( esc_html( $contact_text ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba ?>
						<?php else : ?>
							<?php esc_html_e( '[pendiente] Tampoco hay contacto general en Negocio todavía.', 'ai-knowledge' ); ?>
						<?php endif; ?>
					</p>
				<?php endif; ?>
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
	<?php esc_html_e( 'Genera (o regenera) los documentos compuestos de "cómo comprar / condiciones de venta / envío y pago" y "catálogo de tienda", en el idioma principal, a partir de la configuración real de WooCommerce (incluida esta pestaña). No dependen de ningún post concreto, así que se generan a mano con este botón — no se disparan solos al cambiar los ajustes de WooCommerce.', 'ai-knowledge' ); ?>
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

<p class="description"><?php esc_html_e( 'Cada bloque muestra el Markdown publicado. Puedes pulir su formato con IA usando solo los datos que ya contiene; para sustituirlo por un texto completo externo, usa el modo manual de la pestaña Registro.', 'ai-knowledge' ); ?></p>

<?php foreach ( $langs as $lang ) : ?>
	<?php
	$store_info_row = Registry::find( Store_Info_Doc::SOURCE_ID_STORE_INFO, $lang );
	$catalog_row    = Registry::find( Store_Info_Doc::SOURCE_ID_SHOP_CATALOG, $lang );
	$lang_suffix    = count( $langs ) > 1 ? ' (' . strtoupper( $lang ) . ')' : '';
	?>
	<h2><?php echo esc_html( __( 'Información de tienda', 'ai-knowledge' ) . $lang_suffix ); ?></h2>
	<p class="description"><?php esc_html_e( 'Pule la estructura y la redacción del documento de compra, condiciones, envío y pago. Las instrucciones prevalecen sobre el formato predeterminado, pero no pueden añadir ni cambiar datos.', 'ai-knowledge' ); ?></p>
	<?php if ( $store_info_row && $store_info_row->md_path ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="wookb_polish_store_doc" />
			<input type="hidden" name="row_id" value="<?php echo esc_attr( $store_info_row->id ); ?>" />
			<?php wp_nonce_field( 'wookb_polish_store_doc' ); ?>
			<p>
				<label><?php esc_html_e( 'Instrucciones para pulir el texto (opcional)', 'ai-knowledge' ); ?></label><br />
				<textarea name="extra_info" rows="2" class="large-text" placeholder="<?php esc_attr_e( 'Indica solo el tono, la estructura o el estilo. La IA no consulta ni añade datos de WooCommerce desde este campo.', 'ai-knowledge' ); ?>"></textarea>
				<span class="description"><?php esc_html_e( 'Puedes escribir una indicación breve o pegar un prompt completo. Estas instrucciones prevalecen sobre el formato predeterminado, pero solo pueden usar los datos presentes en el documento. Si ya tienes un texto final generado con otra IA, pégalo manualmente desde la pestaña Registro.', 'ai-knowledge' ); ?></span>
			</p>
			<?php submit_button( __( 'Pulir redacción con IA', 'ai-knowledge' ), '' ); ?>
		</form>
		<textarea readonly rows="10" class="large-text code"><?php echo esc_textarea( Markdown_Store::body_only( Markdown_Store::read( $store_info_row->md_path ) ) ); ?></textarea>
	<?php else : ?>
		<p class="description"><?php esc_html_e( 'Todavía no se ha generado.', 'ai-knowledge' ); ?></p>
	<?php endif; ?>

	<h2><?php echo esc_html( __( 'Catálogo de tienda', 'ai-knowledge' ) . $lang_suffix ); ?></h2>
	<p class="description"><?php esc_html_e( 'Pule la estructura y la redacción del catálogo publicado. Las instrucciones prevalecen sobre el formato predeterminado, pero no pueden añadir productos ni datos que no aparezcan en el documento.', 'ai-knowledge' ); ?></p>
	<?php if ( $catalog_row && $catalog_row->md_path ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="wookb_polish_store_doc" />
			<input type="hidden" name="row_id" value="<?php echo esc_attr( $catalog_row->id ); ?>" />
			<?php wp_nonce_field( 'wookb_polish_store_doc' ); ?>
			<p>
				<label><?php esc_html_e( 'Instrucciones para pulir el texto (opcional)', 'ai-knowledge' ); ?></label><br />
				<textarea name="extra_info" rows="2" class="large-text" placeholder="<?php esc_attr_e( 'Indica solo el tono, la estructura o el estilo. La IA no consulta ni añade datos de WooCommerce desde este campo.', 'ai-knowledge' ); ?>"></textarea>
				<span class="description"><?php esc_html_e( 'Puedes escribir una indicación breve o pegar un prompt completo. Estas instrucciones prevalecen sobre el formato predeterminado, pero solo pueden usar los datos presentes en el documento. Si ya tienes un texto final generado con otra IA, pégalo manualmente desde la pestaña Registro.', 'ai-knowledge' ); ?></span>
			</p>
			<?php submit_button( __( 'Pulir redacción con IA', 'ai-knowledge' ), '' ); ?>
		</form>
		<textarea readonly rows="10" class="large-text code"><?php echo esc_textarea( Markdown_Store::body_only( Markdown_Store::read( $catalog_row->md_path ) ) ); ?></textarea>
	<?php else : ?>
		<p class="description"><?php esc_html_e( 'Todavía no se ha generado.', 'ai-knowledge' ); ?></p>
	<?php endif; ?>
<?php endforeach; ?>
