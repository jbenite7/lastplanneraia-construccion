# Facts - Popup de Proceso para Familias Auto-generadas

- En la tabla de Familias de obra, cada fila correspondiente a una familia auto-generada muestra un ícono de info-circle (fas fa-info-circle) en la columna de acciones.
- Al hacer clic en el ícono de info-circle, se abre un popover/tooltip discreto mostrando los datos de la familia auto-generada.
- El popover muestra la siguiente información: familia detectada, regla de matching usada, nivel de confianza (porcentaje), lista de actividades del Programa General agrupadas, y capítulo/contexto.
- El ícono de info-circle NO aparece en familias creadas manualmente o importadas desde Excel, solo en las auto-generadas por el sistema semi-automático.
- La API de listado (/api/listado-actividades/list) retorna un campo adicional 'auto_generado' (boolean) que indica si la familia fue creada por el sistema semi-automático.
- La API de listado retorna un campo 'fuentes_info' (objeto/null) con los datos de la tabla actividad_programa_fuentes: family_name, match_rule, confidence, context, y lista de source_activity.
