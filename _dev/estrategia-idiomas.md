# Estrategia de idiomas

Estado: acordada funcionalmente el 2026-09-24. **No implementada.**

## Principio

Una entidad = un documento principal. Los idiomas son versiones de acceso a
esa misma entidad, no contenidos distintos. El plugin no traduce: es una
capa de conocimiento para IA con una única fuente de verdad.

## Cómo está hoy (verificado en el código)

- Solo detecta WPML. Sin WPML da por hecho el idioma `es` (`Wpml::
  active_languages()` y `Wpml::element_language()`), y el prompt de la IA
  dice "Idioma de salida: ES". Polylang y TranslatePress no se detectan.
- Con WPML se genera un `.md` por idioma (`wp-content/llm/{idioma}/`). Si
  falta la traducción, se crea un documento puente sin IA con enlaces.
- `llms.txt` es un único archivo, con secciones "(ES)/(EN)" si hay varios
  idiomas.
- Chatbot: con WPML se crea un documento de Genix por idioma. Sin idioma
  registrado, Genix lo excluye al buscar por idioma.
- FAQ, tienda y Negocio ya se generan solo en el idioma principal, con una
  frase "también disponible en…" **sin enlaces**.
- Páginas y productos: bloque "Disponible también en" con enlaces a las
  traducciones reales.

## Evidencia externa

- La especificación de `llms.txt` no dice nada sobre idiomas
  ([llmstxt.org](https://llmstxt.org/)); hay una propuesta abierta sin
  respuesta ([issue #108](https://github.com/AnswerDotAI/llms-txt/issues/108)).
- Cada proyecto lo resuelve distinto: un `llms.txt` por idioma con un índice
  ([Rebelytics](https://www.rebelytics.com/creating-a-scalable-international-llms-txt-structure-step-by-step/))
  o solo el idioma por defecto
  ([módulo de Drupal](https://git.drupalcode.org/project/llms_txt/-/work_items/3548950)).
- ChatGPT, Perplexity y Claude no siguen bien `hreflang`; Copilot y Gemini
  lo hacen mejor
  ([gsqi](https://www.gsqi.com/marketing-blog/ai-search-hreflang-multilingual-queries/)).
  El propio autor avisa de que no es una ciencia exacta.

## Reglas acordadas

1. **Negocio manda.** En la página de Negocio se declaran el idioma principal
   y los idiomas de la web. Se rellena sola si hay WPML y es editable. Si
   Negocio y WPML discrepan, gana Negocio.
2. **Lo que genera el propio plugin** (FAQ, tienda, Negocio) va siempre en el
   idioma principal, igual que `llms.txt`.
3. **Todos los `.md` llevan un apartado "Idiomas"**: idioma del documento y
   "Disponible en: …" con enlaces cuando sea posible.
   - Sitio de un solo idioma: se pone igualmente una línea "Idioma: …". El
     idioma sale del idioma de WordPress, no de un valor fijo.
4. **Check en Negocio: "Separar contenido por idioma"**. Solo afecta a
   páginas, entradas y productos con traducción real.

## Qué pasa según Genix y el check

| | Con Genix | Sin Genix |
|---|---|---|
| Check desmarcado | Se crean igualmente los documentos por idioma para el chatbot. `llms.txt` y lo público llevan solo el idioma principal | Solo documentos en idioma principal |
| Check marcado | Igual, y los de otros idiomas también salen en `llms.txt` | Se crean por idioma y salen en `llms.txt` |
| Siempre | Apartado "Idiomas" en todos los `.md` | Igual |

## Decisiones cerradas

1. Los documentos por idioma que existen solo para el chatbot (check
   desmarcado): no listados en `llms.txt`, no anunciados en el `<head>`, no
   servidos por URL. Sí sincronizados con Genix.
2. Instalaciones que ya generan por idioma: al actualizar, el check aparece
   **marcado** para no cambiar su `llms.txt` de golpe. En instalaciones
   nuevas, desmarcado.
3. Un solo idioma: línea "Idioma: …" tomada del idioma de WordPress.
4. Idioma principal: gana Negocio sobre WPML.

## Limitaciones

- Sin WPML no hay traducciones reales de las que extraer texto: el check no
  tiene efecto y solo queda el apartado "Idiomas", enlazando a la portada de
  cada idioma.
- Enlaces: con WPML, enlace exacto a la traducción en páginas y productos;
  en FAQ, tienda y Negocio, enlace a la portada de cada idioma.

## Fases previstas

1. Idioma principal y lista de idiomas en Negocio; arreglar el `es` fijo
   usando el idioma de WordPress.
2. Apartado "Idiomas" unificado, con enlaces, en todos los `.md`.
3. El check y su efecto en `llms.txt`, en el `<head>` y en el acceso por URL
   (decisiones 1 y 2).

## Pendiente de validar

- Que los documentos por idioma sincronizados con Genix funcionan sin
  aparecer en `llms.txt`.
- Si las IA siguen de verdad los enlaces "Disponible en" (evidencia débil).

## Fuera de alcance por ahora

- Integración con TranslatePress y Polylang. Se hará en fases, WPML primero.
- El problema de TranslatePress del informe de supershippingwoo.com: se
  trata aparte.

## Arquitectura (propuesta en discusión, sin decidir)

Idea: un sistema global de idiomas, en vez de parches por plugin de
traducción. Pendiente de que el usuario lo piense y decida.

### Punto de partida (verificado en el código)

- Todo el plugin pasa por una sola clase, `Wpml`, con 8 métodos. Usos:
  `active_languages` 14, `get_translation_id` 11, `element_language` 10,
  `default_language` 8, `languages_note` 3, `set_language` 2, `get_trid` 2,
  `is_active` 1.
- 7 archivos llaman a WPML directamente, sin pasar por esa clase:
  `class-chatbot-language-fix.php`, `class-chatbot-relevance-guard.php`,
  `class-doc-redirect.php`, `class-document-pipeline.php`,
  `class-llms-txt.php`, `class-scope.php`, `class-store-info-doc.php`.
- En el Local de trabajo (`plugins.local`) **WPML no está activo** y no hay
  Polylang ni TranslatePress instalados. Para probar cualquiera de los tres
  hace falta un sitio de pruebas aparte.

### Servicio de idiomas con un contrato único

El resto del plugin le pregunta al servicio; nunca a un plugin de traducción
concreto. Un solo sitio conoce WPML, Polylang o TranslatePress.

1. **Idioma principal**: Negocio; si no, el del plugin de traducción; si no,
   el de WordPress. Elimina el `es` fijo.
2. **Lista de idiomas**: código, nombre nativo y URL de portada.
3. **Idioma de un contenido.**
4. **Traducción de un contenido**: su ID, o nada.
5. **URL exacta de un contenido en otro idioma.**
6. **Capacidades**: hay un post por idioma, puede enlazar exacto, puede
   traducir texto. El check "separar por idioma" solo se activa si hay un post
   por idioma.

Proveedores: WPML, Polylang, TranslatePress y ninguno, con detección
automática.

### Qué se sabe de cada proveedor

| | Modelo | Estado |
|---|---|---|
| WPML | Un post por idioma | Es el código de hoy. No se puede probar en el Local actual, porque WPML no está activo |
| Polylang | Un post por idioma | Funciones documentadas: `pll_languages_list`, `pll_default_language`, `pll_get_post_language`, `pll_get_post`, `pll_home_url` ([doc](https://polylang.pro/doc/function-reference/)). Encaja casi 1 a 1 con WPML |
| TranslatePress | Traduce al vuelo | `trp_translate($contenido, $idioma)` y una función para la URL traducida ([doc](https://translatepress.com/docs/translation-function/)). Hay un hilo de "trp_translate no funciona" ([hilo](https://wordpress.org/support/topic/trp_translate-function-not-working/)): hay que probarlo. Es el origen del problema del informe |

### Orden propuesto

0. Extraer el servicio sin cambiar comportamiento: el proveedor WPML es el
   código de hoy y las llamadas directas a WPML pasan por él. No cambia nada
   visible.
1. Idioma principal y lista de idiomas desde Negocio (fase 1 de arriba).
2. Apartado "Idiomas" con enlaces en todos los `.md` (fase 2 de arriba).
3. Proveedor Polylang.
4. Proveedor TranslatePress: primero lista de idiomas y URLs, luego el texto
   traducido de los endpoints (problema 1 del informe).
5. El check y su efecto en `llms.txt` (fase 3 de arriba).

### Riesgos y requisitos

- El paso 0 toca 8 archivos y afecta a WPML: antes hay que activar WPML en un
  sitio de pruebas.
- Polylang y TranslatePress hay que instalarlos, mejor en un sitio de Local
  aparte y no en el de `plugins`.
- Polylang filtra las consultas por idioma actual: el alcance
  (`Scope::resolve_ids()`) necesitará cuidado.

### Preguntas abiertas para el usuario

- ¿El contrato de 6 operaciones encaja o le falta algo?
- ¿Se empieza por el paso 0 o por los pasos 1 y 2, que dan valor visible
  antes?
