---
slug: refine-model-contracts
status: awaiting-approval
intent: clear
pending-action: write .omo/plans/refine-model-contracts.md
approach: Two-part plan: (1) Add 6 new construction process families with regex rules and contract options; (2) Redesign contract type as multi-select modalities (SI exclusive, MO+S+OC combinable) stored as comma-separated varchar, with /listado-actividades/ read-only and /contratos/ as the editing point.
---

# Draft: refine-model-contracts

## Components (topology ledger)

| id | outcome | status | evidence |
|----|---------|--------|----------|
| C1 | 6 new families + rename SANITARIOS→APARATOS_SANITARIOS + regex rules in SQL patch | active | database/patches/20260612_pdc_familias_maestro.sql |
| C2 | Migrate existing tipoContrato values: "1"→"MO,S", "2"→"SI" across all project DBs | active | {prefix}_actividades.tipoContrato varchar(10) |
| C3 | /listado-actividades/ tipoContrato column read-only (badges, no select in create or edit) | active | views/listado-actividades/listadoActividades.view.php:128-135, 1390-1391 |
| C4 | /contratos/ modal: 4 checkboxes (SI exclusive, MO+S+OC combinable), dynamic section show/hide, new OC section | active | views/contratos/contratos.view.php:102, 105-130, 503-622 |
| C5 | ContratosApiController: autoAssign pre-selects checkboxes based on family suggested tipo_contrato | active | src/Controllers/Api/ContratosApiController.php |
| C6 | general_pdc_family_contract_options: expand tipo_contrato INT values (1=MO,S; 2=SI; 3=S; 4=MO; 5=OC) | active | database/patches/20260612_pdc_familias_maestro.sql:49-68 |

## Open assumptions (announced defaults)

| assumption | adopted default | rationale | reversible? |
|------------|----------------|-----------|-------------|
| New families category | ACABADOS for all 6 | All are finishing/installation processes | Yes |
| ASEO suggested modalidad | tipo_contrato=1 (MO,S) → pre-checks MO+S | User said "contratable, a todo costo" | Yes |
| EQUIPOS_COCINA suggested | tipo_contrato=2 (SI) | Campana/cubierta = supply + install | Yes |
| LAVAPLATOS suggested | tipo_contrato=2 (SI) | Equipment install | Yes |
| LAVADERO suggested | tipo_contrato=2 (SI) | Like mesones, supply + install | Yes |
| APARATOS_SANITARIOS suggested | tipo_contrato=2 (SI) | Same as existing SANITARIOS | Yes |
| ACCESORIOS_SANITARIOS suggested | tipo_contrato=2 (SI) | Accessories are supply + install | Yes |
| OC section UI | Proveedor text field (→S1) + OC# text field (→paqueteS1), no 5-slot grid | OC is negotiation-based, not paquete-based | Yes |
| tipoContrato varchar(10) capacity | Enough for "MO,S,OC" (8 chars) | Max combination = 8 chars, fits in 10 | Yes — could ALTER to varchar(20) if needed |
| general_pdc_family_contract_options.tipo_contrato | Keep INT with expanded values 1-5, application layer converts to checkbox pre-selection | Avoid changing schema of general table | Yes |
| Migration runs on all project DBs | Iterate over general_proyectos_procesos to find all db_prefixes | Need to migrate every project's actividades table | Yes |

## Findings (cited - path:lines)

- `database/patches/20260612_pdc_familias_maestro.sql:154-236` — 75 existing families
- `database/patches/20260612_pdc_familias_maestro.sql:266-417` — ~120 regex rules
- `database/patches/20260612_pdc_familias_maestro.sql:423-516` — contract options (tipo_contrato 1 or 2)
- `database/patches/20260612_pdc_familias_maestro.sql:49-68` — contract_options schema (tipo_contrato INT)
- `src/Support/ActivityMatcher.php:18-30` — loadRules() query
- `views/listado-actividades/listadoActividades.view.php:128-135` — tipoContrato select in create modal
- `views/listado-actividades/listadoActividades.view.php:1138-1149` — DataTable render for tipoContrato
- `views/listado-actividades/listadoActividades.view.php:1390-1391` — inline edit select
- `views/contratos/contratos.view.php:102` — hidden input tipoContrato
- `views/contratos/contratos.view.php:105-130` — 3 sections (S, MO, SI)
- `views/contratos/contratos.view.php:503-622` — JS show/hide: tipoContrato==1 shows S+MO, ==2 shows SI
- `src/Controllers/Api/ContratosApiController.php` — autoAssign + selectBestContratoOption

## Decisions (with rationale)

1. **Multi-select checkboxes instead of dropdown** — 4 checkboxes: SI (exclusive), MO, S, OC (combinable). SI blocks all others; MO/S/OC freely combine. This replaces the old 2-value dropdown.

2. **Comma-separated storage in varchar** — tipoContrato varchar(10) stores "SI", "MO", "S", "MO,S", "OC", "MO,S,OC" etc. No ALTER TABLE needed. Migrate existing: "1"→"MO,S", "2"→"SI".

3. **Migration across all project DBs** — Query general_proyectos_procesos for all db_prefixes, run UPDATE on each {prefix}_actividades table.

4. **general_pdc_family_contract_options keeps INT** — Expand values: 1=MO,S; 2=SI; 3=S; 4=MO; 5=OC. Application layer converts INT → checkbox pre-selection. No schema change to general table.

5. **6 new families in ACABADOS** — ASEO (orden 62), EQUIPOS_COCINA (63), LAVAPLATOS (64), LAVADERO (65), ACCESORIOS_SANITARIOS (66). Rename SANITARIOS→APARATOS_SANITARIOS (UPDATE, preserve id/FK).

6. **Read-only in /listado-actividades/** — Column shows badges (one per modality). No select in create modal. No select in inline edit. Text display only when editing.

7. **OC section reuses S1/paqueteS1** — S1 = proveedor name, paqueteS1 = OC reference number. No 5-slot grid, just 2 fields.

## Scope IN

- New SQL patch: 6 families, ~15 regex rules, contract options
- Rename SANITARIOS → APARATOS_SANITARIOS (UPDATE)
- Migration script: "1"→"MO,S", "2"→"SI" for all project DBs
- /listado-actividades/: read-only tipoContrato (badges)
- /contratos/: 4 checkboxes, OC section, dynamic show/hide for all combinations
- ContratosApiController: autoAssign converts INT suggestion → checkbox pre-selection
- Deploy to Docker + Playwright QA

## Scope OUT (Must NOT have)

- Do NOT ALTER TABLE {prefix}_actividades (no schema migration)
- Do NOT add electrodomésticos (nevera, horno) to EQUIPOS_COCINA
- Do NOT add INSUMOS category or families
- Do NOT change ActivityMatcher 3-pass algorithm
- Do NOT modify PdcAutoGenerateController or PDC module
- Do NOT DELETE SANITARIOS row (UPDATE to rename)
- Do NOT change general_pdc_family_contract_options schema (keep INT, expand values)

## Open questions

None — all criteria confirmed by user.

## Approval gate
status: awaiting-approval
