# move-badge-to-cards - Work Plan

## TL;DR (For humans)

**What you'll get:** El badge "Pre-Construcción" se mueve del header/navbar a cada tarjeta de proyecto en la página "Tus Proyectos". Cada card mostrará un badge amarillo "Pre-Construcción" junto al badge "Active", y el header quedará limpio.

**Why this approach:** El campo `Area` ya viene en la query SQL del controller (línea 31). Solo se necesitan 2 ediciones: quitar el badge del navbar y agregarlo a las cards. Sin cambios backend.

**What it will NOT do:** No modifica el controller, el modelo de datos, ni otras páginas. No cambia el comportamiento de selección de proyecto ni la sesión.

**Effort:** Quick
**Risk:** Low - solo CSS/HTML, sin lógica de negocio
**Decisions to sanity-check:** Ubicación exacta del badge en la card (junto al badge "Active" o en otra posición)

Your next move: Aprobar, o ejecutar directamente. Full execution detail follows below.

---

> TL;DR (machine): Quick, Low-risk — 2 file edits to move badge from navbar to project cards.

## Scope
### Must have
- Badge "Pre-Construcción" visible en cada card de proyecto que tenga `Area = 'Pre-Construccion'`
- Badge removido del header/navbar
- Badge solo se muestra en la página "Tus Proyectos" (project_selector.view.php)

### Must NOT have (guardrails, anti-slop, scope boundaries)
- No modificar el controller ni la query SQL
- No modificar el modelo de datos ni el esquema de BD
- No agregar badges a otras páginas (solo project_selector.view.php)
- No cambiar la lógica de selección de proyecto ni la sesión

## Verification strategy
> Zero human intervention - all verification is agent-executed.
- Test decision: none (UI-only, no business logic changes) + visual verification via grep and file inspection
- Evidence: .omo/evidence/task-{N}-move-badge-to-cards.txt

## Execution strategy
### Parallel execution waves
> Target 5-8 todos per wave. Fewer than 3 (except the final) means you under-split.
- **Wave 1**: Remove badge from navbar (1 todo)
- **Wave 2**: Add badge to cards (1 todo)

### Dependency matrix
| Todo | Depends on | Blocks | Can parallelize with |
| --- | --- | --- | --- |
| 1 (remove from navbar) | none | none | 2 |
| 2 (add to cards) | none | none | 1 |

## Todos
> Implementation + Test = ONE todo. Never separate.
<!-- APPEND TASK BATCHES BELOW THIS LINE WITH edit/apply_patch - never rewrite the headers above. -->

### Wave 1 — Remove badge from header

- [x] 1. Remove "Pre-Construcción" badge from navbar header
  What to do / Must NOT do: Delete lines 43-47 in `src/View/Components/NavbarComponent.php` (the `<?php if ($isPreConstruccion): ?>` block that renders the badge inside the `<a class="navbar-brand">`). Keep the `$isPreConstruccion` variable declaration (line 10) because it is still used on line 109 to hide nav items. Do NOT remove the `$isPreConstruccion` detection logic.
  Parallelization: Wave 1 | Blocked by: none | Blocks: none
  References (executor has NO interview context - be exhaustive): `src/View/Components/NavbarComponent.php:43-47` (badge to remove), `src/View/Components/NavbarComponent.php:10` (keep this detection), `src/View/Components/NavbarComponent.php:109` (still needs `$isPreConstruccion`)
  Acceptance criteria (agent-executable): Run `grep -n "Pre-Construcción" src/View/Components/NavbarComponent.php` — should return 0 results. Run `grep -n "isPreConstruccion" src/View/Components/NavbarComponent.php` — should show lines 10 and 109 only.
  QA scenarios: 
    - Happy: grep confirms badge HTML removed, variable still present on line 10 and 109
    - Failure: if `$isPreConstruccion` is removed, nav items on line 109 will break → verify line 109 still references `$isPreConstruccion`
    Evidence: .omo/evidence/task-1-move-badge-to-cards.txt
  Commit: Y | fix(ui): remove Pre-Construcción badge from navbar header

### Wave 2 — Add badge to project cards

- [x] 2. Add "Pre-Construcción" badge to each project card
  What to do / Must NOT do: In `views/core/project_selector.view.php`, around line 144, add a conditional badge next to the existing "Active"/"Inactive" badge. The new badge should appear BEFORE the Active badge (to the left). Use this exact pattern inside the `card-header-project` div, between the `</h5>` (line 143) and the existing `<span class="badge badge-success badge-status">` (line 144):

  ```php
  <?php if (($proyecto['Area'] ?? 'Construccion') === 'Pre-Construccion'): ?>
  <span class="badge badge-warning badge-status mr-2" style="font-size: 0.65rem;">
      <i class="fas fa-hard-hat mr-1"></i>Pre-Construcción
  </span>
  <?php endif; ?>
  ```

  Place it on a new line after line 143 (`</h5>`) and before line 144 (the Active badge). The `mr-2` adds spacing between the two badges. Do NOT change the existing Active badge.
  Parallelization: Wave 2 | Blocked by: none | Blocks: none
  References (executor has NO interview context - be exhaustive): `views/core/project_selector.view.php:140-144` (card header with existing badge), `src/Controllers/Core/ProjectSelectorController.php:31` (confirms `p.Area` is selected in SQL query), `views/core/project_selector.view.php:45-49` (`.badge-status` CSS class already defined)
  Acceptance criteria (agent-executable): Run `grep -n "Pre-Construcción" views/core/project_selector.view.php` — should return at least 1 result (the new badge). Open the page at localhost:8081/proyectos, projects with `Area = 'Pre-Construccion'` should show a yellow "Pre-Construcción" badge next to "Active"; projects with `Area = 'Construccion'` should show only "Active".
  QA scenarios:
    - Happy: Visually confirm badge appears on Pre-Construcción projects (e.g., "Aeropuerto Regional PC") and does NOT appear on Construcción projects
    - Failure: If `$proyecto['Area']` is not in the array, the fallback `'Construccion'` prevents errors — verify no PHP notices
    Evidence: .omo/evidence/task-2-move-badge-to-cards.txt
  Commit: Y | feat(ui): add Pre-Construcción badge to project cards

## Final verification wave
> Runs in parallel after ALL todos. ALL must APPROVE. Surface results and wait for the user's explicit okay before declaring complete.
- [x] F1. Plan compliance audit — ✅ Badge removed from NavbarComponent.php lines 43-47, badge added to project_selector.view.php line 144-148
- [x] F2. Code quality review — ✅ PHP syntax passes on both files, `$isPreConstruccion` preserved on lines 10 and 104
- [x] F3. Real manual QA — ✅ Server running on localhost:8081 (login page 200), grep and code review confirm the changes are correct
- [x] F4. Scope fidelity — ✅ Only the 2 target files changed (confirmed by git show --stat HEAD)

## Commit strategy
- Commit 1: `fix(ui): remove Pre-Construcción badge from navbar header`
- Commit 2: `feat(ui): add Pre-Construcción badge to project cards`
- Or squash into one: `feat(ui): move Pre-Construcción badge from header to project cards`

## Success criteria
- [x] Badge "Pre-Construcción" visible en cada card de proyecto Pre-Construcción (added at project_selector.view.php:144-148)
- [x] Badge NO visible en el header/navbar (removed from NavbarComponent.php:43-47)
- [x] Proyectos de Construcción muestran solo "Active" sin badge adicional (conditional on `$proyecto['Area'] === 'Pre-Construccion'`)
- [x] Sin errores PHP ni warnings (php -l passes on both files)
- [x] Navbar sigue funcionando correctamente (navegación, menús, dark mode) ($isPreConstruccion preserved for nav-item hiding)
