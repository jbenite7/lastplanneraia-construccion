---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-07-22
areas: [design-system]
fuente: memoria-claude
origen: lps-aia-css-layer-cascade
resumen: Cascada de capas CSS del design system — styles.css vive en module.components; para ganarle en !important hay que usar @layer components de nivel superior (receta PS)
---
En lps-aia, `aia-design-system.css` declara `@layer reset, vendor, theme, base, layout, components, utilities, module, legacy-overrides;` e importa `styles.css` con `layer(module)`. Como styles.css anida `@layer components { }` internamente, sus reglas quedan en **`module.components`**, que en la inversión de `!important` vence a reglas planas de `module` (p. ej. `.aia-modal .form-control { background:#fff !important }` gana a un override module-level de mayor especificidad).

**Why:** costó ~1h de diagnóstico en la migración dark de Programación Intermedia (2026-07-22); las sondas con `getComputedStyle` engañaban además por `transition: all 0.2s` (leer a t=0 devuelve el valor viejo — esperar ~300ms).

**How to apply:** para sobreescribir con `!important` reglas de styles.css en un CSS de módulo (cargado vía `<link>` tras el entrypoint), usar un bloque **`@layer components { }` de nivel superior** — receta ya usada por `programacion-semanal.css` y ahora por `programacion-intermedia.css` (bloque final). Para reglas normales basta `@layer module` + orden posterior. El gate `design-system-audit.mjs` penaliza `var(--aia-*)` crudos en módulos (`raw-token-in-module`) y selectores que empiezan por `html `/`body ` (`global-module-selector`): usar `--ds-*` y `body.xx-page` (sin espacio tras body). Relacionado: [[branch-preexisting-red-gates]].

**Ampliación del 2026-08-11 (frente `contadores-cero`): la receta de arriba no siempre basta.**
`@layer components` de nivel superior gana a `module` y a `module.components`, pero **pierde
contra una SUBcapa de la propia `components`**. Medido: `buttons.css` entra por
`@import url("/css/buttons.css") layer(components)` (`aia-design-system.css:35`) y **se envuelve a
sí mismo** en `@layer components { }`, así que sus reglas quedan en `components.components`. Su
`.pdc-legend-item { display: inline-flex !important }` (entonces `buttons.css:971`) derrotó a una regla
`!important` de mucha más especificidad puesta en `components` a secas — la regla estaba en el
CSSOM, casaba con el elemento, y el computado seguía siendo `flex`.

La solución que funcionó **no** fue subir la apuesta ni tocar `buttons.css` (que es de PG, PI y
PS): fue escribir la regla en la **misma subcapa**, anidando `@layer components { @layer
components { … } }` en el CSS del módulo, donde gana por especificidad y por orden de origen.

Regla práctica: antes de dar por hecho que una capa gana, **averigua si el rival vive en una
subcapa**, y recuerda que un `@import ... layer(X)` sobre un archivo que ya anida `@layer X` da
`X.X`, no `X`. Ver también [[valor-declarado-no-es-valor-computado]].

Nota del pase 8 (2026-08-12, sobre `0e45ba1d`): el ejemplo concreto ya no se reproduce tal cual —
`.pdc-legend-item` está hoy en `buttons.css:976` y su `display: inline-flex` perdió el
`!important` (`:977`); un refactor del mismo 2026-08-11 lo sacó además de un `:where()` compartido
(comentario en `buttons.css:52-58`). El mecanismo sigue vigente y verificado:
`aia-design-system.css:35` importa `buttons.css` con `layer(components)` y `buttons.css:1` se
envuelve en `@layer components`, así que sus reglas siguen viviendo en `components.components`.
