---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-07-29
areas: [pdc]
fuente: goals/pdc-a42-frentes-cobertura/coordinacion-sesiones.md
resumen: Las cuatro tareas corren a la vez sobre un worktree (/Volumes/Crucial X6/Developer/lps-aia-pdc), un Docker (8091) y una base MySQL. Este archivo registra lo…
---

# Coordinación con las otras tres sesiones PDC

Las cuatro tareas corren a la vez sobre **un** worktree (`/Volumes/Crucial X6/Developer/lps-aia-pdc`),
**un** Docker (8091) y **una** base MySQL. Este archivo registra lo que esta sesión (A4.2) midió y
necesita comunicar. Intenté enviarlo por `SendMessage` y la sesión no es alcanzable desde aquí, así
que queda escrito para que se transmita.

## Línea base, tomada antes de que nadie escriba

`2026-07-28 22:38:41` · project 73 (Da Porto) · versión de presupuesto 292 · semana activa 4.

| Medición | Valor |
|---|---|
| Paquetes que generan proceso | 96 |
| Amarrados a un frente | 11 |
| **Sin frente** | **85** |
| — de esos, **sin ninguna propuesta** | **45** |
| — reparto de las 40 con propuesta | **ALTA 3 · MEDIA 37 · BAJA 0** |

Evidencia: `scratchpad/diag-a42/LINEA-BASE-20260728-223841.txt`.

## Para la sesión «unique_id vacío en pdc_insumo_actividades» — medido, importante

Confirmado: `pdc_insumo_actividades.unique_id` está **NULL en las 820/820 filas** del proyecto 73.

Me pidieron comprobar si mi conclusión «las hojas del cronograma no dan señal» venía de que esa
columna estuviera vacía. **No viene de ahí.** Lo medí por la vía que su backfill habilitaría —
insumo → item del presupuesto (esa columna sí está poblada) → parecido con las 242 actividades del
cronograma — sobre los 45 paquetes sin propuesta:

| Mejor parecido | Paquetes |
|---|---|
| ≥ 0,50 | **0** |
| 0,34 – 0,49 | **0** |
| 0,20 – 0,33 | 18 (falsos) |
| < 0,20 o nada | 27 |

**Aviso: si el backfill puebla `unique_id` por similitud de nombres, escribirá datos falsos.** El
presupuesto describe productos y el cronograma describe operaciones por ubicación, así que el
parecido léxico engancha por la palabra equivocada. Casos reales medidos:

- `IMPERMEABILIZACION LOSA CUBIERTA` → `LOSA DE CIMENTACIÓN SÓTANO 3` (comparten «LOSA») → frente ESTRUCTURA
- `CONCRETO PILOTES 3500PSI` → `VACIADO EN CONCRETO` colgado de SKATE PARK, fecha **2028**
- `REJILLA DE PISO PARA DUCHA` → `PISO 1` (comparten «PISO»)
- `P8 - PUERTA METALICA 0.80X2.30M` → `RED DE GAS`

Como estas filas arrastran fecha, el error no se queda quieto: se propaga al plan de compras. Solo
**3 de los 45** dan un acierto genuino (`CARPETA ASFALTICA` → `PAVIMENTO ASFALTICO`; `BORDILLOS` y
`M. de O URBANISMO` → `INSTALACIÓN DE CORDONES`).

Si su criterio de backfill **no** es léxico (un código común, un mapeo manual, otra llave), la
lectura cambia y quiero saberlo: no encontré ningún código compartido entre presupuesto y cronograma.

Evidencia: `scratchpad/diag-a42/salida_a42d_unique_id.txt`.

## Compromisos de esta sesión

1. **No leo `pdc_insumo_actividades.unique_id`.** Mi señal es otra — el subcapítulo del presupuesto
   contra el frente del cronograma. Su backfill y mi motor no compiten, y mi medición antes/después
   no se contamina con su escritura. Aun así re-mediré la línea base justo antes de implementar.
2. **No creo ramas en el worktree compartido.** Cuando toque implementar, `git worktree add` propio.
3. **Aviso explícito antes de cualquier `--apply`.** Necesitaré al menos una migración:
   `pdc_paquete_frente.origen` es hoy un enum cerrado (`similitud`, `rama`, `humano`) y la capa nueva
   no cabe sin DDL.
4. **No escribo los 85 amarres.** Los confirma una persona en pantalla.

## Observado en el worktree compartido

Apareció sin trackear `database/migrations/20260728_pdc_v2_tipo_no_aplica.php`, que no es mío. Si es
de otra de las cuatro sesiones, adelante; queda señalado por si no lo es.
