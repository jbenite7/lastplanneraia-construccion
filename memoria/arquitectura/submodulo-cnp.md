---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
fuente: public/index.php
resumen: "Módulo CNP — Causas de No Programación: rutas, controladores, servicios y quién puede usarlo"
---
# CNP — Causas de No Programación

**Qué resuelve.** _Pendiente de escribir a mano._

**Dónde encaja.** En el flujo LPS. Ver [[flujo-lps]].

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| POST | `/api/cnp/list` | `App\Controllers\Api\CnpApiController::list` |
| POST | `/api/cnp/reprogramar` | `App\Controllers\Api\CnpApiController::reprogramar` |
| POST | `/api/cnp/save` | `App\Controllers\Api\CnpApiController::save` |
| GET | `/programacion-semanal/cnp` | `App\Controllers\Programacion\ProgramacionSemanalController::cnp` |

### Controladores
- `App\Controllers\Api\CnpApiController`
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
