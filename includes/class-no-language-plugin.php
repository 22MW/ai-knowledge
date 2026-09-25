<?php
namespace AIKB;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Proveedor «ninguno»: web de un solo idioma (o sin plugin de idiomas). El
 * idioma sale de Negocio o del idioma de WordPress, decidido por `Languages`.
 */
class No_Language_Plugin extends Language_Provider {

	public static function detect() {
		return true;
	}

	public function id() {
		return 'none';
	}

	public function label() {
		return __( 'Ninguno', 'ai-knowledge' );
	}
}
