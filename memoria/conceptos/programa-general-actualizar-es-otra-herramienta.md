---
capa: wiki
tipo: concepto
estado: vigente
fecha: 2026-08-18
areas: [lps]
fuente: aclaración del usuario en la sesión de coordinación 2026-08-18
resumen: "programa-general es el cronograma del proyecto; programa-general-actualizar es el actualizador desde Project — herramientas distintas, no dos vistas"
---
# Programa General Actualizar no es otra vista: es otra herramienta

**`programa-general` = el cronograma del proyecto.** **`programa-general-actualizar` = el
actualizador de cronogramas a nuevas versiones desde Project.** Lo aclaró el usuario el
2026-08-18 al revisar D-VOC-3.

Por qué importa: la medición del frente de vocabulario había concluido que eran «dos vistas de la
misma tabla `{prog_consolidado}` que clasifican distinto» y recomendaba absorber el estado
`Bloqueado` para igualarlas. Con el contexto real, esa premisa cae: un estado propio del
actualizador (una fila que la versión nueva no puede mover, por ejemplo) puede tener sentido allí
y no existir en el cronograma. **Cualquier unificación de estados entre ambos se evalúa como
herramientas distintas con datos compartidos, no como duplicado a limpiar.**

Afecta a: D-VOC-3 (reevaluar, no absorber por defecto), al replanteo de D-VOC-1, y a las
aserciones de censo de `tests/design-system/states-feedback.test.mjs` (D-1 del frente
contrato-estados, aprobada pero condicionada al censo final).
