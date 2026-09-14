<?php

namespace WOOKB;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Página de menú "Base de conocimiento IA" con pestañas: Alcance, Exclusiones, Registro, Ajustes, Carga inicial.
 */
class Admin
{

	const CAPABILITY_WOO = 'manage_woocommerce';
	const CAPABILITY_FALLBACK = 'manage_options';

	public static function init()
	{
		add_action('admin_menu', array(__CLASS__, 'menu'));
		add_action('admin_post_wookb_save_scope', array(__CLASS__, 'save_scope'));
		add_action('admin_post_wookb_save_exclusions', array(__CLASS__, 'save_exclusions'));
		add_action('admin_post_wookb_save_settings', array(__CLASS__, 'save_settings'));
		add_action('admin_post_wookb_start_seed', array(__CLASS__, 'start_seed'));
		add_action('admin_post_wookb_cancel_seed', array(__CLASS__, 'cancel_seed'));
		add_action('admin_post_wookb_row_action', array(__CLASS__, 'row_action'));
		add_action('admin_post_wookb_regenerate_single', array(__CLASS__, 'regenerate_single'));
		add_action('admin_post_wookb_sync_chatbot_prompt', array(__CLASS__, 'sync_chatbot_prompt'));
		add_action('admin_post_wookb_generate_prompt_draft', array(__CLASS__, 'generate_prompt_draft'));
		add_action('admin_post_wookb_normalize_prompt', array(__CLASS__, 'normalize_prompt'));
		add_action('admin_post_wookb_save_prompt_draft', array(__CLASS__, 'save_prompt_draft'));
		add_action('admin_post_wookb_sync_store_docs', array(__CLASS__, 'sync_store_docs'));
		add_action('admin_post_wookb_save_llms_faq', array(__CLASS__, 'save_llms_faq'));
		add_action('admin_post_wookb_force_generate', array(__CLASS__, 'force_generate'));
		add_action('admin_post_wookb_delete_all', array(__CLASS__, 'delete_all'));
		add_action('admin_post_wookb_reset_queue', array(__CLASS__, 'reset_queue'));
		add_action('admin_post_wookb_set_manual', array(__CLASS__, 'set_manual'));
		add_action('admin_post_wookb_set_char_limit', array(__CLASS__, 'set_char_limit'));
		add_action('admin_post_wookb_resolve_stale', array(__CLASS__, 'resolve_stale'));
		add_action('admin_post_wookb_back_to_auto', array(__CLASS__, 'back_to_auto'));
		add_action('admin_post_wookb_save_woocommerce_settings', array(__CLASS__, 'save_woocommerce_settings'));
		add_action('admin_enqueue_scripts', array(__CLASS__, 'assets'));
		add_action('admin_notices', array(__CLASS__, 'maybe_stale_notice'));
		add_action('admin_bar_menu', array(__CLASS__, 'admin_bar_stale_node'), 100);
	}

	public static function capability()
	{
		return class_exists('WooCommerce') ? self::CAPABILITY_WOO : self::CAPABILITY_FALLBACK;
	}

	public static function menu()
	{
		$hook = add_menu_page(
			__('Base de conocimiento IA', 'ai-knowledge'),
			__('Base de conocimiento IA', 'ai-knowledge'),
			self::capability(),
			'woo-kb-generator',
			array(__CLASS__, 'render'),
			'dashicons-admin-generic',
			58
		);

		// Bulk actions del Registro: procesadas aqui (load-{hook}, antes de
		// imprimir ningun HTML) en vez de via admin_post.php. Ver
		// maybe_handle_bulk_action() para el porque.
		add_action('load-' . $hook, array(__CLASS__, 'maybe_handle_bulk_action'));
	}

	public static function assets($hook)
	{
		if (false === strpos($hook, 'woo-kb-generator')) {
			return;
		}
		// Orden importa: Tabler primero (variables/base), luego nuestro tema
		// (usa esas variables), luego admin.css (ajustes puntuales que ya
		// existian antes de Tabler, se mantienen por si acaso).
		wp_enqueue_style('wookb-tabler', WOOKB_URL . 'assets/tabler.min.css', array(), WOOKB_VERSION);
		wp_enqueue_style('wookb-theme', WOOKB_URL . 'assets/wookb-theme.css', array('wookb-tabler'), WOOKB_VERSION);
		wp_enqueue_style('wookb-admin', WOOKB_URL . 'assets/admin.css', array('wookb-theme'), WOOKB_VERSION);
		wp_enqueue_script('wookb-admin', WOOKB_URL . 'assets/admin.js', array('jquery'), WOOKB_VERSION, true);
	}

	public static function render()
	{
		if (! current_user_can(self::capability())) {
			wp_die(esc_html__('No tienes permisos suficientes.', 'ai-knowledge'));
		}

		$tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'alcance'; // phpcs:ignore
		$tabs = array(
			'alcance'      => __('Alcance', 'ai-knowledge'),
			'exclusiones'  => __('Exclusiones', 'ai-knowledge'),
			'registro'     => __('Registro', 'ai-knowledge'),
			'prompt'       => __('Prompt', 'ai-knowledge'),
		);
		// Fase 2: pestaña "WooCommerce" solo si WooCommerce esta activo -- sin
		// el, no hay nada real que detectar (moneda, envios, impuestos, pagos)
		// y la pestaña quedaria vacia/confusa.
		if (class_exists('WooCommerce')) {
			$tabs['woocommerce'] = __('WooCommerce', 'ai-knowledge');
		}
		$tabs['ajustes']       = __('Ajustes', 'ai-knowledge');
		$tabs['carga-inicial'] = __('Carga inicial', 'ai-knowledge');

		echo '<div class="wookb-wrap">';
		// Fase 1, arreglo del salto de tema: script inline SINCRONO, impreso
		// justo al abrir .wookb-wrap, antes de que se pinte el resto del
		// contenido. Pone data-bs-theme en este mismo elemento leyendo
		// localStorage/prefers-color-scheme -- misma logica y mismo orden de
		// preferencia que applyTheme()/detectDefaultTheme() en admin.js, pero
		// ejecutado ya (admin.js va en el footer y solo se ejecutaba en
		// $(document).ready, demasiado tarde: se veia primero claro y luego
		// oscuro en cada carga).
		echo '<script>(function(){var w=document.currentScript.parentNode;var t=null;try{t=window.localStorage.getItem("wookb_theme");}catch(e){}if("dark"!==t&&"light"!==t){if(window.matchMedia&&window.matchMedia("(prefers-color-scheme: light)").matches){t="light";}else{t="dark";}}w.setAttribute("data-bs-theme",t);})();</script>';
		echo '<div class="wookb-header-row"><h3>' . esc_html__('Base de conocimiento IA', 'ai-knowledge') . '</h3>';
		echo '<button type="button" class="wookb-theme-toggle"> ' . esc_html__('Modo oscuro', 'ai-knowledge') . '</button></div>';

		if ('registro' === $tab) {
			self::render_registry_summary();
		}

		if (isset($_GET['wookb_notice'])) { // phpcs:ignore
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Guardado.', 'ai-knowledge') . '</p></div>';
		}

		echo '<h2 class="nav-tab-wrapper">';
		foreach ($tabs as $key => $label) {
			$class = ($key === $tab) ? 'nav-tab nav-tab-active' : 'nav-tab';
			$url   = admin_url('admin.php?page=woo-kb-generator&tab=' . $key);
			echo '<a class="' . esc_attr($class) . '" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
		}
		echo '</h2>';

		$view_file = WOOKB_DIR . 'admin/views/tab-' . $tab . '.php';
		if (file_exists($view_file)) {
			// Envoltorio visual "card" de Tabler: solo un <div> alrededor de
			// todo el contenido de la pestaña, no toca nada dentro de la vista
			// (formularios, name=, action=, nonces siguen exactamente igual).
			echo '<div class="wookb-card">';
			require $view_file;
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Resumen de una linea justo debajo del titulo de la pagina (no dentro de
	 * la vista de la pestaña): total, por idioma, por estado. Formato pedido
	 * explicitamente por el usuario.
	 */
	protected static function render_registry_summary()
	{
		require_once WOOKB_DIR . 'admin/class-registry-table.php';
		$summary = Registry::summary();

		$parts_lang = array();
		foreach ($summary['por_idioma'] as $row) {
			$parts_lang[] = esc_html(strtoupper($row->lang)) . ': ' . (int) $row->c;
		}

		$parts_status = array();
		foreach ($summary['por_estado'] as $row) {
			$parts_status[] = esc_html(Registry_Table::status_label($row->status)) . ': ' . (int) $row->c;
		}

		echo '<p>';
		echo '<strong>' . esc_html__('Total de documentos:', 'ai-knowledge') . '</strong> ' . (int) $summary['total'];
		echo '&nbsp;&nbsp;·&nbsp;&nbsp;' . implode('&nbsp;&nbsp;&nbsp;', $parts_lang); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba
		echo '&nbsp;&nbsp;·&nbsp;&nbsp;' . implode('&nbsp;&nbsp;&nbsp;', $parts_status); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado arriba
		echo '</p>';
	}

	protected static function verify($action)
	{
		if (! current_user_can(self::capability())) {
			wp_die(esc_html__('No tienes permisos suficientes.', 'ai-knowledge'));
		}
		check_admin_referer($action);
	}

	protected static function redirect($tab)
	{
		wp_safe_redirect(admin_url('admin.php?page=woo-kb-generator&tab=' . $tab . '&wookb_notice=1'));
		exit;
	}

	public static function save_scope()
	{
		self::verify('wookb_save_scope');

		$post_types = isset($_POST['post_types']) ? array_map('sanitize_key', (array) wp_unslash($_POST['post_types'])) : array(); // phpcs:ignore
		$extra_ids  = isset($_POST['extra_ids']) ? array_filter(array_map('intval', explode(',', sanitize_text_field(wp_unslash($_POST['extra_ids']))))) : array(); // phpcs:ignore

		$tax_terms = array();
		if (! empty($_POST['tax_terms']) && is_array($_POST['tax_terms'])) { // phpcs:ignore
			foreach (wp_unslash($_POST['tax_terms']) as $taxonomy => $terms) { // phpcs:ignore
				$tax_terms[sanitize_key($taxonomy)] = array_map('intval', (array) $terms);
			}
		}

		$custom_fields = array();
		if (! empty($_POST['custom_fields']) && is_array($_POST['custom_fields'])) { // phpcs:ignore
			foreach (wp_unslash($_POST['custom_fields']) as $post_type => $fields) { // phpcs:ignore
				$custom_fields[sanitize_key($post_type)] = array_map('sanitize_text_field', (array) $fields);
			}
		}

		Scope::update_settings(
			array(
				'post_types'    => $post_types,
				'extra_ids'     => $extra_ids,
				'tax_terms'     => $tax_terms,
				'custom_fields' => $custom_fields,
			)
		);

		self::redirect('alcance');
	}

	public static function save_exclusions()
	{
		self::verify('wookb_save_exclusions');

		$exclude_ids = isset($_POST['exclude_ids']) ? array_filter(array_map('intval', explode(',', sanitize_text_field(wp_unslash($_POST['exclude_ids']))))) : array(); // phpcs:ignore

		$exclude_terms = array();
		if (! empty($_POST['exclude_terms']) && is_array($_POST['exclude_terms'])) { // phpcs:ignore
			foreach (wp_unslash($_POST['exclude_terms']) as $taxonomy => $terms) { // phpcs:ignore
				$exclude_terms[sanitize_key($taxonomy)] = array_map('intval', (array) $terms);
			}
		}

		// Sincronía: para cada ID recién excluido, ejecutar borrado de sus documentos.
		$previous = Scope::settings();
		$new_exclusions = array_diff($exclude_ids, (array) $previous['exclude_ids']);
		foreach ($new_exclusions as $id) {
			Sync::delete_documents_for($id);
		}

		Scope::update_settings(
			array(
				'exclude_ids'   => $exclude_ids,
				'exclude_terms' => $exclude_terms,
			)
		);

		self::redirect('exclusiones');
	}

	public static function save_settings()
	{
		self::verify('wookb_save_settings');

		Scope::update_settings(
			array(
				'daily_limit'      => max(1, (int) ($_POST['daily_limit'] ?? 100)), // phpcs:ignore
				'no_limit'         => ! empty($_POST['no_limit']), // phpcs:ignore
				'batch_size'       => max(1, (int) ($_POST['batch_size'] ?? 20)), // phpcs:ignore
				'debounce_seconds' => max(0, (int) ($_POST['debounce_seconds'] ?? 300)), // phpcs:ignore
				'output_tokens'    => max(200, (int) ($_POST['output_tokens'] ?? 2500)), // phpcs:ignore
				'ai_key_source'    => in_array($_POST['ai_key_source'] ?? '', array('genix', 'own'), true) ? sanitize_key($_POST['ai_key_source']) : 'genix', // phpcs:ignore
				'own_api_key'      => isset($_POST['own_api_key']) ? sanitize_text_field(wp_unslash($_POST['own_api_key'])) : '', // phpcs:ignore
				'own_model'        => isset($_POST['own_model']) ? sanitize_text_field(wp_unslash($_POST['own_model'])) : 'gpt-4o-mini', // phpcs:ignore
				'extra_prompt'     => isset($_POST['extra_prompt']) ? sanitize_textarea_field(wp_unslash($_POST['extra_prompt'])) : '', // phpcs:ignore
				'chatbot_docs_list_limit' => max(0, (int) ($_POST['chatbot_docs_list_limit'] ?? Chatbot_Relevance_Guard::DOCS_LIST_LIMIT_DEFAULT)), // phpcs:ignore
				// Fase 1: post_types donde se muestra el meta box del editor.
				// Se guarda siempre que llegue el campo oculto 'editor_button_post_types_submitted'
				// (ver tab-ajustes.php) para poder distinguir "ningun CPT marcado"
				// (array vacio real) de "ajuste nunca guardado" (null, ver Scope::settings()).
				'editor_button_post_types' => isset($_POST['editor_button_post_types_submitted'])
					? (isset($_POST['editor_button_post_types']) ? array_map('sanitize_key', (array) wp_unslash($_POST['editor_button_post_types'])) : array()) // phpcs:ignore
					: Scope::settings()['editor_button_post_types'],
			)
		);

		self::redirect('ajustes');
	}

	public static function start_seed()
	{
		self::verify('wookb_start_seed');
		Queue::start_seed();
		self::redirect('carga-inicial');
	}

	public static function cancel_seed()
	{
		self::verify('wookb_cancel_seed');
		Queue::cancel_seed();
		self::redirect('carga-inicial');
	}

	public static function row_action()
	{
		self::verify('wookb_row_action');

		$id     = isset($_POST['row_id']) ? (int) $_POST['row_id'] : 0; // phpcs:ignore
		$action = isset($_POST['row_op']) ? sanitize_key(wp_unslash($_POST['row_op'])) : ''; // phpcs:ignore

		$row = Registry::find_by_id($id);
		if ($row) {
			if ('regenerate' === $action) {
				Queue::enqueue($row->source_id, $row->lang);
			} elseif ('delete' === $action) {
				Sync::delete_documents_for($row->source_id);
			}
		}

		self::redirect('registro');
	}

	/**
	 * Regenera UNA fila del Registro de forma sincrona, con un limite de
	 * caracteres de uso unico (no se persiste en la fila: el select de la UI
	 * siempre vuelve a mostrar el valor por defecto la proxima vez, por
	 * decision explicita del usuario).
	 *
	 * Deliberadamente NO pasa por Queue::enqueue()/Queue::run_generate(): llama
	 * directamente a Document_Pipeline::process(), así que NO consume el
	 * daily_limit ni respeta el debounce. Criterio: es una correccion puntual
	 * de un documento concreto pedida a mano por el admin, no una carga masiva
	 * (que sí debe respetar el limite diario -- ver maybe_handle_bulk_action()).
	 */
	public static function regenerate_single()
	{
		self::verify('wookb_regenerate_single');

		$id         = isset($_POST['row_id']) ? (int) $_POST['row_id'] : 0; // phpcs:ignore
		$char_limit = isset($_POST['char_limit']) ? (int) $_POST['char_limit'] : 0; // phpcs:ignore
		// Freno de seguridad: aunque el input HTML ya limita a 100-10000, el
		// POST puede manipularse a mano -- no dejar que un valor absurdo
		// dispare un gasto de tokens de IA desproporcionado.
		if ($char_limit > 0) {
			$char_limit = max(100, min(10000, $char_limit));
		}

		$row = Registry::find_by_id($id);
		if ($row) {
			// force=true: el usuario pulso "Generar" explicitamente pidiendo una
			// regeneracion; sin esto, Document_Pipeline::process() se saltaba todo
			// en silencio si el producto de origen no habia cambiado desde la
			// ultima vez (bug real: el boton no hacia nada con ningun limite).
			$result = Document_Pipeline::process($row->source_id, $row->lang, $char_limit > 0 ? $char_limit : null, true);
			if (is_wp_error($result)) {
				wp_safe_redirect(admin_url('admin.php?page=woo-kb-generator&tab=registro&wookb_regen_error=' . rawurlencode($result->get_error_message())));
				exit;
			}
		}

		self::redirect('registro');
	}

	/**
	 * Genera (o regenera) un producto/pagina por ID o URL, de forma sincrona
	 * y en todos los idiomas activos, SIN depender de que exista ya una fila
	 * en el Registro. Cubre el caso "borre el documento y quiero recuperarlo
	 * ya": una vez borrada la fila, el producto ya no aparece en la tabla del
	 * Registro y no hay boton "Generar" al que darle -- este campo aparte
	 * resuelve un ID/URL cualquiera y lo procesa directo, igual que
	 * regenerate_single() pero sin partir de una fila existente.
	 */
	public static function force_generate()
	{
		self::verify('wookb_force_generate');

		$input = isset($_POST['force_id_or_url']) ? trim(wp_unslash($_POST['force_id_or_url'])) : ''; // phpcs:ignore

		$post_id = 0;
		if (is_numeric($input)) {
			$post_id = (int) $input;
		} elseif ('' !== $input) {
			// url_to_postid() solo resuelve URLs del propio sitio; si el usuario
			// pega una URL de otro dominio o mal formada, devuelve 0 igual que
			// "no encontrado", tratado abajo.
			$post_id = url_to_postid($input);
		}

		if (! $post_id || ! get_post($post_id)) {
			wp_safe_redirect(admin_url('admin.php?page=woo-kb-generator&tab=registro&wookb_regen_error=' . rawurlencode(__('No se encontró ningún producto o página con ese ID/URL.', 'ai-knowledge'))));
			exit;
		}

		if (! Scope::is_included($post_id)) {
			wp_safe_redirect(admin_url('admin.php?page=woo-kb-generator&tab=registro&wookb_regen_error=' . rawurlencode(__('Ese contenido no está dentro del alcance configurado del plugin (pestaña Alcance/Exclusiones).', 'ai-knowledge'))));
			exit;
		}

		$errors = array();
		foreach (Wpml::active_languages() as $lang) {
			$result = Document_Pipeline::process($post_id, $lang, null, true);
			if (is_wp_error($result)) {
				$errors[] = $lang . ': ' . $result->get_error_message();
			}
		}

		if ($errors) {
			wp_safe_redirect(admin_url('admin.php?page=woo-kb-generator&tab=registro&wookb_regen_error=' . rawurlencode(implode(' | ', $errors))));
			exit;
		}

		// Misma red de seguridad que en reset_queue(): asegura que las reglas
		// de enrutado del CPT sgkb-docs esten al dia tras generar.
		flush_rewrite_rules();

		self::redirect('registro');
	}

	/**
	 * Borra TODOS los documentos que cumplen el filtro actual (status/lang/
	 * busqueda de la URL) -- o toda la tabla si no hay ningun filtro puesto.
	 * Pedido explicito del usuario tras acumular 587 documentos con muchos
	 * duplicados: necesitaba una forma de vaciar todo y regenerar desde cero,
	 * no solo lo seleccionado a mano fila por fila. La confirmacion fuerte
	 * (JS, ver tab-registro.php) es la unica red de seguridad -- no hay
	 * papelera para esto, borra de verdad Registro + .md + post de Genix.
	 */
	public static function delete_all()
	{
		self::verify('wookb_delete_all');

		$status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : ''; // phpcs:ignore
		$lang   = isset($_POST['lang']) ? sanitize_key(wp_unslash($_POST['lang'])) : ''; // phpcs:ignore
		$search = isset($_POST['s']) ? sanitize_text_field(wp_unslash($_POST['s'])) : ''; // phpcs:ignore

		$ids = Registry::query_all_ids(array('status' => $status, 'lang' => $lang, 'search' => $search));
		foreach ($ids as $row_id) {
			$row = Registry::find_by_id($row_id);
			if ($row) {
				Sync::delete_documents_for($row->source_id);
			}
		}

		self::redirect('registro');
	}

	/**
	 * Tamaño de lote de "Reiniciar cola" por clic. Bug real detectado hoy:
	 * Queue::enqueue() marca la fila 'queued' en el Registro y programa una
	 * accion en Action Scheduler/WP-Cron -- pero si el sitio se cae a mitad
	 * de un lote grande (como paso hoy, dos veces), la fila queda marcada
	 * 'queued' sin que la accion real llegue a programarse o sobreviva.
	 * Resultado: 300+ filas atascadas para siempre, porque nada las va a
	 * procesar. Este boton no confia en el cron: llama a Document_Pipeline::
	 * process() directo (force=true, sin daily_limit). Procesar TODAS de
	 * golpe en una sola peticion arriesgaba agotar el tiempo maximo de
	 * ejecucion de PHP a mitad (30-60s tipico en hosting compartido) con
	 * 300+ llamadas a IA seguidas -- se limita a 20 por clic, mostrando
	 * cuantas quedan, para que el admin pulse varias veces con seguridad.
	 */
	const RESET_QUEUE_BATCH = 20;

	public static function reset_queue()
	{
		self::verify('wookb_reset_queue');

		$ids   = Registry::query_all_ids(array('status' => 'queued'));
		$batch = array_slice($ids, 0, self::RESET_QUEUE_BATCH);

		foreach ($batch as $row_id) {
			$row = Registry::find_by_id($row_id);
			if (! $row) {
				continue;
			}
			Document_Pipeline::process($row->source_id, $row->lang, null, true);
		}

		$remaining = max(0, count($ids) - count($batch));
		$url       = admin_url('admin.php?page=woo-kb-generator&tab=registro');
		if ($remaining > 0) {
			$url = add_query_arg('wookb_queue_remaining', $remaining, $url);
		} else {
			$url = add_query_arg('wookb_notice', 1, $url);
			// Cola vacia: refresco de reglas de enrutado (permalinks). Una sola
			// vez al terminar el lote completo, no por cada documento -- la
			// regla de enrutado es una sola para todo el CPT sgkb-docs, no
			// hace falta repetirla por fila. Red de seguridad para el bug real
			// detectado hoy: reglas desactualizadas hacian que los enlaces del
			// chatbot a estos documentos redirigieran a portada en vez de al
			// documento (ver investigacion redireccion-docs-a-home).
			flush_rewrite_rules();
		}
		wp_safe_redirect($url);
		exit;
	}

	/**
	 * Acciones en bloque desde el Registro: borrar o regenerar los
	 * documentos seleccionados.
	 *
	 * NO se procesa via admin_post.php (a diferencia del resto de acciones
	 * mutables del plugin). Motivo: WP_List_Table renderiza su propio
	 * <select name="action"> (y name="action2") para el desplegable de bulk
	 * actions dentro de $table->display(). Si el <form> tambien llevase el
	 * campo oculto name="action" que usa el patron admin-post.php de todo
	 * el resto del plugin, ambos campos compartirian la misma clave POST:
	 * PHP se queda con el ULTIMO valor repetido al parsear el body, que es
	 * el del <select> (p.ej. "delete"), no "wookb_bulk_action" -- rompiendo
	 * el enrutado de admin-post.php sin avisar (el hook admin_post_delete
	 * no existe, la peticion simplemente no hace nada).
	 *
	 * La solucion nativa de WordPress -- la que usan sus propias listas
	 * (edit.php, etc.) -- es que el formulario se autoenvie a la misma
	 * pagina (sin action= explicito) y se procese en load-{hook} de esa
	 * pagina de menu, ANTES de imprimir HTML, usando WP_List_Table::
	 * current_action() (lee action/action2 y descarta "-1" automaticamente).
	 * Ver Admin::menu() para el enganche del hook.
	 */
	public static function maybe_handle_bulk_action()
	{
		if (! current_user_can(self::capability())) {
			return;
		}

		$tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : ''; // phpcs:ignore
		if ('registro' !== $tab) {
			return;
		}

		// Salida rapida en cualquier carga normal (GET) de la pestaña: solo
		// seguimos si de verdad se envio el formulario de la tabla.
		if (empty($_POST['row_ids']) || ! is_array($_POST['row_ids'])) { // phpcs:ignore
			return;
		}

		require_once WOOKB_DIR . 'admin/class-registry-table.php';
		$table  = new Registry_Table();
		$action = $table->current_action();
		if (! in_array($action, array('delete', 'regenerate'), true)) {
			return;
		}

		// Nonce que WP_List_Table::bulk_actions() ya renderiza solo, con
		// accion 'bulk-' . $args['plural'] ('documentos' en el constructor
		// de Registry_Table -> 'bulk-documentos').
		check_admin_referer('bulk-documentos');

		$row_ids = array_map('intval', wp_unslash($_POST['row_ids'])); // phpcs:ignore
		$skipped_manual = 0;

		foreach ($row_ids as $row_id) {
			$row = Registry::find_by_id($row_id);
			if (! $row) {
				continue;
			}

			if ('delete' === $action) {
				// Reutiliza el mismo borrado sincronizado que la accion individual:
				// fila del Registro + .md + post sgkb-docs de Genix.
				Sync::delete_documents_for($row->source_id);
			} elseif ('regenerate' === $action) {
				// Fase 1: las filas en modo manual no se tocan en la regeneracion
				// en bloque -- su texto lo fijo el admin a mano, "Regenerar
				// seleccionados" no debe pisarlo. Se cuentan para avisar cuantas
				// se saltaron.
				if ('manual' === $row->override_mode) {
					$skipped_manual++;
					continue;
				}
				// Regeneracion MASIVA: cambiado de Queue::enqueue() (asincrono, via
				// Action Scheduler/WP-Cron con debounce) a llamada sincrona directa,
				// por peticion explicita del usuario: encolar dejaba la fila en
				// "En cola" indefinidamente si el cron no se disparaba (poco trafico
				// en staging), sin regenerar nunca el .md ni el post real -- aunque
				// la fecha de la fila si cambiaba (por el propio enqueue()), dando la
				// falsa impresion de que "algo paso". force=true por el mismo motivo
				// que regenerate_single(): es una accion manual explicita del admin,
				// no debe saltarse por hash sin cambios. NO respeta daily_limit a
				// proposito (pedido explicito: "regenerar todas sin mirar limite").
				Document_Pipeline::process($row->source_id, $row->lang, null, true);
			}
		}

		$redirect = admin_url('admin.php?page=woo-kb-generator&tab=registro&wookb_notice=1');
		if ($skipped_manual > 0) {
			$redirect = add_query_arg('wookb_skipped_manual', $skipped_manual, $redirect);
		}
		wp_safe_redirect($redirect);
		exit;
	}

	public static function sync_chatbot_prompt()
	{
		self::verify('wookb_sync_chatbot_prompt');
		$result = Chatbot_Prompt::sync(true);
		wp_safe_redirect(admin_url('admin.php?page=woo-kb-generator&tab=ajustes&wookb_notice=1&wookb_sync_status=' . rawurlencode($result['status'])));
		exit;
	}

	/**
	 * Genera un borrador con IA a partir del cuestionario (+ paginas de
	 * referencia opcionales) y lo deja en un transient para mostrarlo en el
	 * textarea editable de la pestana Prompt -- no toca el .md todavia.
	 */
	public static function generate_prompt_draft()
	{
		self::verify('wookb_generate_prompt_draft');

		$raw_answers = isset($_POST['answers']) && is_array($_POST['answers']) ? wp_unslash($_POST['answers']) : array(); // phpcs:ignore
		$answers     = Chatbot_Prompt_Builder::save_answers($raw_answers);

		$reference_raw   = isset($_POST['reference_pages']) ? sanitize_text_field(wp_unslash($_POST['reference_pages'])) : ''; // phpcs:ignore
		$reference_pages = Chatbot_Prompt_Builder::fetch_reference_content($reference_raw);

		$draft = Chatbot_Prompt_Builder::generate_draft($answers, $reference_pages);

		if (is_wp_error($draft)) {
			set_transient('wookb_prompt_draft_error', $draft->get_error_message(), MINUTE_IN_SECONDS);
		} else {
			set_transient('wookb_prompt_draft', $draft, HOUR_IN_SECONDS);
		}

		wp_safe_redirect(admin_url('admin.php?page=woo-kb-generator&tab=prompt'));
		exit;
	}

	/**
	 * Pule el texto que el usuario ya edito en el textarea (post 'draft'),
	 * sin cambiar decisiones de fondo.
	 */
	public static function normalize_prompt()
	{
		self::verify('wookb_normalize_prompt');

		$draft = isset($_POST['draft']) ? sanitize_textarea_field(wp_unslash($_POST['draft'])) : ''; // phpcs:ignore
		$result = Chatbot_Prompt_Builder::normalize($draft);

		if (is_wp_error($result)) {
			set_transient('wookb_prompt_draft_error', $result->get_error_message(), MINUTE_IN_SECONDS);
			set_transient('wookb_prompt_draft', $draft, HOUR_IN_SECONDS);
		} else {
			set_transient('wookb_prompt_draft', $result, HOUR_IN_SECONDS);
		}

		wp_safe_redirect(admin_url('admin.php?page=woo-kb-generator&tab=prompt'));
		exit;
	}

	/**
	 * Guarda el texto final (ya editado/normalizado por el usuario) en
	 * chatbot-system-prompt.md y sincroniza con Genix, reusando el mecanismo
	 * existente de Chatbot_Prompt.
	 */
	public static function save_prompt_draft()
	{
		self::verify('wookb_save_prompt_draft');

		$draft = isset($_POST['draft']) ? sanitize_textarea_field(wp_unslash($_POST['draft'])) : ''; // phpcs:ignore
		if ('' === trim($draft)) {
			wp_safe_redirect(admin_url('admin.php?page=woo-kb-generator&tab=prompt'));
			exit;
		}

		file_put_contents(Chatbot_Prompt::file_path(), $draft . "\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_put_contents
		Chatbot_Prompt::sync(true);
		Chatbot_Prompt_Builder::write_info_doc();
		delete_transient('wookb_prompt_draft');

		wp_safe_redirect(admin_url('admin.php?page=woo-kb-generator&tab=prompt&wookb_notice=1'));
		exit;
	}

	/**
	 * Genera/actualiza los documentos compuestos de tienda (Tarea 1 y 1.1 de
	 * Store_Info_Doc) para todos los idiomas activos de WPML. Se dispara a
	 * mano desde el admin (botón en la pestaña WooCommerce, movido ahí en la
	 * Fase 2 -- antes vivía en Ajustes) en vez de encolarse automáticamente
	 * como las fichas de producto: no dependen de ningún hook de guardado de
	 * post (no hay "post" que editar), así que no hay un evento natural que
	 * lo dispare -- se regeneran cuando el admin lo pide, o se podría añadir
	 * un cron propio más adelante si conviene mantenerlos al día solos (fuera
	 * del alcance de esta tarea).
	 */
	public static function sync_store_docs()
	{
		self::verify('wookb_sync_store_docs');

		$summary = Store_Info_Doc::generate_all();

		if (! empty($summary['errores'])) {
			set_transient('wookb_store_docs_error', implode(' | ', $summary['errores']), MINUTE_IN_SECONDS);
		} else {
			delete_transient('wookb_store_docs_error');
		}

		wp_safe_redirect(admin_url('admin.php?page=woo-kb-generator&tab=woocommerce&wookb_notice=1'));
		exit;
	}

	/**
	 * Fase 2: guarda la sección "Rellenar a mano" de la pestaña WooCommerce
	 * (plazo de entrega en texto libre, notas legales adicionales). El
	 * contacto/horario NO se guarda aquí -- se reutiliza tal cual desde
	 * Chatbot_Prompt_Builder (ver tab-woocommerce.php), no se duplica.
	 */
	public static function save_woocommerce_settings()
	{
		self::verify('wookb_save_woocommerce_settings');

		Scope::update_settings(
			array(
				'delivery_time_note' => isset($_POST['delivery_time_note']) ? sanitize_textarea_field(wp_unslash($_POST['delivery_time_note'])) : '', // phpcs:ignore
				'legal_notes_extra'  => isset($_POST['legal_notes_extra']) ? sanitize_textarea_field(wp_unslash($_POST['legal_notes_extra'])) : '', // phpcs:ignore
			)
		);

		self::redirect('woocommerce');
	}

	/**
	 * Guarda el contenido de la sección "## Preguntas frecuentes" pública de
	 * llms.txt. Delega en Llms_Faq::save() (ver class-llms-faq.php para el
	 * porqué de un archivo propio, distinto de chatbot-system-prompt.md).
	 */
	public static function save_llms_faq()
	{
		self::verify('wookb_save_llms_faq');

		$content = isset($_POST['llms_faq']) ? sanitize_textarea_field(wp_unslash($_POST['llms_faq'])) : ''; // phpcs:ignore
		Llms_Faq::save($content);

		self::redirect('ajustes');
	}

	/**
	 * Fase 1: pasa una fila del Registro a modo manual (o guarda un nuevo
	 * texto en una fila que ya estaba en modo manual: es la MISMA accion,
	 * pedido explicito -- "pasar a manual" y "editar el texto manual" no son
	 * dos acciones distintas, siempre guarda + publica). Guarda tal cual el
	 * texto que el admin dejo en el textarea Y lo publica de verdad: reescribe
	 * el .md publico y, si Support Genix esta activo, el post sgkb-docs -- sin
	 * pasar por Generator ni por IA en ningun momento (ver publish_manual_text()).
	 * stale se reinicia a 0: se acaba de fijar el texto a mano, todavia no hay
	 * divergencia que avisar.
	 */
	public static function set_manual()
	{
		self::verify('wookb_set_manual');

		$id   = isset($_POST['row_id']) ? (int) $_POST['row_id'] : 0; // phpcs:ignore
		$text = isset($_POST['override_text']) ? sanitize_textarea_field(wp_unslash($_POST['override_text'])) : ''; // phpcs:ignore

		$row = Registry::find_by_id($id);
		if ($row) {
			self::publish_manual_text($row, $text);
		}

		self::redirect('registro');
	}

	/**
	 * Publica de verdad el texto manual de una fila: reescribe el .md publico
	 * (Markdown_Store::write(), mismo slug y mismos campos de front matter que
	 * usa Document_Pipeline::process() para esta misma fila) y, si Support
	 * Genix esta disponible, actualiza el post sgkb-docs existente reutilizando
	 * doc_post_id/doc_trid ya guardados (mismo patron que el pipeline). NO pasa
	 * por Generator ni llama a ninguna IA: $text es el contenido final tal
	 * cual lo dejo el admin.
	 *
	 * source_hash de la fila NO se toca aqui a proposito: sigue siendo el hash
	 * del ORIGEN (producto/pagina) calculado la ultima vez que se generó o se
	 * comprobó -- es el valor contra el que Document_Pipeline::process()
	 * compara para decidir si marca 'stale', y debe seguir reflejando el
	 * origen, no el texto manual.
	 */
	protected static function publish_manual_text($row, $text)
	{
		$post        = get_post($row->source_id);
		$product_url = $post ? get_permalink($row->source_id) : '';

		// Fallback si el origen ya no resuelve a un post real (source_id
		// centinela de documentos compuestos, o post borrado): reutiliza la
		// URL que ya quedo guardada en el front matter del .md anterior, si lo
		// hay, en vez de dejarla vacia.
		if ('' === $product_url && $row->md_path) {
			$raw = Markdown_Store::read($row->md_path);
			if ($raw && preg_match('/^product_url:\s*"?([^"\n]*)"?\s*$/m', $raw, $m)) {
				$product_url = trim($m[1], '" ');
			}
		}

		$slug     = Markdown_Store::slug_for($row->source_id, $row->lang);
		$relative = Markdown_Store::write(
			$row->lang,
			$slug,
			$text,
			array(
				'source_id'    => $row->source_id,
				'source_type'  => $row->source_type,
				'lang'         => $row->lang,
				'source_hash'  => $row->source_hash,
				'generated_at' => current_time('mysql'),
				'product_url'  => $product_url,
				'bridge'       => (bool) $row->is_bridge,
			)
		);

		$doc_post_id = $row->doc_post_id;
		if (Genix_Bridge::is_available()) {
			$data = array(
				'title' => $post ? get_the_title($row->source_id) : ($row->source_type . ' #' . $row->source_id),
				'id'    => $row->source_id,
				'url'   => $product_url,
			);
			$result = Genix_Bridge::upsert_document(
				$row->doc_post_id,
				$data,
				$text,
				$row->lang,
				$row->doc_trid
			);
			if (! is_wp_error($result)) {
				$doc_post_id = $result;
			}
		}

		Registry::upsert(
			array(
				'source_id'     => $row->source_id,
				'lang'          => $row->lang,
				'override_mode' => 'manual',
				'override_text' => $text,
				'stale'         => 0,
				'md_path'       => $relative,
				'doc_post_id'   => $doc_post_id,
				'status'        => 'synced',
				'generated_at'  => current_time('mysql'),
			)
		);

		Llms_Txt::invalidate();
	}

	/**
	 * Fase 1: guarda el limite de caracteres propio de una fila (vacio/0 =
	 * usa el limite general de Generator::BODY_CHAR_LIMIT). Mismo freno de
	 * seguridad 100-10000 que ya usa regenerate_single(): el input HTML ya lo
	 * limita, pero el POST puede manipularse a mano.
	 */
	public static function set_char_limit()
	{
		self::verify('wookb_set_char_limit');

		$id         = isset($_POST['row_id']) ? (int) $_POST['row_id'] : 0; // phpcs:ignore
		$char_limit = isset($_POST['char_limit']) ? (int) $_POST['char_limit'] : 0; // phpcs:ignore
		if ($char_limit > 0) {
			$char_limit = max(100, min(10000, $char_limit));
		} else {
			$char_limit = null;
		}

		$row = Registry::find_by_id($id);
		if ($row) {
			Registry::upsert(
				array(
					'source_id'  => $row->source_id,
					'lang'       => $row->lang,
					'char_limit' => $char_limit,
				)
			);
		}

		self::redirect('registro');
	}

	/**
	 * Fase 1: el admin revisa el aviso de "origen actualizado" (stale) y
	 * decide mantener el texto manual tal cual, sin regenerar. Solo apaga el
	 * aviso, no toca override_mode ni override_text.
	 */
	public static function resolve_stale()
	{
		self::verify('wookb_resolve_stale');

		$id  = isset($_POST['row_id']) ? (int) $_POST['row_id'] : 0; // phpcs:ignore
		$row = Registry::find_by_id($id);
		if ($row) {
			Registry::upsert(
				array(
					'source_id' => $row->source_id,
					'lang'      => $row->lang,
					'stale'     => 0,
				)
			);
		}

		self::redirect('registro');
	}

	/**
	 * Fase 1: vuelve una fila a modo automatico y fuerza una regeneracion
	 * inmediata con IA (force=true, mismo motivo que regenerate_single():
	 * es una accion manual explicita del admin, no debe saltarse por hash sin
	 * cambios). El cambio a 'auto' se guarda ANTES de llamar a
	 * Document_Pipeline::process(), para que esa llamada ya no se tope con la
	 * comprobacion de modo manual del propio pipeline.
	 */
	public static function back_to_auto()
	{
		self::verify('wookb_back_to_auto');

		$id  = isset($_POST['row_id']) ? (int) $_POST['row_id'] : 0; // phpcs:ignore
		$row = Registry::find_by_id($id);
		if ($row) {
			Registry::upsert(
				array(
					'source_id'     => $row->source_id,
					'lang'          => $row->lang,
					'override_mode' => 'auto',
					'override_text' => null,
					'stale'         => 0,
				)
			);
			$result = Document_Pipeline::process($row->source_id, $row->lang, null, true);
			if (is_wp_error($result)) {
				wp_safe_redirect(admin_url('admin.php?page=woo-kb-generator&tab=registro&wookb_regen_error=' . rawurlencode($result->get_error_message())));
				exit;
			}
		}

		self::redirect('registro');
	}

	/**
	 * Aviso de "stale" (Fase 1): banner en cualquier pantalla del propio
	 * plugin si hay al menos una fila con el origen cambiado en modo manual.
	 */
	public static function maybe_stale_notice()
	{
		if (! current_user_can(self::capability())) {
			return;
		}
		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		if (! $screen || false === strpos((string) $screen->id, 'woo-kb-generator')) {
			return;
		}

		$count = Registry::count(array('stale' => 1));
		if ($count < 1) {
			return;
		}

		$url = admin_url('admin.php?page=woo-kb-generator&tab=registro&stale=1');
		echo '<div class="notice notice-warning"><p>';
		printf(
			/* translators: %d: numero de documentos con el origen actualizado desde que se fijaron a mano */
			esc_html(_n('%d documento en modo manual tiene el origen actualizado desde que se fijó el texto. %s', '%d documentos en modo manual tienen el origen actualizado desde que se fijó el texto. %s', $count, 'ai-knowledge')),
			(int) $count,
			'<a href="' . esc_url($url) . '">' . esc_html__('Revisar en el Registro', 'ai-knowledge') . '</a>'
		);
		echo '</p></div>';
	}

	/**
	 * Nodo en la barra de admin (Fase 1) con el contador de filas 'stale',
	 * visible solo para quien tiene la capability del plugin.
	 */
	public static function admin_bar_stale_node($wp_admin_bar)
	{
		if (! current_user_can(self::capability())) {
			return;
		}

		$count = Registry::count(array('stale' => 1));
		if ($count < 1) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'wookb-stale',
				'title' => sprintf(
					/* translators: %d: numero de documentos con el origen actualizado desde que se fijaron a mano */
					esc_html__('KB IA: %d desactualizado(s)', 'ai-knowledge'),
					(int) $count
				),
				'href'  => admin_url('admin.php?page=woo-kb-generator&tab=registro&stale=1'),
			)
		);
	}
}
