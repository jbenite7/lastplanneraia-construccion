# group-badges - Work Plan

## TL;DR (For humans)

**What you'll get:** Los badges de área (Pre-Construcción/Construcción) y "Active" se agrupan juntos en un contenedor flex, alineados como un bloque a la derecha de cada card.

**Why this approach:** Envolver los badges en un `<div class="d-flex align-items-center">` para que se mantengan juntos.

**What it will NOT do:** No modifica otros archivos.

**Effort:** Quick
**Risk:** Low

---

> TL;DR (machine): Quick — wrap badges in flex container to group them together.

## Scope
### Must have
- Badges agrupados juntos a la derecha de cada card

### Must NOT have
- No modificar otros archivos

## Verification strategy
- Test decision: none (HTML structure change)
- Evidence: .omo/evidence/task-1-group-badges.txt

## Execution strategy
### Dependency matrix
| Todo | Depends on | Blocks | Can parallelize with |
| --- | --- | --- | --- |
| 1 (group badges) | none | none | none |

## Todos

### Wave 1 — Group badges

- [ ] 1. Wrap badges in flex container
  What to do / Must NOT do: In `views/core/project_selector.view.php`, wrap the area badge (lines 144-152) and the Active badge (line 153) in a `<div class="d-flex align-items-center">` container. Remove `mr-2` from the area badge (the container handles spacing). The result should be:

  ```html
  <div class="d-flex align-items-center">
      <?php if (($proyecto['Area'] ?? 'Construccion') === 'Pre-Construccion'): ?>
      <span class="badge badge-warning badge-status mr-2" style="font-size: 0.65rem;">
          <i class="fas fa-hard-hat mr-1"></i>Pre-Construcción
      </span>
      <?php elseif (($proyecto['Area'] ?? 'Construccion') === 'Construccion'): ?>
      <span class="badge badge-info badge-status mr-2" style="font-size: 0.65rem;">
          <i class="fas fa-hard-hat mr-1"></i>Construcción
      </span>
      <?php endif; ?>
      <span class="badge badge-success badge-status"><?php echo $proyecto['Activo'] == 1 ? 'Active' : 'Inactive'; ?></span>
  </div>
  ```

  Parallelization: Wave 1 | Blocked by: none | Blocks: none
  References: `views/core/project_selector.view.php:140-153` (current structure to modify)
  Acceptance criteria: `grep -n "d-flex align-items-center" views/core/project_selector.view.php` returns at least 1 result (the new wrapper). `php -l views/core/project_selector.view.php` passes.
  QA scenarios:
    - Happy: Badges grouped together on right side of each card
    - Failure: N/A
    Evidence: .omo/evidence/task-1-group-badges.txt
  Commit: Y | fix(ui): group badges together in project cards

## Final verification wave
- [ ] F1. Plan compliance audit — Verify wrapper div added
- [ ] F2. Code quality review — PHP syntax passes
- [ ] F3. Real manual QA — Visual grouping verified
- [ ] F4. Scope fidelity — Only project_selector.view.php changed

## Commit strategy
- Single commit: `fix(ui): group badges together in project cards`

## Success criteria
- [ ] Badges agrupados juntos a la derecha
- [ ] Sin errores PHP ni warnings
