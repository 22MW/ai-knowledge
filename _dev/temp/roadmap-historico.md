# Histórico de roadmap — AI Knowledge & Visibility

Archivo de consulta: recoge trabajo cerrado y su evidencia ya registrada. No es una lista de trabajo activa; para pendientes, ver [`roadmap.md`](../roadmap.md).

## Fases cerradas

- **Fase 0 y 0.1 — Identidad:** renombrado a AI Knowledge & Visibility, archivo principal `ai-knowledge.php`, namespace `AIKB` y slug de menú `ai-knowledge`. Se conservaron tabla, opciones y nombres `wookb_*` que requerían compatibilidad.
- **Fase 0.2 — Documentación:** creados el índice, instalación y guías de las nueve pestañas en [`docs/`](../../docs/index.md), además de documentación técnica interna. Los medios marcados como pendientes permanecen en el roadmap activo.
- **Fase 1 — Control manual:** modo Auto/Manual, texto fijado, límite por documento, detección de origen actualizado y acceso desde el editor.
- **Fase 2 — WooCommerce:** pestaña específica de tienda, envíos, impuestos, pagos, condiciones y vistas previas; ampliada posteriormente con selección editable y pulido factual por IA.
- **Fases 3 a 5 — Exposición pública:** API JSON de contenido, Markdown público descubrible y JSON-LD Schema.org.
- **Fase 6 — Visibilidad IA:** panel de exposición, contador, comprobación de accesibilidad y gestión de `llms.txt` físico. Confirmado en real el contador y la detección de conflicto robots/noindex.
- **Fase 7 — OpenAPI:** `openapi.json` para la API pública, enlazado desde `llms.txt`; confirmado por el usuario en navegador.
- **Fase 8 — IndexNow:** aviso no bloqueante para buscadores compatibles; confirmado en real con respuesta HTTP 202. Google queda fuera por no soportar IndexNow.
- **Fase 9 — Feeds:** feeds de productos y contenido, enlazados desde `llms.txt` y el `<head>`; confirmados en real.
- **Fase 10 — UX admin:** pestaña Contenido, etiquetas de campos, modo de todos los CPT, Registro con ajustes avanzados y Generación masiva.
- **Fase 11 — Crawlers IA:** catálogo, configuración individual, bloques protegidos de `robots.txt` y `.htaccess`, copias obligatorias y logs. Confirmado en real el uso de `robots.txt`.

## Rondas cerradas posteriores

### Documentación y UX AJAX (2026-09-18)

- La documentación pública se movió de `_dev/docs/` a `docs/`, fuera de la
  memoria interna, conservando sus enlaces y archivos Markdown.
- Cada pestaña del admin abre su guía en un panel lateral derecho con lectura
  normal, cierre y adaptación responsive.
- Los guardados simples y las generaciones/pulidos con IA usan AJAX con
  fallback tradicional, estados de proceso, protección contra doble envío y
  spinner visible.
- Se publicó el release `1.1.1` con ZIP limpio y documentación actualizada.

### Release 1.1.2 (2026-09-18)

- Navegación contextual desde títulos del admin hacia secciones concretas de
  la documentación.
- Enlaces Markdown con anclas dentro del popup y ajustes finales de contraste,
  hover, tamaño y cierre del panel.

### Negocio, Chatbot y FAQs (2026-09-16)

- Pestañas separadas para Negocio, Chatbot condicional y FAQs.
- Instrucciones privadas delimitadas, borradores editables y guardado manual.
- FAQs multiidioma reales y corrección de etiquetas de idioma en sitios monolingües.
- Orígenes editables trasladados a `wp-content/llm/`; no se borran en desinstalación.
- Corregidos formularios anidados que rompían acciones masivas del Registro.

### WooCommerce: selección editable y pulido (2026-09-16)

- Selección de envíos, impuestos, pagos y categorías.
- Snapshot editable de datos detectados y campos de respaldo de tienda.
- Pulido de redacción limitado a datos factuales, sin generación libre.
- Corregido el tratamiento de IDs de pasarela de pago de texto.

### Correcciones confirmadas (2026-09-17)

- `Scope::resolve_ids()` aplica relación `OR` entre taxonomías incluidas; los feeds dejaron de devolver alcance vacío y se verificó en real.

### Estrategia de idiomas (2026-09-25)

- Servicio común de idiomas (`AIKB\Languages`) con proveedores WPML, Polylang,
  TranslatePress y «ninguno»; el resto del plugin ya no llama a WPML
  directamente. Se acabó el español fijo.
- Idioma principal e idiomas de la web en Negocio y en el asistente (detectados
  por el plugin de idiomas, con idiomas añadidos a mano). La pregunta libre
  «Idioma principal del negocio» se sustituyó por el campo estructurado, con
  migración de lo ya guardado.
- Un solo `.md` por contenido, en el idioma principal; las traducciones lo
  enlazan en el `<head>`. Apartado «Disponible en» con las URL de cada idioma.
- Casilla «Crear por idioma» (solo WPML y Polylang), en Carga inicial y en el
  asistente, con aviso y «Reiniciar todo» (confirmación con el número de
  documentos que se borran, incluidos los de tipos fuera del alcance).
- URL por idioma correctas (`Languages::permalink()`), `.md` y `llms.txt` sin
  prefijo de idioma, `llms.txt` único bajo el idioma principal.
- Botón «Añadir a la base de conocimiento» del editor por AJAX (antes un
  formulario anidado que redirigía a `edit.php`).
- Textos nuevos traducidos en el `.pot` y los `.po` de `ca`, `de_DE`, `en_US`, `eu`
  y `fr_FR` (2026-09-25).
- Probado con WPML real (docthinks: es principal, en, ca), con el check
  desmarcado y marcado. Detalle en `estrategia-idiomas.md` y
  `ajustes-idiomas-docthinks.md` (ambos en esta carpeta).

### Otros cierres recogidos al limpiar el roadmap (2026-09-25)

- «Reemplazar .htaccess» probado en un servidor real (según el usuario).
- Internacionalización del plugin: catálogos `ca`, `de_DE`, `en_US`, `eu` y
  `fr_FR` al día con la 1.3.0; selección de `docs/{locale}/` implementada.
- Release **1.3.2** publicada (2026-09-25): estrategia de idiomas, tag `v1.3.2` y
  fusión en `main`.
- Releases 1.2.0 a 1.3.0 fusionadas en `main` con el script sin conflictos; el
  script ya no sube un tag local.

## Decisiones históricas relevantes

- El núcleo del producto continúa siendo generación por IA y Support Genix; las capas JSON, Markdown, Schema y feeds lo complementan.
- Los documentos legales o de pago se construyen de forma determinista: la IA solo puede pulir redacción sin cambiar datos.
- Los contenidos reales generados viven en `wp-content/llm/`, fuera del repo.
- No se escribe `robots.txt` ni `.htaccess` sin el flujo de seguridad y copia obligatoria aprobado para esa función.
- El detalle completo de decisiones estables está en [`decisiones.md`](../decisiones.md).
