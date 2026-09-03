---
capa: wiki
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [bi, arquitectura]
tags: [generado]
fuente: public/index.php
resumen: "Torre de Control BI: los reportes consolidados de LPS y PDC en un solo tablero, sin tocar los datos operativos — oculto de la navegación desde 2026-08-13, visible para Admin, Director de Obra (D desde 2026-08-20) y Residente de Obra (R desde 2026-08-24)"
---
# Torre de Control BI

**Qué resuelve.** Da una vista de arriba hacia abajo del proyecto: cumplimiento, avance, curva S y
desempeño de contratistas, todo leído desde las mismas tablas operativas de LPS y PDC pero sin
escribir en ellas. Es adonde manda `Control Tower - Informes` del sidebar. **Corregido
2026-08-14:** desde el 2026-08-13 (`44632801`, `9483cd5a`) está oculto de la navegación y de las
rutas mientras se desarrolla — lo ven y abren los roles Admin, Director de Obra (`D` ampliado el 2026-08-20) y Residente de Obra (`R` ampliado el 2026-08-24, `docs/superpowers/specs/2026-08-24-reparto-lienzos-por-rol-design.md`), gateado por la capacidad
`internal.bi.preview` (`src/Security/RbacCatalog.php:120`) a través de
`BiPreviewAccessPolicy::canOpen()`. Ver [[control-tower-oculto-mientras-se-desarrolla]].

**Dónde encaja.** En los dos flujos. Ver [[flujo-lps]] y [[flujo-pdc]].

## Navegación interna

`views/bi/_nav.php` es la barra propia de este módulo, con ocho destinos que no pasan por el
sidebar general: `Control Tower` (el resumen), `Programa General`, `Programación Intermedia`,
`Programación Semanal`, `Plan de Compras`, `Contratistas`, `Responsables` y `Curva S`. Cada uno es
su propia ruta bajo `/bi/*` (ver el inventario generado arriba), así que moverse entre reportes
recarga la página — no es una SPA como [[plan-de-compras]].

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| GET | `/api/bi/control-tower/metricas/{metricKey}` | `App\Controllers\Api\BiMetricController::ejecutar` |
| POST | `/api/bi/control-tower/restricciones/{id}/gestion` | `App\Controllers\Api\BiConstraintWriteController::gestion` |
| GET | `/api/bi/control-tower/restricciones/pareto` | `App\Controllers\Api\BiRestrictionParetoController::pareto` |
| GET | `/api/bi/control-tower/restricciones` | `App\Controllers\Api\BiConstraintListController::listar` |
| GET | `/api/bi/control-tower` | `App\Controllers\Api\BiControlTowerApiController::controlTower` |
| GET | `/api/bi/filter-options` | `App\Controllers\Api\BiControlTowerApiController::filterOptions` |
| GET | `/api/bi/lineage` | `App\Controllers\Api\BiControlTowerApiController::lineage` |
| GET | `/api/bi/projects` | `App\Controllers\Api\BiControlTowerApiController::projects` |
| GET | `/api/bi/report/cic` | `App\Controllers\Api\BiControlTowerApiController::cic` |
| GET | `/api/bi/report/cip` | `App\Controllers\Api\BiControlTowerApiController::cip` |
| GET | `/api/bi/report/curva-s` | `App\Controllers\Api\BiControlTowerApiController::curvaS` |
| GET | `/api/bi/report/intermedia` | `App\Controllers\Api\BiControlTowerApiController::intermedia` |
| GET | `/api/bi/report/pdc/detail` | `App\Controllers\Api\BiControlTowerApiController::pdcDetail` |
| GET | `/api/bi/report/pdc` | `App\Controllers\Api\BiControlTowerApiController::pdc` |
| GET | `/api/bi/report/programa-general/cnc-detail` | `App\Controllers\Api\BiControlTowerApiController::programaCncDetail` |
| GET | `/api/bi/report/programa-general/cnp-detail` | `App\Controllers\Api\BiControlTowerApiController::programaCnpDetail` |
| GET | `/api/bi/report/programa-general/compliance-detail` | `App\Controllers\Api\BiControlTowerApiController::programaComplianceDetail` |
| GET | `/api/bi/report/programa-general/delay-detail` | `App\Controllers\Api\BiControlTowerApiController::programaDelayDetail` |
| GET | `/api/bi/report/programa-general/progress-detail` | `App\Controllers\Api\BiControlTowerApiController::programaProgressDetail` |
| GET | `/api/bi/report/programa-general/radar-detail` | `App\Controllers\Api\BiControlTowerApiController::programaRadarDetail` |
| GET | `/api/bi/report/programa-general` | `App\Controllers\Api\BiControlTowerApiController::programaGeneral` |
| GET | `/api/bi/report/semanal` | `App\Controllers\Api\BiControlTowerApiController::semanal` |
| GET | `/api/bi/weeks` | `App\Controllers\Api\BiControlTowerApiController::weeks` |
| GET | `/bi/contratistas` | `App\Controllers\Bi\BiViewController::contratistas` |
| GET | `/bi/control-tower` | `App\Controllers\Bi\BiViewController::controlTower` |
| GET | `/bi/curva-s` | `App\Controllers\Bi\BiViewController::curvaS` |
| GET | `/bi/intermedia` | `App\Controllers\Bi\BiViewController::intermedia` |
| GET | `/bi/pdc` | `App\Controllers\Bi\BiViewController::pdc` |
| GET | `/bi/programa-general` | `App\Controllers\Bi\BiViewController::programaGeneral` |
| GET | `/bi/responsables` | `App\Controllers\Bi\BiViewController::responsables` |
| GET | `/bi/semanal` | `App\Controllers\Bi\BiViewController::semanal` |

### Controladores
- `App\Controllers\Api\BiConstraintListController`
- `App\Controllers\Api\BiConstraintWriteController`
- `App\Controllers\Api\BiControlTowerApiController`
- `App\Controllers\Api\BiMetricController`
- `App\Controllers\Api\BiRestrictionParetoController`
- `App\Controllers\Bi\BiViewController`

### Servicios
- `BiProjectScope`
- `ControlTowerService`
- `LineageService`
- `MetricDictionaryService`
- `MetricExecutor`
- `MetricResult`
- `MetricScope`
- `SeguimientoService`

### Tablas
- `bi_cic_contratistas`
- `bi_cip_responsables`
- `bi_pi_restricciones`
- `general_paquetes_contratacion`
- `general_pasos_contratacion`
- `general_proyectos_procesos`
- `general_usuarios`
- `pdc_insumo_paquete`
- `pdc_insumo_vinculos`
- `pdc_paquete_frente`
- `pdc_plan_paquete`
- `pdc_plan_paso`
- `pdc_presupuesto_versiones`
- `pdc_subpaquete`
- `pi_shared_constraint_links`
- `pi_shared_constraints`
- `programa_consolidado`
- `programacion_semanal`
- `project_members`
- `semanas_activas`

### Quién puede
_Sin capacidad propia: la ruta exige sesión y proyecto, no una capacidad específica._
<!-- generado:fin -->
