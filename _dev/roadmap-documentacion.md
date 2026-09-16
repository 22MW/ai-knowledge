# Roadmap — Documentación pública + técnica de AI Knowledge & Visibility

Plan de trabajo para la documentación que pediste. No se ha escrito ningún
`.md` final todavía — esto es solo el roadmap, a validar antes de arrancar
Fase 1.

## Objetivo

Dos públicos, dos documentos distintos:

1. **Documentación pública** (varios `.md` enlazados entre sí): para la web
   de 22MW y para soporte a usuarios/clientes reales del plugin. Comercial
   en el índice, práctica y humana en el resto.
2. **Documentación técnica**: un único documento que se queda dentro del
   plugin (no se publica en la web), para quien programe sobre este plugin
   en el futuro (vos u otro desarrollador).

## Guía de lenguaje y tono (prompt para cada `.md`)

Basado en las dos referencias que diste
(`verifacwoo.com/docs/instalacion-y-puesta-en-marcha-verifacwoo` y
`verifacwoo.com/docs/impuestos`), mismo estudio (22MW), mismo lector tipo:

> Escribe para una persona que usa WordPress y WooCommerce pero no es
> programadora. Tono conversacional y cercano, nunca frío ni robótico —
> como si se lo explicaras a un cliente por chat. Frases cortas. Evita
> jerga técnica salvo que sea imprescindible, y si aparece un término
> técnico (namespace, hook, endpoint REST...) explícalo en la misma frase,
> no lo des por sabido. Usa "tú"/"tu web"/"tu catálogo", nunca "el
> usuario" en tercera persona. Reafirma tranquilidad cuando el tema da
> miedo (tocar robots.txt, borrar un archivo, IA generando contenido):
> nunca se hace nada irreversible sin que el propio plugin lo confirme
> antes. Un ejemplo de código o de valor real (ej. una URL de ejemplo, un
> fragmento de JSON) solo donde aporte, no como decoración. Pasos
> numerados cuando hay una secuencia real que seguir; listas con viñetas
> para opciones o conceptos sin orden. Un aviso destacado (blockquote `>`)
> para lo importante o lo irreversible. Nunca inventar una función,
> pantalla o comportamiento que no esté confirmado en el código.

Aplica a todos los `.md` públicos. El documento técnico usa un registro
distinto (ver Fase 4).

## Estructura de archivos y enlazado

```
_dev/docs-publicas/
  index.md                    ← qué es, para quién, por qué (comercial)
  instalacion.md              ← instalar y poner en marcha
  tab-registro.md
  tab-contenido.md
  tab-negocio.md
  tab-faqs.md
  tab-woocommerce.md          ← nota: solo visible con WooCommerce activo
  tab-chatbot.md              ← nota: solo visible con Support Genix activo
  tab-visibilidad-ia.md
  tab-generacion-masiva.md
  tab-ajustes.md

_dev/documentacion-tecnica.md ← para desarrolladores, se queda en el plugin
```

`_dev/docs-publicas/` es zona de trabajo/staging dentro del repo del plugin
(no es `_dev` de memoria operativa, es el borrador de lo que luego subís a
la web). Cuando lo apruebes, vos decidís cómo pasa a la web real (copiar,
importar, etc. — eso no lo hago yo salvo que lo pidas).

**Enlazado**: cada `.md` de tab lleva arriba un breadcrumb tipo
`[← Volver al índice](index.md)` y al final una sección "Ver también" con
links a las tabs relacionadas (ej. `tab-contenido.md` enlaza a
`tab-generacion-masiva.md` porque el alcance que configurás ahí es lo que
después generás en masa). `index.md` enlaza a los 9 documentos de tabs +
`instalacion.md`.

**Cierre de cada `.md`**: sección `## Preguntas frecuentes` al final,
3-6 preguntas reales (no genéricas) sobre esa pantalla concreta.

**Etiquetas pendientes de medios**: donde haga falta una captura o vídeo,
se marca en línea propia así:

```
[SCREENSHOT: pantalla de Contenido con los tipos de contenido marcados]
[VIDEO: flujo completo de generación masiva, 1-2 min]
```

para que las reemplaces después por el embed real.

## Fases

### Fase 0 — Confirmar inventario real (evidencia, ya hecho aquí)

Tabs confirmadas contra `admin/class-admin.php` (orden real, no el que dice
`readme.txt`, que está desactualizado en sus pasos de instalación —
menciona "Alcance" y "Exclusiones" como tabs separadas cuando ya están
fusionadas en "Contenido"; lo marco como pendiente aparte, no se toca en
esta tarea de documentación):

| Tab | Slug | Condición |
|---|---|---|
| Registro | `registro` | siempre (tab por defecto) |
| Contenido | `contenido` | siempre |
| Negocio | `negocio` | siempre |
| FAQs | `faqs` | siempre |
| WooCommerce | `woocommerce` | solo si WooCommerce activo |
| Chatbot | `prompt` | solo si Support Genix activo |
| Visibilidad IA | `visibilidad-ia` | siempre |
| Generación masiva | `carga-inicial` | siempre |
| Ajustes | `ajustes` | siempre |

### Fase 1 — `index.md` (comercial, ⏸️ validación)

Qué es el plugin, para quién (tiendas WooCommerce, sitios de contenido),
qué problema resuelve en una frase, qué lo hace distinto (GEO/IA, no solo
SEO clásico), sin prometer nada que el código no haga. Termina con el
índice enlazado a todos los demás documentos.

### Fase 2 — `instalacion.md` (⏸️ validación)

Instalar, activar, primer arranque, qué pide de entrada (clave IA o
Support Genix, WooCommerce opcional), primeros pasos recomendados
(Contenido → Generación masiva). Enlaza a `tab-contenido.md` y
`tab-generacion-masiva.md`.

### Fase 3 — Un `.md` por tab (⏸️ validación por lotes, no una por una)

Para cada tab: qué hace, cada campo/opción explicado en lenguaje humano,
qué pasa si activás cada switch, capturas/vídeo marcados con etiqueta,
preguntas frecuentes. Se agrupan en 2-3 lotes para no pedirte validar
9 documentos de uno en uno (ej. lote A: Registro+Contenido+Negocio+FAQs;
lote B: WooCommerce+Chatbot; lote C: Visibilidad IA+Generación
masiva+Ajustes).

### Fase 4 — Documento técnico para desarrolladores (⏸️ validación)

`_dev/documentacion-tecnica.md`, se queda en el plugin, no en la web.
Registro distinto: técnico directo, sin capas comerciales. Cubre:
arquitectura de archivos, namespace `AIKB`, autoload, tabla
`wookb_documents` y su esquema, hooks/filtros propios si los hay, puntos
de extensión, dependencias en runtime (WooCommerce/WPML/Support Genix
detectadas con `class_exists`/`is_genix_ready`), y las decisiones de
naming ya documentadas en `_dev/decisiones.md` (por qué la tabla sigue
llamándose `wookb_documents` aunque el resto ya es `AIKB`).

### Fase 5 — Revisión final y checklist de medios pendientes

Lista de todas las etiquetas `[SCREENSHOT]`/`[VIDEO]` insertadas, para que
tengas un checklist único de qué grabar/capturar antes de publicar.

## Decisiones que necesito antes de arrancar Fase 1

1. ¿El tono de los dos ejemplos de VerifacWOO (cercano, con avisos
   destacados y FAQ) te sirve tal cual, o querés algún matiz distinto para
   este plugin (más orientado a "visibilidad ante IA" que a fiscalidad)?
2. ¿Arranco ya con Fase 1 (`index.md`) en cuanto apruebes este roadmap, o
   preferís revisar primero el roadmap sin más y decidir el arranque
   después?
