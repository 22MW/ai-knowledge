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
