---
capa: fuente
tipo: plan
estado: vigente
fecha: 2026-07-22
areas: [pdc]
fuente: docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md
resumen: La fundación técnica (isla React en lps-aia + endpoint de contexto) está mergeada a main. El usuario corrigió el rumbo: antes de seguir codificando, fijar el…
---

# Roadmap PDC v2 — producto en 2 submódulos y fases de desarrollo

## Contexto

La fundación técnica (isla React en lps-aia + endpoint de contexto) está mergeada a `main`. El usuario corrigió el rumbo: antes de seguir codificando, fijar el mapa completo. Decisiones del usuario que gobiernan este roadmap:

1. **La app tiene solo 2 submódulos de UI:**
   - **Ensamble del Plan de Compras** — construir el plan: importar presupuesto → maestro de insumos → paquetes de contratación → matching con cronograma → plan con fechas.
   - **Seguimiento al Plan de Compras** — operar el plan: avance por pasos de contratación, alertas/semáforos, re-matching automático, responsables y gestión, y **Torre de Control (BI)**.
2. **Alcance del desarrollo:** importador, maestro de insumos, paquetes, matching contra el **programa-general de la última semana activa**, plan final con fechas, y retiro del PDC viejo.

## Hechos del modelo de datos LPS (verificados en lps-aia — vinculantes para el diseño)

- **Última semana activa** = `MAX(Semana)` de `semanas_activas` (patrón existente, p.ej. `PdcController.php:23`). No hay flag "activa".
- **El programa general se versiona por semana**: `programa` es la tabla viva; **`programa_consolidado`** es el snapshot semanal (`WHERE project_id=? AND Semana=?`), con `Fecha_Inicio`/`Fecha_Fin`, `Titulo` (nodo/hoja), `codigo_actividad` e **`unique_id`** — la identidad estable de la actividad entre semanas/reprogramaciones (FKs del contrato `20260630_global_tables_contract.sql`). El matching de v2 es contra `programa_consolidado` de la semana MAX y persiste `unique_id` como amarre.
- **Fechas del PDC actual**: programación **hacia atrás** desde la `Fecha_Inicio` de la actividad ancla, restando duraciones por paso (`calculateProcessDates`, `SemiAutoService.php:3028`). Duraciones vigentes en el catálogo global **`general_dias_procesos_contratacion`** (`tipoPaquete`, `paqueteContratacion`, 7 columnas de días; seeds ESTÁNDAR MO/S/SI/OC en `20260706_duraciones_estandar_contratacion.sql`). **No usar** `general_pdc_plantillas` (dropeada en `20260612_pdc_consolidado_full.sql`).
- **Convención de tablas nuevas**: operativas = `project_id int NOT NULL` + índice compuesto liderado por `project_id` + `utf8mb4_unicode_ci`; catálogos = prefijo `general_` sin `project_id`; migraciones SQL (DDL) / PHP con dry-run→`--apply` (backfills); gates `test_global_table_safety.php` + reconciliación.
- El PDC viejo matchea por **familia/regex** (`ActivityMatcher` + `general_pdc_activity_rules`) — modelo que v2 elimina; v2 amarra paquete↔actividad por selección/código y persiste `unique_id`.

## Fases de desarrollo (cada una con su propio spec/plan detallado + ciclo subagent-driven, como la fundación)

### Submódulo A — Ensamble del Plan de Compras
- **Fase A1 — Importador de presupuesto:** migraciones de tablas de presupuesto/APU-insumo (por `project_id`); endpoint upload + parsing PhpSpreadsheet por chunks (memoria SiteGround); validación por fila; import transaccional versionado; vista de import en la SPA.
- **Fase A1.5 — Visor del presupuesto** *(añadida 2026-07-23)*: vista de solo lectura del presupuesto importado — árbol jerárquico expandible (capítulos → subcapítulos → grupos → actividades → insumos de APU) con selector de versión; endpoint `GET /plan-compras/api/presupuesto/arbol`. Sin migraciones (lee las tablas de A1).
- **Fase A1.6 — Comparativo de versiones** *(añadida 2026-07-23; spec `docs/superpowers/specs/2026-07-23-a16-comparativo-versiones-design.md`)*: incorpora (mejorándolo) el comparativo de la app externa de Tomás — qué cambió entre dos versiones del presupuesto, por actividad (jerárquico) y por insumo (Pareto), clasificando sobrecostos vs ahorros; endpoint `GET /plan-compras/api/presupuesto/comparar`. Sin migraciones (lee las tablas de A1). Independiente: puede correr en paralelo a A3. *(Implementada.)*
- **Fase A1.7 — Versionamiento inteligente del importador** *(añadida 2026-07-23; spec `docs/superpowers/specs/2026-07-23-a17-versionamiento-inteligente-design.md`)*: refuerzo de A1 surgido del cargue real DAPORTO (columna VERSION del Excel vacía → versiones sin identificador). Auto-numeración secuencial por proyecto (Versión N) + fecha de cargue en el identificador; anti-duplicado por **hash del contenido** canónico (no del binario) vs la versión activa → no crea versiones idénticas; auto-comparativo tras cargar (reusa A1.6). Migración aditiva `version_numero`/`contenido_hash` en `pdc_presupuesto_versiones`. Depende de A1, reusa A1.6. *(Implementada.)*
- **Fase A2 — Maestro de insumos:** tabla **global** (catálogo multiproyecto) + normalización/dedupe de insumos importados contra el maestro con confirmación humana; vista de administración. Decisiones 2026-07-23: auto-match exacto + cola de pendientes sobre insumos únicos consolidados, con creación masiva para el cold start (maestro arranca vacío); RBAC nueva `lps.pdc.maestro` (A y D). *(Implementada.)*
- **Fase A2.5 — Importador del maestro SINCO** *(añadida 2026-07-23; spec `docs/superpowers/specs/2026-07-23-a25-importador-maestro-sinco-design.md`)*: el maestro deja de nacer vacío. Importa el `.xlsx` autoritativo de AIA (3.088 insumos de SINCO) hacia `general_maestro_insumos` extendido con `codigo_sinco` (clave), `agrupacion` (73 familias), `tipo_recurso`, `valor_unitario`; upsert idempotente por `codigo_sinco`; RBAC `lps.pdc.maestro`. El maestro pasa a ser autoritativo; la cola de pendientes de A2 queda como excepción.
- **Fase A3 — Paquetes de contratación + asistente de empaquetamiento** *(spec `docs/superpowers/specs/2026-07-23-a3-paquetes-contratacion-design.md`, revisado tras destilar la app de Tomás)*: `general_paquetes_contratacion` (catálogo global **sembrado con 188 paquetes reales de AIA**) + `pdc_insumo_paquete` (asignación por `project_id`, un-insumo-un-destino: paquete u **omitido**, atada a la versión activa). Tipos de negociación alineados a los 3 reales de la app (Sum+Inst / Suministro / Mano de Obra) sobre el enum de 4 del dominio. **Motor cross-proyecto sin tabla nueva** (agrega sobre `pdc_insumo_paquete`): exacto por insumo, similitud por tokens, Agrupación SINCO de respaldo, y filtro por **tipo de recurso**; siempre con confirmación humana. Dos modos: masivo (grilla) y **asistente paso a paso** (wizard estilo app de Tomás, orden Pareto). Indicador **100% asignado** (asignados + omitidos); RBAC `lps.paquetes_contratacion.*` (ya en catálogo). *(Implementada.)*
- **Fase A3.1 — Sembrado inteligente de la 1ª iteración:** motor en cascada (overrides IA → exacta cross-proyecto → reglas de dominio → tokens → indirectos → agrupación SINCO) con preview en la grilla antes de escribir; botón «Sembrar 1ª iteración» y resumen por fuente. Aplicado a DAPORTO v292: **325 asignaciones en 84 paquetes, 82,1 % de cobertura**. *(Implementada.)*
- **Fase A3.2 — Modalidad de contratación:** `general_paquetes_contratacion.modalidad_contratacion` (enum `contrato` | `orden_compra` | `consumo_directo` | `no_contratable`, default `contrato` ⇒ cero regresión), **ortogonal** al tipo de negociación. Define qué entra al plan de fechas de A4: `contrato` proceso completo; `orden_compra` solo la **primera entrega** (commodities que rotan de proveedor — el histórico por proveedor y cuantía es del submódulo B); `consumo_directo` y `no_contratable` no generan proceso. Parte el bucket «Indirectos» en Nómina / Imprevistos / Ferretería. *(Implementada.)*
- **Fase A3.3 — Motor auditable y generalizable:** procedencia por asignación (origen/confianza/evidencia/confirmado) + `pdc_correcciones_motor` y tres indicadores (conteo · valor · tasa de acierto); overrides destilados de 158 a 8 midiendo el motor con la lista apagada; desempate por tipo de recurso (el material no depende de la actividad) y `pdc_insumo_actividades` con TODAS las actividades por insumo; veto de doble conteo (material en paquete a todo costo) con excepción `admite_materiales`; cola larga cerrada (71 → 1 insumo sin destino); auto-asignación acotada (confianza alta y < $20M) con preview; puente `duracion_ref` con el catálogo legacy; permiso `lps.paquetes_contratacion.reglas`. *(Implementada.)*
- **Fase A4 — Matching con cronograma + fechas (cierra el Ensamble):** amarre paquete↔actividad de `programa_consolidado` (semana = MAX de `semanas_activas`) persistiendo `unique_id`; ancla `Fecha_Inicio`; **programación hacia atrás** con duraciones de `general_dias_procesos_contratacion` (pasos configurables por proyecto — no hardcodear variantes Licify/Aprobación cliente); responsable por paquete; vista "Plan de Compras" resultante.
  - **Pendiente detectado en A3.2:** el puente con `general_dias_procesos_contratacion` casa por nombre (`loadPdcDurationRow()`) y los paquetes de A3 llevan prefijo — `Suministro CONCRETO` vs el legacy `CONCRETO` —, así que hoy no encuentra fila. Resolverlo antes de derivar fechas. La **modalidad** (A3.2) decide además qué paquetes reciben proceso completo, cuáles solo la primera entrega y cuáles ninguno.
- **Fase A4.1 — Pasos configurables por proyecto:** cierra el último requisito escrito de A4. Catálogo global `general_pasos_contratacion` (9 pasos: los 7 de siempre + `licify` + `aprobacion_cliente`) + `pdc_proyecto_pasos` por obra (orden, alias, días fijos); `pdc_plan_paso` pasa a identificarse por `paso_id` en vez de por posición — **contrato con B1**, que colgará de esas filas la fecha real de cada paso. Las variantes Licify y Aprobación del cliente quedan disponibles sin tocar código ni el catálogo legacy compartido. Cero regresión: obra sin configurar = los siete de siempre, demostrado contra las 11 filas y 77 pasos de DAPORTO. *(Implementada.)*
  - **Pendientes registrados, fuera del alcance de A4.1** (decisión del grilleo 2026-07-28): listas de pasos distintas **por modalidad o tipo de negociación** dentro de una misma obra · **copiar** la configuración de una obra a otra · **historial de versiones** de la configuración · **editar las duraciones del catálogo legacy** desde la pantalla de pasos. Ninguno bloquea B1.

### Submódulo B — Seguimiento al Plan de Compras
- **Fase B1 — Avance y gestión:** fechas reales vs programadas por paso de contratación por paquete; edición con RBAC (`lps.pdc.editar`); responsables y filtros.
- **Fase B2 — Alertas y re-matching:** semáforos (al día / próximo a vencer / vencido) contra fechas derivadas; al avanzar la semana activa o reprogramarse el programa, recalcular fechas vía `unique_id` y mostrar el delta.
- **Fase B3 — Torre de Control (BI):** integración con la Torre de Control existente de lps-aia (`/bi/control-tower`): indicadores del plan de compras (cobertura de asignación, paquetes vencidos/en riesgo, avance de contratación). Alcance exacto a especificar en su brainstorming.

### Cierre
- **Fase C1 — Retiro del PDC viejo:** apagado/migración del modelo de "familias" (`/pdc`, `/api/pdc/*`, `OperationalFamilyPolicy`, vista Handsontable) cuando A+B estén validados en producción. Incluye decisión sobre datos históricos del `pdc` v1.

Dependencias: A1→A2→A2.5→A3→A4→B1→B2; **A1.6 depende solo de A1** (comparativo, paralelo a A2/A3); B3 tras B1; C1 al final. La UI de la SPA se organiza desde A1 con la navegación de los 2 submódulos (Ensamble | Seguimiento).

## Capa de base de datos MySQL (transversal a todas las fases — de primera clase, no implícita)

- **Esquema por fase:** cada fase que persiste datos entrega sus **migraciones** en `lps-aia/database/migrations/` — DDL en `.sql` (tablas nuevas: `project_id int NOT NULL`, índice compuesto liderado por `project_id`, `utf8mb4_unicode_ci`, FKs a `programa(project_id, unique_id)` donde aplique) y `.php` con dry-run→`--apply` para backfills/transformaciones. Catálogos compartidos = `general_*` sin `project_id`. Registro en el resolver (`Database.php`/`TableResolver.php`) si la tabla participa del rewrite por prefijo.
- Tablas previstas: **A1** presupuesto importado (cabecera de versión de import + detalle actividad/APU-insumo); **A2** `general_maestro_insumos` (catálogo global) + tabla puente insumo-presupuesto↔maestro; **A3** paquetes + asignaciones insumo↔paquete; **A4/B1** fechas por paso y avance por paquete (programadas/reales, responsable). El diseño fino de columnas vive en el spec de cada fase.
- **Verificación de BD en cada fase (sobre el MySQL 8 real del Docker de lps-aia, nunca mocks):**
  1. Aplicar migraciones en el contenedor (`docker compose exec app php database/migrations/... --apply` / ejecutar el `.sql`) y verificar esquema resultante (`SHOW CREATE TABLE`).
  2. Tests PHP autoejecutables que leen/escriben contra la BD real: asserts de integridad (aislamiento por `project_id`, unicidad, FKs, transaccionalidad del import — rollback ante fila inválida).
  3. Gates de arquitectura de datos existentes: `tests/test_global_table_safety.php` y `tests/test_global_table_reconciliation.php` deben seguir en verde.
  4. Seeds/fixtures de prueba por fase para el e2e (proyecto Da Porto local).
- **Reglas duras:** PDO prepared statements vía `Database::queryWithProject`; ningún `TRUNCATE`/DDL runtime sobre tablas globales; todo apply destructivo con backup verificable previo (política lps-aia).

## Ejecución de este plan (al aprobar)

1. Versionar este roadmap en `plan-de-compras/docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md` y actualizar `CLAUDE.md` (sección breve: producto en 2 submódulos + orden de fases + hechos del modelo LPS + capa MySQL y sus gates) — commit.
2. Iniciar el ciclo de la **Fase A1** (brainstorming corto de decisiones abiertas del importador → spec → plan detallado writing-plans → ejecución subagent-driven con los mismos gates de la fundación).

## Verificación

- El roadmap commiteado y el CLAUDE.md actualizado son el entregable de este paso.
- La verificación de cada fase vive en su plan detallado, siempre con las dos patas: **aplicación** (Vitest, tests PHP autoejecutables, PHPStan, Playwright e2e) y **base de datos** (migraciones aplicadas en Docker + asserts de integridad sobre MySQL real + gates `test_global_table_safety`/`reconciliation`).
