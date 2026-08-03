---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [arquitectura, design-system]
fuente: public/index.php
resumen: "Módulo Núcleo, sesión y runtime: rutas, controladores, servicios y quién puede usarlo"
---
# Núcleo, sesión y runtime

**Qué resuelve.** _Pendiente de escribir a mano._

**Dónde encaja.** Fuera de los dos flujos de negocio: es infraestructura de la aplicación.

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
