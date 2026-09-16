# Plan global — AI Knowledge & Visibility

**Este es el único documento de plan.** Todo lo acordado está aquí, en
fases con pasos concretos — nada queda en un "backlog" aparte para después.
Cada fase explica QUÉ hace y CÓMO se construye (pasos).

Otros archivos de `_dev/` que siguen existiendo y para qué sirven (no son
plan, son consulta):
- [`decisiones.md`](decisiones.md) — decisiones ya cerradas y su motivo (identidad, alcance).
- [`contexto-activo.md`](contexto-activo.md) — en qué fase estamos ahora mismo.
- [`estado-plugin-informe.md`](estado-plugin-informe.md) — auditoría técnica del código tal como estaba
  al empezar (referencia, no cambia).
- [`mejoras-plugin-ai-woocommerce.md`](mejoras-plugin-ai-woocommerce.md) — documento original de ideas del
  usuario; todo lo aprovechable de ahí ya está repartido en las fases de
  abajo.

Núcleo del plugin (generación de documentos vía OpenAI + Support Genix) se
mantiene sin reescribir. Todas las fases se añaden encima o al lado.

---

## Fase 0 — Identidad — HECHO

**Qué hace:** el plugin pasa a llamarse AI Knowledge & Visibility.

**Pasos:**
1. Cambiar cabecera de `woo-kb-generator.php`: `Plugin Name`, `Text Domain` → `ai-knowledge`.
2. Renombrar carpeta del plugin y el repositorio Git a `ai-knowledge`.
3. Actualizar `readme.txt` y `CHANGELOG.md`.
4. Mantener sin tocar: namespace `WOOKB`, constantes `WOOKB_*`, tabla `wookb_documents`, opciones `wookb_*` (evita migración de datos innecesaria).

---

## Fase 1 — Control manual de documentos + botón en el editor — HECHO

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

## Fase 2 — Pestaña propia "WooCommerce" (tienda, envíos, IVA, pagos) — HECHO (rediseño UX pendiente, ver qa-resultados)

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

## Fase 3 — JSON estructurado (sin IA) — HECHO

**Qué hace:** expone el contenido real de WordPress/WooCommerce en JSON, sin
pasar por IA — dato tal cual existe, para que crawlers/agentes lo lean sin
procesar HTML.

**Pasos:**
1. Nuevo endpoint REST `GET /wp-json/ai-knowledge/v1/content/{id}` y `GET /wp-json/ai-knowledge/v1/{post_type}`, solo lectura.
2. Reutilizar `Extractor_Base::for_post_type()` / `Extractor_Woo` para construir la respuesta (mismo dato que ya se usa para generar el Markdown, no se duplica lógica).
3. Permisos: público de solo lectura, pero filtrado por `Scope::is_included()` (solo lo que ya está en el alcance del plugin).
4. Caché: cabecera HTTP de caché + invalidación cuando `Sync` detecte cambio en ese post.

---

## Fase 4 — Markdown público con descubrimiento automático — HECHO

**Qué hace:** el mismo `.md` que ya se genera para el chatbot se anuncia
también a buscadores/agentes IA, con una ruta estable y un enlace en el
`<head>` de la página de origen.

**Pasos:**
1. En el `<head>` del contenido incluido en `Scope`, añadir `<link rel="alternate" type="text/markdown" href="...">` apuntando a la URL pública del `.md` (`Markdown_Store::public_url()`).
2. Confirmar que la ruta ya es estable (lo es: `wp-content/llm/{lang}/{slug}.md`), documentarlo como contrato público.

---

## Fase 5 — JSON-LD (Schema.org) — HECHO

**Qué hace:** añade datos estructurados Schema.org por tipo de contenido,
para que buscadores entiendan qué es cada página sin adivinarlo.

**Pasos:**
1. Mapeo fijo por post_type: WooCommerce (`product`) → `Product`/`Offer` (usando `Extractor_Woo`, precio, stock, SKU ya disponibles); resto de post_types del alcance → `Article`.
2. Inyectar el JSON-LD en `wp_head` solo para contenido dentro de `Scope`.
3. Sin admin nuevo en esta fase: mapeo fijo, no configurable todavía.

---

## Fase 6 — Panel de visibilidad y diagnóstico — HECHO

**Qué hace:** una pestaña nueva en el admin ya existente que resume si el
contenido está bien expuesto a IA/buscadores.

**MVP:**
1. Nueva pestaña "Visibilidad IA" en `class-admin.php` (mismo patrón que las pestañas actuales), vista `admin/views/tab-visibilidad-ia.php`.
2. Resumen (sin acción del usuario, calculado en `render()`): estado de `llms.txt` (ok / tapado por archivo físico, `Llms_Txt::physical_file_exists()`), confirmación de que Markdown/JSON (Fase 3)/Schema (Fase 5) están activos para el contenido en scope, y contador simple "Quedan N elementos sin sincronizar" = `Scope::resolve_ids()` menos filas con `status = 'synced'` en `Registry` — decisión tomada en [`analisis-jet-geo.md`](analisis-jet-geo.md) (Content_Inspector). Sin desglose por post_type (mejora futura).
3. Selector desplegable con contenido ya sincronizado (`Registry::query(['status' => 'synced'])`: título + URL) + botón "Comprobar accesibilidad". **La URL a comprobar sale solo de este selector, nunca de un campo libre** (evita SSRF).
4. Acción `admin_post` `wookb_check_accessibility` (nonce + `current_user_can()`) en `class-admin.php`: `wp_remote_get()` a la URL elegida, lee cabecera `X-Robots-Tag` y `<meta name="robots">` del HTML devuelto; lee `home_url('/robots.txt')` para saber si esa ruta está permitida. Resultado guardado en transient corto y mostrado tras redirect (mismo patrón que otras acciones `admin_post` del plugin).
5. Detección de conflicto de señales: avisa si `robots.txt` permite la URL pero `noindex`/`X-Robots-Tag` la bloquea, o viceversa — decisión tomada en [`analisis-jet-geo.md`](analisis-jet-geo.md) (opción A).

**Casos límite:** desplegable vacío si no hay contenido sincronizado (ocultar botón); `wp_remote_get` puede fallar (timeout/local) → mostrar error, no fatal.

**Validación:** `php -l`; prueba manual — contador contra `Registry::summary()`, forzar un `noindex` en un post permitido por `robots.txt` y confirmar que se detecta el conflicto. **Confirmado por el usuario en real**: contador correcto (26 pendientes), conflicto detectado en un caso real (noindex del sitio local vía "Desalentar a los motores de búsqueda").

**Ampliación tras confirmación visual:** descripción introductoria de la pestaña; cada fila (Markdown/JSON/JSON-LD) enlaza a un ejemplo real ya sincronizado y explica para qué sirve; `llms.txt` físico muestra fecha de modificación + vista previa de sus primeras líneas, y botón "Borrar archivo físico" (confirmación JS fuerte, mismo patrón que "Borrar todos" del Registro) explicando antes que el plugin genera el suyo dinámicamente al vuelo, sin archivo fijo.

**Bug aparte encontrado y corregido en el mismo commit:** `<select>` del admin con texto invisible en hover/foco en modo oscuro (`.wp-core-ui select:hover` de WordPress core, mismo caso que los botones — ver `_dev/decisiones.md`).

---

## Fase 7 — API pública documentada — HECHO (confirmado en navegador por el usuario)

**Qué hace:** describe la API REST de la Fase 3 (`class-rest-content.php`)
para que un agente pueda descubrirla solo.

**Hallazgo tras `evaluar-cambio`:** el enunciado original ("usar el propio
schema de REST de WordPress, no escribirlo a mano") no era del todo
aplicable — WordPress genera solo un índice de descubrimiento propio en
`/wp-json/ai-knowledge/v1` (no es formato OpenAPI). Decisión tomada: un
documento **OpenAPI 3.0 real, escrito a mano** (son solo 2 rutas, poco
trabajo), en vez de renombrar el índice nativo de WordPress como si fuera
OpenAPI sin serlo.

**Implementado:**
1. Nueva ruta `GET /ai-knowledge/v1/openapi.json` en
   `Rest_Content::register_routes()`, pública (mismo criterio que las
   otras 2 rutas).
2. Nuevo método `Rest_Content::get_openapi_spec()`: `info` (título, versión
   = `WOOKB_VERSION`), `servers` (`home_url('/wp-json/ai-knowledge/v1')`),
   `paths` para `/content/{id}` y `/{post_type}` (con `page`/`per_page`),
   respuesta 200 + 404 genérico.
3. `components.schemas.ContentItem` tipado según
   `Extractor_Base::extract()`/`Extractor_Woo`: `id` (integer),
   `post_type`/`title`/`content`/`excerpt`/`url`/`lang` (string),
   `taxonomies`/`custom_fields` (object), `price`/`stock`/`sku` (string,
   nullable — solo poblados si es un producto WooCommerce),
   `variants` (array de objetos).

**Excluido:** rutas de `sgkb-docs`/`wp/v2` (protegidas por `Rest_Guard`, no
públicas a propósito); validación automática contra un validador OpenAPI
externo (solo manual).

**Validación:** `php -l`; prueba manual — pedir el endpoint y revisar la
estructura (`openapi`, `info`, `paths`, `components.schemas.ContentItem`).

**Ampliación (descubribilidad):** publicar el endpoint no basta si nada
enlaza a él. `llms.txt` (`Llms_Txt::build()`) ahora incluye una sección
`## API` con enlace a `openapi.json`, justo después del resumen/info y
antes de las categorías de contenido — ver decisión en `decisiones.md`.
Nota: `llms.txt` se cachea 24h, este cambio tarda en verse reflejado hasta
que expire el caché o se sincronice contenido (`Llms_Txt::invalidate()`).

---

## Fase 8 — Aviso a buscadores en tiempo real (IndexNow) — HECHO (confirmado en real: HTTP 202)

**Qué hace:** cuando se crea, actualiza o borra contenido del alcance, avisa
a los buscadores compatibles con IndexNow (Bing y otros; Google no lo
soporta) en vez de esperar a que rastreen.

**Pasos:**
1. Enganchar en los mismos puntos que ya usa `Sync` (`on_product_saved`, `on_generic_post_saved`, `on_trash_or_delete`).
2. Cola con debounce (reusar patrón de `Queue`) para no notificar de más si hay varios guardados seguidos.
3. Ajuste on/off en Ajustes, clave IndexNow propia del sitio.

**Scope lock (precheck 2026-09-16):**
- Nuevo: `includes/class-indexnow.php` — genera/guarda la clave del sitio,
  sirve `https://{sitio}/{key}.txt` (mismo patrón de rewrite que ya usan
  `Llms_Txt`/`Markdown_Server`), y hace el `wp_remote_post` a IndexNow
  (no bloqueante, best-effort: si falla, no rompe el guardado del post).
- Editar `includes/class-sync.php`: llamar al aviso en los 3 puntos ya
  existentes (`on_product_saved`, `on_generic_post_saved`,
  `on_trash_or_delete`), reusando debounce vía `Queue` si aplica.
- Editar `includes/class-plugin.php`: `require` + `Indexnow::init()`.
- Editar `admin/class-admin.php` (`save_settings()` o handler propio) +
  `admin/views/tab-ajustes.php`: interruptor on/off y clave (generada o
  editable) del sitio.
- Documentación a actualizar al cerrar: `CHANGELOG.md`, `readme.txt`,
  `_dev/contexto-activo.md`, este roadmap (pasar a HECHO).
- Validación: `php -l`; prueba manual — guardar un producto del alcance y
  confirmar el ping saliente; comprobar que `{key}.txt` responde en la URL
  esperada.
- **Confirmado por el usuario en real (2026-09-16):** guardado de producto
  → acción `wookb_indexnow_notify` encolada en Action Scheduler con la URL
  correcta → ejecutada → `api.indexnow.org` responde `HTTP 202`. Verificado
  con un log temporal en `Indexnow::run_notify()` (`blocking => true` +
  `error_log()`), revertido a no bloqueante tras confirmar.
- Pendiente de decidir aparte, no bloquea el cierre de esta fase: cobertura
  de Google (IndexNow no lo soporta) requeriría la Search Console Indexing
  API, integración distinta con OAuth propio — no forma parte de esta fase.

---

## Fase 9 — Feeds especializados — HECHO (confirmado en real: enlaces en llms.txt funcionan)

**Qué hace:** exporta el catálogo en formatos que ya esperan otras
plataformas (comparadores, Google Merchant), en vez de un JSON de
propósito general (eso ya lo cubre la Fase 3).

**Implementado:**
1. `GET /wp-json/ai-knowledge/v1/feeds/products.xml` (solo si WooCommerce activo): RSS 2.0 + namespace `g:` (formato Google Merchant), reutilizando `Extractor_Woo` — mismo dato que ya se extrae para el resto del plugin, sin duplicar lógica. Sin marca/GTIN propios: `g:identifier_exists = no` explícito para que Google no rechace el feed.
2. `GET /wp-json/ai-knowledge/v1/feeds/content.json`: JSON sin paginar con el resto de post_types del alcance (no producto), reutilizando `Extractor_Base`.
3. Ambos filtrados por `Scope::resolve_ids()`, mismo criterio que la Fase 3. Salida escrita directamente (`header()` + `echo` + `exit`), mismo patrón que `Llms_Txt`/`Markdown_Server`, porque el servidor REST envuelve en JSON por defecto y estos formatos no lo son.
4. **Descubribilidad para IA/crawlers (mismo criterio que Fase 7):** sección `## Feeds` en `llms.txt` (`Llms_Txt::build()`), con enlace a `feeds/products.xml` (solo si WooCommerce) y `feeds/content.json`, junto a `## API`.
5. **Ampliación pedida por el usuario:** `<link rel="alternate">` en el `<head>` de TODAS las páginas (sitewide, no por post — un feed representa el catálogo entero, no un contenido concreto), en `Rest_Content::print_feed_links()`. Para herramientas que no leen `llms.txt` (Google Merchant en sí no lo necesita: su feed se registra a mano en Merchant Center, no hay auto-descubrimiento por crawler).

**Excluido (decisión, no pedido):** actualizar el documento OpenAPI de la Fase 7 con estas 2 rutas — son formatos fijos sin parámetros reales que documentar, y no se pidió; queda como ampliación aparte si se quiere más adelante.

**Validación:** `php -l`; prueba manual — pedir ambos feeds y comprobar estructura (XML válido para Google Merchant, JSON coherente para el resto); confirmado que `llms.txt` enlaza a los dos y los enlaces funcionan.

---

## Fase 10 — Replanteada: plan conjunto de UX del admin — EN PAUSA, pendiente de `planificar-cambio`

Fase ampliada el 2026-09-16 (segunda vez): además de Alcance/Exclusiones +
campos custom + modo "todos los CPT" (ya fusionados antes), se añade
descripción por pestaña y el rediseño Simple/Avanzada del Registro — las
tres piezas tocan la experiencia general del admin, se planifican juntas.

**1. Descripción por pestaña.** Cada pestaña del admin (Alcance,
Exclusiones, Registro, Prompt, WooCommerce, Ajustes, Carga inicial,
Visibilidad IA) debe empezar con una descripción clara de para qué sirve
— hoy varias no la tienen o la dan por hecha. Cambio de contenido, bajo
riesgo, se puede hacer pestaña a pestaña sin bloquear el resto.

**2. Rediseño Alcance/Exclusiones (antes "UX5").** El selector "Taxonomías
/ términos" es casi idéntico en `tab-alcance.php` y `tab-exclusiones.php`
(misma lista de términos, mismo diseño de chips, mismo bucle por
post_type — solo cambia si significa incluir o excluir), fácil de
confundir entre las dos pestañas.
**Confirmado por el usuario:** pestaña única, con el bloque de
taxonomías/términos renderizado UNA sola vez, y un interruptor de 3
estados por término (Incluir / Excluir / Sin decidir) en vez de dos listas
de checkboxes en pestañas separadas. **"Sin decidir" es el estado por
defecto de cada término** — igual que hoy los checkboxes de post_types
están desactivados hasta que se marcan a mano, aquí ningún término queda
incluido ni excluido hasta que el admin lo active explícitamente. Se
sustituyen `tax_terms` (incluir) y `exclude_terms` (excluir) en
`Scope::settings()` por una única estructura, ej.
`term_actions[taxonomia][term_id] = 'include'|'exclude'` (retrocompatible:
migrar los datos existentes al activar, no perder configuración ya
guardada).

**3. Campos personalizados de ACF / Meta Box / Pods.** El dato **ya es
seleccionable hoy** en Alcance (esos plugins guardan sus valores como
`post_meta` normal, y `Scope::sampled_custom_field_keys()` ya lo muestrea)
— esto es solo cosmético: mostrar la etiqueta legible del campo en vez de
la key técnica cruda, y filtrar el ruido propio de ACF (su meta "espejo"
`_nombre_del_campo`, referencia interna sin valor real). No es un hueco de
datos, es un pulido de usabilidad del selector.

**4. Modo "todos los CPT públicos" en Scope (antes idea suelta de
[`analisis-jet-geo.md`](analisis-jet-geo.md)).** Alternativa a la lista
explícita actual de post_types en Alcance: un modo "todos los CPT
públicos, presente y futuro", más un filtro de extensión sobre la lista de
post_types excluidos por defecto. Afecta directamente a cómo se dibuja la
pestaña Alcance, por eso se junta aquí.

**5. Registro: "Ajustes avanzados" por fila (2026-09-16, sustituye el plan
anterior de vista Simple/Avanzada — probado y descartado en el mismo día).**

**Historial de esta pieza:** se implementó primero una versión con dos
vistas completas (Simple/Avanzada, interruptor + selector de filas por
página) — código llegó a escribirse y luego se **revirtió entero** tras ver
el resultado: quitaba los filtros, el checkbox de selección y las acciones
por fila de la vista por defecto, cambiando demasiado la tabla que ya
funcionaba. Descartado, no se retoma.

**Enfoque definitivo, mucho más simple — una sola tabla, sin vistas
alternativas:**
- La tabla principal sigue exactamente como está hoy en su mecánica:
  filtros (Estado/Idioma/Buscar) encima, checkbox de selección por fila,
  desplegable "Aplicar" con Borrar/Regenerar seleccionados, acciones
  masivas del toolbar (Borrar todos, Reiniciar cola) — nada de esto se
  toca ni se quita.
- **Columnas siempre visibles:** Origen, Idioma (solo si hay más de un
  idioma activo, como ya se acordó), Estado, Actualizado, Enlaces, y
  Control manual **reducido a un badge** (Auto/Manual, y aviso si está
  desactualizado) — ya no la gestión completa en la celda.
- **Columna Acciones se queda** (Generar/Borrar por fila), pero **sin el
  campo numérico de límite de caracteres** — ese control ya vive dentro de
  "Ajustes avanzados", no hace falta repetirlo en la fila principal.
- El desplegable que hoy existe por fila ("Ver/editar Markdown") se
  renombra a **"Ajustes avanzados"** y pasa a contener TODO lo que hoy está
  disperso o son columnas propias: Puente, Hash, el textarea de
  ver/editar Markdown, el campo de límite de caracteres, y los botones
  "Pasar a manual"/"Volver a Auto". Nada de información se pierde, se
  reorganiza dentro de esa fila expandible en vez de ocupar columnas
  siempre visibles.
- Sin selector de filas por página nuevo, sin `view=`/`per_page=` en la
  URL, sin cambios en `Registry_Table::get_columns()`/`get_bulk_actions()`
  — la paginación sigue como está hoy (20 fijas), no forma parte de este
  cambio.

**Por qué van juntas (2, 3, 4, 5):** todas tocan la experiencia general de
selección/revisión de contenido del admin. Decidir por separado arriesga
maquetar el mismo tipo de selector o de tabla varias veces. La pieza 1
(descripciones) es independiente y de bajo riesgo, se puede adelantar sin
esperar al resto.

**Pendiente antes de tocar código:** `planificar-cambio` con las
decisiones de arriba ya propuestas — confirmar o ajustar antes de
implementar. Los puntos 2, 4 y 5 cambian cómo se resuelve/persiste el
alcance y la preferencia de vista (`Scope::resolve_ids()`,
`custom_fields_for()`, nueva `user_meta`), no son solo maquetación.

---

## Fase 11 — Gestión de crawlers de IA — HECHO (confirmado en real: robots.txt aplicado correctamente)

**Qué hace:** distingue crawlers de búsqueda/retrieval (OpenAI, Claude,
Perplexity, Google, Bing) de crawlers de entrenamiento, deja ver/ajustar
`robots.txt` en consecuencia, y permite bloquear un bot concreto de verdad
(vía `.htaccess`, no solo `robots.txt`, que es una petición educada que un
bot puede ignorar).

**Alcance confirmado por el usuario (2026-09-16): completo** — lectura +
catálogo, generador de bloque, y logs de accesos.

**Piezas:**
1. Lectura de `robots.txt` actual (solo lectura) en el panel de Visibilidad IA (Fase 6, `tab-visibilidad-ia.php`).
2. Catálogo de ~24 crawlers de IA conocidos, clasificados en 3 categorías de propósito (estándar del sector): `ai_search` (indexan para buscadores/IA), `user_requested_assistant` (visitan bajo demanda de un usuario de un asistente IA), `model_training` (solo recopilan para entrenar modelos). Filtro de extensión propio (`apply_filters`) para añadir crawlers no listados.
3. Generador de bloque de `robots.txt` a partir de preguntas sí/no al admin — nunca se escribe solo, solo se propone para copiar/aplicar.
4. **Bloqueo real de un bot concreto vía `.htaccess`:** añade una regla (`RewriteCond %{HTTP_USER_AGENT}` + `RewriteRule`, o equivalente) para el user-agent elegido. Sensible: modifica un archivo fuera del propio plugin que puede tumbar el sitio entero si se rompe la sintaxis.
5. **Logs de accesos de crawlers conocidos**, con límite de filas — requisito explícito del usuario: **bajo impacto de rendimiento, sin llenar la base de datos**. Enfoque: hook ligero (`init` o similar) que compara el User-Agent contra el catálogo conocido ANTES de cualquier registro — si no coincide con ningún user-agent del catálogo, no se guarda nada (no se registra tráfico humano ni bots desconocidos). Tabla propia con límite duro de filas (borrado de las más antiguas al superar el tope, mismo espíritu que `Registry`), no `wp_options` ni post meta.

**Requisito de seguridad explícito del usuario, aplica a `robots.txt` Y a
`.htaccess` por igual:**
- Cualquier modificación debe llevar un **aviso extra** (más fuerte que el
  aviso normal de confirmación que ya usa el plugin en otras acciones
  destructivas).
- **Descarga obligatoria de la versión actual del archivo antes de poder
  modificarlo** (backup manual en la mano del usuario, no solo una copia
  interna del plugin) — sin esa descarga hecha, el botón de aplicar no
  debe estar disponible.

**Anotación ([`analisis-jet-geo.md`](analisis-jet-geo.md)):** catálogo base de ~24 crawlers
conocidos, 3 categorías de propósito de bot — ya incorporado arriba.

**Decisiones técnicas propuestas (2026-09-16, pendientes de aprobación del usuario):**

1. **Tabla de logs:** tabla propia nueva vía `dbDelta` (`wp_wookb_crawler_log`): `id`, `bot_name` (del catálogo, no el user-agent crudo completo), `category`, `url`, `timestamp`. Tope duro de **500 filas**: al insertar la 501, se borra la más antigua (mismo espíritu que `Registry`, sin cron aparte). Sin guardar IP completa (dato personal innecesario para el propósito de "qué bot pasó por aquí").
2. **Escritura en `.htaccess`:** vía `insert_with_markers()` de WordPress (la misma función que usa WordPress core para sus propias reglas de reescritura), con un marcador propio (`# BEGIN AI Knowledge crawlers` / `# END AI Knowledge crawlers`) — evita pisar reglas de otros plugins o de WordPress, y permite quitar el bloque limpiamente si se desactiva el bloqueo.
3. **Si `.htaccess` no es editable** (permisos, o servidor nginx sin `.htaccess` real — `is_writable()` falso o archivo inexistente en Apache): no se oculta la función, se avisa con mensaje claro y se ofrece el bloque de código para que el usuario lo aplique a mano en su configuración de servidor (nginx no lee `.htaccess`, necesita su propia sintaxis — fuera del alcance de escritura automática, solo se le muestra qué añadir).
4. **Descarga obligatoria antes de modificar:** botón "Descargar copia actual" (fuerza la descarga real del archivo, no una copia interna) que, al pulsarse, marca un aviso de sesión de corta duración (transient, ~10 minutos) confirmando que se descargó. El botón "Aplicar cambios" permanece desactivado hasta que ese aviso existe — así no basta con "decir que sí", hay que haber pulsado descargar de verdad.

**Primera implementación (2026-09-16): hecha, con `php -l` OK, pendiente de
probar en real.** Archivos: `class-crawler-catalog.php`,
`class-crawler-log.php`, `class-htaccess-guard.php`, más las acciones
`admin_post` en `class-admin.php` y la UI en `tab-visibilidad-ia.php`.

**Revisión de UX tras ver la primera versión (2026-09-16) — el usuario la
encontró confusa y pidió rehacer la interfaz. Cambios de alcance
aprobados, sustituyen las piezas 3 y 4 de arriba:**

1. **Selección por bot individual, no por categoría.** En vez de 2
   checkboxes ("bloquear entrenamiento" / "bloquear Amazonbot"), una
   **tabla única estilo Registro**: una fila por cada uno de los ~28 bots
   del catálogo, con columnas Bot / Operador / Categoría / Descripción /
   Acción (selector Permitir/Bloquear, valor inicial = `default_action`
   del catálogo). Un botón "Guardar configuración de crawlers" persiste la
   elección en `Scope::settings()['crawler_actions']` (mapa
   `user_agent => 'allow'|'block'`). Esta tabla es la ÚNICA fuente de
   verdad: alimenta tanto el bloque de `robots.txt` como el de
   `.htaccess`, no se vuelve a preguntar en cada sitio.
2. **`robots.txt` pasa a tener el MISMO tratamiento que `.htaccess`**
   (cambio de decisión, antes era "nunca se escribe solo"): escritura real
   del archivo físico, con el mismo requisito de descarga obligatoria +
   aviso fuerte antes de aplicar. Nueva clase `Robots_Txt_Guard` (mismo
   patrón que `Htaccess_Guard`: `is_available()`, transient de backup
   confirmado propio y separado del de `.htaccess` — son archivos
   distintos —, `apply_block()`). Si `robots.txt` no existe como archivo
   físico todavía, se crea; si ya existe (con o sin bloque previo del
   plugin), se actualiza con `insert_with_markers()` igual que
   `.htaccess`, marcador propio.
3. **Reorganización visual — agrupar por bloque, no intercalar:** la
   pestaña Visibilidad IA reordena así (de arriba abajo):
   1. Tabla única de configuración de crawlers (pieza 1 de esta revisión).
   2. Todo lo de `robots.txt` junto: vista previa actual + descarga +
      aplicar (usa la tabla de arriba).
   3. Todo lo de `.htaccess` junto: aviso + descarga + aplicar (usa la
      misma tabla).
   4. Logs de accesos.
   El contenido ya existente de la Fase 6 (estado de exposición,
   comprobación de accesibilidad, gestión de `llms.txt` físico) se
   mantiene en la misma pestaña pero claramente separado de este bloque
   nuevo de crawlers, no mezclado.

**Revisión implementada (2026-09-16):** tabla única con `<select>`
Permitir/Bloquear por bot (se probó primero con chips/radios estilo
Registro, pero el usuario prefirió el desplegable nativo una vez arreglado
su estilo oscuro), `Robots_Txt_Guard` nueva clase con el mismo patrón que
`Htaccess_Guard`, y la pestaña reorganizada en el orden pedido.

**Ajustes visuales finales tras QA del usuario (2026-09-16):**
- Las `<option>` de los `<select>` no heredaban el tema oscuro (solo la
  caja cerrada lo tenía) — el navegador pinta la lista desplegable con su
  blanco nativo si no se le pone color explícito a `option`. Añadida la
  regla que faltaba en `wookb-theme.css`.
- Las dos tablas nuevas (config. por bot y logs) usaban `widefat striped`
  sin la clase `wp-list-table` — por eso salían con fondo blanco de
  WordPress core en vez del tema oscuro del plugin (todo el estilo oscuro
  de tablas está condicionado a esa clase). Añadida, más un contenedor con
  `overflow-x:auto` para pantallas estrechas (sigue siendo un `<table>`
  real, no divs).
- Botones deshabilitados (antes de descargar la copia) se veían con el
  color sólido de su variante como si estuvieran activos — faltaba una
  regla `:disabled` en todo el plugin (nunca hizo falta antes: es el primer
  botón deshabilitado que tiene el plugin).
- Espaciado: `.wookb-card h2 { margin-top: 0; }` es una regla pensada para
  un único título por pestaña; con varios `<h2>`/`<h3>` seguidos en esta
  sección quedaban todos pegados al `<hr>` de arriba. Solución con una
  clase propia `.wookb-crawler-section` (scoped, sin tocar el resto de
  pestañas).
- `robots.txt` actual + vista previa del bloque a insertar: pasan a verse
  lado a lado (`.wookb-crawler-compare`, se apila solo en pantalla
  estrecha) en vez de apiladas y con texto duplicado.
- Aviso "descarga la copia primero" movido encima de los dos botones
  (antes solo estaba pegado al de "Aplicar"), mismo criterio en
  `robots.txt` y `.htaccess`.
- Descarga por `fetch()` en `admin.js`: dispara el archivo igual, pero
  habilita el botón "Aplicar" hermano al momento sin recargar la pestaña
  (con fallback a submit normal si `fetch` fallara). La protección real
  sigue siendo 100% server-side (el transient que comprueba
  `class-admin.php` al aplicar); esto es solo comodidad de interfaz.
- Título de la sección sin "(Fase 11)" — el plugin no expone su propio
  roadmap interno al usuario final.

---

## Fase futura (sin número) — Tags dinámicos en prompts y textos manuales — IDEA, NO PLANIFICADA

**Qué haría:** placeholders tipo `{post.title}`, `{post.excerpt}`,
`{post.meta key="..."}`, `{product.price}`, `{product.stock}`, `{site.name}`
usables en el "Instrucciones adicionales del prompt" (`extra_prompt`) y en
el texto manual de un documento (Fase 1), sustituidos por el dato real ya
disponible en `Extractor_Base`/`Extractor_Woo` antes de mandarlo a OpenAI o
de publicar el texto manual.

**Origen:** [`analisis-jet-geo.md`](analisis-jet-geo.md), punto 4. No está planificada: requiere
`evaluar-cambio`/`planificar-cambio` propios cuando se quiera abordar.

---

## Fase futura (sin número) — Onboarding por pasos — IDEA, NO PLANIFICADA

**Qué haría:** asistente de primera configuración con estado persistente,
un paso por bloque (alcance/content-settings, robots.txt, llms.txt,
Markdown, logs de crawlers), cada paso validando su propio cambio antes de
aplicarlo — en vez de configurar pestaña por pestaña sin guía, como hoy.

**Origen:** [`analisis-jet-geo.md`](analisis-jet-geo.md), punto 5. No está planificada: requiere
`evaluar-cambio`/`planificar-cambio` propios cuando se quiera abordar.

---

## UX pendiente de rediseño (detalle completo en [`qa-resultados-fase-0-a-5.md`](qa-resultados-fase-0-a-5.md))

- **UX2 — "Carga inicial" confusa — HECHO.** Botón único sustituido por
  "Generar pendientes" (comportamiento de siempre, seguro repetir) +
  "Reiniciar todo" (fuerza regenerar también lo ya sincronizado, con
  confirmación fuerte). Límite diario/tamaño de lote/debounce movidos aquí
  desde Ajustes, con guardado propio (`wookb_save_queue_settings`,
  `Scope::update_settings()` con merge — no reescribe el resto de Ajustes).
  De paso: contador "Total de documentos" visible en todas las pestañas, no
  solo en Registro; y nuevo ajuste general "Largo del texto generado
  (caracteres)" en Ajustes (antes fijo en código, `Generator::BODY_CHAR_LIMIT`,
  sin ningún sitio del admin donde verlo o cambiarlo).
- **UX3 — WooCommerce: checkboxes + prompt por campo — PENDIENTE.** Cambio
  de arquitectura de datos (nueva estructura para selección por campo y
  texto manual por campo suelto), requiere `rol-analista` antes de tocar
  código.
- **UX4 — Visibilidad del botón "Generar documentos de tienda" — PENDIENTE.**
  Revisar si quedó poco visible/mal etiquetado en la pestaña WooCommerce.
- **Rediseño Alcance/Exclusiones + campos custom + modo "todos los CPT":
  ver Fase 10 replanteada** (fusionado ahí el 2026-09-16, no se repite
  aquí).

---

## Explícitamente descartado (decisión ya tomada, no una fase)

- Prompt específico por documento — se descartó a favor de solo texto manual (Fase 1).
- Generación de `.md` opcional según tráfico — no es un problema real (generación async, servido como archivo estático); se mantiene siempre activa.
- Pivote a normalizador puro sin IA como núcleo — se mantiene IA + Support Genix como núcleo del producto.
