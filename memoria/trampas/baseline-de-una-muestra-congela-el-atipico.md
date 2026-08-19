---
capa: wiki
tipo: trampa
estado: vigente
fecha: 2026-08-18
areas: [design-system, qa, proceso]
fuente: sesión del 2026-08-18, re-aprobación del presupuesto de runtime (generación 0.3.5)
resumen: el gate de runtime mide una sola vez y sus métricas de tiempo varían de 174 a 744 ms entre corridas; congelar una muestra como baseline hornea el valor atípico y produce falsas alarmas que cuestan días
---
`npm run test:runtime-budget:measure` toma **una** muestra por métrica. Para el tamaño del CSS eso
da igual —`cssGzipBytes` salió 196.733 en siete corridas seguidas, al byte—, pero para las métricas
de tiempo no: en la misma máquina y en el mismo commit, `initializationMs` dio 174,9 · 190,7 ·
226,3 · 236 · 237,5 · 239,5 · **744,2**.

**Lo que esto provoca, medido dos veces:**

| Generación | Métrica congelada | Qué pasó |
|---|---|---|
| 0.3.4 (2026-08-12) | `handsontableInteractionMs` = 134,6 | Es el **mínimo de toda la serie** (0.3.3 medía 280,7; 0.3.6 midió 180,4; 1.0.0 midió 234,2). Todo lo que vino después «violaba» el techo sin que nada se hubiera degradado. |
| 0.3.5 (2026-08-18) | `initializationMs` = 191,4 | Mismo error, distinta métrica: la corrida siguiente dio 744,2 y rompió el gate recién aprobado. |

**El diagnóstico que parece obvio y es falso:** «la máquina está cargada». Se comprobó y no era eso.
`initializationMs` bajó de 1.070,6 (donde se midió 0.3.4) a ~230 en el equipo de esta sesión: un
arranque cinco veces más rápido no convive con una interacción inflada por falta de CPU. Con la
máquina descargada, `handsontableInteractionMs` siguió estable entre 271 y 277 en cuatro corridas.
La varianza no viene de la carga: viene de tomar una sola muestra.

**El criterio que deja esta página:**

1. **Ante una violación, mira la serie histórica antes que el diff.** Las mediciones sueltas viven en
   `docs/design-system/runtime-measurements/`. Si el valor de hoy se parece a los de dos
   generaciones atrás y el atípico es el baseline, no hay regresión que buscar.
2. **Separa las métricas deterministas de las volátiles.** El tamaño de un asset es reproducible al
   byte y se puede congelar con confianza. Un tiempo medido una vez, no.
3. **No apruebes un baseline de tiempos desde una máquina de desarrollo.** El entorno de CI es
   comparable consigo mismo; un portátil con dos stacks Docker no lo es ni consigo mismo.

**Lo que queda abierto:** que el medidor tome varias muestras y se quede con la mediana. Es un
cambio en el diseño del gate, con plan y revisión propios — decisión del usuario del 2026-08-18 de
no colarlo dentro de otro frente. Mientras tanto, cada re-aprobación de las métricas de tiempo
depende de una corrida de CI.

Ver también [[exec-en-contenedor-vivo-corre-el-repo-ajeno]], que mordió en la misma sesión por otro
lado, y [[aislar-stack-docker-por-worktree]].
