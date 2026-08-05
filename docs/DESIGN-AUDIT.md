# Design Audit — hallazgos medidos

Registro de hallazgos de UX y diseño de la aplicación, con su severidad y su disposición real.

**Este archivo no es contrato.** El contrato de consumo es `DESIGN.md` (raíz) y la autoridad
ejecutable vive en `docs/design-system/`. Aquí solo se anota **qué se midió, qué se hizo y qué
falta** — es el equivalente de `docs/EXPERIMENTS.md` para la fase de auditoría del journey
`improve-app` (`docs/IMPROVE-APP-PLAN.md`, fases 2 y 4).

**Origen de los datos.** Nada de esta tabla se midió aquí: es el volcado del registro de decisiones
`docs/superpowers/decisiones-pendientes-2026-08-03.md` (54 entradas A-*/B-*/C-*, producto de 38
ciclos y 8 barridos de la campaña de cierre de dark mode) más la disposición real de cada entrada
según el ledger de esa campaña. Cuando ambas fuentes discrepan, manda el ledger, que es posterior.

**Severidad (0-4)**, escala de Nielsen: 0 = no es un problema · 1 = cosmético · 2 = menor ·
3 = mayor · 4 = catástrofe. La columna «Heurística» cita el número de Nielsen cuando la fuente lo
nombra, y si no, el principio de diseño que la entrada incumple (deferencia al contenido, un acento
por vista, jerarquía por espaciado y no por cromo, piso de accesibilidad).

**Estados posibles:** `done (commit <sha>)` · `pendiente (Task N)` — task viva del plan de la
campaña · `backlog ICE` — sin task, vive en `docs/EXPERIMENTS.md` · `cerrado sin código` — medido y
resuelto sin cambio · `no aplica: módulo eliminado` — el PDC v1 se borró el 2026-08-04.

## UX Audit Findings

| Issue | Heuristic | Severity (0-4) | Fix | Status |
|---|---|---|---|---|
| **A-1** · Los goldens de tabla estaban sin recapturar y el visual de Programa General en rojo correcto | Visibilidad del estado del sistema (N1), aplicada a las redes de prueba | 3 | El usuario revisó los pares esperado/actual/diff de PG (42.763 px) y PI (31.508 px) y autorizó la recaptura; 4/4 visuales en verde | done (commit 560a98fd) |
| **A-2** · Densidad compacta: alto de fila 36 → 24 px | Eficiencia de uso (N7) | 2 | Aplicada y ratificada. Resultado honesto: **no gana filas** en PG/PI con datos reales, porque el texto envuelto domina la altura — el token es un mínimo, no un máximo. Lo que sí arregló: cabeceras truncadas | done (commit 67f35c4) |
| **A-3** · La rejilla de Handsontable era lo más brillante del área de contenido: `rgb(203,213,225)` a 11,96:1 sobre el fondo de celda, en 34 celdas | Deferencia al contenido | 3 | Separadores horizontales tokenizados a `var(--ds-active-border)`, cero verticales. Luminancia del borde 0,657 → 0,0617 frente a 0,719 de la tinta de datos: de empatar a 11,6× por debajo. Cero hex en `public/css/` | done (commit 1835d379) |
| **B-1** · El barrido horario corre en sesión, no como tarea en la nube | — (decisión de método) | 0 | Ratificada: una tarea en la nube no podía reinyectar hallazgos al plan | cerrado sin código |
| **B-2** · La primera pasada del barrido espera a que cierre el task que edita CSS de tablas | — (decisión de método) | 0 | Ratificada: medir un árbol a medio cambiar produce hallazgos falsos | cerrado sin código |
| **C-1** · 22 ramas viejas sin contenido único | Higiene de repositorio | 0 | Censo en `docs/superpowers/ramas-viejas-2026-08-03.md`, 22/22 verificadas sin contenido único. Borrado pendiente de mano del usuario | pendiente (Task 28) |
| **C-2** · ~2.600 hallazgos estructurales: `!important`, `css-outside-layer`, `raw-token-in-module` | Consistencia y estándares (N4) | 3 | Requiere que el usuario apruebe el inventario inicial de excepciones justificadas. Es la campaña larga y el punto natural de corte | backlog ICE |
| **C-3** · `pdc.css:318` — borde-acento del toast marcado por el detector | Un acento por vista | 1 | Sin objeto: `pdc.css` ya no existe | no aplica: módulo eliminado |
| **C-4** · 23 hallazgos de `pdc.css` y 16 de PS fuera de la rampa tipográfica | Consistencia y estándares (N4) | 2 | Los 23 de `pdc.css` se fueron con el módulo; los de PS los absorbió el colapso de racimos de C-40. El remanente es deuda ancha, contabilizada en la baseline y cubierta por C-2 | cerrado sin código (resto en C-2) |
| **C-5** · Sidecar `.impeccable/design.json` desactualizado frente a `DESIGN.md` | Consistencia y estándares (N4) | 1 | **No ejecutable como se planteó: «regenerarlo es un comando» era falso.** `/impeccable document` es un procedimiento de autoría que reescribe `DESIGN.md` *y* el sidecar; no hay script en `scripts/` ni entrada en `package.json`. Regenerar de verdad tocaría `DESIGN.md`, que es contrato gobernado y tiene su propio gate. El sidecar **no se tocó**. Deriva medida: `generatedAt` 2026-08-03 frente a commits posteriores de `DESIGN.md`, y **una sola frase** de `narrative.rules` que aún nombra «PDC, Contratos, Listado de Actividades», eliminados el 2026-08-04. `scope`, `densityException`, `stateChannels` y `colorMeta` siguen coherentes. **Recomendación:** corregir esa frase en el próximo paso que toque `DESIGN.md`; regeneración completa cuando se ejecute `/impeccable document` con su gate | no ejecutable (Task 27) · deriva acotada y documentada |
| **C-6** · Contenido de «HOMECENTER CALI» en la fila 1 del sandbox `pdc_sandbox_e2e` no coincide con el seed | Integridad del fixture | 2 | Sin investigar: puede ser residuo de importación manual o un bug de importación real | backlog ICE |
| **C-7** · Gatillo de filtro de DataTables sin verificación visual | Visibilidad del estado del sistema (N1) | 1 | **Cierra de otra forma:** el gatillo no existe en ningún producto — su único uso es un fixture del laboratorio, y las DataTables del producto llevan `sorting_disabled`. Medido en el lab: 4×4 px. No es deuda de producto | cerrado sin código |
| **C-8** · `state-tint-exceptions.json` anclado por número de línea | Robustez del contrato de test | 2 | **Corrección (Task 27):** la fila anterior daba esto por cerrado, pero confundía los dos inventarios hermanos. Lo que la sesión paralela migró a v3.0.0 fue `state-token-exceptions.json`; **`state-tint-exceptions.json` seguía con `"line"` en sus 6 entradas**. Migrado ahora a ancla por firma (`file` + `selector` + `token` + `occurrence`), v2.0.0, reusando `scripts/design-system/state-token-locator.mjs` en vez de duplicar el troceo. El guard gana la prueba de fragilidad (insertar una línea antes de las entradas no mueve el ancla) y rechaza `line` como campo | done (Task 27 · `aeb58277`) |
| **C-9** · La densidad no gana filas: la fila mediana mide 111 px con un token de 24 (4,6×), gobernada por el texto envuelto de «Actividad» | Eficiencia de uso (N7) | 3 | Exige decidir qué hacer con esa columna (truncar con detalle al pasar el cursor, ancho fijo, o dos líneas máximo). Es decisión de producto: afecta a cuánto texto lees de un vistazo | backlog ICE · decide: usuario |
| **C-10** · Los chips contadores del PDC no se pueden usar con teclado (WCAG 2.1.1, nivel A) | Accesibilidad — nivel A | 3 | Sin objeto: `/pdc` y sus chips ya no existen | no aplica: módulo eliminado |
| **C-11** · Reglas `@media` pensadas para tablet alcanzan el viewport canónico de 1180 px | Consistencia y estándares (N4) | 2 | **Auditoría emitida (Task 27), nada aplicado.** 40 media queries con condición de ancho alcanzan 1180 px; **13 tienen techo**. **Recomendación: solo 1 de las 13 es acción clara ahora** (la de `768–1199.98` con `!important` que impone 44 px táctiles al escritorio); 10 son escaleras de escala que **hoy hacen trabajo** y 2 son una cura, no una herida. Detalle y razonamiento en §Auditorías C-11/C-15/C-20 | informe emitido (Task 27) · decide: usuario |
| **C-12** · Programación Intermedia se queda sin acción primaria | Jerarquía visual; reconocimiento antes que recuerdo (N6) | 2 | No había candidata defendible sin inventar criterio de dominio. Requiere que el usuario diga cuál debe destacar | backlog ICE · decide: usuario |
| **C-13** · Los chips de PI y PS envuelven a dos líneas por un ancho fijo de 155 px | Eficiencia de uso (N7) | 2 | Quitar el ancho fijo y dimensionar por contenido, como ya hace PG | backlog ICE |
| **C-14** · El aviso de «qué retiene el estado» ya existe por tres vías y aun así el usuario no lo vio | Visibilidad del estado del sistema (N1) — descubribilidad; peso visual del signifier | 2 | **Medido en la lente de Norman (Task 22) y reclasificado: el caso del usuario fue «había indicio y no lo vi», y el indicio pesa menos que el dato que lo rodea.** `.ps-missing-assignment` (`programacion-semanal.css:1690`) pinta `⚠ Sin asignar` con `--ds-color-state-critical-text` (`#ffcdc8`) a `--ds-type-size-sm` (14 px) y **sin `font-weight`**: el mismo tamaño y el mismo peso que la tinta de datos de la celda. Contra el fondo de celda (`--ds-active-bg-page`, `#111a15`) da **12,53:1**, pero el texto normal (`#f7faf8`) da **16,90:1** — la marca es **más apagada que el dato normal** (73 % de su luminancia) y entre ambos solo hay **1,35:1**, por debajo del 3:1 que WCAG 1.4.11 pide para distinguir por color. Y el segundo canal que el propio código pretende —teñir la celda— **está muerto**: `td.classList.add('ps-cell-empty-alert')` (`hot.js:2542`) pone una clase que **no tiene ninguna regla CSS en todo el árbol** (`grep` → 2 usos en JS, 0 en CSS). Queda un solo canal, el matiz, y encima con menos peso. **Remedio propuesto, NO aplicado:** dar regla a `.ps-cell-empty-alert` con la superficie de estado crítico ya tokenizada y subir el peso del `⚠` (negrita y un escalón de tamaño en el glifo), de modo que la marca gane por forma y fondo, no solo por color | backlog ICE · decide: usuario |
| **N-1** · En Programación Intermedia el sistema **avisa después del hecho** en vez de impedirlo: editas una restricción de una actividad sin Responsable AIA, la edición entra, y solo entonces se revierte la celda con un error | Restricción (*forcing function*) mejor que aviso; golfo de ejecución | 3 | `programacion_intermedia/hot.js:4008-4016` comprueba `hasResp` **dentro de `afterChange`**, o sea cuando el cambio ya ocurrió: llama a `revertCell()` y emite «No puede gestionar restricciones de una actividad sin asignar Responsable AIA». El sistema ya sabía que era imposible antes de dejar abrir el editor. Norman: la restricción de UI hace innecesario el aviso. Salida: `readOnly` condicional en las columnas de restricción mientras falte Responsable, con el motivo visible en la celda y la celda causante señalada. **Es cambio de comportamiento: se registra, no se aplica** | backlog ICE · decide: usuario |
| **N-2** · Confirmar compromisos es irreversible **para quien lo hace**: solo el rol `A` ve «Reabrir Semana» | Deshacer antes que confirmar; control y libertad del usuario (N3) | 3 | `syncPhaseUI()` (`programacion_semanal/hot.js:3115-3119`) muestra `#btn_reabrir_semana` únicamente si `getPermiso() === 'A'`. El Residente —que es quien confirma a diario, según `docs/CUSTOMER.md`— no tiene salida propia: depende de que un Admin la abra. La app sustituye el deshacer por una advertencia («Al confirmar, no se podrán modificar compromisos ni eliminar actividades», `hot.js:3517`), que es justo el intercambio que Norman desaconseja. **Puede ser correcto a propósito**: el candado de compromisos es doctrina Last Planner, no un descuido de UI. Por eso se registra como pregunta de dominio y no como defecto | backlog ICE · decide: usuario |
| **N-3** · «Responsable AIA sin asignar» bloquea en los dos módulos y **solo uno lo marca en la celda** | Signifier ausente; golfo de evaluación | 2 | La misma condición revierte ediciones en PI (N-1) y bloquea el cierre en PS (`hot.js:3420`). PS al menos pinta `⚠ Sin asignar` en la celda de Responsable (C-14); **PI no marca esa celda de ninguna forma** — `grep ps-missing-assignment` no tiene equivalente en `programacion-intermedia.css`. En PI la condición solo se descubre chocando contra ella. El patrón correcto ya existe en el repo, en el módulo hermano | backlog ICE |
| **N-4** · La rejilla de escritorio no tiene estado «guardando»; el único que existe vive en la vista móvil, que está fuera de alcance | Feedback ≤0,1 s; visibilidad del estado del sistema (N1) | 2 | PG, PI y PS emiten señal **solo al terminar** (`showFeedback('success', 'Guardado')` tras el `done` del AJAX: `programacion_intermedia/hot.js:2960`). Entre la tecla y el badge hay una ida y vuelta de red sin ningún acuse de recibo. El patrón correcto está dos veces en el repo: `programa_actualizar/hot_actualizar.js:861-867` pinta «Guardando... (n)» de inmediato y agrupa con *debounce* de 800 ms, y `programacion_semanal/hot.js:3360` hace lo mismo… pero solo dentro de `ps-legacy-card-view`, la vista de tarjetas móvil que `AGENTS.md` excluye del alcance. O sea: el canal existe justo donde no se puede trabajar | backlog ICE |
| **N-5** · El acuse de guardado se **anuncia en 1 de 4** rejillas: `#save-status` lleva `role="status"` solo en Programación Intermedia | Golfo de evaluación; accesibilidad — anuncio de estado | 2 | Mismo id, mismo componente, mismo papel, cuatro vistas: `programacion_intermedia.view.php:56` lo declara con `role="status"`; `programa_general.view.php:70`, `programacion_semanal.view.php:100` y `programaGeneralActualizar.view.php:106` **no**. Para quien usa lector de pantalla, guardar en tres de las cuatro pantallas de la cascada no produce ningún anuncio. Arreglo de una palabra por vista, pero toca contrato de accesibilidad y conviene verificarlo | backlog ICE |
| **N-6** · El resumen de cierre de semana **corta las listas a 8 sin decirlo**, mientras el contador de arriba muestra el total | Golfo de evaluación; visibilidad del estado del sistema (N1) | 2 | `buildCloseSummary()` acota las cuatro listas con `items.length < 8` (`programacion_semanal/hot.js:3436` y hermanos) y `renderSummaryList()` no añade ningún «y N más». Con 30 actividades bloqueadas el KPI dice **30** y el detalle enseña **8**: el usuario cree que arreglando esas ocho termina. Es el momento de mayor consecuencia del flujo semanal y el que peor informa | backlog ICE |
| **N-7** · El gate de cierre está implementado **dos veces** y pueden discrepar; cuando discrepan, el error no señala ninguna fila | Golfo de ejecución y de evaluación; ayuda a reconocer errores (N9) | 2 | El cliente deshabilita «Confirmar» con `hasBlocking` (`hot.js:3522`); el servidor puede aun así responder `No_Bloqueado`, y la UI lo traduce en «Se detectaron actividades sin compromiso o sin asignaciones obligatorias» (`hot.js:4056-4058`), un texto que **no dice cuáles**. El usuario atraviesa el modal entero creyendo que estaba listo y sale sin saber dónde mirar. La salida barata no es unificar los dos gates, sino que la respuesta del servidor devuelva los ids y la UI los filtre en la rejilla | backlog ICE |
| **C-15** · `buttons.css` (1.215 líneas) se auto-encapsula en `@layer components` **y** se importa con `layer(components)`, creando `components.components` | Consistencia y estándares (N4) — arquitectura de capas | 2 | **Auditoría emitida (Task 27), nada aplicado.** 8 casos: **4 de capa duplicada exacta** y 4 de anidamiento con capa distinta. Hallazgo que el registro no tenía: `buttons.css` y `access.css` **entran cada uno por dos puertas** (`aia-design-system.css` y `entrypoints/core.css`), así que un arreglo que toque una sola deja la otra. **Recomendación: diferir a ticket propio y no tocarlo dentro de una campaña visual** — hoy el accidente juega a favor y quitarlo cambia el orden de cascada en toda la app. Detalle en §Auditorías C-11/C-15/C-20 | informe emitido (Task 27) · decide: usuario |
| **C-16** · La caja interna del `.colHeader` renderiza 33 px donde el `th` mide 56: 23 px por columna desperdiciados; la flecha del selector tapa el último dígito de las fechas | Deferencia al contenido; eficiencia de uso (N7) | 3 | Token nuevo `--ds-table-header-pad-x` (3px) recupera 14-22 px por columna corrigiendo la aritmética del `container-type`. Las cabeceras se leen enteras **sin quitarle ancho a ningún dato**. 20 cabeceras truncadas → 0 | done (commits d877a76c, 5555127a, 12c457f3) |
| **C-17** · Cinco acciones de Programación Semanal se fueron a un menú «Más», entre ellas «Recargar» | Reconocimiento antes que recuerdo (N6); flexibilidad y eficiencia (N7) | 2 | Por decisión del usuario, «Recargar» y «BI Semanal» vuelven a la barra. Barra final: Autoprogramar · Agregar Actividad · Confirmar Compromisos · Reabrir Semana · Registrar TNP · Recargar · BI Semanal · «Más» (Leyenda, Imprimir, Exportar CSV). `scrollWidth == clientWidth`, sin desbordar | done (commit 9f4e9926) |
| **C-18** · `fitActionsRowSingleLine()` es código muerto con comentario falso (`hot.js:1203`) | Consistencia y estándares (N4) | 1 | **Hecho (`aeb58277`). Dos correcciones al enunciado:** no estaba en `programa_general` sino en **`programacion_semanal/hot.js`**, y no era una función suelta sino una **cadena entera** — `scheduleActionsRowFit()` no hacía más que llamarla tras un `setTimeout` y tenía **10 sitios de llamada**. Borrado el conjunto (2 funciones + `toolbarFitTimer` + los 10 sitios): **118 líneas, 0 referencias residuales**; se comprobó que ningún callback quedara vacío. Un **segundo** comentario falso, en `programacion-semanal.css:262`, citaba ese mismo JS y se corrigió después (ver fila de seguimiento). Quedan huérfanas las reglas `.ps-actions-stacked` y la variable `--ps-hot-scale` desde JS: **anotado, fuera de alcance**, a revisar con C-11 | done (Task 27 · `aeb58277`) |
| **C-19** · El tooltip de cabecera aparece también donde no hace falta: «Id» muestra un tooltip que dice «Id» | Estética y diseño minimalista (N8) | 1 | **Hecho (`aeb58277`).** La receta del ledger era correcta y es la que se siguió: el barrido se movió a `afterRender`, que es cuando el ancho de columna ya es el definitivo. `refreshHeaderTitles()` pone `title` solo si el nodo se recorta, comprobando **los dos cortes** que produce el CSS —`-webkit-line-clamp: 2` vía `scrollHeight` y palabra que no cabe vía `scrollWidth`—, con 1 px de margen para el redondeo subpíxel. Aplicado a los tres módulos del task 26: PG, PS y PI. Sondas con datos reales a 1180×820: **0 tooltips redundantes** (12/17/12 cabeceras, ninguna con `title`; «Id» limpio) y, forzando un recorte real, la cabecera recortada **sí** recibe el texto íntegro y sus vecinas no. Límite: con anchos reales ninguna cabecera se recorta hoy, así que el camino positivo se observó forzado, no en producción | done (Task 27 · `aeb58277`) |
| **C-20** · Tokens de color sin variante oscura (`--ds-color-surface`, `--ds-color-brand-architecture`) | Consistencia y estándares (N4) | 3 | **Auditoría emitida (Task 27), nada aplicado.** El censo encontró que la raíz es **otra y mayor** que la enunciada: además de 13 tokens estáticos de verdad, hay **47 consumos directos de la mitad clara de un par** —el token existe en par, pero el consumidor usa el nombre pelado en vez de `--ds-active-*`—, y 42 se concentran en `--ds-color-brand-primary` (33) y `--ds-color-brand-construction` (9). Dos precisiones al enunciado: el token se llama `--ds-color-domain-architecture`, y **`--ds-color-surface` ya tiene 0 consumos directos** (la cura de `312ba9b` aguantó). **Recomendación: empezar por esos 42, yendo por consumidor y cambiando el par completo fondo+texto** —nunca «dándole variante oscura al token», que es justo el intento que bajó el contraste a 1,67:1 y hubo que revertir. Detalle en §Auditorías C-11/C-15/C-20 | informe emitido (Task 27) · decide: usuario |
| **C-21** · La suite de navegador de PS estaba caída por entorno: 34 fallos de 35, desde antes de la campaña | Confianza en las redes de prueba | 4 | Dos clases de fixture rot eliminadas: membresía del proyecto 27 en el seed (12 casos) y carrera de semana en `selectProject` (9 casos). Línea base real medida 44/16; tras el arreglo 38/22. 11 casos exigen el stack de CI y 7 miden mobile/tablet que el repo borró a propósito — inalcanzables por diseño | done (commit 9e11f612) |
| **C-22** · El bloque blanco de `/indicadores` es un `<iframe>` de `app.powerbi.com`, otro origen | Consistencia y estándares (N4) | 2 | No se puede tematizar desde nuestras hojas por ningún medio legítimo. Queda enmarcarlo para suavizar el salto; cambiar el tema del informe es trabajo del usuario en Power BI. Descartado `filter: invert()`: destroza los datos | pendiente (Task 21) |
| **C-23** · Tres de las ocho pestañas de BI quedan fuera de vista (1626 px de pestañas en un carril de 1116) | Visibilidad del estado del sistema (N1) | 3 | No caben ni quitando iconos. Degradado de anuncio de corte en el borde derecho, como excepción de presupuesto documentada; el carril desplaza y la barra de scroll es visible | done (commit 539aaf68) |
| **C-24** · Los chips en cero gritan igual que los chips con alarma | Deferencia al contenido; un acento por vista | 2 | Chip en cero → `--ds-active-surface-raised` con tinta secundaria, contraste 8,99:1, sin `opacity`. A/B con datos reales: los 6 chips no-cero conservan su fondo byte a byte. **Corrección de la propia entrada:** con datos, el color saturado sí hace su trabajo — el problema solo existe en cero | done (commit 8bf5518c) |
| **C-25** · La marca «AIA» de `admin/` da 4,46:1 donde el mínimo AA es 4,5:1 | Accesibilidad — contraste AA | 1 | Subir un escalón de luminancia y verificar en las 8 rutas de admin. Ojo: la paleta de `admin/` está fuera del design system por `AGENTS.md` | pendiente (Task 23) |
| **C-26** · Dos modales distintos de PI comparten el id `modal_leyenda_colores`, y la «Leyenda de Colores» es inalcanzable por cualquier vía | Control y libertad del usuario (N3); consistencia (N4) | 3 | Verificado en vivo: el botón abre siempre la Guía Operativa. La Guía sustituye a la Leyenda muerta y su markup se borra, dejando el id único | pendiente (Task 19) |
| **C-27** · La guía operativa escribe sin tildes lo que los chips escriben con tilde | Consistencia y estándares (N4) | 1 | Son nombres de estado del dominio y el naming lo gobierna `GLOSARIO.md`: se corrige con visto bueno | pendiente (Task 20) |
| **C-28** · El formulario de crear usuario no marca qué campos son obligatorios (5 de 7 lo son, cero marcas) | Prevención de errores (N5) | 2 | Marcar **los dos opcionales** («Email (opcional)», «Cargo (opcional)») en vez de asteriscar cinco | pendiente (Task 24) |
| **C-29** · Los mensajes de error de admin se ven casi blancos: `rgb(250,234,231)`, indistinguibles del texto normal | Visibilidad del estado del sistema (N1); ayuda a reconocer errores (N9) | 3 | Token de texto crítico con su fondo, vía excepción justificada en `state-token-exceptions.json`: → `rgb(255,205,200)`, 11,42:1, croma 19→55. «Salir» conserva el aclarado. La ronda de fix descubrió **dos casos más** en `admin/dashboard.php` que la excepción afirmaba inexistentes, y los cerró | done (commits 539aaf68 + 58d3476b) |
| **C-30** · Solo 3 rutas declaran `<main>` y la mayoría no tiene `h1`; `/programa-general` salta de `h1` a `h3` | Accesibilidad — estructura del documento | 3 | Envolver el contenido en `<main>` y decidir el `h1` de cada pantalla, con `/dashboard/escalamientos` como patrón (la mejor estructura medida de la sesión). Los títulos de pestaña ya se cerraron en `e6f7f4c` | pendiente (Task 18) |
| **C-31** · 105 truncamientos silenciosos en toda la app, cero elipsis en ninguna parte; `3.5.2.1.1` se lee igual que `3.5.2.1`, que es otra actividad de la misma tabla | Visibilidad del estado del sistema (N1) — el dato se lee mal | 4 | Anchos de columna corregidos: **48 celdas y 20 cabeceras truncadas → 0 y 0 en 6 de 6 superficies vivas.** Id de PG 56→84 px, de PI 36→74. Subcontratistas: `proyectos@concreacero.com.co` y los NIT de 9 dígitos, enteros. Los 54 truncamientos de `/pdc` se fueron con el módulo | done (commits d877a76c, 5555127a, 12c457f3); parte PDC: no aplica |
| **C-32** · Los gráficos de BI traen `aria-label` pero no comunican sus datos (WCAG 1.1.1) | Accesibilidad — nivel A | 3 | Tabla equivalente oculta (`.sr-only`) junto a cada gráfico, **generada de la misma fuente que alimenta la serie** para que no pueda desincronizarse | pendiente (Task 26) |
| **C-33** · El estado vacío de `/control-cambios` es el único que deja al usuario sin salida | Ayuda y documentación (N10); control y libertad (N3) | 2 | Es una frase, pero explica una regla de dominio (de dónde nacen las solicitudes de cambio) y esa la tiene el usuario. El resto de estados vacíos de la app está notablemente bien | pendiente (Task 30) · si el usuario no da la frase → chip |
| **C-34** · Al pasar el ratón, el botón secundario adelanta a la acción principal: luminancia 0,443 frente a 0,245 del primario en reposo (+80%) | Jerarquía visual; un acento por vista | 3 | El hover del secundario sube a superficie elevada con el borde más vivo, en vez de rellenarse del verde del acento: luminancia 0,443 → **0,0715** (29% del primario), texto 8,23:1, borde 3,20:1 → 5,46:1. El primario no se toca | done (commit 293b2540) |
| **C-35** · El motivo por el que un botón está bloqueado vive solo en el `title`, y los 13 `disabled` nativos no son focalizables | Accesibilidad; ayuda a reconocer errores (N9) | 2 | Lo demás está bien resuelto (cuatro canales de distinción, motivo explícito). El remedio estándar —`aria-disabled` focalizable— devuelve el botón a pulsable y exige bloqueo por JS: es comportamiento | backlog ICE · decide: usuario |
| **C-36** · 34 de los 53 campos del modal de contrato del PDC tienen etiqueta visible pero no asociada | Accesibilidad — nivel A | 3 | Sin objeto: el modal de contrato del PDC v1 ya no existe. Si el PDC v2 monta un formulario equivalente, se vuelve a medir allí | no aplica: módulo eliminado |
| **C-37** · Solo 12 de los 24 gatillos decorativos de PG llevan `aria-hidden="true"` | Consistencia y estándares (N4); accesibilidad | 1 | **Hecho (`aeb58277`), pero no «en el renderer» como decía el plan: esa vía no funciona.** Medido con datos reales: en `afterGetColHeader` el botón todavía no existe (lo inyecta después el plugin `dropdownMenu`), y un barrido en `afterRender` sí marca los 24 pero **cada `render()` reusa los nodos de la tabla maestra y les borra el atributo** (24/24 → `render()` → 12/24, con el clon superior intacto). Resuelto con el patrón que el propio archivo ya usa para `.htFocusCatcher`: `MutationObserver` sobre el contenedor vigilando `childList` **y** la mutación de `aria-hidden`, escritura condicional y coalescido por frame. Sonda: **24/24, y sigue 24/24 tras `render()` forzado**; los 24 con `tabindex="-1"`. PI y PS ya estaban al 100 % (34/34 y 24/24): era inconsistencia exclusiva de PG | done (Task 27 · `aeb58277`) |
| **C-38** · El adaptador de `admin/` cubre tres variantes de botón y no puede alcanzar las otras tres: texto verde con borde azul o ámbar | Consistencia y estándares (N4) | 2 | El adaptador usa `:where()` (especificidad 0) y AdminLTE trae `.dark-mode .btn-outline-*` (0,2,0): el vendor gana siempre. Salida: anclar las tres variantes a `.dark-mode` sin `!important`, cambiando el par borde+texto. No es accesibilidad (5,24:1 a 10,07:1), es coherencia | pendiente (Task 23) |
| **C-39** · `Esc` no cierra los modales pese a que su configuración reporta `keyboard: true`, y `data-backdrop="static"` tampoco deja cerrar fuera | Control y libertad del usuario (N3); consistencia (N4) | 2 | No es trampa de teclado (la «×» es alcanzable con Tab), pero rompe la convención que todo usuario intenta primero. **Alcance sin medir:** verificado en un solo modal de los 12 | backlog ICE · decide: usuario |
| **C-40** · 16 tamaños de letra en un solo viewport de PI, diez de ellos en dos racimos indistinguibles por debajo de un píxel | Consistencia y estándares (N4); jerarquía tipográfica | 2 | Racimos colapsados a `--ds-type-size-sm` (14px) y `--ds-type-size-xs` (12px): 60 líneas, **cero que no sean `font-size`**. La baseline de deuda bajó: `off-scale-typography` 306 → 247. El espaciado ya estaba sano, así que no hace falta campaña equivalente | done (commit 20321285) |
| **C-41** · `/control-cambios` repite doce ids, y diez son buscadores de columna | Consistencia y estándares (N4); accesibilidad — asociación etiqueta-campo | 3 | Mismo mecanismo que C-26, multiplicado por diez: la mitad de esos filtros no puede recibir etiqueta ni ser alcanzada por script de forma fiable. Renombrarlos toca el cableado del filtrado, no la apariencia | backlog ICE |
| **C-42** · El recorrido del tabulador por la barra lateral atraviesa 6 selectores de semana intercalados en 20 paradas | Eficiencia de uso (N7) | 2 | Bien resuelto donde importa: las 20 paradas tienen anillo `2px solid`, ninguna fuera del viewport ni tapada. El menú se hace visible al recibir el foco, así que es ineficiente, no inaccesible. La convención ARIA sería una sola parada recorrida con flechas — cambia el comportamiento del teclado | backlog ICE · decide: usuario |
| **C-43** · Filtrar en el PDC cambia la tabla de 33 filas a 10 sin anunciar nada (WCAG 4.1.3), y los chips no dicen si están activos | Visibilidad del estado del sistema (N1); accesibilidad | 3 | Sin objeto: `/pdc` ya no existe. El patrón correcto sí sobrevive en Programación Semanal, que usa la misma clase con `role="button"` y `tabindex="0"` | no aplica: módulo eliminado |
| **C-44** · Las filas de «Capítulo» del PDC se pintaban con `rgb(139,64,17)` en crudo: 6,6× la luminancia de una fila normal, en 40 de 100 celdas visibles | Jerarquía por espaciado y tipografía, no por cromo; un acento por vista | 2 | Capítulo por peso tipográfico y filete en vez de bloque de color: luminancia 5,72× → **1,64×** (objetivo ≤2×). Compuerta de semántica pasada: el naranja significaba «esto es un encabezado», no un estado, así que quitarlo no pierde información. Hecho sobre un módulo hoy eliminado; la paridad para PG/PI queda como task propia | done (commit 1e479a94); paridad → Task 36 |
| **C-45** · El botón flotante se posa sobre la última fila y oculta parcialmente el valor de la última columna | Deferencia al contenido; visibilidad del estado (N1) | 2 | Confirmado con datos reales en `/programa-general` (29 filas): `tapaCelda: true`, el `0,0` de «Lib. Restr.» aparece cortado. **No se arregla desplazando**: la tabla virtualiza y siempre hay una fila al fondo. Salida recomendada: reservar hueco al final del área de scroll, con verificación de que la tabla no pierde alto | backlog ICE |
| **C-46** · `/programa-general-actualizar` repite diez ids, y es la vista que importa cronogramas | Consistencia y estándares (N4); prevención de errores (N5) | 3 | Origen localizado, dos fuentes: (1) `cargarDatosGeneralesPagina2.js` y `funcionesGenerales6.js` inyectan campos ocultos con los mismos ids que el PHP ya renderiza — explica 8 de 10 y las cuatro vistas afectadas; (2) copia-pega dentro de la vista, **ya arreglado** en `dc25242` (10 → 8). Queda decidir cuál copia manda; la recomendación es el PHP | pendiente (Task 15) |
| **C-47** · El guard del matiz de la leyenda de PG llevaba fallando desde antes de la campaña: exigía 35% de saturación y el píxel daba 29% | Confianza en las redes de prueba | 3 | Diagnóstico doble, ambas mitades verificadas: (a) `actividad-futura` sí cumplía el piso al nacer el guard (35,5%) y `6dfeb993` le bajó el croma al token un 19% al tokenizarlo a dark — el factor 1,75 se **deriva** del croma histórico, no se ajusta a la vara; (b) `en-curso` pedía teal pero `e32e7e84` cedió `progress` al azul a propósito: la banda nueva es **más estricta** (55° frente a 85°). `MIN_SATURATION` intacto | done (commits ba5da88c..0e56a592) |
| **C-48** · El gatillo de filtro de PS se pinta como una píldora de 11×32 px dentro de un botón de 13, y cuelga por debajo de la cabecera | Consistencia y estándares (N4); piso táctil de accesibilidad | 3 | `::before` de 22×32 (fondo, borde, radio) → 6×6 transparente, idéntico a PG y PI; botón 24×24 en los tres. **24 px es el piso real**, no un recorte: WCAG 2.2 SC 2.5.8 (AA) exige 24×24 CSS px, y los 44 px son SC 2.5.5 (AAA)/heurística táctil, que `DESIGN.md` §5 bis declara no aplicable a esta familia desktop-only. Los 44 px no caben, medido: el `thead` subía +20 px y rompía la paridad con PG/PI. Geometría sin mover, 25/25 goldens verdes sin recapturar | done (commit 606f64bd) |
| **C-49** · El botón de «Estado Operativo» esconde su nombre (0×0 px por un container query a 120 px, en una celda de 116) y da dos señales de color opuestas: punto verde con marco crítico | Visibilidad del estado del sistema (N1); un acento por vista | 3 | **p1 (el nombre) hecho:** apilado nombre/contador aprobado por el usuario, con la columna por encima del umbral — era eso o un muñón elidido. **p2 (el color) abierto:** elegir cuál de los dos hechos manda es significado de dominio — si el estado es «Lista para Confirmar» pero hay 2 condiciones pendientes, ¿la fila se lee como buena o como crítica? | p1: done (Task 8); p2: pendiente (Task 16) · decide: usuario |

## Auditorías C-11 / C-15 / C-20 (Task 27 — solo informe, nada aplicado)

Las tres se censaron con script sobre `public/css` el 2026-08-05. **Ninguna se aplicó:** reportan y
recomiendan; la decisión es del usuario. Método y sondas completas en
`.superpowers/sdd/2026-08-04-cierre-dark-mode-campana-decisiones/task-27-report.md` (no versionado).

### C-11 · Media queries que solapan 1180 px

**40** media queries con condición de ancho alcanzan 1180 px; **13 tienen techo** (`max-width`), o sea se
escribieron para un rango que no es «escritorio abierto» y aun así muerden el viewport de trabajo. No son
una sola cosa — son tres grupos con naturalezas distintas:

| Archivo | Línea | Rango | Qué hace |
|---|---|---|---|
| `programacion-semanal.css` | 1331/1334/1337/1340/1343 | `≤1650 … ≤1250` | escalan `--ps-hot-scale` 0,95→0,75 en `.ps-hot-header-actions` |
| `styles.css` | 2719/2725/2731/2737/2743 | `≤1650 … ≤1250` | escalan `--ps-module-scale` 0,95→0,75 en `.filaBotones` |
| `programacion-semanal.css` | 1447 y 1450 | `≤1180` | re-tokenizan `.pdc-legend` a la superficie activa |
| `programacion-semanal.css` | 1728 | `768–1199.98` | fuerza `min-width/min-height: var(--ds-target-min)` con `!important` |

1. **Las dos escaleras (10 de 13).** A 1180 px caen en el escalón `≤1250`: el contenido se encoge al
   **0,75** en el viewport canónico. Cubren la banda 1161–1440 px que ninguna otra regla alcanza — la
   misma que citaba C-18. Ahora que el compensador JS se borró, **son el único mecanismo que queda**.
2. **Las dos de `.pdc-legend`.** No son deuda: existen *para corregir* que `styles.css` pintaba un gris
   claro dentro de un `@media (max-width: 1180px)`. Es una cura, no una herida.
3. **La de `768–1199.98`.** La que el registro ya señaló dos veces; única que impone geometría táctil
   (44 px) al escritorio con `!important`.

**Recomendación.** *Ahora:* solo la línea 1728 — cambiar el techo a `1179.98px` la saca del viewport
canónico sin tocar lo que sí es tablet. Bajo riesgo, reversible. *Diferible y con medición antes:* las dos
escaleras; tocarlas es un cambio **visible** de tamaño en la barra de Semanal a 1180 px, no una limpieza, y
conviene mirarlo en navegador ahora que C-18 quitó el compensador JS. *No tocar:* las de `.pdc-legend`.

### C-15 · Dobles capas (`@import … layer(x)` sobre archivos ya auto-encapsulados)

**8 casos**, más de los registrados. **4 con la capa duplicada exacta**, que es la patología descrita:

| Importador | Archivo | Resultado |
|---|---|---|
| `aia-design-system.css:35` | `buttons.css` | **`components.components`** |
| `aia-design-system.css:46` | `access.css` | **`utilities.utilities`** |
| `design-system/entrypoints/core.css:23` | `buttons.css` | **`components.components`** |
| `design-system/entrypoints/core.css:25` | `access.css` | **`utilities.utilities`** |

Lo que el registro no tenía: **cada archivo entra por dos puertas** (el entrypoint viejo y el nuevo), así
que un arreglo que toque una sola deja la otra viva. Los otros 4 son anidamientos con capa distinta —
`handsontable-module.css` → `vendor.base`/`vendor.components` (desde `aia-design-system.css:10` y
`entrypoints/attach-handsontable.css:3`) y `styles.css` → `module.theme`/`module.components` (desde
`aia-design-system.css:34` y `entrypoints/core.css:22`)—; no son el mismo defecto, pero el sub-orden
interno decide igual y conviene tenerlos censados.

**Recomendación: diferir a ticket propio, y no tocarlo dentro de una campaña visual.** Hoy
`components.components` **juega a favor** — es lo que deja ganar al componente compartido. Quitar la capa
duplicada cambia el orden de la cascada de golpe y en toda la app; el efecto no es local ni predecible
leyendo, hay que medirlo. El task dedicado debería (a) decidir el orden que se quiere, (b) aplicarlo en las
**dos** puertas a la vez y (c) validar en navegador. Riesgo de hacerlo a la ligera: alto. De dejarlo: bajo.

### C-20 · Tokens de color sin variante oscura

De 60 tokens `--ds-color-*`, solo **10 declaran par claro/oscuro**. Hay **dos familias**, y el registro
solo nombraba una:

**Familia A — estáticos de verdad (13; 12 en uso).** Ni tienen `-dark` ni derivan de nada que dependa del
tema: `--ds-color-text-inverse` (53 usos), `--ds-color-border-strong` (11), `--ds-color-brand-aqua` (7),
`--ds-color-text-tertiary` (5), `--ds-color-border-default` (5), `--ds-color-domain-corporate` (4),
`--ds-color-border-subtle` (4), y con 1 uso cada uno `--ds-color-domain-construction`,
`--ds-color-domain-real-estate`, `--ds-color-domain-architecture`, `--ds-color-domain-corporate-deep` y
`--ds-color-surface-tint`.

**Familia B — la mitad clara de un par, consumida directamente (47 consumos).** Ésta es la raíz que
produjo el defecto: el token existe en par, pero el consumidor usa el nombre pelado —el valor **claro**—
en vez de pasar por `--ds-active-*`, y se queda claro haga lo que haga el tema.

| Token | Consumos | Dónde |
|---|---|---|
| `--ds-color-brand-primary` | **33** | `bi-control-tower.css`, `adapters/select2.css`, `adapters/sweetalert2.css`, `components/primitives.css` |
| `--ds-color-brand-construction` | **9** | `bi-control-tower.css` (×4), `design-system/core.css` (×2), `programacion-semanal.css` |
| `--ds-color-text-primary` | 2 | `components/dialog.css`, `lab.css` (ambos en `color-mix` al 60 %) |
| `--ds-color-surface-raised` · `--ds-color-text-secondary` · `--ds-color-focus-ring` | 1 c/u | `programacion-semanal.css:619` · `:419` · `login-brand-unified.css:328` |

Se descontaron 2 apariciones que **no** son defecto: `foundation.css:8-9` los usa como *fallback* dentro de
`var(--ds-active-X, var(--ds-color-X))`, que es el uso correcto. Y **`--ds-color-surface` tiene 0 consumos
directos**: la cura de `312ba9b` aguantó, lo que confirma cuál es el patrón bueno.

**Recomendación.** *Ahora, y es lo que más rinde:* los **42 consumos de `brand-primary` +
`brand-construction`** — 85 % del problema, concentrados en pocos archivos, y son colores de marca sobre
superficies oscuras, el escenario exacto que ya produjo un 1,67:1 medido. Ir **por consumidor**, cambiando
el par completo fondo+texto y midiendo contraste, como en `312ba9b`. **No** «arreglar el token» dándole
variante oscura: ése es precisamente el intento que hubo que revertir. *Después:* los 5 sueltos, baratos y
aislados, aunque `focus-ring` toca accesibilidad y merece medirse aparte. *Decisión de sistema, no
mecánica:* la Familia A — `--ds-color-text-inverse: #ffffff` con 53 usos puede ser correcto a propósito
(blanco sobre relleno de marca), y los `--ds-color-domain-*` ya tienen hermanos `-on-dark` que el tema sí
usa. Antes de tocar nada hay que decidir **si estos tokens deben ser conscientes del tema o si su gracia es
no serlo**; ésa es la pregunta que convierte C-20 de síntoma en raíz.

## Lente de Norman sobre PG → PI → PS (Task 22 — fase 3 de `improve-app`, IA-3)

Las siete entradas `N-*` salen de aplicar *The Design of Everyday Things* a los tres flujos núcleo
que `docs/CUSTOMER.md` señala como cuello de botella de los tres jobs a la vez: **confirmar
compromisos** (PS), **liberar restricciones** (PI) y **actualizar avance** (PG). **Nada se aplicó:**
seis de las siete son cambio de comportamiento o de contrato, y la regla de esta fase es registrar y
preguntar.

**Método y su límite, dicho sin adornos.** Todo se midió **leyendo el código de esta rama**, no
sondeando el navegador: los contrastes están **calculados a partir de los tokens** (`tokens.css` →
`aia-design-system.css`) con la fórmula de WCAG 2.x, no muestreados de un píxel renderizado. Es
suficiente para lo que se afirma —los valores son literales de token y la cadena de herencia se
verificó archivo a archivo— pero significa que **no se comprobó si el `⚠` de C-14 cae dentro del
viewport de 1180 px sin desplazamiento horizontal**, que es la otra explicación posible de «no lo vi»
y sigue sin medir. El contenedor Docker en marcha sirve el otro checkout, así que medirlo exige
montar la rama aparte; se dejó fuera por proporcionalidad.

**Los cuatro cortes de la lente y qué encontró cada uno:**

| Corte de Norman | Qué se buscó | Entradas |
|---|---|---|
| **Signifiers débiles** | ¿La marca que anuncia el estado pesa más que el dato que la rodea? | C-14 (medido), N-3 |
| **Restricción antes que aviso** | ¿Dónde una restricción de UI haría imposible el error, en vez de reprocharlo? | N-1 |
| **Feedback ≤0,1 s** | ¿Hay acuse de recibo antes de que termine la red? | N-4, N-5 |
| **Deshacer antes que confirmar** | ¿La acción irreversible tiene salida para quien la ejecuta? | N-2 |
| **Golfos de ejecución y evaluación** | ¿El usuario sabe qué hacer, y sabe qué pasó? | N-6, N-7 (y N-1, N-3 por su lado de evaluación) |

**Lo que la lente aporta y las dos anteriores no vieron.** Las fases 2 y 4 (`ux-heuristics` y
`refactoring-ui`) miraron la superficie: contraste, densidad, truncamiento, jerarquía. Esta mira **el
ciclo de acción**, y por eso da hallazgos de otra clase — tres de ellos son **canales muertos o
mudos**, no cosas mal pintadas:

1. `.ps-cell-empty-alert` se aplica desde JS y **no existe en CSS** (C-14).
2. El estado «guardando» de escritorio **solo existe en la vista móvil**, que el repo excluye (N-4).
3. `role="status"` está en **1 de las 4** declaraciones del mismo `#save-status` (N-5).

Ninguno de los tres se ve en una captura: los tres se ven mirando qué pasa **entre** que el usuario
actúa y que el sistema responde. Ése es el aporte de la fase.

**Lo que no es un hallazgo, y conviene decirlo.** `programa_actualizar` —el módulo de actualizar
avance— es el **mejor de los cuatro** en este eje: badge inmediato con contador de pendientes,
agrupación por *debounce*, guardia de salida con cambios sin guardar (`hot_actualizar.js:1230`) y un
«Decisión revertida» que es deshacer de verdad (`:1445`). La lente no le encontró golfo propio. Eso
tiene consecuencia práctica: **el patrón correcto ya está escrito en este repositorio** y N-4 es
darle paridad, no inventar nada.

## Recuento

| Estado | Entradas |
|---|---|
| `done` | 19 |
| `informe emitido (Task 27)` | 3 |
| `no ejecutable (Task 27)` | 1 |
| `pendiente (Task N)` | 12 |
| `backlog ICE` (sin task, en `docs/EXPERIMENTS.md`) | 18 |
| `cerrado sin código` | 4 |
| `no aplica: módulo eliminado` | 4 |
| **Total** | **61** |

C-31 y C-49 cuentan una sola vez, en el estado de su parte principal (`done` y `pendiente`
respectivamente); sus mitades restantes están anotadas en su propia fila.

Los 7 movimientos del Task 27 salen todos de `pendiente`, que baja de 20 a 13: C-37, C-18 y C-19 pasan a
`done` (16 → 19), C-11, C-15 y C-20 a `informe emitido` y C-5 a `no ejecutable`. Los ids no son solo `C-`:
la tabla incluye también `A-`, `B-` y ahora `N-` —`cerrado sin código` son B-1, B-2, C-4 y C-7—, así que
cualquier recuento tiene que contar sobre `^| \*\*[A-CN]-` o se dejará filas fuera.

**Aritmética del Task 22** (lente de Norman, IA-3), que lleva el total de 54 a **61**: se añaden las
**7** entradas `N-1` … `N-7`, todas `backlog ICE`, y **C-14 se mueve de `pendiente` a `backlog ICE`**
porque el Task 22 era justo su task viva y la cerró midiendo en vez de aplicando. Por tanto
`pendiente` 13 → **12**, `backlog ICE` 10 + 7 + 1 = **18**, y el resto de estados no se toca
(`done` 19, `informe emitido` 3, `no ejecutable` 1, `cerrado sin código` 4, `no aplica` 4).
Comprobación: 19 + 3 + 1 + 12 + 18 + 4 + 4 = **61**.
