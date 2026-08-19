---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-07-29
areas: [proceso]
fuente: docs/superpowers/specs/2026-07-29-tamiz-presupuesto-design.md
resumen: goals/pdc-preparar-b1 - Origen: Comité del 2026-07-29 — dos observaciones del dueño del producto hechas en vivo, mirando Da Porto. - Estado: implementado y en…
---

# PDC v2 — El presupuesto se explica solo: tamiz y cifras honestas — Design

- **Fecha:** 2026-07-29
- **Ola:** 1 (antes del lanzamiento)
- **Goal:** `goals/pdc-preparar-b1`
- **Origen:** Comité del 2026-07-29 — dos observaciones del dueño del producto hechas en vivo, mirando
  Da Porto.
- **Estado:** **implementado y en `main`** (fila 2 del tablero, `31e9145`). Avisos de insumos vacíos y partidas globales en el visor, y las cifras de insumos etiquetadas.

## Problema

En la demo, el módulo mostró dos veces datos que no se explicaban solos.

**1 · Cifras que no dicen qué cuentan.** El visor anunció 820 insumos; Tomás esperaba ~390 y dijo «me
suena raro el número de insumos». La explicación —cuenta apariciones en los APU, no insumos distintos—
tardó tres turnos de conversación. Más adelante aparecieron 396 en otra pantalla. Tres cifras verdaderas
que parecen contradecirse porque ninguna dice de qué habla.

**2 · El presupuesto entra sin tamizar.** Da Porto trae ~46 insumos con cantidad cero y precio cero, y
partidas globales que resuelven una actividad entera de un plumazo. Tomás le da a esto el mismo valor
que a armar el plan:

> «Vos te vas a dar cuenta al recorrer los 350 insumos dónde presupuestos hizo machetazos […] te
> aparecen unos insumos que son unos globalazos sacados del sombrero y es como esto no me cuadra.»

Hoy el módulo los deja pasar callado. El que arma el plan de compras es el primero que los ve, y es la
mejor oportunidad de la empresa para cazarlos.

## Decisiones cerradas en el grilleo

| Decisión | Valor |
|---|---|
| El tamiz entra | **Sí, como avisos en el visor.** Señala, no bloquea |
| Qué es un «globalazo» | **Un insumo caro que es toda la actividad**: el APU se resuelve con uno o dos insumos de unidad global y ese insumo pesa por encima de un umbral del presupuesto |
| Las cifras | Cada número en pantalla **dice cuál de las dos cosas cuenta** |

Descartadas: marcar por unidad de medida a secas (marca decenas de partidas legítimas y la gente aprende
a ignorar el aviso) y listar el Pareto de valor (no distingue «caro» de «mal presupuestado»).

## Alcance

### Entra

**Avisos en el visor del presupuesto**, sobre la versión activa:

| Aviso | Regla | Texto |
|---|---|---|
| Insumo vacío | `cantidad = 0` o `valor_unitario = 0` | «N insumos sin cantidad o sin precio» |
| Partida global | El APU de la actividad se resuelve con ≤ 2 insumos de unidad global **y** el insumo supera el umbral de valor | «N actividades resueltas con una partida global» |

- Ambos avisos se pueden desplegar a la lista, con capítulo, actividad, insumo y valor.
- **No bloquean nada**: ni la importación, ni la asignación a paquetes, ni el plan. Son un dedo señalando.
- El **umbral de valor es configurable y tiene un valor por defecto explícito** en el spec de
  implementación, medido contra Da Porto — no un número inventado en el código.

**Cifras honestas**: se revisa cada número de insumos que el módulo muestra (visor, paquetes, maestro,
resúmenes) y se etiqueta como una de dos magnitudes, con la misma palabra siempre:

- **apariciones en APU** — cuántas veces un insumo se usa a lo largo del presupuesto (el 820);
- **insumos distintos** — cuántos insumos diferentes hay (el 396 / ~390 que esperaba Tomás).

### No entra

- Corregir el presupuesto desde el módulo. Se señala; se corrige en el origen.
- Juzgar precios contra el mercado o contra otros proyectos.
- Bloquear el avance por tener avisos abiertos.

## Arquitectura

- Sin tablas nuevas ni migraciones: las dos reglas se calculan al vuelo sobre `pdc_presupuesto_*`.
- El cálculo vive en el servicio del visor, expuesto junto a los datos que la pantalla ya pide — sin
  endpoint aparte, para que un presupuesto no pueda mostrarse sin sus avisos.
- Las etiquetas de las cifras son cambio de vista: texto y tooltip en `pdc-app/`.

## Condición de hecho

1. En Da Porto, el aviso de insumos vacíos reporta un número que coincide con el conteo hecho a mano
   contra la base (el orden esperado es ~46), y su lista los nombra.
2. El aviso de partidas globales reporta actividades reales; se revisan una por una con el dueño del
   producto y se acepta el umbral solo si el listado le resulta accionable, no ruidoso.
3. Ninguna de las dos alertas impide importar, asignar ni recalcular: se demuestra completando el flujo
   con avisos abiertos.
4. Se recorre la app y **toda** cifra de insumos dice si son apariciones en APU o insumos distintos; el
   820 y el 396 conviven en pantalla sin parecer un error.
5. Regresión: Vitest, build y e2e del visor en verde.

## Riesgos

- **El umbral es un juicio disfrazado de número.** Si se elige mal, el aviso o no marca nada o marca todo.
  Por eso el punto 2 de la condición de hecho exige la mirada del dueño del producto, no solo que el
  código corra.
- **Los insumos vacíos pueden ser legítimos** (partidas previstas sin valorar todavía). El aviso dice
  «mira esto», nunca «esto está mal», y el texto tiene que sonar así.
