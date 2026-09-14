<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orquestador: registra todos los hooks del núcleo, admin y guardias de dependencias.
 */
class Plugin {

	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		require_once WOOKB_DIR . 'includes/class-scope.php';
		require_once WOOKB_DIR . 'includes/class-registry.php';
		require_once WOOKB_DIR . 'includes/class-wpml.php';
		require_once WOOKB_DIR . 'includes/extractors/class-extractor-base.php';
		require_once WOOKB_DIR . 'includes/extractors/class-extractor-woo.php';
		require_once WOOKB_DIR . 'includes/class-generator.php';
		require_once WOOKB_DIR . 'includes/class-markdown-store.php';
		require_once WOOKB_DIR . 'includes/class-genix-bridge.php';
		require_once WOOKB_DIR . 'includes/class-document-pipeline.php';
		require_once WOOKB_DIR . 'includes/class-store-info-doc.php';
		require_once WOOKB_DIR . 'includes/class-llms-faq.php';
		require_once WOOKB_DIR . 'includes/class-queue.php';
		require_once WOOKB_DIR . 'includes/class-llms-txt.php';
		require_once WOOKB_DIR . 'includes/class-rest-guard.php';
		require_once WOOKB_DIR . 'includes/class-sync.php';
		require_once WOOKB_DIR . 'includes/class-doc-redirect.php';
		require_once WOOKB_DIR . 'includes/class-markdown-discovery.php';
		require_once WOOKB_DIR . 'includes/class-chatbot-language-fix.php';
		require_once WOOKB_DIR . 'includes/class-chatbot-prompt.php';
		require_once WOOKB_DIR . 'includes/class-chatbot-prompt-builder.php';
		require_once WOOKB_DIR . 'includes/class-chatbot-relevance-guard.php';
		require_once WOOKB_DIR . 'includes/class-genix-hooks-guard.php';

		Queue::init();
		Sync::init();
		Doc_Redirect::init();
		Markdown_Discovery::init();
		Chatbot_Language_Fix::init();
		Chatbot_Relevance_Guard::init();
		Genix_Hooks_Guard::init();
		add_action( 'admin_init', array( '\WOOKB\Chatbot_Prompt', 'maybe_auto_sync' ), 999 );
		add_action( 'admin_notices', array( '\WOOKB\Chatbot_Prompt_Builder', 'maybe_short_summary_notice' ) );

		// sgkb-docs (y otros CPTs de plugins de terceros) se registran en 'init', no antes:
		// el guard REST debe engancharse después de que el CPT exista.
		add_action( 'init', array( '\WOOKB\Rest_Guard', 'init' ), 20 );

		add_action( 'init', array( __CLASS__, 'register_rewrite' ) );
		add_filter( 'query_vars', array( '\WOOKB\Llms_Txt', 'register_query_var' ) );
		add_action( 'parse_request', array( '\WOOKB\Llms_Txt', 'maybe_serve' ) );

		if ( is_admin() ) {
			require_once WOOKB_DIR . 'admin/class-admin.php';
			Admin::init();

			require_once WOOKB_DIR . 'includes/class-editor-metabox.php';
			Editor_Metabox::init();
		}
	}

	public static function register_rewrite() {
		Llms_Txt::add_rewrite_rule();
	}
}
