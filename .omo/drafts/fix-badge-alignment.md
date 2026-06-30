# fix-badge-alignment - Draft

## Status: awaiting-approval

## Findings
- Current: `align-items-center` on `card-header-project` causes badges to float when title wraps to 2+ lines
- Fix: Change to `align-items-start` so badges align to top-right corner consistently

## Scope
- **IN**: CSS alignment fix in project_selector.view.php
- **OUT**: No other files
