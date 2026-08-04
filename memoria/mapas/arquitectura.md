---
tipo: mapa
estado: vigente
fecha: 2026-08-02
areas: [arquitectura]
fuente: sesion
resumen: "Cómo está montada la aplicación: front controller, src/, el mini-app admin/ y las tablas globales"
---
# Mapa · Arquitectura

## Qué manda

- [[AGENTS]] — contrato autoritativo. Define el aislamiento por `project_id` y qué se puede tocar.
- [[docs/global-tables-architecture]] — **lectura obligatoria** antes de tocar schema, migraciones,
  backfills o ciclo de vida de proyectos. Empieza siempre en dry-run.
- [[CLAUDE]] — dónde vive cada cosa en el código.
- [[docs/global-tables-unique-ids|Unique IDs en tablas globales]] — el hermano operativo del
  anterior: cómo se generan y qué garantiza cada identificador cuando las tablas son compartidas.
- [[database/patches/global/README|database/patches/global]] — los parches equivalentes en versión
  global de los que había por proyecto. Se mira antes de escribir un parche nuevo.

Orientación general del producto y del repo, fuera del código: [[README]] es la puerta de entrada,
[[PRODUCT]] fija propósito y principios, y [[GEMINI]] repite las reglas para otro asistente (donde
choque con [[AGENTS]], manda AGENTS).

## La forma del sistema

`public/index.php` es un front controller plano, sin framework: carga el autoload, aplica
`MaintenanceMode` y `SessionMiddleware::check()`, y despacha por `App\Core\Router`, que envuelve a
FastRoute. Las ~150 rutas están declaradas ahí mismo, en una lista larga agrupada por comentarios.

Algunas rutas apuntan a cierres que hacen `require_once` de un script procedural de `src/Legacy/`.
Eso es el carril legado, no un error. Es zona de mantenimiento: se corrige la causa con el cambio
mínimo y no se añade funcionalidad nueva.

`src/` se reparte en `Controllers/` (por dominio), `Services/` (lógica de negocio), `Core/`
(router, base de datos, sesión, resolución de tablas), `Security/` (RBAC, CSRF, políticas),
`Support/` (helpers transversales) y `View/Components/`.

## `admin/` es otra aplicación

No reutiliza `src/Core` ni `src/Security`. Tiene su propio front controller, su propio router, sus
propios modelos y sus propias vistas. Comparte el autoloader de Composer y el mismo esquema MySQL,
nada más. Al rastrear un fallo, trátalo como un código base aparte.

En CSS sí comparte tokens y tema, pero por una vía propia — ver [[admin-adminlte-adaptador]].

## Datos

Tablas globales compartidas entre proyectos, aisladas por `project_id` en **toda** consulta
operativa. Las tablas `{prefix}_*` son compatibilidad histórica: no escribas SQL nuevo contra
ellas, ni construyas SQL dinámico apoyándote en `Base_de_Datos` o `dbPrefix`.

Trampas medidas al tocar datos: [[dos-stacks-docker]],
[[stack-principal-migraciones-pdc-pendientes]], [[no-enriquecer-daporto-para-medir]].

## Vecinos

[[rbac-y-rutas]] para sesión y permisos · [[pdc]] para el módulo de compras ·
[[entorno-y-despliegue]] para levantar todo esto.
