---
slug: centralize-db-architecture
status: approved
intent: clear
pending-action: write .omo/plans/centralize-db-architecture.md
approach: Zero-downtime dual-write migration. 16 global tables created alongside existing 144 per-project tables. Dual-write with Database wrapper auto-injecting project_id. Old tables kept for 8 weeks before retirement. DB backup as absolute first step.
---

# Draft: centralize-db-architecture

## Components (topology ledger)
C1 | Backup completo de BD + rollback drill probado en staging | active | .omo/evidence/c1-backup-rollback.log
C2 | Schema audit: detectar y corregir drift en 144 tablas | active | .omo/evidence/c2-schema-audit.json
C3 | Infraestructura: TableResolver + Database wrapper con project_id | active | src/Core/TableResolver.php, src/Core/Database.php
C4 | Migración de queries PHP (~100+ archivos) | active | src/, construccion/, src/Legacy/
C5 | Frontend JS: eliminar nombres de tabla en cliente | active | public/js/
C6 | Migración de datos: dual-write 144→16 tablas | active | database/migrations/
C7 | Verificación: cross-project leak tests, integridad, performance | active | .omo/evidence/c7-verification/
C8 | Cutover + retiro de tablas viejas (semana 8+) | deferred | .omo/evidence/c8-cutover.log

## Open assumptions (announced defaults)
as1 | Zero-downtime con dual-write | Dual-write: cada INSERT/UPDATE/INSERT IGNORE usa Database wrapper que escribe a tabla vieja Y global | Reversible (flag toggle) | Decisión explícita del usuario
as2 | Legacy code se actualiza, no se puentea con VIEWs | Modificar todos los scripts legacy para usar TableResolver | Reversible (revertir commit) | Los VIEWs son deuda técnica; mejor actualizar de una vez
as3 | MySQL 8 particionamiento NO requerido | Las 16 tablas globales tendrán ~9K-50K filas; índices compuestos (project_id, ...) bastan | Reversible (añadir particionamiento después si es necesario) | Escala actual no lo justifica
as4 | Caché no existe actualmente en la app | No se encontró Redis/Memcached en el código; si se añade después, incluir project_id en keys | Reversible | Confirmado por exploración del código

## Findings (cited)
f1 | src/Core/Database.php:181 líneas, método query($sql, $params) con PDO | Punto de inyección para project_id automático
f2 | src/Legacy/nueva_semana.php: usa {$db}_semanas_activas, {$db}_programa, etc. | Legacy code usa $dbName, $db como variables de prefijo
f3 | public/js/: NO construye nombres de tabla SQL directamente | JS usa URLs de servidor — el riesgo en frontend es menor de lo estimado
f4 | 8 proyectos Construccion activos + 1 Pre-Construccion = 9 proyectos × 16 tablas = 144 tablas | Volumen exacto de migración
f5 | admin/src/Models/Project.php: getProjectTableQueries() ya centraliza creación de tablas | Base para TableResolver
f6 | database/patches/: 43 archivos SQL con referencias a prefijos de proyecto | Necesitan auditoría y reescritura

## Decisions (with rationale)
d1 | Database wrapper inyecta project_id automáticamente | Mitiga CR-1 (data leak): ningún developer puede olvidar el WHERE
d2 | Dual-write por 8 semanas antes de dropear tablas viejas | Mitiga CR-3 (rollback): siempre hay camino de vuelta
d3 | Schema audit PREVIO a cualquier migración de datos | Mitiga CR-2 (schema drift): corregir diferencias antes de merge
d4 | Playwright E2E cross-project leak test como CI gate | Mitiga CR-1: prueba automatizada de que proyecto A nunca ve datos de B
d5 | MySQL General Query Log en staging para detectar queries sin project_id | Mitiga HR-1 (coverage): captura todo lo que el static analysis no encuentra
d6 | Transaction-per-test rollback para aislamiento en PHPUnit | Mitiga HR-3 (test pollution)

## Scope IN
- 16 tablas globales con project_id discriminator
- TableResolver centralizado para resolución de nombres
- Database wrapper con project_id inyectado
- Migración de datos con dual-write (144→16)
- Actualización de ~100+ archivos PHP (src/, construccion/, src/Legacy/)
- Actualización de 43 patches SQL
- Actualización de seed files
- Cross-project leak tests
- Performance benchmarks pre/post migración
- Rollback drill en staging

## Scope OUT (Must NOT have)
- NO dropear tablas viejas hasta verificación completa (8 semanas mínimo)
- NO modificar schema de tablas viejas durante la migración
- NO permitir queries sin project_id en el nuevo código (CI gate)
- NO permitir construcción de nombres de tabla en JS
- NO modificar lógica de negocio (solo cambia cómo se accede a los datos)
- NO afectar otros entornos (staging, producción) sin verificación

## Open questions
q1 | ¿Duración exacta del dual-write? → Default: 8 semanas (el usuario dijo "cero downtime", esto es el safety net)
q2 | ¿Eliminar proyectos inactivos antes de migrar? → Default: sí, solo migrar activos (9 proyectos)

## Approval gate
status: approved
approved by: user ("Elabora el plan...")
date: 2026-06-25
