# Auditoria de cierre del goal

Fecha: 2026-07-02  
Goal: `goals/catalogo-familias-operativas-contratos-aliases/goal.md`

## Resultado ejecutivo

El objetivo operativo de los 3 proyectos obligatorios queda probado: Optimización Aeropuerto JMC, Da Porto y Milán Campestre Torre 19 tienen PDC perfecto contra Contratos para el corte 2026-07-02.

El goal amplio queda listo para cierre con dos decisiones explícitas registradas:

- Metrolinea se omite de la aplicación/CRUD completo por decisión del usuario; se conserva evidencia de preview para Listado, Contratos y PDC.
- La limpieza legacy LACP fue aprobada por el usuario y ejecutada después de backup externo local, checksum, restauración y comparación.

Manifiesto machine-readable de cierre: `docs/qa/evidence/catalog-goal-audit-20260702/goal-close-blockers.json`.
Semaforo operativo de cierre: `docs/qa/scripts/goal_close_readiness.php`.

## Criterios del goal

| Criterio | Estado | Evidencia fuerte | Brecha restante |
|---|---|---|---|
| `general_pdc_familias` contiene solo familias operativas canónicas. | Probado para decisiones aprobadas | `tests/test_family_catalog_operational_only.php`; `tests/test_operational_family_policy.php`; `tests/test_learning_persistence_catalog_db.php`; `database/migrations/20260711_apply_human_family_decisions.sql`. | Sin bloqueo humano abierto; el catálogo puede seguir afinándose con nuevas entrevistas. |
| Aliases y elementos contractuales viven fuera de `general_pdc_familias`. | Probado para conocidos | `general_pdc_family_aliases`, `general_pdc_contractual_elements`; `tests/test_family_catalog_operational_only.php`; `tests/test_learning_persistence_catalog_db.php`; `tests/test_human_decision_matrix_coverage.php`. | Sin brecha para los 13 casos aprobados. |
| `/listado-actividades/` no genera propuestas listas con aliases, contratos, capítulos, ubicaciones o contexto como familia. | Probado en alcance real | `tests/test_listado_contractual_exclusion_real_projects.php`; `tests/test_listado_reclassified_real_projects.php`; `tests/test_review_required_families_block_auto_apply.php`; evidencia `previews-lacp-four-projects-20260702130343`. | Queda revisión condicional cuando aparecen Telecomunicaciones y Seguridad y Control juntas. |
| `/contratos/` conserva paquetes, fuentes e intervenciones sin duplicar actividades. | Probado en casos clave | `tests/test_contratos_activity_sources_multi_group.php`; `tests/test_activity_program_sources_traceability.php`; `tests/browser/auto-definir-contratos.mjs`. | Metrolinea queda omitido para aplicación/CRUD completo por decisión del usuario. |
| `/pdc/` se genera desde Contratos y sigue pasos/fechas sin crear familias. | Probado | `tests/test_pdc_three_projects_perfect_20260702.php`; `tests/test_pdc_does_not_create_families.php`; `tests/test_pdc_modern_replaces_legacy_update.php`; `docs/qa/evidence/pdc-perfect-20260702/EVIDENCE.md`. | Sin brecha para los 3 proyectos obligatorios. |
| Administración permite mantener familias, aliases, elementos contractuales, reglas, impacto, auditoría y aprobaciones. | Probado funcionalmente | `tests/test_admin_family_catalog_crud.php`; `tests/test_admin_family_catalog_permissions.php`; `tests/browser/admin-family-catalog.mjs`; capturas `admin-family-catalog-20260702.png` y `admin-family-catalog-decisions-20260702.png`. | Las 13 decisiones reales fueron aplicadas en BD; los tests de CRUD siguen usando familias temporales. |
| Legacy directo de Listado, Contratos y PDC queda deprecado o retirado según plan. | Probado para alcance aprobado | `tests/test_legacy_absence_for_lacp_runtime.php`; `tests/test_lacp_modern_navigation.php`; `tests/test_contratos_modern_assistant_replaces_auto_define_ui.php`; `tests/test_pdc_modern_replaces_legacy_update.php`; `docs/qa/evidence/catalog-goal-audit-20260702/legacy-cleanup-readiness.md`. | Si se amplía el alcance fuera de estos módulos, requiere auditoría aparte. |
| Antes de cualquier borrado destructivo existe backup externo, restauración local y comparación de datos. | Probado y ejecutado para alcance LACP | `tests/test_lacp_backup_restore_before_cleanup.php`; `docs/qa/evidence/catalog-goal-audit-20260702/backup-restore-smoke/`; `docs/qa/evidence/catalog-goal-audit-20260702/legacy-cleanup-readiness.md`. | Producción queda fuera de este borrado local. |
| Evidencia final cubre JMC, Da Porto, un Metrolinea y Milan con capturas, recordings, resumen sanitizado, tests PHP y E2E. | Probado con Metrolinea omitido para CRUD | `docs/qa/evidence/previews-lacp-four-projects-20260702130343/EVIDENCE.md`; `recording.webm`; `trace.zip`; `summary.png`; `summary.json`; `docs/qa/evidence/pdc-perfect-20260702/EVIDENCE.md`; `tests/test_metrolinea_evidence_scope.php`. | Sin brecha abierta tras omitir Metrolinea para aplicación/CRUD completo. |

## Objetivo operativo de 3 proyectos

Estado: probado.

Evidencia principal:

- `docs/qa/evidence/pdc-perfect-20260702/EVIDENCE.md`.
- `docs/qa/evidence/pdc-perfect-20260702/goal-runner-summary.json`.
- `tests/test_pdc_three_projects_perfect_20260702.php`.

Resumen:

| Proyecto | Semana | Contratos | PDC | Faltantes | Extras | Duplicados |
|---|---:|---:|---:|---:|---:|---:|
| Optimización Aeropuerto JMC | 7 | 50 | 50 | 0 | 0 | 0 |
| Da Porto | 8 | 25 | 25 | 0 | 0 | 0 |
| Milán Campestre Torre 19 | 6 | 4 | 4 | 0 | 0 | 0 |

## Decisiones humanas aplicadas

Las 13 decisiones humanas ya no son bloqueo de cierre. Quedaron registradas en:

- `docs/qa/evidence/catalog-goal-audit-20260702/catalog-human-audit.md`.
- `docs/qa/evidence/catalog-goal-audit-20260702/human-decision-matrix-13-families.md`.
- `docs/qa/evidence/catalog-goal-audit-20260702/human-decision-approval-checklist.md`.
- `tests/test_human_decision_matrix_coverage.php`.

Estado aplicado: cero familias activas con `siempre_revision = 1`. Aseo, Telecomunicaciones, Seguridad y Control y Dotación Zonas Comunes quedan como familias operativas. Campamento, Botada, equipos y Amenidades quedan como contrato/alias según la decisión humana.

## Comandos de evidencia mínimos para revalidar

```bash
docker compose exec app php tests/test_pdc_three_projects_perfect_20260702.php
docker compose exec app php tests/test_learning_persistence_catalog_db.php
docker compose exec app php tests/test_family_catalog_operational_only.php
docker compose exec app php tests/test_review_required_families_block_auto_apply.php
docker compose exec app php tests/test_human_decision_matrix_coverage.php
docker compose exec app php tests/test_metrolinea_evidence_scope.php
docker compose exec app php tests/test_legacy_absence_for_lacp_runtime.php
docker compose exec app php tests/test_lacp_legacy_cleanup_readiness.php
docker compose exec app php tests/test_lacp_backup_restore_before_cleanup.php
docker compose exec app php tests/test_goal_close_blockers_manifest.php
docker compose exec app php tests/test_goal_close_readiness_script.php
docker compose exec app vendor/bin/phpstan analyse src public/index.php admin --memory-limit=1G
git diff --check
```

## Decision

Marcar el goal completo.

El PDC perfecto de los 3 proyectos obligatorios está probado. Metrolinea queda omitido para aplicación/CRUD completo por decisión del usuario, y la limpieza legacy local aprobada se ejecutó después de backup, restauración y comparación. No quedan bloqueos abiertos en `goal-close-blockers.json`.
