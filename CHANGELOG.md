# Changelog

Todas las modificaciones relevantes de este plugin se documentan en este archivo.

## [1.0.8.4] - 2026-09-15/16

Ver `_dev/roadmap.md` para el plan completo por fases y `_dev/decisiones.md`
para el porqué de la identidad/alcance. Este plugin sigue orientado a IA +
Support Genix como núcleo; todo lo de abajo es capa añadida encima.

### Fase 10, piezas 2/3/4: pestaña "Contenido" (2026-09-16)

- `tab-alcance.php` + `tab-exclusiones.php` fusionadas en una sola pestaña
  "Contenido" (`tab-contenido.php`). Registro pasa a ser la primera
  pestaña y la que carga por defecto.
- Taxonomías/términos: bloque único (antes duplicado en las dos
  pestañas), checkbox simple por término (marcado = incluido). Taxonomías
  técnicas (`product_type`, `post_format`) ocultas por ser ruido.
- IDs sueltos: dos campos, "IDs a incluir" / "IDs a excluir".
- Nuevo modo "Todos los tipos públicos" para CPTs, con excepciones,
  alternativa a la lista explícita de siempre.
- Campos custom: etiqueta legible (ACF/Meta Box/Pods) junto a la key
  técnica cuando se puede resolver.
- Migración automática y retrocompatible de la configuración guardada
  (`tax_terms`/`exclude_terms`/`extra_ids`/`exclude_ids` → `term_actions`/
  `id_actions`), sin que el usuario tenga que volver a configurar nada.
- Borrar un documento (fila individual, selección o "Borrar todos") añade
  automáticamente su origen a "IDs a excluir", para que el cron no lo
  regenere solo. Mensajes de confirmación explican esto.
- **Fix (bug real, no de esta sesión sino heredado de la pieza 5):** el
  borrado en lote del Registro no funcionaba — la fila expandida "Ajustes
  avanzados" imprimía formularios anidados dentro del formulario grande
  de selección múltiple (HTML inválido), rompiendo el envío de las filas
  seleccionadas. Corregido.

### Fase 10, pieza 5: "Ajustes avanzados" en el Registro (2026-09-16)

- Columnas Puente y Hash ya no son siempre visibles: se movieron dentro
  del desplegable por fila, renombrado de "Ver/editar Markdown" a
  "Ajustes avanzados".
- Columna Acciones: quitado el campo de límite de caracteres puntual (uso
  único, no guardado); "Generar" usa ahora siempre el límite guardado en
  "Ajustes avanzados" o el general de Ajustes.
- Nuevo selector "por página" (20/50/100) en la toolbar del Registro.
- Reordenado el bloque "Generar por ID o URL" antes de la toolbar de
  filtros.
- Fix: botón de modo oscuro/claro sin emoji (corrupción de encoding).

### Rediseño de CSS/admin (2026-09-16)

- Sistema único de 5 variantes de botón (neutro, primario, peligro, éxito,
  info), todas con variables reales de Tabler y mismo tamaño
  (`padding: .5625rem 1rem; font-size: .875rem`, valor real de `.btn` de
  Tabler). Cubre también la clase `delete` que genera `submit_button()` de
  WordPress core, para no tener dos convenciones de "botón rojo".
- Sin bordes decorativos en cajas/paneles/botones/chips — se diferencian
  por color de fondo sólido. Bordes solo en campos de formulario y
  separadores de fila de tabla (funcionales).
- Hover/foco de botones con `filter: invert(1)` y `!important`: WordPress
  core (`.wp-core-ui .button:hover`) empata en especificidad y puede ganar
  si no se fuerza explícitamente.
- Tabla del Registro: fila expandida "Ver/editar Markdown" ligada
  visualmente a su fila (mismo fondo, sin línea divisoria entre ambas);
  columna Acciones (límite/Generar/Borrar) apilada en vertical, mismo ancho.
- Nuevo `_dev/guia-estilo-visual.html`: carga el CSS real del plugin
  (`tabler.min.css` + `wookb-theme.css`), no una copia — única fuente de
  verdad visual, en claro y oscuro. Regla permanente documentada en
  `_dev/decisiones.md`: ningún color/estilo nuevo sin pasar antes por ahí.

### Cambiado
- Renombrado de identidad (Fase 0): nombre visible del plugin de "WOO Knowledge Base Generator" a **"AI Knowledge & Visibility"**, y Text Domain de `woo-kb-generator` a `ai-knowledge`. El slug interno de la página de admin (`page=woo-kb-generator`), las constantes `WOOKB_*` y el namespace `WOOKB\` se mantienen sin cambios (decisión explícita: no tocar identificadores internos sin necesidad).
- Carpeta del plugin y repositorio Git renombrados a `ai-knowledge` (antes `woo-kb-generator`).
- Botón "Generar/actualizar documentos de tienda" movido de Ajustes a la nueva pestaña WooCommerce.

### Añadido
- **Fase 1 — Control manual de documentos:** modo por documento `auto`/`manual` (texto fijado a mano que ya no se regenera solo), límite de caracteres persistido por documento, aviso de "origen actualizado" (`stale`) en el Registro, en `admin_notices` y en la barra de admin, y botón "Añadir a la base de conocimiento" en el editor de cualquier post/CPT marcado en Ajustes.
- **Fase 2 — Pestaña WooCommerce:** tienda, envíos, impuestos/IVA (nuevo, no existía antes), pagos, condiciones de venta y devoluciones detectados automáticamente desde la configuración real de WooCommerce; plazo de entrega y notas legales rellenables a mano; vista previa del Markdown publicado por idioma.
- **Fase 3 — API REST de contenido:** `GET /wp-json/ai-knowledge/v1/content/{id}` y `GET /wp-json/ai-knowledge/v1/{post_type}` (listado paginado), solo lectura, sin IA, filtrado por Alcance, sin filtrar si un contenido existe pero está excluido.
- **Fase 4 — Descubrimiento de Markdown:** `<link rel="alternate" type="text/markdown">` en el `<head>` del contenido ya sincronizado.
- **Fase 5 — JSON-LD:** `Product`/`Offer` (WooCommerce) o `Article` (resto) en el `<head>`, cediendo el schema a WooCommerce/RankMath/Yoast/AIOSEO cuando ya lo cubren, para no duplicar.
- **Fase 6 — Panel "Visibilidad IA":** nueva pestaña con estado de exposición (llms.txt, Markdown, JSON, JSON-LD) con enlace a un ejemplo real de cada uno; contador de contenido pendiente de sincronizar; selector cerrado de contenido ya sincronizado + botón "Comprobar accesibilidad" que lee `robots.txt` y noindex/`X-Robots-Tag` en vivo y avisa de contradicciones entre ambas señales; detección y explicación de un `llms.txt` físico que tape al generado por el plugin, con vista previa, fecha de modificación y botón para borrarlo (confirmación explícita, el plugin sigue sirviendo el suyo generado al vuelo).
- **UX2 — Carga inicial rediseñada:** botón único sustituido por "Generar pendientes" (comportamiento de siempre) y "Reiniciar todo" (fuerza regenerar también lo ya sincronizado, confirmación fuerte); límite diario/tamaño de lote/debounce movidos aquí desde Ajustes con guardado propio; el resumen "Total de documentos" ahora es visible en todas las pestañas, no solo en Registro.
- **Ajuste general "Largo del texto generado (caracteres)"** en Ajustes: antes fijo en código (`Generator::BODY_CHAR_LIMIT = 1000`), sin ningún sitio del admin donde verlo o cambiarlo; ahora editable, con el límite por documento del Registro (Fase 1) teniendo prioridad si está puesto.
- **Fase 7 — API pública documentada:** `GET /wp-json/ai-knowledge/v1/openapi.json`, documento OpenAPI 3.0 real (escrito a mano, no el índice nativo de WordPress) describiendo `/content/{id}` y `/{post_type}` con sus parámetros y el schema `ContentItem`. `llms.txt` enlaza ahora a este documento en una sección `## API` propia, para que los crawlers que ya lo leen lo descubran sin depender de visitarlo por su cuenta.
- **Fase 8 — Aviso a buscadores en tiempo real (IndexNow):** al crear, actualizar o borrar contenido del alcance, se avisa a buscadores compatibles con IndexNow (Bing y otros; Google no lo soporta) en vez de esperar a que rastreen. Interruptor on/off en Ajustes, clave del sitio autogenerada y servida por rewrite (`/{key}.txt`), aviso no bloqueante con el mismo debounce que ya usa la cola de generación de documentos. Verificado en real: respuesta `HTTP 202` de `api.indexnow.org` al guardar un producto.
- **Fase 9 — Feeds especializados:** `GET /wp-json/ai-knowledge/v1/feeds/products.xml` (formato Google Merchant, solo si WooCommerce activo, reutilizando `Extractor_Woo`) y `GET /wp-json/ai-knowledge/v1/feeds/content.json` (JSON sin paginar con el resto del contenido del alcance). Enlazados desde `llms.txt` (sección `## Feeds`) y desde el `<head>` de todas las páginas (`<link rel="alternate">` sitewide, no por post, para herramientas que no leen `llms.txt`). Verificado en real: enlaces funcionando en `llms.txt` y en el código fuente de las páginas.
- **Fase 11 — Gestión de crawlers de IA:** nueva sección "Gestión de crawlers de IA" en la pestaña Visibilidad IA, con catálogo de ~28 crawlers conocidos (OpenAI, Anthropic, Google, Meta, Apple, Bing, Perplexity, DuckDuckGo, You.com, Cohere, ByteDance, Amazon, xAI, y agregadores de datasets), clasificados en 3 categorías de propósito (búsqueda/citas IA, uso bajo demanda, entrenamiento de modelos) con una acción Permitir/Bloquear configurable por bot — tabla única que alimenta tanto el bloqueo por `robots.txt` como por `.htaccess`. Ambos archivos se pueden aplicar de verdad (no solo copiar/pegar), siempre exigiendo primero descargar una copia real del archivo actual (dos protecciones: botón deshabilitado en pantalla y comprobación en el servidor), con vista previa del bloque exacto que se insertará antes de aplicar. Escritura vía `insert_with_markers()` de WordPress: sustituye el bloque propio en cada aplicación sin duplicar ni tocar el resto del archivo. Logs de accesos de crawlers conocidos (tabla propia, tope de 500 filas, sin IP, sin registrar tráfico humano ni bots desconocidos). Verificado en real: aplicación de `robots.txt` confirmada funcionando.

### Corregido
- El botón "Generar" de una fila del Registro no reflejaba el límite de caracteres ya guardado para esa fila, mostraba siempre el valor por defecto general.
- Salto visual de claro a oscuro en cada recarga del panel (el tema se aplicaba con jQuery al final de la carga; ahora se fija con un script inline síncrono antes de pintar).
- **Los `.md` públicos se descargaban en vez de abrirse en el navegador** en servidores nginx (como Local by Flywheel): antes se enlazaba directo al archivo físico, cuyo `Content-Type` depende de la configuración MIME del servidor. Ahora se sirven por una ruta virtual de WordPress (`/ai-knowledge-doc/{lang}/{slug}.md`, clase `Markdown_Server`) que fija `Content-Type: text/plain` desde PHP, funciona igual en Apache o nginx.
- JSON-LD duplicado con el schema nativo de WooCommerce (`WC_Structured_Data`, siempre activo si WooCommerce lo está, independientemente de RankMath): ahora se detecta y se cede el schema, igual que ya se hacía con RankMath/Yoast/AIOSEO.
- Texto de los `<select>` del admin invisible en hover/foco en modo oscuro: `.wp-core-ui select:hover` de WordPress core forzaba `color:#1e1e1e` con la misma especificidad que la regla del tema — mismo caso ya conocido con los botones, resuelto igual (`!important`, ver `_dev/decisiones.md`).

### Pendiente de esta versión (ver `_dev/qa-resultados-fase-0-a-5.md`)
- Confirmar si el botón "Añadir a la base de conocimiento" del editor da feedback suficiente (reportado como "no se ve nada" en la primera ronda de QA).
- Borde/zona oscura visible tras el fix del tema (reportado, no reproducido aún en código).
- Rediseño de UX pendiente de acordar: editor de texto del Registro, claridad de "Carga inicial", selección de campos de WooCommerce igual que los posts, notas legales.

## [1.0.7] - 2026-08-22

### Añadido
- Skin visual completo del panel de administración (Alcance, Exclusiones, Registro) basado en Tabler: tema oscuro/claro, checkboxes y tabla legibles en ambos modos, chips clicables para listas de CPTs/taxonomías/campos custom.
- Auto-continuación de la cola de generación: "Reiniciar cola" ya no requiere pulsar varias veces, avanza sola en lotes de 20 hasta vaciarse.
- Refresco automático de las reglas de enrutado (`flush_rewrite_rules()`) al terminar la cola o generar por ID/URL suelto, como red de seguridad.
- Cabecera del plugin actualizada (autoría, URI, text domain).
- Documentación: `readme.txt`, este `CHANGELOG.md` y un documento comercial de referencia.

### Corregido
- **Bug de origen de la "Carga inicial":** `run_seed_batch()` encolaba el mismo ID de contenido para todos los idiomas activos sin comprobar si existía traducción real, generando filas de cola "huérfanas" que nunca se procesaban (quedaban en cola para siempre). Ahora resuelve la traducción real de cada idioma antes de encolar, igual que ya hacía correctamente el sincronizador de altas/ediciones.
- **Enlaces del chatbot redirigiendo a la home:** las reglas de enrutado del tipo de contenido interno `sgkb-docs` quedaron desactualizadas tras una limpieza de contenido, haciendo que WordPress no reconociera esas URLs y cayera en la página de inicio por defecto. Corregido con un refresco de permalinks; ahora se previene automáticamente.
- Registro: el título "Estado" de la cabecera de la tabla desaparecía por una regla CSS demasiado amplia que también afectaba a la cabecera, no solo a la celda de datos.
- Registro: la columna "Estado" mostraba un badge con aspecto de doble contorno por colisión de nombre entre la clase nativa de WordPress y un componente del framework visual (Tabler) con el mismo nombre; ahora es texto plano.
- Avisos (notices) del panel invisibles en modo oscuro (texto negro fijo sobre fondo oscuro).
- Avisos de plugins ajenos (WooCommerce, Newsletter, WPML) apareciendo dentro de la caja del plugin por compartir la clase genérica `wrap` de wp-admin.

## [1.0.1] - 2026-08-22

### Añadido
- Límite de caracteres configurable por fila en el Registro (uso puntual, no se guarda).
- Selección múltiple de filas en el Registro con acciones en lote (borrar, regenerar).
- Generación directa por ID o URL, sin depender de que la fila exista en el Registro ni de esperar al cron.
- Sincronización con Support Genix **Lite**, además de la versión Pro (antes solo se parcheaba Pro).

### Corregido
- Colisión entre las acciones en lote del Registro y el enrutado `admin-post.php` (los formularios competían por el mismo campo `action`).

## [1.0.0] - 2026-08-20 a 2026-08-21

### Añadido
- Plugin completo: generación de documentos `.md` desde productos WooCommerce y otros CPTs.
- Cola de generación con límite diario configurable, vía Action Scheduler (o WP-Cron como alternativa).
- Integración WPML: un documento por idioma activo, con `trid` propio para los documentos `sgkb-docs` (evita colisiones de traducción con el contenido original).
- Publicación dinámica de `/llms.txt` desde el registro interno de documentos (no un archivo estático).
- Sección de FAQ pública editable, incluida en `/llms.txt`.
- Documentos compuestos automáticos de información de tienda (cómo comprar, condiciones, envío y pago, catálogo), uno por idioma, generados desde la configuración real de WooCommerce.
- Conexión con Support Genix: creación/actualización de posts `sgkb-docs`, y asistente de redacción del prompt de sistema del chatbot sincronizado con el ajuste nativo de Genix.
- Redirección automática de visitas directas a los documentos internos hacia la página/producto real, según el idioma real de navegación.
- Varias mitigaciones de comportamiento del chatbot: filtro de relevancia sobre resultados de búsqueda (para activar la recuperación por historial de conversación cuando la búsqueda inicial encuentra solo resultados irrelevantes), manejo de idiomas no soportados por el sitio, límite configurable de "documentos relacionados" mostrados en el chat, resúmenes en formato de bullets en vez de prosa.
- Paridad de interfaz entre las pestañas Alcance y Exclusiones, con enlaces cruzados entre idiomas.
