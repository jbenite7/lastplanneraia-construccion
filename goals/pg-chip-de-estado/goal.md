# Goal — Programa General recupera su chip de estado

**Slug:** `pg-chip-de-estado`
**Fecha de apertura:** 2026-08-03
**Estado:** CERRADO el 2026-08-06 — lo resolvió la campaña de dark mode, no una ejecución propia
**Línea G** de `docs/superpowers/specs/2026-08-03-reparto-trabajo-pendiente-design.md`

## Cierre formal

El goal se abrió con el diseño aprobado y nunca se ejecutó como tal, pero el objetivo se cumplió
por otra vía: el commit `51ccd5ca` («paridad del chip de estado en PG, PI y PS, medida en
píxeles») de la campaña de cierre de dark mode le dio a `/programa-general` el mismo
`ops-state-chip` que ya tenían PI y PS.

Verificado el 2026-08-06 antes de cerrar, no asumido:

- `public/js/modules/programa_general/hot.js:1658` pinta el `<span class="ops-state-chip">` con
  sus atributos de matiz (`data-aia-hue`, línea 804), que es exactamente lo que pedía el diseño.
- `npx playwright test tests/browser/programa-general-state-hue.mjs` → **1 passed**: «cada matiz
  declarado se pinta distinto y legible».
- Comprobado en pantalla en `/programa-general` (dark, 1180×820): los siete estados —Terminada,
  En curso, Actividad futura, Debe iniciar, Atrasada, Capítulo, Sin Datos— se distinguen entre sí.

*Actividad Futura* y *En Curso*, el síntoma que abrió el goal, ya no se ven idénticas.

## Objetivo

Que `/programa-general` distinga en pantalla los siete estados que su contrato declara, dándole el
chip de estado que Programación Intermedia y Programación Semanal ya tienen.

## El problema, en una frase

*Actividad Futura* y *En Curso* se ven idénticas en la grilla, y la leyenda de la toolbar sí las
distingue.

## Por qué ocurre

El sistema tiene **dos canales ortogonales** para el estado, declarados en
`docs/design-system/state-semantics.json` y descritos en `DESIGN.md` §2:

- el **nivel** (`healthy`, `attention`, `urgent`, `neutral`) se pinta en el fondo;
- el **matiz** (ocho: green, blue, teal, amber, orange, red, violet, neutral) desempata dentro de
  un mismo nivel.

PI y PS implementan los dos: nivel en el fondo de fila, matiz en un `<span class="ops-state-chip">`
dentro de la celda de estado, con `data-aia-hue`, `data-aia-severity` y `data-aia-urgency`.
`states-feedback.css` traduce esos atributos a fondo por matiz **con texto pareado**.

**A PG nunca se le puso el chip.** Su columna Estado se pinta con `pgGenericTextRenderer` — texto
suelto. Como *Actividad Futura* y *En Curso* comparten nivel `healthy`, sin chip no queda nada que
los separe: ambas caen en `--ds-cell-state-ok-bg`.

## Lo que NO es

Tres cosas que se creyeron durante el diagnóstico y resultaron falsas. Se dejan escritas porque
volver a creerlas cuesta dinero:

1. **No es que las tres grillas estén rotas.** PI y PS están bien; su fondo compartido por nivel es
   el canal de nivel funcionando como se diseñó, no una colisión.
2. **No es que unas reglas `!important` estén anulando un mecanismo montado.** Nada lo anula: en PG
   el mecanismo no existe. Borrar esas reglas dejaría las celdas **sin fondo**.
3. **No es un problema de datos ni de fixture.** `Da Porto` tiene 273 filas y la grilla pinta 312
   celdas al navegarla.

## Alcance

- Renderer para la columna Estado de PG que emita el chip con los tres atributos, siguiendo
  `stateChipAttrs()` de `public/js/modules/programacion_intermedia/hot.js:448`.
- Mapa `estado → { level, hue }` para los siete estados de PG. **No se inventa**: sale de
  `state-semantics.json`, que ya los declara — Actividad Futura green, En Curso blue, Terminada
  neutral, Con Alerta Restricciones amber, Debe Iniciar orange, Atrasada red, Sin Datos violet.
- El CSS del chip. Hoy está duplicado bajo `.pi-page` y `.ps-page`. Decidir entre una tercera copia
  o extraerlo a componente compartido, con el coste delante.

## Fuera de alcance

- Tocar PI y PS, que no tienen defecto.
- Cambiar la escala `--ds-cell-state-*` ni la paleta de matices.
- Cambiar qué nivel o qué matiz le corresponde a cada estado: eso lo fija el contrato.
- Mobile, tablet y el tema `linen` (`AGENTS.md`).

## Condición de hecho

1. Los siete estados de PG se distinguen en pantalla a 1180×820 dark, medido en navegador.
2. El matiz de cada uno coincide con el que `state-semantics.json` le declara.
3. Cada chip cumple AA sobre su propio fondo, medido con `tests/browser/support/contrast.mjs`.
4. La leyenda de la toolbar y la grilla concuerdan: lo que el punto promete, la celda lo cumple.
5. Los goldens de `programa-general.visual.mjs` recapturados **con aprobación explícita del
   usuario**, y con el antes/después a la vista.

## Trampas que aplican

- **El chip se define sin `background` a propósito** (`programacion-intermedia.css:257`): lo pinta
  la capa de componentes vía el atributo. Una copia que declare fondo rompe el mecanismo sin dar la
  cara.
- **La doble condición `[data-aia-hue][data-aia-severity]` pesa 0,2,0 a propósito**, para ganarle
  al fondo del nivel por especificidad. Un selector con un solo atributo pierde.
- `memoria/trampas/guard-valida-declaracion-contra-si-misma.md` — el guard que debería haber
  detectado esto lee `state-semantics.json` contra sí mismo.
- `memoria/trampas/audit-ve-color-en-comentarios.md` — describir colores con palabras, nunca con el
  literal.
- **No escribir otra sonda de contraste.** `tests/browser/support/contrast.mjs` existe desde el
  2026-07-28, rasteriza por canvas y compone alfa. Dos sesiones ya escribieron copias y tuvieron
  que sustituirlas. Reserva conocida: puede devolver 1:1 en elementos **en línea**; medir sobre
  bloques.

## Rojos preexistentes

Ninguno. `npm run test:design-system:static` quedó en **363/363** el 2026-08-03.

`test:visual:pilot` **sí está en rojo a propósito** desde `96d9fd3`: el mock de PG devuelve filas
con estado y los goldens aún retratan la grilla vacía. **Este goal es quien lo cierra.**

## Archivos de este goal

Diseño y contexto: `docs/superpowers/specs/2026-08-03-reparto-trabajo-pendiente-design.md`, línea G.

Estado y relación con los demás goals: [[estado|Estado de los goals]].
