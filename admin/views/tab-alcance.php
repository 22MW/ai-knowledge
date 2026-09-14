<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = Scope::settings();
$public_post_types = get_post_types( array( 'public' => true ), 'objects' );
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_save_scope" />
	<?php wp_nonce_field( 'wookb_save_scope' ); ?>

	<h2><?php esc_html_e( 'Tipos de contenido a incluir', 'ai-knowledge' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'CPTs', 'ai-knowledge' ); ?></th>
			<td>
				<div class="wookb-chip-group">
				<?php foreach ( $public_post_types as $pt ) : ?>
					<?php if ( in_array( $pt->name, array( 'attachment' ), true ) ) { continue; } ?>
					<label class="wookb-chip">
						<input type="checkbox" name="post_types[]" value="<?php echo esc_attr( $pt->name ); ?>"
							<?php checked( in_array( $pt->name, (array) $settings['post_types'], true ) ); ?> />
						<?php echo esc_html( $pt->labels->name . ' (' . $pt->name . ')' ); ?>
					</label>
				<?php endforeach; ?>
				</div>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Taxonomías / términos', 'ai-knowledge' ); ?></th>
			<td>
				<?php foreach ( (array) $settings['post_types'] as $pt ) : ?>
					<?php foreach ( get_object_taxonomies( $pt, 'objects' ) as $tax ) : ?>
						<p><strong><?php echo esc_html( $tax->label ); ?></strong></p>
						<div style="padding:8px 0;">
							<div class="wookb-chip-group">
							<?php
							$terms = get_terms( array( 'taxonomy' => $tax->name, 'hide_empty' => false ) );
							$selected = isset( $settings['tax_terms'][ $tax->name ] ) ? $settings['tax_terms'][ $tax->name ] : array();
							foreach ( $terms as $term ) :
								?>
								<label class="wookb-chip">
									<input type="checkbox" name="tax_terms[<?php echo esc_attr( $tax->name ); ?>][]" value="<?php echo esc_attr( $term->term_id ); ?>" <?php checked( in_array( $term->term_id, $selected, true ) ); ?> />
									<?php echo esc_html( $term->name ); ?>
								</label>
							<?php endforeach; ?>
							</div>
							<?php if ( ! $terms ) : ?>
								<p class="description"><?php esc_html_e( 'Sin términos.', 'ai-knowledge' ); ?></p>
							<?php endif; ?>
						</div>
						<p class="description"><?php esc_html_e( 'Nada marcado = todos los términos.', 'ai-knowledge' ); ?></p>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'IDs sueltos adicionales', 'ai-knowledge' ); ?></th>
			<td>
				<input type="text" name="extra_ids" class="regular-text" value="<?php echo esc_attr( implode( ',', (array) $settings['extra_ids'] ) ); ?>" placeholder="123,456" />
				<p class="description"><?php esc_html_e( 'IDs separados por comas, independiente de CPT/taxonomía.', 'ai-knowledge' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Campos custom a incluir por CPT', 'ai-knowledge' ); ?></th>
			<td>
				<?php foreach ( (array) $settings['post_types'] as $pt ) : ?>
					<p><strong><?php echo esc_html( $pt ); ?></strong></p>
					<?php
						// Muestrea varios posts recientes (no solo 1) y fusiona sus meta keys,
						// para no perder campos (p.ej. meta boxes de JetEngine) que el primer
						// post de la muestra podria no tener rellenados. Ruido tecnico filtrado
						// con la lista compartida de Scope (misma fuente que Exclusiones).
						$keys = Scope::sampled_custom_field_keys( $pt, 20 );
						$selected = isset( $settings['custom_fields'][ $pt ] ) ? $settings['custom_fields'][ $pt ] : array();
					?>
					<div class="wookb-chip-group">
					<?php foreach ( $keys as $key ) : ?>
						<label class="wookb-chip">
							<input type="checkbox" name="custom_fields[<?php echo esc_attr( $pt ); ?>][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $selected, true ) ); ?> />
							<?php echo esc_html( $key ); ?>
						</label>
					<?php endforeach; ?>
					</div>
					<?php if ( ! $keys ) : ?>
						<p class="description"><?php esc_html_e( 'No se detectaron campos custom en los productos analizados.', 'ai-knowledge' ); ?></p>
					<?php endif; ?>
				<?php endforeach; ?>
			</td>
		</tr>
	</table>
	<?php submit_button( __( 'Guardar alcance', 'ai-knowledge' ) ); ?>
</form>
