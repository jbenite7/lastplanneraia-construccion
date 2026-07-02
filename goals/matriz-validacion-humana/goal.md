# Matriz de validación humana

Crear una matriz de validación humana en XLSX para convertir el corpus actual de familias, asociaciones y patrones en un set revisable por el usuario. La matriz debe venir prellenada con propuestas iniciales, listas desplegables y trazabilidad suficiente para corregir familias, nombres de actividades y decisiones de clasificación sin tocar todavía el motor de `/listado-actividades/`.

La comprensión compartida está en `goals/matriz-validacion-humana/facts.md`.

El plan de ejecución aprobado está en `goals/matriz-validacion-humana/plan.md`.

## Condición de terminado

El objetivo queda terminado cuando exista `docs/qa/matriz-validacion-humana.xlsx` con 300 casos balanceados, columnas de revisión prellenadas, listas desplegables, resumen accionable y verificación automática aprobada. No debe cambiar el comportamiento de `/listado-actividades/` ni aplicar reglas nuevas al motor.
