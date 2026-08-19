---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-19
areas: [proceso]
tags: [pendiente]
fuente: decisiones/estados-consolidado-coordinadora.md
resumen: Tomadas por Felipe en conversación directa con la coordinadora, 2026-08-19. Complementan las cuatro del brainstorming DS-F1a…
---

<!-- cas:cita-textual — registro de decisiones del usuario: se citan tal como se tomaron -->
# Decisiones del usuario — estados de programa_consolidado

Tomadas por Felipe en conversación directa con la coordinadora, 2026-08-19. Complementan las
cuatro del brainstorming DS-F1a (`decisiones/ds-f1a-estado-ejecutor.md`).

## 1. «Fuera de Ventana» es valor persistido, no solo etiqueta

**Qué se decide:** el estado recuperado (antes `No Requerida`, 12.338 filas en 10 de 16 proyectos)
se guarda en la columna `Estado` de `programa_consolidado` con su nombre nuevo, no solo se muestra
en pantalla.

**Consecuencia asumida:** migración de datos sobre los 16 proyectos — respaldo verificable, gate
de Plannotator, dry-run primero y reconciliación posterior, según `docs/global-tables-architecture.md`.
Se unifica con el recálculo masivo de la columna `Estado` (los 8 estados legacy + 11,8% vacío):
es una sola pasada, no dos.

**Se presentó y no se eligió:** etiqueta de pantalla sin migración (recomendación de la
coordinadora, descartada por el usuario).

**Ratificación con el número real (2026-08-19, más tarde):** medido que la regla de 7+ semanas
reclasifica 26.084 filas (no 12.338): «Fuera de Ventana» pasaría a ~51% de las actividades y
«Actividad Futura» bajaría a ~6,8%. Presentado a Felipe con opciones de ajustar el umbral o ver la
distribución primero; **eligió mantener 7+ semanas** conociendo ese reparto. Los 113 casos
contradictorios (`En Curso`/`Terminada` con `Semanas_Inicio >= 7`) se auditan antes de migrar.

## 2b. Apply del recálculo — AUTORIZADO por Felipe (2026-08-19)

Con el informe completo del dry-run delante (40.664 filas cambiarían; respaldo probado restaurando
2.024 filas estropeadas; las 24 y las 296 medidas con recomendación; las 31 que corrigen a favor),
Felipe autorizó en el canal de la coordinadora: **«Sí, apply completo»** — las tres familias
incluidas, sin excepciones. Se presentaron y no se eligieron: apply excluyendo las 24 y las 296, y
aplazar. La ejecución exige los gates de `docs/global-tables-architecture.md` (Plannotator,
respaldo verificable —ya existe y está probado—, restauración y reconciliación posterior) y ventana
de base exclusiva. Solo cubre la base de DESARROLLO; producción es deploy y va aparte.

## 3. Orden de ejecución (decisión de la coordinadora, técnica)

El trabajo se parte en dos frentes: **(A)** contrato + calculador (reversible, sin tocar datos) y
**(B)** la migración sobre 16 proyectos (irreversible; respaldo, dry-run, gate de Plannotator y
autorización expresa del usuario para el apply). A desplaza al frente de z-index (DS-F1b) en la
cola. Un error de contrato debe descubrirse antes de que los datos estén migrados.

## 2. «Con Alerta Restricciones» se queda como está — DEROGADA la versión anterior

**Historia:** la primera versión de esta decisión («el PG no la implementa; sale de leyenda y
contrato») partía de una premisa falsa: se midió la columna `Estado` (cero filas) y se concluyó
ausencia. El ejecutor de ds-f1a bloqueó la ejecución y demostró con código que la etiqueta se
deriva **al pintar** (`public/js/modules/programa_general/hot.js:745` `getRestrictionAlertKey`)
desde columnas que **sí persisten**: `Estado_Restricciones` (ratio 0..1, el % visible en PG,
verificado: 5.427 filas en 1, 50.454 en 0) y las cinco duras (`D_y_E`, `Materiales`, `MdeO`,
`Equipos`, `Predecesora`), leídas por 8 archivos del servidor. Señal real medida: 202 actividades
en un proyecto con datos. Felipe corrigió la premisa en conversación directa.

**Qué se decide (2026-08-19, con el cuadro completo):** la etiqueta se queda como está — derivada
en el cliente desde los datos persistidos. NO se quita de la leyenda del PG ni de
`state-semantics.json` (su mapeo `attention` es correcto y describe algo que existe).

**Se presentó y no se eligió:** derivarla también en el servidor (para informes/exports) y
retirar el rótulo dejando solo PI/PS.

**Nota derivada:** `En Liberación de Restricciones` (5.463 filas legacy en la columna `Estado`)
sigue desapareciendo con el recálculo del frente B — eso no cambia; es estado persistido viejo,
no la etiqueta derivada.
