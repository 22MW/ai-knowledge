# Estrategia de idiomas

Estado: plan fijo acordado el 2026-09-25. **Fases 0 a 6 implementadas el
2026-09-25 en la rama `knowBaseDev`, sin commitear y sin probar** (ver «Estado
por fase»). Falta ejecutar la matriz de pruebas. Sustituye a la versión del
2026-09-24 (un `.md` por idioma siempre que hubiera WPML).

## Objetivo

- Un sistema genérico, válido con cualquier plugin de idiomas o sin ninguno.
- El mínimo de archivos `.md`.
- Ahorrar pruebas: pedir a cada plugin de idiomas lo mínimo, para no tener
  que probar a fondo cada uno.

El plugin no traduce. Es una capa de conocimiento para IA con una única
fuente de verdad.

## Principio

**Un contenido = un `.md`, en el idioma principal.** Todas las versiones de
esa página (ES, EN, DE…) enlazan a ese mismo `.md`, y el `.md` dice en qué
idiomas existe la página y con qué URL.

Crear un `.md` por idioma es opcional (check «Crear por idioma») y solo
existe si el plugin de idiomas crea un post por idioma.

## Cómo está hoy (verificado en el código, 2026-09-25; estado ANTES de implementar)

- Solo detecta WPML, a través de la clase `Wpml` (8 métodos). Sin WPML se
  usa `es` fijo (`class-wpml.php`) y el prompt dice «Idioma de salida: ES»
  (`class-generator.php`).
- Con WPML se genera un `.md` por idioma en `wp-content/llm/{idioma}/`. Si
  falta la traducción, se crea un documento puente sin IA.
- Cada traducción anuncia en el `<head>` su propio `.md`
  (`class-markdown-discovery.php`).
- `llms.txt` es un solo archivo, con sufijos «(ES)/(EN)» si hay varios
  idiomas.
- Genix recibe un documento por idioma. La nota de idiomas ya existe en el
  prompt del chatbot (`class-chatbot-prompt-builder.php`,
  `Wpml::languages_note()`).
- FAQ, tienda y Negocio se generan solo en el idioma principal, con una
  frase «también disponible en…» sin enlaces.
- 7 archivos llaman a WPML directamente, sin pasar por la clase `Wpml`:
  `class-chatbot-language-fix.php`, `class-chatbot-relevance-guard.php`,
  `class-doc-redirect.php`, `class-document-pipeline.php`,
  `class-llms-txt.php`, `class-scope.php`, `class-store-info-doc.php`.
  **Corrección al verificarlo (2026-09-25):** 5 de esos 7 llamaban de verdad
  a filtros de WPML (`wpml_current_language`, `wpml_object_id`): language-fix,
  relevance-guard, doc-redirect, llms-txt y store-info-doc. `Document_Pipeline`
  usaba la clase `Wpml` (no WPML directo) y `Scope` solo lo nombraba en
  comentarios y en la exclusión de metadatos `_wpml_`. Otros archivos también
  usaban `Wpml::` (queue, sync, generator, admin, vistas…): todos pasan ahora
  por el servicio.
- Polylang y TranslatePress no se detectan.

## Decisiones cerradas

1. **Idioma principal e idiomas de la web se declaran en Negocio.** Si hay
   plugin de idiomas, se detectan y se rellenan solos; el usuario puede
   editarlos. Orden: Negocio → plugin de idiomas → idioma de WordPress. Se
   elimina el `es` fijo.
2. **Por defecto, un solo `.md` por contenido**, en el idioma principal.
   Todas las traducciones enlazan a él en el `<head>`.
3. **Todos los `.md` llevan un apartado «Idiomas»**: idioma del documento y
   «Disponible en: …» con enlace a cada versión. En un sitio de un solo
   idioma, una línea «Idioma: …».
4. **Check «Crear por idioma»** en Negocio:
   - Solo aparece si el plugin de idiomas crea un post por idioma (WPML,
     Polylang u otro igual). No aparece con TranslatePress ni sin plugin.
   - Desmarcado por defecto en instalaciones nuevas.
   - Marcado: se crea un `.md` por idioma **solo para las traducciones que
     existen**. Sin documentos puente. Esos `.md` salen en `llms.txt` y en el
     `<head>` de su traducción.
5. **Genix es un extra y no fuerza nada.** Sigue al check: desmarcado, un
   documento por contenido; marcado, uno por traducción existente.
6. **Chatbot:** responde en el idioma del usuario. Todos los documentos
   llevan el texto de disponibilidad en otros idiomas, y el mensaje de
   sistema del chatbot también.
7. **Instalaciones que ya generan por idioma:** al actualizar, el check
   aparece marcado, para no cambiar nada de golpe.
8. **Cambiar el check** (marcar o desmarcar) muestra un aviso: hay que usar
   «Regenerar todo». Al regenerar se borran los `.md` por idioma y los
   documentos puente que ya no correspondan.
9. Lo que genera el propio plugin (FAQ, tienda, Negocio) va siempre en el
   idioma principal, igual que `llms.txt`.

## Servicio de idiomas (contrato mínimo)

El resto del plugin pregunta al servicio, nunca a un plugin de idiomas
concreto. Solo el servicio conoce WPML, Polylang o TranslatePress.

1. Idioma principal (según la decisión 1).
2. Lista de idiomas: código, nombre nativo y URL de portada.
3. Idioma de un contenido.
4. Original de un contenido: de una traducción, el post de origen.
5. URL de un contenido en otro idioma.
6. Capacidad: ¿crea un post por idioma? Decide si aparece el check.

Se descarta «traducir texto»: el plugin no extrae texto traducido.

| Proveedor | Modelo | Qué aporta |
|---|---|---|
| Ninguno | Un idioma | Idioma de WordPress o de Negocio. Sin check |
| WPML | Un post por idioma | Es el código de hoy. Con check |
| Polylang | Un post por idioma | `pll_languages_list`, `pll_default_language`, `pll_get_post_language`, `pll_get_post`, `pll_home_url` ([doc](https://polylang.pro/doc/function-reference/)). Con check |
| TranslatePress | Un post, traducido al vuelo | Lista de idiomas y URL traducida ([doc](https://translatepress.com/docs/translation-function/)). Sin check |

## Fases de desarrollo

0. **Extraer el servicio sin cambios visibles.** El proveedor WPML es el
   código de hoy; las llamadas directas de los 7 archivos pasan por él.
   Añadir el proveedor «ninguno».
1. **Idioma principal e idiomas en Negocio**, con detección automática y
   edición. Quitar el `es` fijo en el pipeline, el prompt y
   `class-doc-redirect.php`.
2. **Un solo `.md` por defecto.** Todas las traducciones apuntan en el
   `<head>` al `.md` del original. Apartado «Idiomas» con enlaces en todos los
   `.md`. Genix: un documento por contenido. Nota de idiomas ampliada en el
   mensaje del chatbot.
3. **Check «Crear por idioma»**, visible solo con la capacidad 6. Marcado:
   un `.md` por traducción existente, sin puentes. Aviso al cambiarlo y paso
   por «Regenerar todo», que borra lo que sobra.
4. **Migración:** en instalaciones que ya generan por idioma, marcar el check
   al actualizar.
5. **Proveedor Polylang.** Cuidado: Polylang filtra las consultas por idioma
   actual; revisar `Scope::resolve_ids()`.
6. **Proveedor TranslatePress:** solo lista de idiomas y URL. Sin check.

## Estado por fase (2026-09-25)

Implementadas en un solo pase, en orden, sin commit ni prueba real. Servicio en
`includes/class-languages.php` (`AIKB\Languages`); proveedores
`class-wpml.php` (ahora es el proveedor WPML), `class-polylang.php`,
`class-translatepress.php` y `class-no-language-plugin.php`, sobre la base
abstracta `class-language-provider.php`.

| Fase | Estado | Qué cambia |
|---|---|---|
| 0 | Hecha, sin probar | Servicio + proveedor WPML (código de hoy) + «ninguno». Todas las llamadas (`Wpml::`, `wpml_*`, `SitePress`) pasan por `Languages`. |
| 1 | Hecha, sin probar | Sección «3. Idiomas» en Negocio (idioma principal, idiomas de la web, check). Orden Negocio, plugin, WordPress. Fuera el `es` fijo (pipeline, prompt, `Doc_Redirect`, guard del chatbot). |
| 2 | Hecha, sin probar | Un `.md` por contenido (el del original); `<head>` de las traducciones apunta a él; apartado «Idiomas» en todos los `.md`; Genix sigue el check; nota de idiomas en el mensaje de sistema. |
| 3 | Hecha, sin probar | Check «Crear por idioma» solo con la capacidad 6; sin puentes; aviso persistente; «Reiniciar todo» borra lo que sobra. |
| 4 | Hecha, sin probar | `Languages::maybe_migrate()`: una sola vez, marca el check si ya había documentos de contenido en un idioma distinto del principal. |
| 5 | Hecha, sin probar ni instalar | Proveedor Polylang (API `pll_*`); `Scope::resolve_ids()` pide `lang => ''`. |
| 6 | Hecha, sin probar ni instalar | Proveedor TranslatePress: lista de idiomas y URL traducida; sin check. La API se ha escrito de memoria de su documentación: comprobar en una instalación real. |

Nombre del botón: el plan dice «Regenerar todo»; en el admin es «Reiniciar todo»
(Carga inicial). Es ese botón el que limpia y el que apaga el aviso.

Decisiones menores tomadas al implementar (reversibles):

- Ajustes en la opción `wookb_language_settings` (`main`, `languages`,
  `per_language`), no en el cuestionario de Negocio. La pregunta libre
  «Idioma principal del negocio» sigue como texto descriptivo del prompt.
- Contrato ampliado con auxiliares pequeños: traducción de un post, de un
  término, idioma del visitante, `trid` y asignación de idioma a los sgkb-docs.
- Original de un contenido: la traducción en el idioma principal si existe; si
  no, el original que marque el plugin (WPML) o el propio post.
- El hash del documento incluye las versiones por idioma solo si hay más de
  una (no regenera con IA los sitios de un solo idioma).
- Cambiar el idioma principal también deja el aviso «Reiniciar todo».
- «Reiniciar todo» no borra filas en modo manual ni artículos de Genix ni
  documentos propios (FAQ, tienda).
- La FAQ y los documentos de tienda solo se editan/generan en el idioma
  principal (se quita el selector de idioma de la pestaña FAQs).
- `llms.txt` añade el sufijo «(ES)» solo si los documentos publicados están en
  más de un idioma.
- Sin plugin de idiomas y con documentos en un único idioma, la migración
  conserva ese idioma como principal (antes era `es` fijo).
- La nota de idiomas del mensaje de sistema se añade al sincronizar con Genix
  (no al `.md` editable) y solo si cabe en el límite de 2000 caracteres.
- Textos del apartado «Idiomas» del `.md` en es, en, de, ca, fr, eu (inglés
  para otros).

### Ajustes tras la prueba en docthinks (2026-09-25, sin probar)

Detalle en [`ajustes-idiomas-docthinks.md`](ajustes-idiomas-docthinks.md).
Esto sustituye a lo dicho antes cuando lo contradice:

- La pregunta libre `idioma_principal` ya no existe: el idioma principal y los
  idiomas de la web son un campo estructurado (Negocio y asistente), migrado
  una vez desde el texto libre (`Languages::maybe_migrate_language_answer()`).
- El apartado «Idiomas» solo lleva «Disponible en: …» y no se escribe con una
  sola versión (antes: «Idioma: …» / «Idioma del documento»).
- «Crear por idioma» tiene formulario propio (AJAX) con botón «Reiniciar
  todo»; la ayuda ya no habla de «Support Genix».
- URLs por idioma vía `Languages::permalink()`; `.md` y `llms.txt` sin prefijo
  de idioma; `llms.txt` bajo el idioma principal y sin versión localizada.
- `Scope::resolve_ids()` reduce a un id por contenido sin el check.
- Nombres de idioma: tabla propia primero.
- «Reiniciar todo» borra también los documentos de tipos fuera del alcance.

Riesgos abiertos (para las pruebas):

- Con un solo `.md` por contenido y WPML, la búsqueda nativa de Genix filtra
  por el idioma actual del visitante: puede no encontrar el documento del
  idioma principal cuando el visitante navega en otro idioma. Comprobar la fila
  «Chatbot / Desmarcado».
- Polylang: los sgkb-docs solo reciben idioma si el CPT es traducible en
  Polylang.
- TranslatePress: API no verificada contra una instalación.

## Pruebas (al final)

Se ejecutan cuando estén las fases 0 a 6. Matriz:

| Escenario | Check | Genix | Qué comprobar |
|---|---|---|---|
| Sin plugin de idiomas | No aparece | Sí / No | Idioma de WordPress o Negocio, línea «Idioma: …», sin `es` fijo |
| WPML | Desmarcado | Sí / No | Un `.md` por contenido; todas las traducciones lo enlazan; apartado «Idiomas» con URL exactas |
| WPML | Marcado | Sí / No | Un `.md` solo por traducción existente, sin puentes; en `llms.txt` y en el `<head>` |
| WPML | Cambio de check | Sí | Aviso; «Regenerar todo» borra los sobrantes |
| WPML (instalación antigua) | Tras actualizar | Sí | Check marcado, nada cambia |
| Polylang | Desmarcado / Marcado | No | Igual que WPML; alcance correcto |
| TranslatePress | No aparece | No | Un `.md`, URL por idioma correctas |
| Chatbot | Desmarcado | Sí | Responde en el idioma del usuario con un único documento |

Dónde probar, instalando lo mínimo:

- Sin plugin: el Local `plugins`.
- WPML: está instalado en el Local `plugins`; comprobar si está activo antes
  de probar. Mejor en una copia o un sitio aparte.
- Polylang y TranslatePress: un sitio Local aparte, uno cada vez.

Pendiente de validar en las pruebas:

- Si las IA siguen de verdad los enlaces «Disponible en» (evidencia débil:
  ChatGPT, Perplexity y Claude no siguen bien `hreflang`,
  [gsqi](https://www.gsqi.com/marketing-blog/ai-search-hreflang-multilingual-queries/)).

## Evidencia externa

- La especificación de `llms.txt` no trata los idiomas
  ([llmstxt.org](https://llmstxt.org/); propuesta abierta,
  [issue #108](https://github.com/AnswerDotAI/llms-txt/issues/108)).

## Fuera de alcance

- Traducir contenido o extraer texto traducido.
- El problema de TranslatePress del informe de supershippingwoo.com: se
  trata aparte.
- Traducciones (`.po`) de los textos nuevos: aplazadas, como el resto.
