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

- **llms.txt** — el archivo físico que gestiona AI Knowledge en la raíz de
  tu web. Ayuda a los asistentes de IA a entender qué contiene tu sitio y
  dónde encontrar la información más importante. Es una convención
  emergente (todavía no un estándar oficial), pero cada vez más usada. El
  plugin lo crea y lo regenera cuando cambian los documentos o los datos que
  lo alimentan. Su encabezado incluye la fecha y hora de la última generación
  en formato ISO 8601, para que los lectores puedan comprobar si la
  información está actualizada. Dentro del archivo, los enlaces aparecen
  agrupados por secciones (por ejemplo "Páginas" para tus páginas y
  productos, o "Documentación" para los artículos exclusivos de Genix que
  hayas hecho públicos desde la pestaña [Genix](tab-chatbot.md)), para que
  sea más fácil de entender qué tipo de contenido es cada enlace.
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

La tabla incluye crawlers de IA, herramientas SEO y scraping, buscadores
tradicionales, servicios de archivado y scanners automatizados. Puedes filtrar
la lista por tipo y por estado sin recargar la página, y combinar ambos filtros.

Un catálogo de crawlers de IA conocidos, agrupados por su propósito, con
una acción por cada uno: **permitir** o **bloquear**. Esta configuración
es la que alimenta tanto el bloqueo por `robots.txt` como el bloqueo real
por `.htaccess` de más abajo.

### Bloqueo por robots.txt

`robots.txt` es una petición educada: la mayoría de bots serios la
respetan, pero técnicamente un bot puede ignorarla. El plugin muestra el
archivo actual y una propuesta completa antes de guardar. Antes de aplicar
el cambio, **te obliga a descargar una copia de seguridad**.

El plugin conserva las reglas externas. Si una regla original contradice la
configuración elegida, la comenta sin borrarla y añade sus reglas activas en
un bloque propio. En cada actualización sustituye entero solo ese bloque.

### Bloqueo real por .htaccess

Esto sí es un bloqueo de verdad a nivel de servidor: si un bot marcado
como "Bloquear" en la tabla intenta acceder, el servidor rechaza la
petición directamente, la respete o no.

El interruptor **«Incluir llms.txt para los modelos desactivados»** decide
qué pasa con esos bots bloqueados:

- **Activado:** no pueden entrar en tu web, pero sí leen `/llms.txt`, el
  resumen que has preparado para ellos. Cualquier otra página les responde
  `404`.
- **Desactivado:** no pueden acceder a nada, ni siquiera a `/llms.txt`. Es un
  bloqueo total del sitio.

Este ajuste solo cambia las reglas propuestas para `robots.txt` y
`.htaccess`; nada se modifica en tu servidor hasta que tú lo apliques. Está
al principio de la tabla de crawlers y se guarda con **«Guardar configuración
de crawlers»** (la página se recarga y las propuestas se actualizan). También
lo encuentras en el paso de Visibilidad IA del asistente.

Las reglas de `.htaccess` se agrupan por comportamiento para evitar bloques
repetidos y se colocan antes de las reglas de WordPress, de modo que el bloqueo
se evalúe antes de enviar la petición a `index.php`.

El plugin muestra el `.htaccess` actual y el archivo completo propuesto. El
archivo real no se sobrescribe automáticamente: puedes copiar el código o
descargar el archivo preparado y sustituirlo manualmente.

> `robots.txt` se guarda con confirmación y copia previa. `.htaccess` se
> prepara para copiar o descargar, pero no se sobrescribe automáticamente.

### Registro de accesos de crawlers de IA

Un histórico de qué bots de IA han accedido a tu web y cuándo, para que
veas de un vistazo quién te está leyendo de verdad.

## Preguntas frecuentes

**¿Qué pasa si todavía no existe un llms.txt físico?**
La pestaña te lo indica y ofrece crearlo. Después AI Knowledge lo regenera
cuando cambian los documentos o los datos que alimentan el archivo.

**¿Bloquear un bot en robots.txt es suficiente para que no me lea?**
No siempre — algunos bots lo ignoran. Si quieres una garantía real, usa
también el bloqueo por `.htaccess`, que actúa a nivel de servidor.

**¿Por qué me pide descargar una copia antes de cambiar robots.txt?**
Porque es un archivo real del sitio. La copia te permite restaurar la
versión anterior si algo sale mal. Para `.htaccess`, además, el plugin te
entrega el archivo completo para que lo revises y lo sustituyas manualmente.

**¿Este bloqueo afecta también a Google?**
No de forma especial: solo afecta a los bots que tú marques como
"Bloquear" en el catálogo. Los buscadores clásicos que quieras seguir
permitiendo, simplemente no los marques.

---
[Ver también: [Contenido](tab-contenido.md) · [Registro](tab-registro.md)]
