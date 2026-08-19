---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-18
areas: [proceso]
fuente: goals/bug-coloreado-severidad/propuesta-arreglo-3-estados.md
resumen: Pedido del usuario, 2026-08-18 (botón del panel): «Arreglar solo los 3 estados mal asignados en el mapeo de styles.css:3664-3725, sin rediseñar la escala de…
---

# Propuesta — arreglar los 3 estados mal asignados en `styles.css:3664-3725`

**Pedido del usuario, 2026-08-18** (botón del panel): *«Arreglar solo los 3 estados mal asignados en
el mapeo de styles.css:3664-3725, sin rediseñar la escala de color.»*

**Estado: NO APLICADO.** Este archivo es la propuesta, no el cambio. Ver «Por qué no lo apliqué».

**Medido sobre `8841c04e`.** Ninguna cifra de aquí es nueva: sale de
`evidence/medicion-computada.json`, salvo la matriz de contraste de los cinco peldaños, que se
deriva de esos mismos valores computados.

---

## Lo primero, porque cambia la recomendación que yo mismo di

Recomendé esta opción como «barata y reversible». **Al ir a escribirla, la medición dice que no es
un cambio: son tres decisiones distintas, y dos de ellas empeoran la pantalla.** Lo corrijo aquí
antes de que nadie gaste una jornada en ello.

**La causa es aritmética.** La escalera de celda tiene 5 peldaños en uso; el contrato tiene 4
niveles; y Programación Intermedia reparte sus 8 estados así:

| Nivel del contrato | Estados de PI | Cuántos |
|---|---|---|
| `urgent` (high/now) | RC inicio vencido · Inicio vencido · **Alistamiento Urgente** | **3** |
| `attention` (medium/soon) | Inicio por Habilitar · Alistamiento en Riesgo · Alistamiento Pendiente · **En Ejecución Pendiente** | **4** |
| `healthy` (low/none) | Listo para Comprometer | 1 |

**Si la fila obedeciera al nivel declarado, los 8 estados pintarían 3 colores.** Hoy pintan 5. Es
decir: «hacer que respete el contrato» **reduce** la diferenciación en vez de aumentarla. El
desajuste que encontré no es descuido — es el precio que alguien pagó para tener cinco colores en
vez de tres, sin escribirlo en ninguna parte.

---

## Los tres casos, uno por uno. No son el mismo problema

### Caso A · «En Ejecución Pendiente» → **esto sí es un arreglo inequívoco**

Es el único de los tres que **invierte** la severidad en vez de aplanarla: el contrato lo declara
`attention` y la fila lo pinta `--ds-cell-state-ok-bg` (`#173d26`), el verde de «controlado»,
idéntico a «Listo para Comprometer». La propia Guía Operativa lo clasifica **P1 — Resolver hoy** y
lo pinta verde. Un estado que pide acción hoy se lee como uno que no pide nada.

Además **no cuesta diferenciación**: mover `ok → atencion` deja `ok` con un solo ocupante
(`liberated-control`) y `atencion` con cuatro en vez de tres. El recuento de colores no cambia: 5
antes, 5 después.

```css
/* public/css/styles.css:3707-3711 — ANTES */
.pi-page .handsontable td.pi-state-execution-blocked,
.pi-page #hot-container .handsontable td.pi-state-execution-blocked {
  background-color: var(--ds-cell-state-ok-bg) !important;
  color: var(--ds-cell-state-ok-fg) !important;
  border-color: var(--ds-cell-state-ok-fg) !important;
}

/* DESPUÉS */
.pi-page .handsontable td.pi-state-execution-blocked,
.pi-page #hot-container .handsontable td.pi-state-execution-blocked {
  background-color: var(--ds-cell-state-atencion-bg) !important;
  color: var(--ds-cell-state-atencion-fg) !important;
  border-color: var(--ds-cell-state-atencion-fg) !important;
}
```

El muestrario de la leyenda (`styles.css:3916-3919`) lleva el mismo cambio, o la leyenda dejaría de
describir la tabla.

**Efecto medido esperado:** `#173d26` → `#3a3a0f`. Contraste entre el color viejo y el nuevo: 1,035:1
— es decir, **el arreglo apenas se va a notar a la vista**. Corrige la semántica, no la legibilidad.
Hay que decirlo antes y no después.

### Caso B · «Alistamiento Urgente» → **no hay peldaño donde ponerlo**

Es `urgent` y hoy va en `atencion`, empatado con dos estados `attention`. Para respetar el nivel
haría falta un tercer peldaño de urgencia, y **la escalera no lo tiene**: `critico` ya es «RC inicio
vencido» y `riesgo` ya es «Inicio vencido». Las salidas posibles son tres y **ninguna es mecánica**:

- **B1 · a `riesgo`** — comparte color con «Inicio vencido». Sube su gravedad aparente, pero
  «Alistamiento Urgente» empieza **en una semana** y «Inicio vencido» **ya venció**: los iguala al
  revés de como los ordena la obra.
- **B2 · a `critico`** — tres estados en rojo. Es exactamente lo que la reasignación de matices del
  2026-07-28 deshizo, con medición.
- **B3 · dejarlo donde está** y aceptar que el nivel `urgent` del contrato está mal para este estado
  — que es lo que `docs/matriz-severidad-cajon-contextual-lps.md` ya sostiene: allí `alert-1-week`
  es `attention`, no `urgent`.

**B3 no cambia CSS: cambia el contrato.** Y es, para mí, la lectura más probable — un estado que
arranca dentro de siete días difícilmente es «actuar ahora». Pero es una llamada de obra, no mía.

### Caso C · «Alistamiento Pendiente» → arreglarlo cuesta un color

Es `attention` y va en `neutral`, empatado con la fila sin clasificar. Moverlo a `atencion` lo
alinea con el contrato **y deja cinco estados compartiendo un solo color** (Inicio por Habilitar,
Alistamiento Urgente, en Riesgo, Pendiente y En Ejecución Pendiente). La tabla pasaría de 5 colores
a 4, con un bloque de cinco filas indistinguibles. Mismo dilema que B, y con el mismo trasfondo: la
matriz vieja también lo declara `normal` preventivo, no `attention`.

---

## El dato que atraviesa los tres casos

La escalera entera es perceptualmente plana. Contraste entre **todos** los pares de peldaños:

```
critico  vs riesgo    1,126      riesgo   vs atencion  1,180
critico  vs atencion  1,329      riesgo   vs ok        1,141
critico  vs ok        1,284      riesgo   vs neutral   1,163
critico  vs neutral   1,033      atencion vs ok        1,035
atencion vs neutral   1,372      ok       vs neutral   1,326
```

**Ningún par pasa de 1,372:1.** Mover un estado de un peldaño a otro cambia el tono, casi nunca la
sensación de gravedad. Cualquier arreglo que se quede dentro de esta escalera es correcto en el
código y sigue sin comunicar gravedad — que es la respuesta (3) del diagnóstico, y no se resuelve
reasignando peldaños.

> **Corrección del 2026-08-18, misma jornada.** Aquí escribí «sigue siendo ilegible en pantalla» y
> era una exageración: el contraste WCAG solo mide luminancia, así que un 1,03:1 entre dos tonos
> distintos **no** significa que no se distingan. Medido en ΔE-OK —la métrica que este repo ya usa—
> el mínimo entre peldaños distintos es **0,0487**, casi el triple del umbral de percepción del
> propio repo (0,0168): **se distinguen bien**. Lo que las cifras prueban es otra cosa, y está en
> `insumo-ds-f1.md`: no hay eje de intensidad, y el poco recorrido de luminosidad que existe va
> **al revés** (`atencion` es el peldaño más claro; `critico`, el penúltimo más oscuro).

---

## Recomendación

1. **Aplicar el Caso A y solo el Caso A.** Es el único desajuste que invierte el significado, no
   cuesta diferenciación y no necesita decidir nada de negocio. Dos bloques de CSS, sin tokens
   nuevos y sin tocar la escala.
2. **B y C no son bugs sino desacuerdo entre dos documentos.** Se resuelven decidiendo qué autoridad
   manda (la decisión ya anotada en `decisiones/bug-coloreado-severidad-ejecutor.md`), y lo más
   probable es que la corrección caiga del lado del **contrato**, no del CSS.
3. **La legibilidad no se arregla aquí.** Va a DS-F1 con la matriz de contraste de arriba delante.

## Verificación que exigiría el Caso A

- `npm run test:design-system:static` (la verificación declarada del frente).
- Re-correr `evidence/sonda-severidad.mjs` y comprobar que `execution-blocked` pasa de `#173d26` a
  `#3a3a0f` **y que ningún otro estado se mueve** — computado contra computado.
- `evidence/sonda-leyenda.mjs` para que el muestrario siga describiendo la tabla.
- Riesgo de goldens: `tests/browser/programacion-intermedia.visual.mjs` retrata las primeras ~5 filas
  a 1180×820. La fila `execution-blocked` (id 105) está en el borde inferior del retrato, así que el
  golden **puede** moverse. **Eso no lo decide quien implementa:** regenerar un golden exige
  aprobación visual explícita (AGENTS.md §Verificación).

## Por qué no lo apliqué

Tres razones, y ninguna es que no quiera:

1. **Está fuera del alcance declarado de este frente.** La condición de hecho dice «cero cambios en
   producto», y la `## Posture` de la spec dice, literal, «No arreglar. Ni "ya que estoy". El
   arreglo se decide con el diagnóstico delante».
2. **El gate de rutas lo deniega mecánicamente.** Este frente declaró
   `archivos = goals/bug-coloreado-severidad/**`; `public/css/styles.css` queda fuera y
   `gate-rutas.sh` bloquea el `Edit`. Ampliarlo exige re-declarar el frente, y eso pasa por la
   coordinadora.
3. **Dos de los tres casos no tienen respuesta mecánica**, y elegir por mi cuenta cuál es «el
   arreglo» sería exactamente la decisión cómoda sin medir que esta ficha existe para impedir.

Con el visto y el alcance ampliado, el Caso A es un cambio de diez minutos y está escrito arriba
listo para aplicar.
