---
capa: fuente
tipo: reporte
estado: vigente
fecha: 2026-08-20
areas: [proceso]
fuente: docs/superpowers/reports/2026-08-20-auditoria-estado-specs.md
resumen: "Auditoría del estado real de las 61 specs vigentes contra el código: 44 ejecutadas, 16 parciales, 1 pendiente; pendientes desglosados con el plan que los cierra"
---

# Auditoría de estado real de las specs — 2026-08-20

- Alcance: las 61 specs vigentes de `docs/superpowers/specs/`. Las 12 de
  `docs/archive/superpowers/specs/` están cerradas por definición del archivo y no se reauditaron.
- Método: 7 pasadas en paralelo, una por lote. La línea «Estado:» interna de cada spec se tomó como
  hipótesis y se confirmó la afirmación clave contra el árbol: rutas, clases, migraciones, tests,
  goals con `## Cierre`, `docs/design-system/closeout-evidence.json` y `TASKS.md`. Verificación
  dirigida, no exhaustiva: existencia y 1–2 señales de contenido por spec.
- Regla aplicada (medida en [[TASKS]]): las casillas de los planes no miden nada — 0 de 435
  marcadas con trabajo en producción — así que **nada se clasificó por checkbox**.

**Corte: 44 ejecutadas · 16 parciales · 1 pendiente · 0 derogadas · 12 cerradas (archivadas).**

## Ejecutadas (44)

Objetivo implementado con evidencia en el código. Una línea de evidencia por spec.

| Spec | Evidencia |
|---|---|
| 2026-07-22 lab-colapsado-primitiva | `public/css/design-system/components/navigation.css:135-426` (selectores `collapsed` promovidos); `shell-navigation.php:15` |
| 2026-07-23 a16-comparativo-versiones | `PresupuestoImportService.php:830` `comparar()`; ruta en `public/index.php:164`; `pdc-app/src/lib/comparativo.ts` |
| 2026-07-23 a17-versionamiento-inteligente | `database/migrations/20260723_pdc_v2_versionamiento_inteligente.sql`; plan cerrado |
| 2026-07-23 a25-importador-maestro-sinco | `MaestroSincoParser.php` + `MaestroSincoImportService.php`; migración `20260723_pdc_v2_maestro_sinco_cols.sql` |
| 2026-07-23 a3-paquetes-contratacion | `PaquetesService.php`; migración + seed de 188 paquetes; plan «EJECUTADO con los deltas del spec revisado» |
| 2026-07-28 responsable-usuario-proyecto | `PlanFechasService.php:1912,1950`; el bug del primer plan lo reparó `2026-07-28-pdc-responsable-usuario` |
| 2026-07-29 a41-diferidos-configuracion-pasos | `PasosContratacionService.php:278-355` + historial; el diferido de modalidad se archivó con motivo, no es deuda |
| 2026-07-29 ayuda-in-app-pdc | `pdc-app/src/lib/ayuda.ts`, `BotonAyuda.tsx`, `Recorrido.tsx` |
| 2026-07-29 b2-semaforos-lookahead | `SeguimientoService.php:57,388`; ruta `seguimiento/vencimientos`; `Seguimiento.tsx` |
| 2026-07-29 b3-torre-control-pdc | `BiControlTowerApiController.php:195` alimentado por PDC v2; `vencimientosAgregados()`. La curva de desembolsos quedó fuera de B3 a propósito |
| 2026-07-29 c1-retiro-pdc-viejo | Cero rutas `/pdc` v1 en `public/index.php`; 18 tablas retiradas con respaldo (2026-08-04) |
| 2026-07-29 equipo-alquilado-comprado | Migraciones `20260729_pdc_v2_equipo_*`; validación con salida real en `goals/pdc-preparar-b1/evidence/`. Gastos generales de Tomás → sesión propia (ya anotado en `estado-olas.md:177-181`) |
| 2026-07-29 flujo-caja-desembolsos | `FlujoCajaService.php`; `test_pdc_v2_flujo_caja.php` (31 asserts); verificado en pantalla en Da Porto |
| 2026-07-29 impacto-reimport-presupuesto | Ruta `presupuesto/comparar`; `ImportarPresupuesto.tsx` con `pdc-import-impacto` |
| 2026-07-29 pdc-b1-seguimiento | `SeguimientoService.php`; `test_pdc_v2_seguimiento.php`; seis SHAs en la spec |
| 2026-07-29 rematching-reprogramacion | `PlanFechasService.php` `simularReprogramacion()`/`aplicarReprogramacion()`; `test_pdc_v2_reprogramacion.php` |
| 2026-07-29 subpaquetes-obra | `SubpaquetesService.php`; tests de 30 asserts + volumen |
| 2026-07-29 tamiz-presupuesto | `impactoDeReimportar()` + `avisosDelPresupuesto()`; `tamiz.ts`; 26 usos en `VisorPresupuesto.tsx` |
| 2026-07-29 unificacion-repos | `pdc-app/` fusionado por subtree; `docs/pdc-v2.md` |
| 2026-07-30 dev-door | `src/Core/DevDoor.php`; `/dev/entrar` condicional; `test_dev_door_guard.php`. El 404 anotado en la spec no reaparece en ningún documento posterior; verificación atómica en navegador si se quiere cerrar del todo |
| 2026-07-30 shell-layout-design-system | `shell_sidebar.php` en 13 vistas; goal cerrado delegando en 3 hijos, los 3 «HECHO» |
| 2026-08-03 admin-dev-door | `admin/src/Controllers/DevDoorController.php`; `test_admin_dev_door_guard.php`. La etiqueta «propuesta» de la spec quedó caducada frente al código |
| 2026-08-03 arquitectura-en-la-wiki | `memoria/arquitectura/` (23 páginas), `memoria/flujos/`, `scripts/wiki-arquitectura.mjs`; `docs/ROUTES.md` retirado |
| 2026-08-03 lint-wiki-memoria | Plan archivado; `memoria/paginas.base` |
| 2026-08-03 saneamiento-deudas-usabilidad | `bi-kpi-copy.spec.mjs:2` importa `BASE_URL` del fixture; aserto `'87 %'` |
| 2026-08-04 biblia-de-flujos | `docs/flujos/` (11 documentos); las 5 tandas con `## Cierre` «HECHO». Hallazgos sin arreglar en `docs/EXPERIMENTS.md`, fuera de alcance declarado |
| 2026-08-04 cierre-dark-mode-campana-decisiones | Tabla de cierre 2026-08-07: 36 ejecutadas · 2 retiradas · 0 pendientes |
| 2026-08-04 cierre-version-1-1-0-design-system | `version.json` = 1.1.0 stable; goal cerrado con 8/8 y 39→32 excepciones re-vencidas a 1.2.0 |
| 2026-08-06 adopcion-logo-construccion | `public/img/brand/`, `head_brand.php`; goal cerrado 2026-08-19, 4 superficies verificadas |
| 2026-08-06 cierre-hallazgos-seguridad-biblia | Los 4 hallazgos `cerrado` con SHA en `docs/EXPERIMENTS.md:40-65`; `legacy_require_csrf` en los 6 controladores |
| 2026-08-06 pdc-filtros-y-buscadores | `ListaBuscable/Selector/FiltroLista/BarraFiltros` + libs con tests; AG Grid con filtros en 7 páginas |
| 2026-08-10 runner-tests-php | `scripts/run-php-tests.php` en `composer.json` y en los 2 jobs del CI |
| 2026-08-11 buttons-important-leyenda | `grep -c '!important' buttons.css` = 138, cifra exacta del cierre |
| 2026-08-11 contadores-cero | `OCULTAR_CONTADORES_EN_CERO = true` activo en `hot.js:31`, consumido en `:2987` |
| 2026-08-11 contrato-estados-modulo-fantasma | `state-semantics.json` sin `programa-general-actualizar` |
| 2026-08-11 semana-fija-visual | Goal cerrado 2026-08-19; `POST /context/week`; 2/2 goldens; mutación probada y revertida |
| 2026-08-12 espejo-produccion-a-pruebas | `## Cierre` en la propia spec: 3 bases migradas con conteos; smoke 1.1.2 Da Porto limpio |
| 2026-08-13 ocultar-control-tower | `BiPreviewAccessPolicy.php` consumido en componente, vista y API. Los 2 casos rotos de `bi_control_tower_access.spec.mjs` son deuda previa con dueño propio |
| 2026-08-14 fixture-ci-semanal-roles | Workflow invoca `gate-receipt.mjs semanal-roles-phases`; gate `passed`, 15 casos. Los 4 `test.skip` son el candado que la spec exige conservar |
| 2026-08-14 shell-menu-flotante-responsive | `shell-drawer.js` (pieza canónica); MO-F2a-2b cerrada 2026-08-14. Migrar `design_system_lab.js` quedó como deuda declarada en la propia spec |
| 2026-08-19 bug-coloreado-severidad | Goal: condición de hecho «Cumplida», `publicar.sh --solo-verificar` verde sobre `9e534129` |
| 2026-08-19 ds-f0-auditoria-total | `docs/design-system/auditoria/` (censo 257 rutas, 68 hallazgos); goal cerrado `3fd1af09` |
| 2026-08-19 ds-f1a-estado | `ds-f1a-escala-estado.{json,md}` + test; publicado en `main` (`4a152a54`) |
| 2026-08-19 publicar-sh-invariante-de-montaje | `scripts/publicar.sh:41-78`: compara el mount de `app` contra `pwd -P` y deniega con remedio |

## Parciales (16) — qué falta y el plan que lo cierra

| Spec | Qué falta | → Plan |
|---|---|---|
| 2026-07-21 stack-plan-de-compras | El stack decidido está ejecutado; la decisión #3 (repo separado `plan-de-compras`) quedó derogada por la unificación del 2026-07-29. Brecha solo documental | Ninguno; el conocimiento vivo está en `docs/pdc-v2.md` |
| 2026-07-29 cierre-prelanzamiento-pdc | Punto 6 sin contenido del piloto de Tomás; H1/H2 diferidos (e2e inestables por dato real, trinquete sin referencia) | Ola 2 según `estado-olas.md:215`; fixtures/sandbox 990100 (`hallazgos-piloto.md:51,66`) |
| 2026-07-29 despliegue-pdc-v2-produccion | Solo `prueba-lps` (`9e77dd2`); producción real ~1.255 commits atrás. Falta humo autenticado del PDC v2 en `prueba-lps` | CP-F-E de `plans/2026-08-11-cierre-hasta-produccion.md` + `docs/siteground-deploy-routine.md`; autorización explícita siempre |
| 2026-07-31 ui-audit-and-repair-plan | Plan sin ningún cierre; `password-forgot` migrada pero CNP/CNC/CIC e Indicadores sin evidencia de `aia-*` en plantilla | `impeccable:audit` puntual sobre esas superficies antes de reabrir; si se retoma, goal propio con `writing-plans` |
| 2026-08-01 ui-audit-core-lps-ops | `profesionales` migrada; `/indicadores` sin evidencia de tarjetas KPI `aia-*` pese a estar en dos planes | El mismo audit puntual de arriba; cruzar antes con las tandas biblia (posible cierre no documentado) |
| 2026-08-03 cierre-dark-mode | Fases 0–3 y campaña de decisiones cerradas; la condición «cero hallazgos en todo el árbol» (fase 6, ~2.600 hallazgos) nunca se verificó | **No reabrir**: sustituida por decisión del 2026-08-18 — programa DS-F0..F3 ([[docs/superpowers/specs/2026-08-19-ds-f0-auditoria-total-design]]) |
| 2026-08-03 reparto-trabajo-pendiente | 7 de 8 líneas ejecutadas; la línea E (26 hallazgos altos/medios de usabilidad) tiene ejecución parcial sin goal ni cierre | Releer `inventario-usabilidad.md` §7 hallazgo por hallazgo y escribir el goal con cierre (mismo tratamiento que los 9 goals-plantilla del 2026-08-19) |
| 2026-08-07 f2a-piloto-movil-programacion | F2a-2b cerrada en código, pero los manifiestos siguen `"layouts": ["desktop"]`: falta el escenario `390x844` con golden (condición de hecho #2) | Retomar con `plans/2026-08-13-f2a-2b-2-extraccion-umbral-y-montaje.md` o abrirlo dentro de MO-F2b |
| 2026-08-07 reapertura-movil-y-tema-claro | F1–F2a cerradas; F2b (13 módulos), F3 (tema claro) y F4 (matriz de gates, absorbida por DS-F3) pendientes | F2b: plan por escribir con el coste medido del piloto; F3: spec propia por escribir; F4: dentro de DS-F3 |
| 2026-08-10 programa-cierre-pendientes | Frentes 0–2 con avance real; frentes 3–5 sin evidencia; frente 1b (reconstruir 15 gates) sin plan localizado | Los frentes restantes dependen de F2b; el 1b quedó absorbido por DS-F3 — verificar al arrancarlo |
| 2026-08-11 plan-cierre-hasta-produccion | 8/9 gates `passed`; F-AB pausado (runtime-budgets al CI); F-E sin ejecutar | F-AB: `plans/2026-08-19-runtime-budgets-al-ci.md` (vigente); F-E: autorización explícita + rutina SiteGround |
| 2026-08-11 vocabulario-estados-cascada | La resta de desviación en Intermedia se ejecutó (35→29); la unificación real de los tres vocabularios sigue sin decidirse | Replanteo pedido por el usuario (`TASKS.md` «Replanteo antes de ejecutar»), aún sin plan escrito |
| 2026-08-18 espacio-cuenta-siteground | Frentes A y B ejecutados; C (clon shallow en `prueba-lps`) y D (basura en producción) son cambios de servidor sin verificar, y el gate de cierre no corrió | Tareas 1, 4 y 5 de `plans/2026-08-18-espacio-cuenta-siteground.md` |
| 2026-08-18 wiki-v2-visual | Las 6 tandas cerradas; quedan los plugins de comunidad (decisión del usuario pendiente) y el snippet CSS de severidad sin verificar en `.obsidian/` | Fase 4 puntos 2–3 de `plans/2026-08-18-wiki-v2-visual.md`, tras decisión de Felipe sobre plugins |
| 2026-08-19 estados-severidad-contrato | `severity-rail` implementado e Intermedia completa, pero el frente **no publicó** y su cierre se retiró: choca con `ds-f1a-estado` (3 vs 4 niveles). Felipe decidió el 2026-08-19 que manda el contrato del hermano | Reescribir la spec bajo el vocabulario de 3 niveles, coordinada con `ds-f1a-estado`; fondo de PS y `r0` como frentes propios |
| 2026-08-19 runtime-budgets-al-ci | `cssGzipBytes` ya pasa en CI (126.885 B / 198.781); `initializationMs` rojo (639,4 / 301,9 ms) escalado como D-11; `closeout-evidence.json` sigue `blocked` sin actualizar; `test_bi_programa_general_chart_values` corta el pipeline con `baseline-drift` | D-11 espera a Felipe; Fase 3 del plan vigente cierra la procedencia; el test BI es frente propio (ya relatado en `TASKS.md`, linea-base-contractual) |

## Pendiente (1)

| Spec | Situación | → Plan |
|---|---|---|
| 2026-08-19 organizar-la-casa | Sin rastro de ejecución: no hay `goals/organizar-la-casa/`, los vistos siguen en `.claude/vistos/` (7 archivos), las plantillas vacías de `decisiones/` sin borrar, y `docs/coordinacion-sesiones.md` es el documento del 2026-08-10 con la ruta caducada del Crucial X6 | Ejecutar la spec completa (§1 mudanza de vistos, §2 reescritura de coordinación, §3 limpieza), inline y con worktree propio como ella misma pide |

## Cerradas por archivo (12)

`a1-importador-presupuesto`, `semanas-sidebar`, `control-tower-shell-dark`, `a4-plan-fechas`,
`a41-pasos-configurables`, `paleta-estado-oscura`, `retiro-modo-legacy`,
`obsidian-memoria-proyecto`, `wiki-veracidad-y-grafo`, `conceptos-design-system-en-la-wiki`,
`marca-carril-visible`, `phpunit-incremental` — todas con `estado: cerrado` en frontmatter y
movidas a `docs/archive/superpowers/` porque el trabajo terminó y nadie las cita.

## Decisiones de esta auditoría, anotadas

- **El frontmatter de las specs no se tocó.** Su vocabulario (`vigente`/`cerrado`/`derogada`) es
  del esquema wiki v2 y mide vigencia documental, no ejecución; meterle `ejecutada`/`parcial`
  rompería el vocabulario cerrado del lint. El estado de ejecución vive aquí y en el resumen de
  [[IMPLEMENTATION_PLAN_INVENTORY]].
- **`CHANGELOG.md` no lleva entrada**: una auditoría no es un cambio de producto, y ese archivo
  registra solo cambios liberados o por liberar (su propia nota del 2026-08-19).
- Observados de paso, sin acción aquí: la redacción residual de `docs/EXPERIMENTS.md:115-129`
  (análisis previo conviviendo con la fila ya cerrada), y el patrón de dos frentes sobre la misma
  superficie sin revisar contención (`ds-f1a-estados-severidad`) — lección para el frente
  `organizar-la-casa`.

## Acta — lote G sin registro de cierre, verificado a posteriori (2026-08-20)

La auditoría corrió con 7 agentes en paralelo. El lote G (las 8 specs del 2026-08-18/19) entregó
sus hallazgos a la consolidación, pero **su proceso murió sin dejar acta de terminación** — la
notificación llegó como `stopped` al reanudar la sesión. Este apartado deja constancia de la
verificación posterior, para que la ausencia de acta no se confunda con ausencia de cobertura:

- Las 8 specs del lote están en este informe **con estado y evidencia propia** (no derivada):
  `wiki-v2-visual`, `bug-coloreado-severidad`, `ds-f0-auditoria-total`, `ds-f1a-estado`,
  `estados-severidad-contrato`, `organizar-la-casa`, `publicar-sh-invariante-de-montaje` y
  `runtime-budgets-al-ci`. La evidencia incluye datos que solo salen de comprobación real
  (SHAs de goals, cifras de `closeout-evidence.json`, líneas de `publicar.sh`).
- Las dos que este corte dejó mal paradas se cerraron el mismo día con frente propio:
  [[docs/superpowers/reports/2026-08-20-cierre-pendientes-auditoria]].
- **No se relanzó el agente**: repetiría lecturas sobre un resultado ya consolidado y publicado.

Verificado el 2026-08-20 contra el informe en `main` (`grep` de las 8 specs sobre este archivo,
todas presentes en sus secciones).
