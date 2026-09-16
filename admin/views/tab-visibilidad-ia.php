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

// Fase 11: lectura en vivo de robots.txt actual (pieza 1, solo lectura).
$robots_txt_content = '';
$robots_txt_error   = '';
$robots_response     = wp_remote_get( home_url( '/robots.txt' ) );
if ( is_wp_error( $robots_response ) ) {
	$robots_txt_error = $robots_response->get_error_message();
} else {
	$robots_txt_content = wp_remote_retrieve_body( $robots_response );
}

// Fase 11 (revision UX 2026-09-16): disponibilidad real de robots.txt/.htaccess
// y confirmacion de descarga vigente, comprobadas en cada render (doble
// proteccion server-side, no basta con deshabilitar el boton en el HTML).
$robots_available   = Robots_Txt_Guard::is_available();
$robots_confirmed   = Robots_Txt_Guard::backup_confirmed();
$htaccess_available = Htaccess_Guard::is_available();
$htaccess_confirmed = Htaccess_Guard::backup_confirmed();

$crawler_saved_actions = Scope::settings()['crawler_actions'];

// Fase 11, pieza 3: ultimos accesos registrados del catalogo de crawlers.
$crawler_log_rows = Crawler_Log::recent( 50 );
$crawler_log_total = Crawler_Log::count_rows();

// Fase 11 (revision UX): vista previa del bloque que se insertaria con la
// configuracion actual de la tabla -- no es una simulacion del archivo
// completo (insert_with_markers ya garantiza que solo se reemplaza el
// bloque propio, el resto del archivo queda intacto), solo el contenido
// exacto que ira dentro del marcador, para que el usuario vea el efecto de
// sus elecciones antes de aplicar.
$crawler_blocked_bots  = Crawler_Catalog::blocked_user_agents();
$robots_block_preview   = implode( "\n", Robots_Txt_Guard::build_rules( $crawler_blocked_bots ) );
// "RewriteEngine On" se antepone en Htaccess_Guard::apply_block() al escribir
// de verdad; se replica aqui solo para que la vista previa sea fiel a lo que
// se escribira.
$htaccess_block_preview = "RewriteEngine On\n" . implode( "\n", Htaccess_Guard::build_rules( $crawler_blocked_bots ) );
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

<hr />

<div class="wookb-crawler-section">

<h2><?php esc_html_e( 'Gestión de crawlers de IA', 'ai-knowledge' ); ?></h2>

<h3><?php esc_html_e( 'Configuración por bot', 'ai-knowledge' ); ?></h3>
<p class="description">
	<?php esc_html_e( 'Catálogo de crawlers de IA conocidos, con su categoría de propósito y una acción por bot (Permitir/Bloquear). Esta tabla es la única fuente de verdad: alimenta tanto el bloqueo por robots.txt como el bloqueo por .htaccess de más abajo.', 'ai-knowledge' ); ?>
</p>
<?php
$category_labels = array(
	'ai_search'                => __( 'Búsqueda/citas IA', 'ai-knowledge' ),
	'user_requested_assistant' => __( 'Uso bajo demanda', 'ai-knowledge' ),
	'model_training'           => __( 'Entrenamiento de modelos', 'ai-knowledge' ),
);
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_save_crawler_actions" />
	<?php wp_nonce_field( 'wookb_save_crawler_actions' ); ?>
	<div style="overflow-x:auto;">
	<table class="wp-list-table widefat striped" style="min-width:800px;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Bot', 'ai-knowledge' ); ?></th>
				<th><?php esc_html_e( 'Operador', 'ai-knowledge' ); ?></th>
				<th><?php esc_html_e( 'Categoría', 'ai-knowledge' ); ?></th>
				<th><?php esc_html_e( 'Descripción', 'ai-knowledge' ); ?></th>
				<th style="width:140px;"><?php esc_html_e( 'Acción', 'ai-knowledge' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( Crawler_Catalog::all() as $entry ) :
				$ua       = $entry['user_agent'];
				$current  = isset( $crawler_saved_actions[ $ua ] ) ? $crawler_saved_actions[ $ua ] : $entry['default_action'];
				?>
				<tr>
					<td><?php echo esc_html( $ua ); ?></td>
					<td><?php echo esc_html( $entry['operator'] ); ?></td>
					<td><?php echo esc_html( isset( $category_labels[ $entry['category'] ] ) ? $category_labels[ $entry['category'] ] : $entry['category'] ); ?></td>
					<td><?php echo esc_html( $entry['description'] ); ?></td>
					<td>
						<select name="crawler_action[<?php echo esc_attr( $ua ); ?>]">
							<option value="allow" <?php selected( 'allow', $current ); ?>><?php esc_html_e( 'Permitir', 'ai-knowledge' ); ?></option>
							<option value="block" <?php selected( 'block', $current ); ?>><?php esc_html_e( 'Bloquear', 'ai-knowledge' ); ?></option>
							<?php if ( 'ask' === $current ) : ?>
								<option value="ask" selected="selected" disabled="disabled"><?php esc_html_e( 'Sin decidir (uso mixto)', 'ai-knowledge' ); ?></option>
							<?php endif; ?>
						</select>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	</div>
	<div class="submit-row"><?php submit_button( __( 'Guardar configuración de crawlers', 'ai-knowledge' ), 'primary', 'submit', false ); ?></div>
</form>

<h3><?php esc_html_e( 'robots.txt', 'ai-knowledge' ); ?></h3>
<div class="wookb-crawler-compare">
	<div>
		<p class="description"><?php esc_html_e( 'Actual (lo que vería un crawler ahora mismo)', 'ai-knowledge' ); ?></p>
		<?php if ( $robots_txt_error ) : ?>
			<div class="notice notice-error inline"><p><?php echo esc_html( $robots_txt_error ); ?></p></div>
		<?php else : ?>
			<textarea readonly rows="8"><?php echo esc_textarea( $robots_txt_content ); ?></textarea>
		<?php endif; ?>
	</div>
	<div>
		<p class="description"><?php esc_html_e( 'Bloque que se insertará (el resto del archivo no se toca)', 'ai-knowledge' ); ?></p>
		<textarea readonly rows="8"><?php echo esc_textarea( $robots_block_preview ); ?></textarea>
	</div>
</div>

<p><strong><?php esc_html_e( '⚠ Escribe de verdad el archivo robots.txt del sitio. Descarga la copia actual antes de continuar.', 'ai-knowledge' ); ?></strong></p>

<?php if ( ! $robots_available ) : ?>
	<div class="notice notice-warning inline">
		<p><?php esc_html_e( 'robots.txt no es escribible en este servidor (permisos de la carpeta raíz).', 'ai-knowledge' ); ?></p>
	</div>
<?php else : ?>
	<p class="description" data-wookb-unlock-notice="robots" <?php echo $robots_confirmed ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Descarga la copia actual primero (botón de la izquierda); se habilitará automáticamente al terminar.', 'ai-knowledge' ); ?></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wookb-download-form" data-wookb-unlock="robots" style="display:inline-block;margin-right:10px;">
		<input type="hidden" name="action" value="wookb_download_robots_backup" />
		<?php wp_nonce_field( 'wookb_download_robots_backup' ); ?>
		<?php submit_button( __( 'Descargar copia actual de robots.txt', 'ai-knowledge' ), 'secondary', 'submit', false ); ?>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;" onsubmit="return confirm('<?php echo esc_js( __( 'Vas a modificar el robots.txt real del sitio con los bots marcados como Bloquear en la tabla de arriba. ¿Confirmas que ya descargaste la copia y quieres continuar?', 'ai-knowledge' ) ); ?>');">
		<input type="hidden" name="action" value="wookb_apply_robots_block" />
		<?php wp_nonce_field( 'wookb_apply_robots_block' ); ?>
		<?php submit_button( __( 'Aplicar bloqueo a robots.txt', 'ai-knowledge' ), 'delete', 'submit', false, $robots_confirmed ? array( 'data-wookb-apply' => 'robots' ) : array( 'disabled' => 'disabled', 'data-wookb-apply' => 'robots' ) ); ?>
	</form>
<?php endif; ?>

<hr />

<h3><?php esc_html_e( 'Bloqueo real vía .htaccess', 'ai-knowledge' ); ?></h3>
<p class="description">
	<?php esc_html_e( 'robots.txt es una petición educada que un bot puede ignorar. Esto bloquea de verdad a nivel de servidor a los bots marcados como Bloquear en la tabla de arriba.', 'ai-knowledge' ); ?>
</p>
<p><strong><?php esc_html_e( '⚠ Modifica un archivo fuera de este plugin que puede afectar a todo el sitio si algo sale mal. Descarga la copia actual antes de continuar.', 'ai-knowledge' ); ?></strong></p>

<p class="description"><?php esc_html_e( 'Bloque que se insertará (el resto del archivo no se toca)', 'ai-knowledge' ); ?></p>
<textarea readonly rows="6" style="width:100%;max-width:800px;"><?php echo esc_textarea( $htaccess_block_preview ); ?></textarea>

<?php if ( ! $htaccess_available ) : ?>
	<div class="notice notice-warning inline">
		<p><?php esc_html_e( 'No se encontró un .htaccess editable en este servidor (por ejemplo, nginx no lo usa, o los permisos no permiten escribirlo). Añade el bloque de arriba a mano en la configuración de tu servidor.', 'ai-knowledge' ); ?></p>
	</div>
<?php else : ?>
	<p class="description" data-wookb-unlock-notice="htaccess" <?php echo $htaccess_confirmed ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Descarga la copia actual primero (botón de la izquierda); se habilitará automáticamente al terminar.', 'ai-knowledge' ); ?></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wookb-download-form" data-wookb-unlock="htaccess" style="display:inline-block;margin-right:10px;">
		<input type="hidden" name="action" value="wookb_download_htaccess_backup" />
		<?php wp_nonce_field( 'wookb_download_htaccess_backup' ); ?>
		<?php submit_button( __( 'Descargar copia actual de .htaccess', 'ai-knowledge' ), 'secondary', 'submit', false ); ?>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;" onsubmit="return confirm('<?php echo esc_js( __( 'Vas a modificar el .htaccess real del sitio para bloquear los bots marcados como Bloquear en la tabla de arriba. Un error aquí puede afectar a TODO el sitio. ¿Confirmas que ya descargaste la copia y quieres continuar?', 'ai-knowledge' ) ); ?>');">
		<input type="hidden" name="action" value="wookb_apply_htaccess_block" />
		<?php wp_nonce_field( 'wookb_apply_htaccess_block' ); ?>
		<?php submit_button( __( 'Aplicar bloqueo por .htaccess', 'ai-knowledge' ), 'delete', 'submit', false, $htaccess_confirmed ? array( 'data-wookb-apply' => 'htaccess' ) : array( 'disabled' => 'disabled', 'data-wookb-apply' => 'htaccess' ) ); ?>
	</form>
<?php endif; ?>

<hr />

<h3><?php esc_html_e( 'Logs de accesos de crawlers de IA', 'ai-knowledge' ); ?></h3>
<p class="description">
	<?php
	printf(
		/* translators: %d: numero maximo de filas guardadas */
		esc_html__( 'Solo se registra tráfico que coincide con el catálogo de arriba (nunca tráfico humano ni bots desconocidos). Límite duro de %d filas: al superarlo se borran las más antiguas.', 'ai-knowledge' ),
		(int) Crawler_Log::MAX_ROWS
	);
	?>
</p>
<p><strong><?php esc_html_e( 'Total registrado:', 'ai-knowledge' ); ?></strong> <?php echo (int) $crawler_log_total; ?></p>
<?php if ( empty( $crawler_log_rows ) ) : ?>
	<p class="description"><?php esc_html_e( 'Todavía no se ha registrado ningún acceso de un crawler conocido.', 'ai-knowledge' ); ?></p>
<?php else : ?>
	<div style="overflow-x:auto;">
	<table class="wp-list-table widefat striped" style="min-width:700px;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Bot', 'ai-knowledge' ); ?></th>
				<th><?php esc_html_e( 'Categoría', 'ai-knowledge' ); ?></th>
				<th><?php esc_html_e( 'URL', 'ai-knowledge' ); ?></th>
				<th><?php esc_html_e( 'Fecha', 'ai-knowledge' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $crawler_log_rows as $log_row ) : ?>
				<tr>
					<td><?php echo esc_html( $log_row->bot_name ); ?></td>
					<td><?php echo esc_html( $log_row->category ); ?></td>
					<td><?php echo esc_html( $log_row->url ); ?></td>
					<td><?php echo esc_html( $log_row->created_at ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	</div>
<?php endif; ?>

</div><?php // cierra .wookb-crawler-section ?>
