---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
fuente: public/index.php
resumen: "Programación Semanal: la grilla de compromisos de los próximos 7 días y sus tres píldoras CNP/CNC/CIC"
---
# Programación Semanal

**Qué resuelve.** Es el corazón del [[lps-dominio|Last Planner System]]: la grilla donde los
Last Planners asumen compromisos reales para la semana. De aquí sale el PAC/PPC, el indicador que
mide si el equipo cumple lo que promete. Antes de tocarla conviene conocer [[submodulo-cnp]],
[[submodulo-cnc]] y [[submodulo-cic]], que son sus tres vistas satélite.

**Dónde encaja.** En el flujo LPS. Ver [[flujo-lps]].

Su vista está catalogada en [[VISTAS-MODULOS|docs/VISTAS-MODULOS.md]].

Desde la propia vista, tres píldoras cambian de pestaña sin salir del módulo: `CNP` lleva a
`/programacion-semanal/cnp` ([[submodulo-cnp]], causas por las que algo no entró a la semana),
`CNC` a `/programacion-semanal/cnc` ([[submodulo-cnc]], causas por las que algo no se cumplió) y
`CIC` a `/programacion-semanal/cic` ([[submodulo-cic]], la calificación de contratistas).

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| GET | `/api/semanal/auto-program-log` | `App\Controllers\Api\SemanalApiController::getAutoProgramLog` |
| POST | `/api/semanal/auto-program` | `App\Controllers\Api\SemanalApiController::autoProgram` |
| GET | `/api/semanal/list` | `App\Controllers\Api\SemanalApiController::list` |
| POST | `/api/semanal/list` | `App\Controllers\Api\SemanalApiController::list` |
| POST | `/api/semanal/reabrir` | `App\Controllers\Api\SemanalApiController::reabrir` |
| POST | `/api/semanal/save` | `App\Controllers\Api\SemanalApiController::save` |
| GET | `/api/semanal/tnp-actividades` | `App\Controllers\Api\SemanalApiController::getTnpActivities` |
| POST | `/api/semanal/tnp-actividades` | `App\Controllers\Api\SemanalApiController::getTnpActivities` |
| GET | `/programacion-semanal` | `App\Controllers\Programacion\ProgramacionSemanalController::index` |

### Controladores
- `App\Controllers\Api\SemanalApiController`
- `App\Controllers\Programacion\ProgramacionSemanalController`

### Servicios
- `LpsService`
- `ProgramChangeDetector`
- `ProgramaConsolidadoNormalizationService`
- `ProjectLandingService`
- `RestrictionConfigResolver`
- `WeeklyRealProgressCarryoverService`

### Tablas
- `general_cnc`
- `general_proyectos_procesos`
- `general_usuarios`

### Quién puede
| Capacidad | Roles que la tienen |
| --- | --- |
| `canManageWeeklyProgram` | A, D, R, G, S, SG |
| `canManageWeeks` | A, D, R, DCV, OT |
<!-- generado:fin -->
