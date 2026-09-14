# Plan global — AI Knowledge & Visibility

**Este es el único documento de plan.** Todo lo acordado está aquí, en
fases con pasos concretos — nada queda en un "backlog" aparte para después.
Cada fase explica QUÉ hace y CÓMO se construye (pasos).

Otros archivos de `_dev/` que siguen existiendo y para qué sirven (no son
plan, son consulta):
- `decisiones.md` — decisiones ya cerradas y su motivo (identidad, alcance).
- `contexto-activo.md` — en qué fase estamos ahora mismo.
- `estado-plugin-informe.md` — auditoría técnica del código tal como estaba
  al empezar (referencia, no cambia).
- `mejoras-plugin-ai-woocommerce.md` — documento original de ideas del
  usuario; todo lo aprovechable de ahí ya está repartido en las fases de
  abajo.

Núcleo del plugin (generación de documentos vía OpenAI + Support Genix) se
mantiene sin reescribir. Todas las fases se añaden encima o al lado.

---

## Fase 0 — Identidad

**Qué hace:** el plugin pasa a llamarse AI Knowledge & Visibility.

**Pasos:**
1. Cambiar cabecera de `woo-kb-generator.php`: `Plugin Name`, `Text Domain` → `ai-knowledge`.
2. Renombrar carpeta del plugin y el repositorio Git a `ai-knowledge`.
3. Actualizar `readme.txt` y `CHANGELOG.md`.
4. Mantener sin tocar: namespace `WOOKB`, constantes `WOOKB_*`, tabla `wookb_documents`, opciones `wookb_*` (evita migración de datos innecesaria).

---

## Fase 1 — Control manual de documentos + botón en el editor

**Qué hace:** permite fijar a mano el texto de un documento (sin que se
regenere solo), controlar el límite de caracteres por documento, avisar
cuando el original cambió después de fijarlo a mano, y añadir cualquier post
a la base de conocimiento desde su propio editor.

**Datos nuevos en `wp_wookb_documents`:**
- `override_mode` (`auto` | `manual`), default `auto`.
- `override_text` (texto largo, Markdown fijado a mano).
- `char_limit` (número, límite propio de ese documento; vacío = usa el límite general).
- `stale` (0/1: 1 si el post de origen cambió después de fijar el modo manual).

Migración automática: sube `WOOKB_VERSION`, el mecanismo ya existente en
`plugins_loaded` vuelve a ejecutar `Registry::create_table()` (dbDelta añade
columnas sin borrar datos).

**Pasos:**
1. `class-registry.php`: añadir las 4 columnas al `CREATE TABLE` y a `upsert()`.
2. `class-document-pipeline.php`: al entrar en `process()`, si `override_mode = manual` — calcular el hash del origen igual que hoy, pero:
   - si coincide con `source_hash` guardado → no hacer nada.
   - si no coincide → marcar `stale = 1`, sin tocar `override_text` ni el `.md`.
   Si `override_mode = auto` (como hoy), generar con IA usando `char_limit` de la fila si tiene valor, si no el límite general.
3. `admin/views/tab-registro.php`: por fila — textarea para ver/editar el Markdown actual (leído con `Markdown_Store::read()`), campo numérico de `char_limit`, botón "Pasar a manual" / "Volver a Auto" (fuerza regeneración), badge "Origen actualizado" si `stale = 1`.
4. `admin/class-admin.php`: nuevas acciones `admin_post`: `wookb_set_manual`, `wookb_set_char_limit`, `wookb_resolve_stale`, `wookb_back_to_auto`. Todas con `check_admin_referer` + `current_user_can()`, sanitizando el texto con `sanitize_textarea_field`.
5. `class-admin.php::maybe_handle_bulk_action()`: "Regenerar seleccionados" excluye las filas en modo manual, mostrando cuántas se saltaron.
6. Aviso de `stale`: `admin_notices` en la página del plugin si hay alguna fila `stale`, más un nodo en la barra de admin (`admin_bar_menu`) con el contador y enlace al Registro filtrado.
7. Nuevo `includes/class-editor-metabox.php`: meta box "Base de conocimiento IA" en el editor, visible solo en los CPTs marcados en Ajustes (nuevo checkbox por CPT público, default: todos marcados). Botón "Añadir a la base de conocimiento" → añade el `post_type` (si falta) y el ID a `Scope::update_settings()` y encola en modo auto (`Queue::enqueue()`). Si el post ya está añadido, muestra su estado y enlace directo a su fila del Registro.
8. Diseño: reusar clases de Tabler/`wookb-theme.css`/`.wookb-card` ya existentes; CSS nuevo solo para lo que no exista (el badge de "desactualizado").
9. Arreglo aparte, mismo commit o el siguiente: salto de tema claro→oscuro en cada recarga. Causa: `assets/admin.js` aplica `data-bs-theme` en `$(document).ready` y el script está en el footer. Arreglo: `Admin::render()` imprime un `<script>` inline síncrono al abrir `.wookb-wrap` que lee `localStorage`/`prefers-color-scheme` y pone el atributo antes de pintar; `admin.js` deja de fijar el tema al cargar, solo gestiona el clic del botón.

**Validación:** `php -l` en los archivos tocados; prueba manual — fijar texto manual, editar el post de origen, confirmar que el `.md` no cambia y que aparece el aviso; volver a Auto y confirmar que regenera.

---

## Fase 2 — Pestaña propia "WooCommerce" (tienda, envíos, IVA, pagos)

**Qué hace:** hoy la información de tienda (`Store_Info_Doc`) se genera con
un solo botón perdido en Ajustes, sin ver qué detecta el plugin ni poder
rellenar lo que falta — y no recoge impuestos/IVA en absoluto. Se convierte
en una pestaña propia con secciones claras: lo que WooCommerce ya tiene
configurado (autodetectado, de solo lectura) y lo que hay que rellenar a
mano porque WooCommerce no lo sabe.

**Pasos:**
1. Nueva pestaña "WooCommerce" en `class-admin.php` (mismo patrón que las
   pestañas actuales), visible solo si `class_exists('WooCommerce')`.
2. Sección "Detectado automáticamente" (solo lectura, tal como ya construye
   `Store_Info_Doc::build_store_info_body()`):
   - Datos de tienda: nombre, moneda (`get_woocommerce_currency()`), país base.
   - Envíos: zonas y métodos reales (`WC_Shipping_Zones::get_zones()`), igual
     que ya se inspeccionó para confirmar que no hay plazos de entrega.
   - **Impuestos/IVA (nuevo, no existe hoy):** tipos de impuesto configurados
     (`WC_Tax::get_rates()` / tablas de tipos por clase fiscal), si los
     precios ya incluyen IVA o no (`wc_prices_include_tax()`).
   - Pagos: pasarelas habilitadas (ya existe `payment_summary()`, se reutiliza).
   - Condiciones de venta y devoluciones: páginas configuradas (ya existe).
3. Sección "Rellenar a mano" (lo que WooCommerce no puede saber): plazo de
   entrega en texto libre, contacto y horario (reutiliza el campo ya
   existente de `Chatbot_Prompt_Builder`), notas legales adicionales.
4. Botón "Generar/actualizar documentos de tienda" en esta misma pestaña
   (mueve el botón que hoy está en Ajustes), llamando a
   `Store_Info_Doc::generate_all()` sin cambios en su lógica de generación
   — la pestaña nueva es solo mejor interfaz sobre lo que ya existe, más el
   dato de IVA que se añade a `build_store_info_body()`.
5. Vista previa: mostrar el Markdown ya generado (`Markdown_Store::read()`)
   de `store-info` y `shop-catalog` en un textarea de solo lectura, para que
   el admin vea exactamente qué se está publicando antes de sincronizar.
6. Diseño: misma pestaña/tarjeta Tabler que el resto, sin CSS nuevo salvo
   las dos secciones (detectado / manual) si hace falta separarlas visualmente.

**Validación:** `php -l`; prueba manual — con impuestos configurados en
WooCommerce, confirmar que aparecen en la vista previa del documento.

---

## Fase 3 — JSON estructurado (sin IA)

**Qué hace:** expone el contenido real de WordPress/WooCommerce en JSON, sin
pasar por IA — dato tal cual existe, para que crawlers/agentes lo lean sin
procesar HTML.

**Pasos:**
1. Nuevo endpoint REST `GET /wp-json/ai-knowledge/v1/content/{id}` y `GET /wp-json/ai-knowledge/v1/{post_type}`, solo lectura.
2. Reutilizar `Extractor_Base::for_post_type()` / `Extractor_Woo` para construir la respuesta (mismo dato que ya se usa para generar el Markdown, no se duplica lógica).
3. Permisos: público de solo lectura, pero filtrado por `Scope::is_included()` (solo lo que ya está en el alcance del plugin).
4. Caché: cabecera HTTP de caché + invalidación cuando `Sync` detecte cambio en ese post.

---

## Fase 4 — Markdown público con descubrimiento automático

**Qué hace:** el mismo `.md` que ya se genera para el chatbot se anuncia
también a buscadores/agentes IA, con una ruta estable y un enlace en el
`<head>` de la página de origen.

**Pasos:**
1. En el `<head>` del contenido incluido en `Scope`, añadir `<link rel="alternate" type="text/markdown" href="...">` apuntando a la URL pública del `.md` (`Markdown_Store::public_url()`).
2. Confirmar que la ruta ya es estable (lo es: `wp-content/llm/{lang}/{slug}.md`), documentarlo como contrato público.

---

## Fase 5 — JSON-LD (Schema.org)

**Qué hace:** añade datos estructurados Schema.org por tipo de contenido,
para que buscadores entiendan qué es cada página sin adivinarlo.

**Pasos:**
1. Mapeo fijo por post_type: WooCommerce (`product`) → `Product`/`Offer` (usando `Extractor_Woo`, precio, stock, SKU ya disponibles); resto de post_types del alcance → `Article`.
2. Inyectar el JSON-LD en `wp_head` solo para contenido dentro de `Scope`.
3. Sin admin nuevo en esta fase: mapeo fijo, no configurable todavía.

---

## Fase 6 — Panel de visibilidad y diagnóstico

**Qué hace:** una pestaña nueva en el admin ya existente que resume si el
contenido está bien expuesto a IA/buscadores.

**Pasos:**
1. Nueva pestaña "Visibilidad IA" en `class-admin.php` (mismo patrón que las pestañas actuales).
2. Mostrar: total de contenido expuesto, estado de `llms.txt` (ok / tapado por archivo físico, ver `Llms_Txt::physical_file_exists()`), estado del Markdown, del JSON (Fase 2) y del Schema (Fase 4).
3. Botón "Comprobar accesibilidad": lee `robots.txt`, cabecera `X-Robots-Tag` y `noindex` de una URL de muestra; muestra resultado simple (ok / aviso), sin editar nada automáticamente.

---

## Fase 7 — API pública documentada

**Qué hace:** describe la API de la Fase 2 para que un agente pueda
descubrirla solo.

**Pasos:**
1. Generar `/wp-json/ai-knowledge/v1/openapi.json` a partir de las rutas ya registradas en la Fase 2 (usar el propio schema de REST de WordPress, no escribirlo a mano).

---

## Fase 8 — Aviso a buscadores en tiempo real (IndexNow)

**Qué hace:** cuando se crea, actualiza o borra contenido del alcance, avisa
a los buscadores compatibles con IndexNow en vez de esperar a que rastreen.

**Pasos:**
1. Enganchar en los mismos puntos que ya usa `Sync` (`on_product_saved`, `on_generic_post_saved`, `on_trash_or_delete`).
2. Cola con debounce (reusar patrón de `Queue`) para no notificar de más si hay varios guardados seguidos.
3. Ajuste on/off en Ajustes, clave IndexNow propia del sitio.

---

## Fase 9 — Feeds especializados

**Qué hace:** exporta el catálogo en formatos que ya esperan otras
plataformas (comparadores, Google Merchant).

**Pasos:**
1. `GET /wp-json/ai-knowledge/v1/feeds/products.xml` (Google Merchant) para WooCommerce, usando `Extractor_Woo`.
2. Un feed genérico `feeds/content.json` para el resto de post_types del alcance.

---

## Fase 10 — Campos personalizados de ACF / Meta Box / Pods

**Qué hace:** además de `post_meta` plano (ya soportado hoy en
`Scope::custom_fields_for()`), detecta campos definidos con esos plugins
para poder seleccionarlos igual que los nativos.

**Pasos:**
1. Detectar si `class_exists('ACF')` / Meta Box / Pods está activo.
2. Ampliar `Scope::sampled_custom_field_keys()` para incluir esos campos con su etiqueta legible, no solo la key técnica.

---

## Fase 11 — Gestión de crawlers de IA

**Qué hace:** distingue crawlers de búsqueda/retrieval (OpenAI, Claude,
Perplexity, Google, Bing) de crawlers de entrenamiento, y deja ver/ajustar
`robots.txt` en consecuencia.

**Pasos:**
1. Lectura de `robots.txt` actual (solo lectura) en el panel de la Fase 5.
2. Edición asistida: requiere permiso explícito del usuario antes de escribir en `robots.txt` (archivo sensible, fuera del propio plugin) — no se automatiza sin esa confirmación en cada caso.
3. Logs de accesos de crawlers conocidos, con límite de filas para no llenar la base de datos.

---

## Explícitamente descartado (decisión ya tomada, no una fase)

- Prompt específico por documento — se descartó a favor de solo texto manual (Fase 1).
- Generación de `.md` opcional según tráfico — no es un problema real (generación async, servido como archivo estático); se mantiene siempre activa.
- Pivote a normalizador puro sin IA como núcleo — se mantiene IA + Support Genix como núcleo del producto.
