# Contexto activo

## Plugin objetivo

`ai-knowledge` (antes `woo-kb-generator`), rama `knowBaseDev`, repo
`github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Confirmado (Fases 0-11 completas + reestructuración Negocio/Chatbot/FAQs/WooCommerce)

Fase 10 (plan conjunto de UX del admin, 5 piezas) completa. Además, tras
probarla en real, se hicieron dos rondas más grandes no planificadas como
fase (ver `_dev/roadmap.md`, secciones "Reestructuración
Negocio/Chatbot/FAQs" y "Pestaña WooCommerce: selección editable + pulido
con IA"), probadas en real y confirmadas por el usuario (2026-09-16):

**Ronda 1 — Negocio/Chatbot/FAQs:**
- Negocio y FAQs usan ya el mismo sistema de instrucciones privadas que
  WooCommerce: prioridad sobre formato/orden/estructura, datos protegidos,
  bloques delimitados y rechazo si la IA copia instrucciones. Sus borradores
  siguen siendo editables y requieren guardado manual.
- Prompt genérico de generación corregido (ya no sesgado a "bodega").
- Pestaña **Negocio** nueva: datos del negocio + generación IA del resumen
  de `llms.txt`.
- Pestaña **Chatbot** (antes "Prompt"): solo visible si Genix está activo.
- Limpieza de Ajustes: eliminado el antiguo `extra_prompt` de interfaz,
  guardado y generación; el valor histórico no se borra de la base de datos,
  simplemente deja de usarse. `chatbot_docs_list_limit` se mueve a Chatbot
  con guardado propio.
- Pestaña **FAQs** nueva (movida desde Ajustes): generación IA,
  **multiidioma real** (antes un único archivo global).
- Fix: sufijo "(ES)"/"(EN)" en `llms.txt`/Registro solo en sitios
  multiidioma de verdad.
- Bug real corregido (heredado de la pieza 5): formularios anidados en la
  fila expandida del Registro rompían "Borrar seleccionados" en silencio.
- Fix de UX en Registro: "Guardar cambios" aparece siempre en el editor;
  desde Auto guarda y pasa a Manual, y desde Manual guarda sin cambiar de
  modo. "Volver a Auto" aparece además cuando corresponde.
- Orígenes editables (`chatbot-system-prompt.md`, FAQ fuente) movidos de
  la raíz del plugin a `wp-content/llm/`, junto al resto de contenido.
  `chatbot-system-prompt.md` de la raíz (trackeado en git con datos
  reales del cliente) se destrackeó con permiso explícito. `uninstall.php`
  sigue sin borrar `wp-content/llm/` — confirmado, sin cambios ahí.

**Ronda 2 — WooCommerce:**
- Envíos, impuestos/IVA, métodos de pago y categorías del catálogo pasan
  a ser seleccionables (checkbox), mismo patrón que Contenido. Envíos no
  entraba antes en el documento en absoluto — ahora sí.
- "Detectado automáticamente" deja de ser solo lectura: snapshot editable
  que sobrevive a cambios/borrados en WooCommerce.
- Nuevo "Contacto y horario de la tienda online" (independiente del de
  Negocio) y campos de respaldo "Pedido mínimo/envío gratis" y "Recogida
  en tienda" (solo se usan si WooCommerce no lo tiene ya detectable).
- Nuevo botón "Pulir redacción con IA" en los documentos de tienda —
  punto intermedio: sigue siendo determinista en los datos, la IA solo
  mejora cómo está escrito (por riesgo de alucinación en contenido legal).
- Fix tras prueba real: el campo pasa a llamarse "Instrucciones para pulir
  el texto", admite una indicación breve o un prompt completo y prevalece
  sobre formato/orden, nunca sobre los datos. Documento e instrucciones se
  delimitan y se rechaza cualquier respuesta que copie instrucciones
  internas. Los textos completos externos se pegan manualmente en Registro.
- Distribución visual de ambos documentos igualada a Negocio: título `h2`,
  explicación, instrucciones, botón y resultado. El idioma se oculta si solo
  hay uno y se integra en el título cuando hay varios.
- Estilo unificado con Contenido/Negocio (sin cajas de fondo).
- Fix: IDs de pasarela de pago (texto) se destruían con `absint()`.

Las rondas 1 y 2 quedaron publicadas en `knowBaseDev` como `ff3daa8`; la
limpieza posterior de Ajustes/Chatbot, como `a332da1`.

## Cambio actual — Conectores WordPress 7.0

- Integrado el AI Client nativo como origen seleccionable junto a Support
  Genix, con adaptador central para generación y traducción auxiliar.
- El selector descubre modelos de texto reales de los proveedores
  `anthropic`, `openai` y `google`; admite Automático o elección explícita.
- La clave propia deja de mostrarse y usarse. Sus valores históricos no se
  borran de la opción existente.
- Pendiente QA real: conectar proveedor, guardar modelo automático/manual y
  probar documentos, Negocio, FAQs, WooCommerce y traducción del chatbot.

## Ajuste del resumen de Negocio

- Corregida la colisión de datos: `answers['negocio']` conserva el enfoque
  fuente y `wookb_business_summary` guarda el resumen público por separado.
- Compatibilidad: mientras la nueva opción no exista, el resumen lee el valor
  histórico de `answers['negocio']`; el primer guardado los separa.
- El prompt predeterminado ya no pide solo «prosa breve»: genera una
  descripción conectada y proporcional a los datos reales disponibles.
- Evita repetir información para rellenar y pide conservar los correos sin
  barras invertidas. Las instrucciones escritas por el usuario siguen
  prevaleciendo sobre este formato predeterminado.

## Pendiente de confirmar

- El fix de instrucciones de pulido pasa `php -l` con PHP 8.3.23 en sus dos
  archivos PHP. La ronda 2 completa sigue pendiente de prueba real por el
  usuario, especialmente el comportamiento de la llamada a IA.
- Hay ~100 filas de datos de PRUEBA en la tabla `wookb_crawler_log` (Fase
  11, botón temporal ya retirado). Inofensivas, dentro del tope de 500.

## Pendiente real (sin empezar)

- UX3 (WooCommerce: checkboxes + prompt por campo a nivel de PRODUCTO,
  cambio de arquitectura de datos, requiere `rol-analista` — distinto de
  la selección de envíos/impuestos/pagos/catálogo ya hecha en la ronda 2)
  y UX4 (visibilidad del botón de generar documentos de tienda).
- Estilos inline en PHP (`style="width:100%"`, etc. en varias vistas):
  detectado, no abordado.
- FAQ fuente y `chatbot-system-prompt.md`: contenido real del sitio,
  ahora en `wp-content/llm/` (fuera del repo del plugin por completo —
  ya no hace falta ni `.gitignore` para ellos, aunque se mantienen las
  entradas por si queda algún archivo de la migración en la raíz).
- Bug sin repetir: botón del editor (Fase 1) sin feedback claro la primera
  vez que se probó.
- Ideas sueltas sin fase (`analisis-jet-geo.md`): tags dinámicos en
  prompts, onboarding por pasos.
- Cobertura de Google para avisos en tiempo real (Fase 8): IndexNow no lo
  soporta; requeriría la Search Console Indexing API (OAuth propio) — no
  planificada.
- Documentación OpenAPI (Fase 7) sin actualizar con las rutas de feeds
  (Fase 9) ni con las de crawlers (Fase 11) — decisión explícita.

## Decisiones de proceso (aplican siempre en este plugin)

- Commit/push: permiso explícito cada vez, mostrando status+diff+mensaje.
- Cambios de versión: preguntar antes.
- Subagente: solo trabajo grande/aislado; ajustes pequeños los hace la
  sesión principal.
- Nunca "hecho" en algo visual sin confirmación del usuario.
- Ningún color/estilo nuevo sin usar una variable real de Tabler o una
  clase ya definida — ver `_dev/guia-estilo-visual.html` primero.
- Antes de rediseñar una tabla/vista existente que ya funciona, confirmar
  el diseño exacto columna por columna con el usuario antes de escribir
  código.
- Al cambiar un comportamiento consolidado, probar en real cuanto antes:
  el diseño cerrado por escrito no sustituye ver el resultado en pantalla.
- Los archivos de contenido real del sitio generados por el plugin viven
  en `wp-content/llm/` (fuera del repo del plugin), no en su carpeta —
  nunca se incluyen en commits.
- Contenido legal/de pago (documentos de tienda): nunca generación libre
  por IA, solo "pulir redacción" sin tocar datos — decisión reafirmada
  explícitamente en la ronda 2 tras planteárselo al usuario.

## Relevo mínimo — siguiente paso

Confirmar commit + push del estado actual (rondas 1 y 2). Después:
probar la pestaña WooCommerce en real (checkboxes, snapshot editable,
pulido con IA) y decidir el siguiente foco (UX3/UX4 u otra tarea).
