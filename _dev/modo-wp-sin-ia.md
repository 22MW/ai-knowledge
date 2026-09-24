# Modo WP: gestionar documentos sin IA

Estado: planteado y valorado el 2026-09-25. **No implementado.** Cambio grande, en fases.

## Idea

Poder usar el plugin sin IA. En «modo WP» el documento se crea copiando el
contenido de WordPress directamente, sin llamar a ningún modelo.

## Decisiones tomadas

1. **Un ajuste global** en Ajustes (y en el paso de IA del asistente): modo de
   generación **IA** o **WP**.
2. **Cambiar de modo no regenera nada por sí solo.** Lo ya generado se queda
   como está. Para pasar todo al modo nuevo hay un botón «Regenerar todo» en
   Ajustes, con confirmación. Solo afecta a los documentos en Auto; los
   Manuales no se tocan (igual que hoy).
3. **Qué pasa al editar una página ya generada con otro modo.** El usuario lo
   decide en Ajustes con una opción de dos valores:
   - **B (por defecto): mantiene el modo con el que se generó.** Un documento
     hecho con IA se sigue regenerando con IA aunque el modo global sea WP.
   - **A: usa el modo global actual.**
4. **Sin columna nueva.** El modo de cada documento se guarda en la columna
   que ya existe, `override_mode` (`VARCHAR(8)`), con un valor nuevo `auto_wp`
   (7 caracteres, cabe). Los valores quedan:
   - `auto`: Auto generado con IA (también todos los documentos antiguos).
   - `auto_wp`: Auto generado en modo WP.
   - `manual`: Manual.
5. **Badge del Registro.** En la columna «Control manual» el badge actual
   Manual/Auto pasa a mostrar también el origen: `Auto · IA`, `Auto · WP`,
   `Manual`.

## Cómo queda cada pantalla en modo WP

- **Registro:** el prompt propio por documento no se muestra en ningún sitio.
- **Pestaña Genix (prompt):** no se muestran los prompts.
- **Negocio, FAQs, WooCommerce y Genix:** sin botones de «generar con IA» ni
  «pulir». Se pega el texto y se guarda a mano.
- **Asistente:** sin comprobación de conexión de IA ni paso de FAQ con IA. Los
  pasos que hoy generan con IA solo guardan.

## Cómo se genera el documento en modo WP

Punto de partida: `Generator::build_bridge_markdown()` (documento sin IA ya
existente) más el contenido completo del post.

- Título, contenido y extracto del post.
- El bloque «Datos de compra» de los productos ya lo anexa el código sin IA:
  funciona igual en los dos modos.
- Bloque «Disponible también en» y enlaces de idioma: igual que hoy.
- Se aplica el límite de caracteres del documento igual que en el modo IA.

### Problema a resolver: el texto plano sale flojo

El extractor usa `wp_strip_all_tags( post_content )`: se pierden títulos,
listas y enlaces. Hace falta convertir el HTML a Markdown sencillo (títulos,
listas, enlaces, negritas) en el modo WP.

### Riesgo sin confirmar

Si una página usa un constructor (Elementor u otro) que guarda el contenido
fuera de `post_content`, el documento saldría casi vacío. No he visto que el
plugin lo trate. **Hay que probarlo con una página real antes de dar por buena
la fase 1.**

## Dónde está la IA hoy (verificado en el código)

- Documentos de contenido y productos: `Generator::generate()` llama a
  `AI_Client::generate()`.
- Borradores y pulido: resumen de Negocio, FAQs, prompt de Genix, normalizar
  prompt y pulir documento de tienda (`generate_business_summary_draft`,
  `generate_faqs_draft`, `generate_prompt_draft`, `normalize_prompt`,
  `polish_store_doc`).
- El origen de IA se elige en Ajustes con `ai_key_source` (`genix` |
  `wp_connectors`). El modo WP se añade como un valor más de ese ajuste o como
  ajuste aparte (a decidir al planificar).
- Todo el código que mira `override_mode` compara solo con `'manual'`
  (pipeline, documento de tienda, metabox, admin y tabla del Registro), así
  que añadir `auto_wp` no rompe esas comprobaciones. Hay que revisar los dos
  sitios que escriben `auto` (`class-registry.php` y «Volver a Auto»).

## Fases propuestas

1. **Modo global y generador WP.**
   - Ajuste de modo y ajuste A/B.
   - Generador sin IA con conversión a Markdown.
   - Valor `auto_wp` y badge `Auto · IA` / `Auto · WP`.
   - El pipeline decide el modo por la fila (B) o por el global (A).
2. **Botón «Regenerar todo»** en Ajustes y ocultar la parte de IA en las
   pestañas (Registro, Genix, Negocio, FAQs, WooCommerce).
3. **Asistente sin IA.**

## Pendiente de decidir al planificar

- Si «WP» es un valor de `ai_key_source` o un ajuste independiente.
- Qué hace «Volver a Auto» en una fila: usar el modo global actual.
- Texto exacto de la opción A/B en Ajustes.
- Traducciones (.po) de los textos nuevos: aplazadas, como el resto.

## No hace falta

- Columna nueva ni migración de base de datos.
