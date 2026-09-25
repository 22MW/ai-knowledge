<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fase 1: meta box "Base de conocimiento IA" en el editor de post, para
 * añadir cualquier post/CPT al alcance del plugin sin pasar por la pestaña
 * Alcance. Solo se registra en los post_types marcados en Ajustes
 * ('editor_button_post_types', ver Scope::settings()).
 */
class Editor_Metabox {

	const BOX_ID = 'wookb-editor-metabox';

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'wp_ajax_wookb_editor_add_to_kb', array( __CLASS__, 'handle_add' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * post_types donde debe aparecer el meta box. null en el ajuste guardado
	 * (nunca configurado a mano) -> default real: todos los post_types
	 * públicos, resuelto aquí (no en el guardado) para que cubra también CPTs
	 * de terceros registrados después de instalar el plugin.
	 */
	public static function allowed_post_types() {
		$settings = Scope::settings();
		if ( is_array( $settings['editor_button_post_types'] ) ) {
			return $settings['editor_button_post_types'];
		}

		$types = get_post_types( array( 'public' => true ), 'names' );
		unset( $types['attachment'], $types['sgkb-docs'] );
		return array_values( $types );
	}

	/** Script del botón (solo en la pantalla de edición de un tipo con metabox). */
	public static function enqueue( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! current_user_can( Admin::capability() ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, self::allowed_post_types(), true ) ) {
			return;
		}
		wp_enqueue_script( 'wookb-editor-metabox', AIKB_URL . 'assets/editor-metabox.js', array(), AIKB_VERSION, true );
		wp_localize_script(
			'wookb-editor-metabox',
			'wookbEditorMetabox',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'working' => __( 'Añadiendo…', 'ai-knowledge' ),
				'error'   => __( 'No se pudo añadir. Recarga la página y vuelve a intentarlo.', 'ai-knowledge' ),
			)
		);
	}

	public static function add_meta_box() {
		if ( ! current_user_can( Admin::capability() ) ) {
			return;
		}
		foreach ( self::allowed_post_types() as $post_type ) {
			if ( ! post_type_exists( $post_type ) ) {
				continue;
			}
			add_meta_box(
				self::BOX_ID,
				__( 'Base de conocimiento IA', 'ai-knowledge' ),
				array( __CLASS__, 'render' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	public static function render( $post ) {
		if ( ! current_user_can( Admin::capability() ) ) {
			esc_html_e( 'No tienes permisos suficientes.', 'ai-knowledge' );
			return;
		}

		$target = Languages::document_target( $post->ID );
		$row    = Registry::find( $target['id'], $target['lang'] );

		if ( $row ) {
			require_once AIKB_DIR . 'admin/class-registry-table.php';
			echo '<p>' . esc_html__( 'Estado:', 'ai-knowledge' ) . ' ' . esc_html( Registry_Table::status_label( $row->status ) ) . '</p>';
			if ( 'manual' === $row->override_mode ) {
				echo '<p>' . esc_html__( 'Modo: manual', 'ai-knowledge' ) . '</p>';
			}
			// Se busca por el documento real (el del original, o el propio con «Crear
			// por idioma»), no por el título de la traducción.
			$url = admin_url( 'admin.php?page=ai-knowledge&tab=registro&s=' . rawurlencode( get_the_title( $target['id'] ) ) );
			echo '<p><a href="' . esc_url( $url ) . '" target="_blank">' . esc_html__( 'Ver en el Registro', 'ai-knowledge' ) . '</a></p>';
			return;
		}

		echo '<p>' . esc_html__( 'Este contenido todavía no está en la base de conocimiento.', 'ai-knowledge' ) . '</p>';
		?>
		<?php // Sin <form>: el metabox vive dentro del formulario del editor y los formularios anidados no existen en HTML. ?>
		<button type="button" class="button button-primary" data-wookb-editor-add data-post-id="<?php echo esc_attr( $post->ID ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wookb_editor_add_to_kb_' . $post->ID ) ); ?>">
			<?php esc_html_e( 'Añadir a la base de conocimiento', 'ai-knowledge' ); ?>
		</button>
		<p class="description" data-wookb-editor-msg role="status" aria-live="polite"></p>
		<?php
	}

	/**
	 * AJAX. Suma al alcance el ID del contenido ORIGINAL (un «ID a incluir» ya
	 * lo fuerza sin importar su tipo: no se añade el tipo entero) y, con «Crear
	 * por idioma», también el de este post; encola el documento del target.
	 * Devuelve la URL de edición con su idioma para volver a la misma pantalla.
	 */
	public static function handle_add() {
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		check_ajax_referer( 'wookb_editor_add_to_kb_' . $post_id, 'nonce' );

		if ( ! current_user_can( Admin::capability() ) ) {
			wp_send_json_error( array( 'message' => __( 'No tienes permisos suficientes.', 'ai-knowledge' ) ), 403 );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_send_json_error( array( 'message' => __( 'Contenido no encontrado.', 'ai-knowledge' ) ), 404 );
		}

		$ids = array( (int) Languages::original_id( $post_id ) );
		if ( Languages::per_language_enabled() ) {
			$ids[] = (int) $post_id;
		}

		// Fase 10, pieza 2: extra_ids fue sustituido por id_actions.
		$id_actions = (array) Scope::settings()['id_actions'];
		foreach ( array_unique( $ids ) as $id ) {
			$id_actions[ $id ] = 'include';
		}
		Scope::update_settings( array( 'id_actions' => $id_actions ) );

		$target = Languages::document_target( $post_id );
		Queue::enqueue( $target['id'], $target['lang'] );

		$redirect = get_edit_post_link( $post_id, 'raw' );
		if ( $redirect && Languages::creates_post_per_language() ) {
			$redirect = add_query_arg( 'lang', Languages::post_language( $post_id ), $redirect );
		}
		wp_send_json_success(
			array(
				'message'  => __( 'Añadido a la base de conocimiento.', 'ai-knowledge' ),
				'redirect' => $redirect ? $redirect : admin_url(),
			)
		);
	}
}
