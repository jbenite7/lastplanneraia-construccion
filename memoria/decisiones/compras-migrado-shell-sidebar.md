---
tipo: decision
estado: vigente
fecha: 2026-07-29
areas: [design-system, pdc]
fuente: memoria-claude
origen: lps-aia-compras-migrado-shell-sidebar
resumen: "Compras (/contratos, /listado-actividades, /pdc) usa el shell sidebar canónico desde el 2026-07-25 aunque el goal sidebar-todos-modulos las excluyó a propósito; el harness lista 23 rutas pero solo cuenta 21 como migradas, y las dos que deja fuera son /contratos y /listado-actividades"
---
El 2026-07-25 se migraron `/contratos`, `/listado-actividades` y `/pdc` al shell sidebar canónico.
El harness `tests/browser/shell-sidebar-rollout.mjs` lista **23** rutas en `ALL_ROUTES`
(`:15-38`), las mismas 23 que declara `docs/design-system/manifests/foundation-shell.json`.

**Ojo con el matiz, medido el 2026-08-03:** listadas no es lo mismo que migradas. El conjunto
`MIGRATED` del harness (`:39`) tiene **21**, y las dos que excluye son precisamente
`/listado-actividades` y `/contratos` — dos de las tres rutas que esta nota da por migradas. `/pdc`
sí está dentro. Antes de citar esta nota como prueba de que Compras entera pasó al shell, mira esa
línea: el harness las recorre, pero no las cuenta en el conjunto migrado.

**Sobre el goal `sidebar-todos-modulos`** (revisado el 2026-08-03): su `goal.md` excluye a Compras,
y eso **no es un texto olvidado**. El goal cerró el 2026-07-31 con una sección «Cierre formal» que
documenta la omisión como excepción deliberada: «Compras… omitidas — PDC v2 tiene su propia
navegación; las rutas viejas ya están retiradas». Las dos cosas son ciertas a la vez: aquel goal no
migró Compras, y Compras llegó al shell sidebar por otra vía. No hay nada que corregir en el goal;
lo que había que corregir era esta nota, que lo acusaba de estar desactualizado.

**Why:** el usuario revirtió esa exclusión explícitamente al arreglar el navbar huérfano; sin este
apunte, una sesión futura que lea el goal asumiría que Compras sigue en navegación legacy.

**How to apply:** trata las 23 rutas listadas como el alcance recorrido y las 21 de `MIGRATED`
como el alcance realmente migrado; no las confundas. Antes de citar el alcance de
`sidebar-todos-modulos`, contrasta con el manifiesto y el harness, no con `goal.md`.
**CORREGIDO el 2026-07-29:** este apunte decía que `/pdc` seguía «sin migrar a dark mode», con el
body en el fallback claro `rgb(245,245,247)`. Eso **caducó** y me indujo a arrancar F2 con una
premisa falsa. Medido ese día antes de tocar nada: el body ya estaba en `rgb(11,16,13)` y la grilla
en `rgba(28,36,31,0.92)`. Lo único visiblemente roto era `.pdc-message-neutral`, en tinta casi negra
sobre el canvas oscuro. `/pdc` cerró F2 ese mismo día (capa, tokens, manifiesto y head segmentado).

Relacionado: [[navbar-css-consumidor-vivo]], [[sidebar-default-collapsed]].
