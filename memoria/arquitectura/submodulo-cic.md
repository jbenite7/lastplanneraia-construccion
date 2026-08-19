---
capa: wiki
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
tags: [generado]
fuente: public/index.php
resumen: "CIC: califica a cada subcontratista en 4 dimensiones dentro de Programación Semanal"
---
# CIC — Cumplimiento de Actividades

**Qué resuelve.** Evalúa a cada subcontratista en 4 dimensiones de desempeño dentro de la semana en
curso. Se abre como una de las tres píldoras de [[programacion-semanal]] y trabaja sobre el mismo
catálogo que [[subcontratistas]].

**Dónde encaja.** En el flujo LPS. Ver [[flujo-lps]].

Su vista está catalogada en [[VISTAS-MODULOS|docs/VISTAS-MODULOS.md]].

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| POST | `/api/cic/list` | `App\Controllers\Api\CicApiController::list` |
| POST | `/api/cic/save` | `App\Controllers\Api\CicApiController::save` |
| GET | `/programacion-semanal/cic` | `App\Controllers\Programacion\ProgramacionSemanalController::cic` |

### Controladores
- `App\Controllers\Api\CicApiController`
- `App\Controllers\Programacion\ProgramacionSemanalController`

### Servicios
- `ProjectLandingService`

### Tablas
- `cic`
- `general_cnc`

### Quién puede
| Capacidad | Roles que la tienen |
| --- | --- |
| `canManageWeeklyProgram` | A, D, R, G, S, SG |
<!-- generado:fin -->
