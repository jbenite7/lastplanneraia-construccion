# Plan: escenarios operativos de /contratos/

## Solution approach

Resolver `/contratos/` como el lugar donde el usuario define modalidades, paquetes, recursos, cantidades de contratos por paquete, duraciones contractuales y trazabilidad semanal de cambios. `/pdc/` entra solo como receptor existente de duraciones y recalculo cuando `/contratos/` actualiza datos que ya lo alimentan; no se rediseña PDC. Proveedores disponibles, cambio de proveedor por calidad y proveedor renombrado quedan fuera del goal porque proveedores no se seleccionan ni mantienen en `/contratos/`.

## /eli5 fixes

1. Fix de cantidades por paquete: al lado de cada paquete en el modal de `/contratos/` habrá un numerito con flechas para que el usuario diga cuantos contratos salen de ese paquete. El sistema no decide ese numero solo.

2. Fix de modalidades: si el usuario necesita mano de obra y suministro separados, puede elegir `MO,S`. Si necesita un contrato integral, elige `SI`, y `SI` no se mezcla con las otras modalidades.

3. Fix de orden de compra: `OC` puede acompañar `MO` o `S` cuando el usuario necesita una compra adicional.

4. Fix de cinco paquetes: se mantienen cinco paquetes por tipo. Si el usuario llena los cinco, el sistema muestra una alerta suave de posible sobreplanificacion, sin ampliar el modelo.

5. Fix de trazabilidad semanal: cuando cambien actividad de inicio, fecha de inicio, contratos, paquetes, recursos o cantidades, debe quedar claro en que semana ocurrió el cambio.

6. Fix de duraciones: si un paquete no tiene las 7 duraciones del proceso contractual, `/contratos/` abre un submodal para completarlas y guardarlas en `general_dias_procesos_contratacion`.

7. Fix de fecha de inicio: si cambia la fecha de inicio, `/contratos/` lo refleja, avisa al usuario y dispara el recalculo hacia el PDC ya existente.

8. Fix de semi-auto: si una decision ya fue confirmada manualmente, el asistente no la cambia solo. Puede mostrar una propuesta, pero no aplicarla ni preseleccionarla.

9. Fix de undo: deshacer una corrida semi-auto restaura solo campos de `/contratos/`, no toca otros modulos salvo el flujo existente que ya corresponda.

10. Fix de permisos y preconstruccion: quien no tiene permiso no edita ni auto-define; y proyectos de preconstruccion no operan `/contratos/` ni por URL directa ni por API.

## Ordered steps

1. Documentar matriz funcional antes de tocar tests.
   - Files/systems: `docs/qa/workflows.md`, nuevo `docs/qa/contratos-escenarios.md`.
   - Work: registrar los 20 escenarios, marcando proveedores como fuera de alcance, semanas como trazabilidad tecnica, y PDC como receptor existente de duraciones/recalculo.
   - Verification: diff documental y `git status` para confirmar que no se tocaron tests antes de cerrar la matriz.

2. Auditar y ajustar modelo de datos de `/contratos/`.
   - Files/systems: `actividades`, `general_dias_procesos_contratacion`, posible nueva tabla global `contratos_trazabilidad` o columnas por slot si el modelo actual no alcanza.
   - Work: definir donde viven las cantidades por paquete. `actividades.numeroSubcontratos` existe, pero hoy parece global por actividad; el fact exige cantidad al lado de cada paquete, por lo que puede requerir campos por slot o una tabla normalizada por `project_id`, `actividad_id`, `semana`, `tipo`, `slot`.
   - Verification: prueba PHP de schema, guardado por paquete, y aislamiento por `project_id` + semana.

3. Implementar cantidades por paquete en backend manual.
   - Files/systems: `src/Controllers/Api/ContratosApiController.php`.
   - Work: `list()` debe devolver cantidad por paquete; `save()` debe recibir enteros >= 1 para cada paquete lleno; paquetes vacios no deben conservar cantidad; el motor no debe inferir cantidades por su cuenta.
   - Verification: PHP para MO estructura con cantidad 2, paquetes vacios, valores invalidos y guardado/recarga.

4. Implementar cantidades por paquete en UI.
   - Files/systems: `views/contratos/contratos.view.php`, CSS solo si hace falta.
   - Work: agregar input entero con flechas al lado de cada paquete de cada tipo; mantener layout usable; serializar valores; mostrar alerta si se llenan los 5 paquetes de un tipo.
   - Verification: Playwright abre modal, edita paquete MO con cantidad 2, guarda, recarga y verifica el valor.

5. Mantener reglas de modalidad.
   - Files/systems: `views/contratos/contratos.view.php`, `src/Controllers/Api/ContratosApiController.php`, `tests/test_contratos_manual_tipo_badge.php`.
   - Work: asegurar `MO,S`, `MO,OC`, `S,OC`, `MO,S,OC`; SI excluyente; OC combinable con MO/S; backend rechaza combinaciones invalidas aunque UI falle.
   - Verification: PHP de normalizacion/validacion y Playwright de checkboxes.

6. Submodal de duraciones contractuales.
   - Files/systems: `views/contratos/contratos.view.php`, `src/Controllers/Api/ContratosApiController.php`, `general_dias_procesos_contratacion`.
   - Work: cuando un paquete no tenga duraciones completas, mostrar estado incompleto y abrir submodal para capturar 7 duraciones: elaboracion pliegos, entrega pliegos, recibo propuestas, cuadros comparativos, legalizacion, fabricacion, insumos obra. Guardar esas duraciones en `general_dias_procesos_contratacion`.
   - Verification: PHP de guardar duraciones y Playwright de submodal.

7. Integracion con PDC existente para recalculo.
   - Files/systems: `src/Controllers/Api/ContratosApiController.php`, flujo existente de PDC que ya recibe duraciones/recalculo.
   - Work: al cambiar fecha de inicio o duraciones desde `/contratos/`, reflejar/advertir y disparar el recalculo por el mecanismo existente, sin redisenar PDC.
   - Verification: test enfocado que confirma que `general_dias_procesos_contratacion` alimenta el flujo esperado y que no se introducen rutas PDC nuevas innecesarias.

8. Trazabilidad semanal.
   - Files/systems: nueva tabla/servicio de auditoria o log existente, `ContratosApiController`.
   - Work: registrar semana, usuario, actividad, campo cambiado y valores antes/despues para actividad de inicio, fecha de inicio, modalidad, paquetes, recursos y cantidades.
   - Verification: PHP que edita en una semana y comprueba que la trazabilidad queda asociada a esa semana sin contaminar otra.

9. Semi-auto Contratos respetando decisiones manuales.
   - Files/systems: `src/Services/SemiAutoService.php`, `src/Support/SemiAutoQualityGate.php`, `public/js/modules/semi_auto_review.js`.
   - Work: sin familia confiable queda en revision; familia sin paquete queda revision/manual; actividad manual confirmada no se cambia nunca automaticamente; re-run puede mostrar propuesta pero no preseleccionarla ni aplicarla sobre decision manual.
   - Verification: PHP de preview/apply/re-run y Playwright del panel.

10. Undo semi-auto de campos de Contratos.
   - Files/systems: `src/Services/SemiAutoService.php`.
   - Work: asegurar que undo restaura modalidad, paquetes, recursos, cantidades, confianza y marca de auto-definicion. No debe tocar proveedores porque no aplican a `/contratos/`.
   - Verification: PHP con snapshot antes/aplicar/deshacer.

11. Permisos y proyectos de preconstruccion.
   - Files/systems: `src/Controllers/Gestion/ContratosController.php`, `src/Controllers/Api/ContratosApiController.php`, `src/Controllers/Api/SemiAutoController.php`, `src/Security/RbacCatalog.php`, `tests/browser/fixtures/projects.mjs`.
   - Work: roles sin permiso no editan ni auto-definen por UI/API; proyectos PC bloquean operar `/contratos/` por navegacion, URL directa y API.
   - Verification: PHP/API 403 y Playwright con proyecto PC opt-in.

12. Evidencia final.
   - Files/systems: nuevos tests y `docs/qa/evidence/contratos-escenarios-*`.
   - Work: cubrir escenarios principales con PHP, navegador, snapshots, guardar/recargar y capturas/recording.
   - Verification commands:
     - `docker compose exec app php tests/test_contratos_operational_scenarios.php`
     - `docker compose exec app php tests/test_contratos_permissions_scope.php`
     - `docker compose exec app php tests/test_contratos_activity_sources_multi_group.php`
     - `docker compose exec app php tests/test_contratos_manual_tipo_badge.php`
     - `npx playwright test tests/browser/contratos-operational-scenarios.mjs`
     - `npx playwright test tests/browser/auto-definir-contratos.mjs tests/browser/semi-auto-review.mjs`

## Risks and open questions

- El modelo actual tiene `numeroSubcontratos` global por actividad, pero el fact aprobado exige cantidad al lado de cada paquete. Esto probablemente requiere normalizar cantidades por paquete/slot o agregar columnas por slot.
- El submodal de duraciones toca `general_dias_procesos_contratacion`, que alimenta PDC. Hay que mantener el cambio como integracion de datos, no redisenar PDC.
- `actualizarBadgePendientesContratos()` llama preview al cargar la pagina. Si preview crea corridas, puede generar ruido de trazabilidad; debe revisarse antes de sumar auditoria nueva.
- Proveedores quedan fuera de `/contratos/`; no deben reaparecer como campos, selects ni adjudicaciones indirectas en este goal.
- Preconstruccion ya oculta el nav, pero falta confirmar bloqueo por URL/API directa.
