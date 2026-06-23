# refine-model-contracts - Work Plan

## TL;DR (For humans)

**What you'll get:** Seis procesos constructivos nuevos (Aseo, Equipos de Cocina, Lavaplatos, Lavadero, Accesorios Sanitarios, y renombrado de Sanitarios→Aparatos Sanitarios) quedarán cubiertos por el modelo de matching. Adicionalmente, el tipo de contrato se gestionará desde /contratos/ con un sistema de checkboxes multi-selección: SI (exclusivo), MO, S y OC (combinables entre sí). En /listado-actividades/ la columna será read-only con badges. Los valores existentes migran: "1"→"MO,S", "2"→"SI".

**Why this approach:** El multi-select de modalidades refleja la realidad operativa: un proceso constructivo puede requerir MO + S + OC simultáneamente, o solo SI, o solo OC. Guardar como comma-separated en varchar evita alterar el schema. Las 6 familias nuevas siguen el patrón existente de regex + opciones de contrato.

**What it will NOT do:** No modificará el schema de tablas. No añadirá electrodomésticos. No cambiará el algoritmo de matching. No modificará el módulo PDC.

**Effort:** Medium
**Risk:** Medium — migración de datos existentes + rediseño de UI de contratos
**Decisions to sanity-check:** (1) Migración "1"→"MO,S" en todas las DBs de proyecto; (2) OC reutiliza S1/paqueteS1; (3) general_pdc_family_contract_options mantiene INT con valores 1-5

Your next move: approve to proceed, or request a high-accuracy review. Full execution detail follows below.

---

> TL;DR (machine): Medium effort, Medium risk — 6 new families + regex + contract options; multi-select checkbox system (SI exclusive, MO/S/OC combinable) stored as comma-separated varchar; migration 1→MO,S 2→SI; read-only in listado; checkboxes + OC section in contratos.

## Scope
### Must have
- SQL patch: 6 new families, ~15 regex rules, contract options for each
- Rename SANITARIOS → APARATOS_SANITARIOS (UPDATE preserving id)
- Migration script: UPDATE all project DBs {prefix}_actividades SET tipoContrato='MO,S' WHERE tipoContrato='1'; SET tipoContrato='SI' WHERE tipoContrato='2'
- /listado-actividades/: tipoContrato column read-only (badges, no select in create/edit)
- /contratos/: 4 checkboxes (SI exclusive, MO/S/OC combinable), dynamic section show/hide, OC section with Proveedor + OC# fields
- ContratosApiController: autoAssign converts INT suggestion (1-5) → checkbox pre-selection
- Deploy to Docker + Playwright QA

### Must NOT have (guardrails, anti-slop, scope boundaries)
- Do NOT ALTER TABLE {prefix}_actividades (no schema migration)
- Do NOT ALTER TABLE general_pdc_family_contract_options (keep tipo_contrato INT, expand values 1-5)
- Do NOT break existing activities (migration handles all existing values)
- Do NOT add electrodomésticos (nevera, horno) to EQUIPOS_COCINA
- Do NOT add INSUMOS category or families
- Do NOT change ActivityMatcher 3-pass algorithm
- Do NOT modify PdcAutoGenerateController or PDC module
- Do NOT DELETE SANITARIOS row (UPDATE to rename, preserving all FK references)
- Do NOT allow SI + MO/S/OC combination (SI is exclusive in UI)

## Verification strategy
> Zero human intervention - all verification is agent-executed.
- Test decision: tests-after + manual QA via Playwright
- Evidence: .omo/evidence/task-<N>-refine-model-contracts.<ext>

## Execution strategy
### Parallel execution waves

### Dependency matrix
| Todo | Depends on | Blocks | Can parallelize with |
| --- | --- | --- | --- |
| 1 (SQL: families + rules) | — | 2, 3 | 4, 5, 6, 7 |
| 2 (SQL: contract options + items) | 1 | 3 | 4, 5, 6, 7 |
| 3 (SQL: migration + deploy to Docker DB) | 1, 2 | 9 | 4, 5, 6, 7 |
| 4 (ContratosApiController: autoAssign multi-select) | — | 8 | 1, 2, 3, 5, 6, 7 |
| 5 (Contratos view: checkboxes + OC section) | — | 8 | 1, 2, 3, 4, 6, 7 |
| 6 (Listado view: remove select from create modal) | — | 9 | 1, 2, 3, 4, 5, 7 |
| 7 (Listado view: read-only badges + inline edit) | — | 9 | 1, 2, 3, 4, 5, 6 |
| 8 (Contratos view JS: checkbox logic + show/hide) | 5 | 9 | 6, 7 |
| 9 (Deploy PHP + Playwright QA) | 3, 4, 5, 6, 7, 8 | — | — |

## Todos
> Implementation + Test = ONE todo. Never separate.
<!-- APPEND TASK BATCHES BELOW THIS LINE WITH edit/apply_patch - never rewrite the headers above. -->
- [ ] 1. SQL patch: 6 new families + rename SANITARIOS + regex rules
  What to do / Must NOT do: Create `database/patches/20260614_new_families.sql`. INSERT 6 new families into general_pdc_familias (categoria='ACABADOS'):
    - ASEO, orden=62, nombre='Aseo y Entrega'
    - EQUIPOS_COCINA, orden=63, nombre='Equipos de Cocina'
    - LAVAPLATOS, orden=64, nombre='Lavaplatos'
    - LAVADERO, orden=65, nombre='Lavadero Zona de Ropas'
    - ACCESORIOS_SANITARIOS, orden=66, nombre='Accesorios Sanitarios'
    UPDATE existing SANITARIOS row: `UPDATE general_pdc_familias SET codigo='APARATOS_SANITARIOS', nombre='Aparatos Sanitarios' WHERE codigo='SANITARIOS'` (preserves id and all FK references).
    INSERT ~15 new regex rules into general_pdc_activity_rules:
    - ASEO: `/ASEO|LIMPIEZA.*FINAL|ENTREGA.*APARTAMENTO|ENTREGA.*APTOS|ASEO.*GENERAL|LIMPIEZA.*OBRA/u` confianza=85 prioridad=90
    - EQUIPOS_COCINA: `/CAMPANA.*EXTRACTORA|CAMPANA.*COCINA|CUBIERTA.*COCINA|ESTUFA|EQUIPO.*COCINA/u` confianza=90 prioridad=110
    - LAVAPLATOS: `/LAVAPLATOS|LAVA.*PLATOS|LAVAVAJILLAS/u` confianza=92 prioridad=115
    - LAVADERO: `/LAVADERO|LAVANDERO|LAVADERO.*GRANITO|LAVADERO.*MARMOL|LAVADERO.*SINTETICO|ZONA.*ROPA.*LAVADERO|CUARTO.*LAVADO/u` confianza=90 prioridad=110
    - ACCESORIOS_SANITARIOS: `/ACCESORIO.*BANO|ACCESORIO.*COCINA|ACCESORIO.*ROPA|TOALLERO|JABONERA|PAPELERA|BARRA.*AGARRE|PORTA.*PAPEL|PORTA.*TOALLA|GANCHERA|COLGADOR|ACCESORIO.*SANITARIO/u` confianza=88 prioridad=100
    - APARATOS_SANITARIOS (complementaria): `/APARATO.*SANITARIO|APARATOS.*SANITARIOS|SANITARIO|LAVAMANOS|INODORO|GRIFERIA|DUCHA/u` confianza=92 prioridad=110
  Must NOT DELETE SANITARIOS row. Must NOT add electrodomésticos. Must NOT create INSUMOS families.
  Parallelization: Wave 1 | Blocked by: — | Blocks: 2, 3
  References: database/patches/20260612_pdc_familias_maestro.sql:13-22 (familias schema), :24-37 (rules schema), :154-236 (existing families), :266-417 (existing rules), :193 (SANITARIOS family row)
  Acceptance criteria: SQL executes without error. `SELECT COUNT(*) FROM general_pdc_familias` returns 81. `SELECT codigo FROM general_pdc_familias WHERE codigo='APARATOS_SANITARIOS'` returns 1. `SELECT codigo FROM general_pdc_familias WHERE codigo='SANITARIOS'` returns 0. Each new family has ≥1 regex rule.
  QA scenarios: `docker compose exec db mysql -uapp -psecret last_planner -e "SELECT codigo, nombre FROM general_pdc_familias WHERE codigo IN ('ASEO','EQUIPOS_COCINA','LAVAPLATOS','LAVADERO','ACCESORIOS_SANITARIOS','APARATOS_SANITARIOS')"` → 6 rows. Evidence: .omo/evidence/task-1-refine-model-contracts.txt
  Commit: Y | feat(families): add 6 new construction process families with regex rules

- [ ] 2. SQL patch: contract options + option items for 6 new families
  What to do / Must NOT do: In same `database/patches/20260614_new_families.sql` (append), INSERT into general_pdc_family_contract_options:
    - ASEO: tipo_contrato=1 (MO,S), tipo_paquete='Mano de Obra y Suministro por separado', dias_elaboracion=8, dias_entrega=7, dias_cuadros=5, dias_legalizacion=10
    - EQUIPOS_COCINA: tipo_contrato=2 (SI), tipo_paquete='Suministro e Instalación', dias_elaboracion=8, dias_entrega=15, dias_cuadros=10, dias_legalizacion=20
    - LAVAPLATOS: tipo_contrato=2 (SI), tipo_paquete='Suministro e Instalación', dias_elaboracion=8, dias_entrega=15, dias_cuadros=10, dias_legalizacion=20
    - LAVADERO: tipo_contrato=2 (SI), tipo_paquete='Suministro e Instalación', dias_elaboracion=8, dias_entrega=7, dias_cuadros=5, dias_legalizacion=10
    - ACCESORIOS_SANITARIOS: tipo_contrato=2 (SI), tipo_paquete='Suministro e Instalación', dias_elaboracion=8, dias_entrega=7, dias_cuadros=5, dias_legalizacion=10
    - APARATOS_SANITARIOS: no new option needed (existing SANITARIOS option preserved by rename — FK references id which is unchanged)
  INSERT option items with 1-2 paquetes per family (following existing pattern from lines 522-640).
  Must NOT remove existing SANITARIOS/APARATOS_SANITARIOS options. Must NOT add tipo_contrato=3,4,5 options yet (those are for families that need them, can be added later).
  Parallelization: Wave 1 | Blocked by: 1 | Blocks: 3
  References: database/patches/20260612_pdc_familias_maestro.sql:423-516 (contract options pattern), :522-640 (option items pattern)
  Acceptance criteria: Each new family has ≥1 contract option. `SELECT f.codigo, o.tipo_contrato, o.tipo_paquete FROM general_pdc_family_contract_options o JOIN general_pdc_familias f ON f.id=o.familia_id WHERE f.codigo IN ('ASEO','EQUIPOS_COCINA','LAVAPLATOS','LAVADERO','ACCESORIOS_SANITARIOS')` returns 5+ rows.
  QA scenarios: Verify APARATOS_SANITARIOS still has its existing option (tipo_contrato=2, tipo_paquete='Suministro e Instalación'). Evidence: .omo/evidence/task-2-refine-model-contracts.txt
  Commit: N (same commit as task 1)

- [ ] 3. SQL migration: migrate tipoContrato values + deploy SQL to Docker
  What to do / Must NOT do: In same `database/patches/20260614_new_families.sql` (append migration section):
    1. Find all project DB prefixes: `SELECT Base_de_Datos FROM general_proyectos_procesos WHERE Base_de_Datos IS NOT NULL AND Base_de_Datos != ''`
    2. For each db_prefix, run: `UPDATE {db_prefix}_actividades SET tipoContrato='MO,S' WHERE tipoContrato='1'` and `UPDATE {db_prefix}_actividades SET tipoContrato='SI' WHERE tipoContrato='2'`
    Since MySQL doesn't support dynamic SQL in a simple script, use a prepared statement or document that the migration must be run per-project. Alternatively, write a small PHP migration script that iterates over projects.
    PRACTICAL APPROACH: Create `database/migrations/migrate_tipos_contrato.php` that:
    - Connects to DB
    - Queries general_proyectos_procesos for all db_prefixes
    - For each, runs the two UPDATE statements
    Then deploy: `docker compose cp database/patches/20260614_new_families.sql db:/tmp/ && docker compose exec db mysql -uapp -psecret last_planner -e "SOURCE /tmp/20260614_new_families.sql"` and run the PHP migration script.
  Must NOT run on production. Must NOT delete any rows. Must NOT fail silently if a project DB doesn't exist.
  Parallelization: Wave 2 | Blocked by: 1, 2 | Blocks: 9
  References: general_proyectos_procesos table (Base_de_Datos column), {prefix}_actividades.tipoContrato varchar(10)
  Acceptance criteria: All existing tipoContrato='1' become 'MO,S'. All tipoContrato='2' become 'SI'. No '1' or '2' values remain in any project DB. `SELECT DISTINCT tipoContrato FROM da_porto_actividades` returns no '1' or '2' values.
  QA scenarios: `docker compose exec db mysql -uapp -psecret last_planner -e "SELECT DISTINCT tipoContrato FROM da_porto_actividades"` → should show 'MO,S', 'SI', '' or NULL, but NOT '1' or '2'. Evidence: .omo/evidence/task-3-refine-model-contracts.txt
  Commit: Y | feat(migration): migrate tipoContrato from numeric to comma-separated modality codes

- [ ] 4. ContratosApiController: autoAssign with multi-select modalities
  What to do / Must NOT do: In `src/Controllers/Api/ContratosApiController.php`:
    1. Update autoAssign: when it finds a family contract option with tipo_contrato INT, convert to comma-separated:
       - 1 → 'MO,S' (pre-select MO and S checkboxes)
       - 2 → 'SI'
       - 3 → 'S'
       - 4 → 'MO'
       - 5 → 'OC'
    2. Store the comma-separated value in tipoContrato field (not the INT).
    3. Update selectBestContratoOption to handle all 5 INT values.
    4. Update assignContratoToActivity: based on comma-separated value, assign paquetes:
       - Contains 'SI' → fill SI1-SI5
       - Contains 'MO' → fill MO1-MO5
       - Contains 'S' → fill S1-S5
       - Contains 'OC' → set S1=supplier, paqueteS1=OC#
    5. Update the response labels: send both the comma-separated tipoContrato AND the human-readable label for the auto-assign modal table.
  Must NOT break existing autoAssign for activities that already have 'MO,S' or 'SI'. Must NOT change ActivityMatcher.
  Parallelization: Wave 1 | Blocked by: — | Blocks: 9
  References: src/Controllers/Api/ContratosApiController.php (autoAssign, selectBestContratoOption, assignContratoToActivity)
  Acceptance criteria: `php -l src/Controllers/Api/ContratosApiController.php` passes. autoAssign stores comma-separated values. Response includes tipoContratoLabel for all combinations.
  QA scenarios: Call POST /api/contratos/auto-assign — verify response shows tipoContrato as 'MO,S' or 'SI' (not 1 or 2). Evidence: .omo/evidence/task-4-refine-model-contracts.txt
  Commit: Y | feat(contratos): autoAssign stores comma-separated modality codes

- [ ] 5. Contratos view: replace hidden input with 4 checkboxes + OC section
  What to do / Must NOT do: In `views/contratos/contratos.view.php`:
    1. Replace `<input type="hidden" id="tipoContrato" name="tipoContrato" value="">` (line 102) with a visible checkbox group:
       ```html
       <div class="form-group ct-modalidad-group">
         <label>Modalidad de Contratación</label>
         <div class="ct-checkbox-group">
           <label><input type="checkbox" id="modalidadSI" name="modalidades[]" value="SI"> Suministro e Instalación</label>
           <label><input type="checkbox" id="modalidadMO" name="modalidades[]" value="MO"> Mano de Obra</label>
           <label><input type="checkbox" id="modalidadS" name="modalidades[]" value="S"> Suministro</label>
           <label><input type="checkbox" id="modalidadOC" name="modalidades[]" value="OC"> Orden de Compra</label>
         </div>
         <input type="hidden" id="tipoContrato" name="tipoContrato" value="">
       </div>
       ```
    2. Add new section after SI section (after line ~130):
       ```php
       [
           'id' => 'parametro_EditarContratosOC',
           'title' => 'Orden de Compra',
           'type' => 'oc', // special type — 2 fields, not 5 slots
       ]
       ```
       Render the OC section with: "Proveedor" text input (id="ocProveedor", maps to S1) and "Orden de Compra #" text input (id="ocNumero", maps to paqueteS1). No 5-slot grid.
    3. Keep the hidden tipoContrato input — it's synced by JS from checkbox state.
  Must NOT remove existing S, MO, SI sections. Must NOT change DataTable column definitions.
  Parallelization: Wave 1 | Blocked by: — | Blocks: 8
  References: views/contratos/contratos.view.php:102 (hidden input), :105-130 (section definitions), :133-144 (section render loop)
  Acceptance criteria: `php -l views/contratos/contratos.view.php` passes. 4 checkboxes visible in modal. OC section with 2 fields exists. Hidden tipoContrato input still present.
  QA scenarios: Playwright — open /contratos/, click edit — verify 4 checkboxes visible, OC section exists. Evidence: .omo/evidence/task-5-refine-model-contracts.png
  Commit: Y | feat(contratos): add multi-select modality checkboxes and OC section

- [ ] 6. Listado view: remove tipoContrato select from create modal
  What to do / Must NOT do: In `views/listado-actividades/listadoActividades.view.php`:
    1. Remove the tipoContrato field group (lines 128-135) from "Nueva Actividad" modal.
    2. Activity is created without tipoContrato (NULL/empty).
  Must NOT remove the column from DataTable. Must NOT change the save flow.
  Parallelization: Wave 1 | Blocked by: — | Blocks: 9
  References: views/listado-actividades/listadoActividades.view.php:128-135
  Acceptance criteria: `php -l views/listado-actividades/listadoActividades.view.php` passes. Create modal has no tipoContrato dropdown.
  QA scenarios: Playwright — open /listado-actividades/, click "Nueva Actividad" — verify no tipoContrato dropdown. Evidence: .omo/evidence/task-6-refine-model-contracts.png
  Commit: Y | refactor(listado): remove editable tipoContrato from create modal

- [ ] 7. Listado view: read-only tipoContrato column with badges + inline edit as text
  What to do / Must NOT do: In `views/listado-actividades/listadoActividades.view.php`:
    1. Update DataTable render (lines 1138-1149) to parse comma-separated and render multiple badges:
       ```js
       render: function(data, type, full, meta) {
           if (!data || data === '') return '<span class="text-muted">Sin asignar</span>';
           var modalidades = data.split(',');
           var badges = {
               'SI': '<span class="badge badge-primary">SI</span>',
               'MO': '<span class="badge badge-info">MO</span>',
               'S':  '<span class="badge badge-secondary">S</span>',
               'OC': '<span class="badge badge-dark">OC</span>'
           };
           return modalidades.map(function(m) { return badges[m] || m; }).join(' ');
       }
       ```
    2. Replace inline edit select (line 1390) with plain text: `$row.find('td:eq(6)').html(escaparHtml(data.tipoContrato || 'Sin asignar'));` — no dropdown when editing inline.
    3. Update createdCell header array (line 1131) if column index shifts.
  Must NOT remove the column. Must NOT hide the column.
  Parallelization: Wave 1 | Blocked by: — | Blocks: 9
  References: views/listado-actividades/listadoActividades.view.php:1131, :1138-1149, :1390-1391
  Acceptance criteria: `php -l` passes. DataTable renders badges for comma-separated values. Inline edit shows text, not select.
  QA scenarios: Playwright — verify column shows colored badges for 'MO,S' (two badges), 'SI' (one badge), empty (Sin asignar). Evidence: .omo/evidence/task-7-refine-model-contracts.png
  Commit: Y | refactor(listado): make tipoContrato read-only with multi-badge display

- [ ] 8. Contratos view JS: checkbox logic + section show/hide + sync hidden field
  What to do / Must NOT do: In `views/contratos/contratos.view.php`:
    1. Add checkbox event handlers:
       - `#modalidadSI` change: if checked → uncheck + disable #modalidadMO, #modalidadS, #modalidadOC. If unchecked → enable all.
       - `#modalidadMO, #modalidadS, #modalidadOC` change: if any checked → uncheck + disable #modalidadSI. If all unchecked → enable #modalidadSI.
    2. Sync hidden tipoContrato from checkboxes: on any checkbox change, build comma-separated from checked values in order SI,MO,S,OC and set `$('#tipoContrato').val(result)`.
    3. Replace existing show/hide logic (lines 522-622) with new logic based on checkbox state:
       ```js
       function updateSections() {
           var tc = $('#tipoContrato').val();
           var hasSI = tc.indexOf('SI') >= 0;
           var hasMO = tc.indexOf('MO') >= 0;
           var hasS  = tc.indexOf('S') >= 0 && tc.indexOf('SI') < 0; // avoid matching SI's S
           var hasOC = tc.indexOf('OC') >= 0;
           // Show/hide sections
           $('#parametro_EditarContratosSI').toggle(hasSI);
           $('#parametro_EditarContratosMO').toggle(hasMO);
           $('#parametro_EditarContratosS').toggle(hasS);
           $('#parametro_EditarContratosOC').toggle(hasOC);
       }
       ```
       **IMPORTANT**: 'S' check must avoid false-positive on 'SI'. Use `tc.split(',').indexOf('S') >= 0` instead of `tc.indexOf('S')`.
    4. On modal open (line 503-504): parse existing tipoContrato comma-separated → check corresponding checkboxes → call updateSections().
    5. On save: the hidden tipoContrato is already synced by checkbox handlers, so the existing save flow works unchanged.
    6. Update the auto-assign modal table (lines 837-843) to show comma-separated labels instead of INT-based labels.
  Must NOT break existing save flow. Must NOT allow SI + MO/S/OC simultaneously.
  Parallelization: Wave 2 | Blocked by: 5 | Blocks: 9
  References: views/contratos/contratos.view.php:503-622 (existing JS show/hide), :837-843 (auto-assign table)
  Acceptance criteria: `php -l` passes. Checking SI disables others. Checking MO does not disable S or OC. Checking any of MO/S/OC disables SI. Hidden tipoContrato always matches checkbox state. Sections show/hide correctly for all combinations.
  QA scenarios: Playwright — open edit modal, check SI → verify MO/S/OC disabled, only SI section visible. Uncheck SI, check MO+S → verify both sections visible, SI disabled. Check OC → verify OC section visible. Evidence: .omo/evidence/task-8-refine-model-contracts.png
  Commit: Y | feat(contratos): implement multi-select checkbox logic with dynamic sections

- [ ] 9. Deploy all changes to Docker + full Playwright QA
  What to do / Must NOT do: Copy all modified files to Docker:
    ```
    docker compose cp views/contratos/contratos.view.php app:/var/www/html/views/contratos/contratos.view.php
    docker compose cp views/listado-actividades/listadoActividades.view.php app:/var/www/html/views/listado-actividades/listadoActividades.view.php
    docker compose cp src/Controllers/Api/ContratosApiController.php app:/var/www/html/src/Controllers/Api/ContratosApiController.php
    docker compose cp database/migrations/migrate_tipos_contrato.php app:/var/www/html/database/migrations/migrate_tipos_contrato.php
    ```
    Run PHP migration script: `docker compose exec app php database/migrations/migrate_tipos_contrato.php`
    Full Playwright QA:
    1. Login as test.A/aia2026
    2. /listado-actividades/ — verify badges (MO+S = 2 badges, SI = 1 badge, empty = "Sin asignar"). Create modal has no tipoContrato. Inline edit shows text.
    3. /contratos/ — verify 4 checkboxes in edit modal. Check SI → others disabled, SI section visible. Uncheck SI, check MO+S → both sections visible. Check OC → OC section visible.
    4. Auto-generate on Da Porto — verify new families (ASEO, EQUIPOS_COCINA, etc.) match if PG has those activities.
    5. Auto-assign from /contratos/ — verify it stores comma-separated values.
  Must NOT deploy to production. Must NOT skip any QA step.
  Parallelization: Wave 3 | Blocked by: 3, 4, 5, 6, 7, 8 | Blocks: —
  References: docker-compose.yml, .env.example
  Acceptance criteria: All Playwright QA steps pass. No PHP errors. No SQL errors. New families match. Multi-select works. Migration complete (no '1' or '2' values remain).
  QA scenarios: Full Playwright flow. Evidence: .omo/evidence/task-9-refine-model-contracts.png, .omo/evidence/task-9-refine-model-contracts.log
  Commit: N (deployment step)

## Final verification wave
> Runs in parallel after ALL todos. ALL must APPROVE.
- [ ] F1. Plan compliance audit — every Must-have implemented, every Must-NOT respected. 6 families exist. Migration complete. Multi-select works. Read-only in listado.
- [ ] F2. Code quality review — no SQL injection, no breaking API changes, PHP syntax clean, JS checkbox logic covers all combinations including edge cases (empty, all unchecked).
- [ ] F3. Real manual QA — Playwright: login → listado (badges, no edit) → contratos (4 checkboxes, SI exclusive, sections) → auto-generate (new families) → auto-assign (comma-separated).
- [ ] F4. Scope fidelity — no INSUMOS, no ALTER TABLE, no PDC changes, no electrodomésticos, SANITARIOS renamed not deleted, SI never combines with MO/S/OC.

## Commit strategy

| Commit | Type | Scope | Summary |
|--------|------|-------|---------|
| 1 | feat | families | Add 6 new construction process families with regex rules and contract options |
| 3 | feat | migration | Migrate tipoContrato from numeric to comma-separated modality codes |
| 4 | feat | contratos | autoAssign stores comma-separated modality codes |
| 5 | feat | contratos | Add multi-select modality checkboxes and OC section |
| 6 | refactor | listado | Remove editable tipoContrato from create modal |
| 7 | refactor | listado | Make tipoContrato read-only with multi-badge display |
| 8 | feat | contratos | Implement multi-select checkbox logic with dynamic sections |

## Success criteria
1. Auto-generate on Da Porto matches ASEO activities (if PG contains "ASEO" or "LIMPIEZA")
2. /listado-actividades/ shows tipoContrato as read-only badges (one per modality)
3. /contratos/ modal has 4 checkboxes: SI (exclusive), MO, S, OC (combinable)
4. Checking SI disables MO/S/OC; checking MO/S/OC disables SI
5. Sections show/hide correctly for all valid combinations
6. OC section shows Proveedor + OC# fields (not 5-slot grid)
7. Existing activities migrated: "1"→"MO,S", "2"→"SI" — no "1" or "2" values remain
8. No database schema changes (no ALTER TABLE on any table)
