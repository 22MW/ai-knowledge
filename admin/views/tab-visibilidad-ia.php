<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$scope_ids = Scope::resolve_ids();
$synced    = Registry::get_synced_public_urls();

$synced_source_ids = array();
$check_options     = array();
$example_row       = null;
foreach ( $synced as $row ) {
	$synced_source_ids[ (int) $row->source_id ] = true;

	if ( ! in_array( (int) $row->source_id, $scope_ids, true ) ) {
		continue;
	}
	$permalink = get_permalink( $row->source_id );
	if ( ! $permalink ) {
		continue; // Documentos compuestos (store-info, shop-catalog) sin post real: no tienen URL que comprobar.
	}
	$check_options[ $row->source_id . ':' . $row->lang ] = get_the_title( $row->source_id ) . ' (' . strtoupper( $row->lang ) . ')';
	if ( ! $example_row ) {
		$example_row = $row; // Primer elemento real y sincronizado: sirve de ejemplo para Markdown/JSON/JSON-LD.
	}
}

$pending_count = 0;
foreach ( $scope_ids as $id ) {
	if ( empty( $synced_source_ids[ $id ] ) ) {
		$pending_count++;
	}
}

$llms_physical_preview = '';
$llms_physical_mtime   = 0;
if ( Llms_Txt::physical_file_exists() ) {
	$llms_physical_path  = ABSPATH . 'llms.txt';
	$llms_physical_mtime = (int) filemtime( $llms_physical_path );
	$raw                 = (string) file_get_contents( $llms_physical_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$lines               = array_slice( preg_split( '/\r\n|\r|\n/', $raw ), 0, 3 );
	$llms_physical_preview = implode( "\n", $lines );
}

$accessibility_result = get_transient( 'wookb_accessibility_result' );
$accessibility_error  = get_transient( 'wookb_accessibility_error' );
delete_transient( 'wookb_accessibility_result' );
delete_transient( 'wookb_accessibility_error' );
?>

<p class="description">
	<?php esc_html_e( 'Resumen de si el contenido del plugin está bien expuesto a buscadores y agentes de IA, y una comprobación puntual de accesibilidad. Todo lo que ves aquí es solo lectura: no cambia nada por sí solo.', 'ai-knowledge' ); ?>
</p>

<h2><?php esc_html_e( 'Estado de exposición', 'ai-knowledge' ); ?></h2>
<table class="form-table">
	<tr>
		<th><?php esc_html_e( 'llms.txt', 'ai-knowledge' ); ?></th>
		<td>
			<?php if ( Llms_Txt::physical_file_exists() ) : ?>
				<span class="description"><?php esc_html_e( '⚠ Tapado por un archivo físico llms.txt en la raíz del sitio: se sirve ese en vez del generado por el plugin.', 'ai-knowledge' ); ?></span>
				<?php if ( $llms_physical_mtime ) : ?>
					<p class="description">
						<?php
						printf(
							/* translators: %s: fecha de última modificación del archivo físico */
							esc_html__( 'Modificado por última vez: %s.', 'ai-knowledge' ),
							esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $llms_physical_mtime ) )
						);
						?>
					</p>
				<?php endif; ?>
				<?php if ( '' !== $llms_physical_preview ) : ?>
					<pre style="white-space:pre-wrap;margin:4px 0;"><?php echo esc_html( $llms_physical_preview ); ?></pre>
				<?php endif; ?>
				<p class="description">
					<?php
					printf(
						/* translators: %s: enlace para abrir el archivo físico */
						esc_html__( 'Este plugin NO necesita un archivo físico: genera su llms.txt al vuelo cada vez que un bot lo pide (ruta virtual, sin nada guardado en disco). Si este archivo físico no lo creaste tú a propósito ni lo necesita otro plugin, puedes borrarlo desde aquí para que se sirva el que genera este plugin. %s', 'ai-knowledge' ),
						'<a href="' . esc_url( home_url( '/llms.txt' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Ver el archivo actual', 'ai-knowledge' ) . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba
					);
					?>
				</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Vas a borrar el archivo físico llms.txt de la raíz del sitio. Esto NO se puede deshacer. El plugin seguirá sirviendo su propio llms.txt generado al vuelo. ¿Seguro que quieres continuar?', 'ai-knowledge' ) ); ?>');">
					<input type="hidden" name="action" value="wookb_delete_physical_llms_txt" />
					<?php wp_nonce_field( 'wookb_delete_physical_llms_txt' ); ?>
					<?php submit_button( __( 'Borrar archivo físico', 'ai-knowledge' ), 'delete', 'submit', false ); ?>
				</form>
			<?php else : ?>
				<span class="description"><?php esc_html_e( 'Activo, servido por el plugin.', 'ai-knowledge' ); ?></span>
				<a href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Ver', 'ai-knowledge' ); ?></a>
			<?php endif; ?>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Markdown público', 'ai-knowledge' ); ?></th>
		<td>
			<span class="description"><?php esc_html_e( 'El mismo contenido que usa el chatbot, publicado como archivo .md para que cualquier crawler o agente IA lo lea sin procesar HTML (Fase 4).', 'ai-knowledge' ); ?></span>
			<?php if ( $example_row && $example_row->md_path ) : ?>
				<br /><a href="<?php echo esc_url( Markdown_Store::public_url( $example_row->md_path ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Ver un ejemplo real', 'ai-knowledge' ); ?></a>
			<?php endif; ?>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'JSON estructurado', 'ai-knowledge' ); ?></th>
		<td>
			<span class="description"><?php esc_html_e( 'El mismo dato de WordPress/WooCommerce en JSON, sin pasar por IA, vía API REST propia — para que un agente lea el dato exacto sin interpretarlo (Fase 3).', 'ai-knowledge' ); ?></span>
			<?php if ( $example_row ) : ?>
				<br /><a href="<?php echo esc_url( rest_url( 'ai-knowledge/v1/content/' . $example_row->source_id ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Ver un ejemplo real', 'ai-knowledge' ); ?></a>
			<?php endif; ?>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'JSON-LD (Schema.org)', 'ai-knowledge' ); ?></th>
		<td>
			<span class="description"><?php esc_html_e( 'Datos estructurados (Product/Article) incrustados en el código de cada página del alcance, para que buscadores entiendan qué es cada contenido sin adivinarlo (Fase 5).', 'ai-knowledge' ); ?></span>
			<?php if ( $example_row ) :
				$example_url = get_permalink( $example_row->source_id );
				?>
				<?php if ( $example_url ) : ?>
					<br />
					<?php
					printf(
						/* translators: %s: enlace a una página de ejemplo */
						esc_html__( 'No tiene URL propia: %s y mira el código fuente, bloque <script type="application/ld+json">.', 'ai-knowledge' ),
						'<a href="' . esc_url( $example_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'abre un ejemplo real', 'ai-knowledge' ) . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba
					);
					?>
				<?php endif; ?>
			<?php endif; ?>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Contenido pendiente de sincronizar', 'ai-knowledge' ); ?></th>
		<td>
			<?php if ( $pending_count > 0 ) : ?>
				<span class="description">
					<?php
					printf(
						/* translators: %d: numero de elementos del alcance sin sincronizar todavia */
						esc_html( _n( 'Queda %d elemento sin sincronizar.', 'Quedan %d elementos sin sincronizar.', $pending_count, 'ai-knowledge' ) ),
						(int) $pending_count
					);
					?>
				</span>
			<?php else : ?>
				<span class="description"><?php esc_html_e( 'Todo el contenido del alcance está sincronizado.', 'ai-knowledge' ); ?></span>
			<?php endif; ?>
		</td>
	</tr>
</table>

<hr />

<h2><?php esc_html_e( 'Comprobar accesibilidad', 'ai-knowledge' ); ?></h2>
<p class="description"><?php esc_html_e( 'Elige una URL ya sincronizada y comprueba en vivo si robots.txt permite rastrearla y si lleva noindex — avisa si ambas señales se contradicen entre sí.', 'ai-knowledge' ); ?></p>

<?php if ( $accessibility_error ) : ?>
	<div class="notice notice-error inline"><p><?php echo esc_html( $accessibility_error ); ?></p></div>
<?php endif; ?>

<?php if ( $accessibility_result ) : ?>
	<div class="notice <?php echo $accessibility_result['conflict'] ? 'notice-warning' : 'notice-success'; ?> inline">
		<p>
			<strong><?php echo esc_html( $accessibility_result['url'] ); ?></strong><br />
			<?php
			echo esc_html(
				$accessibility_result['robots_allowed']
					? __( 'robots.txt: permitida.', 'ai-knowledge' )
					: __( 'robots.txt: bloqueada.', 'ai-knowledge' )
			);
			?>
			<br />
			<?php
			echo esc_html(
				$accessibility_result['noindex']
					? __( 'Señal noindex: sí (meta robots o cabecera X-Robots-Tag).', 'ai-knowledge' )
					: __( 'Señal noindex: no.', 'ai-knowledge' )
			);
			?>
			<?php if ( $accessibility_result['conflict'] ) : ?>
				<br /><strong><?php esc_html_e( '⚠ Conflicto de señales: robots.txt y noindex no coinciden — revisa la configuración.', 'ai-knowledge' ); ?></strong>
			<?php endif; ?>
		</p>
	</div>
<?php endif; ?>

<?php if ( empty( $check_options ) ) : ?>
	<p class="description"><?php esc_html_e( 'Todavía no hay contenido sincronizado que comprobar.', 'ai-knowledge' ); ?></p>
<?php else : ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="wookb_check_accessibility" />
		<?php wp_nonce_field( 'wookb_check_accessibility' ); ?>
		<select name="check_target">
			<?php foreach ( $check_options as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php submit_button( __( 'Comprobar accesibilidad', 'ai-knowledge' ), 'secondary', 'submit', false ); ?>
	</form>
<?php endif; ?>
