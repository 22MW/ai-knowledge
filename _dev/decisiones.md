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
- **Paleta de 6 variantes de botón**, mismo tamaño siempre
  (`padding:6px 14px; font-size:14px`), solo cambia color de fondo/texto:
  neutro, primario, peligro, éxito, aviso, info. Hover/foco: oscurecer el
  propio fondo (`filter: brightness(0.92)`), nunca añadir un color ni un
  contorno nuevo.
- Motivo: tras varias rondas de ajustes puntuales de CSS sin una referencia
  común, cada pantalla del plugin había terminado con su propio criterio de
  color/tamaño (botones de distinto tamaño entre sí, colores inventados por
  bloque). Se corrige fijando una única fuente de verdad visual.
- **Regla permanente: ningún botón/color/estilo nuevo se inventa.** Se usa
  siempre una variable real de Tabler (`--tblr-*`, ver `assets/tabler.min.css`)
  y una de las 6 clases ya definidas en `assets/wookb-theme.css`
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
