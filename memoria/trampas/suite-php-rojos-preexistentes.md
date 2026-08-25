---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-07-29
areas: [qa]
fuente: memoria-claude
origen: lps-aia-suite-php-rojos-preexistentes
resumen: "Rojos preexistentes de tests/test_*.php: 4/108 en main @1a75b19 (2026-07-29); universo re-medido el 2026-08-11 en 101 archivos (96 el 2026-08-10, tras el retiro del PDC v1) — cita siempre la fecha o re-mide; y las dos trampas al medirlos en macOS"
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
>
> **Re-medido el 2026-08-24 sobre `a4f19884`: da 117** (puro 29, db 52, http 5, datos-proyecto 31).
> Entraron, entre otros, `test_bi_lienzo_por_rol.php` y `test_bi_alcance_responsables.php`. La cifra
> de *fallos* no se re-midió sobre ese universo; lo que sí cambió el 2026-08-24 es que **ya no hay
> sospechosos**: el runner dejó de juzgar en inglés lo que la suite anuncia en español.
>
> **Re-medido el 2026-08-11 sobre `123a8bff`: daba 101.** El 2026-08-10 daba **96**, un 24 % menos que el
> 2026-08-03. El retiro del PDC v1 del 2026-08-04 se llevó varios tests por delante; el universo de
> 96 ya incluye `tests/test_password_reset_resultados.php`, creado ese mismo día. La cifra de
> fallos sigue sin re-medirse sobre este universo — solo cambia cuántos archivos hay.

> **Re-medido el 2026-08-25 sobre `410ac132`, árbol limpio, nivel `puro`: 26 pasan, 3 fallan de 29**
> (más 4 clases PHPUnit en verde; 89 omitidos por nivel, 118 descubiertos). Los tres:
> `test_legacy_csrf_guard` (rc=1, `FAIL token válido pasa el guard`) — llega el mismo día en que se
> cerró el frente de CSRF de `LpsApiController`, así que **muy probablemente es trabajo en curso**,
> no un rojo estable; y `test_pdc_v2_import_parser` + `test_pdc_v2_maestro_sinco_parser` (rc=255),
> **ambientales por una causa que esta página no tenía**: `/tmp` está montado de **solo lectura**
> dentro del contenedor, y los dos escriben ahí su fixture `.xlsx` vía PhpSpreadsheet. No es dato ni
> código: es el montaje. Si los ve en rojo, no los diagnostique desde cero.
>
> **Tercera trampa de medición, cazada en esta misma pasada:** `… | tail -30; echo "RC=$?"` reporta
> el rc de `tail`, no el de la suite — dio `RC=0` con tres rojos delante. Redirija a archivo y lea
> `$?` en su propia línea, o en zsh use `$pipestatus[1]` (minúscula y 1-indexado).

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

## La cifra se ha movido tres veces en 24 horas

126 → 96 → 99 → **101**. El retiro del PDC v1 se llevó treinta; el frente de seguridad y el del
runner trajeron cinco y una. Cada una de esas cifras fue correcta al medirla.

Por eso esta página insiste en citar la fecha **y el commit**: una afirmación sobre el tamaño de la
suite sin sha no es verificable y caduca en horas, no en meses. Es el mismo motivo por el que
`docs/coordinacion-sesiones.md` exige que toda afirmación sobre `main` viaje con su sha.

