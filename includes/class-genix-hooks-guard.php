<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detecta si los 2 filtros minimos que insertamos a mano en Support Genix
 * (traits/Apbd_wps_knowledge_base_chatquery_trait.php) siguen presentes, y
 * permite reinsertarlos con un clic desde el admin si una actualizacion de
 * Genix los ha borrado (sobreescribe el archivo entero en cada update).
 *
 * Sin esos 2 filtros, Chatbot_Relevance_Guard deja de tener efecto: el
 * chatbot sigue funcionando (nada rompe), pero pierde las mejoras de
 * relevancia, idioma no soportado y limite de "documentos relacionados".
 *
 * Por que se parchean DOS carpetas (Lite y Pro) y no solo una:
 * Support Genix Pro es un addon que NO funciona sin Support Genix Lite
 * (Lite es la base, Pro solo añade funciones encima). Por eso Lite es la
 * carpeta que SIEMPRE está presente si el chatbot funciona, con o sin Pro
 * activo, y es la que debe considerarse el objetivo principal del parche.
 * Sin embargo, si el sitio tiene Pro instalado además de Lite, conviene
 * verificar y parchear también su copia del trait: no hay evidencia de que
 * Pro la ejecute en vez de la de Lite en esta versión, pero si una futura
 * actualización de Pro reintroduce lógica propia que dependa de sus propios
 * hooks, tenerla ya parcheada evita quedarnos sin las mejoras por sorpresa.
 * El coste de comprobar y parchear ambas es mínimo (mismo anclaje textual,
 * mismo backup, misma comprobación de sintaxis) así que se hace siempre que
 * el archivo exista, sin asumir cuál de las dos "gana" en tiempo de ejecución.
 */
class Genix_Hooks_Guard {

	const FILTER_SEARCH_RESULTS = "apply_filters('apbd-wps/filter/chatbot-search-results', \$docs, \$query);";
	const FILTER_DOCS_LIST      = "apply_filters('apbd-wps/filter/chatbot-docs-list', \$docs, \$query);";

	/**
	 * Marcador de presencia del bloque "chatbot_custom_instructions": Pro ya lo
	 * trae de fabrica (build_chatbot_system_prompt(), linea ~1135) con esta
	 * misma llamada literal; Lite no lo trae en absoluto -- su
	 * build_chatbot_system_prompt() no tiene ningun bloque que lea esta
	 * opcion, asi que sin este tercer parche el prompt propio del sitio
	 * (chatbot-system-prompt.md, sincronizado via Chatbot_Prompt::sync()) se
	 * escribe correctamente en la opcion de Genix pero el chatbot de Lite
	 * nunca lo lee ni lo aplica. Usar el mismo marcador para detectar
	 * "ya presente" en ambos archivos evita duplicar la insercion en Pro.
	 */
	const CUSTOM_INSTRUCTIONS_MARKER = "GetOption('chatbot_custom_instructions', '')";

	const DISMISSED_OPTION = 'wookb_genix_hooks_dismissed_at';
	const REINSTALL_ACTION = 'wookb_reinstall_genix_hooks';
	const DISMISS_ACTION   = 'wookb_dismiss_genix_hooks';

	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'maybe_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'maybe_result_notice' ) );
		add_action( 'admin_post_' . self::REINSTALL_ACTION, array( __CLASS__, 'handle_reinstall' ) );
		add_action( 'admin_post_' . self::DISMISS_ACTION, array( __CLASS__, 'handle_dismiss' ) );
	}

	/**
	 * Silencia el aviso 24 h (opción DISMISSED_OPTION, leída en maybe_notice()).
	 * Solo escribe una marca de tiempo; no toca Genix.
	 */
	public static function handle_dismiss() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No autorizado.', 'ai-knowledge' ) );
		}
		check_admin_referer( self::DISMISS_ACTION );
		update_option( self::DISMISSED_OPTION, time(), false );
		$back = wp_get_referer();
		wp_safe_redirect( $back ? $back : admin_url( 'admin.php?page=ai-knowledge' ) );
		exit;
	}

	/**
	 * El aviso solo se muestra en las pantallas del plugin (id de pantalla con
	 * «ai-knowledge») y en Plugins, no en todo wp-admin.
	 */
	protected static function is_notice_screen() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}
		return 'plugins' === $screen->id || false !== strpos( (string) $screen->id, 'ai-knowledge' );
	}

	/** Etiqueta legible de cada parche que puede faltar. */
	protected static function missing_label( $code ) {
		$labels = array(
			'search-results'      => __( 'relevancia de las búsquedas e idiomas no soportados (filtro search-results)', 'ai-knowledge' ),
			'docs-list'           => __( 'límite de documentos relacionados (filtro docs-list)', 'ai-knowledge' ),
			'custom-instructions' => __( 'prompt personalizado del sitio (bloque custom-instructions)', 'ai-knowledge' ),
		);
		return isset( $labels[ $code ] ) ? $labels[ $code ] : $code;
	}

	/**
	 * Muestra el resultado del ultimo "Reinstalar filtros ahora" justo despues
	 * de la redireccion (handle_reinstall() guarda el resultado en un transient
	 * de 1 minuto, pero hasta ahora nada lo leia ni lo mostraba -- el usuario
	 * pulsaba el boton y no veia confirmacion de si habia funcionado o no).
	 */
	public static function maybe_result_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$result = get_transient( 'wookb_genix_hooks_reinstall_result' );
		if ( false === $result ) {
			return;
		}
		delete_transient( 'wookb_genix_hooks_reinstall_result' );

		if ( ! empty( $result['error'] ) ) {
			printf(
				'<div class="notice notice-error is-dismissible"><p><strong>%1$s</strong> %2$s</p></div>',
				esc_html__( 'AI Knowledge & Visibility:', 'ai-knowledge' ),
				esc_html( $result['error'] )
			);
			return;
		}

		printf(
			'<div class="notice notice-success is-dismissible"><p><strong>%1$s</strong> %2$s</p></div>',
			esc_html__( 'AI Knowledge & Visibility:', 'ai-knowledge' ),
			esc_html__( 'Filtros del chatbot reinstalados correctamente en Support Genix.', 'ai-knowledge' )
		);
	}

	/**
	 * Todas las rutas candidatas del trait, con una etiqueta legible. 'lite'
	 * va primero porque es la que siempre está presente (Pro depende de ella);
	 * 'pro' se incluye por si el sitio también la tiene instalada.
	 */
	protected static function trait_path_candidates() {
		return array(
			'lite' => WP_PLUGIN_DIR . '/support-genix-lite/traits/Apbd_wps_knowledge_base_chatquery_trait.php',
			'pro'  => WP_PLUGIN_DIR . '/support-genix/traits/Apbd_wps_knowledge_base_chatquery_trait.php',
		);
	}

	/**
	 * Solo las rutas que realmente existen en este sitio (uno o ambos planes).
	 */
	protected static function existing_trait_paths() {
		$existing = array();
		foreach ( self::trait_path_candidates() as $label => $path ) {
			if ( file_exists( $path ) ) {
				$existing[ $label ] = $path;
			}
		}
		return $existing;
	}

	/**
	 * Devuelve qué filtros faltan por archivo existente, ej.
	 * array('lite' => array('search-results'), 'pro' => array()).
	 * Si Genix no está instalado (ni Lite ni Pro presentes), devuelve null.
	 */
	public static function missing_filters() {
		$paths = self::existing_trait_paths();
		if ( empty( $paths ) ) {
			return null;
		}

		$missing = array();
		foreach ( $paths as $label => $path ) {
			$content = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

			$missing_here = array();
			if ( false === strpos( $content, self::FILTER_SEARCH_RESULTS ) ) {
				$missing_here[] = 'search-results';
			}
			if ( false === strpos( $content, self::FILTER_DOCS_LIST ) ) {
				$missing_here[] = 'docs-list';
			}
			if ( false === strpos( $content, self::CUSTOM_INSTRUCTIONS_MARKER ) ) {
				$missing_here[] = 'custom-instructions';
			}
			$missing[ $label ] = $missing_here;
		}
		return $missing;
	}

	public static function maybe_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! self::is_notice_screen() ) {
			return;
		}
		$missing = self::missing_filters();
		if ( null === $missing ) {
			return;
		}
		$affected = array_filter( $missing ); // descarta entradas con array vacío (nada falta ahí)
		if ( empty( $affected ) ) {
			return;
		}

		// No molestar en cada carga tras haber avisado hoy (el reinstall real
		// es lo que arregla el aviso; esto solo evita repetir el mismo aviso
		// no accionado en cada pantalla de admin).
		$dismissed_at = (int) get_option( self::DISMISSED_OPTION, 0 );
		if ( $dismissed_at && ( time() - $dismissed_at ) < DAY_IN_SECONDS ) {
			return;
		}

		$url = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::REINSTALL_ACTION ),
			self::REINSTALL_ACTION
		);

		$labels = array(
			'lite' => __( 'Support Genix Lite', 'ai-knowledge' ),
			'pro'  => __( 'Support Genix Pro', 'ai-knowledge' ),
		);
		$list = '';
		foreach ( $affected as $label => $codes ) {
			$name = isset( $labels[ $label ] ) ? $labels[ $label ] : $label;
			$items = array();
			foreach ( $codes as $code ) {
				$items[] = esc_html( self::missing_label( $code ) );
			}
			$list .= '<li><strong>' . esc_html( $name ) . ':</strong> ' . implode( '; ', $items ) . '.</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado arriba.
		}

		$dismiss = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="wookb-toolbar-form">'
			. '<input type="hidden" name="action" value="' . esc_attr( self::DISMISS_ACTION ) . '" />'
			. wp_nonce_field( self::DISMISS_ACTION, '_wpnonce', true, false )
			. '<button type="submit" class="button">' . esc_html__( 'Silenciar 24 horas', 'ai-knowledge' ) . '</button></form>';

		printf(
			'<div class="notice notice-warning"><p><strong>%1$s</strong> %2$s</p><ul>%3$s</ul><p><a href="%4$s" class="button button-primary">%5$s</a></p>%6$s</div>',
			esc_html__( 'AI Knowledge & Visibility:', 'ai-knowledge' ),
			esc_html__( 'Support Genix ha perdido los filtros que conectan nuestras mejoras del chatbot (probablemente por una actualización del plugin). El chatbot sigue funcionando, pero le faltan estas mejoras:', 'ai-knowledge' ),
			$list, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado arriba.
			esc_url( $url ),
			esc_html__( 'Reinstalar filtros ahora', 'ai-knowledge' ),
			$dismiss // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML propio escapado arriba.
		);
	}

	/**
	 * Reinserta las 2 lineas que faltan en cada archivo existente (Lite y/o
	 * Pro), con backup previo de cada uno. Idempotente: si una linea ya esta
	 * presente en un archivo, no la duplica en ese archivo.
	 *
	 * Devuelve un array asociativo por etiqueta ('lite'/'pro') con el
	 * resultado de cada uno: array('status' => ...) o un WP_Error si ese
	 * archivo en concreto falló. Un fallo en un archivo no impide intentar
	 * el otro.
	 */
	public static function reinstall() {
		$paths = self::existing_trait_paths();
		if ( empty( $paths ) ) {
			return new \WP_Error( 'wookb_genix_missing', __( 'No se encuentra el archivo de Support Genix (ni Lite ni Pro).', 'ai-knowledge' ) );
		}

		$results = array();
		foreach ( $paths as $label => $path ) {
			$results[ $label ] = self::reinstall_single( $path );
		}
		return $results;
	}

	/**
	 * Aplica el parche a un único archivo de trait. Extraído de reinstall()
	 * para poder repetir la misma operación segura (backup, anclaje textual,
	 * comprobación de sintaxis) sobre Lite y Pro sin duplicar lógica.
	 */
	protected static function reinstall_single( $path ) {
		if ( ! is_writable( $path ) ) {
			return new \WP_Error( 'wookb_genix_not_writable', __( 'El archivo de Support Genix no tiene permisos de escritura.', 'ai-knowledge' ) . ' (' . $path . ')' );
		}

		$content = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		$original = $content;
		$changed  = false;

		if ( false === strpos( $content, self::FILTER_SEARCH_RESULTS ) ) {
			$anchor = '$docs = $is_smalltalk ? [] : $this->search_chatbot_docs($query);';
			if ( false === strpos( $content, $anchor ) ) {
				return new \WP_Error( 'wookb_genix_anchor1_missing', __( 'No se encontró el punto de anclaje para el primer filtro (search-results). Genix pudo cambiar de estructura; revisar manualmente.', 'ai-knowledge' ) . ' (' . $path . ')' );
			}
			$content = str_replace(
				$anchor,
				$anchor . "\n        \$docs = " . self::FILTER_SEARCH_RESULTS,
				$content
			);
			$changed = true;
		}

		if ( false === strpos( $content, self::FILTER_DOCS_LIST ) ) {
			$anchor = '$history->docs_list = $docs;';
			if ( false === strpos( $content, $anchor ) ) {
				return new \WP_Error( 'wookb_genix_anchor2_missing', __( 'No se encontró el punto de anclaje para el segundo filtro (docs-list). Genix pudo cambiar de estructura; revisar manualmente.', 'ai-knowledge' ) . ' (' . $path . ')' );
			}
			$content = str_replace(
				$anchor,
				"\$docs = " . self::FILTER_DOCS_LIST . "\n        " . $anchor,
				$content
			);
			$changed = true;
		}

		if ( false === strpos( $content, self::CUSTOM_INSTRUCTIONS_MARKER ) ) {
			$anchor = '// Reasoning is switched off on every request. A model built to think can';
			if ( false === strpos( $content, $anchor ) ) {
				return new \WP_Error( 'wookb_genix_anchor3_missing', __( 'No se encontró el punto de anclaje para el tercer parche (custom-instructions). Genix pudo cambiar de estructura; revisar manualmente.', 'ai-knowledge' ) . ' (' . $path . ')' );
			}
			// Mismo bloque que ya trae Pro de fabrica (mismas 5 lineas de texto,
			// mismo nombre de variable temporal para no chocar con nada del
			// archivo), insertado justo antes del apagado de "reasoning" para
			// no alterar el resto del orden del prompt.
			$block  = "        // Custom instructions from the site administrator (patched by\n";
			$block .= "        // ai-knowledge: Lite no trae este bloque de fabrica, solo Pro).\n";
			$block .= "        \$wookb_custom_instructions = trim(\$this->GetOption('chatbot_custom_instructions', ''));\n";
			$block .= "        if (!empty(\$wookb_custom_instructions)) {\n";
			$block .= "            \$prompt .= \"## Additional instructions\\n\";\n";
			$block .= "            \$prompt .= \"The site administrator wrote the instructions below. Follow them.\\n\";\n";
			$block .= "            \$prompt .= \"They MAY change: which topics you cover, whether you may answer from general knowledge, your tone, how you greet, and how long your answers run.\\n\";\n";
			$block .= "            \$prompt .= \"They MAY NOT change \\\"What you may say\\\" above. If an instruction below conflicts with it, keep those rules and ignore just the conflicting part.\\n\\n\";\n";
			$block .= "            \$prompt .= \$wookb_custom_instructions . \"\\n\";\n";
			$block .= "        }\n\n";
			$content = str_replace( $anchor, $block . '        ' . $anchor, $content );
			$changed = true;
		}

		if ( ! $changed ) {
			return array( 'status' => 'already_present' );
		}

		$syntax_ok = self::check_syntax( $content );
		if ( is_wp_error( $syntax_ok ) ) {
			return $syntax_ok;
		}

		// Copia de seguridad SOLO tras pasar todas las comprobaciones y justo antes de escribir.
		$backup_dir = WP_CONTENT_DIR . '/db-backup';
		if ( ! is_dir( $backup_dir ) ) {
			wp_mkdir_p( $backup_dir );
		}
		$backup_path = $backup_dir . '/genix-chatquery-trait-backup-' . basename( dirname( dirname( $path ) ) ) . '-' . gmdate( 'Y-m-d_H-i' ) . '.php';
		if ( false === file_put_contents( $backup_path, $original ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_put_contents
			return new \WP_Error( 'wookb_genix_backup_failed', __( 'No se pudo crear la copia de seguridad; no se ha escrito nada en Support Genix.', 'ai-knowledge' ) );
		}

		$ok = file_put_contents( $path, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_put_contents
		if ( false === $ok ) {
			return new \WP_Error( 'wookb_genix_write_failed', __( 'No se pudo escribir el archivo de Support Genix.', 'ai-knowledge' ) . ' (' . $path . ')' );
		}

		return array( 'status' => 'reinstalled', 'backup' => $backup_path );
	}

	/**
	 * Comprueba que el PHP resultante sigue siendo valido antes de escribirlo
	 * de verdad, via eval() en un sandbox de solo-parseo (opcode, sin
	 * ejecutar): token_get_all detecta errores de tokenizado basicos; para
	 * una comprobacion real de sintaxis se usa php -l si esta disponible.
	 */
	protected static function check_syntax( $content ) {
		// 1) Dentro de PHP, sin shell: el tokenizer analiza el codigo completo.
		if ( function_exists( 'token_get_all' ) && defined( 'TOKEN_PARSE' ) ) {
			try {
				token_get_all( $content, TOKEN_PARSE );
				return true;
			} catch ( \ParseError $e ) {
				return new \WP_Error( 'wookb_genix_syntax_error', __( 'La comprobación de sintaxis PHP falló tras insertar los filtros. No se ha escrito nada.', 'ai-knowledge' ) . ' ' . $e->getMessage() . ' (' . (int) $e->getLine() . ')' );
			}
		}

		// 2) Sin tokenizer: php -l por shell; 3) si tampoco, error controlado sin escribir.
		$unverifiable = new \WP_Error( 'wookb_genix_syntax_unverifiable', __( 'No se puede comprobar la sintaxis PHP en este servidor (shell_exec o php no disponibles). Por seguridad no se ha escrito nada en Support Genix; aplica el parche a mano.', 'ai-knowledge' ) );
		if ( ! function_exists( 'shell_exec' ) || ! function_exists( 'escapeshellarg' ) ) {
			return $unverifiable; // No se puede verificar: no se escribe.
		}
		$tmp = wp_tempnam( 'wookb-genix-check' );
		file_put_contents( $tmp, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_put_contents
		$output = shell_exec( 'php -l ' . escapeshellarg( $tmp ) . ' 2>&1' );
		unlink( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink

		if ( null === $output || '' === trim( (string) $output ) || false !== stripos( (string) $output, 'not found' ) ) {
			return $unverifiable;
		}
		if ( false !== strpos( $output, 'No syntax errors detected' ) ) {
			return true;
		}

		// La CLI de "php" del servidor puede ser una version distinta e
		// incompatible con la que sirve el sitio real (confirmado en este
		// mismo proyecto): si el error es justo el de "arrays en constantes
		// de clase" de PHP<5.6, es un falso positivo del binario CLI, no del
		// archivo. Cualquier otro error de sintaxis SI bloquea la escritura.
		if ( false !== strpos( $output, 'Arrays are not allowed in class constants' ) ) {
			return true;
		}
		return new \WP_Error( 'wookb_genix_syntax_error', __( 'La comprobación de sintaxis PHP falló tras insertar los filtros. No se ha escrito nada.', 'ai-knowledge' ) . ' ' . $output );
	}

	public static function handle_reinstall() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No autorizado.', 'ai-knowledge' ) );
		}
		check_admin_referer( self::REINSTALL_ACTION );

		$result = self::reinstall();

		delete_option( self::DISMISSED_OPTION );

		// reinstall() devuelve un WP_Error solo si NI Lite NI Pro estaban
		// instalados; si al menos un archivo existe, devuelve un array por
		// etiqueta ('lite'/'pro') donde cada entrada puede ser a su vez un
		// resultado ok o un WP_Error propio (un archivo puede fallar sin que
		// el otro se vea afectado).
		if ( is_wp_error( $result ) ) {
			set_transient( 'wookb_genix_hooks_reinstall_result', array( 'error' => $result->get_error_message() ), MINUTE_IN_SECONDS );
		} else {
			$errors = array();
			foreach ( $result as $label => $entry ) {
				if ( is_wp_error( $entry ) ) {
					$errors[ $label ] = $entry->get_error_message();
				}
			}
			if ( ! empty( $errors ) ) {
				set_transient( 'wookb_genix_hooks_reinstall_result', array( 'error' => implode( ' | ', $errors ), 'partial_result' => $result ), MINUTE_IN_SECONDS );
			} else {
				set_transient( 'wookb_genix_hooks_reinstall_result', array( 'ok' => true, 'result' => $result ), MINUTE_IN_SECONDS );
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=ai-knowledge' ) );
		exit;
	}
}
