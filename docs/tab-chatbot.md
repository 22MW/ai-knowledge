# Pestaña Genix

[← Volver al índice](index.md)

> Esta pestaña solo aparece si tienes **Support Genix activo**. Sin él, no
> hay ningún chatbot al que enviar este prompt, así que la pestaña
> permanece oculta.

> Esta pestaña se llamaba antes "Chatbot". Se ha renombrado a "Genix"
> porque ahora también incluye la sección de artículos exclusivos de Genix
> (más abajo), no solo el comportamiento del chatbot.

Aquí defines **cómo debe comportarse** el chatbot de tu web: su tono, qué
debe hacer cuando no sabe algo, y qué no debe hacer nunca. Los datos de tu
negocio en sí (dirección, contacto, horario...) no se rellenan aquí, sino
en [Negocio](tab-negocio.md) — este cuestionario los combina
automáticamente al generar el borrador.

No hace falta acertar a la primera: puedes responder lo que sepas, generar
un primer borrador, editarlo a mano, pulirlo con IA, o añadir más
respuestas al cuestionario y volver a generar para mejorarlo.

> El prompt del chatbot es **privado**: se guarda en la carpeta `privado/` del
> propio plugin (nunca en una carpeta accesible por URL) y, como copia de
> seguridad, en la base de datos, porque WordPress borra la carpeta del plugin
> al actualizarlo. Si encuentra uno antiguo en `wp-content/ai-knowledge/` (o
> `llm/`), lo importa y borra ese archivo público.

## 1. Ajustes del chat

Un único ajuste por ahora: cuántos "Documentos relacionados" muestra el
chatbot como máximo bajo cada respuesta (0 = sin límite).

Además, la casilla **«Filtro de relevancia por título»** (desactivada por
defecto) oculta los documentos cuando ninguna palabra de la pregunta coincide
con un título. Puede ocultar documentos cuyo contenido responde a la pregunta
pero cuyo título no coincide, así que solo conviene activarla si ves resultados
poco relacionados; Genix ya filtra por su cuenta.

## 2. Cuestionario

Responde a un pequeño cuestionario sobre cómo quieres que hable tu
chatbot. Opcionalmente puedes:

- **Añadir páginas de referencia** (hasta 3, por ID o URL): el plugin lee
  el contenido real de esas páginas para dar más contexto a la IA — útil
  si tienes una página "Sobre nosotros" con un tono muy concreto que
  quieres que el chatbot imite.
- **Añadir información extra**: cualquier instrucción o dato adicional
  que quieras pegar para esa generación en concreto.

[SCREENSHOT: cuestionario del chatbot con el campo de páginas de referencia]

Pulsa **"Generar borrador con IA"** para obtener un primer texto.

> En el **asistente de configuración**, el paso Chatbot hace algo parecido al
> guardar: si ya existe un prompt lo sincroniza tal cual con Genix; si no, crea
> uno predeterminado con tus respuestas (con IA si hay conexión; si no, con una
> plantilla de hasta 2000 caracteres) y lo sincroniza. Después puedes afinarlo
> aquí. Recuerda que en Genix Lite el prompt solo se aplica con el parche de
> filtros instalado (ver el aviso de Genix más abajo).

## 3. Borrador editable

El resultado aparece en un cuadro de texto totalmente editable. Cuando
termines de ajustarlo puedes:

- **Pulir la redacción con IA** (mejora la forma, sin tocar el fondo de lo
  que ya escribiste).
- **Guardar y sincronizar con Genix**, para que el chatbot empiece a
  usarlo de inmediato.

## 4. Artículos exclusivos de Genix

Si usas Support Genix, es normal que además de los productos y páginas de
tu web tengas otros artículos de ayuda escritos **directamente dentro de
Genix** (por ejemplo, guías de uso o respuestas largas que solo querías
tener a mano para el chatbot, sin crear una página nueva en tu web). Esta
sección te deja decidir cuáles de esos artículos también se hacen
**públicos** en `/llms.txt`, para que los buscadores y asistentes de IA
externos puedan leerlos también, no solo tu propio chatbot.

Verás una tabla con esos artículos y, para cada uno:

- **Estado**: si ya está "Publicado" (visible en `/llms.txt`) o "No
  publicado" (solo lo usa tu chatbot, hacia fuera no existe).
- **Generar contenido** (o **Actualizar contenido** si ya está publicado):
  copia el texto del artículo tal cual está en Genix en ese momento y lo
  publica. No usa IA ni resume nada: es una copia fiel. Si editas el
  artículo en Genix más adelante, vuelve a pulsar este botón para
  refrescar la copia publicada.
- **Quitar**: deja de publicarlo. El artículo sigue existiendo en Genix
  igual que antes (esto no lo borra ni lo toca), simplemente deja de
  aparecer en `/llms.txt`.

Dos artículos que **no aparecen nunca** en esta lista, a propósito:

- Los que en Genix están marcados como **"solo para uso del chatbot"**:
  si ni siquiera son visibles dentro de Genix para un visitante normal, no
  tiene sentido hacerlos públicos hacia fuera desde aquí.
- Los que **ya tienen su propia ficha generada por este plugin** (porque
  son la copia automática que se crea para un producto o página normal de
  tu web): esos ya se gestionan desde el [Registro](tab-registro.md), no
  hace falta duplicarlos aquí.

[SCREENSHOT: tabla de artículos exclusivos de Genix con los botones Generar/Actualizar y Quitar]

## Aviso «Support Genix ha perdido los filtros»

Algunas mejoras del chatbot (relevancia de las búsquedas, idiomas no
soportados, límite de documentos relacionados y el prompt personalizado del
sitio) dependen de unos pequeños parches que el plugin inserta en un archivo de
Support Genix. Una actualización de Genix puede borrarlos. Cuando faltan, el
aviso lista **qué falta en cada archivo** (Genix Lite y/o Pro) y ofrece
**«Reinstalar filtros ahora»**. Solo aparece en las pantallas del plugin y en
Plugins, y puedes silenciarlo 24 horas. Si el servidor no permite comprobar la
sintaxis PHP, el plugin **no escribe nada** en Genix y te lo dice: en ese caso,
aplica el parche a mano. Sin el parche, en Genix Lite el prompt se guarda pero
el chatbot no lo usa.

## Preguntas frecuentes

**¿Esto es lo mismo que las FAQs?**
No. Las [FAQs](tab-faqs.md) son contenido público, pensado para que lo
lea cualquiera. El prompt del chatbot son instrucciones internas de
comportamiento — nunca se muestran tal cual a tus visitantes.

**¿Qué pasa si no relleno el cuestionario y genero directamente?**
La IA generará un borrador más genérico, con menos detalle sobre tu
negocio concreto. Cuantas más respuestas le des (aquí y en Negocio),
mejor será el resultado.

**¿Puedo escribir el prompt yo mismo, sin pasar por el cuestionario?**
Sí, el cuadro del paso 3 es libre: puedes escribir o pegar directamente
tu propio texto y guardarlo, sin generar nada con IA primero.

**¿Cada vez que genero se pierde lo que ya tenía guardado?**
No, "Generar borrador con IA" reemplaza el borrador en pantalla, pero
hasta que no pulses "Guardar y sincronizar con Genix" no se sobrescribe
lo que el chatbot está usando en producción.

**No veo un artículo de Genix en la lista de "Artículos exclusivos de
Genix", ¿por qué?**
Puede ser por dos motivos: está marcado en Genix como "solo para uso del
chatbot" (no puede hacerse público desde aquí), o ya tiene su propia
ficha en el [Registro](tab-registro.md) porque corresponde a un producto
o página real de tu web.

**Si "Quito" un artículo publicado, ¿se borra en Genix?**
No. "Quitar" solo deja de publicarlo hacia fuera (deja de aparecer en
`/llms.txt`). El artículo original sigue existiendo en Genix igual que
antes.

---
[Ver también: [Negocio](tab-negocio.md) · [FAQs](tab-faqs.md)]
