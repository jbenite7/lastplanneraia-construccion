# Plan de Implementacion

## Enfoque

Refactorizar el flujo como una migracion controlada, no como un reemplazo de golpe. Primero se estabiliza la verdad de negocio: familias operativas canonicas, aliases y elementos contractuales separados. Luego se ajustan `/listado-actividades/`, `/contratos/` y `/pdc/` para usar esa verdad. Solo despues de probar paridad, evidencia y rollback local se deprecan y retiran rutas, JS y tablas legacy.

El criterio central queda asi:

- Actividad = trabajo operativo que se sigue en obra.
- Contrato = paquete, proveedor, modalidad y posible separacion por fuente/intervencion.
- PDC = seguimiento de pasos y fechas del proceso de contratacion, sin crear familias.

## Fase 0 - Baseline, backup y mapa de impacto

1. Congelar una linea base local de conteos y rutas.
   - Sistemas: `general_pdc_familias`, `general_pdc_activity_rules`, `general_pdc_family_contract_options`, `general_pdc_family_contract_option_items`, `actividades`, `pdc`, `general_informe_pdc`, `general_curvas_pdc`.
   - Archivos/rutas: `public/index.php`, `views/listado-actividades/listadoActividades.view.php`, `views/contratos/contratos.view.php`, `views/pdc/pdc.view.php`, `src/Controllers/Api/*`, `src/Services/SemiAutoService.php`.

2. Crear backup externo verificable antes de cualquier borrado.
   - Guardar dump local fuera de MySQL.
   - Probar restauracion local en una base temporal o prefijo temporal.
   - Comparar conteos clave antes/despues.

3. Crear auditoria de legacy runtime.
   - Identificar llamadas vivas a `/legacy/pdc/actualizar_pdc.php`, `/api/contratos/auto-define*`, `/legacy/cambiar_pagina.php`, tablas `{prefix}_actividades`, `{prefix}_pdc` y equivalentes.
   - Clasificar cada hallazgo como: reemplazar ahora, deprecar una version, conservar por dependencia externa, o fuera de alcance.

Verificacion:

- Nuevo test PHP `tests/test_catalog_refactor_baseline.php`.
- Nuevo reporte sanitizado `docs/qa/evidence/catalog-refactor-*/baseline.json`.
- Auditoria por busqueda automatizada que falle si aparecen usos runtime no permitidos.

## Fase 1 - Catalogo canonico, aliases y contratos

1. Crear tablas separadas para conceptos distintos.
   - `general_pdc_family_aliases`: alias, `familia_id`, activo, fuente, notas, auditoria.
   - `general_pdc_contractual_elements`: nombre contractual, tipo paquete, paquete sugerido, activo, fuente, notas.
   - `general_pdc_family_rule_audit` o equivalente para registrar reasignaciones de reglas.

2. Migrar datos actuales.
   - Mantener en `general_pdc_familias` solo familias operativas canonicas.
   - Mover aliases como `Red RCI`, `Red Contra Incendio - Piping` y equivalentes a aliases canonicos.
   - Mover elementos como mano de obra, suministros, equipos, acero, luminarias, contenedores y similares a elementos contractuales.
   - Reasignar reglas seguras a familias canonicas.
   - Inactivar reglas dudosas o contractuales que no puedan migrarse con confianza.

3. Sacar politica hardcodeada del codigo cuando ya exista persistencia.
   - Hoy `src/Support/OperationalFamilyPolicy.php` contiene aliases y contractuales en arrays.
   - El estado final debe leer de tablas, dejando defaults minimos solo como fallback de emergencia/test.

4. Ajustar matriz humana.
   - `docs/qa/pdc_family_corpus_extractor.php` debe generar el desplegable solo desde familias operativas canonicas.
   - Debe reportar conteos separados: familias, aliases, contractuales, dudosas.

Verificacion:

- `docker compose exec app php tests/test_operational_family_policy.php`
- Nuevo `tests/test_family_catalog_migration.php`
- Nuevo `tests/test_family_aliases_contractual_elements.php`
- `docker compose exec app php docs/qa/pdc_family_corpus_extractor.php --verify-matrix`
- Conteo esperado: `general_pdc_familias` no contiene aliases ni elementos contractuales conocidos.

## Fase 2 - `/listado-actividades/`: familias operativas reales

1. Ajustar matching.
   - Archivos: `src/Support/ActivityMatcher.php`, `src/Support/SemiAutoQualityGate.php`, `src/Services/SemiAutoService.php`.
   - Usar aliases para llegar a familia canonica.
   - Bloquear elementos contractuales como propuestas listas de actividad.
   - Mantener contexto, capitulo, piso, eje, zona e intervencion como evidencia, no como familia.

2. Endurecer la prueba de propuesta lista.
   - Exigir familia canonica.
   - Exigir fuente auditable con nombre, fecha, `unique_id` o identificador equivalente.
   - Exigir trazabilidad completa.
   - Bloquear actividad cuyo nombre sea alias, paquete, proveedor, modalidad, capitulo o ubicacion.
   - Marcar como revision humana cuando una familia sea desconocida, una regla sea dudosa o falte evidencia.

3. Validar la agrupacion operacional.
   - La llave de agrupacion no debe multiplicar actividades por capitulo, piso, eje, zona, frente, sub-obra o intervencion.
   - Si hay alcances realmente distintos, deben separarse por naturaleza operativa: retiro/instalacion, provisional/definitivo, red/equipo, fabricacion/instalacion, deteccion/extincion, excavacion/cimentacion/estructura.

4. Hacer visible la explicacion para usuario final.
   - UI: `public/js/modules/semi_auto_review.js`.
   - Mostrar "Como llego a esta propuesta" con lenguaje no tecnico.
   - Mostrar fuentes e intervenciones como contexto trazable, no como nombres de actividad.
   - Mantener la animacion de analisis guiado suficientemente lenta para que el usuario entienda que se esta revisando.

Verificacion:

- `docker compose exec app php tests/test_semi_auto_quality_gate.php`
- `docker compose exec app php tests/test_semi_auto_service.php`
- `docker compose exec app php tests/test_listado_contractual_exclusion_real_projects.php`
- Nuevo `tests/test_listado_operational_catalog_contract.php`
- E2E con evidencia en JMC, Da Porto, un Metrolinea y Milan Campestre Torre 19.
- Muestra humana obligatoria documentada en `docs/qa/evidence/.../EVIDENCE.md`.

## Fase 3 - `/contratos/`: paquetes desde fuentes, no duplicados de actividades

1. Consumir trazabilidad multi-fuente.
   - Tabla: `actividad_programa_fuentes`.
   - Archivos: `src/Services/SemiAutoService.php`, `src/Controllers/Api/ContratosApiController.php`.
   - Si una actividad operativa viene de varias fuentes, contratos debe sugerir paquetes por intervencion, zona, modalidad o proveedor cuando aplique.

2. Reubicar elementos contractuales.
   - Los elementos retirados de `general_pdc_familias` alimentan opciones contractuales.
   - `general_pdc_family_contract_options` y `general_pdc_family_contract_option_items` deben usar familia canonica o elemento contractual explicito, sin depender de nombres de actividad falsos.

3. Reemplazar auto-definir legacy.
   - Modernizar la funcionalidad de `/api/contratos/auto-define*` dentro del flujo semi-auto.
   - Durante una version, deprecar con mensaje/redireccion clara.
   - Retirar rutas cuando pruebas y auditoria indiquen que no hay uso runtime.

4. Cubrir CRUD manual.
   - Crear, editar, guardar, recargar y ver contratos asociados por proyecto/semana.
   - Validar permisos y auditoria.

Verificacion:

- `docker compose exec app php tests/test_contractual_family_routing.php`
- `docker compose exec app php tests/test_activity_program_sources_traceability.php`
- `docker compose exec app php tests/test_contratos_paquetes_dedup.php`
- Nuevo `tests/test_contratos_from_activity_sources.php`
- E2E: contratos ve multiples fuentes cuando actividad consolidada viene de varias intervenciones.

## Fase 4 - `/pdc/`: seguimiento del proceso de contratacion

1. Reemplazar actualizacion legacy.
   - Archivos: `views/pdc/pdc.view.php`, `src/Controllers/Api/PdcAutoGenerateController.php`, `src/Controllers/Api/PdcApiController.php`, `src/Services/SemiAutoService.php`.
   - Sustituir llamada a `/legacy/pdc/actualizar_pdc.php` por endpoint moderno.
   - Mantener ruta legacy deprecada con mensaje claro durante una version.

2. Garantizar que PDC no cree familias.
   - PDC se genera desde Contratos: toma contratos/paquetes y fechas, no actividades como fuente directa.
   - PDC sigue pasos del proceso: elaboracion pliegos, entrega, recibo propuestas, cuadros, legalizacion, fabricacion/insumos/obra, fechas reales y estado.

3. Probar reportes derivados.
   - `src/Services/ReportProcessor.php` y `src/Controllers/Gestion/ReportController.php`.
   - Informe PDC y curva PDC deben salir de tablas globales con `project_id`.
   - No debe haber SQL runtime que dependa de `{prefix}_pdc` para estos flujos, salvo wrappers de compatibilidad ya justificados.

4. Cubrir CRUD manual y semi-auto.
   - Crear/editar/guardar PDC.
   - Generar desde contratos.
   - Guardar, recargar y comprobar estado visual.

Verificacion:

- `docker compose exec app php tests/test_report_processor_project_scope.php`
- Nuevo `tests/test_pdc_modern_replaces_legacy_update.php`
- Nuevo `tests/test_pdc_does_not_create_families.php`
- Playwright de `/pdc` con captura antes/despues y recording.

## Fase 5 - Administracion completa

1. Crear pantalla admin para catalogo.
   - Ruta nueva en `admin/public/index.php`.
   - Controlador nuevo o extension de `admin/src/Controllers/ConfigController.php`.
   - Vistas bajo `admin/views/pages/matching/` o una carpeta especifica de catalogo.

2. Funciones admin requeridas.
   - CRUD de familias canonicas, aliases y elementos contractuales.
   - Auditoria de cambios.
   - Activar/desactivar.
   - Importar/exportar.
   - Reasignar reglas.
   - Vista de impacto antes de guardar.
   - Flujo de aprobacion.
   - Permisos solo admin.

3. Evitar volver a mezclar conceptos.
   - La UI debe separar claramente "familia operativa", "alias" y "elemento contractual".
   - No permitir guardar un alias como familia nueva sin aprobacion explicita.
   - No permitir que un elemento contractual active propuestas listas de `/listado-actividades/`.

Verificacion:

- Nuevo `tests/test_admin_family_catalog_permissions.php`
- Nuevo `tests/test_admin_family_catalog_crud.php`
- E2E smoke de admin con captura.
- Auditoria DB: cada cambio admin deja actor, fecha, accion y antes/despues.

## Fase 6 - Deprecacion, limpieza y retiro legacy

1. Deprecar primero.
   - `/legacy/pdc/actualizar_pdc.php`
   - `/api/contratos/auto-define*`
   - botones/modales/JS legacy reemplazados por semi-auto moderno.
   - Navegacion que aun usa `/legacy/cambiar_pagina.php` en las tres pantallas.

2. Probar que ya no hay uso runtime.
   - Busqueda automatizada de rutas legacy en `views/`, `public/js/`, `src/Controllers/Api/`, `src/Services/`.
   - E2E de navegacion entre `/listado-actividades`, `/contratos` y `/pdc` sin pasar por legacy.

3. Borrar solo despues de backup, restore y comparacion.
   - Eliminar tablas legacy migradas por prefijo y archives huerfanos del alcance.
   - Conservar backups externos, reportes de limpieza, migraciones y patches historicos.

4. Produccion queda fuera del borrado automatico.
   - La rutina destructiva se ejecuta primero localmente.
   - Produccion requiere checklist aparte y aprobacion explicita.

Verificacion:

- Nuevo `tests/test_legacy_absence_for_lacp_runtime.php`
- Nuevo `tests/test_backup_restore_before_cleanup.php`
- `docker compose exec app php tests/test_global_table_safety.php`
- Evidencia de backup, restore y comparacion en `docs/qa/evidence/...`.

## Fase 7 - Evidencia final y cierre

1. Ejecutar flujo completo en proyectos obligatorios.
   - Optimización Aeropuerto JMC.
   - Da Porto.
   - Un proyecto Metrolinea.
   - Milan Campestre Torre 19.

2. Registrar evidencia local.
   - Capturas.
   - Recording.
   - Trace.
   - `summary.json` sanitizado.
   - `EVIDENCE.md` con conteos y hallazgos humanos.

3. Ejecutar verificacion tecnica completa.
   - `docker compose exec app php tests/test_operational_family_policy.php`
   - `docker compose exec app php tests/test_contractual_family_routing.php`
   - `docker compose exec app php tests/test_semi_auto_quality_gate.php`
   - `docker compose exec app php tests/test_semi_auto_service.php`
   - `docker compose exec app php tests/test_activity_program_sources_traceability.php`
   - `docker compose exec app php tests/test_listado_contractual_exclusion_real_projects.php`
   - Nuevos tests de catalogo, admin, PDC moderno, contratos por fuentes, legacy absence y backup/restore.
   - `docker compose exec app vendor/bin/phpstan analyse src public/index.php --memory-limit=1G`
   - `npx playwright test` para las suites E2E tocadas.

4. Criterio de cierre.
   - No hay familias no canonicas en propuestas listas de `/listado-actividades/`.
   - Contratos conserva paquetes y fuentes sin duplicar actividades.
   - PDC sigue procesos y fechas sin crear familias.
   - Admin permite mantener el sistema sin tocar codigo.
   - Legacy del alcance esta deprecado o eliminado segun fase, con evidencia.
   - Existe rollback local probado para los cambios destructivos.

## Riesgos y controles

- Riesgo: una familia aparentemente contractual tambien puede ser actividad real en otro proyecto.
  - Control: mover a revision humana si la evidencia contradice la clasificacion.

- Riesgo: limpiar legacy antes de probar reportes rompe PDC o curva.
  - Control: deprecar primero, comparar reportes y borrar al final.

- Riesgo: admin completo crece demasiado.
  - Control: mantener el alcance aceptado; no agregar gobierno avanzado fuera del flujo de aprobacion pedido.

- Riesgo: las pruebas JMC/Da Porto no cubren todo.
  - Control: agregar Metrolinea y Milan Campestre Torre 19 como evidencia obligatoria.

- Riesgo: backup existe pero rollback no funciona.
  - Control: restore local y comparacion antes de cualquier `DROP`.
