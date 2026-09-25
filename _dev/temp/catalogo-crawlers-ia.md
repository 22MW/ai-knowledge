# Catálogo de crawlers de IA — Fase 11

Referencia para implementar el catálogo de `Fase 11 — Gestión de crawlers
de IA` (ver `roadmap.md`). ~24 crawlers conocidos, agrupados por operador,
con su categoría de propósito, qué hace exactamente, y una recomendación
por defecto — el usuario del plugin siempre puede cambiarla, esto es solo
el valor sugerido inicial.

**3 categorías de propósito (estándar del sector):**
- `ai_search` — indexa para que un buscador o asistente IA te muestre en
  resultados/citas. Bloquearlo te quita visibilidad ahí.
- `user_requested_assistant` — visita tu web en el momento porque un
  usuario le pidió a su asistente IA que la leyera/resumiera. Bloquearlo
  rompe esa función para tus visitantes.
- `model_training` — solo recopila contenido para entrenar modelos de IA
  en general, sin beneficio directo de visibilidad para ti. Es la
  categoría donde más sentido tiene bloquear si te preocupa que tu
  contenido se use para entrenar sin permiso.

---

## OpenAI

| Bot | Categoría | Qué hace | Recomendación por defecto |
|---|---|---|---|
| `GPTBot` | `model_training` | Recopila contenido para entrenar los modelos de OpenAI (GPT). | Bloquear si no quieres que tu contenido entrene modelos ajenos. No afecta a que ChatGPT te cite o te busque (eso son los otros dos bots). |
| `ChatGPT-User` | `user_requested_assistant` | Visita una URL concreta porque un usuario le pidió a ChatGPT que la leyera/resumiera/navegara. | Permitir. Bloquearlo rompe esa función justo cuando un usuario tuyo la usa sobre tu web. |
| `OAI-SearchBot` | `ai_search` | Indexa contenido para la función de búsqueda de ChatGPT (citas con enlace en respuestas). | Permitir. Es como aparecer en resultados de búsqueda, pero dentro de ChatGPT. |

## Anthropic

| Bot | Categoría | Qué hace | Recomendación por defecto |
|---|---|---|---|
| `ClaudeBot` | `model_training` | Recopila contenido para entrenar los modelos de Anthropic (Claude). | Bloquear si no quieres entrenar modelos ajenos con tu contenido. |
| `Claude-User` | `user_requested_assistant` | Visita una URL porque un usuario se lo pidió a Claude en una conversación. | Permitir. |
| `Claude-SearchBot` | `ai_search` | Indexa contenido para que Claude pueda citarlo/buscarlo. | Permitir. |

## Google

| Bot | Categoría | Qué hace | Recomendación por defecto |
|---|---|---|---|
| `Googlebot` | `ai_search` (búsqueda tradicional, no específico de IA) | El rastreador normal de Google Search. No es un bot "de IA" propiamente, pero se incluye porque bloquearlo por error aquí sería crítico. | Permitir siempre. Bloquearlo te saca de Google Search por completo. |
| `Google-Extended` | `model_training` | Controla específicamente si tu contenido se usa para entrenar Gemini/Vertex AI — **separado** de `Googlebot**, bloquearlo NO afecta tu posición en Google Search. | Bloquear si te preocupa el entrenamiento, es seguro hacerlo sin perder SEO. |

## Microsoft / Bing

| Bot | Categoría | Qué hace | Recomendación por defecto |
|---|---|---|---|
| `Bingbot` | `ai_search` | Rastreador de Bing Search, también alimenta Copilot (el asistente de Microsoft). | Permitir. |

## Perplexity

| Bot | Categoría | Qué hace | Recomendación por defecto |
|---|---|---|---|
| `PerplexityBot` | `ai_search` | Indexa contenido para que Perplexity AI pueda citarte en sus respuestas. | Permitir si quieres aparecer citado en Perplexity. |
| `Perplexity-User` | `user_requested_assistant` | Visita una URL porque un usuario se lo pidió a Perplexity en el momento. | Permitir. |

## Meta / Facebook

| Bot | Categoría | Qué hace | Recomendación por defecto |
|---|---|---|---|
| `FacebookBot` | `ai_search` (no es IA generativa, pero se agrupa aquí por convención del sector) | Genera las vistas previas de enlaces compartidos en Facebook/Instagram. | Permitir. Bloquearlo rompe las previews de tus enlaces compartidos. |
| `Meta-ExternalAgent` | `model_training` | Recopila contenido para entrenar los modelos de Meta AI. | Bloquear si te preocupa el entrenamiento. |
| `Meta-ExternalFetcher` | `user_requested_assistant` | Visita una URL porque un usuario se lo pidió a Meta AI. | Permitir. |

## Apple

| Bot | Categoría | Qué hace | Recomendación por defecto |
|---|---|---|---|
| `Applebot` | `ai_search` | Alimenta Siri y Spotlight Search (búsqueda de Apple). | Permitir. |
| `Applebot-Extended` | `model_training` | Controla el uso de tu contenido para entrenar los modelos de IA de Apple — separado de `Applebot`. | Bloquear si te preocupa el entrenamiento, sin afectar a Siri/Spotlight. |

## DuckDuckGo

| Bot | Categoría | Qué hace | Recomendación por defecto |
|---|---|---|---|
| `DuckAssistBot` | `ai_search` | Alimenta el asistente de IA de DuckDuckGo. | Permitir. |

## You.com

| Bot | Categoría | Qué hace | Recomendación por defecto |
|---|---|---|---|
| `YouBot` | `ai_search` | Indexa contenido para el buscador con IA de You.com. | Permitir si te interesa esa plataforma (uso minoritario). |

## Cohere

| Bot | Categoría | Qué hace | Recomendación por defecto |
|---|---|---|---|
| `cohere-ai` / `cohere-training-data-crawler` | `model_training` | Recopila contenido para entrenar los modelos de Cohere. | Bloquear salvo que tengas relación comercial con ellos. |

## ByteDance / TikTok

| Bot | Categoría | Qué hace | Recomendación por defecto |
|---|---|---|---|
| `Bytespider` | `model_training` | Rastreador agresivo de ByteDance para entrenar sus modelos de IA. Conocido por ignorar `robots.txt` en algunos casos. | Bloquear (además vía `.htaccess` si `robots.txt` no basta, ver Fase 11). |

## Amazon

| Bot | Categoría | Qué hace | Recomendación por defecto |
|---|---|---|---|
| `Amazonbot` | `ai_search` / `model_training` (uso mixto, documentado por Amazon como ambos) | Alimenta Alexa y otros productos IA de Amazon. | Depende: permitir si te interesa Alexa, bloquear si prima la preocupación por entrenamiento. Sin recomendación única por defecto — dejar a elección explícita del usuario. |

## xAI / Grok

| Bot | Categoría | Qué hace | Recomendación por defecto |
|---|---|---|---|
| `xAI-Crawler` / `Grok` | `model_training` (uso poco documentado públicamente por xAI) | Recopila contenido para entrenar Grok. | Bloquear por precaución hasta que xAI documente mejor su uso — mismo criterio que otros bots de entrenamiento sin transparencia clara. |

## Datasets / agregadores (venden o ceden datos a terceros para entrenar IA)

| Bot | Categoría | Qué hace | Recomendación por defecto |
|---|---|---|---|
| `CCBot` | `model_training` | Rastreador de Common Crawl — su dataset lo usan MUCHOS laboratorios de IA (no solo uno) para entrenar modelos. | Bloquear si te preocupa el entrenamiento en general: es la vía más amplia de reutilización de tu contenido. |
| `Diffbot` | `model_training` | Extracción de datos comercial, vende acceso estructurado a tu contenido. | Bloquear salvo relación comercial explícita. |
| `ImagesiftBot` | `model_training` | Recopila imágenes específicamente para datasets de entrenamiento de IA. | Bloquear si tu contenido incluye imágenes que no quieres en datasets de entrenamiento. |
| `Omgili` / `Omgilibot` | `model_training` | De webhose.io, vende los datos recopilados a terceros (incluye entrenamiento de IA). | Bloquear. |
| `Timpibot` | `model_training` | Recopila contenido para entrenamiento de IA (Timpi, buscador). | Bloquear salvo interés explícito en esa plataforma. |

---

## Resumen de recomendaciones por defecto (para el generador de bloque sí/no)

- **Bloquear por defecto:** todos los `model_training` — `GPTBot`, `ClaudeBot`, `Google-Extended`, `Meta-ExternalAgent`, `Applebot-Extended`, `cohere-*`, `Bytespider`, `xAI-Crawler`, `CCBot`, `Diffbot`, `ImagesiftBot`, `Omgili*`, `Timpibot`.
- **Permitir por defecto:** todos los `ai_search` y `user_requested_assistant` — `Googlebot`, `Bingbot`, `OAI-SearchBot`, `ChatGPT-User`, `Claude-SearchBot`, `Claude-User`, `PerplexityBot`, `Perplexity-User`, `FacebookBot`, `Meta-ExternalFetcher`, `Applebot`, `DuckAssistBot`, `YouBot`.
- **Sin recomendación única (preguntar explícitamente):** `Amazonbot` (uso documentado como mixto).

Esta tabla resumen es la base de las "3 preguntas sí/no al admin" del
generador de bloque (roadmap, Fase 11, pieza 3): en realidad puede acabar
siendo una sola pregunta general ("¿bloquear entrenamiento de IA?") que
aplica esta tabla completa, más una pregunta aparte para `Amazonbot` por
su ambigüedad. A decidir en la planificación técnica de la fase.

## Fuente y mantenimiento

Esta lista refleja el estado público conocido de cada bot a fecha
2026-09-16. Los operadores de IA cambian sus crawlers, alias y políticas
con frecuencia (algunos han cambiado de nombre de user-agent más de una
vez). El catálogo debe implementarse como array filtrable
(`apply_filters`, ya decidido en el roadmap) para poder actualizarlo sin
tocar el core del plugin, y conviene revisar esta tabla antes de
implementar la Fase 11 por si algo cambió entre esta fecha y la
implementación real.
