# Informe de auditoría — Woo Knowledge Base Generator

Fecha: 2026-09-14
Rama auditada: `knowBaseDev`
Commit: `46988c3`
Modo: solo lectura

## Resumen

Plugin funcional orientado actualmente a generar documentación Markdown para productos/CPT, publicar `llms.txt` y sincronizar documentos con Support Genix. La base permite evolucionar a AI Visibility, pero el naming, namespace y varias piezas del flujo están centrados en WooCommerce/Support Genix.

## Datos detectados

- Archivo principal: `woo-kb-generator.php`.
- Versión: `1.0.7`.
- Text Domain: `woo-kb-generator`.
- PHP mínimo: `7.4`.
- Namespace: `WOOKB`.
- Tabla propia: `wookb_documents`.
- Opciones principales: `wookb_settings`, `wookb_daily_counter`, `wookb_seed_running`.
- Código PHP: aproximadamente 5.859 líneas.

## Funcionalidad existente

- Generación de Markdown.
- Publicación de `/llms.txt`.
- Registro de documentos y estados.
- Cola con Action Scheduler o WP-Cron.
- Carga inicial por lotes y regeneración individual/masiva.
- Sincronización al guardar, actualizar stock o borrar contenido.
- Soporte WPML.
- FAQ pública.
- Adapter WooCommerce.
- Integración con Support Genix y sincronización de prompt.
- Panel admin: Alcance, Exclusiones, Registro, Prompt, Ajustes y Carga inicial.

## Arquitectura observada

El núcleo está repartido entre `Scope`, `Registry`, `Generator`, `Document_Pipeline`, `Markdown_Store`, `Queue` y `Sync`. Existe una clase base de extractor (`Extractor_Base`) y un extractor WooCommerce (`Extractor_Woo`). La sincronización genérica ya usa `save_post_{post_type}` para tipos configurados, aunque el modelo de datos y parte de la terminología siguen usando producto/WooCommerce.

## Hooks, REST, AJAX y cron

- Acciones administrativas mediante `admin_post_wookb_*`.
- No hay AJAX propio.
- No existe todavía una REST API propia de AI Visibility.
- Se registran eventos de generación, carga inicial e integridad.
- Action Scheduler se usa cuando está disponible; WP-Cron es el fallback.
- Hay guardias REST para proteger `sgkb-docs` de Support Genix.

## Seguridad visible

Se observan comprobaciones de capabilities, nonces, sanitización y escape en la administración, además de `wpdb->prepare()` para consultas con valores variables. Deben revisarse durante el desarrollo la exposición de custom fields, el diseño de la futura API pública, la uniformidad de validaciones y cualquier migración de identificadores.

## Riesgos y trabajo pendiente

1. Renombrado de carpeta, repositorio, cabecera, Text Domain y documentación.
2. Separación formal Core WordPress / adapters.
3. Normalizador canónico para posts, páginas y CPT.
4. Representaciones JSON, Markdown, JSON-LD y API.
5. Política de campos públicos frente a datos sensibles.
6. Caché e invalidación selectiva.
7. Compatibilidad opcional con WooCommerce, WPML y chatbots.
8. Tests de permisos, exposición y dependencias.

## Dependencias

No hay dependencias duras declaradas. WooCommerce, WPML y Support Genix se detectan en tiempo de ejecución y activan funciones concretas.

## Evidencia revisada

Archivo principal, clases de `includes/`, extractor WooCommerce, clases administrativas y vistas, `uninstall.php`, `readme.txt`, `CHANGELOG.md`, assets y documentación `_dev/`. `git status` limpio y `git diff --check` sin incidencias.

## Conclusión

No hace falta conservar la arquitectura nominal actual por instalaciones existentes. Se puede rehacer progresivamente tomando el código útil como base, empezando por el renombrado seguro y un Core genérico. El desarrollo debe seguir el roadmap guardado en `_dev/roadmap.md`.

