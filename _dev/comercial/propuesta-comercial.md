<img src="../logo/ai%20knowldge%20bw.svg" alt="AI Knowledge & Visibility" width="90" align="right">

# AI Knowledge & Visibility

**Que las inteligencias artificiales entiendan tu negocio tan bien como tú.**

Documento comercial · Versión del plugin: 1.3.1 · Septiembre 2026

> Este documento tiene dos partes: una para **dueños de tienda o web** (A) y
> otra para **agencias y profesionales** (B). Las secciones comunes (C en
> adelante) sirven para los dos.

---

## La idea

**Las IA no leen tu web como una persona. Así que la propia IA la escribe en su idioma.**

Tu web está hecha para personas: menús, banners, textos que buscan convencer.
Una IA necesita otra cosa: información limpia, ordenada y sin ruido.

AI Knowledge & Visibility usa IA para **crear contenido para IA**. Toma lo que
ya tienes, escrito para personas, y lo convierte al lenguaje que entienden las
inteligencias artificiales: documentos claros y estructurados, `llms.txt`,
Markdown y datos estructurados. Es la IA escribiendo en su propio idioma, y por
eso te entiende.

Tu web sigue igual para tus visitantes; lo nuevo va en una capa aparte pensada
para las IA.

---

## En una frase

Un plugin de WordPress que convierte tu catálogo, tus páginas y los datos de tu
negocio en documentos claros que las IA pueden leer, los publica donde las IA
los buscan y, si usas el chatbot Support Genix, se los da también a él.

---

> **Pruébalo gratis:** puedes comprobar ahora mismo cómo ve una IA tu web, sin
> instalar nada. Está explicado al final, en el anexo.

## El problema

Cada vez más gente le pregunta a ChatGPT, Claude, Gemini o Perplexity antes de
comprar. Si esas IA no encuentran tu información de forma clara, hablan de otros
o se inventan la respuesta.

Si usas el chatbot Support Genix, tiene el mismo problema: si nadie le ha
escrito tus envíos, tus devoluciones y tus precios (y los mantiene al día),
responde con generalidades.

Este plugin resuelve las dos cosas con una sola fuente de información: la que
ya tienes en WordPress.

---

# A. Para dueños de tienda o web

## Lo que consigues

- **Te ven las IA.** Publica tu información en `/llms.txt` (la ficha resumen
  que empiezan a consultar los buscadores de IA), en documentos Markdown
  públicos y como datos estructurados dentro de tus páginas.
- **Tus productos, con lo que importa.** Cada producto lleva su descripción y
  un bloque de «Datos de compra» sacado directamente de WooCommerce: precio,
  precio anterior, descuento, envío, impuestos y todas las variaciones. Esos
  datos no los redacta la IA, así que no se los puede inventar.
- **Un chatbot que responde con datos reales** (si usas Support Genix): consulta
  los mismos documentos, no un texto que alguien tenga que mantener a mano.
- **Sin mantenimiento.** Si publicas o cambias un producto, el plugin lo
  detecta y actualiza su documento. Si pasas algo a borrador, lo retira.
- **Vale para webs en varios idiomas.** Con WPML, la IA recibe un solo documento
  por página, en tu idioma principal, que le dice en qué otros idiomas está y
  con qué enlace.
- **Tú mandas.** Decides qué entra, qué se deja fuera y puedes fijar a mano el
  texto de cualquier documento para que no se regenere.

## Cómo empiezas

1. Instalas y activas el plugin. Se abre solo un **asistente de 12 pasos**.
2. El asistente te pide lo justo: qué contenido entra, datos de tu negocio,
   datos de tienda si tienes WooCommerce, y qué crawlers de IA quieres permitir.
3. Cada paso genera su documento al guardarlo. Al final ves un resumen con lo
   que está listo y lo que falta.

Puedes saltar cualquier paso, salir y volver más tarde. No borra ninguna
configuración existente.

## Control sobre quién entra en tu web

Un catálogo de crawlers de IA y de otros bots (IA, SEO, buscadores, archivado,
scanners) donde marcas **Permitir** o **Bloquear** uno a uno, con propuestas
para `robots.txt` y `.htaccess`.

Y una opción que solo tiene sentido en esta época: **bloquear a un bot para todo
el sitio menos para `/llms.txt`**. Ese bot no entra en tu web, pero sí lee el
resumen que has preparado para él.

Antes de tocar `robots.txt` o `.htaccess`, el plugin te obliga a descargar una
copia, y para reemplazar `.htaccess` además pide una casilla de responsabilidad
y comprueba los permisos. Si no hay permisos, no toca nada.

---

# B. Para agencias y profesionales

## Por qué te interesa

- **Un servicio nuevo que ofrecer.** «Visibilidad para IA» (GEO) es lo que el
  SEO fue para Google: tus clientes ya lo están pidiendo o lo van a pedir.
- **Mucho trabajo hecho.** Lo que se tardaría días en escribir y mantener a
  mano (fichas de producto para IA, `llms.txt`, prompt del chatbot Support Genix, reglas de
  crawlers) sale del propio WordPress del cliente.
- **Control fino, no una caja negra.** Por cada documento puedes:
  - editarlo a mano y fijarlo (el plugin te avisa si el original cambia);
  - darle instrucciones propias de estilo;
  - fijarle su propio límite de caracteres;
  - regenerarlo, borrarlo o volver a modo automático.
- **Seguro de instalar en sitios ajenos.** No recrea productos, pedidos ni
  pagos: solo lee lo que ya existe con las APIs oficiales de WooCommerce. Los
  cambios en archivos del servidor exigen copia y confirmación.
- **Webs en varios idiomas.** Funciona con WPML (probado en una web real) y
  también sin plugin de idiomas. Cada página o producto tiene un solo documento,
  en el idioma principal, que indica en qué otros idiomas existe y con qué
  enlace; si prefieres uno por idioma, es una casilla. FAQ, tienda y Negocio
  salen en el idioma principal. El `llms.txt` es único.
- **Interfaz en español, catalán, alemán, inglés y francés.**
- **Sin dependencias duras.** WooCommerce, tu plugin de idiomas y Support Genix se
  detectan solos; si no están, esas funciones no se activan y el resto sigue.
- **Actualizaciones automáticas** desde el propio panel de WordPress.

## Un entregable que se ve

Para enseñar el resultado al cliente: su `/llms.txt` público, el resumen del
estado de exposición y la comprobación de accesibilidad (si `robots.txt` o un
`noindex` bloquean un contenido). Además, el plugin incluye un **prompt de
auditoría** para pasarlo por un agente de IA externo antes y después de la
configuración, y comparar.

---

# C. Cómo funciona

1. **Lee** tu contenido real (productos, páginas, entradas y otros tipos que
   elijas) y los datos de tu negocio.
2. **Redacta** un documento claro por cada elemento. La IA solo escribe la
   descripción; precio, envío, impuestos y variaciones los añade el código.
3. **Publica** en varios formatos a la vez: `/llms.txt`, Markdown público, datos
   estructurados (JSON-LD) y una API JSON propia.
4. **Alimenta a tu chatbot** (Support Genix, Pro o Lite), con esos documentos y
   con el prompt de sistema.
5. **Se mantiene solo**: detecta cambios, actualiza y respeta un límite diario
   de generaciones que tú fijas para controlar el gasto de IA.

## Requisitos

- WordPress 5.8 o superior (probado hasta 7.1) y PHP 7.4 o superior.
- **Una conexión de IA** para redactar: los Conectores de WordPress 7.0 o
  superior (Anthropic, OpenAI o Google) o Support Genix. El consumo de IA lo
  cobra tu proveedor, no el plugin; el límite diario ayuda a controlarlo.
- WooCommerce, un plugin de idiomas (hoy, WPML) y Support Genix son opcionales.

## Lo que no hace (y conviene decirlo)

- **No garantiza aparecer en ChatGPT ni en ninguna IA.** Hace que tu
  información sea fácil de encontrar y de entender; lo que cite cada IA no
  depende del plugin.
- **`llms.txt` es una convención emergente**, todavía no un estándar oficial. El
  plugin lo publica, pero cada IA decide si lo consulta.
- **`robots.txt` es una petición**, no un muro: un bot puede ignorarlo. Para un
  bloqueo real, el plugin ofrece reglas en `.htaccess`. Aun así, el
  `User-Agent` de un bot puede falsificarse.
- **No sustituye a tu SEO.** Lo complementa.
- **No genera contenido legal ni de pago libremente:** los documentos de
  tienda solo se pulen de redacción, sin cambiar datos.

## Preguntas que te harán

**¿Necesito WooCommerce?**
No. Funciona con páginas, entradas y cualquier tipo de contenido.

**¿Necesito Support Genix?**
No para generar y publicar. Solo si quieres que el chatbot Support Genix use esos datos.

**¿La IA se inventa cosas de mi negocio?**
El plugin está diseñado para que redacte solo con los datos reales del
sitio, y el bloque de datos de compra no pasa por la IA. Los documentos se
pueden revisar y fijar a mano.

**¿Cuánto me va a costar la IA?**
Depende de tu proveedor y de cuántos documentos generes. Fijas un límite
diario y puedes empezar con pocos.

**¿Y si cambio un precio o un envío?**
El documento del producto se regenera. Una venta que solo cambia el stock, no.

**¿Y si mi web está en varios idiomas?**
Funciona con WPML y también sin plugin de idiomas. Por defecto genera un
documento por contenido, en tu idioma principal, con enlaces a sus
traducciones. Con Polylang o TranslatePress todavía no lo ofrecemos como
funcionalidad probada (ver más abajo).

**¿Y si quiero quitar el plugin?**
Tus datos se quedan guardados. Si lo desinstalas del todo, se eliminan, con
un aviso previo.

---

# D. En estudio (todavía no disponible)

Estas líneas están pensadas pero **no se ofrecen como funciones**:

- Un modo para generar los documentos **sin IA**, copiando el contenido de
  WordPress tal cual.
- Compatibilidad con **Polylang** y **TranslatePress**: ya está programada,
  pero solo se ha probado con WPML en una web real. Hasta comprobarla en una
  instalación de cada uno, no se ofrece como función.

---

# E. Precio

| Plan                   | Qué incluye                                           | Precio                 |
| ---------------------- | ----------------------------------------------------- | ---------------------- |
| **Sitio**              | 1 web, actualizaciones y soporte                      | **49 €/año por web**   |
| **Sin límite de webs** | Todas las webs que quieras, actualizaciones y soporte | **99 €/año**           |
| **Lifetime**           | Sin límite de webs, para siempre                      | **222 € (pago único)** |

**Servicio opcional de configuración:** si prefieres que lo dejemos todo
configurado por ti, unos **50 €** aparte.

### Por qué estos precios

- **Lo que compras es el trabajo de traducir tu web al idioma de la IA**, con
  IA, y mantenerlo al día: no un simple archivo `llms.txt`.
- **Tu chatbot gratuito funciona como uno Pro, o mejor.** Con Support Genix
  (Lite o Pro), el plugin le da a tu chatbot lo que más importa: conocimiento
  real y actualizado de tu negocio, en vez de respuestas genéricas. Un Genix
  gratuito con este plugin responde como uno Pro, o mejor.
- Los plugins que solo generan `llms.txt` suelen ser **gratuitos** o freemium:
  [LLMs.txt Generator](https://wordpress.org/plugins/aiready-llms-txt-generator/),
  [Website LLMs.txt](https://wordpress.org/plugins/website-llms-txt/),
  [Emargy GEO](https://wordpress.org/plugins/emargy-geo/). Este plugin no se
  vende como un `llms.txt`, sino como una base de conocimiento completa con
  datos de compra reales, chatbot Support Genix conectado, control de crawlers y asistente.
- Los plugins de chatbot y base de conocimiento con IA cobran por año y por
  número de sitios, en un rango de unos 39 a 240 dólares:
  [WPBot](https://www.wpbot.pro/pricing/) desde 39 $/año (1 sitio),
  [AI Engine Pro](https://kinsta.com/blog/wordpress-ai-plugins/) 79 $/año y
  [BetterDocs](https://betterdocs.co/wordpress-chatbot-plugins-with-ai-knowledge-base/)
  con IA a unos 240 $/año (5 sitios).

El consumo de IA lo cobra tu proveedor (Anthropic, OpenAI, Google o Support
Genix) y no está incluido en ningún plan.

---

# F. Siguiente paso

**Misha** · [help@22mw.online](mailto:help@22mw.online)

Enlace de compra o demo: por completar.

---

---

# Anexo · Pruébalo tú antes de decidir

Puedes comprobar **ahora mismo, sin instalar nada**, cómo ve una IA tu web. Es
el mismo prompt de auditoría que usa el plugin.

## Cómo hacerlo

1. Copia el prompt de abajo.
2. Sustituye `[URL]` por la dirección de tu web, **con la barra final** (por
   ejemplo `https://tuweb.com/`). Aparece varias veces.
3. Pégalo en una IA que **pueda navegar por internet** (ChatGPT, Claude,
   Gemini u otra). Si la IA no puede abrir páginas, no podrá comprobar tu web.
4. Lee el resultado: una tabla con lo que encuentra y un **nivel final de
   visibilidad para IA: BAJO, MEDIO o ALTO**.

El prompt solo mira la **capa técnica**: si una IA puede descubrir, leer y
entender tu web. No es una auditoría de SEO, ni de textos, ni de diseño.

## Cómo lo usamos nosotros

Usamos este mismo prompt **antes y después**:

- **Antes** de configurar nada: es tu punto de partida.
- **Después**, al terminar: repites la prueba con el mismo prompt y comparas.

En las webs donde lo hemos aplicado, el resultado **antes** suele ser **BAJO o
MEDIO** y **después**, **ALTO**. Si ya sales en ALTO, no necesitas el plugin; si
sales en BAJO o MEDIO, el resultado te dice qué falta.
Dentro del plugin, el asistente incluye este prompt en su primer paso y en el
último, con la dirección de tu web ya puesta, para que solo tengas que copiarlo.

> Cada IA puede responder de forma algo distinta. Para comparar antes y
> después, usa la misma IA y el mismo prompt.

## El prompt

```markdown
# Auditoría GEO - Visibilidad IA de una web

Analiza la web: [URL]

Objetivo:
Evaluar cómo una inteligencia artificial, agente autónomo o buscador con IA puede descubrir, interpretar y acceder a esta web actualmente.

NO hagas auditoría SEO.
NO analices keywords.
NO analices posicionamiento.
NO analices copywriting.
NO analices diseño visual.
NO valores estrategia comercial.

Analiza únicamente la capa técnica de visibilidad, accesibilidad y comprensión para sistemas IA.

---

## Recursos GEO y archivos técnicos

Comprueba directamente todos los recursos disponibles:

- [URL]robots.txt
- [URL]llms.txt
- [URL]sitemap.xml
- [URL]sitemap_index.xml
- [URL]wp-sitemap.xml
- feeds XML
- feeds JSON
- endpoints públicos
- APIs relacionadas
- archivos Markdown
- documentación para IA
- cualquier archivo específico GEO encontrado

Para cada recurso:

- URL exacta comprobada.
- Código HTTP.
- Si existe o no.
- Si ha podido ser leído completamente.
- Información que aporta a una IA.

IMPORTANTE:
No marques un archivo como "no verificado" sin intentar acceder primero.
Si no puedes leerlo indica exactamente:

- motivo del fallo;
- bloqueo encontrado;
- error HTTP;
- limitación técnica.

No dejes recursos sin intentar comprobar.

---

# Analiza exclusivamente:

## 1. Percepción IA actual

Explica brevemente:

- Qué puede entender una IA de la web.
- Qué nivel de acceso tiene.
- Si existe una capa preparada para agentes IA.
- Si la información está organizada para interpretación automática.

Máximo 50 líneas.

---

## 2. Tabla comparativa técnica

Entrega una tabla:

| Elemento           | Estado | Resultado observado | Impacto para IA |
| ------------------ | ------ | ------------------- | --------------- |
| robots.txt         |        |                     |                 |
| llms.txt           |        |                     |                 |
| sitemap            |        |                     |                 |
| Markdown IA        |        |                     |                 |
| JSON               |        |                     |                 |
| Feeds              |        |                     |                 |
| APIs               |        |                     |                 |
| Schema.org         |        |                     |                 |
| Product            |        |                     |                 |
| Organization       |        |                     |                 |
| FAQ                |        |                     |                 |
| Otros recursos GEO |        |                     |                 |

---

## 3. Calidad de archivos GEO

Evalúa únicamente la calidad técnica de cada archivo:

### llms.txt

Analiza:

- existencia;
- estructura;
- claridad para modelos IA;
- enlaces útiles;
- organización;
- actualización;
- relación con otros recursos.

### robots.txt

Analiza:

- acceso permitido/bloqueado para bots IA;
- reglas específicas;
- coherencia con llms.txt.

### JSON / APIs / Feeds

Analiza:

- existencia;
- accesibilidad;
- formato;
- utilidad para agentes IA.

NO evalúes la calidad del texto comercial.
Evalúa únicamente si sirve como fuente interpretable por IA.

---

## 4. Problemas detectados

Lista únicamente problemas confirmados.

Clasificación:

- Confirmado: comprobado directamente.
- Riesgo: posible limitación no confirmada.
- No verificado: no se ha podido comprobar.

No inventes problemas.

---

## 5. Nivel final de visibilidad IA

Clasifica:

BAJO / MEDIO / ALTO

Basado únicamente en:

- accesibilidad;
- archivos GEO;
- estructuras técnicas;
- facilidad de interpretación automática.

No valores contenido, SEO ni marketing.

---

Formato final obligatorio:

1. Resumen humano (máximo 50 líneas).
2. Tabla comparativa técnica.
3. Nivel de visibilidad IA.
4. Problemas confirmados.

No incluyas recomendaciones generales.
No expliques qué se podría hacer.
Entrega únicamente el estado actual de la web.
```

---

###

<img src="../logo/ai%20knowldge%20bw.svg" alt="AI Knowledge & Visibility" width="222">
