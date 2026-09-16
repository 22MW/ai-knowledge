<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings           = Scope::settings();
$public_post_types  = get_post_types( array( 'public' => true ), 'objects' );
$effective_post_types = Scope::effective_post_types();
?>
<p class="description">
	<?php esc_html_e( 'Qué contenido de tu web entra en la base de conocimiento: qué tipos de contenido, qué categorías/etiquetas concretas, IDs sueltos adicionales o excluidos, y qué campos personalizados se envían a la IA. Nada se genera para lo que no esté incluido aquí, y un ID excluido nunca genera documento aunque su tipo de contenido o término esté incluido.', 'ai-knowledge' ); ?>
</p>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_save_content" />
	<?php wp_nonce_field( 'wookb_save_content' ); ?>

	<h2><?php esc_html_e( 'Tipos de contenido a incluir', 'ai-knowledge' ); ?></h2>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Modo', 'ai-knowledge' ); ?></th>
			<td>
				<label class="wookb-chip">
					<input type="radio" name="post_types_mode" value="explicit" <?php checked( 'all_public' !== $settings['post_types_mode'] ); ?> />
					<?php esc_html_e( 'Solo los tipos marcados abajo', 'ai-knowledge' ); ?>
				</label>
				<label class="wookb-chip">
					<input type="radio" name="post_types_mode" value="all_public" <?php checked( 'all_public' === $settings['post_types_mode'] ); ?> />
					<?php esc_html_e( 'Todos los tipos públicos', 'ai-knowledge' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'CPTs a incluir', 'ai-knowledge' ); ?></th>
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
				<p class="description"><?php esc_html_e( 'Solo se usa si el modo es "Solo los tipos marcados".', 'ai-knowledge' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'CPTs a excluir cuando el modo es "Todos"', 'ai-knowledge' ); ?></th>
			<td>
				<div class="wookb-chip-group">
				<?php foreach ( $public_post_types as $pt ) : ?>
					<?php if ( in_array( $pt->name, array( 'attachment' ), true ) ) { continue; } ?>
					<label class="wookb-chip">
						<input type="checkbox" name="post_types_excluded_when_all[]" value="<?php echo esc_attr( $pt->name ); ?>"
							<?php checked( in_array( $pt->name, (array) $settings['post_types_excluded_when_all'], true ) ); ?> />
						<?php echo esc_html( $pt->labels->name . ' (' . $pt->name . ')' ); ?>
					</label>
				<?php endforeach; ?>
				</div>
				<p class="description"><?php esc_html_e( 'Solo se usa si el modo es "Todos los tipos públicos": el resto de tipos, presentes y futuros, entra automáticamente.', 'ai-knowledge' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Taxonomías / términos', 'ai-knowledge' ); ?></th>
			<td>
				<?php
				$noise_taxonomies = Scope::noise_taxonomies();
				foreach ( $effective_post_types as $pt ) :
					foreach ( get_object_taxonomies( $pt, 'objects' ) as $tax ) :
						if ( in_array( $tax->name, $noise_taxonomies, true ) ) {
							continue;
						}
						$terms = get_terms( array( 'taxonomy' => $tax->name, 'hide_empty' => false ) );
						if ( ! $terms ) {
							continue;
						}
						?>
						<h2><?php echo esc_html( $tax->label ); ?></h2>
						<div class="wookb-chip-group">
							<?php
							$selected = isset( $settings['term_actions'][ $tax->name ] ) ? $settings['term_actions'][ $tax->name ] : array();
							foreach ( $terms as $term ) :
								$is_included_term = isset( $selected[ $term->term_id ] ) && 'include' === $selected[ $term->term_id ];
								?>
								<label class="wookb-chip">
									<input type="checkbox" name="term_actions[<?php echo esc_attr( $tax->name ); ?>][<?php echo esc_attr( $term->term_id ); ?>]" value="include" <?php checked( $is_included_term ); ?> />
									<?php echo esc_html( $term->name ); ?>
								</label>
							<?php endforeach; ?>
						</div>
						<p class="description"><?php esc_html_e( 'Nada marcado = todos los términos. Marcado = solo entran posts con ese término.', 'ai-knowledge' ); ?></p>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'IDs sueltos', 'ai-knowledge' ); ?></th>
			<td>
				<h2><?php esc_html_e( 'IDs a incluir', 'ai-knowledge' ); ?></h2>
				<input type="text" name="include_ids" class="regular-text" value="<?php echo esc_attr( implode( ',', array_keys( array_filter( (array) $settings['id_actions'], function ( $v ) {
					return 'include' === $v;
				} ) ) ) ); ?>" placeholder="123,456" />
				<p class="description"><?php esc_html_e( 'IDs separados por comas, independiente de CPT/taxonomía.', 'ai-knowledge' ); ?></p>

				<h2><?php esc_html_e( 'IDs a excluir', 'ai-knowledge' ); ?></h2>
				<input type="text" name="exclude_ids" class="regular-text" value="<?php echo esc_attr( implode( ',', array_keys( array_filter( (array) $settings['id_actions'], function ( $v ) {
					return 'exclude' === $v;
				} ) ) ) ); ?>" placeholder="789,1011" />
				<p class="description"><?php esc_html_e( 'IDs separados por comas: nunca generan documento, aunque su tipo de contenido o término esté incluido.', 'ai-knowledge' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Campos custom a incluir por CPT', 'ai-knowledge' ); ?></th>
			<td>
				<?php foreach ( $effective_post_types as $pt ) : ?>
					<h2><?php echo esc_html( $pt ); ?></h2>
					<?php
						// Muestrea varios posts recientes (no solo 1) y fusiona sus meta keys,
						// para no perder campos (p.ej. meta boxes de JetEngine) que el primer
						// post de la muestra podria no tener rellenados. Ruido tecnico filtrado
						// con la lista compartida de Scope.
						$keys     = Scope::sampled_custom_field_keys( $pt, 20 );
						$selected = isset( $settings['custom_fields'][ $pt ] ) ? $settings['custom_fields'][ $pt ] : array();
					?>
					<div class="wookb-chip-group">
					<?php foreach ( $keys as $key ) : ?>
						<?php $label = Scope::custom_field_label( $pt, $key ); ?>
						<label class="wookb-chip">
							<input type="checkbox" name="custom_fields[<?php echo esc_attr( $pt ); ?>][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $selected, true ) ); ?> />
							<?php echo $label !== $key ? esc_html( $label . ' (' . $key . ')' ) : esc_html( $key ); ?>
						</label>
					<?php endforeach; ?>
					</div>
					<?php if ( ! $keys ) : ?>
						<p class="description"><?php esc_html_e( 'No se detectaron campos custom en los posts analizados.', 'ai-knowledge' ); ?></p>
					<?php endif; ?>
				<?php endforeach; ?>
			</td>
		</tr>
	</table>
	<?php submit_button( __( 'Guardar', 'ai-knowledge' ) ); ?>
</form>
