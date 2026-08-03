---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [pdc, arquitectura]
fuente: public/index.php
resumen: "Plan de Compras v2: del presupuesto al paquete de contratación, con fechas y seguimiento por hash"
---
# Plan de Compras v2

**Qué resuelve.** Convierte el presupuesto importado en un plan de compras con fechas: reconoce
insumos, los agrupa en paquetes de contratación y calcula cuándo hay que comprar cada uno para no
frenar la obra. Es una isla React que vive detrás de una sola ruta (`/plan-compras`); todo lo demás
se mueve por hash dentro del navegador, no por rutas HTTP nuevas. Antes de tocarlo, lee [[pdc]] y
[[pdc-v2|docs/pdc-v2.md]] — ahí está el modelo de dominio completo y las trampas ya medidas.

**Dónde encaja.** En el flujo del Plan de Compras. Ver [[flujo-pdc]].

**Nota del manifiesto.** SPA React en `pdc-app/`, bundle en `public/pdc-app/`. Sub-router por hash.

## Sub-router por hash

La SPA no cambia de ruta HTTP al navegar entre sus pantallas: cambia el hash de `/plan-compras`.
`#/ensamble/importar` es donde se carga el presupuesto; `#/ensamble/maestro`, el catálogo global de
insumos; `#/ensamble/paquetes`, dónde se arma cada paquete de contratación; `#/plan/fechas`, el
calendario resultante; `#/seguimiento`, el avance real de cada paquete comprado; y
`#/torre-control`, el resumen ejecutivo de compras. Si buscas dónde vive una pantalla del PDC en el
código, empieza por el hash, no por el router de `public/index.php`.

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| GET | `/plan-compras/api/contexto` | `App\Controllers\Api\PlanComprasApiController::contexto` |
| POST | `/plan-compras/api/maestro/crear-desde-pendientes` | `App\Controllers\Api\PlanComprasMaestroController::crearDesdePendientes` |
| POST | `/plan-compras/api/maestro/desactivar` | `App\Controllers\Api\PlanComprasMaestroController::desactivar` |
| POST | `/plan-compras/api/maestro/equipos/clasificar` | `App\Controllers\Api\PlanComprasMaestroController::clasificarEquipos` |
| GET | `/plan-compras/api/maestro/equipos` | `App\Controllers\Api\PlanComprasMaestroController::equipos` |
| POST | `/plan-compras/api/maestro/importar/confirmar` | `App\Controllers\Api\PlanComprasMaestroImportController::confirmar` |
| POST | `/plan-compras/api/maestro/importar/preview` | `App\Controllers\Api\PlanComprasMaestroImportController::preview` |
| POST | `/plan-compras/api/maestro/reactivar` | `App\Controllers\Api\PlanComprasMaestroController::reactivar` |
| GET | `/plan-compras/api/maestro/sugerencias` | `App\Controllers\Api\PlanComprasMaestroController::sugerencias` |
| POST | `/plan-compras/api/maestro/vinculos/confirmar` | `App\Controllers\Api\PlanComprasMaestroController::confirmar` |
| POST | `/plan-compras/api/maestro/vinculos/generar` | `App\Controllers\Api\PlanComprasMaestroController::generar` |
| GET | `/plan-compras/api/maestro/vinculos` | `App\Controllers\Api\PlanComprasMaestroController::vinculos` |
| GET | `/plan-compras/api/maestro` | `App\Controllers\Api\PlanComprasMaestroController::catalogo` |
| POST | `/plan-compras/api/maestro` | `App\Controllers\Api\PlanComprasMaestroController::crearManual` |
| POST | `/plan-compras/api/paquetes/asignar` | `App\Controllers\Api\PlanComprasPaquetesController::asignar` |
| POST | `/plan-compras/api/paquetes/auto-asignar` | `App\Controllers\Api\PlanComprasPaquetesController::autoAsignar` |
| GET | `/plan-compras/api/paquetes/candidatos` | `App\Controllers\Api\PlanComprasPaquetesController::candidatos` |
| POST | `/plan-compras/api/paquetes/desasignar` | `App\Controllers\Api\PlanComprasPaquetesController::desasignar` |
| GET | `/plan-compras/api/paquetes/insumo-actividades` | `App\Controllers\Api\PlanComprasPaquetesController::insumoActividades` |
| GET | `/plan-compras/api/paquetes/insumos` | `App\Controllers\Api\PlanComprasPaquetesController::insumos` |
| POST | `/plan-compras/api/paquetes/omitir` | `App\Controllers\Api\PlanComprasPaquetesController::omitir` |
| GET | `/plan-compras/api/paquetes/plan-auto` | `App\Controllers\Api\PlanComprasPaquetesController::planAuto` |
| GET | `/plan-compras/api/paquetes/resumen` | `App\Controllers\Api\PlanComprasPaquetesController::resumen` |
| GET | `/plan-compras/api/paquetes/sugerencias` | `App\Controllers\Api\PlanComprasPaquetesController::sugerencias` |
| GET | `/plan-compras/api/paquetes` | `App\Controllers\Api\PlanComprasPaquetesController::catalogo` |
| POST | `/plan-compras/api/paquetes` | `App\Controllers\Api\PlanComprasPaquetesController::crear` |
| POST | `/plan-compras/api/plan/amarrar` | `App\Controllers\Api\PlanComprasPlanController::amarrar` |
| GET | `/plan-compras/api/plan/anclas` | `App\Controllers\Api\PlanComprasPlanController::anclas` |
| POST | `/plan-compras/api/plan/calcular` | `App\Controllers\Api\PlanComprasPlanController::calcular` |
| GET | `/plan-compras/api/plan/correspondencias` | `App\Controllers\Api\PlanComprasPlanController::correspondencias` |
| POST | `/plan-compras/api/plan/correspondencias` | `App\Controllers\Api\PlanComprasPlanController::guardarCorrespondencia` |
| POST | `/plan-compras/api/plan/desamarrar` | `App\Controllers\Api\PlanComprasPlanController::desamarrar` |
| GET | `/plan-compras/api/plan/desfases` | `App\Controllers\Api\PlanComprasPlanController::desfases` |
| GET | `/plan-compras/api/plan/duraciones` | `App\Controllers\Api\PlanComprasPlanController::duraciones` |
| POST | `/plan-compras/api/plan/duraciones` | `App\Controllers\Api\PlanComprasPlanController::guardarDuracion` |
| GET | `/plan-compras/api/plan/frentes` | `App\Controllers\Api\PlanComprasPlanController::frentes` |
| GET | `/plan-compras/api/plan/pasos/copia-preview` | `App\Controllers\Api\PlanComprasPlanController::previewCopiaPasos` |
| POST | `/plan-compras/api/plan/pasos/copiar` | `App\Controllers\Api\PlanComprasPlanController::copiarPasos` |
| GET | `/plan-compras/api/plan/pasos/historial` | `App\Controllers\Api\PlanComprasPlanController::historialPasos` |
| GET | `/plan-compras/api/plan/pasos/origenes` | `App\Controllers\Api\PlanComprasPlanController::origenesPasos` |
| POST | `/plan-compras/api/plan/pasos/restablecer` | `App\Controllers\Api\PlanComprasPlanController::restablecerPasos` |
| GET | `/plan-compras/api/plan/pasos` | `App\Controllers\Api\PlanComprasPlanController::pasos` |
| POST | `/plan-compras/api/plan/pasos` | `App\Controllers\Api\PlanComprasPlanController::guardarPasos` |
| POST | `/plan-compras/api/plan/reprogramacion/aplicar` | `App\Controllers\Api\PlanComprasPlanController::aplicarReprogramacion` |
| GET | `/plan-compras/api/plan/reprogramacion/simular` | `App\Controllers\Api\PlanComprasPlanController::simularReprogramacion` |
| POST | `/plan-compras/api/plan/responsable` | `App\Controllers\Api\PlanComprasPlanController::responsable` |
| GET | `/plan-compras/api/plan/responsables` | `App\Controllers\Api\PlanComprasPlanController::responsables` |
| GET | `/plan-compras/api/plan/sugerencias` | `App\Controllers\Api\PlanComprasPlanController::sugerencias` |
| GET | `/plan-compras/api/plan` | `App\Controllers\Api\PlanComprasPlanController::plan` |
| POST | `/plan-compras/api/presupuesto/activar` | `App\Controllers\Api\PlanComprasImportController::activar` |
| GET | `/plan-compras/api/presupuesto/arbol` | `App\Controllers\Api\PlanComprasImportController::arbol` |
| GET | `/plan-compras/api/presupuesto/comparar` | `App\Controllers\Api\PlanComprasImportController::comparar` |
| POST | `/plan-compras/api/presupuesto/confirmar` | `App\Controllers\Api\PlanComprasImportController::confirmar` |
| GET | `/plan-compras/api/presupuesto/impacto-version` | `App\Controllers\Api\PlanComprasImportController::impactoVersion` |
| POST | `/plan-compras/api/presupuesto/preview` | `App\Controllers\Api\PlanComprasImportController::preview` |
| GET | `/plan-compras/api/presupuesto/versiones` | `App\Controllers\Api\PlanComprasImportController::versiones` |
| GET | `/plan-compras/api/seguimiento/flujo-caja.csv` | `App\Controllers\Api\PlanComprasSubpaquetesController::flujoCajaCsv` |
| GET | `/plan-compras/api/seguimiento/flujo-caja` | `App\Controllers\Api\PlanComprasSubpaquetesController::flujoCaja` |
| GET | `/plan-compras/api/seguimiento/paquete` | `App\Controllers\Api\PlanComprasSeguimientoController::paquete` |
| POST | `/plan-compras/api/seguimiento/paso` | `App\Controllers\Api\PlanComprasSeguimientoController::paso` |
| GET | `/plan-compras/api/seguimiento/vencimientos` | `App\Controllers\Api\PlanComprasSeguimientoController::vencimientos` |
| GET | `/plan-compras/api/seguimiento` | `App\Controllers\Api\PlanComprasSeguimientoController::resumen` |
| POST | `/plan-compras/api/subpaquetes/actualizar` | `App\Controllers\Api\PlanComprasSubpaquetesController::actualizar` |
| POST | `/plan-compras/api/subpaquetes/agregar` | `App\Controllers\Api\PlanComprasSubpaquetesController::agregar` |
| GET | `/plan-compras/api/subpaquetes/destinos` | `App\Controllers\Api\PlanComprasSubpaquetesController::destinos` |
| POST | `/plan-compras/api/subpaquetes/eliminar` | `App\Controllers\Api\PlanComprasSubpaquetesController::eliminar` |
| POST | `/plan-compras/api/subpaquetes/mover` | `App\Controllers\Api\PlanComprasSubpaquetesController::mover` |
| POST | `/plan-compras/api/subpaquetes/partir` | `App\Controllers\Api\PlanComprasSubpaquetesController::partir` |
| GET | `/plan-compras/api/subpaquetes` | `App\Controllers\Api\PlanComprasSubpaquetesController::listar` |
| GET | `/plan-compras` | `App\Controllers\Gestion\PlanComprasController::index` |

### Controladores
- `App\Controllers\Api\PlanComprasApiController`
- `App\Controllers\Api\PlanComprasImportController`
- `App\Controllers\Api\PlanComprasMaestroController`
- `App\Controllers\Api\PlanComprasMaestroImportController`
- `App\Controllers\Api\PlanComprasPaquetesController`
- `App\Controllers\Api\PlanComprasPlanController`
- `App\Controllers\Api\PlanComprasSeguimientoController`
- `App\Controllers\Api\PlanComprasSubpaquetesController`
- `App\Controllers\Gestion\PlanComprasController`

### Servicios
- `DuracionesCatalogoService`
- `FlujoCajaService`
- `MaestroInsumosService`
- `MaestroSincoImportService`
- `MaestroSincoParser`
- `PaquetesService`
- `PasosContratacionService`
- `PlanFechasService`
- `PresupuestoExcelParser`
- `PresupuestoImportService`
- `PresupuestoImportStore`
- `SeguimientoService`
- `SesionUsuario`
- `SubpaquetesService`

### Tablas
- `general_dias_procesos_contratacion`
- `general_maestro_insumos`
- `general_paquetes_contratacion`
- `general_pasos_contratacion`
- `general_proyectos_procesos`
- `general_rama_frente`
- `general_usuarios`
- `pdc_correcciones_frente`
- `pdc_correcciones_motor`
- `pdc_insumo_actividades`
- `pdc_insumo_paquete`
- `pdc_insumo_vinculos`
- `pdc_paquete_frente`
- `pdc_plan_paquete`
- `pdc_plan_paso`
- `pdc_presupuesto_apu_insumos`
- `pdc_presupuesto_items`
- `pdc_presupuesto_versiones`
- `pdc_proyecto_pasos`
- `pdc_proyecto_pasos_historial`
- `pdc_rama_frente`
- `pdc_subpaquete`
- `programa_consolidado`
- `project_members`
- `semanas_activas`

### Quién puede
| Capacidad | Roles que la tienen |
| --- | --- |
| `canManagePdC` | A, D, R, OT |
| `canManageContracts` | A, D, R, OT |
<!-- generado:fin -->
