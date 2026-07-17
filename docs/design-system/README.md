# Design system innegociable AIA

Este directorio define la parte versionada del design system. Stitch y `docs/brand/` son insumos visuales; el contrato ejecutable vive en el repo.

## Reglas innegociables

- Usar tokens `--ds-*` y `--aia-*`; no introducir colores hex sueltos en modulos migrados.
- Usar Montserrat para titulos, metricas y jerarquia de alto impacto.
- Usar Inter para cuerpo, navegacion, formularios, tablas, grillas y ayudas.
- Mantener densidad operativa: la UI debe ayudar a leer, decidir y actuar.
- Aplicar mobile-first, desktop denso, modo linen y modo dark reales.
- Diseñar según los criterios accesibles definidos para el alcance, con foco visible, targets tactiles de 44px y `prefers-reduced-motion`.
- Usar glass solo para jerarquia: shell, nav, modales, paneles y cards. Tablas y grillas priorizan legibilidad.
- No crear componentes visuales ad hoc en modulos migrados. Registrar excepciones en `exceptions.json`.

## Archivos canonicos

- `version.json` y `CHANGELOG.md`: SemVer y evolución contractual.
- `component-catalog.json`: inventario ejecutable de componentes y familias.
- `stable-api-1.0.0.json`: enumeración exacta de la API `stable` candidata a la
  garantía 1.0.0. Permanece `pending-gates` mientras la versión esté en
  construcción o exista cualquier gate de cierre sin aprobar.
- `homologation.json`: candidatos, escenarios y matriz visual por familia. Cuando una familia declara `activeCandidate`, el laboratorio muestra su estado real; una base aprobada no hereda aprobación a una variante activa distinta.
- `family-approvals.json`: aprobaciones humanas trazables con evidencia.
- `vendors.json` y `legacy-aliases.json`: dependencias y puente de compatibilidad.
- `manifests/`: consumo por módulo; Programa General es el único piloto completo.
- `public/css/tokens.css`: tokens base AIA y alias semanticos `--ds-*`.
- `public/css/aia-design-system.css`: componentes y utilidades `aia-*`.
- `public/js/modules/aia_ui/theme-bootstrap.js`: aplica dark por defecto o la preferencia persistida antes de la primera hoja de estilo y evita flash de tema.
- `public/js/modules/aia_ui/theme.js`: API interactiva linen/dark y reduced motion después del bootstrap.
- `scripts/design-system-audit.mjs`: gate estatico de deuda visual.
- `audit-baseline.json` y `exceptions.json`: deuda congelada y excepciones exactas.
- `phpstan-baseline.json`: cinco fingerprints legacy tolerados; cualquier hallazgo nuevo bloquea CI.
- `runtime-budget.schema.json`, `runtime-baseline-0.3.3.json`, `runtime-measurements/0.3.3-retrospective.json` y `runtime-measurements/0.3.3-recovery-manifest.json`: contrato fail-closed, presupuesto aprobado y retrospectiva histórica portable pero incompleta. La retrospectiva usa `sourceRef:null` y `rawSamplesPreserved:false`; el manifiesto comprometido verifica sus resúmenes recuperados sin depender de un objeto o checkpoint Git oculto. El gate actual permanece pendiente hasta obtener tres muestras frescas desde un `HEAD` limpio, con recibos verificables, y compararlas contra los límites aprobados.
- `tests/browser/__screenshots__/design-system-lab.visual.mjs/`: 60 goldens por familia, tema y viewport.
- `tests/browser/__screenshots__/programa-general.visual.mjs/`: 6 goldens sanitizados del piloto.
- `docker-compose.ci.yml`: runtime aislado con base sanitizada; nunca reutiliza datos productivos.
- `contracts/`: reglas ejecutables de gobierno, migración y cierre.
- `manual-accessibility-review.md`: contrato bloqueante de revisión automatizada básica
  con Accessibility Insights y evidencia separada para laboratorio, piloto y
  estados revelados.

## API inicial de clases

- `aia-shell`
- `aia-page`
- `aia-section`
- `aia-card`
- `aia-panel`
- `aia-glass`
- `aia-toolbar`
- `aia-btn`
- `aia-input`
- `aia-select`
- `aia-textarea`
- `aia-table-shell`
- `aia-grid-shell`
- `aia-modal-surface`
- `aia-chip`
- `aia-alert`
- `aia-empty`
- `aia-visually-hidden`

## Gate

Ejecutar:

```bash
npm run test:design-system:static
npm run test:design-system:phpstan
npm run test:design-system:runtime
```

La evidencia no bloqueante se ejecuta por separado:

```bash
npm run test:design-system:evidence
```

El gate runtime recorre diez familias y el piloto Programa General en dark y
linen a 390x844, 1180x820 y 1440x900. Las animaciones se desactivan y Chromium
compara los 66 goldens. Actualizar snapshots exige una decisión visual aprobada;
CI nunca usa `--update-snapshots`.

CI construye una imagen aislada con dependencias de análisis, mientras la imagen
normal conserva `--no-dev`. Su base nace del schema global sin datos y añade solo
usuarios, proyecto, semana, membresías y una fila determinista de Programa General;
al cerrar elimina el volumen.

`test:design-system:evidence` ejecuta teclado y reflow como diagnóstico
no bloqueante. El comando runtime no los invoca; CI tolera su fallo y conserva
los artefactos correspondientes.

Accessibility Insights se registra como tres revisiones automatizadas básicas separadas:
laboratorio, piloto y estados revelados. Cada una requiere cero reglas fallidas y
cero instancias fallidas; el resultado no autoriza una afirmación más amplia.

El gate estatico permite deuda legacy registrada, pero debe fallar si una ruta marcada como migrada supera su baseline o si se introduce deuda visual nueva sin excepcion.
Login, Projects, Programa General, PDC, Contratos, Listado de Actividades y el modulo de tema tienen presupuesto cero por ruta para hex sueltos, inline styles, bloques `<style>`, Roboto y radios hardcodeados.

Cobertura automática del Sprint 00:

- Assets canonicos: tokens, CSS del sistema y API de tema.
- Shell autenticado.
- Laboratorio protegido: diez familias, dos temas y tres viewports.
- Programa General: único piloto, con componentes canónicos, axe y goldens.
- Los demás módulos permanecen fuera de la migración y solo conservan sus
  contratos preexistentes hasta un sprint posterior.

El baseline no se regenera libremente. Todo cambio requiere hashes before/after,
excepción exacta y aprobación explícita; el auditor bloqueante se implementa en Step 2.
