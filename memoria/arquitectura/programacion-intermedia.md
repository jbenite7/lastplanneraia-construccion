---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
fuente: public/index.php
resumen: "Módulo Programación Intermedia: rutas, controladores, servicios y quién puede usarlo"
---
# Programación Intermedia

**Qué resuelve.** _Pendiente de escribir a mano._

**Dónde encaja.** En el flujo LPS. Ver [[flujo-lps]].

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| GET | `/api/pi/list` | `App\Controllers\Programacion\ProgramacionIntermediaController::list` |
| POST | `/api/pi/save` | `App\Controllers\Programacion\ProgramacionIntermediaController::save` |
| POST | `/programacion-intermedia/filtros` | `App\Controllers\Programacion\ProgramacionIntermediaController::getFilters` |
| GET | `/programacion-intermedia/set-filtro` | `App\Controllers\Programacion\ProgramacionIntermediaController::setFilter` |
| GET | `/programacion-intermedia/set-view-all` | `App\Controllers\Programacion\ProgramacionIntermediaController::setViewAll` |
| POST | `/programacion-intermedia/shared-constraints/apply` | `App\Controllers\Programacion\ProgramacionIntermediaController::applySharedConstraints` |
| POST | `/programacion-intermedia/shared-constraints/preview` | `App\Controllers\Programacion\ProgramacionIntermediaController::previewSharedConstraints` |
| GET | `/programacion-intermedia` | `App\Controllers\Programacion\ProgramacionIntermediaController::index` |

### Controladores
- `App\Controllers\Programacion\ProgramacionIntermediaController`

### Servicios
- `NotificationService`

### Tablas
- `general_proyectos_procesos`
- `general_usuarios`
- `project_members`
- `system_notifications`

### Quién puede
| Capacidad | Roles que la tienen |
| --- | --- |
| `canEditMediumTerm` | A, D, R, DCV |
| `canManageMediumTermProgram` | A, D, R, DCV |
| `canEditConstraints` | A, D, R, DCV, OT, G, S, SG |
<!-- generado:fin -->
