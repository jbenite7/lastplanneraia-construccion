---
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
