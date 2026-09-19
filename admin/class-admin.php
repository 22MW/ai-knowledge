<?php

namespace AIKB;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Página de menú "Base de conocimiento IA" con sus pestañas de contenido, generación y ajustes.
 */
class Admin
{

	const CAPABILITY_WOO = 'manage_woocommerce';
	const CAPABILITY_FALLBACK = 'manage_options';

	public static function init()
	{
		add_action('admin_menu', array(__CLASS__, 'menu'));
		$ajax_actions = array(
			'wookb_save_content' => 'save_content',
			'wookb_save_settings' => 'save_settings',
			'wookb_save_chatbot_settings' => 'save_chatbot_settings',
			'wookb_save_queue_settings' => 'save_queue_settings',
			'wookb_save_business_answers' => 'save_business_answers',
			'wookb_save_business_summary' => 'save_business_summary',
			'wookb_save_woocommerce_settings' => 'save_woocommerce_settings',
			'wookb_save_llms_faq' => 'save_llms_faq',
			'wookb_save_crawler_actions' => 'save_crawler_actions',
			'wookb_save_crawler_visibility' => 'save_crawler_visibility',
			'wookb_generate_business_summary_draft' => 'generate_business_summary_draft',
			'wookb_generate_faqs_draft' => 'generate_faqs_draft',
			'wookb_generate_prompt_draft' => 'generate_prompt_draft',
			'wookb_normalize_prompt' => 'normalize_prompt',
			'wookb_polish_store_doc' => 'polish_store_doc',
		);
		foreach ($ajax_actions as $action => $callback) {
			add_action('wp_ajax_' . $action, array(__CLASS__, $callback));
		}
		add_action('admin_post_wookb_save_content', array(__CLASS__, 'save_content'));
		add_action('admin_post_wookb_save_settings', array(__CLASS__, 'save_settings'));
		add_action('admin_post_wookb_save_chatbot_settings', array(__CLASS__, 'save_chatbot_settings'));
		add_action('admin_post_wookb_start_seed', array(__CLASS__, 'start_seed'));
		add_action('admin_post_wookb_start_seed_force', array(__CLASS__, 'start_seed_force'));
		add_action('admin_post_wookb_save_queue_settings', array(__CLASS__, 'save_queue_settings'));
		add_action('admin_post_wookb_cancel_seed', array(__CLASS__, 'cancel_seed'));
		add_action('admin_post_wookb_row_action', array(__CLASS__, 'row_action'));
		add_action('admin_post_wookb_regenerate_single', array(__CLASS__, 'regenerate_single'));
		add_action('admin_post_wookb_sync_chatbot_prompt', array(__CLASS__, 'sync_chatbot_prompt'));
		add_action('admin_post_wookb_save_business_answers', array(__CLASS__, 'save_business_answers'));
		add_action('admin_post_wookb_generate_business_summary_draft', array(__CLASS__, 'generate_business_summary_draft'));
		add_action('admin_post_wookb_save_business_summary', array(__CLASS__, 'save_business_summary'));
		add_action('admin_post_wookb_generate_prompt_draft', array(__CLASS__, 'generate_prompt_draft'));
		add_action('admin_post_wookb_normalize_prompt', array(__CLASS__, 'normalize_prompt'));
		add_action('admin_post_wookb_save_prompt_draft', array(__CLASS__, 'save_prompt_draft'));
		add_action('admin_post_wookb_sync_store_docs', array(__CLASS__, 'sync_store_docs'));
		add_action('admin_post_wookb_polish_store_doc', array(__CLASS__, 'polish_store_doc'));
		add_action('admin_post_wookb_generate_faqs_draft', array(__CLASS__, 'generate_faqs_draft'));
		add_action('admin_post_wookb_save_llms_faq', array(__CLASS__, 'save_llms_faq'));
		add_action('admin_post_wookb_force_generate', array(__CLASS__, 'force_generate'));
		add_action('admin_post_wookb_delete_all', array(__CLASS__, 'delete_all'));
		add_action('admin_post_wookb_reset_queue', array(__CLASS__, 'reset_queue'));
		add_action('admin_post_wookb_set_manual', array(__CLASS__, 'set_manual'));
		add_action('admin_post_wookb_set_char_limit', array(__CLASS__, 'set_char_limit'));
		add_action('admin_post_wookb_resolve_stale', array(__CLASS__, 'resolve_stale'));
		add_action('admin_post_wookb_back_to_auto', array(__CLASS__, 'back_to_auto'));
		add_action('admin_post_wookb_save_woocommerce_settings', array(__CLASS__, 'save_woocommerce_settings'));
		add_action('admin_post_wookb_check_accessibility', array(__CLASS__, 'check_accessibility'));
		add_action('admin_post_wookb_delete_physical_llms_txt', array(__CLASS__, 'delete_physical_llms_txt'));
		add_action('admin_post_wookb_download_llms_backup', array(__CLASS__, 'download_llms_backup'));
		add_action('admin_post_wookb_apply_llms_physical', array(__CLASS__, 'apply_llms_physical'));
		add_action('admin_post_wookb_save_crawler_actions', array(__CLASS__, 'save_crawler_actions'));
		add_action('admin_post_wookb_save_crawler_visibility', array(__CLASS__, 'save_crawler_visibility'));
		add_action('admin_post_wookb_download_robots_backup', array(__CLASS__, 'download_robots_backup'));
		add_action('admin_post_wookb_apply_robots_block', array(__CLASS__, 'apply_robots_block'));
		add_action('admin_post_wookb_download_htaccess_backup', array(__CLASS__, 'download_htaccess_backup'));
		add_action('admin_post_wookb_apply_htaccess_block', array(__CLASS__, 'apply_htaccess_block'));
		add_action('admin_post_wookb_download_htaccess_generated', array(__CLASS__, 'download_htaccess_generated'));
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
			'ai-knowledge',
			array(__CLASS__, 'render'),
			'dashicons-admin-generic',
			58
		);

		// Bulk actions del Registro: procesadas aqui (load-{hook}, antes de
		// imprimir ningun HTML) en vez de via admin_post.php. Ver
		// maybe_handle_bulk_action() para el porque.
		add_action('load-' . $hook, array(__CLASS__, 'maybe_handle_bulk_action'));

		// Mismas tabs que render(), tambien como entradas de submenu: acceso
		// directo desde la barra lateral de WordPress, ademas de las tabs
		// internas. El slug con query string ('ai-knowledge&tab=xxx') esta
		// soportado por WordPress core (wp-admin/menu-header.php construye el
		// href y detecta la entrada activa tratando explicitamente el '?'
		// dentro del slug), no es un hack fragil.
		foreach (self::tabs() as $key => $label) {
			$slug = ('registro' === $key) ? 'ai-knowledge' : 'ai-knowledge&tab=' . $key;
			add_submenu_page(
				'ai-knowledge',
				$label,
				$label,
				self::capability(),
				$slug,
				array(__CLASS__, 'render')
			);
		}
	}

	/**
	 * Listado de tabs del admin, en el orden en que se muestran. Compartido
	 * entre render() (tabs internas) y menu() (entradas de submenu): una
	 * unica fuente de verdad para no desincronizar ambos listados.
	 */
	protected static function tabs()
	{
		$tabs = array(
			'registro'  => __('Registro', 'ai-knowledge'),
			'contenido' => __('Contenido', 'ai-knowledge'),
			'negocio'   => __('Negocio', 'ai-knowledge'),
			'faqs'      => __('FAQs', 'ai-knowledge'),
		);
		// Fase 2: pestaña "WooCommerce" solo si WooCommerce esta activo -- sin
		// el, no hay nada real que detectar (moneda, envios, impuestos, pagos)
		// y la pestaña quedaria vacia/confusa.
		if (class_exists('WooCommerce')) {
			$tabs['woocommerce'] = __('WooCommerce', 'ai-knowledge');
		}
		// Chatbot va despues de WooCommerce y solo aparece si Support Genix
		// esta activo: sin el, el prompt no tiene un consumidor real.
		if (Chatbot_Prompt::is_genix_ready()) {
			$tabs['prompt'] = __('Chatbot', 'ai-knowledge');
		}
		$tabs['visibilidad-ia'] = __('Visibilidad IA', 'ai-knowledge');
		$tabs['carga-inicial']  = __('Generación masiva', 'ai-knowledge');
		$tabs['ajustes']        = __('Ajustes', 'ai-knowledge');

		return $tabs;
	}

	public static function assets($hook)
	{
		if (false === strpos($hook, 'ai-knowledge')) {
			return;
		}
		// Orden importa: Tabler primero (variables/base), luego nuestro tema
		// (usa esas variables), luego admin.css (ajustes puntuales que ya
		// existian antes de Tabler, se mantienen por si acaso).
		wp_enqueue_style('wookb-tabler', AIKB_URL . 'assets/tabler.min.css', array(), AIKB_VERSION);
		wp_enqueue_style('wookb-theme', AIKB_URL . 'assets/wookb-theme.css', array('wookb-tabler'), AIKB_VERSION);
		wp_enqueue_style('wookb-admin', AIKB_URL . 'assets/admin.css', array('wookb-theme'), AIKB_VERSION);
		wp_enqueue_script('wookb-admin', AIKB_URL . 'assets/admin.js', array('jquery'), AIKB_VERSION, true);
	}

	public static function render()
	{
		if (! current_user_can(self::capability())) {
			wp_die(esc_html__('No tienes permisos suficientes.', 'ai-knowledge'));
		}

		$tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'registro'; // phpcs:ignore
		$tabs = self::tabs();

		echo '<div class="wookb-wrap" data-wookb-doc-tab="' . esc_attr($tab) . '">';
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

		self::render_registry_summary();

		if (isset($_GET['wookb_notice'])) { // phpcs:ignore
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Guardado.', 'ai-knowledge') . '</p></div>';
		}

		echo '<h2 class="nav-tab-wrapper">';
		foreach ($tabs as $key => $label) {
			$class = ($key === $tab) ? 'nav-tab nav-tab-active' : 'nav-tab';
			$url   = admin_url('admin.php?page=ai-knowledge&tab=' . $key);
			echo '<a class="' . esc_attr($class) . '" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
		}
		echo '</h2>';

		$view_file = AIKB_DIR . 'admin/views/tab-' . $tab . '.php';
		if (file_exists($view_file)) {
			// Envoltorio visual "card" de Tabler: solo un <div> alrededor de
			// todo el contenido de la pestaña, no toca nada dentro de la vista
			// (formularios, name=, action=, nonces siguen exactamente igual).
			echo '<div class="wookb-card">';
			require $view_file;
			echo '</div>';
		}
		self::render_documentation_drawer($tab);

		echo '</div>';
	}

	/** Renderiza el acceso a la guía de la pestaña actual. */
	public static function documentation_link($tab)
	{
		$docs = self::documentation_map();
		if (empty($docs[$tab])) {
			return;
		}
		echo '<button type="button" class="button wookb-btn-info" data-wookb-doc-open="' . esc_attr($tab) . '">' . esc_html__('Leer documentación', 'ai-knowledge') . '</button>';
	}

	protected static function documentation_map()
	{
		return array(
			'registro' => 'tab-registro.md',
			'contenido' => 'tab-contenido.md',
			'negocio' => 'tab-negocio.md',
			'faqs' => 'tab-faqs.md',
			'woocommerce' => 'tab-woocommerce.md',
			'prompt' => 'tab-chatbot.md',
			'visibilidad-ia' => 'tab-visibilidad-ia.md',
			'carga-inicial' => 'tab-generacion-masiva.md',
			'ajustes' => 'tab-ajustes.md',
		);
	}

	protected static function render_documentation_drawer($tab)
	{
		$docs = self::documentation_map();
		if (empty($docs[$tab])) {
			return;
		}
		$file = AIKB_DIR . 'docs/' . $docs[$tab];
		if (!is_readable($file)) {
			return;
		}
		$title = __('Documentación', 'ai-knowledge');
		$content = self::markdown_to_html((string) file_get_contents($file));
		echo '<div class="wookb-doc-backdrop" data-wookb-doc-close></div>';
		echo '<aside class="wookb-doc-drawer" data-wookb-doc-drawer role="dialog" aria-modal="true" aria-label="' . esc_attr($title) . '" aria-hidden="true">';
		echo '<div class="wookb-doc-drawer-header"><button type="button" class="button-link wookb-doc-back" data-wookb-doc-back hidden>←</button><h2>' . esc_html($title) . '</h2><button type="button" class="button-link" data-wookb-doc-close aria-label="' . esc_attr__('Cerrar documentación', 'ai-knowledge') . '">×</button></div>';
		echo '<div class="wookb-doc-drawer-content">' . $content . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitizado por markdown_to_html
		foreach ($docs as $doc_file) {
			if ($doc_file === $docs[$tab] || ! is_readable(AIKB_DIR . 'docs/' . $doc_file)) {
				continue;
			}
			echo '<template data-wookb-doc-template="' . esc_attr($doc_file) . '">' . self::markdown_to_html((string) file_get_contents(AIKB_DIR . 'docs/' . $doc_file)) . '</template>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</aside>';
	}

	protected static function markdown_to_html($markdown)
	{
		$lines = preg_split('/\r\n|\r|\n/', $markdown);
		$html = '';
		$paragraph = array();
		$list_tag = '';
		$flush = static function () use (&$html, &$paragraph) {
			if ($paragraph) {
				$html .= '<p>' . implode(' ', $paragraph) . '</p>';
				$paragraph = array();
			}
		};
		foreach ($lines as $line) {
			$line = trim($line);
			if ('' === $line) {
				$flush();
				if ($list_tag) {
					$html .= '</' . $list_tag . '>';
					$list_tag = '';
				}
				continue;
			}
			if (preg_match('/^#{1,3}\s+(.+)$/', $line, $match)) {
				$flush();
				if ($list_tag) {
					$html .= '</' . $list_tag . '>';
					$list_tag = '';
				}
				$level = strlen(strstr($line, ' ', true));
				$heading_id = sanitize_title(wp_strip_all_tags($match[1]));
				$html .= '<h' . $level . ' id="' . esc_attr($heading_id) . '">' . self::markdown_inline($match[1]) . '</h' . $level . '>';
				continue;
			}
			if (preg_match('/^[-*]\s+(.+)$/', $line, $match)) {
				$flush();
				if ('ul' !== $list_tag) {
					if ($list_tag) {
						$html .= '</' . $list_tag . '>';
					}
					$html .= '<ul>';
					$list_tag = 'ul';
				}
				$html .= '<li>' . self::markdown_inline($match[1]) . '</li>';
				continue;
			}
			if (preg_match('/^\d+\.\s+(.+)$/', $line, $match)) {
				$flush();
				if ('ol' !== $list_tag) {
					if ($list_tag) {
						$html .= '</' . $list_tag . '>';
					}
					$html .= '<ol>';
					$list_tag = 'ol';
				}
				$html .= '<li>' . self::markdown_inline($match[1]) . '</li>';
				continue;
			}
			if ($list_tag) {
				$html .= '</' . $list_tag . '>';
				$list_tag = '';
			}
			$paragraph[] = self::markdown_inline($line);
		}
		$flush();
		if ($list_tag) {
			$html .= '</' . $list_tag . '>';
		}
		return wp_kses_post($html);
	}

	protected static function markdown_inline($text)
	{
		$text = esc_html($text);
		$text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
		$text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
		$text = preg_replace_callback('/\[([^]]+)\]\((https?:\/\/[^)]+|[^)]+\.md(?:#[^)]+)?)\)/', static function ($match) {
			$url = $match[2];
			$parts = explode('#', $url, 2);
			if ('.md' === substr($parts[0], -3)) {
				if (false !== stripos($match[1], 'volver al índice')) {
					return '';
				}
				$anchor = isset($parts[1]) ? sanitize_title($parts[1]) : '';
				return '<a href="#" data-wookb-doc-link="' . esc_attr(basename($parts[0])) . '"' . ( $anchor ? ' data-wookb-doc-anchor="' . esc_attr($anchor) . '"' : '' ) . '>' . esc_html($match[1]) . '</a>';
			}
			return '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . $match[1] . '</a>';
		}, $text);
		return $text;
	}

	/**
	 * Resumen de una linea justo debajo del titulo de la pagina (no dentro de
	 * la vista de la pestaña): total, por idioma, por estado. Formato pedido
	 * explicitamente por el usuario.
	 */
	protected static function render_registry_summary()
	{
		require_once AIKB_DIR . 'admin/class-registry-table.php';
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
		if (wp_doing_ajax()) {
			wp_send_json_success(
				array(
					'message' => __('Guardado.', 'ai-knowledge'),
					'tab' => $tab,
				)
			);
		}
		wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=' . $tab . '&wookb_notice=1'));
		exit;
	}

	/**
	 * Fase 10, pieza 2/4: sustituye a save_scope()/save_exclusions(). Un unico
	 * formulario ("Contenido") guarda modo de CPT, taxonomias/terminos e IDs
	 * sueltos como acciones include/exclude, mas los campos custom.
	 */
	public static function save_content()
	{
		self::verify('wookb_save_content');

		$post_types_mode = isset($_POST['post_types_mode']) ? sanitize_key(wp_unslash($_POST['post_types_mode'])) : 'explicit'; // phpcs:ignore
		if (! in_array($post_types_mode, array('explicit', 'all_public'), true)) {
			$post_types_mode = 'explicit';
		}

		$post_types = isset($_POST['post_types']) ? array_map('sanitize_key', (array) wp_unslash($_POST['post_types'])) : array(); // phpcs:ignore
		$post_types_excluded_when_all = isset($_POST['post_types_excluded_when_all']) ? array_map('sanitize_key', (array) wp_unslash($_POST['post_types_excluded_when_all'])) : array(); // phpcs:ignore

		$term_actions = array();
		if (! empty($_POST['term_actions']) && is_array($_POST['term_actions'])) { // phpcs:ignore
			foreach (wp_unslash($_POST['term_actions']) as $taxonomy => $terms) { // phpcs:ignore
				if (! is_array($terms)) {
					continue;
				}
				foreach ($terms as $term_id => $value) {
					$value = sanitize_key($value);
					if (! in_array($value, array('include', 'exclude'), true)) {
						// Ausencia = "sin decidir": no se guarda esa clave.
						continue;
					}
					$term_actions[sanitize_key($taxonomy)][absint($term_id)] = $value;
				}
			}
		}

		$include_ids = isset($_POST['include_ids']) ? array_filter(array_map('intval', explode(',', sanitize_text_field(wp_unslash($_POST['include_ids']))))) : array(); // phpcs:ignore
		$exclude_ids = isset($_POST['exclude_ids']) ? array_filter(array_map('intval', explode(',', sanitize_text_field(wp_unslash($_POST['exclude_ids']))))) : array(); // phpcs:ignore

		$id_actions = array();
		foreach ($include_ids as $id) {
			$id_actions[$id] = 'include';
		}
		foreach ($exclude_ids as $id) {
			// exclude pisa si el mismo ID aparece en ambos campos.
			$id_actions[$id] = 'exclude';
		}

		$custom_fields = array();
		if (! empty($_POST['custom_fields']) && is_array($_POST['custom_fields'])) { // phpcs:ignore
			foreach (wp_unslash($_POST['custom_fields']) as $post_type => $fields) { // phpcs:ignore
				$custom_fields[sanitize_key($post_type)] = array_map('sanitize_text_field', (array) $fields);
			}
		}

		// Sincronía de borrado: para cada ID recién excluido, y para cada
		// termino recien excluido, ejecutar borrado de sus documentos.
		$previous = Scope::settings();

		$previous_id_actions = (array) $previous['id_actions'];
		foreach ($id_actions as $id => $action) {
			$was_excluded = isset($previous_id_actions[$id]) && 'exclude' === $previous_id_actions[$id];
			if ('exclude' === $action && ! $was_excluded) {
				Sync::delete_documents_for($id);
			}
		}

		$previous_term_actions = (array) $previous['term_actions'];
		foreach ($term_actions as $taxonomy => $terms) {
			foreach ($terms as $term_id => $action) {
				if ('exclude' !== $action) {
					continue;
				}
				$was_excluded = isset($previous_term_actions[$taxonomy][$term_id]) && 'exclude' === $previous_term_actions[$taxonomy][$term_id];
				if ($was_excluded) {
					continue;
				}
				$affected = get_posts(
					array(
						'post_type'      => 'any',
						'post_status'    => 'publish',
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'tax_query'      => array( // phpcs:ignore
							array(
								'taxonomy' => $taxonomy,
								'field'    => 'term_id',
								'terms'    => array($term_id),
							),
						),
					)
				);
				foreach ($affected as $affected_id) {
					Sync::delete_documents_for($affected_id);
				}
			}
		}

		Scope::update_settings(
			array(
				'post_types'                   => $post_types,
				'post_types_mode'              => $post_types_mode,
				'post_types_excluded_when_all' => $post_types_excluded_when_all,
				'term_actions'                 => $term_actions,
				'id_actions'                   => $id_actions,
				'custom_fields'                => $custom_fields,
			)
		);

		self::redirect('contenido');
	}

	public static function save_settings()
	{
		self::verify('wookb_save_settings');

		$ai_source = isset($_POST['ai_key_source']) ? sanitize_key(wp_unslash($_POST['ai_key_source'])) : 'genix'; // phpcs:ignore
		if (! in_array($ai_source, array('genix', 'wp_connectors'), true) || ('wp_connectors' === $ai_source && ! AI_Client::wordpress_available())) {
			$ai_source = 'genix';
		}

		$wp_ai_model = isset($_POST['wp_ai_model']) ? sanitize_text_field(wp_unslash($_POST['wp_ai_model'])) : AI_Client::MODEL_AUTO; // phpcs:ignore
		if (AI_Client::MODEL_AUTO !== $wp_ai_model && ! isset(AI_Client::available_models()[$wp_ai_model])) {
			$wp_ai_model = AI_Client::MODEL_AUTO;
		}

		Scope::update_settings(
			array(
				// daily_limit/no_limit/batch_size/debounce_seconds: movidos a
				// Generación masiva (UX2), se guardan con save_queue_settings(), no
				// aqui -- este formulario ya no los envia.
				'output_tokens'    => max(200, (int) ($_POST['output_tokens'] ?? 2500)), // phpcs:ignore
				'body_char_limit'  => max(100, min(10000, (int) ($_POST['body_char_limit'] ?? 1000))), // phpcs:ignore
				'ai_key_source'    => $ai_source,
				'wp_ai_model'      => $wp_ai_model,
				// Fase 1: post_types donde se muestra el meta box del editor.
				// Se guarda siempre que llegue el campo oculto 'editor_button_post_types_submitted'
				// (ver tab-ajustes.php) para poder distinguir "ningun CPT marcado"
				// (array vacio real) de "ajuste nunca guardado" (null, ver Scope::settings()).
				'editor_button_post_types' => isset($_POST['editor_button_post_types_submitted'])
					? (isset($_POST['editor_button_post_types']) ? array_map('sanitize_key', (array) wp_unslash($_POST['editor_button_post_types'])) : array()) // phpcs:ignore
					: Scope::settings()['editor_button_post_types'],
				'indexnow_enabled' => ! empty($_POST['indexnow_enabled']), // phpcs:ignore
			)
		);

		self::redirect('ajustes');
	}

	/** Guarda los ajustes que afectan solo al chatbot de Support Genix. */
	public static function save_chatbot_settings()
	{
		self::verify('wookb_save_chatbot_settings');

		Scope::update_settings(
			array(
				'chatbot_docs_list_limit' => max(0, (int) ($_POST['chatbot_docs_list_limit'] ?? Chatbot_Relevance_Guard::DOCS_LIST_LIMIT_DEFAULT)), // phpcs:ignore
			)
		);

		self::redirect('prompt');
	}

	public static function start_seed()
	{
		self::verify('wookb_start_seed');
		Queue::start_seed();
		self::redirect('carga-inicial');
	}

	/**
	 * UX2: "Reiniciar todo" -- misma cola que "Generar pendientes" pero con
	 * force=true, para regenerar tambien lo que ya estaba sincronizado. Accion
	 * propia (no un parametro del formulario de start_seed) para que el
	 * nonce/confirmacion JS de la vista sean inequivocos sobre cual de las
	 * dos se esta pidiendo.
	 */
	public static function start_seed_force()
	{
		self::verify('wookb_start_seed_force');
		Queue::start_seed(true);
		self::redirect('carga-inicial');
	}

	/**
	 * UX2: guarda SOLO los 3 ajustes de cola (limite diario, lote, debounce),
	 * movidos de Ajustes a Generación masiva. Accion separada de save_settings()
	 * a proposito: save_settings() reescribe TODAS sus claves desde $_POST
	 * con valores por defecto si faltan -- si este formulario mas pequeño
	 * llamase a esa misma accion, cada guardado desde aqui resetearia
	 * output_tokens/clave IA/prompt adicional a su valor por defecto. Con
	 * Scope::update_settings() (merge) esto no puede pasar.
	 */
	public static function save_queue_settings()
	{
		self::verify('wookb_save_queue_settings');

		Scope::update_settings(
			array(
				'daily_limit'      => max(1, (int) ($_POST['daily_limit'] ?? 100)), // phpcs:ignore
				'no_limit'         => ! empty($_POST['no_limit']), // phpcs:ignore
				'batch_size'       => max(1, (int) ($_POST['batch_size'] ?? 20)), // phpcs:ignore
				'debounce_seconds' => max(0, (int) ($_POST['debounce_seconds'] ?? 300)), // phpcs:ignore
			)
		);

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
				self::exclude_from_scope(array($row));
			}
		}

		self::redirect('registro');
	}

	/**
	 * Borrar una fila del Registro (individual, en lote o "Borrar todos")
	 * tambien excluye su origen del alcance (id_actions en Contenido): si
	 * no, la cola/cron la volveria a generar sola en el siguiente ciclo.
	 * Solo aplica a posts reales, no a documentos compuestos (Store_Info_Doc)
	 * que usan un source_id centinela sin post_type real detras. Recibe
	 * varias filas para hacer un unico update_option, no uno por fila.
	 */
	protected static function exclude_from_scope(array $rows)
	{
		$id_actions = (array) Scope::settings()['id_actions'];
		$changed    = false;

		foreach ($rows as $row) {
			if (! post_type_exists($row->source_type)) {
				continue;
			}
			$id_actions[(int) $row->source_id] = 'exclude';
			$changed = true;
		}

		if ($changed) {
			Scope::update_settings(array('id_actions' => $id_actions));
		}
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
				wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=registro&wookb_regen_error=' . rawurlencode($result->get_error_message())));
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
			wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=registro&wookb_regen_error=' . rawurlencode(__('No se encontró ningún producto o página con ese ID/URL.', 'ai-knowledge'))));
			exit;
		}

		if (! Scope::is_included($post_id)) {
			wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=registro&wookb_regen_error=' . rawurlencode(__('Ese contenido no está dentro del alcance configurado del plugin (pestaña Alcance/Exclusiones).', 'ai-knowledge'))));
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
			wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=registro&wookb_regen_error=' . rawurlencode(implode(' | ', $errors))));
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

		$ids  = Registry::query_all_ids(array('status' => $status, 'lang' => $lang, 'search' => $search));
		$rows = array();
		foreach ($ids as $row_id) {
			$row = Registry::find_by_id($row_id);
			if ($row) {
				Sync::delete_documents_for($row->source_id);
				$rows[] = $row;
			}
		}
		self::exclude_from_scope($rows);

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
		$url       = admin_url('admin.php?page=ai-knowledge&tab=registro');
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

		require_once AIKB_DIR . 'admin/class-registry-table.php';
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
		$deleted_rows   = array();

		foreach ($row_ids as $row_id) {
			$row = Registry::find_by_id($row_id);
			if (! $row) {
				continue;
			}

			if ('delete' === $action) {
				// Reutiliza el mismo borrado sincronizado que la accion individual:
				// fila del Registro + .md + post sgkb-docs de Genix.
				Sync::delete_documents_for($row->source_id);
				$deleted_rows[] = $row;
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

		self::exclude_from_scope($deleted_rows);

		$redirect = admin_url('admin.php?page=ai-knowledge&tab=registro&wookb_notice=1');
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
		wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=ajustes&wookb_notice=1&wookb_sync_status=' . rawurlencode($result['status'])));
		exit;
	}

	/**
	 * Guarda solo las respuestas del grupo "negocio" (pestaña Negocio):
	 * datos validos con o sin WooCommerce, no especificos del chatbot.
	 * save_answers() ya solo sobreescribe las keys presentes, asi que no
	 * borra lo guardado en la pestaña Chatbot. Regenera llm/info.md porque
	 * estos datos tambien alimentan llms.txt, no solo el prompt del bot.
	 */
	public static function save_business_answers()
	{
		self::verify('wookb_save_business_answers');

		$raw_answers = isset($_POST['answers']) && is_array($_POST['answers']) ? wp_unslash($_POST['answers']) : array(); // phpcs:ignore
		Chatbot_Prompt_Builder::save_answers($raw_answers);
		Chatbot_Prompt_Builder::write_info_doc();

		self::redirect('negocio');
	}

	/**
	 * Genera/pule con IA el resumen de negocio (campo 'negocio') a partir
	 * del resto de datos ya guardados, y lo deja en transient para revisar
	 * antes de guardar -- mismo patron que generate_prompt_draft().
	 */
	public static function generate_business_summary_draft()
	{
		self::verify('wookb_generate_business_summary_draft');

		$answers    = Chatbot_Prompt_Builder::get_saved_answers();
		$extra_info = isset($_POST['extra_info']) ? sanitize_textarea_field(wp_unslash($_POST['extra_info'])) : ''; // phpcs:ignore
		$draft      = Chatbot_Prompt_Builder::generate_business_summary($answers, $extra_info);

		if (is_wp_error($draft)) {
			set_transient('wookb_business_summary_error', $draft->get_error_message(), MINUTE_IN_SECONDS);
			if (wp_doing_ajax()) {
				wp_send_json_error(array('message' => $draft->get_error_message()), 422);
			}
		} else {
			set_transient('wookb_business_summary_draft', $draft, HOUR_IN_SECONDS);
			if (wp_doing_ajax()) {
				wp_send_json_success(array('message' => __('Borrador generado.', 'ai-knowledge'), 'draft' => $draft));
			}
		}

		self::redirect('negocio');
	}

	/**
	 * Guarda el resumen editado/revisado sin sustituir el enfoque del negocio.
	 */
	public static function save_business_summary()
	{
		self::verify('wookb_save_business_summary');

		$summary = isset($_POST['summary_draft']) ? sanitize_textarea_field(wp_unslash($_POST['summary_draft'])) : ''; // phpcs:ignore
		Chatbot_Prompt_Builder::save_business_summary($summary);
		Chatbot_Prompt_Builder::write_info_doc();
		delete_transient('wookb_business_summary_draft');

		self::redirect('negocio');
	}

	/**
	 * Genera un borrador con IA a partir del cuestionario (+ paginas de
	 * referencia opcionales) y lo deja en un transient para mostrarlo en el
	 * textarea editable de la pestana Prompt -- no toca el .md todavia.
	 */
	public static function generate_prompt_draft()
	{
		self::verify('wookb_generate_prompt_draft');
		// Defensa: la pestaña Chatbot solo se anuncia en el menu si Genix
		// esta activo, pero esta accion es alcanzable via admin-post.php
		// directamente -- sin Genix, chatbot-system-prompt.md no tiene a
		// donde sincronizarse (ver Chatbot_Prompt::sync()).
		if (! Chatbot_Prompt::is_genix_ready()) {
			if (wp_doing_ajax()) {
				wp_send_json_error(array('message' => __('Esta acción requiere Support Genix activo.', 'ai-knowledge')), 422);
			}
			wp_die(esc_html__('Esta acción requiere Support Genix activo.', 'ai-knowledge'));
		}

		$raw_answers = isset($_POST['answers']) && is_array($_POST['answers']) ? wp_unslash($_POST['answers']) : array(); // phpcs:ignore
		$answers     = Chatbot_Prompt_Builder::save_answers($raw_answers);

		$reference_raw   = isset($_POST['reference_pages']) ? sanitize_text_field(wp_unslash($_POST['reference_pages'])) : ''; // phpcs:ignore
		$reference_pages = Chatbot_Prompt_Builder::fetch_reference_content($reference_raw);
		$extra_info      = isset($_POST['extra_info']) ? sanitize_textarea_field(wp_unslash($_POST['extra_info'])) : ''; // phpcs:ignore

		$draft = Chatbot_Prompt_Builder::generate_draft($answers, $reference_pages, $extra_info);

		if (is_wp_error($draft)) {
			set_transient('wookb_prompt_draft_error', $draft->get_error_message(), MINUTE_IN_SECONDS);
			if (wp_doing_ajax()) {
				wp_send_json_error(array('message' => $draft->get_error_message()), 422);
			}
		} else {
			set_transient('wookb_prompt_draft', $draft, HOUR_IN_SECONDS);
			if (wp_doing_ajax()) {
				wp_send_json_success(array('message' => __('Borrador generado.', 'ai-knowledge'), 'draft' => $draft));
			}
		}

		wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=prompt'));
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
			if (wp_doing_ajax()) {
				wp_send_json_error(array('message' => $result->get_error_message()), 422);
			}
		} else {
			set_transient('wookb_prompt_draft', $result, HOUR_IN_SECONDS);
			if (wp_doing_ajax()) {
				wp_send_json_success(array('message' => __('Texto pulido.', 'ai-knowledge'), 'draft' => $result));
			}
		}

		wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=prompt'));
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
		if (! Chatbot_Prompt::is_genix_ready()) {
			wp_die(esc_html__('Esta acción requiere Support Genix activo.', 'ai-knowledge'));
		}

		$draft = isset($_POST['draft']) ? sanitize_textarea_field(wp_unslash($_POST['draft'])) : ''; // phpcs:ignore
		if ('' === trim($draft)) {
			wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=prompt'));
			exit;
		}

		wp_mkdir_p(dirname(Chatbot_Prompt::file_path()));
		file_put_contents(Chatbot_Prompt::file_path(), $draft . "\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_put_contents
		Chatbot_Prompt::sync(true);
		Chatbot_Prompt_Builder::write_info_doc();
		delete_transient('wookb_prompt_draft');

		wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=prompt&wookb_notice=1'));
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

		wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=woocommerce&wookb_notice=1'));
		exit;
	}

	/**
	 * Pule con IA (sin inventar datos, ver Chatbot_Prompt_Builder::
	 * polish_factual_text()) el texto ya generado de un documento de tienda
	 * (informacion-tienda o catalogo-tienda) y lo fija en modo manual
	 * (publish_manual_text(), reutilizado tal cual de la Fase 1) para que
	 * "Generar/actualizar ahora" no lo pise despues (ver guard de
	 * override_mode en Store_Info_Doc::persist()).
	 */
	public static function polish_store_doc()
	{
		self::verify('wookb_polish_store_doc');

		$row_id     = isset($_POST['row_id']) ? (int) $_POST['row_id'] : 0; // phpcs:ignore
		$extra_info = isset($_POST['extra_info']) ? sanitize_textarea_field(wp_unslash($_POST['extra_info'])) : ''; // phpcs:ignore

		$row = Registry::find_by_id($row_id);
		if (! $row) {
			self::redirect('woocommerce');
		}

		$current_text = ('manual' === $row->override_mode && null !== $row->override_text && '' !== $row->override_text)
			? $row->override_text
			: Markdown_Store::body_only(Markdown_Store::read($row->md_path));

		$polished = Chatbot_Prompt_Builder::polish_factual_text($current_text, $extra_info, Store_Info_Doc::CHAR_LIMIT);

		if (is_wp_error($polished)) {
			set_transient('wookb_store_docs_error', $polished->get_error_message(), MINUTE_IN_SECONDS);
			if (wp_doing_ajax()) {
				wp_send_json_error(array('message' => $polished->get_error_message()), 422);
			}
			self::redirect('woocommerce');
		}

		self::publish_manual_text($row, $polished);
		if (wp_doing_ajax()) {
			wp_send_json_success(array('message' => __('Texto pulido.', 'ai-knowledge'), 'polished' => $polished, 'row_id' => $row_id));
		}

		self::redirect('woocommerce');
	}

	/**
	 * Convierte un array de IDs marcados (checkbox) en [ id => 'include' ].
	 * $numeric=true para IDs enteros (instance_id, tax_rate_id, term_id);
	 * false para IDs de texto (gateway->id de WooCommerce, ej. 'bacs',
	 * 'paypal' -- absint() los destruiria a 0).
	 */
	protected static function checked_ids_to_selection($post_key, $numeric = true)
	{
		if (! isset($_POST[$post_key]) || ! is_array($_POST[$post_key])) { // phpcs:ignore
			return array();
		}
		$raw       = wp_unslash($_POST[$post_key]); // phpcs:ignore
		$ids       = $numeric ? array_map('absint', $raw) : array_map('sanitize_key', $raw);
		$selection = array();
		foreach ($ids as $id) {
			$selection[$id] = 'include';
		}
		return $selection;
	}

	public static function save_woocommerce_settings()
	{
		self::verify('wookb_save_woocommerce_settings');

		Scope::update_settings(
			array(
				'delivery_time_note'    => isset($_POST['delivery_time_note']) ? sanitize_textarea_field(wp_unslash($_POST['delivery_time_note'])) : '', // phpcs:ignore
				'legal_notes_extra'     => isset($_POST['legal_notes_extra']) ? sanitize_textarea_field(wp_unslash($_POST['legal_notes_extra'])) : '', // phpcs:ignore
				'wc_shipping_methods'   => self::checked_ids_to_selection('wc_shipping_methods'),
				'wc_tax_rates'          => self::checked_ids_to_selection('wc_tax_rates'),
				'wc_catalog_categories' => self::checked_ids_to_selection('wc_catalog_categories'),
				'wc_payment_methods'    => self::checked_ids_to_selection('wc_payment_methods', false),
				'wc_min_order_note'     => isset($_POST['wc_min_order_note']) ? sanitize_textarea_field(wp_unslash($_POST['wc_min_order_note'])) : '', // phpcs:ignore
				'wc_pickup_available'   => ! empty($_POST['wc_pickup_available']), // phpcs:ignore
				'wc_store_name'         => isset($_POST['wc_store_name']) ? sanitize_text_field(wp_unslash($_POST['wc_store_name'])) : '', // phpcs:ignore
				'wc_currency'           => isset($_POST['wc_currency']) ? sanitize_text_field(wp_unslash($_POST['wc_currency'])) : '', // phpcs:ignore
				'wc_base_country'       => isset($_POST['wc_base_country']) ? sanitize_text_field(wp_unslash($_POST['wc_base_country'])) : '', // phpcs:ignore
				'wc_terms_text'         => isset($_POST['wc_terms_text']) ? sanitize_textarea_field(wp_unslash($_POST['wc_terms_text'])) : '', // phpcs:ignore
				'wc_returns_text'       => isset($_POST['wc_returns_text']) ? sanitize_textarea_field(wp_unslash($_POST['wc_returns_text'])) : '', // phpcs:ignore
				'wc_contact_hours'      => isset($_POST['wc_contact_hours']) ? sanitize_textarea_field(wp_unslash($_POST['wc_contact_hours'])) : '', // phpcs:ignore
			)
		);

		self::redirect('woocommerce');
	}

	/**
	 * Guarda el contenido de la sección "## Preguntas frecuentes" pública de
	 * llms.txt. Delega en Llms_Faq::save() (ver class-llms-faq.php para el
	 * porqué de un archivo propio, distinto de chatbot-system-prompt.md).
	 */
	/**
	 * Idioma de trabajo de la pestaña FAQs: el que venga en la peticion
	 * (selector de idioma, solo visible si hay mas de uno activo), validado
	 * contra los idiomas activos de verdad -- si no coincide con ninguno,
	 * cae al primero. Mismo criterio en los 2 handlers de FAQs, para que
	 * generar y guardar operen siempre sobre el mismo idioma.
	 */
	protected static function faqs_lang()
	{
		$requested = isset($_POST['lang']) ? sanitize_key(wp_unslash($_POST['lang'])) : ''; // phpcs:ignore
		$active    = Wpml::active_languages();
		if ($requested && in_array($requested, $active, true)) {
			return $requested;
		}
		return $active ? $active[0] : 'es';
	}

	public static function save_llms_faq()
	{
		self::verify('wookb_save_llms_faq');

		$lang    = self::faqs_lang();
		$content = isset($_POST['llms_faq']) ? sanitize_textarea_field(wp_unslash($_POST['llms_faq'])) : ''; // phpcs:ignore
		Llms_Faq::save($content, $lang);
		Llms_Faq::persist_doc($lang);
		delete_transient('wookb_faqs_draft_' . $lang);
		if (wp_doing_ajax()) {
			wp_send_json_success(
				array(
					'message' => __('FAQ guardada.', 'ai-knowledge'),
					'tab' => 'faqs',
					'lang' => $lang,
				)
			);
		}

		wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=faqs&lang=' . $lang . '&wookb_notice=1'));
		exit;
	}

	/**
	 * Genera/amplia con IA un borrador de FAQs a partir de los datos de
	 * Negocio y del FAQ ya guardado (si hay), y lo deja en transient para
	 * revisar antes de guardar -- mismo patron que generate_prompt_draft().
	 */
	public static function generate_faqs_draft()
	{
		self::verify('wookb_generate_faqs_draft');

		$lang       = self::faqs_lang();
		$answers    = Chatbot_Prompt_Builder::get_saved_answers();
		$current    = class_exists('\AIKB\Llms_Faq') ? Llms_Faq::read($lang) : '';
		$extra_info = isset($_POST['extra_info']) ? sanitize_textarea_field(wp_unslash($_POST['extra_info'])) : ''; // phpcs:ignore
		$draft      = Chatbot_Prompt_Builder::generate_faqs($answers, $current, $extra_info);

		if (is_wp_error($draft)) {
			set_transient('wookb_faqs_error_' . $lang, $draft->get_error_message(), MINUTE_IN_SECONDS);
			if (wp_doing_ajax()) {
				wp_send_json_error(array('message' => $draft->get_error_message()), 422);
			}
		} else {
			set_transient('wookb_faqs_draft_' . $lang, $draft, HOUR_IN_SECONDS);
			if (wp_doing_ajax()) {
				wp_send_json_success(array('message' => __('FAQ generada.', 'ai-knowledge'), 'draft' => $draft, 'lang' => $lang));
			}
		}

		wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=faqs&lang=' . $lang . '&wookb_notice=1'));
		exit;
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
				wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=registro&wookb_regen_error=' . rawurlencode($result->get_error_message())));
				exit;
			}
		}

		self::redirect('registro');
	}

	/**
	 * Fase 6: comprueba accesibilidad de UNA URL del contenido ya sincronizado
	 * (Registro + dentro del Scope) frente a robots.txt y noindex/X-Robots-Tag,
	 * avisando si se contradicen. El objetivo NUNCA llega como URL libre desde
	 * el formulario -- solo un "source_id:lang" que se valida contra filas
	 * reales del Registro (status=synced) y contra Scope::resolve_ids(), para
	 * que wp_remote_get() no pueda usarse para pedir una URL arbitraria (SSRF).
	 */
	public static function check_accessibility()
	{
		self::verify('wookb_check_accessibility');

		$target = isset($_POST['check_target']) ? sanitize_text_field(wp_unslash($_POST['check_target'])) : ''; // phpcs:ignore
		list($source_id, $lang) = array_pad(explode(':', $target, 2), 2, '');
		$source_id = (int) $source_id;
		$lang      = sanitize_key($lang);

		$row = $source_id && $lang ? Registry::find($source_id, $lang) : null;

		if (! $row || 'synced' !== $row->status || ! Scope::is_included($source_id)) {
			set_transient('wookb_accessibility_error', __('Selección no válida: elige una de las opciones de la lista.', 'ai-knowledge'), MINUTE_IN_SECONDS);
			self::redirect('visibilidad-ia');
		}

		$url = get_permalink($source_id);
		if (! $url) {
			set_transient('wookb_accessibility_error', __('No se pudo resolver la URL pública de ese contenido.', 'ai-knowledge'), MINUTE_IN_SECONDS);
			self::redirect('visibilidad-ia');
		}

		$result = Accessibility_Checker::check($url);
		if (is_wp_error($result)) {
			set_transient('wookb_accessibility_error', $result->get_error_message(), MINUTE_IN_SECONDS);
		} else {
			set_transient('wookb_accessibility_result', $result, MINUTE_IN_SECONDS);
		}

		self::redirect('visibilidad-ia');
	}

	/**
	 * Fase 6: borra el llms.txt físico de la raíz del sitio, si existe. Solo
	 * ese archivo, ruta fija (nunca a partir de input del usuario): el
	 * plugin genera el suyo dinámicamente vía rewrite (Llms_Txt::maybe_serve())
	 * cada vez que se pide, así que borrar el físico no deja al sitio sin
	 * llms.txt, solo deja de tapar al del plugin. Confirmación fuerte en JS
	 * (mismo patrón que "Borrar todos" del Registro) porque es un archivo
	 * fuera de la carpeta del propio plugin.
	 */
	public static function delete_physical_llms_txt()
	{
		self::verify('wookb_delete_physical_llms_txt');

		$path = ABSPATH . 'llms.txt';
		if (file_exists($path)) {
			wp_delete_file($path);
		}

		self::redirect('visibilidad-ia');
	}

	public static function download_llms_backup()
	{
		self::verify('wookb_download_llms_backup');
		$path = Llms_Txt::physical_path();
		$content = file_exists($path) ? (string) file_get_contents($path) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		set_transient('wookb_llms_backup_confirmed_' . get_current_user_id(), 1, 10 * MINUTE_IN_SECONDS);
		nocache_headers();
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="llms-backup-' . gmdate('Ymd-His') . '.txt"');
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	public static function apply_llms_physical()
	{
		self::verify('wookb_apply_llms_physical');
		$key = 'wookb_llms_backup_confirmed_' . get_current_user_id();
		if (Llms_Txt::physical_file_exists() && ! get_transient($key)) {
			wp_die(esc_html__('Antes de sustituir llms.txt tienes que descargar la copia actual.', 'ai-knowledge'));
		}
		if (! Llms_Txt::write_physical()) {
			wp_die(esc_html__('No se pudo escribir el llms.txt físico en la raíz del sitio.', 'ai-knowledge'));
		}
		delete_transient($key);
		self::redirect('visibilidad-ia');
	}

	/**
	 * Fase 11 (revision UX 2026-09-16): guarda la tabla unica de configuracion
	 * por bot (Permitir/Bloquear). El user_agent NUNCA se acepta como texto
	 * libre del POST: se resuelve exclusivamente contra Crawler_Catalog::all(),
	 * y el valor de accion se restringe a 'allow'|'block' via sanitize_key()
	 * mas una lista blanca -- esta tabla es la fuente unica que alimentan
	 * despues robots.txt y .htaccess (Crawler_Catalog::blocked_user_agents()).
	 */
	public static function save_crawler_actions()
	{
		self::verify('wookb_save_crawler_actions');

		$posted = isset($_POST['crawler_action']) && is_array($_POST['crawler_action']) ? wp_unslash($_POST['crawler_action']) : array(); // phpcs:ignore

		$actions = array();
		foreach (Crawler_Catalog::all() as $entry) {
			$ua = $entry['user_agent'];
			if (! isset($posted[$ua])) {
				continue;
			}
			$value = sanitize_key($posted[$ua]);
			if (in_array($value, array('allow', 'block'), true)) {
				$actions[$ua] = $value;
			}
		}

		Scope::update_settings(array('crawler_actions' => $actions));

		self::redirect('visibilidad-ia');
	}

	/** Guarda el modo de visibilidad aplicado a los bloques de crawler. */
	public static function save_crawler_visibility()
	{
		self::verify('wookb_save_crawler_visibility');
		$mode = isset($_POST['crawler_visibility_mode']) ? sanitize_key(wp_unslash($_POST['crawler_visibility_mode'])) : 'site'; // phpcs:ignore
		if (! in_array($mode, array('site', 'llms_only'), true)) {
			$mode = 'site';
		}
		Scope::update_settings(array('crawler_visibility_mode' => $mode));
		self::redirect('visibilidad-ia');
	}

	/**
	 * Fase 11 (revision UX 2026-09-16): fuerza la descarga real de robots.txt
	 * actual. Si no existe como archivo fisico (caso normal: WordPress sirve
	 * una version virtual), descarga esa version virtual leyendola por HTTP,
	 * igual que hace la pieza 1 de solo lectura de la propia pestaña.
	 */
	public static function download_robots_backup()
	{
		self::verify('wookb_download_robots_backup');

		if (file_exists(Robots_Txt_Guard::path())) {
			$content = (string) file_get_contents(Robots_Txt_Guard::path()); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_get_contents
		} else {
			$response = wp_remote_get(home_url('/robots.txt'));
			if (is_wp_error($response)) {
				wp_die(esc_html__('No se pudo leer el robots.txt actual (ni físico ni virtual) para generar la copia de seguridad.', 'ai-knowledge'));
			}
			$content = wp_remote_retrieve_body($response);
		}

		Robots_Txt_Guard::mark_backup_confirmed();

		nocache_headers();
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="robots-backup-' . gmdate('Ymd-His') . '.txt"');
		header('Content-Length: ' . strlen($content));
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- descarga de archivo, no HTML.
		exit;
	}

	/**
	 * Fase 11 (revision UX 2026-09-16): aplica de verdad el bloqueo via
	 * robots.txt, mismo criterio de seguridad que apply_htaccess_block():
	 * exige el transient de descarga confirmada, y los bots a bloquear salen
	 * siempre de Crawler_Catalog::blocked_user_agents() (la tabla unica),
	 * nunca de texto libre del POST.
	 */
	public static function apply_robots_block()
	{
		self::verify('wookb_apply_robots_block');

		if (! Robots_Txt_Guard::backup_confirmed()) {
			wp_die(esc_html__('Antes de aplicar el bloqueo tienes que descargar la copia actual de robots.txt. Pulsa "Descargar copia actual" y vuelve a intentarlo.', 'ai-knowledge'));
		}

		if (! Robots_Txt_Guard::is_available()) {
			wp_die(esc_html__('robots.txt no es escribible en este servidor (permisos de la carpeta raíz).', 'ai-knowledge'));
		}

		Robots_Txt_Guard::apply_actions(Crawler_Catalog::effective_actions(), Scope::settings()['crawler_visibility_mode']);
		// Uso unico: la confirmacion de descarga solo vale para esta aplicacion.
		Robots_Txt_Guard::clear_backup_confirmation();

		self::redirect('visibilidad-ia');
	}

	/**
	 * Fase 11, pieza 5: fuerza la descarga real del .htaccess actual (backup
	 * en mano del usuario, no solo una copia interna) y marca el transient de
	 * confirmacion de corta duracion que habilita "Aplicar bloqueo" -- doble
	 * proteccion server-side, no basta con deshabilitar el boton en el HTML.
	 */
	public static function download_htaccess_backup()
	{
		self::verify('wookb_download_htaccess_backup');

		if (! Htaccess_Guard::is_available()) {
			wp_die(esc_html__('No se encontró un .htaccess editable en este servidor (nginx u otra configuración): usa el bloque de código manual en su lugar.', 'ai-knowledge'));
		}

		$path = Htaccess_Guard::path();

		Htaccess_Guard::mark_backup_confirmed();

		nocache_headers();
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="htaccess-backup-' . gmdate('Ymd-His') . '.txt"');
		header('Content-Length: ' . filesize($path));
		readfile($path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile
		exit;
	}

	/**
	 * Fase 11, pieza 5: aplica de verdad el bloqueo via .htaccess. Antes de
	 * nada comprueba el transient de descarga confirmada -- si no existe,
	 * wp_die() con mensaje claro pidiendo descargar antes, sin tocar el
	 * archivo. Revision UX 2026-09-16: los bots a bloquear ya no salen de
	 * checkboxes de categoría, sino de la tabla unica de configuracion
	 * (Crawler_Catalog::blocked_user_agents()), misma fuente que robots.txt.
	 */
	public static function apply_htaccess_block()
	{
		self::verify('wookb_apply_htaccess_block');

		if (! Htaccess_Guard::backup_confirmed()) {
			wp_die(esc_html__('Antes de aplicar el bloqueo tienes que descargar la copia actual de .htaccess. Pulsa "Descargar copia actual" y vuelve a intentarlo.', 'ai-knowledge'));
		}

		if (! Htaccess_Guard::is_available()) {
			wp_die(esc_html__('No se encontró un .htaccess editable en este servidor (nginx u otra configuración): usa el bloque de código manual en su lugar.', 'ai-knowledge'));
		}

		Htaccess_Guard::apply_actions(Crawler_Catalog::effective_actions(), Scope::settings()['crawler_visibility_mode']);
		// Uso unico: la confirmacion de descarga solo vale para esta aplicacion.
		Htaccess_Guard::clear_backup_confirmation();

		self::redirect('visibilidad-ia');
	}

	public static function download_htaccess_generated()
	{
		self::verify('wookb_download_htaccess_generated');
		$content = Htaccess_Guard::generate_full_file(Crawler_Catalog::effective_actions(), Scope::settings()['crawler_visibility_mode']);
		nocache_headers();
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="htaccess-ai-knowledge-' . gmdate('Ymd-His') . '.txt"');
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
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
		if (! $screen || false === strpos((string) $screen->id, 'ai-knowledge')) {
			return;
		}

		$count = Registry::count(array('stale' => 1));
		if ($count < 1) {
			return;
		}

		$url = admin_url('admin.php?page=ai-knowledge&tab=registro&stale=1');
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
				'href'  => admin_url('admin.php?page=ai-knowledge&tab=registro&stale=1'),
			)
		);
	}
}
