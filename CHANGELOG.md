# Changelog

Todas las modificaciones relevantes de este plugin se documentan en este archivo.

## [1.1.3.5] - Desarrollo - 2026-09-23

### Registro: generación instantánea, prompt propio y auditoría de publicados

- "Generar", "Guardar límite", "Guardar cambios" y "Volver a Auto" ya no
  recargan la página (AJAX con fallback tradicional si no hay JavaScript).
- "Ajustes avanzados" de cada documento se divide en dos pestañas:
  Contenido y Prompt.
- Nuevo prompt propio por documento e idioma (columna `custom_prompt`):
  instrucciones de estilo/enfoque que nunca sustituyen los datos reales; si
  está vacío, se usa el prompt genérico de siempre. Desactivado mientras el
  documento esté en modo Manual (con aviso explicando por qué).
- "Guardar cambios" solo se activa cuando hay ediciones reales sin guardar.
- Corregido: un producto/página que pasaba de "Publicado" a "Borrador" sin
  usar la papelera se quedaba visible en el Registro y en `/llms.txt`
  indefinidamente. Ahora se retira de inmediato, igual que ya pasaba al
  moverlo a la papelera.
- Todos los avisos de guardado del admin pasan a notificaciones flotantes
  (arriba a la derecha, desaparecen solas a los 10 segundos).

### Genix

- Nueva sección "Artículos exclusivos de Genix" en la pestaña Genix (antes
  "Chatbot", renombrada): permite publicar en `/llms.txt` artículos escritos
  directamente en Support Genix que no tienen ya su propio documento en
  este plugin, copiando su contenido tal cual (sin IA). Un artículo marcado
  en Genix como "solo para uso del chatbot" nunca puede hacerse público
  desde aquí. Salen agrupados en `/llms.txt` bajo su propia sección
  "Documentación".
- `sgkb-docs` (los documentos internos de Genix) ya no puede elegirse como
  tipo de contenido a documentar, ni en la pestaña Contenido, ni en el botón
  del editor, ni en el modo "todos los tipos públicos".
- Corregida una regresión previa que había desactivado el puente con Genix
  para el chatbot en el pipeline general de documentos.

### Documentación

- Actualizada toda la documentación de usuario (`docs/`) con lo anterior.

## [1.1.3.1] - Desarrollo - 2026-09-20

### Internacionalización y documentación

- Añadidos y sincronizados los catálogos de interfaz en español, catalán,
  alemán, inglés y francés, incluyendo sus archivos `.po`, `.mo` y JSON.
- Actualizados los índices de documentación pública e interna con los idiomas
  disponibles y un canal de contacto para solicitar o corregir traducciones.
- Reorganizada la documentación de desarrollo: los planes y materiales
  temporales quedan en `_dev/temp/`.

## [1.1.3] - 2026-09-20

### Internacionalización

- Añadida la carga del text domain `ai-knowledge` desde `languages/`.
- Internacionalizados los textos dinámicos de `assets/admin.js` mediante
  `wp.i18n` y `wp_set_script_translations()`.
- Incluidos catálogos PHP y JSON JavaScript en español, catalán, alemán, inglés
  y francés.
- La documentación del administrador busca primero `docs/{locale}/` y vuelve
  a la documentación española de `docs/` cuando no existe traducción.

### Crawlers y reglas del servidor

### Crawlers y reglas del servidor

- Ampliado el catálogo de crawlers con filtros por tipo y estado.
- Reglas `.htaccess` agrupadas y ordenadas antes de WordPress.
- `Amazonbot` permitido por defecto; eliminado el estado «Sin decidir».
- `llms.txt` incluye la fecha ISO 8601 de su última generación.

## [1.1.2] - 2026-09-18

### Documentación contextual en el admin

- Los títulos `h2` y `h3` con sección documentada muestran un botón `?` para
  abrir el popup y saltar directamente al apartado correspondiente.
- Los enlaces internos con ancla mantienen la navegación dentro del popup.
- El popup deja interactuar con el contenido lateral y conserva su estado al
  pulsar los accesos de sección.
- Ajustado el contraste, tamaño y estado hover de los botones de navegación.

## [1.1.1] - 2026-09-18

### UX del administrador y documentación

- La documentación de cada pestaña vive en `docs/` y se puede abrir desde el
  admin en un panel lateral de lectura.
- Los guardados simples y las generaciones/pulidos con IA usan AJAX, con
  fallback tradicional, mensajes de estado y protección contra doble envío.
- Los botones AJAX muestran un spinner mientras se procesa la petición.

## [1.1.0.2] - 2026-09-18

### Visibilidad IA y archivos de rastreo

- `llms.txt` se gestiona como archivo físico y se regenera cuando cambian
  los documentos o los datos que lo alimentan.
- Visibilidad global para permitir que los bots bloqueados lean solo
  `/llms.txt` y reciban `404` en el resto del sitio mediante `.htaccess`.
- `robots.txt` y `.htaccess` comparan las reglas existentes, conservan las
  externas y comentan las que contradicen la configuración activa.
- La pantalla muestra el archivo actual y la propuesta completa. El
  `.htaccess` se puede copiar o descargar sin sobrescribirlo automáticamente.
- Documentación de Visibilidad IA, roadmap y vista de estado actualizadas.

## [1.1.0.1] - 2026-09-17

### Fix: Alcance vacío para todos los post_types

- `Scope::resolve_ids()` combinaba las taxonomías con términos incluidos
  (p.ej. `category` para posts y `product_cat` para productos) con `AND`
  implícito de `WP_Query`, en vez de tratarlas como alternativas. Con
  ambas configuradas a la vez, el alcance quedaba vacío para todo:
  generación de documentos, `/llms.txt` y los feeds de Fase 9
  (`/feeds/products.xml` y `/feeds/content.json`).
- Corregido añadiendo `'relation' => 'OR'` cuando hay más de una taxonomía
  con términos incluidos.

## [1.1.0] - 2026-09-17

Ver `_dev/roadmap.md` para el plan completo por fases y `_dev/decisiones.md`
para el porqué de la identidad/alcance. Este plugin sigue orientado a IA +
Support Genix como núcleo; todo lo de abajo es capa añadida encima.

### Auto-actualización desde GitHub Releases

- El plugin ahora aparece en Dashboard → Actualizaciones y se instala con
  un clic, igual que un plugin del repositorio oficial. Mismo patrón que
  AuthGate: consulta la última release pública de `22MW/ai-knowledge` cada
  hora, sin licencias ni servidor propio.
- Incluye `_dev/deploy-release.sh` para publicar releases (uso interno,
  no forma parte del ZIP distribuido).

### Documentación pública y técnica

- Documentación completa en `_dev/docs/`: índice comercial, guía de
  instalación y un documento por cada pestaña del admin, enlazados entre
  sí y con preguntas frecuentes. Pendiente de sustituir capturas/vídeos
  marcados antes de publicar en la web.
- `_dev/documentacion-tecnica.md`: documento técnico para desarrolladores
  (arquitectura, namespace, base de datos, API REST), interno al plugin.
- Corregido `readme.txt`: las instrucciones de instalación mencionaban las
  pestañas "Alcance" y "Exclusiones", ya fusionadas en "Contenido".

### Cierre del rename de identidad interno (2026-09-16)

- Eliminado todo rastro de "woo-kb-generator" del código: archivo principal
  renombrado a `ai-knowledge.php`, namespace `WOOKB\` → `AIKB\`, constantes
  `WOOKB_*` → `AIKB_*` y slug de menú admin `page=woo-kb-generator` →
  `page=ai-knowledge`. Corregido también `readme.txt`, que indicaba subir
  la carpeta `woo-kb-generator` en vez de `ai-knowledge`.
- Sin cambios, por compatibilidad con instalaciones existentes: la tabla de
  base de datos `wookb_documents`, las funciones de activación/desactivación
  y la opción `wookb_db_version`.
- Importante para quien actualice desde una versión anterior: al renombrarse
  el archivo principal, WordPress desactiva el plugin automáticamente y hay
  que reactivarlo a mano tras la actualización.

### Navegación del administrador más clara (2026-09-16)

- «Carga inicial» pasa a llamarse «Generación masiva», porque también se usa
  después de la primera carga.
- Orden final: WooCommerce, Chatbot, Visibilidad IA, Generación masiva y
  Ajustes; las pestañas dependientes siguen ocultas si falta su plugin.
- Revisados los textos de Visibilidad IA: eliminadas referencias internas a
  fases, simplificado el lenguaje técnico y aclarado el alcance real de los
  bloqueos mediante robots.txt y .htaccess.

### Resumen de Negocio más útil por defecto (2026-09-16)

- Separados el campo fuente «Enfoque del negocio» y el resumen público usado
  por llms.txt: guardar o vaciar uno ya no sobrescribe el otro.
- Los valores existentes siguen disponibles como compatibilidad inicial hasta
  que se guarda por primera vez el resumen independiente.
- Cuando no se indican instrucciones propias, el resumen usa prosa natural,
  evita repetir datos para alargar el texto y mantiene los correos sin escapes.
- La extensión se adapta a la información real guardada, sin inventar contenido.

### Conectores nativos de WordPress 7.0 (2026-09-16)

- Nuevo origen de IA mediante **Ajustes → Conectores** de WordPress 7.0,
  limitado a proveedores configurados de Anthropic, OpenAI y Google.
- Selector de modelo real: modo Automático recomendado o modelo explícito
  descubierto desde los conectores disponibles.
- Todas las generaciones del plugin y la traducción auxiliar del chatbot
  usan un transporte central común. Support Genix se mantiene como origen
  alternativo, sin fallback silencioso entre ambos.
- Retirada la opción de clave propia de la interfaz y del código activo. Los
  valores históricos guardados no se borran automáticamente de la base de
  datos.
- En WordPress anterior a 7.0 no se muestra la opción de Conectores y se
  conserva Support Genix.

### Pestaña WooCommerce: selección editable + pulido con IA (2026-09-16)

- Envíos, impuestos/IVA, métodos de pago y categorías del catálogo pasan
  de listarse automáticamente a ser **seleccionables** (checkbox = entra
  en el documento). Envíos no entraba antes en el documento en absoluto —
  ahora sí, con nueva sección "## Envíos" (envío gratis con mínimo
  configurado se detecta solo).
- "Detectado automáticamente" (nombre, moneda, país, condiciones de venta,
  devoluciones) deja de ser solo lectura: ahora es un snapshot editable,
  precargado de WooCommerce la primera vez pero que ya no se pierde si
  WooCommerce cambia o se borra la página.
- Nuevo campo "Contacto y horario de la tienda online", independiente del
  contacto general de Negocio (con fallback si se deja vacío).
- Nuevos campos "Pedido mínimo/envío gratis" y "Recogida en tienda", solo
  usados como respaldo si WooCommerce no tiene ya el dato como método de
  envío real (si lo tiene, se detecta solo).
- Nuevo botón "Pulir redacción con IA" en los documentos generados de
  tienda/catálogo: mejora solo la redacción, sin cambiar ningún dato
  (documentos legales/de pago siguen construyéndose de forma determinista,
  decisión ya tomada antes por riesgo de alucinación).
- Las instrucciones opcionales de pulido pueden ser una indicación breve o
  un prompt completo y prevalecen sobre el formato predeterminado. Los datos
  siguen protegidos: si el prompt solicita información ausente, se omite.
  Las respuestas que copian instrucciones internas se rechazan sin publicar.
  Un texto completo generado externamente se puede pegar en modo manual desde
  Registro.
- Los dos bloques de pulido de WooCommerce adoptan el orden visual de Negocio:
  título `h2`, explicación, instrucciones, acción y resultado. El código de
  idioma solo aparece en el título cuando el sitio tiene varios idiomas.
- Estilo unificado con Contenido/Negocio (sin cajas de fondo, una sola
  tabla).
- Fix: selector de métodos de pago usaba `absint()` sobre IDs de texto
  (`'bacs'`, `'paypal'`...), los destruía a `0`.

### Reestructuración Negocio/Chatbot/FAQs (2026-09-16)

- Ajustes generales deja de mostrar y usar el antiguo campo "Instrucciones
  adicionales del prompt". El límite de "Documentos relacionados" se mueve
  a Chatbot y se guarda desde un formulario propio, sin arriesgar el resto de
  ajustes generales.
- Negocio y FAQs adoptan el mismo contrato de instrucciones que los
  documentos WooCommerce: prompt breve o completo, prioridad sobre formato,
  orden y estructura, pero nunca sobre los datos reales. Documento/datos e
  instrucciones viajan delimitados y las respuestas que copian instrucciones
  internas se rechazan antes de mostrar el borrador.
- Prompt genérico de generación de documentos corregido: ya no dice
  "bodega/tienda online" (sesgado al sitio de pruebas), ahora
  "negocio/tienda online" — afecta a todos los clientes del plugin.
- Nueva pestaña **Negocio** (siempre visible): datos puros del negocio,
  independientes de WooCommerce/Genix. Incluye generación con IA del
  resumen para `llms.txt`, con campo "Información extra" opcional.
- Pestaña **Chatbot** (antes "Prompt"): ahora solo visible en el menú si
  Support Genix está activo (mismo patrón que la pestaña WooCommerce),
  con guardas también en los handlers por si se accede por URL directa.
- Nueva pestaña **FAQs** (movida desde Ajustes): generación con IA,
  información extra opcional, y ahora **multiidioma real** — un
  documento/`.md` por idioma activo, con fila propia en Registro y enlace
  normal desde `llms.txt` (ya no bloque inline aparte). Migración
  automática del FAQ antiguo (mono-idioma) al primer idioma activo.
- Fix: sufijo "(ES)"/"(EN)" en encabezados de `llms.txt`, columna Idioma
  del Registro y filtro de idioma — ahora solo aparecen si el sitio es
  multiidioma de verdad (antes siempre visibles, incluso con un solo
  idioma).
- `save_answers()` del cuestionario ahora parte de lo ya guardado en vez
  de sobreescribir con vacío las keys no presentes en cada envío —
  evita que guardar una pestaña borre datos de otra.
- Orígenes editables (chatbot-system-prompt.md, FAQ fuente) movidos de la
  raíz del plugin a `wp-content/llm/`, junto al resto de contenido
  generado. `chatbot-system-prompt.md` de la raíz, que estaba trackeado en
  git con datos reales del cliente, se destrackeó.

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

- El editor muestra siempre "Guardar cambios": desde Auto guarda el texto y
  pasa a Manual; desde Manual guarda y permanece en Manual. En modo Manual
  se mantiene además "Volver a Auto".
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

### Pendiente de esta versión (ver `_dev/temp/qa-resultados-fase-0-a-5.md`)
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
