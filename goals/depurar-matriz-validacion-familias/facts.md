# Hechos aceptados

- La matriz no debe incluir filas cuya decisión humana sea No es actividad.
- El desplegable de familia correcta no debe incluir familias de Mano de Obra como Mano de Obra - Acabados, Mano de Obra - Cimentacion, Mano de Obra - Estructura, Mano de Obra - Excavaciones, Mano de Obra - Instalaciones, Mano de Obra - Mamposteria ni Mano de Obra - Urbanismo.
- Red RCI y Red Contra Incendio - Piping deben unificarse bajo una sola familia visible llamada Red de Extinción.
- El desplegable de familia correcta no debe mostrar Red RCI como opción separada.
- La implementación no debe eliminar ni cambiar otras familias fuera de las familias de Mano de Obra y la unificación de RCI indicada por el usuario.
- La matriz seguirá entregándose como XLSX con hojas Validacion, Listas y Resumen.
- La matriz seguirá teniendo 300 casos revisables después de excluir filas No es actividad.
- Los resúmenes JSON y Markdown deben regenerarse con la matriz depurada.
- La prueba automática debe comprobar que ya no aparecen No es actividad, familias de Mano de Obra ni Red RCI como opción separada.
