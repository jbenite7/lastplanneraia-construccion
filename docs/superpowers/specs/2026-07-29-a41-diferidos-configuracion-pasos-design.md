---
capa: fuente
tipo: spec
estado: cerrado
fecha: 2026-07-29
areas: [proceso]
fuente: docs/superpowers/specs/2026-07-29-a41-diferidos-configuracion-pasos-design.md
resumen: goals/pdc-preparar-b1 - Origen: decisión del grilleo de A4.1 (2026-07-28), que los registró fuera de alcance dejando constancia de que ninguno bloquea B1. El…
---

# PDC v2 — Los cuatro diferidos de A4.1 (configuración de pasos) — Design

- **Fecha:** 2026-07-29
- **Ola:** 2 (después del lanzamiento)
- **Goal:** `goals/pdc-preparar-b1`
- **Origen:** decisión del grilleo de A4.1 (2026-07-28), que los registró fuera de alcance dejando
  constancia de que ninguno bloquea B1. El comité no los pidió; entran porque el aeropuerto los va a
  necesitar.
- **Estado:** **cerrado** (fila 7b del tablero, `3a0da33`). Tres diferidos construidos y uno archivado con motivo: las dos obras siguen el mismo proceso en todas las modalidades. A4.1 no tiene pendientes.

## Problema

A4.1 hizo configurables los pasos del proceso de contratación por obra: catálogo global
`general_pasos_contratacion` (9 pasos) + `pdc_proyecto_pasos` por obra, con `paso_id` como identidad
estable. Al cerrarlo se registraron cuatro cosas que se dejaron fuera a propósito:

1. Listas de pasos distintas **por modalidad o tipo de negociación** dentro de una misma obra.
2. **Copiar** la configuración de una obra a otra.
3. **Historial de versiones** de la configuración.
4. **Editar las duraciones del catálogo legacy** desde la pantalla de pasos.

## Por qué ahora

El módulo pasa de una obra piloto a dos obras reales, y la segunda es el aeropuerto —«la prueba de
fuego», con insumos a tres manos—. La primera cosa que hace quien monta la segunda obra es querer partir
de lo que ya funcionó en la primera. **El diferido nº 2 (copiar) es el que el uso va a exigir primero**;
los otros tres se ordenan detrás.

## Alcance y orden

| # | Qué | Prioridad | Nota de diseño |
|---|---|---|---|
| 2 | **Copiar configuración entre obras** | Alta — la pide el aeropuerto | Copia explícita y puntual, no un vínculo vivo: copiar y luego editar sin que la fuente se entere. Requiere elegir obra origen entre las que el usuario puede ver |
| 4 | **Duraciones del catálogo legacy editables** | Media | Hoy hay que tocar la base para cambiar un número que mueve las fechas de toda la obra. RBAC `lps.paquetes_contratacion.reglas`, el mismo que ya protege los pasos |
| 1 | **Listas por modalidad** | **ARCHIVADO 2026-07-30** | Se preguntó a las dos obras y **siguen el mismo proceso** en todas las modalidades. La precondición no se cumple porque no hace falta, no por falta de respuesta. Ver `goals/pdc-preparar-b1/evidence/listas-por-modalidad-no-se-construye.md`. Solo se reabre con evidencia de una obra que de verdad contrate por caminos distintos, y entonces con grilleo propio |
| 3 | **Historial de versiones** | Baja | Solo tiene sentido cuando haya varias obras cambiando su configuración y alguien pregunte «¿por qué se movieron mis fechas?». Hoy no hay a quién responderle |

## Decisión de proceso

**Cada uno de los cuatro se escribe y se ejecuta por separado.** Se agrupan en este spec porque comparten
origen y superficie (la pantalla de pasos), no porque sean un solo trabajo. El nº 1 en particular debe
pasar por su propio grilleo antes de tocar nada: cambia la forma del modelo de datos.

## Condición de hecho

1. **Copiar:** configurar la obra A, copiarla a la obra B, y comprobar que B queda igual; editar B no
   cambia A; recalcular el plan de B usa los pasos copiados.
2. **Duraciones:** cambiar un día en la pantalla mueve la fecha del plan que dependía de él, y solo esa.
   Un rol sin la capacidad recibe 403.
3. **Listas por modalidad:** **cumplido por la negativa el 2026-07-30.** La precondición pedía evidencia
   de al menos una obra que necesitara dos listas distintas; se preguntó y las dos obras siguen el mismo
   proceso en todas las modalidades. No se construye, y el porqué está anotado. Este punto queda cerrado.
4. **Historial:** cada cambio de configuración deja quién, cuándo y qué cambió, y se puede ver.
5. Cero regresión: una obra sin configurar sigue teniendo los siete pasos de siempre — el contrato que
   A4.1 demostró contra las 11 filas y 77 pasos de Da Porto.

## Riesgos

- **El nº 1 era el caro y el que menos evidencia tenía.** Estaba aquí porque se registró, no porque
  alguien lo hubiera pedido desde entonces. La precondición existía para no construir a ciegas, y funcionó:
  al preguntar, la respuesta fue que no hace falta. Es el caso en que una precondición ahorra la fase
  entera, no solo la retrasa.
- **Copiar puede arrastrar basura.** Si la obra origen tiene una configuración a medias, la copia la
  hereda. La pantalla debe mostrar qué se va a copiar antes de copiarlo.

---

## Estado verificado — cerrado

Verificado contra el código el 2026-08-25. **`estado: cerrado` es una afirmación deliberada**, no el valor por defecto del backfill.

**Evidencia:** PasosContratacionService.php (copiar); historialVersiones.ts; DuracionesCatalogoService.php

Criterio y método: [[docs/superpowers/plans/2026-08-25-estado-real-de-planes-y-specs]].
