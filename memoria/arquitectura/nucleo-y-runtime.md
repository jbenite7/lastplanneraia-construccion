---
capa: wiki
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [arquitectura, design-system]
tags: [generado]
fuente: public/index.php
resumen: "Núcleo y runtime: sesión, contexto de semana activa y los assets del design system que sirve cada request"
---
# Núcleo, sesión y runtime

**Qué resuelve.** No es un módulo que alguien abra: es lo que sostiene a todos los demás. Guarda
qué semana está activa en cada flujo (PG/PI/PS), mantiene la sesión viva mientras se trabaja
(`/session/touch`) y sirve el CSS del design system compilado para el resto de vistas. Antes de
tocar algo aquí, ten claro que un cambio afecta a toda la app, no a un módulo aislado.

**Dónde encaja.** Fuera de los dos flujos de negocio: es infraestructura de la aplicación.

## Navegación desde el shell

Las rutas PHP legadas que conservan el panel usan `views/partials/shell_sidebar.php`. Las rutas
migradas a React usan AppShell y NavegacionLateral (frontend/src/shell/AppShell.tsx:65-72,
NavegacionLateral.tsx:39-79); el selector de proyectos (frontend/src/shell/rutas.tsx:290-296) y
Programa General (rutas.tsx:450-451) ya entran por esa SPA. No
asumas que todo módulo activo comparte el sidebar PHP ni su comportamiento de flyouts. En el shell
legado, el grupo **Información** lleva a Control Tower - Informes ([[torre-de-control-bi]]), a
Semanas del Proyecto (ver [[legado]] para crear y eliminar semana), Profesionales,
Subcontratistas, Indicadores LPS y Control de Cambios; **Obra** lleva a Programa General,
Programación Intermedia, Programación Semanal y Actualizar Cronograma; **Compras** lleva a Plan de
Compras ([[plan-de-compras]]). El menú de usuario ofrece cambiar proyecto y cerrar sesión.

Ver [[arquitectura]] para el mapa completo y [[navbar-css-consumidor-vivo]] para las trampas del
CSS que consume el shell legado.

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| POST | `/api/context/weeks/create` | `App\Controllers\Api\WeekContextApiController::crear` |
| POST | `/api/context/weeks/delete-last` | `App\Controllers\Api\WeekContextApiController::eliminarUltima` |
| POST | `/context/clear-week` | `App\Controllers\Core\ContextController::clearWeek` |
| POST | `/context/week` | `App\Controllers\Core\ContextController::setWeek` |
| GET | `/runtime/css/aia-design-system.css` | `App\Controllers\Core\DesignSystemAssetController::main` |
| GET | `/runtime/css/design-system/entrypoints/attach-anychart.css` | `App\Controllers\Core\DesignSystemAssetController::attachAnychart` |
| GET | `/runtime/css/design-system/entrypoints/attach-handsontable.css` | `App\Controllers\Core\DesignSystemAssetController::attachHandsontable` |
| GET | `/runtime/css/design-system/entrypoints/attach-jquery-ui.css` | `App\Controllers\Core\DesignSystemAssetController::attachJqueryUi` |
| GET | `/runtime/css/design-system/entrypoints/attach-select2.css` | `App\Controllers\Core\DesignSystemAssetController::attachSelect2` |
| GET | `/runtime/css/design-system/entrypoints/attach-sweetalert2.css` | `App\Controllers\Core\DesignSystemAssetController::attachSweetalert2` |
| GET | `/runtime/css/design-system/entrypoints/core.css` | `App\Controllers\Core\DesignSystemAssetController::core` |
| GET | `/runtime/css/design-system/lab-entrypoint.css` | `App\Controllers\Core\DesignSystemAssetController::laboratory` |
| GET | `/runtime/frontend-config.js` | `App\Controllers\Core\FrontendConfigController::javascript` |
| POST | `/session/touch` | `App\Controllers\Core\SessionController::touch` |

### Controladores
- `App\Controllers\Api\WeekContextApiController`
- `App\Controllers\Core\ContextController`
- `App\Controllers\Core\DesignSystemAssetController`
- `App\Controllers\Core\FrontendConfigController`
- `App\Controllers\Core\SessionController`

### Servicios
- `CrearSemanaComando`
- `DatabaseWeekAdministrationRepository`
- `EliminarSemanaComando`
- `FeatureFlagService`
- `RestrictionConfigResolver`
- `ResultadoCreacionSemana`
- `ResultadoEliminacionSemana`
- `WeekAdministrationService`
- `WeekContextService`

### Tablas
- `general_proyectos_procesos`

### Quién puede
_Sin capacidad propia: la ruta exige sesión y proyecto, no una capacidad específica._
<!-- generado:fin -->
