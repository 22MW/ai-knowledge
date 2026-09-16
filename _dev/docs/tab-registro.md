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

- **Regenerar** ese documento en concreto.
- **Borrar** el documento (y su post asociado). Al borrarlo, su origen se
  añade automáticamente a "IDs a excluir" en
  [Contenido](tab-contenido.md), para que no se vuelva a generar solo —
  si quieres que vuelva a generarse, quítalo de esa lista de exclusiones.
- **Fijar el texto a mano** (modo manual): si editas el contenido de un
  documento y no quieres que la próxima regeneración automática lo
  pise, puedes fijarlo. El plugin te avisará si el contenido original
  cambia después de fijarlo, para que decidas si actualizarlo.

También puedes seleccionar varios documentos a la vez y borrarlos en
bloque, con el mismo aviso sobre las exclusiones.

> Borrar documentos aquí no borra tus productos ni páginas reales — solo
> el documento de conocimiento generado a partir de ellos.

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

---
[Ver también: [Contenido](tab-contenido.md) · [Generación masiva](tab-generacion-masiva.md)]
