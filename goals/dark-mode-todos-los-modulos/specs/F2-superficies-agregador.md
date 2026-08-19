---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-07-29
areas: [proceso]
fuente: goals/dark-mode-todos-los-modulos/specs/F2-superficies-agregador.md
resumen: Que las siete superficies hoy marcadas inventory-only pasen a tener manifiesto, presupuesto y evidencia propios, de modo que ninguna pueda regresar a claro sin…
---

# F2 · Las siete superficies del agregador

**Depende de:** F1. Puede correr en paralelo con F3.
**Riesgo:** medio — siete trabajos independientes, ninguno estructural.

> **Revisión del 2026-07-29 — el spec nació con nueve superficies y quedan siete.**
> `/listado-actividades` y `/contratos` **salen del plan entero**: están deprecadas (decisión
> del usuario). Son la interfaz del PDC viejo, y ese mismo día `views/partials/shell_sidebar.php`
> retiró sus entradas del rail. No reciben manifiesto, ni presupuesto, ni evidencia. Sus
> `pathBudgets` actuales en `exceptions.json` **se conservan**: las rutas siguen servidas y
> aflojar un gate sobre archivos vivos no es parte de deprecarlas.

## Objetivo

Que las siete superficies hoy marcadas `inventory-only` pasen a tener manifiesto, presupuesto
y evidencia propios, de modo que ninguna pueda regresar a claro sin que CI falle.

## Problema

Estas siete superficies cargan el design system y hoy arrancan en dark, pero **nada lo
garantiza**: no tienen manifiesto, no aparecen en `pathBudgets` del audit y no tienen evidencia
visual versionada. Son las que más deuda concentran por vista.

| Superficie | Vista | `<style>` | inline | hex | `rgba()` |
|---|---|---|---|---|---|
| programa-general-actualizar | `views/programa-general-actualizar/programaGeneralActualizar.view.php` | 1 | 25 | 55 | 14 |
| profesionales | `views/profesionales/profesionales.view.php` | 1 | 7 | 21 | 4 |
| subcontratistas | `views/subcontratistas/subcontratistas.view.php` | 1 | 9 | 18 | 5 |
| dashboard/escalamientos | `views/dashboard/escalamientos.php` | 1 | 3 | 2 | 33 |
| control-cambios | `views/control-cambios/controlCambios.view.php` | 1 | 8 | 0 | 0 |
| indicadores | `views/indicadores/indicadores.view.php` | 0 | 4 | 0 | 0 |
| pdc | `views/pdc/pdc.view.php` | 0 | 4 | 0 | 0 |

`pdc` ya tiene presupuesto de ruta en el audit (368 hallazgos emparejados) pero **no
manifiesto**, así que no participa del contrato de consumo ni del gate de partición del
entrypoint.

## Alcance

Un trabajo por superficie, siete en total, independientes entre sí y paralelizables.

### Patrón por superficie

1. **Crear el manifiesto y su golden, en un solo paso**, en
   `docs/design-system/manifests/<moduleId>.json`, siguiendo `module-manifest.schema.json` y el
   patrón de los existentes: `moduleId`, `routes`, `sources`, `components`, `vendors`,
   `layouts`, `states`, `roles`, `persistence`, `exceptions`, `tests`, `evidence`, `scenarios`.

   **Corregido el 2026-07-29 al ejecutarlo:** el schema exige `scenarios` con `minItems: 1`, y
   cada escenario un `golden` que exista en disco y un `sha256` que case con él. Un manifiesto
   sin captura no es válido, así que los pasos 1 y 6 son el mismo paso. Los goldens viven en
   `tests/browser/__screenshots__/<moduleId>/`, junto a los de los manifiestos existentes.

   **No declarar `consumerContract: "v1"` todavía:** v1 prohíbe `<style>`, `style=`, hex y
   `exceptions[]` no vacío — el estado al que llega la superficie *después* del paso 3. Se añade
   al cerrar la vista, no al abrirla.
2. **Migrar el head** de `DesignSystemHeadComponent::render()` a
   `renderForModule('<moduleId>')`, declarando en `vendors[]` sólo los realmente usados. El
   componente degrada al agregador si el manifiesto falla, así que el cambio es seguro por
   construcción.
3. **Vaciar la vista de estilos**: eliminar el bloque `<style>`, los `style="…"` y los hex,
   llevándolos a la hoja del módulo en su capa correcta y tokenizados contra `--ds-active-*`.
4. **Declarar presupuesto de ruta** en el audit, con cero para `hardcoded-hex`,
   `hardcoded-color-function`, `inline-style`, `embedded-style-block`, `forbidden-font-roboto`
   y `hardcoded-radius`, igual que los presupuestos de `login` y `programa-general`.
5. **Registrar en `inventory.json`** con estado `pilot` y su manifiesto.
6. **Evidencia visual** en `1180×820` dark — ejecutada ya en el paso 1, en
   `tests/browser/__screenshots__/<moduleId>/`.

### Notas por superficie

- **`programa-general-actualizar`** es la de mayor deuda del goal (95 infracciones en la
  vista). Conviene atacarla primero: lo que se aprenda ahí abarata las seis restantes.
  Cuidado: `DESIGN.md` protege Programa General y sus archivos; verificar que esta vista, que
  es la de *actualización*, no comparte hojas con el piloto antes de tocar nada.
- **`indicadores`** embebe un informe de Power BI *publish-to-web* por iframe. El contenido del
  iframe es ajeno y **no es tematizable**: se documenta como excepción permanente en
  `exceptions.json`, no como deuda pendiente. El alcance es el marco alrededor del iframe.
- **`profesionales`** y **`subcontratistas`** comparten un bloque `<style>` casi idéntico cuyo
  propósito declarado es «prevenir que `styles.css` rompa el layout de Handsontable». Tras F1
  ese bloque debería ser innecesario; verificarlo antes de reescribirlo.
  **Verificado el 2026-07-29 y la hipótesis se sostiene:** retirado por completo, la grilla
  renderiza igual. Sus reglas apuntaban a `mobile-table-fix.js`, que ya no existe; el par
  fondo/tinta de celda y la rejilla los declara `handsontable-module.css` desde `layer(vendor)`.
- **`dashboard/escalamientos`** concentra 33 `rgba()` y 7 radios hardcodeados en un solo
  `<style>`: es tokenización directa, sin ambigüedad de diseño.
- **`dashboard/escalamientos` se pinta en claro**, medido el 2026-07-29 con
  `data-aia-theme="dark"` aplicado: su `<style>` embebido hardcodea una paleta OKLCH clara que
  gana a los tokens. Es la superficie con más distancia al dark de las siete.
- **`profesionales` y `programa-general-actualizar` desbordan horizontalmente a 1180 px**: el
  contenido arranca recortado por la izquierda, y persiste tras forzar `scrollTo(0,0)`. Es de
  layout, no de scroll heredado de Handsontable. `AGENTS.md` prohíbe el overflow horizontal en el
  viewport permitido, así que su paso 3 no cierra sin resolverlo.
- **Resuelto el 2026-07-29 en las dos**, con la misma causa: el `padding: 0` sobre `html, body`
  del bloque embebido, que al entrar sin capa le ganaba al `padding-inline-start` con que el
  shell reserva el rail (72px).
- **Corregido el 2026-07-29: migrar el head no es gratis.** `renderForModule` emite `core.css`
  más los adjuntos declarados, y `core.css` **no** importa `handsontable-module.css`, donde vive
  —mal ubicada— toda la geometría del Cajón Contextual LPS. `escalamientos` perdió su drawer al
  migrar. Antes de dar por bueno un head segmentado hay que mirar la página, no sólo la lista de
  hojas: el componente degrada en silencio, pero un adjunto que falta no degrada nada.

## Fuera de alcance

- Rediseñar ninguna de las siete. F2 es normalización, no diseño.
- `/listado-actividades` y `/contratos`: deprecadas, fuera del plan entero (ver el aviso de
  cabecera).
- Las rutas `/bi/*` (F3) y `admin/` (F4).
- El contenido del iframe de Power BI.

## Verificación

Por superficie:

```bash
node scripts/design-system-audit.mjs
node scripts/design-system-entrypoint-partition.mjs
npm run test:design-system:static
```

Al cerrar cada superficie: validación funcional del módulo en navegador contra el contenedor
servido, consola y red limpias, foco visible, objetivos de 44 px, y captura en `1180×820`
dark. Un rol permitido y uno denegado si la superficie tiene restricción por capacidad.

## Criterio de cierre

1. Siete manifiestos nuevos, validados contra `module-manifest.schema.json`. **Seis hechos el
   2026-07-29**; falta `pdc`.
2. Siete entradas nuevas en `pathBudgets` con presupuesto cero en las seis reglas duras.
   **Seis hechas el 2026-07-29**; falta `pdc`.
3. Ninguna de las siete vistas conserva `<style>`, `style="…"` ni hex. **Seis hechas el
   2026-07-29**; falta `pdc`.
4. `inventory.json` sin ninguna entrada `inventory-only`. Las dos deprecadas quedan como
   `deprecated`, que no es lo mismo que pendiente.
5. Evidencia visual de las siete en `tests/browser/__screenshots__/`.
