# Goal: Popup de Proceso para Familias Auto-generadas

## Articulación del Goal
Agregar un ícono de info-circle en la columna de acciones de cada familia auto-generada en la tabla de "Familias de obra". Al hacer clic, se muestra un popover/tooltip discreto con el proceso de cómo se llegó a esa familia: familia detectada, regla de matching, confianza, actividades del Programa General agrupadas, y capítulo/contexto.

## Referencias
- **Facts:** `goals/popup-proceso-familia/facts.md`
- **Plan:** `goals/popup-proceso-familia/plan.md`

## Done Condition
1. El ícono de info-circle aparece en la columna de acciones de cada familia auto-generada
2. El ícono NO aparece en familias creadas manualmente o importadas desde Excel
3. Al hacer clic se muestra un popover con la información del proceso
4. La información mostrada incluye: familia detectada, regla, confianza, contexto, y actividades del PG
5. La API retorna los campos `auto_generado` y `fuentes_info`
