<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings        = Scope::settings();
$ai_cfg          = AI_Client::config();
$wp_ai_available = AI_Client::wordpress_available();
$wp_ai_models    = $wp_ai_available ? AI_Client::available_models() : array();
?>
<p class="description">
	<?php esc_html_e( 'Configuración general del generador de documentos: largo del texto, origen de IA y qué post_types muestran el botón de añadir a la base de conocimiento desde su editor.', 'ai-knowledge' ); ?>
</p>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_save_settings" />
	<?php wp_nonce_field( 'wookb_save_settings' ); ?>

	<p class="description">
		<?php
		printf(
			/* translators: %s: enlace a la pestaña Generación masiva */
			esc_html__( 'El límite diario, el tamaño de lote y el debounce de la cola se editan en %s.', 'ai-knowledge' ),
			'<a href="' . esc_url( admin_url( 'admin.php?page=ai-knowledge&tab=carga-inicial' ) ) . '">' . esc_html__( 'Generación masiva', 'ai-knowledge' ) . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba
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
			<th><?php esc_html_e( 'Origen de IA', 'ai-knowledge' ); ?></th>
			<td>
				<?php if ( $wp_ai_available ) : ?>
					<label><input type="radio" name="ai_key_source" value="wp_connectors" <?php checked( 'wp_connectors' === $settings['ai_key_source'] ); ?> /> <?php esc_html_e( 'Conectores de WordPress', 'ai-knowledge' ); ?></label><br />
				<?php endif; ?>
				<label><input type="radio" name="ai_key_source" value="genix" <?php checked( 'wp_connectors' !== $settings['ai_key_source'] || ! $wp_ai_available ); ?> /> <?php esc_html_e( 'Support Genix', 'ai-knowledge' ); ?></label>
				<p class="description">
					<?php
					if ( $ai_cfg ) {
						printf(
							/* translators: %1$s modelo, %2$s origen */
							esc_html__( 'Configuración activa: modelo %1$s, origen %2$s.', 'ai-knowledge' ),
							esc_html( AI_Client::MODEL_AUTO === $ai_cfg['model'] ? __( 'Automático', 'ai-knowledge' ) : $ai_cfg['model'] ),
							esc_html( 'wp_connectors' === $ai_cfg['source'] ? __( 'Conectores de WordPress', 'ai-knowledge' ) : __( 'Support Genix', 'ai-knowledge' ) )
						);
					} else {
						esc_html_e( 'El origen seleccionado no está conectado.', 'ai-knowledge' );
					}
					?>
				</p>
			</td>
		</tr>
		<?php if ( $wp_ai_available ) : ?>
		<tr>
			<th><?php esc_html_e( 'Modelo de WordPress', 'ai-knowledge' ); ?></th>
			<td>
				<select name="wp_ai_model">
					<option value="<?php echo esc_attr( AI_Client::MODEL_AUTO ); ?>" <?php selected( AI_Client::MODEL_AUTO === $settings['wp_ai_model'] ); ?>><?php esc_html_e( 'Automático (recomendado)', 'ai-knowledge' ); ?></option>
					<?php
					$current_provider = '';
					foreach ( $wp_ai_models as $key => $model ) :
						if ( $current_provider !== $model['provider'] ) :
							if ( '' !== $current_provider ) {
								echo '</optgroup>';
							}
							$current_provider = $model['provider'];
							echo '<optgroup label="' . esc_attr( $model['provider_name'] ) . '">';
						endif;
						?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key === $settings['wp_ai_model'] ); ?>><?php echo esc_html( $model['name'] . ' (' . $model['model'] . ')' ); ?></option>
					<?php endforeach; ?>
					<?php if ( '' !== $current_provider ) { echo '</optgroup>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- etiqueta HTML fija. ?>
				</select>
				<p class="description">
					<?php if ( empty( $wp_ai_models ) ) : ?>
						<?php esc_html_e( 'No hay modelos compatibles conectados todavía.', 'ai-knowledge' ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Automático elige entre los modelos de texto conectados de Anthropic, OpenAI o Google.', 'ai-knowledge' ); ?>
					<?php endif; ?>
					<a href="<?php echo esc_url( admin_url( 'options-connectors.php' ) ); ?>"><?php esc_html_e( 'Gestionar Conectores de WordPress', 'ai-knowledge' ); ?></a>
				</p>
			</td>
		</tr>
		<?php endif; ?>
		<tr>
			<th><?php esc_html_e( 'Botón "Añadir a la base de conocimiento" en el editor', 'ai-knowledge' ); ?></th>
			<td>
				<?php
				$editor_post_types = class_exists( '\AIKB\Editor_Metabox' ) ? Editor_Metabox::allowed_post_types() : (array) $settings['editor_button_post_types'];
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
		'<a href="' . esc_url( admin_url( 'admin.php?page=ai-knowledge&tab=prompt' ) ) . '">' . esc_html__( 'Prompt', 'ai-knowledge' ) . '</a>'
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
		'<a href="' . esc_url( admin_url( 'admin.php?page=ai-knowledge&tab=woocommerce' ) ) . '">' . esc_html__( 'WooCommerce', 'ai-knowledge' ) . '</a>'
	);
	?>
</p>
<hr />
<?php endif; ?>
