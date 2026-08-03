---
tipo: trampa
estado: vigente
fecha: 2026-07-29
areas: [qa]
fuente: memoria-claude
origen: lps-aia-suite-php-rojos-preexistentes
resumen: "Los rojos preexistentes de la suite PHP autoejecutable (tests/test_*.php): 16/103 en pdc-a4-fechas, pero solo 4/108 en main @1a75b19 — no cites la cifra vieja sin re-medir; y las dos trampas al medirlos en macOS"
---
Medido el 2026-07-28 en el worktree `lps-aia-pdc`, rama `pdc-a4-fechas` @ `2357b0a`, con árbol
revertido a HEAD: de los 103 `tests/test_*.php`, **16 fallan sin que nadie los haya tocado**.

- `rc=255` (fatal, 5): `test_bi_filters_apply_to_charts`, `test_bi_programa_general_activity_timeline`,
  `test_bi_programa_general_chart_values`, `test_bi_programa_general_cnc`, `test_bi_programa_general_cnp`.
- `rc=1` (aserciones, 11): `test_completion_audit_goal_coverage`, `test_goal_close_blockers_manifest`,
  `test_goal_close_readiness_script`, `test_human_decision_actions_package`,
  `test_human_decision_approval_checklist`, `test_human_decision_matrix_coverage`,
  `test_human_validation_matrix`, `test_learning_persistence_catalog_db`,
  `test_pdc_three_projects_perfect_20260702`, `test_report_processor_cic_project_scope`,
  `test_review_required_families_block_auto_apply`.

`test_pdc_three_projects_perfect_20260702` falla por dato/fecha, no por código: exige semanas activas
que cubran **2026-07-02**, una fecha ya pasada, en Da Porto, Milán T19 y Aeropuerto JMC.

`test_pdc_v2_import_flujo` NO está en la lista pero falló en la primera pasada de un lote y luego pasó
3/3: es dependiente de orden/estado de BD, no un rojo estable. Si aparece suelto, re-córrelo antes de
diagnosticar.

**Re-medido el 2026-07-29 en el worktree principal, rama `main` @ `1a75b19`, árbol limpio: son 4 de 108,
no 16 de 103.**

> **Universo medido el 2026-08-03:** `ls tests/test_*.php | wc -l` da **126** archivos, no 108. La
> cifra de fallos de arriba es de `main@1a75b19` (2026-07-29) y **no se ha vuelto a medir**. Cítala
> siempre con su fecha, o vuelve a correr la suite.

La cifra de arriba es de una rama, y sobre `main` la mayoría ya estaba resuelta —así que
**la lista de 16 no sirve como línea base de `main`**. Los 4 de `main`: `test_pdc_phpstan_nivel6` (roto
de verdad, arreglado en `88c37b8`), `test_pdc_v2_brecha_daporto` (obsoleto: fija la versión 292 de Da
Porto, que desapareció al reimportarse el presupuesto el 2026-07-29; se dejó rojo a propósito),
`test_human_validation_matrix` (obsoleto y contradictorio consigo mismo) y
`test_report_processor_cic_project_scope` (ambiental, datos del proyecto 73). Diagnóstico por escrito en
`goals/pdc-preparar-b1/evidence/cierre-prelanzamiento-2026-07-29.md`.

Dos trampas al medir esto desde macOS/zsh:
1. `timeout` **no existe** en macOS — `timeout 180 docker …` devuelve `rc=127` en los 103 y parece una
   catástrofe. Usa `gtimeout` o ninguno.
2. Contar fallos con `grep -cE "^FAIL"` da 0 falsos: las líneas `FAIL:` del runner van **sangradas**.
   Mide siempre por código de salida (`rc`), nunca por grep del texto.

**Why:** sin esta línea base es imposible saber si un rojo es tuyo; las dos trampas de medición
producen lecturas que parecen regresiones masivas o suites impolutas, ambas falsas. **How to apply:**
antes de atribuirte un fallo, revierte tus archivos a HEAD (`git checkout HEAD -- <archivos>`, nunca
`git stash` en este worktree: suele haber ediciones ajenas en curso) y re-mide por `rc`. Relacionado:
[[branch-preexisting-red-gates]], [[pdc-e2e-sandbox]].
