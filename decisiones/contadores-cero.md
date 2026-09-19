# Decisiones encoladas — frente `contadores-cero`

Sesión 06e4383d (ejecutor). Sha de arranque: `de02471a`.

---

## D-CERO-1 — Los goldens visuales de PI quedan obsoletos (BLOQUEANTE, escalada)

`docs/design-system/manifests/programacion-intermedia.json` fija por `sha256` dos capturas
golden de `/programacion-intermedia` (dark 1180×820 y dark 1440×900). Ocultar 7 etiquetas de
la leyenda cambia el render, así que ambos goldens dejan de coincidir.

Regenerar un baseline está en la lista de bloqueo incondicional. **No lo decido.** Escalado a
la coordinadora. Mientras tanto **no toco** ni los `.png` ni los `sha256` del manifiesto.

Opciones que veo, sin elegir ninguna:
- (a) Regenerar los dos goldens como parte de este frente, con aprobación explícita.
- (b) Publicar sin regenerar y dejar el gate `runtime` en rojo conocido, con nota.
- (c) Frente aparte para la regeneración.

## D-CERO-2 — El chip `0 selec.` también marca cero, y no lo toco

`#shared-selection-count` (`views/…/programacion_intermedia.view.php:55`) muestra `0 selec.`
de forma permanente y es el octavo elemento en cero de la pantalla. **Lo dejo fuera del
cambio a propósito**, no por olvido: lleva `aria-live="polite"`. Sacarlo del árbol de
accesibilidad con `display:none` y devolverlo al seleccionar hace que el anuncio de
«3 selec.» se pierda o llegue tarde en lectores de pantalla — y el piso de accesibilidad es
contrato. Arreglarlo bien exigiría una región viva persistente aparte, que es una **adición**,
justo lo contrario del objetivo del frente.

Queda a decisión de arriba si se aborda y cómo.

## D-CERO-3 — `programa-general` y `programacion-semanal` tienen el mismo patrón

`is-zero` existe idéntico en `public/js/modules/programa_general/hot.js:2926` y
`public/js/modules/programacion_semanal/hot.js:3029`, con su CSS gemelo. Extender el ocultado
a esas dos pantallas **ampliaría el alcance** del encargo, que nombra
`/programacion-intermedia`. No lo hago. Si se quiere, es un frente hermano barato: el
mecanismo que dejo es copiable tal cual.

## D-CERO-4 — El audit excede su presupuesto por un `!important` (BLOQUEANTE, escalada)

Sobre `c39603ed`. La regla `display:none !important` sube `programacion-intermedia` de 175 a
**176** `unauthorized-important`, y el presupuesto está en
`docs/design-system/exceptions.json:389` en **175**. `[static-suite] FAIL audit`, con la base del
mismo frente en verde: **es regresión mía, no preexistente.**

No lo resuelvo porque las tres salidas están en la lista de bloqueo incondicional:

- (a) Subir el presupuesto a 176 → **tocar un baseline**.
- (b) Quitar otro `!important` del módulo para compensar → **borrar algo** fuera del encargo
  (aunque sería una resta más, en el espíritu del frente).
- (c) Poner el `display:none` como estilo en línea con prioridad desde el JS, que el auditor no
  cuenta porque solo escanea CSS → **desviarse del plan**, que dice una regla CSS.

Comprobado que (c) funcionaría: `el.style.setProperty('display','none','important')` computa
`none`. Y comprobado que la vía barata NO existe: una declaración normal, o un
`.hide()` de jQuery (que pone estilo en línea sin prioridad), **pierden** contra el
`display:inline-flex !important` de `buttons.css:971`.

## D-CERO-5 — El término duplicado: la premisa heredada no se sostiene

Medido a 1180×820 sobre `c39603ed`: el filtro está en x=86 (dentro del viewport) y la celda
`ops-state-chip` en **x=1332, fuera** — hay que desplazar la tabla para verla. **No coexisten a
la vista.** Corrijo aquí mi propia afirmación anterior de que estaban a 140px: la primera
comprobación no miraba los límites del viewport.

Además no son dos copias de lo mismo: una es el **botón de filtro**, otra la **lectura de estado
de la fila** (`stateLabels`, hot.js:505), más una tercera en el modal (hot.js:2823). Borrar
cualquiera quita función. Lo duplicado es la cadena en dos capitalizaciones desde dos fuentes.

Y `GLOSARIO.md` **no define** «Listo para Comprometer» — el contraste pedido no da veredicto.
Sin autoridad local, elegir capitalización canónica **es fijar vocabulario**, y hay un frente
vivo unificando los 21 términos del ciclo. Escalado; el orden lo decide la coordinadora.

---

## Resoluciones recibidas de la coordinadora (2026-08-11)

- **D-CERO-1 → resuelta por el usuario, opción (a):** regenerar los dos goldens **dentro** de este
  frente, con su aprobación explícita. Condiciones suyas, no negociables: se le enseña **antes y
  después** en las dos resoluciones **antes** de fijar los `sha256`; si aparece cualquier
  diferencia que no sea «faltan siete etiquetas vacías», se **para** y se avisa, porque su
  aprobación cubre el cambio que decidió y no cualquiera que salga; y se regenera **al final**,
  con todo verificado, nunca durante. Queda constancia de qué cambió, quién lo aprobó, cuándo y
  sobre qué sha.
- **D-CERO-5 (término duplicado) → sale de este frente.** Pasa al frente
  `vocabulario-estados-cascada`. Este frente cierra solo con la decisión 1, y **no se apunta la 2
  como resuelta**.
- **D-CERO-2 y D-CERO-3 → confirmadas fuera de alcance.**
- **D-CERO-4 → sigue SIN respuesta.** Es lo único que bloquea, y bloquea también el paso de los
  goldens: no se puede regenerar «al final, con todo verificado» mientras `audit` esté en rojo por
  mi causa. Fijar una firma sobre un árbol con un gate rojo propio es exactamente lo que la regla
  del golden existe para impedir.

## D-CERO-4 (continuación) — (b) no desbloquea el gate, medido

Resuelta como (b) «quitar `!important` de `buttons.css`», y **no sirve para lo que se pretendía**.
No se tocó `buttons.css`; se comprobó primero.

- El presupuesto `programacion-intermedia` cubre **solo tres paths** (`exceptions.json:374-380`):
  la vista, `programacion-intermedia.css` y `hot.js`. **`buttons.css` no está**, y el auditor solo
  cuenta lo que casa con esos paths (`design-system-audit.mjs:311-316`). Limpiarlo sanea la causa
  pero **deja el 176 igual**.
- Segunda vía descartada por experimento: quitar el `!important` del `display` en `buttons.css`
  **no** permite que mi regla prescinda del suyo. Con ambas sin prioridad el chip reaparece
  (`display: flex`), porque `styles.css` gana desde `module.components`.
- De las 16 de `buttons.css`: **sobran 12, hacen falta 4** (`white-space`, `font-size`,
  `line-height`, `border`).
- En el archivo que sí cuenta, `programacion-intermedia.css`: **83 declaraciones sobrantes** y
  solo **5 necesarias**, entre las reglas que casan con esta página.
  **Limitación:** 29 reglas no tenían elementos en este estado y no están probadas; una que hoy no
  cambia nada puede importar en un modal o en hover. Son candidatas, no borrables.

Reenviado a la coordinadora con tres opciones (b1 / b2 / a). **Sin decidir.**

## D-CERO-4 → resuelta como (b1). Hecho: 176 → 170

Seis `!important` retirados de `programacion-intermedia.css`, **confirmados uno a uno en pantalla**
contra la línea base (no en lote): `#hot-container {width, max-width}`,
`td.ops-state-td {padding, overflow, vertical-align}`, `thead th .colHeader {padding}` (verificado
en los 34 encabezados). Ninguno mueve nada. `audit` vuelve a verde con **170/175**, margen de 5.

**No se tocaron**, a propósito: las de color/fondo (dependen del tema y solo se midió dark) y el
`min-height` de la barra (piso AA de 24px). `buttons.css` queda intacto: sus 16 son causa real
pero no cuentan para este presupuesto, y van a frente propio con la medición ya hecha.

**Aviso sobre mi propio método:** en el barrido masivo llegué a creer que el padding de
`ops-state-td` cambiaba, y era un error de lectura mío —comparé el valor *declarado* con el
*computado*, no el antes con el después—. Se detectó revirtiendo y midiendo la línea base de
verdad. Sirve de recordatorio de por qué la confirmación una a una no es burocracia.

## D-CERO-1 — puede que no haga falta regenerar nada

El fixture del golden (`tests/browser/programacion-intermedia.visual.mjs`) siembra **los nueve
estados, uno por fila**. Con todas las categorías contando ≥1, **ningún chip queda en cero**, así
que no se aplica `is-empty` y la leyenda del golden no cambia. Si el test visual pasa sin tocar
nada, la aprobación de regenerar **no se usa** — y no se usa por buena razón: no hay nada que
actualizar. Se verifica ejecutando, no razonando.

## D-CERO-1 — PARO: la diferencia no es la que se aprobó. **No regenero.**

Ejecutado el test visual, no razonado. Falla en las dos resoluciones — **pero no por mi cambio.**

**Lo que muestra el diff:** las ocho etiquetas están **presentes y con conteo ≥1**. Ninguna falta.
Era la hipótesis: el fixture siembra los nueve estados, uno por fila, así que en esa captura
ninguna categoría está vacía y `is-empty` no se aplica. **Mi cambio no toca este golden.**

Lo que difiere en rojo es otra cosa: el **selector de semana** (el golden dice «Semana 1», la
corrida dice «Semana 4») y el botón **«Restricción Compartida»**.

**Control ejecutado:** el mismo test contra el árbol principal en `de02471a`, **sin mi código**,
falla igual y con **el mismo diff, píxel por píxel en las mismas dos zonas**. Es deriva
preexistente, dependiente del estado de la base (el número de semana), no una regresión mía.

Por eso **no uso la aprobación de regenerar**. La condición del usuario era parar si aparecía
cualquier diferencia que no fuera «faltan siete etiquetas vacías», y es exactamente el caso.
Regenerar ahora congelaría en el baseline una deriva ajena —y un número de semana que depende de
cuándo se corra— y sería indistinguible de rehacer una firma para tapar un rojo.

Nota de alcance: este golden vive en el gate `runtime`, no en `static`. La condición de hecho del
frente (`static` sin regresión) se cumple igual: **7/8, `audit` verde en 170/175**.

---

## Respuesta del usuario, relatada por la coordinadora — 2026-08-18

**Visto concedido** al cierre del frente tal como está (ocultar contadores en cero en PI).
Publicar según el gate. Además el usuario amplía el alcance en dos encargos que **no** frenan este
cierre y van a frentes nuevos:

1. **Extender el patrón a todos los módulos** con leyendas/contadores, no solo PI.
2. **Revisar el coloreado en cascada de la tabla:** el usuario espera un orden de severidad de
   «Crítico» a «Sin problema» y cree que **no está pasando**. Tratarlo como posible bug de la
   escala de tintes de estado (diagnóstico primero, `systematic-debugging`), no como preferencia.
