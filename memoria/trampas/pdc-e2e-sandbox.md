---
tipo: trampa
estado: vigente
fecha: 2026-07-28
areas: [pdc, qa]
fuente: memoria-claude
origen: lps-aia-pdc-e2e-sandbox
resumen: Los e2e del PDC v2 corren contra el proyecto sacrificable 990100 y exigen E2E_BASE_URL=8091 en el worktree lps-aia-pdc
---
Desde 2026-07-28 los specs `tests/browser/pdc-v2-*.spec.mjs` ya no escriben en Da Porto
(project_id=73): usan el proyecto sacrificable **990100 «PDC Sandbox E2E»**, que siembra y resetea
`database/seeds/pdc_e2e_sandbox_project.php` (invocado desde `tests/browser/support/pdc-sandbox.mjs`
en un `beforeEach`). El gate `PDC_E2E_DESTRUCTIVO` desapareció.

**Why:** el gate dejaba la cobertura e2e del módulo en cero; el sandbox la devuelve sin arriesgar
datos reales.

**How to apply:**
- En el worktree `/Volumes/Crucial X6/Developer/lps-aia-pdc` **hay que exportar
  `E2E_BASE_URL=http://localhost:8091`**: el default de `tests/browser/fixtures/projects.mjs` es
  8081, que es el stack `last-planner-aia` (otro worktree, otra BD). Sin la variable, los tests
  corren contra el stack equivocado sin avisar.
- Lo que los specs escriben en catálogos GLOBALES (`general_maestro_insumos`,
  `general_paquetes_contratacion`) se aísla por nomenclatura, no por proyecto: prefijo `ZZTEST`
  (y `E2E ` para paquetes). El reseteo del sandbox borra esas filas si nadie las referencia.
- `--purge` desmonta el sandbox entero.

Relacionado: [[tests-browser-allowlist]], [[captura-playwright-miente]].
