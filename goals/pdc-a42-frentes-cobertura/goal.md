---
capa: fuente
tipo: goal-doc
estado: vigente
fecha: 2026-07-29
areas: [pdc]
fuente: goals/pdc-a42-frentes-cobertura/goal.md
resumen: Goal — A4.2: que el plan de compras sepa a qué frente va cada paquete
---

# Goal — A4.2: que el plan de compras sepa a qué frente va cada paquete

## El objetivo

El plan de compras está al 11 % por conteo porque 85 de los 96 paquetes que generan proceso no tienen
frente, y a 45 de ellos el motor no les ofrece ni una propuesta. La causa medida no es el umbral: los
paquetes hablan de oficios (`CIELOS RASOS`, `CEMENTO`) y el cronograma habla de fases (`ACABADOS`,
`ESTRUCTURA`), así que no comparten palabras. Esta tanda añade el puente que falta —**subcapítulo del
presupuesto → frente del cronograma**, 25 correspondencias que cubren el 100 % de los 85— y convierte
el nombre del paquete en el desempate dentro del grupo que el subcapítulo ya acotó.

- Entendimiento compartido: [`facts.md`](facts.md) — 33 hechos, todos aceptados.
- Plan de ejecución: [`plan.md`](plan.md) — 8 tareas.
- Grilleo: [`interview.json`](interview.json) / [`interview-result.json`](interview-result.json) —
  14 preguntas; 13 recomendaciones aceptadas y una divergencia (actividades sueltas del cronograma)
  resuelta después en chat: **el motor nunca las propone, pero se pueden amarrar a mano**.
- Coordinación con las otras tres sesiones PDC: [`coordinacion-sesiones.md`](coordinacion-sesiones.md).

## Línea base, medida antes de tocar nada

`2026-07-28 22:38:41` · Da Porto (project 73) · versión 292 · semana activa 4.

| Medición | Antes |
|---|---|
| Paquetes que generan proceso | 96 |
| Amarrados | 11 |
| **Sin frente** | **85** |
| — **sin ninguna propuesta** | **45** |
| — reparto de las 40 con propuesta | **ALTA 3 · MEDIA 37 · BAJA 0** |

Evidencia: `scratchpad/diag-a42/LINEA-BASE-20260728-223841.txt`.

## Condición de hecho

Se cumple cuando, corriendo **el mismo script de la línea base sin modificarlo** contra el MySQL real:

1. Los paquetes **sin ninguna propuesta bajan de 45 a 10 o menos** (f26).
2. Las propuestas de **confianza ALTA suben de 3 a 30 o más** (f27).
3. Los **11 amarres que ya existían siguen intactos** (f28).
4. **Ningún amarre se escribió solo**: los 85 los confirma una persona en pantalla (f29).
5. La pestaña «Sin frente» permite cerrar la sesión de amarre sin escrituras a ciegas — el botón
   principal solo acepta ALTA, las MEDIA pasan por confirmación con importe y lista, las BAJA van de
   una en una (f22/f23).
6. Sigue en verde lo que ya estaba: Vitest, `npm run build` y los tests de plan de fechas contra la
   base real (f33).

Sin cifra medida y pegada como salida de comandos, no está hecho.

## Estado al cerrar la sesión — 2026-07-28

**Cumplido y medido tres veces** (la última a las 23:10, ya con el backfill de `unique_id` de la
sesión hermana escrito, sin que las cifras se movieran). Commit `be256ee` en la rama
`pdc-a42-frentes` del worktree `/Volumes/Crucial X6/Developer/lps-aia-a42`, sin push ni merge:

| Condición | Antes | Después | ¿Cumple? |
|---|---|---|---|
| 1 · sin ninguna propuesta ≤ 10 | 45 | **4** | ✅ |
| 2 · confianza ALTA ≥ 30 | 3 | **71** | ✅ |
| 3 · los 11 amarres intactos | 11 | **11** | ✅ |
| 4 · ningún amarre escrito por la máquina | — | **0 escritos** | ✅ |
| 5 · cerrar la sesión de amarre desde la pantalla | — | **panel + atajo + endpoints** | ✅ |
| 6 · Vitest / build / tests en verde | — | **242/242 · build OK · 17/17 · PHPStan 0** | ✅ |

### Cierre — condición 5 completada el 2026-07-28 (commit `ed5dfd1`)

Los cinco puntos que faltaban están hechos: endpoints `GET/POST /plan/correspondencias` y
`GET /plan/anclas` con RBAC (`…reglas` para el catálogo global, `…editar` para la excepción de obra);
`sugerencias` devuelve `motivos`; panel plegable en «Sin frente» con el atajo desde la fila sin
propuesta; `sugeridoUniqueId` viaja en la procedencia; y el test PHP dedicado.

Verificado: `test_pdc_v2_plan_fechas_correspondencias.php` **17/17**, PHPStan **0 errores**, Vitest
**242/242**, `npm run build` OK, e2e `pdc-v2-plan` **2/2** contra el bundle publicado, y la medición
final **4 sin propuesta · ALTA 71 · MEDIA 10** con el panel en **33 confirmadas / 4 pendientes**.

**Lo único que no se verificó visualmente** es el panel renderizado en pantalla: el e2e existente
pasa contra este bundle (o sea, la pantalla carga y funciona), pero no se llegó a tomar una captura
del panel abierto. Queda como la primera comprobación de quien retome.

Los cinco puntos que quedaron pendientes en la primera pasada (endpoints, anclas en el selector,
panel, `sugeridoUniqueId` y el test dedicado) están todos hechos y verificados arriba.

**Sobre la condición 6.** `tests/test_pdc_v2_plan_fechas.php` queda con 3 fallos, en
`calcular()`/`pdc_plan_paso`. No son de este cambio: se comprobó revirtiendo el servicio y seguían.
En el worktree compartido, con los commits de A4.1 dentro y el árbol limpio, ese mismo test da 317
PASS / 0 FAIL, así que **no hay evidencia de un fallo en el estado integrado** y no debe escalarse:
esos asserts dependen del trabajo de A4.1, que aquí no está. Las 12 aserciones que este cambio sí
rompió están actualizadas y en verde. Vitest (242/242) y `npm run build` sí se corrieron en la segunda pasada,
junto con PHPStan (0 errores) y el e2e `pdc-v2-plan` (2/2).

## Lo que esta tanda NO toca

El cálculo de fechas, `const PASOS` y los pasos de contratación configurables (son de la tarea A4.1,
que corre en paralelo sobre el mismo archivo), la pestaña «Plan», y los 25 paquetes que siguen sin
`duracion_ref` (Task 5 del plan A4, anotada y diferida). Tampoco lee
`pdc_insumo_actividades.unique_id`: está vacío, lo va a poblar otra sesión, y esta tanda no depende de
ese trabajo ni lo estorba.

---

## Cierre formal

**Estado:** HECHO
**Fecha de cierre:** 2026-07-28 (documentado formalmente 2026-07-31)
**Commit:** `ed5dfd1` (rama `pdc-a42-frentes`, mergeado a `main`)

### Condición de hecho — cumplida

| # | Condición | Resultado |
|---|---|---|
| 1 | Sin propuesta ≤ 10 | **4** (de 45) ✅ |
| 2 | ALTA ≥ 30 | **71** (de 3) ✅ |
| 3 | 11 amarres intactos | **11** ✅ |
| 4 | 0 amarres automáticos | **0** ✅ |
| 5 | Panel de amarre funcional | Endpoints + panel + atajo ✅ |
| 6 | Tests en verde | 242/242 Vitest · PHPStan 0 · 2/2 e2e ✅ |

---

## Archivos de este goal

[[goals/pdc-a42-frentes-cobertura/coordinacion-sesiones|coordinacion-sesiones.md]] · [[goals/pdc-a42-frentes-cobertura/facts|facts.md]] · [[goals/pdc-a42-frentes-cobertura/plan|plan.md]]

Estado y relación con los demás goals: [[estado|Estado de los goals]].
