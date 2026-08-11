---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
fuente: public/index.php
resumen: "CNC: registra por qué una actividad comprometida no se cumplió, base del PAC/PPC"
---
# CNC — Causas de No Cumplimiento

**Qué resuelve.** Registra por qué una actividad que sí se había comprometido para la semana no se
terminó. Es el dato crudo detrás del PAC/PPC y del análisis de causa raíz (RCA) de
[[indicadores]]. Se abre como una de las tres píldoras de [[programacion-semanal]].

**Dónde encaja.** En el flujo LPS. Ver [[flujo-lps]].

Su vista está catalogada en [[VISTAS-MODULOS|docs/VISTAS-MODULOS.md]].

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
| `canManageWeeklyProgram` | A, D, R, G, S, SG |
<!-- generado:fin -->
