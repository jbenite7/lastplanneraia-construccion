---
tipo: trampa
estado: vigente
fecha: 2026-07-29
areas: [design-system, admin]
fuente: memoria-claude
origen: lps-aia-admin-adminlte-adaptador
resumen: admin/ tiene entrypoint CSS propio porque VIEW_OWNED_VENDORS prohíbe un attach-adminlte; y para ganarle a un !important del vendor hay que capar en @layer reset, no en components
---
F4 del goal dark-mode-todos-los-modulos (2026-07-29) dejó `admin/` en dark con dos piezas
nuevas: `admin/public/css/admin-entrypoint.css` (+ `admin-auth-entrypoint.css`) y
`public/css/design-system/adapters/admin-lte.css`.

**Por qué entrypoint propio y no `renderForModule('admin')`:**
`DesignSystemHeadComponent::VIEW_OWNED_VENDORS` declara `adminlte` con un candado explícito —
ningún miembro de esa lista puede tener `entrypoints/attach-<vendor>.css`. Crearlo rompe
`scripts/design-system-entrypoint-partition.mjs`. El aislamiento que AGENTS.md exige para
`admin/` es de PHP, no de CSS: tokens, tema y adaptadores sí se comparten.

**La trampa de capas que decide todo el adaptador:** capar el vendor con
`@import ... layer(vendor)` basta para ganarle en declaraciones normales desde `components`.
Pero las utilidades de color de Bootstrap traen `!important` propio, y para `!important` el
orden de capas se **invierte**: solo una capa **anterior** a `vendor` puede ganarles. Por eso
los remapeos de `.bg-*`, `.text-muted`, `.badge-*` viven en `@layer reset`, no en `components`.

**Otros dos filos medidos ahí:**
- El audit marca hex y `rgba()` incluso dentro de **comentarios** en archivos de
  `public/css/design-system/` (solo salta si `/*` cae en los 8 caracteres previos). Citar
  valores de Bootstrap en un comentario multilínea rompe el presupuesto de ruta.
- Usar `--ds-color-state-*-text` sin su `-bg` en la misma regla lo caza
  `tests/design-system/state-token-pairing.test.mjs`; si es medio par a propósito, va a
  `docs/design-system/state-token-exceptions.json` con `kind` y una razón de 80+ caracteres.

`admin/` quedó como `inventory-only` en `inventory.json` (ya no `observed-frozen`, que por
definición significa «sin presupuesto de rutas»), en dark y tokenizado pero **no migrado al
design system** — desviación deliberada y vinculante. Ver [[goal-dark-mode-todos-modulos]]
y [[css-layer-cascade]].
