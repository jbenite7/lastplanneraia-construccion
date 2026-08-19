---
capa: wiki
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
tags: [generado]
fuente: public/index.php
resumen: "Profesionales: grilla editable en vivo con el equipo AIA asignado al proyecto"
---
# Profesionales

**Qué resuelve.** Mantiene la lista de profesionales de AIA asignados al proyecto (residentes,
directores, etc.) en una grilla Handsontable con autosave. Otros módulos, como
[[programa-general]] o [[control-de-cambios]], reutilizan estos nombres como responsables.

**Dónde encaja.** En el flujo LPS. Ver [[flujo-lps]].

Su vista está catalogada en [[VISTAS-MODULOS|docs/VISTAS-MODULOS.md]].

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| GET | `/api/profesionales/list` | `App\Controllers\Api\ProfesionalesApiController::list` |
| POST | `/api/profesionales/list` | `App\Controllers\Api\ProfesionalesApiController::list` |
| POST | `/api/profesionales/save` | `App\Controllers\Api\ProfesionalesApiController::save` |
| GET | `/profesionales` | `App\Controllers\Gestion\ProfesionalesController::index` |

### Controladores
- `App\Controllers\Api\ProfesionalesApiController`
- `App\Controllers\Gestion\ProfesionalesController`

### Servicios
- `LpsService`
- `ProjectProfessionalsSyncService`

### Tablas
- `general_proyectos_procesos`
- `general_usuarios`
- `project_members`

### Quién puede
_Sin capacidad propia: la ruta exige sesión y proyecto, no una capacidad específica._
<!-- generado:fin -->
