---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
fuente: public/index.php
resumen: "Módulo Profesionales: rutas, controladores, servicios y quién puede usarlo"
---
# Profesionales

**Qué resuelve.** _Pendiente de escribir a mano._

**Dónde encaja.** En el flujo LPS. Ver [[flujo-lps]].

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
