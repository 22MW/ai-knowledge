# Ajustes de idiomas — prueba 1 en docthinks.local (check desmarcado)

Estado: análisis del 2026-09-25, solo lectura (código + BD del sitio). Ajustes aplicados después (ver «Estado»).
Sitio: WPML, es (principal) + en + ca, instalación nueva. Prefijo de tablas `m22w_`.

## Datos del sitio (BD real)

- `wookb_language_settings` = `{per_language: 0}`. No guarda idioma principal: sale de WPML (`es`).
- `wookb_chatbot_prompt_answers.idioma_principal` = «Español, Ingles y Catalan» (texto libre).
- Contenidos publicados en el alcance (post, page, product, film, community-member): 57 + 68 + 7 + 15 + 259 = **406**, contando todas las traducciones. Solo en `es`: 142.
- Documentos (`wookb_documents`): 152 filas; `es` 145 + `en` 3 + `ca` 2 + los propios (FAQ, tienda, info). Cola en curso (`wookb_seed_running = 1`, sin límite diario).

## Hallazgos por punto

| # | Punto del usuario | Qué pasa (evidencia) | Tipo |
|---|---|---|---|
| 1 | Idiomas deben ser parte del formulario de Negocio | Hoy es una sección «3. Idiomas» con su propio formulario y handler, aparte del cuestionario de Negocio (`tab-negocio.php`). | Ajuste de diseño |
| 2 | «Idioma principal del negocio» no duplicar | Hay **dos fuentes**: la pregunta libre `idioma_principal` (`class-chatbot-prompt-builder.php:75,204`; alimenta el resumen, `llms.txt` y la línea «Idiomas: …» del prompt) y `Languages::main_language()`. En docthinks dicen cosas distintas: «Español, Ingles y Catalan» frente a `es`. Además `llms.txt` dice «Su idioma principal es Español, Ingles y Catalan», que es incorrecto. | Duplicado |
| 3 | «Es» en vez de «Español» | WPML de este sitio tiene `native_name` = «Es», «En», «Ca» (`icl_languages_translations`). El plugin se fía del nombre del proveedor (`class-wpml.php:37`) y solo usa su tabla propia si falta. | Bug |
| 4 | Idiomas también en el asistente | Confirmado: el paso `business` del asistente solo pinta las preguntas del grupo negocio (`class-admin.php`), sin idiomas ni check. | Falta |
| 5 | Mensaje de FAQ «Ya hay un FAQ publicado…» | Sale cuando `Llms_Faq::read(idioma)` no está vacío (`class-admin.php:403`). En docthinks existe `llm/es/preguntas-frecuentes.md` y `wookb-faq` está `synced`: lo generó el propio asistente en su primera pasada, y al reabrir el paso aparece. El texto engaña (parece algo previo a instalar) y «se amplía/mejora» no lo he verificado en el código. | Texto confuso |
| 6 | Se creó doc en EN de «TestTesti» con el check desmarcado | Confirmado: el grupo WPML (trid 110029) tiene `es:10070`, pero **el post 10070 no existe en `wp_posts`** (fila huérfana). Sin original en español, el plugin genera el documento de la traducción que queda (EN). Hay 4 casos más sin original: 10331 (en), 10334 (ca), 10698 (ca), 11547 (en). Es la decisión menor 3 del desarrollador («si no hay original en el idioma principal, el que marque el plugin»). | Comportamiento por diseño, a decidir |
| 7 | Apartado «Idiomas» del .md | Salen «Idioma del documento: Es» y «Disponible en: [Es](url) · [En](url) · [Ca](url)» con **la misma URL en las tres**. Ejemplos: 9776 (tiene en 9773 y ca 9766 publicados) enlaza las tres a `/community-member/9776/`; Vinayak (es 11653) las enlaza todas a la URL `/en/…/vinayakrajesekhar/`. Causa probable (no probada): `get_permalink()` de un post traducido lo reescribe WPML según el idioma actual de la petición (cron/AJAX) en vez del idioma del post. Además el usuario pide quitar «Idioma del documento» y dejar solo «Disponible en». | Bug + decisión |
| 8 | 406 elementos × 1 idioma = 406 | Es `count(Scope::resolve_ids())` (`tab-carga-inicial.php`), que cuenta **todas las traducciones**. 406 coincide exactamente con la suma de contenidos publicados en los 3 idiomas. Con un `.md` por contenido debería contar solo originales (~142 + huérfanos ≈ 147). La cola sí procesa solo originales (152 filas). | Bug de conteo |

## Decisiones del usuario (2026-09-25)

1. Contenido sin original en el idioma principal (huérfanos como TestTesti): **se genera** el documento en el idioma que exista.
2. Apartado «Idiomas»: **solo «Disponible en: …»**, sin «Idioma del documento», con nombre completo del idioma y URLs correctas. Con una sola versión no se escribe el apartado.
3. Idioma principal: **la pregunta libre se sustituye por el campo estructurado** (principal + idiomas de la web), en Negocio y en el asistente. El texto del prompt y de `llms.txt` se deriva de él; se migra el texto libre ya guardado.
4. FAQ: **reescribir el aviso** (fecha de generación y qué ocurre al guardar de nuevo). Antes, verificar en el código qué hace realmente al guardar.

Además, sin decisión que tomar (corrección directa): nombres de idioma completos (punto 3), URLs por idioma (punto 7), conteo del alcance solo con originales (punto 8) e idiomas en el asistente (punto 4).

## Ajustes añadidos por el usuario (mientras prueba)

- **Check «Crear por idioma»:** hoy comparte formulario con los demás campos de idiomas. Separarlo en **su propio formulario**, con enlace o ancla propios en la pestaña. Guardado por AJAX.
- **Botón «Reiniciar todo» tras guardar el check:** al guardar el cambio del check (AJAX), debe aparecer ahí mismo el botón «Reiniciar todo», sin ir a Carga inicial. Hoy solo sale un aviso.
- **Aviso repetido en Carga inicial:** el aviso «Has cambiado la configuración de idiomas… usa «Reiniciar todo» en la pestaña Carga inicial…» sigue saliendo **dentro de Carga inicial** (`tab-carga-inicial.php` llama a `Admin::render_language_regen_notice()`), donde ya no tiene sentido mandar a esa pestaña. Ahí debe mostrar el botón «Reiniciar todo» junto al aviso, con texto adaptado (sin «usa la pestaña Carga inicial»). En Negocio, el aviso lleva enlace a esa pestaña o el botón directo (ver punto anterior).
- El texto de ayuda del check menciona «Support Genix»: revisar la redacción («Genix sigue este ajuste»).

- **Documentos de CPT que ya no están en el alcance siguen en el registro** (prueba 2, check marcado, alcance = page + product): quedan 85 de community-member (es 80, en 3, ca 2), 5 de film y 19 de post, y se cuentan en el total («Total de documentos: 189»). «Reiniciar todo» solo borra por check/puentes, no por alcance. **Decidido (usuario): sí, en todo «Reiniciar todo»** (siempre, no solo tras cambiar el check o el alcance), «Reiniciar todo» borra también los documentos cuyo tipo ya no está en el alcance, y avisa antes de cuántos borra (confirmación previa con el número). No toca FAQ, tienda, Negocio, artículos de Genix ni filas en modo manual.
- **«75 elementos × 3 idiomas = 225» sigue mal con el check marcado:** los 75 ya incluyen las traducciones (68 páginas + 7 productos, en los tres idiomas), y se multiplican otra vez por 3. Lo real son ~75 documentos posibles. Mismo fallo que el punto 8, ahora con el check.

- **El apartado «Idiomas» sigue igual con el check marcado (prueba 2):** en el `.md` catalán de «Newsletter» sale «Idioma del document: Ca» y «Disponible en: [Es](http://docthinks.loca…» (pegado cortado). Se repiten los tres fallos del punto 7: nombre como código, «Idioma del documento» (a quitar) y URLs de otro idioma. Además el cuerpo del `.md` catalán enlaza la URL `/en/newsletter/`. Comprobar al corregir que el enlace del cuerpo también sea el del idioma del documento.

## Causas raíz investigadas (2026-09-25, solo lectura)

1. **URLs de otro idioma (puntos 7 y Newsletter):** confirmado. El post 10265 es `ca` (grupo WPML 120285: es 10261, en 10264, ca 10265) y su `.md` tiene `product_url` y el enlace del cuerpo en `/en/newsletter/`. `get_permalink()` devuelve la URL del **idioma actual de la petición** (WPML reescribe), no la del post; en la cola (Action Scheduler) el idioma actual es el que tenga ese contexto, por eso unos salen en ES y otros en EN. Afecta a **más que el apartado «Idiomas»**: `product_url`, el enlace «Más información» y `Languages::versions()`/`post_url()`. Corrección: obtener la URL con `wpml_permalink` indicando el idioma del post (o cambiando de idioma alrededor de `get_permalink`), en un solo sitio del proveedor WPML.
2. **Todas las versiones con la misma URL:** consecuencia de lo anterior, más que `versions()` lista los idiomas con `translation_id` existente pero la URL sale del mismo `get_permalink`. Se arregla con la misma corrección.
3. **Conteo del alcance:** `Scope::resolve_ids()` devuelve todos los posts publicados de todos los idiomas (`all_languages_query_args()`) sin quedarse con originales. Corrección: sin el check, reducir a un id por contenido (original, o el que exista si no hay original); con el check, todos los existentes. Y `tab-carga-inicial.php` no debe multiplicar por número de idiomas en ningún caso (el id ya es una traducción concreta).
4. **Nombre «Ca»/«Es»:** viene de `native_name` de WPML en este sitio. Corrección: usar la tabla propia de nombres y solo aceptar el del proveedor si no parece el código.

## Pruebas hechas en docthinks

- **`<head>` de traducción, check desmarcado: OK.** `/en/newsletter/` anuncia `es/newsletter-10261.md` (el `.md` del original).
  - Matiz: en la versión EN la URL del `.md` sale con prefijo de idioma (`/en/ai-knowledge-doc/es/newsletter-10261.md`), porque `home_url()` pasa por WPML. Funciona (200, `text/plain`), pero es una URL distinta para el mismo `.md`. Ajuste: construir la URL del `.md` sin prefijo de idioma para que sea una sola URL canónica (misma corrección de fondo que las URLs de idioma). Lo mismo puede pasar con `llms.txt` en `describedby`.
- **Genix (chatbot): no concluyente.** Los documentos de Newsletter, Aviso legal y Comunidad existen, están publicados y con contenido correcto, pero Genix responde «no tengo información» incluso en español. Incidencia de Genix, aparte del plan de idiomas.

## llms.txt (revisado 2026-09-25)

- El plugin **escribe un `llms.txt` físico** en la raíz (`class-llms-txt.php::write_physical`, en cada `invalidate()`); en docthinks `/llms.txt`, `/en/llms.txt` y `/ca/llms.txt` son idénticos (17783 bytes) y `/es/llms.txt` da 404 (idioma por defecto sin prefijo).
- **Prefijo de idioma dentro del archivo:** los enlaces de API y feeds salen como `/ca/wp-json/...` porque se generó bajo el idioma actual de esa petición. Un solo archivo global con URLs en un idioma cualquiera. Corrección: generar siempre bajo el idioma principal (o sin prefijo).
- Sigue saliendo «## Idiomas / Idioma del documento: Es / Disponible en: [Es](…) · [En](…) · [Ca](…)»: aplicar la decisión 2 (sin «Idioma del documento», nombres completos) también aquí.
- Sigue diciendo «Idiomas: Español, Ingles y Catalan» (pregunta libre; decisión 3).
- Cabeceras «(CA)/(EN)/(ES)» y «Páginas» con CPT viejos: efecto de los documentos fuera de alcance; desaparecen con la limpieza.

## Informe externo (TranslatePress, supershippingwoo.com)

«Genera automáticamente `/es/llms.txt` aunque exista un `/llms.txt` personalizado; la versión localizada ignora el contenido manual y genera una descripción antigua.» Tiene sentido: con TranslatePress, `/es/llms.txt` no es el archivo físico (que solo se sirve en la raíz) sino la ruta dinámica del plugin (`maybe_serve` → `build()`), que ignora el archivo manual. No reproducido aquí (docthinks usa WPML). **Decisión de diseño (coherente con «una única fuente de verdad»):** no existe `llms.txt` localizado; cualquier `/xx/llms.txt` sirve el mismo contenido que `/llms.txt` (el archivo físico si existe, y si no el dinámico). Entra en la fase de TranslatePress.

## Prueba 3 en docthinks — versión 1.3.1.1 instalada (2026-09-25)

OK: 1 (URLs y nombres por idioma correctos), 4 (tras «Reiniciar todo»: 30 documentos, todos ES), 5 (el `/es/` de `ai-knowledge-doc/es/…` es la carpeta del `.md`, no un prefijo de idioma; correcto), 6, 7, 10 (80 documentos: ES 30 = 27 + 3 propios, EN 26, CA 24; cola 77), 11, 12, 13 (con check, `<head>` de `/en/newsletter/` → `en/newsletter-10264.md`), 14, 15, 16.
Pruebas 2 (Negocio/info.md) y 3 (conteo de Carga inicial): OK (usuario, 2026-09-25).

Ajustes nuevos (pendientes de decidir/hacer):
- **Selector de idiomas (Negocio y asistente):** ofrece una **lista fija de 10 idiomas** (`native_names()`), aunque el sitio tenga WPML. Es arbitraria: un idioma extra que no esté en la lista no se puede poner. Propuesta: con plugin de idiomas, «Idioma principal» solo entre los idiomas detectados y «Idiomas de la web» = los detectados (no editable o solo para quitar); sin plugin, principal = idioma de WordPress (desplegable con el locale) y otros idiomas escritos a mano (texto libre), sin lista fija. Mismo comportamiento en Negocio y en el asistente.
- **Check «Crear por idioma»:** en Negocio «se quedó mal». Propuesta (usuario): moverlo a **Carga inicial / generación masiva**, junto al botón «Reiniciar todo», porque es un modo de generación y su consecuencia es reiniciar. Solo visible con WPML/Polylang. Revisar también el asistente.
- **Botón «Añadir a la base de conocimiento» del editor** (`class-editor-metabox.php::handle_add`): al pulsarlo en una traducción (EN) de un CPT fuera de alcance, el usuario acaba en otro CPT (interview) y el contenido no aparece en la base de conocimiento. Hipótesis (sin verificar): se añaden al alcance el tipo entero y el **id de la traducción**, pero se encola el documento del **original** (`document_target`); y «Ver en el Registro» busca por el título de la traducción, que no coincide con el del original. Reproducir con pasos exactos antes de corregir.

### Decisiones del usuario sobre los ajustes nuevos (2026-09-25)

- **Selector de idiomas:** con plugin de idiomas se ofrecen **los idiomas detectados** y, además, el usuario puede **añadir a mano** otros. Sin plugin: idioma de WordPress + los que escriba a mano. Sin lista fija de 10. Igual en Negocio y en el asistente.
- **Check «Crear por idioma»:** **se mueve a Carga inicial** (generación masiva), junto a «Reiniciar todo». Solo con WPML/Polylang. Quitarlo de la pestaña Negocio. **En el asistente, el check SÍ se muestra en su paso de Negocio** (decisión del usuario, 2026-09-25; hoy el asistente no lo tiene): mismo control y mismo aviso con «Reiniciar todo», solo con WPML/Polylang.
- **Carpeta del idioma principal (`llm/es/…`):** el usuario pregunta si se puede no crear la carpeta para el idioma principal. Técnicamente sí (`Markdown_Store::relative_path()` construye siempre `{idioma}/{slug}.md`), pero cambia las URL ya publicadas y exige migración y redirecciones. Recomendación: mantener la carpeta. **Decidido (usuario): se mantiene `llm/{idioma}/`.**

### Botón del editor: investigado

Posts probados: 11762 (es) y 11739 (en), ambos `community-member` de «Seleme Aydin» (grupo WPML 120442, con ca 11760). En el estado actual de docthinks: el tipo no está en el alcance (`post_types` = page, product, project), `id_actions` vacío y no hay documentos de esos posts.
Causas en el código (`class-editor-metabox.php::handle_add`, `class-scope.php::is_included`):
1. Un «ID a incluir» ya fuerza el contenido **sin importar su tipo** (`is_included`), pero `handle_add` añade además **el tipo entero** al alcance: pulsar el botón en un solo contenido mete todos los `community-member` (259) en el alcance.
2. Se incluye el **ID del post donde se pulsa** (la traducción EN), pero el documento que se encola es el del **original** (`document_target`). Si el original no está incluido por sí mismo, no se genera o no aparece.
3. La redirección final usa `get_edit_post_link($post_id)` sin el parámetro `lang`, así que WPML puede devolver al usuario a otro idioma o contexto.
4. «Ver en el Registro» busca por el título del post actual, que puede no coincidir con el del documento del original.
Corrección propuesta: no añadir el tipo; incluir el ID del **original** (y el actual si el check está marcado); volver a la misma pantalla de edición con su `lang`; buscar en el Registro por el ID/título del documento real.
**Causa confirmada del botón (2026-09-25):** el metabox pinta un `<form>` **dentro** del formulario del editor (`post.php`). Los formularios anidados no existen en HTML: el navegador ignora el interior y el botón envía el formulario del editor con `action=wookb_editor_add_to_kb`. `post.php` no conoce esa acción y cae en su `default:`, que hace `wp_redirect( admin_url( 'edit.php' ) )` (comprobado en `wp-admin/post.php` del sitio). Por eso el usuario acaba en `/wp-admin/edit.php`. La BD lo confirma: el clic no cambió el alcance (`id_actions` vacío) ni encoló nada (última acción de 11762: 14:03 GMT, anterior al clic). `handle_add` nunca se ejecuta. Corrección: **no usar `<form>` en el metabox**; botón `type="button"` con AJAX (el plugin ya tiene `ajaxActions` en `assets/admin.js`) o equivalente, con nonce y capability. Las cuatro causas anteriores siguen siendo válidas para cuando el handler sí se ejecute.

Verificar tras corregir: pulsar en 11762 y en 11739 con el check desmarcado y marcado.

## Prueba 4 en docthinks — tanda 3 (2026-09-25)

OK: 1 a 11, 13 (Registro), 14 (con check marcado se generan el documento del original y el de la traducción: 11536 es y 11530 en) y 15 (consola).
Prueba 12 («los documentos salen en inglés desde es y desde en»): **no es un fallo del botón.** En la BD `wookb_language_settings` tiene `main = en` (guardado explícitamente; WPML por defecto es `es`) y `per_language = 1`, así que el «original» de cada contenido es la versión inglesa: desde 12347 (es) se generó `rainbowg-12346` (en) y desde 12503 (en) `eduardo-gutierrez-12503` (en). Es lo previsto con idioma principal English. Pendiente de confirmar con el usuario si el principal en inglés fue una prueba del selector; si no, volver a Español en Negocio. Estado de prueba dejado en docthinks: `id_actions` con 5 IDs incluidos (11739, 12503, 12346, 11530, 11536) y `extra` = ja, ru.

## Estado

**Cerrado el 2026-09-25.** Las tres tandas de ajustes están aplicadas y
probadas con WPML en docthinks (todo OK; la prueba 12 del botón del editor era
el idioma principal en inglés, no un fallo). Lo que sigue pendiente (Genix y
otros plugins de idiomas) está en `roadmap.md`. El plan y su estado están en
`estrategia-idiomas.md`.
