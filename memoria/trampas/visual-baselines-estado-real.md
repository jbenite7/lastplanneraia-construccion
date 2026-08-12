---
tipo: trampa
estado: vigente
fecha: 2026-07-28
verificado: 2026-08-12
areas: [qa, design-system]
fuente: memoria-claude
origen: lps-aia-visual-baselines-estado-real
resumen: Las baselines visuales del lab están todas rojas y las de states-feedback ni siquiera se comparan; medir el delta antes de culpar a tu cambio
---
Medido el 2026-07-27 sobre `main`. Antes de aceptar que un cambio de CSS rompió una baseline visual, revierte tu archivo y vuelve a correr: casi siempre el rojo es previo.


> **Pase de veracidad del 2026-08-12 — la afirmación de abajo ya no es cierta tal cual, y la
> corrección importa más que el dato.**
>
> `npm run test:visual:lab` en local, sobre `b9b65cb0`: **`20 passed`, RC=0**. **En local no falla
> ninguna familia.** En CI, sobre el **mismo sha**, fallan **18 de 20** con `ratio 0.03` contra una
> tolerancia de `0.002` — y las diferencias son casi idénticas entre familias (26952, 27211, 25536,
> 27012 píxeles), que es lo que descarta un cambio de diseño y apunta al render de plataforma.
>
> **Causa medida:** los 60 goldens están versionados **sin sufijo de plataforma**
> (`tests/browser/__screenshots__/auth/login-dark-1180x820.png`): un solo juego para macOS y Linux.
> El recibo verde de `runtime` es **honesto** y solo vale en la máquina que lo midió.
>
> **Por qué nadie lo vio en un mes:** el job de CI que lo comprobaría no se ejecutaba desde el
> **2026-07-17** (`needs: design-system-static`, con el static rojo). No es una regresión nueva: es
> una ceguera vieja que se destapó al arreglar el static. Ficha: `D-GAC-4`, **abierta**.
>
> **Y un intento fallido que conviene no repetir:** mover los goldens a carpeta por plataforma pone el
> gate estático en rojo — un manifiesto del contrato los ancla **por ruta y por hash**. Revertido en
> `949bb644`. La opción sigue siendo la correcta, pero es un frente, no un retoque.

- **`design-system-lab.visual.mjs`: todas las familias fallan** (actions, bi-primitives, data-display, forms-filters, foundations, overlays…). `actions-dark-1180x820` da 68.014 px con el árbol limpio. No es tu cambio.
- **`states-feedback-dark-*.png` no se compara nunca.** En `design-system-lab.visual.mjs`, la rama `if (scenario.family === STATES_FEEDBACK_FAMILY)` hace `return` tras `assertStatesFeedbackVisualContract` + `captureEvidence`, **antes** de `toHaveScreenshot`. Los golden existen en `__screenshots__` pero están muertos: tocar esa familia no requiere aprobación visual.
- **`programa-general.visual.mjs` y `programacion-intermedia.visual.mjs` 1180×820** están rojas por un reflow ajeno (la leyenda ocupa una fila en la baseline y dos en el render): 43.973 px y 30.812 px respectivamente con el árbol limpio. `programacion-intermedia` 1440×900 sí está verde.
- ~~**`test:design-system:static` da 323 pass / 1 fail**~~ — **medido de nuevo el 2026-08-07: la suite estática corre verde en sus ocho gates** desde el árbol principal y con el índice limpio. Sigue siendo cierto que `contracts.test.mjs` exige `worktree and index must be clean`, así que un árbol sucio la pone en rojo sin que haya regresión.
- **biome no es un gate verde**: **859 errores, 2.610 avisos y 397 infos** en `check:frontend` (eran 879 errores el 2026-07-27) y `check:design-system:biome` también sale en rojo; todos de formato preexistente. Reformatear un archivo para «arreglarlo» genera diffs de ~1000 líneas ajenos a la tarea.

**Lo que este pase NO reverificó** (exige Playwright y stack servido, no lectura): las cifras de
píxeles de las baselines visuales del laboratorio y de las dos rejillas, y el `return` temprano de
`states-feedback`. Se dejan como estaban, medidas el 2026-07-27.

**Cómo medir el delta real:** copia tu archivo al scratchpad, `git checkout -- <archivo>`, corre la baseline, restaura. Solo toca los tuyos — el worktree suele tener archivos de sesiones paralelas.
