# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Estado actual

Rama en curso: **Fases A1, A1.5, A2 y A2.5 implementadas** — importador de presupuesto, visor en árbol, maestro de insumos global (auto-match + cola de pendientes) y **importador del maestro SINCO** (siembra `general_maestro_insumos` con 3.088 insumos: código, agrupación, tipo de recurso, valor), todo bajo la navegación Ensamble | Seguimiento. Verificado con Vitest, tests PHP autoejecutables y e2e Playwright. En detalle: importador de presupuesto (preview→confirmar, versionado con única activa, todo-o-nada) sobre 3 tablas `pdc_presupuesto_*` en lps-aia con RBAC `lps.pdc.importar`, visor del presupuesto en árbol jerárquico con selector de versión (`#/ensamble/presupuesto`), y maestro de insumos global (`#/ensamble/maestro`) con RBAC `lps.pdc.maestro`: cola de vínculos pendientes por versión con selección múltiple y creación masiva (cold start), vinculación individual con sugerencias por similitud, y catálogo único de insumos (`general_maestro_insumos`) con búsqueda — auto-match idempotente en cada re-import. Follow-ups del review final A2 aplicados: tolerancia a errno 1062 (carrera/colisión de prefijo → vincula al existente), upsert de vínculos en lotes multi-fila, comodines LIKE escapados, y retiro/reactivación de insumos del catálogo (`activo=0` con reversión global del auto-match, auditoría `actualizado_por`/`updated_at` y UI en el catálogo). Verificado con Vitest (28 tests), tests PHP autoejecutables (RBAC, parser, flujo BD, árbol, maestro, import SINCO) y e2e Playwright (import, fundación, visor, maestro e import del maestro SINCO).

El desarrollo sigue el **roadmap maestro** `docs/superpowers/plans/2026-07-22-roadmap-pdc-v2.md` (fases A1→A4, B1→B3, C1); cada fase recibe su propio spec y plan detallado antes de ejecutarse.

## Producto: 2 submódulos de UI

La app se organiza en exactamente **dos submódulos** (decisión 2026-07-22):

1. **Ensamble del Plan de Compras** — construir el plan: importar presupuesto → maestro de insumos → paquetes de contratación → matching con cronograma → plan con fechas. (Fases A1–A4.)
2. **Seguimiento al Plan de Compras** — operar el plan: avance por pasos de contratación (fechas reales vs programadas), alertas/semáforos, re-matching automático al reprogramar, responsables y gestión, y Torre de Control (BI). (Fases B1–B3.)

No existe una vista de Pareto en este desarrollo.

## Hechos del modelo de datos LPS (verificados en lps-aia — vinculantes)

- **Última semana activa** = `MAX(Semana)` de `semanas_activas` (no hay flag).
- El programa se versiona por semana: `programa` (viva) vs **`programa_consolidado`** (snapshot semanal). El matching de v2 va contra `programa_consolidado WHERE Semana = MAX` y persiste **`unique_id`** (identidad de actividad estable ante reprogramaciones).
- **Fechas:** programación hacia atrás desde `Fecha_Inicio` de la actividad ancla, con duraciones por paso del catálogo global `general_dias_procesos_contratacion` (pasos configurables por proyecto). **No usar** `general_pdc_plantillas` (dropeada).
- Tablas nuevas: operativas con `project_id int NOT NULL` + índice liderado por `project_id` + `utf8mb4_unicode_ci`; catálogos `general_*` sin `project_id`; migraciones en `lps-aia/database/migrations/` (DDL `.sql`; backfills `.php` dry-run→`--apply`).
- **Verificación de BD por fase sobre el MySQL real de Docker (nunca mocks):** migraciones aplicadas, asserts de integridad en tests PHP, y gates `test_global_table_safety` + `test_global_table_reconciliation` en verde.

## Propósito del proyecto

Herramienta de **plan de compras** (procurement de obra) para **AIA – Arquitectos e Ingenieros Asociados**.

Es una **implementación global y multiproyecto**, no específica de un proyecto. *DAPORTO – Rionegro* es solo el proyecto de ejemplo con el que se corre el ejercicio; la meta es que sirva para todos los proyectos de AIA (aeropuerto, etc.) sobre un **maestro de insumos único** de la empresa.

## Relación con `lastplanneraia-construccion` (lps-aia)

Este repo **no es autónomo**: es la reimplementación (modelo nuevo, ver abajo) del módulo de **Plan de Compras (PDC)** que se integrará a la plataforma **Last Planner System de AIA**.

- **Repo destino:** `lastplanneraia-construccion` — GitHub `jbenite7/lastplanneraia-construccion`; en local es el repo hermano `../lps-aia`.
- **Qué es lps-aia:** app web PHP/MySQL madura de Last Planner System (planificación y control de obra). **Ya tiene un módulo PDC en producción** (SiteGround), construido sobre el **modelo viejo de "familias"** (`OperationalFamilyPolicy` en `src/Support/`, vista PDC en Handsontable, tabla `general_dias_procesos_contratacion`, automatización PDCA v4.0 de jun-2026). Este repo produce el **reemplazo** de ese módulo con el modelo revisado que elimina "familias".
- **Decisión de stack (2026-07-21, ver spec en `docs/superpowers/specs/`):** este repo desarrolla el **frontend** del módulo como SPA **React + Vite + AG Grid Community**; el "glue" PHP (vista shell, endpoints JSON, migraciones) se agrega a **lps-aia**. No cambies este reparto sin confirmarlo.
- **Documentos autoritativos de lps-aia** (léelos antes de decisiones de arquitectura, dominio o UI): `AGENTS.md` (contrato del repo), `GLOSARIO.md` (terminología LPS/Lean), `docs/VISTAS-MODULOS.md` (módulos de UI, incl. PDC), `docs/pdca-automatizacion-plan-compras.md` (histórico del PDC actual y duraciones por categoría), `docs/global-tables-architecture.md` (tablas globales por `project_id`), `docs/design-system/`.

## Flujo de negocio (modelo de dominio)

Entender esta cadena es clave para cualquier funcionalidad. Anatomía de un presupuesto:
`capítulos > subcapítulos > grupos > actividades`. Cada **actividad** tiene un **APU** (Análisis de Precios Unitarios) que la descompone en **insumos** (mano de obra, materiales, equipos, transporte, subcontratos), cada uno con tipo, unidad, cantidad (rendimiento × cantidad de actividad) y valor.

Flujo acordado (orden importante — refleja el "cambiazo" decidido en la reunión de 2026-07-16):

1. **Presupuesto** (Excel exportado del software de presupuestos) → es el punto de partida.
2. **Maestro de insumos**: lista única y normalizada de insumos para *todos* los proyectos de AIA (concepto tipo ERP que hoy no existe).
3. **Pareto de insumos**: consolida cada insumo sumando sus fracciones a lo largo de todas las actividades que lo usan → costo total por insumo. Habilita decisiones estratégicas de compra.
4. **Paquetes de contratación**: se agrupan insumos en paquetes (ej. suministro de acero, mano de obra estructura). Principio: *no se compran actividades, se compran/negocian insumos*. **Meta central: que el 100% de los insumos del presupuesto quede asignado a algún paquete** (que "no quede nada suelto").
5. **Plan de compras final** = paquetes **+ fechas**, obtenidas por *matching* contra el **cronograma** (amarrado por código, para que reprogramar el cronograma actualice fechas automáticamente). Cada paquete lleva una **duración** del proceso de contratación y un **responsable**.

**Cambio de enfoque importante (no reintroducir el modelo viejo):** antes el flujo agrupaba actividades en "familias" y las explotaba a insumos apoyándose en el cronograma. El modelo vigente parte del presupuesto → maestro de insumos → empaqueta insumos directamente → luego hace matching con el cronograma solo para las fechas. **El concepto de "familias" queda eliminado** (en lps-aia todavía vive como `OperationalFamilyPolicy`; aquí no se replica).

Detalles del proceso de contratación (heredados del PDC actual de lps-aia, útiles para las fechas): los pasos típicos son *Elaboración → (Envío pliegos / Licify / Aprobación cliente) → Entrega → Recibo → Cuadros → Legalización → Fabricación*; los pasos intermedios son **configurables por proyecto** (no hardcodear). Las **duraciones** varían por categoría de recurso (*A todo costo, Mano de obra, Equipos, Insumos*).

Conceptos adicionales:
- **Cuatro tipos de negociación** por paquete: (a) suministro e instalación ("a todo costo"), (b) mano de obra, (c) suministro (material / órdenes de compra), (d) consumibles. Contratar "suministro+instalación" bloquea los otros para ese alcance.
- **Ecosistema adyacente de AIA** (contexto, otras vistas de lps-aia): visor de presupuestos, visor de cronogramas, definición de alcance, matriz de riesgos, e integración futura con modelos **Revit/BIM** (matching por clasificación, no exacto).

## Stack técnico (decidido 2026-07-21 — spec: `docs/superpowers/specs/2026-07-21-stack-plan-de-compras-design.md`)

Arquitectura de **"isla moderna" dentro de lps-aia**, pensada para SiteGround hosting compartido (el build corre local/CI; al servidor solo llegan estáticos + PHP):

- **Este repo (`plan-de-compras`):** SPA **React + Vite + AG Grid Community** (MIT — no usar features Enterprise ni Handsontable, cuyo tier gratis es solo no-comercial). Vistas: importar presupuesto, maestro de insumos, Pareto, paquetes, plan final. Recibe contexto por `window.__PDC_BOOTSTRAP__` (projectId, proyectoNombre, usuario, rol, csrfToken) y consume los tokens `aia-*` de lps-aia. El build (`dist/`) se despliega a `lps-aia/public/pdc-app/` (nombre distinto de la ruta `/plan-compras` para no romper el ruteo de Apache).
- **En `../lps-aia` (glue PHP):** vista shell `views/plan-compras/app.view.php` tras `SessionMiddleware`; rutas y **endpoints JSON delgados** (`/plan-compras/api/...`, envelope `{ok,data|error}`) vía FastRoute con CSRF (form key `plan_compras_v2`) + RBAC (`lps.pdc.ver`); import de Excel con `phpoffice/phpspreadsheet`; tablas nuevas aisladas por `project_id` con migraciones en `database/migrations/` (ver `docs/global-tables-architecture.md`).
- **Testing:** Vitest (lógica SPA) y `npm run build` como gate aquí; en lps-aia, scripts `tests/test_pdc_*.php` autoejecutables (no hay PHPUnit), PHPStan, y e2e Playwright en `tests/browser/`.
- **Deploy:** rutina de lps-aia (`docs/siteground-deploy-routine.md`). Watch-items SiteGround: verificar `upload_max_filesize`/`post_max_size` ≥ 10M (límite del importador) y `memory_limit` de PhpSpreadsheet con presupuestos grandes — el parser usa `toArray()` sobre la hoja completa (read-only, medido OK a escala DAPORTO: parse 0.13s / confirmar 0.42s); migrar a lectura por chunks solo si un presupuesto real lo exige.

Comandos de lps-aia para la parte PHP/e2e (se ejecutan en `../lps-aia`):

```bash
docker compose up -d --build db app adminer   # levantar stack (app: localhost:8081, adminer: 8082)
docker compose exec app composer install
docker compose exec app php tests/test_global_table_safety.php   # correr un solo test PHP
npx playwright test tests/browser/full-app-flow.spec.mjs --workers=1
```

Comandos de este repo:

```bash
npm run dev     # Vite dev server con proxy /plan-compras/api → localhost:8081 (Docker de lps-aia)
npm run build   # tsc + vite build → dist/ con nombres fijos (assets/pdc.js, assets/pdc.css)
npm run test    # Vitest (src/lib/*.test.ts)
npm run sync    # build + copia dist/ a ../lps-aia/public/pdc-app/ (commitear allá: deploy = git pull)
```

## Materiales de referencia (locales, no versionados)

`docs/` está en `.gitignore`; son insumos de trabajo, no artefactos del repo:

- `102 - 2026 09 DAPORTO - RIONEGRO - PI_Version_3 (4).xlsx` — **presupuesto fuente**. Hoja `Presupuesto`; columnas clave: `Código, Descripción, Padre, UM, CANTIDAD, SUBCAPITULO, ID PROYECTO, VERSION, ID APU, Cant APU, Rend, IVA, VrUnit, Tipo Insumo, Agrupacion, ...`. Jerarquía por código (`01`, `01.01`, `01.01.01.01`).
- `pareto-insumos-...-DAPORTO-RIONEGRO-...xlsx` — **Pareto de insumos** ya procesado. Hoja `Insumos`; columnas: `Insumo, Unidad, Valor Total`. Es el punto de partida para armar los paquetes.
- `Innovación y Procesos.docx` + grabación `.mp4` — transcripción/notas de la reunión donde se define el flujo.

## Convenciones

- **Idioma:** el proyecto y su dominio son en español. Documentación y comentarios en español; identificadores de código, rutas y comandos en su idioma original.
- Preserva la terminología de dominio del equipo (confírmala en `lps-aia/GLOSARIO.md`): *maestro de insumos*, *Pareto de insumos*, *paquetes de contratación*, *APU*, *plan de compras (PDC)*.
- `.omo/` (continuaciones de sesión), `.claude/`, `docs/`, `.DS_Store` y `*.mp4` están ignorados y no deben versionarse.
