---
tipo: trampa
estado: vigente
fecha: 2026-07-28
areas: [qa, design-system]
fuente: memoria-claude
origen: lps-aia-visual-baselines-estado-real
resumen: Las baselines visuales del lab están todas rojas y las de states-feedback ni siquiera se comparan; medir el delta antes de culpar a tu cambio
---
Medido el 2026-07-27 sobre `main`. Antes de aceptar que un cambio de CSS rompió una baseline visual, revierte tu archivo y vuelve a correr: casi siempre el rojo es previo.

- **`design-system-lab.visual.mjs`: todas las familias fallan** (actions, bi-primitives, data-display, forms-filters, foundations, overlays…). `actions-dark-1180x820` da 68.014 px con el árbol limpio. No es tu cambio.
- **`states-feedback-dark-*.png` no se compara nunca.** En `design-system-lab.visual.mjs`, la rama `if (scenario.family === STATES_FEEDBACK_FAMILY)` hace `return` tras `assertStatesFeedbackVisualContract` + `captureEvidence`, **antes** de `toHaveScreenshot`. Los golden existen en `__screenshots__` pero están muertos: tocar esa familia no requiere aprobación visual.
- **`programa-general.visual.mjs` y `programacion-intermedia.visual.mjs` 1180×820** están rojas por un reflow ajeno (la leyenda ocupa una fila en la baseline y dos en el render): 43.973 px y 30.812 px respectivamente con el árbol limpio. `programacion-intermedia` 1440×900 sí está verde.
- **`test:design-system:static` da 323 pass / 1 fail**, y ese 1 es `contracts.test.mjs` exigiendo `worktree and index must be clean` — se resuelve al commitear, no es un red real. Esto corrige lo que decía [[branch-preexisting-red-gates]].
- **biome no es un gate verde**: 879 errores en `check:frontend` y 1 en `check:design-system:biome` (`shell-sidebar.css`), todos de formato preexistente. Reformatear un archivo para «arreglarlo» genera diffs de ~1000 líneas ajenos a la tarea.

**Cómo medir el delta real:** copia tu archivo al scratchpad, `git checkout -- <archivo>`, corre la baseline, restaura. Solo toca los tuyos — el worktree suele tener archivos de sesiones paralelas.
