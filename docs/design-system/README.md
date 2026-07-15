# Design system innegociable AIA

Este directorio define la parte versionada del design system. Stitch y `docs/brand/` son insumos visuales; el contrato ejecutable vive en el repo.

## Reglas innegociables

- Usar tokens `--ds-*` y `--aia-*`; no introducir colores hex sueltos en modulos migrados.
- Usar Montserrat para titulos, metricas y jerarquia de alto impacto.
- Usar Inter para cuerpo, navegacion, formularios, tablas, grillas y ayudas.
- Mantener densidad operativa: la UI debe ayudar a leer, decidir y actuar.
- Aplicar mobile-first, desktop denso, modo linen y modo dark reales.
- Respetar WCAG AA, focus visible, targets tactiles de 44px y `prefers-reduced-motion`.
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
- `runtime-budget.schema.json`, `runtime-baseline-0.3.3.json` y `runtime-measurements/0.3.3-retrospective.json`: contrato fail-closed, referencia histórica pendiente de aprobación y medición retrospectiva trazable. La medición usa tres muestras sobre la fixture sanitizada compartida y no adquiere tolerancias ni aprobación por existir.
- `tests/browser/__screenshots__/design-system-lab.visual.mjs/`: 60 goldens por familia, tema y viewport.
- `tests/browser/__screenshots__/programa-general.visual.mjs/`: 6 goldens sanitizados del piloto.
- `docker-compose.ci.yml`: runtime aislado con base sanitizada; nunca reutiliza datos productivos.
- `contracts/`: reglas ejecutables de gobierno, migración y cierre.
- `manual-accessibility-review.md`: checklist humano bloqueante para Accessibility Insights, teclado, VoiceOver, zoom y reflow.

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
npm run test:keyboard
npm run test:reflow
npm run test:design-system:runtime
```

El gate runtime recorre diez familias y el piloto Programa General en dark y
linen a 390x844, 1180x820 y 1440x900. Las animaciones se desactivan y Chromium
compara los 66 goldens. Actualizar snapshots exige una decisión visual aprobada;
CI nunca usa `--update-snapshots`.

CI construye una imagen aislada con dependencias de análisis, mientras la imagen
normal conserva `--no-dev`. Su base nace del schema global sin datos y añade solo
usuarios, proyecto, semana, membresías y una fila determinista de Programa General;
al cerrar elimina el volumen.

`test:reflow` añade un precheck a 320 CSS px para las diez familias y Programa
General en ambos temas. No reemplaza zoom 200% ni la revisión humana de reflow.

`test:keyboard` comprueba el orden de tabulación, el foco visible y el ciclo
abrir–cerrar–devolver foco del diálogo piloto. No reemplaza la revisión manual
completa con teclado.

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
