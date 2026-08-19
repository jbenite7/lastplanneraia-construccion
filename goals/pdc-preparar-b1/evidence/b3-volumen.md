---
capa: fuente
tipo: evidencia
estado: vigente
fecha: 2026-07-29
areas: [pdc]
fuente: goals/pdc-preparar-b1/evidence/b3-volumen.md
resumen: El punto 4 dice «los indicadores cargan con el volumen real de las obras activas». La Ola 1 declaró que su regla se validó con 4 paquetes y 21 pasos, no con…
---

# B3 — Medición de volumen (punto 4 de la condición de hecho)

**Fecha:** 2026-07-30 · **Base:** `lastplanneraia_dev` del stack `last-planner-aia` · **Rama:** `worktree-pdc-b3-torre-control`

El punto 4 dice «los indicadores cargan con el volumen real de las obras activas». La Ola 1 declaró que
su regla se validó con 4 paquetes y 21 pasos, no con los 96 previstos: **probada, no estresada**. Esta
medición existe para no repetir esa ambigüedad.

## Volumen real disponible hoy

| Obra | `project_id` | Pasos pendientes | Destinos |
|---|---|---|---|
| Da Porto | 73 | 21 | 3 |

**Es la única obra con plan de compras en esta base.** Cualquier afirmación de rendimiento apoyada solo en
este dato sería falsa por insuficiencia, no por error.

## Medición con el volumen real

| Operación | Tiempo |
|---|---|
| `getBrief('pdc', [73])` completo | 0,039 s |
| `vencimientosAgregados([73])` | < 0,001 s |
| `detalleDestinos([73])` | 0,001 s |

## Medición con el volumen que el spec preveía

Como el volumen real no basta, se sembró un proyecto sintético (`999952`) con **96 paquetes × 9 pasos =
864 pasos pendientes**, que es el tamaño que el spec de la Ola 1 anticipaba para Da Porto. Sembrado,
medido y **borrado** al terminar (residuo verificado en 0).

| Operación | Volumen | Tiempo |
|---|---|---|
| `vencimientosAgregados()` | 864 pasos, 96 destinos | 0,004 s |
| `detalleDestinos()` | 864 filas devueltas | 0,008 s |
| `getBrief('pdc')` una obra grande | 864 pasos | 0,024 s |
| `getBrief('pdc')` diez obras grandes | 8 640 pasos | 0,009 s |

## Lo que esto sí demuestra y lo que no

**Sí:** el agregado multi-obra aguanta de sobra el volumen previsto. La consulta única con `IN (...)` no se
degrada al multiplicar obras, que era el riesgo que motivó descartar el bucle por obra.

**No:** que el bucle de `PaquetesService::resumen()` —una consulta **por obra**, la excepción declarada en
el plan— escale a decenas de obras **con presupuesto real cargado**. En la medición de diez obras el
proyecto sintético no tenía presupuesto, así que `resumen()` devolvía pronto. Con presupuestos reales ese
bucle es el primer candidato a convertirse en agregado.

**Tampoco:** el comportamiento con varias obras reales a la vez, sencillamente porque hoy **solo hay una**.
Cuando entren más a producción, esta medición hay que repetirla; el número que la invalidaría es el tiempo
de `getBrief`, no el del agregado.
