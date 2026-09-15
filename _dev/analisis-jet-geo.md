# Análisis de JetGEO (Crocoblock) — qué copiar/adaptar para AI Knowledge & Visibility

Fecha: 2026-09-15. Plugin analizado:
`app/public/wp-content/plugins/jet-geo` (v1.0.0, activo/inactivo no
verificado, es de otro proveedor — no se toca ni se copia código literal,
solo se documentan ideas y patrones a reimplementar con nuestro propio
código).

Contexto: JetGEO es un plugin nuevo de Crocoblock (mismo fabricante de
JetEngine) con el mismo objetivo de fondo que el nuestro — hacer el
contenido de WordPress legible/gobernable para crawlers e IA — pero sin
generación de documentos vía IA para chatbot (esa parte no la tiene). Vale
la pena robar ideas de arquitectura donde son mejores que lo que tenemos
planeado, no el producto entero.

---

## 1. Catálogo de bots de IA + categorización por propósito (lo que pediste)

**Qué hace JetGEO:** `includes/crawlers/crawler-registry.php` mantiene una
lista de ~24 crawlers de IA conocidos (GPTBot, ChatGPT-User, ClaudeBot,
Claude-User, Claude-SearchBot, Google-Extended, Google-CloudVertexBot,
Applebot-Extended, PerplexityBot, Amazonbot, Bytespider, DeepSeekBot,
meta-externalagent, MistralAI-User, YouBot, CCBot, etc.), cada uno con:
`name` (el token exacto de User-Agent/robots.txt), `operator` (empresa),
`function`/`description` (para qué se usa, en texto), y sobre todo
**`purposes`**: cada bot se clasifica en 1 o más de estos 3 propósitos:

- `ai_search` — genera resultados de búsqueda con IA (ej. OAI-SearchBot,
  PerplexityBot).
- `user_requested_assistant` — visita la web porque un usuario se lo pidió a
  un asistente (ej. ChatGPT-User, Claude-User).
- `model_training` — entrena modelos con el contenido (ej. GPTBot,
  ClaudeBot, Bytespider, CCBot).

Esto es más útil que una lista plana "bloquear sí/no por bot": permite una
pregunta de negocio real ("¿quiero que entrenen modelos con mi contenido?"
es una decisión distinta de "¿quiero aparecer en respuestas de ChatGPT?").

**Para nosotros:** nuestra Fase 11 (roadmap) ya prevé "separar crawlers de
búsqueda/retrieval vs entrenamiento" — esto lo confirma y lo concreta: usar
las mismas 3 categorías (`ai_search` / `user_requested_assistant` /
`model_training`) en vez de inventar las nuestras, es un estándar ya
consolidado en el sector (coincide con cómo lo documentan OpenAI/Anthropic
en sus propias páginas de robots). El catálogo de bots también es
directamente reutilizable como lista base (son datos públicos, no código
protegido — la lista de user-agents y qué hace cada uno es información
factual, no propiedad intelectual de Crocoblock).

**Extensibilidad vía filtro:** exponen `apply_filters('jet_geo_crawlers',
$defaults)` para que otros añadan bots nuevos sin tocar su código. Haríamos
lo mismo (`apply_filters('wookb_ai_crawlers', $defaults)` o el nombre que
corresponda) para no tener que actualizar el plugin cada vez que aparece un
bot nuevo.

---

## 2. Auto-configuración por 3 preguntas (lo que pediste)

**Qué hace JetGEO:** en vez de pedir al admin que configure bot por bot,
`includes/crawlers/purpose-policy-compiler.php` hace lo siguiente:

1. Pregunta 3 cosas en el onboarding (sí/no): ¿permitir `ai_search`?
   ¿permitir `user_requested_assistant`? ¿permitir `model_training`?
2. Con esas 3 respuestas, `Purpose_Policy_Compiler::compile()` recorre TODO
   el catálogo de bots y decide `allow`/`disallow` para cada uno según sus
   `purposes` (un bot con dos propósitos se bloquea si CUALQUIERA de sus
   propósitos está desactivado — política conservadora razonable).
3. `merge()` fusiona esa decisión con las reglas de `robots.txt` que ya
   hubiera, preservando grupos existentes y detectando conflictos
   (`Basic_Group_Resolver`) antes de guardar nada.

Resultado: el admin responde 3 preguntas simples y obtiene un `robots.txt`
completo y correcto para ~24 bots, sin tener que saber qué es "Amzn-User" o
"Bytespider".

**Para nuestro flujo:** esto encaja perfectamente como **Fase 11
(Gestión de crawlers de IA)** del roadmap, que hoy solo dice "lectura +
edición asistida con permiso explícito". Propongo concretarla así:

- 3 preguntas iguales (mismo criterio de categorías del punto 1).
- Generar/proponer un bloque de `robots.txt` a partir de las respuestas.
- **Diferencia importante con JetGEO, por nuestra regla de seguridad
  (`32-seguridad.md`, `.claude/rules`): no escribir en `robots.txt`
  automáticamente.** JetGEO sí lo hace directo. Nosotros ya decidimos en el
  roadmap que la edición de `robots.txt` requiere permiso explícito del
  usuario en cada caso por ser un archivo sensible fuera del propio plugin
  — mantener esa decisión, pero sí se puede auto-generar el bloque
  propuesto y mostrarlo para copiar/aplicar con un clic que pida
  confirmación.

---

## 3. Selección de Custom Post Types (lo que pediste)

**Qué hace JetGEO:** `includes/settings/content-settings.php` —
`Content_Settings::get_allowed_post_types()`:

- Parte de `get_post_types(['public' => true], 'objects')` (igual que
  nuestro `Scope`).
- Excluye una lista técnica fija de "ruido" conocido (`attachment`,
  `elementor_library`, `e-floating-buttons`, `jet-engine`...) — equivalente
  a nuestro `Scope::noise_meta_prefixes()`, pero aplicado a post_types en
  vez de a meta keys.
- Expone DOS filtros de extensión: `jet_geo_content_settings_excluded_post_types`
  (para quitar más) y `jet_geo_content_settings_allowed_post_types` (para
  añadir/quitar libremente la lista final) — permite que integraciones de
  terceros se registren solas sin tocar su código.
- Guarda dos modos: `enable_all_post_types` (booleano, "todos los públicos
  automáticamente, incluidos los que se registren en el futuro") o
  `selected_post_types` (lista concreta) — el modo "todos" es la clave
  interesante: nuestro `Scope::settings()['post_types']` hoy siempre es una
  lista explícita, nunca "todos, presente y futuro".

**Para nosotros:** dos mejoras concretas a `Scope`:
1. Añadir un modo "todos los CPT públicos" (booleano) como alternativa a la
   lista explícita actual — resuelve el caso real de un sitio que añade CPTs
   nuevos con el tiempo (inmobiliarias, eventos...) sin que el admin tenga
   que volver a Alcance cada vez. Encaja con la Fase 9 del roadmap
   ("detección automática de cualquier CPT público").
2. Exponer un filtro de exclusión técnica (`apply_filters` sobre la lista de
   post_types excluidos por defecto) — hoy `Scope` no tiene ningún
   mecanismo de extensión por filtro para post_types, solo para meta keys de
   ruido.

---

## 4. "Códigos cortos" para prompts — Dynamic Tags (lo que pediste)

**Qué hace JetGEO:** `includes/dynamic-tags/` es un sistema propio de
placeholders tipo `{post.title}`, `{post.excerpt}`, `{post.meta key="..."}`,
`{site.name}`, `{woocommerce.price}`, etc. (`manager.php` los registra,
`renderer.php` los sustituye por el valor real en tiempo de generación).
Cada tag tiene `id`, `label`, `description`, `group`, y opcionalmente
`attributes` (para tags parametrizados como `post.meta key="precio"`).

Los usan dentro de sus plantillas de robots.txt/markdown/llms.txt para que
el admin pueda insertar datos dinámicos sin escribir PHP.

**Para nosotros, aplicado a lo que ya tenemos:** encaja directamente con
`Generator::build_prompt()` y con el "Instrucciones adicionales del prompt"
de Ajustes (`extra_prompt`) y con el cuestionario de
`Chatbot_Prompt_Builder`. Hoy esos campos son texto libre sin ninguna
variable dinámica. Se podría permitir escribir, en el prompt adicional o en
el texto manual de un documento, cosas como:

```
Escribe la ficha usando el tono de la marca. El producto se llama {post.title}
y pertenece a la categoría {post.taxonomy name="product_cat"}.
```

y que se sustituya antes de mandarlo a OpenAI (o antes de publicar el texto
manual). Esto es justo lo que pediste en su día para el prompt general (más
indicaciones/control) — un sistema de tags cerraría ese hueco sin tener que
inventar una sintaxis propia desde cero, reutilizando ideas ya probadas:
`{post.title}`, `{post.excerpt}`, `{post.meta key="..."}`,
`{product.price}`, `{product.stock}`, `{site.name}` como mínimo viable,
usando el mismo dato que ya extrae `Extractor_Base`/`Extractor_Woo` (no hay
que consultar nada nuevo, ya está todo en `$data`).

**Dónde encajaría en el roadmap:** no hay fase dedicada hoy. Propongo
añadirlo como pieza de una fase futura ("Fase 12 — Tags dinámicos en
prompts y textos manuales") o como ampliación de la Fase 1 si se quiere
adelantar, ya que el texto manual y el `extra_prompt` ya existen y es donde
más se nota la falta de esto.

---

## 5. Otras cosas interesantes vistas de paso (no pedidas, pero relevantes)

- **Onboarding por pasos con estado persistente** (`includes/onboarding/`,
  `Onboarding_State`, un `Step_Interface` por paso: content-settings,
  robots-txt, llms-txt, markdown, crawler-logs). Cada paso valida su propio
  payload y devuelve los cambios a aplicar (`Option_Change::capture(...)`)
  sin tocar la opción directamente hasta el final — permite deshacer/revisar
  antes de guardar. Útil como patrón si en algún momento montamos un asistente
  de primera configuración para AI Knowledge & Visibility (hoy no lo
  tenemos: el usuario configura pestaña por pestaña sin guía).
- **`Content_Inspector`** (`includes/onboarding/content-inspector.php`, no
  leído en detalle): parece calcular una "proyección de disponibilidad" del
  contenido para mostrar en el onboarding cuánto contenido hay listo — idea
  aprovechable para nuestra futura Fase 6 (panel de diagnóstico): mostrar
  "quedan 40 productos sin sincronizar" de forma proactiva, no solo cuando
  el admin entra al Registro.
- **Content-Signal-Policy-Conflict** (`content-signal-policy-conflict.php`):
  detectan cuando `robots.txt` bloquea un bot pero otra señal (ej. una
  cabecera HTTP `Content-Signal` o meta noindex) lo contradice, y avisan del
  conflicto en vez de dejarlo pasar en silencio — buena práctica para
  nuestra Fase 6 ("test básico de accesibilidad").
- **REST controller propio por módulo** (`rest-controller.php` en cada
  módulo: robots-txt, markdown, llms-txt) en vez de un único controlador
  REST genérico — mismo patrón que ya vamos a seguir nosotros con
  `class-rest-content.php` por pieza.

---

## Resumen de qué llevar al roadmap

| Idea | Fase afectada | Acción propuesta |
|---|---|---|
| 3 categorías de propósito de bot (`ai_search`/`user_requested_assistant`/`model_training`) + catálogo de ~24 bots | Fase 11 | Adoptar las mismas categorías y una lista base de bots equivalente, con filtro de extensión propio. |
| Auto-generar robots.txt a partir de 3 preguntas | Fase 11 | Sí, pero solo generar/proponer el bloque — nunca escribir el archivo sin confirmación explícita (ya es la decisión tomada). |
| Modo "todos los CPT públicos, presente y futuro" en Scope | Fase 9 | Añadir como alternativa a la lista explícita actual. |
| Filtro de exclusión de post_types por defecto | Fase 9 | Añadir `apply_filters` equivalente en `Scope`. |
| Sistema de tags dinámicos (`{post.title}`, etc.) en prompts/texto manual | Nueva fase o ampliación de Fase 1 | Definir MVP con `evaluar-cambio`/`planificar-cambio` cuando se quiera abordar — no está en el roadmap actual. |
| Onboarding por pasos | Ninguna actual | Backlog, no crítico. |
| Detección de conflictos de señales (robots vs noindex) | Fase 6 | Añadir como check del "Test AI accessibility". |

No se ha copiado ni un carácter de código de JetGEO — todo lo anterior es
descripción de comportamiento/arquitectura para reimplementar con nuestro
propio código y nuestras propias reglas de seguridad.
