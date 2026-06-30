# move-badge-to-cards - Draft

## Status: awaiting-approval
## Pending action: write .omo/plans/move-badge-to-cards.md (already scaffolded)

## Findings

### Current State
- **Header badge**: `NavbarComponent.php` lines 43-47 render a "Pre-Construcción" badge next to "Last Planner AIA" when `$_SESSION['area'] === 'Pre-Construccion'`. This shows on ALL pages after selecting a project.
- **Project cards**: `project_selector.view.php` line 144 shows only "Active"/"Inactive" badge. The `$proyecto['Area']` field IS available (confirmed in controller line 31) but NOT used in the view.
- **Controller**: `ProjectSelectorController::index()` line 31 already selects `p.Area` in the SQL query. No backend changes needed.

### Decision
- Remove badge from header (NavbarComponent.php lines 43-47)
- Add "Pre-Construcción" badge to each project card in project_selector.view.php

### Scope
- **IN**: Move badge from header to cards
- **OUT**: No changes to controller, data model, or other pages

## Components
1. NavbarComponent.php - remove badge from header
2. project_selector.view.php - add badge to card header
