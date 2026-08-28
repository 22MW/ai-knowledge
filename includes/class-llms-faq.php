<?php
namespace WOOKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contenido de la sección "## Preguntas frecuentes" de /llms.txt.
 *
 * Se guarda en un archivo propio (llms-faq.md, en la raíz del plugin, mismo
 * sitio donde vive chatbot-system-prompt.md) en vez de reutilizar ese mismo
 * archivo, porque el propósito y el destino son opuestos:
 * - chatbot-system-prompt.md es el prompt de SISTEMA del chatbot: se envía
 *   al modelo de IA en cada conversación, nunca se muestra públicamente
 *   (Chatbot_Prompt::sync() lo escribe en un ajuste interno de Support Genix).
 * - llms-faq.md es contenido PÚBLICO pensado para que lo lean crawlers de
 *   IA/LLM directamente desde /llms.txt (Llms_Txt::build()).
 * Mezclarlos en el mismo archivo obligaría a elegir entre exponer
 * instrucciones internas del chatbot en una URL pública, o filtrar el FAQ
 * fuera de lo que recibe el modelo -- ninguna opción es aceptable. Un
 * archivo por cada uno mantiene la responsabilidad y el nivel de exposición
 * separados, igual que el sistema ya separa Markdown_Store (documentos
 * públicos) de wp-content/llm/info.md (también público, pero con su propio
 * archivo por la misma razón de responsabilidad única).
 *
 * Formato: Markdown libre escrito por el administrador del sitio (igual que
 * el campo "Instrucciones adicionales" de Ajustes), sin parseo estructurado
 * de preguntas/respuestas: se inserta tal cual bajo el encabezado "##
 * Preguntas frecuentes" de llms.txt. Sin límite de caracteres forzado (es
 * contenido editorial y estático, no compite por relevancia de búsqueda
 * como las fichas de producto -- ver Generator::BODY_CHAR_LIMIT).
 */
class Llms_Faq {

	public static function file_path() {
		return WOOKB_DIR . 'llms-faq.md';
	}

	public static function read() {
		$path = self::file_path();
		if ( ! file_exists( $path ) ) {
			return '';
		}
		return trim( (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	/**
	 * Guarda el contenido y invalida la caché de llms.txt para que el cambio
	 * se vea en la siguiente petición (mismo patrón que Chatbot_Prompt_Builder::write_info_doc()).
	 */
	public static function save( $content ) {
		$content = trim( (string) $content );
		file_put_contents( self::file_path(), $content . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_put_contents

		if ( class_exists( '\WOOKB\Llms_Txt' ) ) {
			Llms_Txt::invalidate();
		}

		return $content;
	}
}
