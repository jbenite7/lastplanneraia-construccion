---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [arquitectura, design-system]
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

Todos los módulos activos comparten el mismo `views/partials/shell_sidebar.php`. Se organiza en
tres grupos. El grupo **Información** lleva a `Control Tower - Informes` ([[torre-de-control-bi]]),
a `Semanas del Proyecto` (abre un flyout con la lista de semanas — ver [[legado]] para las rutas de
crear y eliminar semana), a `Profesionales`, `Subcontratistas`, `Indicadores LPS` y
`Control de Cambios`. El grupo **Obra** lleva a `Programa General`, `Programación Intermedia`,
`Programación Semanal` y `Actualizar Cronograma`; en los tres primeros, pasar el mouse sobre el
ítem abre un flyout con las semanas de ese módulo, y elegir una semana **cambia la semana activa y
redirige al propio módulo** — no es un enlace fijo, depende del contexto que guarda este módulo. El
grupo **Compras** solo lleva a `Plan de Compras` (la isla React, ver [[plan-de-compras]]). El menú
de usuario ofrece `Cambiar proyecto` (hacia [[selector-de-proyectos]]) y `Cerrar sesión`.

Ver [[arquitectura]] para el mapa completo y [[navbar-css-consumidor-vivo]] para las trampas del
CSS que consume este shell.

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
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
- `App\Controllers\Core\ContextController`
- `App\Controllers\Core\DesignSystemAssetController`
- `App\Controllers\Core\FrontendConfigController`
- `App\Controllers\Core\SessionController`

### Servicios
- `FeatureFlagService`

### Tablas
_indeterminado_

### Quién puede
_Sin capacidad propia: la ruta exige sesión y proyecto, no una capacidad específica._
<!-- generado:fin -->
