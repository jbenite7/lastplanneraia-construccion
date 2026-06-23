---
slug: refine-model-contracts
status: approved
intent: clear
pending-action: execute .omo/plans/refine-model-contracts.md
approach: 18 new families + 6 regex expansions + multi-select contract system (SI exclusive, MO/S/OC combinable) stored as comma-separated varchar + migration + UI redesign.
---

# Draft: refine-model-contracts

## Components (topology ledger)

| id | outcome | status | evidence |
|----|---------|--------|----------|
| C1 | 18 new families + rename SANITARIOS→APARATOS_SANITARIOS + 45 regex rules | active | database/patches/20260614_new_families.sql |
| C2 | 6 regex expansions on existing families | active | database/patches/20260615_regex_expansions.sql |
| C3 | Migration: "1"→"MO,S", "2"→"SI" across all project DBs | active | database/migrations/migrate_tipos_contrato.php |
| C4 | /listado-actividades/ tipoContrato read-only (badges) | active | views/listado-actividades/listadoActividades.view.php |
| C5 | /contratos/ modal: 4 checkboxes + OC section + dynamic show/hide | active | views/contratos/contratos.view.php |
| C6 | ContratosApiController: autoAssign with comma-separated modalities | active | src/Controllers/Api/ContratosApiController.php |

## Findings

- 49,826 activities across 13 PGs, 6,917 unique
- 84% current match coverage (5,875/6,917)
- 1,042 unmatched, ~700 are mappable to new families/expansions
- ~70 are administrative (entregables, actas, firmas) — correctly not mapped
- OBRA_NEGRA/OBRA_BLANCA are phases, not processes — not mapped

## Decisions

1. 18 new families: ASEO, EQUIPOS_COCINA, LAVAPLATOS, LAVADERO, ACCESORIOS_SANITARIOS, DEMOLICIONES, PASAMANOS, MUEBLES, EQUIPOS_ESPECIALES, APUNTALAMIENTO, SENALETICA, INFRAESTRUCTURA_DRENES, RESANES, CCTV_SEGURIDAD, SONIDO_VIDEO, SISTEMA_DATOS, AUTOMATIZACION_BMS + rename SANITARIOS→APARATOS_SANITARIOS
2. 6 regex expansions: ESTRUCTURA_CONCRETO (+CUBIERTA/ESCALERAS/RAMPAS), VIAS_PAVIMENTOS (+ASFALT), CIELOS_RASOS (+^CIELOS$), FACHADA (+ALUCOBOND/MUROS_EXTERIORES), RED_ELECTRICA (+SUICHES/CABLE_COBRE), CARPINTERIA_METALICA (+DIVISIONES_BANO)
3. Multi-select checkboxes: SI exclusive, MO/S/OC combinable
4. Comma-separated storage in varchar(10)
5. OC reuses S1/paqueteS1 (no ALTER TABLE)
6. OBRA_NEGRA/OBRA_BLANCA not mapped (fases)

## Approval gate
status: approved
User confirmed all criteria. Plan ready for execution.
