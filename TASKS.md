---
project: lps-aia
type: tasks
status: activo
updated: 2026-08-19
tags: [proyecto, php]
---

# Tareas

Estado del 2026-08-19. El trabajo corre en un enjambre de sesiones sobre `.claude/worktrees/`
(ver [[docs/coordinacion-sesiones]]); cada frente tiene su `goals/<slug>/goal.md` y su registro en
`decisiones/`. Esta lista es la vista consolidada para retomar sin releer el chat de cada sesión.

## Bloqueantes

- [ ] **Abrir una coordinadora nueva.** «Coordinadora Intento 3» ya no existe como sesión viva y el
  proyecto quedó sin sesión coordinadora: nadie audita el trabajo de las sesiones de ejecución, da
  el visto antes de publicar, ni es el único punto de contacto con el usuario para decisiones (regla
  en [[docs/coordinacion-sesiones]]). Mientras no haya coordinadora, los frentes activos no deberían
  publicar a `main` sin ese visto.

## Ahora — frentes activos en worktrees (2026-08-19)

- [ ] **Tests de BI frágiles** (`bi-control-tower-gemini`, chip ya arrancado). El goal lleva
  bloqueado desde 2026-08-10 por causa mal diagnosticada: no es «falta aprobación visual», es que
  pide aprobar 6 modos y 3 usan el tema `linen`, retirado el 2026-07-25 (DS-030). Hay que rehacer la
  condición de hecho antes de seguir, no solo correr los tests.
- [ ] **runtime-budgets-al-ci** — Fase 1 del plan `docs/superpowers/plans/2026-08-19-runtime-budgets-al-ci.md`,
  sha verificado `c23b1c6a`. Desbloquea el único gate `blocked` de `closeout-evidence.json` (9
  gates). Es andamio declarado, no inversión: DS-F3 lo reemplaza cuando llegue.
- [ ] **bug-coloreado-severidad** — verificación objetivo `npm run test:design-system:static`; el
  `goal.md` todavía no tiene plan/fase asociados (`?` declarado, no `-`).
- [ ] **DS-F0 / DS-F1a** — auditoría total del design system (`docs/superpowers/plans/2026-08-19-ds-f0-auditoria-total.md`,
  usa encabezados `## Tanda N` que `cas-frente.sh` no reconoce como `Task N`/`Fase N` — decisión
  encolada en `decisiones/ds-f0-auditoria-ejecutor.md`) y estado por severidad `ds-f1a-estado`
  (decisiones en `decisiones/ds-f1a-estado-ejecutor.md`).
- [ ] **Wiki v2 — Tanda 1** (`wiki-t1`, plan `docs/superpowers/plans/2026-08-18-wiki-v2-visual.md`).
  Sha verificado `0de2f902`: `npm run test:wiki` → RC=0, 51 tests, 145 páginas de `memoria/`. Deja
  lista la base del esquema v2 (manual, lint, backfill, plantillas) pero **no la aplica** sobre las
  fuentes — eso es la Tanda 2, todavía sin arrancar.
- [ ] **migracion-estados** / **apply-recalculo-estados** — migración de estados con verificación en
  `database/migrations/20260819_recalculo_estados.php` y `tests/test_global_table_reconciliation.php`
  respectivamente. Sin plan/fase asociados aún (`goal.md` con `Plan: -`).
- [ ] **estados-fuera-de-ventana** — decisiones en `decisiones/estados-fuera-de-ventana-ejecutor.md`.

## Diferibles

- [ ] **Proponer verificación de tests en contenedor como config por proyecto.** La vía Docker se
  quitó del gate global de `~/.claude` el 2026-08-19; este repo es 100% dockerizado (`app`/`db`/
  `adminer`, sin PHP en el PATH del host) y su `verify.quick` en `.claude/gate.yaml` evita
  deliberadamente PHP/Docker por costo, pero el resto de la suite (PHPUnit, PHPStan, tests puros) sí
  necesita el contenedor. Diferido porque afecta la config del gate global, no solo este repo — se
  difiere a una decisión de Felipe sobre `~/.claude`, no un fix local.
- [ ] **Fusionar contenido solapado de `AGENTS.md`/`GEMINI.md`/`CLAUDE.md`** (constitución operativa
  de los agentes) con lo que ahora vive en [[README]]/[[ROADMAP]] tras el bootstrap de la wiki LLM —
  no se tocó su contenido en este bootstrap, solo se enlazó.
- [ ] **Backlog Fase 7-10** (notificaciones por rol, QA sistemático, despliegue gradual, shared
  schema): sin frente abierto todavía. Ver [[ROADMAP]] tabla de fases.
- [ ] Realces sin declarar (r0 de Programa General y ruta crítica de Programación Semanal) como
  decisión única de producto — anotado en la cola de decisiones del usuario
  ([[docs/decisiones-pendientes]]), pendiente de Felipe, sin prisa.

## Hechas (últimas 10)

- [x] 2026-08-19 — Andamiaje de los tres frentes que arrancan en paralelo entra en `main` (`720b27b9`).
- [x] 2026-08-19 — Fix del invariante de montaje de `publicar.sh`: el invariante es el montaje, no
  el nombre del proyecto compose (`b334604e`).
- [x] 2026-08-19 — Spec y plan de los tres frentes (BI, runtime-budgets, coloreado por severidad)
  documentados (`6abe2436`).
- [x] 2026-08-19 — Fuente única de las 22 fases del pendiente general; lo verificado se archiva
  (`fc098810`).
- [x] 2026-08-19 — Los goals dejan de escaparse del control de versiones (`9711ae3f`).
- [x] 2026-08-19 — Wiki: Fase 0 registrada como hecha con su verificación (`1eef8e14`).
- [x] 2026-08-19 — Tablero de control, decisiones del 18-ago y spec+plan de la wiki v2 (`613decb2`).
- [x] 2026-08-04 — Retiro completo del PDC v1 (Listado/Contratos/`/pdc`, 18 tablas); sucesor PDC v2.
- [x] 2026-08-19 — Correo de recuperación de contraseña sale por el MTA local del hosting, no por
  relay externo (`21243c7e`).
- [x] 2026-08-19 — Bootstrap de la wiki LLM de 5 archivos en la raíz del repo (este cambio).
