---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
fuente: public/index.php
resumen: "Módulo CNC — Causas de No Cumplimiento: rutas, controladores, servicios y quién puede usarlo"
---
# CNC — Causas de No Cumplimiento

**Qué resuelve.** _Pendiente de escribir a mano._

**Dónde encaja.** En el flujo LPS. Ver [[flujo-lps]].

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| POST | `/api/cnc/list` | `App\Controllers\Api\CncApiController::list` |
| POST | `/api/cnc/reasons` | `App\Controllers\Api\CncApiController::reasons` |
| POST | `/api/cnc/save` | `App\Controllers\Api\CncApiController::save` |
| GET | `/programacion-semanal/cnc` | `App\Controllers\Programacion\ProgramacionSemanalController::cnc` |

### Controladores
- `App\Controllers\Api\CncApiController`
- `App\Controllers\Programacion\ProgramacionSemanalController`

### Servicios
- `ProjectLandingService`

### Tablas
- `general_cnc`

### Quién puede
| Capacidad | Roles que la tienen |
| --- | --- |
| `canEditWeeklyProgram` | A, D, R, G, S, SG |
<!-- generado:fin -->
