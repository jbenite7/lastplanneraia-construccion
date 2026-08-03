---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [pdc, arquitectura]
fuente: public/index.php
resumen: "Listado de Actividades: CRUD de actividades del proyecto, base sobre la que se arma el PDC v1"
---
# Listado de Actividades (PDC v1)

**Qué resuelve.** Es el CRUD de actividades del proyecto — la versión anterior al PDC v2 de
[[plan-de-compras]], y todavía la fuente de las actividades que ese módulo consume. No se elimina
mientras el PDC v2 no cubra todo lo que cubre este. Ver [[pdc]] y [[pdc-v2|docs/pdc-v2.md]] para el
mapa completo de dónde encaja cada versión.

**Dónde encaja.** En el flujo del Plan de Compras. Ver [[flujo-pdc]].

Su vista está catalogada en [[VISTAS-MODULOS|docs/VISTAS-MODULOS.md]].

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| GET | `/api/pdc/categorias-recurso` | `App\Controllers\Api\PdcPlantillaController::categorias` |
| GET | `/api/pdc/duracion-sugerida` | `App\Controllers\Api\PdcApiController::duracionSugerida` |
| POST | `/api/pdc/list` | `App\Controllers\Api\PdcApiController::list` |
| GET | `/api/pdc/plantillas/{id}/items` | `App\Controllers\Api\PdcPlantillaController::items` |
| GET | `/api/pdc/plantillas/{id}` | `App\Controllers\Api\PdcPlantillaController::show` |
| GET | `/api/pdc/plantillas` | `App\Controllers\Api\PdcPlantillaController::list` |
| POST | `/api/pdc/save` | `App\Controllers\Api\PdcApiController::save` |
| POST | `/api/pdc/update-cell` | `App\Controllers\Api\PdcApiController::updateCell` |
| GET | `/pdc` | `App\Controllers\Gestion\PdcController::index` |

### Controladores
- `App\Controllers\Api\PdcApiController`
- `App\Controllers\Api\PdcPlantillaController`
- `App\Controllers\Gestion\PdcController`

### Servicios
- `ModuleRequestContext`

### Tablas
- `general_dias_defaults_categoria`
- `general_dias_procesos_contratacion`
- `general_proyectos_procesos`
- `papelera_pdc`
- `pdc`
- `semanas_activas`
- `subcontratistas`

### Quién puede
| Capacidad | Roles que la tienen |
| --- | --- |
| `canManagePdC` | A, D, R, OT |
<!-- generado:fin -->
