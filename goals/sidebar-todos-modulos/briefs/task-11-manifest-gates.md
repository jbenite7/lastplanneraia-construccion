# Task 11 — Registrar rutas migradas en foundation-shell.json + gates verdes

## Objetivo
Declarar las 11 rutas ya migradas al shell sidebar en `docs/design-system/manifests/foundation-shell.json` (`routes`) y dejar verdes los gates del foundation-shell. (Control Tower queda fuera — sub-goal futuro.)

## Rutas a tener en `routes` (11)
```
/programacion-intermedia            (ya está)
/programa-general
/profesionales
/subcontratistas
/control-cambios
/programa-general-actualizar
/programacion-semanal
/programacion-semanal/cic
/programacion-semanal/cnc
/programacion-semanal/cnp
/indicadores
```

## Pasos
1. **Investiga primero** qué disparan esas rutas en los gates del DS antes de asumir que basta con agregarlas:
   - Lee `scripts/design-system-router.mjs` (traduce "estos archivos cambiaron" → "corre estos gates") y `scripts/design-system-entrypoint-partition.mjs` si el router lo invoca.
   - Verifica si el gate de partición/manifiesto valida algo por ruta (p.ej. que cada ruta consuma `renderForModule('foundation-shell')`, o que los `vendors`/`sources` del manifiesto cubran esas rutas). OJO: los 11 módulos consumen `DesignSystemHeadComponent::render()` (el agregador congelado), NO `renderForModule`. Si el gate espera `renderForModule` para las rutas declaradas, agregarlas romperá el gate → NO fuerces; repórtalo como concern/blocker con el detalle exacto.
2. Si agregar las rutas es consistente con el modelo del manifiesto: agrégalas a `routes` (ordenadas), y corre los gates.

## Gates a correr y reportar (con salida)
- `node tests/browser/shell-sidebar-rollout.mjs` → 11 rutas PASS (55/55), exit 0.
- `node --test tests/design-system/shell-navigation.test.mjs`
- `docker compose exec -T app php tests/test_shell_sidebar_partial.php`
- `node tests/browser/shell-week-admin.mjs`
- `node tests/test_foundation_shell_contract.mjs`
- `node scripts/design-system-router.mjs docs/design-system/manifests/foundation-shell.json` (y cualquier gate que el router indique para el manifiesto cambiado) → verde.
- Si alguno es rojo, reporta el comando y la salida exacta; NO regeneres baselines ni fuerces verde.

## Restricciones
- Directo en main; commit solo del manifiesto (y nada más): `git add docs/design-system/manifests/foundation-shell.json`. Verifica `git diff --cached --name-only` — nada de PDC/DESIGN.md/.impeccable.
- No cambies el modelo del manifiesto (sources/components/vendors/tests) salvo que un gate lo exija y sea el cambio mínimo correcto; si lo exige, documéntalo.

## Verificación / commit
- Si todos los gates verdes: commit `chore(shell-sidebar): declarar 11 rutas migradas en foundation-shell.json` + trailer `Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>`.
- Si algún gate exige más que las rutas (renderForModule, vendors), NO commitees a ciegas: reporta BLOCKED/DONE_WITH_CONCERNS con el detalle para que el orquestador decida.

## Reporte
`goals/sidebar-todos-modulos/reports/task-11-report.md`. Devuelve SOLO: status, hash del commit (si aplica), resumen de gates (cuáles verdes/rojos con una línea c/u), concerns.
