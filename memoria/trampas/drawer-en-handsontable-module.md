---
tipo: trampa
estado: vigente
fecha: 2026-07-29
areas: [design-system, lps]
fuente: memoria-claude
origen: lps-aia-drawer-en-handsontable-module
resumen: toda la geometría del Cajón Contextual LPS vive dentro de handsontable-module.css, que core.css no importa; migrar un head a renderForModule sin declarar el vendor handsontable tumba el drawer
---
`public/css/handsontable-module.css:377+` contiene **toda** la geometría de `.lps-drawer` y su
overlay (`position: fixed`, `translateX(100%)`, z-index). El nombre del archivo miente: no es sólo
Handsontable.

`aia-design-system.css` (el agregador) lo importa; `public/css/design-system/entrypoints/core.css`
**no**. Así que cualquier vista que pase de `DesignSystemHeadComponent::render()` a
`renderForModule('<id>')` e incluya `views/partials/drawer_unificado.php` pierde el drawer —cae al
flujo del documento, visible debajo del contenido— salvo que declare `handsontable` en los
`vendors[]` de su manifiesto, aunque no monte ninguna grilla. Le pasó a `escalamientos` el
2026-07-29 (ver [[goal-dark-mode-todos-modulos]]).

**Why:** el fallo no lo atrapa ningún gate: `renderForModule` degrada al agregador si el manifiesto
falla, pero un adjunto que falta no degrada nada — la página sale servida y rota.

**How to apply:** al migrar un head, mirar la página renderizada, no sólo comprobar en
`document.styleSheets` que la lista es la reducida. El arreglo de fondo —mover esas reglas a
`adapters/lps-drawer.css`, que ya está en `core.css`— cambia de capa (`vendor` → `components`) y
afecta a todas las vistas que hoy cargan ambas hojas; sigue pendiente.
