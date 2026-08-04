# Decisiones pendientes del usuario — cierre de dark mode

**Modo acordado el 2026-08-03:** el usuario pidió no ser interrumpido durante la ejecución. Todo
lo que requiere su criterio se acumula aquí y se le presenta **al final del plan**, en bloque.

Regla que sigue esta sesión mientras tanto: donde haga falta decidir para no detenerse, se toma la
opción **más conservadora y reversible**, se ejecuta, y se registra abajo como *asumida* — nunca
como aprobada. Lo irreversible o lo que consagra un estado visual (goldens) **no se aplica**: se
deja preparado y aquí anotado.

---

## A · Requieren aprobación antes de aplicarse (nada se ha aplicado)

| # | Qué | Estado | Por qué es tuya |
|---|---|---|---|
| **A-1** | **Goldens de tabla** (task 15) | **Sin recapturar**, a propósito. El visual de Programa General está en rojo y ese rojo es correcto: refleja los cambios de hoy | Recapturar consagra el aspecto actual como «lo correcto» para siempre. Ya te envié el par (esperado / actual / diferencias) de `/programa-general`. **Lo que decides:** si el «actual» es lo que quieres, se recapturan y el rojo desaparece; si ves algo que no esperabas, se investiga antes. Ficheros en `test-output/programa-general.visual.mj-b4b64-…/`. Los demás goldens de tabla se recapturarían en el mismo lote |
| **A-2** | **Densidad compacta** (task 19) | **Aplicada** (`67f35c4`) — reversible con `git revert` | Ya la aprobaste dos veces (extender la escala + «maximizar espacio»), así que se aplicó. Te quedan por ver las capturas antes/después: `task-19-densidad/`. **Ojo al resultado honesto**: el alto de fila bajó de 36 a 24 px, pero **no se ganan filas** en PG/PI con datos reales, porque el texto envuelto domina la altura (el token es un mínimo, no un máximo). Lo que sí se arregló: las cabeceras truncadas e ilegibles de `/pdc` |
| **A-3** | **Variante B de bordes en tablas reales** | Decidida en maqueta, **no aplicada** | Aprobaste B + numéricas a la derecha sobre una maqueta HTML. Handsontable y DataTables pintan sus propios bordes: hay que verlo ahí antes de producción |

## B · Asumidas por la sesión, ratifícalas o revócalas

| # | Decisión asumida | Razón | Reversible |
|---|---|---|---|
| **B-1** | El barrido horario de todas las superficies corre **en esta sesión**, no como tarea programada en la nube | Tú mismo pediste que los hallazgos «se envíen a esta sesión para incorporarlas al plan»: una tarea en la nube no podría hacerlo. Además muere al cerrar la sesión, que es lo conservador | Sí — se puede mover a la nube con `/schedule` |
| **B-2** | La primera pasada del barrido horario **no arranca de inmediato**, sino cuando cierre el task en vuelo que está editando el CSS de tablas | Medir un árbol a medio cambiar produce hallazgos falsos que luego contaminarían el plan | Sí |

## C · Deuda inventariada que espera tu criterio

| # | Qué | Tamaño | Nota |
|---|---|---|---|
| **C-1** | **Las 22 ramas viejas** | Censo en `docs/superpowers/ramas-viejas-2026-08-03.md`: 22/22 sin contenido único, verificado por muestreo | Borrarlas es seguro según la medición, pero borrar ramas es tuyo |
| **C-2** | **Fase 6 — el grueso estructural** | ~2 600 hallazgos: `!important`, `css-outside-layer`, `raw-token-in-module` | Requiere que apruebes el inventario inicial de excepciones justificadas. Es la campaña larga; el punto natural de corte si quieres parar |
| **C-3** | **`pdc.css:318` — borde-acento del toast** | 1 línea | El detector lo marca en cada parada. O se suaviza el acento, o se declara intencional en config (necesita tu confirmación) |
| **C-4** | **23 hallazgos de `pdc.css` y 16 de PS** fuera de rampa tipográfica | Preexistentes, con techo en presupuesto | Parte los racionaliza el task 19; el resto entra en C-2 |
| **C-5** | **Sidecar `.impeccable/design.json` desactualizado** respecto a `DESIGN.md` | Trivial | Regenerarlo es un comando; no se hizo por no tocar el sistema de diseño sin necesidad |
| **C-6** | **Contenido de «HOMECENTER CALI» en la fila 1 del sandbox** `pdc_sandbox_e2e` | Desconocido | No coincide con el seed. Puede ser residuo de una importación manual o un bug de importación real; no se investigó |
| **C-7** | **DataTables: gatillo de filtro sin verificación visual** | Menor | Ninguna tabla alcanzable tiene ordenación activa hoy; la regla está escrita pero nadie la ha visto en acción |
| **C-8** | **`state-tint-exceptions.json` sigue anclado por número de línea** | Menor | Su hermano ya migró a firma (task 12-bis). Las 3 entradas actuales están verificadas correctas |
| **C-17** | **Cinco acciones de Programación Semanal pasaron a un menú «Más»** | Cambio visible de UX | Era la única salida: dos botones caían fuera de la pantalla y no se podía llegar a ellos. **Visibles quedan** Autoprogramar, Agregar Actividad, Confirmar Compromisos, Reabrir Semana y Registrar TNP. **Al menú fueron** Leyenda, Imprimir, Exportar CSV, Recargar y BI Semanal. Ninguna acción se perdió ni cambió de comportamiento. **Lo que decides tú:** si «Recargar» o «Leyenda» los usas a diario, dilo y vuelven a la barra a cambio de bajar otra. Es un reparto, no una lista cerrada |
| **C-18** | **`fitActionsRowSingleLine()` es código muerto con comentario falso** | Menor, preexistente | En `hot.js:1203` la función empieza con `return; // DISABLED…` desde antes de este plan. Era la que compensaba el rango de anchos 1161–1440 px que ninguna regla CSS cubre. Hoy ya no hace falta para la semanal (el menú resuelve), pero el comentario sigue engañando a quien lo lea |
| **C-16** | **La caja interna de la cabecera es más estrecha que la celda visible** | Estructural, causa raíz | El task 24 quitó el corte de palabras, pero no la causa: el `.colHeader` renderiza 33-35 px donde el `th` mide 56 px, probablemente por el `colWidths` del JS contra el auto-layout de la tabla. Mientras siga ese desajuste, cualquier regla de wrapping futura volverá a morder ahí. Arreglar la causa toca el JS de las tablas, no solo CSS |
| **C-15** | **`buttons.css` entero vive en el escalón más bajo de su capa, sin que nadie lo decidiera** | Estructural, preexistente | Hallazgo de la re-revisión del task 22: `buttons.css` (1 215 líneas) se auto-encapsula en `@layer components` **y además** se importa con `layer(components)`, creando `components.components`. Resultado: pierde silenciosamente ante cualquier otro componente que colisione en selector. Hoy juega a favor —es lo que permite que el componente compartido gane—, pero es un accidente, no un diseño. `access.css` tiene el mismo patrón con `utilities`. Merece ticket propio: auditar todos los `@import … layer(x)` sobre archivos ya auto-encapsulados en la misma capa |
| **C-14** | **El aviso de «qué retiene el estado» YA existe — y aun así no lo viste** | Ninguno de código; es de descubribilidad | El task 23 encontró tres mecanismos ya construidos y verificados en vivo: (1) marca `⚠ Sin asignar` **visible en la propia celda** que falta, (2) el chip de estado lleva `title`/`aria-label` con las razones al pasar el cursor, (3) al hacer clic, un panel lista cada condición pendiente. Si aun así no lo encontraste, hay dos posibilidades y solo tú puedes distinguirlas: **(a)** tu caso lo retenía otra condición (restricciones abiertas, no responsables) y esa no se marca en celda, solo al pasar el cursor; **(b)** el aviso está pero no invita a mirarlo. **Lo que te pido, cuando te vuelva a pasar:** mira si en la fila aparece `⚠ Sin asignar`, y pasa el cursor por encima del chip de estado. Con esa respuesta se decide en un minuto si hay que hacer el motivo visible sin interacción |
| **C-10** | **Los chips contadores del PDC no se pueden usar con teclado** | Pequeño, pero es accesibilidad **nivel A** | Filtran al hacer clic, pero no son alcanzables ni activables con teclado (les falta `role`/`tabindex`/`keydown`). Es un fallo más básico que el tamaño que acabamos de ajustar: WCAG 2.1.1 es nivel A, el mínimo del mínimo. **No se arregló** porque añadir manejo de teclado es funcionalidad nueva y tú pusiste ese límite. Mi recomendación: autorizarlo — hacer alcanzable un control que ya existe no cambia lo que hace |
| **C-11** | **Hay reglas pensadas para tablet aplicándose a tu pantalla de trabajo** | Estructural | Segunda vez que aparece hoy: el task 17 halló un `@media 768–1199.98px` que agrandaba el gatillo de filtro, y el task 22 otro que forzaba 44 px con `!important` — ambos alcanzan el viewport canónico de 1180 px. El repo prohíbe trabajar tablet, pero su CSS sigue ahí y muerde el escritorio. Merece task propio: auditar todos los media query que solapen 1180 px |
| **C-12** | **Programación Intermedia se queda sin acción primaria** | Menor | En las otras tres toolbars se pudo señalar cuál es la acción principal; en PI no había candidata defendible sin inventar criterio de dominio. Si sabes cuál debería destacar («Restricción Compartida»?, «Listas»?), se aplica en un minuto |
| **C-13** | **Los chips de PI y PS envuelven a dos líneas** | Menor | Un ancho fijo de 155 px preexistente los obliga a partir en dos renglones, lo que come el espacio que acabamos de ganar |
| **C-9** | **La densidad no gana filas por el ancho de la columna «Actividad»** | Medio | Hallazgo del task 19: bajar el alto de fila no sirve de nada mientras el texto largo se envuelva en varias líneas. Ganar filas de verdad exige decidir qué hacer con esa columna (truncar con detalle al pasar el cursor, ancho fijo, o dos líneas máximo). Es una decisión de producto: afecta a cuánto texto lees de un vistazo |
