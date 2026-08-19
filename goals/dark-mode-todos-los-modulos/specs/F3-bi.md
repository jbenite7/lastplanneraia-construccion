---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-07-29
areas: [bi]
fuente: goals/dark-mode-todos-los-modulos/specs/F3-bi.md
resumen: Que las ocho rutas /bi/ entren en el contrato de consumo del design system: head canónico, manifiesto propio, sin CDN externas y sin un segundo sistema de…
---

# F3 · BI

**Depende de:** F1. Puede correr en paralelo con F2.
**Riesgo:** medio — ocho rutas con un layout compartido; el trabajo se concentra en un archivo.

## Objetivo

Que las ocho rutas `/bi/*` entren en el contrato de consumo del design system: head canónico,
manifiesto propio, sin CDN externas y sin un segundo sistema de utilidades.

## Estado

Las ocho rutas comparten `views/bi/_layout.php`:

| Ruta | Vista |
|---|---|
| `/bi/control-tower` | `views/bi/control-tower.php` |
| `/bi/curva-s`, `/bi/intermedia`, `/bi/pdc`, `/bi/programa-general`, `/bi/responsables`, `/bi/semanal`, `/bi/contratistas` | `views/bi/index.php` |

`bi-runtime` figura en `inventory.json` como `deferred-last`, sin manifiesto.

### Lo que ya está bien

Buena parte del trabajo visual está hecho y **no debe rehacerse**:

- `public/css/bi-control-tower.css` — 1 880 líneas, **cero hex**, 302 usos de `var(--ds-*)`,
  143 de `--ds-active-*`.
- `public/css/bi-filter-drawer.css` — cero hex, 33 usos de tokens.
- `public/js/modules/bi_chart_theme.js` — **ya implementa la decisión 10**: lee
  `--ds-active-text-primary`, `--ds-active-border`, `--ds-active-surface`,
  `--ds-active-surface-raised` y los seis tokens de serie con
  `getComputedStyle(document.documentElement)`. Los 15 hex que el audit marca son *fallbacks*
  con procedencia documentada, validados contra `scripts/validate_palette.js` para dark.
  **No hay trabajo de tokenización aquí**; sólo registrar la excepción para que el audit deje
  de contarlos como deuda.

### Lo que falta

1. **Head fuera de contrato.** `_layout.php:17-18` carga `tokens.css` y el agregador con
   `renderStylesheet` crudo, en lugar de `renderForModule('bi-runtime')`. No participa del
   gate de partición del entrypoint.
2. **Tema por script inline.** `_layout.php:7-15` fuerza dark con su propio IIFE en vez de
   `theme-bootstrap.js`. F0 lo resuelve; F3 sólo verifica.
3. **Dos CDN externas** en `_layout.php:40-41`:
   - `https://cdn.tailwindcss.com` — segundo sistema de utilidades, prohibido por `DESIGN.md`.
   - `https://unpkg.com/lucide@latest` — **sin versión fijada**. Además de la prohibición de
     CDN, un `@latest` sin pin es riesgo de rotura silenciosa y de cadena de suministro.
     Merece atención propia, no ir de acompañante de Tailwind.
4. **Sin manifiesto**, luego sin presupuesto de ruta ni evidencia versionada.

## Alcance

### T3.1 — Head canónico

Crear `docs/design-system/manifests/bi-runtime.json` con las ocho rutas y los vendors reales.
Migrar `_layout.php` a `renderForModule('bi-runtime')`. Registrar en `inventory.json` con
estado `pilot`, retirando `deferred-last`.

### T3.2 — Retirar Tailwind

Decisión 9. Inventariar las clases Tailwind realmente usadas en `control-tower.php`,
`index.php`, `_nav.php` y `_filters.php`, y sustituirlas por primitivas `aia-*` y utilidades
del DS. Eliminar el `<script src="https://cdn.tailwindcss.com">` y el bloque
`window.tailwind.config` de `_layout.php:20-39`.

El grueso del aspecto ya lo aporta `bi-control-tower.css`, que es CSS propio y tokenizado. Lo
que Tailwind cubre es principalmente layout utilitario, que el DS ya tiene.

### T3.3 — Resolver Lucide

`unpkg.com/lucide@latest` es una dependencia externa sin pin. Dos salidas, a decidir al
escribir el plan de F3:

- Vendorizar a `/public/vendor/lucide/` con versión fija, como el resto de vendors.
- Sustituir por Font Awesome, que ya es vendor del proyecto y está servido local.

La segunda evita añadir un vendor de iconos más; la primera es menos trabajo. Se decide al
inventariar qué iconos usa BI y si existen equivalentes.

### T3.4 — Presupuesto y excepción de charts

Declarar `pathBudgets` para `bi-runtime` con cero en las seis reglas duras, y registrar en
`docs/design-system/exceptions.json` los `SERIES_FALLBACKS` y `TEXT_FALLBACKS` de
`bi_chart_theme.js` como **excepción permanente justificada** —no deuda pendiente—, citando la
validación de paleta ya hecha.

### T3.5 — Evidencia

Captura en `1180×820` dark de `/bi/control-tower` y de al menos dos de las siete rutas de
informe, con los charts renderizados y el cajón de filtros abierto.

## Fuera de alcance

- Rediseñar los dashboards. El trabajo visual de `bi-control-tower.css` se conserva.
- Cambiar la lógica de datos de `bi-spa.js` ni los endpoints `/api/bi/*`.
- Tocar `bi_chart_theme.js` más allá de registrar su excepción.

## Verificación

```bash
node scripts/design-system-audit.mjs
node scripts/design-system-entrypoint-partition.mjs
npm run test:design-system:static
npx playwright test tests/browser/bi_control_tower.spec.mjs --workers=1
```

En navegador, contra el contenedor servido: las ocho rutas cargan sin error de consola, sin
petición a `cdn.tailwindcss.com` ni a `unpkg.com` en la pestaña de red, con los charts
pintados y los filtros operativos.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Retirar Tailwind rompe el layout de los dashboards | Inventario previo de clases usadas; sustitución por tramos con captura antes y después |
| Los charts pierden color al cambiar el head | `bi_chart_theme.js` lee tokens en runtime: si `tokens.css` carga, funcionan. Verificar orden de carga respecto a `bi-spa.js` |
| Sustituir Lucide deja iconos sin equivalente | Inventariar antes de decidir; si falta cobertura, vendorizar con pin |

## Criterio de cierre

1. `bi-runtime.json` existe, valida contra el esquema y las ocho rutas están declaradas.
2. `_layout.php` usa `renderForModule` y no contiene script de tema ni configuración de vendor.
3. Cero peticiones a dominios externos en las ocho rutas.
4. Presupuesto de ruta en cero para las seis reglas duras.
5. Excepción de `bi_chart_theme.js` registrada y justificada.
6. Evidencia visual en `evidence/F3/`.
