# Hechos aceptados

- La primera versión entregará un archivo XLSX editable como artefacto principal de revisión humana.
- El XLSX tendrá listas desplegables predefinidas para las columnas de decisión humana, familia correcta y acción recomendada cuando aplique.
- La matriz incluirá una muestra balanceada de 300 casos tomados del corpus actual.
- La muestra balanceada cubrirá proyectos prioritarios como JMC y Da Porto, e incluirá representación de Milan Campestre y proyectos Metrolinea.
- Cada fila tendrá trazabilidad suficiente para revisar el caso: proyecto, fuente, actividad de origen, contexto, paquete o contrato cuando exista, patrón detectado y familia sugerida.
- Cada fila tendrá columnas de revisión humana: decisión humana, familia correcta, nombre de actividad correcto, motivo, acción recomendada y notas.
- Las columnas de revisión vendrán prellenadas por el sistema con una propuesta inicial, para que el usuario pueda editarlas en Excel.
- La columna decisión humana permitirá estos valores: Correcto, Familia incorrecta, Nombre incorrecto, No es actividad, Va en contratos, Nueva familia y Dudoso.
- La matriz será regenerable desde los datos actuales sin modificar archivos fuente, cronogramas, PDC ni reglas del motor.
- La implementación no cambiará el comportamiento de /listado-actividades/ ni aplicará reglas nuevas al motor.
- Además del XLSX, se generará un resumen accionable que muestre distribución por proyecto, familia sugerida, patrón detectado y decisión propuesta.
- La matriz deberá poder verificarse automáticamente: existencia del XLSX, 300 filas de casos, encabezados esperados, listas desplegables y resumen generado.
