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
- ~~**`test:design-system:static` da 323 pass / 1 fail**~~ — **medido de nuevo el 2026-08-07: la suite estática corre verde en sus ocho gates** desde el árbol principal y con el índice limpio. Sigue siendo cierto que `contracts.test.mjs` exige `worktree and index must be clean`, así que un árbol sucio la pone en rojo sin que haya regresión.
- **biome no es un gate verde**: **859 errores, 2.610 avisos y 397 infos** en `check:frontend` (eran 879 errores el 2026-07-27) y `check:design-system:biome` también sale en rojo; todos de formato preexistente. Reformatear un archivo para «arreglarlo» genera diffs de ~1000 líneas ajenos a la tarea.

**Lo que este pase NO reverificó** (exige Playwright y stack servido, no lectura): las cifras de
píxeles de las baselines visuales del laboratorio y de las dos rejillas, y el `return` temprano de
`states-feedback`. Se dejan como estaban, medidas el 2026-07-27.

**Cómo medir el delta real:** copia tu archivo al scratchpad, `git checkout -- <archivo>`, corre la baseline, restaura. Solo toca los tuyos — el worktree suele tener archivos de sesiones paralelas.
