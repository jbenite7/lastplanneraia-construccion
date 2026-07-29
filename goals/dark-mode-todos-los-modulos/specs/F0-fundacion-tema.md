# F0 · Fundación de tema

**Depende de:** nada. Bloquea a F1, F2, F3, F4, F5, F6.
**Riesgo:** alto — toca la raíz de la cascada y borra código.

## Objetivo

Dejar un único mecanismo de aplicación de tema, con dark como default de la cascada, y poner
`admin/` bajo observación del audit. Ninguna fase posterior debe volver a discutir cómo se
aplica el tema.

## Problema

Hoy conviven **cuatro** señalizadores de tema:

1. `html[data-aia-theme="dark"]` — atributo canónico, lo pone `theme-bootstrap.js`.
2. `html.aia-theme-dark` — clase espejo del anterior, también canónica.
3. `body.dark-mode` — clase legacy que `theme.js:26` y `theme.js:31` siguen aplicando, y de la
   que dependen **62 selectores vivos**: `listado-actividades.css` (37), `contratos.css` (19),
   `design-system/adapters/legacy-bridge.css` (5), `design-system/components/navigation.css` (1).
   `design_system_lab.js:7` la aplica a mano.
4. Atributo escrito a mano en `<html>`: `views/plan-compras/app.view.php:11`,
   `views/design-system/lab.view.php:2`, y script inline en `views/bi/_layout.php:7-15`.

Y el default de la cascada es **linen**: `public/css/tokens.css:2` define todo en `:root`, y
dark se pinta encima en `aia-design-system.css:64` y `theme-overrides.css:29`. Consecuencia:
toda superficie que no cargue `theme-bootstrap.js` cae en claro. Así están las 14 vistas de
`admin/`.

## Alcance

### T0.1 — Cerrar el rojo de `programacion-semanal` (primero)

`node scripts/design-system-audit.mjs` falla hoy en `main`:

```
programacion-semanal embedded-style-block: 1 > path budget 0
```

Origen: `views/programacion-semanal/programacion_semanal.view.php:1` (bloque `<style>`).

Mover ese CSS a la hoja del módulo (`public/css/programacion-semanal.css`), en su capa
correcta, y confirmar que el audit vuelve a verde. **Ninguna otra tarea de F0 empieza hasta
que el audit pase.** El motivo es de atribución: con CI rojo de partida no se distingue lo que
rompemos de lo que ya estaba roto.

### T0.2 — Invertir el default de la cascada

`:root` pasa a servir los valores **dark**. Linen queda como override explícito bajo
`[data-aia-theme="linen"]` / `.aia-theme-linen`, y desaparece en T0.4.

Archivos: `public/css/tokens.css`, `public/css/aia-design-system.css` (bloque `:root` de la
línea 37), `public/css/design-system/entrypoints/theme-overrides.css` (bloque `:root` de la
línea 2).

Criterio de aceptación: cargar cualquier documento HTML con JavaScript deshabilitado y sin
atributo `data-aia-theme` produce una superficie oscura.

### T0.3 — Unificar el señalizador de tema

Un solo señalizador: `html[data-aia-theme]` más su clase espejo `html.aia-theme-dark`.

- Reescribir los 62 selectores `body.dark-mode` a `html.aia-theme-dark`. En
  `listado-actividades.css` y `contratos.css` la mayoría ya vienen prefijados como
  `html.aia-theme-dark body.dark-mode`, así que basta con eliminar el segmento redundante.
- Quitar la aplicación de la clase en `theme.js:26`, `theme.js:31` y `design_system_lab.js:7`.
- Sustituir el script inline de `views/bi/_layout.php:7-15` por
  `DesignSystemHeadComponent::renderScript('/js/modules/aia_ui/theme-bootstrap.js')`.
- Quitar el atributo escrito a mano de `views/design-system/lab.view.php:2` y hacer que
  `renderLaboratory()` emita `theme-bootstrap.js`, como ya hacen `render()` y
  `renderForModule()`.
- `views/plan-compras/app.view.php:11` queda para F5, que decide su contrato completo.

Criterio de aceptación: `grep -rn "dark-mode" public/css public/js views src admin` no
devuelve ninguna coincidencia fuera de comentarios históricos.

### T0.4 — Retirar el tema `linen`

Decisión 2 del grilleo: un solo tema.

- **CSS**: eliminar las ramas `[data-aia-theme="linen"]` / `.aia-theme-linen` de
  `tokens.css`, `aia-design-system.css`, `theme-overrides.css`, `handsontable-module.css`,
  `pdc.css`, `programacion-semanal.css`, `programacion-intermedia.css`,
  `adapters/lps-drawer.css`, `adapters/shell-sidebar.css`.
- **JS**: `theme.js` pierde `toggleTheme`, `bindThemeSwitches`, `updateThemeSwitches` y la
  normalización a linen. `theme-bootstrap.js` se reduce a fijar dark.
  `design_system_lab.js` pierde su rama linen.
- **Markup**: eliminar `views/auth/partials/auth-theme-switch.php` y su inclusión, y todo
  `.aia-theme-switch` del shell.
- **Contratos y esquemas**: `themes` deja de admitir `linen` en
  `module-manifest.schema.json`, `ui-groups-inventory.schema.json`,
  `runtime-budget.schema.json`, `family-approvals.schema.json`. Actualizar los **89 grupos**
  de `ui-groups-inventory.json` (hoy todos declaran `["dark","linen"]`), más
  `family-approvals.json`, `homologation.json` y `component-catalog.json`.
- **Gates**: `design-system-contracts.mjs`, `design-system-runtime-budget.mjs`,
  `design-system-runtime-budget-provenance.mjs` dejan de iterar sobre dos temas.
- **Tests**: ~12 ficheros bajo `tests/browser/` y `tests/design-system/` referencian linen;
  ajustar aserciones y escenarios.
- **Persistencia**: la clave `aia-theme` de `localStorage` queda obsoleta. `theme-bootstrap.js`
  la ignora; no se borra del navegador del usuario.
- **Documentación**: `DESIGN.md` pierde la sección de deuda consciente sobre linen;
  `docs/design-system/CHANGELOG.md` y `decisions.md` registran la retirada.

Criterio de aceptación: `grep -rin "linen" public admin views src scripts docs/design-system tests`
sólo devuelve entradas históricas de changelog y decisiones.

### T0.5 — Borrar el tema muerto

- `src/View/Components/NavbarComponent.php` — huérfano, nadie lo instancia.
- `public/css/dark-mode.css` — sólo lo cargaba `NavbarComponent.php:36`.
- `public/css/navbar.css` — **verificado el 2026-07-25**: su único cargador es
  `NavbarComponent.php:35`. No aparece en la cadena de `@import` de `entrypoints/core.css` ni
  de `aia-design-system.css`, y las demás menciones en el repositorio son comentarios
  históricos (`handsontable-module.css:336`, `styles.css:208`, `dark-mode.css:33`,
  `adapters/shell-sidebar.css:36`). Se borra con su componente.

Commit propio y reversible, separado del resto de F0.

### T0.6 — `admin/` bajo el audit

- Añadir `admin` a `scanRoots` en `scripts/design-system-audit.mjs:19`.
- Medir el baseline resultante y registrarlo en `docs/design-system/audit-baseline.json` como
  entrada propia de `admin`.
- Instalar el gate monotónico: el total no puede subir. Override sólo por entrada explícita en
  `docs/design-system/exceptions.json` con justificación.
- Registrar `admin` en `docs/design-system/manifests/inventory.json` con estado
  `observed-frozen` (no `pilot`, no `inventory-only`: es un estado nuevo, documentarlo en el
  esquema).

### T0.7 — Gate monotónico global

Decisión 16. El total de `totalViolations` del audit no puede crecer entre commits. Se
implementa comparando contra `audit-baseline.json` y se documenta el procedimiento de override.

## Fuera de alcance

- Tocar `styles.css` (es F1).
- Reescribir vistas de `admin/` (es F4, y sólo adaptación).
- Cambiar el contrato de `views/plan-compras/app.view.php` (es F5).

## Verificación

| Qué | Comando |
|---|---|
| Audit en verde | `node scripts/design-system-audit.mjs` |
| Contratos estáticos | `npm run test:design-system:static` |
| Runtime y a11y | `npm run test:design-system:runtime` |
| PHPStan | `docker compose exec app vendor/bin/phpstan analyse src admin/src --memory-limit=1G` |
| Flujo completo | `npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1` |

**Verificación visual obligatoria** en `1180×820`, dark, sobre el contenedor servido: una
superficie de cada grupo — `/login` (A), `/contratos` (B), `/bi/control-tower` (C),
`/admin` (D) — comprobando que ninguna cae en claro y que no hay flash de tema al cargar.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Invertir `:root` rompe superficies que asumían linen de base | T0.2 va en commit propio, con captura before/after de las 6 superficies con manifiesto |
| Reescribir 62 selectores `body.dark-mode` introduce regresiones en listado-actividades y contratos | Reescritura mecánica verificable por diff; ambas tienen presupuesto de ruta que debe seguir verde |
| Retirar linen toca 4 esquemas, 89 grupos y ~12 tests | T0.4 se subdivide: primero esquemas y datos, luego CSS/JS, luego tests; cada tramo con su verificación |
| Borrar `navbar.css` rompe un módulo que lo cargue suelto | Verificación por grep previa al borrado; si aparece consumidor, se pospone |

## Criterio de cierre

1. Audit en verde, con `admin/` dentro de `scanRoots` y baseline congelado registrado.
2. Un solo señalizador de tema en todo el repositorio.
3. `:root` sirve dark; ningún documento cae en claro sin JavaScript.
4. Ninguna referencia viva a `linen` fuera de historial.
5. Gate monotónico global activo y documentado.
6. Evidencia visual de las cuatro superficies representativas en `evidence/F0/`.
