# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Estado actual

Rama en curso: **Fases A1, A1.5, A1.6, A1.7, A2, A2.5, A3, A3.1 y A3.2 implementadas** (+ fix **A1.8**: el importador ignoraba `Cant APU` e inflaba el valor de los insumos → corregido a `Cant APU × Rend × cantidad`) — importador de presupuesto, visor en árbol; comparativo de versiones (`#/ensamble/comparar`): diff por actividad (jerárquico) e insumo (Pareto), sobrecostos vs ahorros; endpoint `GET /plan-compras/api/presupuesto/comparar`, sin migraciones; versionamiento inteligente del importador (`#/ensamble/importar`): auto-numeración (Versión N · fecha) por proyecto, anti-duplicado por hash de contenido vs la versión activa, y resumen del auto-comparativo tras cargar (reusa A1.6); maestro de insumos global (auto-match + cola de pendientes) y **importador del maestro SINCO** (siembra `general_maestro_insumos` con 3.088 insumos: código, agrupación, tipo de recurso, valor), y **paquetes de contratación** (`#/ensamble/paquetes`): catálogo global `general_paquetes_contratacion` **sembrado con los 188 paquetes reales de AIA** (extraídos del bundle de la app de Tomás; 107 a-todo-costo / 53 suministro / 28 mano-de-obra) + asignación por proyecto `pdc_insumo_paquete` (un insumo un destino — paquete u **omitido**; herencia en re-import), con motor de sugerencias cross-proyecto (exacta/tokens/agrupación SINCO + candidatos filtrados por tipo de recurso, confirmación humana), **grilla masiva y asistente paso a paso** (orden Pareto), y cobertura hacia el 100%; RBAC `lps.paquetes_contratacion.ver/editar`. Todo bajo la navegación Ensamble | Seguimiento. Verificado con Vitest, tests PHP autoejecutables y e2e Playwright. En detalle: importador de presupuesto (preview→confirmar, versionado con única activa, todo-o-nada) sobre 3 tablas `pdc_presupuesto_*` en lps-aia con RBAC `lps.pdc.importar`, visor del presupuesto en árbol jerárquico con selector de versión (`#/ensamble/presupuesto`), y maestro de insumos global (`#/ensamble/maestro`) con RBAC `lps.pdc.maestro`: cola de vínculos pendientes por versión con selección múltiple y creación masiva (cold start), vinculación individual con sugerencias por similitud, y catálogo único de insumos (`general_maestro_insumos`) con búsqueda — auto-match idempotente en cada re-import. Follow-ups del review final A2 aplicados: tolerancia a errno 1062 (carrera/colisión de prefijo → vincula al existente), upsert de vínculos en lotes multi-fila, comodines LIKE escapados, y retiro/reactivación de insumos del catálogo (`activo=0` con reversión global del auto-match, auditoría `actualizado_por`/`updated_at` y UI en el catálogo). Verificado con Vitest (28 tests), tests PHP autoejecutables (RBAC, parser, flujo BD, árbol, maestro, import SINCO) y e2e Playwright (import, fundación, visor, maestro e import del maestro SINCO).

### A3.2 — Modalidad de contratación (4 modalidades)

Dimensión **ortogonal** a `tipo_negociacion` (columna `general_paquetes_contratacion.modalidad_contratacion`,
enum, default `contrato`): el tipo dice *qué* se compra, la modalidad dice **cómo**, que es lo que decide si el
paquete genera proceso y fecha en A4.

| Modalidad | Qué es | ¿Entra al plan de fechas? |
|---|---|---|
| `contrato` | Alcance cerrado con un proveedor | Sí, proceso completo |
| `orden_compra` | Commodity recurrente (concreto, acero, cemento, agregados…): cambia de proveedor en el tiempo | Solo la **primera entrega** — garantizar el arranque de la actividad; el histórico por proveedor/cuantía es del módulo de Seguimiento |
| `consumo_directo` | Ferretería y consumibles pedidos a necesidad contra almacén | No — se controla el gasto, no se contrata |
| `no_contratable` | Nómina propia, imprevistos y provisiones | No — no se le compran a nadie |

El bucket único «Indirectos / Administración» de A3.1 se partió por naturaleza en **Nómina de obra**,
**Imprevistos y provisiones** (ambos `no_contratable`) y **Ferretería y consumibles de obra**
(`consumo_directo`), para que no contaminen cobertura ni semáforos. Migraciones
`20260725_pdc_v2_modalidad_contratacion.php` (DDL, cero regresión: los 202 paquetes existentes quedan en
`contrato`) y `20260725_pdc_v2_backfill_modalidades.php` (11 paquetes a `orden_compra`, respaldados por el
catálogo legacy `general_dias_procesos_contratacion`, que ya marcaba ACERO/CONCRETO como orden de compra con
ciclos propios). La SPA la ofrece al crear paquetes y la pinta como badge **solo cuando no es `contrato`**.
Reparto real en DAPORTO v292 (325 asignados, 82,1 % de cobertura): contrato 59,26 % · orden de compra 24,55 % ·
no contratable 12,99 % · consumo directo 0,98 %.

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
`capítulos > subcapítulos > grupos > actividades`. Cada **actividad** tiene un **APU** (Análisis de Precios Unitarios) que la descompone en **insumos** (mano de obra, materiales, equipos, transporte, subcontratos), cada uno con tipo, unidad, cantidad (**Cant APU × rendimiento × cantidad de actividad** — el coeficiente de consumo vive en `Cant APU`; en el export real de AIA `Rend` es 1. Ver fix A1.8: omitir `Cant APU` inflaba los insumos de coeficiente pequeño y el costo total) y valor.

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

### Worktree dedicado en lps-aia (sesiones paralelas)

El working tree principal de lps-aia (`../lps-aia`) lo comparten otras sesiones activas (indicadores/Power BI, design-system/sidebar) que lo dejan con cambios sin commitear y bloquean checkouts/merges. Por eso **el trabajo PDC en lps-aia se hace en un git worktree dedicado**, no en el principal:

- **Worktree PDC:** `/Volumes/Crucial X6/Developer/lps-aia-pdc`, rama base `pdc-dev` (desde `main`). Crear ahí las ramas de feature por fase (`git checkout -b pdc-a3-paquetes`, etc.). NO trabajar PDC en `../lps-aia` (es de las otras sesiones); tampoco tocar `../lps-aia/.claude/worktrees/lab-preview` (locked, ajeno).
- **Docker:** el `docker-compose.override.yml` (versionado) monta `./` (relativo), así que `docker compose` **desde el worktree** monta el código del worktree. Levantar el stack del worktree con `COMPOSE_PROJECT_NAME` y puertos propios para no chocar con el principal (app `8081` es fijo en `docker-compose.yml`; db/adminer son `${DOCKER_DB_PORT:-3307}`/`${DOCKER_ADMINER_PORT:-8082}`).
- **Integración:** consolidar en `origin/main` (decisión 2026-07-23; ya no la rama `desarrollo-pdc-v2`). Antes de mergear: `git fetch` y FF `main` a `origin/main` — las sesiones ajenas pushean seguido, así que main avanza en horas. Si el principal está bloqueado, mergear vía worktree temporal aislado.

Comandos de lps-aia para la parte PHP/e2e (se ejecutan en el **worktree** `/Volumes/Crucial X6/Developer/lps-aia-pdc`):

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
                # OJO: apunta a ../lps-aia (principal). Trabajando en el worktree, copiar el bundle
                # a /Volumes/Crucial X6/Developer/lps-aia-pdc/public/pdc-app/ y commitear en la rama del worktree.
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
