# Plan de implementación: depurar matriz de validación humana de familias

## Enfoque

Depurar la matriz como artefacto revisable, sin modificar todavía el catálogo base de familias en la base de datos. La corrección se hará en una capa explícita de normalización para la matriz: excluir familias que el usuario marcó como no válidas, unificar las dos variantes de RCI bajo `Red de Extinción`, y eliminar la decisión `No es actividad` de las filas y listas de selección.

El cambio debe ser quirúrgico: no se eliminan ni cambian familias fuera de las siete familias `Mano de Obra - ...` indicadas por el usuario y la unificación `Red RCI` / `Red Contra Incendio - Piping`.

## Pasos

1. Identificar la generación actual de la matriz en `docs/qa/pdc_family_corpus_extractor.php`.
   - Revisar cómo se construyen las filas de `Validacion`.
   - Revisar cómo se construyen las listas de `Listas`.
   - Revisar cómo se generan `matriz-validacion-humana.summary.json` y `matriz-validacion-humana.summary.md`.
   - Verificación: confirmar que el cambio se puede hacer sin tocar tablas ni migraciones.

2. Agregar una capa de depuración exclusiva para la matriz en `docs/qa/pdc_family_corpus_extractor.php`.
   - Crear una lista cerrada de familias excluidas:
     - `Mano de Obra - Acabados`
     - `Mano de Obra - Cimentacion`
     - `Mano de Obra - Estructura`
     - `Mano de Obra - Excavaciones`
     - `Mano de Obra - Instalaciones`
     - `Mano de Obra - Mamposteria`
     - `Mano de Obra - Urbanismo`
   - Crear un alias visible para matriz:
     - `Red RCI` => `Red de Extinción`
     - `Red Contra Incendio - Piping` => `Red de Extinción`
   - Aplicar la normalización a `familia_sugerida`, `familia_correcta` y a la lista desplegable de familias.
   - Verificación: ninguna de las familias excluidas aparece como opción en `Listas`; `Red RCI` no aparece como opción separada; `Red de Extinción` sí aparece.

3. Eliminar `No es actividad` de la matriz generada.
   - Quitar `No es actividad` de la lista de decisiones humanas disponibles.
   - Cambiar cualquier regla que hoy proponga `No es actividad` para que use una decisión revisable, por ejemplo `Nombre incorrecto` cuando el problema sea que una ubicación fue usada como actividad.
   - Filtrar cualquier fila residual que todavía llegue con `No es actividad`.
   - Mantener la matriz con 300 casos revisables, rellenando desde el siguiente candidato válido cuando una fila sea excluida.
   - Verificación: no debe existir ninguna celda `No es actividad` en `decision_humana` y la hoja `Validacion` debe conservar 300 filas de casos.

4. Regenerar artefactos de matriz.
   - Regenerar:
     - `docs/qa/matriz-validacion-humana.xlsx`
     - `docs/qa/matriz-validacion-humana.summary.json`
     - `docs/qa/matriz-validacion-humana.summary.md`
   - Verificación: los tres archivos reflejan la misma depuración.

5. Actualizar `tests/test_human_validation_matrix.php`.
   - Comprobar que la matriz tiene 300 casos.
   - Comprobar que no hay decisiones `No es actividad`.
   - Comprobar que las siete familias `Mano de Obra - ...` no aparecen en el desplegable.
   - Comprobar que `Red RCI` no aparece como familia separada.
   - Comprobar que `Red de Extinción` aparece en el desplegable.
   - Mantener las pruebas existentes que siguen siendo válidas para estructura, columnas y hojas.

6. Ejecutar verificación final.
   - `docker compose exec app php -l docs/qa/pdc_family_corpus_extractor.php`
   - `docker compose exec app php -l tests/test_human_validation_matrix.php`
   - `docker compose exec app php docs/qa/pdc_family_corpus_extractor.php --matrix`
   - `docker compose exec app php docs/qa/pdc_family_corpus_extractor.php --verify-matrix`
   - `docker compose exec app php tests/test_human_validation_matrix.php`

## Riesgos

- `Red de Extinción` puede no existir como familia en `general_pdc_familias`; por eso debe tratarse como alias visible de matriz, no como cambio automático de catálogo.
- Al eliminar `No es actividad`, la muestra puede cambiar. La selección debe seguir llenando 300 casos revisables.
- La exclusión debe ser exacta. No se deben retirar familias similares que no fueron mencionadas, por ejemplo `Mobiliario Urbano`, `Urbanismo`, `Excavaciones Manuales` o `Excavaciones y Movimiento de Tierra`.
- Los planes de compra y el corpus siguen siendo evidencia de aprendizaje, no verdad automática para aplicar reglas en `/listado-actividades/`.

## Condición de terminado

El objetivo queda terminado cuando la matriz XLSX y sus resúmenes quedan regenerados con 300 casos revisables, sin `No es actividad`, sin las siete familias `Mano de Obra - ...`, sin `Red RCI` como opción separada, y con `Red de Extinción` como familia unificada. Además, las pruebas automáticas deben cubrir esas reglas y pasar en Docker.
