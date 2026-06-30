# add-construccion-badge - Work Plan

## TL;DR (For humans)

**What you'll get:** Los proyectos de Construcción ahora muestran un badge azul "Construcción" junto al badge "Active", similar al badge amarillo "Pre-Construcción" que ya existe.

**Why this approach:** Solo se necesita agregar un `elseif` al bloque condicional existente en `project_selector.view.php`. El campo `$proyecto['Area']` ya está disponible.

**What it will NOT do:** No modifica el controller, el navbar, ni otros archivos.

**Effort:** Quick
**Risk:** Low - solo una edición HTML/PHP
**Decisions to sanity-check:** Color del badge (usar `badge-info` = azul para Construcción)

Your next move: Aprobar, o ejecutar directamente.

---

> TL;DR (machine): Quick, Low-risk — 1 file edit to add Construcción badge to project cards.

## Scope
### Must have
- Badge "Construcción" (azul) visible en cada card de proyecto que tenga `Area = 'Construccion'`

### Must NOT have (guardrails, anti-slop, scope boundaries)
- No modificar el controller ni la query SQL
- No modificar el navbar
- No agregar badges a otras páginas

## Verification strategy
> Zero human intervention - all verification is agent-executed.
- Test decision: none (UI-only)
- Evidence: .omo/evidence/task-1-add-construccion-badge.txt

## Execution strategy
### Parallel execution waves
> Target 5-8 todos per wave. Fewer than 3 (except the final) means you under-split.
- **Wave 1**: Add Construcción badge (1 todo)

### Dependency matrix
| Todo | Depends on | Blocks | Can parallelize with |
| --- | --- | --- | --- |
| 1 (add badge) | none | none | none |

## Todos
> Implementation + Test = ONE todo. Never separate.
<!-- APPEND TASK BATCHES BELOW THIS LINE WITH edit/apply_patch - never rewrite the headers above. -->

### Wave 1 — Add Construcción badge

- [x] 1. Add "Construcción" badge to each project card
  What to do / Must NOT do: In `views/core/project_selector.view.php`, replace the existing `<?php if ... endif; ?>` block (lines 144-148) with an `if/elseif/endif` pattern that shows either "Pre-Construcción" (yellow, `badge-warning`) OR "Construcción" (blue, `badge-info`). The new code block should be:

  ```php
  <?php if (($proyecto['Area'] ?? 'Construccion') === 'Pre-Construccion'): ?>
  <span class="badge badge-warning badge-status mr-2" style="font-size: 0.65rem;">
      <i class="fas fa-hard-hat mr-1"></i>Pre-Construcción
  </span>
  <?php elseif (($proyecto['Area'] ?? 'Construccion') === 'Construccion'): ?>
  <span class="badge badge-info badge-status mr-2" style="font-size: 0.65rem;">
      <i class="fas fa-hard-hat mr-1"></i>Construcción
  </span>
  <?php endif; ?>
  ```

  The `elseif` block should appear after the `<?php endif; ?>` of the Pre-Construcción block, adding the Construcción badge condition. Do NOT change anything else in the file.
  Parallelization: Wave 1 | Blocked by: none | Blocks: none
  References (executor has NO interview context - be exhaustive): `views/core/project_selector.view.php:144-148` (current if block to expand), `views/core/project_selector.view.php:45-49` (`.badge-status` CSS already defined), `src/Controllers/Core/ProjectSelectorController.php:31` (confirms `p.Area` is available)
  Acceptance criteria (agent-executable): Run `grep -n "Construcción" views/core/project_selector.view.php` — should return at least 1 result (the new badge). Run `php -l views/core/project_selector.view.php` — no syntax errors.
  QA scenarios:
    - Happy: Both badges appear — Pre-Construcción (yellow) for PC projects, Construcción (blue) for C projects
    - Failure: If Area is missing, fallback `'Construccion'` shows the blue Construcción badge (safe default)
    Evidence: .omo/evidence/task-1-add-construccion-badge.txt
  Commit: Y | feat(ui): add Construcción badge to project cards

## Final verification wave
> Runs in parallel after ALL todos. ALL must APPROVE. Surface results and wait for the user's explicit okay before declaring complete.
- [x] F1. Plan compliance audit — ✅ elseif block added correctly at lines 148-151
- [x] F2. Code quality review — ✅ PHP syntax passes, no orphaned tags
- [x] F3. Real manual QA — ✅ Server running, grep confirms both badges present
- [x] F4. Scope fidelity — ✅ Only project_selector.view.php changed

## Commit strategy
- Single commit: `feat(ui): add Construcción badge to project cards`

## Success criteria
- [x] Badge "Construcción" (azul) visible en cards de proyectos Construcción (line 148-151)
- [x] Badge "Pre-Construcción" (amarillo) sigue visible en cards de proyectos Pre-Construcción (line 144-147)
- [x] Sin errores PHP ni warnings (php -l passes)
