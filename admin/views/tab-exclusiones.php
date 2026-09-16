<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = Scope::settings();
?>
<p class="description">
	<?php esc_html_e( 'Excepciones dentro del alcance ya marcado en Alcance: IDs concretos o categorías/etiquetas que NO quieres que generen documento, aunque su tipo de contenido esté incluido. La exclusión actúa en el origen: un elemento excluido no genera .md, ni documento privado, ni entrada en llms.txt.', 'ai-knowledge' ); ?>
</p>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_save_exclusions" />
	<?php wp_nonce_field( 'wookb_save_exclusions' ); ?>

	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'IDs individuales excluidos', 'ai-knowledge' ); ?></th>
			<td>
				<input type="text" name="exclude_ids" class="regular-text" value="<?php echo esc_attr( implode( ',', (array) $settings['exclude_ids'] ) ); ?>" placeholder="789,1011" />
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Taxonomías / términos excluidos', 'ai-knowledge' ); ?></th>
			<td>
				<?php foreach ( (array) $settings['post_types'] as $pt ) : ?>
					<?php foreach ( get_object_taxonomies( $pt, 'objects' ) as $tax ) : ?>
						<p><strong><?php echo esc_html( $tax->label ); ?></strong></p>
						<div style="padding:8px 0;">
							<div class="wookb-chip-group">
							<?php
							$terms = get_terms( array( 'taxonomy' => $tax->name, 'hide_empty' => false ) );
							$selected = isset( $settings['exclude_terms'][ $tax->name ] ) ? $settings['exclude_terms'][ $tax->name ] : array();
							foreach ( $terms as $term ) :
								?>
								<label class="wookb-chip">
									<input type="checkbox" name="exclude_terms[<?php echo esc_attr( $tax->name ); ?>][]" value="<?php echo esc_attr( $term->term_id ); ?>" <?php checked( in_array( $term->term_id, $selected, true ) ); ?> />
									<?php echo esc_html( $term->name ); ?>
								</label>
							<?php endforeach; ?>
							</div>
							<?php if ( ! $terms ) : ?>
								<p class="description"><?php esc_html_e( 'Sin términos.', 'ai-knowledge' ); ?></p>
							<?php endif; ?>
						</div>
						<p class="description"><?php esc_html_e( 'Nada marcado = ningún término excluido.', 'ai-knowledge' ); ?></p>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</td>
		</tr>
	</table>
	<?php submit_button( __( 'Guardar exclusiones', 'ai-knowledge' ) ); ?>
</form>
