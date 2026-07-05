# Hechos aceptados

- En la revision semi-auto de /listado-actividades, el campo Actividad debe ser un selector poblado con todas las fuentes posibles de la propuesta.
- Las opciones del selector Actividad deben ordenarse por mayor confianza, luego por fecha de inicio mas cercana a mas lejana, y luego por nombre para mantener un orden estable.
- El valor inicial del selector Actividad debe ser la fuente mas confiable con la fecha de inicio mas cercana.
- Actividad de inicio y Fecha de inicio no deben mostrarse como campos editables en la revision; deben mostrarse solo como contexto legible derivado de la Actividad seleccionada.
- Al cambiar la Actividad seleccionada, el contexto visible de actividad fuente y fecha de inicio debe actualizarse automaticamente.
- La Semana no debe aparecer como campo editable ni como decision de usuario en la revision de crear actividad.
- Modalidad de contratacion debe permitir seleccionar una o varias modalidades usando el mismo criterio de /contratos/: SI es excluyente, y MO, S y OC pueden combinarse.
- La edicion inline de Modalidad de contratacion en la tabla debe permitir las mismas selecciones multiples que la revision/modal de /contratos/.
- Al aplicar o guardar una propuesta, el backend debe recibir y persistir la Actividad seleccionada, su actividad de inicio, su fecha de inicio y todas las modalidades seleccionadas.
- La revision debe usar lenguaje operativo para usuarios finales y ocultar conceptos tecnicos como run IDs, payloads, reglas internas, IDs crudos o nombres tecnicos salvo en detalle de administrador.
- La verificacion final debe incluir pruebas PHP enfocadas, una prueba Playwright del flujo en Da Porto semana 1 y una captura donde se vea el selector de Actividad, la modalidad multiple y la ausencia de Semana.
