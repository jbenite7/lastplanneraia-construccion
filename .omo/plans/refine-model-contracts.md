# refine-model-contracts - Work Plan

## TL;DR (For humans)

**What you'll get:** 18 familias nuevas de procesos constructivos (Aseo, Equipos de Cocina, Lavaplatos, Lavadero, Accesorios Sanitarios, Aparatos Sanitarios renombrado, Demoliciones, Pasamanos, Muebles, Equipos Especiales, Apuntalamiento, Señaletica, Infraestructura-Drenes, Resanes, CCTV-Seguridad, Sonido-Video, Sistema-Datos, Automatización-BMS) + 6 expansiones de regex en familias existentes (Estructura, Vías, Cielos, Fachada, Red Eléctrica, Carpintería Metálica). Adicionalmente, el tipo de contrato se gestionará desde /contratos/ con un sistema de checkboxes multi-selección: SI (exclusivo), MO, S y OC (combinables). En /listado-actividades/ la columna será read-only con badges. Los valores existentes migran: "1"→"MO,S", "2"→"SI".

**Why this approach:** El análisis de 49,826 actividades en 13 PGs reveló 1,042 actividades sin match (15%). Las 18 familias nuevas + 6 expansiones cubren ~210 adicionales, llevando la cobertura de 84% a ~92%. El resto son actividades administrativas (entregables, actas, firmas) que correctamente no mapean. El multi-select de modalidades refleja la realidad operativa sin alterar schema.

**What it will NOT do:** No modificará el schema de tablas. No añadirá electrodomésticos. No cambiará el algoritmo de matching. No modificará el módulo PDC. No mapeará OBRA_NEGRA/OBRA_BLANCA (son fases). No mapeará actividades administrativas.

**Effort:** Large
**Risk:** Medium — migración de datos + rediseño de UI + SQL seed grande
**Decisions to sanity-check:** (1) Migración "1"→"MO,S" en todas las DBs; (2) OC reutiliza S1/paqueteS1; (3) 18 familias nuevas requieren orden secuencial nuevo

Your next move: approve to proceed with execution.

---

> TL;DR (machine): Large effort, Medium risk — 18 new families + 6 regex expansions + multi-select contract system + migration + UI redesign.

## Scope
### Must have
- SQL patch: 18 new families, ~45 regex rules, contract options for each
- Rename SANITARIOS → APARATOS_SANITARIOS (UPDATE preserving id)
- 6 regex expansions in existing families (ESTRUCTURA_CONCRETO, VIAS_PAVIMENTOS, CIELOS_RASOS, FACHADA, RED_ELECTRICA, CARPINTERIA_METALICA)
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
- Do NOT map OBRA_NEGRA or OBRA_BLANCA (they are phases, not processes)
- Do NOT map administrative activities (entregables, actas, firmas, liberaciones, movilización)

## Verification strategy
> Zero human intervention - all verification is agent-executed.
- Test decision: tests-after + manual QA via Playwright
- Evidence: .omo/evidence/task-<N>-refine-model-contracts.<ext>

## Execution strategy
### Parallel execution waves

### Dependency matrix
| Todo | Depends on | Blocks | Can parallelize with |
| --- | --- | --- | --- |
| 1 (SQL: 18 families + rename + 45 rules) | — | 2, 3 | 4, 5, 6, 7 |
| 2 (SQL: contract options + items) | 1 | 3 | 4, 5, 6, 7 |
| 3 (SQL: 6 regex expansions + migration + deploy) | 1, 2 | 10 | 4, 5, 6, 7 |
| 4 (ContratosApiController: autoAssign multi-select) | — | 9 | 1, 2, 3, 5, 6, 7 |
| 5 (Contratos view: checkboxes + OC section) | — | 8 | 1, 2, 3, 4, 6, 7 |
| 6 (Listado view: remove select from create modal) | — | 10 | 1, 2, 3, 4, 5, 7 |
| 7 (Listado view: read-only badges + inline edit) | — | 10 | 1, 2, 3, 4, 5, 6 |
| 8 (Contratos view JS: checkbox logic + show/hide) | 5 | 10 | 6, 7 |
| 9 (ContratosApiController: autoAssign 5 types) | 4 | 10 | 6, 7, 8 |
| 10 (Deploy PHP + Playwright QA) | 3, 8, 9, 6, 7 | — | — |

## Todos
> Implementation + Test = ONE todo. Never separate.
<!-- APPEND TASK BATCHES BELOW THIS LINE WITH edit/apply_patch - never rewrite the headers above. -->
- [ ] 1. SQL patch: 18 new families + rename SANITARIOS + 45 regex rules
  What to do / Must NOT do: Create `database/patches/20260614_new_families.sql`. This is the master SQL patch with ALL new families and rules.

  STEP 1 — INSERT 18 new families into general_pdc_familias (preserve existing orden ranges, use new slots):

  ACABADOS category (orden 62-75):
  - ASEO, orden=62, nombre='Aseo y Entrega'
  - EQUIPOS_COCINA, orden=63, nombre='Equipos de Cocina'
  - LAVAPLATOS, orden=64, nombre='Lavaplatos'
  - LAVADERO, orden=65, nombre='Lavadero Zona de Ropas'
  - ACCESORIOS_SANITARIOS, orden=66, nombre='Accesorios Sanitarios'
  - PASAMANOS, orden=67, nombre='Pasamanos y Barandas'
  - MUEBLES, orden=68, nombre='Muebles'
  - RESANES, orden=69, nombre='Resanes'

  PRELIMINARES category (orden 9):
  - DEMOLICIONES, orden=9, nombre='Demoliciones y Desmontes'

  CIMENTACION category (orden 19):
  - APUNTALAMIENTO, orden=19, nombre='Apuntalamiento'
  - INFRAESTRUCTURA_DRENES, orden=39, nombre='Infraestructura y Drenes' (put after existing cimentacion range)

  EQUIPOS category (orden 108):
  - EQUIPOS_ESPECIALES, orden=108, nombre='Equipos Especiales'

  URBANISMO category (orden 85):
  - SENALETICA, orden=85, nombre='Señaletica'

  INSTALACIONES category (orden 86-89):
  - CCTV_SEGURIDAD, orden=86, nombre='CCTV y Seguridad Electronica'
  - SONIDO_VIDEO, orden=87, nombre='Sistema de Sonido y Video'
  - SISTEMA_DATOS, orden=88, nombre='Sistema de Datos'
  - AUTOMATIZACION_BMS, orden=89, nombre='Automatizacion y BMS'

  STEP 2 — UPDATE existing SANITARIOS row:
  `UPDATE general_pdc_familias SET codigo='APARATOS_SANITARIOS', nombre='Aparatos Sanitarios' WHERE codigo='SANITARIOS'`

  STEP 3 — INSERT ~45 regex rules into general_pdc_activity_rules:
  ORIGINAL 6 (from approved plan):
  - ASEO: `/ASEO|LIMPIEZA.*FINAL|ENTREGA.*APARTAMENTO|ENTREGA.*APTOS|ASEO.*GENERAL|LIMPIEZA.*OBRA/u` confianza=85 prioridad=90
  - EQUIPOS_COCINA: `/CAMPANA.*EXTRACTORA|CAMPANA.*COCINA|CUBIERTA.*COCINA|ESTUFA|EQUIPO.*COCINA/u` confianza=90 prioridad=110
  - LAVAPLATOS: `/LAVAPLATOS|LAVA.*PLATOS|LAVAVAJILLAS/u` confianza=92 prioridad=115
  - LAVADERO: `/LAVADERO|LAVANDERO|LAVADERO.*GRANITO|LAVADERO.*MARMOL|LAVADERO.*SINTETICO|ZONA.*ROPA|CUARTO.*LAVADO/u` confianza=90 prioridad=110
  - ACCESORIOS_SANITARIOS: `/ACCESORIO.*BANO|ACCESORIO.*COCINA|ACCESORIO.*ROPA|TOALLERO|JABONERA|PAPELERA|BARRA.*AGARRE|PORTA.*PAPEL|PORTA.*TOALLA|GANCHERA|COLGADOR|ACCESORIO.*SANITARIO/u` confianza=88 prioridad=100
  - APARATOS_SANITARIOS: `/APARATO.*SANITARIO|APARATOS.*SANITARIOS|SANITARIO|LAVAMANOS|INODORO|GRIFERIA|DUCHA/u` confianza=92 prioridad=110

  NEW 12:
  - DEMOLICIONES: `/DEMOLICION|DESMONTE|DESMONTES/u` confianza=88 prioridad=100
  - PASAMANOS: `/PASAMANOS|TALON.*PASAMANOS|BARANDA|BARRANDA|RIELES/u` confianza=88 prioridad=100
  - MUEBLES: `/MUEBLE|MUEBLES|FABRICACION.*MUEBLES|SUMINISTRO.*MUEBLES/u` confianza=88 prioridad=100
  - EQUIPOS_ESPECIALES: `/EQUIPO.*ESPECIAL|EQUIPOS.*ESPECIAL|EQUIPOS.*RX|BHS|BANDA.*EQUIPAJE/u` confianza=85 prioridad=90
  - APUNTALAMIENTO: `/PUNTAL|APUNTALAMIENTO|APUNTALAR/u` confianza=88 prioridad=110
  - SENALETICA: `/SENALETICA|SENAL.*HORIZONTAL/u` confianza=88 prioridad=100
  - INFRAESTRUCTURA_DRENES: `/CARCAMO|COLCHON.*DRENANTE|ROCA.*HINCADA|SUBRASANTE|GEOTEXTIL/u` confianza=88 prioridad=100
  - RESANES: `/RESAN|RESANES|RESANE.*PUNTO.*FIJO/u` confianza=85 prioridad=90
  - CCTV_SEGURIDAD: `/CCTV|CIRCUITO.*CERRADO.*TV|CONTROL.*ACCESO|INTRUSION|SEGURIDAD.*CONTROL|VIGILANCIA.*ELECTRONICA/u` confianza=88 prioridad=110
  - SONIDO_VIDEO: `/SONIDO|VIDEO|SISTEMA.*SONIDO/u` confianza=88 prioridad=110
  - SISTEMA_DATOS: `/SISTEMA.*DATOS|DATOS|DUCTERIA|CUARTO.*DATOS/u` confianza=85 prioridad=100
  - AUTOMATIZACION_BMS: `/AUTOMATIZACION|BMS|INTEGRACION.*CONTROL|MONITOREO.*CONTROL/u` confianza=85 prioridad=100

  Must NOT DELETE SANITARIOS row. Must NOT add electrodomésticos. Must NOT create INSUMOS families. Must NOT map OBRA_NEGRA/OBRA_BLANCA.
  Parallelization: Wave 1 | Blocked by: — | Blocks: 2, 3
  References: database/patches/20260612_pdc_familias_maestro.sql:13-22 (familias schema), :24-37 (rules schema), :154-236 (existing families), :266-417 (existing rules), :193 (SANITARIOS row)
  Acceptance criteria: SQL executes without error. `SELECT COUNT(*) FROM general_pdc_familias` returns 93 (75 existing + 18 new). `SELECT codigo FROM general_pdc_familias WHERE codigo='APARATOS_SANITARIOS'` returns 1. `SELECT codigo FROM general_pdc_familias WHERE codigo='SANITARIOS'` returns 0. Each new family has ≥1 regex rule.
  QA scenarios: `docker compose exec db mysql -uapp -p'<DB_PASSWORD>' last_planner -e "SELECT codigo, nombre FROM general_pdc_familias WHERE codigo IN ('ASEO','EQUIPOS_COCINA','LAVAPLATOS','LAVADERO','ACCESORIOS_SANITARIOS','APARATOS_SANITARIOS','DEMOLICIONES','PASAMANOS','MUEBLES','EQUIPOS_ESPECIALES','APUNTALAMIENTO','SENALETICA','INFRAESTRUCTURA_DRENES','RESANES','CCTV_SEGURIDAD','SONIDO_VIDEO','SISTEMA_DATOS','AUTOMATIZACION_BMS')"` → 18 rows. Evidence: .omo/evidence/task-1-refine-model-contracts.txt
  Commit: Y | feat(families): add 18 new construction process families with regex rules

- [ ] 2. SQL patch: contract options + option items for 18 new families
  What to do / Must NOT do: In same `database/patches/20260614_new_families.sql` (append), INSERT into general_pdc_family_contract_options for each new family:
    - ASEO: tipo_contrato=1 (MO,S), dias_elab=8, dias_entrega=7, dias_cuadros=5, dias_legalizacion=10
    - EQUIPOS_COCINA: tipo_contrato=2 (SI), dias_elab=8, dias_entrega=15, dias_cuadros=10, dias_legalizacion=20
    - LAVAPLATOS: tipo_contrato=2 (SI), dias_elab=8, dias_entrega=15, dias_cuadros=10, dias_legalizacion=20
    - LAVADERO: tipo_contrato=2 (SI), dias_elab=8, dias_entrega=7, dias_cuadros=5, dias_legalizacion=10
    - ACCESORIOS_SANITARIOS: tipo_contrato=2 (SI), dias_elab=8, dias_entrega=7, dias_cuadros=5, dias_legalizacion=10
    - DEMOLICIONES: tipo_contrato=1 (MO,S), dias_elab=8, dias_entrega=7, dias_cuadros=5, dias_legalizacion=10
    - PASAMANOS: tipo_contrato=2 (SI), dias_elab=8, dias_entrega=7, dias_cuadros=5, dias_legalizacion=10
    - MUEBLES: tipo_contrato=2 (SI), dias_elab=8, dias_entrega=15, dias_cuadros=10, dias_legalizacion=20
    - EQUIPOS_ESPECIALES: tipo_contrato=2 (SI), dias_elab=8, dias_entrega=15, dias_cuadros=10, dias_legalizacion=20
    - APUNTALAMIENTO: tipo_contrato=2 (SI), dias_elab=8, dias_entrega=7, dias_cuadros=5, dias_legalizacion=10
    - SENALETICA: tipo_contrato=2 (SI), dias_elab=8, dias_entrega=7, dias_cuadros=5, dias_legalizacion=10
    - INFRAESTRUCTURA_DRENES: tipo_contrato=1 (MO,S), dias_elab=8, dias_entrega=7, dias_cuadros=5, dias_legalizacion=10
    - RESANES: tipo_contrato=1 (MO,S), dias_elab=8, dias_entrega=7, dias_cuadros=5, dias_legalizacion=10
    - CCTV_SEGURIDAD: tipo_contrato=2 (SI), dias_elab=8, dias_entrega=15, dias_cuadros=10, dias_legalizacion=20
    - SONIDO_VIDEO: tipo_contrato=2 (SI), dias_elab=8, dias_entrega=15, dias_cuadros=10, dias_legalizacion=20
    - SISTEMA_DATOS: tipo_contrato=2 (SI), dias_elab=8, dias_entrega=15, dias_cuadros=10, dias_legalizacion=20
    - AUTOMATIZACION_BMS: tipo_contrato=2 (SI), dias_elab=8, dias_entrega=15, dias_cuadros=10, dias_legalizacion=20
    - APARATOS_SANITARIOS: no new option needed (existing SANITARIOS option preserved by rename)
  INSERT option items with 1-2 paquetes per family (following existing pattern from lines 522-640).
  Must NOT remove existing SANITARIOS/APARATOS_SANITARIOS options.
  Parallelization: Wave 1 | Blocked by: 1 | Blocks: 3
  References: database/patches/20260612_pdc_familias_maestro.sql:423-516 (contract options pattern), :522-640 (option items pattern)
  Acceptance criteria: Each new family has ≥1 contract option. 18 new contract options total (17 INSERTs + APARATOS_SANITARIOS preserved).
  QA scenarios: `SELECT f.codigo, o.tipo_contrato, o.tipo_paquete FROM general_pdc_family_contract_options o JOIN general_pdc_familias f ON f.id=o.familia_id WHERE f.codigo IN ('ASEO','EQUIPOS_COCINA','LAVAPLATOS','LAVADERO','ACCESORIOS_SANITARIOS','DEMOLICIONES','PASAMANOS','MUEBLES','EQUIPOS_ESPECIALES','APUNTALAMIENTO','SENALETICA','INFRAESTRUCTURA_DRENES','RESANES','CCTV_SEGURIDAD','SONIDO_VIDEO','SISTEMA_DATOS','AUTOMATIZACION_BMS')"` → 17+ rows. Evidence: .omo/evidence/task-2-refine-model-contracts.txt
  Commit: N (same commit as task 1)

- [ ] 3. SQL: 6 regex expansions + migration + deploy to Docker DB
  What to do / Must NOT do: Three sub-tasks in this todo:

  SUB-TASK A — 6 regex expansions on existing families (append to same SQL patch or separate file `database/patches/20260615_regex_expansions.sql`):
  - ESTRUCTURA_CONCRETO: Add new rule `/CUBIERTA|ESCALERAS|RAMPAS/u` confianza=80 prioridad=75 (lower than existing so specific rules win first)
  - VIAS_PAVIMENTOS: Add new rule `/ASFALT|MEZCLA.*ASFALTICA|SUBRASANTE|BASE.*ASFALTICA|PLATAFORMA.*CONCRETO/u` confianza=85 prioridad=90
  - CIELOS_RASOS: Add new rule `/^CIELOS$/u` confianza=82 prioridad=85
  - FACHADA: Add new rule `/FACHADA.*METECNO|ALUCOBOND|MUROS.*EXTERIORES|FACHADAS/u` confianza=85 prioridad=90
  - RED_ELECTRICA: Add new rule `/SUICHES|CABLE.*COBRE/u` confianza=82 prioridad=85
  - CARPINTERIA_METALICA: Add new rule `/DIVISIONES.*BANO|DIVISION.*BANO/u` confianza=85 prioridad=95
  Use `INSERT INTO general_pdc_activity_rules` with familia_id from `SELECT id FROM general_pdc_familias WHERE codigo='...'`.

  SUB-TASK B — Migration script `database/migrations/migrate_tipos_contrato.php`:
  - Connect to DB (`app` / `<DB_PASSWORD>` / `last_planner`)
  - Query: `SELECT Base_de_Datos FROM general_proyectos_procesos WHERE Base_de_Datos IS NOT NULL AND Base_de_Datos != ''`
  - For each db_prefix, run: `UPDATE {db_prefix}_actividades SET tipoContrato='MO,S' WHERE tipoContrato='1'` and `UPDATE {db_prefix}_actividades SET tipoContrato='SI' WHERE tipoContrato='2'`

  SUB-TASK C — Deploy to Docker:
  ```
  docker compose cp database/patches/20260614_new_families.sql db:/tmp/
  docker compose exec db mysql -uapp -p'<DB_PASSWORD>' last_planner -e "SOURCE /tmp/20260614_new_families.sql"
  docker compose cp database/patches/20260615_regex_expansions.sql db:/tmp/  # if separate file
  docker compose exec db mysql -uapp -p'<DB_PASSWORD>' last_planner -e "SOURCE /tmp/20260615_regex_expansions.sql"
  docker compose cp database/migrations/migrate_tipos_contrato.php app:/var/www/html/database/migrations/
  docker compose exec app php database/migrations/migrate_tipos_contrato.php
  ```

  Must NOT run on production. Must NOT fail silently if a project DB doesn't exist. Must NOT DELETE any rows.
  Parallelization: Wave 2 | Blocked by: 1, 2 | Blocks: 10
  References: general_proyectos_procesos table, {prefix}_actividades.tipoContrato, database/patches/20260612_pdc_familias_maestro.sql (existing rule patterns)
  Acceptance criteria: All existing tipoContrato='1' become 'MO,S'. All tipoContrato='2' become 'SI'. No '1' or '2' values remain. 6 new regex rules added. `SELECT COUNT(*) FROM general_pdc_activity_rules` increases by 6.
  QA scenarios: `SELECT DISTINCT tipoContrato FROM da_porto_actividades` → no '1' or '2'. `SELECT COUNT(*) FROM general_pdc_activity_rules WHERE familia_id=(SELECT id FROM general_pdc_familias WHERE codigo='VIAS_PAVIMENTOS')` → at least 3 (existing 2 + new 1). Evidence: .omo/evidence/task-3-refine-model-contracts.txt
  Commit: Y | feat(migration): migrate tipoContrato + expand 6 existing family regex rules

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
    5. Update the response labels: send both the comma-separated tipoContrato AND the human-readable label.
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
    2. Add new section after SI section (after line ~130) for OC:
       Section id='parametro_EditarContratosOC', title='Orden de Compra', type='oc' (special — 2 fields, not 5 slots)
       Render: "Proveedor" text input (id="ocProveedor", maps to S1) and "Orden de Compra #" text input (id="ocNumero", maps to paqueteS1). No 5-slot grid.
    3. Keep the hidden tipoContrato input — synced by JS from checkbox state.
  Must NOT remove existing S, MO, SI sections. Must NOT change DataTable column definitions.
  Parallelization: Wave 1 | Blocked by: — | Blocks: 8
  References: views/contratos/contratos.view.php:102 (hidden input), :105-130 (section definitions), :133-144 (section render loop)
  Acceptance criteria: `php -l views/contratos/contratos.view.php` passes. 4 checkboxes visible. OC section with 2 fields exists. Hidden tipoContrato input still present.
  QA scenarios: Playwright — open /contratos/, click edit — verify 4 checkboxes visible, OC section exists. Evidence: .omo/evidence/task-5-refine-model-contracts.png
  Commit: Y | feat(contratos): add multi-select modality checkboxes and OC section

- [ ] 6. Listado view: remove tipoContrato select from create modal
  What to do / Must NOT do: In `views/listado-actividades/listadoActividades.view.php`:
    1. Remove the tipoContrato field group (lines 128-135) from "Nueva Actividad" modal.
    2. Activity is created without tipoContrato (NULL/empty).
  Must NOT remove the column from DataTable. Must NOT change the save flow.
  Parallelization: Wave 1 | Blocked by: — | Blocks: 10
  References: views/listado-actividades/listadoActividades.view.php:128-135
  Acceptance criteria: `php -l` passes. Create modal has no tipoContrato dropdown.
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
           return modalidades.map(function(m) { return badges[m.trim()] || m; }).join(' ');
       }
       ```
    2. Replace inline edit select (line 1390) with plain text: `$row.find('td:eq(6)').html(escaparHtml(data.tipoContrato || 'Sin asignar'));`
    3. Update createdCell header array (line 1131) if column index shifts.
  Must NOT remove the column. Must NOT hide the column.
  Parallelization: Wave 1 | Blocked by: — | Blocks: 10
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
           var mods = tc ? tc.split(',') : [];
           var hasSI = mods.indexOf('SI') >= 0;
           var hasMO = mods.indexOf('MO') >= 0;
           var hasS  = mods.indexOf('S') >= 0;
           var hasOC = mods.indexOf('OC') >= 0;
           $('#parametro_EditarContratosSI').toggle(hasSI);
           $('#parametro_EditarContratosMO').toggle(hasMO);
           $('#parametro_EditarContratosS').toggle(hasS);
           $('#parametro_EditarContratosOC').toggle(hasOC);
       }
       ```
       CRITICAL: Use split(',').indexOf('S') NOT indexOf('S') to avoid false-positive on 'SI'.
    4. On modal open (line 503-504): parse existing tipoContrato comma-separated → check corresponding checkboxes → call updateSections().
    5. On save: hidden tipoContrato is already synced by checkbox handlers, save flow works unchanged.
    6. Update the auto-assign modal table (lines 837-843) to show comma-separated labels instead of INT-based labels.
  Must NOT break existing save flow. Must NOT allow SI + MO/S/OC simultaneously.
  Parallelization: Wave 2 | Blocked by: 5 | Blocks: 10
  References: views/contratos/contratos.view.php:503-622 (existing JS show/hide), :837-843 (auto-assign table)
  Acceptance criteria: `php -l` passes. Checking SI disables others. Checking MO does not disable S or OC. Hidden tipoContrato always matches checkbox state. Sections show/hide correctly for all combinations.
  QA scenarios: Playwright — open edit modal, check SI → verify MO/S/OC disabled, only SI section visible. Uncheck SI, check MO+S → both sections visible, SI disabled. Check OC → OC section visible. Evidence: .omo/evidence/task-8-refine-model-contracts.png
  Commit: Y | feat(contratos): implement multi-select checkbox logic with dynamic sections

- [ ] 9. ContratosApiController: extend autoAssign for 5 contract types + multi-select response
  What to do / Must NOT do: In `src/Controllers/Api/ContratosApiController.php`:
    1. Update autoAssign response to include the comma-separated tipoContrato (e.g., 'MO,S', 'SI', 'OC') AND a human-readable label array (e.g., ['Mano de Obra', 'Suministro']).
    2. Ensure the auto-assign modal table (frontend) can display the new format.
    3. Verify that the autoAssign flow works with the new 18 families and 6 expanded regex rules (no code change needed — it loads rules dynamically from DB).
  Must NOT hardcode family names. Must NOT break existing autoAssign for types 1 and 2 (now 'MO,S' and 'SI').
  Parallelization: Wave 2 | Blocked by: 4 | Blocks: 10
  References: src/Controllers/Api/ContratosApiController.php (autoAssign, selectBestContratoOption, assignContratoToActivity)
  Acceptance criteria: `php -l` passes. autoAssign response includes both comma-separated tipoContrato and label array. Works with all 93 families.
  QA scenarios: Call POST /api/contratos/auto-assign — verify response handles all modality combinations. Evidence: .omo/evidence/task-9-refine-model-contracts.txt
  Commit: Y | feat(contratos): autoAssign response with comma-separated modalities and labels

- [ ] 10. Deploy all changes to Docker + full Playwright QA
  What to do / Must NOT do: Copy all modified files to Docker:
    ```
    docker compose cp views/contratos/contratos.view.php app:/var/www/html/views/contratos/contratos.view.php
    docker compose cp views/listado-actividades/listadoActividades.view.php app:/var/www/html/views/listado-actividades/listadoActividades.view.php
    docker compose cp src/Controllers/Api/ContratosApiController.php app:/var/www/html/src/Controllers/Api/ContratosApiController.php
    docker compose cp database/migrations/migrate_tipos_contrato.php app:/var/www/html/database/migrations/migrate_tipos_contrato.php
    ```
    Run PHP migration script if not already run in task 3.
    Full Playwright QA:
    1. Login as `test.A` / `<TEST_PASSWORD>`
    2. /listado-actividades/ — verify badges (MO+S = 2 badges, SI = 1 badge, empty = "Sin asignar"). Create modal has no tipoContrato. Inline edit shows text.
    3. /contratos/ — verify 4 checkboxes in edit modal. Check SI → others disabled, SI section visible. Uncheck SI, check MO+S → both sections visible. Check OC → OC section visible.
    4. Auto-generate on Da Porto — verify new families (ASEO, EQUIPOS_COCINA, DEMOLICIONES, PASAMANOS, etc.) match if PG has those activities. Verify sin-match count decreased.
    5. Auto-assign from /contratos/ — verify it stores comma-separated values.
  Must NOT deploy to production. Must NOT skip any QA step.
  Parallelization: Wave 3 | Blocked by: 3, 8, 9, 6, 7 | Blocks: —
  References: docker-compose.yml, .env.example
  Acceptance criteria: All Playwright QA steps pass. No PHP errors. No SQL errors. 18 new families match. 6 regex expansions match. Multi-select works. Migration complete (no '1' or '2' values remain). Sin-match count decreased from ~1,042 to ~700.
  QA scenarios: Full Playwright flow. Evidence: .omo/evidence/task-10-refine-model-contracts.png, .omo/evidence/task-10-refine-model-contracts.log
  Commit: N (deployment step)

## Final verification wave
> Runs in parallel after ALL todos. ALL must APPROVE.
- [ ] F1. Plan compliance audit — 18 families exist in DB. 6 regex expansions applied. Migration complete. Multi-select works. Read-only in listado. No OBRA_NEGRA/OBRA_BLANCA mapping.
- [ ] F2. Code quality review — no SQL injection, no breaking API changes, PHP syntax clean, JS checkbox logic covers all combinations including edge cases.
- [ ] F3. Real manual QA — Playwright: login → listado (badges, no edit) → contratos (4 checkboxes, SI exclusive, sections, OC) → auto-generate (18 new families match) → auto-assign (comma-separated).
- [ ] F4. Scope fidelity — no INSUMOS, no ALTER TABLE, no PDC changes, no electrodomésticos, SANITARIOS renamed not deleted, SI never combines with MO/S/OC, no OBRA_NEGRA/OBRA_BLANCA families, no administrative activity mapping.

## Commit strategy

| Commit | Type | Scope | Summary |
|--------|------|-------|---------|
| 1 | feat | families | Add 18 new construction process families with 45 regex rules and contract options |
| 3 | feat | migration | Migrate tipoContrato to comma-separated + expand 6 existing family regex rules |
| 4 | feat | contratos | autoAssign stores comma-separated modality codes |
| 5 | feat | contratos | Add multi-select modality checkboxes and OC section |
| 6 | refactor | listado | Remove editable tipoContrato from create modal |
| 7 | refactor | listado | Make tipoContrato read-only with multi-badge display |
| 8 | feat | contratos | Implement multi-select checkbox logic with dynamic sections |
| 9 | feat | contratos | autoAssign response with comma-separated modalities and labels |

## Success criteria
1. Auto-generate on Da Porto matches ASEO, DEMOLICIONES, PASAMANOS and other new families
2. Sin-match count across all PGs drops from ~1,042 to ~700 (84% → ~92% coverage)
3. /listado-actividades/ shows tipoContrato as read-only badges (one per modality)
4. /contratos/ modal has 4 checkboxes: SI (exclusive), MO, S, OC (combinable)
5. Checking SI disables MO/S/OC; checking MO/S/OC disables SI
6. Sections show/hide correctly for all valid combinations
7. OC section shows Proveedor + OC# fields (not 5-slot grid)
8. Existing activities migrated: "1"→"MO,S", "2"→"SI" — no "1" or "2" values remain
9. No database schema changes (no ALTER TABLE on any table)
10. 6 regex expansions capture additional activities in ESTRUCTURA_CONCRETO, VIAS_PAVIMENTOS, CIELOS_RASOS, FACHADA, RED_ELECTRICA, CARPINTERIA_METALICA
