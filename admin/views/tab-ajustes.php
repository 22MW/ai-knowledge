<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings        = Scope::settings();
$ai_cfg          = Generator::ai_config();
?>
<p class="description">
	<?php esc_html_e( 'Configuración general del generador de documentos: largo del texto, clave de IA a usar, instrucciones adicionales del prompt, y qué post_types muestran el botón de añadir a la base de conocimiento desde su editor.', 'ai-knowledge' ); ?>
</p>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_save_settings" />
	<?php wp_nonce_field( 'wookb_save_settings' ); ?>

	<p class="description">
		<?php
		printf(
			/* translators: %s: enlace a la pestaña Carga inicial */
			esc_html__( 'El límite diario, el tamaño de lote y el debounce de la cola se editan en %s.', 'ai-knowledge' ),
			'<a href="' . esc_url( admin_url( 'admin.php?page=woo-kb-generator&tab=carga-inicial' ) ) . '">' . esc_html__( 'Carga inicial', 'ai-knowledge' ) . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba
		);
		?>
	</p>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Largo del texto generado (caracteres)', 'ai-knowledge' ); ?></th>
			<td>
				<input type="number" min="100" max="10000" name="body_char_limit" value="<?php echo esc_attr( $settings['body_char_limit'] ); ?>" />
				<p class="description"><?php esc_html_e( 'El tamaño real del cuerpo de cada documento generado (sin contar el título). Se puede sobrescribir por documento individual desde el Registro.', 'ai-knowledge' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Tokens de salida por documento', 'ai-knowledge' ); ?></th>
			<td>
				<input type="number" min="200" name="output_tokens" value="<?php echo esc_attr( $settings['output_tokens'] ); ?>" />
				<p class="description"><?php esc_html_e( 'Techo técnico de la petición a la IA (para que la respuesta no se corte a mitad), no el largo del texto — eso lo controla el campo de arriba.', 'ai-knowledge' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Origen de la clave IA', 'ai-knowledge' ); ?></th>
			<td>
				<label><input type="radio" name="ai_key_source" value="genix" <?php checked( 'genix' === $settings['ai_key_source'] ); ?> /> <?php esc_html_e( 'Reutilizar la de Support Genix', 'ai-knowledge' ); ?></label><br />
				<label><input type="radio" name="ai_key_source" value="own" <?php checked( 'own' === $settings['ai_key_source'] ); ?> /> <?php esc_html_e( 'Clave propia', 'ai-knowledge' ); ?></label>
				<p class="description">
					<?php
					if ( $ai_cfg ) {
						printf(
							/* translators: %1$s clave enmascarada, %2$s modelo, %3$s origen */
							esc_html__( 'Config. activa: %1$s (modelo %2$s, origen %3$s)', 'ai-knowledge' ),
							esc_html( substr( $ai_cfg['api_key'], 0, 4 ) . '…' . substr( $ai_cfg['api_key'], -4 ) ),
							esc_html( $ai_cfg['model'] ),
							esc_html( $ai_cfg['source'] )
						);
					} else {
						esc_html_e( 'No hay clave IA configurada todavía.', 'ai-knowledge' );
					}
					?>
				</p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Clave propia (fallback)', 'ai-knowledge' ); ?></th>
			<td>
				<input type="password" name="own_api_key" class="regular-text" value="<?php echo esc_attr( $settings['own_api_key'] ); ?>" autocomplete="off" />
				<p class="description"><?php esc_html_e( 'Se guarda en claro, igual que la clave de Support Genix. Límite conocido y documentado.', 'ai-knowledge' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Modelo (clave propia)', 'ai-knowledge' ); ?></th>
			<td><input type="text" name="own_model" class="regular-text" value="<?php echo esc_attr( $settings['own_model'] ); ?>" /></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Instrucciones adicionales del prompt', 'ai-knowledge' ); ?></th>
			<td><textarea name="extra_prompt" rows="4" class="large-text"><?php echo esc_textarea( $settings['extra_prompt'] ); ?></textarea></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Límite de "Documentos relacionados" en el chat', 'ai-knowledge' ); ?></th>
			<td>
				<input type="number" min="0" name="chatbot_docs_list_limit" value="<?php echo esc_attr( $settings['chatbot_docs_list_limit'] ); ?>" />
				<p class="description"><?php esc_html_e( 'Máximo de enlaces mostrados bajo la respuesta del chatbot (Fase 2, pieza 3). 0 = sin límite.', 'ai-knowledge' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Botón "Añadir a la base de conocimiento" en el editor', 'ai-knowledge' ); ?></th>
			<td>
				<?php
				$editor_post_types = class_exists( '\WOOKB\Editor_Metabox' ) ? Editor_Metabox::allowed_post_types() : (array) $settings['editor_button_post_types'];
				?>
				<input type="hidden" name="editor_button_post_types_submitted" value="1" />
				<div class="wookb-chip-group">
					<?php foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $pt ) : ?>
						<?php if ( 'attachment' === $pt->name ) { continue; } ?>
						<label class="wookb-chip">
							<input type="checkbox" name="editor_button_post_types[]" value="<?php echo esc_attr( $pt->name ); ?>"
								<?php checked( in_array( $pt->name, $editor_post_types, true ) ); ?> />
							<?php echo esc_html( $pt->labels->name . ' (' . $pt->name . ')' ); ?>
						</label>
					<?php endforeach; ?>
				</div>
				<p class="description"><?php esc_html_e( 'Tipos de contenido públicos donde aparece el meta box "Base de conocimiento IA" en su editor. Por defecto, todos.', 'ai-knowledge' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Aviso a buscadores (IndexNow)', 'ai-knowledge' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="indexnow_enabled" value="1" <?php checked( ! empty( $settings['indexnow_enabled'] ) ); ?> />
					<?php esc_html_e( 'Avisar a buscadores compatibles con IndexNow (Bing y otros; Google no lo soporta) al crear, actualizar o borrar contenido del alcance.', 'ai-knowledge' ); ?>
				</label>
				<p class="description">
					<?php
					printf(
						/* translators: %s: URL del archivo de clave IndexNow */
						esc_html__( 'Clave del sitio (generada automáticamente): %s', 'ai-knowledge' ),
						'<code>' . esc_html( home_url( '/' . Indexnow::get_key() . '.txt' ) ) . '</code>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba
					);
					?>
				</p>
			</td>
		</tr>
	</table>
	<?php submit_button( __( 'Guardar ajustes', 'ai-knowledge' ) ); ?>
</form>

<hr />

<p class="description">
	<?php
	printf(
		/* translators: %s: enlace a la pestaña Prompt */
		esc_html__( 'El prompt de sistema del chatbot se gestiona en la pestaña %s.', 'ai-knowledge' ),
		'<a href="' . esc_url( admin_url( 'admin.php?page=woo-kb-generator&tab=prompt' ) ) . '">' . esc_html__( 'Prompt', 'ai-knowledge' ) . '</a>'
	);
	?>
</p>

<hr />

<?php if ( class_exists( 'WooCommerce' ) ) : ?>
<p class="description">
	<?php
	printf(
		/* translators: %s: enlace a la pestaña WooCommerce */
		esc_html__( 'Los documentos de información de tienda (cómo comprar, condiciones, envío, pago, impuestos) se generan desde la pestaña %s.', 'ai-knowledge' ),
		'<a href="' . esc_url( admin_url( 'admin.php?page=woo-kb-generator&tab=woocommerce' ) ) . '">' . esc_html__( 'WooCommerce', 'ai-knowledge' ) . '</a>'
	);
	?>
</p>
<hr />
<?php endif; ?>
