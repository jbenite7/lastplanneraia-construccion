---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
fuente: public/index.php
resumen: "CNP: registra por qué una actividad del look-ahead no entró a la semana comprometida"
---
# CNP — Causas de No Programación

**Qué resuelve.** Registra por qué una actividad que estaba en el look-ahead de
[[programacion-intermedia]] no llegó a entrar al plan de la semana — normalmente porque alguno de
sus 7 recursos de liberación no estaba resuelto a tiempo. Se abre como una de las tres píldoras de
[[programacion-semanal]].

**Dónde encaja.** En el flujo LPS. Ver [[flujo-lps]].

Su vista está catalogada en [[VISTAS-MODULOS|docs/VISTAS-MODULOS.md]].

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
| `canManageWeeklyProgram` | A, D, R, G, S, SG |
<!-- generado:fin -->
