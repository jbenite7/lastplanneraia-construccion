---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-07-29
areas: [proceso]
fuente: goals/dark-mode-todos-los-modulos/specs/F6-vendors.md
resumen: Cerrar los tres archivos de estilo que hoy pintan colores propios sin pasar por tokens, y eliminar la duplicidad de tener dos librerías resolviendo el mismo…
---

# F6 · Vendors sin adaptador

**Depende de:** F0. **Requiere F1 cerrado** antes de T6.3 — `styles.css` pisa hoy tanto Select2
como Tom Select, y migrar sobre esa cascada sería trabajar dos veces.
**Riesgo:** bajo en tokenización, **alto** en la consolidación de selects.

## Objetivo

Cerrar los tres archivos de estilo que hoy pintan colores propios sin pasar por tokens, y
eliminar la duplicidad de tener dos librerías resolviendo el mismo problema.

## Estado

| Archivo | Líneas | hex | `rgba()` | `var(--ds` | Naturaleza |
|---|---|---|---|---|---|
| `public/css/handsontable-module.css` | 972 | 54 | 40 | 40 | Skin de vendor, capa `vendor` |
| `public/css/change-monitor.css` | 127 | 23 | 1 | **0** | CSS **propio**, no vendor |
| `public/css/tom-select-premium-aia.css` | 154 | 16 | 2 | **0** | Skin de vendor |

Los dos últimos no usan un solo token: son islas de color independientes del tema.

### Detalle relevante

`change-monitor.css` no es un vendor pese a vivir junto a ellos. Lo carga **una sola vista**,
`views/programacion-semanal/programacion_semanal.view.php:42`, y lo hace con un `<link>` a mano
y versión escrita a pulso (`?v=20260602a`) en vez de
`DesignSystemHeadComponent::renderStylesheet`, así que no recibe cache-busting por `mtime`. No
necesita adaptador: necesita tokenización y entrar por el head canónico.

## Consolidación de selects

Dos librerías cubren «select enriquecido». Ambas están vendorizadas en `public/vendor/`.

**Decisión del usuario (2026-07-25, posterior al grilleo): consolidar en Tom Select y eliminar
Select2.**

Esto invierte la dirección que este spec proponía en su primera redacción. El motivo que la
justifica: **Select2 depende de jQuery y Tom Select no.** Retirar Select2 elimina una de las
razones por las que jQuery sigue cargándose y abre la puerta a soltarlo más adelante.
`programacion-semanal.json` es hoy el único manifiesto que declara `jquery` como vendor
explícito, junto con `select2`.

El coste es real y va en la dirección contraria al inventario: Select2 está más extendido y es
el que ya tiene adaptador del design system.

### Inventario

| | Select2 | Tom Select |
|---|---|---|
| Vistas de producto | **9** | 4 |
| Adaptador DS | `adapters/select2.css` (34 usos de tokens) | **ninguno** |
| Entrypoint de adjunto | `entrypoints/attach-select2.css` | **ninguno** |
| Registrado en `VENDOR_ATTACHMENTS` | sí | **no** |
| Dependencia de jQuery | **sí** | no |
| Servido local | `public/vendor/select2/` | `public/vendor/tom-select/` |
| Skin actual | adaptador DS | `tom-select.bootstrap4.min.css` + `tom-select-premium-aia.css` |

Vistas con Select2: `listado-actividades` (12 usos), `contratos` (4),
`programacion-semanal` (2) y sus tres subvistas CIC/CNC/CNP (1 cada una), `pdc` (2),
`indicadores` (1), `control-cambios` (1). Más el laboratorio.

Vistas con Tom Select: `programa-general-actualizar` (9), `listado-actividades` (4),
`programacion-intermedia` (3). Más el laboratorio.

`listado-actividades` usa **las dos**: era el mejor punto de partida, porque permitía migrar sus
12 usos de Select2 contra un Tom Select ya presente en la misma página.

> **Revisión del 2026-07-29 — `listado-actividades` y `contratos` están deprecadas** y salen del
> plan del goal (ver `goal.md` §Fuera de alcance). Esto **afecta a T6.3 de tres maneras, y dos
> siguen abiertas**:
>
> 1. **T6.3.c desaparece** — sus 12 usos de Select2 ya no se migran. F6 pierde su punto de partida
>    designado; el siguiente candidato natural es `programacion-semanal`, que es también el más
>    difícil (T6.3.e). **Decidir por dónde empieza T6.3 antes de escribir su sub-plan.**
> 2. **T6.3.d cambia de forma** — `semi_auto_review.js` lo comparten Listado, Contratos y PDC.
>    Con dos de los tres deprecados, la verificación del flujo semi-automático se reduce a PDC…
>    que a su vez está siendo reemplazado por PDC V2. **Confirmar con ese frente qué queda vivo
>    del contrato `auto/*` antes de tocar el archivo.**
> 3. **T6.3.f (retirar Select2) NO se abarata sola.** Deprecar la página no borra el código: los
>    16 usos de Select2 de esas dos vistas siguen en el repo y seguirán impidiendo borrar
>    `public/vendor/select2/`. Retirar Select2 exige borrar esas vistas o migrarlas igual. Es una
>    decisión de producto, no de estilos, y este goal no la toma.

### Integraciones que no son un `<select>` cualquiera

Tres puntos concentran el riesgo:

1. **`public/js/modules/semi_auto_review.js:1166-1168`** — el flujo semi-automático compartido
   por Listado de Actividades, Contratos y PDC. AGENTS.md lo declara contrato compartido y
   prohíbe expresamente crear un flujo paralelo. Migrarlo toca los tres módulos a la vez.
   Va en su propio commit, con verificación de los tres.
2. **`public/js/modules/programacion_semanal/hot.js:4355-4359, 4633`** — Select2 instanciado y
   destruido dentro de celdas de Handsontable, con `destroy()` en el ciclo de vida del editor.
   Es el equivalente en Semanal de lo que `HandsontableTomSelectEditor.js` ya hace con Tom
   Select. Debería poder reusarse ese editor en vez de reescribirlo.
3. **`public/js/modules/aia_ui/design_system_lab.js`** (16 usos) y
   `views/design-system/families/vendor-adapters.php` (13) — el laboratorio documenta el
   adaptador de Select2 como primitiva versionada. Retirar Select2 exige reemplazar esa
   familia por la de Tom Select, no sólo borrarla.

### Hallazgo latente que F6 debe corregir

`tom-select` **no está en `VENDOR_ATTACHMENTS` ni en `CORE_VENDORS`** de
`src/View/Components/DesignSystemHeadComponent.php:35-44`. Pero
`docs/design-system/manifests/programacion-intermedia.json` y `laboratory.json` **sí lo
declaran** en `vendors[]`.

Consecuencia: si cualquiera de esos dos módulos migrara a `renderForModule()`, `moduleVendors()`
rechazaría el vendor desconocido y degradaría en silencio al agregador completo. Hoy no
estalla porque ambos usan `render()` / `renderLaboratory()`. Es una trampa esperando a F2.

Consolidar en Tom Select obliga a arreglarlo, y eso es un punto a favor de la decisión.

## Alcance

### T6.1 — Tokenizar `change-monitor.css`

Sustituir los 23 hex por `--ds-active-*`, mover el archivo a su capa correcta y hacer que
`programacion_semanal.view.php` lo cargue con `renderStylesheet`. Bajo, acotado, sin riesgo.
Independiente de T6.3.

### T6.2 — Tokenizar `handsontable-module.css`

Sustituir los 54 hex y 40 `rgba()` por tokens, conservando la capa `vendor` y sin alterar la
geometría de la grilla. Handsontable es insustituible y su skin es crítico para la legibilidad
de la densidad operativa: se cambia color, no layout.

Verificación con `tests/browser/listado-actividades-handsontable.mjs` y
`tests/browser/support/handsontableGoalMatrix.mjs`, que ya cubren la grilla.

### T6.3 — Consolidar en Tom Select

**Se escribe como sub-plan propio antes de ejecutarse.** Es el tramo más grande del goal
después de F1 y F4, y se ejecuta en este orden:

#### T6.3.a — Dar a Tom Select el estatus que hoy tiene Select2

Antes de migrar una sola vista:

- Crear `public/css/design-system/adapters/tom-select.css`, en la capa `vendor`, siguiendo el
  patrón de `adapters/select2.css`. Absorbe y sustituye a `tom-select-premium-aia.css`, cuyos
  16 hex se tokenizan en el proceso.
- Crear `public/css/design-system/entrypoints/attach-tom-select.css`.
- Registrar `'tom-select'` en `VENDOR_ATTACHMENTS` de `DesignSystemHeadComponent.php`. Esto
  cierra el hallazgo latente de arriba con independencia de cómo termine la migración.
- Decidir si se conserva `tom-select.bootstrap4.min.css`: es un skin de Bootstrap 4 que el
  adaptador va a pisar entero. Si el adaptador lo cubre, se retira.

Al terminar T6.3.a, Tom Select está adaptado y `programacion-intermedia` puede migrar a
`renderForModule` sin degradar. **Este sub-tramo tiene valor propio aunque la migración se
detenga después.**

#### T6.3.b — Migrar los usos simples

`indicadores` (1), `control-cambios` (1), `pdc` (2), CIC/CNC/CNP (1 cada una). Los 4 de
`contratos` salen: vista deprecada. Son inicializaciones directas sobre `<select>`. Uno o dos commits, verificando cada módulo.

#### T6.3.c — ~~Migrar `listado-actividades`~~ · retirado el 2026-07-29

La vista está deprecada. Sus 12 usos de Select2 no se migran. Ver el aviso de arriba: el tramo
que valida el patrón hay que reasignarlo antes de escribir el sub-plan de T6.3.

#### T6.3.d — Migrar `semi_auto_review.js`

Commit propio. Verificación funcional del flujo semi-automático completo —`preview`, `apply`,
`undo`, `feedback`, `metrics`—. El spec original exigía los **tres** módulos que lo comparten
(Listado de Actividades, Contratos y PDC); con los dos primeros deprecados, queda por confirmar
con el frente de PDC V2 qué sigue vivo. Sin esa confirmación el tramo no cierra.

#### T6.3.e — Migrar la integración con Handsontable de Semanal

`programacion_semanal/hot.js`. Objetivo: reusar `HandsontableTomSelectEditor.js` en vez de
escribir un segundo editor. Si el editor existente no cubre el caso, se amplía él, no se
duplica.

#### T6.3.f — Retirar Select2

Sólo cuando b–e estén verificados:

- Borrar `public/vendor/select2/`, `adapters/select2.css`,
  `entrypoints/attach-select2.css`.
- Quitar `'select2'` de `VENDOR_ATTACHMENTS` y de los manifiestos `laboratory.json` y
  `programacion-semanal.json`.
- Sustituir la familia de Select2 del laboratorio por la de Tom Select en
  `views/design-system/families/vendor-adapters.php` y `design_system_lab.js`.
- Actualizar `docs/design-system/vendors.json`, `ui-groups-inventory.json` (grupo
  `enhanced-select`) y `component-catalog.json`.
- Revisar `src/Controllers/Core/DesignSystemAssetController.php`, que referencia select2 en el
  servido de entrypoints en runtime.

#### Condición de parada

Si en T6.3.e resulta que la integración con Handsontable de Semanal no puede resolverse con
`HandsontableTomSelectEditor.js` sin degradar el comportamiento de la grilla, **se detiene la
retirada de Select2**, se escala al usuario con los datos, y se cierra F6 con las dos librerías
adaptadas y la duplicidad documentada como deuda consciente.

No es un fracaso: T6.3.a ya habrá entregado el adaptador de Tom Select y cerrado el hallazgo
latente. Lo que no está permitido es dejar Select2 a medio retirar.

### T6.4 — Actualizar contratos

Coherencia final entre `vendors.json`, manifiestos, `ui-groups-inventory.json`,
`component-catalog.json` y el laboratorio con el resultado real de T6.3.

## Fuera de alcance

- Retirar jQuery. Consolidar en Tom Select es un paso hacia eso, no ese paso.
- Actualizar versiones de vendors.
- Sustituir Handsontable o DataTables.
- Rediseñar los controles: F6 es tokenización y consolidación, no diseño.

## Verificación

```bash
node scripts/design-system-audit.mjs
node scripts/design-system-entrypoint-partition.mjs
npm run test:design-system:static
npm run test:design-system:runtime
npx playwright test tests/browser/listado-actividades-handsontable.mjs --workers=1
npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1
```

En navegador, `1180×820` dark, contra el contenedor servido:

- La grilla de Handsontable legible con densidad compacta.
- El monitor de cambios de `programacion-semanal` coherente con el tema.
- **Cada punto de uso migrado**: abrir, buscar, seleccionar, limpiar, con foco visible,
  navegación por teclado y objetivo de 44 px.
- El flujo semi-automático completo en Listado, Contratos y PDC tras T6.3.d.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Migrar `semi_auto_review.js` rompe el contrato compartido de tres módulos | Commit propio, verificación funcional de los tres antes de continuar |
| La integración de Handsontable en Semanal no tiene equivalente en Tom Select | T6.3.e la aborda antes de retirar nada; existe condición de parada explícita |
| Select2 queda a medio retirar y la app carga dos librerías peor integradas | T6.3.f es todo o nada, y sólo se ejecuta con b–e verificados |
| Tokenizar Handsontable degrada la legibilidad de la grilla densa | Sólo color, nunca geometría; captura antes y después con datos reales |
| El laboratorio pierde la documentación del select enriquecido | La familia se sustituye, no se borra; es criterio de cierre |

## Criterio de cierre

1. `change-monitor.css` tokenizado y cargado por el head canónico.
2. `handsontable-module.css` sin hex ni `rgba()` propios.
3. `adapters/tom-select.css` y `attach-tom-select.css` existen; `tom-select` registrado en
   `VENDOR_ATTACHMENTS`; `tom-select-premium-aia.css` retirado.
4. Select2 eliminado del repositorio **o**, si se activó la condición de parada, ambas
   librerías adaptadas y la deuda documentada en `exceptions.json`.
5. El laboratorio documenta el select enriquecido con la librería que quede.
6. Contratos y manifiestos coherentes con el resultado.
7. Evidencia visual en `evidence/F6/`.
