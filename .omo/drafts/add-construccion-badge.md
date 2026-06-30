# add-construccion-badge - Draft

## Status: awaiting-approval
## Pending action: write .omo/plans/add-construccion-badge.md (already scaffolded)

## Findings

### Current State
- `project_selector.view.php` line 144-148: conditional block shows "Pre-Construcción" badge (yellow) when `$proyecto['Area'] === 'Pre-Construccion'`
- No badge shown for "Construcción" projects
- `$proyecto['Area']` is available (controller line 31 selects `p.Area`)

### Decision
- Add `elseif` block for "Construcción" badge (blue/info color) after the Pre-Construcción block
- Use `badge-info` (Bootstrap-4 blue) to distinguish from the yellow `badge-warning`

### Scope
- **IN**: Add Construcción badge to project cards
- **OUT**: No changes to controller, navbar, or other files
