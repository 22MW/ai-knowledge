# Pestaña Visibilidad IA

[← Volver al índice](index.md)

Todo lo que generas con este plugin no sirve de nada si los buscadores de
IA no pueden acceder a ello. Esta pantalla te lo comprueba en tiempo real,
y te deja decidir exactamente qué crawlers de IA entran en tu web y
cuáles no.

> Las comprobaciones de esta pantalla son de solo lectura — no cambian
> nada por sí solas. Solo las acciones sobre `robots.txt` y `.htaccess`
> modifican archivos reales, y siempre piden confirmación explícita antes
> de tocar nada.

## Estado de exposición

Un resumen de las distintas formas en que se publica tu contenido:

- **llms.txt** — el archivo que leen los buscadores de IA para tener un
  índice de tu contenido más importante. Es una convención emergente
  (todavía no un estándar oficial), pero cada vez más usada. El plugin lo
  sirve automáticamente sin necesidad de crear ningún archivo físico. Si
  detecta que ya existe un `llms.txt` físico en la raíz de tu web (creado
  por ti o por otro plugin), te avisa: ese archivo físico tiene prioridad
  y tapa al que genera el plugin. Puedes borrarlo desde aquí mismo si fue
  sin querer.
- **Markdown público** — el mismo contenido que usa tu chatbot, servido
  como archivo de texto plano, fácil de leer para cualquier IA sin tener
  que procesar HTML.
- **JSON estructurado** — los datos originales de WordPress/WooCommerce,
  sin pasar por IA, disponibles vía la API propia del plugin.
- **JSON-LD (Schema.org)** — datos estructurados estándar (tipo Producto,
  Artículo...) incrustados en el código de cada página, para que los
  buscadores identifiquen mejor de qué trata cada contenido.
- **Contenido pendiente de sincronizar** — cuánto de tu alcance
  configurado en [Contenido](tab-contenido.md) todavía no tiene documento
  generado.

[SCREENSHOT: sección "Estado de exposición" con los 4 formatos y sus enlaces de ejemplo]

## Comprobar accesibilidad

Elige cualquier contenido ya sincronizado y el plugin comprueba en vivo:

- Si `robots.txt` permite rastrearlo.
- Si lleva una señal `noindex` (por metaetiqueta o cabecera).

Si ambas señales se contradicen (por ejemplo, `robots.txt` lo permite
pero `noindex` lo bloquea), te avisa del conflicto para que lo revises.

## Gestión de crawlers de IA

Un catálogo de crawlers de IA conocidos, agrupados por su propósito, con
una acción por cada uno: **permitir** o **bloquear**. Esta configuración
es la que alimenta tanto el bloqueo por `robots.txt` como el bloqueo real
por `.htaccess` de más abajo.

### Bloqueo por robots.txt

`robots.txt` es una petición educada: la mayoría de bots serios la
respetan, pero técnicamente un bot puede ignorarla. Antes de aplicar el
bloqueo, el plugin **te obliga a descargar una copia de seguridad** del
`robots.txt` actual — es una protección doble (también a nivel de
servidor, no solo deshabilitando el botón en pantalla).

### Bloqueo real por .htaccess

Esto sí es un bloqueo de verdad a nivel de servidor: si un bot marcado
como "Bloquear" en la tabla intenta acceder, el servidor rechaza la
petición directamente, la respete o no. Modifica un archivo fuera del
propio plugin que puede afectar a todo tu sitio si algo sale mal, así que
también exige descargar una copia de seguridad antes de aplicar cambios.

> Nunca se escribe `robots.txt` ni `.htaccess` automáticamente. Ambas
> acciones piden tu confirmación explícita cada vez.

### Registro de accesos de crawlers de IA

Un histórico de qué bots de IA han accedido a tu web y cuándo, para que
veas de un vistazo quién te está leyendo de verdad.

## Preguntas frecuentes

**¿Qué pasa si borro mi llms.txt físico por error?**
El plugin sigue sirviendo su propia versión generada automáticamente en
la misma ruta, así que tu web no se queda sin `llms.txt` en ningún
momento.

**¿Bloquear un bot en robots.txt es suficiente para que no me lea?**
No siempre — algunos bots lo ignoran. Si quieres una garantía real, usa
también el bloqueo por `.htaccess`, que actúa a nivel de servidor.

**¿Por qué me pide descargar una copia antes de aplicar el bloqueo?**
Porque tanto `robots.txt` como `.htaccess` son archivos delicados: si algo
sale mal, quieres poder restaurar la versión anterior sin depender de
nadie más.

**¿Este bloqueo afecta también a Google?**
No de forma especial: solo afecta a los bots que tú marques como
"Bloquear" en el catálogo. Los buscadores clásicos que quieras seguir
permitiendo, simplemente no los marques.

---
[Ver también: [Contenido](tab-contenido.md) · [Registro](tab-registro.md)]
