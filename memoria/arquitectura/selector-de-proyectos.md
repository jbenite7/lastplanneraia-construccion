---
capa: wiki
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [rbac, arquitectura]
tags: [generado]
fuente: public/index.php
resumen: "Selector de proyectos: pantalla React (/proyectos) tras el login para elegir en qué proyecto trabajar; el controlador y la vista PHP legados quedan sin uso"
---
# Selector de proyectos

**Qué resuelve.** Es la pantalla que aparece justo después de iniciar sesión (o al usar
`Cambiar proyecto` desde el menú de usuario, ver [[nucleo-y-runtime]]): elegir con qué proyecto
trabajar. El rol de la sesión depende de a qué proyecto entraste, porque `project_members` guarda
el rol por proyecto, no por cuenta.

**Dónde encaja.** Fuera de los dos flujos de negocio: es infraestructura de la aplicación.

**Corregido el 2026-09-21 (S04, PR #50).** La tabla generada de abajo lista lo que registra el
router, pero para `GET`/`HEAD /proyectos` **no es lo que se sirve**: está en
`SpaRouter::RUTAS_EXACTAS_MIGRADAS` (`src/Core/SpaRouter.php:18`) desde S04, así que
`SpaHostRenderer` pinta el shell React (`RutaProyectos` en `frontend/src/shell/rutas.tsx:56-57,247`,
con `BarraLateral` + `SelectorProyectos`/`TarjetaProyecto` en `frontend/src/shell/proyectos/`) antes
de llegar a `App\Controllers\Core\ProjectSelectorController::index`. Ese controlador y
`views/core/project_selector.view.php` siguen en el repo pero **sin ninguna ruta que los alcance en
la práctica** (el router sigue registrándolos como fallback: `public/index.php:123-125`), igual que
`POST /proyecto/seleccionar`. La pantalla React consume `GET /api/proyectos` y
`POST /api/proyectos/seleccionar` vía `frontend/src/lib/api/proyectos.ts:16-28`, que llegan a
`App\Controllers\Api\ProjectApiController` — la misma que ya listaba la tabla generada; no cambió.

Su vista PHP legada está catalogada en [[VISTAS-MODULOS|docs/VISTAS-MODULOS.md]], que como en
[[autenticacion]] va atrás: no refleja que `/proyectos` ya no la sirve.

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| POST | `/api/proyectos/seleccionar` | `App\Controllers\Api\ProjectApiController::select` |
| GET | `/api/proyectos` | `App\Controllers\Api\ProjectApiController::index` |
| POST | `/proyecto/seleccionar` | `App\Controllers\Core\ProjectSelectorController::select` |
| GET | `/proyectos` | `App\Controllers\Core\ProjectSelectorController::index` |

### Controladores
- `App\Controllers\Api\ProjectApiController`
- `App\Controllers\Core\ProjectSelectorController`

### Servicios
- `ProjectAccessService`

### Tablas
- `general_proyectos_procesos`
- `general_usuarios`
- `project_members`

### Quién puede
_Sin capacidad propia: la ruta exige sesión y proyecto, no una capacidad específica._
<!-- generado:fin -->
