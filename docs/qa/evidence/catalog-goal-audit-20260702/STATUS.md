# Estado del goal: familias, contratos y PDC

Fecha de corte: 2026-07-02  
Goal auditado: `goals/catalogo-familias-operativas-contratos-aliases/goal.md`

## Resultado actual

Regla confirmada: `semanas_activas` manda para determinar la semana vigente del corte. Para el 2026-07-02, las semanas activas son JMC 7, Da Porto 8 y Milan 6.

El bloque de PDC desde Contratos quedo probado para los tres proyectos obligatorios trabajados en esta ronda. Despues de incorporar el feedback de familias de Da Porto/JMC, defaults de contrato y la resincronizacion revisada de Carpinteria Metalica en JMC, la validacion viva queda:

| Proyecto | Semana | Contratos | PDC | Faltantes | Duplicados |
|---|---:|---:|---:|---:|---:|
| Optimizacion Aeropuerto JMC | 7 | 50 | 50 | 0 | 0 |
| Da Porto | 8 | 25 | 25 | 0 | 0 |
| Milan Campestre Torre 19 | 6 | 4 | 4 | 0 | 0 |

Evidencia principal: `docs/qa/evidence/pdc-perfect-20260702/EVIDENCE.md`.
Evidencia E2E de previews en los cuatro proyectos obligatorios: `docs/qa/evidence/previews-lacp-four-projects-20260702130343/EVIDENCE.md`.
Evidencia de reclasificacion de listados: `docs/qa/evidence/reclassify-listado-families-20260702/summary.json`.
Evidencia de sincronizacion PDC desde Contratos actuales: `docs/qa/evidence/pdc-sync-current-contracts-20260702/summary.json`; luego el runner final `docs/qa/scripts/pdc_goal_20260702_runner.php` dejo JMC 50/50, Da Porto 25/25 y Milan 4/4.
Evidencia de catalogo operativo depurado: `database/migrations/20260709_inactivate_alias_contractual_families.sql` y `tests/test_family_catalog_operational_only.php`.
Evidencia de persistencia de aprendizajes en BD: `tests/test_learning_persistence_catalog_db.php`.
Evidencia de reportes derivados: `tests/test_report_processor_project_scope.php`.
Auditoria de cierre criterio por criterio: `docs/qa/evidence/catalog-goal-audit-20260702/completion-audit.md`.
Manifiesto de limpieza legacy LACP: `docs/qa/evidence/catalog-goal-audit-20260702/legacy-cleanup-readiness.md`.
Auditoria humana pendiente del catalogo: `docs/qa/evidence/catalog-goal-audit-20260702/catalog-human-audit.md`.
Matriz de decision para las 13 familias ambiguas: `docs/qa/evidence/catalog-goal-audit-20260702/human-decision-matrix-13-families.md`.
Paquete JSON de decisiones propuestas: `docs/qa/evidence/catalog-goal-audit-20260702/human-decision-proposed-actions.json`.
Checklist de aprobacion humana: `docs/qa/evidence/catalog-goal-audit-20260702/human-decision-approval-checklist.md`.
Manifiesto de bloqueos de cierre: `docs/qa/evidence/catalog-goal-audit-20260702/goal-close-blockers.json`.
Semaforo operativo de cierre: `docs/qa/scripts/goal_close_readiness.php`.

## Persistencia de aprendizajes en BD

Los aprendizajes revisados no quedan solo en codigo ni en documentos. Quedan registrados en tablas globales para que el flujo no vuelva a proponerlos de la misma manera:

| Tipo de aprendizaje | Tabla persistente | Uso esperado |
|---|---|---|
| Familias operativas canonicas | `general_pdc_familias` | `/listado-actividades/` crea una actividad por familia canonica. |
| Nombres equivalentes o aliases | `general_pdc_family_aliases` | El motor reconoce variantes como RCI/Red Contra Incendio sin duplicar familias. |
| Elementos de contrato o compra | `general_pdc_contractual_elements` | `/contratos/` y `/pdc/` generan paquetes sin contaminar el listado de actividades. |
| Reglas de deteccion del cronograma | `general_pdc_activity_rules` | El matcher decide familia desde nombre, capitulo o ruta profunda. |
| Defaults de modalidad/paquetes | `general_pdc_family_contract_options` y `general_pdc_family_contract_option_items` | Contratos propone S, MO, SI o combinaciones validas por familia. |
| Auditoria de cambios humanos | `general_pdc_family_rule_audit` y auditoria general | Permite rastrear ajustes y reasignaciones. |

Estado vivo del catalogo despues de la ultima depuracion: 94 familias totales, 63 activas, 31 inactivas, 15 aliases activos, 16 elementos contractuales activos y 124 reglas activas.

La migracion `20260709_inactivate_alias_contractual_families.sql` cierra una brecha importante: si una fila de `general_pdc_familias` tambien existe como alias o como elemento contractual activo, queda inactiva como familia operativa. Las reglas activas que apuntaban a esas familias inactivas tambien se desactivan.

La politica runtime `OperationalFamilyPolicy` ya no conserva listas internas de aliases ni elementos contractuales. Carga aliases activos desde `general_pdc_family_aliases` y elementos contractuales activos desde `general_pdc_contractual_elements`; si el catalogo cambia, Listado y Contratos leen esa decision desde BD.

## Objetivo operativo de 3 proyectos

El objetivo operativo "los 3 proyectos obligatorios tienen un plan de compras perfecto, actualizado al 2 de julio 2026" queda probado para:

- Optimizacion Aeropuerto JMC.
- Da Porto.
- Milan Campestre Torre 19.

La prueba viva `tests/test_pdc_three_projects_perfect_20260702.php` valida que cada proyecto cubre el 2026-07-02, tiene paquetes definidos en Contratos, tiene el mismo numero de paquetes en PDC, no tiene faltantes, no tiene extras, no tiene duplicados, no tiene filas incompletas y no deja propuestas pendientes en segundo pase PDC.

## Criterios ya cubiertos

- `/pdc/` se genera desde Contratos, no desde Actividades.
- `/listado-actividades/` agrupa por familia canonica una sola vez y conserva todas las fuentes asociadas.
- `/contratos/` usa esas fuentes asociadas para estimar paquetes/subcontratos por intervencion o zona sin crear actividades duplicadas.
- JMC semana 7 queda con 36 familias y Da Porto semana 8 queda con 21 familias, sin duplicados por zona, piso, etapa o intervencion.
- Da Porto incorpora Cimentaciones, Estabilizacion del Suelo, Aseo, Carpinteria Metalica, Topografia, Red Hidrosanitaria, Red Electrica, Mesones de Cocina y Banos y Paisajismo desde el cronograma.
- En Da Porto no se crean Red de Deteccion de Incendio, Red de Extincion de Incendios ni Red de Telecomunicaciones porque esas fuentes no aparecen en `programa_consolidado` de la semana 8.
- En JMC se adoptan los mismos patrones y quedan presentes Cimentaciones, Estabilizacion del Suelo, Topografia, Red Hidrosanitaria, Red Electrica, Red de Deteccion de Incendio, Red de Extincion de Incendios y Red de Telecomunicaciones.
- Red Electrica queda por defecto como Suministro + Mano de Obra; Pinturas Interiores y Exteriores queda por defecto como Suministro e Instalacion.
- `LOCALIZACION Y REPLANTEO` se trata como Topografia aunque este bajo el capitulo Preliminares.
- El matcher de familias ya revisa nombre visible, `[Capitulo: ...]` y ruta jerarquica profunda del cronograma.
- Nombres que son solo contexto o accion, como `Piso 12`, `Ejes 45 y 47`, `Staff` o `Retiro`, no desplazan la familia detectada en el capitulo/ruta.
- El boton visible de PDC usa el endpoint moderno `/api/pdc/auto/apply-from-contratos`.
- La ruta vieja `/legacy/pdc/actualizar_pdc.php` fue retirada del router y el archivo `src/Legacy/actualizar_pdc.php` fue eliminado.
- La navegacion entre `/listado-actividades/`, `/contratos/` y `/pdc/` ya no usa `/legacy/cambiar_pagina.php` en esas tres vistas.
- Las rutas modernas aceptan `?semana=` y sincronizan la semana activa si existe para el proyecto.
- La accion visible de auto-definicion en Contratos abre el asistente moderno y el contador de pendientes usa `/api/contratos/auto/preview`.
- Los endpoints legacy `auto-define*` de Contratos fueron retirados; el reemplazo vigente es `/api/contratos/auto/*`.
- La vista de Contratos ya no carga el modal ni JS legacy de `auto-define*`.
- El controlador de Contratos ya no conserva los metodos internos legacy `autoDefine*`.
- Existe auditoria automatizada de ausencia de legacy runtime para Listado, Contratos y PDC dentro del alcance aprobado.
- PDC no crea familias ni actividades.
- El segundo pase de PDC queda sin propuestas pendientes en JMC, Da Porto y Milan.
- Hay capturas, videos y traces locales para los tres proyectos anteriores.
- Hay capturas, videos y traces locales de preview para JMC, Da Porto, Milan y Metrolinea Estacion 16 - Edificio Ascendente.
- Hay backup externo local previo a las mutaciones de esos proyectos.
- Existe backup/restauracion probado para el alcance Listado/Contratos/PDC: crea backup SQL externo de un proyecto temporal, genera checksum, borra solo ese proyecto temporal, restaura y compara conteos/huella antes del retiro legacy aprobado.
- La pantalla Admin de catálogo permite mantener familias, aliases y elementos contractuales, exportar CSV, importar CSV, aprobar/activar elementos y reasignar reglas con auditoría.
- La reasignación de reglas deja registro en `general_pdc_family_rule_audit` y en la auditoría general del sistema.
- Los aliases y elementos contractuales conocidos ya no quedan activos como familias operativas en `general_pdc_familias`.
- Las reglas activas ya no apuntan a familias inactivas.
- Los elementos contractuales siguen disponibles para Contratos aunque hayan salido del catalogo activo de familias.
- La politica de familias ya no depende de arreglos hardcodeados para aliases o contractuales; el test compara contra los conteos activos de BD.
- La administracion bloquea que un alias o elemento contractual activo se vuelva a aprobar como familia operativa.
- Los reportes derivados de PDC e informe/curva PDC se regeneran desde tablas globales con `project_id` y validacion de alcance por proyecto.
- Las decisiones humanas de los 13 casos ambiguos quedaron aplicadas: cero familias activas permanecen con `siempre_revision = 1`.
- En previews reales de JMC, Da Porto, Milan y Metrolinea, los elementos contractuales conocidos no quedan como familias listas ni preseleccionadas.
- Admin muestra una cola de "Decisiones pendientes" para decidir si las familias ambiguas siguen en Listado o pasan a Contratos.
- Admin permite resolver cada decision pendiente: mantenerla en Listado cierra la revision obligatoria; pasarla a Contratos inactiva la familia de Listado, desactiva sus reglas y crea/activa el elemento contractual, dejando auditoria. El E2E usa familias temporales para probar ambas rutas sin tocar decisiones reales.
- El CRUD manual aislado de Listado, Contratos y PDC queda probado con proyecto temporal: crear/editar/recargar actividad, guardar/recargar paquetes de contratos y crear/editar/recargar paquete PDC.

## Criterios cubiertos con decisiones aprobadas

- `general_pdc_familias` ya queda depurada contra aliases y elementos contractuales conocidos; las 13 decisiones humanas quedaron aplicadas en BD.
- `/listado-actividades/` ya tiene protecciones para no usar elementos contractuales conocidos como familias listas y evidencia de preview con Metrolinea.
- `/contratos/` conserva fuentes y paquetes en los casos probados y ya tiene prueba aislada de fuentes multiples sin duplicar actividades; Metrolinea queda omitido para aplicacion/CRUD completo por decision del usuario.
- La UI semi-auto muestra explicacion en lenguaje de usuario final y proceso guiado dentro de la evidencia final disponible.

## Decisiones de alcance

- Mantener el monitoreo de nuevas familias ambiguas que aparezcan en futuras rondas; los 13 casos documentados ya no son bloqueo abierto.
- Evidencia completa de aplicacion/CRUD en Metrolinea queda omitida por decision del usuario: `tests/test_metrolinea_evidence_scope.php` valida que los proyectos Metrolinea tienen cronograma para preview, pero 0 actividades globales y 0 PDC global.
- Ampliar la auditoria de legacy solo si se decide incluir modulos fuera de Listado, Contratos y PDC.
- Borrado legacy LACP aprobado y ejecutado para rutas/archivo dentro de alcance, despues de backup/restauracion/comparacion.
- Retiro o deprecacion de otros puntos legacy fuera de Listado/Contratos/PDC requiere aprobacion aparte.

## Verificaciones ejecutadas

- `docker compose exec app php tests/test_pdc_modern_replaces_legacy_update.php`: OK.
- `docker compose exec app php tests/test_activity_matcher_hierarchy.php`: OK.
- `docker compose exec app php tests/test_activity_program_sources_traceability.php`: OK.
- `docker compose exec app php tests/test_contratos_activity_sources_multi_group.php`: OK, valida que Contratos usa dos fuentes/intervenciones para proponer dos subcontratos, conserva la explicacion, no duplica paquetes y no crea actividades adicionales.
- `docker compose exec app php tests/test_semi_auto_quality_gate.php`: OK.
- `docker compose exec app php tests/test_semi_auto_service.php`: OK.
- `docker compose exec app php tests/test_contractual_family_routing.php`: OK.
- `docker compose exec app php tests/test_da_porto_jmc_family_patterns.php`: OK.
- `docker compose exec app php tests/test_listado_reclassified_real_projects.php`: OK.
- `docker compose exec app php tests/test_semi_auto_da_porto_feedback.php`: OK.
- `docker compose exec app php tests/test_listado_contractual_exclusion_real_projects.php`: OK.
- `docker compose exec app php tests/test_admin_family_catalog_crud.php`: OK, incluye resolver decisiones pendientes hacia Listado o Contratos.
- `docker compose exec app php tests/test_admin_family_catalog_permissions.php`: OK.
- `npx playwright test tests/browser/admin-family-catalog.mjs`: OK, incluye exportacion CSV, captura, importacion controlada, aprobacion/activacion, reasignacion de regla con auditoria y resolucion de decisiones pendientes hacia Listado o Contratos.
- Captura de decisiones pendientes: `docs/qa/evidence/admin-family-catalog-decisions-20260702.png`.
- `docker compose exec app php tests/test_pdc_three_projects_perfect_20260702.php`: OK.
- `docker compose exec app php tests/test_learning_persistence_catalog_db.php`: OK, valida tablas globales de aprendizaje/catalogo, 507 feedbacks registrados, aliases RCI/Enchapes, elementos contractuales y defaults Red Electrica/Pinturas.
- `docker compose exec app php tests/test_family_catalog_operational_only.php`: OK.
- `docker compose exec app php tests/test_operational_family_policy.php`: OK, valida que aliases y contractuales salen de BD y no de arreglos hardcodeados.
- `docker compose exec app php tests/test_pdc_does_not_create_families.php`: OK.
- `docker compose exec app php tests/test_report_processor_project_scope.php`: OK, regenera Curva S, Curva S PDC e informe PDC y valida alcance por `project_id`.
- `docker compose exec app php tests/test_equipment_families_require_review.php`: OK, valida que las familias `EQUIPOS` activas y sus reglas no quedan sin revision obligatoria.
- `docker compose exec app php tests/test_listado_equipment_review_real_projects.php`: OK, valida previews reales y confirma que `EQUIPOS` no queda listo/preseleccionado.
- `docker compose exec app php tests/test_review_required_families_block_auto_apply.php`: OK, valida que no quedan revisiones obligatorias globales y que Telecomunicaciones + Seguridad y Control pasan a revision cuando aparecen juntas.
- `docker compose exec app php tests/test_human_decision_matrix_coverage.php`: OK, valida que la matriz humana conserva trazabilidad de las 13 decisiones y que la BD ya no tiene pendientes globales.
- `docker compose exec app php tests/test_human_decision_actions_package.php`: OK, valida que el paquete JSON de decisiones queda aprobado/aplicado y cubre las 13 decisiones.
- `docker compose exec app php tests/test_human_decision_approval_checklist.php`: OK, valida que el registro de aprobacion cubre las 13 decisiones y que la BD ya no tiene pendientes globales.
- `docker compose exec app php tests/test_completion_audit_goal_coverage.php`: OK, valida que `completion-audit.md` cubre los 9 criterios Done del goal y registra decisiones de alcance aprobadas.
- `docker compose exec app php tests/test_goal_close_blockers_manifest.php`: OK, valida que el manifiesto no conserva bloqueos abiertos y registra Metrolinea como exclusion aceptada.
- `docker compose exec app php tests/test_goal_close_readiness_script.php`: OK, valida que el semaforo `docs/qa/scripts/goal_close_readiness.php` devuelve `ready_to_close` y sale con codigo 0.
- `docker compose exec app php tests/test_metrolinea_evidence_scope.php`: OK, valida que Metrolinea tiene evidencia E2E de preview para Listado, Contratos y PDC, y que la BD actual no tiene actividades/PDC globales para aplicar CRUD completo sin datos artificiales.
- `docker compose exec app php tests/test_lacp_legacy_cleanup_readiness.php`: OK, valida que el manifiesto legacy cubre rutas retiradas, reemplazos modernos, guardas y backup/restauracion previo al retiro.
- `docker compose exec app php tests/test_lacp_modern_navigation.php`: OK.
- `docker compose exec app php tests/test_lacp_manual_crud_persistence.php`: OK, valida CRUD manual y recarga en Listado, Contratos y PDC sobre proyecto temporal sin tocar proyectos reales.
- `docker compose exec app php tests/test_contratos_modern_assistant_replaces_auto_define_ui.php`: OK.
- `docker compose exec app php tests/test_auto_definir_contratos.php`: OK.
- `docker compose exec app php tests/test_legacy_absence_for_lacp_runtime.php`: OK.
- `docker compose exec app php tests/test_lacp_backup_restore_before_cleanup.php`: OK, valida backup SQL externo, checksum, limpieza temporal, restauracion local y comparacion de conteos/huella para tablas LACP globales.
- `npx playwright test tests/browser/auto-definir-contratos.mjs`: 2/2 OK.
- `npx playwright test tests/browser/test-pdc.mjs`: 6/6 OK.
- `npx playwright test tests/browser/semi-auto-real-projects.evidence.mjs`: OK, evidencia nueva en `docs/qa/evidence/previews-lacp-four-projects-20260702130343/`.
- `docker compose exec app vendor/bin/phpstan analyse src public/index.php admin --memory-limit=1G`: OK.
- `node --check tests/browser/admin-family-catalog.mjs`: OK.
- `node --check tests/browser/test-pdc.mjs`: OK.
- `git diff --check`: OK.

## Decision de avance

La app queda funcional en el flujo PDC probado y el objetivo operativo de 3 proyectos queda verificado. Metrolinea queda omitido para aplicacion/CRUD completo por decision del usuario y cubierto como preview E2E. La limpieza legacy LACP aprobada fue ejecutada localmente con backup/restauracion/comparacion previa.

## Verificacion adicional 2026-07-02

Se repitio la verificacion de persistencia de aprendizajes y protecciones de catalogo:

- `general_pdc_familias`: 63 familias activas.
- `general_pdc_family_aliases`: 15 aliases activos.
- `general_pdc_contractual_elements`: 16 elementos contractuales activos.
- `general_pdc_activity_rules`: 124 reglas activas.
- `general_pdc_family_rule_audit`: 22 registros de auditoria de reasignacion.
- `semi_auto_feedback`: 507 feedbacks guardados.

Tambien se corrigio este documento para referenciar las tablas reales de defaults contractuales: `general_pdc_family_contract_options` y `general_pdc_family_contract_option_items`.

La prueba `tests/test_learning_persistence_catalog_db.php` deja esta garantia ejecutable: si `Enchapes Ceramicos en Muros`, `Red RCI` o `Red Contra Incendio - Piping` vuelven a quedar como familias activas; si `Acero de Refuerzo y Estructural`, `Equipos de Extincion`, `Mano de Obra - Acabados` o `Luminarias y Artefactos Electricos` dejan de vivir como elementos contractuales; o si Red Electrica/Pinturas pierden sus defaults aprobados, la verificacion falla.

El comando `docker compose exec app php docs/qa/scripts/goal_close_readiness.php` es el semaforo de cierre. En el estado actual devuelve `ready_to_close`, confirma los 3 PDC obligatorios como verificados y no lista bloqueos abiertos.
