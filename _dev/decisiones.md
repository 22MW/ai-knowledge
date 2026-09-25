# Decisiones

## 2026-09-14 — Repositorio y proceso

- El plugin se desarrolla como repositorio Git independiente.
- La memoria viva del proyecto se guarda en `_dev/`.
- `.kilo/` es solo lectura y no forma parte de la arquitectura del plugin.
- No se implementa una fase sin plan y validación de la fase anterior.

## 2026-09-14 — Alcance del producto (decisión final, reemplaza la anterior)

Se evaluaron tres caminos (ver análisis en el historial de esta tarea):

- A. Pivote completo a normalizador determinista (sin IA en el núcleo).
- B. Dos productos separados (motor IA actual + normalizador nuevo aparte).
- **C. Elegida: mantener la generación vía IA (OpenAI) como núcleo del
  producto, con Support Genix como integración principal de chatbot, y
  añadir encima una capa de visibilidad/exposición para crawlers y agentes
  IA** (JSON estructurado sin IA, Markdown público descubrible, JSON-LD,
  panel de diagnóstico).

Motivo: el generador IA + Support Genix es la parte madura y probada del
plugin; no se descarta ese trabajo. La capa de visibilidad se construye
reutilizando `Extractor_Base`/`Extractor_Woo` y `Scope`, sin tocar el prompt
ni el flujo de generación existente.

## 2026-09-14 — Identidad

- Nombre visible: **AI Knowledge & Visibility**
- Nombre corto: **AI Knowledge**
- Slug/carpeta objetivo: `ai-knowledge`
- Text Domain objetivo: `ai-knowledge`
- Namespace de código: se mantiene `WOOKB` (y `wookb_*`/`wookb_documents`) por
  ahora, para no migrar identificadores sin necesidad real.
- Repositorio GitHub objetivo: `ai-knowledge` (pendiente de renombrar).

(Sustituye la identidad aprobada antes: "AI Visibility for WordPress" /
`ai-visibility`, que asumía el pivote a normalizador puro, descartado.)

## 2026-09-16 — Diseño visual del admin (decisión permanente)

Guía completa con ejemplos claro/oscuro en `_dev/guia-estilo-visual.html`.
Antes de crear cualquier color/estilo nuevo en el plugin: mirar ahí primero,
no inventar por pantalla.

- **Sin bordes decorativos** en cajas, paneles, botones, chips. La jerarquía
  visual se marca con color de fondo sólido + contraste de texto. Excepción:
  campos de formulario y separadores de fila de tabla (bordes funcionales).
- **Paleta de 5 variantes de botón**, mismo tamaño siempre
  (`padding:6px 14px; font-size:14px`), solo cambia color de fondo/texto:
  neutro, primario, peligro, éxito, info. Hover/foco: oscurecer el
  propio fondo (`filter: brightness(0.92)`), nunca añadir un color ni un
  contorno nuevo. **No crear una 6ª variante ("aviso" u otra) sin necesidad
  real** — confirmado 2026-09-16: para una acción fuerte pero no
  destructiva ("Reiniciar todo" de Carga inicial) se reutilizó el neutro
  ya existente en vez de añadir una variante nueva.
- Motivo: tras varias rondas de ajustes puntuales de CSS sin una referencia
  común, cada pantalla del plugin había terminado con su propio criterio de
  color/tamaño (botones de distinto tamaño entre sí, colores inventados por
  bloque). Se corrige fijando una única fuente de verdad visual.
- **Regla permanente: ningún botón/color/estilo nuevo se inventa.** Se usa
  siempre una variable real de Tabler (`--tblr-*`, ver `assets/tabler.min.css`)
  y una de las 5 clases ya definidas en `assets/wookb-theme.css`
  (`.button`, `.button-primary`, `.button-link-delete`/`.delete`,
  `.wookb-btn-success`, `.wookb-btn-info`). No crear una clase de botón
  nueva sin añadirla antes a `_dev/guia-estilo-visual.html` y a esta lista.
- `_dev/guia-estilo-visual.html` carga el CSS real del plugin
  (`tabler.min.css` + `wookb-theme.css`) con los mismos elementos HTML, no
  una copia a mano — si algo se ve distinto ahí que en wp-admin de verdad,
  es un bug de la guía, no una diferencia aceptable.
- **Hover/foco de botones: siempre con `!important`.** WordPress core
  (`.wp-core-ui .button:hover`) fuerza su propio `background`/`border-color`/
  `color` con la misma especificidad que cualquier regla nuestra de 3
  clases — en un empate de especificidad gana quien cargue después en el
  DOM, y no es fiable asumir que siempre seamos nosotros. Motivo confirmado
  por el usuario inspeccionando el navegador. Por eso el bloque de hover de
  botones en `wookb-theme.css` usa `!important` a propósito — no quitarlo
  pensando que "no hace falta". Mismo caso confirmado el 2026-09-16 con
  `.wp-core-ui select:hover` (texto invisible en oscuro): el hover/foco de
  `select` en `wookb-theme.css` usa `!important` por el mismo motivo. Si
  aparece un tercer caso igual (otro elemento nativo con hover/foco pisado
  por WordPress core), se resuelve igual, no como excepción rara.

## 2026-09-16 — Orígenes de IA

- Orígenes soportados: Conectores nativos de WordPress 7.0 o Support Genix.
- Conectores limita descubrimiento y selección a Anthropic, OpenAI y Google.
- Automático usa únicamente modelos de texto disponibles dentro de esos tres
  proveedores; también se puede fijar un modelo real del catálogo.
- No existe fallback silencioso entre orígenes: si falla el elegido, la
  operación devuelve error.
- La clave propia deja de ser configuración activa. Los valores históricos
  se conservan en base de datos para evitar una eliminación destructiva no
  solicitada.

## 2026-09-16 — Resumen predeterminado de Negocio

- «Enfoque del negocio» es el dato fuente y el resumen público es una opción
  independiente. Ninguno puede sobrescribir o borrar el otro.
- La lectura mantiene fallback al valor histórico hasta el primer guardado del
  resumen separado; no se migra ni elimina información automáticamente.
- Si no hay instrucciones propias, la longitud se adapta a los datos reales:
  no se fuerza un mínimo que provoque repeticiones.
- El formato predeterminado pide párrafos conectados, sin encabezados ni
  escapes en correos. Las instrucciones del usuario mantienen prioridad sobre
  este formato, pero nunca autorizan datos inventados.

## 2026-09-16 — Orden y nombre de pestañas

- «Generación masiva» sustituye al nombre visible «Carga inicial» porque la
  pantalla sirve también para regeneraciones posteriores.
- Se conserva `carga-inicial` como identificador técnico compatible.
- Ajustes queda como última pestaña; Generación masiva, como penúltima.
- WooCommerce solo aparece activo y Chatbot solo con Genix; Chatbot se coloca
  después de WooCommerce y antes de Visibilidad IA.
- Los textos públicos del administrador no muestran números de fase ni usan
  afirmaciones absolutas como «bloqueo real»: explican el efecto verificable
  de robots.txt y de las reglas del servidor.

## 2026-09-16 — Cierre del rename interno (Fase 0.1)

- La Fase 0 dejó a propósito sin tocar el namespace `WOOKB`, las constantes
  `WOOKB_*` y el slug de menú `woo-kb-generator`, para no arriesgar antes de
  tiempo. Se decide completarlo ahora porque el rastro seguía visible en
  redirects, formularios y en `readme.txt` (que indicaba mal la carpeta de
  instalación).
- Namespace `WOOKB\` → `AIKB\`, constantes `WOOKB_*` → `AIKB_*`, archivo
  principal `woo-kb-generator.php` → `ai-knowledge.php`, slug de menú
  `page=woo-kb-generator` → `page=ai-knowledge`.
- Se conserva sin cambiar, por compatibilidad con instalaciones existentes:
  el valor string de la tabla `wookb_documents`, las funciones
  `wookb_activate()`/`wookb_deactivate()`, la opción `wookb_db_version`, los
  query args transitorios `wookb_notice`/`wookb_regen_error`/`wookb_sync_status`
  y el archivo `assets/wookb-theme.css` con su handle de enqueue. Ninguno
  estaba en el alcance pedido y tocarlos no aportaba nada, solo riesgo.
- Efecto esperado y confirmado: al renombrar el archivo principal, WordPress
  desactiva el plugin en cualquier instalación existente hasta reactivarlo
  a mano. Reactivado en Local por el usuario, sin errores.

## 2026-09-16 — Descubribilidad de la API OpenAPI (Fase 7)

Publicar `/wp-json/ai-knowledge/v1/openapi.json` no basta por sí solo: sin
que nada enlace a él, depende de que alguien lo visite por su cuenta o lo
configure a mano en una integración (ej. una Action de un GPT
personalizado) — ningún crawler ni IA lo "descubre solo".

**Decisión:** `llms.txt` (`Llms_Txt::build()`) enlaza siempre a
`openapi.json` en una sección `## API` propia, justo después del
resumen/info y antes de las categorías de contenido. Motivo: `llms.txt` es
el punto de entrada real que ya leen los crawlers de IA (mismo criterio que
el resto de la capa de visibilidad — Markdown público, JSON-LD); un
documento técnico sin nada que apunte a él es, en la práctica, invisible.

**Nota de caché:** `llms.txt` se cachea 24h (`wookb_llms_txt` transient).
Este cambio no se ve reflejado hasta que expire el caché o se llame a
`Llms_Txt::invalidate()` (se dispara ya con la sincronización normal de
contenido) — no se forzó una invalidación especial para este cambio de código.
## 2026-09-18 — Release 1.1.1 y AJAX progresivo

- La documentación de uso se distribuye en `docs/`, fuera de `_dev/`, y se
  abre desde cada pestaña del admin en un panel lateral.
- Los guardados simples y las operaciones de generación/pulido con IA usan
  AJAX de forma progresiva; las colas, descargas, borrados y escrituras de
  archivos físicos conservan el flujo tradicional por riesgo y trazabilidad.
- Cada acción AJAX conserva el formulario POST como fallback y muestra estado
  de proceso, incluyendo spinner para los botones generados por WordPress.
## 2026-09-18 — Flujo de release y ramas

- `main` dentro del repositorio `ai-knowledge` es la rama estable del plugin;
  `main` del workspace es otra cosa y no debe recibir archivos del plugin.
- Antes de ejecutar `_dev/deploy-release.sh` hay que comprobar la divergencia
  entre `knowBaseDev`, `release` y `main`.
- El script elimina `_dev/` en la rama de distribución. Si `main` conserva o
  elimina rutas distintas, el merge puede producir conflictos de
  `modify/delete` o `rename/delete`; no se resuelven automáticamente.
- El release puede publicarse con el ZIP y el tag sin hacer un merge ciego:
  primero se revisa el contenido de la rama estable y se decide cómo tratar
  la documentación interna.

## 2026-09-18 — Navegación contextual de documentación

- Los accesos `?` solo aparecen junto a títulos del admin cuyo anclaje existe
  realmente en la guía correspondiente.
- El popup conserva su contenido al pulsar un acceso mientras ya está abierto;
  el clic fuera no lo cierra y el cierre explícito queda en `×` o Escape.

## 2026-09-19 — Fecha de actualización de llms.txt

- La salida de `llms.txt` incluye en su cabecera la fecha y hora de la última
  generación en formato ISO 8601, tanto en la ruta virtual como en el archivo
  físico regenerado. Así los lectores pueden valorar la frescura del contenido.

## 2026-09-19 — Internacionalización y documentación por idioma

- El español es el idioma base del plugin y la documentación actual de `docs/`
  se conserva como fuente principal.
- El catálogo gettext previsto es `languages/ai-knowledge.pot` y el primer
  catálogo traducible será `languages/ai-knowledge-es_ES.po`.
- Todos los textos visibles deben quedar preparados para traducción: PHP,
  JavaScript, botones, errores, avisos y mensajes AJAX.
- Las traducciones de documentación no se mezclan con gettext: si en el futuro
  existe `docs/{locale}/archivo.md`, el panel usará ese archivo; si no existe,
  usará el archivo equivalente de `docs/`.
- No se crean ahora carpetas ni copias traducidas de Markdown y nunca se borra
  ni sustituye la documentación base española.
- Esta decisión está documentada; la implementación y la validación siguen
  implementadas localmente el 2026-09-19. La prueba visual multiidioma y la
  revisión lingüística del catálogo siguen pendientes.

## 2026-09-23 — Registro y guardados
- Generar, guardar límite, guardar cambios y «Volver a Auto» en el Registro
  usan AJAX con aviso flotante (toast); el formulario POST se conserva como
  fallback. «Guardar cambios» empieza desactivado y solo se activa con edición.
- Los artículos de Genix marcados «solo para el chatbot» nunca se publican. El
  resto de artículos exclusivos de Genix solo salen en `/llms.txt` si el
  usuario los hace públicos desde la pestaña Genix.
- El prompt propio por documento solo pide estilo o enfoque; nunca sustituye
  los datos reales.

## 2026-09-24 — Asistente
- Cada paso genera su documento al guardarse (contenido, WooCommerce, FAQs,
  chatbot). Sin IA disponible, solo guarda y avisa; nunca bloquea.
- El paso `faqs` genera y publica de una vez (excepción explícita a «revisar
  antes de publicar», solo en el asistente).

## 2026-09-24 — Idioma principal
- FAQ, tienda y Negocio se generan solo en el idioma principal con una nota de
  los demás idiomas. Páginas y productos siguen con un documento por idioma.
- No se borran las filas antiguas de otros idiomas: es una limpieza con efecto
  en `/llms.txt` y la decide el usuario.
- Estrategia global de idiomas (Negocio manda, apartado «Idiomas» en los `.md`,
  check «Crear por idioma»): `_dev/estrategia-idiomas.md`. Implementada el
  2026-09-25, sin probar.

## 2026-09-24 — Datos de compra en productos
- Precio, descuento, envío, impuestos, variaciones (tope 100) y campos
  personalizados los anexa el código sin IA; la IA solo redacta la
  descripción.
- El hash incluye la parte estable de esos datos (sin cantidades de stock): un
  cambio de peso, envío, impuestos o variaciones regenera; una venta, no.
- Títulos sin entidades HTML.

## 2026-09-24 — Schema y feeds
- Con Rank Math el plugin no emite su `Article` (módulo `rich-snippet`).
- `products.xml` solo se publica y anuncia si `product` está en el alcance.

## 2026-09-25 — Visibilidad IA: interruptor y reemplazo de .htaccess
- «Incluir llms.txt para los modelos desactivados» es un interruptor grande
  que se guarda con «Guardar configuración de crawlers» (POST con recarga, no
  AJAX): así las propuestas de robots.txt y .htaccess se recalculan.
- «Reemplazar .htaccess» (pestaña y asistente): solo cambia el bloque propio;
  las reglas originales que chocan se comentan, nunca se borran. Exige copia
  descargada (10 min, invalidada al recargar la pantalla), casilla de
  responsabilidad y archivo escribible; si no, no toca nada.

## 2026-09-25 — Release y proceso
- Versión 1.3.0 publicada. El tag lo crea la API de GitHub Release; el script
  ya no crea ni sube un tag local.
- Proceso permanente: commit/push, cambios de versión y cualquier edición
  requieren permiso explícito; una pregunta no es permiso. Ajustes pequeños,
  en la sesión principal; subagente solo para trabajo grande.
- Nunca dar por hecho algo visual sin confirmación del usuario. Sin colores ni
  estilos nuevos que no salgan de una variable de Tabler o una clase existente
  (`_dev/guia-estilo-visual.html`).
- Los archivos de contenido real del sitio viven en `wp-content/llm/`, fuera
  del repo. Contenido legal o de pago: solo «pulir redacción», nunca
  generación libre.

## 2026-09-25 — Precio y contacto comercial
- Precios: **49 €/año por web**, **99 €/año sin límite de webs** y **lifetime
  222 € sin límite de webs**. Servicio opcional de configuración: unos 50 €.
- Contacto: Misha, help@22mw.online.
- El material comercial vive en `_dev/comercial/` (nunca en ZIP ni release).
- No se prometen resultados de posicionamiento en IA ni funciones «en
  estudio» (modo WP, Polylang, TranslatePress).
- Resultado comprobado por el usuario en más de una web: el prompt de auditoría GEO suele dar BAJO o MEDIO antes y ALTO después de configurar el plugin. Se puede afirmar así en el material comercial, sin cifras ni garantías.

## 2026-09-25 — Servicio de idiomas
- Solo `AIKB\Languages` (y sus proveedores WPML, Polylang, TranslatePress y
  «ninguno») conocen un plugin de idiomas. El resto del plugin pregunta al
  servicio; no se llama a `wpml_*` ni a `pll_*` fuera de los proveedores.
- Un `.md` por contenido, en el idioma del original. «Crear por idioma»
  (solo con plugins que crean un post por idioma) añade un `.md` por cada
  traducción que existe. No hay documentos puente.
- Idioma principal: Negocio, después el plugin de idiomas, después el idioma
  de WordPress. Sin `es` fijo.
- Cambiar el check o el idioma principal deja un aviso hasta usar «Reiniciar
  todo», que borra los `.md` por idioma y los puentes que sobran.
- Detalle y decisiones menores en `_dev/estrategia-idiomas.md`.

## 2026-09-25 — Ajustes tras la prueba en docthinks (WPML)
- Una sola función por proveedor da la URL de un contenido en SU idioma
  (`Languages::permalink()`); WPML cambia de idioma solo alrededor de
  `get_permalink()`. La URL del `.md` y `llms.txt` se construyen sin prefijo de
  idioma (`Markdown_Store::root_url()`).
- `llms.txt` se genera siempre bajo el idioma principal. No existe `llms.txt`
  localizado: `/xx/llms.txt` sirve el mismo archivo (físico si existe, si no el
  dinámico).
- Apartado «Idiomas» de los `.md`: solo «Disponible en: …»; con una versión, sin
  apartado. Nombres completos desde tabla propia; el del plugin de idiomas solo
  si no parece el código.
- Sin «Crear por idioma», `Scope::resolve_ids()` devuelve un id por contenido
  (el original o, si no existe, la versión que quede en orden de idiomas); con
  el check, todos. El conteo de Carga inicial no multiplica por idiomas.
- El idioma principal y los idiomas de la web son un campo estructurado
  (Negocio y asistente); sustituye a la pregunta libre. El texto de prompt,
  `llms.txt` y resumen se derivan de él. Lo que coincide con la detección
  automática se guarda vacío (sigue detectando solo).
- «Reiniciar todo», en cada ejecución, borra también los documentos cuyo tipo
  ya no está en el alcance (con confirmación que indica cuántos). No toca FAQ,
  tienda, Negocio, artículos de Genix ni filas en modo manual.
- Guardar de nuevo el paso FAQ del asistente regenera con IA (con el FAQ actual
  como referencia) y sustituye el publicado.
