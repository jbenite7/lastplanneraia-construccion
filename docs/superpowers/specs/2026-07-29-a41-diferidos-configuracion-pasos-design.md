# PDC v2 — Los cuatro diferidos de A4.1 (configuración de pasos) — Design

- **Fecha:** 2026-07-29
- **Ola:** 2 (después del lanzamiento)
- **Goal:** `goals/pdc-preparar-b1`
- **Origen:** decisión del grilleo de A4.1 (2026-07-28), que los registró fuera de alcance dejando
  constancia de que ninguno bloquea B1. El comité no los pidió; entran porque el aeropuerto los va a
  necesitar.
- **Estado:** aprobado en grilleo, pendiente de plan.

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
| 1 | **Listas por modalidad** | Media | Es el que más modelo toca: la configuración deja de ser por obra y pasa a ser por obra × modalidad. Antes de construirlo hay que **medir si de verdad hace falta**: preguntar a las dos obras si sus cuatro modalidades siguen procesos distintos |
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
3. **Listas por modalidad:** *precondición* — evidencia escrita de al menos una obra que necesite dos
   listas distintas. Sin esa evidencia, no se construye y se anota el porqué.
4. **Historial:** cada cambio de configuración deja quién, cuándo y qué cambió, y se puede ver.
5. Cero regresión: una obra sin configurar sigue teniendo los siete pasos de siempre — el contrato que
   A4.1 demostró contra las 11 filas y 77 pasos de Da Porto.

## Riesgos

- **El nº 1 es el caro y el que menos evidencia tiene.** Está aquí porque se registró, no porque alguien
  lo haya pedido desde que se registró. La precondición del punto 3 existe para no construir a ciegas.
- **Copiar puede arrastrar basura.** Si la obra origen tiene una configuración a medias, la copia la
  hereda. La pantalla debe mostrar qué se va a copiar antes de copiarlo.
