# Contexto activo

## Plugin objetivo

`ai-knowledge` (antes `woo-kb-generator`), rama `knowBaseDev`, repo
`github.com/22MW/ai-knowledge`. Ruta:
`app/public/wp-content/plugins/ai-knowledge/`.

## Estado de documentación e idiomas — 2026-09-20

- La interfaz dispone de español (`es_ES`), catalán (`ca`), alemán (`de_DE`),
  inglés (`en_US`) y francés (`fr_FR`).
- `docs/index.md` y `_dev/docs/index.md` explican los idiomas disponibles e
  indican que se contacte con el equipo si falta un idioma o hay una traducción
  incompleta o incorrecta.
- Los planes y documentos temporales se conservan en `_dev/temp/`; la
  documentación vigente permanece fuera de esa carpeta.

## Asistente de configuración — implementación inicial — 2026-09-20

- Añadido el estado persistente `aikb_setup_assistant`, sin duplicar ajustes
  del plugin ni almacenar credenciales.
- Añadidos menú propio, enlace desde la fila del plugin y apertura única tras
  activación para usuarios con la capability existente.
- Implementada navegación con progreso, continuar, atrás, saltar, salir,
  reanudación y finalización.
- WooCommerce y Chatbot aparecen solo cuando sus dependencias están activas;
  cada paso enlaza a la pantalla completa existente.
- Pendiente de QA manual en WordPress: activación, permisos, reanudación,
  modo oscuro, enlaces de configuración y flujo con JavaScript desactivado.

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

## Próximo cambio documentado — Internacionalización

El diseño quedó acordado en otro hilo, pero todavía no se ha implementado:

- Español como idioma base.
- `languages/ai-knowledge.pot` como plantilla y
  `languages/ai-knowledge-es_ES.po` como primer catálogo.
- Todos los strings visibles del plugin deben pasar por gettext o por la
  internacionalización JavaScript, incluidos botones, errores, avisos y AJAX.
- La documentación seguirá en español dentro de `docs/`.
- Las traducciones futuras podrán vivir en `docs/{locale}/`; el panel deberá
  usar ese archivo cuando exista y volver a `docs/` cuando no exista.
- No crear copias traducidas de los `.md` ahora.

Estado actualizado el 2026-09-19: creados `languages/ai-knowledge.pot`,
`languages/ai-knowledge-es_ES.po`, el `.mo` y el JSON JavaScript generado.
`assets/admin.js` usa `wp.i18n`, el bootstrap carga el text domain y el panel
de documentación aplica el fallback por locale. Falta QA visual real con un
locale alternativo y completar/revisar las traducciones del `.po`.

Actualización posterior: los catálogos inglés y catalán, sus `.mo` y sus JSON
JavaScript están completos e incluidos en el release `1.1.3`. Falta únicamente
QA visual real cambiando el locale de WordPress.

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

## Navegación admin

- «Carga inicial» se muestra como «Generación masiva»; el slug interno
  `carga-inicial` se conserva para no romper enlaces ni redirecciones.
- Orden acordado: WooCommerce (condicional), Chatbot (condicional),
  Visibilidad IA, Generación masiva y Ajustes al final.
- Visibilidad IA ya no muestra referencias internas a fases. Sus textos
  distinguen con claridad las comprobaciones de solo lectura y las acciones
  que modifican robots.txt o .htaccess.

## Rename interno completado (Fase 0.1, 2026-09-16)

- Ya no queda rastro de "woo-kb-generator" en código: archivo principal
  `ai-knowledge.php`, namespace `AIKB\`, constantes `AIKB_*`, slug de menú
  `page=ai-knowledge`. Detalle completo en `_dev/roadmap.md` (Fase 0.1) y
  `_dev/decisiones.md`.
- Sin tocar por compatibilidad: tabla `wookb_documents` (valor string),
  funciones `wookb_activate()`/`wookb_deactivate()`, opción `wookb_db_version`,
  query args `wookb_notice`/`wookb_regen_error`/`wookb_sync_status`,
  `assets/wookb-theme.css` y su handle de enqueue.

## Documentación pública + técnica (Fase 0.2, actualizada 2026-09-18)

- `docs/` (11 archivos): `index.md`, `instalacion.md` y un `.md` por
  cada una de las 9 tabs del admin, tono VerifacWOO, enlazados entre sí,
  con FAQ al final de cada uno. 13 etiquetas `[SCREENSHOT]`/`[VIDEO]`
  pendientes de sustituir por medios reales antes de publicar en la web.
- Cada pestaña del admin ofrece «Leer documentación» y abre su guía en un
  panel lateral derecho de aproximadamente el 50% de la pantalla, con
  Markdown convertido a lectura normal y sanitizado.
- `_dev/documentacion-tecnica.md`: documento técnico para desarrolladores,
  se queda en el plugin, no se publica.
- De paso, corregido `readme.txt` (tabs "Alcance"/"Exclusiones" → ya
  fusionadas en "Contenido").
- Sin commit ni push todavía.
- Reactivado en Local por el usuario tras el rename del archivo principal
  (WordPress lo desactiva automáticamente al cambiar esa ruta). Confirmado
  OK.
- Sin commit ni push todavía: cambio hecho por el subagente `desarrollador`,
  pendiente de revisión de diff y aprobación antes de subir.
- QA real pendiente: solo se validó `php -l` y greps estáticos, más la
  activación manual. Falta probar en real cada tab/redirect del admin y una
  generación de documento completa.

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

## Fix — Alcance vacío para todos los post_types (2026-09-17, v1.1.0.1)

- Bug real confirmado en real (feeds `/feeds/products.xml` y
  `/feeds/content.json` de Fase 9 devolvían vacío sin error): con más de
  una taxonomía en `term_actions` a la vez (p.ej. `category` para posts y
  `product_cat` para productos), `Scope::resolve_ids()` construía un
  `tax_query` sin `relation`, que `WP_Query` trata como `AND` — exigía
  ambas taxonomías a la vez y dejaba el alcance vacío para todo: no solo
  los feeds, también generación de documentos y `/llms.txt`.
- Fix: `'relation' => 'OR'` cuando hay más de una taxonomía con términos
  incluidos. Verificado en real contra `https://plugins.local` (feeds
  pasaron de vacíos a con contenido real).
- Pendiente de decidir junto al diff de `is_included()` ya presente antes
  de este fix (ver más abajo, "Cambio actual — Conectores WordPress 7.0"
  no lo menciona; ese diff toca IDs forzados a incluir, no relacionado con
  este bug de `tax_query`).

## Relevo mínimo — siguiente paso

Confirmar commit + push del fix de `tax_query` (v1.1.0.1) y del diff
pendiente de `is_included()`. Después: probar la pestaña WooCommerce en
real (checkboxes, snapshot editable, pulido con IA) y decidir el
siguiente foco (UX3/UX4 u otra tarea).

## Release 1.1.1 — 2026-09-18

- Documentación de pestañas movida a `docs/` con panel lateral de lectura.
- Guardados simples y generación/pulido con IA disponibles por AJAX con
  fallback tradicional y spinner.
- QA visual confirmado por el usuario para el spinner; queda pendiente la
  validación completa de proveedores, WooCommerce y errores reales de IA.
- Release preparado localmente; no implica deploy.
- La GitHub Release y el ZIP están publicados usando el tag `1.1.1`.
- El merge automático del script hacia `main` quedó bloqueado por conflictos
  de rutas `_dev/` y `docs/`; el merge fue abortado y no se forzó una
  resolución sin decisión sobre esos archivos.

## Release 1.1.2 — preparación

- Incluye navegación contextual desde títulos del admin hacia secciones de
  documentación, enlaces internos con anclas y ajustes finales de contraste.
- QA técnico pendiente de ejecutar antes de publicar el release.

## Cambio posterior — fecha de llms.txt (2026-09-19)

- `llms.txt` incluye la fecha y hora de su última generación en formato ISO
  8601, también cuando se escribe como archivo físico.
- Validado con `php -l` y `git diff --check`; pendiente probar el contenido en
  el sitio real.

## Cambio posterior — filtros de crawlers (2026-09-19)

- La tabla de Visibilidad IA permite filtrar sin recarga por tipo y por estado
  (`permitidos`, `bloqueados` o `sin decidir`), combinando ambos criterios.
- El catálogo incorpora herramientas SEO/scraping, buscadores tradicionales,
  archivado/datasets y scanners automatizados, todos configurables por bot.
- `.htaccess` agrupa condiciones con el mismo comportamiento y coloca el bloque
  propio antes de WordPress, también en la descarga del archivo propuesto.
- La tabla muestra inicialmente 10 crawlers y permite desplegar el resto con
  «Ver todos»; los filtros siguen buscando en el catálogo completo.
- `Amazonbot` queda permitido por defecto y se elimina el estado «Sin decidir»;
  cualquier valor histórico `ask` se normaliza a `allow`.
- Validación técnica ejecutada; queda pendiente comprobar visualmente los
  filtros y aplicar ambos modos sobre un `.htaccess` real con copia previa.

## Implementado por `desarrollador` — plan `_dev/plan-publicacion-ajax-prompts.md` (2026-09-23)

Piezas 1, 2, 3 (solo confirmación), 4 y 5 implementadas sobre `knowBaseDev`.
`php -l` y `git diff --check` OK en todos los archivos tocados. Sin commit,
sin push, sin cambio de versión. Detalle completo en el informe del
subagente; resumen aquí:

- **Pieza 1 (AJAX "Generar")**: `regenerate_single()` responde JSON si
  `wp_doing_ajax()`; el JS actualiza estado/fecha/enlaces/contenido de la
  fila sin recargar. Fallback tradicional intacto.
- **Pieza 2 (prompt por documento)**: columna `custom_prompt` añadida al
  `CREATE TABLE` de `Registry::create_table()`. **BLOQUEO/pendiente real**:
  como el sitio ya está instalado, esa columna solo se crea de verdad cuando
  `wookb_db_version` (comparado con `AIKB_VERSION`) cambie — es decir, con un
  **bump de versión** en un release. El subagente `desarrollador` tiene
  prohibido tocar versión/release, así que el código está listo pero el
  campo `custom_prompt` NO existe todavía en la tabla real hasta ese paso.
  Sin la columna, el textarea guarda vacío en silencio (upsert sobre una
  columna inexistente) y `custom_prompt` en generación se lee como null
  (fallback seguro al prompt genérico, sin romper nada, pero sin efecto
  real). Acción pendiente del usuario/Jefe de Proyecto: decidir cuándo
  incluir este bump en un release y ejecutar `preparar-release`.
- **Pieza 3**: confirmado por lectura, sin cambios: `Generator::
  resolve_char_limit()` sigue aplicándose igual con las piezas 1-2 encima.
- **Pieza 4 (auditoría "solo publicado")**: bug real confirmado y corregido
  en `Sync::on_product_saved()`/`on_generic_post_saved()` (un post que pasa
  a borrador sin pasar por la papelera no borraba su documento; el `.md`
  seguía sirviéndose y apareciendo en `llms.txt`). Añadido el mismo freno
  post_status=publish, ya presente en `Queue::run_generate()`, a
  `Admin::regenerate_single()`, `force_generate()` y `reset_queue()` (los
  tres llamaban a `Document_Pipeline::process()` directo, sin ese filtro).
- **Pieza 5 (Genix exclusivo)**: `Genix_Reader`, `Genix_Markdown`,
  `Genix_Publish` nuevas, gateadas por `post_type_exists('sgkb-docs')`.
  Excluye correctamente los `sgkb-docs` que ya son `doc_post_id` de una fila
  del Registro (el bug de la vez anterior). **Corregido tras revisión del
  usuario (2026-09-23)**: el criterio de "¿puede publicarse?" es el meta
  real `only_for_chatbot` que escribe Genix (support-genix-lite) —
  `Genix_Reader` también excluye del listado cualquier artículo con ese
  meta activo, y `Genix_Publish::publish()` lo rechaza como freno
  redundante. Se quitó el checkbox manual "Público" y el status inventado
  `hidden`: ahora "publicado" = existe fila en Registry (status siempre
  `synced`), sin tocar `Registry::get_synced_public_urls()`. UI: botones
  "Generar contenido"/"Actualizar contenido" y "Quitar" (AJAX + fallback
  tradicional) en la sección "Artículos exclusivos de Genix" al final de
  Ajustes. `Registry::query()/count()/query_all_ids()` siguen excluyendo
  `source_type='sgkb-docs'` por defecto (`apply_registry_scope()`) para que
  no se mezclen en la tabla del Registro.

### Ajuste de UX posterior (2026-09-23), pedido por el usuario tras verlo en pantalla

- "Ajustes avanzados" de una fila del Registro dividido en dos sub-pestañas
  ("Contenido"/"Prompt") dentro del mismo `<details>`, sin AJAX (solo
  mostrar/ocultar con JS), mismas clases `nav-tab`/`nav-tab-active` que ya
  usan las pestañas grandes del admin.
- Los botones "Guardar límite" y "Guardar cambios" (ya existían antes de
  esta tarea) pasan a AJAX con el mismo patrón que "Generar" (Pieza 1):
  extraído `Admin::registry_row_ajax_data()` compartido entre
  `regenerate_single()`, `set_manual()` y `set_char_limit()`.
- Pendiente, NO forzado (pedido explícitamente así por el usuario si no era
  trivial): los botones de Genix (`Generar contenido`/`Actualizar
  contenido`/`Quitar`) siguen recargando la página tras guardar — llevarlos
  al mismo tratamiento en caliente exige reconstruir la celda de acciones
  completa (nonces nuevos incluidos), no es un cambio trivial como el resto.

### Ajuste de UX posterior (2026-09-23, ronda 2): ubicación del aviso de guardado

- El aviso de "guardado"/error de Generar, Guardar límite, Guardar cambios y
  Guardar prompt (fila del Registro) ahora sale DENTRO del bloque "Ajustes
  avanzados" de esa fila (slot `[data-wookb-row-notice]`, justo debajo de las
  sub-pestañas Contenido/Prompt), no en otro punto de la pantalla. Antes
  usaba `$form.after()`, que colocaba el aviso lejos porque esos `<form>`
  viven "fuera de banda" (ver `render_out_of_band_forms()`).
- Los botones de Genix (Generar/Actualizar contenido, Quitar) no se
  tocaron: sus `<form>` ya están inline en la misma celda de la tabla (no
  fuera de banda), así que `$form.after()` ya colocaba el aviso justo al
  lado del botón — verificado por lectura, sin necesidad de cambio.

### Ajuste de UX posterior (2026-09-23, ronda 3): mover Genix exclusivo a su propia pestaña

- Pestaña "Chatbot" renombrada a **"Genix"** (solo la etiqueta visible,
  `Admin::tabs()`). Slug interno `prompt` sin cambiar a propósito: lo usan
  `self::redirect('prompt')`, `documentation_map()['prompt']` y el enlace
  del asistente de configuración (`admin.php?page=ai-knowledge&tab=prompt`)
  — cambiarlo rompería esos enlaces sin necesidad, el usuario solo pidió la
  etiqueta.
- Sección "Artículos exclusivos de Genix" (Pieza 5) movida de
  `admin/views/tab-ajustes.php` a `admin/views/tab-prompt.php` (al final,
  debajo del prompt del chatbot), con el mismo gate
  `Genix_Reader::is_available()` que tenía antes. Quitada por completo de
  Ajustes.

### Documentación actualizada (2026-09-23) — docs/ (usuario final, sin jerga técnica)

Revisados todos los archivos de `docs/`. Actualizados: `index.md`,
`instalacion.md`, `tab-ajustes.md`, `tab-chatbot.md`, `tab-contenido.md`,
`tab-faqs.md`, `tab-negocio.md`, `tab-registro.md`, `tab-visibilidad-ia.md`.
Resumen: "Chatbot" renombrado a "Genix" en todo el texto (el archivo sigue
llamándose `tab-chatbot.md` a propósito, para no romper enlaces existentes
de otros documentos); añadida la sección "Artículos exclusivos de Genix" en
`tab-chatbot.md`; documentado en `tab-registro.md` el botón "Generar"
instantáneo, las sub-pestañas Contenido/Prompt de "Ajustes avanzados", el
prompt propio por documento, "Guardar límite"/"Guardar cambios" también
instantáneos, y que un post que pasa a borrador desaparece del Registro y
de `/llms.txt` solo; nota en `tab-contenido.md` sobre por qué los "Docs" de
Genix no aparecen como tipo de contenido; nota en `tab-visibilidad-ia.md`
sobre las secciones "Páginas"/"Documentación" dentro de `/llms.txt`.
`_dev/documentacion-tecnica.md` no se tocó (es aparte, para desarrolladores).

### Ajuste de UX posterior (2026-09-23, ronda 4): avisos de guardado AJAX pasan a "toast"

- Nueva función centralizada `showToast(message, type)` en `assets/admin.js`
  (al principio del archivo, expuesta también como `window.wookbShowToast`).
  Usada por el único punto donde ya se centralizaban todos los guardados
  AJAX del admin (Ajustes, Negocio, FAQs, WooCommerce, crawlers, Generar,
  Guardar límite/cambios/prompt, Genix) — no hubo que tocar cada acción por
  separado.
- CSS nuevo en `assets/wookb-theme.css` (`#wookb-toast-container`,
  `.wookb-toast*`): arriba a la izquierda, `position: fixed`, 10s y
  desaparece sola (o clic en la X). Reutiliza `--tblr-success`/
  `--tblr-danger` (mismas variables que ya usa `.wookb-assistant-feedback`
  para éxito/error), no las de `.notice-success/.notice-error` (esas son un
  azul propio del proyecto, no semánticas de éxito/error).
- Quitado el slot fijo `data-wookb-row-notice` (código muerto de la ronda
  anterior) de `admin/class-registry-table.php` y toda su lógica de
  localización en `admin.js`.
- El fallback sin JavaScript (recarga con `wookb_notice=1`, gestionado en
  PHP por `Admin::redirect()`/`Admin::render()`) no se tocó: sigue igual.

### Ajuste de UX posterior (2026-09-23, ronda 5): Volver a Auto por AJAX + botón "Guardar cambios" con estado

- "Volver a Auto" añadido también a la pestaña "Prompt" (mismo `<form>`
  `wookb_back_to_auto` que ya usaba Contenido, solo un botón más apuntando
  a el).
- `Admin::back_to_auto()` ahora responde AJAX (`wp_doing_ajax()` +
  `registry_row_ajax_data()`, mismo patrón que `set_manual`/
  `set_char_limit`), registrado en `$ajax_actions` y en el whitelist de
  `admin.js`.
- "Guardar cambios" empieza deshabilitado (atributo `disabled` puesto por
  PHP) y se activa/añade `.wookb-btn-success` (reutilizada, no inventada)
  en cuanto el textarea difiere de su valor original
  (`data-wookb-original-value`); se desactiva de nuevo al guardar/
  regenerar/volver a Auto con éxito, o si el usuario deshace los cambios a
  mano. Botón "Generar" con `.wookb-btn-success` siempre (criterio: no hay
  "dirty state" que detectar ahí, así que se aplica de forma fija; el
  estado "procesando" ya existente sigue deshabilitándolo mientras
  trabaja).
- Limitación conocida, no abordada (fuera del alcance pedido): tras
  "Volver a Auto" por AJAX, los elementos que dependen de `override_mode`
  (aviso "Este documento está en modo Manual" y los propios botones
  "Volver a Auto") no se ocultan solos sin recargar — solo se actualiza
  estado/fecha/enlaces/contenido de la fila, no el HTML condicional de
  "Ajustes avanzados". Una recarga de la pestaña los refleja bien.

### Rediseño del asistente de configuración (2026-09-24)

Implementado el diseño del `arquitecto` (aprobado por el usuario, 2 decisiones
ya resueltas) en `admin/class-admin.php` y `admin/views/tab-carga-inicial.php`.
Cada paso genera su documento en cuanto se guarda, en vez de todo junto al
final:

- Orden nuevo: welcome → ai → limits (antes "finish") → content → business →
  woocommerce → faqs (paso NUEVO) → chatbot → visibility → server → finish
  (reescrito como RESUMEN de solo lectura) → success.
- `assistant_ai_available()` (nuevo helper): `AI_Client::available_models()`
  no vacío O `Chatbot_Prompt::is_genix_ready()`. Gatea el paso `faqs` y decide,
  dentro de `assistant_save_step()`, si `woocommerce`/`faqs`/`chatbot` generan
  contenido real o solo guardan datos con aviso (nunca bloquea, nunca fatal —
  mismo patrón de captura de errores que `sync_store_docs()`).
- `content` ahora llama a `Queue::start_seed()` al guardarse (después de
  `limits`, que ya dejó guardado el límite diario/tamaño de lote).
- `woocommerce` llama a `Store_Info_Doc::generate_all()` si hay conexión.
- `faqs` (nuevo paso) genera y PUBLICA de una vez por idioma activo
  (excepción explícita y confirmada por el usuario a la norma de "revisar
  antes de publicar", solo para este paso del asistente).
- `chatbot` llama a `Chatbot_Prompt::sync(true)` si hay conexión.
- Paso `finish` rediseñado: solo lecturas baratas (Registry, Llms_Faq::read,
  Chatbot_Prompt::read, filas de Store_Info_Doc) — nunca dispara nada, porque
  `assistant_screen_content()` se precalcula para TODOS los pasos disponibles
  en cada carga de la página (`Admin::assets()`), no solo al guardar.
- Nuevo `Admin::render_seed_controls()`: extrae los botones "Generar
  pendientes"/"Reiniciar todo" (antes solo en `tab-carga-inicial.php`) para
  reutilizarlos también en el resumen del asistente — mismos handlers de
  `admin-post.php`, sin duplicar lógica, solo el marcado.
- Limpieza de código muerto: quitada la acción `'generate'` (botón "Generar
  documentos iniciales" ya no existe) de `assistant_panel()`,
  `render_assistant()` y `assistant_navigate()` — el bug real que esto
  corregía (`Llms_Faq::persist_doc()` solo se llamaba en el camino sin JS,
  nunca en AJAX) ya no aplica: el paso `faqs` genera y publica siempre por el
  mismo camino (`assistant_save_step()`), sea AJAX o no.
- Bug real encontrado y corregido durante la implementación (no estaba en el
  diseño original): `assistant_save_step('ai')` escribe su propio
  `ai_connection` en la opción `aikb_setup_assistant` vía `get_option()`/
  `update_option()` propios; sin releer esa opción justo después en
  `render_assistant()`/`assistant_navigate()`, el `update_option()` de más
  abajo (con el `$state` capturado ANTES de la llamada) lo sobrescribía y lo
  perdía. Corregido con un `$state = get_option(...)` de refresco en los dos
  sitios.

### Catálogos de idioma actualizados (2026-09-24)

Comparados los 5 `.po` (`ca`, `de_DE`, `en_US`, `eu`, `fr_FR`) contra
`languages/ai-knowledge.pot` (ya regenerado por la sesión principal,
confirmado estable: `wp i18n make-pot` no cambia nada al repetirlo). Añadidas
125 entradas nuevas por idioma (las del rediseño del asistente + Genix +
toasts + demás cambios de hoy) más 1 entrada suelta que ya faltaba de antes
("Mostrar menos crawlers", solo en `de_DE`/`eu`/`fr_FR` — `ca`/`en_US` ya la
tenían). Solo adiciones, ninguna traducción existente tocada ni borrada
(`git diff --stat` confirma 0 líneas eliminadas en los 5 archivos). Validado
con `wp i18n make-mo` hacia un directorio temporal (sin tocar los `.mo`
reales) — los 5 `.po` compilan sin error. No se tocó ningún `.mo`, ningún
`.json` de JS ni el propio `.pot`.

### QA pendiente (todo, no se ha probado en real)

Ver lista de pruebas manuales en el informe del subagente. Ninguna de las 5
piezas ni el ajuste de UX posterior se ha probado en el navegador todavía.
