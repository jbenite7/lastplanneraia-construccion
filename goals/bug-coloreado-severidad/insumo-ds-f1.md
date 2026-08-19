---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-08-18
areas: [proceso]
fuente: goals/bug-coloreado-severidad/insumo-ds-f1.md
resumen: Qué es esto. El frente bug-coloreado-severidad es de diagnóstico y termina aquí; la spec dice que su resultado «es entrada para DS-F1». Este archivo es esa…
---

# Insumo medido para DS-F1 — rediseño de la escala de estado

**Qué es esto.** El frente `bug-coloreado-severidad` es de diagnóstico y termina aquí; la spec dice
que su resultado «es entrada para DS-F1». Este archivo es esa entrada: **los hechos medidos y las
restricciones duras**, para que DS-F1 no vuelva a medir lo mismo. **No es una spec y no propone un
diseño** — eso se decide con el usuario, no en una sesión de ejecución.

**Medido sobre `fe9a51e8`**, 1180×820, dark, sesión real. Métricas: contraste WCAG (relación de
luminancias) y **ΔE-OK**, que es la que este repo ya usa como umbral de percepción (0,0168 en
`tests/design-system/state-tint-ladder.test.mjs`).

---

## Corrección a lo que yo mismo dije hace dos turnos

En la propuesta anterior escribí que cualquier arreglo dentro de esta escalera «sigue siendo
ilegible en pantalla». **Eso está mal y lo corrijo con la medida.**

El **ΔE-OK mínimo entre dos peldaños distintos es 0,0487**, casi el triple del umbral de percepción
que el propio repo fijó (0,0168). Los colores **sí se distinguen**. Lo que yo tomé por ilegibilidad
era otra cosa, y la cifra correcta la dice mejor:

| Lo que medí | Lo que prueba de verdad |
|---|---|
| Contraste WCAG ≤ 1,372:1 entre todos los pares | **No que no se distingan** — el contraste WCAG solo mide luminancia, y dos tonos distintos a igual luminancia se distinguen perfectamente. Prueba que **no hay eje de intensidad**: ningún color «pesa» más que otro |
| ΔE-OK ≥ 0,0487 entre peldaños distintos | Se distinguen sin problema, **por tono** |
| Tres pares a 1,000:1 y ΔE-OK = 0 | Ésos sí son literalmente el mismo color |

**El enunciado correcto:** la paleta distingue bien **qué** estado es y no comunica en absoluto
**cuán grave** es. Es exactamente lo que el contrato dice que hace, y exactamente lo que el usuario
no espera.

---

## El hallazgo que hace falta para rediseñar: la luminosidad no ordena, y donde ordena va al revés

`L*` OKLab de los cinco peldaños de la escalera de celda, de más claro a más oscuro:

```
atencion 0,339  >  ok 0,325  >  riesgo 0,300  >  critico 0,268  >  neutral 0,246
```

Tres cosas, y las tres importan:

1. **Todo cabe en una banda del 9 %** (`L*` de 0,246 a 0,339, rango 0,092). No hay recorrido con el
   que construir una cascada.
2. **El orden no es monótono** en ningún sentido: sube de `critico` a `atencion` y vuelve a bajar.
3. **Está prácticamente invertido.** El peldaño más claro —el que más «pesa» a la vista— es
   `atencion`, y `critico` es el **penúltimo más oscuro**, solo por encima de `neutral`, que es el
   que no pide nada. Si el ojo lee «más claro = más urgente», hoy la tabla dice que atender algo la
   semana que viene es más grave que un bloqueo en ruta crítica.

Lo mismo en la paleta nominal de ocho anclas (`--ds-state-tint-*`): rango `L*` 0,097, con `red` en
0,268 —**el más oscuro de los ocho**— y `teal` en 0,365, el más claro.

**Ésta es la restricción de fondo que DS-F1 tiene que resolver o declarar imposible:** sobre este
canvas oscuro, las ocho anclas se eligieron para ser equiluminantes y distinguirse por tono. Una
cascada por severidad necesita lo contrario — un eje que varíe. Las dos cosas no caben en la misma
propiedad, y por eso el contrato repartió los ejes (matiz al fondo, nivel al acento). **Si el
usuario quiere la cascada en el fondo, hay que romper esa decisión a sabiendas, no por descuido.**

---

## Restricciones duras que DS-F1 hereda

### Lo aritmético

| | Cuántos |
|---|---|
| Anclas de tinte publicadas (`--ds-state-tint-*`) | 8, cerradas por arriba y por abajo por test |
| Peldaños de la escalera de celda en uso (`--ds-cell-state-*`) | 5 (+`bloqueado` y `sin-datos`, sin usar en PI) |
| Niveles del contrato (`state-semantics.json`) | 4 |
| Estados de Programación Intermedia | 8, repartidos en solo **3** niveles (urgent×3, attention×4, healthy×1) |

Consecuencia directa: **una tabla que obedezca al nivel declarado tiene 3 colores.** Cualquier
diseño con más de 3 necesita un segundo eje declarado, no improvisado.

### Lo que se rompe si se toca sin cuidado

- `tests/design-system/state-tint-ladder.test.mjs` fija **los ocho hex exactos** y exige que la
  paleta no publique un noveno. Un ancla nueva es un cambio de vocabulario del contrato.
- `tests/browser/ops-state-chip-hue.mjs` exige que el fondo del chip sea **el tinte de su matiz**.
- `tests/browser/programacion-intermedia.visual.mjs` y `programa-general.visual.mjs` son goldens de
  imagen: cualquier cambio de color los mueve, y regenerarlos **exige aprobación visual explícita**
  (AGENTS.md §Verificación).
- `docs/design-system/state-tint-exceptions.json` tiene siete entradas medidas contra estos hex
  concretos; cambiar un ancla las invalida.

### Lo que ya se intentó y se midió

La versión con **tres pasos por familia** (derivados del ancla bajando croma con luminosidad fija)
se retiró el 2026-07-28: medida en navegador, la separación máxima entre dos pasos consecutivos era
**1,012:1 de contraste y ΔE-OK 0,0168**. Dos entradas de leyenda que el usuario filtraba por
separado pintaban fondos **bit-idénticos**. Está documentado en `state-tint-ladder.test.mjs:9-27`.
**Repetir ese intento sin cambiar el método daría el mismo resultado.**

### Las dos autoridades que se contradicen

`docs/design-system/state-semantics.json` (contrato ejecutable, dark) y
`docs/matriz-severidad-cajon-contextual-lps.md` (2026-05-22, paleta clara, alcance Cajón Contextual)
asignan severidad distinta a cuatro estados de PI. **DS-F1 tiene que derogar una de las dos**, o
seguirá arrastrando el desacuerdo a cualquier rediseño.

### Un defecto vivo que conviene resolver dentro de DS-F1

`public/css/design-system/components/states-feedback.css:162` (excepción crítica del 2026-08-11) es
letra muerta: `public/css/design-system/adapters/legacy-bridge.css:104-142` gana desde la capa
`legacy-overrides` con `:where()` y el matiz va después. Detalle y reproducción en `diagnostico.md`.

---

## Las tres preguntas que DS-F1 le tiene que hacer al usuario

No las contesto: son de su criterio, no técnico.

1. **¿La gravedad se lee en el fondo o en otro sitio?** Hoy el contrato dice que el fondo es
   identidad y la gravedad va en el acento. Si la respuesta es «en el fondo», se rompe esa decisión
   a propósito y hay que reescribir el contrato, no parchear una hoja.
2. **¿Cuántos escalones de gravedad quiere ver de verdad?** Con 3 se puede ser fiel al contrato y
   tener una cascada limpia. Con 8 hay que renunciar a la cascada o inventar un segundo eje.
3. **¿Está dispuesto a perder el tema oscuro como está?** Una cascada real necesita recorrido de
   luminosidad, y eso significa fondos claramente más claros o más oscuros que el canvas actual.

## Evidencia reutilizable

Las tres sondas de `evidence/` son de solo lectura y sirven para medir cualquier propuesta de DS-F1
antes y después, computado contra computado:

```bash
node goals/bug-coloreado-severidad/evidence/sonda-severidad.mjs
node goals/bug-coloreado-severidad/evidence/sonda-leyenda.mjs
node goals/bug-coloreado-severidad/evidence/sonda-reglas-chip.mjs
```
