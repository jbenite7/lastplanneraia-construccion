---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
fuente: public/index.php
resumen: "Programación Intermedia: el look-ahead a 6 semanas que libera restricciones antes de comprometer la semana"
---
# Programación Intermedia

**Qué resuelve.** Es la ventana de look-ahead (6 semanas) donde se identifican y resuelven las
restricciones de una actividad antes de que llegue a [[programacion-semanal]]. Una actividad solo
está «liberada» cuando sus 7 recursos de liberación quedan resueltos aquí — ver [[lps-dominio]]
para el detalle de esos 7 recursos.

**Dónde encaja.** En el flujo LPS. Ver [[flujo-lps]].

Su vista está catalogada en [[VISTAS-MODULOS|docs/VISTAS-MODULOS.md]].

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
- `ResponsableAiaPolicy`

### Tablas
- `general_proyectos_procesos`
- `general_usuarios`
- `project_members`
- `system_notifications`

### Quién puede
| Capacidad | Roles que la tienen |
| --- | --- |
| `canManageMediumTermProgram` | A, D, R, DCV |
| `canEditConstraints` | A, D, R, DCV, OT, G, S, SG |
<!-- generado:fin -->
