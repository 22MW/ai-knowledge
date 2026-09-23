# AI Knowledge & Visibility — Documentación técnica

Este documento es para desarrolladores que vayan a tocar o extender este
plugin. Se queda dentro del propio plugin, **no se publica en la web**.
Documenta lo real, verificado en el código a fecha 2026-09-16 — no
promesas ni roadmap. Para eso ver `_dev/roadmap.md`.

## Identidad y estructura

- Archivo principal: `ai-knowledge.php`.
- Namespace: `AIKB\` (clases de dominio) y `AIKB\Extractors\` (extractores
  de contenido para prompts, en `includes/extractors/`).
- Constantes: `AIKB_VERSION`, `AIKB_FILE`, `AIKB_DIR`, `AIKB_URL`,
  `AIKB_TABLE_DOCUMENTS`.
- Text Domain: `ai-knowledge`.
- Autoload: `spl_autoload_register` por convención de nombre en
  `ai-knowledge.php` — mapea `AIKB\Nombre_Clase` a
  `includes/class-nombre-clase.php` (o `admin/class-nombre-clase.php`), y
  `AIKB\Extractors\Nombre` a `includes/extractors/class-nombre.php`.

### Deuda de naming heredada (intencional, no tocar sin motivo)

El namespace/constantes se renombraron de `WOOKB` a `AIKB` en 2026-09-16
(ver `_dev/decisiones.md`, "Cierre del rename interno"). Por compatibilidad
con instalaciones existentes, **se mantienen sin cambiar**:

- Nombre real de la tabla de BD: `wookb_documents` (la constante que la
  referencia ya se llama `AIKB_TABLE_DOCUMENTS`, pero su *valor* sigue
  siendo el string `'wookb_documents'`).
- Funciones de ciclo de vida: `wookb_activate()`, `wookb_deactivate()`.
- Opción de versión de esquema: `wookb_db_version`.
- Múltiples opciones/transients/query-args con prefijo `wookb_*` (ver
  lista completa en `_dev/decisiones.md`).
- Archivo `assets/wookb-theme.css` y su handle de enqueue `wookb-theme`.

Si renombras cualquiera de esto en el futuro, necesitas una migración de
datos, no un simple find-and-replace.

## Base de datos

Una única tabla propia: `{$wpdb->prefix}wookb_documents`.

```sql
CREATE TABLE wp_wookb_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_id BIGINT UNSIGNED NOT NULL,
    source_type VARCHAR(32) NOT NULL,
    lang VARCHAR(8) NOT NULL,
    md_path VARCHAR(255) NOT NULL,
    doc_post_id BIGINT UNSIGNED NULL,
    source_hash CHAR(64) NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'queued',
    is_bridge TINYINT(1) NOT NULL DEFAULT 0,
    product_trid BIGINT UNSIGNED NULL,
    doc_trid BIGINT UNSIGNED NULL,
    last_error TEXT NULL,
    generated_at DATETIME NULL,
    updated_at DATETIME NOT NULL,
    override_mode VARCHAR(8) NOT NULL DEFAULT 'auto',
    override_text LONGTEXT NULL,
    char_limit INT UNSIGNED NULL,
    stale TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY source_lang (source_id, lang),
    KEY status (status),
    KEY source_type (source_type),
    KEY product_trid (product_trid)
);
```

- `source_id` + `lang` identifican de forma única cada documento (un
  producto/página puede tener un documento por idioma activo de WPML).
- `override_mode = manual` + `override_text`: modo de edición manual
  (ver "Registro" en la doc pública). `stale = 1` cuando el origen cambió
  después de fijar el modo manual.
- `is_bridge`, `product_trid`, `doc_trid`: soporte de traducciones WPML
  (relacionan el documento con el `trid` del producto y con el `trid` del
  propio post-documento).
- Migraciones: se gestionan con `dbDelta()` en `plugins_loaded`, subiendo
  la versión de esquema en la opción `wookb_db_version` — añade columnas,
  no borra datos existentes.

Otras tablas propias: `{$wpdb->prefix}wookb_crawler_log` (accesos de
crawlers de IA, purgada a un máximo de filas con `trim_to_limit()` en
`class-crawler-log.php`).

## Dependencias en runtime (ninguna dura)

Todas se detectan en tiempo real, nunca se declaran como dependencia
obligatoria:

- **WooCommerce**: `class_exists('WooCommerce')`. Activa la pestaña
  `woocommerce` y los datos autodetectados de tienda.
- **Support Genix**: `Chatbot_Prompt::is_genix_ready()`. Activa la
  pestaña `prompt` (Chatbot) y la sincronización del system prompt.
- **WPML**: se detecta por la presencia de sus filtros (`wpml_*`) vía
  `apply_filters()`, encapsulado en `includes/class-wpml.php`. Sin WPML,
  esos filtros simplemente no están registrados y las funciones de esa
  clase devuelven valores por defecto (un solo idioma).
- **Conectores nativos de IA de WordPress** (WP 7.0+): `AI_Client::
  wordpress_available()`, comprueba `function_exists('wp_supports_ai')` /
  `wp_ai_client_prompt()` antes de usarlas.

## Puntos de extensión propios

El plugin expone pocos filtros propios, todos con prefijo `wookb_`
(heredado, ver nota de naming arriba):

| Filtro | Dónde | Para qué |
|---|---|---|
| `wookb_crawler_catalog` | `class-crawler-catalog.php` | Modificar el catálogo de user-agents de bots de IA conocidos |
| `wookb_skip_schema` | `class-markdown-discovery.php` | Forzar que un post concreto no lleve JSON-LD |
| `wookb_chatbot_docs_list_limit` | `class-chatbot-relevance-guard.php` | Sobrescribir el límite de "documentos relacionados" del chatbot |

## API REST propia

Namespace `ai-knowledge/v1`, registrada en `class-rest-content.php`:

- `GET /wp-json/ai-knowledge/v1/content/{id}` — datos estructurados en
  JSON de un contenido concreto (WordPress/WooCommerce, sin pasar por
  IA).
- `GET /wp-json/ai-knowledge/v1/openapi.json` — documentación OpenAPI de
  la propia API, para descubribilidad automática por agentes de IA.
- `GET /wp-json/ai-knowledge/v1/feeds/products.xml` — feed tipo Google
  Merchant (solo si WooCommerce está activo).
- `GET /wp-json/ai-knowledge/v1/feeds/content.json` — feed JSON de
  contenido.

Todas de solo lectura (`WP_REST_Server::READABLE`); revisar
`class-rest-guard.php` para las reglas de acceso/capability.

## Rutas virtuales (sin archivo físico)

Servidas mediante rewrite rules + hooks de `template_redirect` (no
`file_put_contents` en la raíz del sitio):

- `/llms.txt` — `class-llms-txt.php`.
- Markdown público de cada documento — `class-markdown-server.php`.

Escrituras reales en disco (fuera de la carpeta del plugin, en
`wp-content/`, no se pierden en updates):
`wp-content/llm/index.php` (protección de listado de directorio),
`wp-content/llm/info.md` (resumen de negocio publicado),
`wp-content/db-backup/` (backups puntuales generados por
`class-genix-hooks-guard.php`).

## Seguridad — patrón usado en todo el admin

- Todas las acciones de escritura del admin pasan por
  `Admin::verify($action)` (`admin/class-admin.php`), que hace
  `current_user_can(self::capability())` + `check_admin_referer($action)`
  en un único punto. `capability()` devuelve `manage_woocommerce` si
  WooCommerce está activo, o el fallback definido en
  `CAPABILITY_FALLBACK` si no.
- Todas las vistas de tab (`admin/views/tab-*.php`) empiezan con
  `defined('ABSPATH') || exit;`.
- Sanitización de entrada: `sanitize_key()`/`sanitize_text_field()`/
  `absint()` según el campo; salida siempre escapada por contexto
  (`esc_html`, `esc_attr`, `esc_url`, `esc_textarea`).
- SQL directo (`$wpdb`) solo en `class-registry.php`,
  `class-crawler-log.php` y `class-chatbot-relevance-guard.php`, siempre
  con `$wpdb->prepare()` para los valores; el nombre de tabla se
  interpola porque `prepare()` no soporta placeholders para identificadores
  — patrón aceptado, ver `_dev/temp/plugin-check-20260916.md`.

## Generación de documentos — flujo general

1. `class-scope.php` resuelve qué IDs entran en el alcance configurado
   (tipos de contenido, taxonomías, IDs sueltos, exclusiones).
2. `class-document-pipeline.php` orquesta la generación: calcula el hash
   del contenido origen, decide si hace falta regenerar (o si está en
   modo manual y hay que marcarlo `stale`), llama a `class-generator.php`
   (o a los extractors de `includes/extractors/` para el contexto
   enviado a la IA), y guarda el resultado vía `class-registry.php`.
3. `class-queue.php` + Action Scheduler gestionan el procesamiento por
   lotes de Generación masiva, respetando el límite diario.
4. `class-sync.php` / `class-doc-redirect.php` mantienen sincronizados
   los posts espejo (`sgkb-docs` de Support Genix, si está activo) y las
   rutas públicas.

## Convenciones a seguir si extiendes este plugin

- Jerarquía técnica: núcleo de WordPress → API de WooCommerce si aplica →
  hooks/filtros ya existentes → helpers propios (`Scope`, `Registry`,
  etc.) → código nuevo, en ese orden — no recrear lo que ya resuelve
  WordPress/WooCommerce.
- Cualquier acción de escritura nueva en el admin debe pasar por
  `Admin::verify()`, no reinventar la verificación de nonce/capability.
- Cualquier prompt a IA debe declarar explícitamente que no puede inventar
  datos fuera de lo que se le pasa como contexto — es una decisión de
  producto ya tomada (ver `_dev/decisiones.md`), no opcional por defecto.
- No recrear productos, pedidos, checkout, pagos o stock de WooCommerce
  con sistemas paralelos — leer siempre con las APIs oficiales (`wc_*`,
  `WC_Product`, `WC_Order`...).

## Dónde mirar para más contexto

- `_dev/decisiones.md` — por qué se tomó cada decisión de diseño.
- `_dev/roadmap.md` — historial de fases y qué está hecho/pendiente.
- `_dev/temp/plugin-check-20260916.md` — triage de seguridad/estilo del
  escáner Plugin Check, con verificación real en código.
