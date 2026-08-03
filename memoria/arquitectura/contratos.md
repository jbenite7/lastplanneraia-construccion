---
tipo: modulo
estado: vigente
fecha: 2026-08-03
areas: [pdc, arquitectura]
fuente: public/index.php
resumen: "Contratos: vincula actividades con paquetes de contratación (S, MO, SI) usando el motor semi-auto"
---
# Contratos y definición semiautomática

**Qué resuelve.** Une cada actividad del proyecto con su paquete de contratación (Suministro, Mano
de Obra o Suministro e Instalación) para saber quién es responsable de comprarla o ejecutarla. Usa
el mismo motor semiautomático (`auto/preview`, `auto/apply`, `auto/undo`) que
[[listado-de-actividades]] y el PDC: antes de tocar la lógica de sugerencias conviene revisar cómo
se usa en los otros dos para no crear un flujo paralelo.

**Dónde encaja.** En el flujo del Plan de Compras. Ver [[pdc]].

**Nota del manifiesto.** Reparto de criterio: /api/pdc/auto/* se atribuye a Contratos por ser el contrato auto/preview·apply·undo·feedback·metrics que define contratos; el resto de /api/pdc/* queda en Listado de Actividades. Verificable leyendo src/Controllers/Api/PdcAutoGenerateController.php y src/Services/SemiAutoService.php.

## Inventario

Lo de abajo lo genera `scripts/wiki-arquitectura.mjs` desde el código. **No lo edites a mano:**
se sobrescribe en cada regeneración. Todo lo de fuera de los marcadores sí es tuyo.

<!-- generado:inicio -->
### Rutas
| Verbo | Ruta | Destino |
| --- | --- | --- |
| POST | `/api/pdc/auto/apply-from-contratos` | `App\Controllers\Api\PdcAutoGenerateController::applyFromContratos` |
| POST | `/api/pdc/auto/apply` | `App\Controllers\Api\SemiAutoController::applyPdc` |
| POST | `/api/pdc/auto/assistant/ack` | `App\Controllers\Api\SemiAutoController::assistantAckPdc` |
| POST | `/api/pdc/auto/assistant/feedback` | `App\Controllers\Api\SemiAutoController::assistantFeedbackPdc` |
| POST | `/api/pdc/auto/assistant/inbox` | `App\Controllers\Api\SemiAutoController::assistantInboxPdc` |
| POST | `/api/pdc/auto/feedback` | `App\Controllers\Api\SemiAutoController::feedbackPdc` |
| POST | `/api/pdc/auto/learning/approve` | `App\Controllers\Api\SemiAutoController::learningApprovePdc` |
| POST | `/api/pdc/auto/learning/candidates` | `App\Controllers\Api\SemiAutoController::learningCandidatesPdc` |
| POST | `/api/pdc/auto/learning/reject` | `App\Controllers\Api\SemiAutoController::learningRejectPdc` |
| POST | `/api/pdc/auto/metrics` | `App\Controllers\Api\SemiAutoController::metricsPdc` |
| POST | `/api/pdc/auto/preview` | `App\Controllers\Api\SemiAutoController::previewPdc` |
| POST | `/api/pdc/auto/status` | `App\Controllers\Api\SemiAutoController::statusPdc` |
| POST | `/api/pdc/auto/undo` | `App\Controllers\Api\SemiAutoController::undoPdc` |

### Controladores
- `App\Controllers\Api\PdcAutoGenerateController`
- `App\Controllers\Api\SemiAutoController`

### Servicios
- `ModuleRequestContext`
- `SemiAutoAssistantService`
- `SemiAutoService`

### Tablas
- `actividad_programa_fuentes`
- `actividades`
- `general_dias_procesos_contratacion`
- `general_pdc_familias`
- `general_pdc_family_contract_option_items`
- `general_pdc_family_contract_options`
- `general_proyectos_procesos`
- `pdc`
- `programa_consolidado`
- `semanas_activas`
- `semi_auto_assistant_feedback`
- `semi_auto_decisions`
- `semi_auto_feedback`
- `semi_auto_learning_candidates`
- `semi_auto_learning_rules`
- `semi_auto_proactive_queue`
- `semi_auto_project_config`
- `semi_auto_runs`
- `semi_auto_suggestions`

### Quién puede
| Capacidad | Roles que la tienen |
| --- | --- |
| `canManageContracts` | A, D, R, OT |
| `canAutoDefineContracts` | A, D, R, OT |
<!-- generado:fin -->
