---
tipo: concepto
estado: vigente
fecha: 2026-08-04
areas: [design-system, qa]
fuente: scripts/design-system-audit.mjs, tests/design-system/runtime-budget.test.mjs, scripts/design-system-phpstan-baseline.mjs
resumen: "Una baseline congela el desorden existente para que solo lo nuevo bloquee; un presupuesto fija un techo absoluto; cada uno tiene su gate y su archivo"
---
# Baselines y presupuestos: congelar lo viejo, acotar lo nuevo

El design system usa dos herramientas parecidas que conviene no confundir:

- **Una baseline congela el desorden que ya existía.** Registra cuántas violaciones hay hoy para
  que el gate solo falle si aparecen **más**. No absuelve lo viejo: lo deja contado y vigilado.
- **Un presupuesto fija un techo absoluto.** No importa la historia: si la medición supera el
  techo, falla.

Quién es quién, con su consumidor verificado:

| Archivo | Tipo | Qué bloquea |
|---|---|---|
| `audit-baseline.json` | baseline | Más hallazgos de estilo (hex, inline, fuera de capa) que los contados por regla — `scripts/design-system-audit.mjs:11,338-352` |
| `phpstan-baseline.json` | baseline | Cualquier fingerprint de PHPStan nuevo; hoy tolera **cero** (`"fingerprints": []`, verificado el 2026-08-10) — `scripts/design-system-phpstan-baseline.mjs:79-91`. Las cinco legacy que tenía (de `ListadoActividadesApiController` y `SemiAutoService`) se retiraron en `9011c99c` al borrarse esos archivos con el PDC v1 |
| `a11y-baseline.json` | baseline | Hoy exige `fingerprints` **vacío**: tolerancia cero, todo pasa por excepción — `tests/design-system/accessibility.test.mjs:225` (verificado el 2026-08-07: el archivo sigue con `"fingerprints": []`) |
| `lab-performance-budget.json` | presupuesto | Cada métrica del laboratorio (FCP, CLS, long tasks, peso CSS) contra su techo — `tests/browser/design-system-lab.performance.mjs:11,168` |
| `runtime-baseline-0.3.3.json` | baseline con tolerancias | El peso runtime de Programa General (CSS/JS gzip, adapters) contra lo aprobado más su margen — `tests/design-system/runtime-budget.test.mjs:68,76-92`, corrido por `npm run test:runtime-budget:check` |

**Las baselines llevan candado.** `audit-baseline.json` está protegida por hash con aprobación en
`baseline-approvals/`, y `runtime-baseline-0.3.3.json` registra `approval`, `sourceTreeHash` y
manifiestos `sha256` de recuperación: regenerarlas para forzar un verde no es un atajo disponible,
es una violación de contrato (AGENTS.md lo prohíbe explícitamente).

**Hallazgo del 2026-08-04, deuda conocida:** `lab-performance-baseline.json` existe en disco
(10 KB, del 19 de julio) pero **ningún script ni test lo lee** — el único consumidor de performance
del laboratorio es `lab-performance-budget.json`. Solo lo nombra `docs/design-system/README.md:62`.
Mismo patrón que [[comentario-de-token-afirma-uso-inexistente]]: documentado no implica cableado.

## Dónde se rompe esto en la práctica

- [[gate-estatico-no-ve-tokens-rotos]] — las baselines de lectura de archivos no ven valores
  resueltos; el rojo real solo aparece en navegador.
- [[visual-baselines-estado-real]] — las baselines visuales del laboratorio están rojas de fondo:
  mide el delta antes de culparte.
- [[gate-visual-tolerancia-enganosa]] — la tolerancia de píxeles absorbe más de lo que parece.
- [[branch-preexisting-red-gates]] — rojos preexistentes que no son tuyos.

Mapa del área: [[design-system]] · vecino: [[qa-y-gates]].
