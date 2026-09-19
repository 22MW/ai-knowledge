<?php
namespace AIKB;

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
$llms_backup_confirmed = (bool) get_transient( 'wookb_llms_backup_confirmed_' . get_current_user_id() );
$htaccess_available = Htaccess_Guard::is_available();
$htaccess_confirmed = Htaccess_Guard::backup_confirmed();

$crawler_saved_actions = Crawler_Catalog::effective_actions();
$crawler_visibility_mode = Scope::settings()['crawler_visibility_mode'];

// Fase 11, pieza 3: ultimos accesos registrados del catalogo de crawlers.
$crawler_log_rows = Crawler_Log::recent( 50 );
$crawler_log_total = Crawler_Log::count_rows();

// Fase 11 (revision UX): vista previa del bloque que se insertaria con la
// configuracion actual de la tabla -- no es una simulacion del archivo
// completo (insert_with_markers ya garantiza que solo se reemplaza el
// bloque propio, el resto del archivo queda intacto), solo el contenido
// exacto que ira dentro del marcador, para que el usuario vea el efecto de
// sus elecciones antes de aplicar.
$crawler_actions = Crawler_Catalog::effective_actions();
$crawler_blocked_bots  = array_keys( array_filter( $crawler_actions, function ( $action ) { return 'block' === $action; } ) );
$robots_block_preview   = implode( "\n", Robots_Txt_Guard::build_action_rules( $crawler_actions, $crawler_visibility_mode ) );
$robots_full_preview = Robots_Txt_Guard::generate_full_file( $crawler_actions, $crawler_visibility_mode );
// "RewriteEngine On" se antepone en Htaccess_Guard::apply_block() al escribir
// de verdad; se replica aqui solo para que la vista previa sea fiel a lo que
// se escribira.
$htaccess_block_preview = "RewriteEngine On\n" . implode( "\n", Htaccess_Guard::build_action_rules( $crawler_actions, $crawler_visibility_mode ) );
$htaccess_full_preview = Htaccess_Guard::generate_full_file( $crawler_actions, $crawler_visibility_mode );
$htaccess_current_content = Htaccess_Guard::is_available() ? (string) file_get_contents( Htaccess_Guard::path() ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
$robots_conflicts = Robots_Txt_Guard::action_conflicts( $crawler_actions );
$htaccess_conflicts = Htaccess_Guard::conflicts( $crawler_blocked_bots, $crawler_visibility_mode );
?>

<p class="description">
	<?php esc_html_e( 'Comprueba cómo se publica el contenido para buscadores y agentes de IA, revisa si las páginas sincronizadas pueden rastrearse y decide qué crawlers de IA pueden acceder a la web. El estado de exposición y la comprobación de accesibilidad son de solo lectura. Las acciones sobre robots.txt y .htaccess modifican archivos reales y siempre requieren confirmación.', 'ai-knowledge' ); ?>
</p>
<?php Admin::documentation_link( 'visibilidad-ia' ); ?>

<h2><?php esc_html_e( 'Estado de exposición', 'ai-knowledge' ); ?></h2>
<table class="form-table">
	<tr>
		<th><?php esc_html_e( 'llms.txt', 'ai-knowledge' ); ?></th>
		<td>
			<p class="description"><?php esc_html_e( 'Este archivo ayuda a los asistentes de IA a entender qué contiene tu web y dónde encontrar la información más importante.', 'ai-knowledge' ); ?></p>
			<?php if ( Llms_Txt::physical_file_exists() ) : ?>
				<span class="description"><?php esc_html_e( 'Archivo físico gestionado por AI Knowledge.', 'ai-knowledge' ); ?></span>
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
				<p class="description"><a href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Ver el archivo actual', 'ai-knowledge' ); ?></a></p>
			<?php else : ?>
				<p><strong><?php esc_html_e( 'Estado:', 'ai-knowledge' ); ?></strong> <?php esc_html_e( 'Todavía no existe el archivo físico.', 'ai-knowledge' ); ?></p>
				<a href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Ver llms.txt', 'ai-knowledge' ); ?></a>
			<?php endif; ?>
			<p><strong><?php esc_html_e( 'Guardar una copia física', 'ai-knowledge' ); ?></strong></p>
			<p class="description"><?php esc_html_e( 'AI Knowledge puede guardar llms.txt como archivo real en la raíz de tu web.', 'ai-knowledge' ); ?></p>
			<?php if ( Llms_Txt::physical_file_exists() ) : ?>
				<p class="description"><?php esc_html_e( 'Ya existe un llms.txt. Descarga una copia antes de sustituirlo.', 'ai-knowledge' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wookb-download-form" data-wookb-unlock="llms" style="display:inline-block;margin-right:10px;">
					<input type="hidden" name="action" value="wookb_download_llms_backup" />
					<?php wp_nonce_field( 'wookb_download_llms_backup' ); ?>
					<?php submit_button( __( 'Descargar copia actual de llms.txt', 'ai-knowledge' ), 'secondary', 'submit', false ); ?>
				</form>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'Todavía no existe un archivo físico. Puedes crearlo con el contenido generado por AI Knowledge.', 'ai-knowledge' ); ?></p>
			<?php endif; ?>
			<p class="description" data-wookb-unlock-notice="llms" <?php echo ( ! Llms_Txt::physical_file_exists() || $llms_backup_confirmed ) ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Descarga la copia actual primero; el botón se habilitará automáticamente al terminar.', 'ai-knowledge' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;" onsubmit="return confirm('<?php echo esc_js( Llms_Txt::physical_file_exists() ? __( 'Vas a sustituir el llms.txt físico de la raíz por el generado por AI Knowledge. ¿Confirmas que ya descargaste la copia?', 'ai-knowledge' ) : __( 'Vas a crear un llms.txt físico en la raíz con el contenido generado por AI Knowledge. ¿Quieres continuar?', 'ai-knowledge' ) ); ?>');">
				<input type="hidden" name="action" value="wookb_apply_llms_physical" />
				<?php wp_nonce_field( 'wookb_apply_llms_physical' ); ?>
				<?php submit_button( Llms_Txt::physical_file_exists() ? __( 'Sustituir por el generado', 'ai-knowledge' ) : __( 'Crear archivo físico', 'ai-knowledge' ), Llms_Txt::physical_file_exists() ? 'delete' : 'primary', 'submit', false, ( ! Llms_Txt::physical_file_exists() || $llms_backup_confirmed ) ? array( 'data-wookb-apply' => 'llms' ) : array( 'disabled' => 'disabled', 'data-wookb-apply' => 'llms' ) ); ?>
			</form>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Markdown público', 'ai-knowledge' ); ?></th>
		<td>
			<span class="description"><?php esc_html_e( 'El mismo contenido que usa el chatbot, publicado como archivo Markdown para que crawlers y agentes de IA puedan leerlo sin procesar el HTML de la página.', 'ai-knowledge' ); ?></span>
			<?php if ( $example_row && $example_row->md_path ) : ?>
				<br /><a href="<?php echo esc_url( Markdown_Store::public_url( $example_row->md_path ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Ver un ejemplo real', 'ai-knowledge' ); ?></a>
			<?php endif; ?>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'JSON estructurado', 'ai-knowledge' ); ?></th>
		<td>
			<span class="description"><?php esc_html_e( 'Los datos originales de WordPress y WooCommerce en formato JSON, sin pasar por una IA. Permite consultar la información exacta mediante la API del sitio.', 'ai-knowledge' ); ?></span>
			<?php if ( $example_row ) : ?>
				<br /><a href="<?php echo esc_url( rest_url( 'ai-knowledge/v1/content/' . $example_row->source_id ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Ver un ejemplo real', 'ai-knowledge' ); ?></a>
			<?php endif; ?>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'JSON-LD (Schema.org)', 'ai-knowledge' ); ?></th>
		<td>
			<span class="description"><?php esc_html_e( 'Datos estructurados de Schema.org, como Product o Article, incluidos en el código de cada página para ayudar a los buscadores a identificar el tipo de contenido.', 'ai-knowledge' ); ?></span>
			<?php if ( $example_row ) :
				$example_url = get_permalink( $example_row->source_id );
				?>
				<?php if ( $example_url ) : ?>
					<br />
					<?php
					printf(
						/* translators: %s: enlace a una página de ejemplo */
						esc_html__( 'Se incluye dentro de cada página: %s y revisa en el código fuente el bloque <script type="application/ld+json">.', 'ai-knowledge' ),
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
	<?php esc_html_e( 'Lista de crawlers de IA conocidos, su finalidad y la acción asignada a cada uno. Esta configuración se aplica tanto a robots.txt como a las reglas de .htaccess que aparecen más abajo.', 'ai-knowledge' ); ?>
</p>
<?php
$category_labels = array(
	'ai_search'                => __( 'Búsqueda/citas IA', 'ai-knowledge' ),
	'user_requested_assistant' => __( 'Uso bajo demanda', 'ai-knowledge' ),
	'model_training'           => __( 'Entrenamiento de modelos', 'ai-knowledge' ),
	'seo_scraper'              => __( 'SEO y scraping', 'ai-knowledge' ),
	'security_scanner'         => __( 'Scanners de seguridad', 'ai-knowledge' ),
	'traditional_search'       => __( 'Buscadores tradicionales', 'ai-knowledge' ),
	'archive_dataset'          => __( 'Archivado y datasets', 'ai-knowledge' ),
);
?>
<div class="wookb-crawler-filters" data-wookb-crawler-filters>
	<label>
		<span class="screen-reader-text"><?php esc_html_e( 'Filtrar por tipo', 'ai-knowledge' ); ?></span>
		<select data-wookb-crawler-filter="category">
			<option value="all"><?php esc_html_e( 'Todos los tipos', 'ai-knowledge' ); ?></option>
			<?php foreach ( $category_labels as $category => $label ) : ?>
				<option value="<?php echo esc_attr( $category ); ?>"><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
	</label>
	<label>
		<span class="screen-reader-text"><?php esc_html_e( 'Filtrar por estado', 'ai-knowledge' ); ?></span>
		<select data-wookb-crawler-filter="action">
			<option value="all"><?php esc_html_e( 'Todos los estados', 'ai-knowledge' ); ?></option>
			<option value="allow"><?php esc_html_e( 'Permitidos', 'ai-knowledge' ); ?></option>
			<option value="block"><?php esc_html_e( 'Bloqueados', 'ai-knowledge' ); ?></option>
		</select>
	</label>
	<span class="description" data-wookb-crawler-count aria-live="polite"></span>
</div>
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
			<?php foreach ( Crawler_Catalog::all() as $index => $entry ) :
				$ua       = $entry['user_agent'];
				$current  = isset( $crawler_saved_actions[ $ua ] ) ? $crawler_saved_actions[ $ua ] : $entry['default_action'];
				?>
				<tr data-wookb-crawler-row data-crawler-index="<?php echo (int) $index; ?>" data-crawler-category="<?php echo esc_attr( $entry['category'] ); ?>" data-crawler-action="<?php echo esc_attr( $current ); ?>">
					<td><?php echo esc_html( $ua ); ?></td>
					<td><?php echo esc_html( $entry['operator'] ); ?></td>
					<td><?php echo esc_html( isset( $category_labels[ $entry['category'] ] ) ? $category_labels[ $entry['category'] ] : $entry['category'] ); ?></td>
					<td><?php echo esc_html( $entry['description'] ); ?></td>
					<td>
						<select name="crawler_action[<?php echo esc_attr( $ua ); ?>]">
							<option value="allow" <?php selected( 'allow', $current ); ?>><?php esc_html_e( 'Permitir', 'ai-knowledge' ); ?></option>
							<option value="block" <?php selected( 'block', $current ); ?>><?php esc_html_e( 'Bloquear', 'ai-knowledge' ); ?></option>
						</select>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	</div>
	<div class="submit-row wookb-crawler-actions">
		<?php submit_button( __( 'Guardar configuración de crawlers', 'ai-knowledge' ), 'primary', 'submit', false ); ?>
		<button type="button" class="button" data-wookb-crawler-toggle><?php esc_html_e( 'Ver todos los crawlers', 'ai-knowledge' ); ?></button>
	</div>
</form>

<h3><?php esc_html_e( 'Visibilidad para los bots bloqueados', 'ai-knowledge' ); ?></h3>
<p class="description"><?php esc_html_e( 'Puedes bloquear el resto del sitio y mantener visible únicamente /llms.txt para los bots configurados como Bloquear.', 'ai-knowledge' ); ?></p>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="wookb_save_crawler_visibility" />
	<?php wp_nonce_field( 'wookb_save_crawler_visibility' ); ?>
	<select name="crawler_visibility_mode">
		<option value="site" <?php selected( 'site', $crawler_visibility_mode ); ?>><?php esc_html_e( 'Bloquear el sitio completo', 'ai-knowledge' ); ?></option>
		<option value="llms_only" <?php selected( 'llms_only', $crawler_visibility_mode ); ?>><?php esc_html_e( 'Solo permitir visibilidad de llms.txt', 'ai-knowledge' ); ?></option>
	</select>
	<?php submit_button( __( 'Guardar modo de visibilidad', 'ai-knowledge' ), 'secondary', 'submit', false ); ?>
</form>

<h3><?php esc_html_e( 'robots.txt', 'ai-knowledge' ); ?></h3>
<?php if ( $robots_conflicts ) : ?><div class="notice notice-warning inline"><p><?php esc_html_e( 'Se han detectado reglas originales que contradicen el bloque propuesto. Al aplicar, se conservarán y se comentarán para dejar constancia del conflicto:', 'ai-knowledge' ); ?></p><ul><?php foreach ( $robots_conflicts as $conflict ) : ?><li><code><?php echo esc_html( $conflict ); ?></code></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="wookb-crawler-compare">
	<div>
		<p class="description"><?php esc_html_e( 'Actual (lo que vería un crawler ahora mismo)', 'ai-knowledge' ); ?></p>
		<?php if ( $robots_txt_error ) : ?>
			<div class="notice notice-error inline"><p><?php echo esc_html( $robots_txt_error ); ?></p></div>
		<?php else : ?>
			<textarea readonly rows="12"><?php echo esc_textarea( $robots_txt_content ); ?></textarea>
		<?php endif; ?>
	</div>
	<div>
		<p class="description"><?php esc_html_e( 'Archivo completo después del cambio', 'ai-knowledge' ); ?></p>
		<textarea readonly rows="12"><?php echo esc_textarea( $robots_full_preview ); ?></textarea>
	</div>
</div>

<p><strong><?php esc_html_e( '⚠ Esta acción modifica el archivo robots.txt del sitio. Descarga una copia antes de continuar.', 'ai-knowledge' ); ?></strong></p>

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

<h3><?php esc_html_e( 'Bloqueo en el servidor mediante .htaccess', 'ai-knowledge' ); ?></h3>
<?php if ( $htaccess_conflicts ) : ?><div class="notice notice-warning inline"><p><?php esc_html_e( 'Se han detectado reglas originales de .htaccess que contradicen el bloque propuesto. Al aplicar, se conservarán y se comentarán.', 'ai-knowledge' ); ?></p><ul><?php foreach ( $htaccess_conflicts as $conflict ) : ?><li><code><?php echo esc_html( $conflict ); ?></code></li><?php endforeach; ?></ul></div><?php endif; ?>
<p class="description">
	<?php esc_html_e( 'robots.txt comunica preferencias de rastreo, pero un bot puede ignorarlas. Estas reglas rechazan en el servidor las solicitudes que se identifican como alguno de los bots marcados como Bloquear.', 'ai-knowledge' ); ?>
</p>
<p><strong><?php esc_html_e( '⚠ Modifica un archivo fuera de este plugin que puede afectar a todo el sitio si algo sale mal. Descarga la copia actual antes de continuar.', 'ai-knowledge' ); ?></strong></p>

<p class="description"><?php esc_html_e( 'Archivo completo preparado. Puedes copiarlo y pegarlo manualmente en tu servidor o descargarlo. AI Knowledge no sobrescribe el .htaccess real.', 'ai-knowledge' ); ?></p>
<p class="description"><?php esc_html_e( 'Archivo actual', 'ai-knowledge' ); ?></p>
<textarea readonly rows="16" style="width:100%;max-width:1000px;"><?php echo esc_textarea( $htaccess_current_content ); ?></textarea>
<p class="description"><?php esc_html_e( 'Archivo completo después del cambio', 'ai-knowledge' ); ?></p>
<textarea readonly rows="16" style="width:100%;max-width:1000px;" id="wookb-generated-htaccess"><?php echo esc_textarea( $htaccess_full_preview ); ?></textarea>
<p><button type="button" class="button" data-wookb-copy-target="wookb-generated-htaccess"><?php esc_html_e( 'Copiar código', 'ai-knowledge' ); ?></button>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-left:8px;">
	<input type="hidden" name="action" value="wookb_download_htaccess_generated" />
	<?php wp_nonce_field( 'wookb_download_htaccess_generated' ); ?>
	<?php submit_button( __( 'Descargar .htaccess preparado', 'ai-knowledge' ), 'secondary', 'submit', false ); ?>
</form></p>

<?php if ( ! $htaccess_available ) : ?>
	<div class="notice notice-warning inline">
		<p><?php esc_html_e( 'No se encontró un .htaccess editable en este servidor (por ejemplo, nginx no lo usa, o los permisos no permiten escribirlo). Añade el bloque de arriba a mano en la configuración de tu servidor.', 'ai-knowledge' ); ?></p>
	</div>
<?php else : ?>
	<p class="description"><?php esc_html_e( 'El archivo real no se modifica desde aquí. Copia el contenido generado o descarga el archivo preparado y sustitúyelo manualmente después de conservar tu copia original.', 'ai-knowledge' ); ?></p>
<?php endif; ?>

<hr />

<h3><?php esc_html_e( 'Registro de accesos de crawlers de IA', 'ai-knowledge' ); ?></h3>
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
					<td><?php echo esc_html( isset( $category_labels[ $log_row->category ] ) ? $category_labels[ $log_row->category ] : $log_row->category ); ?></td>
					<td><?php echo esc_html( $log_row->url ); ?></td>
					<td><?php echo esc_html( $log_row->created_at ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	</div>
<?php endif; ?>

</div><?php // cierra .wookb-crawler-section ?>
