---
capa: wiki
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [lps, arquitectura]
tags: [generado]
fuente: public/index.php
resumen: "Programa General: la línea base del cronograma maestro; editar el pasado exige rol A o D"
---
# Programa General

**Qué resuelve.** Es el cronograma maestro del proyecto — la línea base contra la que se mide todo
lo demás en el flujo LPS. Solo Admin y Director pueden editar filas de semanas ya pasadas
(`canEditPastGeneralProgram`); el resto del equipo puede ver y editar el presente/futuro. Para
traer una versión nueva desde Excel, ver [[cronograma]].

**Dónde encaja.** En el flujo LPS. Ver [[flujo-lps]].

Su vista está catalogada en [[VISTAS-MODULOS|docs/VISTAS-MODULOS.md]].

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| POST | `/api/general/auto-associate` | `App\Controllers\Api\GeneralApiController::autoAssociate` |
| GET | `/api/general/codigos` | `App\Controllers\Api\GeneralApiController::getCodigos` |
| POST | `/api/general/decision-log` | `App\Controllers\Api\GeneralApiController::decisionLog` |
| POST | `/api/general/delete-update` | `App\Controllers\Api\GeneralApiController::deleteUpdate` |
| POST | `/api/general/import` | `App\Controllers\Api\GeneralApiController::importExcel` |
| GET | `/api/general/list` | `App\Controllers\Api\GeneralApiController::list` |
| POST | `/api/general/list` | `App\Controllers\Api\GeneralApiController::list` |
| GET | `/api/general/restriction-config` | `App\Controllers\Api\GeneralApiController::restrictionConfig` |
| POST | `/api/general/update-batch` | `App\Controllers\Api\GeneralApiController::updateBatch` |
| POST | `/api/general/update` | `App\Controllers\Api\GeneralApiController::update` |
| POST | `/api/pg/breadcrumb-estandarizar` | `App\Controllers\Api\PgBreadcrumbController::standardize` |
| POST | `/api/pg/breadcrumb-preview` | `App\Controllers\Api\PgBreadcrumbController::preview` |
| POST | `/programa-general/filtros` | `App\Controllers\Programacion\ProgramaGeneralController::getFilters` |
| GET | `/programa-general/set-filtro` | `App\Controllers\Programacion\ProgramaGeneralController::setFilter` |
| GET | `/programa-general` | `App\Controllers\Programacion\ProgramaGeneralController::index` |

### Controladores
- `App\Controllers\Api\GeneralApiController`
- `App\Controllers\Api\PgBreadcrumbController`
- `App\Controllers\Programacion\ProgramaGeneralController`

### Servicios
- `ActivityMatcherService`
- `EstadoSemanalService`
- `LpsService`
- `ModuleRequestContext`
- `PgAvanceEdicionManualService`
- `ProgramaConsolidadoNormalizationService`
- `ProjectLandingService`
- `WeeklyRealProgressCarryoverService`

### Tablas
- `general_codigos_actividades`
- `general_matching_config`
- `general_proyectos_procesos`
- `general_usuarios`
- `pg_avance_edicion_manual`
- `program_unique_id_sequences`
- `programa`
- `programa_consolidado`

### Quién puede
| Capacidad | Roles que la tienen |
| --- | --- |
| `canManageGeneralProgram` | A, D, R, DCV |
| `canEditPastGeneralProgram` | A, D |
<!-- generado:fin -->
