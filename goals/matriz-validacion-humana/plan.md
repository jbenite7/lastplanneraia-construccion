# Plan de implementación: matriz de validación humana

## Enfoque

Extender el extractor existente de `docs/qa/pdc_family_corpus_extractor.php` para que, además del corpus maestro, pueda generar una matriz XLSX de 300 casos revisables. La matriz será un artefacto de QA: no tocará `/listado-actividades/`, reglas del motor, servicios runtime ni datos fuente.

## Pasos

1. Hacer reutilizable el extractor de corpus.
   - Archivo: `docs/qa/pdc_family_corpus_extractor.php`.
   - Agregar un `main` protegido para que el archivo pueda seguir ejecutándose por CLI y también pueda probarse sin disparar generación accidental.
   - Mantener los comandos actuales:
     - `docker compose exec app php docs/qa/pdc_family_corpus_extractor.php`
     - `docker compose exec app php docs/qa/pdc_family_corpus_extractor.php --verify`
   - Verificación:
     - `docker compose exec app php -l docs/qa/pdc_family_corpus_extractor.php`
     - `docker compose exec app php docs/qa/pdc_family_corpus_extractor.php --verify`

2. Enriquecer la trazabilidad de los candidatos.
   - Archivo: `docs/qa/pdc_family_corpus_extractor.php`.
   - Ampliar las filas internas con datos útiles para revisión: `unique_id`, fecha de inicio, semana, archivo Excel, hoja, fila, fuente, proyecto, actividad limpia, contexto, paquete y familia sugerida.
   - Conservar el criterio actual: la familia se infiere primero desde el nombre de la actividad y el contexto solo como respaldo marcado para revisión.
   - Verificación:
     - Confirmar que JMC, Da Porto, Milan y Metrolinea siguen apareciendo en muestras.
     - Confirmar que los patrones actuales siguen presentes.

3. Crear selección determinística de 300 casos.
   - Archivo: `docs/qa/pdc_family_corpus_extractor.php`.
   - Generar una muestra balanceada por proyecto, patrón y familia:
     - Priorizar JMC y Da Porto.
     - Incluir Milan Campestre y Metrolinea.
     - Cubrir todos los patrones de confusión detectados.
     - Completar con familias frecuentes cuando falten cupos.
   - Evitar duplicados por proyecto + actividad + contexto + patrón.
   - Verificación:
     - La matriz tiene exactamente 300 filas de casos.
     - Hay representación de JMC, Da Porto, Milan y Metrolinea.
     - Hay al menos un caso por patrón detectado disponible.

4. Generar el XLSX editable.
   - Archivo: `docs/qa/pdc_family_corpus_extractor.php`.
   - Salida principal: `docs/qa/matriz-validacion-humana.xlsx`.
   - Hojas:
     - `Validacion`: 300 casos para editar.
     - `Listas`: valores permitidos para desplegables.
     - `Resumen`: conteos por proyecto, patrón, familia sugerida y decisión propuesta.
   - Columnas de `Validacion`:
     - `id_caso`
     - `prioridad`
     - `proyecto`
     - `fuente`
     - `actividad_origen`
     - `contexto`
     - `paquete_pdc`
     - `patron_detectado`
     - `familia_sugerida`
     - `decision_humana`
     - `familia_correcta`
     - `nombre_actividad_correcto`
     - `motivo`
     - `accion_recomendada`
     - `notas`
   - Verificación:
     - Abrir el XLSX con PhpSpreadsheet y validar hojas, encabezados y cantidad de filas.

5. Agregar listas desplegables y prellenado inteligente.
   - Archivo: `docs/qa/pdc_family_corpus_extractor.php`.
   - Listas:
     - Decisiones: `Correcto`, `Familia incorrecta`, `Nombre incorrecto`, `No es actividad`, `Va en contratos`, `Nueva familia`, `Dudoso`.
     - Familias: familias candidatas del corpus más opción `Nueva familia`.
     - Acciones: `Mantener`, `Corregir familia`, `Corregir nombre`, `Mover a contratos`, `Excluir`, `Crear nueva familia`, `Revisar manual`.
   - Prellenado recomendado:
     - `familia_correcta`: igual a `familia_sugerida`, salvo cuando el patrón indique una corrección evidente.
     - `decision_humana`: propuesta inicial editable.
     - `nombre_actividad_correcto`: actividad limpia o propuesta corregida cuando la actividad sea ubicación.
     - `motivo`: explicación breve en lenguaje de usuario final.
     - `accion_recomendada`: acción inicial editable.
   - Verificación:
     - PhpSpreadsheet detecta validaciones de datos en las columnas con listas.
     - Las columnas editables vienen prellenadas.

6. Generar resumen accionable.
   - Archivos:
     - `docs/qa/matriz-validacion-humana.summary.json`
     - `docs/qa/matriz-validacion-humana.summary.md`
   - Contenido:
     - Conteos por proyecto.
     - Conteos por patrón.
     - Conteos por familia sugerida.
     - Conteos por decisión propuesta.
     - Lista corta de casos de mayor prioridad.
   - Verificación:
     - JSON válido.
     - Markdown existe y resume los mismos conteos principales.

7. Agregar prueba automática de la matriz.
   - Archivo nuevo: `tests/test_human_validation_matrix.php`.
   - Validar:
     - Existe el XLSX.
     - La hoja `Validacion` tiene 300 filas de casos.
     - Están los encabezados esperados.
     - Existen hojas `Listas` y `Resumen`.
     - Hay listas desplegables en `decision_humana`, `familia_correcta` y `accion_recomendada`.
     - El resumen JSON es válido y coincide con 300 casos.
   - Verificación:
     - `docker compose exec app php tests/test_human_validation_matrix.php`

8. Ejecutar verificación final.
   - Comandos:
     - `docker compose exec app php -l docs/qa/pdc_family_corpus_extractor.php`
     - `docker compose exec app php docs/qa/pdc_family_corpus_extractor.php --verify`
     - `docker compose exec app php docs/qa/pdc_family_corpus_extractor.php --matrix`
     - `docker compose exec app php docs/qa/pdc_family_corpus_extractor.php --verify-matrix`
     - `docker compose exec app php tests/test_human_validation_matrix.php`
   - Criterio de cierre:
     - XLSX generado.
     - 300 filas revisables.
     - Listas desplegables activas.
     - Resumen generado.
     - Ningún cambio al motor ni a `/listado-actividades/`.

## Riesgos y controles

- Riesgo: el extractor actual mezcla generación CLI y funciones reutilizables.
  - Control: introducir `main` protegido antes de añadir nuevos modos.
- Riesgo: la muestra balanceada podría sesgarse demasiado hacia los patrones más numerosos.
  - Control: cuotas mínimas por patrón y proyecto, con relleno determinístico.
- Riesgo: Excel puede tener límites en validaciones largas.
  - Control: usar hoja `Listas` y rangos nombrados en lugar de listas incrustadas largas.
- Riesgo: alguien interprete la matriz como verdad automática.
  - Control: nombrar columnas como propuestas editables y documentar que no cambia reglas del motor.
