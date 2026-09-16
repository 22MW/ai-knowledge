<?php
namespace WOOKB;

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
		add_action( 'admin_post_wookb_editor_add_to_kb', array( __CLASS__, 'handle_add' ) );
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
		unset( $types['attachment'] );
		return array_values( $types );
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

		$lang = Wpml::element_language( $post->ID );
		$row  = Registry::find( $post->ID, $lang );

		if ( $row ) {
			require_once WOOKB_DIR . 'admin/class-registry-table.php';
			echo '<p>' . esc_html__( 'Estado:', 'ai-knowledge' ) . ' ' . esc_html( Registry_Table::status_label( $row->status ) ) . '</p>';
			if ( 'manual' === $row->override_mode ) {
				echo '<p>' . esc_html__( 'Modo: manual', 'ai-knowledge' ) . '</p>';
			}
			$url = admin_url( 'admin.php?page=woo-kb-generator&tab=registro&s=' . rawurlencode( $post->post_title ) );
			echo '<p><a href="' . esc_url( $url ) . '" target="_blank">' . esc_html__( 'Ver en el Registro', 'ai-knowledge' ) . '</a></p>';
			return;
		}

		echo '<p>' . esc_html__( 'Este contenido todavía no está en la base de conocimiento.', 'ai-knowledge' ) . '</p>';
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="wookb_editor_add_to_kb" />
			<input type="hidden" name="post_id" value="<?php echo esc_attr( $post->ID ); ?>" />
			<?php wp_nonce_field( 'wookb_editor_add_to_kb_' . $post->ID, 'wookb_editor_nonce' ); ?>
			<button type="submit" class="button button-primary">
				<?php esc_html_e( 'Añadir a la base de conocimiento', 'ai-knowledge' ); ?>
			</button>
		</form>
		<?php
	}

	/**
	 * Añade el post_type (si falta) y el ID de este post concreto al alcance
	 * (mismo patron que Admin::save_content()), y encola su generación en modo
	 * auto. No quita nada del alcance existente: solo suma.
	 */
	public static function handle_add() {
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0; // phpcs:ignore
		check_admin_referer( 'wookb_editor_add_to_kb_' . $post_id, 'wookb_editor_nonce' );

		if ( ! current_user_can( Admin::capability() ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes.', 'ai-knowledge' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_die( esc_html__( 'Contenido no encontrado.', 'ai-knowledge' ) );
		}

		$settings   = Scope::settings();
		$post_types = (array) $settings['post_types'];
		if ( ! in_array( $post->post_type, $post_types, true ) ) {
			$post_types[] = $post->post_type;
		}

		// Fase 10, pieza 2: extra_ids fue sustituido por id_actions.
		$id_actions = (array) $settings['id_actions'];
		$id_actions[ (int) $post_id ] = 'include';

		Scope::update_settings(
			array(
				'post_types'  => $post_types,
				'id_actions'  => $id_actions,
			)
		);

		$lang = Wpml::element_language( $post_id );
		Queue::enqueue( $post_id, $lang );

		$redirect = get_edit_post_link( $post_id, 'raw' );
		wp_safe_redirect( $redirect ? $redirect : admin_url() );
		exit;
	}
}
