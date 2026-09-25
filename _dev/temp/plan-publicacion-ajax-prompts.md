# Plan corto: público, AJAX, prompt y Genix

Sin implementar. Nada de esto toca lo que ya funciona hasta que se apruebe
cada pieza por separado.

## 1. ~~Elegir qué se publica en llms.txt~~ — descartado

Ya existe la exclusión general en "Contenido" (excluir por ID). No hace
falta un checkbox nuevo por documento.

## 2. Botón "Generar" sin recargar la página

El botón que ya existe para regenerar un documento, pero por AJAX en vez de
recargar toda la pantalla.

## 3. Prompt propio por documento

Poder escribir instrucciones especiales para un documento en concreto. Si no
escribes nada, se usa el prompt de siempre.

## 4. Límite de caracteres

Ya existe, no hay que construir nada nuevo, solo comprobar que sigue
funcionando igual con lo de arriba.

## 5. Revisar que solo se publica lo publicado

Comprobar que en ningún sitio del plugin se cuela un borrador o algo
despublicado.

## 6. Genix — artículos escritos solo ahí, sin producto detrás

Para un artículo que escribiste directamente en Genix (no generado por este
plugin): poder copiarlo tal cual a `.md` y meterlo en `llms.txt`, con su
propio checkbox "Público". Sin IA, sin tocar nada dentro de Genix.

**Lo que salió mal la vez pasada:** se leían también los artículos que YA
tenían su propio documento generado por este plugin (como "IVA – 0"),
duplicándolos. Esta vez se excluyen esos — solo entran los que de verdad
solo existen en Genix.

## Lo que NO se toca

Nada de lo que ya funciona hoy (chatbot, generación normal, Registro) se
altera mientras no apruebes cada pieza.

---
¿Confirmas estas 6 piezas para empezar a diseñarlas técnicamente?
