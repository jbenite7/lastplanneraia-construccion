# Plan: Listado con fuentes y contratacion claras

## Enfoque

Corregir el flujo semi-auto de `/listado-actividades` sin cambiar la arquitectura global. El cambio vive en el payload de sugerencias de `SemiAutoService`, la UI compartida `SemiAutoReview` y pruebas enfocadas. La decision de usuario debe ser una fuente de Programa General y una o varias modalidades de contratacion; `actividadInicio`, `fechaInicio` y `semanaActualizacion` dejan de ser campos editables.

## Pasos

1. Normalizar fuentes en backend para `create_activity`.
   - Archivos: `src/Services/SemiAutoService.php`, `src/Support/SemiAutoQualityGate.php`.
   - Ordenar `analysis.sources` por `confidence DESC`, `start_date ASC`, `activity ASC`, `unique_id ASC`.
   - Usar esa misma fuente ordenada como valor inicial de `actividadInicio` y `fechaInicio`.
   - Agregar al `apply_payload` un campo estable como `selectedSourceId` o `selectedSource` para que la UI pueda cambiar la fuente sin exponer IDs crudos como decision principal.
   - Mantener `semanaActualizacion` fuera de la edicion del usuario; `applyCreateActivity()` debe seguir usando la semana del contexto.
   - Verificacion: prueba PHP que construya una propuesta con fuentes desordenadas y confirme orden, fuente inicial y ausencia de semana editable.

2. Derivar campos dependientes al guardar ajustes inline.
   - Archivo: `src/Services/SemiAutoService.php`.
   - Cambiar `editableFieldsForAction('create_activity')`: quitar `actividadInicio`, `fechaInicio` y `semanaActualizacion`; permitir el nuevo campo de fuente seleccionada y `tipoContrato`.
   - Cuando llega correccion de fuente seleccionada, buscarla en `apply_payload._analysis.sources` y actualizar `fields.actividadInicio`, `fields.fechaInicio` y el diff local desde esa fuente.
   - Cuando llega `tipoContrato`, aceptar valores multiples normalizados (`SI`, `MO`, `S`, `OC`, `MO,S`, `MO,OC`, `S,OC`, `MO,S,OC`) y rechazar combinaciones invalidas donde `SI` venga mezclado con otras.
   - Verificacion: prueba PHP de `feedback()` que cambie la fuente y confirme que aplicar persiste la fuente elegida; prueba de modalidad multiple e invalida.

3. Cambiar la UI de revision semi-auto para Listado.
   - Archivo: `public/js/modules/semi_auto_review.js`.
   - En `editFieldsHtml()` tratar `create_activity` con una vista especifica:
     - `Actividad`: select con todas las fuentes posibles.
     - Contexto legible: actividad fuente y fecha de inicio derivadas de la opcion seleccionada.
     - Sin inputs editables para `Actividad de inicio`, `Fecha de inicio` ni `Semana`.
     - `Modalidad de contratacion`: checkboxes reutilizando la regla de `/contratos/`: `SI` excluye `MO/S/OC`; `MO/S/OC` son combinables.
   - En `saveInlineFeedback()` serializar correctamente select de fuente y checkboxes de modalidad.
   - Verificacion: prueba Playwright que abra `/listado-actividades?semana=1` en Da Porto, revise una propuesta y confirme selector de actividad, contexto derivado, modalidad multiple y ausencia de Semana.

4. Ajustar resumen y lenguaje visible.
   - Archivo: `public/js/modules/semi_auto_review.js`.
   - Cambiar textos visibles para usuarios finales: “Propuesta para crear actividad”, “Basado en el programa general”, “Necesita revision porque mezcla trabajos distintos”.
   - Mantener run IDs, payloads, reglas internas e IDs tecnicos solo dentro de “Detalle tecnico” para admin.
   - Evitar mostrar `tipoContrato`, `actividadInicio`, `semanaActualizacion`, `match_source`, `breadcrumb` o payloads como texto normal.
   - Verificacion: ampliar `tests/browser/semi-auto-review.mjs` para assert de ausencia de conceptos tecnicos y presencia de lenguaje operativo.

5. Verificar persistencia al aplicar.
   - Archivos: `src/Services/SemiAutoService.php`, tests PHP existentes.
   - Asegurar que `applyCreateActivity()` inserta `actividadInicio`, `fechaInicio`, `tipoContrato` y `actividad_programa_fuentes` coherentes con la fuente elegida y todas las fuentes trazables.
   - Verificacion: prueba PHP enfocada que aplique una sugerencia corregida y lea `actividades` + `actividad_programa_fuentes`.

6. Evidencia final.
   - Comandos:
     - `docker compose exec app php tests/test_semi_auto_service.php`
     - `docker compose exec app php tests/test_semi_auto_da_porto_feedback.php`
     - Nuevo test PHP enfocado si se crea, por ejemplo `docker compose exec app php tests/test_listado_activity_source_selection.php`
     - `npx playwright test tests/browser/semi-auto-review.mjs`
   - Captura requerida: Da Porto semana 1 con selector de Actividad, modalidad multiple y sin campo Semana.

## Riesgos y notas

- `SemiAutoReview` es UI compartida por Listado, Contratos y PDC. La personalizacion debe activarse solo para `module === 'listado-actividades'` y `action === 'create_activity'`.
- El diff actual se genera desde campos planos; si se mantiene `actividadInicio`/`fechaInicio` en `visibleDiff`, la UI puede volver a exponerlos. Hay que filtrar campos derivados en frontend y backend.
- La modalidad `E` existe en el sistema para equipos, pero el contrato aprobado para este goal solo menciona `SI`, `MO`, `S` y `OC`. No introducir `E` en la UI de Listado salvo que otro goal lo apruebe.
- Algunas propuestas pueden no tener `analysis.sources`. En ese caso el selector debe mostrar una opcion de revision bloqueada o dejar la tarjeta como no aplicable, no inventar fuente.
