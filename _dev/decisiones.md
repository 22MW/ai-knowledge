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
