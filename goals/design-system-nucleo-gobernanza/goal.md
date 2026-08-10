# Núcleo y gobernanza del Design System AIA

Continuar y cerrar el Sprint 00 transversal —sin reiniciarlo, crear otro Epic ni rehacer el trabajo ya válido— para convertir el design system AIA en una fuente de verdad global, versionada y ejecutable, con homologación previa de cada familia visual, cascada determinista, tokens y componentes canónicos, adaptadores, laboratorio protegido, gates continuos y Programa General como único piloto. La evaluación accesible se gobierna con Playwright + `@axe-core/playwright`, baseline y excepciones verificables, más revisiones automatizadas básicas separadas de Accessibility Insights para laboratorio, piloto y estados revelados; no mediante un framework frontend nuevo.

El prerrequisito Git consiste exclusivamente en preservar y aislar el trabajo preexistente mediante una maniobra aprobada; no exige terminar, corregir ni aprobar visualmente módulos consumidores. Durante el Sprint 00 las aprobaciones visuales se concentran en las familias del laboratorio y en Programa General.

La comprensión compartida y verificable está en `facts.md`. El orden de implementación, archivos, pruebas, revisiones y riesgos está en `plan.md`. Estos tres archivos son la única autoridad de ejecución del objetivo; entrevistas y resultados anteriores son procedencia histórica y no pueden dirigir mutaciones.

La ejecución debe seleccionar y usar de forma explícita las skills, plugins, MCPs, conectores, hooks y subagentes disponibles que aporten calidad, velocidad, evidencia o seguridad material al paso activo. El catálogo efectivo del runtime, no un inventario histórico, determina qué está disponible. Cada selección debe ser mínima, justificada, proporcional al riesgo y producir evidencia verificable; ninguna capacidad instalada, hook o subagente amplía alcance, permisos ni autorización de cambios externos.

Done condition: el trabajo preexistente fue preservado y aislado fuera del sprint sin convertir ese prerrequisito en sprints por módulo; todos los hechos vigentes de `facts.md` están verificados; cada fase deja trazabilidad de sus capacidades seleccionadas, su evidencia y sus fallback; cada ficha del catálogo declara madurez y solo la API `stable`, ejercida por laboratorio y Programa General, entra en la garantía SemVer `1.0.0`; los quince gates exactos de `closeout-evidence.json` tienen evidencia fresca y estado `passed`; los datos de prueba quedaron restaurados; el usuario aprobó la revisión consolidada del laboratorio y del piloto; la revisión local quedó resuelta; el milestone quedó en una serie de commits coherentes más un commit de release, sin push, deploy ni activación de branch protection, y PDC queda listo como prueba posterior de reutilización y retorno. Teclado y reflow permanecen como evidencia no bloqueante y no activan la versión.

---

## Por qué sigue abierto — 2026-08-10

`docs/design-system/closeout-evidence.json` declara los 15 gates `passed` con `generatedAt: 2026-07-15`
y `designSystemVersion: 1.1.0` — versión que se publicó el 2026-08-07 (`a5223a0c`), tres semanas
**después** de la evidencia que dice avalar. Peor aún: los 15 artefactos citados en
`docs/design-system/evidence/*.json` no son recibos de ejecución — son stubs literales
(`{"gateId": "...", "result": "passed"}`, 47–60 bytes cada uno) cuyo hash coincide con lo declarado
porque nunca fueron otra cosa. `validation-log.md` lo confirma línea por línea: casi todas las filas
dicen «el closer final debe ejecutarlos sobre el candidato exacto» o «superseded». No es evidencia
vieja, es evidencia que nunca se produjo. Esta sesión midió los 15 gates contra el HEAD real de hoy
(`97e6abef`) y ninguno cae limpiamente en «verificado y pasa»: **el goal NO se cierra.**

Clasificación gate a gate (comandos y salidas de esta sesión, 2026-08-10):

**Ejecutados hoy — PASAN de verdad:**
- `static` — `npm run test:design-system:static`, exit 0, resumen 8/8 (`entrypoint-partition`,
  `unlayered-delivery`, `bi-utilities`, `table-contract`, `node-tests`, `contracts`,
  `consumer-contract`, `audit`).
- `global-table-safety` — `docker compose exec app php tests/test_global_table_safety.php`, exit 0,
  `=== Global Table Safety: OK ===`.

**Ejecutados hoy — FALLAN (evidencia real, contradice el `passed` declarado):**
- `runtime` — `npm run test:design-system:runtime`, exit ≠0: 2 fallos de 31
  (`design-system-lab.mjs:252` severity/urgency backgrounds, `design-system-lab.mjs:375` sidebar
  brand-mark filter). El chain `&&` del script nunca llegó a correr `test:a11y:lab`,
  `test:visual:lab` ni `test:performance:lab`, así que tampoco hay evidencia fresca de esas tres
  capas.
- `phpstan-scoped` — `docker compose exec app vendor/bin/phpstan analyse src --memory-limit=1G`,
  exit 1, «Found 7 errors».
- `phpstan-global` — `npm run test:design-system:phpstan`, exit 1, «New PHPStan findings: 7».
  Causa raíz encontrada y documentada en `facts.md`: `docs/design-system/phpstan-baseline.json`
  sigue esperando 5 huellas sobre `ListadoActividadesApiController.php` y `SemiAutoService.php`,
  archivos que ya no existen desde la eliminación del PDC v1 (2026-08-04). El baseline del design
  system quedó huérfano de esa eliminación.
- `git-preservation` — `npm run test:design-system:preservation`, exit 1, «Worktree preservation:
  FAIL» (repository/committedWork/unstaged/status cambiaron). El snapshot de
  `worktree-preservation.json` es del arranque del Sprint 00; con 1352 commits de distancia entre
  ese punto y HEAD, el gate falla por diseño — no es un gate re-ejecutable en este punto del
  proyecto, es un candado de un solo uso que ya se disparó.

**No ejecutables en esta sesión (fixture, consentimiento de mutación o aprobación humana):**
- `runtime-budgets` — intentado (`npx playwright test tests/browser/design-system-runtime-budget.mjs`);
  falla con «CI_GIT_SHA must match the current clean worktree»: el comparador exige contexto de
  procedencia de CI que no existe en una corrida local ad hoc. Coincide con `facts.md`, que ya
  declaraba este gate «pendiente hasta recolectar tres muestras frescas».
- `pg-roles`, `pg-persistence`, `data-restoration` — las tres apuntan al mismo comando
  (`npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1`), que aborta con «Missing
  isolated E2E database mutation consent: set E2E_REQUIRE_ISOLATED_DB=1 and
  E2E_ALLOW_DB_MUTATION=design-system-ci». Es un candado fail-closed intencional contra mutación de
  datos sin consentimiento explícito; activarlo por cuenta propia en una tarea de medición no está
  autorizado.
- `consolidated-lab`, `consolidated-pilot`, `review` — sus comandos declarados son literalmente
  `local-review ...`: revisión humana en navegador, no automatizable desde esta sesión. `review`
  además no tiene sobre qué operar hoy: no hay un diff de release en preparación.
- `atomic-commit` — `git diff --cached --check` corrió y dio exit 0, pero `git status --short` está
  vacío: no hay nada staged. El resultado es verdaderamente vacío, no evidencia sobre un commit de
  release real.

**Caducado (el recibo no corresponde a nada ejecutable):**
- `accessibility-insights` — el comando registrado, `accessibility-insights basic-automated-review`,
  no es un binario instalado (`which accessibility-insights` no lo encuentra) ni un script del
  repo. No hay evidencia de que este comando haya sido ejecutable alguna vez en este entorno; el
  recibo `passed` de julio no puede provenir de una ejecución real.

**Total:** 2 pasan hoy, 4 fallan hoy con evidencia real, 8 no son ejecutables en esta sesión, 1 es un
recibo sin comando real detrás. Cero de los 15 quedan en «verificado y pasa» de forma completa.

**Qué haría falta para cerrar, y de quién es ese trabajo:**
1. Sincronizar `docs/design-system/phpstan-baseline.json` con la eliminación del PDC v1 (quitar las
   5 huellas de `ListadoActividadesApiController`/`SemiAutoService`) y triage de los 2 hallazgos
   reales nuevos — trabajo de higiene de PHPStan, no de este goal.
2. Investigar y corregir los 2 fallos reales de `design-system-lab.mjs` (severity/urgency
   backgrounds, sidebar brand-mark filter) antes de poder declarar `runtime` en verde — trabajo de
   design system, probablemente F2a/regresión visual.
3. Decidir si `git-preservation` se re-arma con un nuevo snapshot de referencia o se retira como
   gate de cierre recurrente — es un candado de un solo uso, no algo que un closer pueda volver a
   pasar sin rediseñarlo.
4. Ejecutar `runtime-budgets` dentro de un pipeline de CI real (con `CI_GIT_SHA` y demás contexto de
   procedencia) para producir las tres muestras frescas que `facts.md` ya exige.
5. Obtener consentimiento explícito y ejecutar `full-app-flow.spec.mjs` con
   `E2E_REQUIRE_ISOLATED_DB=1`/`E2E_ALLOW_DB_MUTATION=design-system-ci` para `pg-roles`,
   `pg-persistence` y `data-restoration`, con restauración verificada después.
6. Obtener la aprobación humana real de `consolidated-lab`, `consolidated-pilot` y `review` sobre el
   árbol exacto que se vaya a etiquetar como release, y un `atomic-commit` corrido sobre ese diff
   staged real — no sobre un working tree vacío.
7. Decidir qué reemplaza a `accessibility-insights`: o se instala/adopta la herramienta real, o se
   sustituye el gate por uno con comando ejecutable y se corrige el registro en
   `scripts/design-system-gate-command-registry.mjs`.

Ninguno de estos seis puntos se resuelve regenerando `closeout-evidence.json` — eso solo produciría
otra identidad auto-declarada. `docs/design-system/closeout-evidence.json` y `version.json` siguen
sin tocar en esta sesión.

---

## Archivos de este goal

[[goals/design-system-nucleo-gobernanza/facts|facts.md]] · [[goals/design-system-nucleo-gobernanza/plan|plan.md]] · [[goals/design-system-nucleo-gobernanza/validation-log|validation-log.md]]

Estado y relación con los demás goals: [[estado|Estado de los goals]].
