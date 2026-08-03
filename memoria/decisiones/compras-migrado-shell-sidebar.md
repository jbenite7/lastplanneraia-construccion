---
tipo: decision
estado: vigente
fecha: 2026-07-29
areas: [design-system, shell, pdc]
fuente: memoria-claude
origen: lps-aia-compras-migrado-shell-sidebar
resumen: "Compras (/contratos, /listado-actividades, /pdc) YA usa el shell sidebar, revirtiendo la exclusión que sigue escrita en goals/sidebar-todos-modulos/goal.md"
---
El 2026-07-25 se migraron `/contratos`, `/listado-actividades` y `/pdc` al shell sidebar canónico.
El harness `tests/browser/shell-sidebar-rollout.mjs` cubre ahora 22 rutas (113 checks) y
`docs/design-system/manifests/foundation-shell.json` las declara.

**Sobre el goal `sidebar-todos-modulos`** (revisado el 2026-08-03): su `goal.md` excluye a Compras,
y eso **no es un texto olvidado**. El goal cerró el 2026-07-31 con una sección «Cierre formal» que
documenta la omisión como excepción deliberada: «Compras… omitidas — PDC v2 tiene su propia
navegación; las rutas viejas ya están retiradas». Las dos cosas son ciertas a la vez: aquel goal no
migró Compras, y Compras llegó al shell sidebar por otra vía. No hay nada que corregir en el goal;
lo que había que corregir era esta nota, que lo acusaba de estar desactualizado.

**Why:** el usuario revirtió esa exclusión explícitamente al arreglar el navbar huérfano; sin este
apunte, una sesión futura que lea el goal asumiría que Compras sigue en navegación legacy.

**How to apply:** trata las 22 rutas como el alcance real del shell. Antes de citar el alcance de
`sidebar-todos-modulos`, contrasta con el manifiesto y el harness, no con `goal.md`.
**CORREGIDO el 2026-07-29:** este apunte decía que `/pdc` seguía «sin migrar a dark mode», con el
body en el fallback claro `rgb(245,245,247)`. Eso **caducó** y me indujo a arrancar F2 con una
premisa falsa. Medido ese día antes de tocar nada: el body ya estaba en `rgb(11,16,13)` y la grilla
en `rgba(28,36,31,0.92)`. Lo único visiblemente roto era `.pdc-message-neutral`, en tinta casi negra
sobre el canvas oscuro. `/pdc` cerró F2 ese mismo día (capa, tokens, manifiesto y head segmentado).

Relacionado: [[navbar-css-consumidor-vivo]], [[sidebar-default-collapsed]].
