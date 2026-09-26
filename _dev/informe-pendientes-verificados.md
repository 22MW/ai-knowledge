# Informe de pendientes verificados con el código real

Plugin: `ai-knowledge` 1.3.2 · rama `knowBaseDev` · árbol limpio (`git status` sin cambios) · 2026-09-25.

**Método.** Lectura del código actual y comprobaciones con `grep`. No se ha ejecutado nada en un sitio real ni se ha cambiado ningún archivo del plugin. Lo que no se puede saber solo con el código se marca como `No puedo confirmarlo con seguridad con los datos actuales`.

**Resultado global.** Ninguna de las 11 tareas está corregida: ni el `CHANGELOG.md` ni los commits recientes las mencionan, y el código lo confirma. Una es parcial (1), otra ya funciona en la pestaña pero no en el asistente (2) y una tiene una premisa que el código no respalda (9).

Referencias `archivo:línea` sobre el estado actual. `A` = `admin/class-admin.php`.

---

## Resumen

| # | Tarea | Estado | Riesgo del arreglo |
|---|---|---|---|
| 1 | Asistente: comprobar conexión de IA | Parcial: solo mira si hay algo configurado, no si conecta | Bajo |
| 2 | WooCommerce: autorrellenar | Pestaña sí, asistente no | Bajo |
| 3 | Error al descargar robots.txt | Vivo; causa exacta sin confirmar | Medio (escribe en la raíz) |
| 4 | robots.txt «raro» entre pestaña y asistente | Misma lógica; solo cambia lo que se muestra. Riesgo real al crear el archivo | Medio |
| 5 | Asistente → sincronizar chatbot | Vivo: no sincroniza nada si no existe el prompt | Medio |
| 6 | Cola sin WooCommerce / sin Action Scheduler | Vivo: el texto es falso y hay un fallo de estado | Medio (toca la cola) |
| 7 | Aviso «resumen de 37 caracteres» | Vivo, explicado | Bajo |
| 8 | Aviso «Support Genix Lite ha perdido los filtros» | Vivo y real en este entorno | Alto (parchea archivos de un tercero) |
| 9 | Resumen final y documentos en cola | Vivo: explicado casi todo; una cifra no cuadra | Medio |
| 10 | Check «borrar todo al desinstalar» | Vivo; hoy borra muy poco | Alto (destructivo) |
| 11 | Strings Permitir/Bloquear → Permitido/Bloqueado | Vivo | Mínimo |

---

## 1. Asistente, paso 2: comprobar conexión con la IA

**Estado: parcial.**

**Qué hace hoy**
- El paso pinta un aviso verde o naranja según `assistant_ai_available()` (A:382-387). Esa función devuelve `true` si hay modelos de Conectores de WordPress o si el módulo de Genix está cargado (A:492-498). El comentario que la precede admite que no hay prueba real de conexión (A:482-491).
- Tras guardar, el panel se vuelve a pintar y el aviso se recalcula (A:525-529). Esa parte de la tarea ya existe.
- Al guardar el paso se escribe `ai_connection` en el estado (A:553-559), pero nadie lo lee (`grep`: solo aparece escrito, A:222, A:513, A:554).
- La pestaña Ajustes (`save_settings`, A:1293-1328) tampoco comprueba nada.

**Problema real.** `assistant_ai_available()` no coincide con lo que decide si se genera de verdad: `AI_Client::config()` (`includes/class-ai-client.php:69-103`). Esta mira el origen elegido, el modelo y, con Genix, la clave OpenAI de Genix (`GetOpenAIConfig()`). Con Genix activo pero sin esa clave, el asistente diría «conexión disponible» y después cada generación fallaría con «El origen de IA seleccionado no está conectado» (`class-ai-client.php:111`). Genix Lite usa `ai_proxy` por defecto (`support-genix-lite/traits/Apbd_wps_knowledge_base_chatquery_trait.php:75`); si `GetOpenAIConfig()` devuelve vacío en ese modo, el desajuste es real (probable, no probado).

**Solución propuesta**
1. Sustituir `assistant_ai_available()` por `AI_Client::config() !== null`. Es la misma condición que usa el generador y no hace red.
2. Mensaje con el motivo: «WordPress < 7.0», «sin modelos en Conectores», «Genix sin clave OpenAI», «modelo ya no disponible».
3. Botón opcional «Probar conexión» por AJAX, con una llamada mínima (pocos tokens), solo bajo demanda y con el resultado en un transient corto.
4. Mostrar el mismo estado tras guardar el paso y tras guardar Ajustes.
5. Quitar `ai_connection`, que no se usa.

**Riesgos**
- Una prueba real cuesta tokens y puede fallar por timeout sin que la configuración esté mal: no automatizarla y no bloquear nunca (decisión 2026-09-24).
- Cambiar la función mueve la visibilidad del paso FAQs (A:478) y las ramas de WooCommerce, FAQs y chatbot: algunas web que hoy «intentan y fallan» pasarán a «guardan sin generar». Es correcto, pero es un cambio de comportamiento.

---

## 2. WooCommerce: rellenar automáticamente lo que se pueda

**Estado: la pestaña sí lo hace; el asistente no.**

**Evidencia**
- Pestaña (`admin/views/tab-woocommerce.php:37-63`): precarga nombre (`get_bloginfo`), moneda en vivo («EUR (€)»), país (etiqueta) y extracto de las páginas de condiciones y devoluciones, y lo guarda como snapshot editable.
- Asistente (A:394): pinta `$settings[$key]` tal cual. Si nunca se guardó, salen vacíos. Al guardar el paso graba cadenas vacías (A:602-611).
- La lógica de precarga vive dentro de la vista, no se puede reutilizar.
- El paso también deja sin marcar «Recogida en tienda» aunque la pestaña sí sabe detectar el método «Recogida local».

**Hallazgo relacionado.** `Store_Info_Doc::generate_all()` no usa IA: compone los documentos de forma determinista (`includes/class-store-info-doc.php:8-25`; `grep` sin llamadas a `AI_Client`). Aun así, el asistente solo lo ejecuta si hay conexión de IA (A:614) y el texto del paso lo anuncia así (A:335). Sin IA, no se generan documentos de tienda sin necesidad.

**Solución propuesta**
1. Extraer los valores en vivo a un único helper (p. ej. `Admin::woocommerce_live_defaults()`) que usen la pestaña y el asistente.
2. Precargar en el asistente con el mismo criterio que la pestaña (guardado → si no, en vivo).
3. Quitar la condición de IA en el paso WooCommerce (A:614).
4. Lo que WooCommerce no sabe (plazo de entrega, contacto y horario de la tienda) sigue a mano.

**Riesgos**
- Bajo. Cambia qué valores se muestran y cuándo se guarda el snapshot. Decisión pendiente: si el asistente debe guardar el valor en vivo (como la pestaña, que lo congela) o dejarlo vacío mientras coincida con el vivo.
- La moneda se guarda como texto («EUR (€)») y el país como etiqueta, no como código. Convertirlos a códigos sería un cambio de datos: fuera de esta tarea.

---

## 3. robots.txt: error al descargar la copia en el asistente

**Estado: vivo. Causa exacta del fallo: no puedo confirmarla.**

**Cómo llega el error**
- El botón del asistente no usa `fetch`: `admin.js:564-582` crea un formulario oculto y lo envía por POST a `admin-post.php`. Cuando el servidor hace `wp_die`, el navegador navega a esa pantalla; por eso se vio la URL `…/admin-post.php` con el mensaje.
- El manejador (A:2869-2891) lee el archivo físico si existe y, si no, hace `wp_remote_get( home_url('/robots.txt') )`, es decir, una petición HTTP contra el propio sitio. El `wp_die` con «No se pudo leer el robots.txt actual (ni físico ni virtual)…» (A:2878) solo salta cuando esa petición devuelve `WP_Error`. El mensaje no incluye el error real.
- No se comprueba el código HTTP: un 404 o 500 se descargaría como si fuera la «copia» (A:2880).
- Sin archivo físico no hay nada que respaldar: el virtual lo genera WordPress y se recupera borrando el físico. Aun así, se exige descargar la copia antes de aplicar (A:2904).
- Al pulsar «Descargar», el JS habilita «Actualizar robots.txt» antes de saber si la descarga funcionó (`admin.js:573`). El servidor sigue exigiendo la copia, así que no hay riesgo de escritura sin respaldo, pero el botón parece listo y luego falla.

**Por qué falla la petición en `docthinks.local`**: `No puedo confirmarlo con seguridad con los datos actuales`. Candidatos (sin probar): certificado autofirmado de Local, resolución de nombre desde PHP o timeout. Hace falta el `get_error_message()` real.

**Riesgo real de «crear desde cero» (verificado en el código)**
- `Robots_Txt_Guard::is_available()` ya admite crear el archivo si la raíz es escribible (`includes/class-robots-txt-guard.php:35-41`) y `apply_actions()` usa `insert_with_markers()` (`:95-98`), que crea el archivo.
- `generate_full_file()` parte de cadena vacía cuando no hay físico (`:209-227`). El archivo creado contendría solo el bloque de AI Knowledge. Con un robots.txt físico presente, el servidor lo sirve directamente (comportamiento habitual de Apache y Nginx; no probado aquí) y WordPress deja de generar el virtual: se perderían `Disallow: /wp-admin/`, `Allow: /wp-admin/admin-ajax.php`, las reglas de WooCommerce y la línea `Sitemap`.

**Solución propuesta**
1. Leer el robots virtual sin HTTP, capturando `do_robots()` con `ob_start()` (incluye lo que añaden WooCommerce y los plugins SEO por el filtro `robots_txt`). Una única función para pestaña, asistente y descarga.
2. Sin archivo físico: no exigir copia y mostrar «Crear robots.txt», ya habilitado. Contenido = el virtual real de WordPress + el bloque de AI Knowledge. Ese es el «mínimo necesario» del robots.txt básico de WP.
3. Con archivo físico: se mantiene la copia obligatoria, como hoy.
4. Errores: mostrar el mensaje real, validar código 200 y no dejar al usuario en una pantalla `wp_die` del asistente (avisar en el propio paso).
5. Habilitar «Actualizar» solo cuando la descarga haya terminado bien.

**Riesgos**
- Escribe en la raíz del sitio, fuera del plugin: recomendar staging y copia antes.
- Un robots.txt físico congela el virtual: reglas futuras de plugins SEO o de WooCommerce dejarían de aplicarse. Hay que decirlo en el aviso.
- `do_robots()` emite cabeceras y dispara `do_robotstxt` que otros plugins usan: capturarlo dentro de un buffer y probar con un plugin SEO activo.
- Reversible: borrar el archivo físico devuelve el virtual.

---

## 4. «Desde la pestaña sí reconoce el robots.txt, en el asistente es raro»

**Estado: no hay dos lógicas distintas; sí un riesgo real (el del punto 3).**

**Evidencia**
- Las dos rutas leen igual: físico si existe; si no, `wp_remote_get` al virtual (`admin/views/tab-visibilidad-ia.php:55-62` y A:352-357).
- Lo que se ve («User-agent: * Disallow: /wp-content/uploads/wc-logs/ … Allow: /wp-admin/admin-ajax.php») es el robots virtual de WordPress más WooCommerce, no un archivo. Por eso «no veo ningún físico» es coherente.
- La diferencia es de presentación. La pestaña enseña el contenido actual y la vista «después del cambio». El asistente solo dice «difieren/coinciden» (A:366, A:369) y su texto declara que no muestra el código completo (A:339).
- Ineficiencia: la pestaña hace la petición HTTP siempre, incluso con archivo físico, porque `wp_remote_get` va antes del `if` (`tab-visibilidad-ia.php:55` frente a `:56`).

**Solución propuesta**
- Con el helper del punto 3, mostrar también en el asistente el bloque «Actual» con su origen: «Generado por WordPress (no hay archivo físico)» o «Archivo físico».
- Mover la petición al `else` en la pestaña, o eliminarla con el helper.

**Riesgos.** Bajo: solo lectura. Mismo patrón visual que el textarea del FAQ del asistente (sin estilos nuevos).

---

## 5. Asistente: sincronizar con el chatbot como al guardar en la pestaña

**Estado: vivo.**

**Evidencia**
- Pestaña Genix: el borrador con IA (A:2160) y el guardado (A:2232-2253) escriben `chatbot-system-prompt.md`, sincronizan (`Chatbot_Prompt::sync(true)`) y regeneran `info.md`.
- Paso `chatbot` del asistente (A:592-600): guarda respuestas y el límite de documentos relacionados y llama a `sync(true)`. Pero `sync()` lee ese `.md` y devuelve `empty` si no existe (`includes/class-chatbot-prompt.php:104-108`); el asistente no genera ni escribe el `.md` en ningún punto. Sin `.md` previo, no se sincroniza nada y no se avisa. Si el `.md` existe de antes, se sincroniza el texto viejo, ignorando las respuestas nuevas.
- La llamada depende de `assistant_ai_available()` (A:597), pero sincronizar solo necesita Genix listo.
- El resumen final dice «Sincronizado con Genix» si el `.md` existe y Genix está listo (A:1406), sin comprobar el hash de sincronización.

**Solución propuesta**
1. En el paso chatbot, con IA: generar el borrador con `generate_draft()` (el mismo de la pestaña), escribir el `.md`, sincronizar y regenerar `info.md`. Sin IA: guardar respuestas y avisar «prompt pendiente de generar».
2. Si ya hay un `.md`: no sobrescribirlo sin aviso explícito (mismo criterio que el aviso del FAQ del asistente).
3. Mostrar el resultado real de `sync()` (`synced`, `skipped`, `empty`…).
4. El resumen final debe basarse en el hash sincronizado (`wookb_chatbot_prompt_synced_hash`).
5. Anotarlo en `decisiones.md` como excepción del asistente, igual que el FAQ.

**Riesgos**
- Puede pisar un prompt editado a mano.
- Gasta IA.
- El prompt tiene tope de 2000 caracteres (`Chatbot_Prompt_Builder::MAX_LENGTH`).
- En Genix Lite sin el parche, el prompt sincronizado no se aplica (ver punto 8): sincronizar no cambiaría nada en el chatbot.

---

## 6. Sin WooCommerce y sin «schedulers»: ¿cómo se crea la cola?

**Estado: la cola funciona sin ellos; hay un texto falso y un fallo de estado.**

**Evidencia**
- `Queue` usa Action Scheduler si existe (lo trae WooCommerce) y cae a WP-Cron nativo si no (`includes/class-queue.php:19-21`, `:49-60`, `:96-98`, `:136-140`).
- El texto «Generación en curso (procesando por lotes vía Action Scheduler)» es fijo (A:1581) y es falso con WP-Cron.
- **Fallo de estado.** `wookb_seed_running` se activa en `start_seed()` (`class-queue.php:156`) y solo se borra en `cancel_seed()` (`:161`). Cuando `run_seed_batch()` termina, sale con `return` sin limpiarlo (`:114-116`). Resultado: tras acabar, la pantalla sigue diciendo «Generación en curso» y oculta «Generar pendientes» y «Reiniciar todo» (A:1577-1587) hasta pulsar «Cancelar generación».
- **Cancelar no cancela con WP-Cron.** `cancel_seed()` solo llama a `as_unschedule_all_actions` (`:163`). Los eventos `wp_schedule_single_event` de `wookb_seed_batch` siguen vivos y `run_seed_batch()` no consulta el flag, así que sigue encadenando lotes.
- WP-Cron solo corre con visitas. En Local o staging sin tráfico, las filas se quedan «En cola» (el propio código lo reconoce: A:1891-1899 y A:2017-2022).
- El transient `wookb_lock_*` se escribe (`class-queue.php:57-58`) y no se lee en ningún punto del plugin (`grep`).
- No hay comprobación de `DISABLE_WP_CRON` en el plugin (`grep`).

**Solución propuesta**
1. Texto según `Queue::has_action_scheduler()`: «Action Scheduler» o «WP-Cron».
2. Borrar `wookb_seed_running` al terminar el último lote y comprobarlo al inicio de `run_seed_batch()`.
3. `cancel_seed()` también des-programa `wookb_seed_batch` de WP-Cron. Solo el hook de semillas, no el de generación (`wookb_generate_document` también lo usan los guardados normales).
4. Aviso si WP-Cron está desactivado o el sitio no tiene tráfico, con enlace al «Procesar ahora» que ya existe (`reset_queue`, A:1904-1940, lotes de 20).
5. Retirar el lock que no se usa.

**Riesgos.** Toca el núcleo de generación: probar con y sin Action Scheduler, con «Cancelar» a mitad y con un guardado normal durante una semilla.

---

## 7. Aviso «El resumen público del negocio tiene 37 caracteres…»

**Estado: vivo. Qué es y por qué sale:**

- Lo emite `Chatbot_Prompt_Builder::maybe_short_summary_notice()` (`includes/class-chatbot-prompt-builder.php:568-594`), enganchado en `admin_notices` (`class-plugin.php:85`).
- Condición: pantalla `page=ai-knowledge` (todas las pestañas, no el asistente) y `get_business_summary()` con menos de 1000 caracteres (`:32`, `:576-578`).
- `get_business_summary()` (`:162-170`) devuelve el campo «Enfoque del negocio» mientras nunca se haya guardado el resumen público. Los 37 caracteres son ese campo, no un resumen.
- El asistente no genera ni guarda el resumen público: el paso Negocio solo guarda respuestas e `info.md` (A:582-591). Tras el asistente, el aviso persiste.
- El umbral de 1000 es «a ojo» (comentario, `:25-32`) y contradice la decisión del 2026-09-16 en `decisiones.md`: «la longitud se adapta a los datos reales: no se fuerza un mínimo».
- Usa el nombre antiguo «WOO Knowledge Base Generator:» (`:585`; también en `class-genix-hooks-guard.php:78`, `:86`, `:183`).
- Es descartable, pero no hay código que recuerde el descarte: vuelve en cada carga.
- La frase «es de versión vital» de la tarea no se entiende; hay que confirmar qué se quería decir.

**Solución propuesta**
1. Sustituir el nombre antiguo por «AI Knowledge & Visibility».
2. Avisar solo si no existe resumen público propio (la opción `wookb_business_summary`), sin contar el fallback al Enfoque.
3. Umbral: decisión del usuario (quitar el mínimo y avisar solo si está vacío, o bajarlo a un valor razonable).
4. Texto que explique qué es: «cita de apertura pública de `/llms.txt`».
5. Descarte persistente por usuario.
6. Opcional: que el paso Negocio del asistente ofrezca generar el resumen.

**Riesgos.** Bajo. Cambia textos: los `msgid` nuevos requieren actualizar `.pot`/`.po` de `ca`, `de_DE`, `en_US`, `eu` y `fr_FR`; los `.mo` y JSON los genera el usuario (roadmap).

---

## 8. Aviso «Support Genix Lite ha perdido los filtros…»

**Estado: vivo y real en este entorno (no es un falso positivo aquí).**

**Evidencia**
- `Genix_Hooks_Guard::maybe_notice()` (`includes/class-genix-hooks-guard.php:146-192`) hace una comprobación estática: lee el archivo `traits/Apbd_wps_knowledge_base_chatquery_trait.php` de Genix (rutas Lite y Pro, `:96-101`) y busca tres cadenas (`:121-144`). No consulta filtros en ejecución.
- En el trait de Support Genix Lite instalado en este workspace no existen los dos `apply_filters('apbd-wps/filter/chatbot-search-results'…)` ni `chatbot-docs-list`. En las líneas 122 y 224 del trait no hay filtro (`support-genix-lite/traits/…:122`, `:224`). `build_chatbot_system_prompt()` (`:563-651`) no lee `chatbot_custom_instructions`. Faltan los tres parches.
- Consecuencia: `Chatbot_Relevance_Guard::init()` registra los filtros (`includes/class-chatbot-relevance-guard.php:52-55`), pero Genix nunca los dispara. Sin relevancia mejorada, sin gestión de idiomas no soportados y sin límite de documentos relacionados. Además, el prompt del sitio se sincroniza a la opción `chatbot_custom_instructions`, que Lite no lee (lo dice el propio docblock, `class-genix-hooks-guard.php:37-48`).
- El estado de `docthinks.local` no lo he leído: `No puedo confirmarlo con seguridad con los datos actuales` para ese sitio.
- Contradicción interna: `Chatbot_Prompt` afirma que no toca archivos de Genix y que no hace falta ningún filtro ni parche (`includes/class-chatbot-prompt.php:9-26`), pero `Genix_Hooks_Guard::reinstall_single()` modifica el archivo de un plugin de terceros. Documentación desincronizada.
- El parche se pierde en cada actualización de Genix (el docblock lo dice) y el aviso reaparece.
- El silencio de 24 h no funciona: `wookb_genix_hooks_dismissed_at` se lee (`:162`) y se borra (`:342`), pero nunca se escribe (`grep`). El aviso sale en todo wp-admin para quien tenga `manage_options`.
- No puedo confirmar relación con la incidencia de Genix del roadmap («no tengo información»).

**Solución propuesta (decisión del usuario)**
- **A (recomendada ahora).** Mantener el parche pero: aviso solo en pantallas del plugin, descarte real (escribir la opción), nombre corregido y texto que liste qué falta (`missing_filters()` ya lo calcula por archivo), y alinear el docblock de `Chatbot_Prompt`.
- **B (a medio plazo).** Pedir a Genix hooks oficiales y dejar de parchear archivos ajenos.

**Riesgos**
- «Reinstalar filtros» escribe en un plugin de terceros: hace copia en `wp-content/db-backup`, pero puede romper con una actualización si cambian los anclajes textuales (falla con error controlado).
- `check_syntax()` se salta la verificación si `shell_exec` no existe y devuelve `true`, y escribe igualmente (`:311-313`).
- Los cambios en archivos de otro plugin no sobreviven a sus actualizaciones ni a reinstalaciones.

---

## 9. Resumen final del asistente y documentos «en cola»

**Estado: casi todo explicado por el código; una cifra no cuadra.**

**Por qué «nada en proceso» tras el asistente**
- El paso Contenido solo encola (A:574-581). Cada documento se programa a `time() + debounce_seconds` (por defecto 300 s: `class-scope.php:31`, `class-queue.php:45`, `:55`). El primer lote es `batch_size` (por defecto 20, `class-scope.php:30`) y el siguiente se programa a +60 s (`class-queue.php:137`). Por eso justo después no hay nada «Generando» y el número de filas crece con el tiempo.
- Qué pasos sí generan al guardar: WooCommerce (síncrono, A:614-621) y FAQs (A:635-647). Negocio no genera nada con IA (solo `info.md`). Chatbot: ver punto 5. La decisión del 2026-09-24 («cada paso genera su documento al guardarse») se cumple solo en parte.

**La cifra que no cuadra**
- «Documentos registrados» es `COUNT(*)` de toda la tabla; «pendientes» es `COUNT` con estado `queued` excluyendo `sgkb-docs` (`includes/class-registry.php:286-294`, `:252-279`, `:176-181`; usados en A:447-448 y A:1423-1424). Con esas definiciones registrados ≥ pendientes siempre. «Registrados 8 · Pendientes 54» no puede salir en una misma carga: `No puedo confirmarlo con seguridad con los datos actuales` (posible lectura en momentos distintos).
- «Total 103 · EN 1 · ES 102 · Generando 1 · En cola 67 · Listo 35» (cabecera, A:1139-1159) es coherente con lotes que se fueron encolando. El «EN: 1» no puedo explicarlo.
- «Pendientes» ignora los que están «Generando» y los que dieron error: subestima lo que falta.

**Solución propuesta**
1. Pantalla final con desglose por estado (En cola, Generando, Listo, Error) usando el mismo helper que la cabecera del Registro, con enlace al Registro.
2. Aviso claro: «La generación continúa en segundo plano (Action Scheduler o WP-Cron) y puede tardar», con «Procesar ahora» (`reset_queue`).
3. Para que empiece ya: en las semillas, encolar con retraso 0 (`start_seed` → `enqueue` con `$delay = 0`) en vez de los 300 s de debounce, que existen para las ediciones sueltas.
4. Decidir si el paso Negocio del asistente debe generar el resumen (punto 7).

**Riesgos.** Quitar el debounce en semillas grandes concentra llamadas de IA (lo mitigan el límite diario y el tamaño de lote). Procesar en síncrono dentro del asistente puede agotar el tiempo de PHP: por eso `reset_queue` va de 20 en 20.

---

## 10. Ajuste: borrar toda la BD y los documentos al eliminar el plugin

**Estado: vivo. Hoy la desinstalación borra muy poco y no ofrece opción.**

**Qué hace `uninstall.php` (líneas 1-16).** Elimina la tabla `wookb_documents`, las opciones `wookb_settings` y `wookb_daily_counter` y el transient `wookb_llms_txt`. Los `.md` se conservan a propósito («opt-in a borrado de .md no implementado en esta fase», líneas 8-9). Se ejecuta siempre, sin ajuste.

**Qué queda tras desinstalar (según `grep`)**
- Tabla `wp_wookb_crawler_log` (`class-crawler-log.php:22`).
- Opciones: `aikb_setup_assistant`, `wookb_db_version`, `wookb_seed_running`, `wookb_lang_answer_migrated`, `wookb_language_settings`, `wookb_lang_regen_pending`, `wookb_chatbot_prompt_answers`, `wookb_business_summary`, `wookb_chatbot_prompt_synced_hash`, `wookb_genix_hooks_dismissed_at`, y los transients `wookb_*`.
- Carpeta `wp-content/llm/` (documentos, FAQ, `info.md`, `chatbot-system-prompt.md`).
- `llms.txt` físico en la raíz; el bloque propio en `robots.txt` y `.htaccess`.
- Posts `sgkb-docs` creados para Genix (`includes/class-genix-bridge.php:57`).
- Valor `chatbot_custom_instructions` escrito en las opciones de Genix y los parches en sus archivos.
- Acciones pendientes de Action Scheduler (grupo `woo-kb`) y eventos de WP-Cron; la desactivación no los retira (`ai-knowledge.php:101-104`).

**Solución propuesta**
1. Check en Ajustes «Al eliminar el plugin, borrar todos sus datos y documentos», **desmarcado por defecto**, con texto que enumera lo que se borra y lo que no.
2. `uninstall.php` lee el ajuste antes de borrar nada. Marcado → borra: las dos tablas, una lista cerrada de opciones, los transients con prefijo `wookb_`, la carpeta `wp-content/llm/` (ruta fija) y las acciones pendientes de la cola. Sin marcar → se comporta como hoy.
3. Posts `sgkb-docs`: borrar solo los que tienen fila en el Registro (`doc_post_id`), nunca todos (los artículos exclusivos de Genix son del usuario).
4. Archivos fuera de `wp-content/llm/` (robots.txt, .htaccess, llms.txt físico, parches de Genix): no tocarlos a ciegas. Decisión del usuario: no borrarlos y avisar, o ofrecer un botón previo «Quitar reglas de AI Knowledge».

**Riesgos**
- Irreversible: por eso desmarcado, con confirmación clara y recomendación de copia.
- Borrar los `sgkb-docs` equivocados.
- Borrar `wp-content/llm/` rompe las URLs `.md` ya indexadas (esperable al desinstalar).
- Multisitio: no he revisado si el plugin lo soporta; `uninstall.php` actual no recorre sitios.
- Probar antes en staging.

---

## 11. Pantalla Visibilidad IA: «Bloquear/Permitir» → «Bloqueado/Permitido»

**Estado: vivo.** (Interpreto «Boldear y Permitir» como «Bloquear y Permitir».)

**Ocurrencias**
- Estado de cada bot, `<option>`: pestaña (`admin/views/tab-visibilidad-ia.php:338-339`) y asistente (A:426).
- Botones masivos: pestaña `:312-313` («Permitir/Bloquear todos (visibles)») y asistente A:425. Son acciones, no estados.
- Frases que dicen «marcados como Bloquear»: A:1374, `tab-visibilidad-ia.php:386` y `:398`.
- Los filtros de la pestaña ya dicen «Permitidos/Bloqueados» (`:307-308`).
- Documentación: `docs/tab-visibilidad-ia.md` (líneas 89, 139 y 150).
- Catálogos `languages/*.po` y `.pot` con los `msgid` actuales.

**Solución propuesta.** Cambiar los `<option>` a «Permitido»/«Bloqueado» en pestaña y asistente y las tres frases a «marcados como Bloqueado». Decisión menor: mantener los botones masivos como verbo («Permitir todos»), porque son acciones. Los valores internos `allow`/`block` no cambian (compatibilidad con `crawler_actions` ya guardado).

**Riesgos.** Mínimo: solo texto. Los `msgid` nuevos no tendrán traducción en `ca`, `de_DE`, `en_US`, `eu` y `fr_FR` hasta actualizar los `.po`; mientras tanto, se ven en español.

---

## Hallazgos adicionales

Salieron al verificar y no estaban en la lista.

| Hallazgo | Dónde | Relación |
|---|---|---|
| El asistente precalcula todas las pantallas en cada carga y el JS nunca usa ese contenido (`screenContent` solo aparece en A:924). Ese cálculo ejecuta la petición HTTP del robots e invalida la copia descargada (`clear_backup_confirmation()`, A:363-364) | A:914-925, A:349-365 | Puntos 3, 4 y 9. Quitar `screenContent` del `localize` es un arreglo de bajo riesgo |
| Nombre antiguo «WOO Knowledge Base Generator:» en 4 avisos | `class-chatbot-prompt-builder.php:585`, `class-genix-hooks-guard.php:78`, `:86`, `:183` | Puntos 7 y 8 |
| Silencio de 24 h del aviso de Genix inoperante (la opción nunca se escribe) | `class-genix-hooks-guard.php:162`, `:342` | Punto 8 |
| Lock de generación que no se lee | `class-queue.php:57-58` | Punto 6 |

---

## Orden sugerido (para decidir)

1. **Cola y estado** (6) y **textos y avisos** (7, 8 parcial, 11): bajo riesgo, se ven rápido.
2. **Conexión de IA y WooCommerce en el asistente** (1, 2), más quitar la dependencia de IA en la tienda.
3. **robots.txt** (3, 4): helper único sin HTTP y «Crear robots.txt». Requiere probar en staging.
4. **Asistente ↔ chatbot** (5) y **resumen final** (9), tras decidir qué genera cada paso.
5. **Genix** (8, opción A) y **desinstalación** (10): los de más riesgo, con decisión y copia previas.

## Decisiones del usuario (2026-09-25)

| Punto | Decisión |
|---|---|
| 1 | Comprobar conexión y avisar. Si es correcta, mostrar los modelos posibles para elegir, **tanto en el asistente como en la pestaña Ajustes**. Repetir la comprobación tras guardar. |
| 2 | Asistente y pestaña hacen lo mismo: muestran lo que hay en WooCommerce (nombre, moneda, país, dirección y todo lo posible), lo guardan como datos propios y la segunda vez muestran lo guardado. (Es el comportamiento «snapshot» de la pestaña actual.) |
| 3 | Sin robots.txt físico: ofrecer «Crear robots.txt», **pidiendo confirmación y explicando bien los riesgos**. |
| 4 | Usar la solución del punto 3 (mismo helper, mostrar el «Actual» también en el asistente). |
| 5 | Si ya existe prompt de chatbot, sincronizarlo. Si no existe, **crear uno predeterminado a partir de los campos rellenados**; el ajuste fino se hace después. |
| 6 | De acuerdo con todo. Además: valores por defecto con **bloques de generación más grandes y menos espacio entre ellos**, y **comprobar por qué tras el asistente todo queda «En cola» sin procesarse**. |
| 7 | Pendiente de explicarlo mejor (ver abajo). El nombre antiguo del aviso se corrige sí o sí. |
| 8 | Pendiente de decidir si estos avisos son necesarios (ver abajo). |
| 9 | Explicado en llano (ver abajo). |
| 10 | **Dos checks separados:** (a) borrar datos de la base de datos; (b) borrar los archivos generados: los `.md` y el `llms.txt`. `robots.txt` y `.htaccess` se quedan como están. |
| 11 | Los botones «Permitir todos» y «Bloquear todos» se quedan como verbo. Solo cambian los estados a «Permitido/Bloqueado». |

### Aclaraciones para el diseño

- **Punto 2.** Con «snapshot»: primera vez, valor de WooCommerce; al guardar, queda como dato propio; después, se muestra lo guardado. Es lo que ya hace la pestaña. La dirección de WooCommerce hoy no tiene campo propio en el plugin (solo existe «Dirección» en Negocio): falta decidir dónde se guarda.
- **Punto 5, en llano.** El plugin escribe el prompt del sitio en un ajuste de Support Genix llamado `chatbot_custom_instructions`. Genix Lite no lee ese ajuste (no trae ese bloque de fábrica; Pro sí). Por eso, sin el parche del punto 8, el prompt se guarda pero el chatbot nunca lo usa. Crear y sincronizar el prompt es correcto, pero en Lite solo tendrá efecto cuando el parche esté puesto.
- **Punto 10.** El check (b) borra el `llms.txt` físico de la raíz. Si el usuario tenía un `llms.txt` propio anterior al plugin, no debe borrarse: solo el que generó el plugin. Con `robots.txt` y `.htaccess` sin tocar, las reglas de bloqueo de AI Knowledge siguen activas tras desinstalar; el texto del check debe decirlo.

### Punto 7 y punto 8: para qué sirven los avisos

- **Aviso del resumen (7).** Es una sugerencia, no un error. `/llms.txt` abre con una cita corta (el «resumen público»). Si es de una frase, las IA reciben poca información de tu negocio. El aviso solo pide ampliarla. No rompe nada si se ignora.
- **Aviso de Genix (8).** Este sí avisa de algo que no funciona: el chatbot ha perdido las mejoras (relevancia, idiomas, límite, prompt del sitio). Es necesario mientras dependamos de parchear Genix, pero hoy sale en todo el escritorio y no se puede silenciar; debería salir solo en las pantallas del plugin y en Plugins, con descarte real.
- **«Versión vital».** No aparece en el código: el aviso termina en «Ir a la pestaña Negocio». Sale de tu nota original; sin más contexto lo trato como errata y no lo cambio.

### Punto 9 en llano

1. Al terminar el asistente, el plugin **no genera** los documentos de contenido: solo los apunta en una lista de espera. Cada uno se programa para dentro de 5 minutos, y en lotes de 20 con un minuto entre lotes.
2. Esa lista la ejecuta una tarea de fondo (Action Scheduler con WooCommerce; WP-Cron sin él). WP-Cron solo se dispara cuando alguien visita la web: en Local o staging sin visitas, no arranca. Por eso se ve «todo en cola y nada procesando».
3. La pantalla final cuenta mal: «pendientes» no suma los que están generándose ni los que dieron error, y no separa por estado.
4. Arreglo: que la primera tanda empiece de inmediato, lotes más grandes y más seguidos (respetando el límite diario), un aviso claro de que sigue en segundo plano, un botón «Procesar ahora» y el desglose por estado. Antes hay que comprobar en el sitio real por qué no procesa (¿WP-Cron desactivado?, ¿Action Scheduler con acciones pendientes?).

### Segunda ronda de decisiones (2026-09-25)

| Punto | Decisión |
|---|---|
| 1 | Con origen Genix: mostrar qué modelo usa (solo lectura) y un texto que indique que, para cambiarlo, se vaya a Genix. |
| 2 | La dirección de WooCommerce se guarda en el campo «Dirección» de Negocio, solo si está vacío. |
| 6 | Valores propuestos aceptados: **50 documentos por lote, 15 s entre lotes, espera inicial 0** al lanzar semillas. Desde el asistente, **«Sin límite diario» activado por defecto**; si no fuera viable, subir el límite diario a **222**. Riesgo: sin límite, el gasto de IA no tiene tope; el aviso del paso debe decirlo. |
| 7 | Avisar solo si el resumen público propio no existe (sin contar el fallback al «Enfoque»). |
| 10 | Borrar solo el `llms.txt` que creó el plugin (comprobado por su cabecera). Uno anterior del usuario se respeta. |

### Comprobación en `docthinks` (2026-09-25)

- `wp-config.php` **no** define `DISABLE_WP_CRON` ni `ALTERNATE_WP_CRON`.
- Tiene WooCommerce (por tanto Action Scheduler), WPML, WooCommerce Multilingual y Support Genix Lite.
- Copia del plugin instalada: versión `1.3.1.1` (dev), no la 1.3.2 de este repo.
- El trait de Genix Lite de `docthinks` **no contiene ninguno** de los tres parches (0 coincidencias). El aviso del punto 8 es real también allí.
- Causa de «todo en cola»: sigue sin poder confirmarse solo con archivos. Con lo ya sabido, lo esperable es la espera inicial de 300 s más que Action Scheduler se ejecuta desde WP-Cron, que depende de visitas. Falta comprobar en el admin de `docthinks` (Herramientas → Acciones programadas) si hay acciones `wookb_generate_document` pendientes y si su hora ya pasó.

## Preguntas abiertas

- Punto 8: confirmar la opción A (mantener el parche con aviso solo en pantallas del plugin y en Plugins, descarte real y texto que liste qué falta).
- Orden de trabajo: ¿empezar por el bloque de cola y textos?

## No verificado

- Causa del fallo de la petición HTTP a `robots.txt` en `docthinks.local` (punto 3).
- Estado del trait de Genix en `docthinks.local` (punto 8): solo he leído el de este workspace.
- Origen de «Registrados 8 · Pendientes 54» y del «EN: 1» (punto 9).
- Que el servidor sirva el `robots.txt` físico por encima del virtual (comportamiento habitual, no probado aquí).
- Si `GetOpenAIConfig()` de Genix devuelve la clave con `ai_proxy` (punto 1).
- Soporte multisitio del plugin (punto 10).

---

## Comprobación completa de los filtros de Genix (2026-09-26)

Genix Lite `1.4.54` en el workspace y en `docthinks`, con el mismo archivo (mismo hash). Pruebas hechas sobre **copias** en un directorio temporal, cargando las clases reales del plugin con stubs mínimos de WordPress. No se ha tocado ningún archivo de Genix ni ningún sitio.

| # | Comprobación | Resultado |
|---|---|---|
| 1 | Parches presentes en Genix Lite 1.4.54 | Ninguno de los tres, en ambos sitios (`docthinks`: 0 coincidencias) |
| 2 | Ganchos oficiales de Genix para resultados de búsqueda, lista de documentos o prompt | **No existen.** Oficiales del chatbot: `chatbot-history-*`, `carryover-*`, `strip-external-links`, `source`, `iframe-csp`, `ai-no-reasoning-*`, y `support_genix_current_language_key` (este sí lo usamos y existe) |
| 3 | ¿Lee Genix Lite `chatbot_custom_instructions`? | **No, en ningún punto** (solo Pro, según el docblock). Sin el parche, el prompt sincronizado no tiene efecto |
| 4 | Botón «Reinstalar filtros ahora» | **Reproducido el fallo** con el código real: sin `php` en el PATH devuelve error y deja una copia en `db-backup` sin escribir nada. Es idéntico a `docthinks` (3 copias del mismo tamaño) |
| 5 | Parche con `php` disponible | Se aplica limpio en los 3 puntos, el resultado pasa `php -l`, y una segunda ejecución da `already_present` |
| 6 | Lógica del filtro de relevancia (`Chatbot_Relevance_Guard`) | **Fallo de diseño:** vacía los documentos si las palabras de la pregunta no están en el **título**. «envíos a Canarias», «cuánto tarda el envío» y «política de devoluciones» vacían los documentos aunque el contenido tenga la respuesta |
| 7 | Guardado del prompt en Genix (`AddOption`) | Se persiste como opción normal (no es campo multiidioma) y sobrevive a los guardados de Genix. **Fallo:** `update_option` devuelve `false` si el valor no cambia y `Chatbot_Prompt::sync()` lo trata como error («Genix rechazó guardar la opción») |
| 8 | Conexión de IA con origen Genix | `AI_Client` solo lee la clave de **OpenAI** de Genix (`GetOpenAIConfig`). Si el chatbot usa Claude o `ai_proxy`, el plugin dice «sin conexión» |
| 9 | Puntos de integración | Existen en 1.4.54: filtro de idioma, `search_chatbot_docs` (reflexión), `ApbdWps_PostValue`, `Apbd_Wps_Parsedown`, CPT `sgkb-docs`, meta `only_for_chatbot`, tabla `apbd_wps_chatbot_history` |

**Consecuencia de 6.** En cuanto se reinstalen los parches, el filtro de relevancia haría que el chatbot responda «no tengo información» a preguntas cuya respuesta está en el contenido del documento. Genix ya exige que coincida al menos el 50 % de los términos, así que esa comprobación por título es redundante y dañina. En `docthinks` hoy los parches no están, así que la incidencia del roadmap allí **no** la explica este filtro: causa sin confirmar.

**Propuestas**
1. Comprobar la sintaxis dentro de PHP con `token_get_all(..., TOKEN_PARSE)` (probado en PHP 8.4) y hacer la copia solo tras pasar la comprobación.
2. Desactivar la pieza 1 (vaciar documentos por título) y conservar idioma, límite y contacto; dejar un interruptor para reactivarla.
3. `sync()`: tratar «sin cambios» como correcto, no como error.
4. Opcional: aceptar también la clave de Claude de Genix (`GetClaudeConfig` existe).
5. A medio plazo: pedir a Genix ganchos oficiales; los parches seguirán perdiéndose en cada actualización.
