---
capa: wiki
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
`MaintenanceMode` y `SessionMiddleware::beginRequest()` (era `check()` hasta el 2026-08-29), y despacha por `App\Core\Router`, que envuelve a
FastRoute. Las rutas están declaradas ahí mismo, en una lista larga agrupada por comentarios: **218
llamadas `$router->…`** contadas el 2026-09-21 (215 rutas en el inventario de
`scripts/wiki-arquitectura.mjs --cobertura`, sin cambios desde el 2026-09-17); esta línea decía
«217» el 2026-09-17 y «~150» antes.

**Antes del router está la frontera con el shell React** (añadido el 2026-09-17, ampliado el
2026-09-21). `SpaRouter::sirveLaSpa()` decide qué `GET`/`HEAD` son de la SPA —hoy `/`, `/login`,
`/password/forgot`, `/password/reset`, `/proyectos` (sumada en S04, PR #50) y el árbol `/app`
(`src/Core/SpaRouter.php:18,21`)—: esas rutas no exigen sesión de PHP (`public/index.php:56`) y se
despachan con `SpaHostRenderer::render()` antes de `$router->dispatch()` (`:403-406`). Las
mutaciones siguen siempre en su controlador PHP.

Algunas rutas apuntan a cierres que hacen `require_once` de un script procedural de `src/Legacy/`.
Eso es el carril legado, no un error. Es zona de mantenimiento: se corrige la causa con el cambio
mínimo y no se añade funcionalidad nueva.

`src/` se reparte en `Controllers/` (por dominio), `Services/` (lógica de negocio), `Core/`
(router, base de datos, sesión, resolución de tablas), `Security/` (RBAC, CSRF, políticas),
`Support/` (helpers transversales) y `View/Components/`.

## Los 12 dominios y submódulos del producto

El inventario canónico y detallado de cada pantalla, herramienta satélite y controlador vive en **[[mapa-de-modulos-y-submodulos]]**:

- **[[autenticacion|Autenticación y Acceso]]**: Login SPA, recuperación, cambio forzado de clave, `/dev/entrar` y ruta secreta.
- **[[selector-de-proyectos|Contexto de Proyecto y Runtime]]**: Selector de obras, `AppShell`, selector y gestión de semanas (`/context/week`, crear y eliminar última semana) y conmutador de tema.
- **[[programa-general|Programa General]] y [[cronograma|Actualizador de Cronogramas]]**: Grilla maestra EDT con bloqueo temporal vs herramienta independiente de cargue de reprogramaciones Excel/Project (`programa-general-actualizar`).
- **[[programacion-intermedia|Programación Intermedia]]**: Lookahead de 6 semanas, 8 estados de restricción y modal de restricciones compartidas.
- **[[programacion-semanal|Programación Semanal y Causa Raíz]]**: Plan Semanal (WWP), auto-programación, reapertura, monitor de cambios y los tres submódulos de análisis: [[submodulo-cnp|CNP]], [[submodulo-cnc|CNC]] y [[submodulo-cic|CIC]].
- **[[plan-de-compras|Plan de Compras v2 (PDC)]]**: SPA React en `pdc-app/` con 6 submódulos (Presupuesto/Versiones, Maestro/Equipos, Paquetes/Auto-asignación, Fechas/Reprogramación, Pasos/Duraciones, Seguimiento/Subpaquetes/Flujo de caja).
- **Directorios Maestros**: [[profesionales|Directorio de Profesionales AIA]] y [[subcontratistas|Directorio de Subcontratistas y Proveedores]].
- **Integración y Reportes**: [[control-de-cambios|Control de Cambios Contractual (ODC)]] y [[integracion|Centro de Descargas Excel y Reportes Asíncronos (`/reportes/{tipo}`)]].
- **[[torre-de-control-bi|Torre de Control BI]] e [[indicadores|Indicadores LPS]]**: Tablero ejecutivo React (`ct-app`), radar/gestión de restricciones, 8 reportes dedicados `/bi/*` e indicadores embebidos PowerBI.
- **[[escalamientos-y-crisis|Comunicación Contextual y Crisis]]**: Cajón contextual LPS, protocolo SOS/crisis, dashboard de escalamientos y bandeja de notificaciones.
- **[[panel-admin|Panel de Administración Central]]**: Mini-app `admin/` (dashboard, usuarios, proyectos, matching/familias, flags de módulos y limpieza PDC).
- **Herramientas Internas y Soporte**: [[laboratorio-design-system|Laboratorio del Design System]] y [[legado|Carril Legado]].

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

## El área, en una tabla

<!-- Vista nativa de Obsidian Bases. Si no renderiza, el contenido de arriba sigue siendo
     legible: los plugins y las vistas amplifican, no sostienen. -->
![[area-arquitectura.base]]

## Vecinos

[[rbac-y-rutas]] para sesión y permisos · [[pdc]] para el módulo de compras ·
[[entorno-y-despliegue]] para levantar todo esto.
