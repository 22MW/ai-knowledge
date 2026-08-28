# Resumen revisión `llms.txt` dinámico

## Qué está bien

El enfoque del plugin es sólido: WooCommerce y WordPress actúan como fuente de datos, el plugin genera automáticamente versiones limpias en Markdown y `llms.txt` funciona como índice para que un sistema de IA pueda descubrir productos, páginas y contenidos sin tener que interpretar toda la web.

Lo mejor del planteamiento actual es:

- Generación automática desde WooCommerce/WordPress.
- Archivos `.md` individuales para productos y contenidos.
- Actualización dinámica cuando cambia la información de la web.
- Separación entre la web visual y una versión limpia pensada para máquinas.
- Posibilidad de reutilizar la misma infraestructura tanto para el chatbot propio como para otros agentes o sistemas de IA.

## Mejores mejoras recomendadas

### 1. Usar `llms.txt` principalmente como índice

Evitar cargar demasiado contenido directamente en el archivo principal.

Mejor estructura:

```text
/llms.txt
/es/llms.txt
/en/llms.txt
/de/llms.txt
```

Y desde cada índice enlazar a:

```text
productos
categorías
enoturismo
información de tienda
FAQ
páginas importantes
```

Esto permite escalar a tiendas con cientos o miles de productos.

### 2. Mantener el contenido detallado en archivos Markdown individuales

Ejemplo:

```text
/llm/es/productos/vi-rei-reserva-2020.md
/llm/es/enoturismo/visita-bodega.md
/llm/es/faq.md
```

Así una IA puede recuperar solamente el contenido que necesita.

### 3. Añadir descubrimiento automático del Markdown desde cada página HTML

Cada producto o página debería indicar que existe una versión Markdown equivalente:

```html
<link rel="alternate"
      type="text/markdown"
      href="https://dominio.com/llm/es/productos/producto.md">
```

Y también:

```html
<link rel="describedby"
      href="https://dominio.com/llms.txt">
```

Esto permite que un agente descubra la versión limpia aunque entre directamente en una página y no conozca previamente `llms.txt`.

### 4. Corregir y controlar el encoding

Todo debería generarse siempre en UTF-8 para evitar errores como caracteres rotos en acentos o signos de interrogación.

Recomendado:

```text
text/plain; charset=utf-8
text/markdown; charset=utf-8
```

### 5. Evitar contradicciones o contenido inferido

La información expuesta a IA debería salir únicamente de fuentes controladas de WordPress/WooCommerce.

No conviene generar automáticamente afirmaciones del tipo:

```text
"según reseñas"
"a menudo"
"normalmente"
"puede incluir"
```

si no existe un dato explícito que lo respalde.

Principio recomendado:

> WordPress/WooCommerce debe ser la fuente única de verdad.

### 6. No exponer la implementación interna

Mejor usar URLs limpias como:

```text
/llm/es/productos/producto.md
```

en lugar de:

```text
/wp-content/llm/...
```

El plugin puede generar estos endpoints dinámicamente sin necesidad de exponer la estructura física interna.

### 7. Generar todo dinámicamente y con caché

No es necesario crear físicamente todos los archivos cada vez.

El plugin puede:

```text
WooCommerce
    ↓
Endpoint virtual
    ↓
Generación Markdown
    ↓
Caché
```

Cuando un producto cambia, se invalida únicamente su caché y el índice relacionado.

## Arquitectura recomendada

```text
WooCommerce / WordPress
          │
          ▼
      Plugin IA
          │
    ┌─────┼─────┐
    │     │     │
    ▼     ▼     ▼
llms.txt  MD    Chat
          │
   ┌──────┼─────────┐
   ▼      ▼         ▼
Productos Páginas  FAQ
```

Y cada página HTML puede apuntar directamente a su versión Markdown.

## Conclusión

La base actual tiene mucho sentido.

La mejora más importante no es añadir más contenido a `llms.txt`, sino convertir el plugin en una capa automática de publicación para IA:

> WooCommerce → datos estructurados → Markdown limpio → `llms.txt` → chatbot / agentes / sistemas de IA.

Las prioridades serían:

1. `llms.txt` más ligero y jerárquico.
2. Markdown individual por producto/página.
3. `rel="alternate"` hacia Markdown.
4. UTF-8 correcto.
5. Una única fuente de verdad.
6. URLs limpias.
7. Generación dinámica + caché.
 