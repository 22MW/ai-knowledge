# Mejoras para convertir el plugin en una capa de visibilidad IA para WordPress

## Objetivo

Ampliar el plugin actual más allá de `llms.txt` y los archivos Markdown para convertirlo en una **capa automática de publicación y exposición de contenido de WordPress preparada para buscadores, asistentes y agentes de IA**.

El plugin no debería estar limitado a WooCommerce.

La arquitectura debería funcionar con:

- Entradas.
- Páginas.
- Cualquier Custom Post Type (CPT).
- Taxonomías personalizadas.
- Campos personalizados.
- ACF, Meta Box, Pods u otros sistemas de custom fields.
- WooCommerce como integración especializada.

La filosofía debería ser:

> WordPress = fuente única de verdad  
> Plugin = capa automática de exposición, estructuración y sincronización para IA

El plugin no debería inventar contenido. Debe leer los datos existentes, normalizarlos, transformarlos a formatos útiles y mantenerlos actualizados automáticamente.

---

# 1. Arquitectura general: Core + Adapters

La arquitectura debería separar el núcleo del plugin de las integraciones específicas.

```text
                     WORDPRESS
                         │
        ┌────────────────┼────────────────┐
        │                │                │
      Posts            Pages             CPT
                                           │
                         ┌─────────────────┼─────────────────┐
                         │                 │                 │
                    Inmuebles         Servicios         Eventos
                         │
                         └─────────────────┬─────────────────┘
                                           │
                                      CORE PLUGIN
                                           │
                                  Content Normalizer
                                           │
              ┌────────────────────────────┼────────────────────────────┐
              ▼                            ▼                            ▼
          Markdown                        JSON                        JSON-LD
              │                            │                            │
              └────────────────────────────┼────────────────────────────┘
                                           ▼
                                  API / Feeds / Discovery
```

WooCommerce debería ser simplemente un adapter especializado:

```text
WooCommerce
    │
    ▼
WooCommerce Adapter
    │
    ▼
Core Normalizer
```

Esto permite reutilizar toda la infraestructura para cualquier CPT.

---

# 2. Soporte para cualquier Custom Post Type

El plugin debería detectar automáticamente los CPT públicos registrados en WordPress.

Ejemplos:

```text
product
property
service
event
course
vehicle
hotel
room
team_member
project
portfolio
restaurant
destination
```

En ajustes:

```text
Contenido disponible para IA

[x] Entradas
[x] Páginas
[x] Productos WooCommerce
[x] Inmuebles
[x] Servicios
[ ] Equipo
[x] Eventos
```

Cada tipo de contenido podría configurarse independientemente.

---

# 3. Normalizador genérico de contenido

Crear una capa interna común que convierta cualquier contenido WordPress en un objeto estable.

Ejemplo conceptual:

```json
{
  "id": 123,
  "post_type": "property",
  "title": "Casa en Mallorca",
  "slug": "casa-mallorca",
  "description": "...",
  "excerpt": "...",
  "url": "...",
  "status": "publish",
  "published_at": "...",
  "updated_at": "...",
  "author": {},
  "featured_image": {},
  "taxonomies": {},
  "custom_fields": {}
}
```

Todos los formatos generados deberían consumir este objeto normalizado.

```text
WordPress
    ↓
Content Normalizer
    ↓
Canonical Content Object
    ↓
 ┌────┼─────┬───────┬──────┐
 ↓    ↓     ↓       ↓      ↓
MD   JSON  JSON-LD  API   Feeds
```

---

# 4. Mapeo de campos personalizados

Para CPT complejos, el plugin debería permitir mapear campos internos a conceptos semánticos.

Ejemplo para un CPT `inmueble`:

```text
Título                → name
Contenido             → description
precio                 → price
ubicacion              → location
habitaciones           → bedrooms
banos                  → bathrooms
superficie             → floorSize
imagen_destacada       → image
```

Ejemplo de salida:

```json
{
  "type": "inmueble",
  "name": "Casa en Mallorca",
  "price": 850000,
  "location": "Sóller",
  "bedrooms": 4,
  "bathrooms": 3,
  "floor_size": 240,
  "url": "..."
}
```

El mismo sistema serviría para:

```text
Cursos
Eventos
Hoteles
Vehículos
Restaurantes
Servicios
Profesionales
Proyectos
```

---

# 5. Compatibilidad con ACF, Meta Box, Pods y custom fields

El plugin debería poder detectar:

```text
post_meta
ACF fields
Meta Box fields
Pods fields
taxonomías personalizadas
relaciones entre contenidos
```

Idealmente existirían dos modos:

### Automático

El plugin detecta campos disponibles y los expone según reglas básicas.

### Avanzado

El administrador decide:

```text
campo interno         nombre público
precio_propiedad  →   price
habitaciones       →   bedrooms
superficie_m2      →   floorSize
```

Esto permite convertir WordPress en una fuente de datos estructurada sin exigir desarrollo personalizado.

---

# 6. Catálogo JSON dinámico por tipo de contenido

No limitar el catálogo a productos.

Ejemplos:

```text
/ai/catalog.json
/ai/products.json
/ai/properties.json
/ai/services.json
/ai/events.json
/ai/courses.json
```

Cada CPT podría tener su propio catálogo.

Ejemplo:

```json
{
  "type": "property",
  "updated_at": "2026-08-22T22:30:00+02:00",
  "items": []
}
```

---

# 7. JSON individual por contenido

Cada contenido podría disponer de una representación JSON limpia:

```text
/ai/content/123.json
```

o según su CPT:

```text
/ai/properties/123.json
/ai/services/84.json
/ai/events/520.json
```

También mediante REST:

```text
/wp-json/ai-visibility/v1/content/123
/wp-json/ai-visibility/v1/properties/123
```

Esto permite consultar el contenido sin procesar HTML, CSS, JavaScript, menús, banners o elementos visuales.

---

# 8. API REST pública de solo lectura

Crear una API específica del plugin:

```text
GET /wp-json/ai-visibility/v1/site
GET /wp-json/ai-visibility/v1/content-types
GET /wp-json/ai-visibility/v1/content
GET /wp-json/ai-visibility/v1/content/{id}
GET /wp-json/ai-visibility/v1/{post_type}
GET /wp-json/ai-visibility/v1/{post_type}/{id}
GET /wp-json/ai-visibility/v1/search?q=...
```

La API debería ser:

- read-only
- cacheable
- paginada
- rápida
- sin datos privados
- configurable por CPT

## Ejemplo

```text
GET /wp-json/ai-visibility/v1/properties?location=mallorca
```

Podría devolver únicamente inmuebles relevantes.

---

# 9. OpenAPI automático

Generar:

```text
/openapi.json
```

describiendo la API pública del plugin.

Esto prepara WordPress para agentes capaces de descubrir herramientas y endpoints automáticamente.

Podría documentar operaciones como:

```text
buscar contenido
buscar productos
consultar servicios
consultar propiedades
consultar eventos
consultar disponibilidad
consultar precios
```

---

# 10. Markdown dinámico por cualquier CPT

El sistema actual de Markdown debería ampliarse para cualquier contenido.

Ejemplos:

```text
/llm/es/productos/producto.md
/llm/es/inmuebles/casa-mallorca.md
/llm/es/servicios/diseno-web.md
/llm/es/eventos/cata-vinos.md
/llm/es/cursos/curso-wordpress.md
```

Cada archivo debería generarse desde el objeto normalizado del contenido.

---

# 11. `llms.txt` como índice general

`llms.txt` debería actuar principalmente como mapa de descubrimiento.

Ejemplo:

```text
/llms.txt
/es/llms.txt
/en/llms.txt
```

Y desde cada índice:

```text
Productos
Servicios
Inmuebles
Eventos
Cursos
Páginas principales
FAQ
Información corporativa
```

Así el sistema escala sin convertir el archivo principal en un listado enorme.

---

# 12. Markdown alternativo descubierto desde el HTML

Cada página, post o CPT debería indicar automáticamente su versión Markdown:

```html
<link
  rel="alternate"
  type="text/markdown"
  href="https://dominio.com/llm/es/inmuebles/casa-mallorca.md">
```

También puede enlazar el índice general:

```html
<link
  rel="describedby"
  href="https://dominio.com/llms.txt">
```

La misma información podría añadirse mediante cabeceras HTTP `Link`.

---

# 13. Schema.org / JSON-LD configurable por CPT

El plugin debería generar Schema.org adecuado según el tipo de contenido.

Ejemplos:

```text
Article
BlogPosting
Organization
Person
Product
Offer
Service
Event
Course
LocalBusiness
Hotel
Restaurant
RealEstateListing
Vehicle
Review
FAQPage
BreadcrumbList
```

El administrador podría mapear:

```text
CPT `event`        → Event
CPT `service`      → Service
CPT `property`     → RealEstateListing
CPT `course`       → Course
```

WooCommerce utilizaría su adapter especializado para:

```text
Product
Offer
AggregateOffer
Review
AggregateRating
Brand
```

---

# 14. Adapter específico para WooCommerce

WooCommerce debería añadir capacidades adicionales al Core.

Datos específicos:

```text
precio
precio rebajado
moneda
stock
SKU
GTIN
MPN
marca
variaciones
atributos
reviews
rating
imágenes
categorías
shipping
```

El adapter convertiría:

```text
WC_Product
WC_Product_Simple
WC_Product_Variable
WC_Product_Variation
```

al mismo objeto normalizado utilizado por el Core.

Así WooCommerce no condiciona la arquitectura general.

---

# 15. Gestión de crawlers de IA

Añadir un módulo para comprobar y opcionalmente configurar `robots.txt`.

Separar claramente:

```text
Search / retrieval crawlers
Training crawlers
```

Panel conceptual:

```text
OpenAI Search       ✅
Perplexity          ✅
Claude Search       ✅
Google              ✅
Bing                ✅

AI training         configurable
```

El plugin debería detectar bloqueos provocados por:

```text
robots.txt
X-Robots-Tag
noindex
Cloudflare
WAF
403
401
```

---

# 16. Sitemap avanzado

Mantener automáticamente los sitemaps necesarios según los CPT activos.

Ejemplo:

```text
/sitemap.xml
/post-sitemap.xml
/page-sitemap.xml
/product-sitemap.xml
/property-sitemap.xml
/service-sitemap.xml
/event-sitemap.xml
```

La fecha `lastmod` debe representar cambios reales.

---

# 17. IndexNow automático

Cuando WordPress cree, actualice o elimine contenido:

```text
WordPress
     ↓
contenido actualizado
     ↓
plugin
     ↓
IndexNow
```

Eventos:

```text
post creado
página modificada
CPT actualizado
producto actualizado
precio cambiado
stock cambiado
taxonomía modificada
contenido eliminado
```

Debe existir cola/debounce para evitar notificaciones duplicadas.

---

# 18. Feeds especializados

El sistema podría permitir adapters de feeds según el tipo de contenido.

Ejemplos:

```text
WooCommerce → Google Merchant
Eventos     → Event feed
Inmuebles   → Property feed
Cursos      → Course feed
```

Y además un feed genérico:

```text
/feeds/content.json
/feeds/products.json
/feeds/properties.json
```

---

# 19. Endpoints virtuales en vez de archivos físicos

Preferible:

```text
/llm/es/inmuebles/casa.md
```

a exponer:

```text
/wp-content/llm/...
```

Arquitectura:

```text
Request
   ↓
WordPress rewrite
   ↓
Plugin
   ↓
Content Normalizer
   ↓
Markdown / JSON
   ↓
Cache
```

---

# 20. Caché e invalidación selectiva

No regenerar todos los contenidos cuando cambia uno.

Ejemplo:

```text
Inmueble 123 cambia
      ↓
invalidar:
- inmueble-123.md
- inmueble-123.json
- catálogo de inmuebles
- taxonomías relacionadas
- sitemap correspondiente
```

No invalidar:

```text
todo WordPress
```

Esto será crítico en webs grandes.

---

# 21. Hooks genéricos de WordPress + adapters específicos

El Core debería escuchar:

```text
save_post
deleted_post
transition_post_status
updated_post_meta
created_term
edited_term
delete_term
comment_post
wp_set_comment_status
```

Y cada adapter puede añadir sus propios eventos.

Por ejemplo WooCommerce:

```text
woocommerce_update_product
woocommerce_new_product
woocommerce_delete_product
woocommerce_product_set_stock
woocommerce_variation_set_stock
```

---

# 22. Fuente única de verdad

Todos los formatos deben derivarse de la misma capa.

```text
WordPress
      ↓
Normalizer
      ↓
Canonical Content Object
      ↓
 ┌────┼─────┬───────┬───────┬───────┐
 ↓    ↓     ↓       ↓       ↓       ↓
HTML MD    JSON   JSON-LD   API    Feeds
```

Esto reduce contradicciones y simplifica el mantenimiento.

---

# 23. Panel "AI Visibility"

Crear dentro de WordPress una pantalla sencilla:

```text
AI VISIBILITY

ChatGPT Search         ✅ Accessible
Claude Search          ✅ Accessible
Perplexity             ✅ Accessible
Google                 ✅ Accessible
Bing                   ✅ Accessible

Content exposed        1.482 / 1.482
Markdown               ✅
JSON                    ✅
REST API                ✅
Schema                  ✅
llms.txt                ✅
Sitemap                 ✅
IndexNow                ✅

Content types:
Posts                   320
Pages                    48
Products                284
Properties              730
Services                100
```

---

# 24. Botón "Test AI accessibility"

Diagnóstico automático:

```text
robots.txt
HTTP status
noindex
X-Robots-Tag
canonical
Markdown
JSON
Schema
sitemap
llms.txt
API
Content-Type
UTF-8
```

Salida:

```text
AI Visibility Score: 92/100

2 problemas:
- 14 inmuebles sin ubicación estructurada
- Claude crawler bloqueado por robots.txt
```

---

# 25. Logs de crawlers

Registrar opcionalmente accesos de bots conocidos:

```text
OpenAI
Claude
Perplexity
Google
Bing
```

Mostrar:

```text
crawler
URL
tipo de contenido
fecha
status HTTP
response time
```

Con agregación y límites para no llenar la base de datos.

---

# 26. Sistema modular

Configuración:

```text
CORE
[x] llms.txt
[x] Markdown
[x] JSON
[x] REST API
[x] OpenAPI
[x] JSON-LD
[x] AI crawler management
[x] Sitemap
[x] IndexNow

ADAPTERS
[x] WooCommerce
[x] ACF
[ ] Meta Box
[ ] Pods

COMMERCE
[ ] Google Merchant

ANALYTICS
[ ] Crawler analytics
```

---

# Arquitectura recomendada final

```text
                         WORDPRESS
                            │
          ┌─────────────────┼─────────────────┐
          │                 │                 │
        Posts             Pages              CPT
                                                │
                          ┌─────────────────────┼─────────────────────┐
                          │                     │                     │
                     Inmuebles              Eventos              Servicios
                                                                      │
                         ┌────────────────────────────────────────────┘
                         ▼
                  CONTENT NORMALIZER
                         │
                 Canonical Content Object
                         │
      ┌───────────┬──────┼──────┬───────────┬───────────┐
      ▼           ▼      ▼      ▼           ▼           ▼
  Markdown       JSON  JSON-LD  REST       Feeds      Search
      │           │      │      │           │
      └───────────┴──────┼──────┴───────────┘
                         ▼
                    CACHE / SYNC
                         │
           ┌─────────────┼─────────────┐
           ▼             ▼             ▼
       llms.txt        Sitemap       IndexNow
                         │
                         ▼
                   AI / Search / Agents
```

WooCommerce:

```text
WooCommerce
     │
     ▼
Woo Adapter
     │
     ▼
Content Normalizer
```

---

# Prioridad recomendada

## Fase 1 — Core WordPress

1. Normalizador genérico de contenido.
2. Detección y selección de CPT.
3. Soporte custom fields.
4. Markdown dinámico.
5. `llms.txt`.
6. JSON individual.
7. Catálogos JSON.
8. Caché e invalidación.

## Fase 2 — Semántica y discovery

9. Mapping de campos.
10. JSON-LD configurable.
11. `rel="alternate"` Markdown.
12. API REST.
13. OpenAPI.
14. robots / crawler manager.
15. sitemap avanzado.
16. IndexNow.

## Fase 3 — Adapters

17. WooCommerce.
18. ACF.
19. Meta Box.
20. Pods.
21. Feeds especializados.

## Fase 4 — Diagnóstico y analytics

22. AI Visibility dashboard.
23. Test automático de accesibilidad.
24. Crawler logs.
25. Alertas de datos incompletos.

---

# Posicionamiento del producto

El producto no debería presentarse simplemente como:

> Generador de `llms.txt`.

Ni limitarse a:

> Plugin GEO para WooCommerce.

Una definición más potente sería:

> **AI Visibility Layer for WordPress**

o:

> **Capa automática de publicación y acceso para IA en WordPress.**

WooCommerce sería una integración importante, pero no el núcleo del producto.

El valor real está en que el propietario sigue trabajando normalmente en WordPress y el plugin mantiene automáticamente todas las representaciones externas sincronizadas:

```text
WordPress
     ↓
Posts / Pages / CPT / WooCommerce
     ↓
Markdown
JSON
Schema
API
Feeds
Sitemaps
IndexNow
     ↓
IA / Search / Agents
```
