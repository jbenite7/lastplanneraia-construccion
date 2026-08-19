---
capa: fuente
tipo: guia
estado: vigente
fecha: 2026-07-15
areas: [design-system]
fuente: docs/design-system/README.md
resumen: Este directorio define la parte versionada del design system. Stitch y docs/brand/ son insumos visuales; el contrato ejecutable vive en el repo.
---

# Design system innegociable AIA

Este directorio define la parte versionada del design system. Stitch y `docs/brand/` son insumos visuales; el contrato ejecutable vive en el repo.

## Reglas innegociables

- Usar tokens `--ds-*` y `--aia-*`; no introducir colores hex sueltos en modulos migrados.
- Usar Montserrat para titulos, metricas y jerarquia de alto impacto.
- Usar Inter para cuerpo, navegacion, formularios, tablas, grillas y ayudas.
- Mantener densidad operativa: la UI debe ayudar a leer, decidir y actuar.
- En este repositorio, dark es el tema por defecto y el que se valida; `1180x820` es
  el viewport canónico de validación. `linen` fue retirado del producto en F0 del goal
  `dark-mode-todos-los-modulos` y no existe conmutador de tema: un tema claro no está
  prohibido, pero hay que reconstruirlo.
- Diseñar según los criterios accesibles definidos para el alcance, con foco visible, targets tactiles de 44px y `prefers-reduced-motion`.
- Usar glass solo para jerarquia: shell, nav, modales, paneles y cards. Tablas y grillas priorizan legibilidad.
- No crear componentes visuales ad hoc en modulos migrados. Registrar excepciones en `exceptions.json`.

## Archivos canonicos

- `DESIGN.md` (raíz): guía de consumo para desarrolladores; no es fuente de verdad, apunta a este directorio.
- `version.json` y `CHANGELOG.md`: SemVer y evolución contractual.
- `component-catalog.json`: inventario ejecutable de componentes y familias.
- `stable-api-1.0.0.json`: enumeración exacta de la API `stable` candidata a la
  garantía 1.0.0. Permanece `pending-gates` mientras la versión esté en
  construcción o exista cualquier gate de cierre sin aprobar.
- `homologation.json`: candidatos, escenarios y matriz visual por familia. Cuando una familia declara `activeCandidate`, el laboratorio muestra su estado real; una base aprobada no hereda aprobación a una variante activa distinta.
- `family-approvals.json`: aprobaciones humanas trazables con evidencia.
- `vendors.json` y `legacy-aliases.json`: dependencias y puente de compatibilidad.
- `manifests/`: consumo por módulo; Programa General es el único piloto completo.
  `manifests/inventory.json` declara el `status` de cada módulo en su
  arreglo `modules[]` (sin `$schema`, no se valida contra
  `module-manifest.schema.json`, que gobierna manifiestos individuales como
  `auth.json` o `programa-general.json` y no lleva campo `status`). Los
  cuatro valores posibles: `pilot` (migrado por completo, gobernado por un
  manifiesto por módulo — rutas, escenarios, goldens — validado contra
  `module-manifest.schema.json`); `inventory-only` (catalogado pero aún sin
  migrar; sin manifiesto ni cobertura golden); `deferred-last` (programado
  explícitamente para migrar solo después de todos los demás módulos; sin
  manifiesto); `observed-frozen` (bajo observación de auditoría con baseline
  congelado; sin manifiesto y sin presupuesto de rutas — el contador solo
  puede bajar).
- `public/css/tokens.css`: tokens base AIA y alias semanticos `--ds-*`.
- `public/css/design-system/core.css`: componentes y utilidades canónicas
  `aia-*`, compartidas por los entrypoints productivo y del laboratorio.
- `public/css/aia-design-system.css`: entrypoint productivo, temas y puentes
  legacy; importa el core sin redefinir sus primitivas.
- `public/css/design-system/entrypoints/`: partición del entrypoint productivo
  — `core.css` más un adjunto por vendor (`attach-jquery-ui.css`,
  `attach-anychart.css`, `attach-select2.css`, `attach-sweetalert2.css`,
  `attach-handsontable.css`) y `theme-overrides.css` (copia verbatim de los
  bloques inline del agregador, vigilada por el gate de partición). El campo
  `vendors[]` de los manifiestos es ejecutable vía
  `DesignSystemHeadComponent::renderForModule()`, con fallback fail-safe al
  agregador si el manifiesto falta, no parsea o declara un vendor desconocido.
- `public/js/modules/aia_ui/theme-bootstrap.js`: aplica dark por defecto o la preferencia persistida antes de la primera hoja de estilo y evita flash de tema.
- `public/js/modules/aia_ui/theme.js`: aplica dark de forma incondicional (sin conmutador) y reduced motion después del bootstrap.
- `scripts/design-system-audit.mjs`: gate estatico de deuda visual.
- `audit-baseline.json` y `exceptions.json`: deuda congelada y excepciones exactas.
- `phpstan-baseline.json`: cinco fingerprints legacy tolerados; cualquier hallazgo nuevo bloquea CI.
- `runtime-budget.schema.json`, `runtime-baseline-0.3.3.json`, `runtime-measurements/0.3.3-retrospective.json` y `runtime-measurements/0.3.3-recovery-manifest.json`: contrato fail-closed, presupuesto aprobado y retrospectiva histórica portable pero incompleta. La retrospectiva usa `sourceRef:null` y `rawSamplesPreserved:false`; el manifiesto comprometido verifica sus resúmenes recuperados sin depender de un objeto o checkpoint Git oculto. El gate actual permanece pendiente hasta obtener tres muestras frescas desde un `HEAD` limpio, con recibos verificables, y compararlas contra los límites aprobados.
- `lab-performance-budget.json` y `lab-performance-baseline.json`: presupuesto
  y medición reproducible del laboratorio en dark a `1180x820` y `1440x900`.
  El gate conserva tres cargas frías por viewport y no navega al piloto ni usa su
  baseline histórico.
- `tests/browser/__screenshots__/design-system-lab.visual.mjs/`: archivo de
  snapshots del laboratorio. El gate vigente consume solo 18 goldens desktop
  dark (nueve familias con captura en dos viewports); `states-feedback` conserva
  dos contratos visuales sin golden.
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

El laboratorio se valida de forma aislada con Biome:

```bash
npm run check:design-system:biome
```

La regla `noImportantStyles` sigue activa para el laboratorio. Solo los adaptadores
en `public/css/design-system/adapters/` están exentos porque deben fijar la prioridad
frente a CSS de proveedores; ningún otro archivo se excluye del chequeo.

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

### `capture`: cómo se mide un golden contra su viewport

Cada escenario de un manifiesto ata su golden a los píxeles reales del PNG, no
solo al nombre del archivo. El campo `capture` (enum en
`module-manifest.schema.json`) declara cómo debe medirse:

| Valor | Significado | Qué exige el gate |
|---|---|---|
| `viewport` (por defecto, si se omite) | Captura de pantalla completa | El PNG mide **exactamente** el viewport declarado, ancho y alto |
| `element` | Recorte a un elemento | El PNG **no puede ser más ancho** que el viewport; el alto queda sin acotar |

`element` **no es un modo alternativo: es una excepción.** Un recorte a un
elemento con scroll es legítimamente más alto que el pliegue —los dos recortes
reales de `states-feedback` miden 1649 y 1577 px sobre viewports de 820 y 900—,
así que el gate renuncia a comprobar el alto. Una excepción sin lista blanca es
una puerta abierta: bastaría etiquetar cualquier PNG como `element` para
presentar como evidencia una imagen que no corresponde a su escenario.

Por eso los escenarios habilitados están declarados en
`ELEMENT_CAPTURE_ALLOWLIST`, en `scripts/design-system-contracts.mjs`, con clave
compuesta `moduleId/scenarioId` —los ids solo son únicos dentro de un
manifiesto, así que indexar por id suelto dejaba que otro módulo reclamara el id
de un escenario autorizado y heredara la excepción—. Cualquier escenario que
declare `element` sin estar en esa lista **falla el gate**.

Qué califica para entrar en la lista:

1. El golden es un recorte a un elemento concreto, producido por el propio
   runner, y ese elemento excede verticalmente su viewport por diseño.
2. No existe un encuadre a pantalla completa equivalente que sirva de evidencia.
3. El ancho del recorte sigue acotado por el viewport declarado.

Añadirse a la lista **exige revisión explícita**: es un cambio al contrato de
evidencia, no una propiedad que un manifiesto se auto-asigne. Se registra como
decisión en `decisions.md` y se entrega con la prueba que lo vigila
(`tests/design-system/contracts.test.mjs`, mutaciones de lista blanca).

El gate visual autorizado recorre las diez familias del laboratorio únicamente
en dark a `1180x820` y `1440x900`. Las animaciones se desactivan y Chromium
compara 18 goldens; los dos escenarios de `states-feedback` validan geometría,
contraste y overflow sin snapshot. Actualizar snapshots exige una decisión visual
aprobada; CI nunca usa `--update-snapshots`.

CI construye una imagen aislada con dependencias de análisis, mientras la imagen
normal conserva `--no-dev`. Su base nace del schema global sin datos y añade solo
usuarios, proyecto, semana, membresías y una fila determinista de Programa General;
al cerrar elimina el volumen.

`test:design-system:evidence` ejecuta teclado y reflow como diagnóstico
no bloqueante. El comando runtime no los invoca; CI tolera su fallo y conserva
los artefactos correspondientes.

El runtime canónico de este laboratorio permanece en desktop dark y ejecuta solo
la ruta `/internal/design-system`: smoke funcional, Axe, visual y tres muestras
frías de rendimiento por viewport. Los comandos históricos del piloto se
conservan separados y no forman parte de este gate.

La automatización Axe agrega las 20 combinaciones permitidas del laboratorio.
Toda violación seria bloquea; los resultados `incomplete` solo pueden exceptuarse
mediante fingerprint exacto que incluye `kind`, regla, impacto, superficie y
selector. Una excepción de `incomplete` nunca silencia una violación posterior.

El gate estatico permite deuda legacy registrada, pero debe fallar si una ruta marcada como migrada supera su baseline o si se introduce deuda visual nueva sin excepcion.
Login, Projects, Programa General, PDC, Contratos, Listado de Actividades y el modulo de tema tienen presupuesto cero por ruta para hex sueltos, inline styles, bloques `<style>`, Roboto y radios hardcodeados.

Cobertura automática del Sprint 00:

- Assets canonicos: tokens, CSS del sistema y API de tema.
- Shell autenticado.
- Laboratorio protegido: diez familias, dark y dos viewports desktop permitidos.
- Los demás módulos permanecen fuera de la migración y solo conservan sus
  contratos preexistentes hasta un sprint posterior.

El baseline no se regenera libremente. Todo cambio requiere hashes before/after,
excepción exacta y aprobación explícita; el auditor bloqueante se implementa en Step 2.
