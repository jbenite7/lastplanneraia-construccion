---
tipo: decision
estado: derogada
fecha: 2026-07-29
areas: [design-system, pdc]
fuente: memoria-claude
origen: lps-aia-compras-migrado-shell-sidebar
resumen: "DEROGADA: las tres rutas de Compras (/contratos, /listado-actividades, /pdc) se eliminaron el 2026-08-04 con el PDC v1. El harness quedó en 20 rutas listadas y 20 migradas"
---

> [!warning] Derogada el 2026-08-04 — las tres rutas ya no existen
> `/contratos`, `/listado-actividades` y `/pdc` **se eliminaron** con el PDC v1. Todo el matiz que
> esta nota cuidaba —23 listadas contra 21 migradas, y cuáles eran las dos excluidas— dejó de existir
> con su sujeto.
>
> **El harness había quedado roto, y esta nota fue lo que lo destapó.**
> `tests/browser/shell-sidebar-rollout.mjs` seguía listando las tres rutas retiradas, con `/pdc` aún
> dentro de `MIGRATED`: las dos primeras salían `PENDING` y `/pdc` **reventaba la ejecución entera**
> con un timeout esperando `[data-shell-pattern="sidebar"]` sobre un 404. Podado el 2026-08-04 →
> **20 listadas, 20 migradas, 123/123 checks en verde.**
>
> Lo que sigue en pie del cuerpo: la exclusión de Compras en el goal `sidebar-todos-modulos` fue
> deliberada y su «Cierre formal» lo documenta. Se conserva como registro.
>
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
