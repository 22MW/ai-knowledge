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
		add_filter('admin_title', array(__CLASS__, 'fixed_admin_title'), 10, 2);
		add_filter('plugin_action_links_' . plugin_basename(AIKB_FILE), array(__CLASS__, 'plugin_action_links'));
		add_action('admin_init', array(__CLASS__, 'maybe_open_assistant'));
		add_action('wp_ajax_aikb_assistant_navigate', array(__CLASS__, 'assistant_navigate'));
		add_action('admin_post_aikb_download_geo_prompt', array(__CLASS__, 'download_geo_prompt'));
		$ajax_actions = array(
			'wookb_save_content' => 'save_content',
			'wookb_save_settings' => 'save_settings',
			'wookb_save_chatbot_settings' => 'save_chatbot_settings',
			'wookb_save_queue_settings' => 'save_queue_settings',
			'wookb_save_business_answers' => 'save_business_answers',
			'wookb_save_per_language' => 'save_per_language',
			'wookb_save_business_summary' => 'save_business_summary',
			'wookb_save_woocommerce_settings' => 'save_woocommerce_settings',
			'wookb_save_llms_faq' => 'save_llms_faq',
			'wookb_generate_business_summary_draft' => 'generate_business_summary_draft',
			'wookb_generate_faqs_draft' => 'generate_faqs_draft',
			'wookb_generate_prompt_draft' => 'generate_prompt_draft',
			'wookb_normalize_prompt' => 'normalize_prompt',
			'wookb_polish_store_doc' => 'polish_store_doc',
			// Pieza 1: boton "Generar" del Registro por AJAX, mismo callback
			// que admin_post_wookb_regenerate_single (ver regenerate_single(),
			// que ya distingue wp_doing_ajax() para responder JSON en vez de
			// redirigir).
			'wookb_regenerate_single' => 'regenerate_single',
			// Pedido explícito del usuario: mismo tratamiento AJAX que
			// "Generar" para los botones "Guardar límite" y "Guardar
			// cambios" del bloque "Ajustes avanzados" (ya existían antes de
			// esta tarea, solo se les añade AJAX aquí).
			'wookb_set_manual' => 'set_manual',
			'wookb_set_char_limit' => 'set_char_limit',
			'wookb_back_to_auto' => 'back_to_auto',
			// Pieza 2: guardar el prompt propio de un documento.
			'wookb_save_custom_prompt' => 'save_custom_prompt',
			// Pieza 5: artículos exclusivos de Genix.
			'wookb_genix_generate' => 'genix_generate',
			'wookb_genix_remove' => 'genix_remove',
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
		add_action('admin_post_wookb_save_per_language', array(__CLASS__, 'save_per_language'));
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
		add_action('admin_post_wookb_save_custom_prompt', array(__CLASS__, 'save_custom_prompt'));
		add_action('admin_post_wookb_genix_generate', array(__CLASS__, 'genix_generate'));
		add_action('admin_post_wookb_genix_remove', array(__CLASS__, 'genix_remove'));
		add_action('admin_post_wookb_resolve_stale', array(__CLASS__, 'resolve_stale'));
		add_action('admin_post_wookb_back_to_auto', array(__CLASS__, 'back_to_auto'));
		add_action('admin_post_wookb_save_woocommerce_settings', array(__CLASS__, 'save_woocommerce_settings'));
		add_action('admin_post_wookb_check_accessibility', array(__CLASS__, 'check_accessibility'));
		add_action('admin_post_wookb_delete_physical_llms_txt', array(__CLASS__, 'delete_physical_llms_txt'));
		add_action('admin_post_wookb_download_llms_backup', array(__CLASS__, 'download_llms_backup'));
		add_action('admin_post_wookb_apply_llms_physical', array(__CLASS__, 'apply_llms_physical'));
		add_action('admin_post_wookb_save_crawler_actions', array(__CLASS__, 'save_crawler_actions'));
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

	/**
	 * Todas las pestañas comparten el slug 'page=ai-knowledge' (solo cambia
	 * `tab=` en la URL), asi que WordPress -- que decide el <title> del
	 * navegador solo por el slug de pagina, sin mirar `tab=` -- siempre
	 * coincidia con la primera entrada de submenu registrada (Registro),
	 * cualquiera que fuese la pestaña real abierta. Se fija un titulo unico
	 * y estable del plugin en vez de intentar seguir la pestaña activa.
	 */
	public static function fixed_admin_title($admin_title, $title)
	{
		if (isset($_GET['page']) && 'ai-knowledge' === $_GET['page']) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$plugin_title = __('Base de conocimiento IA', 'ai-knowledge');
			return $plugin_title . ' — ' . get_bloginfo('name');
		}
		return $admin_title;
	}

	public static function menu()
	{
		$hook = add_menu_page(
			__('Base de conocimiento IA', 'ai-knowledge'),
			__('Base de conocimiento IA', 'ai-knowledge'),
			self::capability(),
			'ai-knowledge',
			array(__CLASS__, 'render'),
			AIKB_URL . 'assets/ai-knowledge-logo.svg',
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
		add_submenu_page(
			'ai-knowledge',
			__('Asistente de configuración', 'ai-knowledge'),
			__('Asistente', 'ai-knowledge'),
			self::capability(),
			'ai-knowledge-assistant',
			array(__CLASS__, 'render_assistant')
		);
	}

	public static function plugin_action_links($links)
	{
		if (current_user_can(self::capability())) {
			array_unshift($links, '<a href="' . esc_url(admin_url('admin.php?page=ai-knowledge-assistant')) . '">' . esc_html__('Abrir asistente', 'ai-knowledge') . '</a>');
		}
		return $links;
	}

	public static function maybe_open_assistant()
	{
		if (!is_admin() || !current_user_can(self::capability()) || wp_doing_ajax() || (empty($_GET['activate']) && empty($_GET['activate-multi']))) { // phpcs:ignore
			return;
		}
		$state = get_option('aikb_setup_assistant', array());
		if (!empty($state['initiated']) || !empty($state['finished'])) {
			return;
		}
		$state['initiated'] = true;
		$state['last_opened'] = current_time('mysql');
		update_option('aikb_setup_assistant', $state, false);
		wp_safe_redirect(admin_url('admin.php?page=ai-knowledge-assistant'));
		exit;
	}

	public static function render_assistant()
	{
		if (!current_user_can(self::capability())) {
			wp_die(esc_html__('No tienes permisos suficientes.', 'ai-knowledge'));
		}
		$steps = self::assistant_available_steps();
		$state = get_option('aikb_setup_assistant', array('current' => 'welcome', 'completed' => array(), 'skipped' => array()));
		$current = isset($_GET['step']) ? sanitize_key(wp_unslash($_GET['step'])) : (isset($state['current']) ? $state['current'] : 'welcome'); // phpcs:ignore
		if (!isset($steps[$current])) {
			$current = 'welcome';
		}
		$notice = '';
		if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['aikb_assistant_nonce'])) { // phpcs:ignore
			check_admin_referer('aikb_assistant', 'aikb_assistant_nonce');
			$action = isset($_POST['assistant_action']) ? sanitize_key(wp_unslash($_POST['assistant_action'])) : 'continue';
			$current = isset($_POST['assistant_step']) ? sanitize_key(wp_unslash($_POST['assistant_step'])) : $current;
			if (!isset($steps[$current])) {
				$current = 'welcome';
			}
			if (in_array($action, array('save', 'continue'), true) && 'welcome' !== $current) {
				self::assistant_save_step($current);
				// Releer: assistant_save_step('ai') escribe su propio
				// get_option()/update_option() de 'aikb_setup_assistant'
				// (ai_connection) -- sin releer aqui, el update_option() de
				// mas abajo (con el $state capturado ANTES de esta llamada)
				// lo sobrescribiria y lo perderia.
				$state = get_option('aikb_setup_assistant', $state);
				if ('save' === $action) $notice = __('Guardado correctamente.', 'ai-knowledge');
			}
			$state['initiated'] = true;
			$state['current'] = $current;
			$state['last_opened'] = current_time('mysql');
			if ('skip' === $action) {
				$state['skipped'] = array_values(array_unique(array_merge((array) ($state['skipped'] ?? array()), array($current))));
			} elseif ('finish' === $current) {
				// El usuario acaba de pasar por el paso de resumen (ver
				// assistant_steps()): se marca el asistente como terminado,
				// sin depender de una accion especial que ya no existe.
				$state['finished'] = true;
				$state['completed'] = array_values(array_unique(array_merge((array) ($state['completed'] ?? array()), array($current))));
			} elseif ('exit' !== $action) {
				$state['completed'] = array_values(array_unique(array_merge((array) ($state['completed'] ?? array()), array($current))));
			}
			$next = self::assistant_next_step($current, $steps, $action);
			if ('exit' === $action) {
				update_option('aikb_setup_assistant', $state, false);
				wp_safe_redirect(admin_url('admin.php?page=ai-knowledge'));
				exit;
			}
			$state['current'] = $next;
			update_option('aikb_setup_assistant', $state, false);
			$current = $next;
		}
		$state['last_opened'] = current_time('mysql');
		update_option('aikb_setup_assistant', $state, false);
		echo '<div class="wookb-wrap wookb-assistant is-loading">';
		echo '<div class="wookb-assistant-header"><div class="wookb-assistant-brand"><strong>' . esc_html__('AI Knowledge & Visibility', 'ai-knowledge') . '</strong><span>' . esc_html(sprintf(__('Versión %s', 'ai-knowledge'), AIKB_VERSION)) . '</span></div><a class="button" href="' . esc_url(admin_url('admin.php?page=ai-knowledge')) . '">' . esc_html__('Salir', 'ai-knowledge') . '</a></div>';
		echo '<ol class="wookb-assistant-progress" aria-label="' . esc_attr__('Progreso del asistente', 'ai-knowledge') . '">';
		$step_position = 0;
		$current_position = array_search($current, array_keys($steps), true);
		foreach ($steps as $key => $step) {
			$active = $key === $current ? ' is-active' : '';
			$done = in_array($key, (array) ($state['completed'] ?? array()), true) ? ' is-done' : '';
			$past = ($step_position < $current_position) ? ' is-past' : '';
			$future = ($step_position > $current_position) ? ' is-future' : '';
			echo '<li class="' . esc_attr($active . $done . $past . $future) . '" data-assistant-step="' . esc_attr($key) . '" aria-label="' . esc_attr($step['title']) . '" title="' . esc_attr($step['title']) . '"><a href="' . esc_url(admin_url('admin.php?page=ai-knowledge-assistant&step=' . $key)) . '" data-assistant-step-link>' . esc_html($step['number']) . '</a></li>';
			$step_position++;
		}
		echo '</ol>';
		echo '<div data-assistant-stage>' . self::assistant_panel($current, $steps, $notice) . '</div></div>';
	}

	protected static function assistant_panel($step, $steps, $notice = '')
	{
		ob_start();
		?>
		<div class="wookb-assistant-breadcrumb"><span><?php esc_html_e('Asistente de configuración', 'ai-knowledge'); ?></span><span aria-hidden="true">&gt;</span><strong><?php echo esc_html($steps[$step]['title']); ?></strong></div>
		<div class="wookb-card" data-assistant-panel>
			<div class="wookb-assistant-loader" aria-live="polite"><span class="wookb-assistant-loader-dots" aria-hidden="true"><i></i><i></i><i></i></span></div>
			<p class="wookb-assistant-description"><?php echo esc_html($steps[$step]['description']); ?></p>
			<?php
			// El paso "finish" (resumen) no tiene campos que enviar, solo texto de
			// solo lectura y los formularios propios de render_seed_controls() --
			// se imprime aqui, ANTES del <form> del asistente, para que los botones
			// Atras/Continuar sigan quedando al final como en el resto de pasos, sin
			// anidar un <form> dentro de otro (HTML no lo permite: el navegador
			// cierra el de fuera en el primer <form> anidado, dejando fuera el resto
			// del contenido -- bug real confirmado, "Atras"/"Continuar" no hacian
			// nada en este paso hasta este cambio).
			if ('finish' === $step) {
				echo self::assistant_screen_content($step); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
			<form method="post" data-assistant-form>
				<?php wp_nonce_field('aikb_assistant', 'aikb_assistant_nonce'); ?>
				<input type="hidden" name="assistant_step" value="<?php echo esc_attr($step); ?>" />
				<?php if ('finish' !== $step) : ?>
					<?php echo self::assistant_screen_content($step); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
				<div class="wookb-assistant-actions">
					<?php if ('welcome' !== $step) : ?><button type="submit" class="button" name="assistant_action" value="back"><?php esc_html_e('Atrás', 'ai-knowledge'); ?></button><?php endif; ?>
					<span class="wookb-assistant-actions-main">
						<?php if (!in_array($step, array('welcome', 'success', 'finish'), true)) : ?><button type="submit" class="button" name="assistant_action" value="save"><?php esc_html_e('Guardar configuración', 'ai-knowledge'); ?></button><?php endif; ?>
						<?php if ('success' !== $step) : ?><button type="submit" class="button button-primary" name="assistant_action" value="continue"><?php esc_html_e('Continuar', 'ai-knowledge'); ?></button><?php endif; ?>
					</span>
				</div>
				<div class="wookb-assistant-feedback<?php echo $notice ? ' is-visible' : ''; ?>" data-assistant-feedback role="status" aria-live="polite"><?php echo esc_html($notice); ?></div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Rediseño del asistente (2026-09-24): cada paso genera su documento en
	 * cuanto se guarda (ver assistant_save_step()), en vez de todo junto al
	 * final. Orden aprobado por el usuario tras el diseño del arquitecto:
	 * welcome -> ai -> limits -> content -> business -> woocommerce -> faqs
	 * -> chatbot -> visibility -> server -> finish (resumen) -> success.
	 * Reordenar aqui basta: assistant_next_step() navega por
	 * array_keys($steps), no por el campo "number" (solo la etiqueta visual
	 * del progreso, renumerada 1..12 con el nuevo orden).
	 */
	protected static function assistant_steps()
	{
		return array(
			'welcome' => array('number' => 1, 'title' => __('Bienvenida', 'ai-knowledge'), 'description' => __('AI Knowledge convierte el contenido de tu sitio en documentos preparados para buscadores, asistentes y sistemas de inteligencia artificial. Este recorrido revisará contigo las decisiones principales sin borrar una configuración existente. Puedes saltar cualquier paso, salir cuando quieras y continuar más adelante desde el mismo punto.', 'ai-knowledge')),
			'ai' => array('number' => 2, 'title' => __('Origen de IA', 'ai-knowledge'), 'description' => __('Elige el servicio que ayudará a redactar y mejorar los documentos. AI Knowledge utiliza las conexiones ya configuradas mediante los Conectores de WordPress o Support Genix y nunca guarda aquí sus claves. Si todavía no existe una conexión, puedes continuar sin IA y configurarla después: cada paso siguiente que genere contenido avisará si no hay conexión, sin bloquearte.', 'ai-knowledge'), 'link' => admin_url('admin.php?page=ai-knowledge&tab=ajustes')),
			'limits' => array('number' => 3, 'title' => __('Límites de generación', 'ai-knowledge'), 'description' => __('Comprueba el largo máximo del texto y los límites de generación antes de continuar: a partir de aquí, cada paso puede generar contenido real con IA en cuanto lo guardes.', 'ai-knowledge'), 'link' => admin_url('admin.php?page=ai-knowledge&tab=ajustes')),
			'content' => array('number' => 4, 'title' => __('Contenido y alcance', 'ai-knowledge'), 'description' => __('Selecciona los tipos de contenido público que deben formar parte de la base de conocimiento. La selección determina qué entradas, páginas, productos u otros contenidos podrán generar documentos. Al guardar, se encola la generación de todo lo que entre en el alcance. Las taxonomías, términos, identificadores y campos personalizados pueden configurarse después desde la pantalla Contenido del plugin.', 'ai-knowledge'), 'link' => admin_url('admin.php?page=ai-knowledge&tab=contenido')),
			'business' => array('number' => 5, 'title' => __('Negocio', 'ai-knowledge'), 'description' => __('Añade la información estable que una IA necesita para comprender correctamente tu negocio: identidad, ubicación, público, contacto, horario y enfoque. Estos datos complementan el contenido del sitio y ayudan a producir respuestas coherentes sin inventar información.', 'ai-knowledge'), 'link' => admin_url('admin.php?page=ai-knowledge&tab=negocio')),
			'woocommerce' => array('number' => 6, 'title' => __('WooCommerce', 'ai-knowledge'), 'description' => __('Revisa los datos generales de la tienda, el país, la moneda, las condiciones de compra, la recogida, los plazos y el contacto. Al guardar, se generan los documentos de información de tienda si hay conexión de IA disponible. Los envíos, impuestos, pagos, categorías y otras opciones avanzadas pueden completarse después en una ventana nueva sin perder el progreso.', 'ai-knowledge'), 'link' => admin_url('admin.php?page=ai-knowledge&tab=woocommerce')),
			'faqs' => array('number' => 7, 'title' => __('FAQs', 'ai-knowledge'), 'description' => __('Genera con IA las preguntas frecuentes públicas de tu negocio a partir de los datos ya introducidos, y las publica directamente en llms.txt. Este paso solo aparece si hay una conexión de IA disponible.', 'ai-knowledge'), 'link' => admin_url('admin.php?page=ai-knowledge&tab=faqs')),
			'chatbot' => array('number' => 8, 'title' => __('Chatbot', 'ai-knowledge'), 'description' => __('Configura cómo Support Genix utilizará la base de conocimiento, cuántos documentos relacionados podrá consultar y qué información adicional debe tener en cuenta. Al guardar, se sincroniza con Genix si hay conexión disponible. Este paso solo aparece cuando la integración está disponible.', 'ai-knowledge'), 'link' => admin_url('admin.php?page=ai-knowledge&tab=prompt')),
			'visibility' => array('number' => 9, 'title' => __('Visibilidad IA', 'ai-knowledge'), 'description' => __('Decide qué familias de crawlers pueden acceder al sitio y cómo se gestiona su acceso a llms.txt. Al guardar, esta selección queda conservada para utilizarla en las reglas de visibilidad del plugin.', 'ai-knowledge'), 'link' => admin_url('admin.php?page=ai-knowledge&tab=visibilidad-ia')),
			'server' => array('number' => 10, 'title' => __('Archivos del servidor', 'ai-knowledge'), 'description' => __('Comprueba si robots.txt y .htaccess difieren de la configuración guardada. Descarga las copias y las versiones preparadas; el asistente no muestra el código completo; reemplazar .htaccess exige copia descargada y una casilla de responsabilidad.', 'ai-knowledge'), 'link' => admin_url('admin.php?page=ai-knowledge&tab=visibilidad-ia')),
			'finish' => array('number' => 11, 'title' => __('Resumen', 'ai-knowledge'), 'description' => __('Revisa el estado real de cada paso: qué se ha generado ya y qué queda pendiente. Puedes generar ahora los documentos que aún falten, o dejarlo para más tarde desde Generación masiva.', 'ai-knowledge'), 'link' => admin_url('admin.php?page=ai-knowledge&tab=carga-inicial')),
			'success' => array('number' => 12, 'title' => __('Resumen final', 'ai-knowledge'), 'description' => __('La configuración del asistente ha terminado. Revisa el estado real de los documentos y accede directamente a las áreas principales del plugin.', 'ai-knowledge')),
		);
	}

	protected static function assistant_screen_content($step)
	{
		$settings = Scope::settings();
		ob_start();
		if ('server' === $step) {
			$crawler_actions = Crawler_Catalog::effective_actions();
			$mode = $settings['crawler_visibility_mode'];
			if (file_exists(Robots_Txt_Guard::path())) {
				$robots_current = (string) file_get_contents(Robots_Txt_Guard::path()); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			} else {
				$robots_response = wp_remote_get(home_url('/robots.txt'));
				$robots_current = is_wp_error($robots_response) ? '' : wp_remote_retrieve_body($robots_response);
			}
			$robots_generated = Robots_Txt_Guard::generate_full_file($crawler_actions, $mode);
			$htaccess_current = Htaccess_Guard::is_available() ? (string) file_get_contents(Htaccess_Guard::path()) : '';
			$htaccess_generated = Htaccess_Guard::generate_full_file($crawler_actions, $mode);
			// Cada carga del paso invalida la copia descargada antes: hay que
			// volver a descargarla (vale 10 minutos).
			Robots_Txt_Guard::clear_backup_confirmation();
			Htaccess_Guard::clear_backup_confirmation();
			$robots_backup_ready = Robots_Txt_Guard::backup_confirmed();
			if (!Robots_Txt_Guard::managed_block_matches($robots_current, $crawler_actions, $mode)) : ?><p class="notice notice-warning inline"><strong><?php esc_html_e('Las reglas de AI Knowledge en robots.txt son diferentes de la configuración guardada.', 'ai-knowledge'); ?></strong></p><?php else : ?><p class="notice notice-success inline"><?php esc_html_e('Las reglas de AI Knowledge en robots.txt coinciden con la configuración guardada.', 'ai-knowledge'); ?></p><?php endif; ?>
			<p><?php esc_html_e('Descarga una copia actual antes de actualizar robots.txt con la versión preparada.', 'ai-knowledge'); ?></p>
			<p><button type="button" class="button" data-server-action="wookb_download_robots_backup" data-server-nonce="<?php echo esc_attr(wp_create_nonce('wookb_download_robots_backup')); ?>"><?php esc_html_e('Descargar copia actual de robots.txt', 'ai-knowledge'); ?></button> <button type="button" class="button button-primary" data-server-action="wookb_apply_robots_block" data-server-nonce="<?php echo esc_attr(wp_create_nonce('wookb_apply_robots_block')); ?>" data-return-assistant="1" <?php disabled(!$robots_backup_ready); ?>><?php esc_html_e('Actualizar robots.txt', 'ai-knowledge'); ?></button></p>
			<?php if (!Htaccess_Guard::managed_block_matches($htaccess_current, $crawler_actions, $mode)) : ?><p class="notice notice-warning inline"><strong><?php esc_html_e('Las reglas de AI Knowledge en .htaccess son diferentes de la configuración guardada.', 'ai-knowledge'); ?></strong></p><?php else : ?><p class="notice notice-success inline"><?php esc_html_e('Las reglas de AI Knowledge en .htaccess coinciden con la configuración guardada.', 'ai-knowledge'); ?></p><?php endif; ?>
			<p><?php esc_html_e('Puedes reemplazar el .htaccess desde aquí: solo se cambia el bloque de AI Knowledge y tus reglas que choquen se comentan, no se borran. Descarga primero la copia actual y marca la casilla.', 'ai-knowledge'); ?></p>
			<p><button type="button" class="button" data-server-action="wookb_download_htaccess_generated" data-server-nonce="<?php echo esc_attr(wp_create_nonce('wookb_download_htaccess_generated')); ?>"><?php esc_html_e('Descargar .htaccess preparado', 'ai-knowledge'); ?></button></p>
			<?php if (!Htaccess_Guard::is_available()) : ?><p class="notice notice-warning inline"><?php esc_html_e('No se puede reemplazar el .htaccess: no existe o el servidor no permite escribirlo. No se ha intentado modificar nada; usa el archivo preparado a mano.', 'ai-knowledge'); ?></p><?php else : ?>
			<p><button type="button" class="button" data-server-action="wookb_download_htaccess_backup" data-server-nonce="<?php echo esc_attr(wp_create_nonce('wookb_download_htaccess_backup')); ?>"><?php esc_html_e('Descargar copia actual de .htaccess', 'ai-knowledge'); ?></button></p>
			<p><label><input type="checkbox" data-wookb-htaccess-ack /> <?php esc_html_e('Asumo toda la responsabilidad y sé lo que estoy haciendo.', 'ai-knowledge'); ?></label></p>
			<p><button type="button" class="button button-primary" data-server-action="wookb_apply_htaccess_block" data-server-nonce="<?php echo esc_attr(wp_create_nonce('wookb_apply_htaccess_block')); ?>" data-return-assistant="1" data-wookb-htaccess-apply="1" data-backup-ready="<?php echo Htaccess_Guard::backup_confirmed() ? '1' : '0'; ?>" disabled><?php esc_html_e('Reemplazar .htaccess', 'ai-knowledge'); ?></button></p><?php endif; ?>
			<p><button type="button" class="button" data-assistant-check-server><?php esc_html_e('Comprobar cambios', 'ai-knowledge'); ?></button></p><?php
			return ob_get_clean();
		}
		if ('welcome' === $step) : $geo_prompt = self::build_geo_prompt(); ?>
			<p><?php esc_html_e('Comprueba tu sitio en un agente externo antes de configurar el plugin. Después de completar la configuración, repite la comprobación con este mismo prompt para comparar el resultado.', 'ai-knowledge'); ?></p><textarea id="wookb-welcome-geo-prompt" readonly rows="8" style="width:100%;max-width:100%;font-size:13px;"><?php echo esc_textarea($geo_prompt); ?></textarea><p><button type="button" class="button" data-wookb-copy-target="wookb-welcome-geo-prompt"><?php esc_html_e('Copiar Prompt', 'ai-knowledge'); ?></button></p>
			<div class="wookb-assistant-welcome-copy"><h3><?php esc_html_e('Qué revisaremos', 'ai-knowledge'); ?></h3><ul><li><?php esc_html_e('El origen de IA y el contenido que formará la base de conocimiento.', 'ai-knowledge'); ?></li><li><?php esc_html_e('Los datos del negocio y las integraciones disponibles.', 'ai-knowledge'); ?></li><li><?php esc_html_e('La visibilidad para IA y los límites de generación.', 'ai-knowledge'); ?></li></ul></div>
		<?php elseif ('ai' === $step) : $available = AI_Client::wordpress_available(); $models = $available ? AI_Client::available_models() : array(); $ai_ready = self::assistant_ai_available(); ?>
			<?php if ($ai_ready) : ?>
				<p class="notice notice-success inline"><?php esc_html_e('Conexión de IA disponible: los pasos siguientes podrán generar contenido real.', 'ai-knowledge'); ?></p>
			<?php else : ?>
				<p class="notice notice-warning inline"><strong><?php esc_html_e('No se detecta ninguna conexión de IA activa todavía.', 'ai-knowledge'); ?></strong> <?php esc_html_e('Puedes continuar sin problema: los pasos siguientes guardarán tus datos igual, pero no generarán contenido con IA hasta que conectes un origen (aquí, en Conectores de WordPress, o activando Support Genix) y vuelvas a guardar ese paso.', 'ai-knowledge'); ?></p>
			<?php endif; ?>
			<div class="wookb-assistant-fields"><label class="wookb-assistant-field"><span><?php esc_html_e('Origen de IA', 'ai-knowledge'); ?></span><select name="ai_key_source"><option value="genix" <?php selected('genix', $settings['ai_key_source']); ?>><?php esc_html_e('Support Genix', 'ai-knowledge'); ?></option><?php if ($available) : ?><option value="wp_connectors" <?php selected('wp_connectors', $settings['ai_key_source']); ?>><?php esc_html_e('Conectores de WordPress', 'ai-knowledge'); ?></option><?php endif; ?></select></label><label class="wookb-assistant-field"><span><?php esc_html_e('Modelo', 'ai-knowledge'); ?></span><select name="wp_ai_model"><option value="<?php echo esc_attr(AI_Client::MODEL_AUTO); ?>" <?php selected(AI_Client::MODEL_AUTO, $settings['wp_ai_model']); ?>><?php esc_html_e('Automático (recomendado)', 'ai-knowledge'); ?></option><?php foreach ($models as $key => $model) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($key, $settings['wp_ai_model']); ?>><?php echo esc_html($model['name'] . ' (' . $model['model'] . ')'); ?></option><?php endforeach; ?></select></label></div><div class="wookb-assistant-external-links"><a class="button" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url(admin_url('options-connectors.php')); ?>"><?php esc_html_e('Configurar Conectores de WordPress', 'ai-knowledge'); ?></a> <a class="button" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url(admin_url('plugins.php')); ?>"><?php esc_html_e('Abrir Support Genix', 'ai-knowledge'); ?></a></div>
		<?php elseif ('limits' === $step) : ?><div class="wookb-assistant-fields"><label class="wookb-assistant-field"><span><?php esc_html_e('Largo máximo del texto', 'ai-knowledge'); ?></span><input type="number" min="100" max="10000" name="body_char_limit" value="<?php echo esc_attr($settings['body_char_limit']); ?>" /></label><label class="wookb-assistant-field"><span><?php esc_html_e('Tokens de salida', 'ai-knowledge'); ?></span><input type="number" min="200" name="output_tokens" value="<?php echo esc_attr($settings['output_tokens']); ?>" /></label><label class="wookb-assistant-field"><span><?php esc_html_e('Límite diario', 'ai-knowledge'); ?></span><input type="number" min="1" name="daily_limit" value="<?php echo esc_attr($settings['daily_limit']); ?>" /></label><label class="wookb-assistant-field"><span><?php esc_html_e('Tamaño de lote', 'ai-knowledge'); ?></span><input type="number" min="1" name="batch_size" value="<?php echo esc_attr($settings['batch_size']); ?>" /></label><label class="wookb-assistant-toggle"><input type="checkbox" name="no_limit" value="1" <?php checked(!empty($settings['no_limit'])); ?> /><span><?php esc_html_e('Sin límite diario', 'ai-knowledge'); ?></span></label><label class="wookb-assistant-toggle"><input type="checkbox" name="indexnow_enabled" value="1" <?php checked(!empty($settings['indexnow_enabled'])); ?> /><span><?php esc_html_e('Avisar a IndexNow', 'ai-knowledge'); ?></span></label></div>
		<?php elseif ('content' === $step) : $post_types = get_post_types(array('public' => true), 'objects'); ?>
			<input type="hidden" name="post_types_mode" value="explicit" /><div class="wookb-assistant-field"><span><?php esc_html_e('Tipos de contenido que quieres incluir', 'ai-knowledge'); ?></span><div class="wookb-chip-group"><?php foreach ($post_types as $post_type) : if ('attachment' === $post_type->name) continue; ?><label class="wookb-chip"><input type="checkbox" name="post_types[]" value="<?php echo esc_attr($post_type->name); ?>" <?php checked(in_array($post_type->name, (array) $settings['post_types'], true)); ?> /> <?php echo esc_html($post_type->labels->name); ?></label><?php endforeach; ?></div></div>
		<?php elseif ('business' === $step) : $answers = Chatbot_Prompt_Builder::get_saved_answers(); ?><div class="wookb-assistant-fields"><?php foreach (Chatbot_Prompt_Builder::questions_by_group('negocio') as $key => $question) : ?><label class="wookb-assistant-field"><span><?php echo esc_html($question['label']); ?></span><?php if ('textarea' === $question['type']) : ?><textarea name="answers[<?php echo esc_attr($key); ?>]" rows="3"><?php echo esc_textarea($answers[$key]); ?></textarea><?php else : ?><input type="text" name="answers[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($answers[$key]); ?>" /><?php endif; ?><small><?php echo esc_html($question['placeholder']); ?></small></label><?php endforeach; ?><?php echo self::render_language_fields('assistant'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado y escapado dentro del propio metodo. ?></div>
		<?php elseif ('woocommerce' === $step) : ?>
			<div class="wookb-assistant-fields"><?php foreach (array('wc_store_name' => __('Nombre de la tienda', 'ai-knowledge'), 'wc_currency' => __('Moneda', 'ai-knowledge'), 'wc_base_country' => __('País base', 'ai-knowledge')) as $key => $label) : ?><label class="wookb-assistant-field"><span><?php echo esc_html($label); ?></span><input type="text" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($settings[$key]); ?>" /></label><?php endforeach; ?><label class="wookb-assistant-field"><span><?php esc_html_e('Condiciones de venta', 'ai-knowledge'); ?></span><textarea name="wc_terms_text" rows="3"><?php echo esc_textarea($settings['wc_terms_text']); ?></textarea></label><label class="wookb-assistant-field"><span><?php esc_html_e('Política de devoluciones', 'ai-knowledge'); ?></span><textarea name="wc_returns_text" rows="3"><?php echo esc_textarea($settings['wc_returns_text']); ?></textarea></label><label class="wookb-assistant-field"><span><?php esc_html_e('Plazo de entrega', 'ai-knowledge'); ?></span><textarea name="delivery_time_note" rows="3"><?php echo esc_textarea($settings['delivery_time_note']); ?></textarea></label><label class="wookb-assistant-field"><span><?php esc_html_e('Contacto y horario de la tienda', 'ai-knowledge'); ?></span><textarea name="wc_contact_hours" rows="3"><?php echo esc_textarea($settings['wc_contact_hours']); ?></textarea></label><label class="wookb-assistant-toggle"><input type="checkbox" name="wc_pickup_available" value="1" <?php checked(!empty($settings['wc_pickup_available'])); ?> /><span><?php esc_html_e('Recogida en tienda disponible', 'ai-knowledge'); ?></span></label></div>
		<?php elseif ('chatbot' === $step) : $answers = Chatbot_Prompt_Builder::get_saved_answers(); ?><div class="wookb-assistant-fields"><label class="wookb-assistant-field"><span><?php esc_html_e('Límite de documentos relacionados', 'ai-knowledge'); ?></span><input type="number" min="0" name="chatbot_docs_list_limit" value="<?php echo esc_attr($settings['chatbot_docs_list_limit']); ?>" /></label><?php foreach (Chatbot_Prompt_Builder::questions_by_group('chatbot') as $key => $question) : ?><label class="wookb-assistant-field"><span><?php echo esc_html($question['label']); ?></span><?php if ('textarea' === $question['type']) : ?><textarea name="answers[<?php echo esc_attr($key); ?>]" rows="3"><?php echo esc_textarea($answers[$key]); ?></textarea><?php else : ?><input type="text" name="answers[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($answers[$key]); ?>" /><?php endif; ?></label><?php endforeach; ?></div>
		<?php elseif ('faqs' === $step) :
			// Cambio de comportamiento (2026-09-24): el FAQ ya solo se genera
			// en el idioma principal (ver assistant_save_step('faqs')), no en
			// cada idioma activo -- un solo bloque, no un foreach por idioma.
			$faq_lang = Languages::main_language();
			$faq_error = get_transient('wookb_assistant_faqs_error_' . $faq_lang);
			$faq_current = Llms_Faq::read($faq_lang);
			?>
			<p class="description"><?php esc_html_e('Al guardar este paso, se genera con IA y se publica de inmediato en llms.txt (excepción explícita de este paso del asistente a la norma habitual de revisar antes de publicar).', 'ai-knowledge'); ?></p>
			<h3><?php esc_html_e('Preguntas frecuentes', 'ai-knowledge'); ?></h3>
			<?php if ($faq_error) : ?>
				<p class="notice notice-warning inline"><?php echo esc_html($faq_error); ?></p>
			<?php elseif ('' !== $faq_current) : ?>
				<?php $faq_row = Llms_Faq::registry_row($faq_lang); $faq_date = ($faq_row && !empty($faq_row->generated_at)) ? mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $faq_row->generated_at) : ''; ?>
				<p class="notice notice-success inline"><?php echo esc_html('' !== $faq_date
					/* translators: %s: fecha y hora de generación */
					? sprintf(__('Ya hay un FAQ publicado en llms.txt (generado el %s). Al guardar este paso otra vez (con «Guardar configuración» o «Continuar»), la IA vuelve a redactarlo tomando este texto como referencia y el resultado sustituye al publicado: puede cambiar. Para conservar el actual, sal del paso con «Atrás» o desde el progreso, sin guardar.', 'ai-knowledge'), $faq_date)
					: __('Ya hay un FAQ publicado en llms.txt. Al guardar este paso otra vez (con «Guardar configuración» o «Continuar»), la IA vuelve a redactarlo tomando este texto como referencia y el resultado sustituye al publicado: puede cambiar. Para conservar el actual, sal del paso con «Atrás» o desde el progreso, sin guardar.', 'ai-knowledge')); ?></p>
				<textarea readonly rows="8" style="width:100%;max-width:100%;font-size:13px;"><?php echo esc_textarea($faq_current); ?></textarea>
			<?php else : ?>
				<p class="description"><?php esc_html_e('Todavía no hay FAQ generado para este idioma.', 'ai-knowledge'); ?></p>
			<?php endif; ?>
		<?php elseif ('visibility' === $step) :
			$categories = self::assistant_crawler_categories();
			$category = isset($_POST['assistant_category']) ? sanitize_key(wp_unslash($_POST['assistant_category'])) : 'ai_search'; // phpcs:ignore
			if (!isset($categories[$category])) $category = 'ai_search';
			$actions = Crawler_Catalog::effective_actions(); ?>
			<input type="hidden" name="assistant_category" value="<?php echo esc_attr($category); ?>" />
			<nav class="wookb-assistant-subnav" aria-label="<?php esc_attr_e('Categorías de crawlers', 'ai-knowledge'); ?>"><?php foreach ($categories as $key => $item) : ?><button type="button" class="button<?php echo $key === $category ? ' button-primary' : ''; ?>" data-assistant-category="<?php echo esc_attr($key); ?>"><?php echo esc_html($item['title']); ?></button><?php endforeach; ?></nav>
			<h3><?php echo esc_html($categories[$category]['title']); ?></h3><?php if (!empty($categories[$category]['description'])) : ?><p><?php echo esc_html($categories[$category]['description']); ?></p><?php endif; ?><div class="wookb-assistant-crawler-bulk"><button type="button" class="button" data-crawler-bulk="allow"><?php esc_html_e('Permitir todos', 'ai-knowledge'); ?></button> <button type="button" class="button" data-crawler-bulk="block"><?php esc_html_e('Bloquear todos', 'ai-knowledge'); ?></button></div>
			<div class="wookb-assistant-crawlers"><?php foreach (Crawler_Catalog::all() as $crawler) : if ($crawler['category'] !== $category) continue; ?><div class="wookb-assistant-crawler"><div><strong><?php echo esc_html($crawler['user_agent']); ?></strong><span><?php echo esc_html($crawler['operator']); ?></span><?php if ($crawler['description'] !== $categories[$category]['description']) : ?><p><?php echo esc_html($crawler['description']); ?></p><?php endif; ?></div><select name="crawler_action[<?php echo esc_attr($crawler['user_agent']); ?>]"><option value="allow" <?php selected('allow', $actions[$crawler['user_agent']]); ?>><?php esc_html_e('Permitir', 'ai-knowledge'); ?></option><option value="block" <?php selected('block', $actions[$crawler['user_agent']]); ?>><?php esc_html_e('Bloquear', 'ai-knowledge'); ?></option></select></div><?php endforeach; ?></div>
			<h3><?php esc_html_e('Acceso de crawlers bloqueados a llms.txt', 'ai-knowledge'); ?></h3><?php echo self::render_visibility_switch($settings['crawler_visibility_mode']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado y escapado campo a campo dentro del propio metodo. ?><h3><?php esc_html_e('Robots.txt y .htaccess', 'ai-knowledge'); ?></h3><p><?php esc_html_e('La política que guardes aquí sirve como base para preparar las reglas de robots.txt y .htaccess. Esos archivos se gestionan después desde la pantalla Visibilidad IA, donde puedes revisar el contenido antes de aplicarlo.', 'ai-knowledge'); ?></p>
		<?php elseif ('finish' === $step) :
			// Paso RESUMEN (antes era el paso de limites): SOLO consultas de
			// lectura, ya baratas (Registry, Llms_Faq, get_option...). NUNCA
			// disparar aqui generacion/red -- este contenido se precalcula
			// para TODOS los pasos disponibles en cada carga de la pagina del
			// asistente (ver Admin::assets()), no solo al guardar.
			?>
			<?php echo self::render_assistant_summary(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado y escapado campo a campo dentro del propio metodo. ?>
			<hr />
			<?php
			// render_seed_controls() lleva sus propios <form>; no puede ir dentro del
			// <form data-assistant-form> del asistente (HTML no permite formularios
			// anidados -- el navegador cierra el de fuera en el primer <form> anidado
			// que encuentra, dejando fuera de ese form el resto del contenido, incluido
			// el boton "Continuar": bug real confirmado). Por eso assistant_panel()
			// imprime todo assistant_screen_content('finish') ANTES de abrir el <form>
			// del asistente para este paso en concreto -- no tiene campos que enviar.
			echo self::render_seed_controls(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		<?php elseif ('success' === $step) : $state = get_option('aikb_setup_assistant', array()); $summary = Registry::summary(); $geo_prompt = self::build_geo_prompt(); ?>
			<div class="wookb-assistant-summary"><p><strong><?php esc_html_e('Documentos registrados:', 'ai-knowledge'); ?></strong> <?php echo (int) $summary['total']; ?></p><p><strong><?php esc_html_e('Documentos pendientes:', 'ai-knowledge'); ?></strong> <?php echo (int) Registry::count(array('status' => 'queued')); ?></p><p><strong><?php esc_html_e('Pasos omitidos:', 'ai-knowledge'); ?></strong> <?php echo empty($state['skipped']) ? esc_html__('Ninguno', 'ai-knowledge') : esc_html(implode(', ', (array) $state['skipped'])); ?></p><p><a class="button button-primary" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url(home_url('/llms.txt')); ?>"><?php esc_html_e('Ver llms.txt', 'ai-knowledge'); ?></a></p><p><?php esc_html_e('Recomendamos comprobar este prompt en un agente externo para verificar el funcionamiento del plugin y la visibilidad de tu web.', 'ai-knowledge'); ?></p><textarea id="wookb-assistant-geo-prompt" readonly rows="12" style="width:100%;max-width:100%;"><?php echo esc_textarea($geo_prompt); ?></textarea><p><button type="button" class="button" data-wookb-copy-target="wookb-assistant-geo-prompt"><?php esc_html_e('Copiar Prompt', 'ai-knowledge'); ?></button></p><div class="wookb-assistant-links"><?php foreach (array('registro' => __('Registro', 'ai-knowledge'), 'contenido' => __('Contenido', 'ai-knowledge'), 'negocio' => __('Negocio', 'ai-knowledge'), 'woocommerce' => __('WooCommerce', 'ai-knowledge'), 'visibilidad-ia' => __('Visibilidad IA', 'ai-knowledge'), 'ajustes' => __('Ajustes', 'ai-knowledge')) as $tab => $label) : if ('woocommerce' === $tab && !class_exists('WooCommerce')) continue; ?><a href="<?php echo esc_url(admin_url('admin.php?page=ai-knowledge' . ('registro' === $tab ? '' : '&tab=' . $tab))); ?>"><?php echo esc_html($label); ?></a><?php endforeach; ?></div></div>
		<?php endif;
		return ob_get_clean();
	}

	protected static function assistant_crawler_categories()
	{
		$labels = array(
			'ai_search' => __('Búsqueda/citas IA', 'ai-knowledge'), 'user_requested_assistant' => __('Uso bajo demanda', 'ai-knowledge'),
			'model_training' => __('Entrenamiento de modelos', 'ai-knowledge'), 'seo_scraper' => __('SEO y scraping', 'ai-knowledge'),
			'archive_dataset' => __('Archivado y datasets', 'ai-knowledge'), 'security_scanner' => __('Scanners de seguridad', 'ai-knowledge'),
			'traditional_search' => __('Buscadores tradicionales', 'ai-knowledge'),
		);
		$categories = array();
		foreach ($labels as $category => $title) {
			$descriptions = array();
			foreach (Crawler_Catalog::all() as $crawler) {
				if ($crawler['category'] === $category && '' !== trim((string) $crawler['description'])) $descriptions[] = $crawler['description'];
			}
			$unique = array_values(array_unique($descriptions));
			$categories[$category] = array('title' => $title, 'description' => 1 === count($unique) ? $unique[0] : '');
		}
		return $categories;
	}

	protected static function assistant_available_steps()
	{
		$steps = self::assistant_steps();
		if (!class_exists('WooCommerce')) unset($steps['woocommerce']);
		if (!Chatbot_Prompt::is_genix_ready()) unset($steps['chatbot']);
		if (!self::assistant_ai_available()) unset($steps['faqs']);
		return $steps;
	}

	/**
	 * ¿Hay una conexión de IA real disponible ahora mismo? Es la
	 * comprobación mas fuerte que existe hoy en el plugin (no hay ninguna
	 * prueba de conexión con llamada de red real en todo el codigo): origen
	 * Conectores de WordPress con al menos un modelo de texto configurado,
	 * O origen Support Genix con su modulo cargado. Se usa tanto para
	 * decidir si el paso "faqs" aparece como para decidir, dentro de
	 * assistant_save_step(), si un paso puede generar contenido con IA de
	 * inmediato o solo guardar sus datos con un aviso.
	 */
	protected static function assistant_ai_available()
	{
		if (!empty(AI_Client::available_models())) {
			return true;
		}
		return Chatbot_Prompt::is_genix_ready();
	}

	public static function assistant_navigate()
	{
		if (!current_user_can(self::capability())) wp_send_json_error(array('message' => __('No tienes permisos suficientes.', 'ai-knowledge')), 403);
		check_ajax_referer('aikb_assistant_ajax', 'nonce');
		$steps = self::assistant_available_steps();
		$current = isset($_POST['step']) ? sanitize_key(wp_unslash($_POST['step'])) : 'welcome';
		$action = isset($_POST['assistant_action']) ? sanitize_key(wp_unslash($_POST['assistant_action'])) : 'goto';
		if (!isset($steps[$current])) wp_send_json_error(array('message' => __('Paso no válido.', 'ai-knowledge')), 400);
		$state = get_option('aikb_setup_assistant', array());
		if (in_array($action, array('save', 'continue'), true) && 'welcome' !== $current) {
			self::assistant_save_step($current);
			// Releer por el mismo motivo que en render_assistant(): evitar
			// que el update_option() de mas abajo sobrescriba el
			// 'ai_connection' que assistant_save_step('ai') acaba de guardar.
			$state = get_option('aikb_setup_assistant', $state);
		}
		$state['initiated'] = true;
		$state['last_opened'] = current_time('mysql');
		if (in_array($action, array('save', 'goto'), true)) {
			$state['current'] = $current;
		} elseif ('skip' === $action) $state['skipped'] = array_values(array_unique(array_merge((array) ($state['skipped'] ?? array()), array($current))));
		// El usuario acaba de pasar por el paso de resumen: marcar terminado
		// sin depender de una accion especial (ver mismo cambio en render_assistant()).
		elseif ('finish' === $current) $state['finished'] = true;
		elseif ('back' !== $action) $state['completed'] = array_values(array_unique(array_merge((array) ($state['completed'] ?? array()), array($current))));
		$next = in_array($action, array('save', 'goto'), true) ? $current : self::assistant_next_step($current, $steps, $action);
		$state['current'] = $next;
		update_option('aikb_setup_assistant', $state, false);
		$message = 'save' === $action ? __('Guardado correctamente.', 'ai-knowledge') : '';
		wp_send_json_success(array('step' => $next, 'completed' => (array) ($state['completed'] ?? array()), 'finished' => !empty($state['finished']), 'panel' => self::assistant_panel($next, $steps, $message)));
	}

	/**
	 * Rediseño del asistente (2026-09-24): cada paso que genere contenido
	 * real con IA lo hace aqui mismo, en cuanto se guarda, en vez de todo
	 * junto al final -- SOLO si assistant_ai_available() es true. Si no hay
	 * conexion, el paso se guarda igual (los datos del formulario, que no
	 * dependen de IA) y simplemente no se genera nada: nunca bloquea ni da
	 * error fatal, ver comentarios de cada rama. Mismo patron de captura de
	 * errores que ya usa Admin::sync_store_docs() (transient, nunca deja
	 * pasar un WP_Error/excepcion sin capturar).
	 */
	protected static function assistant_save_step($step)
	{
		$settings = Scope::settings();
		if ('ai' === $step) {
			$source = isset($_POST['ai_key_source']) ? sanitize_key(wp_unslash($_POST['ai_key_source'])) : 'genix'; // phpcs:ignore
			$model = isset($_POST['wp_ai_model']) ? sanitize_text_field(wp_unslash($_POST['wp_ai_model'])) : AI_Client::MODEL_AUTO; // phpcs:ignore
			if (!in_array($source, array('genix', 'wp_connectors'), true) || ('wp_connectors' === $source && !AI_Client::wordpress_available())) $source = 'genix';
			if (AI_Client::MODEL_AUTO !== $model && !isset(AI_Client::available_models()[$model])) $model = AI_Client::MODEL_AUTO;
			Scope::update_settings(array('ai_key_source' => $source, 'wp_ai_model' => $model));
			// Estado de conexion real, guardado para que assistant_screen_content('ai')
			// pueda avisar con claridad sin repetir la comprobacion en cada carga.
			$state = get_option('aikb_setup_assistant', array());
			$state['ai_connection'] = array(
				'checked'   => true,
				'source'    => $source,
				'available' => self::assistant_ai_available(),
			);
			update_option('aikb_setup_assistant', $state, false);
		} elseif ('limits' === $step) {
			// Antes 'finish': mismos campos de siempre (largo de texto, tokens,
			// limite diario, tamaño de lote, IndexNow), solo que ya no es el
			// ultimo paso -- va ANTES de 'content' a proposito, para que
			// Queue::start_seed() (ver abajo) ya respete el limite diario y el
			// tamaño de lote recien guardados.
			Scope::update_settings(array(
				'body_char_limit' => max(100, min(10000, (int) ($_POST['body_char_limit'] ?? $settings['body_char_limit']))), // phpcs:ignore
				'output_tokens' => max(200, (int) ($_POST['output_tokens'] ?? $settings['output_tokens'])), // phpcs:ignore
				'daily_limit' => max(1, (int) ($_POST['daily_limit'] ?? $settings['daily_limit'])), // phpcs:ignore
				'batch_size' => max(1, (int) ($_POST['batch_size'] ?? $settings['batch_size'])), // phpcs:ignore
				'no_limit' => !empty($_POST['no_limit']), // phpcs:ignore
				'indexnow_enabled' => !empty($_POST['indexnow_enabled']), // phpcs:ignore
			));
		} elseif ('content' === $step) {
			$mode = isset($_POST['post_types_mode']) ? sanitize_key(wp_unslash($_POST['post_types_mode'])) : 'explicit'; // phpcs:ignore
			Scope::update_settings(array('post_types_mode' => in_array($mode, array('explicit', 'all_public'), true) ? $mode : 'explicit', 'post_types' => isset($_POST['post_types']) ? array_map('sanitize_key', (array) wp_unslash($_POST['post_types'])) : array())); // phpcs:ignore
			// Encolar (no depende de conexion de IA en si mismo -- si no hay
			// conexion, Queue::run_generate() simplemente fallara al generar
			// cada documento y lo marcara "Error", sin romper nada). Usa el
			// limite diario/tamaño de lote ya guardados en el paso 'limits'.
			Queue::start_seed();
		} elseif ('business' === $step || 'chatbot' === $step) {
			$allowed = array_keys(Chatbot_Prompt_Builder::questions_by_group('business' === $step ? 'negocio' : 'chatbot'));
			$raw = isset($_POST['answers']) && is_array($_POST['answers']) ? wp_unslash($_POST['answers']) : array(); // phpcs:ignore
			Chatbot_Prompt_Builder::save_answers(array_intersect_key($raw, array_flip($allowed)));
			if ('business' === $step) {
				self::save_language_fields_from_post();
				// Ya era inmediato antes de este rediseño: no depende de IA,
				// solo guarda las respuestas en wp-content/llm/info.md.
				Chatbot_Prompt_Builder::write_info_doc();
			}
			if ('chatbot' === $step) {
				Scope::update_settings(array('chatbot_docs_list_limit' => max(0, (int) ($_POST['chatbot_docs_list_limit'] ?? Chatbot_Relevance_Guard::DOCS_LIST_LIMIT_DEFAULT)))); // phpcs:ignore
				// Mismo patron que Admin::sync_chatbot_prompt(): solo si hay
				// conexion real (aqui: si Genix esta listo, que es lo que de
				// verdad hace falta para sincronizar con el).
				if (self::assistant_ai_available()) {
					Chatbot_Prompt::sync(true);
				}
			}
		} elseif ('woocommerce' === $step) {
			Scope::update_settings(array(
				'wc_store_name' => isset($_POST['wc_store_name']) ? sanitize_text_field(wp_unslash($_POST['wc_store_name'])) : '', // phpcs:ignore
				'wc_currency' => isset($_POST['wc_currency']) ? sanitize_text_field(wp_unslash($_POST['wc_currency'])) : '', // phpcs:ignore
				'wc_base_country' => isset($_POST['wc_base_country']) ? sanitize_text_field(wp_unslash($_POST['wc_base_country'])) : '', // phpcs:ignore
				'wc_terms_text' => isset($_POST['wc_terms_text']) ? sanitize_textarea_field(wp_unslash($_POST['wc_terms_text'])) : '', // phpcs:ignore
				'wc_returns_text' => isset($_POST['wc_returns_text']) ? sanitize_textarea_field(wp_unslash($_POST['wc_returns_text'])) : '', // phpcs:ignore
				'delivery_time_note' => isset($_POST['delivery_time_note']) ? sanitize_textarea_field(wp_unslash($_POST['delivery_time_note'])) : '', // phpcs:ignore
				'wc_contact_hours' => isset($_POST['wc_contact_hours']) ? sanitize_textarea_field(wp_unslash($_POST['wc_contact_hours'])) : '', // phpcs:ignore
				'wc_pickup_available' => !empty($_POST['wc_pickup_available']), // phpcs:ignore
			));
			// Mismo patron de manejo de errores que Admin::sync_store_docs():
			// captura errores en un transient, nunca deja pasar un fatal.
			if (self::assistant_ai_available()) {
				$summary = Store_Info_Doc::generate_all();
				if (!empty($summary['errores'])) {
					set_transient('wookb_store_docs_error', implode(' | ', $summary['errores']), MINUTE_IN_SECONDS);
				} else {
					delete_transient('wookb_store_docs_error');
				}
			}
		} elseif ('faqs' === $step) {
			// Paso nuevo. Excepcion explicita, ya confirmada por el usuario, a
			// la norma general de "revisar antes de publicar": aqui se genera
			// Y se publica de una vez. Solo si hay conexion -- si no, no hay
			// nada que hacer en este paso (no tiene campos propios que
			// guardar, es puramente de generacion).
			//
			// Cambio de comportamiento confirmado por el usuario (2026-09-24):
			// antes se generaba un documento por cada idioma ACTIVO de WPML,
			// igual que un producto/pagina con traduccion real. El FAQ no
			// tiene traduccion real detras -- lo redacta el propio plugin, no
			// hay contenido distinto que traducir por idioma. Ahora se genera
			// SOLO en el idioma PRINCIPAL del sitio (Languages::main_language()).
			if (self::assistant_ai_available()) {
				$answers = Chatbot_Prompt_Builder::get_saved_answers();
				$faq_lang = Languages::main_language();
				$current_faq = Llms_Faq::read($faq_lang);
				$draft = Chatbot_Prompt_Builder::generate_faqs($answers, $current_faq, '');
				if (is_wp_error($draft)) {
					set_transient('wookb_assistant_faqs_error_' . $faq_lang, $draft->get_error_message(), MINUTE_IN_SECONDS);
				} else {
					delete_transient('wookb_assistant_faqs_error_' . $faq_lang);
					Llms_Faq::save($draft, $faq_lang);
					Llms_Faq::persist_doc($faq_lang);
				}
			}
		} elseif ('visibility' === $step) {
			$mode = isset($_POST['crawler_visibility_mode']) ? sanitize_key(wp_unslash($_POST['crawler_visibility_mode'])) : 'site'; // phpcs:ignore
			$actions = Crawler_Catalog::effective_actions();
			if (isset($_POST['crawler_action']) && is_array($_POST['crawler_action'])) { // phpcs:ignore
				foreach (wp_unslash($_POST['crawler_action']) as $user_agent => $action) { // phpcs:ignore
					$user_agent = sanitize_text_field($user_agent);
					$action = sanitize_key($action);
					if (isset($actions[$user_agent]) && in_array($action, array('allow', 'block'), true)) $actions[$user_agent] = $action;
				}
			}
			Scope::update_settings(array('crawler_visibility_mode' => in_array($mode, array('site', 'llms_only'), true) ? $mode : 'site', 'crawler_actions' => $actions));
		}
		// 'finish' ya no guarda nada aqui: es el paso de RESUMEN (solo lectura,
		// ver assistant_screen_content('finish')), no un paso de datos.
	}

	public static function download_geo_prompt()
	{
		if (!current_user_can(self::capability())) wp_die(esc_html__('No tienes permisos suficientes.', 'ai-knowledge'));
		check_admin_referer('aikb_download_geo_prompt');
		$content = self::build_geo_prompt();
		nocache_headers();
		header('Content-Type: text/markdown; charset=utf-8');
		header('Content-Disposition: attachment; filename="ai-knowledge-prompt-geo.md"');
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- descarga Markdown plano.
		exit;
	}

	protected static function build_geo_prompt()
	{
		$content = <<<'GEO'
# Auditoría GEO - Visibilidad IA de una web

Analiza la web: [URL]

Objetivo:
Evaluar cómo una inteligencia artificial, agente autónomo o buscador con IA puede descubrir, interpretar y acceder a esta web actualmente.

NO hagas auditoría SEO.
NO analices keywords.
NO analices posicionamiento.
NO analices copywriting.
NO analices diseño visual.
NO valores estrategia comercial.

Analiza únicamente la capa técnica de visibilidad, accesibilidad y comprensión para sistemas IA.

---

## Recursos GEO y archivos técnicos

Comprueba directamente todos los recursos disponibles:

- [URL]robots.txt
- [URL]llms.txt
- [URL]sitemap.xml
- [URL]sitemap_index.xml
- [URL]wp-sitemap.xml
- feeds XML
- feeds JSON
- endpoints públicos
- APIs relacionadas
- archivos Markdown
- documentación para IA
- cualquier archivo específico GEO encontrado

Para cada recurso:

- URL exacta comprobada.
- Código HTTP.
- Si existe o no.
- Si ha podido ser leído completamente.
- Información que aporta a una IA.

IMPORTANTE:
No marques un archivo como "no verificado" sin intentar acceder primero.
Si no puedes leerlo indica exactamente:
- motivo del fallo;
- bloqueo encontrado;
- error HTTP;
- limitación técnica.

No dejes recursos sin intentar comprobar.

---

# Analiza exclusivamente:

## 1. Percepción IA actual

Explica brevemente:

- Qué puede entender una IA de la web.
- Qué nivel de acceso tiene.
- Si existe una capa preparada para agentes IA.
- Si la información está organizada para interpretación automática.

Máximo 50 líneas.

---

## 2. Tabla comparativa técnica

Entrega una tabla:

| Elemento | Estado | Resultado observado | Impacto para IA |
|---|---|---|---|
| robots.txt | | | |
| llms.txt | | | |
| sitemap | | | |
| Markdown IA | | | |
| JSON | | | |
| Feeds | | | |
| APIs | | | |
| Schema.org | | | |
| Product | | | |
| Organization | | | |
| FAQ | | | |
| Otros recursos GEO | | | |

---

## 3. Calidad de archivos GEO

Evalúa únicamente la calidad técnica de cada archivo:

### llms.txt
Analiza:

- existencia;
- estructura;
- claridad para modelos IA;
- enlaces útiles;
- organización;
- actualización;
- relación con otros recursos.

### robots.txt
Analiza:

- acceso permitido/bloqueado para bots IA;
- reglas específicas;
- coherencia con llms.txt.

### JSON / APIs / Feeds
Analiza:

- existencia;
- accesibilidad;
- formato;
- utilidad para agentes IA.

NO evalúes la calidad del texto comercial.
Evalúa únicamente si sirve como fuente interpretable por IA.

---

## 4. Problemas detectados

Lista únicamente problemas confirmados.

Clasificación:

- Confirmado: comprobado directamente.
- Riesgo: posible limitación no confirmada.
- No verificado: no se ha podido comprobar.

No inventes problemas.

---

## 5. Nivel final de visibilidad IA

Clasifica:

BAJO / MEDIO / ALTO

Basado únicamente en:

- accesibilidad;
- archivos GEO;
- estructuras técnicas;
- facilidad de interpretación automática.

No valores contenido, SEO ni marketing.

---

Formato final obligatorio:

1. Resumen humano (máximo 50 líneas).
2. Tabla comparativa técnica.
3. Nivel de visibilidad IA.
4. Problemas confirmados.

No incluyas recomendaciones generales.
No expliques qué se podría hacer.
Entrega únicamente el estado actual de la web.
GEO;
		$content = str_replace('[URL]', home_url('/'), $content);
		return $content;
	}

	protected static function assistant_next_step($current, $steps, $action)
	{
		$keys = array_keys($steps);
		$index = array_search($current, $keys, true);
		if ('back' === $action) {
			return $keys[max(0, $index - 1)];
		}
		return $keys[min(count($keys) - 1, $index + 1)];
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
			// Pedido explicito del usuario: la etiqueta visible pasa a
			// "Genix" (ahora tambien alberga la seccion de articulos
			// exclusivos de Genix, ver tab-prompt.php) -- el slug interno
			// 'prompt' se conserva a proposito por compatibilidad con
			// enlaces/redirects existentes (self::redirect('prompt'),
			// documentation_map()['prompt'], el paso 'chatbot' del asistente
			// que enlaza a '&tab=prompt', etc.): cambiar el slug rompería
			// todos esos enlaces sin necesidad, la etiqueta es lo unico que
			// pidio el usuario.
			$tabs['prompt'] = __('Genix', 'ai-knowledge');
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
		wp_enqueue_script('wookb-admin', AIKB_URL . 'assets/admin.js', array('jquery', 'wp-i18n'), AIKB_VERSION, true);
		wp_set_script_translations('wookb-admin', 'ai-knowledge', AIKB_DIR . 'languages');
		if (false !== strpos($hook, 'ai-knowledge-assistant')) {
			$screen_content = array();
			foreach (array_keys(self::assistant_available_steps()) as $step_key) {
				$screen_content[$step_key] = self::assistant_screen_content($step_key);
			}
			wp_localize_script('wookb-admin', 'aikbAssistant', array(
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'adminPostUrl' => admin_url('admin-post.php'),
				'nonce' => wp_create_nonce('aikb_assistant_ajax'),
				'steps' => self::assistant_available_steps(),
				'screenContent' => $screen_content,
			));
		}
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
		echo '<div class="wookb-header-btn-group"><a class="wookb-header-btn wookb-btn-success" href="' . esc_url(home_url('/llms.txt')) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Ver llms.txt', 'ai-knowledge') . '</a> <button type="button" class="wookb-header-btn wookb-theme-toggle"> ' . esc_html__('Modo oscuro', 'ai-knowledge') . '</button></div></div>';

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
		$file = self::documentation_file($docs[$tab]);
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
			$doc_path = self::documentation_file($doc_file);
			if ($doc_file === $docs[$tab] || ! is_readable($doc_path)) {
				continue;
			}
			echo '<template data-wookb-doc-template="' . esc_attr($doc_file) . '">' . self::markdown_to_html((string) file_get_contents($doc_path)) . '</template>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</aside>';
	}

	/**
	 * Returns a locale-specific documentation file, falling back to Spanish.
	 */
	protected static function documentation_file($filename)
	{
		$filename = basename((string) $filename);
		$locale   = function_exists('determine_locale') ? determine_locale() : get_locale();
		$localized = AIKB_DIR . 'docs/' . sanitize_file_name($locale) . '/' . $filename;
		if (is_readable($localized)) {
			return $localized;
		}
		return AIKB_DIR . 'docs/' . $filename;
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

	/**
	 * Interruptor "Incluir llms.txt para los modelos desactivados" (valor
	 * guardado: crawler_visibility_mode, 'llms_only' = activado, 'site' =
	 * desactivado). Compartido por la pestaña Visibilidad IA y el paso del
	 * asistente: mismo marcado, mismo interruptor (.wookb-assistant-toggle).
	 *
	 * El input hidden 'site' va ANTES del checkbox con el mismo name: si el
	 * checkbox esta marcado, PHP se queda con el ultimo valor ('llms_only');
	 * si no, llega 'site' (un checkbox sin marcar no envia nada). Sirve tanto
	 * para el POST normal como para el serializeArray() del asistente.
	 * No guarda por si mismo: en la pestaña se guarda con "Guardar
	 * configuración de crawlers" y en el asistente con los botones de siempre.
	 */
	public static function render_visibility_switch($mode)
	{
		$on = 'llms_only' === $mode;
		ob_start();
		?>
		<div class="wookb-visibility-switch<?php echo $on ? ' is-on' : ''; ?>">
			<input type="hidden" name="crawler_visibility_mode" value="site" />
			<div class="wookb-visibility-head">
				<label class="wookb-assistant-toggle">
					<input type="checkbox" name="crawler_visibility_mode" value="llms_only" <?php checked($on); ?> />
					<span><?php esc_html_e('Incluir llms.txt para los modelos desactivados', 'ai-knowledge'); ?></span>
				</label>
				<strong class="wookb-visibility-state">
					<span class="on"><?php esc_html_e('ACTIVADO', 'ai-knowledge'); ?></span>
					<span class="off"><?php esc_html_e('DESACTIVADO', 'ai-knowledge'); ?></span>
				</strong>
			</div>
			<p class="description"><?php esc_html_e('Modelos desactivados = los bots que has marcado como Bloquear en la tabla de crawlers.', 'ai-knowledge'); ?></p>
			<ul class="wookb-visibility-effects">
				<li><strong><?php esc_html_e('Activado:', 'ai-knowledge'); ?></strong> <?php esc_html_e('los bots bloqueados no pueden entrar en tu web, pero sí leen /llms.txt, el resumen que has preparado para ellos. Cualquier otra página les responde 404.', 'ai-knowledge'); ?></li>
				<li><strong><?php esc_html_e('Desactivado:', 'ai-knowledge'); ?></strong> <?php esc_html_e('los bots bloqueados no pueden acceder a nada, ni siquiera a /llms.txt. Es un bloqueo total del sitio.', 'ai-knowledge'); ?></li>
			</ul>
			<p class="description"><?php esc_html_e('Este ajuste solo cambia las reglas propuestas para robots.txt y .htaccess. Nada se modifica en tu servidor hasta que tú lo apliques.', 'ai-knowledge'); ?></p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Resumen de estado (Negocio/WooCommerce/FAQ/Chatbot/documentos), extraido
	 * del paso "finish" del asistente para reutilizarlo tambien en
	 * admin/views/tab-carga-inicial.php -- SOLO consultas de lectura, ya
	 * baratas (Registry, Llms_Faq, get_option...), nunca dispara generacion.
	 */
	public static function render_assistant_summary()
	{
		$business_answers = Chatbot_Prompt_Builder::get_saved_answers();
		$business_has_data = false;
		foreach (Chatbot_Prompt_Builder::questions_by_group('negocio') as $business_key => $business_question) {
			if (!empty($business_answers[$business_key])) {
				$business_has_data = true;
				break;
			}
		}
		// Cambio de comportamiento (2026-09-24): WooCommerce y FAQ ya solo se
		// generan en el idioma principal (Store_Info_Doc::generate_all(),
		// Llms_Faq vía assistant_save_step('faqs')), no por cada idioma
		// activo -- un solo bloque cada uno, sin foreach por idioma.
		$default_lang = Languages::main_language();
		$chatbot_synced = '' !== Chatbot_Prompt::read() && Chatbot_Prompt::is_genix_ready();
		$doc_summary = Registry::summary();
		ob_start();
		?>
		<div class="wookb-assistant-summary">
			<p><strong><?php esc_html_e('Negocio:', 'ai-knowledge'); ?></strong> <?php echo $business_has_data ? esc_html__('Datos guardados.', 'ai-knowledge') : esc_html__('Todavía sin datos.', 'ai-knowledge'); ?></p>
			<?php if (class_exists('WooCommerce')) :
				$store_info_row = Registry::find(Store_Info_Doc::SOURCE_ID_STORE_INFO, $default_lang);
				$catalog_row = Registry::find(Store_Info_Doc::SOURCE_ID_SHOP_CATALOG, $default_lang);
				$wc_synced = ($store_info_row && $store_info_row->md_path) || ($catalog_row && $catalog_row->md_path);
				?>
				<p><strong><?php esc_html_e('WooCommerce:', 'ai-knowledge'); ?></strong> <?php echo $wc_synced ? esc_html__('Documentos generados.', 'ai-knowledge') : esc_html__('Todavía sin generar.', 'ai-knowledge'); ?></p>
			<?php endif; ?>
			<p><strong><?php esc_html_e('FAQ:', 'ai-knowledge'); ?></strong> <?php echo '' !== Llms_Faq::read($default_lang) ? esc_html__('Publicado.', 'ai-knowledge') : esc_html__('Todavía sin generar.', 'ai-knowledge'); ?></p>
			<?php if (Chatbot_Prompt::is_genix_ready()) : ?>
				<p><strong><?php esc_html_e('Chatbot:', 'ai-knowledge'); ?></strong> <?php echo $chatbot_synced ? esc_html__('Sincronizado con Genix.', 'ai-knowledge') : esc_html__('Todavía sin sincronizar.', 'ai-knowledge'); ?></p>
			<?php endif; ?>
			<p><strong><?php esc_html_e('Documentos registrados:', 'ai-knowledge'); ?></strong> <?php echo (int) $doc_summary['total']; ?></p>
			<p><strong><?php esc_html_e('Documentos pendientes:', 'ai-knowledge'); ?></strong> <?php echo (int) Registry::count(array('status' => 'queued')); ?></p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Bloque de botones "Generar pendientes"/"Reiniciar todo" (o "Cancelar
	 * generación" si ya hay una en curso), extraido de
	 * admin/views/tab-carga-inicial.php para reutilizarlo tambien en el
	 * paso "finish" (resumen) del asistente de configuracion -- misma logica
	 * de negocio (los handlers de admin-post.php no se duplican, solo el
	 * marcado), pedido explicito del usuario: "déjalo con la misma lógica
	 * como en la tab normal" en los dos sitios.
	 */
	/**
	 * Campo estructurado «Idioma principal» + «Idiomas de la web» (Negocio y
	 * paso «Negocio» del asistente). Solo nombres de idioma, nunca códigos en
	 * pantalla. Variante 'table' (filas de form-table) o 'assistant'.
	 */
	public static function render_language_fields($variant = 'table')
	{
		$options  = Languages::selectable_languages();
		$main     = Languages::main_language();
		$selected = Languages::codes();
		ob_start();

		$select = '<select id="wookb-main-language" name="main_language">';
		foreach ($options as $code => $name) {
			$select .= '<option value="' . esc_attr($code) . '"' . selected($main, $code, false) . '>' . esc_html($name) . '</option>';
		}
		$select .= '</select>';

		$checks = '<fieldset class="wookb-chip-group">';
		foreach ($options as $code => $name) {
			$checks .= '<label class="wookb-chip"><input type="checkbox" name="site_languages[]" value="' . esc_attr($code) . '"' . checked(in_array($code, $selected, true), true, false) . ' /> ' . esc_html($name) . '</label>';
		}
		$checks .= '</fieldset>';

		$main_help  = __('Es el idioma de los documentos que genera el plugin (llms.txt, FAQ, tienda, Negocio y el .md de cada contenido). Si hay un plugin de idiomas se detecta solo.', 'ai-knowledge');
		$langs_help = __('Idiomas en los que está disponible la web. Si hay un plugin de idiomas se detectan solos. Se usan para el prompt del chatbot, llms.txt y el resumen.', 'ai-knowledge');

		if ('assistant' === $variant) {
			echo '<label class="wookb-assistant-field"><span>' . esc_html__('Idioma principal', 'ai-knowledge') . '</span>' . $select . '<small>' . esc_html($main_help) . '</small></label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado arriba.
			echo '<div class="wookb-assistant-field"><span>' . esc_html__('Idiomas de la web', 'ai-knowledge') . '</span>' . $checks . '<small>' . esc_html($langs_help) . '</small></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado arriba.
		} else {
			echo '<tr><th><label for="wookb-main-language">' . esc_html__('Idioma principal', 'ai-knowledge') . '</label></th><td>' . $select . '<p class="description">' . esc_html($main_help) . '</p></td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado arriba.
			echo '<tr><th>' . esc_html__('Idiomas de la web', 'ai-knowledge') . '</th><td>' . $checks . '<p class="description">' . esc_html($langs_help) . '</p></td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado arriba.
		}
		return ob_get_clean();
	}

	/**
	 * Aviso tras cambiar el check «Crear por idioma» o el idioma principal
	 * (Languages::regen_pending()), con el botón «Reiniciar todo» al lado. Se
	 * pinta en Negocio y en Carga inicial, dentro de un contenedor que el JS
	 * rellena tras guardar el check por AJAX. Desaparece al lanzar «Reiniciar
	 * todo» (Queue::start_seed()). Devuelve el HTML (vacío si no hay aviso).
	 */
	public static function language_regen_notice_html()
	{
		if (! Languages::regen_pending()) {
			return '';
		}
		ob_start();
		?>
		<div class="notice notice-warning inline">
			<p><?php esc_html_e('Has cambiado la configuración de idiomas. Para que los documentos ya generados se ajusten, pulsa «Reiniciar todo»: se borran los que ya no corresponden y se regenera el resto.', 'ai-knowledge'); ?></p>
			<?php echo self::render_force_seed_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado y escapado dentro del propio metodo. ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function render_language_regen_notice()
	{
		echo '<div data-wookb-regen-slot>' . self::language_regen_notice_html() . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado y escapado dentro del propio metodo.
	}

	/**
	 * Formulario del botón «Reiniciar todo». La confirmación indica cuántos
	 * documentos se borran (Languages::obsolete_count()): además de regenerar,
	 * «Reiniciar todo» borra los documentos de tipos fuera del alcance, los
	 * puente y las traducciones que ya no tienen documento propio.
	 */
	protected static function render_force_seed_form()
	{
		$count   = Languages::obsolete_count();
		$message = sprintf(
			/* translators: %d: número de documentos que se borrarán */
			_n(
				'Esto va a REGENERAR también el contenido que ya está sincronizado y va a BORRAR %d documento que ya no corresponde (tipo de contenido fuera del alcance, documento puente o traducción sin «Crear por idioma»). No toca el FAQ, la tienda, Negocio, los artículos de Genix ni los documentos en modo manual. Gasta IA de más y no se puede deshacer. ¿Seguro que quieres continuar?',
				'Esto va a REGENERAR también el contenido que ya está sincronizado y va a BORRAR %d documentos que ya no corresponden (tipos de contenido fuera del alcance, documentos puente o traducciones sin «Crear por idioma»). No toca el FAQ, la tienda, Negocio, los artículos de Genix ni los documentos en modo manual. Gasta IA de más y no se puede deshacer. ¿Seguro que quieres continuar?',
				$count,
				'ai-knowledge'
			),
			$count
		);
		ob_start();
		?>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="wookb-toolbar-form" onsubmit="return confirm('<?php echo esc_js($message); ?>');">
			<input type="hidden" name="action" value="wookb_start_seed_force" />
			<?php wp_nonce_field('wookb_start_seed_force'); ?>
			<?php submit_button(__('Reiniciar todo', 'ai-knowledge'), 'secondary', 'submit', false); ?>
		</form>
		<?php
		return ob_get_clean();
	}

	public static function render_seed_controls()
	{
		$running = (bool) get_option('wookb_seed_running');
		ob_start();
		if ($running) {
			?>
			<p><strong><?php esc_html_e('Generación en curso (procesando por lotes vía Action Scheduler).', 'ai-knowledge'); ?></strong></p>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="wookb_cancel_seed" />
				<?php wp_nonce_field('wookb_cancel_seed'); ?>
				<?php submit_button(__('Cancelar generación', 'ai-knowledge'), 'delete'); ?>
			</form>
			<?php
		} else {
			?>
			<p class="description"><?php esc_html_e('"Generar pendientes" es seguro repetirlo: no regenera lo que ya está sincronizado y sin cambios, solo lo nuevo o lo que falló.', 'ai-knowledge'); ?></p>
			<div class="wookb-toolbar-row">
				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="wookb-toolbar-form">
					<input type="hidden" name="action" value="wookb_start_seed" />
					<?php wp_nonce_field('wookb_start_seed'); ?>
					<?php submit_button(__('Generar pendientes', 'ai-knowledge'), 'primary', 'submit', false); ?>
				</form>
				<?php echo self::render_force_seed_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML generado y escapado dentro del propio metodo. ?>
			</div>
			<?php
		}
		return ob_get_clean();
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
			// Pieza 4: Document_Pipeline::process() no comprueba por si mismo
			// el post_status (a diferencia de Queue::run_generate(), que si lo
			// hace) -- confirmado por lectura de class-document-pipeline.php.
			// Sin este freno, "Generar" regeneraba un documento aunque su
			// origen ya no estuviera publicado.
			$source_post = get_post($row->source_id);
			if (!$source_post || 'publish' !== $source_post->post_status) {
				if (wp_doing_ajax()) {
					wp_send_json_error(array('message' => __('El origen de este documento ya no está publicado.', 'ai-knowledge')));
				}
				wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=registro&wookb_regen_error=' . rawurlencode(__('El origen de este documento ya no está publicado.', 'ai-knowledge'))));
				exit;
			}
			// force=true: el usuario pulso "Generar" explicitamente pidiendo una
			// regeneracion; sin esto, Document_Pipeline::process() se saltaba todo
			// en silencio si el producto de origen no habia cambiado desde la
			// ultima vez (bug real: el boton no hacia nada con ningun limite).
			$result = Document_Pipeline::process($row->source_id, $row->lang, $char_limit > 0 ? $char_limit : null, true);
			if (is_wp_error($result)) {
				if (wp_doing_ajax()) {
					wp_send_json_error(array('message' => $result->get_error_message()));
				}
				wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=registro&wookb_regen_error=' . rawurlencode($result->get_error_message())));
				exit;
			}
		}

		if (wp_doing_ajax()) {
			wp_send_json_success(self::registry_row_ajax_data($id, __('Generado.', 'ai-knowledge')));
		}

		self::redirect('registro');
	}

	/**
	 * Datos de una fila del Registro para actualizar en pantalla sin
	 * recargar (botones "Generar"/"Guardar límite"/"Guardar cambios", todos
	 * por AJAX con el mismo whitelist generico de admin.js). Extraido de
	 * regenerate_single() para no duplicarlo en set_manual()/
	 * set_char_limit(): las tres acciones dejan la fila en un estado que hay
	 * que reflejar igual (estado, fecha, enlaces, contenido si no es manual).
	 */
	protected static function registry_row_ajax_data($id, $message)
	{
		$fresh = Registry::find_by_id($id);
		$data  = array(
			'message' => $message,
			'tab'     => 'registro',
		);
		if ($fresh) {
			require_once AIKB_DIR . 'admin/class-registry-table.php';
			$is_manual   = 'manual' === $fresh->override_mode;
			$data['row'] = array(
				'status'    => Registry_Table::status_label($fresh->status),
				'updated'   => $fresh->updated_at,
				'links'     => Registry_Table::links_html($fresh),
				'is_manual' => $is_manual,
				'content'   => $is_manual ? null : ($fresh->md_path ? Markdown_Store::body_only(Markdown_Store::read($fresh->md_path)) : ''),
			);
		}
		return $data;
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

		// Pieza 4: mismo freno que regenerate_single(), Document_Pipeline::
		// process() no comprueba post_status por si mismo.
		if ('publish' !== get_post_status($post_id)) {
			wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=registro&wookb_regen_error=' . rawurlencode(__('Ese contenido no está publicado.', 'ai-knowledge'))));
			exit;
		}

		if (! Scope::is_included($post_id)) {
			wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=registro&wookb_regen_error=' . rawurlencode(__('Ese contenido no está dentro del alcance configurado del plugin (pestaña Alcance/Exclusiones).', 'ai-knowledge'))));
			exit;
		}

		$errors = array();
		// Documentos de este contenido segun el servicio de idiomas (uno por
		// defecto; con "Crear por idioma", uno por traduccion existente).
		foreach (Languages::targets($post_id) as $target) {
			$result = Document_Pipeline::process($target['id'], $target['lang'], null, true);
			if (is_wp_error($result)) {
				$errors[] = $target['lang'] . ': ' . $result->get_error_message();
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
			// Pieza 4: mismo freno que regenerate_single()/force_generate().
			if ('publish' !== get_post_status($row->source_id)) {
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
		self::save_language_fields_from_post();
		Chatbot_Prompt_Builder::write_info_doc();

		self::redirect('negocio');
	}

	/**
	 * Guarda el idioma principal y los idiomas de la web (campo estructurado
	 * de Negocio y del paso «Negocio» del asistente) si vienen en el envío.
	 * Los códigos se sanean y se validan contra la lista de idiomas
	 * seleccionables (Languages::save_language_fields()). Cambiar el idioma
	 * principal deja pendiente el aviso «Reiniciar todo».
	 */
	protected static function save_language_fields_from_post()
	{
		if (! isset($_POST['main_language'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce ya verificado por el llamador.
			return;
		}
		$main  = sanitize_key(wp_unslash($_POST['main_language'])); // phpcs:ignore
		$codes = isset($_POST['site_languages']) && is_array($_POST['site_languages']) ? array_map('sanitize_key', wp_unslash($_POST['site_languages'])) : array(); // phpcs:ignore
		Languages::save_language_fields($main, $codes);
	}

	/**
	 * Guarda SOLO el check «Crear por idioma» (formulario propio de Negocio,
	 * por AJAX). Solo si el plugin de idiomas crea un post por idioma. Si el
	 * cambio deja documentos por ajustar, responde con el aviso ya montado
	 * (con el botón «Reiniciar todo») para pintarlo ahí mismo.
	 */
	public static function save_per_language()
	{
		self::verify('wookb_save_per_language');

		if (Languages::creates_post_per_language()) {
			Languages::save_per_language(! empty($_POST['per_language'])); // phpcs:ignore
			Chatbot_Prompt_Builder::write_info_doc();
		}

		if (wp_doing_ajax()) {
			wp_send_json_success(
				array(
					'message'     => __('Guardado.', 'ai-knowledge'),
					'notice_html' => self::language_regen_notice_html(),
				)
			);
		}
		wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=negocio&wookb_notice=1#wookb-per-language'));
		exit;
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
	 * Idioma de trabajo de la pestaña FAQs: siempre el idioma principal (ver
	 * Languages::main_language()). Mismo criterio en los 2 handlers de FAQs,
	 * para que generar y guardar operen siempre sobre el mismo idioma.
	 */
	protected static function faqs_lang()
	{
		// La FAQ es contenido del propio plugin: solo se genera en el idioma principal.
		return Languages::main_language();
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

		if (wp_doing_ajax()) {
			wp_send_json_success(self::registry_row_ajax_data($id, __('Guardado.', 'ai-knowledge')));
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
		$product_url = $post ? Languages::permalink($row->source_id) : '';

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

		if (wp_doing_ajax()) {
			wp_send_json_success(self::registry_row_ajax_data($id, __('Guardado.', 'ai-knowledge')));
		}

		self::redirect('registro');
	}

	/**
	 * Pieza 2: guarda el prompt propio de un documento concreto (columna
	 * custom_prompt). Vacio = usa el prompt generico de siempre (ver
	 * Generator::build_prompt()). No regenera nada por si solo: el nuevo
	 * prompt se aplica en la siguiente generacion (boton "Generar" o cola).
	 * Mismo patron que set_char_limit(): upsert sobre source_id+lang.
	 */
	public static function save_custom_prompt()
	{
		self::verify('wookb_save_custom_prompt');

		$id            = isset($_POST['row_id']) ? (int) $_POST['row_id'] : 0; // phpcs:ignore
		$custom_prompt = isset($_POST['custom_prompt']) ? sanitize_textarea_field(wp_unslash($_POST['custom_prompt'])) : ''; // phpcs:ignore

		$row = Registry::find_by_id($id);
		if ($row) {
			Registry::upsert(
				array(
					'source_id'     => $row->source_id,
					'lang'          => $row->lang,
					'custom_prompt' => $custom_prompt,
				)
			);
		}

		self::redirect('registro');
	}

	/**
	 * Pieza 5 (corregida): botón "Generar contenido" de un artículo
	 * exclusivo de Genix -- copia/actualiza el .md a partir del contenido
	 * actual del artículo en Genix (sin IA, ver Genix_Markdown) y lo publica
	 * (fila en Registry). Genix_Publish::publish() rechaza por si sola los
	 * artículos marcados 'only_for_chatbot' (no son públicos ni dentro de
	 * Genix), aunque Genix_Reader ya los excluye del listado antes de
	 * llegar aquí -- freno redundante a propósito.
	 */
	public static function genix_generate()
	{
		self::verify('wookb_genix_generate');

		$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0; // phpcs:ignore

		$result = Genix_Publish::publish($post_id);

		if (is_wp_error($result)) {
			if (wp_doing_ajax()) {
				wp_send_json_error(array('message' => $result->get_error_message()));
			}
			wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=ajustes&wookb_regen_error=' . rawurlencode($result->get_error_message())));
			exit;
		}

		self::redirect('ajustes');
	}

	/**
	 * Pieza 5 (corregida): botón "Quitar" -- despublica un artículo
	 * exclusivo de Genix ya publicado (borra .md y la fila del Registro,
	 * ver Genix_Publish::unpublish()).
	 */
	public static function genix_remove()
	{
		self::verify('wookb_genix_remove');

		$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0; // phpcs:ignore

		$result = Genix_Publish::unpublish($post_id);

		if (is_wp_error($result)) {
			if (wp_doing_ajax()) {
				wp_send_json_error(array('message' => $result->get_error_message()));
			}
			wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=ajustes&wookb_regen_error=' . rawurlencode($result->get_error_message())));
			exit;
		}

		self::redirect('ajustes');
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
				if (wp_doing_ajax()) {
					wp_send_json_error(array('message' => $result->get_error_message()));
				}
				wp_safe_redirect(admin_url('admin.php?page=ai-knowledge&tab=registro&wookb_regen_error=' . rawurlencode($result->get_error_message())));
				exit;
			}
		}

		if (wp_doing_ajax()) {
			wp_send_json_success(self::registry_row_ajax_data($id, __('Guardado.', 'ai-knowledge')));
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

		$settings = array('crawler_actions' => $actions);

		// Interruptor "Incluir llms.txt para los modelos desactivados": va en el
		// mismo formulario y se guarda junto a las acciones de cada bot. Solo se
		// toca si viene en el POST, para no reiniciarlo a 'site' si faltara.
		if (isset($_POST['crawler_visibility_mode'])) { // phpcs:ignore
			$mode = sanitize_key(wp_unslash($_POST['crawler_visibility_mode'])); // phpcs:ignore
			$settings['crawler_visibility_mode'] = in_array($mode, array('site', 'llms_only'), true) ? $mode : 'site';
		}

		Scope::update_settings($settings);

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

		$actions = Crawler_Catalog::effective_actions();
		$mode = Scope::settings()['crawler_visibility_mode'];
		$written = Robots_Txt_Guard::apply_actions($actions, $mode);
		$path = Robots_Txt_Guard::path();
		$verified = $written && file_exists($path) && Robots_Txt_Guard::managed_block_matches((string) file_get_contents($path), $actions, $mode); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if (!$verified) {
			wp_die(esc_html__('No se pudo verificar la actualización de robots.txt. Comprueba los permisos de escritura de la raíz del sitio y vuelve a intentarlo.', 'ai-knowledge'));
		}
		// Uso unico: la confirmacion de descarga solo vale para esta aplicacion.
		Robots_Txt_Guard::clear_backup_confirmation();

		if ( ! empty( $_POST['wookb_return_assistant'] ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=ai-knowledge-assistant&step=server&wookb_notice=1' ) );
			exit;
		}
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

		if (empty($_POST['wookb_htaccess_ack'])) {
			wp_die(esc_html__('Marca la casilla de responsabilidad antes de reemplazar el .htaccess.', 'ai-knowledge'));
		}

		if (! Htaccess_Guard::is_available()) {
			wp_die(esc_html__('No se puede modificar el .htaccess: no existe o el servidor no permite escribirlo. No se ha cambiado nada; usa el archivo preparado a mano.', 'ai-knowledge'));
		}

		$actions = Crawler_Catalog::effective_actions();
		$mode = Scope::settings()['crawler_visibility_mode'];
		$written = Htaccess_Guard::apply_actions($actions, $mode);
		$verified = $written && Htaccess_Guard::managed_block_matches((string) file_get_contents(Htaccess_Guard::path()), $actions, $mode); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if (! $verified) {
			wp_die(esc_html__('No se pudo verificar la actualización del .htaccess. Comprueba los permisos y restaura tu copia si hace falta.', 'ai-knowledge'));
		}
		// Uso unico: la confirmacion de descarga solo vale para esta aplicacion.
		Htaccess_Guard::clear_backup_confirmation();

		if (! empty($_POST['wookb_return_assistant'])) {
			wp_safe_redirect(admin_url('admin.php?page=ai-knowledge-assistant&step=server&wookb_notice=1'));
			exit;
		}
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
