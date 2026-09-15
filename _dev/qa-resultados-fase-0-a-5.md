# Resultados QA — Fases 0 a 5 (primera ronda real)

Fuente: `_dev/tests-qa-fase-0-a-5.md` anotado por el usuario. Aquí se
organiza en bugs confirmados, UX pendiente de rediseño, preguntas
respondidas y decisiones abiertas.

## Bugs confirmados (evidencia en código, listos para arreglar)

**B1 — "Generar" (fila) ignora el `char_limit` guardado.** Confirmado en
`admin/class-registry-table.php:296`: el botón "Generar" de cada fila
(acción de un solo uso, `wookb_regenerate_single`) siempre muestra
`Generator::BODY_CHAR_LIMIT` como valor por defecto, nunca `item->char_limit`
— por eso al guardar un límite propio (paso 9) no se refleja ahí. Es la
pieza vieja de antes de la Fase 1, no se actualizó. Severidad: media (el
límite persistido SÍ se aplica en las regeneraciones automáticas, solo la
UI de esta acción concreta no lo muestra).

**B2 — Borde/zona negra tras el fix del tema.** Confirmado como regresión
visual del arreglo de la Fase 1 (paso 13): el script inline que fija
`data-bs-theme` antes de pintar deja un borde/zona oscura visible. Severidad
media (visual, no funcional). Pendiente de revisar el CSS que depende de ese
atributo antes de que cargue el resto del layout.

**B3 — Botón del editor sin feedback claro y sin confirmar si persiste.**
Paso 11: el usuario pulsó el botón y "no se ve nada" — no hay aviso de
éxito, y no confirmó si el ID apareció en Alcance tras el clic. El código
(`Editor_Metabox::handle_add()`) sí añade a `Scope::update_settings()` y
redirige a la misma pantalla de edición — si funcionó, el meta box debería
cambiar de "Añadir..." a "Estado: ...". **Necesito repetición con más
detalle antes de tocar código**: ¿la página se recarga al pulsar? ¿el meta
box pasa a mostrar "Estado: ..." tras la recarga? ¿aparece el ID en Alcance
si refrescas esa pestaña? Sin eso no se puede diagnosticar si es un bug real
o una UI sin feedback visible pero funcional.

**B4 — El `.md` se descarga en vez de abrirse (paso 26).** Los `.md` son
archivos físicos servidos directamente por el servidor web (Apache/Nginx),
no por WordPress — su `Content-Type` depende de la configuración MIME del
servidor, no del plugin. Si Apache no tiene `.md` mapeado a un tipo de texto,
el navegador lo trata como descarga genérica. Arreglo: añadir una regla al
`.htaccess` de `wp-content/llm/` (o a la config del servidor) que sirva
`.md` como `text/plain; charset=utf-8` — esto es configuración de servidor,
no requiere tocar PHP del plugin, pero sí decidir si se automatiza (crear el
`.htaccess` al activar el plugin, como ya se hace con `index.php` en esa
carpeta) o se documenta como paso manual.

## Preguntas respondidas

**P1 (paso 2) — "¿Qué textos son los traducidos?"** Ninguno todavía: no
existe carpeta `languages/` en el plugin (verificado, no hay ni un solo
`.po`/`.mo`). Todo el texto que ves es el string literal en español escrito
en el propio código (`__('Guardar límite', 'ai-knowledge')`) — se muestra
tal cual porque WordPress no encuentra traducción y usa el texto original.
El cambio de Text Domain no rompió nada porque nunca hubo un archivo de
traducción que dependiera del dominio viejo.

**P2 (paso 3) — "¿No se puede cambiar el slug del menú?"** Sí se puede
cambiar, se decidió no tocarlo en la Fase 0 para no romper enlaces/accesos
directos existentes sin necesidad. Si prefieres que también pase a
`ai-knowledge` para que la URL sea consistente con el nombre nuevo, es un
cambio pequeño y aparte — dime y lo hago.

**P3 (paso 28) — "¿Por qué el `<link>` de Markdown no sale en portada/
archivo/categoría? ¿Debería?"** Decisión original: esas páginas no
representan "un contenido" concreto (son listados), así que no hay un `.md`
individual al que apuntar. Si quieres que la página de tienda o los
archivos de categoría enlacen al catálogo (`shop-catalog`, que ya genera
`Store_Info_Doc`), es una ampliación pequeña y razonable — dime si la
quieres y la añado.

**P4 (paso 34) — "No entiendo la prueba de seguridad de `custom_fields`."**
Significa: si en Alcance solo marcaste el campo `precio_especial` como
custom field visible para el CPT `producto`, el endpoint REST
(`/wp-json/ai-knowledge/v1/content/{id}`) no debería devolver ningún OTRO
meta dato del post (ej. `_edit_lock` o cualquier campo interno) — solo el
que marcaste explícitamente. Para probarlo: compara el JSON de la Fase 3
contra los campos que tienes marcados en Alcance.

## Sobre la Fase 5 (JSON-LD) — necesito aclarar antes de dar por bueno o por bug

Pegaste UN bloque `<script type="application/ld+json">` y lo marcaste como
"duplicado" (pasos 29 y 31). El código confirma que nuestro plugin comprueba
`RankMath\Helper::is_module_active('schema')` y si está activo NO imprime
nada — así que ese único bloque que pegaste debería ser el de RankMath, no
el nuestro. **Pregunta directa: en el código fuente de esa página, contaste
más de un `<script type="application/ld+json">`, o solo ese uno?** Si es
solo uno, no hay bug (es RankMath, nosotros correctamente no imprimimos
nada) — si son dos, sí hay un bug real y hace falta ver el segundo bloque.

## UX pendiente de rediseño (no son bugs puntuales, son cambios de diseño)

Estas peticiones son válidas pero grandes — antes de tocar código conviene
acordar el diseño exacto (no "a ver si queda mejor"):

**UX1 — Registro: el bloque "Ver/editar Markdown" es incómodo.** Pedido:
que se abra a todo el ancho debajo de la fila (no dentro de la celda
estrecha), textarea de ~2/3 del ancho de la pantalla y mínimo 15 líneas de
alto, botones más grandes.

**UX2 — "Carga inicial" confunde una vez que ya hay contenido sincronizado
parcialmente. — HECHO (ver `_dev/roadmap.md`, sección "UX pendiente de
rediseño").** Botón único sustituido por "Generar pendientes" +
"Reiniciar todo", y ajustes de cola movidos a esta pestaña.

**UX3 — Pestaña WooCommerce: los datos detectados deberían tratarse como
los posts** (selección tipo checkbox de qué incluir en el prompt/`.md`),
en fila uno al lado del otro en vez de en columna, con la misma maquetación
que ya usa el listado de posts/Alcance. Además: TODOS los campos (no solo
tienda) deberían poder llevar un texto/prompt propio para usar luego, mismo
patrón que el modo manual de la Fase 1 pero generalizado a cualquier campo,
no solo al documento completo.

**UX4 — Confusión sobre dónde generar los documentos de tienda tras
moverlos a la pestaña WooCommerce (paso 19).** Revisar si el botón quedó
poco visible o mal etiquetado en la nueva ubicación.

Estas 4 son candidatas a pasar por `rol-analista`/`evaluar-cambio` como
tarea propia (no arreglos sueltos), porque tocan diseño de interacción, no
solo un bug.

## Siguiente paso propuesto

1. Arreglar ya B1 y B2 (pequeños, diagnosticados, bajo riesgo).
2. Repetir el paso 11 (botón del editor) con el detalle pedido para
   confirmar B3 antes de tocar nada.
3. Confirmar B4 (¿automatizar `.htaccess` o dejarlo manual?).
4. Responder P2 y P3 (¿quieres esos dos cambios sí o no?).
5. Aclarar la duda de Fase 5 (¿uno o dos `<script>` JSON-LD?).
6. Las 4 piezas de UX (registro, carga inicial, WooCommerce con selección
   tipo post, prompts por campo) se agendan como tarea de diseño aparte
   cuando quieras abordarlas — no se improvisan ahora.
