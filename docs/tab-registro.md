# Pestaña Registro

[← Volver al índice](index.md)

Es la pantalla en la que vas a pasar más tiempo: aquí ves **todos los
documentos generados**, su estado, cuándo se actualizaron, y puedes
regenerar, editar a mano o borrar cualquiera de ellos.

## Qué encuentras aquí

Una tabla con todos tus documentos, cada uno con su estado:

- **En cola** — esperando su turno para generarse.
- **Generando** — la IA está redactándolo ahora mismo.
- **Sincronizado** — ya está generado y publicado.
- **Error** — algo falló al generarlo (verás el motivo).
- **Huérfano** — el contenido original ya no existe, pero el documento
  sigue ahí.

Puedes filtrar por estado, por idioma (si tu web es multiidioma) y buscar
por título, además de elegir cuántas filas ver por página.

[SCREENSHOT: tabla de Registro con varios documentos y el filtro de estado abierto]

## Generar por ID o URL

¿Un producto o página no tiene fila aquí todavía? Escribe su ID o pega su
URL en el campo de arriba de todo y pulsa **"Generar ahora"**: se genera
al instante, en todos los idiomas activos, sin esperar a la próxima
ejecución automática.

## Acciones sobre cada documento

Desde cada fila puedes:

- **Generar** (o regenerar) ese documento en concreto. Se hace al instante,
  sin recargar la página: verás el estado, la fecha y el contenido
  actualizarse solos en unos segundos. Si por lo que sea tu navegador tiene
  JavaScript desactivado, el botón sigue funcionando igual, solo que
  recargando la pantalla.
- **Borrar** el documento (y su post asociado). Al borrarlo, su origen se
  añade automáticamente a "IDs a excluir" en
  [Contenido](tab-contenido.md), para que no se vuelva a generar solo —
  si quieres que vuelva a generarse, quítalo de esa lista de exclusiones.

Cada acción se confirma con un aviso flotante que aparece por la derecha
(verde si ha ido bien, rojo si ha fallado), estés donde estés en la fila.

También puedes seleccionar varios documentos a la vez y borrarlos en
bloque, con el mismo aviso sobre las exclusiones.

> Borrar documentos aquí no borra tus productos ni páginas reales — solo
> el documento de conocimiento generado a partir de ellos.

> Si pasas un producto o página a "Borrador" desde su editor (sin
> borrarlo del todo), su documento desaparece de esta tabla y deja de
> estar disponible en `/llms.txt` automáticamente, sin que tengas que
> hacer nada más aquí. En cuanto lo vuelvas a publicar, se genera de
> nuevo solo.

## Manual o Auto

La columna **Control manual** te dice cómo se gestiona cada documento:

- **Auto** — el plugin lo regenera solo cuando cambia el contenido original.
- **Manual** — tú has fijado el texto y no se regenera.
- **Origen actualizado** — aparece si el contenido original cambió después de
  fijar el texto a mano. El botón **Marcar revisado** quita el aviso sin tocar
  tu texto.

## Ajustes avanzados de cada documento

Al desplegar "Ajustes avanzados" de cualquier fila, encuentras dos
pestañas:

### Pestaña "Contenido"

- El texto completo del documento, editable a mano.
- **Fijar el texto a mano** (modo manual, botón "Guardar cambios"): si
  editas el contenido de un documento y no quieres que la próxima
  regeneración automática lo pise, puedes fijarlo aquí. El plugin te
  avisará si el contenido original cambia después de fijarlo, para que
  decidas si actualizarlo.
- **Límite de caracteres** propio para este documento (si lo dejas en
  blanco, se usa el límite general de [Ajustes](tab-ajustes.md)).

El botón "Guardar cambios" empieza desactivado y solo se activa (en verde)
cuando has editado el texto. Tanto "Guardar límite" como "Guardar cambios" se
guardan al instante, sin recargar la página (con el mismo fallback si no
tienes JavaScript).

Si un documento está en **Manual** y quieres que vuelva a generarse con IA,
pulsa **"Volver a Auto"** (está en las pestañas Contenido y Prompt de esa
fila). Vuelve a modo automático y se regenera, ya usando el prompt propio si
lo tiene.

### Pestaña "Prompt"

Puedes escribir **instrucciones propias solo para este documento**: por
ejemplo, "escribe en un tono más cercano" o "destaca especialmente el
plazo de entrega". Si lo dejas vacío, se usa el prompt genérico de
siempre, sin ningún cambio.

Importante: estas instrucciones solo pueden pedir estilo o enfoque —
nunca pueden hacer que la IA invente datos ni sustituya la información
real de tu producto o página. Los datos reales siempre mandan.

[SCREENSHOT: "Ajustes avanzados" de una fila con las pestañas Contenido y Prompt]

## Reiniciar cola

Si la generación se quedó a medias (por ejemplo, tras un corte del
servidor), el botón **"Reiniciar cola"** retoma el proceso donde se
quedó, respetando siempre tu límite diario.

## Preguntas frecuentes

**¿Por qué un documento aparece como "Error"?**
Normalmente por un fallo temporal de conexión con la IA, o porque el
origen de IA configurado no está disponible en ese momento. Puedes
regenerarlo manualmente cuando quieras.

**¿Qué significa "Huérfano"?**
Que el producto o página que generó ese documento ya no existe (se borró
en WordPress), pero el documento sigue publicado. Puedes borrarlo desde
aquí si ya no lo necesitas.

**Si edito un documento a mano, ¿se pierde cuando vuelva a generar todo?**
No, si activas el modo manual para ese documento. El plugin respeta tu
texto fijado y no lo regenera solo — te avisa si el contenido original
cambió, pero la decisión de actualizarlo es tuya.

**¿Puedo buscar por el nombre de un producto?**
Sí, el buscador de la parte superior busca por título.

**Mi documento está en Manual y el prompt no hace nada, ¿por qué?**
Porque un texto fijado a mano nunca se regenera con IA, así que el prompt no
tiene efecto mientras esté en Manual. Pulsa "Volver a Auto" si quieres que
vuelva a generarse y use ese prompt.

**¿Para qué sirve el prompt propio de un documento?**
Para casos puntuales en los que quieres que un documento concreto suene
distinto al resto (más formal, más breve, con más énfasis en algo
concreto) sin cambiar el prompt general de todo el plugin. No sustituye
los datos reales del producto o página: solo influye en cómo se redactan.

**Si dejo el prompt de un documento en blanco, ¿pasa algo malo?**
No, es lo normal: se usa el prompt genérico de siempre, exactamente como
si esa pestaña no existiera.

---
[Ver también: [Contenido](tab-contenido.md) · [Generación masiva](tab-generacion-masiva.md)]
