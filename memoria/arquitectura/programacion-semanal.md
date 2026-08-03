---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
fuente: public/index.php
resumen: "Módulo Programación Semanal: rutas, controladores, servicios y quién puede usarlo"
---
# Programación Semanal

**Qué resuelve.** _Pendiente de escribir a mano._

**Dónde encaja.** En el flujo LPS. Ver [[flujo-lps]].

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
| `canEditWeeklyProgram` | A, D, R, G, S, SG |
| `canManageWeeklyProgram` | A, D, R, G, S, SG |
| `canManageWeeks` | A, D, R, DCV, OT |
<!-- generado:fin -->
