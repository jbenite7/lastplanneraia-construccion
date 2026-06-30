# fix-badge-alignment - Work Plan

## TL;DR (For humans)

**What you'll get:** Los badges "Pre-Construcción" y "Construcción" se alinean correctamente arriba a la derecha de cada card, sin importar si el título ocupa una o varias líneas.

**Why this approach:** Cambiar `align-items-center` a `align-items-start` en el contenedor flex del card header.

**What it will NOT do:** No modifica otros archivos ni la lógica de badges.

**Effort:** Quick
**Risk:** Low - solo una clase CSS

---

> TL;DR (machine): Quick, Low-risk — 1 CSS class change to fix badge alignment.

## Scope
### Must have
- Badges alineados arriba a la derecha en todas las cards

### Must NOT have
- No modificar otros archivos

## Verification strategy
- Test decision: none (CSS-only)
- Evidence: .omo/evidence/task-1-fix-badge-alignment.txt

## Execution strategy
### Parallel execution waves
- **Wave 1**: Fix alignment (1 todo)

### Dependency matrix
| Todo | Depends on | Blocks | Can parallelize with |
| --- | --- | --- | --- |
| 1 (fix alignment) | none | none | none |

## Todos
### Wave 1 — Fix badge alignment

- [x] 1. Fix badge alignment in card headers
  What to do / Must NOT do: In `views/core/project_selector.view.php` line 140, change `align-items-center` to `align-items-start` in the `card-header-project` div. This ensures badges align to the top when the title wraps to multiple lines.
  Parallelization: Wave 1 | Blocked by: none | Blocks: none
  References: `views/core/project_selector.view.php:140` (current `d-flex justify-content-between align-items-center`)
  Acceptance criteria: `grep -n "align-items-start" views/core/project_selector.view.php` returns at least 1 result. `php -l views/core/project_selector.view.php` passes.
  QA scenarios:
    - Happy: Badges align to top-right on all cards regardless of title length
    - Failure: N/A - simple CSS change
    Evidence: .omo/evidence/task-1-fix-badge-alignment.txt
  Commit: Y | fix(ui): align badges to top-right in project cards

## Final verification wave
- [x] F1. Plan compliance audit — ✅ align-items-start applied on line 140
- [x] F2. Code quality review — ✅ PHP syntax passes
- [x] F3. Real manual QA — ✅ Server running, visual alignment verified
- [x] F4. Scope fidelity — ✅ Only project_selector.view.php changed

## Commit strategy
- Single commit: `fix(ui): align badges to top-right in project cards`

## Success criteria
- [x] Badges alineados arriba a la derecha en todas las cards (align-items-start)
- [x] Sin errores PHP ni warnings (php -l passes)
