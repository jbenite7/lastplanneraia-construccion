---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-08-04
areas: [design-system]
fuente: docs/superpowers/specs/2026-08-04-cierre-version-1-1-0-design-system-design.md
resumen: version.json sigue en 1.0.0 mientras el changelog acumula 5 cambios contractuales sin publicar. Declarar 1.1.0 hoy rompe la suite estática por tres frentes: 39…
---

# Cierre de la versión 1.1.0 del design system — Diseño

**Fecha:** 2026-08-04 · **Estado:** aprobado en brainstorming (4 decisiones del usuario)
**Contexto previo:** `memoria/trampas/subir-la-version-del-ds-cobra-deudas.md` (el intento medido de
subir la versión), entrada «Sin publicar (candidato a 1.1.0)» en `docs/design-system/CHANGELOG.md`.

## Problema

`version.json` sigue en `1.0.0` mientras el changelog acumula 5 cambios contractuales sin publicar.
Declarar `1.1.0` hoy rompe la suite estática por tres frentes: 39 excepciones de
`docs/design-system/exceptions.json` vencen (`expiresAtVersion: "1.1.0"`), ~10 manifiestos exigen
`designSystemVersion` sincronizado con `version.json`, y dos gates comprueban `'1.0.0'` literal
para dar el sistema por activado.

## Decisiones del usuario (2026-08-04)

| # | Decisión | Elección |
|---|---|---|
| D1 | 15 excepciones verbatim del selector de proyecto | **Migrar `/proyectos` ahora** a primitivas `aia-*`; las 15 se pagan |
| D2 | Gates que comprueban `1.0.0` literal | **«Al menos 1.0.0»**: versión ≥1.0.0 + `status: stable`; la activación de 1.0.0 es hito único cumplido |
| D3 | 21 blindajes `!important` Handsontable + 2 capas históricas | **Revisar una a una**: si el CSS legacy que la obliga ya no existe, se paga; si sigue, `expiresAtVersion: "1.2.0"` conservando la razón y citando la obra que la paga |
| D4 | Orden respecto a la campaña dark mode | **Después de la campaña** (31 tasks): cero solape de worktree y la revisión una-a-una mide CSS ya limpio |

## Alcance

Desktop ≥1180 px, dark only, viewport canónico 1180×820 (AGENTS.md). Sin cambios de
comportamiento, datos, rutas ni permisos: solo piel, metadatos de versión y scripts de gate.

### 1. Migración de `/proyectos` (paga 15 excepciones)

La pantalla del selector de proyecto abandona los bloques verbatim con `!important`
(tarjetas, modales, dropdowns, botones, inputs/select2/tom-select, input-groups, badges, progress)
y pasa a primitivas `aia-*` y tokens `--ds-*`. Misma estructura y flujo; solo la piel.
Origen de las copias: plan `goals/segmentacion-entry*` (copia verbatim plan-mandated del agregador).

**Gate visual:** ciclo triple `/impeccable audit` → `/ux-heuristics` → `/refactoring-ui` a
1180×820 dark — es la primera pantalla que ve todo usuario.

### 2. Tokenización del acento (`--primary`) (paga 1 excepción)

El hex del acento azul estilo Apple en `:root` (F1 Task 3) se resuelve: o se sustituye por un token
existente del sistema, o se declara token oficial con su definición en `tokens.css` (par dark
incluido) y la excepción se elimina. La premisa de diseño vigente es
`memoria/decisiones/inspiracion-apple-en-dark-aia.md`: el acento puede quedarse, pero tokenizado.

### 3. Revisión una a una de las 23 restantes (D3)

Por cada una de las 21 excepciones de blindaje Handsontable y las 2 capas (`@layer states`,
`@layer responsive`): localizar el CSS legacy que la justifica (selector móvil global, piel del
vendor, estilos inline del vendor, autoscaling del piloto) y comprobar si sigue vivo tras la
campaña dark mode.

- **Ya no existe** → se borra la excepción y la regla `!important` que protegía, con verificación
  visual de la superficie afectada.
- **Sigue vivo** → `expiresAtVersion: "1.2.0"`, conservando `reason` y añadiendo la obra que la
  paga (retiro del puente legacy móvil / piel vendor).

El resultado es un informe en el ledger: pagadas N, re-vencidas M, cada una con su evidencia.

### 4. Gates a «al menos 1.0.0» (D2)

- `scripts/design-system-closeout-contract.mjs:121-124`: `versionActivated` /
  `versionPartiallyActivated` pasan de `=== '1.0.0'` a «SemVer con major ≥1» + `status: stable`.
- `scripts/design-system-activation-git.mjs:55`: mismo cambio sobre la copia commiteada en HEAD.
- `tests/design-system/closeout-evidence.test.mjs:125` no necesita cambio (la rama `stable` ya
  cubre cualquier versión).
- El patrón exacto (regex `^([1-9]\d*)\.\d+\.\d+$`) ya se probó en la sesión del 2026-08-04.

### 5. Publicación

1. Sincronizar `designSystemVersion: "1.1.0"` en los manifiestos que `design-system-contracts.mjs`
   exige iguales a `version.json` (component-catalog, stable-api-1.0.0, ui-groups-inventory,
   state-semantics, vendors, legacy-aliases, manifests/inventory, closeout-evidence, a11y-baseline,
   a11y-exceptions — la lista canónica la da el propio script al fallar).
2. `version.json` → `{ "version": "1.1.0", "status": "stable", "pilot": "/programa-general" }`.
3. Retitular la entrada del changelog: «Sin publicar (candidato a 1.1.0)» → «1.1.0 - <fecha>»,
   retirando la nota de aviso (su contenido pasa a historia).

## Condición de hecho

`npm run test:design-system:static` en **8/8** con `version.json` en `1.1.0`, commits atómicos por
bloque, y el informe una-a-una en el ledger. Verificación visual de `/proyectos` con el ciclo
triple aprobada.

## Errores y riesgos

- **La campaña mueve el terreno:** este cierre NO arranca hasta que la campaña dark mode termine
  (D4). Si al arrancar el diff de la campaña tocó `/proyectos` o `exceptions.json`, re-medir antes
  de editar.
- **Una excepción «pagada» que sí hacía falta:** el retiro de cada `!important` se verifica en la
  superficie real (dev door, 1180×820) antes del commit; si la superficie se rompe, la excepción
  se re-vence en vez de pagarse.
- **Manifiestos olvidados:** la lista de sincronización se toma de la salida real del gate, no de
  memoria.

## Fuera de alcance

Retirar el puente legacy móvil/vendor de Handsontable (obra de 1.2.0), la campaña de ~2.600
hallazgos de fase 6 (C-2), mobile/tablet/`linen`, y cualquier cambio de comportamiento.
