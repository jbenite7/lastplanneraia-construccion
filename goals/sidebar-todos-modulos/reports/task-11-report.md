---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-02
areas: [proceso]
fuente: goals/sidebar-todos-modulos/reports/task-11-report.md
resumen: Task 11 — Registrar rutas migradas en foundation-shell.json + gates
---

# Task 11 — Registrar rutas migradas en foundation-shell.json + gates

## Status
DONE

## Commit
787fea41923a05055388ff1a7f77c7997f38d7ad — `chore(shell-sidebar): declarar 11 rutas migradas en foundation-shell.json`
(solo `docs/design-system/manifests/foundation-shell.json`; `git diff --cached --name-only` confirmado antes del commit)

## Investigación previa (modelo del gate)
- `scripts/design-system-contracts.mjs` valida `manifest.routes` solo contra el registro literal en
  `public/index.php` (`route not registered`) — no exige `renderForModule`. Confirmado grepeando las
  11 rutas + `/programacion-intermedia` como string literal en `public/index.php`: las 11 están registradas.
- `scripts/design-system-consumer-contract.mjs` (chequeo `renderForModule` vs vista) solo se activa si
  `manifest.consumerContract === 'v1'`. `foundation-shell.json` no tiene ese campo → el chequeo se
  salta por completo. Por eso agregar rutas cuyos módulos consumen `DesignSystemHeadComponent::render()`
  (no `renderForModule('foundation-shell')`) NO rompe el gate.
- Conclusión: agregar las 11 rutas a `routes` es consistente con el modelo actual del manifiesto; no
  se requirió tocar `sources`/`components`/`vendors`/`tests`.

## Gates (todos los pedidos por el brief)
- `node tests/browser/shell-sidebar-rollout.mjs` → **PASS** 55/55, exit 0.
- `node --test tests/design-system/shell-navigation.test.mjs` → **PASS** 2/2.
- `docker compose exec -T app php tests/test_shell_sidebar_partial.php` → **PASS** (todas las aserciones OK).
- `node tests/browser/shell-week-admin.mjs` → **PASS** 13/13 checks OK.
- `node tests/test_foundation_shell_contract.mjs` → **PASS** (exit 0, script silencioso sin salida en éxito).
- `node scripts/design-system-router.mjs docs/design-system/manifests/foundation-shell.json` → exit 0,
  "sin cambios de UI relevantes" (el router solo dispara comandos si el archivo cambiado matchea
  `UI_PREFIXES`; un `.json` de manifiesto no matchea, por lo que no indica gates adicionales).

## Concerns
- Diligencia extra (no pedida explícitamente por el brief): corrí `npm run test:design-system:static`
  como verificación más amplia del cambio de manifiesto. 287/288 verde; 1 rojo preexistente y no
  relacionado: `tests/design-system/contracts.test.mjs` → "activation: worktree and index must be
  clean", causado por trabajo ajeno ya presente en el árbol antes de esta tarea (`DESIGN.md` modificado
  sin commitear + `.impeccable/design.json` sin trackear). No se tocó ni se incluyó en el commit, tal
  como indican las restricciones. No bloquea esta tarea: no está en la lista de gates del brief y su
  causa es el estado del worktree, no el cambio del manifiesto.
- Control Tower queda fuera de este manifiesto (fuera de alcance, confirmado por el brief: sub-goal futuro).
