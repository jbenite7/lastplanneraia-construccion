---
capa: fuente
tipo: spec
estado: vigente
fecha: 2026-07-21
areas: [proceso]
fuente: docs/superpowers/specs/2026-07-21-stack-plan-de-compras-design.md
resumen: El módulo de plan de compras (PDC) de AIA se reimplementa con el modelo nuevo (presupuesto → maestro de insumos → Pareto → paquetes de contratación → matching…
---

# Diseño: stack del módulo Plan de Compras (PDC v2)

**Fecha:** 2026-07-21
**Estado:** aprobado en brainstorming (pendiente revisión final del spec)
**Alcance:** decisión de stack y arquitectura de integración. El modelo de datos detallado y las especificaciones funcionales de cada vista son specs posteriores.

## Contexto

El módulo de **plan de compras** (PDC) de AIA se reimplementa con el modelo nuevo (presupuesto → maestro de insumos → Pareto → paquetes de contratación → matching de fechas contra el cronograma; **se elimina el concepto de "familias"**). El PDC actual vive en producción dentro de **lps-aia** (`lastplanneraia-construccion`): PHP 8.3 + MySQL + FastRoute + Handsontable, desplegado en **SiteGround hosting compartido**.

Requisitos que motivaron replantear el stack:

- Frontend **más potente y moderno** que el paradigma actual (Handsontable + JS vanilla sin build step).
- Stack del módulo **más liviano** que el de lps-aia.
- **Integración fundamental** con lps-aia (usuarios, sesión, proyectos, datos).
- Restricción dura: **correr sin fricción en SiteGround hosting compartido**.

## Decisiones

1. **El módulo es parte de lps-aia.** Reusa sesión/login, RBAC, proyectos, MySQL con tablas globales aisladas por `project_id`, design system y la rutina de deploy a SiteGround. No se crea un backend paralelo.
2. **Frontend = "isla moderna": React + Vite + AG Grid Community.**
   - AG Grid **Community** es MIT (gratis para uso comercial) y cubre grilla editable, orden, filtro, selección, export CSV.
   - Se descartó Handsontable: su tier gratis es *non-commercial-and-evaluation*, inválido para uso comercial. (lps-aia hoy lo usa con esa licencia — riesgo preexistente, anotado como tarea aparte; no bloquea este diseño.)
   - AG Grid **Enterprise** (agrupación con agregación, pivote) solo si se vuelve imprescindible; mientras tanto las agregaciones se calculan en el backend.
3. **El frontend se desarrolla en el repo `plan-de-compras`**; el "glue" PHP (vista shell, endpoints, migraciones) se agrega a **lps-aia**. El build de Vite produce estáticos que se despliegan a `public/plan-compras/` de lps-aia.
4. **SiteGround queda satisfecho por diseño:** el build corre local/CI, nunca en el servidor. A SiteGround solo llegan estáticos compilados + PHP, exactamente lo que ya sirve hoy.

## Arquitectura

### Componentes

**Repo `plan-de-compras` — SPA (React + Vite + **TypeScript** + AG Grid Community):**
- Vistas: importar presupuesto, maestro de insumos, Pareto de insumos, paquetes de contratación, plan de compras final (fechas). React Router interno; todo bajo una única ruta de lps-aia.
- Recibe contexto vía `window.__PDC_BOOTSTRAP__` (inyectado por el shell PHP): `project_id`, rol RBAC, token CSRF, base URL de la API.
- Respeta el design system de lps-aia: consume los tokens `aia-*` (`public/css/tokens.css`); alcance visual según su contrato (dark, desktop ≥1180px).
- Salida de build: `dist/` con assets con hash de contenido.

**Repo `lps-aia` — glue PHP:**
- **Shell:** `views/plan-compras/app.view.php`, detrás de `SessionMiddleware`; renderiza `<div id="root">`, inyecta el bootstrap y referencia el bundle.
- **Rutas:** vista + API registradas en `public/index.php` (grupo del módulo) vía `App\Core\Router` (FastRoute).
- **API JSON delgada** (patrón `Controllers/` + `Services/`, mantenida lean):
  - `GET  /pdc/api/insumos` — maestro/Pareto por `project_id`
  - `POST /pdc/api/presupuesto/import` — Excel → PhpSpreadsheet (dependencia ya presente)
  - CRUD `/pdc/api/paquetes` y `/pdc/api/asignaciones` (insumo↔paquete)
  - `GET  /pdc/api/cronograma/match` — fechas por matching de código
  - Todos: sesión + `CsrfTokenManager` + capacidades de `RbacManager`.
- **Persistencia:** tablas nuevas (maestro de insumos global, paquetes, asignaciones) según `docs/global-tables-architecture.md`, con migraciones en `database/migrations/`.

**Reuso existente en lps-aia:** `src/Core/SessionMiddleware.php`, `src/Security/CsrfTokenManager.php`, `src/Security/RbacManager.php` + `RbacCatalog.php`, `src/Core/Router.php`, `src/Core/Database.php` (PDO prepared statements), `phpoffice/phpspreadsheet`, `public/css/tokens.css`, `docs/siteground-deploy-routine.md`.

### Flujo de datos

1. Usuario navega a la ruta del módulo en lps-aia → `SessionMiddleware` valida sesión → shell PHP inyecta bootstrap y carga la SPA.
2. La SPA llama a la API JSON con el token CSRF; el backend resuelve todo por `project_id`.
3. Importación: la SPA sube el Excel del presupuesto → PhpSpreadsheet lo procesa server-side → puebla/actualiza maestro de insumos y Pareto.
4. El usuario arma paquetes y asigna insumos (meta: 100% de insumos asignados); el matching contra el cronograma produce las fechas del plan.

### Manejo de errores

- **API:** envelope JSON uniforme (`{ok, data|error}`) con códigos HTTP correctos; la SPA muestra errores accionables en la UI (sin `alert()`).
- **Sesión expirada / CSRF inválido:** la API responde 401/419; la SPA detecta y redirige al login de lps-aia.
- **Import de Excel:** validación de estructura (hoja `Presupuesto`, columnas requeridas) con reporte de errores por fila; import transaccional (todo o nada) para no dejar el maestro a medias.
- **Archivos grandes:** watch-item de `memory_limit` en SiteGround; mitigación con lectura por chunks/filtros de PhpSpreadsheet. Validar con un presupuesto real en staging.

### Testing

- **Frontend:** `npm run build` sin errores como gate mínimo; Vitest para la lógica de la SPA (transformaciones de datos, estado).
- **Backend:** scripts autoejecutables `tests/test_pdc_*.php` (patrón de lps-aia, sin PHPUnit) para import y endpoints; PHPStan sobre el PHP nuevo.
- **E2E:** spec de Playwright en `tests/browser/` de lps-aia: login → importar presupuesto → ver Pareto → crear paquete → asignar insumos → ver fechas. Corre contra el stack Docker local.
- **Deploy de prueba:** staging en SiteGround siguiendo `siteground-deploy-routine.md` antes de producción.

## Fuera de alcance de este spec

- Modelo de datos detallado (columnas, índices, normalización del maestro de insumos).
- Especificación funcional de cada vista (UX, columnas de grilla, validaciones de negocio).
- Migración/retiro del PDC viejo de "familias" en lps-aia.
- Resolución de la licencia non-commercial de Handsontable en lps-aia (tarea aparte).

## Riesgos anotados

| Riesgo | Mitigación |
|---|---|
| Memoria de PhpSpreadsheet con presupuestos grandes en SiteGround | Lectura por chunks; validar en staging con archivo real |
| Features de agrupación/pivote resultan imprescindibles | Migrar a AG Grid Enterprise (costo por dev/año); las agregaciones viven en el backend mientras tanto |
| Divergencia visual entre la SPA y el design system de lps-aia | Consumir `tokens.css` directamente; revisar contra `DESIGN.md` antes de cada vista nueva |

## Cierre

**Ejecutado.** El stack decidido aquí (React + Vite + AG Grid Community como "isla moderna",
glue PHP en `lps-aia`, build a estáticos sin fricción en SiteGround) está en producción: `pdc-app/`
y `src/Services/Pdc/` en el árbol actual, documentado en [[docs/pdc-v2]].

La brecha que dejaba esta spec abierta era solo documental: por qué el módulo se **unificó** dentro
de `lps-aia` en vez de vivir en el repo separado `plan-de-compras` que esta misma spec proponía en
su decisión 3. La respuesta ya estaba escrita: [[docs/superpowers/specs/2026-07-29-unificacion-repos-design]]
(2026-07-29, decidida en grilleo con el usuario) documenta el motivo medido — un bundle publicado
(`public/pdc-app/assets/pdc.js`) cuya fuente no viajó con él porque compilar y publicar eran dos
repos distintos, y seis decisiones (SPA en `pdc-app/` con `package.json` propio, historial
conservado con `git subtree`, documentación fusionada, build directo a `public/pdc-app/`,
conocimiento en `docs/pdc-v2.md`, repo viejo archivado) que reemplazan la arquitectura de dos repos
de esta spec por la unificada de hoy. Verificado en el árbol actual: `pdc-app/` existe con su propio
`package.json`, y `public/pdc-app/` es el destino directo del build.

No se requirió grilleo nuevo para este cierre: la decisión ya estaba tomada y publicada: solo faltaba
citarla aquí.
