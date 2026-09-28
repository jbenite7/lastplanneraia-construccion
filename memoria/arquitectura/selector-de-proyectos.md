---
capa: wiki
tipo: modulo
estado: vigente
fecha: 2026-09-28
areas: [rbac, arquitectura]
tags: [generado]
fuente: public/index.php
resumen: "Selector de proyectos: pantalla React (/proyectos) tras el login para elegir en qué proyecto trabajar; el controlador y la vista PHP legados se retiraron el 2026-09-28"
---
# Selector de proyectos

**Qué resuelve.** Es la pantalla que aparece justo después de iniciar sesión (o al usar
`Cambiar proyecto` desde el menú de usuario, ver [[nucleo-y-runtime]]): elegir con qué proyecto
trabajar. El rol de la sesión depende de a qué proyecto entraste, porque `project_members` guarda
el rol por proyecto, no por cuenta.

**Dónde encaja.** Fuera de los dos flujos de negocio: es infraestructura de la aplicación.

**Corregido el 2026-09-28 (retiro de VIEW-11).** Desde S04 (PR #50, 2026-09-18) `GET`/`HEAD /proyectos`
están en `SpaRouter::RUTAS_EXACTAS_MIGRADAS` (`src/Core/SpaRouter.php:18`), así que
`SpaHostRenderer` pinta el shell React (`RutaProyectos` en `frontend/src/shell/rutas.tsx`, con
`BarraLateral` + `SelectorProyectos`/`TarjetaProyecto` en `frontend/src/shell/proyectos/`) antes de
llegar al router. El legado PHP —`views/core/project_selector.view.php`,
`App\Controllers\Core\ProjectSelectorController`, `public/css/project-selector.css` y
`POST /proyecto/seleccionar`— se conservó como respaldo hasta que Felipe autorizó retirarlo aparte
(«Retirarlo en un PR aparte», 2026-09-28), como en S02 y S03. Hoy `public/index.php` no registra
`/proyectos` ni `/proyecto/seleccionar`: un `POST /proyecto/seleccionar` sin sesión redirige a
`/login` (no está en `$publicRoutes`) y con sesión cae al 404 controlado. La pantalla React consume
`GET /api/proyectos` y `POST /api/proyectos/seleccionar` vía `frontend/src/lib/api/proyectos.ts`,
que llegan a `App\Controllers\Api\ProjectApiController`; toda la autorización sigue en
`ProjectAccessService`, que no cambió.

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| POST | `/api/proyectos/seleccionar` | `App\Controllers\Api\ProjectApiController::select` |
| GET | `/api/proyectos` | `App\Controllers\Api\ProjectApiController::index` |

### Controladores
- `App\Controllers\Api\ProjectApiController`

### Servicios
- `ProjectAccessService`

### Tablas
- `general_proyectos_procesos`
- `general_usuarios`
- `project_members`

### Quién puede
_Sin capacidad propia: la ruta exige sesión y proyecto, no una capacidad específica._
<!-- generado:fin -->
