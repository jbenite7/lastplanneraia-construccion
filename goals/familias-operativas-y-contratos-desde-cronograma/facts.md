# Hechos aceptados

- /listado-actividades/ debe proponer familias operativas reales tomadas del cronograma.
- Una familia operativa describe trabajo ejecutable y medible del cronograma. Con esa premisa, toma todas las actividades de la BD en las tablas 'programa_consolidado' y 'actividades', y consolida la biblioteca de familias.
- Un contrato, compra, insumo, material, equipo, suministro o subpaquete describe cómo se compra o contrata el trabajo y no debe aparecer como familia de /listado-actividades/. El contrato y la familia pueden llamarse igual, pero su sentido es diferente.
- Los elementos contractuales detectados desde cronograma, matriz o corpus deben alimentar /contratos/ para autogenerar propuestas contractuales cuando apliquen. La primera base es la tabla de la BD 'general_dias_procesos_contratacion'. NO obstante, se deben poblar los contratos que falten (paqueteContratacion) de acuerdo a la biblioteca de familias.
- Acero de Refuerzo y Estructural, Aligerantes Perdidos y Recuperables, Contenedores, Encofrado y Obra Falsa, Equipos de Extincion, Estuco, Fachada HPL, Vidrio y Aluminio, Geodren, Losas de Cimentacion y Luminarias y Artefactos Electricos deben tratarse como ejemplos contractuales iniciales, no como familias de /listado-actividades/.
- Enchapes Ceramicos en Muros debe absorberse en la familia operativa Pisos y Enchapes.
- Red RCI y Red Contra Incendio - Piping deben seguir unificadas como Red de Extinción.
- La matriz/corpus debe separar familias operativas de elementos contractuales y regenerar XLSX, JSON y Markdown con esa separación.
- Las pruebas deben cubrir JMC y Da Porto para comprobar que /listado-actividades/ no propone elementos contractuales como actividades.
- Las pruebas deben comprobar que /contratos/ conserva o genera visibilidad de los elementos contractuales excluidos de /listado-actividades/.
