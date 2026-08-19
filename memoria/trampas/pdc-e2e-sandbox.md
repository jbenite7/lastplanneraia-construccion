---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-07-28
areas: [pdc, qa]
fuente: memoria-claude
origen: lps-aia-pdc-e2e-sandbox
resumen: Los e2e del PDC v2 corren contra el proyecto sacrificable 990100; el puerto del stack ya se autodetecta y E2E_BASE_URL solo hace falta si Docker no responde
---
Desde 2026-07-28 los specs `tests/browser/pdc-v2-*.spec.mjs` ya no escriben en Da Porto
(project_id=73): usan el proyecto sacrificable **990100 «PDC Sandbox E2E»**, que siembra y resetea
`database/seeds/pdc_e2e_sandbox_project.php` (invocado desde `tests/browser/support/pdc-sandbox.mjs`
en un `beforeEach`). El gate `PDC_E2E_DESTRUCTIVO` desapareció.

**Why:** el gate dejaba la cobertura e2e del módulo en cero; el sandbox la devuelve sin arriesgar
datos reales.

**How to apply:**
- **Ya no hace falta exportar `E2E_BASE_URL` a mano** (corregido el 2026-08-06): el default fijo de
  8081 que hacía que un worktree atacara el stack del vecino se sustituyó por
  `tests/browser/fixtures/base-url.mjs:23-37`, que pregunta el puerto del stack de ESTE working
  tree con `docker compose port app 80` y sólo cae a 8081 si ese comando falla. Exporta la variable
  únicamente cuando Docker no responda desde ese `cwd`.
- Lo que los specs escriben en catálogos GLOBALES (`general_maestro_insumos`,
  `general_paquetes_contratacion`) se aísla por nomenclatura, no por proyecto: prefijo `ZZTEST`
  (y `E2E ` para paquetes). El reseteo del sandbox borra esas filas si nadie las referencia.
- `--purge` desmonta el sandbox entero.

Relacionado: [[tests-browser-allowlist]], [[captura-playwright-miente]].
